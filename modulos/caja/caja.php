<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('caja', 'ver');
include $base_path . 'includes/header.php';

// Obtener la caja abierta
$stmtCaja = $pdo->query("SELECT * FROM caja WHERE estado='Abierta' ORDER BY id DESC LIMIT 1");
$caja = $stmtCaja->fetch();

// Obtener movimientos recientes si hay caja abierta
$movimientos = [];
$movimientos_combinados = [];

if($caja){
    // Movimientos manuales
    $stmtMov = $pdo->prepare("SELECT id, fecha, tipo, concepto as descripcion, monto, 'manual' as origen FROM caja_movimientos WHERE caja_id=? ORDER BY fecha DESC");
    $stmtMov->execute([$caja['id']]);
    $movimientos = $stmtMov->fetchAll();
    
    // Ventas (ingresos) - excluyendo anuladas
    $fecha_apertura = $caja['fecha'];
    $stmtVentas = $pdo->prepare("SELECT id, fecha_hora as fecha, 'Ingreso' as tipo, CONCAT('Venta - Factura ', numero_factura) as descripcion, monto_total as monto, 'venta' as origen 
                                 FROM cabecera_factura_ventas 
                                 WHERE fecha_hora >= ? AND (anulada = 0 OR anulada IS NULL)
                                 ORDER BY fecha_hora DESC");
    $stmtVentas->execute([$fecha_apertura]);
    $ventas = $stmtVentas->fetchAll();
    
    // Compras (egresos)
    $stmtCompras = $pdo->prepare("SELECT c.id, c.fecha, 'Egreso' as tipo, CONCAT('Compra - ID ', c.id, ' - Proveedor ID ', c.proveedor_id) as descripcion, c.total as monto, 'compra' as origen 
                                  FROM compras c 
                                  WHERE c.fecha >= ?
                                  ORDER BY c.fecha DESC");
    $stmtCompras->execute([$fecha_apertura]);
    $compras = $stmtCompras->fetchAll();
    
    // Combinar todos los movimientos
    $movimientos_combinados = array_merge($movimientos, $ventas, $compras);
    
    // Ordenar por fecha descendente
    usort($movimientos_combinados, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });
}
?>

<div class="container tabla-responsive">
    <h1>Caja</h1>

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

        <br><br>
        <h2>Registros de Caja (Ventas y Compras Automáticas)</h2>
        <p style="color: #6b7280; margin-bottom: 20px;">
            <i class="fas fa-info-circle"></i> Los movimientos se registran automáticamente al realizar ventas o compras.
        </p>
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
            <?php if(!empty($movimientos_combinados)): ?>
                <?php foreach($movimientos_combinados as $fila): ?>
                    <tr>
                        <td><?= htmlspecialchars($fila['id']) ?></td>
                        <td>
                            <span style="padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;
                                <?php if($fila['tipo'] == 'Ingreso'): ?>
                                    background: #10b981; color: white;
                                <?php else: ?>
                                    background: #ef4444; color: white;
                                <?php endif; ?>">
                                <?= htmlspecialchars($fila['tipo']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($fila['descripcion']) ?></td>
                        <td style="font-weight: bold; <?= $fila['tipo'] == 'Ingreso' ? 'color: #10b981;' : 'color: #ef4444;' ?>">
                            <?= ($fila['tipo'] == 'Ingreso' ? '+' : '-') ?> <?= number_format($fila['monto'],0,',','.') ?> Gs
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($fila['fecha'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No hay registros en esta caja.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
