<?php
require_once '../controllers/AuthController.php';

$authController = new AuthController();

// Verificar si el usuario está autenticado
if (!$authController->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Obtener datos del usuario actual
$userId = $authController->getCurrentUserId();
$userEmail = $authController->getCurrentUserEmail();
$userName = $authController->getCurrentUserName();

// Manejar logout
if (isset($_GET['logout'])) {
    $authController->logout();
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - La Bella Mesa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-utensils me-2"></i>La Bella Mesa
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($userEmail); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog me-2"></i>Perfil</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="?logout=true"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <div class="container mt-4">
        <!-- Encabezado de Bienvenida -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h2 class="mb-0">
                            <i class="fas fa-home me-2"></i><?php echo htmlspecialchars($userName); ?>, ¡Bienvenido a La Bella Mesa!
                        </h2>
                        <p class="mb-0">Sistema de gestión de restaurante</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Funcionalidades -->
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="text-primary mb-3">
                            <i class="fas fa-utensils fa-3x"></i>
                        </div>
                        <h5 class="card-title">Menú</h5>
                        <p class="card-text">Gestionar platos, precios y disponibilidad</p>
                        <a href="#" class="btn btn-primary">Acceder</a>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="text-success mb-3">
                            <i class="fas fa-shopping-cart fa-3x"></i>
                        </div>
                        <h5 class="card-title">Pedidos</h5>
                        <p class="card-text">Gestionar pedidos y entregas</p>
                        <a href="#" class="btn btn-success">Acceder</a>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="text-warning mb-3">
                            <i class="fas fa-users fa-3x"></i>
                        </div>
                        <h5 class="card-title">Clientes</h5>
                        <p class="card-text">Gestionar información de clientes</p>
                        <a href="#" class="btn btn-warning">Acceder</a>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="text-info mb-3">
                            <i class="fas fa-chart-bar fa-3x"></i>
                        </div>
                        <h5 class="card-title">Reportes</h5>
                        <p class="card-text">Estadísticas y análisis de ventas</p>
                        <a href="#" class="btn btn-info">Acceder</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de Usuario -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información de la Sesión</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>ID de Usuario:</strong> <?php echo htmlspecialchars($userId); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($userEmail); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Hora de Login:</strong> 
                                    <?php 
                                    if (isset($_SESSION['login_time'])) {
                                        echo date('d/m/Y H:i:s', $_SESSION['login_time']);
                                    } else {
                                        echo 'No disponible';
                                    }
                                    ?>
                                </p>
                                <p><strong>Estado:</strong> <span class="badge bg-success">Conectado</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>