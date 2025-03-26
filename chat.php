<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Usuario';

require_once 'db_connect.php';

try {
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id != ?");
    $stmt->execute([$userId]);
    $users = [];
    $stmt->bind_result($id, $username);
    while ($stmt->fetch()) {
        $users[] = ['id' => $id, 'username' => $username];
    }
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - ShareMyGym</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        .chat-container {
            height: calc(100vh - 120px);
            display: flex;
        }
        .user-list {
            width: 250px;
            overflow-y: auto;
            border-right: 1px solid #dee2e6;
        }
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .message-container {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }
        .message-input {
            border-top: 1px solid #dee2e6;
            padding: 15px;
        }
        .user-item {
            padding: 10px 15px;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
        }
        .user-item:hover {
            background-color: #f8f9fa;
        }
        .user-item.active {
            background-color: #e9ecef;
        }
        .message {
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 18px;
            max-width: 75%;
            position: relative;
        }
        .message.sent {
            background-color: #dcf8c6;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        .message.received {
            background-color: #f1f0f0;
            margin-right: auto;
            border-bottom-left-radius: 5px;
        }
        .message-time {
            font-size: 0.7rem;
            color: #6c757d;
            margin-top: 2px;
            text-align: right;
        }
        .online-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .online {
            background-color: #28a745;
        }
        .offline {
            background-color: #dc3545;
        }
        .no-chat-selected {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-3">
        <div class="row mb-3">
            <div class="col">
                <h2>Chat de ShareMyGym</h2>
                <p>Conectado como: <strong><?php echo htmlspecialchars($username); ?></strong></p>
            </div>
        </div>
        
        <div class="chat-container">
            <div class="user-list" id="userList">
                <div class="p-3 bg-light">
                    <h5>Usuarios</h5>
                </div>
                <div id="userListItems">
                    <div class="text-center p-3">Cargando usuarios...</div>
                </div>
            </div>
            
            <div class="chat-area">
                <div id="messageContainer" class="message-container">
                    <div class="no-chat-selected">
                        <p>Selecciona un usuario para comenzar a chatear</p>
                    </div>
                </div>
                
                <div class="message-input">
                    <form id="messageForm" class="d-flex">
                        <input type="text" id="messageInput" class="form-control me-2" placeholder="Escribe un mensaje..." disabled>
                        <button type="submit" id="sendButton" class="btn btn-primary" disabled>Enviar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo $userId; ?>;
        const sessionId = "<?php echo session_id(); ?>";
        
        let socket;
        let selectedUserId = null;
        let users = [];
        
        const userListItems = document.getElementById('userListItems');
        const messageContainer = document.getElementById('messageContainer');
        const messageForm = document.getElementById('messageForm');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        
        function initWebSocket() {
            socket = new WebSocket('ws://localhost:8080');
            
            socket.onopen = function(event) {
                console.log('Conexión WebSocket establecida');
                
                sendToServer({
                    type: 'auth',
                    user_id: currentUserId,
                    session_id: sessionId
                });
            };
            
            socket.onmessage = function(event) {
                const data = JSON.parse(event.data);
                console.log('Mensaje recibido:', data);
                
                switch (data.type) {
                    case 'auth_success':
                        sendToServer({ type: 'get_users' });
                        break;
                        
                    case 'user_list':
                        users = data.users;
                        renderUserList();
                        break;
                        
                    case 'user_status':
                        updateUserStatus(data.user_id, data.online);
                        break;
                        
                    case 'chat_history':
                        renderChatHistory(data.messages);
                        break;
                        
                    case 'new_message':
                        if (
                            (data.sender_id === currentUserId && data.receiver_id === selectedUserId) ||
                            (data.sender_id === selectedUserId && data.receiver_id === currentUserId)
                        ) {
                            appendMessage(data);
                        }
                        
                        if (data.sender_id !== currentUserId && data.sender_id !== selectedUserId) {
                            markUserWithUnreadMessage(data.sender_id);
                        }
                        break;
                        
                    case 'unread_messages':
                        processUnreadMessages(data.messages);
                        break;
                        
                    case 'error':
                        console.error('Error del servidor:', data.message);
                        break;
                }
            };
            
            socket.onerror = function(error) {
                console.error('Error de WebSocket:', error);
            };
            
            socket.onclose = function(event) {
                console.log('Conexión WebSocket cerrada');
                
                setTimeout(function() {
                    console.log('Intentando reconectar...');
                    initWebSocket();
                }, 5000);
            };
        }
        
        function sendToServer(data) {
            if (socket && socket.readyState === WebSocket.OPEN) {
                socket.send(JSON.stringify(data));
            } else {
                console.error('WebSocket no está conectado');
            }
        }
        
        function renderUserList() {
            userListItems.innerHTML = '';
            
            if (users.length === 0) {
                userListItems.innerHTML = '<div class="text-center p-3">No hay usuarios disponibles</div>';
                return;
            }
            
            users.forEach(user => {
                const userItem = document.createElement('div');
                userItem.className = 'user-item';
                userItem.dataset.userId = user.id;
                
                if (selectedUserId === user.id) {
                    userItem.classList.add('active');
                }
                
                const onlineStatus = user.online ? 'online' : 'offline';
                
                userItem.innerHTML = `
                    <span class="online-indicator ${onlineStatus}"></span>
                    ${user.username}
                    <span class="unread-badge badge bg-danger rounded-pill float-end" style="display: none;">0</span>
                `;
                
                userItem.addEventListener('click', function() {
                    selectUser(user.id);
                });
                
                userListItems.appendChild(userItem);
            });
        }
        
        function selectUser(userId) {
            const previousUserItem = document.querySelector(`.user-item.active`);
            if (previousUserItem) {
                previousUserItem.classList.remove('active');
            }
            
            selectedUserId = userId;
            const userItem = document.querySelector(`.user-item[data-user-id="${userId}"]`);
            if (userItem) {
                userItem.classList.add('active');
                
                const unreadBadge = userItem.querySelector('.unread-badge');
                if (unreadBadge) {
                    unreadBadge.style.display = 'none';
                }
            }
            
            messageInput.disabled = false;
            sendButton.disabled = false;
            messageInput.focus();
            
            messageContainer.innerHTML = '<div class="text-center p-3">Cargando mensajes...</div>';
            
            sendToServer({
                type: 'get_history',
                other_user_id: userId
            });
        }
        
        function updateUserStatus(userId, online) {
            const user = users.find(u => u.id === userId);
            if (user) {
                user.online = online;
            }
            
            const userItem = document.querySelector(`.user-item[data-user-id="${userId}"]`);
            if (userItem) {
                const indicator = userItem.querySelector('.online-indicator');
                if (indicator) {
                    indicator.className = `online-indicator ${online ? 'online' : 'offline'}`;
                }
            }
        }
        
        function renderChatHistory(messages) {
            messageContainer.innerHTML = '';
            
            if (messages.length === 0) {
                messageContainer.innerHTML = '<div class="text-center p-3">No hay mensajes. ¡Sé el primero en escribir!</div>';
                return;
            }
            
            messages.forEach(message => {
                appendMessage(message);
            });
            
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }
        
        function appendMessage(message) {
            const isSent = message.sender_id === currentUserId;
            const messageElement = document.createElement('div');
            messageElement.className = `message ${isSent ? 'sent' : 'received'}`;
            
            const timestamp = new Date(message.timestamp);
            const formattedTime = timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            messageElement.innerHTML = `
                <div class="message-content">${escapeHtml(message.message)}</div>
                <div class="message-time">${formattedTime}</div>
            `;
            
            messageContainer.appendChild(messageElement);
            
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }
        
        function processUnreadMessages(messages) {
            const messagesBySender = {};
            
            messages.forEach(message => {
                const senderId = message.sender_id;
                
                if (!messagesBySender[senderId]) {
                    messagesBySender[senderId] = [];
                }
                
                messagesBySender[senderId].push(message);
            });
            
            for (const senderId in messagesBySender) {
                markUserWithUnreadMessage(senderId, messagesBySender[senderId].length);
            }
        }
        
        function markUserWithUnreadMessage(userId, count = 1) {
            const userItem = document.querySelector(`.user-item[data-user-id="${userId}"]`);
            if (userItem) {
                const unreadBadge = userItem.querySelector('.unread-badge');
                if (unreadBadge) {
                    const currentCount = parseInt(unreadBadge.textContent) || 0;
                    unreadBadge.textContent = currentCount + count;
                    unreadBadge.style.display = 'inline';
                }
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        messageForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const message = messageInput.value.trim();
            if (!message || !selectedUserId) {
                return;
            }
            
            sendToServer({
                type: 'message',
                receiver_id: selectedUserId,
                message: message
            });
            
            messageInput.value = '';
            messageInput.focus();
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            initWebSocket();
        });
    </script>
</body>
</html>