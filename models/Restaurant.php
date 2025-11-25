<?php
require_once 'Connection.php';

class Restaurant
{
    private $connection;

    public function __construct()
    {
        $this->connection = Connection::getInstance()->getConnection();
    }

    /**
     * Obtiene todos los restaurantes activos
     */
    public function getAllRestaurants($limit = 50, $offset = 0)
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    r.id_restaurante,
                    r.nombre,
                    r.direccion,
                    r.telefono,
                    r.tipo_cocina,
                    r.horario_apertura,
                    r.horario_cierre,
                    r.capacidad_total,
                    r.foto_portada,
                    r.calificacion,
                    r.tiempo_espera,
                    r.precio_rango,
                    r.promocion,
                    r.activo,
                    r.fecha_registro,
                    CASE 
                        WHEN CURTIME() BETWEEN r.horario_apertura AND r.horario_cierre THEN 1
                        WHEN r.horario_cierre < r.horario_apertura AND (CURTIME() >= r.horario_apertura OR CURTIME() <= r.horario_cierre) THEN 1
                        ELSE 0
                    END as abierto
                FROM restaurante r
                WHERE r.activo = 1
                ORDER BY r.calificacion DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Error al obtener restaurantes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene un restaurante por su ID
     */
    public function getRestaurantById($id)
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    r.*,
                    CASE 
                        WHEN CURTIME() BETWEEN r.horario_apertura AND r.horario_cierre THEN 1
                        WHEN r.horario_cierre < r.horario_apertura AND (CURTIME() >= r.horario_apertura OR CURTIME() <= r.horario_cierre) THEN 1
                        ELSE 0
                    END as abierto
                FROM restaurante r
                WHERE r.id_restaurante = ?
            ");
            $stmt->execute([$id]);
            
