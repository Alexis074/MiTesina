<?php
date_default_timezone_set('America/Asuncion');
$base_url = '/repuestos/';
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('proveedores', 'crear');

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empresa = $_POST['empresa'];
    $contacto = $_POST['contacto'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $direccion = $_POST['direccion'];
    $ruc = isset($_POST['ruc']) ? $_POST['ruc'] : '';
    $created_at = date("Y-m-d H:i:s");

    if (!preg_match("/^[A-Za-z0-9\s]+$/", $empresa)) {
        $mensaje = "Error: El nombre de la empresa solo puede contener letras, números y espacios.";
    } elseif (!preg_match("/^[A-Za-z\s]+$/", $contacto)) {
        $mensaje = "Error: El contacto solo puede contener letras y espacios.";
    } elseif (!preg_match("/^[0-9\-]+$/", $telefono)) {
        $mensaje = "Error: El teléfono solo puede contener números y guiones.";
    } else {
        $sql = "INSERT INTO proveedores (empresa, contacto, telefono, email, direccion, ruc, created_at)
                VALUES (:empresa, :contacto, :telefono, :email, :direccion, :ruc, :created_at)";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute([
            ':empresa'=>$empresa,
            ':contacto'=>$contacto,
            ':telefono'=>$telefono,
            ':email'=>$email,
            ':direccion'=>$direccion,
            ':ruc'=>$ruc,
            ':created_at'=>$created_at
        ])){
            $mensaje = "Proveedor agregado correctamente.";
            $empresa = $contacto = $telefono = $email = $direccion = $ruc = "";
        } else {
            $mensaje = "Error al agregar proveedor.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Proveedor - Repuestos Doble A</title>
    <link rel="stylesheet" href="<?= $base_url ?>style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<?php include $base_path . 'includes/header.php'; ?>

<div class="container form-container">
    <h1>Agregar Proveedor</h1>
    
    <div class="form-actions-right" style="margin-bottom: 20px;">
        <a href="<?= $base_url ?>modulos/proveedores/proveedores.php" class="btn-cancelar"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <?php if($mensaje != ""): ?>
        <div class="mensaje <?= strpos($mensaje,'Error') === false ? 'exito' : 'error' ?>"><?= $mensaje; ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Empresa:</label>
        <input type="text" name="empresa" value="<?= isset($empresa) ? htmlspecialchars($empresa) : '' ?>" required>

        <label>Contacto:</label>
        <input type="text" name="contacto" value="<?= isset($contacto) ? htmlspecialchars($contacto) : '' ?>" required>

        <label>Teléfono:</label>
        <input type="text" name="telefono" value="<?= isset($telefono) ? htmlspecialchars($telefono) : '' ?>">

        <label>Email:</label>
        <input type="email" name="email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">

        <label>Dirección:</label>
        <input type="text" name="direccion" value="<?= isset($direccion) ? htmlspecialchars($direccion) : '' ?>">

        <label>RUC:</label>
        <input type="text" name="ruc" value="<?= isset($ruc) ? htmlspecialchars($ruc) : '' ?>" placeholder="Ej: 80012345-6">

        <div class="form-actions">
            <button type="submit" class="btn-submit">Agregar Proveedor</button>
        </div>
    </form>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
