<?php
require_once 'Connection.php';

class Reservation
{
    private $connection;

    public function __construct()
    {
        $this->connection = Connection::getInstance();
    }

    /**
     * Obtener todas las reservaciones del día actual
     */
    public function getTodayReservations()
    {
        $sql = "SELECT 
                    r.id_reservacion,
                    r.fecha_reserva,
                    r.hora_reserva,
                    r.num_personas,
                    r.estado,
                    r.fecha_creacion,
                    u.nombre as cliente_nombre,
                    u.correo as cliente_email,
                    u.telefono as cliente_telefono,
                    m.numero as mesa_numero,
                    rest.nombre as restaurante_nombre
                FROM reservacion r
                INNER JOIN usuario u ON r.id_usuario = u.id_usuario
                INNER JOIN mesa m ON r.id_mesa = m.id_mesa
                INNER JOIN restaurante rest ON r.id_restaurante = rest.id_restaurante
                WHERE DATE(r.fecha_reserva) = CURDATE()
                ORDER BY r.hora_reserva ASC";
        
        return $this->connection->fetchAll($sql);
    }

    /**
     * Obtener reservaciones por fecha específica
     */
    public function getReservationsByDate($date)
    {
        $sql = "SELECT 
                    r.id_reservacion,
                    r.fecha_reserva,
                    r.hora_reserva,
                    r.num_personas,
                    r.estado,
                    r.fecha_creacion,
                    u.nombre as cliente_nombre,
                    u.correo as cliente_email,
                    u.telefono as cliente_telefono,
                    m.numero as mesa_numero,
                    rest.nombre as restaurante_nombre
                FROM reservacion r
                INNER JOIN usuario u ON r.id_usuario = u.id_usuario
                INNER JOIN mesa m ON r.id_mesa = m.id_mesa
                INNER JOIN restaurante rest ON r.id_restaurante = rest.id_restaurante
                WHERE DATE(r.fecha_reserva) = ?
                ORDER BY r.hora_reserva ASC";
        
        return $this->connection->fetchAll($sql, [$date]);
    }

