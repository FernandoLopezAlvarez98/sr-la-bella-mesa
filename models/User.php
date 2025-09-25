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
                1, // Rol Cliente por defecto
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
     * Obtiene todos los usuarios (para administración)
     */
    public function getAllUsers($limit = 50, $offset = 0)
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
     * Verifica si un correo ya está registrado
     */
    public function emailExists($correo)
    {
        $user = $this->getUserByEmail($correo);
        return $user !== null;
    }
}
?>