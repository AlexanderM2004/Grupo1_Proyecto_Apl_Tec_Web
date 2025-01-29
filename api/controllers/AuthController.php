<?php
namespace App\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Utils\Validator;

class AuthController {
    private $userModel;
    private $authService;
    private $validator;

    public function __construct() {
        $this->userModel = new User();
        $this->authService = new AuthService();
        $this->validator = new Validator();
    }

    public function register() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar datos
            if (!$this->validator->validateRegistration($data)) {
                return [
                    'status' => 'error',
                    'message' => 'Datos de registro inválidos'
                ];
            }

            // Verificar si el usuario ya existe
            if ($this->userModel->findByUsername($data['username'])) {
                return [
                    'status' => 'error',
                    'message' => 'El usuario ya existe'
                ];
            }

            // Crear usuario
            $userId = $this->userModel->create($data);
            
            // Generar JWT
            $token = $this->authService->generateToken($userId);

            return [
                'status' => 'success',
                'message' => 'Usuario registrado exitosamente',
                'token' => $token
            ];

        } catch (\Exception $e) {
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

            $token = $this->authService->generateToken($user['id']);

            return [
                'status' => 'success',
                'message' => 'Login exitoso',
                'token' => $token
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error en el login'
            ];
        }
    }
}