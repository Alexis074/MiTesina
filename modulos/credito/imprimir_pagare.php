<?php
date_default_timezone_set('America/Asuncion');
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';

$pagare_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$pagare_id) {
    echo "Pagaré no especificado.";
    exit();
}

// Obtener información del pagaré
$stmt_pagare = $pdo->prepare("SELECT p.*, 
                               cl.nombre, cl.apellido, cl.telefono, cl.direccion, cl.ruc,
                               fv.numero_factura
                               FROM pagares p
                               JOIN clientes cl ON p.cliente_id = cl.id
                               LEFT JOIN ventas_credito vc ON p.venta_credito_id = vc.id
                               LEFT JOIN cabecera_factura_ventas fv ON vc.factura_id = fv.id
                               WHERE p.id = ?");
$stmt_pagare->execute([$pagare_id]);
$pagare = $stmt_pagare->fetch();

if (!$pagare) {
    echo "Pagaré no encontrado.";
    exit();
}

// Datos de la empresa
$empresa = array(
    'nombre' => 'Repuestos Doble A',
    'ruc' => '80012345-6',
    'direccion' => 'Av. Mcal. Estigarribia e/ Mcal. Lopez, San Ignacio, Paraguay',
    'telefono' => '(021) 457-4967'
);

// Meses en español
$meses = array(1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
               7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre');
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pagaré N° <?php echo htmlspecialchars($pagare['numero_pagare']); ?></title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: white; }
.pagare-container { width: 800px; margin: 0 auto; background: white; padding: 40px; border: 2px solid #000; }
.header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #000; padding-bottom: 20px; }
.empresa-nombre { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
.titulo { font-size: 28px; font-weight: bold; text-align: center; margin: 30px 0; text-decoration: underline; }
.texto-pagare { font-size: 14px; line-height: 1.8; margin: 20px 0; text-align: justify; }
.datos-section { margin: 30px 0; }
.datos-row { margin: 15px 0; }
.datos-label { font-weight: bold; display: inline-block; width: 200px; }
.firma-section { margin-top: 60px; display: flex; justify-content: space-between; }
.firma-box { width: 45%; text-align: center; border-top: 1px solid #000; padding-top: 10px; }
@media print { body { margin: 0; padding: 0; } }
</style>
</head>
<body onload="window.print();">

<div class="pagare-container">
    <div class="header">
        <div class="empresa-nombre"><?= htmlspecialchars($empresa['nombre']) ?></div>
        <div style="font-size: 13px;">RUC: <?= htmlspecialchars($empresa['ruc']) ?></div>
    </div>

    <div class="titulo">PAGARÉ</div>

    <div class="texto-pagare">
        <p>
            Por medio del presente documento, yo <strong><?= htmlspecialchars($pagare['nombre'] . ' ' . $pagare['apellido']) ?></strong>,
            <?php if($pagare['ruc']): ?>
            con RUC N° <strong><?= htmlspecialchars($pagare['ruc']) ?></strong>,
            <?php endif; ?>
            domiciliado en <strong><?= htmlspecialchars($pagare['direccion']) ?></strong>,
        </p>
        
        <p>
            Me obligo a pagar incondicionalmente a la orden de <strong><?= htmlspecialchars($empresa['nombre']) ?></strong>,
            RUC N° <strong><?= htmlspecialchars($empresa['ruc']) ?></strong>,
            la suma de <strong><?= number_format($pagare['monto_total'], 0, ',', '.') ?> Guaraníes (<?= number_format($pagare['monto_total'], 0, ',', '.') ?> Gs)</strong>,
        </p>
        
        <p>
            <?php if($pagare['numero_factura']): ?>
            en virtud de la factura N° <strong><?= htmlspecialchars($pagare['numero_factura']) ?></strong>,
            <?php endif; ?>
            el día <strong><?= date('d', strtotime($pagare['fecha_vencimiento'])) ?></strong> 
            de <strong><?= $meses[(int)date('n', strtotime($pagare['fecha_vencimiento']))] ?></strong> 
            de <strong><?= date('Y', strtotime($pagare['fecha_vencimiento'])) ?></strong>,
            en <strong><?= htmlspecialchars($pagare['lugar_pago']) ?></strong>.
        </p>
        
        <p>
            En caso de incumplimiento, me comprometo a pagar los intereses y gastos que correspondan según la legislación vigente.
        </p>
    </div>

    <div class="datos-section">
        <div class="datos-row">
            <span class="datos-label">N° de Pagaré:</span>
            <span><?= htmlspecialchars($pagare['numero_pagare']) ?></span>
        </div>
        <div class="datos-row">
            <span class="datos-label">Fecha de Emisión:</span>
            <span><?= date('d/m/Y', strtotime($pagare['fecha_emision'])) ?></span>
        </div>
        <div class="datos-row">
            <span class="datos-label">Fecha de Vencimiento:</span>
            <span><?= date('d/m/Y', strtotime($pagare['fecha_vencimiento'])) ?></span>
        </div>
        <div class="datos-row">
            <span class="datos-label">Lugar de Pago:</span>
            <span><?= htmlspecialchars($pagare['lugar_pago']) ?></span>
        </div>
        <div class="datos-row">
            <span class="datos-label">Monto Total:</span>
            <span><strong><?= number_format($pagare['monto_total'], 0, ',', '.') ?> Gs</strong></span>
        </div>
    </div>

    <div class="firma-section">
        <div class="firma-box">
            <strong>DEUDOR</strong><br><br><br>
            <?= htmlspecialchars($pagare['nombre'] . ' ' . $pagare['apellido']) ?><br>
            <?php if($pagare['ruc']): ?>
            RUC: <?= htmlspecialchars($pagare['ruc']) ?><br>
            <?php endif; ?>
            <?= htmlspecialchars($pagare['direccion']) ?>
        </div>
        <div class="firma-box">
            <strong>ACEPTADO</strong><br><br><br>
            <?= htmlspecialchars($empresa['nombre']) ?><br>
            RUC: <?= htmlspecialchars($empresa['ruc']) ?>
        </div>
    </div>

    <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #000; text-align: center; font-size: 12px;">
        <strong>** Este documento es un título de crédito **</strong><br>
        Generado por el sistema de gestión de Repuestos Doble A
    </div>
</div>

</body>
</html>

