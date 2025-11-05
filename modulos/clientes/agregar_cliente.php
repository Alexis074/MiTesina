<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $ruc = $_POST['ruc'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $email = $_POST['email'];
    $created_at = date("Y-m-d H:i:s");

    if (!preg_match("/^[A-Za-z\s]+$/", $nombre) || !preg_match("/^[A-Za-z\s]+$/", $apellido)) {
        $mensaje = "Error: El nombre y apellido solo pueden contener letras y espacios.";
    } elseif (!preg_match("/^[0-9\-]+$/", $ruc)) {
        $mensaje = "Error: El RUC solo puede contener números y guion.";
    } elseif (!preg_match("/^[0-9]+$/", $telefono)) {
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
            $nombre = $apellido = $ruc = $telefono = $direccion = $email = "";
        } else {
            $mensaje = "Error al agregar cliente.";
        }
    }
}
?>

<div class="container form-container">
    <h1>Agregar Cliente</h1>
    
    <div class="form-actions-right" style="margin-bottom: 20px;">
        <a href="/repuestos/modulos/clientes/clientes.php" class="btn-cancelar"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <?php if($mensaje != ""): ?>
        <div class="mensaje <?= strpos($mensaje,'Error') === false ? 'exito' : 'error' ?>"><?= $mensaje; ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= isset($nombre) ? htmlspecialchars($nombre) : '' ?>" required>

        <label>Apellido:</label>
        <input type="text" name="apellido" value="<?= isset($apellido) ? htmlspecialchars($apellido) : '' ?>" required>

        <label>RUC:</label>
        <input type="text" name="ruc" value="<?= isset($ruc) ? htmlspecialchars($ruc) : '' ?>" required>

        <label>Teléfono:</label>
        <input type="text" name="telefono" value="<?= isset($telefono) ? htmlspecialchars($telefono) : '' ?>">

        <label>Dirección:</label>
        <input type="text" name="direccion" value="<?= isset($direccion) ? htmlspecialchars($direccion) : '' ?>">

        <label>Email:</label>
        <input type="email" name="email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">

        <div class="form-actions">
            <button type="submit" class="btn-submit">Agregar Cliente</button>
        </div>



    </form>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
