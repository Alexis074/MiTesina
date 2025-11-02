<?php
// Incluye tu conexión PDO
include '../../includes/conexion.php'; // Debe definir $pdo

// Consulta productos usando PDO
$stmt = $pdo->query("SELECT * FROM productos ORDER BY id ASC");
$productos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Productos - Repuestos Doble A</title>
<link rel="stylesheet" href="../../style.css">
<style>
.container { padding: 20px; margin-top: 60px; }
table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
th { background: #2563eb; color: white; }
tr:hover { background: #e0f2fe; }
.btn { padding: 5px 10px; border-radius: 5px; color: white; text-decoration: none; margin: 0 2px; }
.btn-add { background: #10b981; }
.btn-edit { background: #f59e0b; }
.btn-delete { background: #ef4444; }
h1 { display: flex; justify-content: space-between; align-items: center; }
</style>
</head>
<body>

<?php include '../../includes/header.php'; ?>

<div class="container">
<h1>Productos <a href="agregar_producto.php" class="btn btn-add">+ Agregar</a></h1>

<div style="text-align:right; margin-bottom:10px;">
  <a href="exportar_productos_pdf.php" target="_blank" class="btn" style="background:#2563eb; color:white; padding:8px 15px; text-decoration:none; border-radius:5px;">
    📄 Exportar a PDF
  </a>
</div>

<table>
<tr>
  <th>Código</th>
  <th>Nombre</th>
  <th>Categoría</th>
  <th>Marca</th>
  <th>Modelo</th>
  <th>Cilindrada</th>
  <th>Precio</th>
  <th>Stock</th>
  <th>Stock Min</th>
  <th>Acciones</th>
</tr>

<?php if(count($productos) > 0): ?>
    <?php foreach($productos as $fila): ?>
    <tr>
        <td><?= htmlspecialchars($fila['codigo']) ?></td>
        <td><?= htmlspecialchars($fila['nombre']) ?></td>
        <td><?= htmlspecialchars($fila['categoria']) ?></td>
        <td><?= htmlspecialchars($fila['marca']) ?></td>
        <td><?= htmlspecialchars($fila['modelo']) ?></td>
        <td><?= htmlspecialchars($fila['cilindrada']) ?></td>
        <td><?= number_format($fila['precio'],0,",",".") ?></td>
        <td><?= htmlspecialchars($fila['stock']) ?></td>
        <td><?= htmlspecialchars($fila['stock_min']) ?></td>
        <td>
            <a href="editar_producto.php?id=<?= $fila['id'] ?>" class="btn btn-edit">Editar</a>
            <a href="eliminar_producto.php?id=<?= $fila['id'] ?>" class="btn btn-delete" onclick="return confirm('¿Desea eliminar este producto?')">Eliminar</a>
        </td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr>
    <td colspan="10">No hay productos registrados.</td>
</tr>
<?php endif; ?>
</table>
</div>

</body>
</html>
