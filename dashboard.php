<?php
session_start();
require_once 'db_connect.php'; // Archivo para conectar a la base de datos


// Verificar si el usuario está conectado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Obtener el ID del usuario
$userId = $_SESSION['user_id'];

// Manejar el envío de comentarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'])) {
    $postId = $_POST['post_id'];
    $commentText = $_POST['comment_text'];
    $commentQuery = $conn->prepare("INSERT INTO comments (user_id, post_id, comment_text) VALUES (?, ?, ?)");
    $commentQuery->bind_param("iis", $userId, $postId, $commentText);
    $commentQuery->execute();
}

// Manejar el envío de likes
if (isset($_GET['like_post_id'])) {
    $likePostId = $_GET['like_post_id'];

    // Verificar si ya le dio like a la publicación
    $checkLikeQuery = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $checkLikeQuery->bind_param("ii", $userId, $likePostId);
    $checkLikeQuery->execute();
    $likeResult = $checkLikeQuery->get_result();

    if ($likeResult->num_rows > 0) {
        // Si ya le dio like, eliminar el like
        $removeLikeQuery = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $removeLikeQuery->bind_param("ii", $userId, $likePostId);
        $removeLikeQuery->execute();
    } else {
        // Si no le dio like, agregar el like
        $likeQuery = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $likeQuery->bind_param("ii", $userId, $likePostId);
        $likeQuery->execute();
    }
}

// Manejar el envío de likes a los comentarios
if (isset($_GET['like_comment_id'])) {
    $likeCommentId = $_GET['like_comment_id'];

    // Verificar si el usuario ya le dio like al comentario
    $checkLikeQuery = $conn->prepare("SELECT id FROM comment_likes WHERE user_id = ? AND comment_id = ?");
    $checkLikeQuery->bind_param("ii", $userId, $likeCommentId);
    $checkLikeQuery->execute();
    $likeResult = $checkLikeQuery->get_result();

    if ($likeResult->num_rows > 0) {
        // Si ya le dio like, eliminar el like
        $removeLikeQuery = $conn->prepare("DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?");
        $removeLikeQuery->bind_param("ii", $userId, $likeCommentId);
        $removeLikeQuery->execute();
    } else {
        // Si no le dio like, agregar el like
        $likeQuery = $conn->prepare("INSERT INTO comment_likes (user_id, comment_id) VALUES (?, ?)");
        $likeQuery->bind_param("ii", $userId, $likeCommentId);
        $likeQuery->execute();
    }
}

