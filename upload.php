<?php
session_start();
require_once 'db_connect.php'; // Archivo para conectar a la base de datos

// Verificar si el usuario está conectado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];
    $content = $_POST['content'];
    $imageUrl = NULL;
    $videoUrl = NULL;

    // Manejo de la subida de imagen
    if ($_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $targetDir = "uploads/"; // Directorio donde se guardarán las imágenes
        $targetFile = $targetDir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Verificar el tipo de imagen
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile);
            $imageUrl = $targetFile;
        }
    }

    // Manejo de la subida de video
    if ($_FILES['video']['error'] == UPLOAD_ERR_OK) {
        $targetDir = "uploads/videos/"; // Directorio donde se guardarán los videos
        $targetFile = $targetDir . basename($_FILES["video"]["name"]);
        $videoFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Verificar el tipo de video
        if (in_array($videoFileType, ['mp4', 'avi', 'mov'])) {
            move_uploaded_file($_FILES["video"]["tmp_name"], $targetFile);
            $videoUrl = $targetFile;
        }
    }

    // Insertar la publicación en la base de datos
    $query = $conn->prepare("INSERT INTO posts (user_id, content, image_url, video_url, created_at) VALUES (?, ?, ?, ?, NOW())");
    $query->bind_param("isss", $userId, $content, $imageUrl, $videoUrl);
    $query->execute();

    // Redirigir al dashboard después de subir la publicación
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Publicación - ShareMyGym</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="dashboard-container">
    <nav class="sidebar">
        <button onclick="location.href='dashboard.php'">Inicio</button>
        <button onclick="location.href='feed.php'">Feed</button>
        <button onclick="location.href='upload.php'">Subir publicación</button>
        <button onclick="location.href='profile.php'">Mi Perfil</button>
        <button onclick="location.href='logout.php'">Cerrar Sesión</button>
    </nav>

    <div class="container">
        <h2>Subir Publicación</h2>
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <label for="content">Contenido:</label>
            <textarea id="content" name="content" rows="4" required></textarea>

            <label for="image">Imagen (opcional):</label>
            <input type="file" id="image" name="image" accept="image/*">

            <label for="video">Video (opcional):</label>
            <input type="file" id="video" name="video" accept="video/*">

            <button type="submit">Subir Publicación</button>
        </form>
        <a href="dashboard.php">Volver al Dashboard</a>
    </div>
</div>
</body>
</html>