<?php
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$errorMsg = '';
$successMsg = '';

// Obtener datos actuales del usuario
$query = $conn->prepare("SELECT username, profile_picture, password FROM users WHERE id = ?");
$query->bind_param("i", $userId);
$query->execute();
$userData = $query->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newUsername = trim($_POST['username']);
    $currentProfilePicture = $userData['profile_picture'];
    
    // Variables para cambio de contraseña
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $changePassword = !empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword);

    // Validar nombre de usuario
    if (empty($newUsername)) {
        $errorMsg = "El nombre de usuario no puede estar vacío.";
    } elseif (strlen($newUsername) < 3) {
        $errorMsg = "El nombre de usuario debe tener al menos 3 caracteres.";
    } elseif (strlen($newUsername) > 50) {
        $errorMsg = "El nombre de usuario no puede tener más de 50 caracteres.";
    } else {
        // Verificar si el nombre de usuario ya existe (excluyendo el usuario actual)
        $checkUsernameQuery = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $checkUsernameQuery->bind_param("si", $newUsername, $userId);
        $checkUsernameQuery->execute();
        $usernameExists = $checkUsernameQuery->get_result()->num_rows > 0;

        if ($usernameExists) {
            $errorMsg = "Este nombre de usuario ya está en uso. Por favor, elige otro.";
        }
    }

    // Validar cambio de contraseña si se proporcionaron datos
    if (empty($errorMsg) && $changePassword) {
        if (empty($currentPassword)) {
            $errorMsg = "Debes proporcionar tu contraseña actual.";
        } elseif (empty($newPassword)) {
            $errorMsg = "Debes proporcionar una nueva contraseña.";
        } elseif (strlen($newPassword) < 6) {
            $errorMsg = "La nueva contraseña debe tener al menos 6 caracteres.";
        } elseif ($newPassword !== $confirmPassword) {
            $errorMsg = "Las nuevas contraseñas no coinciden.";
        } elseif (!password_verify($currentPassword, $userData['password'])) {
            $errorMsg = "La contraseña actual es incorrecta.";
        } elseif ($currentPassword === $newPassword) {
            $errorMsg = "La nueva contraseña debe ser diferente a la actual.";
        }
    }

    // Manejar la subida de la nueva foto de perfil
    if (empty($errorMsg) && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $targetDir = "uploads/profiles/";
        
        // Crear el directorio si no existe
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = basename($_FILES["profile_picture"]["name"]);
        $targetFile = $targetDir . time() . '_' . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Verificar el tipo de imagen
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            // Verificar el tamaño del archivo (máximo 5MB)
            if ($_FILES["profile_picture"]["size"] <= 5000000) {
                if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile)) {
                    $currentProfilePicture = $targetFile;
                } else {
                    $errorMsg = "Hubo un error al subir tu imagen de perfil.";
                }
            } else {
                $errorMsg = "La imagen es demasiado grande. El tamaño máximo es 5MB.";
            }
        } else {
            $errorMsg = "Solo se permiten archivos JPG, JPEG, PNG y GIF.";
        }
    }

    // Si no hay errores, actualizar la base de datos
    if (empty($errorMsg)) {
        if ($changePassword) {
            // Actualizar con nueva contraseña
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateQuery = $conn->prepare("UPDATE users SET username = ?, profile_picture = ?, password = ? WHERE id = ?");
            $updateQuery->bind_param("sssi", $newUsername, $currentProfilePicture, $hashedPassword, $userId);
        } else {
            // Actualizar sin cambiar contraseña
            $updateQuery = $conn->prepare("UPDATE users SET username = ?, profile_picture = ? WHERE id = ?");
            $updateQuery->bind_param("ssi", $newUsername, $currentProfilePicture, $userId);
        }
        
        if ($updateQuery->execute()) {
            if ($changePassword) {
                $successMsg = "¡Tu perfil y contraseña han sido actualizados con éxito!";
            } else {
                $successMsg = "¡Tu perfil ha sido actualizado con éxito!";
            }
            // Actualizar los datos para mostrar los cambios
            $userData['username'] = $newUsername;
            $userData['profile_picture'] = $currentProfilePicture;
            
            // Redirigir después de 2 segundos
            header("refresh:2;url=profile.php");
        } else {
            $errorMsg = "Error al actualizar el perfil: " . $conn->error;
        }
    }
}

