<?php
date_default_timezone_set('America/Asuncion');
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
    
    // Compras (egresos) - de la tabla compras
    $stmtCompras = $pdo->prepare("SELECT c.id, c.fecha, 'Egreso' as tipo, CONCAT('Compra - ID ', c.id, ' - Proveedor ID ', c.proveedor_id) as descripcion, c.total as monto, 'compra' as origen 
                                  FROM compras c 
                                  WHERE c.fecha >= ?
                                  ORDER BY c.fecha DESC");
    $stmtCompras->execute([$fecha_apertura]);
    $compras = $stmtCompras->fetchAll();
    
    // Facturas de compras (egresos) - si existen
    $facturas_compras = [];
    try {
        $stmtFacturasCompras = $pdo->prepare("SELECT fc.id, fc.fecha_hora as fecha, 'Egreso' as tipo, CONCAT('Factura Compra - ', fc.numero_factura) as descripcion, fc.monto_total as monto, 'factura_compra' as origen 
                                              FROM cabecera_factura_compras fc 
                                              WHERE fc.fecha_hora >= ?
                                              ORDER BY fc.fecha_hora DESC");
        $stmtFacturasCompras->execute([$fecha_apertura]);
        $facturas_compras = $stmtFacturasCompras->fetchAll();
    } catch (Exception $e) {
        // Tabla no existe, continuar
    }
    
    // Combinar todos los movimientos
    $movimientos_combinados = array_merge($movimientos, $ventas, $compras, $facturas_compras);
    
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
    <?php else: 
        // Calcular estadísticas
        $total_ingresos = 0;
        $total_egresos = 0;
        $total_ventas = 0;
        $total_compras = 0;
        $cantidad_ventas = 0;
        $cantidad_compras = 0;
        
        foreach($movimientos_combinados as $m){
            if($m['tipo'] == 'Ingreso') {
                $total_ingresos += (float)$m['monto'];
                if($m['origen'] == 'venta') {
                    $total_ventas += (float)$m['monto'];
                    $cantidad_ventas++;
                }
            } else {
                $total_egresos += (float)$m['monto'];
                if($m['origen'] == 'compra') {
                    $total_compras += (float)$m['monto'];
                    $cantidad_compras++;
                }
            }
        }
        
        $monto_final_calculado = (float)$caja['monto_inicial'] + $total_ingresos - $total_egresos;
        $saldo = $monto_final_calculado - (float)$caja['monto_inicial'];
    ?>
        <div class="form-actions-right">
            <a href="cerrar_caja.php?id=<?= $caja['id'] ?>" class="btn-cancelar" onclick="return confirm('¿Cerrar caja?')"><i class="fas fa-lock"></i> Cerrar Caja</a>
            <a href="exportar_pdf_caja.php?id=<?= $caja['id'] ?>" class="btn-export" target="_blank"><i class="fas fa-file-pdf"></i> Exportar PDF</a>
        </div>

        <!-- Cuadros de estadísticas -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0;">
            <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;">MONTO INICIAL</div>
                <div style="font-size: 24px; font-weight: bold;">
                    <?= number_format($caja['monto_inicial'], 0, ',', '.') ?> Gs
                </div>
                <div style="font-size: 11px; opacity: 0.9; margin-top: 5px;">
                    Abierta: <?= date('d/m/Y H:i', strtotime($caja['fecha'])) ?>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;">TOTAL INGRESOS</div>
                <div style="font-size: 24px; font-weight: bold;">
                    <?= number_format($total_ingresos, 0, ',', '.') ?> Gs
                </div>
                <div style="font-size: 11px; opacity: 0.9; margin-top: 5px;">
                    <?= $cantidad_ventas ?> ventas
                </div>
            </div>

            <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;">TOTAL EGRESOS</div>
                <div style="font-size: 24px; font-weight: bold;">
                    <?= number_format($total_egresos, 0, ',', '.') ?> Gs
                </div>
                <div style="font-size: 11px; opacity: 0.9; margin-top: 5px;">
                    <?= $cantidad_compras ?> compras
                </div>
            </div>

            <div style="background: linear-gradient(135deg, <?= $saldo >= 0 ? '#3b82f6' : '#ef4444' ?> 0%, <?= $saldo >= 0 ? '#2563eb' : '#dc2626' ?> 100%); color: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;">SALDO</div>
                <div style="font-size: 24px; font-weight: bold;">
                    <?= ($saldo >= 0 ? '+' : '') ?><?= number_format($saldo, 0, ',', '.') ?> Gs
                </div>
                <div style="font-size: 11px; opacity: 0.9; margin-top: 5px;">
                    <?= $saldo >= 0 ? 'Ganancia' : 'Pérdida' ?>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;">MONTO FINAL</div>
                <div style="font-size: 24px; font-weight: bold;">
                    <?= number_format($monto_final_calculado, 0, ',', '.') ?> Gs
                </div>
                <div style="font-size: 11px; opacity: 0.9; margin-top: 5px;">
                    Calculado en tiempo real
                </div>
            </div>
        </div>

        <br><br>
        <h2><i class="fas fa-list"></i> Registros de Caja (Ventas y Compras Automáticas)</h2>
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
