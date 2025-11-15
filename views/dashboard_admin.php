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
    <title>Administrador - Sistema de Reservas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Toastify -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <!-- Custom Dashboard CSS -->
    <link rel="stylesheet" href="../src/css/dashboard_admin.css">
    <script src="../src/scripts/script.js"></script>
    
    <!-- Estilos adicionales para el dashboard -->
    <style>
        /* IMPORTANTE: Sobrescribir cualquier overlay que pueda estar bloqueando */
        .loading-overlay,
        .loader-overlay {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            z-index: -1 !important;
            pointer-events: none !important;
        }
        
        /* Solo mostrar el loader cuando tenga la clase active específicamente */
        .loader-overlay.active {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 1030 !important;
            pointer-events: auto !important;
        }
        
        /* Asegurar que el modal de Bootstrap tenga prioridad */
        .modal {
            z-index: 1055 !important;
        }
        
        .modal-backdrop {
            z-index: 1050 !important;
        }
    <style>
        /* Estilos para estados de reservación */
        .status-confirmed { background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.875rem; }
        .status-checkin { background-color: #17a2b8; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.875rem; }
        .status-cancelled { background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.875rem; }
        .status-noshow { background-color: #fd7e14; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.875rem; }
        .status-completed { background-color: #6c757d; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.875rem; }
        .status-pending { background-color: #ffc107; color: #212529; padding: 4px 8px; border-radius: 4px; font-size: 0.875rem; }
        
        /* Estilos para indicadores de cambio */
        .stat-change { font-size: 0.875rem; margin-top: 4px; }
        .stat-change.positive { color: #28a745; }
        .stat-change.negative { color: #dc3545; }
        .stat-change i { margin-right: 4px; }
        
        /* Estilos para avatares */
        .user-avatar-sm { width: 32px; height: 32px; border-radius: 50%; margin-right: 8px; }
        .user-info { display: flex; align-items: center; }
        .user-name { font-weight: 500; }
        .user-email { font-size: 0.875rem; color: #6c757d; }
        
        /* Estilos para botones de acción */
        .btn-action { padding: 4px 8px; margin: 0 2px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-success { background-color: #28a745; color: white; }
        .btn-info { background-color: #17a2b8; color: white; }
        .btn-warning { background-color: #ffc107; color: #212529; }
        .btn-danger { background-color: #dc3545; color: white; }
        
        /* Estilos para el dropdown del usuario */
        .user-details { padding: 8px; border-bottom: 1px solid #eee; }
        
        /* Estilos para la sección de información */
        .section { margin-bottom: 2rem; }
        .section-header { display: flex; justify-content: between; align-items: center; margin-bottom: 1rem; }
        .section-title { margin: 0; font-size: 1.25rem; font-weight: 600; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card-body { padding: 1.5rem; }
        
        /* Estilos para badges */
        .badge { display: inline-block; padding: 4px 8px; font-size: 0.75rem; font-weight: 500; border-radius: 4px; }
        .bg-success { background-color: #28a745; color: white; }
        .bg-info { background-color: #17a2b8; color: white; }
        
        /* Estilos para el mapa de mesas */
        .table-map { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; padding: 1rem; }
        .table-item { padding: 1rem; text-align: center; border: 1px solid #dee2e6; border-radius: 8px; cursor: pointer; transition: transform 0.2s; }
        .table-item:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
        .table-item-label { font-weight: bold; margin: 8px 0 4px; }
        .table-item-status { font-size: 0.875rem; color: #6c757d; }
        
        /* Estilos para franjas horarias */
        .time-slots-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 1rem; padding: 1rem; }
        .time-slot { padding: 1rem; text-align: center; border-radius: 8px; border: 2px solid; }
        .time-slot.available { border-color: #28a745; background-color: #d4edda; }
        .time-slot.limited { border-color: #ffc107; background-color: #fff3cd; }
        .time-slot.full { border-color: #dc3545; background-color: #f8d7da; }
        .time-slot .time { display: block; font-weight: bold; margin-bottom: 4px; }
        .time-slot .availability { font-size: 0.875rem; color: #6c757d; }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .table-map { grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); }
            .time-slots-container { grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); }
        }
        
        /* Estilos para el modal de nueva reserva */
        .table-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .table-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            border-color: #007bff;
        }
        
        .table-card.border-primary {
            border-color: #007bff !important;
            background-color: #f8f9fa !important;
        }
        
        .modal-lg {
            max-width: 800px;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        
        .form-select:focus,
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        #availableTablesList .card {
            height: 100%;
        }
        
        .alert {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <!-- Loader Overlay -->
    <div class="loader-overlay" id="loader">
        <div class="loader"></div>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <!-- Logo and Toggle Button -->
            <div class="logo">
                <i class="fas fa-utensils fa-2x"></i>
                <h2>Admin Panel</h2>
                <button id="collapse-sidebar" class="d-md-none" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <!-- System Status -->
            <div class="system-status">
                <div class="status-indicator online"></div>
                <span>Sistema en línea</span>
            </div>

            <!-- Sidebar Navigation -->
            <ul class="sidebar-nav">
                <!-- Consultas -->
                <li>
                    <a href="#" class="active" onclick="showTab('consultas')" aria-current="page">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Consultas</span>
                    </a>
                </li>

                <!-- Clientes -->
                <li>
                    <a href="#" onclick="showTab('customers')">
                        <i class="fas fa-users"></i>
                        <span>Clientes</span>
                    </a>
                </li>

                <!-- Mesas -->
                <li>
                    <a href="#" onclick="showTab('tables')">
                        <i class="fas fa-chair"></i>
                        <span>Mesas</span>
                    </a>
                </li>

                <!-- Reservas -->
                <li>
                    <a href="#" onclick="showTab('reservations')">
                        <i class="fas fa-calendar-check"></i>
                        <span>Reservas</span>
                    </a>
                </li>

                <!-- Informes -->
                <li>
                    <a href="#" onclick="showTab('reports')">
                        <i class="fas fa-chart-bar"></i>
                        <span>Informes</span>
                        <i class="fas fa-chevron-down submenu-toggle"></i>
                    </a>
                    <ul id="reports-submenu" class="submenu">
                        <li><a href="#" onclick="showTab('daily-reports')">Diarios</a></li>
                        <li><a href="#" onclick="showTab('weekly-reports')">Semanales</a></li>
                        <li><a href="#" onclick="showTab('monthly-reports')">Mensuales</a></li>
                    </ul>
                </li>

                <!-- Configuración -->
                <li>
                    <a href="#" onclick="showTab('settings')">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                </li>

                <!-- Cerrar Sesión -->
                <li>
                    <a href="?logout=true">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content Area -->
        <div class="main-content-area">
            <div class="header">
                <div class="header-left">
                    <button id="toggle-sidebar" class="sidebar-toggler" aria-label="Toggle sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">¡Bienvenido <?php echo htmlspecialchars($userName); ?>! - Dashboard de Administración</h1>
                </div>
                <div class="header-right">
                    <!-- Dark mode toggle -->
                    <button id="dark-mode-toggle" class="btn btn-icon" aria-label="Toggle dark mode">
                        <i class="fas fa-moon"></i>
                    </button>
                    <!-- Search functionality -->
                    <div class="search-container">
                        <input type="text" id="global-search" placeholder="Buscar..." aria-label="Search">
                        <button class="search-btn" aria-label="Search">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <!-- Notifications -->
                    <div class="notification-bell" onclick="toggleNotifications()" aria-expanded="false">
                        <i class="fas fa-bell fa-lg"></i>
                        <span class="notification-badge">3</span>
                        <div class="dropdown-content notification-dropdown" id="notificationDropdown">
                            <div class="dropdown-header">
                                <h3>Notificaciones</h3>
                                <button class="btn btn-text" onclick="markAllAsRead()">Marcar todo como leído</button>
                            </div>
                            <div class="notification-list">
                                <a href="#" class="notification-item unread">
                                    <div class="notification-icon bg-primary">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p class="notification-title">Nueva reserva</p>
                                        <p class="notification-text">Mesa para 4 a las 20:00</p>
                                        <span class="notification-time">Hace 10 minutos</span>
                                    </div>
                                </a>
                                <a href="#" class="notification-item unread">
                                    <div class="notification-icon bg-danger">
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p class="notification-title">No show</p>
                                        <p class="notification-text">Cliente no asistió a su reserva</p>
                                        <span class="notification-time">Hace 30 minutos</span>
                                    </div>
                                </a>
                                <a href="#" class="notification-item">
                                    <div class="notification-icon bg-warning">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p class="notification-title">Cancelación</p>
                                        <p class="notification-text">Reserva #R-2345 cancelada</p>
                                        <span class="notification-time">Hace 1 hora</span>
                                    </div>
                                </a>
                            </div>
                            <div class="dropdown-footer">
                                <a href="#" onclick="showTab('notifications')">Ver todas</a>
                            </div>
                        </div>
                    </div>
                    <!-- User dropdown -->
                    <div class="user-dropdown" aria-expanded="false">
                        <div class="user-info" onclick="toggleUserMenu()">
                            <img src="277591295863fc51586860f820966128.jpg" alt="Avatar" class="user-avatar">
                            <span class="user-name d-none d-md-inline"><?php echo htmlspecialchars($userName); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="dropdown-content" id="userDropdown">
                            <div class="user-details mb-2">
                                <small class="text-muted"><?php echo htmlspecialchars($userEmail); ?></small>
                            </div>
                            <a href="#" onclick="showTab('profile')"><i class="fas fa-user"></i> Mi Perfil</a>
                            <a href="#" onclick="showTab('settings')"><i class="fas fa-cog"></i> Configuración</a>
                            <div class="dropdown-divider"></div>
                            <a href="?logout=true"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reservas del Día -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Reservas del Día</h2>
                    <div class="section-actions">
                        <div class="input-group">
                            <input type="text" id="reservation-search" placeholder="Buscar reserva..." class="form-control">
                            <button class="btn btn-outline-secondary" type="button" id="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <button class="btn btn-primary" id="newReservationBtn">
                            <i class="fas fa-plus"></i> Nueva Reserva
                        </button>
                        <button class="btn btn-warning" onclick="debugOverlays()" title="Debug: Ocultar overlays">
                            <i class="fas fa-bug"></i> Debug
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="table-responsive">
                        <thead>
                            <tr>
                                <th scope="col">
                                    <div class="th-content">
                                        <span>Hora</span>
                                        <button class="sort-btn" data-sort="time" aria-label="Ordenar por hora">
                                            <i class="fas fa-sort"></i>
                                        </button>
                                    </div>
                                </th>
                                <th scope="col">
                                    <div class="th-content">
                                        <span>Cliente</span>
                                        <button class="sort-btn" data-sort="customer" aria-label="Ordenar por cliente">
                                            <i class="fas fa-sort"></i>
                                        </button>
                                    </div>
                                </th>
                                <th scope="col">
                                    <div class="th-content">
                                        <span>Personas</span>
                                        <button class="sort-btn" data-sort="people" aria-label="Ordenar por número de personas">
                                            <i class="fas fa-sort"></i>
                                        </button>
                                    </div>
                                </th>
                                <th scope="col">
                                    <div class="th-content">
                                        <span>Mesa</span>
                                        <button class="sort-btn" data-sort="table" aria-label="Ordenar por mesa">
                                            <i class="fas fa-sort"></i>
                                        </button>
                                    </div>
                                </th>
                                <th scope="col">
                                    <div class="th-content">
                                        <span>Estado</span>
                                        <button class="sort-btn" data-sort="status" aria-label="Ordenar por estado">
                                            <i class="fas fa-sort"></i>
                                        </button>
                                    </div>
                                </th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="today-reservations">
                            <tr data-reservation-id="R-1234">
                                <td data-time="12:30">12:30</td>
                                <td data-customer="María González">
                                    <div class="user-info">
                                        <img src="277591295863fc51586860f820966128.jpg" alt="" class="user-avatar-sm">
                                        <div>
                                            <div class="user-name">María González</div>
                                            <div class="user-email">maria@example.com</div>
                                        </div>
                                    </div>
                                </td>
                                <td data-people="4"><span class="people-count">4</span></td>
                                <td data-table="T5 (Terraza)"><span class="badge bg-info">T5 (Terraza)</span></td>
                                <td data-status="Confirmada"><span class="status status-confirmed">Confirmada</span></td>
                                <td class="actions">
                                    <div class="btn-group">
                                        <button class="btn btn-success btn-action" title="Check-in" data-action="checkin" data-id="R-1234">
                                            <i class="fas fa-check" aria-hidden="true"></i>
                                            <span class="sr-only">Check-in</span>
                                        </button>
                                        <button class="btn btn-info btn-action" title="Ver Detalles" data-action="view" data-id="R-1234">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                            <span class="sr-only">Ver Detalles</span>
                                        </button>
                                        <button class="btn btn-warning btn-action" title="Editar" data-action="edit" data-id="R-1234">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                            <span class="sr-only">Editar</span>
                                        </button>
                                        <button class="btn btn-danger btn-action" title="Cancelar" data-action="cancel" data-id="R-1234">
                                            <i class="fas fa-times" aria-hidden="true"></i>
                                            <span class="sr-only">Cancelar</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr data-reservation-id="R-1235">
                                <td data-time="13:00">13:00</td>
                                <td data-customer="Carlos Rodríguez">
                                    <div class="user-info">
                                        <img src="277591295863fc51586860f820966128.jpg" alt="" class="user-avatar-sm">
                                        <div>
                                            <div class="user-name">Carlos Rodríguez</div>
                                            <div class="user-email">carlos@example.com</div>
                                        </div>
                                    </div>
                                </td>
                                <td data-people="2"><span class="people-count">2</span></td>
                                <td data-table="I3 (Interior)"><span class="badge bg-secondary">I3 (Interior)</span></td>
                                <td data-status="Confirmada"><span class="status status-confirmed">Confirmada</span></td>
                                <td class="actions">
                                    <div class="btn-group">
                                        <button class="btn btn-success btn-action" title="Check-in" data-action="checkin" data-id="R-1236">
                                            <i class="fas fa-check" aria-hidden="true"></i>
                                            <span class="sr-only">Check-in</span>
                                        </button>
                                        <button class="btn btn-info btn-action" title="Ver Detalles " data-action="view" data-id="R-1236">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                            <span class="sr-only">Ver Detalles </span>
                                        </button>
                                        <button class="btn btn-warning btn-action" title="Editar" data-action="edit" data-id="R-1236">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                            <span class="sr-only">Editar</span>
                                        </button>
                                        <button class="btn btn-danger btn-action" title="Cancelar" data-action="cancel" data-id="R-1236">
                                            <i class="fas fa-times" aria-hidden="true"></i>
                                            <span class="sr-only">Cancelar</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación de Control -->
                <div class="pagination-controls">
                    <!-- Botón de página anterior -->
                    <button class="btn btn-sm" disabled>
                        <i class="fas fa-chevron-left"></i> Anterior
                    </button>

                    <!-- Contenedor de números de página -->
                    <div class="page-numbers">
                        <!-- Botón de página activa -->
                        <button class="btn btn-sm btn-primary">1</button>
                        <!-- Botones de páginas inactivas -->
                        <button class="btn btn-sm">2</button>
                        <button class="btn btn-sm">3</button>
                    </div>

                    <!-- Botón de siguiente página -->
                    <button class="btn btn-sm">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Disponibilidad por Franjas Horarias -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Disponibilidad por Franjas Horarias</h2>
                    <div class="section-actions">
                        <label for="date-selector">Seleccionar Fecha:</label>
                        <select id="date-selector" class="form-control">
                            <option value="today">Hoy</option>
                            <option value="tomorrow">Mañana</option>
                            <option value="this-week">Esta semana</option>
                            <option value="next-week">Próxima semana</option>
                        </select>
                    </div>
                </div>
                <div class="time-slots-container">
                    <div class="time-slot available">
                        <span class="time">12:00</span>
                        <span class="availability">12/15</span>
                        <div class="availability-indicator"></div>
                    </div>
                    <div class="time-slot limited">
                        <span class="time">13:00</span>
                        <span class="availability">5/15</span>
                        <div class="availability-indicator"></div>
                    </div>
                    <div class="time-slot full">
                        <span class="time">14:00</span>
                        <span class="availability">0/15</span>
                        <div class="availability-indicator"></div>
                    </div>
                    <div class="time-slot available">
                        <span class="time">15:00</span>
                        <span class="availability">10/15</span>
                        <div class="availability-indicator"></div>
                    </div>
                    <div class="time-slot available">
                        <span class="time">16:00</span>
                        <span class="availability">14/15</span>
                        <div class="availability-indicator"></div>
                    </div>
                    <div class="time-slot available">
                        <span class="time">17:00</span>
                        <span class="availability">11/15</span>
                        <div class="availability-indicator"></div>
                    </div>
                    <div class="time-slot limited">
                        <span class="time">18:00</span>
                        <span class="availability">6/15</span>
                        <div class="availability-indicator"></div>
                    </div>
                    <div class="time-slot limited">
                        <span class="time">19:00</span>
                        <span class="availability">4/15</span>
                        <div class="availability-indicator"></div>
                    </div>
                    <div class="time-slot limited">
                        <span class="time">20:00</span>
                        <span class="availability">3/15</span>
                        <div class="availability-indicator"></div>
                    </div>
                    <div class="time-slot full">
                        <span class="time">21:00</span>
                        <span class="availability">0/15</span>
                        <div class="availability-indicator"></div>
                    </div>
                    <div class="time-slot available">
                        <span class="time">22:00</span>
                        <span class="availability">8/15</span>
                        <div class="availability-indicator"></div>
                    </div>
                </div>
                <div class="status-legend">
                    <div class="legend-item">
                        <div class="legend-color legend-available"></div>
                        <span>Disponible</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color legend-limited"></div>
                        <span>Limitado</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color legend-full"></div>
                        <span>Completo</span>
                    </div>
                </div>
            </div>

            <!-- Dashboard Tab -->
            <div id="dashboard-tab" class="tab-content active">
                <!-- Header with date and refresh option -->
                <div class="dashboard-header">
                    <h2>Panel de Control <span id="current-date"></span></h2>
                    <div class="quick-actions">
                        <button class="btn btn-warning" onclick="refreshDashboard()" data-toggle="tooltip" title="Actualizar">
                            <i class="fas fa-history"></i> Actualizar
                        </button>
                        <button class="btn btn-info" onclick="openTableAssignmentModal()" data-toggle="tooltip" title="Asignar mesas a reservas">
                            <i class="fas fa-chair"></i> Asignar Mesa
                        </button>
                    </div>
                </div>

                <!-- Stats Cards with improved hover effects and animations -->
                <div class="dashboard-cards">
                    <div class="card stat-card animate-card" aria-label="Reservas Hoy">
                        <div class="card-body">
                            <div class="card-icon bg-primary">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="card-stats">
                                <div class="card-value" id="reservations-today">0</div>
                                <div class="card-label">Reservas Hoy</div>
                                <div class="stat-change" id="reservations-change">
                                    <i class="fas fa-arrow-up"></i> <span>0%</span> vs ayer
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="#" onclick="showTab('reservations')">Ver detalles <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                    <div class="card stat-card animate-card" aria-label="Comensales Hoy">
                        <div class="card-body">
                            <div class="card-icon bg-success">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="card-stats">
                                <div class="card-value" id="customers-today">0</div>
                                <div class="card-label">Comensales Hoy</div>
                                <div class="stat-change" id="customers-change">
                                    <i class="fas fa-arrow-up"></i> <span>0%</span> vs ayer
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="#" onclick="showTab('customers')">Ver detalles <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                    <div class="card stat-card animate-card" aria-label="No Shows">
                        <div class="card-body">
                            <div class="card-icon bg-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="card-stats">
                                <div class="card-value" id="no-shows">0</div>
                                <div class="card-label">No Shows</div>
                                <div class="stat-change" id="no-shows-change">
                                    <i class="fas fa-arrow-down"></i> <span>0%</span> vs ayer
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="#" onclick="filterReservationsByStatus('noshow')">Ver detalles <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                    <div class="card stat-card animate-card" aria-label="Tasa de Ocupación">
                        <div class="card-body">
                            <div class="card-icon bg-info">
                                <i class="fas fa-percent"></i>
                            </div>
                            <div class="card-stats">
                                <div class="card-value" id="occupancy-rate">0</div>
                                <div class="card-label">Tasa de Ocupación</div>
                                <div class="stat-change" id="occupancy-change">
                                    <i class="fas fa-arrow-up"></i> <span>0%</span> vs ayer
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="#" onclick="showTab('reports')">Ver detalles <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="section">
                            <div class="section-header">
                                <h2 class="section-title">Mapa de Mesas</h2>
                                <div class="section-actions">
                                    <div class="btn-group">
                                        <button class="btn btn-outline-secondary active" data-view="grid">
                                            <i class="fas fa-th"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" data-view="map">
                                            <i class="fas fa-map"></i>
                                        </button>
                                    </div>
                                    <button class="btn btn-info" onclick="refreshTableMap()">
                                        <i class="fas fa-sync-alt"></i> Actualizar
                                    </button>
                                </div>
                            </div>

                            <div class="table-map-container">
                                <div class="table-map-filters">
                                    <button class="btn btn-sm btn-outline-secondary active" data-filter="all">Todas</button>
                                    <button class="btn btn-sm btn-outline-success" data-filter="available">Libres</button>
                                    <button class="btn btn-sm btn-outline-warning" data-filter="reserved">Reservadas</button>
                                    <button class="btn btn-sm btn-outline-danger" data-filter="occupied">Ocupadas</button>
                                </div>

                                <div id="table-map" class="table-map">
                                    <!-- Mesas interiores -->
                                    <div class="card table-item" style="background-color: #d4edda;" aria-label="Mesa Interior I1 Libre">
                                        <i class="fas fa-chair fa-2x"></i>
                                        <div class="table-item-label">I1</div>
                                        <div class="table-item-status">Libre</div>
                                    </div>
                                    <div class="card table-item" style="background-color: #f8d7da;" aria-label="Mesa Interior I2 Ocupada">
                                        <i class="fas fa-chair fa-2x"></i>
                                        <div class="table-item-label">I2</div>
                                        <div class="table-item-status">Ocupada</div>
                                    </div>
                                    <div class="card table-item" style="background-color: #fff3cd;" aria-label="Mesa Interior I3 Reservada">
                                        <i class="fas fa-chair fa-2x"></i>
                                        <div class="table-item-label">I3</div>
                                        <div class="table-item-status">Reservada</div>
                                    </div>

                                    <!-- Mesas terraza -->
                                    <div class="card table-item" style="background-color: #d4edda;" aria-label="Mesa Terraza T1 Libre">
                                        <i class="fas fa-umbrella-beach fa-2x"></i>
                                        <div class="table-item-label">T1</div>
                                        <div class="table-item-status">Libre</div>
                                    </div>
                                    <div class="card table-item" style="background-color: #fff3cd;" aria-label="Mesa Terraza T2 Reservada">
                                        <i class="fas fa-umbrella-beach fa-2x"></i>
                                        <div class="table-item-label">T2</div>
                                        <div class="table-item-status">Reservada</div>
                                    </div>

                                    <!-- Mesas VIP -->
                                    <div class="card table-item" style="background-color: #f8d7da;" aria-label="Mesa VIP V1 Ocupada">
                                        <i class="fas fa-crown fa-2x"></i>
                                        <div class="table-item-label">V1</div>
                                        <div class="table-item-status">Ocupada</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Información de Usuario -->
            <div class="section mt-4">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-info-circle me-2"></i>Información de la Sesión</h2>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>ID de Usuario:</strong> <?php echo htmlspecialchars($userId); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($userEmail); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($userName); ?></p>
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

        <!-- Tab de Clientes - SERÁ REEMPLAZADO POR LÓGICA DE showTab -->
        </div>
    </div>
</div>

<!-- Modal para Nuevo Cliente -->
<div class="modal fade" id="newClientModal" tabindex="-1" aria-labelledby="newClientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newClientModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Nuevo Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="newClientForm">
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="clientName" class="form-label">Nombre Completo *</label>
                                <input type="text" class="form-control" id="clientName" name="nombre" required 
                                       placeholder="Ej: Juan Pérez García">
                                <div class="invalid-feedback" id="clientName-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="clientEmail" class="form-label">Correo Electrónico *</label>
                                <input type="email" class="form-control" id="clientEmail" name="correo" required 
                                       placeholder="ejemplo@correo.com">
                                <div class="invalid-feedback" id="clientEmail-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="clientPhone" class="form-label">Teléfono *</label>
                                <input type="tel" class="form-control" id="clientPhone" name="telefono" required 
                                       placeholder="1234567890" maxlength="10" pattern="[0-9]{10}">
                                <div class="invalid-feedback" id="clientPhone-error"></div>
                                <div class="form-text">Ingrese 10 dígitos sin espacios ni guiones</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mensaje de estado -->
                    <div id="clientMessage" class="alert d-none" role="alert"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="saveClientBtn">
                    <i class="fas fa-save me-2"></i>Crear Cliente
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Nueva Reserva -->
<div class="modal fade" id="newReservationModal" tabindex="-1" aria-labelledby="newReservationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newReservationModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Nueva Reserva
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="newReservationForm">
                    <div class="row">
                        <!-- Información del Cliente -->
                        <div class="col-md-6">
                            <h6 class="mb-3"><i class="fas fa-user me-2"></i>Información del Cliente</h6>
                            
                            <div class="mb-3">
                                <label for="clientSelect" class="form-label">Cliente *</label>
                                <select class="form-select" id="clientSelect" name="client_id" required>
                                    <option value="">Seleccionar cliente...</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="numPeople" class="form-label">Número de Personas *</label>
                                <select class="form-select" id="numPeople" name="people" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="1">1 persona</option>
                                    <option value="2">2 personas</option>
                                    <option value="3">3 personas</option>
                                    <option value="4">4 personas</option>
                                    <option value="5">5 personas</option>
                                    <option value="6">6 personas</option>
                                    <option value="7">7 personas</option>
                                    <option value="8">8 personas</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Información de la Reserva -->
                        <div class="col-md-6">
                            <h6 class="mb-3"><i class="fas fa-calendar-alt me-2"></i>Información de la Reserva</h6>
                            
                            <div class="mb-3">
                                <label for="reservationDate" class="form-label">Fecha *</label>
                                <input type="date" class="form-control" id="reservationDate" name="date" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reservationTime" class="form-label">Hora *</label>
                                <select class="form-select" id="reservationTime" name="time" required>
                                    <option value="">Seleccionar hora...</option>
                                    <option value="12:00">12:00</option>
                                    <option value="12:30">12:30</option>
                                    <option value="13:00">13:00</option>
                                    <option value="13:30">13:30</option>
                                    <option value="14:00">14:00</option>
                                    <option value="14:30">14:30</option>
                                    <option value="15:00">15:00</option>
                                    <option value="15:30">15:30</option>
                                    <option value="16:00">16:00</option>
                                    <option value="16:30">16:30</option>
                                    <option value="17:00">17:00</option>
                                    <option value="17:30">17:30</option>
                                    <option value="18:00">18:00</option>
                                    <option value="18:30">18:30</option>
                                    <option value="19:00">19:00</option>
                                    <option value="19:30">19:30</option>
                                    <option value="20:00">20:00</option>
                                    <option value="20:30">20:30</option>
                                    <option value="21:00">21:00</option>
                                    <option value="21:30">21:30</option>
                                    <option value="22:00">22:00</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Selección de Mesa -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="mb-3"><i class="fas fa-chair me-2"></i>Selección de Mesa</h6>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-primary" id="searchTablesBtn" disabled>
                                    <i class="fas fa-search me-2"></i>Buscar Mesas Disponibles
                                </button>
                            </div>
                            
                            <div id="availableTablesContainer" class="d-none">
                                <label class="form-label">Mesas Disponibles *</label>
                                <div id="availableTablesList" class="row">
                                    <!-- Las mesas se cargarán aquí dinámicamente -->
                                </div>
                                <input type="hidden" id="selectedTable" name="table_id">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mensaje de estado -->
                    <div id="reservationMessage" class="alert d-none" role="alert"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="saveReservationBtn" disabled>
                    <i class="fas fa-save me-2"></i>Crear Reserva
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para cargar datos dinámicos -->
<script>
// Actualizar datos cada 5 minutos
setInterval(function() {
    loadTodayStats();
    loadTodayReservations();
    loadTimeSlots();
    loadTablesStatus();
}, 300000); // 5 minutos

// Cargar estadísticas del día
function loadTodayStats() {
    fetch('dashboard_api.php?action=today_stats')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const stats = result.data;
                
                // Actualizar contadores
                document.getElementById('reservations-today').textContent = stats.reservations_today;
                document.getElementById('customers-today').textContent = stats.customers_today;
                document.getElementById('no-shows').textContent = stats.no_shows;
                document.getElementById('occupancy-rate').textContent = stats.occupancy_rate + '%';
                
                // Actualizar cambios porcentuales
                updateChangeIndicator('reservations-change', stats.reservations_change);
                updateChangeIndicator('customers-change', stats.customers_change);
                updateChangeIndicator('no-shows-change', stats.no_shows_change);
                updateChangeIndicator('occupancy-change', stats.occupancy_change);
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

// Actualizar indicadores de cambio
function updateChangeIndicator(elementId, change) {
    const element = document.getElementById(elementId);
    if (element) {
        const span = element.querySelector('span');
        const icon = element.querySelector('i');
        
        if (span) span.textContent = Math.abs(change) + '%';
        
        if (icon) {
            icon.className = change >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
        }
        
        element.className = change >= 0 ? 'stat-change positive' : 'stat-change negative';
    }
}

// Cargar reservaciones del día
function loadTodayReservations() {
    fetch('dashboard_api.php?action=today_reservations')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                updateReservationsTable(result.data);
            }
        })
        .catch(error => console.error('Error loading reservations:', error));
}

// Actualizar tabla de reservaciones
function updateReservationsTable(reservations) {
    const tbody = document.getElementById('today-reservations');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    reservations.forEach(reservation => {
        const row = document.createElement('tr');
        row.setAttribute('data-reservation-id', reservation.id_reservacion);
        
        const statusClass = getStatusClass(reservation.estado);
        const statusText = getStatusText(reservation.estado);
        
        row.innerHTML = `
            <td data-time="${reservation.hora_reserva}">${reservation.hora_reserva.substring(0, 5)}</td>
            <td data-customer="${reservation.cliente_nombre}">
                <div class="user-info">
                    <img src="277591295863fc51586860f820966128.jpg" alt="" class="user-avatar-sm">
                    <div>
                        <div class="user-name">${reservation.cliente_nombre}</div>
                        <div class="user-email">${reservation.cliente_email}</div>
                    </div>
                </div>
            </td>
            <td data-people="${reservation.num_personas}"><span class="people-count">${reservation.num_personas}</span></td>
            <td data-table="Mesa ${reservation.mesa_numero}"><span class="badge bg-info">Mesa ${reservation.mesa_numero}</span></td>
            <td data-status="${reservation.estado}"><span class="status ${statusClass}">${statusText}</span></td>
            <td class="actions">
                <div class="btn-group">
                    ${generateActionButtons(reservation)}
                </div>
            </td>
        `;
        
        tbody.appendChild(row);
    });
}

// Generar botones de acción según el estado
function generateActionButtons(reservation) {
    let buttons = '';
    
    if (reservation.estado === 'confirmada') {
        buttons += `
            <button class="btn btn-success btn-action" title="Check-in" onclick="updateReservationStatus('${reservation.id_reservacion}', 'check_in')">
                <i class="fas fa-check"></i>
            </button>
        `;
    }
    
    buttons += `
        <button class="btn btn-info btn-action" title="Ver Detalles" onclick="viewReservationDetails('${reservation.id_reservacion}')">
            <i class="fas fa-eye"></i>
        </button>
        <button class="btn btn-warning btn-action" title="Editar" onclick="editReservation('${reservation.id_reservacion}')">
            <i class="fas fa-edit"></i>
        </button>
    `;
    
    if (reservation.estado !== 'cancelada') {
        buttons += `
            <button class="btn btn-danger btn-action" title="Cancelar" onclick="updateReservationStatus('${reservation.id_reservacion}', 'cancelada')">
                <i class="fas fa-times"></i>
            </button>
        `;
    }
    
    return buttons;
}

// Obtener clase CSS para el estado
function getStatusClass(status) {
    const statusClasses = {
        'confirmada': 'status-confirmed',
        'check_in': 'status-checkin',
        'cancelada': 'status-cancelled',
        'noshow': 'status-noshow',
        'completada': 'status-completed'
    };
    return statusClasses[status] || 'status-pending';
}

// Obtener texto para el estado
function getStatusText(status) {
    const statusTexts = {
        'confirmada': 'Confirmada',
        'check_in': 'Check-in',
        'cancelada': 'Cancelada',
        'noshow': 'No Show',
        'completada': 'Completada'
    };
    return statusTexts[status] || status;
}

// Actualizar estado de reservación
function updateReservationStatus(reservationId, newStatus) {
    const formData = new FormData();
    formData.append('reservation_id', reservationId);
    formData.append('status', newStatus);
    
    fetch('dashboard_api.php?action=update_reservation_status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Recargar las reservaciones para mostrar el cambio
            loadTodayReservations();
            loadTodayStats();
            
            // Mostrar notificación de éxito
            showNotification('Estado actualizado correctamente', 'success');
        } else {
            showNotification(result.message || 'Error al actualizar', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating status:', error);
        showNotification('Error de conexión', 'error');
    });
}

// Cargar franjas horarias
function loadTimeSlots() {
    const date = document.getElementById('date-selector')?.value || 'today';
    let dateParam = '';
    
    switch(date) {
        case 'today':
            dateParam = new Date().toISOString().split('T')[0];
            break;
        case 'tomorrow':
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            dateParam = tomorrow.toISOString().split('T')[0];
            break;
        default:
            dateParam = new Date().toISOString().split('T')[0];
    }
    
    fetch(`dashboard_api.php?action=time_slots&date=${dateParam}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                updateTimeSlots(result.data);
            }
        })
        .catch(error => console.error('Error loading time slots:', error));
}

// Actualizar franjas horarias
function updateTimeSlots(slots) {
    const container = document.querySelector('.time-slots-container');
    if (!container) return;
    
    container.innerHTML = '';
    
    slots.forEach(slot => {
        const slotElement = document.createElement('div');
        slotElement.className = `time-slot ${slot.status}`;
        
        slotElement.innerHTML = `
            <span class="time">${slot.time}</span>
            <span class="availability">${slot.available}/${slot.total}</span>
            <div class="availability-indicator"></div>
        `;
        
        container.appendChild(slotElement);
    });
}

// Cargar estado de mesas
function loadTablesStatus() {
    fetch('dashboard_api.php?action=tables_status')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                updateTablesMap(result.data);
            }
        })
        .catch(error => console.error('Error loading tables:', error));
}

// Actualizar mapa de mesas
function updateTablesMap(tables) {
    const container = document.getElementById('table-map');
    if (!container) return;
    
    container.innerHTML = '';
    
    tables.forEach(table => {
        const tableElement = document.createElement('div');
        tableElement.className = 'card table-item';
        
        const backgroundColor = getTableBackgroundColor(table.estado);
        tableElement.style.backgroundColor = backgroundColor;
        tableElement.setAttribute('aria-label', `Mesa ${table.numero} ${table.estado}`);
        
        const icon = getTableIcon(table.numero);
        
        tableElement.innerHTML = `
            <i class="${icon} fa-2x"></i>
            <div class="table-item-label">${table.numero}</div>
            <div class="table-item-status">${getTableStatusText(table.estado)}</div>
        `;
        
        container.appendChild(tableElement);
    });
}

// Obtener color de fondo para el estado de la mesa
function getTableBackgroundColor(status) {
    const colors = {
        'libre': '#d4edda',
        'reservada': '#fff3cd',
        'ocupada': '#f8d7da'
    };
    return colors[status] || '#e9ecef';
}

// Obtener icono para la mesa
function getTableIcon(numero) {
    if (numero.toString().startsWith('T')) return 'fas fa-umbrella-beach';
    if (numero.toString().startsWith('V')) return 'fas fa-crown';
    return 'fas fa-chair';
}

// Obtener texto del estado de la mesa
function getTableStatusText(status) {
    const texts = {
        'libre': 'Libre',
        'reservada': 'Reservada',
        'ocupada': 'Ocupada'
    };
    return texts[status] || status;
}

// Mostrar notificación
function showNotification(message, type = 'info') {
    // Implementar sistema de notificaciones
    console.log(`${type.toUpperCase()}: ${message}`);
}

// Funciones placeholder para las acciones
function viewReservationDetails(id) {
    console.log('Ver detalles de reservación:', id);
}

function editReservation(id) {
    console.log('Editar reservación:', id);
}

// Event listeners para selectores
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, inicializando dashboard...');
    
    const dateSelector = document.getElementById('date-selector');
    if (dateSelector) {
        dateSelector.addEventListener('change', loadTimeSlots);
    }
    
    // Configurar fecha mínima para reservaciones (hoy)
    const reservationDateInput = document.getElementById('reservationDate');
    if (reservationDateInput) {
        const today = new Date().toISOString().split('T')[0];
        reservationDateInput.min = today;
        reservationDateInput.value = today;
    }
    
    // Event listeners para el formulario de nueva reserva
    setupNewReservationForm();
    
    // Event listeners para la gestión de clientes
    setupClientsEvents();
    
    // Cargar datos iniciales
    loadTodayStats();
    loadTodayReservations();
    loadTimeSlots();
    loadTablesStatus();
    
    console.log('Dashboard inicializado correctamente');
});

// Configurar formulario de nueva reserva
function setupNewReservationForm() {
    // Agregar event listener al botón de nueva reserva
    const newReservationBtn = document.getElementById('newReservationBtn');
    if (newReservationBtn) {
        newReservationBtn.addEventListener('click', openNewReservationModal);
        console.log('Event listener agregado al botón Nueva Reserva');
    }
    
    const numPeopleSelect = document.getElementById('numPeople');
    const dateInput = document.getElementById('reservationDate');
    const timeSelect = document.getElementById('reservationTime');
    const searchBtn = document.getElementById('searchTablesBtn');
    const saveBtn = document.getElementById('saveReservationBtn');
    
    // Habilitar búsqueda cuando se completen los campos necesarios
    function checkSearchAvailability() {
        const canSearch = numPeopleSelect.value && dateInput.value && timeSelect.value;
        searchBtn.disabled = !canSearch;
        
        if (!canSearch) {
            document.getElementById('availableTablesContainer').classList.add('d-none');
            saveBtn.disabled = true;
        }
    }
    
    if (numPeopleSelect) numPeopleSelect.addEventListener('change', checkSearchAvailability);
    if (dateInput) dateInput.addEventListener('change', checkSearchAvailability);
    if (timeSelect) timeSelect.addEventListener('change', checkSearchAvailability);
    
    // Búsqueda de mesas
    if (searchBtn) {
        searchBtn.addEventListener('click', searchAvailableTables);
    }
    
    // Guardar reserva
    if (saveBtn) {
        saveBtn.addEventListener('click', saveNewReservation);
    }
}

// Abrir modal de nueva reserva
function openNewReservationModal() {
    console.log('Abriendo modal de nueva reserva...');
    
    try {
        // PASO 1: Ocultar TODOS los overlays antes de abrir el modal
        const overlays = document.querySelectorAll('.loader-overlay, .loading-overlay, [class*="overlay"], [class*="loader"]');
        overlays.forEach(overlay => {
            overlay.classList.remove('active');
            overlay.style.display = 'none';
            overlay.style.visibility = 'hidden';
            overlay.style.opacity = '0';
            overlay.style.zIndex = '-1';
            overlay.style.pointerEvents = 'none';
        });
        
        console.log('Overlays ocultados:', overlays.length);
        
        // PASO 2: Cargar clientes
        loadClients();
        
        // PASO 3: Verificar que el modal existe
        const modalElement = document.getElementById('newReservationModal');
        if (!modalElement) {
            console.error('Modal element not found');
            return;
        }
        
        // PASO 4: Verificar que Bootstrap está disponible
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap no está disponible');
            alert('Error: Bootstrap no está cargado correctamente');
            return;
        }
        
        // PASO 5: Asegurar que el modal tenga z-index alto
        modalElement.style.zIndex = '1055';
        
        // PASO 6: Mostrar modal
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        console.log('Modal mostrado correctamente');
        
        // PASO 7: Verificar después de 100ms que no hay overlays bloqueando
        setTimeout(() => {
            const stillVisibleOverlays = Array.from(overlays).filter(overlay => {
                const styles = window.getComputedStyle(overlay);
                return styles.display !== 'none' && styles.visibility !== 'hidden' && styles.opacity !== '0';
            });
            
            if (stillVisibleOverlays.length > 0) {
                console.warn('Overlays aún visibles después de abrir modal:', stillVisibleOverlays);
                stillVisibleOverlays.forEach(overlay => {
                    overlay.style.display = 'none';
                    overlay.style.visibility = 'hidden';
                    overlay.style.opacity = '0';
                });
            }
        }, 100);
        
    } catch (error) {
        console.error('Error al abrir modal:', error);
        alert('Error al abrir el modal: ' + error.message);
    }
}

// Cargar lista de clientes
function loadClients() {
    console.log('Cargando clientes...');
    
    fetch('dashboard_api.php?action=get_clients')
        .then(response => {
            console.log('Respuesta recibida:', response);
            return response.json();
        })
        .then(result => {
            console.log('Datos de clientes:', result);
            
            if (result.success) {
                const clientSelect = document.getElementById('clientSelect');
                if (clientSelect) {
                    clientSelect.innerHTML = '<option value="">Seleccionar cliente...</option>';
                    
                    result.data.forEach(client => {
                        const option = document.createElement('option');
                        option.value = client.id_usuario;
                        option.textContent = `${client.nombre} (${client.correo})`;
                        clientSelect.appendChild(option);
                    });
                    
                    console.log('Clientes cargados correctamente');
                } else {
                    console.error('Elemento clientSelect no encontrado');
                }
            } else {
                console.error('Error en la respuesta:', result);
                alert('Error al cargar clientes: ' + (result.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error loading clients:', error);
            alert('Error de conexión al cargar clientes');
        });
}

// Buscar mesas disponibles
function searchAvailableTables() {
    const date = document.getElementById('reservationDate').value;
    const time = document.getElementById('reservationTime').value;
    const capacity = document.getElementById('numPeople').value;
    
    const searchBtn = document.getElementById('searchTablesBtn');
    const originalText = searchBtn.innerHTML;
    searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Buscando...';
    searchBtn.disabled = true;
    
    fetch(`dashboard_api.php?action=get_available_tables&date=${date}&time=${time}&capacity=${capacity}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                displayAvailableTables(result.data);
            } else {
                showMessage('No se pudieron cargar las mesas disponibles', 'danger');
            }
        })
        .catch(error => {
            console.error('Error searching tables:', error);
            showMessage('Error de conexión', 'danger');
        })
        .finally(() => {
            searchBtn.innerHTML = originalText;
            searchBtn.disabled = false;
        });
}

// Mostrar mesas disponibles
function displayAvailableTables(tables) {
    const container = document.getElementById('availableTablesContainer');
    const tablesList = document.getElementById('availableTablesList');
    
    tablesList.innerHTML = '';
    
    if (tables.length === 0) {
        tablesList.innerHTML = '<div class="col-12"><div class="alert alert-warning">No hay mesas disponibles para la fecha y hora seleccionada.</div></div>';
        container.classList.remove('d-none');
        return;
    }
    
    tables.forEach(table => {
        const tableCard = document.createElement('div');
        tableCard.className = 'col-md-4 col-sm-6 mb-3';
        tableCard.innerHTML = `
            <div class="card table-card" data-table-id="${table.id_mesa}" onclick="selectTable('${table.id_mesa}', '${table.numero}')">
                <div class="card-body text-center">
                    <i class="fas fa-chair fa-2x mb-2"></i>
                    <h6 class="card-title">Mesa ${table.numero}</h6>
                    <p class="card-text">Capacidad: ${table.capacidad} personas</p>
                </div>
            </div>
        `;
        tablesList.appendChild(tableCard);
    });
    
    container.classList.remove('d-none');
}

// Seleccionar mesa
function selectTable(tableId, tableNumber) {
    // Remover selección anterior
    document.querySelectorAll('.table-card').forEach(card => {
        card.classList.remove('border-primary', 'bg-light');
    });
    
    // Seleccionar nueva mesa
    const selectedCard = document.querySelector(`[data-table-id="${tableId}"]`);
    selectedCard.classList.add('border-primary', 'bg-light');
    
    // Guardar selección
    document.getElementById('selectedTable').value = tableId;
    
    // Habilitar botón guardar
    document.getElementById('saveReservationBtn').disabled = false;
    
    showMessage(`Mesa ${tableNumber} seleccionada`, 'success');
}

// Guardar nueva reserva
function saveNewReservation() {
    const form = document.getElementById('newReservationForm');
    const formData = new FormData(form);
    
    const saveBtn = document.getElementById('saveReservationBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
    saveBtn.disabled = true;
    
    fetch('dashboard_api.php?action=create_reservation', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showMessage('Reserva creada exitosamente', 'success');
            
            // Cerrar modal después de 2 segundos
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('newReservationModal'));
                modal.hide();
                
                // Recargar datos del dashboard
                loadTodayReservations();
                loadTodayStats();
                loadTimeSlots();
                loadTablesStatus();
                
                // Limpiar formulario
                form.reset();
                document.getElementById('availableTablesContainer').classList.add('d-none');
                document.getElementById('searchTablesBtn').disabled = true;
                saveBtn.disabled = true;
            }, 2000);
        } else {
            showMessage(result.message || 'Error al crear la reserva', 'danger');
        }
    })
    .catch(error => {
        console.error('Error saving reservation:', error);
        showMessage('Error de conexión', 'danger');
    })
    .finally(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// Mostrar mensaje en el modal
function showMessage(message, type) {
    const messageDiv = document.getElementById('reservationMessage');
    messageDiv.className = `alert alert-${type}`;
    messageDiv.textContent = message;
    messageDiv.classList.remove('d-none');
    
    // Ocultar mensaje después de 5 segundos
    setTimeout(() => {
        messageDiv.classList.add('d-none');
    }, 5000);
}

// Función de debug para ocultar overlays
function debugOverlays() {
    console.log('=== DEBUG: Buscando y ocultando overlays ===');
    
    // Buscar todos los posibles overlays
    const overlays = document.querySelectorAll('.loader-overlay, .loading-overlay, [class*="overlay"], [class*="loader"], [id*="loader"], [id*="overlay"]');
    
    console.log('Overlays encontrados:', overlays.length);
    
    overlays.forEach((overlay, index) => {
        const styles = window.getComputedStyle(overlay);
        console.log(`Overlay ${index}:`, {
            element: overlay,
            display: styles.display,
            visibility: styles.visibility,
            opacity: styles.opacity,
            zIndex: styles.zIndex,
            classes: overlay.className,
            id: overlay.id
        });
        
        // Ocultar agresivamente
        overlay.classList.remove('active');
        overlay.style.display = 'none';
        overlay.style.visibility = 'hidden';
        overlay.style.opacity = '0';
        overlay.style.zIndex = '-1';
        overlay.style.pointerEvents = 'none';
    });
    
    // También revisar el body por clases que puedan estar bloqueando
    const body = document.body;
    const bodyClasses = Array.from(body.classList);
    console.log('Body classes:', bodyClasses);
    
    // Remover clases que puedan estar causando overlays
    const problematicClasses = ['loading', 'overlay-active', 'modal-loading'];
    problematicClasses.forEach(cls => {
        if (body.classList.contains(cls)) {
            body.classList.remove(cls);
            console.log(`Removed class ${cls} from body`);
        }
    });
    
    // Verificar z-index de elementos con posición fixed/absolute
    const fixedElements = document.querySelectorAll('*');
    let highZIndexElements = [];
    
    fixedElements.forEach(el => {
        const styles = window.getComputedStyle(el);
        const zIndex = parseInt(styles.zIndex);
        if (styles.position === 'fixed' && zIndex > 1000) {
            highZIndexElements.push({
                element: el,
                zIndex: zIndex,
                display: styles.display,
                classes: el.className,
                id: el.id
            });
        }
    });
    
    console.log('Elementos con z-index alto (>1000):', highZIndexElements);
    
    alert(`Debug completado. Revisá la consola para detalles.\nOverlays encontrados: ${overlays.length}\nElementos con z-index alto: ${highZIndexElements.length}`);
}

// =================== FUNCIONES PARA GESTIÓN DE CLIENTES ===================

// Variables globales para clientes
let currentClientsPage = 1;
let clientsPerPage = 10;
let allClients = [];
let filteredClients = [];

// Función para mostrar/ocultar tabs
function showTab(tabName) {
    console.log('Cambiando a tab:', tabName);
    
    // Remover clase active de todos los enlaces del sidebar
    const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
    sidebarLinks.forEach(link => {
        link.classList.remove('active');
    });
    
    // Agregar clase active al enlace correspondiente
    const activeLink = document.querySelector(`[onclick="showTab('${tabName}')"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }
    
    // Obtener el contenedor principal donde se mostrará el contenido
    const mainContentArea = document.querySelector('.main-content-area');
    if (!mainContentArea) {
        console.error('Main content area not found');
        return;
    }
    
    // Guardar el header original si no está guardado
    if (!window.originalHeader) {
        window.originalHeader = document.querySelector('.header').outerHTML;
    }
    
    if (tabName === 'customers') {
        // Mostrar vista de clientes
        showClientsView(mainContentArea);
    } else {
        // Mostrar vista por defecto (dashboard principal)
        showDashboardView(mainContentArea);
    }
}

// Mostrar vista de clientes
function showClientsView(container) {
    container.innerHTML = `
        <div class="header">
            <div class="header-left">
                <button id="toggle-sidebar" class="sidebar-toggler" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Gestión de Clientes - Sistema de Reservas</h1>
            </div>
            <div class="header-right">
                <!-- Dark mode toggle -->
                <button id="dark-mode-toggle" class="btn btn-icon" aria-label="Toggle dark mode">
                    <i class="fas fa-moon"></i>
                </button>
                <!-- Search functionality -->
                <div class="search-container">
                    <input type="text" id="global-search" placeholder="Buscar..." aria-label="Search">
                    <button class="search-btn" aria-label="Search">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <!-- Notifications -->
                <div class="notification-bell" onclick="toggleNotifications()" aria-expanded="false">
                    <i class="fas fa-bell fa-lg"></i>
                    <span class="notification-badge">3</span>
                </div>
                <!-- User dropdown -->
                <div class="user-dropdown" aria-expanded="false">
                    <div class="user-info" onclick="toggleUserMenu()">
                        <img src="../src/images/admin-avatar.jpg" alt="Usuario Admin" class="user-avatar">
                        <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Header de Clientes -->
        <div class="dashboard-header">
            <h2>Gestión de Clientes</h2>
            <div class="quick-actions">
                <button class="btn btn-primary" onclick="openNewClientModal()">
                    <i class="fas fa-user-plus me-2"></i>Nuevo Cliente
                </button>
                <button class="btn btn-warning" onclick="refreshClients()" data-toggle="tooltip" title="Actualizar lista">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

        <!-- Estadísticas de Clientes -->
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">Estadísticas de Clientes</h3>
            </div>
            <div class="dashboard-cards">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-content">
                            <h3 id="total-clients">0</h3>
                            <p>Total Clientes</p>
                        </div>
                    </div>
                </div>
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="card-content">
                            <h3 id="new-clients-month">0</h3>
                            <p>Nuevos este Mes</p>
                        </div>
                    </div>
                </div>
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="card-content">
                            <h3 id="active-clients">0</h3>
                            <p>Clientes Activos</p>
                        </div>
                    </div>
                </div>
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="card-content">
                            <h3 id="avg-reservations">0</h3>
                            <p>Reservas Promedio</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Clientes -->
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">Lista de Clientes</h3>
                <div class="section-actions">
                    <div class="input-group">
                        <input type="text" id="client-search" placeholder="Buscar cliente..." class="form-control">
                        <button class="btn btn-outline-secondary" type="button" id="search-client-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de Clientes -->
            <div class="table-container">
                <table class="table-responsive">
                    <thead>
                        <tr>
                            <th scope="col">
                                <div class="th-content">
                                    <span class="th-text">Nombre</span>
                                    <button class="sort-btn" data-sort="nombre" aria-label="Ordenar por nombre">
                                        <i class="fas fa-sort"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col">
                                <div class="th-content">
                                    <span class="th-text">Correo</span>
                                    <button class="sort-btn" data-sort="correo" aria-label="Ordenar por correo">
                                        <i class="fas fa-sort"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col">
                                <div class="th-content">
                                    <span class="th-text">Teléfono</span>
                                    <button class="sort-btn" data-sort="telefono" aria-label="Ordenar por teléfono">
                                        <i class="fas fa-sort"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col">
                                <div class="th-content">
                                    <span class="th-text">Fecha Registro</span>
                                    <button class="sort-btn" data-sort="fecha_registro" aria-label="Ordenar por fecha">
                                        <i class="fas fa-sort"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col">
                                <div class="th-content">
                                    <span class="th-text">Total Reservas</span>
                                </div>
                            </th>
                            <th scope="col">
                                <div class="th-content">
                                    <span class="th-text">Acciones</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="clients-table-body">
                        <!-- Los clientes se cargarán aquí dinámicamente -->
                        <tr>
                            <td colspan="6" class="text-center">
                                <i class="fas fa-spinner fa-spin me-2"></i>Cargando clientes...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="pagination-controls">
                <button class="btn btn-sm" id="prev-clients-page" disabled>
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
                <div class="page-numbers" id="clients-page-numbers">
                    <button class="btn btn-sm btn-primary">1</button>
                </div>
                <button class="btn btn-sm" id="next-clients-page">
                    Siguiente <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    `;
    
    // Reconfigurar eventos para la nueva vista
    setupClientsEvents();
    
    // Cargar datos de clientes
    loadClientsData();
}

// Mostrar vista del dashboard principal
function showDashboardView(container) {
    // Aquí iría el contenido original del dashboard
    // Por ahora, podemos recargar la página o mantener el contenido original
    location.reload();
}

// Cargar datos de clientes
function loadClientsData() {
    loadClientsWithStats();
    loadClientStats();
}

// Cargar clientes con estadísticas
function loadClientsWithStats() {
    fetch('dashboard_api.php?action=get_clients_with_stats')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                allClients = result.data;
                filteredClients = [...allClients];
                updateClientsTable();
            } else {
                console.error('Error al cargar clientes:', result);
                showClientsError('Error al cargar la lista de clientes');
            }
        })
        .catch(error => {
            console.error('Error loading clients:', error);
            showClientsError('Error de conexión al cargar clientes');
        });
}

// Cargar estadísticas de clientes
function loadClientStats() {
    fetch('dashboard_api.php?action=get_client_stats')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                updateClientStats(result.data);
            } else {
                console.error('Error al cargar estadísticas:', result);
            }
        })
        .catch(error => {
            console.error('Error loading client stats:', error);
        });
}

// Actualizar estadísticas de clientes
function updateClientStats(stats) {
    document.getElementById('total-clients').textContent = stats.total_clients || 0;
    document.getElementById('new-clients-month').textContent = stats.new_clients_month || 0;
    document.getElementById('active-clients').textContent = stats.active_clients || 0;
    document.getElementById('avg-reservations').textContent = stats.avg_reservations || 0;
}

// Actualizar tabla de clientes
function updateClientsTable() {
    const tbody = document.getElementById('clients-table-body');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (filteredClients.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center">
                    <i class="fas fa-users me-2"></i>No se encontraron clientes
                </td>
            </tr>
        `;
        return;
    }
    
    // Calcular paginación
    const startIndex = (currentClientsPage - 1) * clientsPerPage;
    const endIndex = startIndex + clientsPerPage;
    const pageClients = filteredClients.slice(startIndex, endIndex);
    
    pageClients.forEach(client => {
        const row = document.createElement('tr');
        
        const fechaRegistro = new Date(client.fecha_registro).toLocaleDateString('es-ES');
        const ultimaReserva = client.ultima_reserva ? new Date(client.ultima_reserva).toLocaleDateString('es-ES') : 'Nunca';
        
        row.innerHTML = `
            <td>
                <div class="user-info">
                    <img src="../src/images/default-avatar.png" alt="" class="user-avatar-sm" 
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxNiIgcj0iMTYiIGZpbGw9IiNGM0Y0RjYiLz4KPHBhdGggZD0iTTE2IDhDMTQuOCA4IDEzLjggOC44IDEzLjggMTBDMTMuOCAxMS4yIDE0LjggMTIgMTYgMTJDMTcuMiAxMiAxOC4yIDExLjIgMTguMiAxMEMxOC4yIDguOCAxNy4yIDggMTYgOFoiIGZpbGw9IiM5Q0E0QUMiLz4KPHBhdGggZD0iTTIyIDIyQzIyIDIyIDIyIDIwIDIyIDE4QzIyIDE2IDE5IDEzLjggMTYgMTMuOEMxMyAxMy44IDEwIDE2IDEwIDE4QzEwIDIwIDEwIDIyIDEwIDIySDIyWiIgZmlsbD0iIzlDQTRBQyIvPgo8L3N2Zz4K'">
                    <div>
                        <div class="user-name">${client.nombre}</div>
                        <div class="user-email">${client.correo}</div>
                    </div>
                </div>
            </td>
            <td>${client.correo}</td>
            <td>${client.telefono}</td>
            <td>${fechaRegistro}</td>
            <td>
                <span class="badge ${client.total_reservas > 0 ? 'bg-success' : 'bg-secondary'}">
                    ${client.total_reservas} reservas
                </span>
                ${client.ultima_reserva ? `<br><small class="text-muted">Última: ${ultimaReserva}</small>` : ''}
            </td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-info btn-action" title="Ver Detalles" onclick="viewClientDetails('${client.id_usuario}')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-warning btn-action" title="Editar" onclick="editClient('${client.id_usuario}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-success btn-action" title="Nueva Reserva" onclick="createReservationForClient('${client.id_usuario}')">
                        <i class="fas fa-calendar-plus"></i>
                    </button>
                </div>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    updateClientsPagination();
}

// Actualizar paginación de clientes
function updateClientsPagination() {
    const totalPages = Math.ceil(filteredClients.length / clientsPerPage);
    const pageNumbers = document.getElementById('clients-page-numbers');
    const prevBtn = document.getElementById('prev-clients-page');
    const nextBtn = document.getElementById('next-clients-page');
    
    if (pageNumbers) {
        pageNumbers.innerHTML = '';
        
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `btn btn-sm ${i === currentClientsPage ? 'btn-primary' : ''}`;
            pageBtn.textContent = i;
            pageBtn.onclick = () => goToClientsPage(i);
            pageNumbers.appendChild(pageBtn);
        }
    }
    
    if (prevBtn) {
        prevBtn.disabled = currentClientsPage === 1;
        prevBtn.onclick = () => goToClientsPage(currentClientsPage - 1);
    }
    
    if (nextBtn) {
        nextBtn.disabled = currentClientsPage === totalPages || totalPages === 0;
        nextBtn.onclick = () => goToClientsPage(currentClientsPage + 1);
    }
}

// Ir a página específica de clientes
function goToClientsPage(page) {
    const totalPages = Math.ceil(filteredClients.length / clientsPerPage);
    if (page >= 1 && page <= totalPages) {
        currentClientsPage = page;
        updateClientsTable();
    }
}

// Mostrar error en la tabla de clientes
function showClientsError(message) {
    const tbody = document.getElementById('clients-table-body');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>${message}
                </td>
            </tr>
        `;
    }
}

// Abrir modal de nuevo cliente
function openNewClientModal() {
    console.log('Abriendo modal de nuevo cliente...');
    
    try {
        // Ocultar overlays
        const overlays = document.querySelectorAll('.loader-overlay, .loading-overlay, [class*="overlay"], [class*="loader"]');
        overlays.forEach(overlay => {
            overlay.classList.remove('active');
            overlay.style.display = 'none';
            overlay.style.visibility = 'hidden';
            overlay.style.opacity = '0';
            overlay.style.zIndex = '-1';
            overlay.style.pointerEvents = 'none';
        });
        
        // Verificar que el modal existe
        const modalElement = document.getElementById('newClientModal');
        if (!modalElement) {
            console.error('Modal element not found');
            return;
        }
        
        // Verificar que Bootstrap está disponible
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap no está disponible');
            alert('Error: Bootstrap no está cargado correctamente');
            return;
        }
        
        // Limpiar formulario
        const form = document.getElementById('newClientForm');
        if (form) {
            form.reset();
            clearClientValidation();
        }
        
        // Mostrar modal
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        console.log('Modal de cliente mostrado correctamente');
        
    } catch (error) {
        console.error('Error al abrir modal de cliente:', error);
        alert('Error al abrir el modal: ' + error.message);
    }
}

// Limpiar validación del formulario de cliente
function clearClientValidation() {
    const form = document.getElementById('newClientForm');
    if (form) {
        const inputs = form.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
        });
        
        const feedbacks = form.querySelectorAll('.invalid-feedback');
        feedbacks.forEach(feedback => {
            feedback.textContent = '';
        });
    }
    
    const messageDiv = document.getElementById('clientMessage');
    if (messageDiv) {
        messageDiv.classList.add('d-none');
    }
}

// Guardar nuevo cliente
function saveNewClient() {
    const form = document.getElementById('newClientForm');
    const formData = new FormData(form);
    
    const saveBtn = document.getElementById('saveClientBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
    saveBtn.disabled = true;
    
    // Limpiar validación anterior
    clearClientValidation();
    
    fetch('dashboard_api.php?action=create_client', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showClientMessage('Cliente creado exitosamente', 'success');
            
            // Cerrar modal después de 2 segundos
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('newClientModal'));
                modal.hide();
                
                // Recargar lista de clientes
                loadClientsData();
                
                // Limpiar formulario
                form.reset();
                clearClientValidation();
            }, 2000);
        } else {
            showClientMessage(result.message || 'Error al crear el cliente', 'danger');
            
            // Si hay errores específicos de validación, mostrarlos
            if (result.errors) {
                Object.keys(result.errors).forEach(field => {
                    const input = document.getElementById('client' + field.charAt(0).toUpperCase() + field.slice(1));
                    const error = document.getElementById('client' + field.charAt(0).toUpperCase() + field.slice(1) + '-error');
                    
                    if (input) {
                        input.classList.add('is-invalid');
                    }
                    if (error) {
                        error.textContent = result.errors[field];
                    }
                });
            }
        }
    })
    .catch(error => {
        console.error('Error saving client:', error);
        showClientMessage('Error de conexión', 'danger');
    })
    .finally(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// Mostrar mensaje en el modal de cliente
function showClientMessage(message, type) {
    const messageDiv = document.getElementById('clientMessage');
    messageDiv.className = `alert alert-${type}`;
    messageDiv.textContent = message;
    messageDiv.classList.remove('d-none');
    
    // Ocultar mensaje después de 5 segundos
    setTimeout(() => {
        messageDiv.classList.add('d-none');
    }, 5000);
}

// Refrescar lista de clientes
function refreshClients() {
    loadClientsData();
}

// Funciones placeholder para acciones de clientes
function viewClientDetails(clientId) {
    console.log('Ver detalles del cliente:', clientId);
    // TODO: Implementar modal de detalles
}

function editClient(clientId) {
    console.log('Editar cliente:', clientId);
    // TODO: Implementar modal de edición
}

function createReservationForClient(clientId) {
    console.log('Crear reserva para cliente:', clientId);
    // TODO: Implementar creación de reserva pre-seleccionando el cliente
}

// Configurar eventos para la gestión de clientes
function setupClientsEvents() {
    // Botón de guardar cliente
    const saveClientBtn = document.getElementById('saveClientBtn');
    if (saveClientBtn) {
        saveClientBtn.addEventListener('click', saveNewClient);
    }
    
    // Búsqueda de clientes
    const clientSearch = document.getElementById('client-search');
    if (clientSearch) {
        clientSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filteredClients = allClients.filter(client => 
                client.nombre.toLowerCase().includes(searchTerm) ||
                client.correo.toLowerCase().includes(searchTerm) ||
                client.telefono.includes(searchTerm)
            );
            currentClientsPage = 1;
            updateClientsTable();
        });
    }
    
    // Validación en tiempo real
    const clientNameInput = document.getElementById('clientName');
    const clientEmailInput = document.getElementById('clientEmail');
    const clientPhoneInput = document.getElementById('clientPhone');
    
    if (clientNameInput) {
        clientNameInput.addEventListener('input', function() {
            validateClientField(this, 'nombre');
        });
    }
    
    if (clientEmailInput) {
        clientEmailInput.addEventListener('input', function() {
            validateClientField(this, 'correo');
        });
    }
    
    if (clientPhoneInput) {
        clientPhoneInput.addEventListener('input', function() {
            validateClientField(this, 'telefono');
        });
    }
}

// Validar campo específico del cliente
function validateClientField(input, fieldType) {
    const value = input.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    switch (fieldType) {
        case 'nombre':
            if (value.length < 2) {
                isValid = false;
                errorMessage = 'El nombre debe tener al menos 2 caracteres';
            }
            break;
            
        case 'correo':
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (value && !emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Formato de correo inválido';
            }
            break;
            
        case 'telefono':
            const phoneRegex = /^[0-9]{10}$/;
            if (value && !phoneRegex.test(value)) {
                isValid = false;
                errorMessage = 'El teléfono debe tener exactamente 10 dígitos';
            }
            break;
    }
    
    // Actualizar clases de validación
    if (isValid && value) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    } else if (!isValid) {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
    } else {
        input.classList.remove('is-valid', 'is-invalid');
    }
    
    // Mostrar mensaje de error
    const errorElement = document.getElementById(input.id + '-error');
    if (errorElement) {
        errorElement.textContent = errorMessage;
    }
    
    return isValid;
}

// Asegurar que el loader no bloquee la UI cuando se muestran modales
document.addEventListener('DOMContentLoaded', function() {
    // Ocultar TODOS los posibles overlays/loaders
    const overlays = document.querySelectorAll('.loader-overlay, .loading-overlay, [class*="overlay"], [class*="loader"]');
    overlays.forEach(overlay => {
        overlay.classList.remove('active');
        overlay.style.display = 'none';
        overlay.style.visibility = 'hidden';
        overlay.style.opacity = '0';
        overlay.style.zIndex = '-1';
        overlay.style.pointerEvents = 'none';
    });

    // Si por alguna razón el loader está activo cuando se abre el modal, ocultarlo
    const newModal = document.getElementById('newReservationModal');
    if (newModal) {
        newModal.addEventListener('show.bs.modal', function() {
            // Ocultar todos los overlays al abrir el modal
            overlays.forEach(overlay => {
                overlay.classList.remove('active');
                overlay.style.display = 'none';
                overlay.style.visibility = 'hidden';
                overlay.style.opacity = '0';
                overlay.style.zIndex = '-1';
                overlay.style.pointerEvents = 'none';
            });
            
            console.log('Modal abierto, overlays ocultados');
        });
        
        newModal.addEventListener('shown.bs.modal', function() {
            console.log('Modal completamente visible');
        });
    }
});
</script>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>