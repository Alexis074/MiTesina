<?php
date_default_timezone_set('America/Asuncion');
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('caja', 'ver');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Caja Abierta - Repuestos Doble A</title>
    <link rel="stylesheet" href="/repuestos/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<?php include $base_path . 'includes/header.php'; ?>

<?php
$mensaje = "";

// Obtener caja abierta
$stmt = $pdo->query("SELECT * FROM caja WHERE estado='Abierta' ORDER BY id DESC LIMIT 1");
$caja = $stmt->fetch();

if(!$caja){
    echo "<p>No hay caja abierta. <a href='abrir_caja.php'>Abrir Caja</a></p>";
    exit;
}

// Procesar movimiento
if(isset($_POST['agregar_movimiento'])){
    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
    $concepto = isset($_POST['concepto']) ? trim($_POST['concepto']) : '';
    $monto = isset($_POST['monto']) ? (float)$_POST['monto'] : 0;
    $fecha = date('Y-m-d H:i:s');

    try {
        $sql = "INSERT INTO caja_movimientos (caja_id, tipo, concepto, monto, fecha) 
                VALUES (:caja_id, :tipo, :concepto, :monto, :fecha)";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute([
            'caja_id'=>$caja['id'],
            'tipo'=>$tipo,
            'concepto'=>$concepto,
            'monto'=>$monto,
            'fecha'=>$fecha
        ])){
            $mensaje = "Movimiento agregado correctamente.";
            
            // Registrar en auditoría
            include $base_path . 'includes/auditoria.php';
            $detalle = "Movimiento de caja agregado: " . $tipo . " - " . $concepto . " - " . number_format($monto, 0, ',', '.') . " Gs";
            registrarAuditoria('crear', 'caja', $detalle);
        } else {
            $mensaje = "Error al agregar movimiento.";
        }
    } catch (PDOException $e) {
        $mensaje = "Error al agregar movimiento: " . $e->getMessage();
        error_log('Error en caja_abierta.php: ' . $e->getMessage());
    }
}

// Obtener movimientos
try {
    $stmt = $pdo->prepare("SELECT * FROM caja_movimientos WHERE caja_id=:caja_id ORDER BY fecha DESC");
    $stmt->execute(['caja_id'=>$caja['id']]);
    $movimientos = $stmt->fetchAll();
} catch (PDOException $e) {
    $movimientos = [];
    error_log('Error al obtener movimientos: ' . $e->getMessage());
}

// Calcular totales
$total_ingresos = 0;
$total_egresos = 0;
foreach($movimientos as $m){
    if($m['tipo']=='Ingreso') {
        $total_ingresos += (float)$m['monto'];
    } else {
        $total_egresos += (float)$m['monto'];
    }
}
$monto_final = (float)$caja['monto_inicial'] + $total_ingresos - $total_egresos;
?>

<div class="container">
<h1>Caja Abierta</h1>
<p><strong>Monto Inicial:</strong> <?= number_format($caja['monto_inicial'],0,',','.') ?> Gs | 
   <strong>Total Ingresos:</strong> <?= number_format($total_ingresos,0,',','.') ?> Gs | 
   <strong>Total Egresos:</strong> <?= number_format($total_egresos,0,',','.') ?> Gs | 
   <strong>Monto Final:</strong> <?= number_format($monto_final,0,',','.') ?> Gs</p>

<?php if($mensaje!=""): ?>
<div class="mensaje <?= strpos($mensaje,'Error')===false?'exito':'error' ?>"><?= $mensaje ?></div>
<?php endif; ?>

<h2>Agregar Movimiento</h2>
<form method="POST" class="form-container">
    <label for="tipo">Tipo:</label>
    <select id="tipo" name="tipo" required>
        <option value="Ingreso">Ingreso</option>
        <option value="Egreso">Egreso</option>
    </select>
    
    <label for="concepto">Concepto:</label>
    <input type="text" id="concepto" name="concepto" required>
    
    <label for="monto">Monto (Gs):</label>
    <input type="number" id="monto" name="monto" step="0.01" min="0" required>
    
    <div class="form-actions">
        <button type="submit" name="agregar_movimiento" class="btn-submit">Agregar Movimiento</button>
    </div>
</form>

<h2>Movimientos de Caja</h2>
<table class="crud-table">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Concepto</th>
            <th>Monto</th>
        </tr>
    </thead>
    <tbody>
        <?php if($movimientos && count($movimientos) > 0): ?>
            <?php foreach($movimientos as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['fecha']) ?></td>
                <td><?= htmlspecialchars($m['tipo']) ?></td>
                <td><?= htmlspecialchars($m['concepto']) ?></td>
                <td><?= number_format($m['monto'],0,',','.') ?> Gs</td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">No hay movimientos registrados.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<a href="cerrar_caja.php?id=<?= $caja['id'] ?>" class="btn btn-delete" onclick="return confirm('Seguro que deseas cerrar la caja?')">Cerrar Caja</a>
<a href="exportar_pdf_caja.php?id=<?= $caja['id'] ?>" class="btn btn-edit">Exportar PDF</a>

</div>

<?php include $base_path.'includes/footer.php'; ?>
