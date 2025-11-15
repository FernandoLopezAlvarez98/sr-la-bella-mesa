<?php
/**
 * Clase Connection - Conexión a base de datos Azure MySQL
 * Sistema La Bella Mesa
 */
// prueba de cabio en el repositorio

class Connection {
    private static $instance = null;
    private $pdo;
    
    // Configuración de base de datos (se cargan desde .env)
    private $host;
    private $database;
    private $username;
    private $password;
    private $port;
    
    // Configuración de base de datos (usar .env en producción)

    private function __construct() {
        $this->connect();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        try {
            // Cargar variables de entorno si existe archivo .env
            $this->loadEnvVariables();
            
            // Configuración SSL para Azure MySQL
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                PDO::MYSQL_ATTR_SSL_CA => null
            ];

            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset=utf8mb4";
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
            //echo " Conexión exitosa a Azure MySQL\n";
            
        } catch (PDOException $e) {
            //echo " Error de conexión: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    private function loadEnvVariables() {
        $envFile = dirname(__DIR__) . '/.env';
        if (!file_exists($envFile)) {
            throw new Exception("Archivo .env no encontrado en: " . $envFile);
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                
                switch ($key) {
                    case 'HOST':
                        $this->host = $value;
                        break;
                    case 'DATABASE':
                        $this->database = $value;
                        break;
                    case 'USER':
                        $this->username = $value;
                        break;
                    case 'PASSWORD':
                        $this->password = $value;
                        break;
                    case 'PORT':
                        $this->port = (int)$value;
                        break;
                }
            }
        }
        
        // Validar que todas las variables requeridas estén presentes
        if (empty($this->host)) {
            throw new Exception("Variable HOST no encontrada en .env");
        }
        if (empty($this->database)) {
            throw new Exception("Variable DATABASE no encontrada en .env");
        }
        if (empty($this->username)) {
            throw new Exception("Variable USER no encontrada en .env");
        }
        if (empty($this->port)) {
            throw new Exception("Variable PORT no encontrada en .env");
        }
        // Nota: PASSWORD puede estar vacía, es válido para localhost
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            //echo "Error en consulta: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function insert($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    public function update($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function delete($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        return $this->pdo->rollback();
    }

    // Prevenir clonación y deserialización
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
