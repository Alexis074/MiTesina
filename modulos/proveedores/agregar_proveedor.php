<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empresa = $_POST['empresa'];
    $contacto = $_POST['contacto'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $direccion = $_POST['direccion'];
    $created_at = date("Y-m-d H:i:s");

    // Validaciones básicas
    if (!preg_match("/^[A-Za-z0-9\s]+$/", $empresa)) {
        $mensaje = "Error: El nombre de la empresa solo puede contener letras, números y espacios.";
    } elseif (!preg_match("/^[A-Za-z\s]+$/", $contacto)) {
        $mensaje = "Error: El contacto solo puede contener letras y espacios.";
    } elseif (!preg_match("/^[0-9\-]+$/", $telefono)) {
        $mensaje = "Error: El teléfono solo puede contener números y guiones.";
    } else {
        $sql = "INSERT INTO proveedores (empresa, contacto, telefono, email, direccion, created_at)
                VALUES (:empresa, :contacto, :telefono, :email, :direccion, :created_at)";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute([
            ':empresa'=>$empresa,
            ':contacto'=>$contacto,
            ':telefono'=>$telefono,
            ':email'=>$email,
            ':direccion'=>$direccion,
            ':created_at'=>$created_at
        ])){
            $mensaje = "Proveedor agregado correctamente.";
            $empresa = $contacto = $telefono = $email = $direccion = "";
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
<title>Agregar Proveedor</title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
.container { max-width:600px; margin:80px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
h1 { text-align:center; margin-bottom:20px; }
label { display:block; margin:10px 0 5px; }
input { width:100%; padding:8px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px; font-size:16px; }
button { width:100%; padding:10px; background:#2563eb; color:white; border:none; border-radius:4px; font-size:16px; cursor:pointer; }
button:hover { background:#1e40af; }
.mensaje { padding:10px; margin-bottom:15px; border-radius:4px; text-align:center; }
.mensaje.exito { background-color:#d1fae5; color:#065f46; }
.mensaje.error { background-color:#fee2e2; color:#991b1b; }
</style>
</head>
<body>

<div class="container">
<h1>Agregar Proveedor</h1>

<div style="margin-bottom: 20px; text-align: right;">
    <a href="/repuestos/modulos/proveedores/proveedores.php" class="btn-cancelar" style="display: inline-block; padding: 8px 15px; background:rgb(255, 0, 0); color: white; text-decoration: none; border-radius: 4px;"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<?php if($mensaje != ""): ?>
    <div class="mensaje <?= strpos($mensaje,'Error') === false ? 'exito' : 'error' ?>"><?= $mensaje; ?></div>
<?php endif; ?>
<form method="POST">
<label>Empresa:</label>
<input type="text" name="empresa" value="<?= isset($empresa) ? $empresa : '' ?>" required>

<label>Contacto:</label>
<input type="text" name="contacto" value="<?= isset($contacto) ? $contacto : '' ?>" required>

<label>Telefono:</label>
<input type="text" name="telefono" value="<?= isset($telefono) ? $telefono : '' ?>">

<label>Email:</label>
<input type="email" name="email" value="<?= isset($email) ? $email : '' ?>">

<label>Direccion:</label>
<input type="text" name="direccion" value="<?= isset($direccion) ? $direccion : '' ?>">

<button type="submit">Agregar Proveedor</button>
</form>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>
