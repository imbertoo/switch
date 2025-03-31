<?php
session_start();
require_once 'db_connect.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$userId = $_SESSION['user_id'];

// Verificar si se recibieron los datos necesarios
if (!isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$postId = intval($_POST['post_id']);

// Verificar si ya le dio like a la publicación
$checkLikeQuery = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
$checkLikeQuery->bind_param("ii", $userId, $postId);
$checkLikeQuery->execute();
$likeResult = $checkLikeQuery->get_result();

if ($likeResult->num_rows > 0) {
    // Si ya le dio like, eliminar el like
    $removeLikeQuery = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
    $removeLikeQuery->bind_param("ii", $userId, $postId);
    $success = $removeLikeQuery->execute();
    $action = 'unlike';
} else {
    // Si no le dio like, agregar el like
    $likeQuery = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $likeQuery->bind_param("ii", $userId, $postId);
    $success = $likeQuery->execute();
    $action = 'like';
}

// Obtener el número actualizado de likes
$likeCountQuery = $conn->prepare("SELECT COUNT(*) AS like_count FROM likes WHERE post_id = ?");
$likeCountQuery->bind_param("i", $postId);
$likeCountQuery->execute();
$likeCount = $likeCountQuery->get_result()->fetch_assoc()['like_count'];

echo json_encode([
    'success' => $success,
    'action' => $action,
    'likeCount' => $likeCount
]);
?>