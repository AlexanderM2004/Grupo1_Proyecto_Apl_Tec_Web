<?php
namespace App\Config;

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $this->conn = new \PDO(
                "pgsql:host=" . $_ENV['DB_HOST'] . 
                ";dbname=" . $_ENV['DB_NAME'],
                $_ENV['DB_USER'],
                $_ENV['DB_PASS']
            );
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch(\PDOException $e) {
            throw new \Exception("Connection error: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}