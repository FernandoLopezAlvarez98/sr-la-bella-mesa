<?php
session_start();
require_once '../controllers/AuthController.php';
require_once '../models/User.php';

$authController = new AuthController();

// Verificar autenticación
if (!$authController->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Obtener datos del usuario desde la base de datos
$userModel = new User();
$userId = $_SESSION['user_id'];
$userData = $userModel->getUserById($userId);

$userName = $userData ? $userData['nombre'] : 'Usuario';
$userEmail = $userData ? $userData['correo'] : '';
$userPhone = $userData ? $userData['telefono'] : '';
$userRole = $userData ? $userData['rol'] : 2;
$userDate = $userData ? $userData['fecha_registro'] : '';

$successMessage = '';
$errorMessage = '';

// Procesar formulario de actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // Actualizar información personal
        if ($_POST['action'] === 'update_profile') {
            $newName = trim($_POST['nombre'] ?? '');
            $newPhone = trim($_POST['telefono'] ?? '');
            
            if (empty($newName)) {
                $errorMessage = 'El nombre es requerido.';
            } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $newName)) {
                $errorMessage = 'El nombre solo puede contener letras y espacios.';
            } elseif (!empty($newPhone) && !preg_match('/^[0-9]{10}$/', $newPhone)) {
                $errorMessage = 'El teléfono debe tener 10 dígitos numéricos.';
            } else {
                $result = $userModel->updateUserProfile($userId, $newName, $newPhone);
                if ($result['success']) {
                    $successMessage = $result['message'];
                    // Actualizar datos locales
                    $userData = $userModel->getUserById($userId);
                    $userName = $userData['nombre'];
                    $userPhone = $userData['telefono'];
                } else {
                    $errorMessage = $result['message'];
                }
            }
        }
        
        // Actualizar correo electrónico
        if ($_POST['action'] === 'update_email') {
            $newEmail = trim($_POST['nuevo_correo'] ?? '');
            $currentPassword = $_POST['password_email'] ?? '';
            
            if (empty($newEmail) || empty($currentPassword)) {
                $errorMessage = 'Todos los campos son requeridos.';
            } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $errorMessage = 'El formato del correo no es válido.';
            } else {
                // Verificar contraseña actual
                $fullUserData = $userModel->getUserByEmail($userEmail);
                if ($fullUserData && $userModel->verifyPassword($currentPassword, $fullUserData['password_hash'])) {
                    $result = $userModel->updateUserEmail($userId, $newEmail);
                    if ($result['success']) {
                        $successMessage = $result['message'];
                        $_SESSION['user_email'] = $newEmail;
                        $userEmail = $newEmail;
                    } else {
                        $errorMessage = $result['message'];
                    }
                } else {
                    $errorMessage = 'La contraseña actual es incorrecta.';
                }
            }
        }
        
        // Cambiar contraseña
        if ($_POST['action'] === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $errorMessage = 'Todos los campos son requeridos.';
            } elseif (strlen($newPassword) < 6) {
                $errorMessage = 'La nueva contraseña debe tener al menos 6 caracteres.';
            } elseif ($newPassword !== $confirmPassword) {
                $errorMessage = 'Las contraseñas no coinciden.';
            } else {
                // Verificar contraseña actual
                $fullUserData = $userModel->getUserByEmail($userEmail);
                if ($fullUserData && $userModel->verifyPassword($currentPassword, $fullUserData['password_hash'])) {
                    $result = $userModel->updatePassword($userId, $newPassword);
                    if ($result['success']) {
                        $successMessage = 'Contraseña actualizada exitosamente.';
                    } else {
                        $errorMessage = $result['message'];
                    }
                } else {
                    $errorMessage = 'La contraseña actual es incorrecta.';
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
    <title>Mi Perfil | La Bella Mesa</title>
    <!-- CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.css">
    
    <style>
        :root {
            --primary-color: #e23744;
            --primary-dark: #c41e3a;
            --secondary-color: #ff914d;
            --bg-dark: #f5f5f5;
            --bg-card: #ffffff;
            --bg-light: #f8f9fa;
            --text-light: #333333;
            --text-dark: #333333;
            --text-muted: #6c757d;
            --gold: #ffd700;
            --success: #28a745;
            --warning: #ffc107;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* ========== HEADER/NAVBAR ========== */
        .main-header {
            background: #ffffff;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-top {
            padding: 15px 0;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-text i {
            color: var(--primary-color);
        }

        .logo-text:hover {
            color: var(--primary-color);
        }

        .header-icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(0,0,0,0.05);
            border: none;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .header-icon-btn:hover {
            background: rgba(0,0,0,0.1);
            color: var(--primary-color);
        }

        /* ========== CONTENIDO PRINCIPAL ========== */
        .profile-container {
            padding: 40px 0;
            max-width: 900px;
            margin: 0 auto;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title i {
            color: var(--primary-color);
        }

        /* ========== TARJETA DE PERFIL ========== */
        .profile-header-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .profile-header-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }

        .profile-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 15px;
            border: 4px solid rgba(255,255,255,0.3);
        }

        .profile-header-info h2 {
            margin: 0;
            font-size: 1.8rem;
            position: relative;
        }

        .profile-header-info p {
            margin: 5px 0;
            opacity: 0.9;
            position: relative;
        }

        .profile-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-top: 10px;
            position: relative;
        }

        /* ========== SECCIONES DE CONFIGURACIÓN ========== */
        .settings-section {
            background: #ffffff;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.08);
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-dark);
        }

        .section-title i {
            color: var(--primary-color);
            width: 25px;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            background: #f8f9fa;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            background: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(226, 55, 68, 0.15);
        }

        .form-control:disabled, .form-control[readonly] {
            background: #e9ecef;
            cursor: not-allowed;
        }

        .input-group-text {
            background: #f8f9fa;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 12px 0 0 12px;
            color: var(--text-muted);
        }

        .input-group .form-control {
            border-radius: 0 12px 12px 0;
        }

        /* ========== BOTONES ========== */
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(226, 55, 68, 0.4);
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: white;
        }

        .btn-outline-custom {
            background: transparent;
            border: 2px solid var(--primary-color);
            border-radius: 12px;
            padding: 10px 25px;
            font-weight: 600;
            color: var(--primary-color);
            transition: all 0.3s;
        }

        .btn-outline-custom:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-secondary-custom {
            background: #6c757d;
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }

        .btn-secondary-custom:hover {
            background: #5a6268;
            color: white;
        }

        /* ========== ALERTAS ========== */
        .alert-custom {
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success-custom {
            background: rgba(40, 167, 69, 0.15);
            color: #155724;
        }

        .alert-error-custom {
            background: rgba(220, 53, 69, 0.15);
            color: #721c24;
        }

        /* ========== INFO ADICIONAL ========== */
        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 10px;
        }

        .info-item i {
            width: 40px;
            height: 40px;
            background: rgba(226, 55, 68, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
        }

        .info-item-content h6 {
            margin: 0;
            font-weight: 600;
            color: var(--text-dark);
        }

        .info-item-content p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        /* ========== NAVEGACIÓN LATERAL ========== */
        .profile-nav {
            position: sticky;
            top: 100px;
        }

        .profile-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            border-radius: 10px;
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.3s;
            margin-bottom: 5px;
        }

        .profile-nav-item:hover {
            background: rgba(226, 55, 68, 0.1);
            color: var(--primary-color);
        }

        .profile-nav-item.active {
            background: var(--primary-color);
            color: white;
        }

        .profile-nav-item i {
            width: 20px;
        }

        /* ========== PASSWORD STRENGTH ========== */
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 8px;
            background: #e9ecef;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            border-radius: 3px;
            transition: all 0.3s;
        }

        .password-strength-bar.weak { width: 33%; background: #dc3545; }
        .password-strength-bar.medium { width: 66%; background: #ffc107; }
        .password-strength-bar.strong { width: 100%; background: #28a745; }

        /* ========== FOOTER ========== */
        .user-footer {
            background: #ffffff;
            padding: 30px 0;
            margin-top: 50px;
            border-top: 1px solid rgba(0,0,0,0.1);
            text-align: center;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .profile-container {
                padding: 20px 15px;
            }

            .profile-header-card {
                text-align: center;
            }

            .profile-avatar-large {
                margin: 0 auto 15px;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .settings-section {
                padding: 20px;
            }

            body {
                padding-bottom: 70px;
            }
        }

        /* ========== BARRA NAVEGACIÓN INFERIOR MÓVIL ========== */
        .mobile-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            padding: 10px 0;
            z-index: 1000;
            border-top: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }

        .mobile-nav-items {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }

        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.75rem;
            transition: color 0.3s;
        }

        .mobile-nav-item i {
            font-size: 1.2rem;
        }

        .mobile-nav-item.active,
        .mobile-nav-item:hover {
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .mobile-nav {
                display: block;
            }
        }

        /* ========== TOGGLE PASSWORD ========== */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 5px;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .position-relative {
            position: relative;
        }
    </style>
</head>
<body>

    <!-- ========== HEADER ========== -->
    <header class="main-header">
        <div class="header-top">
            <div class="container">
                <div class="d-flex align-items-center justify-content-between">
                    <!-- Logo -->
                    <a href="dashboardUser.php" class="logo-text">
                        <i class="fas fa-utensils"></i>
                        <span>La Bella Mesa</span>
                    </a>

                    <!-- Navegación -->
                    <div class="d-flex align-items-center gap-3">
                        <a href="dashboardUser.php" class="btn btn-outline-custom">
                            <i class="fas fa-arrow-left me-2"></i>Volver al inicio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ========== CONTENIDO PRINCIPAL ========== -->
    <main class="profile-container">
        <div class="container">
            
            <!-- Título -->
            <h1 class="page-title">
                <i class="fas fa-user-circle"></i>
                Mi Perfil
            </h1>

            <!-- Alertas -->
            <?php if ($successMessage): ?>
            <div class="alert-custom alert-success-custom">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
            <div class="alert-custom alert-error-custom">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
            <?php endif; ?>

            <!-- Tarjeta de perfil principal -->
            <div class="profile-header-card">
                <div class="d-flex align-items-center flex-wrap gap-4">
                    <div class="profile-avatar-large">
                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    </div>
                    <div class="profile-header-info">
                        <h2><?php echo htmlspecialchars($userName); ?></h2>
                        <p><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($userEmail); ?></p>
                        <?php if ($userPhone): ?>
                        <p><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($userPhone); ?></p>
                        <?php endif; ?>
                        <span class="profile-badge">
                            <i class="fas fa-user me-1"></i>
                            <?php echo $userRole == 1 ? 'Administrador' : 'Usuario'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    
                    <!-- Sección: Información Personal -->
                    <div class="settings-section" id="personal-info">
                        <h3 class="section-title">
                            <i class="fas fa-user"></i>
                            Información Personal
                        </h3>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre completo</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" name="nombre" 
                                               value="<?php echo htmlspecialchars($userName); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Teléfono</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="tel" class="form-control" name="telefono" 
                                               value="<?php echo htmlspecialchars($userPhone); ?>" 
                                               placeholder="10 dígitos" maxlength="10">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary-custom">
                                    <i class="fas fa-save me-2"></i>Guardar cambios
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Sección: Correo Electrónico -->
                    <div class="settings-section" id="email-section">
                        <h3 class="section-title">
                            <i class="fas fa-envelope"></i>
                            Correo Electrónico
                        </h3>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_email">
                            
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Correo actual</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($userEmail); ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nuevo correo electrónico</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" name="nuevo_correo" 
                                               placeholder="nuevo@correo.com" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contraseña actual (verificación)</label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control" name="password_email" 
                                               placeholder="Tu contraseña actual" required id="emailPassword">
                                        <button type="button" class="password-toggle" onclick="togglePassword('emailPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary-custom">
                                    <i class="fas fa-envelope me-2"></i>Cambiar correo
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Sección: Cambiar Contraseña -->
                    <div class="settings-section" id="password-section">
                        <h3 class="section-title">
                            <i class="fas fa-lock"></i>
                            Cambiar Contraseña
                        </h3>
                        
                        <form method="POST" action="" id="passwordForm">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Contraseña actual</label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control" name="current_password" 
                                               placeholder="Tu contraseña actual" required id="currentPassword">
                                        <button type="button" class="password-toggle" onclick="togglePassword('currentPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nueva contraseña</label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control" name="new_password" 
                                               placeholder="Mínimo 6 caracteres" required id="newPassword" 
                                               oninput="checkPasswordStrength(this.value)">
                                        <button type="button" class="password-toggle" onclick="togglePassword('newPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength">
                                        <div class="password-strength-bar" id="strengthBar"></div>
                                    </div>
                                    <small class="text-muted" id="strengthText">Ingresa una contraseña</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirmar nueva contraseña</label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control" name="confirm_password" 
                                               placeholder="Repite la contraseña" required id="confirmPassword"
                                               oninput="checkPasswordMatch()">
                                        <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" id="matchText"></small>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary-custom">
                                    <i class="fas fa-key me-2"></i>Cambiar contraseña
                                </button>
                            </div>
                        </form>
                    </div>

                </div>

                <div class="col-lg-4">
                    
                    <!-- Información de la cuenta -->
                    <div class="settings-section">
                        <h3 class="section-title">
                            <i class="fas fa-info-circle"></i>
                            Información de Cuenta
                        </h3>
                        
                        <div class="info-item">
                            <i class="fas fa-calendar-alt"></i>
                            <div class="info-item-content">
                                <h6>Miembro desde</h6>
                                <p><?php echo $userDate ? date('d/m/Y', strtotime($userDate)) : 'N/A'; ?></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-user-tag"></i>
                            <div class="info-item-content">
                                <h6>Tipo de cuenta</h6>
                                <p><?php echo $userRole == 1 ? 'Administrador' : 'Usuario estándar'; ?></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-shield-alt"></i>
                            <div class="info-item-content">
                                <h6>Estado de la cuenta</h6>
                                <p><span class="text-success"><i class="fas fa-check-circle me-1"></i>Activa</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Navegación rápida -->
                    <div class="settings-section">
                        <h3 class="section-title">
                            <i class="fas fa-compass"></i>
                            Navegación rápida
                        </h3>
                        
                        <nav class="profile-nav">
                            <a href="#personal-info" class="profile-nav-item active">
                                <i class="fas fa-user"></i>
                                Información personal
                            </a>
                            <a href="#email-section" class="profile-nav-item">
                                <i class="fas fa-envelope"></i>
                                Correo electrónico
                            </a>
                            <a href="#password-section" class="profile-nav-item">
                                <i class="fas fa-lock"></i>
                                Contraseña
                            </a>
                            <a href="dashboardUser.php" class="profile-nav-item">
                                <i class="fas fa-home"></i>
                                Volver al inicio
                            </a>
                        </nav>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <!-- ========== FOOTER ========== -->
    <footer class="user-footer">
        <div class="container">
            <p class="text-muted mb-0">&copy; 2025 La Bella Mesa. Todos los derechos reservados.</p>
        </div>
    </footer>

    <!-- ========== NAVEGACIÓN MÓVIL ========== -->
    <nav class="mobile-nav">
        <div class="mobile-nav-items">
            <a href="dashboardUser.php" class="mobile-nav-item">
                <i class="fas fa-home"></i>
                <span>Inicio</span>
            </a>
            <a href="#" class="mobile-nav-item">
                <i class="fas fa-search"></i>
                <span>Buscar</span>
            </a>
            <a href="#" class="mobile-nav-item">
                <i class="fas fa-calendar-check"></i>
                <span>Reservas</span>
            </a>
            <a href="#" class="mobile-nav-item">
                <i class="fas fa-heart"></i>
                <span>Favoritos</span>
            </a>
            <a href="userProfile.php" class="mobile-nav-item active">
                <i class="fas fa-user"></i>
                <span>Perfil</span>
            </a>
        </div>
    </nav>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Check password strength
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            
            if (password.length === 0) {
                strengthBar.style.width = '0';
                strengthText.textContent = 'Ingresa una contraseña';
            } else if (strength <= 2) {
                strengthBar.classList.add('weak');
                strengthText.textContent = 'Contraseña débil';
            } else if (strength <= 3) {
                strengthBar.classList.add('medium');
                strengthText.textContent = 'Contraseña media';
            } else {
                strengthBar.classList.add('strong');
                strengthText.textContent = 'Contraseña fuerte';
            }
        }

        // Check if passwords match
        function checkPasswordMatch() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const matchText = document.getElementById('matchText');
            
            if (confirmPassword.length === 0) {
                matchText.textContent = '';
            } else if (newPassword === confirmPassword) {
                matchText.innerHTML = '<span class="text-success"><i class="fas fa-check me-1"></i>Las contraseñas coinciden</span>';
            } else {
                matchText.innerHTML = '<span class="text-danger"><i class="fas fa-times me-1"></i>Las contraseñas no coinciden</span>';
            }
        }

        // Smooth scroll for navigation
        document.querySelectorAll('.profile-nav-item[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    
                    // Update active state
                    document.querySelectorAll('.profile-nav-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    this.classList.add('active');
                }
            });
        });

        // Show toast notification
        function showNotification(message, type = 'info') {
            const colors = {
                'success': 'linear-gradient(to right, #00b09b, #96c93d)',
                'error': 'linear-gradient(to right, #ff5f6d, #ffc371)',
                'warning': 'linear-gradient(to right, #f7971e, #ffd200)',
                'info': 'linear-gradient(to right, #2193b0, #6dd5ed)'
            };

            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "right",
                style: {
                    background: colors[type] || colors['info']
                },
                stopOnFocus: true
            }).showToast();
        }

        // Show messages from PHP
        <?php if ($successMessage): ?>
        showNotification('<?php echo addslashes($successMessage); ?>', 'success');
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
        showNotification('<?php echo addslashes($errorMessage); ?>', 'error');
        <?php endif; ?>

        console.log('👤 Perfil de usuario cargado correctamente');
    </script>

</body>
</html>
