<?php
namespace App\Controllers;

use App\Config\Database;
use App\Config\JWTConfig;
use App\Services\LoggerService;

class StatusController {
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
        $testFile = __DIR__ . '/../storage/test.txt';
        $testDir = dirname($testFile);

        try {
            // Verificar si el directorio existe o crearlo
            if (!file_exists($testDir)) {
                mkdir($testDir, 0755, true);
            }

            // Intentar escribir
            if (file_put_contents($testFile, 'test') === false) {
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

    public function diagnostics() {
        return [
            'status' => 'running',
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'],
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'log_path' => LoggerService::getInstance()->getLogPath(),
            'log_writable' => is_writable(LoggerService::getInstance()->getLogPath()),
            'docker' => [
                'running' => shell_exec('docker ps'),
                'compose_version' => shell_exec('docker compose version')
            ]
        ];
    }
}