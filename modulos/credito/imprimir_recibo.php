<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';

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

<?php
// Función para convertir número a letras
function numero_a_letras($numero) {
    $numero = round($numero);
    if ($numero == 0) return 'CERO GUARANIES';
    
    $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    $especiales = [11=>'ONCE',12=>'DOCE',13=>'TRECE',14=>'CATORCE',15=>'QUINCE'];
    
    $partes = [];
    if ($numero >= 1000000) {
        $millones = (int)floor($numero / 1000000);
        $partes[] = ($millones == 1 ? 'UN MILLON' : numero_a_letras($millones) . ' MILLONES');
        $numero = $numero % 1000000;
    }
    if ($numero >= 1000) {
        $miles = (int)floor($numero / 1000);
        if ($miles == 1) {
            $partes[] = 'MIL';
        } else {
            $partes[] = numero_a_letras($miles) . ' MIL';
        }
        $numero = $numero % 1000;
    }
    if ($numero > 0) {
        if ($numero == 100) {
            $partes[] = 'CIEN';
        } else {
            if ($numero > 100) {
                $c = (int)floor($numero / 100);
                $partes[] = $centenas[$c];
                $numero = $numero % 100;
            }
            if ($numero >= 11 && $numero <= 15) {
                $partes[] = $especiales[$numero];
            } elseif ($numero >= 16 && $numero <= 19) {
                $partes[] = 'DIECI' . $unidades[$numero - 10];
            } elseif ($numero == 10 || $numero == 20 || $numero == 30 || $numero == 40 || $numero == 50 || $numero == 60 || $numero == 70 || $numero == 80 || $numero == 90) {
                $d = (int)floor($numero / 10);
                $partes[] = $decenas[$d];
            } elseif ($numero > 20 && $numero < 30) {
                $partes[] = 'VEINTI' . $unidades[$numero - 20];
            } elseif ($numero > 30 && $numero < 100) {
                $d = (int)floor($numero / 10);
                $u = $numero % 10;
                $partes[] = $decenas[$d];
                if ($u != 0) $partes[] = $unidades[$u];
            } elseif ($numero > 0 && $numero < 10) {
                $partes[] = $unidades[$numero];
            }
        }
    }
    
    return implode(' ', $partes) . ' GUARANIES';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Recibo de Pago N° <?php echo htmlspecialchars($recibo['numero_recibo']); ?></title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: white; }
