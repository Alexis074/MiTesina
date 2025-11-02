<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';

$mensaje = "";

// Obtener proveedor por ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id=:id");
    $stmt->execute([':id' => $id]);
    $fila = $stmt->fetch();

    if (!$fila) {
        echo "<p>Proveedor no encontrado.</p>";
        exit;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $empresa = $_POST['empresa'];
    $contacto = $_POST['contacto'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $direccion = $_POST['direccion'];

    if (!preg_match("/^[A-Za-z0-9\s\.\-]+$/", $empresa)) {
        $mensaje = "Error: El nombre de la empresa contiene caracteres no válidos.";
    } elseif (!preg_match("/^[A-Za-z\s]+$/", $contacto)) {
        $mensaje = "Error: El contacto solo puede contener letras y espacios.";
    } elseif (!preg_match("/^[0-9]+$/", $telefono)) {
        $mensaje = "Error: El teléfono solo puede contener números.";
    } else {
        $sql = "UPDATE proveedores SET 
                    empresa=:empresa,
                    contacto=:contacto,
                    telefono=:telefono,
                    email=:email,
                    direccion=:direccion
                WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([
            ':empresa'=>$empresa,
            ':contacto'=>$contacto,
            ':telefono'=>$telefono,
            ':email'=>$email,
            ':direccion'=>$direccion,
            ':id'=>$id
        ])) {
            $mensaje = "Proveedor actualizado correctamente.";
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
<title>Editar Proveedor</title>
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
<h1>Editar Proveedor</h1>
<?php if($mensaje != ""): ?>
    <div class="mensaje <?= strpos($mensaje,'Error') === false ? 'exito' : 'error' ?>"><?= $mensaje; ?></div>
<?php endif; ?>
<form method="POST">
<input type="hidden" name="id" value="<?= $fila['id'] ?>">

<label>Empresa:</label>
<input type="text" name="empresa" value="<?= $fila['empresa'] ?>" required>

<label>Contacto:</label>
<input type="text" name="contacto" value="<?= $fila['contacto'] ?>" required>

<label>Teléfono:</label>
<input type="text" name="telefono" value="<?= $fila['telefono'] ?>">

<label>Email:</label>
<input type="email" name="email" value="<?= $fila['email'] ?>">

<label>Dirección:</label>
<input type="text" name="direccion" value="<?= $fila['direccion'] ?>">

<button type="submit">Actualizar Proveedor</button>
</form>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>
