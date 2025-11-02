<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';

$mensaje = "";
$nombre = $apellido = $ruc = $telefono = $direccion = $email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $ruc = trim($_POST['ruc']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $email = trim($_POST['email']);
    $created_at = date("Y-m-d H:i:s");

    if (!preg_match("/^[A-Za-z\s]+$/", $nombre) || !preg_match("/^[A-Za-z\s]+$/", $apellido)) {
        $mensaje = "Error: El nombre y apellido solo pueden contener letras y espacios.";
    } elseif (!preg_match("/^[0-9\-]+$/", $ruc)) {
        $mensaje = "Error: El RUC solo puede contener números y guion.";
    } elseif (!preg_match("/^[0-9]+$/", $telefono) && $telefono != "") {
        $mensaje = "Error: El teléfono solo puede contener números.";
    } else {
        $sql = "INSERT INTO clientes (nombre, apellido, ruc, telefono, direccion, email, created_at)
                VALUES (:nombre, :apellido, :ruc, :telefono, :direccion, :email, :created_at)";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute([
            ':nombre'=>$nombre,
            ':apellido'=>$apellido,
            ':ruc'=>$ruc,
            ':telefono'=>$telefono,
            ':direccion'=>$direccion,
            ':email'=>$email,
            ':created_at'=>$created_at
        ])){
            $mensaje = "Cliente agregado correctamente.";
            // Limpiar campos
            $nombre = $apellido = $ruc = $telefono = $direccion = $email = "";
        } else {
            $mensaje = "Error al agregar cliente.";
        }
    }
}
?>

<div class="container">
    <h1>Agregar Cliente</h1>

    <?php if($mensaje != ""): ?>
        <div class="mensaje <?= strpos($mensaje,'Error') === false ? 'exito' : 'error' ?>">
            <?= $mensaje; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>

        <label>Apellido:</label>
        <input type="text" name="apellido" value="<?= htmlspecialchars($apellido) ?>" required>

        <label>RUC:</label>
        <input type="text" name="ruc" value="<?= htmlspecialchars($ruc) ?>" required>

        <label>Teléfono:</label>
        <input type="text" name="telefono" value="<?= htmlspecialchars($telefono) ?>">

        <label>Dirección:</label>
        <input type="text" name="direccion" value="<?= htmlspecialchars($direccion) ?>">

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>">

        <button type="submit">Agregar Cliente</button>
    </form>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
