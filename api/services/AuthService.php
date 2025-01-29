<?php
namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Config\JWTConfig;

class AuthService {
    public function generateToken(int $userId): string {
        $issuedAt = time();
        $expirationTime = $issuedAt + JWTConfig::getExpiration();
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => $userId
        ];

        return JWT::encode($payload, JWTConfig::getSecret(), 'HS256');
    }

    public function validateToken(string $token): ?object {
        try {
            // Usar Key para decodificar el token
            return JWT::decode($token, new Key(JWTConfig::getSecret(), 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }
}