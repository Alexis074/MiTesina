<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('reportes', 'ver');
include $base_path . 'includes/header.php';

// Obtener fechas por defecto (hoy y último mes)
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-d', strtotime('-30 days'));

// Estadísticas de ventas (excluyendo anuladas)
$stmt_ventas = $pdo->prepare("SELECT COUNT(*) as total, SUM(monto_total) as monto_total 
                              FROM cabecera_factura_ventas 
                              WHERE fecha_hora >= ? AND fecha_hora <= ? AND (anulada = 0 OR anulada IS NULL)");
$stmt_ventas->execute([$fecha_desde, $fecha_hasta . ' 23:59:59']);
$stats_ventas = $stmt_ventas->fetch();

// Estadísticas de compras
$stmt_compras = $pdo->prepare("SELECT COUNT(*) as total, SUM(total) as monto_total 
                               FROM compras 
                               WHERE fecha >= ? AND fecha <= ?");
$stmt_compras->execute([$fecha_desde, $fecha_hasta . ' 23:59:59']);
$stats_compras = $stmt_compras->fetch();

// Ventas por día (últimos 7 días para gráfico)
$stmt_ventas_dia = $pdo->prepare("SELECT DATE(fecha_hora) as fecha, COUNT(*) as cantidad, SUM(monto_total) as total
                                   FROM cabecera_factura_ventas
                                   WHERE fecha_hora >= ? AND fecha_hora <= ? AND (anulada = 0 OR anulada IS NULL)
                                   GROUP BY DATE(fecha_hora)
                                   ORDER BY fecha DESC
                                   LIMIT 7");
$stmt_ventas_dia->execute([date('Y-m-d', strtotime('-7 days')), date('Y-m-d') . ' 23:59:59']);
$ventas_dia = $stmt_ventas_dia->fetchAll();

// Compras por día (últimos 7 días para gráfico)
$stmt_compras_dia = $pdo->prepare("SELECT DATE(fecha) as fecha, COUNT(*) as cantidad, SUM(total) as total
                                   FROM compras
                                   WHERE fecha >= ? AND fecha <= ?
                                   GROUP BY DATE(fecha)
                                   ORDER BY fecha DESC
                                   LIMIT 7");
$stmt_compras_dia->execute([date('Y-m-d', strtotime('-7 days')), date('Y-m-d') . ' 23:59:59']);
$compras_dia = $stmt_compras_dia->fetchAll();

// Obtener cajas cerradas en el rango de fechas
$stmt_cajas = $pdo->prepare("SELECT * FROM caja 
                             WHERE estado='Cerrada' AND fecha >= ? AND fecha <= ?
                             ORDER BY fecha DESC");
$stmt_cajas->execute([$fecha_desde, $fecha_hasta . ' 23:59:59']);
$cajas_cerradas = $stmt_cajas->fetchAll();

// Calcular saldo total de cajas
$saldo_total = 0;
foreach($cajas_cerradas as $caja) {
    $saldo_total += ($caja['monto_final'] - $caja['monto_inicial']);
}

$total_ventas = $stats_ventas['monto_total'] ? (float)$stats_ventas['monto_total'] : 0;
$total_compras = $stats_compras['monto_total'] ? (float)$stats_compras['monto_total'] : 0;
$ganancia_neta = $total_ventas - $total_compras;
?>

<div class="container tabla-responsive">
    <h1><i class="fas fa-chart-bar"></i> Reportes y Estadísticas</h1>

    <!-- Filtro de fechas -->
    <div class="form-container" style="margin-bottom: 30px;">
        <form method="GET" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1 1 200px;">
                <label>Fecha Desde:</label>
                <input type="date" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde) ?>" class="form-input-horizontal" required>
            </div>
            <div style="flex: 1 1 200px;">
                <label>Fecha Hasta:</label>
                <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta) ?>" class="form-input-horizontal" required>
            </div>
            <div>
                <button type="submit" class="btn-submit"><i class="fas fa-filter"></i> Filtrar</button>
            </div>
            <div>
                <a href="generar_reporte_pdf.php?fecha_desde=<?= urlencode($fecha_desde) ?>&fecha_hasta=<?= urlencode($fecha_hasta) ?>" 
                   class="btn-export" target="_blank">
                    <i class="fas fa-file-pdf"></i> Generar Reporte PDF
                </a>
            </div>
        </form>
    </div>

    <!-- Cuadros de estadísticas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 10px;">TOTAL VENTAS</div>
            <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">
                <?= number_format($total_ventas, 0, ',', '.') ?> Gs
            </div>
            <div style="font-size: 14px; opacity: 0.9;">
                <?= $stats_ventas['total'] ? $stats_ventas['total'] : 0 ?> facturas
            </div>
        </div>

        <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 10px;">TOTAL COMPRAS</div>
            <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">
                <?= number_format($total_compras, 0, ',', '.') ?> Gs
            </div>
            <div style="font-size: 14px; opacity: 0.9;">
                <?= $stats_compras['total'] ? $stats_compras['total'] : 0 ?> compras
            </div>
        </div>

        <div style="background: linear-gradient(135deg, <?= $ganancia_neta >= 0 ? '#3b82f6' : '#ef4444' ?> 0%, <?= $ganancia_neta >= 0 ? '#2563eb' : '#dc2626' ?> 100%); color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 10px;">GANANCIA NETA</div>
            <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">
                <?= number_format($ganancia_neta, 0, ',', '.') ?> Gs
            </div>
            <div style="font-size: 14px; opacity: 0.9;">
                <?= $ganancia_neta >= 0 ? 'Ganancia' : 'Pérdida' ?>
            </div>
        </div>

        <?php if(!empty($cajas_cerradas)): ?>
        <?php 
        $caja_primera = $cajas_cerradas[count($cajas_cerradas) - 1];
        $caja_ultima = $cajas_cerradas[0];
        $monto_inicial = $caja_primera['monto_inicial'];
        $monto_final = $caja_ultima['monto_final'];
        ?>
        <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 10px;">CAJA</div>
            <div style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">
                Inicial: <?= number_format($monto_inicial, 0, ',', '.') ?> Gs
            </div>
            <div style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">
                Final: <?= number_format($monto_final, 0, ',', '.') ?> Gs
            </div>
            <div style="font-size: 14px; opacity: 0.9;">
                Saldo: <?= number_format($saldo_total, 0, ',', '.') ?> Gs
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Gráficos -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; margin-bottom: 20px;"><i class="fas fa-chart-line"></i> Ventas por Día (Últimos 7 días)</h3>
            <canvas id="chartVentas" style="max-height: 300px;"></canvas>
        </div>

        <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; margin-bottom: 20px;"><i class="fas fa-chart-line"></i> Compras por Día (Últimos 7 días)</h3>
            <canvas id="chartCompras" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <!-- Resumen de cajas -->
    <?php if(!empty($cajas_cerradas)): ?>
    <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0;"><i class="fas fa-cash-register"></i> Resumen de Cajas Cerradas</h3>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Monto Inicial</th>
                    <th>Monto Final</th>
                    <th>Saldo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($cajas_cerradas as $caja): ?>
                <?php 
                $saldo_caja = $caja['monto_final'] - $caja['monto_inicial'];
                ?>
                <tr style="<?= $saldo_caja < 0 ? 'background-color: #fee2e2;' : '' ?>">
                    <td><?= date('d/m/Y H:i', strtotime($caja['fecha'])) ?></td>
                    <td><?= number_format($caja['monto_inicial'], 0, ',', '.') ?> Gs</td>
                    <td><?= number_format($caja['monto_final'], 0, ',', '.') ?> Gs</td>
                    <td style="font-weight: bold; <?= $saldo_caja < 0 ? 'color: #dc2626;' : 'color: #10b981;' ?>">
                        <?= $saldo_caja >= 0 ? '+' : '' ?><?= number_format($saldo_caja, 0, ',', '.') ?> Gs
                    </td>
                    <td>
                        <span style="padding: 3px 8px; border-radius: 3px; font-size: 11px; background: <?= $saldo_caja < 0 ? '#dc2626' : '#10b981' ?>; color: white;">
                            <?= $saldo_caja < 0 ? 'Pérdida' : 'Ganancia' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Preparar datos para gráfico de ventas
var ventasData = <?php 
    $labels = [];
    $data = [];
    foreach(array_reverse($ventas_dia) as $v) {
        $labels[] = date('d/m', strtotime($v['fecha']));
        $data[] = (float)$v['total'];
    }
    echo json_encode(['labels' => $labels, 'data' => $data]);
?>;

// Preparar datos para gráfico de compras
var comprasData = <?php 
    $labels = [];
    $data = [];
    foreach(array_reverse($compras_dia) as $c) {
        $labels[] = date('d/m', strtotime($c['fecha']));
        $data[] = (float)$c['total'];
    }
    echo json_encode(['labels' => $labels, 'data' => $data]);
?>;

// Gráfico de ventas
var ctxVentas = document.getElementById('chartVentas').getContext('2d');
var chartVentas = new Chart(ctxVentas, {
    type: 'line',
    data: {
        labels: ventasData.labels,
        datasets: [{
            label: 'Ventas (Gs)',
            data: ventasData.data,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('es-PY').format(value) + ' Gs';
                    }
                }
            }
        }
    }
});

// Gráfico de compras
var ctxCompras = document.getElementById('chartCompras').getContext('2d');
var chartCompras = new Chart(ctxCompras, {
    type: 'line',
    data: {
        labels: comprasData.labels,
        datasets: [{
            label: 'Compras (Gs)',
            data: comprasData.data,
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('es-PY').format(value) + ' Gs';
                    }
                }
            }
        }
    }
});
</script>

<?php include $base_path . 'includes/footer.php'; ?>
