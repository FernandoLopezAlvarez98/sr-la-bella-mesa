<?php
require_once 'Connection.php';

class Table
{
    private $connection;

    public function __construct()
    {
        $this->connection = Connection::getInstance();
    }

    /**
     * Obtener todas las mesas con su estado actual
     */
    public function getTablesWithStatus($date = null)
    {
        if ($date === null) {
            $date = date('Y-m-d');
        }

        $sql = "SELECT 
                    m.id_mesa,
                    m.numero,
                    m.capacidad,
                    rest.nombre as restaurante_nombre,
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM reservacion r 
                            WHERE r.id_mesa = m.id_mesa 
                            AND DATE(r.fecha_reserva) = ? 
                            AND r.estado = 'check_in'
                            AND TIME(r.hora_reserva) <= CURTIME()
                            AND TIME(DATE_ADD(STR_TO_DATE(CONCAT(?, ' ', r.hora_reserva), '%Y-%m-%d %H:%i:%s'), INTERVAL 2 HOUR)) >= CURTIME()
                        ) THEN 'ocupada'
                        WHEN EXISTS (
                            SELECT 1 FROM reservacion r 
                            WHERE r.id_mesa = m.id_mesa 
                            AND DATE(r.fecha_reserva) = ? 
                            AND r.estado = 'confirmada'
                        ) THEN 'reservada'
                        ELSE 'libre'
                    END as estado,
                    (
                        SELECT r.hora_reserva 
                        FROM reservacion r 
                        WHERE r.id_mesa = m.id_mesa 
                        AND DATE(r.fecha_reserva) = ? 
                        AND r.estado IN ('confirmada', 'check_in')
                        ORDER BY r.hora_reserva ASC 
                        LIMIT 1
                    ) as proxima_reserva
                FROM mesa m
                INNER JOIN restaurante rest ON m.id_restaurante = rest.id_restaurante
                ORDER BY m.numero ASC";
        
        return $this->connection->fetchAll($sql, [$date, $date, $date, $date]);
    }

    /**
     * Obtener todas las mesas
     */
    public function getAllTables()
    {
        $sql = "SELECT 
                    m.id_mesa,
                    m.numero,
                    m.capacidad,
                    rest.nombre as restaurante_nombre
                FROM mesa m
                INNER JOIN restaurante rest ON m.id_restaurante = rest.id_restaurante
                ORDER BY m.numero ASC";
        
        return $this->connection->fetchAll($sql);
    }

    /**
     * Obtener mesas disponibles por capacidad y fecha/hora
     */
    public function getAvailableTables($date, $time, $capacity)
    {
        $sql = "SELECT 
                    m.id_mesa,
                    m.numero,
                    m.capacidad,
                    rest.nombre as restaurante_nombre
                FROM mesa m
                INNER JOIN restaurante rest ON m.id_restaurante = rest.id_restaurante
                WHERE m.capacidad >= ?
                AND NOT EXISTS (
                    SELECT 1 FROM reservacion r 
                    WHERE r.id_mesa = m.id_mesa 
                    AND DATE(r.fecha_reserva) = ? 
                    AND r.estado IN ('confirmada', 'check_in')
                    AND (
                        (TIME(r.hora_reserva) <= ? AND TIME(DATE_ADD(STR_TO_DATE(CONCAT(?, ' ', r.hora_reserva), '%Y-%m-%d %H:%i:%s'), INTERVAL 2 HOUR)) > ?)
                        OR
                        (TIME(r.hora_reserva) < TIME(DATE_ADD(STR_TO_DATE(CONCAT(?, ' ', ?), '%Y-%m-%d %H:%i:%s'), INTERVAL 2 HOUR)) AND TIME(r.hora_reserva) >= ?)
                    )
                )
                ORDER BY m.numero ASC";
        
        return $this->connection->fetchAll($sql, [$capacity, $date, $time, $date, $time, $date, $time, $time]);
    }

    /**
     * Obtener estadísticas de ocupación
     */
    public function getOccupancyStats($date = null)
    {
        if ($date === null) {
            $date = date('Y-m-d');
        }

        $sql = "SELECT 
                    COUNT(DISTINCT m.id_mesa) as total_mesas,
                    COUNT(DISTINCT CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM reservacion r 
                            WHERE r.id_mesa = m.id_mesa 
                            AND DATE(r.fecha_reserva) = ? 
                            AND r.estado IN ('confirmada', 'check_in')
                        ) THEN m.id_mesa 
                    END) as mesas_reservadas,
                    ROUND(
                        (COUNT(DISTINCT CASE 
                            WHEN EXISTS (
                                SELECT 1 FROM reservacion r 
                                WHERE r.id_mesa = m.id_mesa 
                                AND DATE(r.fecha_reserva) = ? 
                                AND r.estado IN ('confirmada', 'check_in')
                            ) THEN m.id_mesa 
                        END) * 100.0 / COUNT(DISTINCT m.id_mesa))
                    , 2) as porcentaje_ocupacion
                FROM mesa m";
        
        return $this->connection->fetchOne($sql, [$date, $date]);
    }

    /**
     * Obtener mesa por ID
     */
    public function getTableById($tableId)
    {
        $sql = "SELECT 
                    m.id_mesa,
                    m.numero,
                    m.capacidad,
                    rest.id_restaurante,
                    rest.nombre as restaurante_nombre
                FROM mesa m
                INNER JOIN restaurante rest ON m.id_restaurante = rest.id_restaurante
                WHERE m.id_mesa = ?";
        
        return $this->connection->fetchOne($sql, [$tableId]);
    }
}
?>