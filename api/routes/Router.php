<?php
namespace App\Routes;

class Router {
    private $routes = [];
    private $middleware = [];

    public function get($path, $handler) {
        $this->routes['GET'][$path] = $handler;
    }

    public function post($path, $handler) {
        $this->routes['POST'][$path] = $handler;
    }

    public function addMiddleware($middleware) {
        $this->middleware[] = $middleware;
    }

    public function resolve() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Manejar solicitudes CORS preflight
        if ($method === 'OPTIONS') {
            return [
                'status' => 'success',
                'message' => 'OK'
            ];
        }

        // Obtener la ruta sin el prefijo /api
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = preg_replace('/^\/api/', '', $path);
        
        // Ejecutar middleware
        foreach ($this->middleware as $middleware) {
            $middleware->handle();
        }

        // Verificar si la ruta existe
        if (!isset($this->routes[$method][$path])) {
            return [
                'status' => 'error',
                'message' => 'Route not found',
                'code' => 404
            ];
        }

        try {
            $handler = $this->routes[$method][$path];
            
            if (is_array($handler)) {
                $controller = new $handler[0]();
                $method = $handler[1];
                return $controller->$method();
            }
            
            return $handler();
            
        } catch (\Exception $e) {
            throw $e; // Dejar que el manejador global de excepciones lo procese
        }
    }
}