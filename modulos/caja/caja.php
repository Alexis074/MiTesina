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

<div class="container tabla-responsive">
    <h1>Caja</h1>

    <?php if($mensaje != ""): ?>
        <div class="mensaje <?= strpos($mensaje,'Error') === false ? 'exito' : 'error' ?>"><?= $mensaje; ?></div>
    <?php endif; ?>

    <?php if(!$caja): ?>
        <div class="form-actions-right">
            <a href="abrir_caja.php" class="btn-submit"><i class="fas fa-lock-open"></i> Abrir Caja</a>
        </div>
    <?php else: ?>
        <div class="form-actions-right">
            <p><strong>Caja abierta:</strong> <?= $caja['fecha'] ?> | Monto inicial: <?= number_format($caja['monto_inicial'],0,',','.') ?> Gs</p>
            <a href="cerrar_caja.php?id=<?= $caja['id'] ?>" class="btn-cancelar" onclick="return confirm('¿Cerrar caja?')"><i class="fas fa-lock"></i> Cerrar Caja</a>
            <a href="exportar_pdf_caja.php?id=<?= $caja['id'] ?>" class="btn-export" target="_blank"><i class="fas fa-file-pdf"></i> Exportar PDF</a>
        </div>

        <h2>Registrar Movimiento</h2>
        <div class="form-container">
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
        </div>

        <br><br>
        <h2>Movimientos Recientes</h2>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Monto</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
            <?php if($movimientos): ?>
                <?php foreach($movimientos as $fila): ?>
                    <tr>
                        <td><?= htmlspecialchars($fila['id']) ?></td>
                        <td><?= htmlspecialchars($fila['tipo']) ?></td>
                        <td><?= htmlspecialchars($fila['concepto']) ?></td>
                        <td><?= number_format($fila['monto'],2,',','.') ?></td>
                        <td><?= htmlspecialchars($fila['fecha']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No hay movimientos registrados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
