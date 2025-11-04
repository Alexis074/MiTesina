<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
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
    $stmt = $pdo->prepare("SELECT puede_ver, puede_crear, puede_editar, puede_eliminar FROM permisos WHERE rol = ? AND modulo = ?");
    $stmt->execute(array($rol, $modulo));
    $permiso = $stmt->fetch();
    
    if (!$permiso) {
        return false;
    }
    
    switch ($accion) {
        case 'ver':
            return (bool)$permiso['puede_ver'];
        case 'crear':
            return (bool)$permiso['puede_crear'];
        case 'editar':
            return (bool)$permiso['puede_editar'];
        case 'eliminar':
            return (bool)$permiso['puede_eliminar'];
        default:
            return false;
    }
}
}

// Requerir permiso específico
if (!function_exists('requerirPermiso')) {
function requerirPermiso($modulo, $accion = 'ver') {
    requerirLogin();
    
    if (!tienePermiso($modulo, $accion)) {
        header('Location: /repuestos/index.php');
        $_SESSION['error'] = 'No tienes permiso para acceder a esta sección.';
        exit();
    }
}
}
?>

