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

// Verificar si el usuario ya ha dado like a la publicación
$likeCheckQuery = $conn->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
$likeCheckQuery->bind_param("ii", $postId, $currentUserId);
$likeCheckQuery->execute();

if ($likeCheckQuery->get_result()->num_rows == 0) {
    // Insertar el like en la base de dato
    $insertLikeQuery = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
    $insertLikeQuery->bind_param("ii", $postId, $currentUserId);
    $insertLikeQuery->execute();
}

header("Location: profile.php?user_id=" . $currentUserId);
exit;
?>
