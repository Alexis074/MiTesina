<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('ventas', 'ver');
include $base_path . 'includes/header.php';

$venta_credito_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$venta_credito_id) {
    header("Location: cuotas.php");
    exit();
}

// Obtener información de la venta a crédito
try {
    $stmt_credito = $pdo->prepare("SELECT vc.*, 
                                    cl.nombre, cl.apellido, cl.telefono, cl.email, cl.direccion, cl.ruc,
                                    COALESCE(fv.numero_factura, CONCAT('CREDITO-', vc.id)) as numero_factura,
                                    fv.fecha_hora
                                    FROM ventas_credito vc
                                    JOIN clientes cl ON vc.cliente_id = cl.id
                                    LEFT JOIN cabecera_factura_ventas fv ON vc.factura_id = fv.id
                                    WHERE vc.id = ?");
    $stmt_credito->execute([$venta_credito_id]);
    $venta_credito = $stmt_credito->fetch();
    
    if (!$venta_credito) {
        $mensaje = "Error: Venta a crédito no encontrada.";
    } else {
        // Obtener cuotas
        $stmt_cuotas = $pdo->prepare("SELECT * FROM cuotas_credito 
                                      WHERE venta_credito_id = ? 
                                      ORDER BY numero_cuota ASC");
        $stmt_cuotas->execute([$venta_credito_id]);
        $cuotas = $stmt_cuotas->fetchAll();
        
        // Obtener recibos
        $stmt_recibos = $pdo->prepare("SELECT * FROM recibos_dinero 
                                       WHERE venta_credito_id = ? 
                                       ORDER BY fecha_pago DESC");
        $stmt_recibos->execute([$venta_credito_id]);
        $recibos = $stmt_recibos->fetchAll();
        
        // Obtener pagaré si existe
        $stmt_pagare = $pdo->prepare("SELECT * FROM pagares 
                                      WHERE venta_credito_id = ? 
                                      ORDER BY id DESC LIMIT 1");
        $stmt_pagare->execute([$venta_credito_id]);
        $pagare = $stmt_pagare->fetch();
        
        // Calcular totales
        $total_pagado = 0;
        $total_pendiente = 0;
        foreach ($cuotas as $cuota) {
            $total_pagado += (float)$cuota['monto_pagado'];
            $total_pendiente += ((float)$cuota['monto'] - (float)$cuota['monto_pagado']);
        }
    }
} catch (Exception $e) {
    $mensaje = "Error: " . $e->getMessage();
}
?>

<div class="container">
    <h1><i class="fas fa-file-invoice"></i> Detalle de Venta a Crédito</h1>

    <?php if(isset($mensaje) && $mensaje != ""): ?>
        <div class="mensaje error"><?= $mensaje ?></div>
    <?php endif; ?>

    <?php if($venta_credito): ?>
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h2>Información del Crédito</h2>
        <table style="width: 100%;">
            <tr>
                <td style="padding: 8px; font-weight: bold;">Cliente:</td>
                <td style="padding: 8px;"><?= htmlspecialchars($venta_credito['nombre'] . ' ' . $venta_credito['apellido']) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Factura:</td>
                <td style="padding: 8px;"><?= htmlspecialchars($venta_credito['numero_factura']) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Monto Total:</td>
                <td style="padding: 8px;"><?= number_format($venta_credito['monto_total'], 0, ',', '.') ?> Gs</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Número de Cuotas:</td>
                <td style="padding: 8px;"><?= $venta_credito['numero_cuotas'] ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Monto por Cuota:</td>
                <td style="padding: 8px;"><?= number_format($venta_credito['monto_cuota'], 0, ',', '.') ?> Gs</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Total Pagado:</td>
                <td style="padding: 8px; color: #10b981; font-weight: bold;"><?= number_format($total_pagado, 0, ',', '.') ?> Gs</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Total Pendiente:</td>
                <td style="padding: 8px; color: #dc2626; font-weight: bold;"><?= number_format($total_pendiente, 0, ',', '.') ?> Gs</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Estado:</td>
                <td style="padding: 8px;">
                    <span style="background: <?= $venta_credito['estado'] == 'Finalizada' ? '#10b981' : '#3b82f6'; ?>; color: white; padding: 4px 8px; border-radius: 4px;">
                        <?= htmlspecialchars($venta_credito['estado']) ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h2>Cuotas</h2>
        <div class="table-responsive-inline">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Monto</th>
                        <th>Monto Pagado</th>
                        <th>Pendiente</th>
                        <th>Fecha Vencimiento</th>
                        <th>Fecha Pago</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($cuotas as $cuota): 
                    $monto_restante = (float)$cuota['monto'] - (float)$cuota['monto_pagado'];
                    $fecha_vencimiento = new DateTime($cuota['fecha_vencimiento']);
                    $hoy = new DateTime();
                    
                    $estado_color = '';
                    if ($cuota['estado'] == 'Pagada') {
                        $estado_color = '#10b981';
                    } elseif ($cuota['estado'] == 'Vencida' || $fecha_vencimiento < $hoy) {
                        $estado_color = '#dc2626';
                    } elseif ($fecha_vencimiento->diff($hoy)->days <= 7) {
                        $estado_color = '#f59e0b';
                    } else {
                        $estado_color = '#3b82f6';
                    }
                ?>
                    <tr>
                        <td><?= $cuota['numero_cuota'] ?></td>
                        <td><?= number_format($cuota['monto'], 0, ',', '.') ?> Gs</td>
                        <td><?= number_format($cuota['monto_pagado'], 0, ',', '.') ?> Gs</td>
                        <td><?= number_format($monto_restante, 0, ',', '.') ?> Gs</td>
                        <td><?= date('d/m/Y', strtotime($cuota['fecha_vencimiento'])) ?></td>
                        <td><?= $cuota['fecha_pago'] ? date('d/m/Y H:i', strtotime($cuota['fecha_pago'])) : '-' ?></td>
                        <td>
                            <span style="background: <?= $estado_color ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                <?= htmlspecialchars($cuota['estado']) ?>
                            </span>
                        </td>
                        <td class="acciones">
                            <?php if($cuota['estado'] != 'Pagada'): ?>
                            <a href="pagar_cuota.php?id=<?= $cuota['id'] ?>" class="btn btn-edit" data-tooltip="Pagar">
                                <i class="fas fa-money-bill-wave"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
        <h2>Recibos de Pago</h2>
        <div class="table-responsive-inline">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>N° Recibo</th>
                        <th>Fecha</th>
                        <th>Monto</th>
                        <th>Forma de Pago</th>
                        <th>Concepto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(empty($recibos)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No hay recibos registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($recibos as $recibo): ?>
                    <tr>
                        <td><?= htmlspecialchars($recibo['numero_recibo']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($recibo['fecha_pago'])) ?></td>
                        <td><?= number_format($recibo['monto'], 0, ',', '.') ?> Gs</td>
                        <td><?= htmlspecialchars($recibo['forma_pago']) ?></td>
                        <td><?= htmlspecialchars($recibo['concepto']) ?></td>
                        <td class="acciones">
                            <a href="ver_recibo.php?id=<?= $recibo['id'] ?>" class="btn btn-edit" data-tooltip="Ver" target="_blank">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="imprimir_recibo.php?id=<?= $recibo['id'] ?>" class="btn btn-edit" data-tooltip="Imprimir" target="_blank">
                                <i class="fas fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if($pagare): ?>
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px;">
        <h2>Pagaré</h2>
        <table style="width: 100%;">
            <tr>
                <td style="padding: 8px; font-weight: bold;">N° de Pagaré:</td>
                <td style="padding: 8px;"><?= htmlspecialchars($pagare['numero_pagare']) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Fecha de Emisión:</td>
                <td style="padding: 8px;"><?= date('d/m/Y', strtotime($pagare['fecha_emision'])) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Fecha de Vencimiento:</td>
                <td style="padding: 8px;"><?= date('d/m/Y', strtotime($pagare['fecha_vencimiento'])) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Monto:</td>
                <td style="padding: 8px;"><?= number_format($pagare['monto_total'], 0, ',', '.') ?> Gs</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Estado:</td>
                <td style="padding: 8px;">
                    <span style="background: <?= $pagare['estado'] == 'Cancelado' ? '#10b981' : '#3b82f6'; ?>; color: white; padding: 4px 8px; border-radius: 4px;">
                        <?= htmlspecialchars($pagare['estado']) ?>
                    </span>
                </td>
            </tr>
        </table>
        <div style="margin-top: 15px;">
            <a href="ver_pagare.php?id=<?= $pagare['id'] ?>" class="btn-submit" target="_blank">
                <i class="fas fa-eye"></i> Ver Pagaré
            </a>
            <a href="imprimir_pagare.php?id=<?= $pagare['id'] ?>" class="btn-export" target="_blank">
                <i class="fas fa-print"></i> Imprimir Pagaré
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div style="margin-top: 20px;">
        <a href="cuotas.php" class="btn-cancelar">
            <i class="fas fa-arrow-left"></i> Volver a Cuotas
        </a>
    </div>
    <?php endif; ?>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