    /**
     * Obtener todas las reservaciones con filtros opcionales
     */
    public function getAllReservations($filters = [])
    {
        $sql = "SELECT 
                    r.id_reservacion,
                    r.fecha_reserva,
                    r.hora_reserva,
                    r.num_personas,
                    r.estado,
                    r.fecha_creacion,
                    u.nombre as cliente_nombre,
                    u.correo as cliente_email,
                    u.telefono as cliente_telefono,
                    m.numero as mesa_numero,
                    rest.nombre as restaurante_nombre
                FROM reservacion r
                INNER JOIN usuario u ON r.id_usuario = u.id_usuario
                INNER JOIN mesa m ON r.id_mesa = m.id_mesa
                INNER JOIN restaurante rest ON r.id_restaurante = rest.id_restaurante";
        
        $whereConditions = [];
        $params = [];
        
        // Filtro por fecha desde
        if (!empty($filters['fecha_desde'])) {
            $whereConditions[] = "DATE(r.fecha_reserva) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        
        // Filtro por fecha hasta
        if (!empty($filters['fecha_hasta'])) {
            $whereConditions[] = "DATE(r.fecha_reserva) <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        
        // Filtro por estado
        if (!empty($filters['estado'])) {
            $whereConditions[] = "r.estado = ?";
            $params[] = $filters['estado'];
        }
        
        // Agregar condiciones WHERE si existen
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        $sql .= " ORDER BY r.fecha_reserva ASC, r.hora_reserva ASC";
        
        return $this->connection->fetchAll($sql, $params);
    }

    /**
     * Obtener reservaciones futuras (desde hoy en adelante)
     */
    public function getFutureReservations()
    {
        $sql = "SELECT 
                    r.id_reservacion,
                    r.fecha_reserva,
                    r.hora_reserva,
                    r.num_personas,
                    r.estado,
                    r.fecha_creacion,
                    u.nombre as cliente_nombre,
                    u.correo as cliente_email,
                    u.telefono as cliente_telefono,
                    m.numero as mesa_numero,
                    rest.nombre as restaurante_nombre
                FROM reservacion r
                INNER JOIN usuario u ON r.id_usuario = u.id_usuario
                INNER JOIN mesa m ON r.id_mesa = m.id_mesa
                INNER JOIN restaurante rest ON r.id_restaurante = rest.id_restaurante
                WHERE DATE(r.fecha_reserva) >= CURDATE()
                ORDER BY r.fecha_reserva ASC, r.hora_reserva ASC";
        
        return $this->connection->fetchAll($sql);
    }

    /**
     * Obtener reservaciones por rango de fechas
     */
    public function getReservationsByDateRange($fechaInicio, $fechaFin)
    {
        $sql = "SELECT 
                    r.id_reservacion,
                    r.fecha_reserva,
                    r.hora_reserva,
                    r.num_personas,
                    r.estado,
                    r.fecha_creacion,
                    u.nombre as cliente_nombre,
                    u.correo as cliente_email,
                    u.telefono as cliente_telefono,
                    m.numero as mesa_numero,
                    rest.nombre as restaurante_nombre
                FROM reservacion r
                INNER JOIN usuario u ON r.id_usuario = u.id_usuario
                INNER JOIN mesa m ON r.id_mesa = m.id_mesa
                INNER JOIN restaurante rest ON r.id_restaurante = rest.id_restaurante
                WHERE DATE(r.fecha_reserva) BETWEEN ? AND ?
                ORDER BY r.fecha_reserva ASC, r.hora_reserva ASC";
        
        return $this->connection->fetchAll($sql, [$fechaInicio, $fechaFin]);
    }

    /**
     * Obtener estadísticas del día
     */
    public function getTodayStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total_reservaciones,
                    SUM(r.num_personas) as total_comensales,
                    SUM(CASE WHEN r.estado = 'noshow' THEN 1 ELSE 0 END) as total_noshows,
                    AVG(r.num_personas) as promedio_personas
                FROM reservacion r
                WHERE DATE(r.fecha_reserva) = CURDATE()";
        
        return $this->connection->fetchOne($sql);
    }

    /**
     * Obtener estadísticas comparativas con el día anterior
     */
    public function getYesterdayStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total_reservaciones,
                    SUM(r.num_personas) as total_comensales,
                    SUM(CASE WHEN r.estado = 'noshow' THEN 1 ELSE 0 END) as total_noshows
                FROM reservacion r
                WHERE DATE(r.fecha_reserva) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        
        return $this->connection->fetchOne($sql);
    }

    /**
     * Obtener disponibilidad por franjas horarias
     */
    public function getTimeSlotAvailability($date = null)
    {
        if ($date === null) {
            $date = date('Y-m-d');
        }

        $sql = "SELECT 
                    TIME_FORMAT(r.hora_reserva, '%H:%i') as franja_horaria,
                    COUNT(*) as reservaciones_activas,
                    SUM(r.num_personas) as personas_reservadas
                FROM reservacion r
                WHERE DATE(r.fecha_reserva) = ? 
                AND r.estado IN ('confirmada', 'check_in')
                GROUP BY TIME_FORMAT(r.hora_reserva, '%H:%i')
                ORDER BY r.hora_reserva";
        
        return $this->connection->fetchAll($sql, [$date]);
    }

