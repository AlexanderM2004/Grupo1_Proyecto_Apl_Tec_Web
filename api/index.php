<?php
// Deshabilitar la salida del buffer
ob_start();

// Require bootstrap
require_once __DIR__ . '/bootstrap.php';

use Dotenv\Dotenv;
use App\Routes\Router;
use App\Controllers\HomeController;
use App\Controllers\StatusController;
use App\Controllers\AuthController;
use App\Middleware\RateLimitMiddleware;
use App\Config\JWTConfig;
use App\Services\LoggerService;

// Configuración inicial
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Asegurar headers JSON desde el inicio
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Inicializar logger
$logger = LoggerService::getInstance();

// Función para determinar si se deben mostrar detalles del error
function shouldShowErrorDetails() {
    return isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true';
}

// Función para obtener detalles del error
function getErrorDetails($e) {
    if (shouldShowErrorDetails()) {
        return [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString())
        ];
    }
    return null;
}

// Función para enviar respuesta JSON limpia
function sendJsonResponse($data, $statusCode = 200) {
    if (ob_get_length()) ob_clean();
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Manejador de errores personalizado
set_error_handler(function($severity, $message, $file, $line) use ($logger) {
    // Generar ID único para el error
    $errorId = uniqid('ERR_');
    
    // Registrar error detallado en el log
    $logger->error("Error ID: {$errorId} - {$message}", [
        'error_id' => $errorId,
        'severity' => $severity,
        'file' => $file,
        'line' => $line,
        'server' => $_SERVER,
        'request' => $_REQUEST
    ]);
    
    $response = [
        'status' => 'error',
        'message' => 'Se ha producido un error en el servidor',
        'error_id' => $errorId,
        'code' => 500
    ];

    if (shouldShowErrorDetails()) {
        $response['details'] = [
            'message' => $message,
            'severity' => $severity,
            'file' => $file,
            'line' => $line
        ];
    }
    
    sendJsonResponse($response, 500);
});

// Manejador de excepciones personalizado
set_exception_handler(function($e) use ($logger) {
    // Generar ID único para la excepción
    $errorId = uniqid('EXC_');
    
    // Registrar excepción detallada en el log
    $logger->error("Exception ID: {$errorId} - {$e->getMessage()}", [
        'error_id' => $errorId,
        'exception' => [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ],
        'server' => $_SERVER,
        'request' => $_REQUEST
    ]);
    
    $response = [
        'status' => 'error',
        'message' => 'Se ha producido un error en el servidor',
        'error_id' => $errorId,
        'code' => 500
    ];

    if (shouldShowErrorDetails()) {
        $response['details'] = getErrorDetails($e);
    }
    
    sendJsonResponse($response, 500);
});

// Manejador de cierre de script
register_shutdown_function(function() use ($logger) {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Generar ID único para el error fatal
        $errorId = uniqid('FATAL_');
        
        // Registrar error fatal detallado en el log
        $logger->error("Fatal Error ID: {$errorId} - {$error['message']}", [
            'error_id' => $errorId,
            'error' => $error,
            'server' => $_SERVER,
            'request' => $_REQUEST,
            'memory_usage' => memory_get_peak_usage(true)
        ]);
        
        $response = [
            'status' => 'error',
            'message' => 'Se ha producido un error fatal en el servidor',
            'error_id' => $errorId,
            'code' => 500
        ];

        if (shouldShowErrorDetails()) {
            $response['details'] = [
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ];
        }
        
        sendJsonResponse($response, 500);
    }
});

try {
    // Cargar variables de entorno
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Inicializar configuraciones
    JWTConfig::init();

    // Crear instancia del router
    $router = new Router();

    // Agregar middleware
    $router->addMiddleware(new RateLimitMiddleware());

    // Definir rutas
    $router->get('/', [HomeController::class, 'welcome']);
    $router->get('/status', [StatusController::class, 'check']);
    $router->post('/register', [AuthController::class, 'register']);
    $router->post('/login', [AuthController::class, 'login']);

    // Log de inicio de solicitud
    $logger->info('Request started', [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'],
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);

    // Manejar la solicitud
    $response = $router->resolve();
    
    // Log de finalización de solicitud
    $logger->info('Request completed successfully');
    
    // Enviar respuesta
    sendJsonResponse($response);
    
} catch (\Throwable $e) {
    $errorId = uniqid('THROW_');
    
    $logger->error("Throwable ID: {$errorId} - {$e->getMessage()}", [
        'error_id' => $errorId,
        'exception' => [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
    
    $response = [
        'status' => 'error',
        'message' => 'Se ha producido un error inesperado',
        'error_id' => $errorId,
        'code' => 500
    ];

    if (shouldShowErrorDetails()) {
        $response['details'] = getErrorDetails($e);
    }
    
    sendJsonResponse($response, 500);
}