            return $stmt->fetch();

        } catch (PDOException $e) {
            error_log("Error al obtener restaurante: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene restaurantes por tipo de cocina
     */
    public function getRestaurantsByCategory($tipoCocina, $limit = 20)
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    r.*,
                    CASE 
                        WHEN CURTIME() BETWEEN r.horario_apertura AND r.horario_cierre THEN 1
                        WHEN r.horario_cierre < r.horario_apertura AND (CURTIME() >= r.horario_apertura OR CURTIME() <= r.horario_cierre) THEN 1
                        ELSE 0
                    END as abierto
                FROM restaurante r
                WHERE r.tipo_cocina LIKE ? AND r.activo = 1
                ORDER BY r.calificacion DESC
                LIMIT ?
            ");
            $stmt->execute(['%' . $tipoCocina . '%', $limit]);
            
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Error al obtener restaurantes por categoría: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca restaurantes por nombre o tipo de cocina
     */
    public function searchRestaurants($query, $limit = 20)
    {
        try {
            $searchTerm = '%' . $query . '%';
            $stmt = $this->connection->prepare("
                SELECT 
                    r.*,
                    CASE 
                        WHEN CURTIME() BETWEEN r.horario_apertura AND r.horario_cierre THEN 1
                        WHEN r.horario_cierre < r.horario_apertura AND (CURTIME() >= r.horario_apertura OR CURTIME() <= r.horario_cierre) THEN 1
                        ELSE 0
                    END as abierto
                FROM restaurante r
                WHERE (r.nombre LIKE ? OR r.tipo_cocina LIKE ? OR r.direccion LIKE ?) AND r.activo = 1
                ORDER BY r.calificacion DESC
                LIMIT ?
            ");
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
            
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Error al buscar restaurantes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene restaurantes destacados (mejor calificación)
     */
    public function getFeaturedRestaurants($limit = 4)
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    r.*,
                    CASE 
                        WHEN CURTIME() BETWEEN r.horario_apertura AND r.horario_cierre THEN 1
                        WHEN r.horario_cierre < r.horario_apertura AND (CURTIME() >= r.horario_apertura OR CURTIME() <= r.horario_cierre) THEN 1
                        ELSE 0
                    END as abierto
                FROM restaurante r
                WHERE r.activo = 1
                ORDER BY r.calificacion DESC, r.fecha_registro DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Error al obtener restaurantes destacados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene restaurantes con promociones activas
     */
    public function getRestaurantsWithPromos($limit = 10)
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    r.*,
                    CASE 
                        WHEN CURTIME() BETWEEN r.horario_apertura AND r.horario_cierre THEN 1
                        WHEN r.horario_cierre < r.horario_apertura AND (CURTIME() >= r.horario_apertura OR CURTIME() <= r.horario_cierre) THEN 1
                        ELSE 0
                    END as abierto
                FROM restaurante r
                WHERE r.promocion IS NOT NULL AND r.promocion != '' AND r.activo = 1
                ORDER BY r.calificacion DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Error al obtener restaurantes con promociones: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta el total de restaurantes activos
     */
    public function countActiveRestaurants()
    {
        try {
            $stmt = $this->connection->query("SELECT COUNT(*) FROM restaurante WHERE activo = 1");
            return $stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("Error al contar restaurantes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene las categorías únicas de cocina
     */
    public function getUniqueCategories()
    {
        try {
            $stmt = $this->connection->query("
                SELECT DISTINCT tipo_cocina 
                FROM restaurante 
                WHERE activo = 1 AND tipo_cocina IS NOT NULL
                ORDER BY tipo_cocina
            ");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            error_log("Error al obtener categorías: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crea un nuevo restaurante
     */
    public function createRestaurant($data)
    {
        try {
            $id = 'rest_' . uniqid();
            
            $stmt = $this->connection->prepare("
                INSERT INTO restaurante (
                    id_restaurante, id_usuario, nombre, direccion, telefono, 
                    tipo_cocina, horario_apertura, horario_cierre, capacidad_total, 
                    foto_portada, calificacion, tiempo_espera, precio_rango, promocion, activo
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            $result = $stmt->execute([
                $id,
                $data['id_usuario'],
                $data['nombre'],
                $data['direccion'],
                $data['telefono'],
                $data['tipo_cocina'],
                $data['horario_apertura'],
                $data['horario_cierre'],
                $data['capacidad_total'],
                $data['foto_portada'],
                $data['calificacion'] ?? 4.0,
                $data['tiempo_espera'] ?? '25-35',
                $data['precio_rango'] ?? '$$',
                $data['promocion'] ?? null
            ]);

            return [
                'success' => $result,
                'message' => $result ? 'Restaurante creado exitosamente.' : 'Error al crear el restaurante.',
                'id' => $result ? $id : null
            ];

        } catch (PDOException $e) {
            error_log("Error al crear restaurante: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor.'
            ];
        }
    }

    /**
     * Actualiza un restaurante
     */
    public function updateRestaurant($id, $data)
    {
        try {
            $stmt = $this->connection->prepare("
                UPDATE restaurante SET
                    nombre = ?,
                    direccion = ?,
                    telefono = ?,
                    tipo_cocina = ?,
                    horario_apertura = ?,
                    horario_cierre = ?,
                    capacidad_total = ?,
                    foto_portada = ?,
                    calificacion = ?,
                    tiempo_espera = ?,
                    precio_rango = ?,
                    promocion = ?
                WHERE id_restaurante = ?
            ");
            
            $result = $stmt->execute([
                $data['nombre'],
                $data['direccion'],
                $data['telefono'],
                $data['tipo_cocina'],
                $data['horario_apertura'],
                $data['horario_cierre'],
                $data['capacidad_total'],
                $data['foto_portada'],
                $data['calificacion'],
                $data['tiempo_espera'],
                $data['precio_rango'],
                $data['promocion'],
                $id
            ]);

            return [
                'success' => $result,
                'message' => $result ? 'Restaurante actualizado exitosamente.' : 'Error al actualizar el restaurante.'
            ];

        } catch (PDOException $e) {
            error_log("Error al actualizar restaurante: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor.'
            ];
        }
    }

    // ==================== MÉTODOS PARA SUPER ADMIN ====================

    /**
     * Obtiene todos los restaurantes (activos e inactivos) con estadísticas
     */
    public function getAllRestaurantsWithStats()
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    r.id_restaurante,
                    r.nombre,
                    r.direccion,
                    r.telefono,
                    r.tipo_cocina,
                    r.horario_apertura,
                    r.horario_cierre,
                    r.capacidad_total,
                    r.foto_portada,
                    r.calificacion,
                    r.activo,
                    r.fecha_registro,
                    u.nombre as propietario_nombre,
                    u.correo as propietario_email,
                    COUNT(DISTINCT res.id_reservacion) as total_reservaciones,
                    COUNT(DISTINCT m.id_mesa) as total_mesas,
                    CASE 
                        WHEN CURTIME() BETWEEN r.horario_apertura AND r.horario_cierre THEN 1
                        WHEN r.horario_cierre < r.horario_apertura AND (CURTIME() >= r.horario_apertura OR CURTIME() <= r.horario_cierre) THEN 1
                        ELSE 0
                    END as abierto_ahora
                FROM restaurante r
                LEFT JOIN usuario u ON r.id_usuario = u.id_usuario
                LEFT JOIN reservacion res ON r.id_restaurante = res.id_restaurante
                LEFT JOIN mesa m ON r.id_restaurante = m.id_restaurante
                GROUP BY r.id_restaurante
                ORDER BY r.fecha_registro DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener restaurantes con estadísticas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear restaurante (método simplificado para Super Admin)
     */
    public function create($data)
    {
        try {
            $id = 'rest_' . uniqid();
            
            $stmt = $this->connection->prepare("
                INSERT INTO restaurante (
                    id_restaurante, nombre, direccion, telefono, 
                    tipo_cocina, horario_apertura, horario_cierre, capacidad_total, 
                    foto_portada, activo
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $id,
                $data['nombre'],
                $data['direccion'],
                $data['telefono'],
                $data['tipo_cocina'],
                $data['horario_apertura'],
                $data['horario_cierre'],
                $data['capacidad_total'],
                $data['foto_portada'],
                $data['activo']
            ]);

            return $result ? $id : false;

        } catch (PDOException $e) {
            error_log("Error al crear restaurante: " . $e->getMessage());
            throw new Exception("Error al crear restaurante: " . $e->getMessage());
        }
    }

    /**
     * Crear restaurante con propietario asignado (para Super Admin)
     */
    public function createWithOwner($data)
    {
        try {
            $id = 'rest_' . uniqid();
            
            $stmt = $this->connection->prepare("
                INSERT INTO restaurante (
                    id_restaurante, id_usuario, nombre, direccion, telefono, 
                    tipo_cocina, horario_apertura, horario_cierre, capacidad_total, 
                    foto_portada, activo
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $id,
                $data['id_usuario'],
                $data['nombre'],
                $data['direccion'],
                $data['telefono'],
                $data['tipo_cocina'],
                $data['horario_apertura'],
                $data['horario_cierre'],
                $data['capacidad_total'],
                $data['foto_portada'],
                $data['activo']
            ]);

            return $result ? $id : false;

        } catch (PDOException $e) {
            error_log("Error al crear restaurante con propietario: " . $e->getMessage());
            throw new Exception("Error al crear restaurante: " . $e->getMessage());
        }
    }

    /**
     * Actualizar estado del restaurante
     */
    public function updateStatus($id, $status)
    {
        try {
            $stmt = $this->connection->prepare("
                UPDATE restaurante 
                SET activo = ?
                WHERE id_restaurante = ?
            ");
            
            return $stmt->execute([$status, $id]);

        } catch (PDOException $e) {
            error_log("Error al actualizar estado del restaurante: " . $e->getMessage());
            throw new Exception("Error al actualizar estado: " . $e->getMessage());
        }
    }

    /**
     * Obtener total de restaurantes
     */
    public function getTotalRestaurants()
    {
        try {
            $stmt = $this->connection->query("SELECT COUNT(*) FROM restaurante");
            return (int)$stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("Error al contar restaurantes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener restaurantes activos
     */
    public function getActiveRestaurantsCount()
    {
        try {
            $stmt = $this->connection->query("SELECT COUNT(*) FROM restaurante WHERE activo = 1");
            return (int)$stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("Error al contar restaurantes activos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener restaurantes más populares por reservaciones
     */
    public function getPopularRestaurants($limit = 10)
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    r.id_restaurante,
                    r.nombre,
                    r.tipo_cocina,
                    r.calificacion,
                    COUNT(res.id_reservacion) as total_reservaciones,
                    AVG(CASE WHEN res.calificacion_cliente IS NOT NULL THEN res.calificacion_cliente END) as rating_promedio
                FROM restaurante r
                LEFT JOIN reservacion res ON r.id_restaurante = res.id_restaurante
                WHERE r.activo = 1
                GROUP BY r.id_restaurante
                ORDER BY total_reservaciones DESC, r.calificacion DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener restaurantes populares: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Eliminar restaurante (solo Super Admin)
     */
    public function delete($id)
    {
        try {
            // Primero eliminar reservaciones asociadas
            $stmt = $this->connection->prepare("DELETE FROM reservacion WHERE id_restaurante = ?");
            $stmt->execute([$id]);

            // Luego eliminar mesas asociadas
            $stmt = $this->connection->prepare("DELETE FROM mesa WHERE id_restaurante = ?");
            $stmt->execute([$id]);

            // Finalmente eliminar el restaurante
            $stmt = $this->connection->prepare("DELETE FROM restaurante WHERE id_restaurante = ?");
            return $stmt->execute([$id]);

        } catch (PDOException $e) {
            error_log("Error al eliminar restaurante: " . $e->getMessage());
            throw new Exception("Error al eliminar restaurante: " . $e->getMessage());
        }
    }

    /**
     * Obtener restaurante(s) por ID de usuario (administrador)
     * Retorna el primer restaurante asociado al usuario
     */
    public function getRestaurantByUserId($userId)
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    r.id_restaurante,
                    r.nombre,
                    r.direccion,
                    r.telefono,
                    r.tipo_cocina,
                    r.horario_apertura,
                    r.horario_cierre,
                    r.capacidad_total,
                    r.foto_portada,
                    r.calificacion,
                    r.activo,
                    r.fecha_registro
                FROM restaurante r
                WHERE r.id_usuario = ?
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener restaurante por usuario: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los restaurantes de un usuario
     */
    public function getRestaurantsByUserId($userId)
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    r.id_restaurante,
                    r.nombre,
                    r.direccion,
                    r.telefono,
                    r.tipo_cocina,
                    r.horario_apertura,
                    r.horario_cierre,
                    r.capacidad_total,
                    r.foto_portada,
                    r.calificacion,
                    r.activo,
                    r.fecha_registro
                FROM restaurante r
                WHERE r.id_usuario = ?
                ORDER BY r.fecha_registro DESC
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener restaurantes por usuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener los restaurantes más visitados (con más reservaciones)
     */
    public function getMostVisitedRestaurants($limit = 3)
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    r.id_restaurante,
                    r.nombre,
                    r.direccion,
                    r.telefono,
                    r.tipo_cocina,
                    r.horario_apertura,
                    r.horario_cierre,
                    r.capacidad_total,
                    r.foto_portada,
                    r.calificacion,
                    r.tiempo_espera,
                    r.precio_rango,
                    r.promocion,
                    r.activo,
                    COUNT(res.id_reservacion) as total_reservaciones,
                    CASE 
                        WHEN CURTIME() BETWEEN r.horario_apertura AND r.horario_cierre THEN 1
                        WHEN r.horario_cierre < r.horario_apertura AND (CURTIME() >= r.horario_apertura OR CURTIME() <= r.horario_cierre) THEN 1
                        ELSE 0
                    END as abierto
                FROM restaurante r
                LEFT JOIN reservacion res ON r.id_restaurante = res.id_restaurante
                WHERE r.activo = 1
                GROUP BY r.id_restaurante
                ORDER BY total_reservaciones DESC, r.calificacion DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener restaurantes más visitados: " . $e->getMessage());
            return [];
        }
    }
}
?>
