<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
if (!function_exists('estaLogueado')) {
    function estaLogueado() {
        return isset($_SESSION['usuario_id']) && isset($_SESSION['usuario']);
    }
}

// Obtener ID del usuario actual
if (!function_exists('obtenerUsuarioId')) {
    function obtenerUsuarioId() {
        return isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
    }
}

// Obtener nombre de usuario actual
if (!function_exists('obtenerUsuario')) {
    function obtenerUsuario() {
        return isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
    }
}

// Obtener rol del usuario actual
if (!function_exists('obtenerRol')) {
    function obtenerRol() {
        return isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
    }
}

// Verificar si el usuario tiene un rol específico
if (!function_exists('tieneRol')) {
    function tieneRol($rol) {
        return obtenerRol() === $rol;
    }
}

// Redirigir a login si no está logueado
if (!function_exists('requerirLogin')) {
    function requerirLogin() {
        if (!estaLogueado()) {
            header('Location: /repuestos/login.php');
            exit();
        }
    }
}
?>