.recibo-container { width: 800px; margin: 0 auto; background: white; padding: 40px; border: 2px solid #000; }
.encabezado { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 15px; }
.encabezado img { height: 70px; }
.empresa-datos { text-align: right; font-size: 13px; }
.empresa-nombre { font-weight: bold; font-size: 18px; margin-bottom: 5px; }
.titulo { text-align: center; background: #0b3d91; color: white; padding: 10px 0; margin: 20px 0; font-size: 22px; font-weight: bold; border-radius: 4px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.datos-section { display: flex; justify-content: space-between; margin: 20px 0; font-size: 13px; }
.datos-left, .datos-right { width: 48%; }
.datos-label { font-weight: bold; margin-bottom: 3px; }
.datos-value { margin-bottom: 10px; padding: 5px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 3px; }
.table-recibo { width: 100%; border-collapse: collapse; margin: 25px 0; font-size: 13px; }
.table-recibo th, .table-recibo td { border: 1px solid #000; padding: 10px; text-align: left; }
.table-recibo th { background: #e8e8e8; font-weight: bold; text-align: center; }
.total-section { margin-top: 20px; padding: 15px; background: #f0f0f0; border: 2px solid #000; border-radius: 4px; }
.total-numero { text-align: right; font-size: 18px; font-weight: bold; margin-bottom: 10px; }
.total-letras { font-size: 13px; font-style: italic; text-align: center; padding: 10px; background: white; border: 1px solid #000; border-radius: 3px; }
.firma-section { margin-top: 50px; display: flex; justify-content: space-between; }
.firma-box { width: 45%; text-align: center; border-top: 2px solid #000; padding-top: 50px; }
.firma-box strong { font-size: 14px; }
.footer { margin-top: 40px; padding-top: 15px; border-top: 1px solid #ccc; text-align: center; font-size: 11px; color: #666; }
.observaciones { margin-top: 15px; padding: 10px; background: #f9fafb; border-left: 4px solid #2563eb; font-size: 12px; }
@media print { 
    body { margin: 0; padding: 0; }
    .recibo-container { box-shadow: none; margin: 0; padding: 30px; }
}
</style>
</head>
<body onload="window.print();">

<div class="recibo-container">
    <div class="encabezado">
        <div>
            <img src="/repuestos/img/logo3.png" alt="Logo" onerror="this.style.display='none'">
        </div>
        <div class="empresa-datos">
            <div class="empresa-nombre"><?= htmlspecialchars($empresa['nombre']) ?></div>
            <div>RUC: <?= htmlspecialchars($empresa['ruc']) ?></div>
            <div><?= htmlspecialchars($empresa['direccion']) ?></div>
            <div>Tel: <?= htmlspecialchars($empresa['telefono']) ?></div>
            <div>Email: <?= htmlspecialchars($empresa['email']) ?></div>
        </div>
    </div>

    <div class="titulo">RECIBO DE DINERO</div>

    <div class="datos-section">
        <div class="datos-left">
            <div class="datos-label">N° de Recibo:</div>
            <div class="datos-value"><?= htmlspecialchars($recibo['numero_recibo']) ?></div>
            
            <div class="datos-label">Fecha y Hora:</div>
            <div class="datos-value"><?= date('d/m/Y H:i', strtotime($recibo['fecha_pago'])) ?></div>
            
            <div class="datos-label">Cliente:</div>
            <div class="datos-value">
                <strong><?= htmlspecialchars($recibo['nombre'] . ' ' . $recibo['apellido']) ?></strong><br>
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
            
            <?php if($recibo['numero_factura'] && strpos($recibo['numero_factura'], 'CREDITO-') === false): ?>
            <div class="datos-label">Factura Relacionada:</div>
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
                <th style="width: 70%;">Concepto</th>
                <th style="width: 30%;">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= htmlspecialchars($recibo['concepto']) ?></td>
                <td style="text-align: right; font-weight: bold;"><?= number_format($recibo['monto'], 0, ',', '.') ?> Gs</td>
            </tr>
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-numero">
            TOTAL RECIBIDO: <?= number_format($recibo['monto'], 0, ',', '.') ?> Gs
        </div>
        <div class="total-letras">
            <strong>Son:</strong> <?= strtoupper(numero_a_letras($recibo['monto'])) ?>
        </div>
    </div>

    <?php if($recibo['observaciones']): ?>
    <div class="observaciones">
        <strong>Observaciones:</strong><br>
        <?= nl2br(htmlspecialchars($recibo['observaciones'])) ?>
    </div>
    <?php endif; ?>

    <div class="firma-section">
        <div class="firma-box">
            <strong>RECIBÍ CONFORME</strong><br><br><br>
            <div style="margin-top: 20px;">
                <?= htmlspecialchars($recibo['nombre'] . ' ' . $recibo['apellido']) ?>
            </div>
        </div>
        <div class="firma-box">
            <strong>ENTREGADO POR</strong><br><br><br>
            <div style="margin-top: 20px;">
                <?= htmlspecialchars($empresa['nombre']) ?>
            </div>
        </div>
    </div>

    <div class="footer">
        <strong>** Este documento es un comprobante de pago válido **</strong><br>
        Generado por el sistema de gestión de <?= htmlspecialchars($empresa['nombre']) ?><br>
        Fecha de impresión: <?= date('d/m/Y H:i:s') ?>
    </div>
</div>

</body>
</html>

