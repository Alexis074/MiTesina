<?php
$base_url = '/repuestos/';
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('proveedores', 'eliminar');

$mensaje = "";

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM proveedores WHERE id=:id");
    if ($stmt->execute([':id' => $id])) {
        $mensaje = "Proveedor eliminado correctamente.";
    } else {
        $mensaje = "Error al eliminar proveedor.";
    }
} else {
    $mensaje = "ID no proporcionado.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Proveedor - Repuestos Doble A</title>
    <link rel="stylesheet" href="<?= $base_url ?>style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<?php include $base_path . 'includes/header.php'; ?>

<div class="container form-container">
    <h1>Eliminar Proveedor</h1>
    <div class="mensaje <?= strpos($mensaje,'Error') !== false || strpos($mensaje,'no proporcionado') !== false ? 'error' : 'exito' ?>">
        <p><?= htmlspecialchars($mensaje); ?></p>
    </div>
    <div class="form-actions" style="margin-top: 20px;">
        <a href="<?= $base_url ?>modulos/proveedores/proveedores.php" class="btn-submit"><i class="fas fa-arrow-left"></i> Volver a Proveedores</a>
    </div>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
