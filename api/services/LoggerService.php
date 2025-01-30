<?php
namespace App\Services;

class LoggerService {
    private static $instance = null;
    private $logPath;
    
    private function __construct() {
        // Asegurar que la ruta de logs esté en la raíz de /var/www/api/logs/
        $this->logPath = realpath(__DIR__ . '/..') . '/logs/';

        // Verificar si el directorio existe, si no, crearlo
        if (!is_dir($this->logPath)) {
            if (!mkdir($this->logPath, 0755, true) && !is_dir($this->logPath)) {
                throw new \RuntimeException("No se pudo crear el directorio de logs: " . $this->logPath);
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    public function debug($message, $context = []) {
        $this->log('DEBUG', $message, $context);
    }
    
    private function log($level, $message, $context = []) {
        $date = new \DateTime();
        $logFile = $this->logPath . $date->format('Y-m-d') . '.log';

        // Asegurar que el directorio existe antes de escribir en el log
        if (!is_dir($this->logPath)) {
            if (!mkdir($this->logPath, 0755, true) && !is_dir($this->logPath)) {
                throw new \RuntimeException("No se pudo crear el directorio de logs: " . $this->logPath);
            }
        }
        
        $logEntry = sprintf(
            "[%s] %s: %s %s\n",
            $date->format('Y-m-d H:i:s'),
            $level,
            $message,
            !empty($context) ? json_encode($context) : ''
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public function getLogPath() {
        return $this->logPath;
    }
    
    public function clearLogs() {
        $files = glob($this->logPath . '*.log');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    public function rotateLogs($daysToKeep = 30) {
        $files = glob($this->logPath . '*.log');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 60 * 60 * 24 * $daysToKeep) {
                    unlink($file);
                }
            }
        }
    }
}
