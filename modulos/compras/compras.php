<?php
session_start(); // ✅ Necesario para manejar el mensaje una sola vez

$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';

$mensaje = "";

// Si existe mensaje de sesión, mostrarlo y luego eliminarlo
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

// Consultar proveedores
$proveedores_stmt = $pdo->query("SELECT * FROM proveedores ORDER BY empresa ASC");
$proveedores = $proveedores_stmt->fetchAll();

// Consultar productos
$productos_stmt = $pdo->query("SELECT * FROM productos ORDER BY nombre ASC");
$productos = $productos_stmt->fetchAll();

// Obtener caja abierta
$caja_stmt = $pdo->query("SELECT * FROM caja WHERE estado='Abierta' ORDER BY id DESC LIMIT 1");
$caja_abierta = $caja_stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $proveedor_id = $_POST['proveedor_id'];
    $fecha = date("Y-m-d H:i:s");
    $productos_ids = $_POST['producto_id']; // array de productos
    $cantidades = $_POST['cantidad'];       // array de cantidades
    $precios = $_POST['precio'];            // array de precios

    // Calcular total de la compra
    $total_compra = 0;
    for ($i = 0; $i < count($productos_ids); $i++) {
        $total_compra += $cantidades[$i] * $precios[$i];
    }

    // Insertar compra incluyendo total
    $sql_compra = "INSERT INTO compras (proveedor_id, fecha, total) VALUES (:proveedor_id, :fecha, :total)";
    $stmt_compra = $pdo->prepare($sql_compra);
    $stmt_compra->execute([
        'proveedor_id' => $proveedor_id,
        'fecha' => $fecha,
        'total' => $total_compra
    ]);
    $compra_id = $pdo->lastInsertId();

    // Insertar detalles y actualizar stock
    for ($i = 0; $i < count($productos_ids); $i++) {
        $subtotal = $cantidades[$i] * $precios[$i]; // calculamos subtotal

        // Insertar detalle incluyendo subtotal
        $sql_detalle = "INSERT INTO compras_detalle (compra_id, producto_id, cantidad, precio_unitario, subtotal)
                        VALUES (:compra_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
        $stmt_detalle = $pdo->prepare($sql_detalle);
        $stmt_detalle->execute([
            'compra_id' => $compra_id,
            'producto_id' => $productos_ids[$i],
            'cantidad' => $cantidades[$i],
            'precio_unitario' => $precios[$i],
            'subtotal' => $subtotal
        ]);

        // Actualizar stock
        $sql_stock = "UPDATE productos SET stock = stock + :cantidad WHERE id=:producto_id";
        $stmt_stock = $pdo->prepare($sql_stock);
        $stmt_stock->execute([
            'cantidad' => $cantidades[$i],
            'producto_id' => $productos_ids[$i]
        ]);
    }

    // Registrar egreso en caja
    if ($caja_abierta) {
        $concepto = "Compra a proveedor ID $proveedor_id, compra ID $compra_id";
        $stmt_egreso = $pdo->prepare("
            INSERT INTO caja_movimientos 
            (caja_id, fecha, tipo, concepto, monto)
            VALUES (:caja_id, :fecha, 'Egreso', :concepto, :monto)
        ");
        $stmt_egreso->execute([
            'caja_id' => $caja_abierta['id'],
            'fecha' => $fecha,
            'concepto' => $concepto,
            'monto' => $total_compra
        ]);
    }

    // ✅ Guardar mensaje en sesión y redirigir para evitar que se repita
    $_SESSION['mensaje'] = "Compra registrada correctamente. Total: " . number_format($total_compra, 2, ',', '.');
    header("Location: compras.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Compras - Repuestos Doble A</title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
.container { padding:20px; margin-top:60px; }
h1 { margin-bottom:20px; }
form { background:white; padding:20px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); margin-bottom:20px; }
form label { display:block; margin:10px 0 5px; }
form select, form input { width:100%; padding:8px; margin-bottom:10px; border-radius:4px; border:1px solid #ccc; }
form button { padding:10px; background:#2563eb; color:white; border:none; border-radius:4px; cursor:pointer; }
form button:hover { background:#1e40af; }
.mensaje { padding:10px; margin-bottom:15px; border-radius:4px; text-align:center; }
.mensaje.exito { background-color:#d1fae5; color:#065f46; }
.mensaje.error { background-color:#fee2e2; color:#991b1b; }
table { width:100%; border-collapse:collapse; background:white; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
th, td { border:1px solid #ccc; padding:8px; text-align:center; }
th { background:#2563eb; color:white; }
tr:hover { background:#e0f2fe; }
.btn { padding:5px 10px; border-radius:4px; text-decoration:none; font-size:14px; }
.btn-export { background:#2563eb; color:white; }
</style>
</head>
<body>

<div class="container">
<h1>Registrar Compra</h1>

<?php if($mensaje != ""): ?>
<div class="mensaje <?= strpos($mensaje,'Error')===false?'exito':'error' ?>"><?= $mensaje ?></div>
<?php endif; ?>

<form method="POST">
<label>Proveedor:</label>
<select name="proveedor_id" required>
    <option value="">-- Seleccione proveedor --</option>
    <?php foreach($proveedores as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['empresa']) ?></option>
    <?php endforeach; ?>
</select>

<h2>Productos</h2>
<div id="productos_container">
<div class="producto_row">
    <label>Producto:</label>
    <select name="producto_id[]" required>
        <option value="">-- Seleccione producto --</option>
        <?php foreach($productos as $prod): ?>
            <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['nombre']) ?> (Stock: <?= $prod['stock'] ?>)</option>
        <?php endforeach; ?>
    </select>
    <label>Cantidad:</label>
    <input type="number" name="cantidad[]" min="1" required>
    <label>Precio Unitario:</label>
    <input type="number" name="precio[]" step="0.01" required>
</div>
</div>
<button type="button" id="add_producto">+ Agregar otro producto</button>
<br><br>
<button type="submit">Registrar Compra</button>
</form>

<script>
// Agregar nueva fila de producto
document.getElementById('add_producto').addEventListener('click', function(){
    var container = document.getElementById('productos_container');
    var newRow = container.children[0].cloneNode(true);
    newRow.querySelectorAll('input').forEach(input => input.value='');
    newRow.querySelector('select').selectedIndex = 0;
    container.appendChild(newRow);
});
</script>

</div>

<?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>
