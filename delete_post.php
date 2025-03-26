<?php
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Verificar si se ha proporcionado un ID de publicación
if (isset($_GET['id'])) {
    $postId = (int)$_GET['id'];

    // Verificar si la publicación pertenece al usuario
    $checkQuery = $conn->prepare("SELECT user_id FROM posts WHERE id = ? AND user_id = ?");
    $checkQuery->bind_param("ii", $postId, $userId);
    $checkQuery->execute();
    $checkResult = $checkQuery->get_result();

    if ($checkResult->num_rows > 0) {
        // Eliminar la publicación
        $deleteQuery = $conn->prepare("DELETE FROM posts WHERE id = ?");
        $deleteQuery->bind_param("i", $postId);
        $deleteQuery->execute();

        // Redirigir de vuelta al dashboard con un mensaje de éxito
        header("Location: dashboard.php?success=1");
        exit;
    } else {
        // Si la publicación no pertenece al usuario, redirigir con un mensaje de error
        header("Location: dashboard.php?error=1");
        exit;
    }
}

// Redirigir al dashboard si no se proporciona un ID
header("Location: dashboard.php");
exit;
?>