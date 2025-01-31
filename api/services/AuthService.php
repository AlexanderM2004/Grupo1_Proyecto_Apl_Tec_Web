<?php
namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Config\JWTConfig;
use App\Models\User;

class AuthService {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function generateToken(array $user): string {
        $issuedAt = time();
        $expirationTime = $issuedAt + JWTConfig::getExpiration();
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => $user['id'],
            'username' => $user['username'],
            'genero' => $user['genero']
        ];

        return JWT::encode($payload, JWTConfig::getSecret(), 'HS256');
    }

    public function validateToken(string $token): ?object {
        try {
            return JWT::decode($token, new Key(JWTConfig::getSecret(), 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }
}