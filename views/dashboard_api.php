<?php
require_once '../controllers/AuthController.php';
require_once '../models/Reservation.php';
require_once '../models/Table.php';
require_once '../models/User.php';
require_once '../models/Restaurant.php';

// Verificar autenticación
$authController = new AuthController();
if (!$authController->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtener el restaurante del administrador actual
$userId = $authController->getCurrentUserId();
$restaurantModel = new Restaurant();
$adminRestaurant = $restaurantModel->getRestaurantByUserId($userId);
$adminRestaurantId = $adminRestaurant ? $adminRestaurant['id_restaurante'] : null;

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    $reservationModel = new Reservation();
    $tableModel = new Table();
    $userModel = new User();

    switch ($action) {
        case 'today_reservations':
            if ($adminRestaurantId) {
                $reservations = $reservationModel->getTodayReservationsByRestaurant($adminRestaurantId);
            } else {
                $reservations = [];
            }
            echo json_encode(['success' => true, 'data' => $reservations]);
            break;

        case 'today_stats':
            if ($adminRestaurantId) {
                $todayStats = $reservationModel->getTodayStatsByRestaurant($adminRestaurantId);
                $yesterdayStats = $reservationModel->getYesterdayStatsByRestaurant($adminRestaurantId);
                $occupancyStats = $tableModel->getOccupancyStatsByRestaurant($adminRestaurantId);
                $yesterdayOccupancy = $tableModel->getOccupancyStatsByRestaurant($adminRestaurantId, date('Y-m-d', strtotime('-1 day')));
            } else {
                $todayStats = ['total_reservaciones' => 0, 'total_comensales' => 0, 'total_noshows' => 0];
                $yesterdayStats = ['total_reservaciones' => 0, 'total_comensales' => 0, 'total_noshows' => 0];
                $occupancyStats = ['porcentaje_ocupacion' => 0, 'total_mesas' => 0, 'mesas_reservadas' => 0];
                $yesterdayOccupancy = ['porcentaje_ocupacion' => 0];
            }
            
            // Asegurar valores por defecto
            $todayReservations = intval($todayStats['total_reservaciones'] ?? 0);
            $todayCustomers = intval($todayStats['total_comensales'] ?? 0);
            $todayNoshows = intval($todayStats['total_noshows'] ?? 0);
            $todayOccupancy = floatval($occupancyStats['porcentaje_ocupacion'] ?? 0);
            
            $yesterdayReservations = intval($yesterdayStats['total_reservaciones'] ?? 0);
            $yesterdayCustomers = intval($yesterdayStats['total_comensales'] ?? 0);
            $yesterdayNoshows = intval($yesterdayStats['total_noshows'] ?? 0);
            $yesterdayOccupancyRate = floatval($yesterdayOccupancy['porcentaje_ocupacion'] ?? 0);
            
            // Calcular cambios porcentuales
            $reservationsChange = 0;
            $customersChange = 0;
            $noshowsChange = 0;
            $occupancyChange = 0;
            
            if ($yesterdayReservations > 0) {
                $reservationsChange = round((($todayReservations - $yesterdayReservations) / $yesterdayReservations) * 100, 1);
            } elseif ($todayReservations > 0) {
                $reservationsChange = 100; // Si ayer fue 0 y hoy hay reservas, es 100% de aumento
            }
            
            if ($yesterdayCustomers > 0) {
                $customersChange = round((($todayCustomers - $yesterdayCustomers) / $yesterdayCustomers) * 100, 1);
            } elseif ($todayCustomers > 0) {
                $customersChange = 100;
            }
            
            if ($yesterdayNoshows > 0) {
                $noshowsChange = round((($todayNoshows - $yesterdayNoshows) / $yesterdayNoshows) * 100, 1);
            } elseif ($todayNoshows > 0) {
                $noshowsChange = 100;
            }
            
            if ($yesterdayOccupancyRate > 0) {
                $occupancyChange = round($todayOccupancy - $yesterdayOccupancyRate, 1);
            } elseif ($todayOccupancy > 0) {
                $occupancyChange = $todayOccupancy;
            }

            $stats = [
                'reservations_today' => $todayReservations,
                'customers_today' => $todayCustomers,
                'no_shows' => $todayNoshows,
                'occupancy_rate' => $todayOccupancy,
                'reservations_change' => $reservationsChange,
                'customers_change' => $customersChange,
                'no_shows_change' => $noshowsChange,
                'occupancy_change' => $occupancyChange,
                // Datos adicionales para debug
                'total_tables' => intval($occupancyStats['total_mesas'] ?? 0),
                'reserved_tables' => intval($occupancyStats['mesas_reservadas'] ?? 0)
            ];
            
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'tables_status':
            if ($adminRestaurantId) {
                $tables = $tableModel->getTablesWithStatusByRestaurant($adminRestaurantId);
            } else {
                $tables = [];
            }
            echo json_encode(['success' => true, 'data' => $tables]);
            break;

        case 'time_slots':
            $date = $_GET['date'] ?? date('Y-m-d');
            if ($adminRestaurantId) {
                $timeSlots = $reservationModel->getTimeSlotAvailabilityByRestaurant($adminRestaurantId, $date);
                $occupancyStats = $tableModel->getOccupancyStatsByRestaurant($adminRestaurantId, $date);
            } else {
                $timeSlots = [];
                $occupancyStats = ['total_mesas' => 0];
            }
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
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Método no permitido. Use POST.']);
                break;
            }
            
            error_log("=== UPDATE RESERVATION STATUS ===");
            error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
            error_log("POST data: " . print_r($_POST, true));
            
            $reservationId = $_POST['reservation_id'] ?? '';
            $newStatus = $_POST['status'] ?? '';
            
            error_log("Reservation ID: '" . $reservationId . "'");
            error_log("New Status: '" . $newStatus . "'");
            
            if (empty($reservationId)) {
                error_log("ERROR: reservation_id está vacío");
                echo json_encode(['success' => false, 'message' => 'ID de reserva requerido']);
                break;
            }
            
            if (empty($newStatus)) {
                error_log("ERROR: status está vacío");
                echo json_encode(['success' => false, 'message' => 'Estado requerido']);
                break;
            }
            
            // Validar que el estado sea válido
            $validStatuses = ['pendiente', 'confirmada', 'check_in', 'completada', 'cancelada', 'noshow'];
            if (!in_array($newStatus, $validStatuses)) {
                error_log("ERROR: Estado no válido: " . $newStatus);
                echo json_encode(['success' => false, 'message' => 'Estado no válido: ' . $newStatus . '. Estados válidos: ' . implode(', ', $validStatuses)]);
                break;
            }
            
            try {
                // Verificar que la reserva existe antes de actualizar
                $existingReservation = $reservationModel->getReservationById($reservationId);
                if (!$existingReservation) {
                    error_log("ERROR: Reserva no encontrada con ID: " . $reservationId);
                    echo json_encode(['success' => false, 'message' => 'Reservación no encontrada']);
                    break;
                }
                
                error_log("Reserva encontrada. Estado actual: " . $existingReservation['estado']);
                
                // Verificar si ya tiene el mismo estado
                if ($existingReservation['estado'] === $newStatus) {
                    error_log("La reserva ya tiene el estado: " . $newStatus);
                    echo json_encode(['success' => true, 'message' => 'La reserva ya tiene el estado: ' . $newStatus]);
                    break;
                }
                
                $updated = $reservationModel->updateReservationStatus($reservationId, $newStatus);
                error_log("Resultado de updateReservationStatus: " . var_export($updated, true));
                
                // rowCount puede ser 0 si el valor no cambió, pero la operación fue exitosa
                // Verificar el estado actual para confirmar
                $verifyReservation = $reservationModel->getReservationById($reservationId);
                error_log("Estado después de UPDATE: " . ($verifyReservation['estado'] ?? 'NO ENCONTRADO'));
                
                if ($verifyReservation && $verifyReservation['estado'] === $newStatus) {
                    error_log("SUCCESS: Estado actualizado/confirmado correctamente");
                    $statusMessages = [
                        'completada' => 'Reserva marcada como completada',
                        'cancelada' => 'Reserva cancelada exitosamente',
                        'check_in' => 'Check-in realizado',
                        'confirmada' => 'Reserva confirmada',
                        'noshow' => 'Marcada como No Show'
                    ];
                    $message = $statusMessages[$newStatus] ?? 'Estado actualizado correctamente';
                    echo json_encode(['success' => true, 'message' => $message]);
                } else {
                    error_log("ERROR: El estado no se actualizó correctamente");
                    error_log("Estado esperado: " . $newStatus);
                    error_log("Estado actual: " . ($verifyReservation['estado'] ?? 'DESCONOCIDO'));
                    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado. Estado actual: ' . ($verifyReservation['estado'] ?? 'desconocido')]);
                }
            } catch (Exception $e) {
                error_log("EXCEPTION al actualizar estado: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
            }
            break;

        case 'get_clients':
            if ($adminRestaurantId) {
                $clients = $reservationModel->getClientsWithStatsByRestaurant($adminRestaurantId);
            } else {
                $clients = $userModel->getAllClients();
            }
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
            
            if ($adminRestaurantId) {
                $tables = $tableModel->getAvailableTablesByRestaurant($adminRestaurantId, $date, $time, $capacity);
            } else {
                $tables = $tableModel->getAvailableTables($date, $time, $capacity);
            }
            echo json_encode(['success' => true, 'data' => $tables]);
            break;

        case 'create_reservation':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                break;
            }
            
            // Debug: Log de los datos recibidos
            error_log("=== CREAR RESERVA - DATOS RECIBIDOS ===");
            error_log("POST data: " . print_r($_POST, true));
            
            // Usar el restaurante del administrador actual
            $defaultRestaurantId = $adminRestaurantId;
            
            if (!$defaultRestaurantId) {
                // Si el admin no tiene restaurante asignado, obtener el primero disponible (fallback)
                $restaurantQuery = "SELECT id_restaurante FROM restaurante LIMIT 1";
                $restaurant = Connection::getInstance()->fetchOne($restaurantQuery);
                $defaultRestaurantId = $restaurant ? $restaurant['id_restaurante'] : null;
            }
            
            if (!$defaultRestaurantId) {
                echo json_encode(['success' => false, 'message' => 'No hay restaurantes configurados en el sistema']);
                break;
            }
            
            $data = [
                'id_usuario' => $_POST['client_id'] ?? '',
                'id_restaurante' => $_POST['restaurant_id'] ?? $defaultRestaurantId,
                'id_mesa' => $_POST['table_id'] ?? '',
                'fecha_reserva' => $_POST['date'] ?? '',
                'hora_reserva' => $_POST['time'] ?? '',
                'num_personas' => (int)($_POST['people'] ?? 1)
            ];
            
            error_log("Datos procesados: " . print_r($data, true));
            
            // Validar datos requeridos
            $required = ['id_usuario', 'id_mesa', 'fecha_reserva', 'hora_reserva'];
            $missing = [];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $missing[] = $field;
                }
            }
            
            if (!empty($missing)) {
                $message = "Campos requeridos faltantes: " . implode(', ', $missing);
                error_log("Error validación: " . $message);
                echo json_encode(['success' => false, 'message' => $message, 'missing_fields' => $missing]);
                break;
            }
            
            if ($data['num_personas'] < 1 || $data['num_personas'] > 20) {
                echo json_encode(['success' => false, 'message' => 'Número de personas debe estar entre 1 y 20']);
                break;
            }
            
            try {
                // Verificar que la mesa existe
                $tableQuery = "SELECT id_mesa, numero, capacidad FROM mesa WHERE id_mesa = ?";
                $table = Connection::getInstance()->fetchOne($tableQuery, [$data['id_mesa']]);
                if (!$table) {
                    echo json_encode(['success' => false, 'message' => 'La mesa seleccionada no existe']);
                    break;
                }
                error_log("Mesa encontrada: " . print_r($table, true));
                
                // Verificar que el usuario existe
                $userQuery = "SELECT id_usuario, nombre FROM usuario WHERE id_usuario = ?";
                $user = Connection::getInstance()->fetchOne($userQuery, [$data['id_usuario']]);
                if (!$user) {
                    echo json_encode(['success' => false, 'message' => 'El cliente seleccionado no existe']);
                    break;
                }
                error_log("Usuario encontrado: " . print_r($user, true));
                
                // Verificar disponibilidad
                error_log("=== VERIFICANDO DISPONIBILIDAD DE MESA ===");
                error_log("ID Mesa: " . $data['id_mesa']);
                error_log("Fecha: " . $data['fecha_reserva']);  
                error_log("Hora: " . $data['hora_reserva']);
                
                $available = $reservationModel->checkTableAvailability(
                    $data['id_mesa'], 
                    $data['fecha_reserva'], 
                    $data['hora_reserva']
                );
                
                error_log("¿Mesa disponible? " . ($available ? 'SÍ' : 'NO'));
                
                if (!$available) {
                    // Mostrar reservas existentes para debug
                    $existingReservations = Connection::getInstance()->fetchAll(
                        "SELECT * FROM reservacion WHERE id_mesa = ? AND DATE(fecha_reserva) = DATE(?)",
                        [$data['id_mesa'], $data['fecha_reserva']]
                    );
                    error_log("Reservas existentes en esta mesa y fecha: " . print_r($existingReservations, true));
                    
                    echo json_encode(['success' => false, 'message' => 'La mesa no está disponible en esa fecha y hora']);
                    break;
                }
                
                // Crear reservación
                $reservationId = $reservationModel->createReservation($data);
                
                if ($reservationId) {
                    error_log("Reserva creada exitosamente con ID: " . $reservationId);
                    echo json_encode(['success' => true, 'message' => 'Reservación creada exitosamente', 'id' => $reservationId]);
                } else {
                    error_log("Error: createReservation retornó false");
                    echo json_encode(['success' => false, 'message' => 'Error al crear la reservación en la base de datos']);
                }
                
            } catch (Exception $e) {
                error_log("Excepción al crear reserva: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
            }
            break;

        case 'get_clients_with_stats':
            if ($adminRestaurantId) {
                $clients = $reservationModel->getClientsWithStatsByRestaurant($adminRestaurantId);
            } else {
                $clients = $userModel->getClientsWithStats();
            }
            echo json_encode(['success' => true, 'data' => $clients]);
            break;

        case 'get_client_stats':
            // Por ahora usamos las estadísticas generales, pero podrían filtrarse
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

        case 'update_reservation':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Método no permitido. Use POST.']);
                break;
            }

            error_log("=== UPDATE RESERVATION API ===");
            error_log("POST data: " . print_r($_POST, true));

            $reservationId = $_POST['reservation_id'] ?? '';
            if (empty($reservationId)) {
                error_log("ERROR: reservation_id está vacío");
                echo json_encode(['success' => false, 'message' => 'ID de reservación requerido']);
                break;
            }

            // Construir datos para actualizar
            $updateData = [];
            $errors = [];
            
            // Validar fecha
            if (!empty($_POST['date'])) {
                $date = $_POST['date'];
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    $updateData['fecha_reserva'] = $date;
                } else {
                    $errors[] = 'Formato de fecha inválido (esperado: YYYY-MM-DD)';
                }
            }
            
            // Validar hora
            if (!empty($_POST['time'])) {
                $time = $_POST['time'];
                if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
                    $updateData['hora_reserva'] = $time;
                } else {
                    $errors[] = 'Formato de hora inválido (esperado: HH:MM)';
                }
            }
            
            // Validar número de personas
            if (!empty($_POST['people'])) {
                $people = (int)$_POST['people'];
                if ($people >= 1 && $people <= 20) {
                    $updateData['num_personas'] = $people;
                } else {
                    $errors[] = 'Número de personas debe estar entre 1 y 20';
                }
            }
            
            // Validar mesa
            if (!empty($_POST['table_id'])) {
                $updateData['id_mesa'] = $_POST['table_id'];
            }
            
            // Validar estado
            if (!empty($_POST['status'])) {
                $validStatuses = ['pendiente', 'confirmada', 'check_in', 'completada', 'cancelada', 'noshow'];
                if (in_array($_POST['status'], $validStatuses)) {
                    $updateData['estado'] = $_POST['status'];
                } else {
                    $errors[] = 'Estado no válido: ' . $_POST['status'];
                }
            }

            // Si hay errores de validación, devolverlos
            if (!empty($errors)) {
                error_log("Errores de validación: " . implode(', ', $errors));
                echo json_encode(['success' => false, 'message' => 'Errores de validación: ' . implode(', ', $errors)]);
                break;
            }

            if (empty($updateData)) {
                error_log("ERROR: No hay datos para actualizar");
                echo json_encode(['success' => false, 'message' => 'No hay datos para actualizar']);
                break;
            }

            error_log("Datos a actualizar: " . print_r($updateData, true));

            try {
                // Verificar que la reserva existe antes de actualizar
                $existingReservation = $reservationModel->getReservationById($reservationId);
                if (!$existingReservation) {
                    error_log("ERROR: Reserva no encontrada con ID: " . $reservationId);
                    echo json_encode(['success' => false, 'message' => 'Reservación no encontrada con ID: ' . substr($reservationId, 0, 8)]);
                    break;
                }
                
                error_log("Reserva encontrada. Datos actuales: " . json_encode($existingReservation));
                
                $result = $reservationModel->updateReservation($reservationId, $updateData);
                error_log("Resultado de updateReservation: " . var_export($result, true));
                
                if ($result) {
                    // Verificar los cambios consultando la reserva actualizada
                    $updatedReservation = $reservationModel->getReservationById($reservationId);
                    error_log("Reserva después de actualizar: " . json_encode($updatedReservation));
                    error_log("SUCCESS: Reserva actualizada exitosamente");
                    echo json_encode(['success' => true, 'message' => 'Reservación actualizada exitosamente']);
                } else {
                    error_log("ERROR: updateReservation devolvió false");
                    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la reservación. Posiblemente la mesa no está disponible en esa fecha/hora.']);
                }
            } catch (Exception $e) {
                error_log("EXCEPTION al actualizar reserva " . $reservationId . ": " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
            }
            break;

        case 'delete_reservation':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Método no permitido. Use POST.']);
                break;
            }

            error_log("=== DELETE RESERVATION API ===");
            error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
            error_log("POST data: " . print_r($_POST, true));

            $reservationId = $_POST['reservation_id'] ?? '';
            
            if (empty($reservationId)) {
                error_log("ERROR: reservation_id está vacío");
                echo json_encode(['success' => false, 'message' => 'ID de reservación requerido']);
                break;
            }
            
            error_log("Intentando eliminar reserva con ID: " . $reservationId);

            try {
                // Verificar que la reserva existe antes de eliminar
                $existingReservation = $reservationModel->getReservationById($reservationId);
                if (!$existingReservation) {
                    error_log("ERROR: Reserva no encontrada con ID: " . $reservationId);
                    echo json_encode(['success' => false, 'message' => 'Reservación no encontrada con ID: ' . substr($reservationId, 0, 8)]);
                    break;
                }
                
                error_log("Reserva encontrada, procediendo a eliminar...");
                error_log("Datos de reserva: " . json_encode($existingReservation));
                
                $result = $reservationModel->deleteReservation($reservationId);
                error_log("Resultado de deleteReservation: " . ($result ? "true" : "false"));
                
                if ($result) {
                    error_log("SUCCESS: Reserva eliminada exitosamente");
                    echo json_encode(['success' => true, 'message' => 'Reservación eliminada exitosamente']);
                } else {
                    error_log("ERROR: deleteReservation retornó false");
                    echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la reservación. Intente nuevamente.']);
                }
            } catch (Exception $e) {
                error_log("EXCEPTION al eliminar reserva: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
            }
            break;

        case 'get_reservation':
            $reservationId = $_GET['id'] ?? '';
            if (empty($reservationId)) {
                echo json_encode(['success' => false, 'message' => 'ID de reservación requerido']);
                break;
            }

            try {
                $reservation = $reservationModel->getReservationById($reservationId);
                if ($reservation) {
                    echo json_encode(['success' => true, 'data' => $reservation]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Reservación no encontrada']);
                }
            } catch (Exception $e) {
                error_log("Error al obtener reserva: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
            }
            break;

        case 'get_available_tables':
            $date = $_GET['date'] ?? '';
            $time = $_GET['time'] ?? '';
            $capacity = (int)($_GET['capacity'] ?? 1);
            $excludeReservation = $_GET['exclude_reservation'] ?? '';

            if (empty($date) || empty($time)) {
                echo json_encode(['success' => false, 'message' => 'Fecha y hora son requeridas']);
                break;
            }

            try {
                // Obtener todas las mesas del restaurante del admin que tengan capacidad suficiente
                if ($adminRestaurantId) {
                    $sql = "SELECT id_mesa, numero, capacidad FROM mesa WHERE id_restaurante = ? AND capacidad >= ? ORDER BY numero";
                    $allTables = Connection::getInstance()->fetchAll($sql, [$adminRestaurantId, $capacity]);
                } else {
                    $sql = "SELECT id_mesa, numero, capacidad FROM mesa WHERE capacidad >= ? ORDER BY numero";
                    $allTables = Connection::getInstance()->fetchAll($sql, [$capacity]);
                }

                $availableTables = [];
                foreach ($allTables as $table) {
                    // Verificar disponibilidad de cada mesa
                    $available = $reservationModel->checkTableAvailability($table['id_mesa'], $date, $time);
                    
                    // Si estamos editando una reserva, excluir esa reserva de la verificación
                    if (!$available && !empty($excludeReservation)) {
                        $sql = "SELECT COUNT(*) as conflictos
                                FROM reservacion r
                                WHERE r.id_mesa = ?
                                AND DATE(r.fecha_reserva) = DATE(?)
                                AND TIME(r.hora_reserva) = TIME(?)
                                AND r.estado IN ('confirmada', 'check_in', 'pendiente')
                                AND r.id_reservacion != ?";
                        
                        $result = Connection::getInstance()->fetchOne($sql, [$table['id_mesa'], $date, $time, $excludeReservation]);
                        $available = $result['conflictos'] == 0;
                    }
                    
                    if ($available) {
                        $availableTables[] = $table;
                    }
                }

                echo json_encode(['success' => true, 'data' => $availableTables]);
            } catch (Exception $e) {
                error_log("Error al obtener mesas disponibles: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
            }
            break;

        // ========== ENDPOINTS DE MESAS ==========
        
        case 'get_all_tables':
            try {
                if ($adminRestaurantId) {
                    $tables = $tableModel->getAllTablesByRestaurant($adminRestaurantId);
                    $stats = $tableModel->getTablesStatsByRestaurant($adminRestaurantId);
                } else {
                    $tables = $tableModel->getAllTables();
                    $stats = $tableModel->getTablesStats();
                }
                echo json_encode([
                    'success' => true, 
                    'data' => $tables,
                    'stats' => $stats
                ]);
            } catch (Exception $e) {
                error_log("Error al obtener mesas: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error al obtener mesas: ' . $e->getMessage()]);
            }
            break;

        case 'get_tables_with_status':
            try {
                $date = $_GET['date'] ?? date('Y-m-d');
                if ($adminRestaurantId) {
                    $tables = $tableModel->getTablesWithStatusByRestaurant($adminRestaurantId, $date);
                } else {
                    $tables = $tableModel->getTablesWithStatus($date);
                }
                echo json_encode(['success' => true, 'data' => $tables]);
            } catch (Exception $e) {
                error_log("Error al obtener mesas con estado: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'get_table':
            $tableId = $_GET['id'] ?? '';
            if (empty($tableId)) {
                echo json_encode(['success' => false, 'message' => 'ID de mesa requerido']);
                break;
            }
            
            try {
                $table = $tableModel->getTableById($tableId);
                if ($table) {
                    echo json_encode(['success' => true, 'data' => $table]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Mesa no encontrada']);
                }
            } catch (Exception $e) {
                error_log("Error al obtener mesa: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'create_table':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                break;
            }
            
            error_log("=== CREATE TABLE API ===");
            error_log("POST data: " . print_r($_POST, true));
            
            $numero = $_POST['numero'] ?? '';
            $capacidad = $_POST['capacidad'] ?? '';
            $restauranteId = $_POST['id_restaurante'] ?? '';
            
            if (empty($numero) || empty($capacidad)) {
                echo json_encode(['success' => false, 'message' => 'Número y capacidad son requeridos']);
                break;
            }
            
            // Si no se proporciona restaurante, usar el del administrador
            if (empty($restauranteId)) {
                if ($adminRestaurantId) {
                    $restauranteId = $adminRestaurantId;
                } else {
                    // Fallback al primer restaurante
                    $sqlRestaurant = "SELECT id_restaurante FROM restaurante LIMIT 1";
                    $restaurant = Connection::getInstance()->fetchOne($sqlRestaurant);
                    if ($restaurant) {
                        $restauranteId = $restaurant['id_restaurante'];
                    } else {
                        echo json_encode(['success' => false, 'message' => 'No hay restaurantes registrados']);
                        break;
                    }
                }
            }
            
            try {
                $result = $tableModel->createTable([
                    'numero' => (int)$numero,
                    'capacidad' => (int)$capacidad,
                    'id_restaurante' => $restauranteId
                ]);
                echo json_encode($result);
            } catch (Exception $e) {
                error_log("Error al crear mesa: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'update_table':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                break;
            }
            
            error_log("=== UPDATE TABLE API ===");
            error_log("POST data: " . print_r($_POST, true));
            
            $tableId = $_POST['table_id'] ?? '';
            if (empty($tableId)) {
                echo json_encode(['success' => false, 'message' => 'ID de mesa requerido']);
                break;
            }
            
            $updateData = [];
            if (!empty($_POST['numero'])) {
                $updateData['numero'] = (int)$_POST['numero'];
            }
            if (!empty($_POST['capacidad'])) {
                $updateData['capacidad'] = (int)$_POST['capacidad'];
            }
            
            if (empty($updateData)) {
                echo json_encode(['success' => false, 'message' => 'No hay datos para actualizar']);
                break;
            }
            
            try {
                $result = $tableModel->updateTable($tableId, $updateData);
                echo json_encode($result);
            } catch (Exception $e) {
                error_log("Error al actualizar mesa: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'delete_table':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                break;
            }
            
            error_log("=== DELETE TABLE API ===");
            error_log("POST data: " . print_r($_POST, true));
            
            $tableId = $_POST['table_id'] ?? '';
            if (empty($tableId)) {
                echo json_encode(['success' => false, 'message' => 'ID de mesa requerido']);
                break;
            }
            
            try {
                $result = $tableModel->deleteTable($tableId);
                echo json_encode($result);
            } catch (Exception $e) {
                error_log("Error al eliminar mesa: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'get_tables_stats':
            try {
                if ($adminRestaurantId) {
                    $stats = $tableModel->getTablesStatsByRestaurant($adminRestaurantId);
                    $occupancy = $tableModel->getOccupancyStatsByRestaurant($adminRestaurantId);
                } else {
                    $stats = $tableModel->getTablesStats();
                    $occupancy = $tableModel->getOccupancyStats();
                }
                echo json_encode([
                    'success' => true, 
                    'data' => array_merge($stats ?: [], $occupancy ?: [])
                ]);
            } catch (Exception $e) {
                error_log("Error al obtener estadísticas de mesas: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
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