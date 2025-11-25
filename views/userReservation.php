<?php
session_start();
require_once '../controllers/AuthController.php';
require_once '../models/User.php';
require_once '../models/Reservation.php';

$authController = new AuthController();

// Verificar autenticación
if (!$authController->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Obtener datos del usuario
$userModel = new User();
$userId = $_SESSION['user_id'];
$userData = $userModel->getUserById($userId);

$userName = $userData ? $userData['nombre'] : 'Usuario';
$userEmail = $userData ? $userData['correo'] : '';

// Obtener reservaciones del usuario
$reservationModel = new Reservation();

$successMessage = '';
$errorMessage = '';

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // Cancelar reservación
        if ($_POST['action'] === 'cancel' && isset($_POST['reservation_id'])) {
            $result = $reservationModel->cancelUserReservation($_POST['reservation_id'], $userId);
            if ($result['success']) {
                $successMessage = $result['message'];
            } else {
                $errorMessage = $result['message'];
            }
        }
        
        // Modificar reservación
        if ($_POST['action'] === 'modify' && isset($_POST['reservation_id'])) {
            $newDate = $_POST['new_date'] ?? '';
            $newTime = $_POST['new_time'] ?? '';
            
            if (empty($newDate) || empty($newTime)) {
                $errorMessage = 'Por favor, selecciona una fecha y hora válidas.';
            } else {
                $result = $reservationModel->modifyUserReservation($_POST['reservation_id'], $userId, $newDate, $newTime);
                if ($result['success']) {
                    $successMessage = $result['message'];
                } else {
                    $errorMessage = $result['message'];
                }
            }
        }
    }
}

// Obtener reservaciones
$activeReservations = $reservationModel->getActiveReservationsByUserId($userId);
$pastReservations = $reservationModel->getPastReservationsByUserId($userId);
$totalActive = $reservationModel->countActiveUserReservations($userId);

// Función para formatear estado
function getStatusBadge($estado) {
    $badges = [
        'pendiente' => ['class' => 'bg-warning text-dark', 'icon' => 'fa-clock', 'text' => 'Pendiente'],
        'confirmada' => ['class' => 'bg-success', 'icon' => 'fa-check-circle', 'text' => 'Confirmada'],
        'check_in' => ['class' => 'bg-info', 'icon' => 'fa-user-check', 'text' => 'En curso'],
        'completada' => ['class' => 'bg-primary', 'icon' => 'fa-check-double', 'text' => 'Completada'],
        'cancelada' => ['class' => 'bg-danger', 'icon' => 'fa-times-circle', 'text' => 'Cancelada'],
        'noshow' => ['class' => 'bg-dark', 'icon' => 'fa-user-slash', 'text' => 'No asistió']
    ];
    return $badges[$estado] ?? ['class' => 'bg-secondary', 'icon' => 'fa-question', 'text' => $estado];
}

