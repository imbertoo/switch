<?php 
session_start();
require_once 'db_connect.php'; // Archivo para conectar a la base de datos

// Verificar si el usuario está conectado
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
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        /* Estilos adicionales para el perfil */
        .profile-header {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f5f5f5;
            margin-right: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-stats {
            display: flex;
            gap: 20px;
            margin: 10px 0;
            color: #555;
        }
        
        .profile-posts {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .post {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .post-profile-img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f5f5f5;
            margin-right: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .post-content {
            flex: 1;
        }
        
        .post-image {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        .post-date {
            display: block;
            font-size: 0.8rem;
            color: #777;
            margin: 5px 0;
        }
        
        .comments {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .comment {
            padding: 8px 0;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 5px;
        }
        
        .comment-profile-img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
            resize: none;
        }
        
        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-sm {
            padding: 3px 8px;
            font-size: 0.8rem;
        }
        
        /* Estilos para dispositivos móviles */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-img {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .profile-stats {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <button onclick="location.href='dashboard.php'">Inicio</button>
            <button onclick="location.href='feed.php'">Feed</button>
            <button onclick="location.href='upload.php'">Subir publicación</button>
            <button onclick="location.href='profile.php'">Mi Perfil</button>
            <!-- Botón de chat con ícono -->
            <button id="chatButton" class="chat-button" onclick="location.href='dashboard.php'">
                <i class="fas fa-comments"></i> Chat
            </button>
            <button onclick="location.href='logout.php'">Cerrar Sesión</button>
        </nav>
        
        <div class="main-content">
            <header>
                <div class="profile-header">
                    <img src="<?= $profilePicture ?>" alt="Perfil" class="profile-img">
                    <div class="profile-info">
                        <h2><?= $username ?></h2>
                        <div class="profile-stats">
                            <span><strong><?= $postCount ?></strong> publicaciones</span>
                            <span><strong><?= $followersCount ?></strong> seguidores</span>
                        </div>
                        <?php if ($profileUserId == $currentUserId): ?>
                            <button onclick="location.href='edit_profile.php'" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Editar Perfil
                            </button>
                        <?php else: ?>
                            <?php if ($isFollowing): ?>
                                <form action="unfollow.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="unfollow_user_id" value="<?= $profileUserId ?>">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-user-minus"></i> Dejar de seguir
                                    </button>
                                </form>
                            <?php else: ?>
                                <form action="follow.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="follow_user_id" value="<?= $profileUserId ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-user-plus"></i> Seguir
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($isFollowedBy): ?>
                                <span class="badge badge-secondary ml-2">Te sigue</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <div class="feed">
                <?php if ($postsResult->num_rows > 0): ?>
                    <?php while ($post = $postsResult->fetch_assoc()): 
                        // Obtener el nombre del autor de la publicación
                        $authorQuery = $conn->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
                        $authorQuery->bind_param("i", $post['user_id']);
                        $authorQuery->execute();
                        $authorData = $authorQuery->get_result()->fetch_assoc();
                        $authorUsername = htmlspecialchars($authorData['username']);
                        $authorProfilePicture = htmlspecialchars($authorData['profile_picture']);
                        
                        // Obtener el número de likes
                        $likeCountQuery = $conn->prepare("SELECT COUNT(*) AS like_count FROM likes WHERE post_id = ?");
                        $likeCountQuery->bind_param("i", $post['id']);
                        $likeCountQuery->execute();
                        $likeCount = $likeCountQuery->get_result()->fetch_assoc()['like_count'];
                    ?>
                        <div class="post">
                            <img src="<?= $authorProfilePicture ?>" alt="Perfil" class="post-profile-img">
                            <div class="post-content">
                                <h4><a href="profile.php?user_id=<?= $post['user_id'] ?>" style="text-decoration: none; color: inherit;"><?= $authorUsername ?></a></h4>
                                <p><?= $post['content'] ?></p>

                                <!-- Mostrar la imagen del post si existe -->
                                <?php if (!empty($post['image_url'])): ?>
                                    <img src="<?= $post['image_url'] ?>" alt="Imagen de la publicación" class="post-image">
                                <?php endif; ?>

                                <!-- Mostrar el video del post solo si existe -->
                                <?php if (!empty($post['video_url'])): ?>
                                    <video controls class="post-image">
                                        <source src="<?= htmlspecialchars($post['video_url'], ENT_QUOTES) ?>" type="video/mp4">
                                        Tu navegador no soporta la etiqueta de video.
                                    </video>
                                <?php endif; ?>

                                <span class="post-date"><?= $post['created_at'] ?></span>

                                <?php
                                // Verificar si el usuario conectado ha dado like
                                $likeCheckQuery = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
                                $likeCheckQuery->bind_param("ii", $currentUserId, $post['id']);
                                $likeCheckQuery->execute();
                                $likeCheckResult = $likeCheckQuery->get_result();
                                $hasLiked = $likeCheckResult->num_rows > 0;
                                ?>

                                <!-- Botón de Like y recuento -->
                                <a href="?user_id=<?= $profileUserId ?>&like_post_id=<?= $post['id'] ?>" class="btn btn-sm">
                                    <?= $hasLiked ? '❤️' : '🤍' ?> <?= $likeCount ?>
                                </a>

                                <?php if ($post['user_id'] == $currentUserId): ?>
                                    <button class="btn btn-sm" onclick="confirmDelete(<?= $post['id'] ?>)">🗑️</button>
                                <?php endif; ?>

                                <form method="POST" action="?user_id=<?= $profileUserId ?>">
                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                    <div class="input-group">
                                        <textarea name="comment_text" required placeholder="Escribe un comentario..." class="form-control"></textarea>
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <div class="comments">
                                    <?php
                                    $commentsQuery = $conn->prepare("SELECT comments.id, comments.comment_text, comments.user_id, users.username, users.profile_picture 
                                    FROM comments 
                                    JOIN users ON comments.user_id = users.id 
                                    WHERE post_id = ?
                                    ORDER BY comments.created_at ASC");
                                    $commentsQuery->bind_param("i", $post['id']);
                                    $commentsQuery->execute();
                                    $commentsResult = $commentsQuery->get_result();
                                    while ($comment = $commentsResult->fetch_assoc()):
                                        $likeCountQuery = $conn->prepare("SELECT COUNT(*) AS like_count FROM comment_likes WHERE comment_id = ?");
                                        $likeCountQuery->bind_param("i", $comment['id']);
                                        $likeCountQuery->execute();
                                        $likeCountResult = $likeCountQuery->get_result()->fetch_assoc();
                                        $likeCount = $likeCountResult['like_count'];
                                        
                                        // Consultar si el usuario ha dado like a un comentario
                                        $hasLikedCommentQuery = $conn->prepare("SELECT id FROM comment_likes WHERE user_id = ? AND comment_id = ?");
                                        $hasLikedCommentQuery->bind_param("ii", $currentUserId, $comment['id']);
                                        $hasLikedCommentQuery->execute();
                                        $hasLikedComment = $hasLikedCommentQuery->get_result()->num_rows > 0;
                                    ?>
                                        <div class="comment">
                                            <img src="<?= $comment['profile_picture'] ?>" alt="Perfil" class="comment-profile-img">
                                            <div style="flex: 1;">
                                                <strong><a href="profile.php?user_id=<?= $comment['user_id'] ?>" style="text-decoration: none; color: inherit;"><?= htmlspecialchars($comment['username']) ?></a>:</strong> 
                                                <span><?= htmlspecialchars($comment['comment_text']) ?></span>
                                                
                                                <div class="d-flex align-items-center mt-1">
                                                    <!-- Mostrar el like del comentario -->
                                                    <a href="?user_id=<?= $profileUserId ?>&like_comment_id=<?= $comment['id'] ?>" class="btn btn-sm mr-2">
                                                        <?= $hasLikedComment ? '❤️' : '🤍' ?> <?= $likeCount ?>
                                                    </a>
                                                    
                                                    <?php if ($comment['user_id'] == $currentUserId || $post['user_id'] == $currentUserId): ?>
                                                        <a href="?user_id=<?= $profileUserId ?>&delete_comment_id=<?= $comment['id'] ?>" class="btn btn-sm">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle mr-2"></i> No hay publicaciones para mostrar.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(postId) {
            if (confirm("¿Estás seguro de que deseas eliminar esta publicación?")) {
                window.location.href = 'delete_post.php?id=' + postId;
            }
        }
    </script>
</body>
</html>