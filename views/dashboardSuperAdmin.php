<?php
require_once '../controllers/AuthController.php';
require_once '../models/User.php';
require_once '../models/Restaurant.php';
require_once '../models/Reservation.php';

$authController = new AuthController();
$userModel = new User();
$restaurantModel = new Restaurant();
$reservationModel = new Reservation();

// Verificar si el usuario está autenticado y es super admin (rol = 3)
if (!$authController->isAuthenticated() || $authController->getCurrentUserRole() != 3) {
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

// Obtener estadísticas generales del sistema
try {
    $totalRestaurants = $restaurantModel->getTotalRestaurants();
    $totalUsers = $userModel->getTotalUsers();
    $totalReservations = $reservationModel->getTotalReservations();
    $todayReservations = $reservationModel->getTodayReservations();
    $allRestaurants = $restaurantModel->getAllRestaurants();
} catch (Exception $e) {
    $totalRestaurants = 0;
    $totalUsers = 0;
    $totalReservations = 0;
    $todayReservations = [];
    $allRestaurants = [];
    error_log("Error al obtener datos del sistema: " . $e->getMessage());
}

// Función para obtener la clase CSS del estado
function getStatusClass($estado) {
    switch (strtolower($estado)) {
        case 'confirmada':
        case 'confirmed':
            return 'status-confirmed';
        case 'checkin':
        case 'check-in':
            return 'status-checkin';
        case 'cancelada':
        case 'cancelled':
            return 'status-cancelled';
        case 'noshow':
        case 'no-show':
            return 'status-noshow';
        case 'completada':
        case 'completed':
            return 'status-completed';
        case 'pendiente':
        case 'pending':
        default:
            return 'status-pending';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Administrador - La Bella Mesa</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Toastify -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.js"></script>
    <!-- Custom Dashboard CSS -->
    <link rel="stylesheet" href="../src/css/dashboard_admin.css">
    
    <style>
        /* Estilos adicionales para Super Admin */
        .super-admin-badge {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .restaurant-card {
            transition: all 0.3s ease;
            border: 1px solid #eee;
        }
        
        .restaurant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .restaurant-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .restaurant-image {
            height: 180px;
            object-fit: cover;
        }
        
        .system-overview {
            background: linear-gradient(135deg, #9aaeffff 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .overview-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .overview-subtitle {
            opacity: 0.9;
            margin-bottom: 0;
        }

        /* Fix para el modal - asegurar que esté por encima de todo */
        .modal {
            z-index: 9999 !important;
        }
        
        .modal-backdrop {
            z-index: 9998 !important;
        }
        
        .modal-dialog {
            z-index: 10000 !important;
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
            <div class="logo">
                <h2><i class="fas fa-crown"></i> Super Admin</h2>
                <button class="sidebar-toggler" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="system-status">
                <div class="status-indicator online"></div>
                <span>Sistema Online</span>
            </div>
            
            <ul class="sidebar-nav">
                <li>
                    <a href="#" class="active" onclick="showTab('dashboard')">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showTab('restaurants')">
                        <i class="fas fa-store"></i>
                        <span>Restaurantes</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showTab('users')">
                        <i class="fas fa-users"></i>
                        <span>Usuarios</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showTab('reservations')">
                        <i class="fas fa-calendar-check"></i>
                        <span>Reservaciones</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showTab('analytics')">
                        <i class="fas fa-chart-line"></i>
                        <span>Analíticas</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showTab('settings')">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                </li>
                <li>
                    <a href="login.php?logout=1" style="color: #e74c3c;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content Area -->
        <div class="main-content-area">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <button class="sidebar-toggler d-lg-none" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">Panel de Control General</h1>
                    <span class="super-admin-badge">Super Administrador</span>
                </div>
                <div class="header-right">
                    <div class="search-container">
                        <input type="text" placeholder="Buscar en el sistema...">
                        <button class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <div class="notification-bell">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">5</span>
                    </div>
                    
                    <div class="user-dropdown">
                        <div class="user-info">
                            <div class="user-avatar" style="background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                <?php echo strtoupper(substr($userName, 0, 1)); ?>
                            </div>
                            <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="dropdown-content">
                            <a href="#"><i class="fas fa-user"></i> Mi Perfil</a>
                            <a href="#"><i class="fas fa-cog"></i> Configuración</a>
                            <div class="dropdown-divider"></div>
                            <a href="login.php?logout=1"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content Container -->
            <div id="tabContent">
                <!-- Dashboard Principal -->
                <div id="dashboard-content" class="tab-content active">
                    <!-- System Overview -->
                    <div class="system-overview">
                        <h2 class="overview-title">
                            <i class="fas fa-crown me-2"></i>
                            Bienvenido al Panel de Super Administrador
                        </h2>
                        <p class="overview-subtitle">
                            Controla y administra todo el ecosistema de La Bella Mesa desde aquí
                        </p>
                    </div>

                    <!-- Dashboard Stats -->
                    <div class="dashboard-cards">
                        <div class="card stat-card animate-card">
                            <div class="card-body">
                                <div class="card-icon bg-primary">
                                    <i class="fas fa-store"></i>
                                </div>
                                <div class="card-stats">
                                    <div class="card-label">Total Restaurantes</div>
                                    <div class="card-value" id="total-restaurants"><?php echo count($allRestaurants); ?></div>
                                    <div class="stat-change positive">
                                        <i class="fas fa-arrow-up"></i>
                                        <span>12% este mes</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="#" onclick="showTab('restaurants')">Ver todos los restaurantes</a>
                            </div>
                        </div>

                        <div class="card stat-card animate-card">
                            <div class="card-body">
                                <div class="card-icon bg-success">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="card-stats">
                                    <div class="card-label">Usuarios Registrados</div>
                                    <div class="card-value" id="total-users"><?php echo $totalUsers ?? 0; ?></div>
                                    <div class="stat-change positive">
                                        <i class="fas fa-arrow-up"></i>
                                        <span>8% este mes</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="#" onclick="showTab('users')">Gestionar usuarios</a>
                            </div>
                        </div>

                        <div class="card stat-card animate-card">
                            <div class="card-body">
                                <div class="card-icon bg-warning">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="card-stats">
                                    <div class="card-label">Reservaciones Hoy</div>
                                    <div class="card-value" id="today-reservations"><?php echo count($todayReservations); ?></div>
                                    <div class="stat-change positive">
                                        <i class="fas fa-arrow-up"></i>
                                        <span>5% vs ayer</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="#" onclick="showTab('reservations')">Ver todas las reservaciones</a>
                            </div>
                        </div>

                        <div class="card stat-card animate-card">
                            <div class="card-body">
                                <div class="card-icon bg-info">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="card-stats">
                                    <div class="card-label">Ingresos del Mes</div>
                                    <div class="card-value">$45,230</div>
                                    <div class="stat-change positive">
                                        <i class="fas fa-arrow-up"></i>
                                        <span>15% vs mes anterior</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="#" onclick="showTab('analytics')">Ver analíticas</a>
                            </div>
                        </div>
                    </div>

                    <!-- Restaurantes más activos -->
                    <div class="section">
                        <div class="section-header">
                            <h3 class="section-title">Restaurantes Activos</h3>
                            <div class="section-actions">
                                <button class="btn btn-primary" onclick="openNewRestaurantModal()">
                                    <i class="fas fa-plus"></i> Nuevo Restaurante
                                </button>
                                <button class="btn btn-outline-secondary" onclick="refreshRestaurants()">
                                    <i class="fas fa-refresh"></i> Actualizar
                                </button>
                            </div>
                        </div>
                        
                        <div class="row" id="restaurants-overview">
                            <?php foreach($allRestaurants as $restaurant): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card restaurant-card">
                                    <div class="position-relative">
                                        <img src="<?php echo $restaurant['foto_portada']; ?>" class="card-img-top restaurant-image" alt="<?php echo htmlspecialchars($restaurant['nombre']); ?>">
                                        <span class="restaurant-status <?php echo $restaurant['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $restaurant['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($restaurant['nombre']); ?></h5>
                                        <p class="card-text text-muted">
                                            <i class="fas fa-utensils me-2"></i><?php echo htmlspecialchars($restaurant['tipo_cocina'] ?? 'Restaurante'); ?>
                                        </p>
                                        <p class="card-text">
                                            <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                            <?php echo htmlspecialchars($restaurant['direccion']); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-star text-warning me-1"></i>
                                                <span><?php echo $restaurant['calificacion'] ?? '4.0'; ?></span>
                                            </div>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-info" onclick="viewRestaurant('<?php echo $restaurant['id_restaurante']; ?>')" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" onclick="editRestaurant('<?php echo $restaurant['id_restaurante']; ?>')" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" onclick="toggleRestaurantStatus('<?php echo $restaurant['id_restaurante']; ?>', <?php echo $restaurant['activo'] ? 'false' : 'true'; ?>)" title="<?php echo $restaurant['activo'] ? 'Pausar' : 'Activar'; ?>">
                                                    <i class="fas fa-<?php echo $restaurant['activo'] ? 'pause' : 'play'; ?>"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteRestaurant('<?php echo $restaurant['id_restaurante']; ?>', '<?php echo addslashes($restaurant['nombre']); ?>')" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Contenido de otros tabs será cargado dinámicamente -->
                <div id="dynamic-content" style="display: none;"></div>
            </div>
        </div>
    </div>

    <!-- Modal para Nuevo Restaurante -->
    <div class="modal fade" id="newRestaurantModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-store me-2"></i>Nuevo Restaurante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="newRestaurantForm">
                    <div class="modal-body">
                        <div class="alert alert-info d-none" id="restaurantMessage"></div>
                        
                        <!-- Sección de Asignación de Propietario -->
                        <div class="card mb-4" style="border: 2px dashed #6c757d;">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-user-tie me-2"></i>Asignar Propietario del Restaurante
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label class="form-label">Seleccionar Usuario</label>
                                            <select class="form-select" name="id_usuario" id="selectUsuario" required>
                                                <option value="">-- Seleccionar usuario propietario --</option>
                                                <?php 
                                                // Obtener todos los usuarios para el select
                                                $allUsers = $userModel->getAllUsers();
                                                foreach($allUsers as $user): 
                                                    $rolText = $user['rol'] == 1 ? '(Admin)' : ($user['rol'] == 3 ? '(Super Admin)' : '(Usuario)');
                                                ?>
                                                <option value="<?php echo $user['id_usuario']; ?>" data-rol="<?php echo $user['rol']; ?>">
                                                    <?php echo htmlspecialchars($user['nombre']); ?> - <?php echo htmlspecialchars($user['correo']); ?> <?php echo $rolText; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Este usuario será el administrador del restaurante</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Cambiar Rol</label>
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" id="cambiarRolAdmin" name="cambiar_rol" value="1">
                                                <label class="form-check-label" for="cambiarRolAdmin">
                                                    Convertir a Admin
                                                </label>
                                            </div>
                                            <div class="form-text" id="rolActualText">Selecciona un usuario</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Datos del Restaurante -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre del Restaurante</label>
                                    <input type="text" class="form-control" name="nombre" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Cocina</label>
                                    <select class="form-select" name="tipo_cocina" required>
                                        <option value="">Seleccionar tipo</option>
                                        <option value="Mexicana">Mexicana</option>
                                        <option value="Italiana">Italiana</option>
                                        <option value="Japonesa">Japonesa</option>
                                        <option value="Argentina">Argentina</option>
                                        <option value="Internacional">Internacional</option>
                                        <option value="Postres & Café">Postres & Café</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" name="telefono" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Capacidad Total (personas)</label>
                                    <input type="number" class="form-control" name="capacidad_total" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Dirección</label>
                                    <textarea class="form-control" name="direccion" rows="2" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Horario de Apertura</label>
                                    <input type="time" class="form-control" name="horario_apertura" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Horario de Cierre</label>
                                    <input type="time" class="form-control" name="horario_cierre" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">URL de Imagen</label>
                                    <input type="text" class="form-control" name="foto_portada" placeholder="../src/images/1.jpg" value="../src/images/1.jpg">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Configuración de Mesas -->
                        <div class="card mt-3" style="border: 2px dashed #28a745;">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-chair me-2 text-success"></i>Configuración de Mesas
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Mesas de 2 personas</label>
                                            <input type="number" class="form-control mesa-input" name="mesas_2" min="0" value="0" onchange="calcularCapacidadMesas()">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Mesas de 4 personas</label>
                                            <input type="number" class="form-control mesa-input" name="mesas_4" min="0" value="0" onchange="calcularCapacidadMesas()">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Mesas de 6 personas</label>
                                            <input type="number" class="form-control mesa-input" name="mesas_6" min="0" value="0" onchange="calcularCapacidadMesas()">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Mesas de 8 personas</label>
                                            <input type="number" class="form-control mesa-input" name="mesas_8" min="0" value="0" onchange="calcularCapacidadMesas()">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Mesas de 10+ personas</label>
                                            <input type="number" class="form-control mesa-input" name="mesas_10" min="0" value="0" onchange="calcularCapacidadMesas()">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Total</label>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-success fs-6 me-2" id="totalMesas">0 mesas</span>
                                                <span class="badge bg-info fs-6" id="capacidadMesas">0 personas</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="saveRestaurantBtn">
                            <i class="fas fa-save me-2"></i>Crear Restaurante
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Ver Detalles del Restaurante -->
    <div class="modal fade" id="viewRestaurantModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>Detalles del Restaurante
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewRestaurantContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando información...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="editFromViewBtn">
                        <i class="fas fa-edit me-2"></i>Editar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Restaurante -->
    <div class="modal fade" id="editRestaurantModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Restaurante
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="editRestaurantForm">
                    <input type="hidden" name="restaurant_id" id="editRestaurantId">
                    <div class="modal-body">
                        <div class="alert alert-info d-none" id="editRestaurantMessage"></div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre del Restaurante</label>
                                    <input type="text" class="form-control" name="nombre" id="editNombre" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Cocina</label>
                                    <select class="form-select" name="tipo_cocina" id="editTipoCocina" required>
                                        <option value="">Seleccionar tipo</option>
                                        <option value="Mexicana">Mexicana</option>
                                        <option value="Italiana">Italiana</option>
                                        <option value="Japonesa">Japonesa</option>
                                        <option value="Argentina">Argentina</option>
                                        <option value="Internacional">Internacional</option>
                                        <option value="Postres & Café">Postres & Café</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" name="telefono" id="editTelefono" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Capacidad Total (personas)</label>
                                    <input type="number" class="form-control" name="capacidad_total" id="editCapacidad" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Dirección</label>
                                    <textarea class="form-control" name="direccion" id="editDireccion" rows="2" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Horario de Apertura</label>
                                    <input type="time" class="form-control" name="horario_apertura" id="editHorarioApertura" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Horario de Cierre</label>
                                    <input type="time" class="form-control" name="horario_cierre" id="editHorarioCierre" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">URL de Imagen</label>
                                    <input type="text" class="form-control" name="foto_portada" id="editFotoPortada">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="updateRestaurantBtn">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Confirmar Eliminación -->
    <div class="modal fade" id="deleteRestaurantModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-trash-alt fa-4x text-danger mb-3"></i>
                        <h5>¿Estás seguro de eliminar este restaurante?</h5>
                        <p class="text-muted" id="deleteRestaurantName">Nombre del restaurante</p>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Advertencia:</strong> Esta acción eliminará permanentemente:
                        <ul class="mb-0 mt-2">
                            <li>El restaurante y toda su información</li>
                            <li>Todas las mesas asociadas</li>
                            <li>Todas las reservaciones históricas</li>
                        </ul>
                    </div>
                    <input type="hidden" id="deleteRestaurantId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteRestaurantBtn">
                        <i class="fas fa-trash me-2"></i>Sí, Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Variables globales
        let currentTab = 'dashboard';
        
        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Super Admin Dashboard cargado');
            setupEventListeners();
        });

        // Configurar event listeners
        function setupEventListeners() {
            // Formulario de nuevo restaurante
            const newRestaurantForm = document.getElementById('newRestaurantForm');
            if (newRestaurantForm) {
                newRestaurantForm.addEventListener('submit', handleNewRestaurantSubmit);
            }
            
            // Select de usuario para mostrar rol actual
            const selectUsuario = document.getElementById('selectUsuario');
            if (selectUsuario) {
                selectUsuario.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const rolActual = selectedOption.getAttribute('data-rol');
                    const rolActualText = document.getElementById('rolActualText');
                    const cambiarRolCheckbox = document.getElementById('cambiarRolAdmin');
                    
                    if (this.value === '') {
                        rolActualText.textContent = 'Selecciona un usuario';
                        rolActualText.className = 'form-text';
                        cambiarRolCheckbox.checked = false;
                        cambiarRolCheckbox.disabled = true;
                    } else if (rolActual === '1') {
                        rolActualText.innerHTML = '<span class="badge bg-warning">Ya es Admin</span>';
                        cambiarRolCheckbox.checked = false;
                        cambiarRolCheckbox.disabled = true;
                    } else if (rolActual === '3') {
                        rolActualText.innerHTML = '<span class="badge bg-danger">Super Admin</span>';
                        cambiarRolCheckbox.checked = false;
                        cambiarRolCheckbox.disabled = true;
                    } else {
                        rolActualText.innerHTML = '<span class="badge bg-primary">Usuario Normal</span>';
                        cambiarRolCheckbox.disabled = false;
                        cambiarRolCheckbox.checked = true; // Por defecto marcado para nuevos admin
                    }
                });
            }
            
            // Formulario de editar restaurante
            const editRestaurantForm = document.getElementById('editRestaurantForm');
            if (editRestaurantForm) {
                editRestaurantForm.addEventListener('submit', handleEditRestaurantSubmit);
            }
            
            // Botón de confirmar eliminación
            const confirmDeleteBtn = document.getElementById('confirmDeleteRestaurantBtn');
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', confirmDeleteRestaurant);
            }
        }

        // Función para cambiar de tab
        function showTab(tabName) {
            console.log('Cambiando a tab:', tabName);
            
            // Actualizar navegación
            document.querySelectorAll('.sidebar-nav a').forEach(link => {
                link.classList.remove('active');
            });
            document.querySelector(`[onclick="showTab('${tabName}')"]`).classList.add('active');
            
            currentTab = tabName;
            
            // Mostrar contenido según el tab
            if (tabName === 'dashboard') {
                document.getElementById('dashboard-content').style.display = 'block';
                document.getElementById('dynamic-content').style.display = 'none';
            } else {
                document.getElementById('dashboard-content').style.display = 'none';
                loadTabContent(tabName);
            }
        }

        // Cargar contenido dinámico de tabs
        function loadTabContent(tabName) {
            const container = document.getElementById('dynamic-content');
            container.style.display = 'block';
            
            showNotification('Cargando contenido...', 'info');
            
            switch(tabName) {
                case 'restaurants':
                    loadRestaurantsView();
                    break;
                case 'users':
                    loadUsersView();
                    break;
                case 'reservations':
                    loadReservationsView();
                    break;
                case 'analytics':
                    loadAnalyticsView();
                    break;
                case 'settings':
                    loadSettingsView();
                    break;
                default:
                    container.innerHTML = '<div class="alert alert-warning">Contenido no disponible</div>';
            }
        }

        // Cargar vista de restaurantes
        function loadRestaurantsView() {
            const content = `
                <div class="section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-store me-2"></i>Gestión de Restaurantes
                        </h3>
                        <div class="section-actions">
                            <button class="btn btn-primary" onclick="openNewRestaurantModal()">
                                <i class="fas fa-plus"></i> Nuevo Restaurante
                            </button>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="table table-responsive">
                            <thead>
                                <tr>
                                    <th>Restaurante</th>
                                    <th>Tipo</th>
                                    <th>Ubicación</th>
                                    <th>Estado</th>
                                    <th>Reservaciones</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="restaurants-table-body">
                                <tr>
                                    <td colspan="6" class="text-center">Cargando restaurantes...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            document.getElementById('dynamic-content').innerHTML = content;
            loadRestaurantsData();
        }

        // Cargar vista de usuarios
        function loadUsersView() {
            const content = `
                <div class="section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-users me-2"></i>Gestión de Usuarios
                        </h3>
                        <div class="section-actions">
                            <button class="btn btn-primary" onclick="openNewUserModal()">
                                <i class="fas fa-plus"></i> Nuevo Usuario
                            </button>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="table table-responsive">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Fecha Registro</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body">
                                <tr>
                                    <td colspan="6" class="text-center">Cargando usuarios...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            document.getElementById('dynamic-content').innerHTML = content;
            loadUsersData();
        }

        // Cargar vista de reservaciones
        function loadReservationsView() {
            const content = `
                <div class="section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-calendar-check me-2"></i>Todas las Reservaciones
                        </h3>
                        <div class="section-actions">
                            <select class="form-select" style="width: auto;" onchange="filterReservations(this.value)">
                                <option value="all">Todas las reservaciones</option>
                                <option value="today">Hoy</option>
                                <option value="week">Esta semana</option>
                                <option value="month">Este mes</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="table table-responsive">
                            <thead>
                                <tr>
                                    <th>Reservación</th>
                                    <th>Cliente</th>
                                    <th>Restaurante</th>
                                    <th>Fecha/Hora</th>
                                    <th>Personas</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="reservations-table-body">
                                <tr>
                                    <td colspan="7" class="text-center">Cargando reservaciones...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            document.getElementById('dynamic-content').innerHTML = content;
            loadReservationsData();
        }

        // Cargar vista de analíticas
        function loadAnalyticsView() {
            const content = `
                <div class="row">
                    <div class="col-12">
                        <div class="section">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <i class="fas fa-chart-line me-2"></i>Analíticas del Sistema
                                </h3>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5>Reservaciones por Mes</h5>
                                            <canvas id="reservationsChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5>Restaurantes más Populares</h5>
                                            <canvas id="restaurantsChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('dynamic-content').innerHTML = content;
            // Aquí cargarías los gráficos con Chart.js
        }

        // Funciones para manejar restaurantes
        function openNewRestaurantModal() {
            const modal = new bootstrap.Modal(document.getElementById('newRestaurantModal'));
            modal.show();
            // Resetear el cálculo de mesas
            calcularCapacidadMesas();
        }

        // Calcular capacidad de mesas en tiempo real
        function calcularCapacidadMesas() {
            const mesas2 = parseInt(document.querySelector('input[name="mesas_2"]')?.value || 0);
            const mesas4 = parseInt(document.querySelector('input[name="mesas_4"]')?.value || 0);
            const mesas6 = parseInt(document.querySelector('input[name="mesas_6"]')?.value || 0);
            const mesas8 = parseInt(document.querySelector('input[name="mesas_8"]')?.value || 0);
            const mesas10 = parseInt(document.querySelector('input[name="mesas_10"]')?.value || 0);
            
            const totalMesas = mesas2 + mesas4 + mesas6 + mesas8 + mesas10;
            const capacidadTotal = (mesas2 * 2) + (mesas4 * 4) + (mesas6 * 6) + (mesas8 * 8) + (mesas10 * 10);
            
            document.getElementById('totalMesas').textContent = totalMesas + ' mesas';
            document.getElementById('capacidadMesas').textContent = capacidadTotal + ' personas';
            
            // Opcional: actualizar el campo de capacidad total
            const capacidadInput = document.querySelector('input[name="capacidad_total"]');
            if (capacidadInput && capacidadTotal > 0) {
                capacidadInput.value = capacidadTotal;
            }
        }

        function handleNewRestaurantSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const submitBtn = document.getElementById('saveRestaurantBtn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
            
            fetch('dashboardSuperAdmin_api.php?action=create_restaurant', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification(result.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('newRestaurantModal')).hide();
                    e.target.reset();
                    
                    // Actualizar vista si estamos en restaurantes
                    if (currentTab === 'restaurants') {
                        loadRestaurantsData();
                    } else {
                        // Actualizar el overview del dashboard
                        location.reload();
                    }
                } else {
                    showMessage(result.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error de conexión', 'danger');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }

        function viewRestaurant(id) {
            const modal = new bootstrap.Modal(document.getElementById('viewRestaurantModal'));
            const contentDiv = document.getElementById('viewRestaurantContent');
            
            // Mostrar loading
            contentDiv.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando información...</p>
                </div>
            `;
            modal.show();
            
            fetch(`dashboardSuperAdmin_api.php?action=get_restaurant_details&id=${id}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const r = result.data;
                        const propietario = r.propietario ? `${r.propietario.nombre} (${r.propietario.correo})` : 'No asignado';
                        const mesasDetalle = r.mesas_detalle || {};
                        
                        contentDiv.innerHTML = `
                            <div class="row">
                                <div class="col-md-4">
                                    <img src="${r.foto_portada || '../src/images/1.jpg'}" class="img-fluid rounded" alt="${r.nombre}">
                                    <div class="mt-3">
                                        <span class="badge ${r.activo ? 'bg-success' : 'bg-danger'} fs-6">
                                            ${r.activo ? 'Activo' : 'Inactivo'}
                                        </span>
                                        <span class="badge bg-warning text-dark fs-6 ms-2">
                                            <i class="fas fa-star"></i> ${r.calificacion || '4.0'}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <h4 class="mb-3">${r.nombre}</h4>
                                    
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <strong><i class="fas fa-utensils me-2 text-primary"></i>Tipo de Cocina:</strong>
                                            <p class="mb-0">${r.tipo_cocina || 'N/A'}</p>
                                        </div>
                                        <div class="col-6">
                                            <strong><i class="fas fa-phone me-2 text-success"></i>Teléfono:</strong>
                                            <p class="mb-0">${r.telefono}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong><i class="fas fa-map-marker-alt me-2 text-danger"></i>Dirección:</strong>
                                        <p class="mb-0">${r.direccion}</p>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <strong><i class="fas fa-clock me-2 text-info"></i>Horario:</strong>
                                            <p class="mb-0">${r.horario_apertura} - ${r.horario_cierre}</p>
                                        </div>
                                        <div class="col-6">
                                            <strong><i class="fas fa-users me-2 text-warning"></i>Capacidad:</strong>
                                            <p class="mb-0">${r.capacidad_total} personas</p>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong><i class="fas fa-user-tie me-2 text-secondary"></i>Propietario:</strong>
                                        <p class="mb-0">${propietario}</p>
                                    </div>
                                    
                                    <hr>
                                    
                                    <h5 class="mb-3"><i class="fas fa-chair me-2"></i>Configuración de Mesas</h5>
                                    <div class="row text-center">
                                        <div class="col">
                                            <div class="border rounded p-2">
                                                <div class="fs-4 fw-bold text-primary">${mesasDetalle.mesas_2 || 0}</div>
                                                <small>2 personas</small>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="border rounded p-2">
                                                <div class="fs-4 fw-bold text-success">${mesasDetalle.mesas_4 || 0}</div>
                                                <small>4 personas</small>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="border rounded p-2">
                                                <div class="fs-4 fw-bold text-info">${mesasDetalle.mesas_6 || 0}</div>
                                                <small>6 personas</small>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="border rounded p-2">
                                                <div class="fs-4 fw-bold text-warning">${mesasDetalle.mesas_8 || 0}</div>
                                                <small>8 personas</small>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="border rounded p-2">
                                                <div class="fs-4 fw-bold text-danger">${mesasDetalle.mesas_10 || 0}</div>
                                                <small>10+ pers.</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-6">
                                            <div class="alert alert-info mb-0 text-center">
                                                <strong>${r.total_mesas || 0}</strong> mesas totales
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="alert alert-success mb-0 text-center">
                                                <strong>${r.reservaciones_hoy || 0}</strong> reservaciones hoy
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Configurar botón de editar desde la vista
                        document.getElementById('editFromViewBtn').onclick = function() {
                            bootstrap.Modal.getInstance(document.getElementById('viewRestaurantModal')).hide();
                            setTimeout(() => editRestaurant(id), 300);
                        };
                    } else {
                        contentDiv.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    contentDiv.innerHTML = `<div class="alert alert-danger">Error al cargar los datos</div>`;
                });
        }

        function editRestaurant(id) {
            // Cargar datos del restaurante y mostrar modal de edición
            fetch(`dashboardSuperAdmin_api.php?action=get_restaurant_details&id=${id}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const r = result.data;
                        
                        // Llenar el formulario con los datos
                        document.getElementById('editRestaurantId').value = r.id_restaurante;
                        document.getElementById('editNombre').value = r.nombre;
                        document.getElementById('editTipoCocina').value = r.tipo_cocina || '';
                        document.getElementById('editTelefono').value = r.telefono;
                        document.getElementById('editCapacidad').value = r.capacidad_total;
                        document.getElementById('editDireccion').value = r.direccion;
                        document.getElementById('editHorarioApertura').value = r.horario_apertura;
                        document.getElementById('editHorarioCierre').value = r.horario_cierre;
                        document.getElementById('editFotoPortada').value = r.foto_portada || '';
                        
                        // Mostrar modal
                        const modal = new bootstrap.Modal(document.getElementById('editRestaurantModal'));
                        modal.show();
                    } else {
                        showNotification(result.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error al cargar datos del restaurante', 'error');
                });
        }

        function deleteRestaurant(id, nombre) {
            document.getElementById('deleteRestaurantId').value = id;
            document.getElementById('deleteRestaurantName').textContent = nombre;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteRestaurantModal'));
            modal.show();
        }

        function confirmDeleteRestaurant() {
            const id = document.getElementById('deleteRestaurantId').value;
            const btn = document.getElementById('confirmDeleteRestaurantBtn');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Eliminando...';
            
            fetch('dashboardSuperAdmin_api.php?action=delete_restaurant', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `restaurant_id=${id}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification(result.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('deleteRestaurantModal')).hide();
                    
                    // Recargar la página para actualizar
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error de conexión', 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        }

        // Manejar envío del formulario de edición
        function handleEditRestaurantSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const submitBtn = document.getElementById('updateRestaurantBtn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
            
            fetch('dashboardSuperAdmin_api.php?action=update_restaurant', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification(result.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('editRestaurantModal')).hide();
                    
                    // Recargar para ver cambios
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showEditMessage(result.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showEditMessage('Error de conexión', 'danger');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }

        function showEditMessage(message, type) {
            const messageDiv = document.getElementById('editRestaurantMessage');
            if (messageDiv) {
                messageDiv.className = `alert alert-${type}`;
                messageDiv.textContent = message;
                messageDiv.classList.remove('d-none');
                
                setTimeout(() => {
                    messageDiv.classList.add('d-none');
                }, 5000);
            }
        }

        function toggleRestaurantStatus(id, newStatus) {
            if (confirm('¿Estás seguro de cambiar el estado de este restaurante?')) {
                fetch('dashboardSuperAdmin_api.php?action=toggle_restaurant_status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `restaurant_id=${id}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showNotification(result.message, 'success');
                        location.reload(); // Recargar para ver cambios
                    } else {
                        showNotification(result.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error de conexión', 'error');
                });
            }
        }

        // Funciones de utilidad
        function loadRestaurantsData() {
            fetch('dashboardSuperAdmin_api.php?action=get_all_restaurants')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        updateRestaurantsTable(result.data);
                    } else {
                        showNotification(result.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error al cargar restaurantes', 'error');
                });
        }

        function loadUsersData() {
            fetch('dashboardSuperAdmin_api.php?action=get_all_users')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        updateUsersTable(result.data);
                    } else {
                        showNotification(result.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error al cargar usuarios', 'error');
                });
        }

        function loadReservationsData() {
            fetch('dashboardSuperAdmin_api.php?action=get_all_reservations')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        updateReservationsTable(result.data);
                    } else {
                        showNotification(result.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error al cargar reservaciones', 'error');
                });
        }

        function updateRestaurantsTable(restaurants) {
            const tbody = document.getElementById('restaurants-table-body');
            if (!tbody) return;

            if (restaurants.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay restaurantes registrados</td></tr>';
                return;
            }

            tbody.innerHTML = restaurants.map(restaurant => `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${restaurant.foto_portada}" class="me-3" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
                            <div>
                                <div class="fw-bold">${restaurant.nombre}</div>
                                <small class="text-muted">${restaurant.telefono}</small>
                            </div>
                        </div>
                    </td>
                    <td>${restaurant.tipo_cocina}</td>
                    <td>${restaurant.direccion}</td>
                    <td>
                        <span class="badge ${restaurant.activo ? 'bg-success' : 'bg-danger'}">
                            ${restaurant.activo ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-info">${restaurant.total_reservaciones || 0}</span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewRestaurant('${restaurant.id_restaurante}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="editRestaurant('${restaurant.id_restaurante}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="toggleRestaurantStatus('${restaurant.id_restaurante}', ${!restaurant.activo})">
                                <i class="fas fa-${restaurant.activo ? 'pause' : 'play'}"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function updateUsersTable(users) {
            const tbody = document.getElementById('users-table-body');
            if (!tbody) return;

            tbody.innerHTML = users.map(user => `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-3" style="width: 40px; height: 40px; background: linear-gradient(135deg, #007bff, #6f42c1); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                ${user.nombre.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div class="fw-bold">${user.nombre}</div>
                                <small class="text-muted">${user.telefono || 'N/A'}</small>
                            </div>
                        </div>
                    </td>
                    <td>${user.correo}</td>
                    <td>
                        <span class="badge ${user.rol === 1 ? 'bg-warning' : user.rol === 3 ? 'bg-danger' : 'bg-primary'}">
                            ${user.rol === 1 ? 'Admin' : user.rol === 3 ? 'Super Admin' : 'Usuario'}
                        </span>
                    </td>
                    <td>${new Date(user.fecha_registro).toLocaleDateString()}</td>
                    <td>
                        <span class="badge bg-success">Activo</span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewUser('${user.id_usuario}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="editUser('${user.id_usuario}')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function updateReservationsTable(reservations) {
            const tbody = document.getElementById('reservations-table-body');
            if (!tbody) return;

            tbody.innerHTML = reservations.map(reservation => {
                const statusClass = getStatusClass(reservation.estado);
                return `
                    <tr>
                        <td>#${reservation.id_reservacion.substr(0, 8)}</td>
                        <td>
                            <div>
                                <div class="fw-bold">${reservation.cliente_nombre}</div>
                                <small class="text-muted">${reservation.cliente_email}</small>
                            </div>
                        </td>
                        <td>${reservation.restaurante_nombre}</td>
                        <td>
                            ${new Date(reservation.fecha_reserva).toLocaleDateString()}<br>
                            <small>${reservation.hora_reserva}</small>
                        </td>
                        <td>
                            <span class="badge bg-info">${reservation.num_personas}</span>
                        </td>
                        <td>
                            <span class="status ${statusClass}">${reservation.estado}</span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewReservation('${reservation.id_reservacion}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success" onclick="editReservation('${reservation.id_reservacion}')">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function getStatusClass(status) {
            const classes = {
                'confirmada': 'status-confirmed',
                'check_in': 'status-checkin',
                'cancelada': 'status-cancelled',
                'completada': 'status-completed',
                'pendiente': 'status-pending'
            };
            return classes[status] || 'status-pending';
        }

        function toggleSidebar() {
            document.body.classList.toggle('sidebar-collapsed');
        }

        function refreshRestaurants() {
            showNotification('Actualizando datos...', 'info');
            location.reload();
        }

        function showMessage(message, type) {
            const messageDiv = document.getElementById('restaurantMessage');
            if (messageDiv) {
                messageDiv.className = `alert alert-${type}`;
                messageDiv.textContent = message;
                messageDiv.classList.remove('d-none');
                
                setTimeout(() => {
                    messageDiv.classList.add('d-none');
                }, 5000);
            }
        }

        function showNotification(message, type = 'info') {
            const colors = {
                'success': 'linear-gradient(to right, #00b09b, #96c93d)',
                'error': 'linear-gradient(to right, #ff5f6d, #ffc371)',
                'warning': 'linear-gradient(to right, #f7971e, #ffd200)',
                'info': 'linear-gradient(to right, #94b1b9ff, #feffffff)'
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

        console.log('🔥 Super Admin Dashboard inicializado correctamente');
    </script>
</body>
</html>
