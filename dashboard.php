<?php
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

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


// Obtener publicaciones del usuario y de las cuentas que sigue
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
    <title>Switch - Tu red social</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="shortcut icon" href="favicon.ico"/>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <img src="logo.png" alt="Switch Logo" class="sidebar-logo">
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php" class="sidebar-item active">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
                <a href="feed.php" class="sidebar-item">
                    <i class="fas fa-heart"></i>
                    <span>Notificaciones</span>
                </a>
                <a href="upload.php" class="sidebar-item">
                    <i class="fas fa-plus-circle"></i>
                    <span>Publicar</span>
                </a>
                <a href="profile.php" class="sidebar-item">
                    <i class="fas fa-user"></i>
                    <span>Perfil</span>
                </a>
                <a href="#" id="chatButton" class="sidebar-item chat-button">
                    <i class="fas fa-comments"></i>
                    <span>Chat</span>
                    <span id="unreadBadge" class="chat-badge" style="display: none;">0</span>
                </a>
                <a href="logout.php" class="sidebar-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Buscar usuarios..." onkeyup="searchUsers()">
                    <div id="searchResults" class="search-results" style="display: none;"></div>
                </div>
                <div class="user-info">
                    <a href="profile.php?user_id=<?= $userId ?>" class="user-profile">
                        <span><?= $userData['username'] ?></span>
                        <img src="<?= $userData['profile_picture'] ?>" alt="Perfil" class="profile-img">
                    </a>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Feed Section -->
                <div class="feed-section">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($post = $result->fetch_assoc()): ?>
                            <div class="post-card">
                                <div class="post-header">
                                    <a href="profile.php?user_id=<?= $post['user_id'] ?>" class="post-author">
                                        <img src="<?= $post['profile_picture'] ?>" alt="<?= $post['username'] ?>" class="post-author-img">
                                        <div class="post-author-info">
                                            <h4><?= $post['username'] ?></h4>
                                            <span class="post-date"><?= date('d M Y, H:i', strtotime($post['created_at'])) ?></span>
                                        </div>
                                    </a>
                                    <?php if ($post['user_id'] == $userId): ?>
                                        <div class="post-actions">
                                            <button class="post-action-btn" onclick="confirmDelete(<?= $post['id'] ?>)">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="post-content">
                                    <p><?= $post['content'] ?></p>
                                    
                                    <?php if (!empty($post['image_url'])): ?>
                                        <div class="post-media">
                                            <img src="<?= $post['image_url'] ?>" alt="Imagen de la publicación" class="post-image">
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($post['video_url'])): ?>
                                        <div class="post-media">
                                            <video controls class="post-video">
                                                <source src="<?= htmlspecialchars($post['video_url'], ENT_QUOTES) ?>" type="video/mp4">
                                                Tu navegador no soporta la etiqueta de video.
                                            </video>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="post-footer">
                                    <?php
                                    $likeCheckQuery = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
                                    $likeCheckQuery->bind_param("ii", $userId, $post['id']);
                                    $likeCheckQuery->execute();
                                    $likeCheckResult = $likeCheckQuery->get_result();
                                    $hasLiked = $likeCheckResult->num_rows > 0;
                                    ?>

                                    <div class="post-stats">
                                        <a href="?like_post_id=<?= $post['id'] ?>" class="post-stat-item <?= $hasLiked ? 'liked' : '' ?>">
                                            <i class="<?= $hasLiked ? 'fas' : 'far' ?> fa-heart"></i>
                                            <span><?= $post['like_count'] ?></span>
                                        </a>
                                        <button class="post-stat-item comment-toggle" data-post-id="<?= $post['id'] ?>">
                                            <i class="far fa-comment"></i>
                                            <span>Comentar</span>
                                        </button>
                                        <button class="post-stat-item">
                                            <i class="far fa-share-square"></i>
                                            <span>Compartir</span>
                                        </button>
                                    </div>

                                    <div class="post-comments" id="comments-<?= $post['id'] ?>">
                                        <div class="comment-form">
                                            <form method="POST" action="" class="comment-input-form">
                                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                                <img src="<?= $userData['profile_picture'] ?>" alt="Tu perfil" class="comment-user-img">
                                                <div class="comment-input-container">
                                                    <input type="text" name="comment_text" placeholder="Escribe un comentario..." required class="comment-input">
                                                    <button type="submit" class="comment-submit">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <div class="comments-list">
                                            <?php
                                            $commentsQuery = $conn->prepare("SELECT comments.id, comments.comment_text, comments.user_id, users.username, users.profile_picture 
                                                                            FROM comments 
                                                                            JOIN users ON comments.user_id = users.id 
                                                                            WHERE post_id = ?
                                                                            ORDER BY comments.created_at DESC
                                                                            LIMIT 5");
                                            $commentsQuery->bind_param("i", $post['id']);
                                            $commentsQuery->execute();
                                            $commentsResult = $commentsQuery->get_result();
                                            
                                            if ($commentsResult->num_rows > 0):
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
                                                <div class="comment-item">
                                                    <img src="<?= $comment['profile_picture'] ?>" alt="<?= $comment['username'] ?>" class="comment-author-img">
                                                    <div class="comment-content">
                                                        <div class="comment-header">
                                                            <a href="profile.php?user_id=<?= $comment['user_id'] ?>" class="comment-author-name"><?= $comment['username'] ?></a>
                                                            <div class="comment-actions">
                                                                <a href="?like_comment_id=<?= $comment['id'] ?>" class="comment-like <?= $hasLikedComment ? 'liked' : '' ?>">
                                                                    <i class="<?= $hasLikedComment ? 'fas' : 'far' ?> fa-heart"></i>
                                                                    <?php if ($likeCount > 0): ?>
                                                                        <span><?= $likeCount ?></span>
                                                                    <?php endif; ?>
                                                                </a>
                                                                <?php if ($comment['user_id'] == $userId || $post['user_id'] == $userId): ?>
                                                                    <a href="?delete_comment_id=<?= $comment['id'] ?>" class="comment-delete" onclick="return confirm('¿Estás seguro de eliminar este comentario?')">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <p class="comment-text"><?= $comment['comment_text'] ?></p>
                                                    </div>
                                                </div>
                                            <?php 
                                                endwhile;
                                            else:
                                            ?>
                                                <div class="no-comments">
                                                    <p>No hay comentarios aún. ¡Sé el primero en comentar!</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-posts">
                            <div class="no-posts-icon">
                                <i class="far fa-newspaper"></i>
                            </div>
                            <h3>No hay publicaciones</h3>
                            <p>Sigue a más personas para ver sus publicaciones o crea tu primera publicación.</p>
                            <a href="upload.php" class="btn-create-post">Crear publicación</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar Section -->
                <div class="sidebar-section">
                    <div class="user-profile-card">
                        <a href="profile.php?user_id=<?= $userId ?>" class="user-profile-link">
                            <img src="<?= $userData['profile_picture'] ?>" alt="<?= $userData['username'] ?>" class="user-profile-img">
                            <div class="user-profile-info">
                                <h4><?= $userData['username'] ?></h4>
                                <p>Ver mi perfil</p>
                            </div>
                        </a>
                    </div>

                    <div class="recommended-card">
                        <div class="recommended-header">
                            <h4>Sugerencias para ti</h4>
                        </div>
                        
                        <?php if ($recommendedResult->num_rows > 0): ?>
                            <div class="recommended-list">
                                <?php while ($recommendation = $recommendedResult->fetch_assoc()): ?>
                                    <div class="recommended-item">
                                        <a href="profile.php?user_id=<?= $recommendation['id'] ?>" class="recommended-user">
                                            <img src="<?= $recommendation['profile_picture'] ?>" alt="<?= $recommendation['username'] ?>" class="recommended-user-img">
                                            <div class="recommended-user-info">
                                                <h5><?= $recommendation['username'] ?></h5>
                                                <p>Sugerido para ti</p>
                                            </div>
                                        </a>
                                        <button class="btn-follow" data-user-id="<?= $recommendation['id'] ?>">Seguir</button>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-recommendations">
                                <p>No hay sugerencias disponibles en este momento.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="footer-links">
                        <a href="#">Acerca de</a>
                        <a href="#">Ayuda</a>
                        <a href="#">Privacidad</a>
                        <a href="#">Términos</a>
                        <p>© 2025 Switch. Todos los derechos reservados.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel de Chat -->
    <div id="chatPanel" class="chat-panel">
        <div class="chat-header">
            <div id="chatHeaderTitle" class="d-flex align-items-center">
                <button id="backButton" class="btn-back" style="display: none;">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <span>Chat</span>
            </div>
            <div class="chat-header-actions">
                <button id="minimizeChat" class="btn-chat-action">
                    <i class="fas fa-minus"></i>
                </button>
                <button id="closeChat" class="btn-chat-action">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <!-- Lista de usuarios para el chat -->
        <div id="userListContainer" class="user-list">
            <?php while ($chatUser = $chatUsersResult->fetch_assoc()): ?>
                <div class="user-item" data-user-id="<?= $chatUser['id'] ?>" data-username="<?= $chatUser['username'] ?>">
                    <img src="<?= $chatUser['profile_picture'] ?>" alt="<?= $chatUser['username'] ?>">
                    <div class="user-item-info">
                        <span class="user-item-name"><?= $chatUser['username'] ?></span>
                        <span class="user-item-status">En línea</span>
                    </div>
                    <span class="unread-badge" style="display: none;">0</span>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Área de mensajes -->
        <div id="chatBody" class="chat-body" style="display: none;">
            <!-- Los mensajes se cargarán aquí dinámicamente -->
        </div>
        
        <!-- Formulario para enviar mensajes -->
        <div id="chatFooter" class="chat-footer" style="display: none;">
            <div class="chat-input-container">
                <input type="text" id="chatInput" class="chat-input" placeholder="Escribe un mensaje...">
                <button id="chatSend" class="chat-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(postId) {
            if (confirm("¿Estás seguro de que deseas eliminar esta publicación?")) {
                window.location.href = 'delete_post.php?id=' + postId;
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

        // Mostrar/ocultar comentarios
        $(document).on('click', '.comment-toggle', function() {
            const postId = $(this).data('post-id');
            $(`#comments-${postId}`).slideToggle();
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
                chatBody.append('<div class="no-messages">No hay mensajes aún. ¡Sé el primero en escribir!</div>');
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