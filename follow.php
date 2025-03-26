<?php
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Obtener el ID del usuario que se está siguiendo
$followUserId = intval($_POST['follow_user_id']);
$currentUserId = $_SESSION['user_id'];

// Insertar la relación de seguimiento en la base de datos
$followQuery = $conn->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'accepted')");
$followQuery->bind_param("ii", $currentUserId, $followUserId);
$followQuery->execute();

// Redirigir de vuelta al perfil
header("Location: profile.php?user_id=" . $followUserId);
exit;
?>