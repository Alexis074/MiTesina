<?php
date_default_timezone_set('America/Asuncion');
$base_url = '/repuestos/';
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('proveedores', 'editar');

$mensaje = "";

// Obtener proveedor por ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id=:id");
    $stmt->execute([':id' => $id]);
    $fila = $stmt->fetch();

    if (!$fila) {
        die("Proveedor no encontrado.");
    }
} else {
    die("ID no proporcionado.");
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $empresa = $_POST['empresa'];
    $contacto = $_POST['contacto'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $direccion = $_POST['direccion'];
    $ruc = isset($_POST['ruc']) ? $_POST['ruc'] : '';

    if (!preg_match("/^[A-Za-z0-9\s\.\-]+$/", $empresa)) {
        $mensaje = "Error: El nombre de la empresa contiene caracteres no válidos.";
    } elseif (!preg_match("/^[A-Za-z\s]+$/", $contacto)) {
        $mensaje = "Error: El contacto solo puede contener letras y espacios.";
    } elseif (!preg_match("/^[0-9\-]+$/", $telefono)) {
        $mensaje = "Error: El teléfono solo puede contener números.";
    } else {
        $sql = "UPDATE proveedores SET 
                    empresa=:empresa,
                    contacto=:contacto,
                    telefono=:telefono,
                    email=:email,
                    direccion=:direccion,
                    ruc=:ruc
                WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([
            ':empresa'=>$empresa,
            ':contacto'=>$contacto,
            ':telefono'=>$telefono,
            ':email'=>$email,
            ':direccion'=>$direccion,
            ':ruc'=>$ruc,
            ':id'=>$id
        ])) {
            $mensaje = "Proveedor actualizado correctamente.";
            $fila = ['empresa'=>$empresa, 'contacto'=>$contacto, 'telefono'=>$telefono, 'email'=>$email, 'direccion'=>$direccion, 'ruc'=>$ruc, 'id'=>$id];
        } else {
            $mensaje = "Error al actualizar proveedor.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Proveedor - Repuestos Doble A</title>
    <link rel="stylesheet" href="<?= $base_url ?>style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<?php include $base_path . 'includes/header.php'; ?>

<div class="container form-container">
    <h1>Editar Proveedor</h1>

    <div class="form-actions-right" style="margin-bottom: 20px;">
        <a href="<?= $base_url ?>modulos/proveedores/proveedores.php" class="btn-cancelar"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <?php if($mensaje != ""): ?>
        <div class="mensaje <?= strpos($mensaje,'Error') === false ? 'exito' : 'error' ?>"><?= $mensaje; ?></div>
    <?php endif; ?>
    <form method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($fila['id']) ?>">

        <label>Empresa:</label>
        <input type="text" name="empresa" value="<?= htmlspecialchars($fila['empresa']) ?>" required>

        <label>Contacto:</label>
        <input type="text" name="contacto" value="<?= htmlspecialchars($fila['contacto']) ?>" required>

        <label>Teléfono:</label>
        <input type="text" name="telefono" value="<?= htmlspecialchars($fila['telefono']) ?>">

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($fila['email']) ?>">

        <label>Dirección:</label>
        <input type="text" name="direccion" value="<?= htmlspecialchars($fila['direccion']) ?>">

        <label>RUC:</label>
        <input type="text" name="ruc" value="<?= isset($fila['ruc']) ? htmlspecialchars($fila['ruc']) : '' ?>" placeholder="Ej: 80012345-6">

        <div class="form-actions">
            <button type="submit" class="btn-submit">Actualizar Proveedor</button>
        </div>
    </form>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
