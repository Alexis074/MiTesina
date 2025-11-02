<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';

$mensaje = "";

// Obtener la caja abierta
$stmtCaja = $pdo->query("SELECT * FROM caja WHERE estado='Abierta' ORDER BY id DESC LIMIT 1");
$caja = $stmtCaja->fetch();

// Guardar movimiento
if(isset($_POST['guardar_movimiento']) && $caja){
    $tipo = $_POST['tipo'];
    $descripcion = $_POST['descripcion'];
    $monto = $_POST['monto'];
    $fecha = date("Y-m-d H:i:s");

    $sql = "INSERT INTO caja_movimientos (caja_id, fecha, tipo, concepto, monto, created_at) 
            VALUES (:caja_id, :fecha, :tipo, :concepto, :monto, :created_at)";
    $stmt = $pdo->prepare($sql);
    if($stmt->execute([
        'caja_id' => $caja['id'],
        'fecha' => $fecha,
        'tipo' => $tipo,
        'concepto' => $descripcion,
        'monto' => $monto,
        'created_at' => $fecha
    ])){
        $mensaje = "Movimiento registrado correctamente.";
    } else {
        $mensaje = "Error al registrar movimiento.";
    }
}

// Obtener movimientos recientes si hay caja abierta
$movimientos = [];
if($caja){
    $stmtMov = $pdo->prepare("SELECT * FROM caja_movimientos WHERE caja_id=? ORDER BY fecha DESC");
    $stmtMov->execute([$caja['id']]);
    $movimientos = $stmtMov->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Caja - Repuestos Doble A</title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
.container { padding: 20px; margin-top: 60px; }
table { width:100%; border-collapse:collapse; background:white; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
th, td { border:1px solid #ccc; padding:8px; text-align:center; }
th { background:#2563eb; color:white; }
tr:hover { background:#e0f2fe; }
.btn { padding:5px 10px; border-radius:4px; text-decoration:none; font-size:14px; }
.btn-add { background:#10b981; color:white; }
.btn-delete { background:#ef4444; color:white; }
.btn-export { background:#2563eb; color:white; }
form input, form select { padding:8px; width:100%; margin-bottom:10px; border-radius:4px; border:1px solid #ccc; }
form button { padding:10px; border:none; border-radius:4px; background:#2563eb; color:white; cursor:pointer; }
form button:hover { background:#1e40af; }
.mensaje { padding:10px; margin-bottom:15px; border-radius:4px; text-align:center; }
.mensaje.exito { background-color:#d1fae5; color:#065f46; }
.mensaje.error { background-color:#fee2e2; color:#991b1b; }
</style>
</head>
<body>

<div class="container">
<h1>Caja</h1>

<?php if($mensaje != ""): ?>
    <div class="mensaje <?= strpos($mensaje,'Error') === false ? 'exito' : 'error' ?>"><?= $mensaje; ?></div>
<?php endif; ?>

<?php if(!$caja): ?>
    <a href="abrir_caja.php" class="btn btn-add">Abrir Caja</a>
<?php else: ?>
    <p><strong>Caja abierta:</strong> <?= $caja['fecha'] ?> | Monto inicial: <?= number_format($caja['monto_inicial'],0,',','.') ?> Gs</p>
    <a href="cerrar_caja.php?id=<?= $caja['id'] ?>" class="btn btn-delete" onclick="return confirm('¿Cerrar caja?')">Cerrar Caja</a>

    <h2>Registrar Movimiento</h2>
    <form method="POST">
        <label>Tipo:</label>
        <select name="tipo" required>
            <option value="Ingreso">Ingreso</option>
            <option value="Egreso">Egreso</option>
        </select>

        <label>Descripción:</label>
        <input type="text" name="descripcion" required>

        <label>Monto:</label>
        <input type="number" step="0.01" name="monto" required>

        <button type="submit" name="guardar_movimiento">Registrar</button>
    </form>

    <br>
    <a href="exportar_caja_pdf.php?id=<?= $caja['id'] ?>" class="btn btn-export" target="_blank">📄 Exportar PDF</a>
    <br><br>

    <h2>Movimientos Recientes</h2>
    <table>
    <tr>
    <th>ID</th><th>Tipo</th><th>Descripción</th><th>Monto</th><th>Fecha</th>
    </tr>

    <?php if($movimientos): ?>
        <?php foreach($movimientos as $fila): ?>
            <tr>
                <td><?= $fila['id'] ?></td>
                <td><?= $fila['tipo'] ?></td>
                <td><?= $fila['concepto'] ?></td>
                <td><?= number_format($fila['monto'],2,',','.') ?></td>
                <td><?= $fila['fecha'] ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
    <tr><td colspan="5">No hay movimientos registrados.</td></tr>
    <?php endif; ?>
    </table>
<?php endif; ?>

</div>

<?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>
