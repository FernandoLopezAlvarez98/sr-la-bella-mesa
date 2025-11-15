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
        $sql = "UPDATE reservacion 
                SET estado = ?, fecha_actualizacion = CURRENT_TIMESTAMP 
                WHERE id_reservacion = ?";
        
        return $this->connection->update($sql, [$newStatus, $reservationId]);
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
        $sql = "SELECT COUNT(*) as conflictos
                FROM reservacion r
                WHERE r.id_mesa = ?
                AND DATE(r.fecha_reserva) = ?
                AND r.estado IN ('confirmada', 'check_in')
                AND (
                    (TIME(r.hora_reserva) <= ? AND TIME(DATE_ADD(STR_TO_DATE(CONCAT(?, ' ', r.hora_reserva), '%Y-%m-%d %H:%i:%s'), INTERVAL 2 HOUR)) > ?)
                    OR
                    (TIME(r.hora_reserva) < TIME(DATE_ADD(STR_TO_DATE(CONCAT(?, ' ', ?), '%Y-%m-%d %H:%i:%s'), INTERVAL 2 HOUR)) AND TIME(r.hora_reserva) >= ?)
                )";
        
        $result = $this->connection->fetchOne($sql, [$tableId, $date, $time, $date, $time, $date, $time, $time]);
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
}
?>