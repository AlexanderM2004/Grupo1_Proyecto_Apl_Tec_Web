<?php
namespace App\Config;

use App\Services\LoggerService;

class Database {
    private static $instance = null;
    private $conn;
    private $logger;

    private function __construct() {
        $this->logger = LoggerService::getInstance();
        
        try {
            // Verificar extensiones instaladas
            $this->logger->info('Extensiones PHP instaladas:', [
                'extensions' => get_loaded_extensions()
            ]);
            
            if (!extension_loaded('pdo_pgsql')) {
                throw new \Exception('La extensión pdo_pgsql no está instalada');
            }

            // Verificar variables de entorno
            $requiredVars = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];
            foreach ($requiredVars as $var) {
                if (!isset($_ENV[$var])) {
                    throw new \Exception("Variable de entorno {$var} no está definida");
                }
            }

            $dsn = "pgsql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']};user={$_ENV['DB_USER']};password={$_ENV['DB_PASS']}";

            $this->logger->info('Intentando conexión a la base de datos', [
                'host' => $_ENV['DB_HOST'],
                'port' => $_ENV['DB_PORT'],
                'dbname' => $_ENV['DB_NAME'],
                'user' => $_ENV['DB_USER']
            ]);

            // Intentar conexión con opciones adicionales
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_TIMEOUT => 5
            ];

            $this->conn = new \PDO($dsn, null, null, $options);
            
            // Probar la conexión
            $this->conn->query('SELECT 1');
            
            $this->logger->info('Conexión a la base de datos establecida exitosamente');
            
        } catch(\PDOException $e) {
            $this->logger->error('Error de conexión a la base de datos PDO', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Error de conexión PDO: " . $e->getMessage());
        } catch(\Exception $e) {
            $this->logger->error('Error general de base de datos', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}