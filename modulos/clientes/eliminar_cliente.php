<?php
$base_url = '/repuestos/';
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('clientes', 'eliminar');

if (!isset($_GET['id'])) {
    die("ID de cliente no proporcionado.");
}
$id = $_GET['id'];

// Obtener cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id=:id");
$stmt->execute([':id'=>$id]);
$cliente = $stmt->fetch();
if (!$cliente) die("Cliente no encontrado.");

// Eliminar si confirma
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id=:id");
    if($stmt->execute([':id'=>$id])){
        header("Location: " . $base_url . "modulos/clientes/clientes.php");
        exit;
    } else {
        $mensaje_error = "Error al eliminar cliente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Cliente - Repuestos Doble A</title>
    <link rel="stylesheet" href="<?= $base_url ?>style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<?php include $base_path . 'includes/header.php'; ?>

<div class="container form-container">
    <h1 class="delete-title">Eliminar Cliente</h1>
    <p>¿Seguro que deseas eliminar al cliente <strong><?= htmlspecialchars($cliente['nombre'] . " " . $cliente['apellido']) ?></strong>?</p>

    <?php if (isset($mensaje_error)): ?>
        <div class="mensaje error"><?= $mensaje_error ?></div>
    <?php endif; ?>

    <form method="POST" class="inline-form">
        <div class="form-actions">
            <button type="submit" class="btn-submit">Sí, eliminar</button>
            <a href="<?= $base_url ?>modulos/clientes/clientes.php" class="btn-cancelar">Cancelar</a>
        </div>
    </form>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
