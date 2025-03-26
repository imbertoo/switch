<?php
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Obtener los datos necesarios
$currentUserId = $_SESSION['user_id'];
$postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

// Verificar si el usuario ha dado like a la publicación
$likeCheckQuery = $conn->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
$likeCheckQuery->bind_param("ii", $postId, $currentUserId);
$likeCheckQuery->execute();

if ($likeCheckQuery->get_result()->num_rows > 0) {
    // Eliminar el like de la base de datos
    $deleteLikeQuery = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
    $deleteLikeQuery->bind_param("ii", $postId, $currentUserId);
    $deleteLikeQuery->execute();
}

header("Location: profile.php?user_id=" . $currentUserId);
exit;
?>