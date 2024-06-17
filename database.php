<?php
// Clase PDO wrapper para manejar la conexión y consultas PDO
//ini_set('display_errors', 0);
class PDOWrapper {
    private $pdo;
    private $errorLogPath = 'error.log'; // Ruta al archivo de log de errores
    private $actionLogFile = 'action.log';

    public function __construct() {
        // Read the configuration from the config.ini file
        $configPath = 'config.ini';
        if (file_exists($configPath)) {
            $config = parse_ini_file($configPath, true);
            if (isset($config['database'])) {
                $dbhost = $config['database']['host'];
                $dbname = $config['database']['database'];
                $dbuser = $config['database']['username'];
                $dbpass = $config['database']['password'];
                
                try {
                    $this->pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
                    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->pdo->exec("set names utf8");
                } catch (PDOException $e) {
                    $this->logError("Connection error: " . $e->getMessage());
                    throw new Exception('Internal server error');
                }
            } else {
                $this->logError('Database configuration section not found in config.ini');
                throw new Exception('Internal server error');
            }
        } else {
            $this->logError('config.ini file not found');
            throw new Exception('Internal server error');
        }
    }

    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Error al ejecutar la consulta: " . $e->getMessage() . ". SQL: " . $sql);
            throw new Exception("Error al ejecutar la consulta: " . $e->getMessage());
        }
    }

    public function exec($sql) {
        try {
            return $this->pdo->exec($sql);
        } catch (PDOException $e) {
            $this->logError("Error al ejecutar la consulta: " . $e->getMessage() . ". SQL: " . $sql);
            throw new Exception("Error al ejecutar la consulta: " . $e->getMessage());
        }
    }

    public function quote($value) {
        return $this->pdo->quote($value);
    }

    private function logError($message) {
        $date = date('Y-m-d H:i:s');
        $logMessage = "[$date] $message" . PHP_EOL;
        file_put_contents($this->errorLogPath, $logMessage, FILE_APPEND);
    }
}
?>