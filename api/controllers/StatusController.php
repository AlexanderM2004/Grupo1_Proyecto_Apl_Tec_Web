<?php
namespace App\Controllers;

use App\Config\Database;
use App\Config\JWTConfig;
use App\Services\LoggerService;

class StatusController {
    private $logger;

    public function __construct() {
        $this->logger = LoggerService::getInstance();
    }

    public function check() {
        $status = [
            'status' => 'success',
            'timestamp' => date('Y-m-d H:i:s'),
            'services' => [
                'api' => true,
                'db' => $this->checkDatabase(),
                'file' => $this->checkFileSystem(),
                'jwt' => $this->checkJWT(),
                'env' => $this->checkEnv()
            ]
        ];

        return $status;
    }

    private function checkDatabase(): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $db->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkFileSystem(): bool {
        
        $logPath = $this->logger->getLogPath();
        $testFile = $logPath . '/status_test.log';

        try {
            // Intentar escribir
            if (file_put_contents($testFile, 'status check: ' . date('Y-m-d H:i:s') . "\n") === false) {
                return false;
            }

            // Intentar leer
            if (file_get_contents($testFile) === false) {
                return false;
            }

            // Limpiar
            unlink($testFile);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkJWT(): bool {
        try {
            return !empty(JWTConfig::getSecret()) && 
                   !empty(JWTConfig::getExpiration());
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkEnv(): bool {
        $requiredVars = [
            'DB_HOST',
            'DB_PORT',
            'DB_NAME',
            'DB_USER',
            'DB_PASS',
            'JWT_SECRET',
            'JWT_EXPIRATION'
        ];

        foreach ($requiredVars as $var) {
            if (empty($_ENV[$var])) {
                return false;
            }
        }

        return true;
    }
}