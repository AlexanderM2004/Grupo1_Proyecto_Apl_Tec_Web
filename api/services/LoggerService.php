<?php
namespace App\Services;

class LoggerService {
    private static $instance = null;
    private $logPath;
    
    private function __construct() {
        $this->logPath = __DIR__ . '/../logs/';
        
        if (!file_exists($this->logPath)) {
            mkdir($this->logPath, 0755, true);
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