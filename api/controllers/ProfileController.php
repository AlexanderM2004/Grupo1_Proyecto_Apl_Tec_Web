<?php
namespace App\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Services\LoggerService;
use App\Utils\Validator;

class ProfileController {
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

    public function changePassword() {
        try {
            $headers = getallheaders();
            $token = $headers['Authorization'] ?? '';
            
            if (empty($token)) {
                return [
                    'status' => 'error',
                    'message' => 'Token no proporcionado'
                ];
            }

            // Validar token y obtener userId
            $tokenData = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            if (!$tokenData) {
                return [
                    'status' => 'error',
                    'message' => 'Token inválido'
                ];
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$this->validator->validatePasswordChange($data)) {
                $this->logger->error('Invalid password change data', ['user_id' => $tokenData->user_id]);
                return [
                    'status' => 'error',
                    'message' => 'Datos inválidos para el cambio de contraseña'
                ];
            }

            // Verificar contraseña actual
            if (!$this->userModel->verifyPassword($tokenData->user_id, $data['current_password'])) {
                $this->logger->warning('Invalid current password in change attempt', [
                    'user_id' => $tokenData->user_id
                ]);
                return [
                    'status' => 'error',
                    'message' => 'La contraseña actual es incorrecta'
                ];
            }

            // Actualizar contraseña
            if ($this->userModel->updatePassword($tokenData->user_id, $data['new_password'])) {
                $this->logger->info('Password changed successfully', [
                    'user_id' => $tokenData->user_id
                ]);
                return [
                    'status' => 'success',
                    'message' => 'Contraseña actualizada exitosamente'
                ];
            }

            throw new \Exception('Error al actualizar la contraseña');

        } catch (\Exception $e) {
            $this->logger->error('Password change error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'status' => 'error',
                'message' => 'Error al cambiar la contraseña'
            ];
        }
    }
}