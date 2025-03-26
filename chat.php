<?php
// Iniciar sesión para verificar que el usuario está autenticado
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    // Redirigir al login si no está autenticado
    header('Location: login.php');
    exit;
}

// Obtener información del usuario actual
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Usuario';

// Incluir el archivo de conexión a la base de datos
require_once 'db_connect.php';
// Asumiendo que bd.php define una variable $conn con la conexión PDO

// Obtener todos los usuarios excepto el actual
try {
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id != ?");
    $stmt->execute([$userId]);
    $users = $stmt->fetchAll();
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
            <!-- Lista de usuarios -->
            <div class="user-list" id="userList">
                <div class="p-3 bg-light">
                    <h5>Usuarios</h5>
                </div>
                <div id="userListItems">
                    <!-- Los usuarios se cargarán dinámicamente aquí -->
                    <div class="text-center p-3">Cargando usuarios...</div>
                </div>
            </div>
            
            <!-- Área de chat -->
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

    <!-- JavaScript para manejar el WebSocket y la interfaz de chat -->
    <script>
        // Datos del usuario actual
        const currentUserId = <?php echo $userId; ?>;
        const sessionId = "<?php echo session_id(); ?>";
        
        // Variables para el chat
        let socket;
        let selectedUserId = null;
        let users = [];
        
        // Elementos del DOM
        const userListItems = document.getElementById('userListItems');
        const messageContainer = document.getElementById('messageContainer');
        const messageForm = document.getElementById('messageForm');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        
        // Inicializar la conexión WebSocket
        function initWebSocket() {
            // Crear una nueva conexión WebSocket
            socket = new WebSocket('ws://localhost:8080');
            
            // Evento cuando se abre la conexión
            socket.onopen = function(event) {
                console.log('Conexión WebSocket establecida');
                
                // Autenticar al usuario
                sendToServer({
                    type: 'auth',
                    user_id: currentUserId,
                    session_id: sessionId
                });
            };
            
            // Evento cuando se recibe un mensaje
            socket.onmessage = function(event) {
                const data = JSON.parse(event.data);
                console.log('Mensaje recibido:', data);
                
                // Manejar diferentes tipos de mensajes
                switch (data.type) {
                    case 'auth_success':
                        // Autenticación exitosa, solicitar lista de usuarios
                        sendToServer({ type: 'get_users' });
                        break;
                        
                    case 'user_list':
                        // Actualizar la lista de usuarios
                        users = data.users;
                        renderUserList();
                        break;
                        
                    case 'user_status':
                        // Actualizar el estado de un usuario
                        updateUserStatus(data.user_id, data.online);
                        break;
                        
                    case 'chat_history':
                        // Mostrar el historial de chat
                        renderChatHistory(data.messages);
                        break;
                        
                    case 'new_message':
                        // Mostrar un nuevo mensaje
                        if (
                            (data.sender_id === currentUserId && data.receiver_id === selectedUserId) ||
                            (data.sender_id === selectedUserId && data.receiver_id === currentUserId)
                        ) {
                            appendMessage(data);
                        }
                        
                        // Si el mensaje es de alguien con quien no estamos chateando, actualizar indicador
                        if (data.sender_id !== currentUserId && data.sender_id !== selectedUserId) {
                            markUserWithUnreadMessage(data.sender_id);
                        }
                        break;
                        
                    case 'unread_messages':
                        // Procesar mensajes no leídos
                        processUnreadMessages(data.messages);
                        break;
                        
                    case 'error':
                        // Mostrar error
                        console.error('Error del servidor:', data.message);
                        break;
                }
            };
            
            // Evento cuando ocurre un error
            socket.onerror = function(error) {
                console.error('Error de WebSocket:', error);
            };
            
            // Evento cuando se cierra la conexión
            socket.onclose = function(event) {
                console.log('Conexión WebSocket cerrada');
                
                // Intentar reconectar después de un tiempo
                setTimeout(function() {
                    console.log('Intentando reconectar...');
                    initWebSocket();
                }, 5000);
            };
        }
        
        // Enviar datos al servidor WebSocket
        function sendToServer(data) {
            if (socket && socket.readyState === WebSocket.OPEN) {
                socket.send(JSON.stringify(data));
            } else {
                console.error('WebSocket no está conectado');
            }
        }
        
        // Renderizar la lista de usuarios
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
        
        // Seleccionar un usuario para chatear
        function selectUser(userId) {
            // Deseleccionar el usuario anterior
            const previousUserItem = document.querySelector(`.user-item.active`);
            if (previousUserItem) {
                previousUserItem.classList.remove('active');
            }
            
            // Seleccionar el nuevo usuario
            selectedUserId = userId;
            const userItem = document.querySelector(`.user-item[data-user-id="${userId}"]`);
            if (userItem) {
                userItem.classList.add('active');
                
                // Ocultar el badge de mensajes no leídos
                const unreadBadge = userItem.querySelector('.unread-badge');
                if (unreadBadge) {
                    unreadBadge.style.display = 'none';
                }
            }
            
            // Habilitar el campo de entrada de mensajes
            messageInput.disabled = false;
            sendButton.disabled = false;
            messageInput.focus();
            
            // Limpiar el contenedor de mensajes
            messageContainer.innerHTML = '<div class="text-center p-3">Cargando mensajes...</div>';
            
            // Solicitar el historial de chat
            sendToServer({
                type: 'get_history',
                other_user_id: userId
            });
        }
        
        // Actualizar el estado de un usuario (en línea/desconectado)
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
        
        // Renderizar el historial de chat
        function renderChatHistory(messages) {
            messageContainer.innerHTML = '';
            
            if (messages.length === 0) {
                messageContainer.innerHTML = '<div class="text-center p-3">No hay mensajes. ¡Sé el primero en escribir!</div>';
                return;
            }
            
            messages.forEach(message => {
                appendMessage(message);
            });
            
            // Desplazarse al último mensaje
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }
        
        // Añadir un mensaje al contenedor
        function appendMessage(message) {
            const isSent = message.sender_id === currentUserId;
            const messageElement = document.createElement('div');
            messageElement.className = `message ${isSent ? 'sent' : 'received'}`;
            
            // Formatear la fecha
            const timestamp = new Date(message.timestamp);
            const formattedTime = timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            messageElement.innerHTML = `
                <div class="message-content">${escapeHtml(message.message)}</div>
                <div class="message-time">${formattedTime}</div>
            `;
            
            messageContainer.appendChild(messageElement);
            
            // Desplazarse al último mensaje
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }
        
        // Procesar mensajes no leídos
        function processUnreadMessages(messages) {
            // Agrupar mensajes por remitente
            const messagesBySender = {};
            
            messages.forEach(message => {
                const senderId = message.sender_id;
                
                if (!messagesBySender[senderId]) {
                    messagesBySender[senderId] = [];
                }
                
                messagesBySender[senderId].push(message);
            });
            
            // Actualizar los badges de mensajes no leídos
            for (const senderId in messagesBySender) {
                markUserWithUnreadMessage(senderId, messagesBySender[senderId].length);
            }
        }
        
        // Marcar un usuario con mensajes no leídos
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
        
        // Escapar HTML para prevenir XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Manejar el envío de mensajes
        messageForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const message = messageInput.value.trim();
            if (!message || !selectedUserId) {
                return;
            }
            
            // Enviar el mensaje al servidor
            sendToServer({
                type: 'message',
                receiver_id: selectedUserId,
                message: message
            });
            
            // Limpiar el campo de entrada
            messageInput.value = '';
            messageInput.focus();
        });
        
        // Iniciar la conexión WebSocket cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            initWebSocket();
        });
    </script>
</body>
</html>