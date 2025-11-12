<?php
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
if (!isset($pdo)) {
    include $base_path . 'includes/conexion.php';
}
if (!function_exists('estaLogueado')) {
    include $base_path . 'includes/session.php';
}

// Verificar permisos de un módulo
if (!function_exists('tienePermiso')) {
function tienePermiso($modulo, $accion = 'ver') {
    if (!estaLogueado()) {
        return false;
    }
    
    $rol = obtenerRol();
    
    // Administrador tiene todos los permisos
    if ($rol === 'Administrador') {
        return true;
    }
    
    // Verificar permisos específicos
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT puede_ver, puede_crear, puede_editar, puede_eliminar FROM permisos WHERE rol = ? AND modulo = ?");
        $stmt->execute([$rol, $modulo]);
        $permiso = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$permiso) {
            return false;
        }
        
        switch ($accion) {
            case 'ver':
                return isset($permiso['puede_ver']) && (bool)$permiso['puede_ver'];
            case 'crear':
                return isset($permiso['puede_crear']) && (bool)$permiso['puede_crear'];
            case 'editar':
                return isset($permiso['puede_editar']) && (bool)$permiso['puede_editar'];
            case 'eliminar':
                return isset($permiso['puede_eliminar']) && (bool)$permiso['puede_eliminar'];
            default:
                return false;
        }
    } catch (PDOException $e) {
        error_log('Error en tienePermiso: ' . $e->getMessage());
        return false;
    }
}
}

// Requerir permiso específico
if (!function_exists('requerirPermiso')) {
function requerirPermiso($modulo, $accion = 'ver') {
    requerirLogin();
    
    if (!tienePermiso($modulo, $accion)) {
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['error'] = 'No tienes permiso para acceder a esta sección.';
        header('Location: /repuestos/index.php');
        exit();
    }
}
}
?>

