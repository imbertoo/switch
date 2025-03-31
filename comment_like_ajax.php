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
if (!isset($_POST['comment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$commentId = intval($_POST['comment_id']);

// Verificar si ya le dio like al comentario
$checkLikeQuery = $conn->prepare("SELECT id FROM comment_likes WHERE user_id = ? AND comment_id = ?");
$checkLikeQuery->bind_param("ii", $userId, $commentId);
$checkLikeQuery->execute();
$likeResult = $checkLikeQuery->get_result();

if ($likeResult->num_rows > 0) {
    // Si ya le dio like, eliminar el like
    $removeLikeQuery = $conn->prepare("DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?");
    $removeLikeQuery->bind_param("ii", $userId, $commentId);
    $success = $removeLikeQuery->execute();
    $action = 'unlike';
} else {
    // Si no le dio like, agregar el like
    $likeQuery = $conn->prepare("INSERT INTO comment_likes (user_id, comment_id) VALUES (?, ?)");
    $likeQuery->bind_param("ii", $userId, $commentId);
    $success = $likeQuery->execute();
    $action = 'like';
}

// Obtener el número actualizado de likes
$likeCountQuery = $conn->prepare("SELECT COUNT(*) AS like_count FROM comment_likes WHERE comment_id = ?");
$likeCountQuery->bind_param("i", $commentId);
$likeCountQuery->execute();
$likeCount = $likeCountQuery->get_result()->fetch_assoc()['like_count'];

echo json_encode([
    'success' => $success,
    'action' => $action,
    'likeCount' => $likeCount
]);
?>