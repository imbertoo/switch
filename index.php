<?php
session_start();
require_once 'db_connect.php'; 

// Redirigir si ya está logueado
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

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
            
            // Redirigir al panel principal
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <img src="logo.png" alt="Switch Logo" class="logo">
        <h2>Iniciar Sesión</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <div class="form-group">
                <label for="login">Usuario o Email</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="login" id="login" class="form-control" placeholder="Ingresa tu usuario o email" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Ingresa tu contraseña" required>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-block">Iniciar Sesión</button>
            </div>
        </form>
        
        <div class="login-footer">
            <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
            <p><a href="#">¿Olvidaste tu contraseña?</a></p>
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
        
        .login-container {
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
    </style>
</body>
</html>