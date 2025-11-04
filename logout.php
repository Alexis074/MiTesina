<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';

// Registrar cierre de sesión
if (isset($_SESSION['sesion_id'])) {
    $stmt = $pdo->prepare("UPDATE sesiones SET fecha_logout = NOW() WHERE id = ?");
    $stmt->execute(array($_SESSION['sesion_id']));
}

// Destruir sesión
session_destroy();

// Redirigir a login
header('Location: /repuestos/login.php');
exit();
?>

