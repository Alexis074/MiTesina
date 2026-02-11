<?php
/**
 * Conexión a la base de datos usando la PDO
 * Compatible con PHP 8.x y XAMPP
 */

// Incluir configuración si existe
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    // Configuración por defecto si no existe config.php
    date_default_timezone_set('America/Asuncion');
    if (!defined('DB_HOST')) {
        define('DB_HOST', '127.0.0.1');
        define('DB_NAME', 'db_repuestos');
        define('DB_USER', 'root');
        define('DB_PASS', '');
        define('DB_CHARSET', 'utf8mb4');
    }
}

// Solo crear conexión si no existe
if (!isset($pdo)) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::ATTR_TIMEOUT            => 5,
            PDO::ATTR_PERSISTENT         => false, // No usar conexiones persistentes para evitar problemas
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Establecer encoding UTF-8
        $pdo->exec("SET NAMES " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_unicode_ci");
        
    } catch (PDOException $e) {
        // Log del error
        error_log('Error de conexión a la base de datos: ' . $e->getMessage());
        error_log('DSN: ' . $dsn);
        
        // En producción, no mostrar el mensaje de error completo por seguridad
        http_response_code(500);
        
        // Mostrar mensaje amigable
        if (defined('DEBUG') && DEBUG) {
            die('Error de conexión a la base de datos: ' . htmlspecialchars($e->getMessage()));
        } else {
            die('Error de conexión a la base de datos. Por favor, contacte al administrador del sistema.');
        }
    }
}