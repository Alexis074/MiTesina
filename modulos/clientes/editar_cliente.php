<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';

if (!isset($_GET['id'])) {
    die("ID de cliente no proporcionado.");
}
$id = $_GET['id'];
$mensaje = "";

// Obtener datos actuales
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id=:id");
$stmt->execute([':id'=>$id]);
$cliente = $stmt->fetch();
if (!$cliente) die("Cliente no encontrado.");

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $ruc = $_POST['ruc'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $email = $_POST['email'];

    if (!preg_match("/^[A-Za-z\s]+$/", $nombre) || !preg_match("/^[A-Za-z\s]+$/", $apellido)) {
        $mensaje = "Error: El nombre y apellido solo pueden contener letras y espacios.";
    } elseif (!preg_match("/^[0-9\-]+$/", $ruc)) {
        $mensaje = "Error: El RUC solo puede contener numeros y guion.";
    } elseif (!preg_match("/^[0-9]+$/", $telefono)) {
        $mensaje = "Error: El telefono solo puede contener numeros.";
    } else {
        $sql = "UPDATE clientes SET nombre=:nombre, apellido=:apellido, ruc=:ruc, telefono=:telefono, direccion=:direccion, email=:email WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute([
            ':nombre'=>$nombre,
            ':apellido'=>$apellido,
            ':ruc'=>$ruc,
            ':telefono'=>$telefono,
            ':direccion'=>$direccion,
            ':email'=>$email,
            ':id'=>$id
        ])){
            $mensaje = "Cliente actualizado correctamente.";
            $cliente = ['nombre'=>$nombre, 'apellido'=>$apellido, 'ruc'=>$ruc, 'telefono'=>$telefono, 'direccion'=>$direccion, 'email'=>$email];
        } else {
            $mensaje = "Error al actualizar cliente.";
        }
    }
}
?>

<div class="container form-container">
    <h1>Editar Cliente</h1>
    <?php if($mensaje != ""): ?>
        <div class="mensaje <?= strpos($mensaje,'Error') === false ? 'exito' : 'error' ?>"><?= $mensaje ?></div>
    <?php endif; ?>
    <form method="POST">
        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($cliente['nombre']) ?>" required>

        <label>Apellido:</label>
        <input type="text" name="apellido" value="<?= htmlspecialchars($cliente['apellido']) ?>" required>

        <label>RUC:</label>
        <input type="text" name="ruc" value="<?= htmlspecialchars($cliente['ruc']) ?>" required>

        <label>Telefono:</label>
        <input type="text" name="telefono" value="<?= htmlspecialchars($cliente['telefono']) ?>">

        <label>Direccion:</label>
        <input type="text" name="direccion" value="<?= htmlspecialchars($cliente['direccion']) ?>">

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($cliente['email']) ?>">

        <div class="form-actions">
            <button type="submit" class="btn-submit">Actualizar Cliente</button>
        </div>
    </form>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
