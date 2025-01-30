<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $userData): ?int {
        $query = "INSERT INTO usuarios (username, password, genero, pais) 
                 VALUES (:username, :password, :genero, :pais) 
                 RETURNING id";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'username' => $userData['username'],
            'password' => password_hash($userData['password'], PASSWORD_DEFAULT),
            'genero' => $userData['genero'],
            'pais' => $userData['pais']
        ]);
        
        return $stmt->fetchColumn();
    }

    public function findByUsername(string $username): ?array {
        $query = "SELECT * FROM usuarios WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['username' => $username]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updatePassword(int $userId, string $newPassword): bool {
        $query = "UPDATE usuarios SET password = :password 
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $userId
        ]);
    }
    
    public function verifyPassword(int $userId, string $currentPassword): bool {
        $query = "SELECT password FROM usuarios WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $userId]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return false;
        
        return password_verify($currentPassword, $user['password']);
    }
}