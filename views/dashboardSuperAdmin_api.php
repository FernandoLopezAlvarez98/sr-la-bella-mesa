<?php
require_once '../controllers/AuthController.php';
require_once '../models/User.php';
require_once '../models/Restaurant.php';
require_once '../models/Reservation.php';
require_once '../models/Table.php';

// Headers para CORS y JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Verificar autenticación y rol
$authController = new AuthController();

if (!$authController->isAuthenticated() || $authController->getCurrentUserRole() != 3) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Acceso no autorizado. Se requiere rol de Super Administrador.'
    ]);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        // ==================== RESTAURANTES ====================
        case 'get_all_restaurants':
            handleGetAllRestaurants();
            break;
            
        case 'create_restaurant':
            handleCreateRestaurant();
            break;
            
        case 'update_restaurant':
            handleUpdateRestaurant();
            break;
            
        case 'toggle_restaurant_status':
            handleToggleRestaurantStatus();
            break;
            
        case 'delete_restaurant':
            handleDeleteRestaurant();
            break;
            
        case 'get_restaurant_details':
            handleGetRestaurantDetails();
            break;

        // ==================== USUARIOS ====================
        case 'get_all_users':
            handleGetAllUsers();
            break;
            
        case 'create_user':
            handleCreateUser();
            break;
            
        case 'update_user':
            handleUpdateUser();
            break;
            
        case 'delete_user':
            handleDeleteUser();
            break;
            
        case 'get_user_details':
            handleGetUserDetails();
            break;

        // ==================== RESERVACIONES ====================
        case 'get_all_reservations':
            handleGetAllReservations();
            break;
            
        case 'update_reservation_status':
            handleUpdateReservationStatus();
            break;
            
        case 'get_reservation_details':
            handleGetReservationDetails();
            break;
            
        case 'delete_reservation':
            handleDeleteReservation();
            break;

        // ==================== ESTADÍSTICAS ====================
        case 'get_system_stats':
            handleGetSystemStats();
            break;
            
        case 'get_analytics_data':
            handleGetAnalyticsData();
            break;

        // ==================== CONFIGURACIÓN ====================
        case 'update_system_settings':
            handleUpdateSystemSettings();
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    error_log("Error en SuperAdmin API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}

// ==================== FUNCIONES DE RESTAURANTES ====================

function handleGetAllRestaurants() {
    $restaurantModel = new Restaurant();
    
    try {
        $restaurants = $restaurantModel->getAllRestaurantsWithStats();
        
        echo json_encode([
            'success' => true,
            'data' => $restaurants,
            'total' => count($restaurants)
        ]);
    } catch (Exception $e) {
        throw new Exception('Error al obtener restaurantes: ' . $e->getMessage());
    }
}

function handleCreateRestaurant() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $required = ['nombre', 'tipo_cocina', 'direccion', 'telefono', 'horario_apertura', 'horario_cierre', 'capacidad_total', 'id_usuario'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo {$field} es requerido");
        }
    }
    
    $restaurantModel = new Restaurant();
    $userModel = new User();
    $tableModel = new Table();
    
    // Obtener el ID del usuario propietario
    $idUsuario = trim($_POST['id_usuario']);
    $cambiarRol = isset($_POST['cambiar_rol']) && $_POST['cambiar_rol'] == '1';
    
    // Verificar que el usuario existe
    $usuario = $userModel->getById($idUsuario);
    if (!$usuario) {
        throw new Exception('El usuario seleccionado no existe');
    }
    
    // Si se debe cambiar el rol a Admin (rol = 1)
    if ($cambiarRol && $usuario['rol'] == 2) {
        try {
            $userModel->changeRole($idUsuario, 1);
        } catch (Exception $e) {
            throw new Exception('Error al cambiar el rol del usuario: ' . $e->getMessage());
        }
    }
    
    // Crear el restaurante con el id_usuario asignado
    $restaurantData = [
        'nombre' => trim($_POST['nombre']),
        'tipo_cocina' => trim($_POST['tipo_cocina']),
        'direccion' => trim($_POST['direccion']),
        'telefono' => trim($_POST['telefono']),
        'horario_apertura' => $_POST['horario_apertura'],
        'horario_cierre' => $_POST['horario_cierre'],
        'capacidad_total' => (int)$_POST['capacidad_total'],
        'foto_portada' => !empty($_POST['foto_portada']) ? trim($_POST['foto_portada']) : '../src/images/1.jpg',
        'activo' => 1,
        'id_usuario' => $idUsuario
    ];
    
    try {
        $restaurantId = $restaurantModel->createWithOwner($restaurantData);
        
        // Obtener configuración de mesas del formulario
        $mesas2 = isset($_POST['mesas_2']) ? (int)$_POST['mesas_2'] : 0;
        $mesas4 = isset($_POST['mesas_4']) ? (int)$_POST['mesas_4'] : 0;
        $mesas6 = isset($_POST['mesas_6']) ? (int)$_POST['mesas_6'] : 0;
        $mesas8 = isset($_POST['mesas_8']) ? (int)$_POST['mesas_8'] : 0;
        $mesas10 = isset($_POST['mesas_10']) ? (int)$_POST['mesas_10'] : 0;
        
        $totalMesasPersonalizadas = $mesas2 + $mesas4 + $mesas6 + $mesas8 + $mesas10;
        $tablesCreated = 0;
        
        // Si se especificaron mesas personalizadas, usar esas
        if ($totalMesasPersonalizadas > 0) {
            // Crear mesas de 2 personas
            for ($i = 1; $i <= $mesas2; $i++) {
                $tableModel->create([
                    'id_restaurante' => $restaurantId,
                    'numero_mesa' => $tablesCreated + 1,
                    'capacidad' => 2,
                    'estado' => 'disponible'
                ]);
                $tablesCreated++;
            }
            
            // Crear mesas de 4 personas
            for ($i = 1; $i <= $mesas4; $i++) {
                $tableModel->create([
                    'id_restaurante' => $restaurantId,
                    'numero_mesa' => $tablesCreated + 1,
                    'capacidad' => 4,
                    'estado' => 'disponible'
                ]);
                $tablesCreated++;
            }
            
            // Crear mesas de 6 personas
            for ($i = 1; $i <= $mesas6; $i++) {
                $tableModel->create([
                    'id_restaurante' => $restaurantId,
                    'numero_mesa' => $tablesCreated + 1,
                    'capacidad' => 6,
                    'estado' => 'disponible'
                ]);
                $tablesCreated++;
            }
            
            // Crear mesas de 8 personas
            for ($i = 1; $i <= $mesas8; $i++) {
                $tableModel->create([
                    'id_restaurante' => $restaurantId,
                    'numero_mesa' => $tablesCreated + 1,
                    'capacidad' => 8,
                    'estado' => 'disponible'
                ]);
                $tablesCreated++;
            }
            
            // Crear mesas de 10 personas
            for ($i = 1; $i <= $mesas10; $i++) {
                $tableModel->create([
                    'id_restaurante' => $restaurantId,
                    'numero_mesa' => $tablesCreated + 1,
                    'capacidad' => 10,
                    'estado' => 'disponible'
                ]);
                $tablesCreated++;
            }
        } else {
            // Si no se especificaron mesas, crear por defecto basadas en la capacidad
            $capacity = $restaurantData['capacidad_total'];
            
            // Crear mesas de 2, 4 y 6 personas proporcionalmente
            $tables2 = ceil($capacity * 0.4 / 2); // 40% para mesas de 2
            $tables4 = ceil($capacity * 0.4 / 4); // 40% para mesas de 4
            $tables6 = ceil($capacity * 0.2 / 6); // 20% para mesas de 6
            
            // Crear mesas de 2 personas
            for ($i = 1; $i <= $tables2; $i++) {
                $tableModel->create([
                    'id_restaurante' => $restaurantId,
                    'numero_mesa' => $tablesCreated + 1,
                    'capacidad' => 2,
                    'estado' => 'disponible'
                ]);
                $tablesCreated++;
            }
            
            // Crear mesas de 4 personas
            for ($i = 1; $i <= $tables4; $i++) {
                $tableModel->create([
                    'id_restaurante' => $restaurantId,
                    'numero_mesa' => $tablesCreated + 1,
                    'capacidad' => 4,
                    'estado' => 'disponible'
                ]);
                $tablesCreated++;
            }
            
            // Crear mesas de 6 personas
            for ($i = 1; $i <= $tables6; $i++) {
                $tableModel->create([
                    'id_restaurante' => $restaurantId,
                    'numero_mesa' => $tablesCreated + 1,
                    'capacidad' => 6,
                    'estado' => 'disponible'
                ]);
                $tablesCreated++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Restaurante creado exitosamente con {$tablesCreated} mesas",
            'restaurant_id' => $restaurantId,
            'tables_created' => $tablesCreated
        ]);
    } catch (Exception $e) {
        throw new Exception('Error al crear restaurante: ' . $e->getMessage());
    }
}

