<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';

$recibo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$recibo_id) {
    echo "Recibo no especificado.";
    exit();
}

// Obtener información del recibo
$stmt_recibo = $pdo->prepare("SELECT r.*, 
                              cl.nombre, cl.apellido, cl.telefono, cl.direccion, cl.ruc,
                              c.numero_cuota, c.monto as monto_cuota,
                              fv.numero_factura
                              FROM recibos_dinero r
                              JOIN clientes cl ON r.cliente_id = cl.id
                              LEFT JOIN cuotas_credito c ON r.cuota_id = c.id
                              LEFT JOIN ventas_credito vc ON r.venta_credito_id = vc.id
                              LEFT JOIN cabecera_factura_ventas fv ON vc.factura_id = fv.id
                              WHERE r.id = ?");
$stmt_recibo->execute([$recibo_id]);
$recibo = $stmt_recibo->fetch();

if (!$recibo) {
    echo "Recibo no encontrado.";
    exit();
}

// Datos de la empresa
$empresa = array(
    'nombre' => 'Repuestos Doble A',
    'ruc' => '80012345-6',
    'direccion' => 'Av. Mcal. Estigarribia e/ Mcal. Lopez, San Ignacio, Paraguay',
    'telefono' => '(021) 457-4967',
    'email' => 'contacto@repuestosdoblea.com'
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Recibo de Pago N° <?php echo htmlspecialchars($recibo['numero_recibo']); ?></title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
body { font-family: Arial, sans-serif; margin: 20px; padding: 0; background: #f7f7f7; }
.recibo-container { width: 800px; margin: 20px auto; background: white; padding: 40px; border: 2px solid #000; box-shadow: 0 0 10px rgba(0,0,0,0.2); }
.header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #000; padding-bottom: 20px; }
.empresa-nombre { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
.empresa-datos { font-size: 13px; margin-bottom: 5px; }
.titulo { font-size: 28px; font-weight: bold; text-align: center; margin: 30px 0; text-decoration: underline; }
.datos-section { display: flex; justify-content: space-between; margin: 20px 0; }
.datos-left, .datos-right { width: 48%; }
.datos-label { font-weight: bold; margin-bottom: 5px; }
.datos-value { margin-bottom: 10px; }
.table-recibo { width: 100%; border-collapse: collapse; margin: 30px 0; }
.table-recibo th, .table-recibo td { border: 1px solid #000; padding: 12px; text-align: left; }
.table-recibo th { background: #f0f0f0; font-weight: bold; }
.total-section { text-align: right; margin-top: 20px; font-size: 18px; font-weight: bold; }
.footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #000; text-align: center; font-size: 12px; }
.firma-section { margin-top: 60px; display: flex; justify-content: space-between; }
.firma-box { width: 45%; text-align: center; border-top: 1px solid #000; padding-top: 10px; }
.btn-print { display: inline-block; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
.btn-print:hover { background: #1e40af; }
@media print { .btn-print { display: none; } body { background: white; } }
</style>
</head>
<body onload="window.print();">

<div class="recibo-container">
    <div class="header">
        <div class="empresa-nombre"><?= htmlspecialchars($empresa['nombre']) ?></div>
        <div class="empresa-datos">RUC: <?= htmlspecialchars($empresa['ruc']) ?></div>
        <div class="empresa-datos"><?= htmlspecialchars($empresa['direccion']) ?></div>
        <div class="empresa-datos">Tel: <?= htmlspecialchars($empresa['telefono']) ?> | Email: <?= htmlspecialchars($empresa['email']) ?></div>
    </div>

    <div class="titulo">RECIBO DE DINERO</div>

    <div class="datos-section">
        <div class="datos-left">
            <div class="datos-label">N° de Recibo:</div>
            <div class="datos-value"><?= htmlspecialchars($recibo['numero_recibo']) ?></div>
            
            <div class="datos-label">Fecha:</div>
            <div class="datos-value"><?= date('d/m/Y H:i', strtotime($recibo['fecha_pago'])) ?></div>
            
            <div class="datos-label">Cliente:</div>
            <div class="datos-value">
                <?= htmlspecialchars($recibo['nombre'] . ' ' . $recibo['apellido']) ?><br>
                <?php if($recibo['ruc']): ?>
                RUC: <?= htmlspecialchars($recibo['ruc']) ?><br>
                <?php endif; ?>
                <?php if($recibo['direccion']): ?>
                <?= htmlspecialchars($recibo['direccion']) ?><br>
                <?php endif; ?>
                <?php if($recibo['telefono']): ?>
                Tel: <?= htmlspecialchars($recibo['telefono']) ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="datos-right">
            <div class="datos-label">Forma de Pago:</div>
            <div class="datos-value"><?= htmlspecialchars($recibo['forma_pago']) ?></div>
            
            <?php if($recibo['numero_factura']): ?>
            <div class="datos-label">Factura:</div>
            <div class="datos-value"><?= htmlspecialchars($recibo['numero_factura']) ?></div>
            <?php endif; ?>
            
            <?php if($recibo['numero_cuota']): ?>
            <div class="datos-label">Cuota:</div>
            <div class="datos-value">Cuota #<?= $recibo['numero_cuota'] ?></div>
            <?php endif; ?>
        </div>
    </div>

    <table class="table-recibo">
        <thead>
            <tr>
                <th>Concepto</th>
                <th style="text-align: right;">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= htmlspecialchars($recibo['concepto']) ?></td>
                <td style="text-align: right;"><?= number_format($recibo['monto'], 0, ',', '.') ?> Gs</td>
            </tr>
        </tbody>
    </table>

    <div class="total-section">
        TOTAL RECIBIDO: <?= number_format($recibo['monto'], 0, ',', '.') ?> Gs
    </div>

    <?php if($recibo['observaciones']): ?>
    <div style="margin-top: 20px; padding: 10px; background: #f9fafb; border-left: 4px solid #2563eb;">
        <strong>Observaciones:</strong><br>
        <?= nl2br(htmlspecialchars($recibo['observaciones'])) ?>
    </div>
    <?php endif; ?>

    <div class="firma-section">
        <div class="firma-box">
            <strong>RECIBÍ CONFORME</strong><br><br><br>
            <?= htmlspecialchars($recibo['nombre'] . ' ' . $recibo['apellido']) ?>
        </div>
        <div class="firma-box">
            <strong>ENTREGADO POR</strong><br><br><br>
            <?= htmlspecialchars($empresa['nombre']) ?>
        </div>
    </div>

    <div class="footer">
        <strong>** Este documento es un comprobante de pago **</strong><br>
        Generado por el sistema de gestión de Repuestos Doble A<br>
        Fecha de impresión: <?= date('d/m/Y H:i:s') ?>
    </div>
</div>

<div style="text-align: center; margin: 20px;">
    <a href="imprimir_recibo.php?id=<?= $recibo_id ?>" target="_blank" class="btn-print">
        <i class="fas fa-print"></i> Imprimir Recibo
    </a>
    <a href="cuotas.php" class="btn-cancelar" style="display: inline-block; padding: 10px 20px;">
        <i class="fas fa-arrow-left"></i> Volver a Cuotas
    </a>
</div>

</body>
</html>

