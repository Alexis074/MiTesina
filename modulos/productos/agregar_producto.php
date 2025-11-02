<?php
$base_url = '/repuestos/';
include $_SERVER['DOCUMENT_ROOT'] . $base_url . 'includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . $base_url . 'includes/conexion.php'; // PDO

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
<style>
body {
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    margin: 0;
    padding: 0;
}
.container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 80vh;
    text-align: center;
}
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
    width: 400px;
    background: white;
    padding: 30px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
}
form input {
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 6px;
    width: 100%;
}
form button {
    padding: 12px;
    font-size: 16px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: 0.3s;
}
form button:hover {
    background: #1e40af;
}
h1 {
    margin-bottom: 20px;
    color: #1e293b;
}
</style>
</head>
<body>

<div class="container">
    <h1>Agregar Producto</h1>
    <?php if($mensaje != "") echo "<p style='color:green;'>$mensaje</p>"; ?>
    <form method="POST">
        <input type="text" name="codigo" placeholder="Codigo" required>
        <input type="text" name="nombre" placeholder="Nombre" required>
        <input type="text" name="categoria" placeholder="Categoria" required>
        <input type="text" name="marca" placeholder="Marca" required>
        <input type="text" name="modelo" placeholder="Modelo" required>
        <input type="text" name="cilindrada" placeholder="Cilindrada" required>
        <input type="number" step="1" name="precio" placeholder="Precio" required>
        <input type="number" name="stock" placeholder="Stock" required>
        <input type="number" name="stock_min" placeholder="Stock Minimo" required>
        <button type="submit" name="guardar">Agregar Producto</button>
    </form>
</div>

</body>
</html>
