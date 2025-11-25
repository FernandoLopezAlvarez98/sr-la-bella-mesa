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

    /**
     * Crear una nueva mesa
     */
    public function createTable($data)
    {
        $id = $this->generateUUID();
        
        error_log("=== CREANDO MESA ===");
        error_log("Datos: " . print_r($data, true));
        
        // Verificar que no exista una mesa con el mismo número en el restaurante
        $existingTable = $this->getTableByNumber($data['numero'], $data['id_restaurante']);
        if ($existingTable) {
            error_log("ERROR: Ya existe una mesa con ese número en el restaurante");
            return ['success' => false, 'message' => 'Ya existe una mesa con el número ' . $data['numero']];
        }
        
        $sql = "INSERT INTO mesa (id_mesa, id_restaurante, numero, capacidad) 
                VALUES (?, ?, ?, ?)";
        
        try {
            $this->connection->insert($sql, [
                $id,
                $data['id_restaurante'],
                $data['numero'],
                $data['capacidad']
            ]);
            
            error_log("Mesa creada exitosamente con ID: " . $id);
            return ['success' => true, 'message' => 'Mesa creada exitosamente', 'id' => $id];
        } catch (Exception $e) {
            error_log("ERROR al crear mesa: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al crear mesa: ' . $e->getMessage()];
        }
    }

    /**
     * Actualizar una mesa existente
     */
    public function updateTable($tableId, $data)
    {
        error_log("=== ACTUALIZANDO MESA ===");
        error_log("ID: " . $tableId);
        error_log("Datos: " . print_r($data, true));
        
        // Verificar que la mesa existe
        $existingTable = $this->getTableById($tableId);
        if (!$existingTable) {
            error_log("ERROR: Mesa no encontrada");
            return ['success' => false, 'message' => 'Mesa no encontrada'];
        }
        
        // Si se está cambiando el número, verificar que no exista otra mesa con ese número
        if (isset($data['numero']) && $data['numero'] != $existingTable['numero']) {
            $tableWithNumber = $this->getTableByNumber($data['numero'], $existingTable['id_restaurante']);
            if ($tableWithNumber && $tableWithNumber['id_mesa'] != $tableId) {
                error_log("ERROR: Ya existe otra mesa con ese número");
                return ['success' => false, 'message' => 'Ya existe otra mesa con el número ' . $data['numero']];
            }
        }
        
        $updates = [];
        $params = [];
        
        if (isset($data['numero'])) {
            $updates[] = "numero = ?";
            $params[] = $data['numero'];
        }
        
        if (isset($data['capacidad'])) {
            $updates[] = "capacidad = ?";
            $params[] = $data['capacidad'];
        }
        
        if (empty($updates)) {
            return ['success' => false, 'message' => 'No hay datos para actualizar'];
        }
        
        $sql = "UPDATE mesa SET " . implode(", ", $updates) . " WHERE id_mesa = ?";
        $params[] = $tableId;
        
        try {
            $result = $this->connection->update($sql, $params);
            error_log("Filas afectadas: " . $result);
            return ['success' => true, 'message' => 'Mesa actualizada exitosamente'];
        } catch (Exception $e) {
            error_log("ERROR al actualizar mesa: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al actualizar mesa: ' . $e->getMessage()];
        }
    }

    /**
     * Eliminar una mesa
     */
    public function deleteTable($tableId)
    {
        error_log("=== ELIMINANDO MESA ===");
        error_log("ID: " . $tableId);
        
        // Verificar que la mesa existe
        $existingTable = $this->getTableById($tableId);
        if (!$existingTable) {
            error_log("ERROR: Mesa no encontrada");
            return ['success' => false, 'message' => 'Mesa no encontrada'];
        }
        
        // Verificar si hay reservas activas para esta mesa
        $hasActiveReservations = $this->hasActiveReservations($tableId);
        if ($hasActiveReservations) {
            error_log("ERROR: La mesa tiene reservas activas");
            return ['success' => false, 'message' => 'No se puede eliminar la mesa porque tiene reservas activas o futuras'];
        }
        
        try {
            // Eliminar reservas antiguas primero (si las hay)
            $sqlDeleteOldReservations = "DELETE FROM reservacion WHERE id_mesa = ? AND (estado IN ('completada', 'cancelada', 'noshow') OR fecha_reserva < CURDATE())";
            $this->connection->delete($sqlDeleteOldReservations, [$tableId]);
            
            // Eliminar la mesa
            $sql = "DELETE FROM mesa WHERE id_mesa = ?";
            $result = $this->connection->delete($sql, [$tableId]);
            
            error_log("Mesa eliminada. Filas afectadas: " . $result);
            return ['success' => true, 'message' => 'Mesa eliminada exitosamente'];
        } catch (Exception $e) {
            error_log("ERROR al eliminar mesa: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al eliminar mesa: ' . $e->getMessage()];
        }
    }

    /**
     * Obtener mesa por número y restaurante
     */
    public function getTableByNumber($numero, $restauranteId)
    {
        $sql = "SELECT * FROM mesa WHERE numero = ? AND id_restaurante = ?";
        return $this->connection->fetchOne($sql, [$numero, $restauranteId]);
    }

    /**
     * Verificar si una mesa tiene reservas activas
     */
    public function hasActiveReservations($tableId)
    {
        $sql = "SELECT COUNT(*) as count FROM reservacion 
                WHERE id_mesa = ? 
                AND estado IN ('pendiente', 'confirmada', 'check_in')
                AND fecha_reserva >= CURDATE()";
        $result = $this->connection->fetchOne($sql, [$tableId]);
        return $result['count'] > 0;
    }

    /**
     * Obtener estadísticas de mesas
     */
    public function getTablesStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total_mesas,
                    SUM(capacidad) as capacidad_total,
                    AVG(capacidad) as capacidad_promedio,
                    MIN(capacidad) as capacidad_minima,
                    MAX(capacidad) as capacidad_maxima
                FROM mesa";
        return $this->connection->fetchOne($sql);
    }

    /**
     * Generar UUID
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