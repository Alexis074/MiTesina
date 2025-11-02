<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';

if(!isset($_GET['id'])) {
    echo "Factura no especificada";
    exit;
}

$factura_id = intval($_GET['id']);

// Datos de la empresa
$empresa = [
    'nombre' => 'Repuestos Doble A',
    'ruc' => '80012345-6',
    'direccion' => 'Av. Principal 123, Asunción, Paraguay',
    'telefono' => '(021) 123-4567'
];

// Obtener cabecera de factura
$stmt_cab = $pdo->prepare("SELECT fv.*, c.nombre, c.apellido, c.ruc 
                           FROM cabecera_factura_ventas fv
                           JOIN clientes c ON fv.cliente_id = c.id
                           WHERE fv.id = :id");
$stmt_cab->execute(['id' => $factura_id]);
$factura = $stmt_cab->fetch(PDO::FETCH_ASSOC);

if(!$factura) {
    echo "Factura no encontrada";
    exit;
}

// Obtener detalle de productos
$stmt_det = $pdo->prepare("SELECT d.*, p.nombre 
                           FROM detalle_factura_ventas d
                           JOIN productos p ON d.producto_id = p.id
                           WHERE d.factura_id = :id");
$stmt_det->execute(['id' => $factura_id]);
$detalle = $stmt_det->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ver Factura - Repuestos Doble A</title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
.container { padding:20px; margin-top:60px; }
.btn { display:inline-block; padding:10px 15px; background:#2563eb; color:white; text-decoration:none; border-radius:4px; }
.btn:hover { background:#1e40af; }
</style>
</head>
<body>
<div class="container">

    <!-- Cabecera de la factura -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <div>
            <img src="/repuestos/img/logo3.png" alt="Logo" style="height:80px;">
        </div>
        <div style="text-align:right;">
            <h2><?= htmlspecialchars($empresa['nombre']) ?></h2>
            <p>RUC: <?= $empresa['ruc'] ?></p>
            <p><?= $empresa['direccion'] ?></p>
            <p>Tel: <?= $empresa['telefono'] ?></p>
        </div>
    </div>
    <hr>

    <!-- Datos del cliente y de la factura -->
    <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
        <div>
            <p><strong>Cliente:</strong> <?= htmlspecialchars($factura['nombre'] . ' ' . $factura['apellido']) ?></p>
            <p><strong>RUC:</strong> <?= htmlspecialchars($factura['ruc']) ?></p>
        </div>
        <div style="text-align:right;">
            <p><strong>Factura N°:</strong> <?= htmlspecialchars($factura['numero_factura']) ?></p>
            <p><strong>Fecha:</strong> <?= $factura['fecha_hora'] ?></p>
            <p><strong>Condición:</strong> <?= $factura['condicion_venta'] ?></p>
            <p><strong>Pago:</strong> <?= $factura['forma_pago'] ?></p>
        </div>
    </div>

    <!-- Tabla de productos -->
    <table style="width:100%; border-collapse:collapse; margin-top:15px;">
        <thead>
            <tr style="background:#eee;">
                <th style="border:1px solid #000; padding:8px;">Producto</th>
                <th style="border:1px solid #000; padding:8px;">Cantidad</th>
                <th style="border:1px solid #000; padding:8px;">Precio Unitario</th>
                <th style="border:1px solid #000; padding:8px;">IVA</th>
                <th style="border:1px solid #000; padding:8px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($detalle as $d): ?>
            <tr>
                <td style="border:1px solid #000; padding:8px;"><?= htmlspecialchars($d['nombre']) ?></td>
                <td style="border:1px solid #000; padding:8px;"><?= $d['cantidad'] ?></td>
                <td style="border:1px solid #000; padding:8px;"><?= number_format($d['precio_unitario'], 2, ',', '.') ?></td>
                <td style="border:1px solid #000; padding:8px;"><?= $d['valor_venta_5']>0?'5%':($d['valor_venta_10']>0?'10%':'Exenta') ?></td>
                <td style="border:1px solid #000; padding:8px;"><?= number_format($d['total_parcial'], 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right; border:1px solid #000; padding:8px;"><strong>Total:</strong></td>
                <td style="border:1px solid #000; padding:8px;"><strong><?= number_format($factura['monto_total'], 2, ',', '.') ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <a href="imprimir_factura.php?id=<?= $factura_id ?>" target="_blank" class="btn" style="margin-top:15px;">Imprimir Factura</a>

</div>
<?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>
