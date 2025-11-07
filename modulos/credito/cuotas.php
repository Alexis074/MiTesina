<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('ventas', 'ver');
include $base_path . 'includes/header.php';

$mensaje = "";

// Actualizar estado de cuotas vencidas
try {
    $pdo->exec("UPDATE cuotas_credito SET estado='Vencida' WHERE estado='Pendiente' AND fecha_vencimiento < CURDATE()");
} catch (Exception $e) {
    // Tabla no existe aún
}

// Obtener cuotas pendientes y vencidas
$cuotas_pendientes = [];
$cuotas_vencidas = [];
$cuotas_proximas = [];

try {
    // Todas las cuotas pendientes y vencidas
    $stmt_vencidas = $pdo->query("SELECT c.*, vc.factura_id, vc.cliente_id, vc.monto_total, vc.numero_cuotas,
                                  cl.nombre, cl.apellido, cl.telefono, cl.email,
                                  fv.numero_factura
                                  FROM cuotas_credito c
                                  JOIN ventas_credito vc ON c.venta_credito_id = vc.id
                                  JOIN clientes cl ON vc.cliente_id = cl.id
                                  JOIN cabecera_factura_ventas fv ON vc.factura_id = fv.id
                                  WHERE c.estado IN ('Pendiente', 'Vencida')
                                  ORDER BY c.fecha_vencimiento ASC");
    $todas_cuotas = $stmt_vencidas->fetchAll();
    
    $hoy = new DateTime();
    $proxima_semana = clone $hoy;
    $proxima_semana->modify('+7 days');
    
    foreach ($todas_cuotas as $cuota) {
        $fecha_vencimiento = new DateTime($cuota['fecha_vencimiento']);
        
        if ($cuota['estado'] == 'Vencida' || $fecha_vencimiento < $hoy) {
            $cuotas_vencidas[] = $cuota;
        } elseif ($fecha_vencimiento <= $proxima_semana) {
            $cuotas_proximas[] = $cuota;
        } else {
            $cuotas_pendientes[] = $cuota;
        }
    }
} catch (Exception $e) {
    $mensaje = "Error al cargar cuotas: " . $e->getMessage();
}

// Obtener totales
$total_pendiente = 0;
$total_vencido = 0;
$total_proximo = 0;

foreach ($cuotas_pendientes as $c) {
    $total_pendiente += (float)$c['monto'] - (float)$c['monto_pagado'];
}
foreach ($cuotas_vencidas as $c) {
    $total_vencido += (float)$c['monto'] - (float)$c['monto_pagado'];
}
foreach ($cuotas_proximas as $c) {
    $total_proximo += (float)$c['monto'] - (float)$c['monto_pagado'];
}
?>

<div class="container tabla-responsive">
    <h1><i class="fas fa-credit-card"></i> Gestión de Cuotas de Crédito</h1>

    <?php if($mensaje != ""): ?>
        <div class="mensaje <?= strpos($mensaje,'Error')===false?'exito':'error' ?>"><?= $mensaje ?></div>
    <?php endif; ?>

    <!-- Alertas -->
    <?php if(!empty($cuotas_vencidas)): ?>
    <div style="background: #fee2e2; border-left: 4px solid #dc2626; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
        <h3 style="margin: 0 0 10px 0; color: #991b1b;">
            <i class="fas fa-exclamation-triangle"></i> Cuotas Vencidas (<?= count($cuotas_vencidas) ?>)
        </h3>
        <p style="margin: 0; color: #991b1b;">
            Total vencido: <strong><?= number_format($total_vencido, 0, ',', '.') ?> Gs</strong>
        </p>
    </div>
    <?php endif; ?>

    <?php if(!empty($cuotas_proximas)): ?>
    <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
        <h3 style="margin: 0 0 10px 0; color: #92400e;">
            <i class="fas fa-clock"></i> Cuotas por Vencer (Próximos 7 días) (<?= count($cuotas_proximas) ?>)
        </h3>
        <p style="margin: 0; color: #92400e;">
            Total próximo: <strong><?= number_format($total_proximo, 0, ',', '.') ?> Gs</strong>
        </p>
    </div>
    <?php endif; ?>

    <!-- Resumen -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
        <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 20px; border-radius: 10px;">
            <div style="font-size: 14px; opacity: 0.9;">Total Pendiente</div>
            <div style="font-size: 24px; font-weight: bold;">
                <?= number_format($total_pendiente + $total_proximo + $total_vencido, 0, ',', '.') ?> Gs
            </div>
        </div>
        <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: white; padding: 20px; border-radius: 10px;">
            <div style="font-size: 14px; opacity: 0.9;">Vencidas</div>
            <div style="font-size: 24px; font-weight: bold;">
                <?= number_format($total_vencido, 0, ',', '.') ?> Gs
            </div>
        </div>
        <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 20px; border-radius: 10px;">
            <div style="font-size: 14px; opacity: 0.9;">Por Vencer</div>
            <div style="font-size: 24px; font-weight: bold;">
                <?= number_format($total_proximo, 0, ',', '.') ?> Gs
            </div>
        </div>
    </div>

    <!-- Tabla de cuotas -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0;"><i class="fas fa-list"></i> Cuotas Pendientes</h2>
        <div class="table-responsive-inline">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Factura</th>
                        <th>Cuota</th>
                        <th>Monto</th>
                        <th>Fecha Vencimiento</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(empty($todas_cuotas)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            No hay cuotas pendientes.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $todas = array_merge($cuotas_vencidas, $cuotas_proximas, $cuotas_pendientes);
                    foreach($todas as $cuota): 
                        $monto_restante = (float)$cuota['monto'] - (float)$cuota['monto_pagado'];
                        $fecha_vencimiento = new DateTime($cuota['fecha_vencimiento']);
                        $hoy = new DateTime();
                        $hoy->setTime(0, 0, 0);
                        $fecha_vencimiento->setTime(0, 0, 0);
                        
                        $dias_diferencia = $hoy->diff($fecha_vencimiento)->days;
                        $esta_vencida = $fecha_vencimiento < $hoy;
                        
                        $estado_color = '';
                        if ($cuota['estado'] == 'Vencida' || $esta_vencida) {
                            $estado_color = '#dc2626';
                            $estado_texto = 'Vencida';
                            $dias_mostrar = $dias_diferencia; // Días vencidos
                        } elseif ($dias_diferencia <= 7) {
                            $estado_color = '#f59e0b';
                            $estado_texto = 'Por Vencer';
                            $dias_mostrar = $dias_diferencia; // Días restantes
                        } else {
                            $estado_color = '#3b82f6';
                            $estado_texto = 'Pendiente';
                            $dias_mostrar = $dias_diferencia; // Días restantes
                        }
                    ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($cuota['nombre'] . ' ' . $cuota['apellido']) ?><br>
                                <small style="color: #6b7280;"><?= htmlspecialchars($cuota['telefono']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($cuota['numero_factura']) ?></td>
                            <td><?= $cuota['numero_cuota'] ?>/<?= $cuota['numero_cuotas'] ?></td>
                            <td><?= number_format($monto_restante, 0, ',', '.') ?> Gs</td>
                            <td style="color: <?= $estado_color ?>;">
                                <?= date('d/m/Y', strtotime($cuota['fecha_vencimiento'])) ?>
                                <?php if ($esta_vencida): ?>
                                    <br><small>(<?= $dias_mostrar ?> días atrás)</small>
                                <?php elseif ($dias_mostrar <= 7): ?>
                                    <br><small>(<?= $dias_mostrar ?> días)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="background: <?= $estado_color ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                    <?= $estado_texto ?>
                                </span>
                            </td>
                            <td class="acciones">
                                <a href="pagar_cuota.php?id=<?= $cuota['id'] ?>" class="btn btn-edit" data-tooltip="Pagar Cuota">
                                    <i class="fas fa-money-bill-wave"></i>
                                </a>
                                <a href="ver_detalle.php?id=<?= $cuota['venta_credito_id'] ?>" class="btn btn-edit" data-tooltip="Ver Detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

