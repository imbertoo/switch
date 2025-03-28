<?php
session_start();
require_once 'db_connect.php'; 
header('Content-Type: application/json');

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'No has iniciado sesión']);
  exit;
}

// Verificar si se recibió el ID del usuario a seguir
if (!isset($_POST['follow_user_id']) || empty($_POST['follow_user_id'])) {
  echo json_encode(['success' => false, 'message' => 'ID de usuario no proporcionado']);
  exit;
}

$followUserId = intval($_POST['follow_user_id']);
$currentUserId = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : 'follow';

// Verificar que no se esté intentando seguir a sí mismo
if ($followUserId === $currentUserId) {
  echo json_encode(['success' => false, 'message' => 'No puedes seguirte a ti mismo']);
  exit;
}

// Verificar que el usuario a seguir existe
$checkUserQuery = $conn->prepare("SELECT id FROM users WHERE id = ?");
$checkUserQuery->bind_param("i", $followUserId);
$checkUserQuery->execute();
$userResult = $checkUserQuery->get_result();

if ($userResult->num_rows === 0) {
  echo json_encode(['success' => false, 'message' => 'El usuario que intentas seguir no existe']);
  exit;
}

// Procesar la acción según sea seguir o dejar de seguir
if ($action === 'follow') {
  // Verificar si ya sigue a este usuario
  $checkQuery = $conn->prepare("SELECT id FROM friends WHERE user_id = ? AND friend_id = ?");
  $checkQuery->bind_param("ii", $currentUserId, $followUserId);
  $checkQuery->execute();
  $result = $checkQuery->get_result();

  if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Ya sigues a este usuario']);
    exit;
  }

  // Insertar la relación de seguimiento en la base de datos
  $followQuery = $conn->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'accepted')");
  $followQuery->bind_param("ii", $currentUserId, $followUserId);

  if ($followQuery->execute()) {
    // Registrar la actividad de seguimiento (opcional)
    $activityQuery = $conn->prepare("INSERT INTO activities (user_id, activity_type, related_user_id, created_at) VALUES (?, 'follow', ?, NOW())");
    if ($activityQuery) {
      $activityQuery->bind_param("ii", $currentUserId, $followUserId);
      $activityQuery->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Ahora sigues a este usuario']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Error al seguir al usuario: ' . $conn->error]);
  }
} else if ($action === 'unfollow') {
  // Eliminar la relación de seguimiento
  $unfollowQuery = $conn->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ?");
  $unfollowQuery->bind_param("ii", $currentUserId, $followUserId);

  if ($unfollowQuery->execute()) {
    // Si se eliminó al menos una fila, significa que la operación fue exitosa
    if ($unfollowQuery->affected_rows > 0) {
      echo json_encode(['success' => true, 'message' => 'Has dejado de seguir a este usuario']);
    } else {
      // Si no se eliminó ninguna fila, significa que no estaba siguiendo al usuario
      echo json_encode(['success' => false, 'message' => 'No estabas siguiendo a este usuario']);
    }
  } else {
    echo json_encode(['success' => false, 'message' => 'Error al dejar de seguir al usuario: ' . $conn->error]);
  }
} else {
  echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

// Cerrar las conexiones y liberar recursos
if (isset($checkUserQuery)) $checkUserQuery->close();
if (isset($checkQuery)) $checkQuery->close();
if (isset($followQuery)) $followQuery->close();
if (isset($unfollowQuery)) $unfollowQuery->close();
if (isset($activityQuery)) $activityQuery->close();

// Cerrar la conexión a la base de datos
$conn->close();
?>