<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';

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
        header("Location: clientes.php");
        exit;
    } else {
        echo "Error al eliminar cliente.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Eliminar Cliente</title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
.container { max-width:500px; margin:80px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); text-align:center; }
h1 { color:#dc2626; margin-bottom:20px; }
p { margin-bottom:20px; }
.btn { padding:10px 20px; font-size:16px; margin:5px; border:none; border-radius:4px; cursor:pointer; }
.btn-danger { background:#dc2626; color:white; }
.btn-danger:hover { background:#b91c1c; }
.btn-secondary { background:#6b7280; color:white; }
.btn-secondary:hover { background:#4b5563; }
</style>
</head>
<body>

<div class="container">
<h1>Eliminar Cliente</h1>
<p>Seguro que deseas eliminar al cliente <strong><?= htmlspecialchars($cliente['nombre'] . " " . $cliente['apellido']) ?></strong>?</p>

<form method="POST" style="display:inline;">
<button type="submit" class="btn btn-danger">Si, eliminar</button>
</form>
<a href="clientes.php" class="btn btn-secondary">Cancelar</a>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>