function handleToggleRestaurantStatus() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $restaurantId = $_POST['restaurant_id'] ?? null;
    $newStatus = $_POST['status'] ?? null;
    
    if (!$restaurantId || $newStatus === null) {
        throw new Exception('ID del restaurante y estado son requeridos');
    }
    
    $restaurantModel = new Restaurant();
    
    try {
        $success = $restaurantModel->updateStatus($restaurantId, $newStatus === 'true' ? 1 : 0);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => $newStatus === 'true' ? 'Restaurante activado' : 'Restaurante desactivado'
            ]);
        } else {
            throw new Exception('No se pudo actualizar el estado del restaurante');
        }
    } catch (Exception $e) {
        throw new Exception('Error al actualizar estado: ' . $e->getMessage());
    }
}

function handleGetRestaurantDetails() {
    $restaurantId = $_GET['id'] ?? null;
    if (!$restaurantId) {
        throw new Exception('ID del restaurante requerido');
    }
    
    $restaurantModel = new Restaurant();
    $tableModel = new Table();
    $reservationModel = new Reservation();
    $userModel = new User();
    
    try {
        $restaurant = $restaurantModel->getRestaurantById($restaurantId);
        if (!$restaurant) {
            throw new Exception('Restaurante no encontrado');
        }
        
        // Obtener información adicional
        $tables = $tableModel->getAllTablesByRestaurant($restaurantId);
        $totalReservations = $reservationModel->getTotalReservationsByRestaurant($restaurantId);
        $todayReservations = $reservationModel->getTodayReservationsByRestaurant($restaurantId);
        
        // Obtener información del propietario
        $owner = null;
        if (!empty($restaurant['id_usuario'])) {
            $owner = $userModel->getById($restaurant['id_usuario']);
            if ($owner) {
                unset($owner['password_hash']);
            }
        }
        
        // Calcular estadísticas de mesas
        $mesasPor2 = 0;
        $mesasPor4 = 0;
        $mesasPor6 = 0;
        $mesasPor8 = 0;
        $mesasPor10 = 0;
        
        foreach ($tables as $table) {
            $capacidad = (int)$table['capacidad'];
            if ($capacidad == 2) $mesasPor2++;
            elseif ($capacidad == 4) $mesasPor4++;
            elseif ($capacidad == 6) $mesasPor6++;
            elseif ($capacidad == 8) $mesasPor8++;
            elseif ($capacidad >= 10) $mesasPor10++;
        }
        
        $restaurant['propietario'] = $owner;
        $restaurant['total_mesas'] = count($tables);
        $restaurant['mesas_detalle'] = [
            'mesas_2' => $mesasPor2,
            'mesas_4' => $mesasPor4,
            'mesas_6' => $mesasPor6,
            'mesas_8' => $mesasPor8,
            'mesas_10' => $mesasPor10
        ];
        $restaurant['total_reservaciones'] = $totalReservations;
        $restaurant['reservaciones_hoy'] = count($todayReservations);
        
        echo json_encode([
            'success' => true,
            'data' => $restaurant
        ]);
    } catch (Exception $e) {
        throw new Exception('Error al obtener detalles: ' . $e->getMessage());
    }
}