    /**
     * Actualizar estado de una reservación
     */
    public function updateReservationStatus($reservationId, $newStatus)
    {
        error_log("=== ACTUALIZANDO ESTADO DE RESERVA ===");
        error_log("ID Reserva: " . $reservationId);
        error_log("Nuevo Estado: " . $newStatus);
        
        // Validar que el estado sea uno de los válidos
        $validStatuses = ['pendiente', 'confirmada', 'check_in', 'completada', 'cancelada', 'noshow'];
        if (!in_array($newStatus, $validStatuses)) {
            error_log("ERROR: Estado no válido: " . $newStatus);
            return false;
        }
        
        // Usar solo SET estado = ? ya que fecha_actualizacion se actualiza automáticamente
        // por el ON UPDATE CURRENT_TIMESTAMP definido en la tabla
        $sql = "UPDATE reservacion SET estado = ? WHERE id_reservacion = ?";
        
        try {
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode([$newStatus, $reservationId]));
            
            $result = $this->connection->update($sql, [$newStatus, $reservationId]);
            error_log("Filas afectadas en updateReservationStatus: " . $result);
            
            // Verificar si realmente se actualizó consultando el nuevo estado
            $verification = $this->connection->fetchOne(
                "SELECT estado FROM reservacion WHERE id_reservacion = ?", 
                [$reservationId]
            );
            error_log("Verificación - Estado actual en BD: " . ($verification['estado'] ?? 'NO ENCONTRADO'));
            
            return $result;
        } catch (Exception $e) {
            error_log("ERROR en updateReservationStatus: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Obtener una reservación específica
     */
    public function getReservationById($reservationId)
    {
        $sql = "SELECT 
                    r.*,
                    u.nombre as cliente_nombre,
                    u.correo as cliente_email,
                    u.telefono as cliente_telefono,
                    m.numero as mesa_numero,
                    m.capacidad as mesa_capacidad,
                    rest.nombre as restaurante_nombre
                FROM reservacion r
                INNER JOIN usuario u ON r.id_usuario = u.id_usuario
                INNER JOIN mesa m ON r.id_mesa = m.id_mesa
                INNER JOIN restaurante rest ON r.id_restaurante = rest.id_restaurante
                WHERE r.id_reservacion = ?";
        
        return $this->connection->fetchOne($sql, [$reservationId]);
    }

    /**
     * Crear una nueva reservación
     */
    public function createReservation($data)
    {
        $id = $this->generateUUID();
        $sql = "INSERT INTO reservacion (
                    id_reservacion, 
                    id_usuario, 
                    id_restaurante, 
                    id_mesa, 
                    fecha_reserva, 
                    hora_reserva, 
                    num_personas, 
                    estado, 
                    fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmada', NOW())";
        
        $result = $this->connection->insert($sql, [
            $id,
            $data['id_usuario'],
            $data['id_restaurante'],
            $data['id_mesa'],
            $data['fecha_reserva'],
            $data['hora_reserva'],
            $data['num_personas']
        ]);
        
        return $result ? $id : false;
    }

    /**
     * Verificar disponibilidad de mesa en fecha y hora específica
     */
    public function checkTableAvailability($tableId, $date, $time)
    {
        // Debug: Log de los parámetros recibidos
        error_log("=== VERIFICANDO DISPONIBILIDAD ===");
        error_log("Mesa ID: " . $tableId);
        error_log("Fecha: " . $date);
        error_log("Hora: " . $time);
        
        // Primero, verificar si la mesa existe
        $mesaExiste = $this->connection->fetchOne("SELECT id_mesa FROM mesa WHERE id_mesa = ?", [$tableId]);
        if (!$mesaExiste) {
            error_log("ERROR: Mesa no existe");
            return false;
        }
        
        // Consulta muy simple - buscar reservas exactas en la misma mesa, fecha y hora
        $sql = "SELECT COUNT(*) as conflictos
                FROM reservacion r
                WHERE r.id_mesa = ?
                AND DATE(r.fecha_reserva) = DATE(?)
                AND TIME(r.hora_reserva) = TIME(?)
                AND r.estado IN ('confirmada', 'check_in', 'pendiente')";
        
        $result = $this->connection->fetchOne($sql, [$tableId, $date, $time]);
        
        error_log("SQL ejecutada: " . $sql);
        error_log("Parámetros: " . json_encode([$tableId, $date, $time]));
        error_log("Conflictos encontrados: " . $result['conflictos']);
        
        $available = $result['conflictos'] == 0;
        error_log("¿Está disponible? " . ($available ? 'SÍ' : 'NO'));
        
        return $available;
    }

    /**
     * Actualizar una reservación existente
     */
    public function updateReservation($reservationId, $data)
    {
        error_log("=== ACTUALIZANDO RESERVA ===");
        error_log("ID Reserva: " . $reservationId);
        error_log("Datos recibidos: " . print_r($data, true));

        // Verificar que la reserva existe
        $existingReservation = $this->getReservationById($reservationId);
        if (!$existingReservation) {
            error_log("ERROR: Reserva no encontrada con ID: " . $reservationId);
            return false;
        }
        
        error_log("Reserva existente: " . json_encode($existingReservation));

        // Determinar valores finales para verificación de disponibilidad
        $finalMesa = $data['id_mesa'] ?? $existingReservation['id_mesa'];
        $finalFecha = $data['fecha_reserva'] ?? $existingReservation['fecha_reserva'];
        $finalHora = $data['hora_reserva'] ?? $existingReservation['hora_reserva'];
        
        // Verificar si hay cambio en mesa, fecha u hora
        $cambioReserva = (
            (isset($data['id_mesa']) && $data['id_mesa'] != $existingReservation['id_mesa']) ||
            (isset($data['fecha_reserva']) && $data['fecha_reserva'] != $existingReservation['fecha_reserva']) ||
            (isset($data['hora_reserva']) && substr($data['hora_reserva'], 0, 5) != substr($existingReservation['hora_reserva'], 0, 5))
        );
        
        error_log("¿Hay cambio en mesa/fecha/hora? " . ($cambioReserva ? 'SÍ' : 'NO'));
        
        // Si hay cambio, verificar disponibilidad
        if ($cambioReserva) {
            error_log("Verificando disponibilidad para: Mesa=$finalMesa, Fecha=$finalFecha, Hora=$finalHora");
            $isAvailable = $this->checkTableAvailabilityForUpdate(
                $finalMesa, 
                $finalFecha, 
                $finalHora,
                $reservationId
            );
            
            if (!$isAvailable) {
                error_log("ERROR: Nueva fecha/hora/mesa no disponible - hay conflicto");
                return false;
            }
            error_log("Disponibilidad confirmada");
        }

        $sql = "UPDATE reservacion SET ";
        $params = [];
        $updates = [];

        // Construir dinámicamente la consulta UPDATE
        if (isset($data['id_mesa'])) {
            $updates[] = "id_mesa = ?";
            $params[] = $data['id_mesa'];
        }
        if (isset($data['fecha_reserva'])) {
            $updates[] = "fecha_reserva = ?";
            $params[] = $data['fecha_reserva'];
        }
        if (isset($data['hora_reserva'])) {
            $updates[] = "hora_reserva = ?";
            $params[] = $data['hora_reserva'];
        }
        if (isset($data['num_personas'])) {
            $updates[] = "num_personas = ?";
            $params[] = $data['num_personas'];
        }
        if (isset($data['estado'])) {
            $updates[] = "estado = ?";
            $params[] = $data['estado'];
        }

        if (empty($updates)) {
            error_log("ERROR: No hay campos para actualizar");
            return false;
        }

        $sql .= implode(", ", $updates) . " WHERE id_reservacion = ?";
        $params[] = $reservationId;

        error_log("SQL Update: " . $sql);
        error_log("Parámetros: " . json_encode($params));

        try {
            $result = $this->connection->update($sql, $params);
            error_log("Filas afectadas en actualización: " . $result);
            
            // Verificar que realmente se actualizó
            $updatedReservation = $this->getReservationById($reservationId);
            error_log("Reserva después de UPDATE: " . json_encode($updatedReservation));
            
            // Devolver true si la operación no falló (result >= 0)
            return $result !== false;
        } catch (Exception $e) {
            error_log("EXCEPTION en updateReservation: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Eliminar una reservación
     */
    public function deleteReservation($reservationId)
    {
        error_log("=== ELIMINANDO RESERVA ===");
        error_log("ID Reserva: " . $reservationId);

        if (empty($reservationId)) {
            error_log("ERROR: ID de reserva vacío");
            return false;
        }

        // Verificar que la reserva existe
        $existingReservation = $this->getReservationById($reservationId);
        if (!$existingReservation) {
            error_log("ERROR: Reserva no encontrada para eliminación");
            return false;
        }
        
        error_log("Reserva encontrada: " . json_encode($existingReservation));

        try {
            // Iniciar transacción para asegurar consistencia
            $this->connection->beginTransaction();
            
            // Primero eliminar registros relacionados (pagos)
            // La tabla pago tiene FK hacia reservacion con ON DELETE NO ACTION
            // Por eso debemos eliminarlos primero manualmente
            $sqlDeletePayments = "DELETE FROM pago WHERE id_reservacion = ?";
            $deletedPayments = $this->connection->delete($sqlDeletePayments, [$reservationId]);
            error_log("Pagos eliminados: " . $deletedPayments);
            
            // Ahora eliminar la reservación
            $sql = "DELETE FROM reservacion WHERE id_reservacion = ?";
            $result = $this->connection->delete($sql, [$reservationId]);
            
            error_log("Filas eliminadas de reservacion: " . $result);
            
            if ($result > 0) {
                // Confirmar transacción
                $this->connection->commit();
                error_log("Reserva eliminada exitosamente - Transacción confirmada");
                return true;
            } else {
                // Revertir si no se eliminó nada
                $this->connection->rollback();
                error_log("No se eliminó ninguna fila - Transacción revertida");
                return false;
            }
        } catch (Exception $e) {
            // Revertir en caso de error
            $this->connection->rollback();
            error_log("ERROR en deleteReservation: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Verificar disponibilidad excluyendo una reserva específica (para actualizaciones)
     */
    private function checkTableAvailabilityForUpdate($tableId, $date, $time, $excludeReservationId)
    {
        $sql = "SELECT COUNT(*) as conflictos
                FROM reservacion r
                WHERE r.id_mesa = ?
                AND DATE(r.fecha_reserva) = DATE(?)
                AND TIME(r.hora_reserva) = TIME(?)
                AND r.estado IN ('confirmada', 'check_in', 'pendiente')
                AND r.id_reservacion != ?";
        
        $result = $this->connection->fetchOne($sql, [$tableId, $date, $time, $excludeReservationId]);
        return $result['conflictos'] == 0;
    }

    /**
     * Generar UUID para nuevas reservaciones
     */
    private function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Obtener reservaciones de un usuario específico
     */
    public function getReservationsByUserId($userId, $status = null)
    {
        $sql = "SELECT 
                    r.id_reservacion,
                    r.fecha_reserva,
                    r.hora_reserva,
                    r.num_personas,
                    r.estado,
                    r.fecha_creacion,
                    r.id_restaurante,
                    m.numero as mesa_numero,
                    m.capacidad as mesa_capacidad,
                    rest.nombre as restaurante_nombre,
                    rest.direccion as restaurante_direccion,
                    rest.telefono as restaurante_telefono,
                    rest.foto_portada as restaurante_imagen,
                    rest.tipo_cocina as restaurante_tipo_cocina
                FROM reservacion r
                INNER JOIN mesa m ON r.id_mesa = m.id_mesa
                INNER JOIN restaurante rest ON r.id_restaurante = rest.id_restaurante
                WHERE r.id_usuario = ?";
        
        $params = [$userId];
        
        if ($status !== null) {
            $sql .= " AND r.estado = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";
        
        return $this->connection->fetchAll($sql, $params);
    }

    /**
     * Obtener reservaciones activas (futuras) de un usuario
     */
    public function getActiveReservationsByUserId($userId)
    {
        $sql = "SELECT 
                    r.id_reservacion,
                    r.fecha_reserva,
                    r.hora_reserva,
                    r.num_personas,
                    r.estado,
                    r.fecha_creacion,
                    r.id_restaurante,
                    m.numero as mesa_numero,
                    m.capacidad as mesa_capacidad,
                    rest.nombre as restaurante_nombre,
                    rest.direccion as restaurante_direccion,
                    rest.telefono as restaurante_telefono,
                    rest.foto_portada as restaurante_imagen,
                    rest.tipo_cocina as restaurante_tipo_cocina
                FROM reservacion r
                INNER JOIN mesa m ON r.id_mesa = m.id_mesa
                INNER JOIN restaurante rest ON r.id_restaurante = rest.id_restaurante
                WHERE r.id_usuario = ?
                AND r.estado IN ('pendiente', 'confirmada')
                AND (r.fecha_reserva > CURDATE() OR (r.fecha_reserva = CURDATE() AND r.hora_reserva >= CURTIME()))
                ORDER BY r.fecha_reserva ASC, r.hora_reserva ASC";
        
        return $this->connection->fetchAll($sql, [$userId]);
    }

    /**
     * Obtener historial de reservaciones pasadas de un usuario
     */
    public function getPastReservationsByUserId($userId)
    {
        $sql = "SELECT 
                    r.id_reservacion,
                    r.fecha_reserva,
                    r.hora_reserva,
                    r.num_personas,
                    r.estado,
                    r.fecha_creacion,
                    r.id_restaurante,
                    m.numero as mesa_numero,
                    rest.nombre as restaurante_nombre,
                    rest.direccion as restaurante_direccion,
                    rest.foto_portada as restaurante_imagen,
                    rest.tipo_cocina as restaurante_tipo_cocina
                FROM reservacion r
                INNER JOIN mesa m ON r.id_mesa = m.id_mesa
                INNER JOIN restaurante rest ON r.id_restaurante = rest.id_restaurante
                WHERE r.id_usuario = ?
                AND (r.fecha_reserva < CURDATE() OR r.estado IN ('completada', 'cancelada', 'noshow'))
                ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";
        
        return $this->connection->fetchAll($sql, [$userId]);
    }

    /**
     * Cancelar una reservación de usuario
     */
    public function cancelUserReservation($reservationId, $userId)
    {
        // Verificar que la reservación pertenece al usuario
        $reservation = $this->connection->fetchOne(
            "SELECT id_reservacion, estado, fecha_reserva FROM reservacion WHERE id_reservacion = ? AND id_usuario = ?",
            [$reservationId, $userId]
        );
        
        if (!$reservation) {
            return ['success' => false, 'message' => 'Reservación no encontrada.'];
        }
        
        if ($reservation['estado'] === 'cancelada') {
            return ['success' => false, 'message' => 'Esta reservación ya está cancelada.'];
        }
        
        if ($reservation['estado'] === 'completada') {
            return ['success' => false, 'message' => 'No se puede cancelar una reservación completada.'];
        }
        
        // Verificar que no sea una reservación pasada
        if ($reservation['fecha_reserva'] < date('Y-m-d')) {
            return ['success' => false, 'message' => 'No se puede cancelar una reservación pasada.'];
        }
        
        $result = $this->updateReservationStatus($reservationId, 'cancelada');
        
        return [
            'success' => $result > 0,
            'message' => $result > 0 ? 'Reservación cancelada exitosamente.' : 'Error al cancelar la reservación.'
        ];
    }

    /**
     * Modificar fecha y hora de una reservación de usuario
     */
    public function modifyUserReservation($reservationId, $userId, $newDate, $newTime)
    {
        // Verificar que la reservación pertenece al usuario
        $reservation = $this->connection->fetchOne(
            "SELECT r.*, m.id_mesa, rest.horario_apertura, rest.horario_cierre 
             FROM reservacion r 
             INNER JOIN mesa m ON r.id_mesa = m.id_mesa
             INNER JOIN restaurante rest ON r.id_restaurante = rest.id_restaurante
             WHERE r.id_reservacion = ? AND r.id_usuario = ?",
            [$reservationId, $userId]
        );
        
        if (!$reservation) {
            return ['success' => false, 'message' => 'Reservación no encontrada.'];
        }
        
        if ($reservation['estado'] === 'cancelada' || $reservation['estado'] === 'completada') {
            return ['success' => false, 'message' => 'No se puede modificar esta reservación.'];
        }
        
        // Verificar que la nueva fecha no sea pasada
        if ($newDate < date('Y-m-d')) {
            return ['success' => false, 'message' => 'No se puede reservar en una fecha pasada.'];
        }
        
        // Verificar disponibilidad de la mesa en la nueva fecha/hora
        $isAvailable = $this->checkTableAvailabilityForUpdate(
            $reservation['id_mesa'],
            $newDate,
            $newTime,
            $reservationId
        );
        
        if (!$isAvailable) {
            return ['success' => false, 'message' => 'La mesa no está disponible en esa fecha y hora.'];
        }
        
        // Actualizar la reservación
        $sql = "UPDATE reservacion SET fecha_reserva = ?, hora_reserva = ? WHERE id_reservacion = ?";
        $result = $this->connection->update($sql, [$newDate, $newTime, $reservationId]);
        
        return [
            'success' => $result > 0,
            'message' => $result > 0 ? 'Reservación modificada exitosamente.' : 'Error al modificar la reservación.'
        ];
    }

    /**
     * Contar reservaciones activas de un usuario
     */
    public function countActiveUserReservations($userId)
    {
        $sql = "SELECT COUNT(*) as total
                FROM reservacion r
                WHERE r.id_usuario = ?
                AND r.estado IN ('pendiente', 'confirmada')
                AND r.fecha_reserva >= CURDATE()";
        
        $result = $this->connection->fetchOne($sql, [$userId]);
        return $result['total'] ?? 0;
    }
}
?>