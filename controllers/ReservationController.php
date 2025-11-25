<?php
session_start();
require_once '../models/Connection.php';
require_once '../models/Reservation.php';
require_once '../models/Table.php';
require_once '../controllers/AuthController.php';

header('Content-Type: application/json');

$authController = new AuthController();

// Verificar autenticación
if (!$authController->isAuthenticated()) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Por favor inicia sesión.'
    ]);
    exit;
}

$reservationModel = new Reservation();
$tableModel = new Table();

// Obtener la acción del request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        createReservation();
        break;
    case 'getAvailableTables':
        getAvailableTables();
        break;
    case 'checkAvailability':
        checkAvailability();
        break;
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida.'
        ]);
}

/**
 * Crear una nueva reservación
 */
function createReservation() {
    global $reservationModel, $tableModel;
    
    // Obtener datos del formulario
    $restaurantId = $_POST['restaurant_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $people = intval($_POST['people'] ?? 0);
    $userId = $_SESSION['user_id'] ?? '';
    
    // Validaciones básicas
    if (empty($restaurantId) || empty($date) || empty($time) || $people <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Por favor completa todos los campos.'
        ]);
        return;
    }
    
    // Validar que la fecha no sea pasada
    if ($date < date('Y-m-d')) {
        echo json_encode([
            'success' => false,
            'message' => 'No puedes hacer reservaciones en fechas pasadas.'
        ]);
        return;
    }
    
    // Si es hoy, validar que la hora no sea pasada
    if ($date == date('Y-m-d') && $time <= date('H:i')) {
        echo json_encode([
            'success' => false,
            'message' => 'No puedes hacer reservaciones en horas pasadas.'
        ]);
        return;
    }
    
    // Buscar una mesa disponible en el restaurante con la capacidad necesaria
    $availableTable = findAvailableTable($restaurantId, $date, $time, $people);
    
    if (!$availableTable) {
        echo json_encode([
            'success' => false,
            'message' => 'Lo sentimos, no hay mesas disponibles para esa fecha y hora. Por favor intenta con otro horario.'
        ]);
        return;
    }
    
    // Crear la reservación
    $reservationData = [
        'id_usuario' => $userId,
        'id_restaurante' => $restaurantId,
        'id_mesa' => $availableTable['id_mesa'],
        'fecha_reserva' => $date,
        'hora_reserva' => $time,
        'num_personas' => $people
    ];
    
    $reservationId = $reservationModel->createReservation($reservationData);
    
    if ($reservationId) {
        echo json_encode([
            'success' => true,
            'message' => '¡Reservación realizada exitosamente!',
            'data' => [
                'reservation_id' => $reservationId,
                'table_number' => $availableTable['numero'],
                'date' => $date,
                'time' => $time,
                'people' => $people
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al crear la reservación. Por favor intenta nuevamente.'
        ]);
    }
}

/**
 * Buscar una mesa disponible en un restaurante
 */
function findAvailableTable($restaurantId, $date, $time, $capacity) {
    global $reservationModel;
    
    $connection = Connection::getInstance();
    
    // Buscar mesas del restaurante con capacidad suficiente que estén disponibles
    $sql = "SELECT m.id_mesa, m.numero, m.capacidad
            FROM mesa m
            WHERE m.id_restaurante = ?
            AND m.capacidad >= ?
            AND NOT EXISTS (
                SELECT 1 FROM reservacion r 
                WHERE r.id_mesa = m.id_mesa 
                AND DATE(r.fecha_reserva) = ?
                AND r.estado IN ('confirmada', 'pendiente', 'check_in')
                AND (
                    -- La reservación existente coincide con la hora solicitada (ventana de 2 horas)
                    (TIME(r.hora_reserva) <= ? AND ADDTIME(TIME(r.hora_reserva), '02:00:00') > ?)
                    OR
                    -- La hora solicitada cae dentro de una reservación existente
                    (TIME(r.hora_reserva) >= ? AND TIME(r.hora_reserva) < ADDTIME(?, '02:00:00'))
                )
            )
            ORDER BY m.capacidad ASC
            LIMIT 1";
    
    return $connection->fetchOne($sql, [
        $restaurantId,
        $capacity,
        $date,
        $time, $time,
        $time, $time
    ]);
}

/**
 * Obtener mesas disponibles para un restaurante en fecha/hora específica
 */
function getAvailableTables() {
    global $tableModel;
    
    $restaurantId = $_GET['restaurant_id'] ?? '';
    $date = $_GET['date'] ?? '';
    $time = $_GET['time'] ?? '';
    $people = intval($_GET['people'] ?? 1);
    
    if (empty($restaurantId) || empty($date) || empty($time)) {
        echo json_encode([
            'success' => false,
            'message' => 'Parámetros incompletos.'
        ]);
        return;
    }
    
    $connection = Connection::getInstance();
    
    $sql = "SELECT m.id_mesa, m.numero, m.capacidad
            FROM mesa m
            WHERE m.id_restaurante = ?
            AND m.capacidad >= ?
            AND NOT EXISTS (
                SELECT 1 FROM reservacion r 
                WHERE r.id_mesa = m.id_mesa 
                AND DATE(r.fecha_reserva) = ?
                AND r.estado IN ('confirmada', 'pendiente', 'check_in')
                AND (
                    (TIME(r.hora_reserva) <= ? AND ADDTIME(TIME(r.hora_reserva), '02:00:00') > ?)
                    OR
                    (TIME(r.hora_reserva) >= ? AND TIME(r.hora_reserva) < ADDTIME(?, '02:00:00'))
                )
            )
            ORDER BY m.capacidad ASC";
    
    $tables = $connection->fetchAll($sql, [
        $restaurantId,
        $people,
        $date,
        $time, $time,
        $time, $time
    ]);
    
    echo json_encode([
        'success' => true,
        'tables' => $tables
    ]);
}

/**
 * Verificar disponibilidad
 */
function checkAvailability() {
    $restaurantId = $_GET['restaurant_id'] ?? '';
    $date = $_GET['date'] ?? '';
    $time = $_GET['time'] ?? '';
    $people = intval($_GET['people'] ?? 1);
    
    if (empty($restaurantId) || empty($date) || empty($time)) {
        echo json_encode([
            'success' => false,
            'available' => false,
            'message' => 'Parámetros incompletos.'
        ]);
        return;
    }
    
    $availableTable = findAvailableTable($restaurantId, $date, $time, $people);
    
    echo json_encode([
        'success' => true,
        'available' => $availableTable !== null,
        'message' => $availableTable ? 'Hay disponibilidad' : 'No hay mesas disponibles para esa fecha y hora'
    ]);
}
?>
