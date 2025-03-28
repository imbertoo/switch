<?php 
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Obtener el ID del usuario conectado
$currentUserId = $_SESSION['user_id'];

// Obtener el ID del usuario del perfil (si se proporciona en la URL)
$profileUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : $currentUserId;

// Obtener datos del usuario del perfil
$userQuery = $conn->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
$userQuery->bind_param("i", $profileUserId);
$userQuery->execute();
$userData = $userQuery->get_result()->fetch_assoc();

// Si no se encuentran los datos del usuario, asignar valores predeterminados
$username = isset($userData['username']) ? htmlspecialchars($userData['username']) : 'Usuario desconocido';
$profilePicture = isset($userData['profile_picture']) ? htmlspecialchars($userData['profile_picture']) : 'default-profile.png'; // Usar una imagen predeterminada si no se encuentra

// Obtener número de publicaciones del usuario
$postCountQuery = $conn->prepare("SELECT COUNT(*) as total_posts FROM posts WHERE user_id = ?");
$postCountQuery->bind_param("i", $profileUserId);
$postCountQuery->execute();
$postCount = $postCountQuery->get_result()->fetch_assoc()['total_posts'];

// Obtener número de seguidores del usuario
$followersQuery = $conn->prepare("SELECT COUNT(*) as total_followers FROM friends WHERE friend_id = ? AND status = 'accepted'");
$followersQuery->bind_param("i", $profileUserId);
$followersQuery->execute();
$followersCount = $followersQuery->get_result()->fetch_assoc()['total_followers'];

// Obtener las publicaciones del usuario
$postsQuery = $conn->prepare("SELECT id, user_id, content, image_url, video_url, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$postsQuery->bind_param("i", $profileUserId);
$postsQuery->execute();
$postsResult = $postsQuery->get_result();

// Verificar si el usuario conectado sigue al usuario del perfil
$followingQuery = $conn->prepare("SELECT * FROM friends WHERE user_id = ? AND friend_id = ? AND status = 'accepted'");
$followingQuery->bind_param("ii", $currentUserId, $profileUserId);
$followingQuery->execute();
$isFollowing = $followingQuery->get_result()->num_rows > 0;

// Verificar si el usuario del perfil sigue al usuario conectado
$followedByQuery = $conn->prepare("SELECT * FROM friends WHERE user_id = ? AND friend_id = ? AND status = 'accepted'");
$followedByQuery->bind_param("ii", $profileUserId, $currentUserId);
$followedByQuery->execute();
$isFollowedBy = $followedByQuery->get_result()->num_rows > 0;

// Obtener usuarios para el chat
$chatUsersQuery = $conn->prepare("
    SELECT id, username, profile_picture 
    FROM users 
    WHERE id != ? 
    ORDER BY username ASC
");
$chatUsersQuery->bind_param("i", $currentUserId);
$chatUsersQuery->execute();
$chatUsersResult = $chatUsersQuery->get_result();

// Manejar el envío de likes
if (isset($_GET['like_post_id'])) {
    $likePostId = $_GET['like_post_id'];

    // Verificar si ya le dio like a la publicación
    $checkLikeQuery = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $checkLikeQuery->bind_param("ii", $currentUserId, $likePostId);
    $checkLikeQuery->execute();
    $likeResult = $checkLikeQuery->get_result();

    if ($likeResult->num_rows > 0) {
        // Si ya le dio like, eliminar el like
        $removeLikeQuery = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $removeLikeQuery->bind_param("ii", $currentUserId, $likePostId);
        $removeLikeQuery->execute();
    } else {
        // Si no le dio like, agregar el like
        $likeQuery = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $likeQuery->bind_param("ii", $currentUserId, $likePostId);
        $likeQuery->execute();
    }
    
    // Redireccionar para evitar reenvío del formulario
    header("Location: profile.php?user_id=" . $profileUserId);
    exit;
}

// Manejar el envío de likes a los comentarios
if (isset($_GET['like_comment_id'])) {
    $likeCommentId = $_GET['like_comment_id'];

    // Verificar si el usuario ya le dio like al comentario
    $checkLikeQuery = $conn->prepare("SELECT id FROM comment_likes WHERE user_id = ? AND comment_id = ?");
    $checkLikeQuery->bind_param("ii", $currentUserId, $likeCommentId);
    $checkLikeQuery->execute();
    $likeResult = $checkLikeQuery->get_result();

    if ($likeResult->num_rows > 0) {
        // Si ya le dio like, eliminar el like
        $removeLikeQuery = $conn->prepare("DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?");
        $removeLikeQuery->bind_param("ii", $currentUserId, $likeCommentId);
        $removeLikeQuery->execute();
    } else {
        // Si no le dio like, agregar el like
        $likeQuery = $conn->prepare("INSERT INTO comment_likes (user_id, comment_id) VALUES (?, ?)");
        $likeQuery->bind_param("ii", $currentUserId, $likeCommentId);
        $likeQuery->execute();
    }
    
    // Redireccionar para evitar reenvío del formulario
    header("Location: profile.php?user_id=" . $profileUserId);
    exit;
}

// Manejar el envío de comentarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'])) {
    $postId = $_POST['post_id'];
    $commentText = $_POST['comment_text'];
    $commentQuery = $conn->prepare("INSERT INTO comments (user_id, post_id, comment_text) VALUES (?, ?, ?)");
    $commentQuery->bind_param("iis", $currentUserId, $postId, $commentText);
    $commentQuery->execute();
    
    // Redireccionar para evitar reenvío del formulario
    header("Location: profile.php?user_id=" . $profileUserId);
    exit;
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
    $deleteCommentQuery->bind_param("iii", $deleteCommentId, $currentUserId, $currentUserId);
    $deleteCommentQuery->execute();
    
    // Redireccionar para evitar reenvío del formulario
    header("Location: profile.php?user_id=" . $profileUserId);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $username ?> - Switch</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="shortcut icon" href="favicon.ico"/>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    /* Estilos para asegurar que todas las fotos de perfil tengan el mismo tamaño */
    .profile-img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 50%;
    }
    
    .profile-header-img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .post-author-img, .comment-author-img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 50%;
    }
    
    .comment-user-img {
        width: 32px;
        height: 32px;
        object-fit: cover;
        border-radius: 50%;
    }
    
    .user-item img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 50%;
    }
