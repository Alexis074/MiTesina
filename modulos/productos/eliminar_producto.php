<?php
$base_url = '/repuestos/';
include $_SERVER['DOCUMENT_ROOT'] . $base_url . 'includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . $base_url . 'includes/conexion.php'; // ✅ tu conexión PDO

$mensaje = "";

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM productos WHERE id = :id");
    if ($stmt->execute(['id' => $id])) {
        $mensaje = "Producto eliminado correctamente.";
    } else {
        $mensaje = "Error al eliminar el producto.";
    }
} else {
    $mensaje = "ID no proporcionado.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Eliminar Producto - Repuestos Doble A</title>
<link rel="stylesheet" href="<?= $base_url ?>style.css">
<style>
body { font-family: Arial, sans-serif; background: #f1f5f9; margin: 0; padding: 0; }
.container { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 80vh; text-align: center; }
.message-box { width: 400px; background: white; padding: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-radius: 8px; }
.message-box p { font-size: 16px; color: #1e293b; margin-bottom: 20px; }
.message-box a { display: inline-block; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; transition: 0.3s; }
.message-box a:hover { background: #1e40af; }
h1 { margin-bottom: 20px; color: #1e293b; }
</style>
</head>
<body>

<div class="container">
    <h1>Eliminar Producto</h1>
    <div class="message-box">
        <p><?= htmlspecialchars($mensaje); ?></p>
        <a href="productos.php">Volver a Productos</a>
    </div>
</div>

</body>
</html>
