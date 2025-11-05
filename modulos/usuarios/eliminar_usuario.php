<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
include $base_path . 'includes/auditoria.php';

requerirLogin();
requerirPermiso('usuarios', 'eliminar');

if (!isset($_GET['id'])) {
    header('Location: usuarios.php');
    exit();
}

$id = (int)$_GET['id'];
$usuario_actual_id = obtenerUsuarioId();

// No permitir eliminarse a sí mismo
if ($id == $usuario_actual_id) {
    header('Location: usuarios.php');
    exit();
}

// Obtener datos del usuario antes de eliminar
$stmt = $pdo->prepare("SELECT usuario, nombre FROM usuarios WHERE id = ?");
$stmt->execute(array($id));
$usuario = $stmt->fetch();

if ($usuario) {
    // Registrar en auditoría antes de eliminar
    registrarAuditoria('eliminar', 'usuarios', 'Usuario ' . $usuario['usuario'] . ' (' . $usuario['nombre'] . ') eliminado');
    
    // Eliminar usuario
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute(array($id));
}

header('Location: usuarios.php');
exit();
?>

