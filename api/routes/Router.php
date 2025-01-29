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

    public function addRoute($path, $handler) {
        $this->routes['POST'][$path] = $handler;
    }

    public function resolve() {
        // Set JSON headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        $method = $_SERVER['REQUEST_METHOD'];
        
        // Handle preflight requests
        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        // Get the path without /api prefix
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = preg_replace('/^\/api/', '', $path);
        
        // Execute middleware
        foreach ($this->middleware as $middleware) {
            $middleware->handle();
        }

        try {
            if (isset($this->routes[$method][$path])) {
                $handler = $this->routes[$method][$path];
                
                if (is_array($handler)) {
                    $controller = new $handler[0]();
                    $method = $handler[1];
                    $response = $controller->$method();
                } else {
                    $response = $handler();
                }

                echo json_encode($response);
                return;
            }

            // Route not found
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Route not found'
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal Server Error',
                'error' => $e->getMessage()
            ]);
        }
    }
}