function handleUpdateRestaurant() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $restaurantId = $_POST['restaurant_id'] ?? null;
    if (!$restaurantId) {
        throw new Exception('ID del restaurante es requerido');
    }
    
    $restaurantModel = new Restaurant();
    
    // Verificar que el restaurante existe
    $existing = $restaurantModel->getRestaurantById($restaurantId);
    if (!$existing) {
        throw new Exception('Restaurante no encontrado');
    }
    
    // Preparar datos para actualizar
    $updateData = [
        'nombre' => trim($_POST['nombre'] ?? $existing['nombre']),
        'tipo_cocina' => trim($_POST['tipo_cocina'] ?? $existing['tipo_cocina']),
        'direccion' => trim($_POST['direccion'] ?? $existing['direccion']),
        'telefono' => trim($_POST['telefono'] ?? $existing['telefono']),
        'horario_apertura' => $_POST['horario_apertura'] ?? $existing['horario_apertura'],
        'horario_cierre' => $_POST['horario_cierre'] ?? $existing['horario_cierre'],
        'capacidad_total' => (int)($_POST['capacidad_total'] ?? $existing['capacidad_total']),
        'foto_portada' => !empty($_POST['foto_portada']) ? trim($_POST['foto_portada']) : $existing['foto_portada'],
        'calificacion' => $existing['calificacion'] ?? 4.0,
        'tiempo_espera' => $existing['tiempo_espera'] ?? '25-35',
        'precio_rango' => $existing['precio_rango'] ?? '$$',
        'promocion' => $existing['promocion'] ?? null
    ];
    
    try {
        $result = $restaurantModel->updateRestaurant($restaurantId, $updateData);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Restaurante actualizado exitosamente'
            ]);
        } else {
            throw new Exception($result['message']);
        }
    } catch (Exception $e) {
        throw new Exception('Error al actualizar restaurante: ' . $e->getMessage());
    }
}

