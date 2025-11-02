<?php
$base_url = '/repuestos/';
include $_SERVER['DOCUMENT_ROOT'] . $base_url . 'includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . $base_url . 'includes/conexion.php'; // ✅ Usar PDO

// Obtener producto por ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $fila = $stmt->fetch();

    if (!$fila) {
        echo "<p>Producto no encontrado.</p>";
        exit;
    }
}

// Procesar formulario
if (isset($_POST['actualizar'])) {
    $id = $_POST['id'];
    $codigo = $_POST['codigo'];
    $nombre = $_POST['nombre'];
    $categoria = $_POST['categoria'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $cilindrada = $_POST['cilindrada'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    $stock_min = $_POST['stock_min'];

    $updateSql = "UPDATE productos SET 
        codigo = :codigo,
        nombre = :nombre,
        categoria = :categoria,
        marca = :marca,
        modelo = :modelo,
        cilindrada = :cilindrada,
        precio = :precio,
        stock = :stock,
        stock_min = :stock_min
        WHERE id = :id";

    $stmt = $pdo->prepare($updateSql);
    if ($stmt->execute([
        'codigo' => $codigo,
        'nombre' => $nombre,
        'categoria' => $categoria,
        'marca' => $marca,
        'modelo' => $modelo,
        'cilindrada' => $cilindrada,
        'precio' => $precio,
        'stock' => $stock,
        'stock_min' => $stock_min,
        'id' => $id
    ])) {
        echo "<p style='color:green; text-align:center;'>Producto actualizado correctamente.</p>";
        // Refresca los datos para mostrar los cambios
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
    } else {
        echo "<p style='color:red; text-align:center;'>Error al actualizar producto.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Producto - Repuestos Doble A</title>
<link rel="stylesheet" href="<?= $base_url ?>style.css"> 
<style>
.container {
    padding: 20px;
    margin-top: 60px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
    background: #fff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
}
h1 { text-align: center; color: #1e293b; }
form input, form button {
    width: 100%;
    padding: 10px;
    margin-bottom: 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
}
form button {
    background: #2563eb;
    color: white;
    border: none;
    cursor: pointer;
    transition: background 0.3s;
}
form button:hover { background: #1e40af; }
</style>
</head>
<body>

<div class="container">
    <h1>Editar Producto</h1>
    <form method="POST">
        <input type="hidden" name="id" value="<?= $fila['id']; ?>">

        <label>Codigo</label>
        <input type="text" name="codigo" value="<?= htmlspecialchars($fila['codigo']); ?>" required>

        <label>Nombre</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($fila['nombre']); ?>" required>

        <label>Categoria</label>
        <input type="text" name="categoria" value="<?= htmlspecialchars($fila['categoria']); ?>" required>

        <label>Marca</label>
        <input type="text" name="marca" value="<?= htmlspecialchars($fila['marca']); ?>" required>

        <label>Modelo</label>
        <input type="text" name="modelo" value="<?= htmlspecialchars($fila['modelo']); ?>" required>

        <label>Cilindrada</label>
        <input type="text" name="cilindrada" value="<?= htmlspecialchars($fila['cilindrada']); ?>" required>

        <label>Precio</label>
        <input type="number" name="precio" value="<?= (int)$fila['precio']; ?>" required>

        <label>Stock</label>
        <input type="number" name="stock" value="<?= (int)$fila['stock']; ?>" required>

        <label>Stock Minimo</label>
        <input type="number" name="stock_min" value="<?= (int)$fila['stock_min']; ?>" required>

        <button type="submit" name="actualizar">Actualizar Producto</button>
    </form>
</div>

</body>
</html>
