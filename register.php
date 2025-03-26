<?php
session_start();
require_once 'db_connect.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Validar que el correo no esté ya registrado
    $query = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $error = "Ya existe una cuenta con ese correo.";
    } else {
        // Hashear la contraseña
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insertar el nuevo usuario en la base de datos
        $insert_query = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $insert_query->bind_param("sss", $username, $email, $hashed_password);

        if ($insert_query->execute()) {
            // Redirigir al inicio de sesión después de un registro exitoso
            header("Location: index.php");
            exit;
        } else {
            $error = "Error al registrar. Inténtalo nuevamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse - Switch</title>
    <link rel="stylesheet" href="styles.css"> 
</head>
<body>
    <div class="register-container">
    <img src="logo.png" alt="Logo de ShareMyGym" class="logo" width="220px" height="220px" style="display: block; margin: 0 auto;"> <!-- Logo -->
        <h2>Registrarse</h2>
        
        <?php if (!empty($error)) { ?>
            <p class="error"><?= $error ?></p>
        <?php } ?>

        <form method="POST" action="register.php">
            <label for="username">Nombre de Usuario:</label>
            <input type="text" name="username" id="username" required>
            
            <label for="email">Correo Electrónico:</label>
            <input type="email" name="email" id="email" required>
            
            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" required>
            
            <button type="submit">Registrarse</button>
        </form>
        
        <p>¿Ya tienes cuenta? <a href="index.php">Inicia sesión aquí</a></p>
    </div>
</body>
</html>
