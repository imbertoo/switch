<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(array('success' => false, 'message' => 'Usuario no autenticado'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
    exit;
}

$userId = $_SESSION['user_id'];
$postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
$commentText = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : '';

if ($postId <= 0) {
    echo json_encode(array('success' => false, 'message' => 'ID de publicación inválido'));
    exit;
}

if (empty($commentText)) {
    echo json_encode(array('success' => false, 'message' => 'El comentario no puede estar vacío'));
    exit;
}

// Verificar que la publicación existe
$postCheckQuery = $conn->prepare("SELECT id FROM posts WHERE id = ?");
$postCheckQuery->bind_param("i", $postId);
$postCheckQuery->execute();
if ($postCheckQuery->get_result()->num_rows === 0) {
    echo json_encode(array('success' => false, 'message' => 'La publicación no existe'));
    exit;
}

// Insertar el comentario
$commentQuery = $conn->prepare("INSERT INTO comments (user_id, post_id, comment_text) VALUES (?, ?, ?)");
$commentQuery->bind_param("iis", $userId, $postId, $commentText);

if ($commentQuery->execute()) {
    $commentId = $conn->insert_id;
    
    // Obtener los datos del comentario recién creado
    $getCommentQuery = $conn->prepare("
        SELECT comments.id, comments.comment_text, comments.user_id, comments.created_at,
               users.username, users.profile_picture 
        FROM comments 
        JOIN users ON comments.user_id = users.id 
        WHERE comments.id = ?
    ");
    $getCommentQuery->bind_param("i", $commentId);
    $getCommentQuery->execute();
    $commentData = $getCommentQuery->get_result()->fetch_assoc();
    
    echo json_encode(array(
        'success' => true, 
        'message' => 'Comentario agregado exitosamente',
        'comment' => $commentData
    ));
} else {
    echo json_encode(array('success' => false, 'message' => 'Error al agregar el comentario'));
}
?>