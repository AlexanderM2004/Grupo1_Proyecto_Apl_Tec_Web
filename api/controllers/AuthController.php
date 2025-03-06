<?php
namespace App\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Services\LoggerService;
use App\Utils\Validator;
use App\Utils\Clean;

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
            
            if (!$this->validator->validateRegistration($data)) {
                return [
                    'status' => 'error',
                    'message' => 'Datos de registro inválidos'
                ];
            }

            if ($this->userModel->findByUsername(Clean::cleanInput($data['username']))) {
                return [
                    'status' => 'error',
                    'message' => 'El usuario ya existe'
                ];
            }

            $userId = $this->userModel->create($data);
            $user = $this->userModel->findById($userId);
            
            $token = $this->authService->generateToken($user);

            return [
                'status' => 'success',
                'message' => 'Usuario registrado exitosamente',
                'token' => $token
            ];

        } catch (\Exception $e) {
            $this->logger->error('Registration error', ['error' => $e->getMessage()]);
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
                return [
                    'status' => 'error',
                    'message' => 'Datos de login inválidos'
                ];
            }

            $user = $this->userModel->findByUsername($data['username']);
            
            if (!$user || !password_verify($data['password'], $user['password'])) {
                return [
                    'status' => 'error',
                    'message' => 'Credenciales inválidas'
                ];
            }

            $token = $this->authService->generateToken($user);

            return [
                'status' => 'success',
                'message' => 'Login exitoso',
                'token' => $token
            ];

        } catch (\Exception $e) {
            $this->logger->error('Login error', ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => 'Error en el login'
            ];
        }
    }
}