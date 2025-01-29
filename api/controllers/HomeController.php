<?php
namespace App\Controllers;

class HomeController {
    public function welcome() {
        return [
            'status' => 'success',
            'message' => 'Bienvenido a la API'
        ];
    }
}