<?php
session_start();
require_once 'db_connect.php';

// Redirigir si ya está logueado
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$message = '';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Por favor, ingresa tu correo electrónico.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, ingresa un correo electrónico válido.";
    } else {
        // Verificar si el email existe en la base de datos
        $query = $conn->prepare("SELECT id, username, email FROM users WHERE email = ?");
        $query->bind_param("s", $email);
        $query->execute();
        $result = $query->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Generar nueva contraseña aleatoria
            $newPassword = generateRandomPassword();
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Actualizar la contraseña en la base de datos
            $updateQuery = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateQuery->bind_param("si", $hashedPassword, $user['id']);
            
            if ($updateQuery->execute()) {
                // Enviar email con la nueva contraseña
                if (sendPasswordResetEmail($user['email'], $user['username'], $newPassword)) {
                    $success = true;
                    $message = "Se ha enviado una nueva contraseña a tu correo electrónico. Por favor, revisa tu bandeja de entrada.";
                } else {
                    $error = "Error al enviar el correo. Por favor, inténtalo de nuevo más tarde.";
                }
            } else {
                $error = "Error al procesar la solicitud. Por favor, inténtalo de nuevo.";
            }
        } else {
            // Por seguridad, mostramos el mismo mensaje aunque el email no exista
            $success = true;
            $message = "Si el correo electrónico está registrado, recibirás una nueva contraseña en tu bandeja de entrada.";
        }
    }
}

function generateRandomPassword($length = 12) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    $charactersLength = strlen($characters);
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, $charactersLength - 1)];
    }
    
    return $password;
}

function sendPasswordResetEmail($email, $username, $newPassword) {
    $to = $email;
    $subject = "Recuperación de Contraseña - Switch";
    
    $message = "
    <html>
    <head>
        <title>Recuperación de Contraseña - Switch</title>
        <style>
            body { font-family: 'Poppins', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #4361ee; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background-color: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
            .password-box { background-color: white; padding: 15px; border-radius: 8px; border-left: 4px solid #4361ee; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            .btn { display: inline-block; background-color: #4361ee; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Recuperación de Contraseña</h1>
                <p>Switch - Red Social</p>
            </div>
            <div class='content'>
                <h2>¡Hola, " . htmlspecialchars($username) . "!</h2>
                <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en Switch.</p>
                
                <p>Tu nueva contraseña temporal es:</p>
                <div class='password-box'>
                    <strong style='font-size: 18px; color: #4361ee;'>" . htmlspecialchars($newPassword) . "</strong>
                </div>
                
                <p><strong>⚠️ Importante:</strong></p>
                <ul>
                    <li>Esta es una contraseña temporal generada automáticamente</li>
                    <li>Te recomendamos cambiarla por una personalizada lo antes posible</li>
                    <li>Para cambiar tu contraseña, ve a tu perfil y selecciona 'Editar Perfil'</li>
                </ul>
                
                <p>Si no solicitaste este cambio, por favor contacta con nuestro equipo de soporte inmediatamente.</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='#' class='btn'>Iniciar Sesión en Switch</a>
                </div>
            </div>
            <div class='footer'>
                <p>Este correo fue enviado automáticamente desde Switch TFG</p>
                <p>© 2025 Switch. Todos los derechos reservados.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Switch TFG <switchtfg@gmail.com>" . "\r\n";
    $headers .= "Reply-To: switchtfg@gmail.com" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Switch</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="forgot-password-container">
        <img src="logo.png" alt="Switch Logo" class="logo">
        
        <?php if ($success): ?>
            <div class="success-container">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>¡Correo Enviado!</h2>
                <div class="success-message">
                    <i class="fas fa-envelope"></i>
                    <?= $message ?>
                </div>
                <div class="success-actions">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Volver al Login
                    </a>
                    <p class="help-text">
                        ¿No recibiste el correo? Revisa tu carpeta de spam o 
                        <a href="forgot_password.php">inténtalo de nuevo</a>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <h2>Recuperar Contraseña</h2>
            <p class="subtitle">Ingresa tu correo electrónico y te enviaremos una nueva contraseña</p>
            
            <?php if (!empty($error)): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="forgot_password.php" id="forgotPasswordForm">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" id="email" class="form-control" 
                               placeholder="Ingresa tu correo electrónico" required
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-block" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Enviar Nueva Contraseña
                    </button>
                </div>
            </form>
            
            <div class="back-to-login">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Volver al Login
                </a>
            </div>
        <?php endif; ?>
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
        
        .forgot-password-container {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
            padding: 2rem;
            background-color: var(--bg-light);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .forgot-password-container h2 {
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            font-size: 1.75rem;
            font-weight: 600;
        }
        
        .subtitle {
            color: var(--text-light);
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-lighter);
        }
        
        .input-with-icon input {
            padding-left: 40px;
        }
        
        .back-to-login {
            margin-top: 1.5rem;
        }
        
        .back-link {
            color: var(--text-light);
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: var(--radius-sm);
            transition: var(--transition);
        }
        
        .back-link:hover {
            color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.1);
        }
        
        /* Success Styles */
        .success-container {
            text-align: center;
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 1rem;
            animation: bounceIn 0.6s ease-out;
        }
        
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .success-container h2 {
            color: var(--success-color);
            margin-bottom: 1rem;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-align: left;
        }
        
        .success-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .help-text {
            font-size: 0.85rem;
            color: var(--text-light);
            margin: 0;
        }
        
        .help-text a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .help-text a:hover {
            text-decoration: underline;
        }
        
        /* Loading state */
        .btn:disabled {
            background-color: var(--text-lighter);
            cursor: not-allowed;
        }
        
        @media (max-width: 480px) {
            .forgot-password-container {
                padding: 1.5rem;
                margin: 1rem;
            }
        }
    </style>

    <script>
        document.getElementById('forgotPasswordForm').addEventListener('submit', function() {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        });
        
        // Auto-focus en el campo de email
        document.getElementById('email').focus();
    </script>
</body>
</html>