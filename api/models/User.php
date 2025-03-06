<?php
namespace App\Models;

use App\Config\Database;
use App\Utils\Clean;
use PDO;

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUsername(string $username): ?array {
        $userClean = Clean::cleanInput($username);

        $query = "SELECT id, username, password, genero FROM usuarios WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['username' => $userClean]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findById(int $id): ?array {
        $query = "SELECT id, username, genero FROM usuarios WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $userData): ?int {
        $query = "INSERT INTO usuarios (username, password, genero, pais) 
                 VALUES (:username, :password, :genero, :pais) 
                 RETURNING id";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'username' => Clean::cleanInput($userData['username']),
            'password' => password_hash(Clean::cleanInput($userData['password']), PASSWORD_DEFAULT),
            'genero' => Clean::cleanInput($userData['genero']),
            'pais' => Clean::cleanInput($userData['pais'])
        ]);
        
        return $stmt->fetchColumn();
    }

    public function updatePassword(int $userId, string $newPassword): bool {
        $query = "UPDATE usuarios SET password = :password WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            'password' => password_hash(Clean::cleanInput($newPassword), PASSWORD_DEFAULT),
            'id' => $userId
        ]);
    }
    public function verifyPassword(int $userId, string $currentPassword): bool {
        $query = "SELECT password FROM usuarios WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $userId]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return password_verify(Clean::cleanInput($currentPassword), $user['password']);
    }
}