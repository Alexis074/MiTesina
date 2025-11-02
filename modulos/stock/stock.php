<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';


if ($conexion->connect_error) die("Error de conexión: " . $conexion->connect_error);
$sql = "SELECT * FROM productos ORDER BY stock ASC";
$resultado = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Stock - Repuestos Doble A</title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
.container { padding: 20px; margin-top: 60px; }
table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
th, td { border: 1px solid #ccc; padding: 8px; text-align: center; font-size: 14px; }
th { background: #2563eb; color: white; }
tr:hover { background: #e0f2fe; }
.low-stock { background-color: #fde68a; } /* Color amarillo para stock bajo */
</style>
</head>
<body>



<div class="container">
<h1>Control de Stock</h1>
<table>
<tr>
<th>Código</th>
<th>Nombre</th>
<th>Categoría</th>
<th>Stock</th>
<th>Stock Mínimo</th>
</tr>
<?php
if($resultado->num_rows > 0){
    while($fila = $resultado->fetch_assoc()){
        $clase = ($fila['stock'] <= $fila['stock_min']) ? 'low-stock' : '';
        echo "<tr class='{$clase}'>
        <td>{$fila['codigo']}</td>
        <td>{$fila['nombre']}</td>
        <td>{$fila['categoria']}</td>
        <td>{$fila['stock']}</td>
        <td>{$fila['stock_min']}</td>
        </tr>";
    }
}else{
    echo "<tr><td colspan='5'>No hay productos registrados.</td></tr>";
}
?>
</table>
</div>
</body>
</html>
