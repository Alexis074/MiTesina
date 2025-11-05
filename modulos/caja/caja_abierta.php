<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'].'/repuestos/';
include $base_path.'includes/conexion.php';
include $base_path.'includes/header.php';

$mensaje = "";

// Obtener caja abierta
$stmt = $pdo->query("SELECT * FROM cajas WHERE fecha_cierre IS NULL");
$caja = $stmt->fetch();

if(!$caja){
    echo "<p>No hay caja abierta. <a href='abrir_caja.php'>Abrir Caja</a></p>";
    exit;
}

// Procesar movimiento
if(isset($_POST['agregar_movimiento'])){
    $tipo = $_POST['tipo'];
    $concepto = $_POST['concepto'];
    $forma_pago = $_POST['forma_pago'];
    $monto = $_POST['monto'];
    $observaciones = $_POST['observaciones'];
    $usuario = $_POST['usuario'];
    $fecha = date('Y-m-d H:i:s');

    $sql = "INSERT INTO movimientos_caja (caja_id,tipo,concepto,forma_pago,monto,observaciones,fecha,usuario)
            VALUES (:caja_id,:tipo,:concepto,:forma_pago,:monto,:observaciones,:fecha,:usuario)";
    $stmt = $pdo->prepare($sql);
    if($stmt->execute([
        'caja_id'=>$caja['id'],
        'tipo'=>$tipo,
        'concepto'=>$concepto,
        'forma_pago'=>$forma_pago,
        'monto'=>$monto,
        'observaciones'=>$observaciones,
        'fecha'=>$fecha,
        'usuario'=>$usuario
    ])){
        $mensaje = "Movimiento agregado.";
    } else {
        $mensaje = "Error al agregar movimiento.";
    }
}

// Obtener movimientos
$stmt = $pdo->prepare("SELECT * FROM movimientos_caja WHERE caja_id=:caja_id ORDER BY fecha ASC");
$stmt->execute(['caja_id'=>$caja['id']]);
$movimientos = $stmt->fetchAll();

// Calcular totales
$total_ingresos = 0;
$total_egresos = 0;
foreach($movimientos as $m){
    if($m['tipo']=='Ingreso') $total_ingresos += $m['monto'];
    else $total_egresos += $m['monto'];
}
$monto_final = $caja['monto_inicial'] + $total_ingresos - $total_egresos;
?>

<div class="container">
<h1>Caja Abierta - Usuario: <?= $caja['usuario'] ?></h1>
<p>Monto Inicial: <?= number_format($caja['monto_inicial'],2) ?> | Total Ingresos: <?= number_format($total_ingresos,2) ?> | Total Egresos: <?= number_format($total_egresos,2) ?> | Monto Final: <?= number_format($monto_final,2) ?></p>

<?php if($mensaje!=""): ?>
<div class="mensaje <?= strpos($mensaje,'Error')===false?'exito':'error' ?>"><?= $mensaje ?></div>
<?php endif; ?>

<h2>Agregar Movimiento</h2>
<form method="POST">
<label>Usuario:</label><input type="text" name="usuario" required>
<label>Tipo:</label>
<select name="tipo" required>
<option value="Ingreso">Ingreso</option>
<option value="Egreso">Egreso</option>
</select>
<label>Concepto:</label><input type="text" name="concepto" required>
<label>Forma de Pago:</label>
<select name="forma_pago" required>
<option value="Efectivo">Efectivo</option>
<option value="Tarjeta">Tarjeta</option>
<option value="Transferencia">Transferencia</option>
<option value="Otro">Otro</option>
</select>
<label>Monto:</label><input type="number" step="0.01" name="monto" required>
<label>Observaciones:</label><input type="text" name="observaciones">
<button type="submit" name="agregar_movimiento">Agregar Movimiento</button>
</form>

<h2>Movimientos de Caja</h2>
<table>
<tr><th>Fecha</th><th>Tipo</th><th>Concepto</th><th>Forma Pago</th><th>Monto</th><th>Usuario</th></tr>
<?php if($movimientos){ foreach($movimientos as $m): ?>
<tr>
<td><?= $m['fecha'] ?></td>
<td><?= $m['tipo'] ?></td>
<td><?= $m['concepto'] ?></td>
<td><?= $m['forma_pago'] ?></td>
<td><?= number_format($m['monto'],2) ?></td>
<td><?= $m['usuario'] ?></td>
</tr>
<?php endforeach; } else { ?>
<tr><td colspan="6">No hay movimientos registrados.</td></tr>
<?php } ?>
</table>

<a href="cerrar_caja.php?id=<?= $caja['id'] ?>" class="btn btn-delete" onclick="return confirm('Seguro que deseas cerrar la caja?')">Cerrar Caja</a>
<a href="exportar_pdf_caja.php?id=<?= $caja['id'] ?>" class="btn btn-edit">Exportar PDF</a>

</div>

<?php include $base_path.'includes/footer.php'; ?>
