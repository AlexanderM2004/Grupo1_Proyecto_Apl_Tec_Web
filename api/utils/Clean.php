<?php
namespace App\Utils;

class Clean {
    /**
     * Limpia una cadena de entrada eliminando etiquetas HTML, espacios en blanco y caracteres especiales.
     * @param string $data La cadena de entrada a limpiar.
     * @return string La cadena limpia.
     */
    public static function cleanInput($data): string {
        return htmlspecialchars(strip_tags(trim($data)));
    }
    
    /**
     * Limpia un array de entrada eliminando etiquetas HTML, espacios en blanco y caracteres especiales en cada elemento.
     * @param array $data El array de entrada a limpiar.
     * @return array El array limpio.
     */
    public static function cleanArray(array $data): array {
        return array_map([self::class, 'cleanInput'], $data);
    }
    
    /**
     * Limpia una cadena de salida escapando caracteres especiales para HTML.
     * @param string $data La cadena de salida a limpiar.
     * @return string La cadena limpia.
     */
    public static function cleanOutput($data): string {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}