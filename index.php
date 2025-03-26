<?php
session_start();
require_once 'db_connect.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login']; 
    $password = $_POST['password'];

    $query = $conn->prepare("SELECT id, username, email, password FROM users WHERE username = ? OR email = ?");
    $query->bind_param("ss", $login, $login); 
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verificar la contraseña
        if (password_verify($password, $user['password'])) {
            // Iniciar sesión y almacenar datos del usuario
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Redirigir al panel principal o página de inicio de la app
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "No se encontró una cuenta con ese nombre de usuario o correo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Switch</title>
    <link rel="stylesheet" href="styles.css"> 
</head>
<body>
    <div class="login-container">
    <h2>Iniciar Sesión</h2>
        
        <?php if (!empty($error)) { ?>
            <p class="error"><?= $error ?></p>
        <?php } ?>

        <form method="POST" action="index.php">
            <label for="login">Nombre de Usuario o Correo Electrónico:</label>
            <input type="text" name="login" id="login" required>
            
            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" required>
            
            <button type="submit">Iniciar Sesión</button>
        </form>
        
        <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
    </div>
</body>
</html>