</style>
    <style>
.profile-action-btn {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    margin-right: 10px;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-primary:hover {
    background-color: #0069d9;
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.follows-you-badge {
    background-color: #f8f9fa;
    color: #6c757d;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}
</style>
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
                <a href="dashboard.php" class="sidebar-item">
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
                <a href="profile.php" class="sidebar-item active">
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
    <a href="profile.php?user_id=<?= $currentUserId ?>" class="user-profile">
        <?php
        // Obtener datos del usuario actual (logueado)
        $currentUserQuery = $conn->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
        $currentUserQuery->bind_param("i", $currentUserId);
        $currentUserQuery->execute();
        $currentUserData = $currentUserQuery->get_result()->fetch_assoc();
        ?>
        <span><?= $currentUserData['username'] ?></span>
        <img src="<?= $currentUserData['profile_picture'] ?>" alt="Perfil" class="profile-img">
    </a>
</div>
            </header>

            <!-- Profile Header -->
            <div class="profile-header-container">
                <div class="profile-header">
                    <div class="profile-header-left">
                        <img src="<?= $profilePicture ?>" alt="<?= $username ?>" class="profile-header-img">
                    </div>
                    <div class="profile-header-right">
                        <div class="profile-header-info">
                            <h1 class="profile-username"><?= $username ?></h1>
                            <div class="profile-stats">
                                <div class="profile-stat">
                                    <span class="profile-stat-count"><?= $postCount ?></span>
                                    <span class="profile-stat-label">Publicaciones</span>
                                </div>
                                <div class="profile-stat">
                                    <span class="profile-stat-count"><?= $followersCount ?></span>
                                    <span class="profile-stat-label">Seguidores</span>
                                </div>
                            </div>
                        </div>
                        <div class="profile-actions">
    <?php if ($profileUserId == $currentUserId): ?>
        <a href="edit_profile.php" class="profile-action-btn btn-primary">
            <i class="fas fa-edit"></i> Editar Perfil
        </a>
    <?php else: ?>
        <?php if ($isFollowing): ?>
            <button class="profile-action-btn btn-danger follow-btn" data-user-id="<?= $profileUserId ?>">
                <i class="fas fa-user-minus"></i> Dejar de seguir
            </button>
        <?php else: ?>
            <button class="profile-action-btn btn-primary follow-btn" data-user-id="<?= $profileUserId ?>">
                <i class="fas fa-user-plus"></i> Seguir
            </button>
        <?php endif; ?>

        <button class="profile-action-btn btn-secondary message-btn" data-user-id="<?= $profileUserId ?>" data-username="<?= $username ?>">
            <i class="fas fa-envelope"></i> Mensaje
        </button>

        <?php if ($isFollowedBy): ?>
            <span class="follows-you-badge">Te sigue</span>
        <?php endif; ?>
    <?php endif; ?>
</div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Posts Section -->
                <div class="posts-section">
                    <?php if ($postsResult->num_rows > 0): ?>
                        <?php while ($post = $postsResult->fetch_assoc()): 
                            // Obtener el número de likes
                            $likeCountQuery = $conn->prepare("SELECT COUNT(*) AS like_count FROM likes WHERE post_id = ?");
                            $likeCountQuery->bind_param("i", $post['id']);
                            $likeCountQuery->execute();
                            $likeCount = $likeCountQuery->get_result()->fetch_assoc()['like_count'];
                            
                            // Verificar si el usuario conectado ha dado like
                            $likeCheckQuery = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
                            $likeCheckQuery->bind_param("ii", $currentUserId, $post['id']);
                            $likeCheckQuery->execute();
                            $likeCheckResult = $likeCheckQuery->get_result();
                            $hasLiked = $likeCheckResult->num_rows > 0;
                        ?>
                            <div class="post-card">
                                <div class="post-header">
                                    <a href="profile.php?user_id=<?= $post['user_id'] ?>" class="post-author">
                                        <img src="<?= $profilePicture ?>" alt="<?= $username ?>" class="post-author-img">
                                        <div class="post-author-info">
                                            <h4><?= $username ?></h4>
                                            <span class="post-date"><?= date('d M Y, H:i', strtotime($post['created_at'])) ?></span>
                                        </div>
                                    </a>
                                    <?php if ($post['user_id'] == $currentUserId): ?>
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
                                    <div class="post-stats">
                                        <a href="?user_id=<?= $profileUserId ?>&like_post_id=<?= $post['id'] ?>" class="post-stat-item <?= $hasLiked ? 'liked' : '' ?>">
                                            <i class="<?= $hasLiked ? 'fas' : 'far' ?> fa-heart"></i>
                                            <span><?= $likeCount ?></span>
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
                                            <form method="POST" action="?user_id=<?= $profileUserId ?>" class="comment-input-form">
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
                                                    $likeCheckQuery->bind_param("ii", $currentUserId, $comment['id']);
                                                    $likeCheckQuery->execute();
                                                    $hasLikedComment = $likeCheckQuery->get_result()->num_rows > 0;
                                            ?>
                                                <div class="comment-item">
                                                    <img src="<?= $comment['profile_picture'] ?>" alt="<?= $comment['username'] ?>" class="comment-author-img">
                                                    <div class="comment-content">
                                                        <div class="comment-header">
                                                            <a href="profile.php?user_id=<?= $comment['user_id'] ?>" class="comment-author-name"><?= $comment['username'] ?></a>
                                                            <div class="comment-actions">
                                                                <a href="?user_id=<?= $profileUserId ?>&like_comment_id=<?= $comment['id'] ?>" class="comment-like <?= $hasLikedComment ? 'liked' : '' ?>">
                                                                    <i class="<?= $hasLikedComment ? 'fas' : 'far' ?> fa-heart"></i>
                                                                    <?php if ($likeCount > 0): ?>
                                                                        <span><?= $likeCount ?></span>
                                                                    <?php endif; ?>
                                                                </a>
                                                                <?php if ($comment['user_id'] == $currentUserId || $post['user_id'] == $currentUserId): ?>
                                                                    <a href="?user_id=<?= $profileUserId ?>&delete_comment_id=<?= $comment['id'] ?>" class="comment-delete" onclick="return confirm('¿Estás seguro de eliminar este comentario?')">
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
                            <?php if ($profileUserId == $currentUserId): ?>
                                <p>¡Comparte tu primera publicación con tus seguidores!</p>
                                <a href="upload.php" class="btn-create-post">Crear publicación</a>
                            <?php else: ?>
                                <p><?= $username ?> aún no ha compartido ninguna publicación.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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
                        <span class="user-item-status">Pulsa para abrir el chat</span>
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

        // Seguir/Dejar de seguir a un usuario
        $(document).on('click', '.follow-btn', function() {
    const button = $(this);
    const userId = button.data('user-id');
    const isFollowing = button.hasClass('btn-danger');
    
    // Cambiar el estado del botón
    button.prop('disabled', true).text('Procesando...');
    
    // Enviar solicitud AJAX
    $.ajax({
        url: 'follow_ajax.php',
        method: 'POST',
        data: { 
            follow_user_id: userId,
            action: isFollowing ? 'unfollow' : 'follow'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (isFollowing) {
                    button.removeClass('btn-danger').addClass('btn-primary');
                    button.html('<i class="fas fa-user-plus"></i> Seguir');
                } else {
                    button.removeClass('btn-primary').addClass('btn-danger');
                    button.html('<i class="fas fa-user-minus"></i> Dejar de seguir');
                }
                // Actualizar contador de seguidores sin recargar la página
                const currentCount = parseInt($('.profile-stat-count').eq(1).text());
                $('.profile-stat-count').eq(1).text(isFollowing ? currentCount - 1 : currentCount + 1);
            } else {
                console.log('Error: ' + response.message);
                // No mostrar alerta, solo registrar en consola
            }
            button.prop('disabled', false);
        },
        error: function(xhr, status, error) {
            console.log('Error en la solicitud: ' + error);
            // Restaurar el botón sin mostrar alerta
            button.prop('disabled', false);
            if (isFollowing) {
                button.html('<i class="fas fa-user-minus"></i> Dejar de seguir');
            } else {
                button.html('<i class="fas fa-user-plus"></i> Seguir');
            }
        }
    });
});

// Abrir chat con el usuario del perfil
$(document).on('click', '.message-btn', function() {
    const userId = $(this).data('user-id');
    const username = $(this).data('username');
    
    // Mostrar el panel de chat
    $('#chatPanel').show();
    
    // Seleccionar el usuario
    selectUser(userId, username);
});

        // Variables para el chat
        let socket;
        let selectedUserId = null;
        let selectedUsername = null;
        const currentUserId = <?= $currentUserId ?>;
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