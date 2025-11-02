<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Clientes - Repuestos Doble A</title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
.container { padding: 20px; margin-top: 60px; }
table { width:100%; border-collapse:collapse; background:white; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
th, td { border:1px solid #ccc; padding:8px; text-align:center; }
th { background:#2563eb; color:white; }
tr:hover { background:#e0f2fe; }
.btn { padding:5px 10px; border-radius:4px; text-decoration:none; font-size:14px; }
.btn-edit { background:#facc15; color:black; }
.btn-delete { background:#ef4444; color:white; }
</style>
</head>
<body>

<div class="container">
<h1>Clientes</h1>
<a href="agregar_cliente.php" class="btn btn-edit">+ Agregar Cliente</a>
<br><br>
<table>
<tr>
<th>ID</th><th>Nombre</th><th>Apellido</th><th>RUC</th><th>Telefono</th><th>Direccion</th><th>Email</th><th>Acciones</th>
</tr>

<?php
$stmt = $pdo->query("SELECT * FROM clientes ORDER BY id ASC");
$clientes = $stmt->fetchAll();
if($clientes){
    foreach($clientes as $fila){
        echo "<tr>
                <td>{$fila['id']}</td>
                <td>{$fila['nombre']}</td>
                <td>{$fila['apellido']}</td>
                <td>{$fila['ruc']}</td>
                <td>{$fila['telefono']}</td>
                <td>{$fila['direccion']}</td>
                <td>{$fila['email']}</td>
                <td>
                    <a href='editar_cliente.php?id={$fila['id']}' class='btn btn-edit'>Editar</a>
                    <a href='eliminar_cliente.php?id={$fila['id']}' class='btn btn-delete' onclick=\"return confirm('Seguro que deseas eliminar este cliente?')\">Eliminar</a>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='8'>No hay clientes registrados.</td></tr>";
}
?>

</table>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

</body>
</html>
