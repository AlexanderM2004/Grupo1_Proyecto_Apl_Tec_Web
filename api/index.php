<?php
require 'vendor/autoload.php';

use Dotenv\Dotenv;
use App\Routes\Router;
use App\Controllers\HomeController;
use App\Controllers\StatusController;
use App\Controllers\AuthController;
use App\Middleware\RateLimitMiddleware;
use App\Config\JWTConfig;

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Handle errors as JSON responses
set_error_handler(function($severity, $message, $file, $line) {
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

// Handle exceptions as JSON responses
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal Server Error'
    ]);
});

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize configurations
JWTConfig::init();

// Create router instance
$router = new Router();

// Add middleware
$router->addMiddleware(new RateLimitMiddleware());

// Define routes
$router->get('/', [HomeController::class, 'welcome']);
$router->get('/status', [StatusController::class, 'check']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/login', [AuthController::class, 'login']);

// Handle request
$router->resolve();