function handleDeleteRestaurant() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $restaurantId = $_POST['restaurant_id'] ?? null;
    if (!$restaurantId) {
        throw new Exception('ID del restaurante es requerido');
    }
    
    $restaurantModel = new Restaurant();
    
    // Verificar que el restaurante existe
    $existing = $restaurantModel->getRestaurantById($restaurantId);
    if (!$existing) {
        throw new Exception('Restaurante no encontrado');
    }
    
    try {
        $success = $restaurantModel->delete($restaurantId);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Restaurante eliminado exitosamente. Se eliminaron también las mesas y reservaciones asociadas.'
            ]);
        } else {
            throw new Exception('No se pudo eliminar el restaurante');
        }
    } catch (Exception $e) {
        throw new Exception('Error al eliminar restaurante: ' . $e->getMessage());
    }
}

// ==================== FUNCIONES DE USUARIOS ====================

function handleGetAllUsers() {
    $userModel = new User();
    
    try {
        $users = $userModel->getAllUsers();
        
        echo json_encode([
            'success' => true,
            'data' => $users,
            'total' => count($users)
        ]);
    } catch (Exception $e) {
        throw new Exception('Error al obtener usuarios: ' . $e->getMessage());
    }
}

function handleCreateUser() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $required = ['nombre', 'correo', 'password', 'rol'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo {$field} es requerido");
        }
    }
    
    $userModel = new User();
    
    // Verificar si el email ya existe
    if ($userModel->emailExists($_POST['correo'])) {
        throw new Exception('El correo electrónico ya está registrado');
    }
    
    $userData = [
        'nombre' => trim($_POST['nombre']),
        'correo' => trim($_POST['correo']),
        'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
        'telefono' => $_POST['telefono'] ?? null,
        'rol' => (int)$_POST['rol']
    ];
    
    try {
        $userId = $userModel->create($userData);
        
        echo json_encode([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'user_id' => $userId
        ]);
    } catch (Exception $e) {
        throw new Exception('Error al crear usuario: ' . $e->getMessage());
    }
}