// Manejar el envío de eliminación de comentarios
if (isset($_GET['delete_comment_id'])) {
    $deleteCommentId = $_GET['delete_comment_id'];

    // Verificar si el comentario pertenece a la sesión del usuario o si es el autor de la publicación
    $deleteCommentQuery = $conn->prepare("
        DELETE FROM comments 
        WHERE id = ? 
        AND (user_id = ? OR post_id IN (SELECT id FROM posts WHERE user_id = ?))
    ");
    $deleteCommentQuery->bind_param("iii", $deleteCommentId, $userId, $userId);
    $deleteCommentQuery->execute();
}

// Obtener perfiles recomendados a seguir
$recommendedQuery = $conn->prepare("
    SELECT DISTINCT u.id, u.username, u.profile_picture
    FROM users u
    JOIN friends f ON f.friend_id = u.id
    WHERE f.user_id IN (SELECT friend_id FROM friends WHERE user_id = ?)
    AND u.id != ?  -- Excluir al propio usuario
    LIMIT 3
");
$recommendedQuery->bind_param("ii", $userId, $userId);
$recommendedQuery->execute();
$recommendedResult = $recommendedQuery->get_result();


// Obtener publicaciones del usuario y de sus amigos
$query = $conn->prepare("
    SELECT posts.id, posts.content, posts.created_at, users.username, users.profile_picture, posts.user_id, posts.image_url, posts.video_url,
           (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) AS like_count
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    WHERE users.id = ? OR users.id IN (
        SELECT friend_id FROM friends WHERE user_id = ?
    )
    ORDER BY posts.created_at DESC
");
$query->bind_param("ii", $userId, $userId);
$query->execute();
$result = $query->get_result();


// Obtener datos del usuario
$userQuery = $conn->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
$userQuery->bind_param("i", $userId);
$userQuery->execute();
$userData = $userQuery->get_result()->fetch_assoc();

// Obtener usuarios para el chat
$chatUsersQuery = $conn->prepare("
    SELECT id, username, profile_picture 
    FROM users 
    WHERE id != ? 
    ORDER BY username ASC
");
$chatUsersQuery->bind_param("i", $userId);
$chatUsersQuery->execute();
$chatUsersResult = $chatUsersQuery->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ShareMyGym</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        /* Estilos para el chat */
        .chat-panel {
            position: fixed;
            bottom: 0;
            right: 20px;
            width: 350px;
            height: 450px;
            background-color: #fff;
            border-radius: 10px 10px 0 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            z-index: 1000;
            display: none;
            flex-direction: column;
            overflow: hidden;
        }
        .chat-header {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .chat-body {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            display: flex;
            flex-direction: column;
            height: calc(100% - 110px); /* Altura total menos header y footer */
        }
        .chat-footer {
            padding: 10px;
            border-top: 1px solid #ddd;
            display: flex;
            position: sticky;
            bottom: 0;
            background-color: #fff;
            z-index: 10;
        }
        .chat-input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 5px;
        }
        .chat-send {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            cursor: pointer;
        }
        .chat-message {
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 18px;
            max-width: 75%;
            position: relative;
        }
        .chat-message.sent {
            background-color: #dcf8c6;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        .chat-message.received {
            background-color: #f1f0f0;
            margin-right: auto;
            border-bottom-left-radius: 5px;
        }
        .chat-time {
            font-size: 0.7rem;
            color: #6c757d;
            margin-top: 2px;
            text-align: right;
        }
        .user-list {
            max-height: 100%;
            overflow-y: auto;
            height: calc(100% - 50px); /* Altura total menos header */
        }
        .user-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .user-item:hover {
            background-color: #f8f9fa;
        }
        .user-item img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .chat-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: red;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
        }
        .chat-button {
            position: relative;
        }
        /* Animación para el botón de chat */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .chat-button-pulse {
            animation: pulse 1.5s infinite;
        }
        
        /* Estilos para el botón de volver */
        #backButton {
            margin-right: 10px;
            padding: 2px 8px;
            border-radius: 50%;
            transition: background-color 0.3s;
        }
        
        #backButton:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        /* Ajuste para el título del chat */
        #chatHeaderTitle {
            display: flex;
            align-items: center;
        }
        
        /* Animación para el botón de volver */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        #backButton {
            animation: fadeIn 0.3s ease-in-out;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <button onclick="location.reload()">Inicio</button>
            <button onclick="location.href='feed.php'">Feed</button>
            <button onclick="location.href='upload.php'">Subir publicación</button>
            <button onclick="location.href='profile.php'">Mi Perfil</button>
            <!-- Botón de chat con ícono -->
            <button id="chatButton" class="chat-button">
                <i class="fas fa-comments"></i> Chat
                <span id="unreadBadge" class="chat-badge" style="display: none;">0</span>
            </button>
            <button onclick="location.href='logout.php'">Cerrar Sesión</button>
        </nav>

        <div class="main-content">
        <header>
            <div class="user-info">
                <!-- Envolver la imagen y el nombre en un enlace -->
                <a href="profile.php?user_id=<?= $userId ?>">
                    <img src="<?= $userData['profile_picture'] ?>" alt="Perfil" class="profile-img" width="50" height="50">
                    <span><?= $userData['username'] ?></span>
                </a>
            </div>
            <!-- Barra de búsqueda -->
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Buscar usuarios..." onkeyup="searchUsers()">
                <div id="searchResults" class="search-results" style="display: none;"></div>
            </div>
        </header>
        <div class="feed-and-recommendations">
            <div class="feed">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($post = $result->fetch_assoc()): ?>
                        <div class="post">
                            <img src="<?= $post['profile_picture'] ?>" alt="Perfil" class="post-profile-img">
                            <div class="post-content">
                                <h4><a href="profile.php?user_id=<?= $post['user_id'] ?>" style="text-decoration: none; color: inherit;"><?= $post['username'] ?></a></h4>
                                <p><?= $post['content'] ?></p>
                                
                                <!-- Mostrar la imagen del post si existe -->
                                <?php if (!empty($post['image_url'])): ?>
                                    <img src="<?= $post['image_url'] ?>" alt="Imagen de la publicación" class="post-image" height="250px" width="250px" style="max-width: 100%; height: auto;">
                                    <br>
                                    <?php endif; ?>

                                <!-- Mostrar el video del post solo si existe -->
                                <?php if (!empty($post['video_url'])): ?>
                                    <video controls style="max-width: 100%; height: auto;">
                                        <source src="<?= htmlspecialchars($post['video_url'], ENT_QUOTES) ?>" type="video/mp4">
                                        Tu navegador no soporta la etiqueta de video.
                                    </video>
                                    <br>
                                <?php else: ?>
                                    <!-- No mostrar nada si no hay video -->
                                <?php endif; ?>

                                <span class="post-date"><?= $post['created_at'] ?></span>
                                
                                <!-- Verificar si el usuario ya le dio like a la publicación -->
                                <?php
                                $likeCheckQuery = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
                                $likeCheckQuery->bind_param("ii", $userId, $post['id']);
                                $likeCheckQuery->execute();
                                $likeCheckResult = $likeCheckQuery->get_result();
                                $hasLiked = $likeCheckResult->num_rows > 0;
                                ?>

                                <!-- Botón de Like y recuento -->
                                <a href="?like_post_id=<?= $post['id'] ?>" class="btn btn-sm">
                                    <?= $hasLiked ? '❤️' : '🤍' ?> <?= $post['like_count'] ?>
                                </a>

                                <?php if ($post['user_id'] == $userId): ?>
                                    <button class="btn btn-sm" onclick="confirmDelete(<?= $post['id'] ?>)">🗑️</button>
                                <?php endif; ?>

                                <!-- Formulario de comentarios -->
                                <form method="POST" action="">
                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                    <textarea name="comment_text" required placeholder="Escribe un comentario..."></textarea>
                                    <button type="submit" class="btn btn-sm">Comentar</button>
                                </form>

                                <!-- Mostrar comentarios -->
                                <div class="comments">
                                    <?php
                                    $commentsQuery = $conn->prepare("SELECT comments.id, comments.comment_text, comments.user_id, users.username, users.profile_picture 
                                                                    FROM comments 
                                                                    JOIN users ON comments.user_id = users.id 
                                                                    WHERE post_id = ?");
                                    $commentsQuery->bind_param("i", $post['id']);
                                    $commentsQuery->execute();
                                    $commentsResult = $commentsQuery->get_result();
                                    while ($comment = $commentsResult->fetch_assoc()):
                                        // Obtener el número de likes del comentario
                                        $likeCountQuery = $conn->prepare("SELECT COUNT(*) AS like_count FROM comment_likes WHERE comment_id = ?");
                                        $likeCountQuery->bind_param("i", $comment['id']);
                                        $likeCountQuery->execute();
                                        $likeCountResult = $likeCountQuery->get_result()->fetch_assoc();
                                        $likeCount = $likeCountResult['like_count'];

                                        // Verificar si el usuario ya le dio like al comentario
                                        $likeCheckQuery = $conn->prepare("SELECT id FROM comment_likes WHERE user_id = ? AND comment_id = ?");
                                        $likeCheckQuery->bind_param("ii", $userId, $comment['id']);
                                        $likeCheckQuery->execute();
                                        $hasLikedComment = $likeCheckQuery->get_result()->num_rows > 0;
                                    ?>
                                        <div class="comment">
                                            <!-- Foto de perfil a la izquierda del nombre, con borde circular -->
                                            <img src="<?= $comment['profile_picture'] ?>" alt="Perfil" class="comment-profile-img" width="30" height="30">
                                            <strong><a href="profile.php?user_id=<?= $comment['user_id'] ?>" style="text-decoration: none; color: inherit;"><?= $comment['username'] ?></a>:</strong> <?= $comment['comment_text'] ?>
                                            
                                            <?php if ($comment['user_id'] == $userId || $post['user_id'] == $userId): ?>
                                                <a href="?delete_comment_id=<?= $comment['id'] ?>" class="btn btn-sm">🗑️</a>
                                            <?php endif; ?>

                                            <!-- Botón de like para comentarios -->
                                            <a href="?like_comment_id=<?= $comment['id'] ?>" class="btn btn-sm">
                                                <?= $hasLikedComment ? '❤️' : '🤍' ?> <?= $likeCount ?>
                                            </a>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-posts">¡Vaya! No hay más publicaciones.</p>
                <?php endif; ?>
            </div>
            <div class="recommended-profiles">
                <h3>Personas que quizás conozcas:</h3>
                <?php while ($recommendation = $recommendedResult->fetch_assoc()): ?>
                    <div class="recommended-profile">
                        <img src="<?= $recommendation['profile_picture'] ?>" alt="Perfil de <?= $recommendation['username'] ?>" class="recommended-profile-img">
                        <span><?= $recommendation['username'] ?></span>
                        <button class="follow-btn">Seguir</button>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        </div>
    </div>

    <!-- Panel de Chat -->
    <div id="chatPanel" class="chat-panel">
        <div class="chat-header">
            <div id="chatHeaderTitle" class="d-flex align-items-center">
                <!-- Botón de volver, inicialmente oculto -->
                <button id="backButton" class="btn btn-sm text-white mr-2" style="display: none;">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <span>Chat</span>
            </div>
            <div>
                <button id="minimizeChat" class="btn btn-sm text-white"><i class="fas fa-minus"></i></button>
                <button id="closeChat" class="btn btn-sm text-white"><i class="fas fa-times"></i></button>
            </div>
        </div>
        
        <!-- Lista de usuarios para el chat -->
        <div id="userListContainer" class="user-list">
            <?php while ($chatUser = $chatUsersResult->fetch_assoc()): ?>
                <div class="user-item" data-user-id="<?= $chatUser['id'] ?>" data-username="<?= $chatUser['username'] ?>">
                    <img src="<?= $chatUser['profile_picture'] ?>" alt="<?= $chatUser['username'] ?>">
                    <span><?= $chatUser['username'] ?></span>
                    <span class="unread-badge badge badge-danger rounded-pill float-right" style="display: none;">0</span>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Área de mensajes -->
        <div id="chatBody" class="chat-body" style="display: none;">
            <!-- Los mensajes se cargarán aquí dinámicamente -->
        </div>
        
        <!-- Formulario para enviar mensajes -->
        <div id="chatFooter" class="chat-footer" style="display: none;">
            <input type="text" id="chatInput" class="chat-input" placeholder="Escribe un mensaje...">
            <button id="chatSend" class="chat-send"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <script>
        function confirmDelete(postId) {
            if (confirm("¿Estás seguro de que deseas eliminar esta publicación?")) {
                window.location.href = 'delete_post.php?id=' + postId; // Redirigir al script de eliminación
            }
        }

        function searchUsers() {
            var input = document.getElementById('searchInput').value;
            if (input.length > 0) {
                $.ajax({
                    url: 'search.php',
                    method: 'GET',
                    data: { query: input },
                    success: function(data) {
                        $('#searchResults').html(data);
                        $('#searchResults').show();
                    }
                });
            } else {
                $('#searchResults').hide();
            }
        }

        // Ocultar resultados al hacer clic fuera
        $(document).click(function(event) {
            if (!$(event.target).closest('#searchInput, #searchResults').length) {
                $('#searchResults').hide();
            }
        });

        // Variables para el chat
        let socket;
        let selectedUserId = null;
        let selectedUsername = null;
        const currentUserId = <?= $userId ?>;
        const sessionId = "<?= session_id(); ?>";
        let unreadMessages = {};

        // Inicializar WebSocket
        function initWebSocket() {
            // Usar la dirección IP o nombre de host específico en lugar de window.location.hostname
            const wsUrl = 'ws://localhost:8080';
            
            console.log('Conectando a WebSocket en:', wsUrl);
            socket = new WebSocket(wsUrl);
            
            socket.onopen = function(event) {
                console.log('Conexión WebSocket establecida');
                
                // Autenticar al usuario
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
                        console.log('Autenticación exitosa');
                        break;
                        
                    case 'new_message':
                        handleNewMessage(data);
                        break;
                        
                    case 'chat_history':
                        displayChatHistory(data.messages);
                        break;
                        
                    case 'unread_messages':
                        processUnreadMessages(data.messages);
                        break;
                        
                    case 'user_status':
                        updateUserStatus(data.user_id, data.online);
                        break;
                        
                    case 'test_response':
                        console.log('Respuesta de prueba recibida:', data.message);
                        break;
                }
            };
            
            socket.onerror = function(error) {
                console.error('Error de WebSocket:', error);
            };
            
            socket.onclose = function(event) {
                console.log('Conexión WebSocket cerrada');
                
                // Intentar reconectar después de 5 segundos
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

        // Manejar nuevos mensajes
        function handleNewMessage(data) {
            // Si el mensaje es para la conversación actual, mostrarlo
            if (
                (data.sender_id === currentUserId && data.receiver_id === selectedUserId) ||
                (data.sender_id === selectedUserId && data.receiver_id === currentUserId)
            ) {
                appendMessage(data);
            } 
            // Si el mensaje es de alguien más, incrementar contador de no leídos
            else if (data.sender_id !== currentUserId) {
                incrementUnreadCount(data.sender_id);
            }
        }

        // Incrementar contador de mensajes no leídos
        function incrementUnreadCount(senderId) {
            if (!unreadMessages[senderId]) {
                unreadMessages[senderId] = 0;
            }
            unreadMessages[senderId]++;
            
            // Actualizar badge en la lista de usuarios
            const userItem = $(`.user-item[data-user-id="${senderId}"]`);
            const badge = userItem.find('.unread-badge');
            badge.text(unreadMessages[senderId]);
            badge.show();
            
            // Actualizar badge global en el botón de chat
            updateGlobalUnreadBadge();
            
            // Añadir animación al botón de chat
            $('#chatButton').addClass('chat-button-pulse');
        }

        // Actualizar badge global
        function updateGlobalUnreadBadge() {
            let totalUnread = 0;
            for (const userId in unreadMessages) {
                totalUnread += unreadMessages[userId];
            }
            
            const badge = $('#unreadBadge');
            if (totalUnread > 0) {
                badge.text(totalUnread);
                badge.show();
            } else {
                badge.hide();
            }
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
            
            // Actualizar contadores de mensajes no leídos
            for (const senderId in messagesBySender) {
                if (!unreadMessages[senderId]) {
                    unreadMessages[senderId] = 0;
                }
                unreadMessages[senderId] += messagesBySender[senderId].length;
                
                // Actualizar badge en la lista de usuarios
                const userItem = $(`.user-item[data-user-id="${senderId}"]`);
                const badge = userItem.find('.unread-badge');
                badge.text(unreadMessages[senderId]);
                badge.show();
            }
            
            // Actualizar badge global
            updateGlobalUnreadBadge();
        }

        // Mostrar historial de chat
        function displayChatHistory(messages) {
            const chatBody = $('#chatBody');
            chatBody.empty();
            
            if (messages.length === 0) {
                chatBody.append('<div class="text-center p-3">No hay mensajes. ¡Sé el primero en escribir!</div>');
                return;
            }
            
            messages.forEach(message => {
                appendMessage(message);
            });
            
            // Desplazarse al último mensaje
            chatBody.scrollTop(chatBody[0].scrollHeight);
        }

        // Añadir un mensaje al chat
        function appendMessage(message) {
    const chatBody = $('#chatBody');
    const isSent = message.sender_id == currentUserId;
    const messageElement = $('<div>').addClass(`chat-message ${isSent ? 'sent' : 'received'}`);
    
    // Formatear la fecha
    const timestamp = new Date(message.timestamp);
    const formattedTime = timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    messageElement.html(`
        <div class="message-content">${escapeHtml(message.message)}</div>
        <div class="chat-time">${formattedTime}</div>
    `);
    
    chatBody.append(messageElement);
    
    // Desplazarse al último mensaje
    chatBody.scrollTop(chatBody[0].scrollHeight);
}

        // Escapar HTML para prevenir XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Actualizar estado de usuario (online/offline)
        function updateUserStatus(userId, online) {
            const userItem = $(`.user-item[data-user-id="${userId}"]`);
            if (online) {
                userItem.addClass('online');
            } else {
                userItem.removeClass('online');
            }
        }

        // Seleccionar un usuario para chatear
        function selectUser(userId, username) {
            selectedUserId = userId;
            selectedUsername = username;
            
            // Actualizar título del chat
            $('#chatHeaderTitle span').text(`Chat con ${username}`);
            
            // Mostrar el botón de volver
            $('#backButton').show();
            
            // Mostrar área de chat y ocultar lista de usuarios
            $('#userListContainer').hide();
            $('#chatBody, #chatFooter').show();
            
            // Resetear contador de mensajes no leídos para este usuario
            if (unreadMessages[userId]) {
                unreadMessages[userId] = 0;
                $(`.user-item[data-user-id="${userId}"] .unread-badge`).hide();
                updateGlobalUnreadBadge();
            }
            
            // Solicitar historial de chat
            sendToServer({
                type: 'get_history',
                other_user_id: userId
            });
        }

        // Volver a la lista de usuarios
        function backToUserList() {
            // Ocultar área de chat y mostrar lista de usuarios
            $('#chatBody, #chatFooter').hide();
            $('#userListContainer').show();
            
            // Ocultar el botón de volver
            $('#backButton').hide();
            
            // Actualizar título del chat
            $('#chatHeaderTitle span').text('Chat');
            
            // Resetear usuario seleccionado
            selectedUserId = null;
            selectedUsername = null;
        }

        // Cuando el documento esté listo
        $(document).ready(function() {
            // Inicializar WebSocket
            initWebSocket();
            
            // Mostrar/ocultar panel de chat al hacer clic en el botón
            $('#chatButton').click(function() {
                $('#chatPanel').toggle();
                
                // Si se muestra el chat, quitar animación del botón
                if ($('#chatPanel').is(':visible')) {
                    $('#chatButton').removeClass('chat-button-pulse');
                }
            });
            
            // Cerrar chat
            $('#closeChat').click(function() {
                $('#chatPanel').hide();
            });
            
            // Minimizar chat
            $('#minimizeChat').click(function() {
                $('#chatPanel').hide();
            });
            
            // Botón de volver a la lista de usuarios
            $('#backButton').click(function() {
                backToUserList();
            });
            
            // Seleccionar usuario para chatear
            $(document).on('click', '.user-item', function() {
                const userId = $(this).data('user-id');
                const username = $(this).data('username');
                selectUser(userId, username);
            });
            
            // Enviar mensaje
            $('#chatSend').click(function() {
                sendMessage();
            });
            
            // Enviar mensaje al presionar Enter
            $('#chatInput').keypress(function(e) {
                if (e.which === 13) {
                    sendMessage();
                    return false;
                }
            });
            
            // Función para enviar mensaje
            function sendMessage() {
                const message = $('#chatInput').val().trim();
                if (!message || !selectedUserId) {
                    return;
                }
                
                // Enviar mensaje al servidor
                sendToServer({
                    type: 'message',
                    receiver_id: selectedUserId,
                    message: message
                });
                
                // Limpiar campo de entrada
                $('#chatInput').val('');
            }
        });
    </script>
</body>
</html>