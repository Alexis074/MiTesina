<?php
/**
 * Configuración centralizada del sistema
 * Repuestos Doble A - Compatible con PHP 8.x y XAMPP
 */

// Prevenir acceso directo
if (!defined('SISTEMA_INICIADO')) {
    define('SISTEMA_INICIADO', true);
}

// Configuración de errores (solo en desarrollo, en producción usar error_log)
if (!defined('DEBUG')) {
    define('DEBUG', false); // Cambiar a false en producción
}

if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Configuración de zona horaria
date_default_timezone_set('America/Asuncion');

// Configuración de rutas
if (!defined('BASE_PATH')) {
    $base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
    // Normalizar rutas para Windows
    $base_path = str_replace('\\', '/', $base_path);
    define('BASE_PATH', $base_path);
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/repuestos/');
}

// Configuración de base de datos
if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.0.1');
    define('DB_NAME', 'db_repuestos');
    define('DB_USER', 'root');
    define('DB_PASS', ''); // Cambiar en producción
    define('DB_CHARSET', 'utf8mb4');
}

// Configuración de sesión
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Strict');

// Configuración de encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Función helper para obtener base_path
if (!function_exists('getBasePath')) {
    function getBasePath() {
        return BASE_PATH;
    }
}

// Función helper para obtener base_url
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        return BASE_URL;
    }
}

