<?php
date_default_timezone_set('America/Asuncion');
$base_url = '/repuestos/';
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('productos', 'crear');

$mensaje = "";

if (isset($_POST['guardar'])) {
    $codigo = $_POST['codigo'];
    $nombre = $_POST['nombre'];
    $categoria = $_POST['categoria'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $cilindrada = $_POST['cilindrada'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    $stock_min = $_POST['stock_min'];
    $created_at = date("Y-m-d H:i:s");

    // Verificar si ya existe el producto
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE nombre=:nombre AND marca=:marca AND modelo=:modelo");
    $stmt->execute(['nombre'=>$nombre, 'marca'=>$marca, 'modelo'=>$modelo]);
    $productoExistente = $stmt->fetch();

    if ($productoExistente) {
        $nuevoStock = $productoExistente['stock'] + $stock;
        $updateStmt = $pdo->prepare("UPDATE productos SET stock=:stock WHERE id=:id");
        if ($updateStmt->execute(['stock'=>$nuevoStock, 'id'=>$productoExistente['id']])) {
            $mensaje = "Producto ya existente, stock actualizado correctamente.";
        } else {
            $mensaje = "Error al actualizar stock.";
        }
    } else {
        $insertStmt = $pdo->prepare("INSERT INTO productos
            (codigo, nombre, categoria, marca, modelo, cilindrada, precio, stock, stock_min, created_at)
            VALUES (:codigo, :nombre, :categoria, :marca, :modelo, :cilindrada, :precio, :stock, :stock_min, :created_at)");
        if ($insertStmt->execute([
            'codigo'=>$codigo,
            'nombre'=>$nombre,
            'categoria'=>$categoria,
            'marca'=>$marca,
            'modelo'=>$modelo,
            'cilindrada'=>$cilindrada,
            'precio'=>$precio,
            'stock'=>$stock,
            'stock_min'=>$stock_min,
            'created_at'=>$created_at
        ])) {
            $mensaje = "Producto agregado correctamente.";
        } else {
            $mensaje = "Error al agregar producto.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Agregar Producto - Repuestos Doble A</title>
<link rel="stylesheet" href="<?= $base_url ?>style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<?php include $base_path . 'includes/header.php'; ?>

<div class="container form-container">
    <h1>Agregar Producto</h1>
    
    <div class="form-actions-right" style="margin-bottom: 20px;">
        <a href="<?= $base_url ?>modulos/productos/productos.php" class="btn-cancelar"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    
    <?php if($mensaje != ""): ?>
        <div class="mensaje <?= strpos($mensaje, 'Error') === false ? 'exito' : 'error' ?>"><?= $mensaje; ?></div>
    <?php endif; ?>
    <form method="POST">
        <label>Codigo:</label>
        <input type="text" name="codigo" placeholder="Codigo" required>

        <label>Nombre:</label>
        <input type="text" name="nombre" placeholder="Nombre" required>

        <label>Categoria:</label>
        <input type="text" name="categoria" placeholder="Categoria" required>

        <label>Marca:</label>
        <input type="text" name="marca" placeholder="Marca" required>

        <label>Modelo:</label>
        <input type="text" name="modelo" placeholder="Modelo" required>

        <label>Cilindrada:</label>
        <input type="text" name="cilindrada" placeholder="Cilindrada" required>

        <label>Precio:</label>
        <input type="number" step="1" name="precio" placeholder="Precio" required>

        <label>Stock:</label>
        <input type="number" name="stock" placeholder="Stock" required>

        <label>Stock Minimo:</label>
        <input type="number" name="stock_min" placeholder="Stock Minimo" required>

        <div class="form-actions">
            <button type="submit" name="guardar" class="btn-submit">Agregar Producto</button>
        </div>
    </form>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>
