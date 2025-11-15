<?php
require_once '../controllers/AuthController.php';
require_once '../models/Reservation.php';
require_once '../models/Table.php';
require_once '../models/User.php';

// Verificar autenticación
$authController = new AuthController();
if (!$authController->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    $reservationModel = new Reservation();
    $tableModel = new Table();
    $userModel = new User();

    switch ($action) {
        case 'today_reservations':
            $reservations = $reservationModel->getTodayReservations();
            echo json_encode(['success' => true, 'data' => $reservations]);
            break;

        case 'today_stats':
            $todayStats = $reservationModel->getTodayStats();
            $yesterdayStats = $reservationModel->getYesterdayStats();
            $occupancyStats = $tableModel->getOccupancyStats();
            
            // Calcular cambios porcentuales
            $reservationsChange = 0;
            $customersChange = 0;
            $noshowsChange = 0;
            
            if ($yesterdayStats['total_reservaciones'] > 0) {
                $reservationsChange = round((($todayStats['total_reservaciones'] - $yesterdayStats['total_reservaciones']) / $yesterdayStats['total_reservaciones']) * 100, 1);
            }
            
            if ($yesterdayStats['total_comensales'] > 0) {
                $customersChange = round((($todayStats['total_comensales'] - $yesterdayStats['total_comensales']) / $yesterdayStats['total_comensales']) * 100, 1);
            }
            
            if ($yesterdayStats['total_noshows'] > 0) {
                $noshowsChange = round((($todayStats['total_noshows'] - $yesterdayStats['total_noshows']) / $yesterdayStats['total_noshows']) * 100, 1);
            }

            $stats = [
                'reservations_today' => $todayStats['total_reservaciones'] ?? 0,
                'customers_today' => $todayStats['total_comensales'] ?? 0,
                'no_shows' => $todayStats['total_noshows'] ?? 0,
                'occupancy_rate' => $occupancyStats['porcentaje_ocupacion'] ?? 0,
                'reservations_change' => $reservationsChange,
                'customers_change' => $customersChange,
                'no_shows_change' => $noshowsChange,
                'occupancy_change' => 0 // Podrías implementar esto comparando con ayer
            ];
            
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'tables_status':
            $tables = $tableModel->getTablesWithStatus();
            echo json_encode(['success' => true, 'data' => $tables]);
            break;

        case 'time_slots':
            $date = $_GET['date'] ?? date('Y-m-d');
            $timeSlots = $reservationModel->getTimeSlotAvailability($date);
            $occupancyStats = $tableModel->getOccupancyStats($date);
            $totalTables = $occupancyStats['total_mesas'] ?? 15; // Valor por defecto
            
            // Generar franjas horarias de 12:00 a 22:00
            $slots = [];
            for ($hour = 12; $hour <= 22; $hour++) {
                $timeStr = sprintf('%02d:00', $hour);
                $reserved = 0;
                
                foreach ($timeSlots as $slot) {
                    if ($slot['franja_horaria'] === $timeStr) {
                        $reserved = $slot['personas_reservadas'];
                        break;
                    }
                }
                
                $available = $totalTables - $reserved;
                $status = 'available';
                
                if ($available <= 0) {
                    $status = 'full';
                } elseif ($available <= 5) {
                    $status = 'limited';
                }
                
                $slots[] = [
                    'time' => $timeStr,
                    'available' => $available,
                    'total' => $totalTables,
                    'status' => $status
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $slots]);
            break;

        case 'update_reservation_status':
            $reservationId = $_POST['reservation_id'] ?? '';
            $newStatus = $_POST['status'] ?? '';
            
            if (empty($reservationId) || empty($newStatus)) {
                echo json_encode(['success' => false, 'message' => 'Parámetros faltantes']);
                break;
            }
            
            $updated = $reservationModel->updateReservationStatus($reservationId, $newStatus);
            
            if ($updated > 0) {
                echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado']);
            }
            break;

        case 'get_clients':
            $clients = $userModel->getAllClients();
            echo json_encode(['success' => true, 'data' => $clients]);
            break;

        case 'get_available_tables':
            $date = $_GET['date'] ?? date('Y-m-d');
            $time = $_GET['time'] ?? '';
            $capacity = $_GET['capacity'] ?? 1;
            
            if (empty($time)) {
                echo json_encode(['success' => false, 'message' => 'Hora requerida']);
                break;
            }
            
            $tables = $tableModel->getAvailableTables($date, $time, $capacity);
            echo json_encode(['success' => true, 'data' => $tables]);
            break;

        case 'create_reservation':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                break;
            }
            
            $data = [
                'id_usuario' => $_POST['client_id'] ?? '',
                'id_restaurante' => $_POST['restaurant_id'] ?? '550e8400-e29b-41d4-a716-446655440000', // ID por defecto del restaurante
                'id_mesa' => $_POST['table_id'] ?? '',
                'fecha_reserva' => $_POST['date'] ?? '',
                'hora_reserva' => $_POST['time'] ?? '',
                'num_personas' => $_POST['people'] ?? 1
            ];
            
            // Validar datos requeridos
            $required = ['id_usuario', 'id_mesa', 'fecha_reserva', 'hora_reserva', 'num_personas'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    echo json_encode(['success' => false, 'message' => "Campo requerido: $field"]);
                    break 2;
                }
            }
            
            // Verificar disponibilidad
            $available = $reservationModel->checkTableAvailability(
                $data['id_mesa'], 
                $data['fecha_reserva'], 
                $data['hora_reserva']
            );
            
            if (!$available) {
                echo json_encode(['success' => false, 'message' => 'La mesa no está disponible en esa fecha y hora']);
                break;
            }
            
            // Crear reservación
            $reservationId = $reservationModel->createReservation($data);
            
            if ($reservationId) {
                echo json_encode(['success' => true, 'message' => 'Reservación creada exitosamente', 'id' => $reservationId]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear la reservación']);
            }
            break;

        case 'get_clients_with_stats':
            $clients = $userModel->getClientsWithStats();
            echo json_encode(['success' => true, 'data' => $clients]);
            break;

        case 'get_client_stats':
            $stats = $userModel->getClientStats();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'create_client':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                break;
            }
            
            $data = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'correo' => trim($_POST['correo'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? '')
            ];
            
            // Validar datos requeridos
            if (empty($data['nombre']) || empty($data['correo']) || empty($data['telefono'])) {
                echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
                break;
            }
            
            // Validar formato de correo
            if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Formato de correo inválido']);
                break;
            }
            
            // Validar teléfono (10 dígitos)
            if (!preg_match('/^[0-9]{10}$/', $data['telefono'])) {
                echo json_encode(['success' => false, 'message' => 'El teléfono debe tener exactamente 10 dígitos']);
                break;
            }
            
            // Crear cliente
            $result = $userModel->createClient($data);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }

} catch (Exception $e) {
    error_log("Error en dashboard_api.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>