// Obtener usuarios para el chat
$chatUsersQuery = $conn->prepare("
    SELECT id, username, profile_picture 
    FROM users 
    WHERE id != ? 
    ORDER BY username ASC
");
$chatUsersQuery->bind_param("i", $userId);
$chatUsersQuery->execute();
$chatUsersResult = $chatUsersQuery->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Switch</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        .edit-profile-section {
            flex: 1;
            max-width: 600px;
        }

        .edit-profile-container {
            background-color: var(--bg-light);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            padding: 2rem;
        }

        .edit-profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .edit-profile-header h2 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 1.75rem;
            font-weight: 600;
        }

        .edit-profile-header p {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .current-profile-preview {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background-color: var(--bg-color);
            border-radius: var(--radius-md);
        }

        .current-profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            margin-bottom: 1rem;
        }

        .current-username {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .edit-profile-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
        }

        .file-input-container {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background-color: var(--bg-color);
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.95rem;
            color: var(--text-light);
        }

        .file-input-label:hover {
            border-color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.05);
            color: var(--primary-color);
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .preview-container {
            margin-top: 1rem;
            text-align: center;
            display: none;
        }

        .preview-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            box-shadow: var(--shadow-md);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
        }

        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            padding: 0.75rem 2rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            background-color: var(--secondary-color);
        }

        .btn-submit:disabled {
            background-color: var(--text-lighter);
            cursor: not-allowed;
        }

        .btn-cancel {
            background-color: var(--text-lighter);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            padding: 0.75rem 2rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-cancel:hover {
            background-color: var(--text-light);
            color: white;
            text-decoration: none;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .character-count {
            font-size: 0.8rem;
            color: var(--text-lighter);
            text-align: right;
            margin-top: 0.25rem;
        }

        .password-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .password-section h3 {
            color: var(--text-color);
            margin-bottom: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle-btn {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-lighter);
            cursor: pointer;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .password-toggle-btn:hover {
            color: var(--primary-color);
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.8rem;
        }

        .strength-weak {
            color: var(--danger-color);
        }

        .strength-medium {
            color: var(--warning-color);
        }

        .strength-strong {
            color: var(--success-color);
        }

        .password-requirements {
            font-size: 0.8rem;
            color: var(--text-lighter);
            margin-top: 0.5rem;
        }

        .password-requirements ul {
            margin: 0.5rem 0 0 1rem;
            padding: 0;
        }

        .password-requirements li {
            margin-bottom: 0.25rem;
        }

        .requirement-met {
            color: var(--success-color);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <img src="logo.png" alt="Switch Logo" class="sidebar-logo">
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php" class="sidebar-item">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
                <a href="feed.php" class="sidebar-item">
                    <i class="fas fa-heart"></i>
                    <span>Notificaciones</span>
                </a>
                <a href="upload.php" class="sidebar-item">
                    <i class="fas fa-plus-circle"></i>
                    <span>Publicar</span>
                </a>
                <a href="profile.php" class="sidebar-item active">
                    <i class="fas fa-user"></i>
                    <span>Perfil</span>
                </a>
                <a href="#" id="chatButton" class="sidebar-item chat-button">
                    <i class="fas fa-comments"></i>
                    <span>Chat</span>
                    <span id="unreadBadge" class="chat-badge" style="display: none;">0</span>
                </a>
                <a href="logout.php" class="sidebar-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Buscar usuarios..." onkeyup="searchUsers()">
                    <div id="searchResults" class="search-results" style="display: none;"></div>
                </div>
                <div class="user-info">
                    <a href="profile.php?user_id=<?= $userId ?>" class="user-profile">
                        <span><?= $userData['username'] ?></span>
                        <img src="<?= $userData['profile_picture'] ?>" alt="Perfil" class="profile-img">
                    </a>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Edit Profile Section -->
                <div class="edit-profile-section">
                    <div class="edit-profile-container">
                        <div class="edit-profile-header">
                            <h2>Editar Perfil</h2>
                            <p>Actualiza tu información personal, foto de perfil y contraseña</p>
                        </div>

                        <!-- Current Profile Preview -->
                        <div class="current-profile-preview">
                            <img src="<?= $userData['profile_picture'] ?>" alt="Perfil actual" class="current-profile-img" id="currentProfileImg">
                            <div class="current-username"><?= htmlspecialchars($userData['username']) ?></div>
                        </div>
                        
                        <?php if (!empty($errorMsg)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?= $errorMsg ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($successMsg)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?= $successMsg ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="edit_profile.php" method="post" enctype="multipart/form-data" id="editProfileForm" class="edit-profile-form">
                            <div class="form-group">
                                <label for="username">Nombre de Usuario</label>
                                <input type="text" id="username" name="username" class="form-control" 
                                       value="<?= htmlspecialchars($userData['username']) ?>" 
                                       required maxlength="50" minlength="3">
                                <div class="character-count">
                                    <span id="usernameCount"><?= strlen($userData['username']) ?></span>/50 caracteres
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="profile_picture">Foto de Perfil</label>
                                <div class="file-input-container">
                                    <label for="profile_picture" class="file-input-label">
                                        <i class="fas fa-camera"></i> Seleccionar nueva foto de perfil
                                    </label>
                                    <input type="file" id="profile_picture" name="profile_picture" class="file-input" accept="image/*">
                                </div>
                                <div class="preview-container" id="imagePreview">
                                    <img src="#" alt="Vista previa" class="preview-image">
                                </div>
                                <small style="color: var(--text-lighter); font-size: 0.8rem; margin-top: 0.5rem; display: block;">
                                    Formatos permitidos: JPG, JPEG, PNG, GIF. Tamaño máximo: 5MB
                                </small>
                            </div>

                            <!-- Password Section -->
                            <div class="password-section">
                                <h3><i class="fas fa-lock"></i> Cambiar Contraseña</h3>
                                <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 1.5rem;">
                                    Deja estos campos vacíos si no deseas cambiar tu contraseña
                                </p>

                                <div class="form-group">
                                    <label for="current_password">Contraseña Actual</label>
                                    <div class="password-toggle">
                                        <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Ingresa tu contraseña actual">
                                        <button type="button" class="password-toggle-btn" onclick="togglePassword('current_password')">
                                            <i class="fas fa-eye" id="current_password_icon"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="new_password">Nueva Contraseña</label>
                                    <div class="password-toggle">
                                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Ingresa tu nueva contraseña" minlength="6">
                                        <button type="button" class="password-toggle-btn" onclick="togglePassword('new_password')">
                                            <i class="fas fa-eye" id="new_password_icon"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength" id="passwordStrength"></div>
                                    <div class="password-requirements">
                                        <small>La contraseña debe cumplir con:</small>
                                        <ul>
                                            <li id="req-length">Al menos 6 caracteres</li>
                                            <li id="req-letter">Al menos una letra</li>
                                            <li id="req-number">Al menos un número</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password">Confirmar Nueva Contraseña</label>
                                    <div class="password-toggle">
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirma tu nueva contraseña">
                                        <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye" id="confirm_password_icon"></i>
                                        </button>
                                    </div>
                                    <div id="passwordMatch" style="font-size: 0.8rem; margin-top: 0.5rem;"></div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-submit" id="submitBtn">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                                <a href="profile.php" class="btn-cancel">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sidebar Section -->
                <div class="sidebar-section">
                    <div class="user-profile-card">
                        <a href="profile.php?user_id=<?= $userId ?>" class="user-profile-link">
                            <img src="<?= $userData['profile_picture'] ?>" alt="<?= $userData['username'] ?>" class="user-profile-img">
                            <div class="user-profile-info">
                                <h4><?= $userData['username'] ?></h4>
                                <p>Ver mi perfil</p>
                            </div>
                        </a>
                    </div>

                    <div class="footer-links">
                        <p>© 2025 Switch. Todos los derechos reservados.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel de Chat -->
    <div id="chatPanel" class="chat-panel">
        <div class="chat-header">
            <div id="chatHeaderTitle" class="d-flex align-items-center">
                <button id="backButton" class="btn-back" style="display: none;">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <span>Chat</span>
            </div>
            <div class="chat-header-actions">
                <button id="minimizeChat" class="btn-chat-action">
                    <i class="fas fa-minus"></i>
                </button>
                <button id="closeChat" class="btn-chat-action">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <!-- Lista de usuarios para el chat -->
        <div id="userListContainer" class="user-list">
            <?php while ($chatUser = $chatUsersResult->fetch_assoc()): ?>
                <div class="user-item" data-user-id="<?= $chatUser['id'] ?>" data-username="<?= $chatUser['username'] ?>">
                    <img src="<?= $chatUser['profile_picture'] ?>" alt="<?= $chatUser['username'] ?>">
                    <div class="user-item-info">
                        <span class="user-item-name"><?= $chatUser['username'] ?></span>
                        <span class="user-item-status">Pulsa para abrir el chat</span>
                    </div>
                    <span class="unread-badge" style="display: none;">0</span>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Área de mensajes -->
        <div id="chatBody" class="chat-body" style="display: none;">
            <!-- Los mensajes se cargarán aquí dinámicamente -->
        </div>
        
        <!-- Formulario para enviar mensajes -->
        <div id="chatFooter" class="chat-footer" style="display: none;">
            <div class="chat-input-container">
                <input type="text" id="chatInput" class="chat-input" placeholder="Escribe un mensaje...">
                <button id="chatSend" class="chat-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        function searchUsers() {
            var input = document.getElementById('searchInput').value;
            if (input.length > 0) {
                $.ajax({
                    url: 'search.php',
                    method: 'GET',
                    data: { query: input },
                    success: function(data) {
                        $('#searchResults').html(data);
                        $('#searchResults').show();
                    }
                });
            } else {
                $('#searchResults').hide();
            }
        }

        // Ocultar resultados al hacer clic fuera
        $(document).click(function(event) {
            if (!$(event.target).closest('#searchInput, #searchResults').length) {
                $('#searchResults').hide();
            }
        });

        // Contador de caracteres para el nombre de usuario
        $('#username').on('input', function() {
            const maxLength = 50;
            const currentLength = $(this).val().length;
            $('#usernameCount').text(currentLength);
            
            if (currentLength >= maxLength) {
                $('.character-count').css('color', '#dc3545');
            } else {
                $('.character-count').css('color', '#6c757d');
            }
        });

        // Vista previa de imagen
        $('#profile_picture').change(function() {
            const file = this.files[0];
            if (file) {
                // Validar tamaño del archivo (5MB)
                if (file.size > 5000000) {
                    alert('La imagen es demasiado grande. El tamaño máximo es 5MB.');
                    $(this).val('');
                    return;
                }

                // Validar tipo de archivo
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Solo se permiten archivos JPG, JPEG, PNG y GIF.');
                    $(this).val('');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#imagePreview img').attr('src', e.target.result);
                    $('#imagePreview').show();
                    $('#currentProfileImg').attr('src', e.target.result);
                }
                reader.readAsDataURL(file);
            } else {
                $('#imagePreview').hide();
                $('#currentProfileImg').attr('src', '<?= $userData['profile_picture'] ?>');
            }
        });

        // Función para mostrar/ocultar contraseñas
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '_icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Validación de fortaleza de contraseña
        $('#new_password').on('input', function() {
            const password = $(this).val();
            const strengthDiv = $('#passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.html('');
                resetRequirements();
                return;
            }

            let score = 0;
            let feedback = '';

            // Verificar longitud
            if (password.length >= 6) {
                score++;
                $('#req-length').addClass('requirement-met');
            } else {
                $('#req-length').removeClass('requirement-met');
            }

            // Verificar letras
            if (/[a-zA-Z]/.test(password)) {
                score++;
                $('#req-letter').addClass('requirement-met');
            } else {
                $('#req-letter').removeClass('requirement-met');
            }

            // Verificar números
            if (/\d/.test(password)) {
                score++;
                $('#req-number').addClass('requirement-met');
            } else {
                $('#req-number').removeClass('requirement-met');
            }

            // Mostrar fortaleza
            if (score < 2) {
                feedback = '<span class="strength-weak">Débil</span>';
            } else if (score === 2) {
                feedback = '<span class="strength-medium">Media</span>';
            } else {
                feedback = '<span class="strength-strong">Fuerte</span>';
            }

            strengthDiv.html('Fortaleza: ' + feedback);
        });

        // Verificar coincidencia de contraseñas
        $('#confirm_password').on('input', function() {
            const newPassword = $('#new_password').val();
            const confirmPassword = $(this).val();
            const matchDiv = $('#passwordMatch');

            if (confirmPassword.length === 0) {
                matchDiv.html('');
                return;
            }

            if (newPassword === confirmPassword) {
                matchDiv.html('<span style="color: var(--success-color);"><i class="fas fa-check"></i> Las contraseñas coinciden</span>');
            } else {
                matchDiv.html('<span style="color: var(--danger-color);"><i class="fas fa-times"></i> Las contraseñas no coinciden</span>');
            }
        });

        function resetRequirements() {
            $('#req-length, #req-letter, #req-number').removeClass('requirement-met');
        }

        // Validación del formulario
        $('#editProfileForm').submit(function(e) {
            const username = $('#username').val().trim();
            const currentPassword = $('#current_password').val();
            const newPassword = $('#new_password').val();
            const confirmPassword = $('#confirm_password').val();
            
            // Validar nombre de usuario
            if (username.length < 3) {
                e.preventDefault();
                alert('El nombre de usuario debe tener al menos 3 caracteres.');
                return false;
            }
            
            if (username.length > 50) {
                e.preventDefault();
                alert('El nombre de usuario no puede tener más de 50 caracteres.');
                return false;
            }

            // Validar contraseñas si se están cambiando
            const changingPassword = currentPassword || newPassword || confirmPassword;
            if (changingPassword) {
                if (!currentPassword) {
                    e.preventDefault();
                    alert('Debes proporcionar tu contraseña actual.');
                    return false;
                }
                
                if (!newPassword) {
                    e.preventDefault();
                    alert('Debes proporcionar una nueva contraseña.');
                    return false;
                }
                
                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('La nueva contraseña debe tener al menos 6 caracteres.');
                    return false;
                }
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Las nuevas contraseñas no coinciden.');
                    return false;
                }
            }
            
            // Deshabilitar el botón de envío para evitar múltiples envíos
            $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
        });

        // Variables para el chat
        let socket;
        let selectedUserId = null;
        let selectedUsername = null;
        const currentUserId = <?= $userId ?>;
        const sessionId = "<?= session_id(); ?>";
        let unreadMessages = {};

        // Inicializar WebSocket
        function initWebSocket() {
            const wsUrl = 'ws://localhost:8080';
            
            console.log('Conectando a WebSocket en:', wsUrl);
            socket = new WebSocket(wsUrl);
            
            socket.onopen = function(event) {
                console.log('Conexión WebSocket establecida');
                
                // Autenticar al usuario
                sendToServer({
                    type: 'auth',
                    user_id: currentUserId,
                    session_id: sessionId
                });
            };
            
            socket.onmessage = function(event) {
                const data = JSON.parse(event.data);
                console.log('Mensaje recibido:', data);
                
                switch (data.type) {
                    case 'auth_success':
                        console.log('Autenticación exitosa');
                        break;
                        
                    case 'new_message':
                        handleNewMessage(data);
                        break;
                        
                    case 'chat_history':
                        displayChatHistory(data.messages);
                        break;
                        
                    case 'unread_messages':
                        processUnreadMessages(data.messages);
                        break;
                        
                    case 'user_status':
                        updateUserStatus(data.user_id, data.online);
                        break;
                        
                    case 'test_response':
                        console.log('Respuesta de prueba recibida:', data.message);
                        break;
                }
            };
            
            socket.onerror = function(error) {
                console.error('Error de WebSocket:', error);
            };
            
            socket.onclose = function(event) {
                console.log('Conexión WebSocket cerrada');
                
                // Intentar reconectar después de 5 segundos
                setTimeout(function() {
                    console.log('Intentando reconectar...');
                    initWebSocket();
                }, 5000);
            };
        }

        // Enviar datos al servidor WebSocket
        function sendToServer(data) {
            if (socket && socket.readyState === WebSocket.OPEN) {
                socket.send(JSON.stringify(data));
            } else {
                console.error('WebSocket no está conectado');
            }
        }

        // Manejar nuevos mensajes
        function handleNewMessage(data) {
            // Si el mensaje es para la conversación actual, mostrarlo
            if (
                (data.sender_id === currentUserId && data.receiver_id === selectedUserId) ||
                (data.sender_id === selectedUserId && data.receiver_id === currentUserId)
            ) {
                appendMessage(data);
            } 
            // Si el mensaje es de alguien más, incrementar contador de no leídos
            else if (data.sender_id !== currentUserId) {
                incrementUnreadCount(data.sender_id);
            }
        }

        // Incrementar contador de mensajes no leídos
        function incrementUnreadCount(senderId) {
            if (!unreadMessages[senderId]) {
                unreadMessages[senderId] = 0;
            }
            unreadMessages[senderId]++;
            
            // Actualizar badge en la lista de usuarios
            const userItem = $(`.user-item[data-user-id="${senderId}"]`);
            const badge = userItem.find('.unread-badge');
            badge.text(unreadMessages[senderId]);
            badge.show();
            
            // Actualizar badge global en el botón de chat
            updateGlobalUnreadBadge();
            
            // Añadir animación al botón de chat
            $('#chatButton').addClass('chat-button-pulse');
        }

        // Actualizar badge global
        function updateGlobalUnreadBadge() {
            let totalUnread = 0;
            for (const userId in unreadMessages) {
                totalUnread += unreadMessages[userId];
            }
            
            const badge = $('#unreadBadge');
            if (totalUnread > 0) {
                badge.text(totalUnread);
                badge.show();
            } else {
                badge.hide();
            }
        }

        // Procesar mensajes no leídos
        function processUnreadMessages(messages) {
            // Agrupar mensajes por remitente
            const messagesBySender = {};
            
            messages.forEach(message => {
                const senderId = message.sender_id;
                
                if (!messagesBySender[senderId]) {
                    messagesBySender[senderId] = [];
                }
                
                messagesBySender[senderId].push(message);
            });
            
            // Actualizar contadores de mensajes no leídos
            for (const senderId in messagesBySender) {
                if (!unreadMessages[senderId]) {
                    unreadMessages[senderId] = 0;
                }
                unreadMessages[senderId] += messagesBySender[senderId].length;
                
                // Actualizar badge en la lista de usuarios
                const userItem = $(`.user-item[data-user-id="${senderId}"]`);
                const badge = userItem.find('.unread-badge');
                badge.text(unreadMessages[senderId]);
                badge.show();
            }
            
            // Actualizar badge global
            updateGlobalUnreadBadge();
        }

        // Mostrar historial de chat
        function displayChatHistory(messages) {
            const chatBody = $('#chatBody');
            chatBody.empty();
            
            if (messages.length === 0) {
                chatBody.append('<div class="no-messages">No hay mensajes aún. ¡Sé el primero en escribir!</div>');
                return;
            }
            
            messages.forEach(message => {
                appendMessage(message);
            });
            
            // Desplazarse al último mensaje
            chatBody.scrollTop(chatBody[0].scrollHeight);
        }

        // Añadir un mensaje al chat
        function appendMessage(message) {
            const chatBody = $('#chatBody');
            const isSent = message.sender_id == currentUserId;
            const messageElement = $('<div>').addClass(`chat-message ${isSent ? 'sent' : 'received'}`);
            
            // Formatear la fecha
            const timestamp = new Date(message.timestamp);
            const formattedTime = timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            messageElement.html(`
                <div class="message-content">${escapeHtml(message.message)}</div>
                <div class="chat-time">${formattedTime}</div>
            `);
            
            chatBody.append(messageElement);
            
            // Desplazarse al último mensaje
            chatBody.scrollTop(chatBody[0].scrollHeight);
        }

        // Escapar HTML para prevenir XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Actualizar estado de usuario (online/offline)
        function updateUserStatus(userId, online) {
            const userItem = $(`.user-item[data-user-id="${userId}"]`);
            if (online) {
                userItem.addClass('online');
            } else {
                userItem.removeClass('online');
            }
        }

        // Seleccionar un usuario para chatear
        function selectUser(userId, username) {
            selectedUserId = userId;
            selectedUsername = username;
            
            // Actualizar título del chat
            $('#chatHeaderTitle span').text(`Chat con ${username}`);
            
            // Mostrar el botón de volver
            $('#backButton').show();
            
            // Mostrar área de chat y ocultar lista de usuarios
            $('#userListContainer').hide();
            $('#chatBody, #chatFooter').show();
            
            // Resetear contador de mensajes no leídos para este usuario
            if (unreadMessages[userId]) {
                unreadMessages[userId] = 0;
                $(`.user-item[data-user-id="${userId}"] .unread-badge`).hide();
                updateGlobalUnreadBadge();
            }
            
            // Solicitar historial de chat
            sendToServer({
                type: 'get_history',
                other_user_id: userId
            });
        }

        // Volver a la lista de usuarios
        function backToUserList() {
            // Ocultar área de chat y mostrar lista de usuarios
            $('#chatBody, #chatFooter').hide();
            $('#userListContainer').show();
            
            // Ocultar el botón de volver
            $('#backButton').hide();
            
            // Actualizar título del chat
            $('#chatHeaderTitle span').text('Chat');
            
            // Resetear usuario seleccionado
            selectedUserId = null;
            selectedUsername = null;
        }

        // Cuando el documento esté listo
        $(document).ready(function() {
            // Inicializar WebSocket
            initWebSocket();
            
            // Mostrar/ocultar panel de chat al hacer clic en el botón
            $('#chatButton').click(function() {
                $('#chatPanel').toggle();
                
                // Si se muestra el chat, quitar animación del botón
                if ($('#chatPanel').is(':visible')) {
                    $('#chatButton').removeClass('chat-button-pulse');
                }
            });
            
            // Cerrar chat
            $('#closeChat').click(function() {
                $('#chatPanel').hide();
            });
            
            // Minimizar chat
            $('#minimizeChat').click(function() {
                $('#chatPanel').hide();
            });
            
            // Botón de volver a la lista de usuarios
            $('#backButton').click(function() {
                backToUserList();
            });
            
            // Seleccionar usuario para chatear
            $(document).on('click', '.user-item', function() {
                const userId = $(this).data('user-id');
                const username = $(this).data('username');
                selectUser(userId, username);
            });
            
            // Enviar mensaje
            $('#chatSend').click(function() {
                sendMessage();
            });
            
            // Enviar mensaje al presionar Enter
            $('#chatInput').keypress(function(e) {
                if (e.which === 13) {
                    sendMessage();
                    return false;
                }
            });
            
            // Función para enviar mensaje
            function sendMessage() {
                const message = $('#chatInput').val().trim();
                if (!message || !selectedUserId) {
                    return;
                }
                
                // Enviar mensaje al servidor
                sendToServer({
                    type: 'message',
                    receiver_id: selectedUserId,
                    message: message
                });
                
                // Limpiar campo de entrada
                $('#chatInput').val('');
            }
        });
    </script>
</body>
</html>