<?php
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Obtener el ID del usuario que desea dejar de seguir
$currentUserId = $_SESSION['user_id'];
$unfollowUserId = intval($_POST['unfollow_user_id']);

// Eliminar la relación de amistad
$unfollowQuery = $conn->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ? AND status = 'accepted'");
$unfollowQuery->bind_param("ii", $currentUserId, $unfollowUserId);
$unfollowQuery->execute();

// Redirigir de vuelta al perfil
header("Location: profile.php?user_id=$unfollowUserId");
exit;
?>