function handleGetUserDetails() {
    $userId = $_GET['id'] ?? null;
    if (!$userId) {
        throw new Exception('ID del usuario requerido');
    }
    
    $userModel = new User();
    
    try {
        $user = $userModel->getById($userId);
        if (!$user) {
            throw new Exception('Usuario no encontrado');
        }
        
        // No enviar contraseña
        unset($user['password']);
        
        echo json_encode([
            'success' => true,
            'data' => $user
        ]);
    } catch (Exception $e) {
        throw new Exception('Error al obtener detalles: ' . $e->getMessage());
    }
}

// ==================== FUNCIONES DE RESERVACIONES ====================

function handleGetAllReservations() {
    $reservationModel = new Reservation();
    
    try {
        $reservations = $reservationModel->getAllReservationsWithDetails();
        
        echo json_encode([
            'success' => true,
            'data' => $reservations,
            'total' => count($reservations)
        ]);
    } catch (Exception $e) {
        throw new Exception('Error al obtener reservaciones: ' . $e->getMessage());
    }
}

function handleUpdateReservationStatus() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $reservationId = $_POST['reservation_id'] ?? null;
    $newStatus = $_POST['status'] ?? null;
    
    if (!$reservationId || !$newStatus) {
        throw new Exception('ID de reservación y estado son requeridos');
    }
    
    $reservationModel = new Reservation();
    
    try {
        $success = $reservationModel->updateStatus($reservationId, $newStatus);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Estado de reservación actualizado'
            ]);
        } else {
            throw new Exception('No se pudo actualizar la reservación');
        }
    } catch (Exception $e) {
        throw new Exception('Error al actualizar reservación: ' . $e->getMessage());
    }
}

// ==================== FUNCIONES DE ESTADÍSTICAS ====================

function handleGetSystemStats() {
    $userModel = new User();
    $restaurantModel = new Restaurant();
    $reservationModel = new Reservation();
    
    try {
        $stats = [
            'total_users' => $userModel->getTotalUsers(),
            'total_restaurants' => $restaurantModel->getTotalRestaurants(),
            'total_reservations' => $reservationModel->getTotalReservations(),
            'today_reservations' => count($reservationModel->getTodayReservations()),
            'active_restaurants' => $restaurantModel->getActiveRestaurantsCount(),
            'recent_users' => $userModel->getRecentUsersCount(7), // últimos 7 días
            'monthly_reservations' => $reservationModel->getMonthlyReservationsCount(),
            'revenue_estimate' => $reservationModel->getEstimatedRevenue()
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
    } catch (Exception $e) {
        throw new Exception('Error al obtener estadísticas: ' . $e->getMessage());
    }
}

function handleGetAnalyticsData() {
    $reservationModel = new Reservation();
    $restaurantModel = new Restaurant();
    
    try {
        $analytics = [
            'reservations_by_month' => $reservationModel->getReservationsByMonth(),
            'popular_restaurants' => $restaurantModel->getPopularRestaurants(),
            'reservations_by_status' => $reservationModel->getReservationsByStatus(),
            'peak_hours' => $reservationModel->getPeakHours(),
            'user_growth' => $reservationModel->getUserGrowthData()
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $analytics
        ]);
    } catch (Exception $e) {
        throw new Exception('Error al obtener datos de analíticas: ' . $e->getMessage());
    }
}

// Función auxiliar para validar campos requeridos
function validateRequiredFields($data, $requiredFields) {
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("El campo {$field} es requerido");
        }
    }
    return true;
}

// Función auxiliar para sanitizar datos de entrada
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
?>