// Función para formatear fecha
function formatDate($date) {
    $timestamp = strtotime($date);
    $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    
    return $days[date('w', $timestamp)] . ', ' . date('d', $timestamp) . ' de ' . $months[date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Reservaciones | La Bella Mesa</title>
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

        /* ========== HEADER ========== */
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

        /* ========== CONTENIDO PRINCIPAL ========== */
        .reservations-container {
            padding: 40px 0;
            max-width: 1000px;
            margin: 0 auto;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title i {
            color: var(--primary-color);
        }

        .page-subtitle {
            color: var(--text-muted);
            margin-bottom: 30px;
        }

        /* ========== TARJETA DE ESTADÍSTICAS ========== */
        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px;
            padding: 25px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }

        .stats-item {
            text-align: center;
            position: relative;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* ========== TABS ========== */
        .nav-tabs-custom {
            border: none;
            background: #ffffff;
            border-radius: 15px;
            padding: 5px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .nav-tabs-custom .nav-link {
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.3s;
        }

        .nav-tabs-custom .nav-link:hover {
            color: var(--primary-color);
            background: rgba(226, 55, 68, 0.1);
        }

        .nav-tabs-custom .nav-link.active {
            background: var(--primary-color);
            color: white;
        }

        /* ========== TARJETAS DE RESERVACIÓN ========== */
        .reservation-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s;
        }

        .reservation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .reservation-card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }

        .reservation-date {
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .reservation-date i {
            color: var(--primary-color);
        }

        .reservation-card-body {
            padding: 20px;
            display: flex;
            gap: 20px;
        }

        .restaurant-img {
            width: 120px;
            height: 120px;
            border-radius: 15px;
            object-fit: cover;
        }

        .reservation-details {
            flex: 1;
        }

        .restaurant-name {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--text-dark);
        }

        .restaurant-type {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .reservation-info {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .info-item i {
            color: var(--primary-color);
            width: 18px;
        }

        .reservation-card-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(0,0,0,0.08);
        }

        /* ========== BOTONES DE ACCIÓN ========== */
        .btn-action {
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-modify {
            background: rgba(0, 123, 255, 0.1);
            color: #007bff;
            border: none;
        }

        .btn-modify:hover {
            background: #007bff;
            color: white;
        }

        .btn-cancel {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: none;
        }

        .btn-cancel:hover {
            background: #dc3545;
            color: white;
        }

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
            color: white;
        }

        /* ========== BADGES ========== */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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

        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #ffffff;
            border-radius: 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }

        .empty-state h4 {
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        /* ========== MODAL ========== */
        .modal-content {
            border-radius: 20px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px 20px 0 0;
            border: none;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            border: none;
            padding: 15px 25px 25px;
        }

        .form-control, .form-select {
            background: #f8f9fa;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 12px;
            padding: 12px 15px;
        }

        .form-control:focus, .form-select:focus {
            background: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(226, 55, 68, 0.15);
        }

        /* ========== FOOTER ========== */
        .user-footer {
            background: #ffffff;
            padding: 30px 0;
            margin-top: 50px;
            border-top: 1px solid rgba(0,0,0,0.1);
            text-align: center;
        }

        /* ========== NAVEGACIÓN MÓVIL ========== */
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

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .mobile-nav {
                display: block;
            }

            body {
                padding-bottom: 70px;
            }

            .reservations-container {
                padding: 20px 15px;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .reservation-card-body {
                flex-direction: column;
            }

            .restaurant-img {
                width: 100%;
                height: 150px;
            }

            .stats-card .row > div {
                margin-bottom: 15px;
            }

            .reservation-card-footer {
                flex-direction: column;
                gap: 10px;
            }

            .reservation-card-footer .d-flex {
                width: 100%;
                justify-content: center;
            }
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
    <main class="reservations-container">
        <div class="container">
            
            <!-- Título -->
            <h1 class="page-title">
                <i class="fas fa-calendar-check"></i>
                Mis Reservaciones
            </h1>
            <p class="page-subtitle">Administra tus reservaciones y mantente al día con tus visitas a restaurantes</p>

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

            <!-- Tarjeta de estadísticas -->
            <div class="stats-card">
                <div class="row">
                    <div class="col-md-4">
                        <div class="stats-item">
                            <div class="stats-number"><?php echo $totalActive; ?></div>
                            <div class="stats-label">Reservaciones activas</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-item">
                            <div class="stats-number"><?php echo count($pastReservations); ?></div>
                            <div class="stats-label">Reservaciones pasadas</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-item">
                            <div class="stats-number"><?php echo count($activeReservations) + count($pastReservations); ?></div>
                            <div class="stats-label">Total de reservaciones</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs de navegación -->
            <ul class="nav nav-tabs-custom" id="reservationTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab">
                        <i class="fas fa-clock me-2"></i>Próximas (<?php echo count($activeReservations); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                        <i class="fas fa-history me-2"></i>Historial (<?php echo count($pastReservations); ?>)
                    </button>
                </li>
            </ul>

            <!-- Contenido de tabs -->
            <div class="tab-content" id="reservationTabsContent">
                
                <!-- Tab: Reservaciones activas -->
                <div class="tab-pane fade show active" id="active" role="tabpanel">
                    <?php if (empty($activeReservations)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h4>No tienes reservaciones próximas</h4>
                        <p>Explora restaurantes y haz una nueva reservación</p>
                        <a href="dashboardUser.php" class="btn btn-primary-custom">
                            <i class="fas fa-search me-2"></i>Explorar restaurantes
                        </a>
                    </div>
                    <?php else: ?>
                        <?php foreach ($activeReservations as $reservation): ?>
                        <?php $statusBadge = getStatusBadge($reservation['estado']); ?>
                        <div class="reservation-card">
                            <div class="reservation-card-header">
                                <div class="reservation-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo formatDate($reservation['fecha_reserva']); ?>
                                </div>
                                <span class="status-badge <?php echo $statusBadge['class']; ?>">
                                    <i class="fas <?php echo $statusBadge['icon']; ?>"></i>
                                    <?php echo $statusBadge['text']; ?>
                                </span>
                            </div>
                            <div class="reservation-card-body">
                                <img src="<?php echo htmlspecialchars($reservation['restaurante_imagen'] ?? '../src/images/1.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($reservation['restaurante_nombre']); ?>" 
                                     class="restaurant-img">
                                <div class="reservation-details">
                                    <h5 class="restaurant-name"><?php echo htmlspecialchars($reservation['restaurante_nombre']); ?></h5>
                                    <p class="restaurant-type">
                                        <i class="fas fa-utensils me-1"></i>
                                        <?php echo htmlspecialchars($reservation['restaurante_tipo_cocina'] ?? 'Restaurante'); ?>
                                    </p>
                                    <div class="reservation-info">
                                        <div class="info-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo date('h:i A', strtotime($reservation['hora_reserva'])); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-users"></i>
                                            <span><?php echo $reservation['num_personas']; ?> persona(s)</span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-chair"></i>
                                            <span>Mesa <?php echo $reservation['mesa_numero']; ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($reservation['restaurante_direccion'] ?? ''); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="reservation-card-footer">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Reservación #<?php echo substr($reservation['id_reservacion'], 0, 8); ?>
                                </small>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-action btn-modify" 
                                            onclick="openModifyModal('<?php echo $reservation['id_reservacion']; ?>', '<?php echo $reservation['fecha_reserva']; ?>', '<?php echo substr($reservation['hora_reserva'], 0, 5); ?>', '<?php echo htmlspecialchars($reservation['restaurante_nombre']); ?>')">
                                        <i class="fas fa-edit"></i>Modificar
                                    </button>
                                    <button class="btn btn-action btn-cancel" 
                                            onclick="openCancelModal('<?php echo $reservation['id_reservacion']; ?>', '<?php echo htmlspecialchars($reservation['restaurante_nombre']); ?>', '<?php echo formatDate($reservation['fecha_reserva']); ?>')">
                                        <i class="fas fa-times"></i>Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Tab: Historial -->
                <div class="tab-pane fade" id="history" role="tabpanel">
                    <?php if (empty($pastReservations)): ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h4>No tienes historial de reservaciones</h4>
                        <p>Tus reservaciones pasadas aparecerán aquí</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($pastReservations as $reservation): ?>
                        <?php $statusBadge = getStatusBadge($reservation['estado']); ?>
                        <div class="reservation-card" style="opacity: 0.85;">
                            <div class="reservation-card-header">
                                <div class="reservation-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo formatDate($reservation['fecha_reserva']); ?>
                                </div>
                                <span class="status-badge <?php echo $statusBadge['class']; ?>">
                                    <i class="fas <?php echo $statusBadge['icon']; ?>"></i>
                                    <?php echo $statusBadge['text']; ?>
                                </span>
                            </div>
                            <div class="reservation-card-body">
                                <img src="<?php echo htmlspecialchars($reservation['restaurante_imagen'] ?? '../src/images/1.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($reservation['restaurante_nombre']); ?>" 
                                     class="restaurant-img">
                                <div class="reservation-details">
                                    <h5 class="restaurant-name"><?php echo htmlspecialchars($reservation['restaurante_nombre']); ?></h5>
                                    <p class="restaurant-type">
                                        <i class="fas fa-utensils me-1"></i>
                                        <?php echo htmlspecialchars($reservation['restaurante_tipo_cocina'] ?? 'Restaurante'); ?>
                                    </p>
                                    <div class="reservation-info">
                                        <div class="info-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo date('h:i A', strtotime($reservation['hora_reserva'])); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-users"></i>
                                            <span><?php echo $reservation['num_personas']; ?> persona(s)</span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-chair"></i>
                                            <span>Mesa <?php echo $reservation['mesa_numero']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="reservation-card-footer">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Reservación #<?php echo substr($reservation['id_reservacion'], 0, 8); ?>
                                </small>
                                <?php if ($reservation['estado'] === 'completada'): ?>
                                <a href="#" class="btn btn-action btn-modify">
                                    <i class="fas fa-star"></i>Dejar reseña
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- ========== MODAL MODIFICAR ========== -->
    <div class="modal fade" id="modifyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Modificar Reservación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="modify">
                        <input type="hidden" name="reservation_id" id="modify_reservation_id">
                        
                        <p class="mb-3">
                            <strong id="modify_restaurant_name"></strong>
                        </p>
                        
                        <div class="mb-3">
                            <label class="form-label">Nueva fecha</label>
                            <input type="date" class="form-control" name="new_date" id="modify_new_date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nueva hora</label>
                            <select class="form-select" name="new_time" id="modify_new_time" required>
                                <option value="">Seleccionar hora</option>
                                <option value="12:00">12:00 PM</option>
                                <option value="12:30">12:30 PM</option>
                                <option value="13:00">1:00 PM</option>
                                <option value="13:30">1:30 PM</option>
                                <option value="14:00">2:00 PM</option>
                                <option value="14:30">2:30 PM</option>
                                <option value="15:00">3:00 PM</option>
                                <option value="18:00">6:00 PM</option>
                                <option value="18:30">6:30 PM</option>
                                <option value="19:00">7:00 PM</option>
                                <option value="19:30">7:30 PM</option>
                                <option value="20:00">8:00 PM</option>
                                <option value="20:30">8:30 PM</option>
                                <option value="21:00">9:00 PM</option>
                                <option value="21:30">9:30 PM</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            La disponibilidad será verificada al confirmar el cambio.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-check me-2"></i>Confirmar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========== MODAL CANCELAR ========== -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white" style="background: linear-gradient(135deg, #dc3545, #c82333) !important;">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Cancelar Reservación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="reservation_id" id="cancel_reservation_id">
                        
                        <div class="text-center mb-4">
                            <i class="fas fa-calendar-times text-danger" style="font-size: 4rem;"></i>
                        </div>
                        
                        <p class="text-center mb-3">
                            ¿Estás seguro de que deseas cancelar tu reservación en <strong id="cancel_restaurant_name"></strong>?
                        </p>
                        <p class="text-center text-muted" id="cancel_date_info"></p>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Esta acción no se puede deshacer.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, mantener</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Sí, cancelar reservación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
            <a href="userReservation.php" class="mobile-nav-item active">
                <i class="fas fa-calendar-check"></i>
                <span>Reservas</span>
            </a>
            <a href="#" class="mobile-nav-item">
                <i class="fas fa-heart"></i>
                <span>Favoritos</span>
            </a>
            <a href="userProfile.php" class="mobile-nav-item">
                <i class="fas fa-user"></i>
                <span>Perfil</span>
            </a>
        </div>
    </nav>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.js"></script>
    
    <script>
        // Configurar fecha mínima (hoy)
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('modify_new_date').min = today;

        // Abrir modal de modificar
        function openModifyModal(reservationId, currentDate, currentTime, restaurantName) {
            document.getElementById('modify_reservation_id').value = reservationId;
            document.getElementById('modify_new_date').value = currentDate;
            document.getElementById('modify_new_time').value = currentTime;
            document.getElementById('modify_restaurant_name').textContent = restaurantName;
            
            new bootstrap.Modal(document.getElementById('modifyModal')).show();
        }

        // Abrir modal de cancelar
        function openCancelModal(reservationId, restaurantName, dateInfo) {
            document.getElementById('cancel_reservation_id').value = reservationId;
            document.getElementById('cancel_restaurant_name').textContent = restaurantName;
            document.getElementById('cancel_date_info').textContent = dateInfo;
            
            new bootstrap.Modal(document.getElementById('cancelModal')).show();
        }

        // Mostrar notificación
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

        // Mostrar mensajes PHP
        <?php if ($successMessage): ?>
        showNotification('<?php echo addslashes($successMessage); ?>', 'success');
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
        showNotification('<?php echo addslashes($errorMessage); ?>', 'error');
        <?php endif; ?>

        console.log('📅 Página de reservaciones cargada correctamente');
    </script>

</body>
</html>
