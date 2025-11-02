<?php
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
        $mensaje = "Error: El RUC solo puede contener numeros y guion.";
    } elseif (!preg_match("/^[0-9]+$/", $telefono)) {
        $mensaje = "Error: El telefono solo puede contener numeros.";
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

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Agregar Cliente</title>
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
<h1>Agregar Cliente</h1>
<?php if($mensaje != ""): ?>
    <div class="mensaje <?= strpos($mensaje,'Error') === false ? 'exito' : 'error' ?>"><?= $mensaje; ?></div>
<?php endif; ?>
<form method="POST">
<label>Nombre:</label>
<input type="text" name="nombre" value="<?= isset($nombre) ? $nombre : '' ?>" required>

<label>Apellido:</label>
<input type="text" name="apellido" value="<?= isset($apellido) ? $apellido : '' ?>" required>

<label>RUC:</label>
<input type="text" name="ruc" value="<?= isset($ruc) ? $ruc : '' ?>" required>

<label>Telefono:</label>
<input type="text" name="telefono" value="<?= isset($telefono) ? $telefono : '' ?>">

<label>Direccion:</label>
<input type="text" name="direccion" value="<?= isset($direccion) ? $direccion : '' ?>">

<label>Email:</label>
<input type="email" name="email" value="<?= isset($email) ? $email : '' ?>">

<button type="submit">Agregar Cliente</button>
</form>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>
