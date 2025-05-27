<?php
session_start();
require_once '../db_connect.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => 'Usuario no autenticado'));
    exit;
}

// Verificar si se recibió el ID de la publicación
if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => 'ID de publicación no válido'));
    exit;
}

$userId = $_SESSION['user_id'];
$postId = intval($_POST['post_id']);

// Verificar si el usuario ya le dio like a la publicación
$checkLikeQuery = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
$checkLikeQuery->bind_param("ii", $userId, $postId);
$checkLikeQuery->execute();
$likeResult = $checkLikeQuery->get_result();

$action = '';

if ($likeResult->num_rows > 0) {
    // Si ya le dio like, eliminar el like
    $removeLikeQuery = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
    $removeLikeQuery->bind_param("ii", $userId, $postId);
    $removeLikeQuery->execute();
    $action = 'unlike';
} else {
    // Si no le dio like, agregar el like
    $likeQuery = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $likeQuery->bind_param("ii", $userId, $postId);
    $likeQuery->execute();
    $action = 'like';
}

// Obtener el número actualizado de likes
$likeCountQuery = $conn->prepare("SELECT COUNT(*) AS like_count FROM likes WHERE post_id = ?");
$likeCountQuery->bind_param("i", $postId);
$likeCountQuery->execute();
$likeCount = $likeCountQuery->get_result()->fetch_assoc()['like_count'];

// Devolver respuesta JSON
header('Content-Type: application/json');
echo json_encode(array(
    'success' => true,
    'action' => $action,
    'likeCount' => $likeCount
));
?>