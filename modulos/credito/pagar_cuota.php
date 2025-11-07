<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('ventas', 'crear');
include $base_path . 'includes/header.php';

$mensaje = "";
$cuota_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cuota_id) {
    header("Location: cuotas.php");
    exit();
}

// Obtener información de la cuota
try {
    $stmt_cuota = $pdo->prepare("SELECT c.*, vc.factura_id, vc.cliente_id, vc.monto_total, vc.numero_cuotas,
                                  cl.nombre, cl.apellido, cl.telefono, cl.email, cl.direccion, cl.ruc,
                                  fv.numero_factura
                                  FROM cuotas_credito c
                                  JOIN ventas_credito vc ON c.venta_credito_id = vc.id
                                  JOIN clientes cl ON vc.cliente_id = cl.id
                                  JOIN cabecera_factura_ventas fv ON vc.factura_id = fv.id
                                  WHERE c.id = ?");
    $stmt_cuota->execute([$cuota_id]);
    $cuota = $stmt_cuota->fetch();
    
    if (!$cuota) {
        $mensaje = "Error: Cuota no encontrada.";
    }
} catch (Exception $e) {
    $mensaje = "Error: " . $e->getMessage();
}

// Procesar pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cuota) {
    $monto_pagado = isset($_POST['monto_pagado']) ? (float)$_POST['monto_pagado'] : 0;
    $forma_pago = isset($_POST['forma_pago']) ? $_POST['forma_pago'] : 'Efectivo';
    $observaciones = isset($_POST['observaciones']) ? $_POST['observaciones'] : '';
    
    if ($monto_pagado <= 0) {
        $mensaje = "Error: El monto a pagar debe ser mayor a 0.";
    } elseif ($monto_pagado > ((float)$cuota['monto'] - (float)$cuota['monto_pagado'])) {
        $mensaje = "Error: El monto a pagar no puede ser mayor al monto pendiente.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Obtener usuario actual
            $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
            
            // Actualizar cuota
            $nuevo_monto_pagado = (float)$cuota['monto_pagado'] + $monto_pagado;
            $estado_cuota = ($nuevo_monto_pagado >= (float)$cuota['monto']) ? 'Pagada' : $cuota['estado'];
            
            $stmt_update = $pdo->prepare("UPDATE cuotas_credito 
                                         SET monto_pagado = ?, estado = ?, fecha_pago = NOW(), observaciones = ?
                                         WHERE id = ?");
            $stmt_update->execute([$nuevo_monto_pagado, $estado_cuota, $observaciones, $cuota_id]);
            
            // Generar número de recibo único
            $numero_recibo = 'REC-' . date('Y') . '-' . str_pad($cuota_id, 6, '0', STR_PAD_LEFT);
            
            // Verificar que el número de recibo no exista
            $stmt_verificar = $pdo->prepare("SELECT id FROM recibos_dinero WHERE numero_recibo = ?");
            $stmt_verificar->execute([$numero_recibo]);
            if ($stmt_verificar->fetch()) {
                $numero_recibo = 'REC-' . date('Y') . '-' . str_pad($cuota_id . rand(100, 999), 6, '0', STR_PAD_LEFT);
            }
            
            // Crear recibo de dinero
            $stmt_recibo = $pdo->prepare("INSERT INTO recibos_dinero 
                                         (numero_recibo, cliente_id, venta_credito_id, cuota_id, monto, fecha_pago, forma_pago, concepto, observaciones, usuario_id)
                                         VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)");
            $concepto = "Pago de cuota #" . $cuota['numero_cuota'] . " - Factura " . $cuota['numero_factura'];
            $stmt_recibo->execute([
                $numero_recibo,
                $cuota['cliente_id'],
                $cuota['venta_credito_id'],
                $cuota_id,
                $monto_pagado,
                $forma_pago,
                $concepto,
                $observaciones,
                $usuario_id
            ]);
            $recibo_id = $pdo->lastInsertId();
            
            // Registrar en caja si está abierta
            $stmt_caja = $pdo->query("SELECT * FROM caja WHERE estado='Abierta' ORDER BY id DESC LIMIT 1");
            $caja_abierta = $stmt_caja->fetch();
            
            if ($caja_abierta) {
                $concepto_caja = "Pago cuota #" . $cuota['numero_cuota'] . " - Cliente: " . $cuota['nombre'] . " " . $cuota['apellido'];
                $stmt_movimiento = $pdo->prepare("INSERT INTO caja_movimientos 
                                                 (caja_id, fecha, tipo, concepto, monto)
                                                 VALUES (?, NOW(), 'Ingreso', ?, ?)");
                $stmt_movimiento->execute([$caja_abierta['id'], $concepto_caja, $monto_pagado]);
            }
            
            // Verificar si todas las cuotas están pagadas para finalizar el crédito
            $stmt_cuotas_restantes = $pdo->prepare("SELECT COUNT(*) as pendientes 
                                                    FROM cuotas_credito 
                                                    WHERE venta_credito_id = ? AND estado != 'Pagada'");
            $stmt_cuotas_restantes->execute([$cuota['venta_credito_id']]);
            $cuotas_restantes = $stmt_cuotas_restantes->fetch();
            
            if ($cuotas_restantes['pendientes'] == 0) {
                // Todas las cuotas pagadas, finalizar crédito
                $stmt_finalizar = $pdo->prepare("UPDATE ventas_credito 
                                                SET estado = 'Finalizada', fecha_finalizacion = NOW()
                                                WHERE id = ?");
                $stmt_finalizar->execute([$cuota['venta_credito_id']]);
                
                // Generar factura final y pagaré
                try {
                    // Obtener información de la venta a crédito
                    $stmt_venta = $pdo->prepare("SELECT vc.*, fv.numero_factura, cl.nombre, cl.apellido, cl.ruc, cl.direccion
                                                 FROM ventas_credito vc
                                                 JOIN cabecera_factura_ventas fv ON vc.factura_id = fv.id
                                                 JOIN clientes cl ON vc.cliente_id = cl.id
                                                 WHERE vc.id = ?");
                    $stmt_venta->execute([$cuota['venta_credito_id']]);
                    $venta_final = $stmt_venta->fetch();
                    
                    if ($venta_final) {
                        // Generar número de pagaré único
                        $numero_pagare = 'PAG-' . date('Y') . '-' . str_pad($venta_final['id'], 6, '0', STR_PAD_LEFT);
                        
                        // Verificar que no exista
                        $stmt_verificar_pagare = $pdo->prepare("SELECT id FROM pagares WHERE numero_pagare = ?");
                        $stmt_verificar_pagare->execute([$numero_pagare]);
                        if ($stmt_verificar_pagare->fetch()) {
                            $numero_pagare = 'PAG-' . date('Y') . '-' . str_pad($venta_final['id'] . rand(100, 999), 6, '0', STR_PAD_LEFT);
                        }
                        
                        // Calcular fecha de vencimiento del pagaré (30 días desde hoy)
                        $fecha_vencimiento_pagare = date('Y-m-d', strtotime('+30 days'));
                        
                        // Insertar pagaré
                        $stmt_pagare = $pdo->prepare("INSERT INTO pagares 
                                                     (venta_credito_id, numero_pagare, cliente_id, monto_total, fecha_emision, fecha_vencimiento, lugar_pago, estado)
                                                     VALUES (?, ?, ?, ?, CURDATE(), ?, 'San Ignacio, Paraguay', 'Vigente')");
                        $stmt_pagare->execute([
                            $venta_final['id'],
                            $numero_pagare,
                            $venta_final['cliente_id'],
                            $venta_final['monto_total'],
                            $fecha_vencimiento_pagare
                        ]);
                    }
                } catch (Exception $e) {
                    error_log("Error al generar pagaré: " . $e->getMessage());
                    // Continuar aunque falle la generación del pagaré
                }
            }
            
            // Registrar en auditoría
            include $base_path . 'includes/auditoria.php';
            registrarAuditoria('pagar', 'credito', 'Pago de cuota #' . $cuota['numero_cuota'] . ' - Cliente: ' . $cuota['nombre'] . ' ' . $cuota['apellido'] . ' - Monto: ' . number_format($monto_pagado, 0, ',', '.') . ' Gs - Recibo: ' . $numero_recibo);
            
            $pdo->commit();
            
            $_SESSION['mensaje'] = "Pago registrado correctamente. Recibo: $numero_recibo";
            header("Location: ver_recibo.php?id=" . $recibo_id);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = "Error al procesar el pago: " . $e->getMessage();
        }
    }
}

// Calcular monto pendiente
$monto_pendiente = 0;
if ($cuota) {
    $monto_pendiente = (float)$cuota['monto'] - (float)$cuota['monto_pagado'];
}
?>

<div class="container">
    <h1><i class="fas fa-money-bill-wave"></i> Pagar Cuota</h1>

    <?php if($mensaje != ""): ?>
        <div class="mensaje <?= strpos($mensaje,'Error')===false?'exito':'error' ?>"><?= $mensaje ?></div>
    <?php endif; ?>

    <?php if($cuota): ?>
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h2>Información de la Cuota</h2>
        <table style="width: 100%;">
            <tr>
                <td style="padding: 8px; font-weight: bold;">Cliente:</td>
                <td style="padding: 8px;"><?= htmlspecialchars($cuota['nombre'] . ' ' . $cuota['apellido']) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Factura:</td>
                <td style="padding: 8px;"><?= htmlspecialchars($cuota['numero_factura']) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Cuota:</td>
                <td style="padding: 8px;"><?= $cuota['numero_cuota'] ?>/<?= $cuota['numero_cuotas'] ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Monto Total:</td>
                <td style="padding: 8px;"><?= number_format($cuota['monto'], 0, ',', '.') ?> Gs</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Monto Pagado:</td>
                <td style="padding: 8px;"><?= number_format($cuota['monto_pagado'], 0, ',', '.') ?> Gs</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Monto Pendiente:</td>
                <td style="padding: 8px; color: #dc2626; font-size: 18px; font-weight: bold;">
                    <?= number_format($monto_pendiente, 0, ',', '.') ?> Gs
                </td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Fecha Vencimiento:</td>
                <td style="padding: 8px;">
                    <?= date('d/m/Y', strtotime($cuota['fecha_vencimiento'])) ?>
                    <?php 
                    $fecha_vencimiento = new DateTime($cuota['fecha_vencimiento']);
                    $hoy = new DateTime();
                    if ($fecha_vencimiento < $hoy): 
                        $dias = $hoy->diff($fecha_vencimiento)->days;
                    ?>
                        <span style="color: #dc2626;">(Vencida hace <?= $dias ?> días)</span>
                    <?php elseif ($fecha_vencimiento->diff($hoy)->days <= 7): ?>
                        <span style="color: #f59e0b;">(Por vencer)</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
        <h2>Registrar Pago</h2>
        <form method="POST">
            <div style="margin-bottom: 15px;">
                <label>Monto a Pagar:</label>
                <input type="number" name="monto_pagado" id="monto_pagado" 
                       class="form-input-horizontal" 
                       step="0.01" 
                       min="0.01" 
                       max="<?= $monto_pendiente ?>" 
                       value="<?= $monto_pendiente ?>" 
                       required>
                <small style="color: #6b7280;">Monto máximo: <?= number_format($monto_pendiente, 0, ',', '.') ?> Gs</small>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Forma de Pago:</label>
                <select name="forma_pago" class="form-select-horizontal" required>
                    <option value="Efectivo" selected>Efectivo</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="Transferencia">Transferencia</option>
                    <option value="Cheque">Cheque</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Observaciones:</label>
                <textarea name="observaciones" class="form-input-horizontal" rows="3" placeholder="Opcional"></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-check"></i> Registrar Pago
                </button>
                <a href="cuotas.php" class="btn-cancelar">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

