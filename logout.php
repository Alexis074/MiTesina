<?php
date_default_timezone_set('America/Asuncion');
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auditoria.php';

// Registrar en auditoría antes de destruir sesión
$usuario_nombre = $_SESSION['usuario'] ?? 'Desconocido';
registrarAuditoria('logout', 'sistema', 'Usuario ' . $usuario_nombre . ' cerró sesión');

// Registrar cierre de sesión
if (isset($_SESSION['sesion_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE sesiones SET fecha_logout = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['sesion_id']]);
    } catch (PDOException $e) {
        error_log('Error al actualizar sesión en logout: ' . $e->getMessage());
    }
}

// Destruir sesión
session_destroy();

// Redirigir a login
header('Location: /repuestos/login.php');
exit();
?>

