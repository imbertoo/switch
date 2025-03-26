<?php
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];

$query = $conn->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
$query->bind_param("i", $userId);
$query->execute();
$userData = $query->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newUsername = $_POST['username'];
    $profilePicture = $_FILES['profile_picture'];

    // Manejar la subida de la nueva foto de perfil
    if ($profilePicture['error'] == UPLOAD_ERR_OK) {
        $targetDir = "uploads/"; // Directorio donde se guardarán las imágenes
        $targetFile = $targetDir . basename($profilePicture["name"]);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Verificar el tipo de imagen
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            // Mover el archivo a la carpeta deseada
            move_uploaded_file($profilePicture["tmp_name"], $targetFile);
            // Actualizar la base de datos con la nueva ruta de la imagen
            $updateQuery = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $updateQuery->bind_param("si", $targetFile, $userId);
            $updateQuery->execute();
        }
    }

    // Actualizar el nombre de usuario
    $updateUsernameQuery = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
    $updateUsernameQuery->bind_param("si", $newUsername, $userId);
    $updateUsernameQuery->execute();

    // Redirigir al dashboard después de la actualización
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - ShareMyGym</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Editar Perfil</h2>
        <form action="edit_profile.php" method="post" enctype="multipart/form-data">
            <label for="username">Nombre de Usuario:</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($userData['username']) ?>" required>

            <label for="profile_picture">Foto de Perfil:</label>
            <input type="file" id="profile_picture" name="profile_picture" accept="image/*">

            <button type="submit">Guardar Cambios</button>
        </form>
        <a href="dashboard.php">Volver al Dashboard</a>
    </div>
</body>
</html>
