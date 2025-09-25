<?php
require_once '../models/User.php';

class AuthController
{
    private $userModel;

    public function __construct()
    {
        // Iniciar sesión si no está activa
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->userModel = new User();
    }

    /**
     * Procesa el login de usuario
     */
    public function login($email, $password)
    {
        try {
            // Sanitizar inputs
            $email = $this->sanitizeInput($email);
            $password = $this->sanitizeInput($password);

            // Validar que los campos no estén vacíos
            if (empty($email) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'Por favor, complete todos los campos.'
                ];
            }

            // Validar formato de email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'El formato del email no es válido.'
                ];
            }

            // Validar credenciales
            $user = $this->validateCredentials($email, $password);
            
            if ($user) {
                // Iniciar sesión
                $this->startSession($user['id_usuario'], $user['correo']);
                
                return [
                    'success' => true,
                    'message' => 'Login exitoso.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Email o contraseña incorrectos.'
                ];
            }

        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor. Intente nuevamente.'
            ];
        }
    }

    /**
     * Valida las credenciales del usuario
     */
    private function validateCredentials($email, $password)
    {
        // Obtener usuario por email
        $user = $this->userModel->getUserByEmail($email);
        
        if (!$user) {
            return false;
        }

        // Verificar contraseña
        if ($this->userModel->verifyPassword($password, $user['password_hash'])) {
            return $user;
        }

        return false;
    }

    /**
     * Inicia la sesión del usuario
     */
    public function startSession($userId, $email)
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Regenerar ID de sesión para seguridad
        session_regenerate_id(true);
    }

    /**
     * Cierra la sesión del usuario
     */
    public function logout()
    {
        // Limpiar todas las variables de sesión
        $_SESSION = array();
        
        // Destruir la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'Sesión cerrada exitosamente.'
        ];
    }

    /**
     * Verifica si el usuario está autenticado
     */
    public function isAuthenticated()
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Sanitiza los datos de entrada
     */
    public function sanitizeInput($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    /**
     * Obtiene el ID del usuario actual
     */
    public function getCurrentUserId()
    {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }

    /**
     * Obtiene el email del usuario actual
     */
    public function getCurrentUserEmail()
    {
        return isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null;
    }

    public function getCurrentUserName()
    {
        $userId = $this->getCurrentUserId();
        if ($userId) {
            $user = $this->userModel->getUserById($userId);
            return $user ? $user['nombre'] : null;
        }
        return null;
    }

    /**
     * Registra un nuevo usuario en el sistema
     */
    public function register($nombre, $correo, $telefono, $password, $confirmPassword)
    {
        try {
            // Sanitizar inputs
            $nombre = $this->sanitizeInput($nombre);
            $correo = $this->sanitizeInput($correo);
            $telefono = $this->sanitizeInput($telefono);
            $password = $this->sanitizeInput($password);
            $confirmPassword = $this->sanitizeInput($confirmPassword);

            // Validaciones básicas
            if (empty($nombre) || empty($correo) || empty($telefono) || empty($password) || empty($confirmPassword)) {
                return [
                    'success' => false,
                    'message' => 'Por favor, complete todos los campos.'
                ];
            }

            // Validar formato de email
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'El formato del correo no es válido.'
                ];
            }

            // Validar que las contraseñas coincidan
            if ($password !== $confirmPassword) {
                return [
                    'success' => false,
                    'message' => 'Las contraseñas no coinciden.'
                ];
            }

            // Validar longitud de contraseña
            if (strlen($password) < 6) {
                return [
                    'success' => false,
                    'message' => 'La contraseña debe tener al menos 6 caracteres.'
                ];
            }

            // Validar teléfono (solo números)
            if (!preg_match('/^[0-9]{10}$/', $telefono)) {
                return [
                    'success' => false,
                    'message' => 'El teléfono debe tener 10 dígitos numéricos.'
                ];
            }

            // Validar nombre (solo letras y espacios)
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre)) {
                return [
                    'success' => false,
                    'message' => 'El nombre solo puede contener letras y espacios.'
                ];
            }

            // Verificar si el correo ya existe
            if ($this->userModel->emailExists($correo)) {
                return [
                    'success' => false,
                    'message' => 'Este correo electrónico ya está registrado.'
                ];
            }

            // Crear el usuario
            $result = $this->userModel->createUser($nombre, $correo, $telefono, $password);
            
            return $result;

        } catch (Exception $e) {
            error_log("Error en registro: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor. Intente nuevamente.'
            ];
        }
    }
}
?>