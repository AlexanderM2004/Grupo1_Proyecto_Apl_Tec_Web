<?php
namespace App\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Services\LoggerService;
use App\Utils\Validator;

class AuthController {
    private $userModel;
    private $authService;
    private $validator;
    private $logger;

    public function __construct() {
        $this->userModel = new User();
        $this->authService = new AuthService();
        $this->validator = new Validator();
        $this->logger = LoggerService::getInstance();
    }

    public function register() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar datos
            if (!$this->validator->validateRegistration($data)) {
                $this->logger->error('Invalid registration data', ['data' => $data]);
                return [
                    'status' => 'error',
                    'message' => 'Datos de registro inválidos'
                ];
            }

            // Verificar si el usuario ya existe
            if ($this->userModel->findByUsername($data['username'])) {
                $this->logger->info('Registration attempt with existing username', [
                    'username' => $data['username']
                ]);
                return [
                    'status' => 'error',
                    'message' => 'El usuario ya existe'
                ];
            }

            // Crear usuario
            $userId = $this->userModel->create($data);
            
            // Generar JWT
            $token = $this->authService->generateToken($userId);

            $this->logger->info('User registered successfully', [
                'user_id' => $userId,
                'username' => $data['username']
            ]);

            return [
                'status' => 'success',
                'message' => 'Usuario registrado exitosamente',
                'token' => $token
            ];

        } catch (\Exception $e) {
            $this->logger->error('Registration error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'status' => 'error',
                'message' => 'Error en el registro'
            ];
        }
    }

    public function login() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$this->validator->validateLogin($data)) {
                $this->logger->error('Invalid login data', ['data' => $data]);
                return [
                    'status' => 'error',
                    'message' => 'Datos de login inválidos'
                ];
            }

            $user = $this->userModel->findByUsername($data['username']);
            
            if (!$user || !password_verify($data['password'], $user['password'])) {
                $this->logger->info('Failed login attempt', [
                    'username' => $data['username']
                ]);
                return [
                    'status' => 'error',
                    'message' => 'Credenciales inválidas'
                ];
            }

            $token = $this->authService->generateToken($user['id']);

            $this->logger->info('User logged in successfully', [
                'user_id' => $user['id'],
                'username' => $user['username']
            ]);

            return [
                'status' => 'success',
                'message' => 'Login exitoso',
                'token' => $token
            ];

        } catch (\Exception $e) {
            $this->logger->error('Login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'status' => 'error',
                'message' => 'Error en el login'
            ];
        }
    }
}