<?php
session_start();
require_once 'db_connect.php'; // Conexión a la base de datos

// Verificar si el usuario está conectado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Obtener los datos necesarios
$currentUserId = $_SESSION['user_id'];
$postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

// Verificar que el comentario no esté vacío
if (!empty($content)) {
    // Insertar el comentario en la base de datos
    $insertCommentQuery = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $insertCommentQuery->bind_param("iis", $postId, $currentUserId, $content);
    $insertCommentQuery->execute();
}

header("Location: profile.php?user_id=" . $currentUserId);
exit;
?>
