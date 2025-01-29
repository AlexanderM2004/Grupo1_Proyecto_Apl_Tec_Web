<?php
namespace App\Middleware;

class RateLimitMiddleware {
    private $redis;

    public function handle() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = "rate_limit:$ip";
        
        // En una implementación real, usarías Redis o una base de datos
        // Este es un ejemplo simple usando archivos
        $requestCount = $this->getRequestCount($key);
        
        if ($requestCount > $_ENV['RATE_LIMIT']) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            exit;
        }
        
        $this->incrementRequestCount($key);
    }

    private function getRequestCount($key) {
        $file = sys_get_temp_dir() . "/$key";
        if (!file_exists($file)) return 0;
        return (int)file_get_contents($file);
    }

    private function incrementRequestCount($key) {
        $file = sys_get_temp_dir() . "/$key";
        $count = $this->getRequestCount($key) + 1;
        file_put_contents($file, $count);
    }
}