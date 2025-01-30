<?php
namespace App\Utils;

class Validator {
    public function validateRegistration(array $data): bool {
        return isset($data['username']) && 
               isset($data['password']) && 
               isset($data['genero']) &&
               isset($data['pais']) &&
               strlen($data['username']) >= 3 &&
               strlen($data['password']) >= 6 &&
               in_array($data['genero'], ['M', 'F', 'O']);
    }

    public function validateLogin(array $data): bool {
        return isset($data['username']) && 
               isset($data['password']);
    }

    public function validatePasswordChange(array $data): bool {
        return isset($data['current_password']) && 
               isset($data['new_password']) && 
               strlen($data['new_password']) >= 6;
    }
}