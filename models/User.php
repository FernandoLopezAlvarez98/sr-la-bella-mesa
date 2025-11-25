<?php
require_once 'Connection.php';

class User
{
    private $connection;

    public function __construct()
    {
        $this->connection = Connection::getInstance()->getConnection();
    }

    /**
     * Obtiene un usuario por su email
     */
    public function getUserByEmail($correo)
    {
        try {
            $stmt = $this->connection->prepare("SELECT id_usuario, nombre, correo, telefono, password_hash, rol, fecha_registro FROM usuario WHERE correo = ? LIMIT 1");
            $stmt->execute([$correo]);
            
            $user = $stmt->fetch();
            return $user ? $user : null;
            
        } catch (PDOException $e) {
            error_log("Error al obtener usuario por email: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene un usuario por su ID
     */
    public function getUserById($id)
    {
        try {
            $stmt = $this->connection->prepare("SELECT id_usuario, nombre, correo, telefono, rol, fecha_registro FROM usuario WHERE id_usuario = ? LIMIT 1");
            $stmt->execute([$id]);
            
            $user = $stmt->fetch();
            return $user ? $user : null;
            
        } catch (PDOException $e) {
            error_log("Error al obtener usuario por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea un nuevo usuario con rol de cliente por defecto
     */
    public function createUser($nombre, $correo, $telefono, $password)
    {
        try {
            // Verificar si el correo ya existe
            if ($this->getUserByEmail($correo)) {
                return [
                    'success' => false,
                    'message' => 'El correo ya está registrado.'
                ];
            }

            // Generar ID único como en ejemplo_conexion.php
            $userId = uniqid('user_');
            
            // Hash de la contraseña
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insertar usuario con rol 1 (Cliente por defecto)
            $stmt = $this->connection->prepare(
                "INSERT INTO usuario (id_usuario, nombre, correo, telefono, password_hash, rol, fecha_registro) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            
            $result = $stmt->execute([
                $userId,
                $nombre,
                $correo,
                $telefono,
                $passwordHash,
                2, // Rol 2 = Usuario normal por defecto
                date('Y-m-d')
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Usuario creado exitosamente. Ya puedes iniciar sesión.',
                    'user_id' => $userId
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al crear el usuario.'
                ];
            }

        } catch (PDOException $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor.'
            ];
        }
    }

    /**
     * Verifica una contraseña contra su hash
     */
    public function verifyPassword($inputPassword, $hashedPassword)
    {
        return password_verify($inputPassword, $hashedPassword);
    }

    /**
     * Actualiza el perfil del usuario (nombre y teléfono)
     */
    public function updateUserProfile($userId, $nombre, $telefono)
    {
        try {
            $stmt = $this->connection->prepare("UPDATE usuario SET nombre = ?, telefono = ? WHERE id_usuario = ?");
            $result = $stmt->execute([$nombre, $telefono, $userId]);

            return [
                'success' => $result,
                'message' => $result ? 'Perfil actualizado exitosamente.' : 'Error al actualizar el perfil.'
            ];

        } catch (PDOException $e) {
            error_log("Error al actualizar perfil: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor.'
            ];
        }
    }

    /**
     * Actualiza el correo electrónico del usuario
     */
    public function updateUserEmail($userId, $newEmail)
    {
        try {
            // Verificar si el nuevo correo ya existe
            if ($this->emailExists($newEmail)) {
                return [
                    'success' => false,
                    'message' => 'Este correo electrónico ya está registrado.'
                ];
            }

            $stmt = $this->connection->prepare("UPDATE usuario SET correo = ? WHERE id_usuario = ?");
            $result = $stmt->execute([$newEmail, $userId]);

            return [
                'success' => $result,
                'message' => $result ? 'Correo actualizado exitosamente.' : 'Error al actualizar el correo.'
            ];

        } catch (PDOException $e) {
            error_log("Error al actualizar correo: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor.'
            ];
        }
    }

    /**
     * Actualiza la contraseña de un usuario
     */
    public function updatePassword($userId, $newPassword)
    {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $this->connection->prepare("UPDATE usuario SET password_hash = ? WHERE id_usuario = ?");
            $result = $stmt->execute([$passwordHash, $userId]);

            return [
                'success' => $result,
                'message' => $result ? 'Contraseña actualizada exitosamente.' : 'Error al actualizar la contraseña.'
            ];

        } catch (PDOException $e) {
            error_log("Error al actualizar contraseña: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor.'
            ];
        }
    }

    /**
     * Elimina un usuario
     */
    public function deleteUser($userId)
    {
        try {
            $stmt = $this->connection->prepare("DELETE FROM usuario WHERE id_usuario = ?");
            $result = $stmt->execute([$userId]);

            return [
                'success' => $result,
                'message' => $result ? 'Usuario eliminado exitosamente.' : 'Error al eliminar el usuario.'
            ];

        } catch (PDOException $e) {
            error_log("Error al eliminar usuario: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor.'
            ];
        }
    }

    /**
     * Obtiene todos los usuarios (para administración) - Versión básica
     */
    public function getAllUsersBasic($limit = 50, $offset = 0)
    {
        try {
            $stmt = $this->connection->prepare("SELECT id_usuario, nombre, correo, telefono, rol, fecha_registro FROM usuario ORDER BY fecha_registro DESC LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
            
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Error al obtener todos los usuarios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta el total de usuarios
     */
    public function countUsers()
    {
        try {
            $stmt = $this->connection->prepare("SELECT COUNT(*) as total FROM usuario");
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result['total'];

        } catch (PDOException $e) {
            error_log("Error al contar usuarios: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener todos los clientes (usuarios con rol de cliente)
     */
    public function getAllClients()
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT id_usuario, nombre, correo, telefono, fecha_registro 
                FROM usuario 
                WHERE rol = 2 
                ORDER BY nombre ASC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Error al obtener clientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener clientes con estadísticas de reservas
     */
    public function getClientsWithStats()
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    u.id_usuario, 
                    u.nombre, 
                    u.correo, 
                    u.telefono, 
                    u.fecha_registro,
                    COUNT(r.id_reservacion) as total_reservas,
                    MAX(r.fecha_reserva) as ultima_reserva
                FROM usuario u 
                LEFT JOIN reservacion r ON u.id_usuario = r.id_usuario
                WHERE u.rol = 2 
                GROUP BY u.id_usuario, u.nombre, u.correo, u.telefono, u.fecha_registro
                ORDER BY u.nombre ASC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Error al obtener clientes con estadísticas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear un nuevo cliente
     */
    public function createClient($data)
    {
        try {
            // Verificar si el correo ya existe
            if ($this->emailExists($data['correo'])) {
                return ['success' => false, 'message' => 'El correo electrónico ya está registrado'];
            }

            // Generar UUID para el nuevo cliente
            $id = $this->generateUUID();
            
            // Password temporal (se puede cambiar después)
            $tempPassword = bin2hex(random_bytes(8)); // Password temporal de 16 caracteres
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
            
            $stmt = $this->connection->prepare("
                INSERT INTO usuario (id_usuario, nombre, correo, telefono, password_hash, rol, fecha_registro) 
                VALUES (?, ?, ?, ?, ?, 2, CURDATE())
            ");
            
            $result = $stmt->execute([
                $id,
                $data['nombre'],
                $data['correo'],
                $data['telefono'],
                $hashedPassword
            ]);
            
            if ($result) {
                return [
                    'success' => true, 
                    'message' => 'Cliente creado exitosamente',
                    'id' => $id,
                    'temp_password' => $tempPassword
                ];
            } else {
                return ['success' => false, 'message' => 'Error al crear el cliente'];
            }

        } catch (PDOException $e) {
            error_log("Error al crear cliente: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }

    /**
     * Obtener estadísticas de clientes
     */
    public function getClientStats()
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    COUNT(*) as total_clients,
                    COUNT(CASE WHEN DATE(fecha_registro) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN 1 END) as new_clients_month,
                    COUNT(CASE WHEN EXISTS(
                        SELECT 1 FROM reservacion r 
                        WHERE r.id_usuario = usuario.id_usuario 
                        AND DATE(r.fecha_reserva) >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                    ) THEN 1 END) as active_clients,
                    ROUND(AVG(reservas_count.total), 1) as avg_reservations
                FROM usuario 
                LEFT JOIN (
                    SELECT id_usuario, COUNT(*) as total 
                    FROM reservacion 
                    GROUP BY id_usuario
                ) reservas_count ON usuario.id_usuario = reservas_count.id_usuario
                WHERE usuario.rol = 2
            ");
            $stmt->execute();
            
            return $stmt->fetch();

        } catch (PDOException $e) {
            error_log("Error al obtener estadísticas de clientes: " . $e->getMessage());
            return [
                'total_clients' => 0,
                'new_clients_month' => 0,
                'active_clients' => 0,
                'avg_reservations' => 0
            ];
        }
    }

    /**
     * Generar UUID para nuevos clientes
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
     * Verifica si un correo ya está registrado
     */
    public function emailExists($correo)
    {
        $user = $this->getUserByEmail($correo);
        return $user !== null;
    }

    // ==================== MÉTODOS PARA SUPER ADMIN ====================

    /**
     * Obtener todos los usuarios del sistema
     */
    public function getAllUsers()
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    u.id_usuario,
                    u.nombre,
                    u.correo,
                    u.telefono,
                    u.rol,
                    u.fecha_registro,
                    COUNT(DISTINCT r.id_reservacion) as total_reservaciones,
                    COUNT(DISTINCT rest.id_restaurante) as restaurantes_propios
                FROM usuario u
                LEFT JOIN reservacion r ON u.id_usuario = r.id_usuario
                LEFT JOIN restaurante rest ON u.id_usuario = rest.id_usuario
                GROUP BY u.id_usuario
                ORDER BY u.fecha_registro DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener todos los usuarios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear nuevo usuario (método para Super Admin)
     */
    public function create($data)
    {
        try {
            $id = $this->generateUUID();
            
            $stmt = $this->connection->prepare("
                INSERT INTO usuario (id_usuario, nombre, correo, telefono, password_hash, rol)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $id,
                $data['nombre'],
                $data['correo'],
                $data['telefono'] ?? null,
                $data['password'], // Ya debe venir hasheada
                $data['rol']
            ]);

            return $result ? $id : false;

        } catch (PDOException $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            throw new Exception("Error al crear usuario: " . $e->getMessage());
        }
    }

    /**
     * Obtener usuario por ID (alias para compatibilidad)
     */
    public function getById($id)
    {
        return $this->getUserById($id);
    }

    /**
     * Obtener total de usuarios
     */
    public function getTotalUsers()
    {
        try {
            $stmt = $this->connection->query("SELECT COUNT(*) FROM usuario");
            return (int)$stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("Error al contar usuarios: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener usuarios registrados recientemente
     */
    public function getRecentUsersCount($days = 7)
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT COUNT(*) 
                FROM usuario 
                WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            
            return (int)$stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("Error al contar usuarios recientes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Actualizar usuario
     */
    public function update($id, $data)
    {
        try {
            $fields = [];
            $values = [];
            
            if (isset($data['nombre'])) {
                $fields[] = "nombre = ?";
                $values[] = $data['nombre'];
            }
            
            if (isset($data['correo'])) {
                $fields[] = "correo = ?";
                $values[] = $data['correo'];
            }
            
            if (isset($data['telefono'])) {
                $fields[] = "telefono = ?";
                $values[] = $data['telefono'];
            }
            
            if (isset($data['rol'])) {
                $fields[] = "rol = ?";
                $values[] = $data['rol'];
            }
            
            if (isset($data['password'])) {
                $fields[] = "password_hash = ?";
                $values[] = $data['password'];
            }
            
            if (empty($fields)) {
                return true; // No hay nada que actualizar
            }
            
            $values[] = $id;
            
            $sql = "UPDATE usuario SET " . implode(', ', $fields) . " WHERE id_usuario = ?";
            $stmt = $this->connection->prepare($sql);
            
            return $stmt->execute($values);

        } catch (PDOException $e) {
            error_log("Error al actualizar usuario: " . $e->getMessage());
            throw new Exception("Error al actualizar usuario: " . $e->getMessage());
        }
    }

    /**
     * Eliminar usuario (solo Super Admin)
     */
    public function delete($id)
    {
        try {
            // Primero actualizar reservaciones para mantener integridad
            $stmt = $this->connection->prepare("
                UPDATE reservacion 
                SET id_usuario = NULL 
                WHERE id_usuario = ?
            ");
            $stmt->execute([$id]);

            // Luego eliminar el usuario
            $stmt = $this->connection->prepare("DELETE FROM usuario WHERE id_usuario = ?");
            return $stmt->execute([$id]);

        } catch (PDOException $e) {
            error_log("Error al eliminar usuario: " . $e->getMessage());
            throw new Exception("Error al eliminar usuario: " . $e->getMessage());
        }
    }

    /**
     * Obtener usuarios por rol
     */
    public function getUsersByRole($role)
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT id_usuario, nombre, correo, telefono, fecha_registro
                FROM usuario 
                WHERE rol = ?
                ORDER BY fecha_registro DESC
            ");
            $stmt->execute([$role]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener usuarios por rol: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cambiar rol de usuario
     */
    public function changeRole($userId, $newRole)
    {
        try {
            $stmt = $this->connection->prepare("
                UPDATE usuario 
                SET rol = ?
                WHERE id_usuario = ?
            ");
            
            return $stmt->execute([$newRole, $userId]);

        } catch (PDOException $e) {
            error_log("Error al cambiar rol de usuario: " . $e->getMessage());
            throw new Exception("Error al cambiar rol: " . $e->getMessage());
        }
    }
}
?>