<?php
session_start();
require_once 'db_connect.php'; 

// Redirigir si ya está logueado
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validaciones básicas
    if (strlen($username) < 3) {
        $error = "El nombre de usuario debe tener al menos 3 caracteres.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, introduce un correo electrónico válido.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Verificar que el correo no esté ya registrado
        $query = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $query->bind_param("s", $email);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            $error = "Ya existe una cuenta con ese correo electrónico.";
        } else {
            // Verificar que el nombre de usuario no esté ya registrado
            $query = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $query->bind_param("s", $username);
            $query->execute();
            $result = $query->get_result();

            if ($result->num_rows > 0) {
                $error = "El nombre de usuario ya está en uso.";
            } else {
                // Hashear la contraseña
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // Imagen de perfil por defecto
                $default_profile = "default-avatar.png";

                // Insertar el nuevo usuario en la base de datos
                $insert_query = $conn->prepare("INSERT INTO users (username, email, password, profile_picture) VALUES (?, ?, ?, ?)");
                $insert_query->bind_param("ssss", $username, $email, $hashed_password, $default_profile);

                if ($insert_query->execute()) {
                    $success = "¡Registro exitoso! Ahora puedes iniciar sesión.";
                    // Redirigir después de 2 segundos
                    header("refresh:2;url=index.php");
                } else {
                    $error = "Error al registrar. Inténtalo nuevamente.";
                }
            }
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="register-container">
        <img src="logo.png" alt="Switch Logo" class="logo">
        <h2>Crear una cuenta</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="username">Nombre de Usuario</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Elige un nombre de usuario" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Ingresa tu correo electrónico" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Crea una contraseña segura" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirma tu contraseña" required>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-block">Registrarse</button>
            </div>
        </form>
        
        <div class="register-footer">
            <p>¿Ya tienes cuenta? <a href="index.php">Inicia sesión aquí</a></p>
            <p>Al registrarte, aceptas nuestros <a href="#">Términos y Condiciones</a> y <a href="#">Política de Privacidad</a>.</p>
        </div>
    </div>

    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .register-container {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .input-with-icon input {
            padding-left: 40px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</body>
</html>