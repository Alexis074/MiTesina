<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';

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
<title>Eliminar Proveedor</title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
.container { display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:80vh; text-align:center; }
.message-box { width:400px; background:white; padding:30px; box-shadow:0 4px 10px rgba(0,0,0,0.1); border-radius:8px; }
.message-box p { font-size:16px; color:#1e293b; margin-bottom:20px; }
.message-box a { display:inline-block; padding:10px 20px; background:#2563eb; color:white; text-decoration:none; border-radius:6px; transition:0.3s; }
.message-box a:hover { background:#1e40af; }
h1 { margin-bottom:20px; color:#1e293b; }
</style>
</head>
<body>

<div class="container">
<h1>Eliminar Proveedor</h1>
<div class="message-box">
<p><?= $mensaje; ?></p>
<a href="proveedores.php">Volver a Proveedores</a>
</div>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>
