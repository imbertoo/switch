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

// Obtener todas las acciones recientes (likes, comentarios y nuevos seguidores)
$query = $conn->prepare("
    SELECT
        actions.type,
        actions.created_at,
        actions.content,
        actions.extra,
        actions.username,
        actions.profile_picture
    FROM (
        -- Likes
        SELECT 
            'like' AS type,
            likes.created_at,
            posts.content,
            NULL AS extra,
            user_like.username,
            user_like.profile_picture
        FROM likes
        JOIN posts ON likes.post_id = posts.id
        JOIN users AS user_like ON likes.user_id = user_like.id
        WHERE posts.user_id = ?

        UNION ALL

        -- Comentarios
        SELECT 
            'comment' AS type,
            comments.created_at,
            posts.content,
            comments.comment_text AS extra,
            user_comment.username,
            user_comment.profile_picture
        FROM comments
        JOIN posts ON comments.post_id = posts.id
        JOIN users AS user_comment ON comments.user_id = user_comment.id
        WHERE posts.user_id = ?

        UNION ALL

        -- Seguidores
        SELECT
            'follow' AS type,
            followers.created_at,
            NULL AS content,
            NULL AS extra,
            user_follower.username,
            user_follower.profile_picture
        FROM followers
        JOIN users AS user_follower ON followers.follower_id = user_follower.id
        WHERE followers.followed_id = ?
    ) AS actions
    ORDER BY actions.created_at DESC
");
$query->bind_param("iii", $userId, $userId, $userId);
$query->execute();
$result = $query->get_result();

// Obtener datos del usuario
$userQuery = $conn->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
$userQuery->bind_param("i", $userId);
$userQuery->execute();
$userData = $userQuery->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - ShareMyGym</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <button onclick="location.href='dashboard.php'">Inicio</button>
            <button onclick="location.href='feed.php'">Feed</button>
            <button onclick="location.href='upload.php'">Subir publicación</button>
            <button onclick="location.href='profile.php'">Mi Perfil</button>
            <button onclick="location.href='logout.php'">Cerrar Sesión</button>
        </nav>

        <div class="main-content">
            <header>
                <div class="user-info">
                    <img src="<?= htmlspecialchars($userData['profile_picture']) ?>" alt="Perfil" class="profile-img">
                    <span><?= htmlspecialchars($userData['username']) ?></span>
                    <div class="dropdown">
                        <button class="dropbtn">▼</button>
                        <div class="dropdown-content">
                            <a href="edit_profile.php">Editar Perfil</a>
                            <a href="logout.php">Cerrar Sesión</a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="feed">
                <h2>Actividad reciente</h2>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="activity">
                            <?php if ($row['type'] === 'like'): ?>
                                <div class="activity-content">
                                    <p>
                                        <strong>
                                            <img src="<?= htmlspecialchars($row['profile_picture']) ?>" alt="Perfil" class="post-profile-img">
                                            <?= htmlspecialchars($row['username']) ?>
                                        </strong> le dio me gusta a tu publicación: <?= htmlspecialchars($row['content']) ?>
                                        (<?= htmlspecialchars($row['created_at']) ?>)
                                    </p>
                                </div>
                            <?php elseif ($row['type'] === 'comment'): ?>
                                <div class="activity-content">
                                    <p>
                                        <strong>
                                            <img src="<?= htmlspecialchars($row['profile_picture']) ?>" alt="Perfil" class="post-profile-img">
                                            <?= htmlspecialchars($row['username']) ?>
                                        </strong> comentó en tu publicación: <?= htmlspecialchars($row['extra']) ?>
                                        (<?= htmlspecialchars($row['created_at']) ?>)
                                    </p>
                                </div>
                            <?php elseif ($row['type'] === 'follow'): ?>
                                <div class="activity-content">
                                    <p>
                                        <strong>
                                            <img src="<?= htmlspecialchars($row['profile_picture']) ?>" alt="Perfil" class="post-profile-img">
                                            <?= htmlspecialchars($row['username']) ?>
                                        </strong> comenzó a seguirte. (<?= htmlspecialchars($row['created_at']) ?>)
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-activity">¡No hay actividad reciente!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>