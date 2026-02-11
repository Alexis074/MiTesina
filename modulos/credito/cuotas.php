<?php
date_default_timezone_set('America/Asuncion');
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('ventas', 'ver');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cuotas de Crédito - Repuestos Doble A</title>
    <link rel="stylesheet" href="/repuestos/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<?php include $base_path . 'includes/header.php'; ?>

<?php
$mensaje = "";

// Crear tablas si no existen (compatible con MySQL 5.6)
function crearTablasCredito($pdo) {
    try {
        // Verificar y crear tabla ventas_credito
        $stmt = $pdo->query("SHOW TABLES LIKE 'ventas_credito'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("CREATE TABLE ventas_credito (
                id INT(11) NOT NULL AUTO_INCREMENT,
                factura_id INT(11) NULL,
                cliente_id INT(11) NOT NULL,
                monto_total DECIMAL(15,2) NOT NULL,
                numero_cuotas INT(11) NOT NULL,
                monto_cuota DECIMAL(15,2) NOT NULL,
                fecha_creacion DATETIME NOT NULL,
                estado ENUM('Activa','Finalizada','Cancelada') DEFAULT 'Activa',
                fecha_finalizacion DATETIME NULL,
                PRIMARY KEY (id),
                KEY factura_id (factura_id),
                KEY cliente_id (cliente_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
        } else {
            // Verificar y modificar la columna factura_id si no permite NULL
            try {
                $stmt_check = $pdo->query("SHOW COLUMNS FROM ventas_credito WHERE Field = 'factura_id'");
                $columna = $stmt_check->fetch(PDO::FETCH_ASSOC);
                if ($columna && strtoupper($columna['Null']) == 'NO') {
                    // Modificar la columna para permitir NULL
                    $pdo->exec("ALTER TABLE ventas_credito MODIFY factura_id INT(11) NULL");
                }
            } catch (Exception $e) {
                error_log("Error al verificar/modificar columna factura_id: " . $e->getMessage());
            }
        }
        
        // Verificar y crear tabla cuotas_credito
        $stmt = $pdo->query("SHOW TABLES LIKE 'cuotas_credito'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("CREATE TABLE cuotas_credito (
                id INT(11) NOT NULL AUTO_INCREMENT,
                venta_credito_id INT(11) NOT NULL,
                numero_cuota INT(11) NOT NULL,
                monto DECIMAL(15,2) NOT NULL,
                fecha_vencimiento DATE NOT NULL,
                fecha_pago DATETIME NULL,
                monto_pagado DECIMAL(15,2) DEFAULT 0,
                estado ENUM('Pendiente','Pagada','Vencida') DEFAULT 'Pendiente',
                observaciones TEXT NULL,
                PRIMARY KEY (id),
                KEY venta_credito_id (venta_credito_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
        }
        
        // Verificar y crear tabla recibos_dinero
        $stmt = $pdo->query("SHOW TABLES LIKE 'recibos_dinero'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("CREATE TABLE recibos_dinero (
                id INT(11) NOT NULL AUTO_INCREMENT,
                numero_recibo VARCHAR(50) NOT NULL,
                cliente_id INT(11) NOT NULL,
                venta_credito_id INT(11) NULL,
                cuota_id INT(11) NULL,
                monto DECIMAL(15,2) NOT NULL,
                fecha_pago DATETIME NOT NULL,
                forma_pago VARCHAR(50) NOT NULL,
                concepto VARCHAR(255) NOT NULL,
                observaciones TEXT NULL,
                usuario_id INT(11) NULL,
                PRIMARY KEY (id),
                UNIQUE KEY numero_recibo (numero_recibo),
                KEY cliente_id (cliente_id),
                KEY venta_credito_id (venta_credito_id),
                KEY cuota_id (cuota_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
        }
        
        // Verificar y crear tabla pagares
        $stmt = $pdo->query("SHOW TABLES LIKE 'pagares'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("CREATE TABLE pagares (
                id INT(11) NOT NULL AUTO_INCREMENT,
                venta_credito_id INT(11) NOT NULL,
                numero_pagare VARCHAR(50) NOT NULL,
                cliente_id INT(11) NOT NULL,
                monto_total DECIMAL(15,2) NOT NULL,
                fecha_emision DATE NOT NULL,
                fecha_vencimiento DATE NOT NULL,
                lugar_pago VARCHAR(255) NOT NULL,
                estado ENUM('Vigente','Cancelado') DEFAULT 'Vigente',
                observaciones TEXT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY numero_pagare (numero_pagare),
                KEY venta_credito_id (venta_credito_id),
                KEY cliente_id (cliente_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
        }
        
        // Verificar y crear tabla detalle_ventas_credito (para guardar productos sin factura)
        $stmt = $pdo->query("SHOW TABLES LIKE 'detalle_ventas_credito'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("CREATE TABLE detalle_ventas_credito (
                id INT(11) NOT NULL AUTO_INCREMENT,
                venta_credito_id INT(11) NOT NULL,
                producto_id INT(11) NOT NULL,
                cantidad DECIMAL(10,2) NOT NULL,
                precio_unitario DECIMAL(15,2) NOT NULL,
                valor_venta_5 DECIMAL(15,2) DEFAULT 0,
                valor_venta_10 DECIMAL(15,2) DEFAULT 0,
                valor_venta_exenta DECIMAL(15,2) DEFAULT 0,
                total_parcial DECIMAL(15,2) NOT NULL,
                PRIMARY KEY (id),
                KEY venta_credito_id (venta_credito_id),
                KEY producto_id (producto_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error al crear tablas de crédito: " . $e->getMessage());
        return false;
    }
}

// Verificar si las tablas existen, si no, crearlas
$tablas_existen = true;
try {
    $pdo->query("SELECT 1 FROM ventas_credito LIMIT 1");
    $pdo->query("SELECT 1 FROM cuotas_credito LIMIT 1");
} catch (Exception $e) {
    // Intentar crear las tablas
    if (crearTablasCredito($pdo)) {
        $tablas_existen = true;
        $mensaje = "Tablas de crédito creadas correctamente.";
    } else {
        $tablas_existen = false;
        $mensaje = "Error: No se pudieron crear las tablas de crédito. Por favor, contacte al administrador.";
    }
}

// Actualizar estado de cuotas vencidas
if ($tablas_existen) {
    try {
        $pdo->exec("UPDATE cuotas_credito SET estado='Vencida' WHERE estado='Pendiente' AND fecha_vencimiento < CURDATE()");
    } catch (Exception $e) {
        // Ignorar error si no se puede actualizar
    }
}

// Obtener cuotas pendientes y vencidas
$cuotas_pendientes = [];
$cuotas_vencidas = [];
$cuotas_proximas = [];
$todas_cuotas = [];

if ($tablas_existen) {
    try {
        // Todas las cuotas que no están completamente pagadas
        // LEFT JOIN porque factura_id puede ser NULL hasta que se complete el pago
        $stmt_vencidas = $pdo->query("SELECT c.*, vc.factura_id, vc.cliente_id, vc.monto_total, vc.numero_cuotas,
                                      cl.nombre, cl.apellido, cl.telefono, cl.email,
                                      COALESCE(fv.numero_factura, CONCAT('CREDITO-', vc.id)) as numero_factura
                                      FROM cuotas_credito c
                                      JOIN ventas_credito vc ON c.venta_credito_id = vc.id
                                      JOIN clientes cl ON vc.cliente_id = cl.id
                                      LEFT JOIN cabecera_factura_ventas fv ON vc.factura_id = fv.id
                                      WHERE vc.estado = 'Activa' 
                                      AND (c.estado IN ('Pendiente', 'Vencida') OR (c.monto_pagado < c.monto))
                                      ORDER BY c.fecha_vencimiento ASC");
        $todas_cuotas = $stmt_vencidas->fetchAll();
        
        $hoy = new DateTime();
        $hoy->setTime(0, 0, 0);
        $proxima_semana = clone $hoy;
        $proxima_semana->modify('+7 days');
        
        foreach ($todas_cuotas as $cuota) {
            $fecha_vencimiento = new DateTime($cuota['fecha_vencimiento']);
            $fecha_vencimiento->setTime(0, 0, 0);
            $monto_restante = (float)$cuota['monto'] - (float)$cuota['monto_pagado'];
            
            // Solo incluir si tiene monto pendiente
            if ($monto_restante > 0) {
                if ($cuota['estado'] == 'Vencida' || $fecha_vencimiento < $hoy) {
                    $cuotas_vencidas[] = $cuota;
                } elseif ($fecha_vencimiento <= $proxima_semana) {
                    $cuotas_proximas[] = $cuota;
                } else {
                    $cuotas_pendientes[] = $cuota;
                }
            }
        }
    } catch (Exception $e) {
        $mensaje = "Error al cargar cuotas: " . $e->getMessage();
    }
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
                <?php if(!$tablas_existen): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #dc2626;">
                            <i class="fas fa-exclamation-triangle"></i> Las tablas de crédito no existen. Por favor, contacte al administrador.
                        </td>
                    </tr>
                <?php elseif(empty($cuotas_vencidas) && empty($cuotas_proximas) && empty($cuotas_pendientes)): ?>
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

