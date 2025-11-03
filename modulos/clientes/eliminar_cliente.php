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

<div class="container form-container">
    <h1 style="color:#dc2626;">Eliminar Cliente</h1>
    <p>Seguro que deseas eliminar al cliente <strong><?= htmlspecialchars($cliente['nombre'] . " " . $cliente['apellido']) ?></strong>?</p>

    <form method="POST" style="display:inline;">
        <button type="submit" class="btn btn-delete">Si, eliminar</button>
    </form>
    <a href="clientes.php" class="btn btn-edit">Cancelar</a>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
