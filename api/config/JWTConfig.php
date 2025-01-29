<?php
namespace App\Config;

class JWTConfig {
    private static $secret;
    private static $expiration;

    public static function init() {
        self::$secret = $_ENV['JWT_SECRET'];
        self::$expiration = $_ENV['JWT_EXPIRATION'];
    }

    public static function getSecret() {
        return self::$secret;
    }

    public static function getExpiration() {
        return self::$expiration;
    }
}