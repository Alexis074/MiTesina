<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';

// Compatibilidad para intdiv en PHP antiguo (PHP 5.6)
if (!function_exists('intdiv_compat')) {
    function intdiv_compat($a, $b) {
        if ($b == 0) return 0;
        return ($a >= 0) ? (int)floor($a / $b) : (int)ceil($a / $b);
    }
}

// Función para convertir número a letras en español (solo enteros)
function numero_a_letras($numero) {
    $numero = round($numero);
    if ($numero == 0) return 'CERO';

    $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    $especiales = [11=>'ONCE',12=>'DOCE',13=>'TRECE',14=>'CATORCE',15=>'QUINCE'];

    $convert_hasta_999 = function($n) use ($unidades, $decenas, $centenas, $especiales) {
        $s = '';
        if ($n == 100) return 'CIEN';
        if ($n > 100) {
            $c = intdiv_compat($n, 100);
            $s .= $centenas[$c] . ' ';
            $n = $n % 100;
        }
        if ($n >= 11 && $n <= 15) {
            $s .= $especiales[$n];
        } elseif ($n >= 16 && $n <= 19) {
            $s .= 'DIECI' . $unidades[$n - 10];
        } elseif ($n == 10 || $n == 20 || $n == 30 || $n == 40 || $n == 50 || $n == 60 || $n == 70 || $n == 80 || $n == 90) {
            $d = intdiv_compat($n, 10);
            $s .= $decenas[$d];
        } elseif ($n > 20 && $n < 30) {
            $s .= 'VEINTI' . $unidades[$n - 20];
        } elseif ($n > 30 && $n < 100) {
            $d = intdiv_compat($n, 10);
            $u = $n % 10;
            $s .= $decenas[$d];
            if ($u != 0) $s .= ' Y ' . $unidades[$u];
        } elseif ($n > 0 && $n < 10) {
            $s .= $unidades[$n];
        }
        return trim($s);
    };

    $partes = [];
    if ($numero >= 1000000) {
        $millones = intdiv_compat($numero, 1000000);
        $partes[] = ($millones == 1 ? 'UN MILLON' : numero_a_letras($millones) . ' MILLONES');
        $numero = $numero % 1000000;
    }
    if ($numero >= 1000) {
        $miles = intdiv_compat($numero, 1000);
        $partes[] = ($miles == 1 ? 'MIL' : $convert_hasta_999($miles) . ' MIL');
        $numero = $numero % 1000;
    }
    if ($numero > 0) {
        $partes[] = $convert_hasta_999($numero);
    }

    return implode(' ', $partes);
}

if(!isset($_GET['id'])) {
    echo "Factura no especificada.";
    exit;
}

$factura_id = (int)$_GET['id'];

// Obtener cabecera de factura de compra
$stmt_cab = $pdo->prepare("SELECT fc.*, p.empresa, p.direccion, p.telefono, p.email, p.ruc
                           FROM cabecera_factura_compras fc
                           JOIN proveedores p ON fc.proveedor_id = p.id
                           WHERE fc.id = :id");
$stmt_cab->execute(array('id' => $factura_id));
$factura = $stmt_cab->fetch(PDO::FETCH_ASSOC);

if(!$factura) {
    echo "Factura no encontrada.";
    exit;
}

// Datos de la empresa compradora (Repuestos Doble A)
$empresa_compradora = array(
    'nombre' => 'Repuestos Doble A',
    'ruc' => '80012345-6',
    'direccion' => 'Av. Mcal. Estigarribia e/ Mcal. Lopez, San Ignacio, Paraguay',
    'telefono' => '(021) 457-4967',
    'email' => 'contacto@repuestosdoblea.com'
);

// Datos del proveedor (emisor de la factura)
$proveedor = array(
    'nombre' => $factura['empresa'],
    'direccion' => $factura['direccion'],
    'telefono' => $factura['telefono'],
    'email' => isset($factura['email']) ? $factura['email'] : ''
);

// Obtener detalle de productos
$stmt_det = $pdo->prepare("SELECT d.*, p.nombre 
                           FROM detalle_factura_compras d
                           JOIN productos p ON d.producto_id = p.id
                           WHERE d.factura_id = :id");
$stmt_det->execute(array('id' => $factura_id));
$detalle = $stmt_det->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Factura de Compra N° <?php echo htmlspecialchars($factura['numero_factura']); ?></title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
body { font-family: Arial, sans-serif; margin:0; padding:0; background: #f7f7f7; }
.factura-container { width: 800px; margin: 20px auto; background: white; padding: 30px; border: 1px solid #ccc; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
.encabezado { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.empresa-datos { text-align: right; font-size: 13px; }
.empresa-datos-left { text-align: left; font-size: 13px; }
.titulo { text-align:center; background:#0b3d91; color:white; padding:5px 0; margin-top:10px; font-size:18px; border-radius:4px; }
.datos-cliente { display:flex; justify-content:space-between; margin-top:15px; font-size:13px; }
.factura-table { width:100%; border-collapse:collapse; margin-top:15px; font-size:13px; }
.factura-table th, .factura-table td { border:1px solid #000; padding:6px; text-align:center; color: #000}
.factura-table th { background:#e8e8e8; }
tfoot td { font-weight:bold; text-align:left; }
.timbrado { margin-top:10px; font-size:12px; text-align:left; }
.footer { text-align:center; font-size:12px; margin-top:20px; color:#555; }
@media print { .btn-print { display:none; } body{background:white;} }
.btn-print { display:inline-block; padding:8px 15px; background:#0b3d91; color:white; text-decoration:none; border-radius:4px; margin-bottom:15px; }
.titulo {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
</style>
</head>
<body onload="window.print();">

<div class="factura-container">

    <div class="encabezado">
        <div class="empresa-datos-left" style="width: 100%;">
            <strong><?php echo htmlspecialchars($proveedor['nombre']); ?></strong><br>
            <?php 
            // RUC del proveedor: usar el RUC de la base de datos, si no existe usar timbrado como fallback
            $ruc_proveedor = '';
            if (isset($factura['ruc']) && $factura['ruc']) {
                $ruc_proveedor = $factura['ruc'];
            } elseif (isset($factura['timbrado']) && $factura['timbrado']) {
                $ruc_proveedor = $factura['timbrado'];
            } else {
                $ruc_proveedor = str_pad($factura['proveedor_id'], 8, '0', STR_PAD_LEFT);
            }
            ?>
            RUC: <?php echo htmlspecialchars($ruc_proveedor); ?><br>
            <?php echo htmlspecialchars($proveedor['direccion']); ?><br>
            Tel: <?php echo htmlspecialchars($proveedor['telefono']); ?><br>
            <?php if($proveedor['email']): ?>
            Email: <?php echo htmlspecialchars($proveedor['email']); ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="titulo">FACTURA DE COMPRA</div>

    <div class="timbrado">
        <?php 
        $timbrado = isset($factura['timbrado']) && $factura['timbrado'] ? $factura['timbrado'] : '';
        $inicio_vigencia = isset($factura['inicio_vigencia']) && $factura['inicio_vigencia'] ? date('d/m/Y', strtotime($factura['inicio_vigencia'])) : '01/01/2025';
        $fin_vigencia = isset($factura['fin_vigencia']) && $factura['fin_vigencia'] ? date('d/m/Y', strtotime($factura['fin_vigencia'])) : '31/12/2025';
        ?>
        <?php if($timbrado): ?>
        <strong>Timbrado:</strong> <?php echo htmlspecialchars($timbrado); ?> |
        <?php endif; ?>
        <strong>Vigencia:</strong> <?php echo $inicio_vigencia; ?> al <?php echo $fin_vigencia; ?><br>
        <strong>N° de Factura:</strong> <?php echo htmlspecialchars($factura['numero_factura']); ?>
    </div>

    <div class="datos-cliente">
        <div>
            <strong>Nombre:</strong> <?php echo htmlspecialchars($empresa_compradora['nombre']); ?><br>
            <strong>RUC:</strong> <?php echo htmlspecialchars($empresa_compradora['ruc']); ?><br> 
            <strong>Dirección:</strong> <?php echo htmlspecialchars($empresa_compradora['direccion']); ?><br>
            <strong>Teléfono:</strong> <?php echo htmlspecialchars($empresa_compradora['telefono']); ?><br>
        </div>
        <div style="text-align:right;">
            <strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($factura['fecha_hora'])); ?><br>
            <strong>Condición:</strong> <?php echo htmlspecialchars($factura['condicion_compra']); ?><br>
            <strong>Forma de pago:</strong> <?php echo htmlspecialchars($factura['forma_pago']); ?>
        </div>
    </div>

    <table class="factura-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio Unitario</th>
                <th>IVA</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $subtotal_sin_iva = 0.0;
        $total_iva_5 = 0.0;
        $total_iva_10 = 0.0;
        $total_exenta = 0.0;
        $total_factura = 0.0;
        
        foreach($detalle as $d) {
            $precio_unitario = (float)$d['precio_unitario'];
            $cantidad = (float)$d['cantidad'];
            $subtotal_producto = (float)$d['subtotal'];
            
            // Calcular subtotal sin IVA (precio * cantidad)
            $subtotal_sin_iva_producto = $precio_unitario * $cantidad;
            $subtotal_sin_iva += $subtotal_sin_iva_producto;
            
            // Obtener valores de IVA (compatibilidad con registros antiguos)
            $valor_compra_5 = isset($d['valor_compra_5']) ? (float)$d['valor_compra_5'] : 0;
            $valor_compra_10 = isset($d['valor_compra_10']) ? (float)$d['valor_compra_10'] : 0;
            $valor_compra_exenta = isset($d['valor_compra_exenta']) ? (float)$d['valor_compra_exenta'] : 0;
            
            // Si no tiene IVA definido, calcular desde el total (compatibilidad con registros antiguos)
            if ($valor_compra_5 == 0 && $valor_compra_10 == 0 && $valor_compra_exenta == 0) {
                if ($subtotal_producto > $subtotal_sin_iva_producto) {
                    // Asumir que la diferencia es IVA 10% por compatibilidad
                    $valor_compra_10 = $subtotal_producto - $subtotal_sin_iva_producto;
                }
            }
            
            $total_iva_5 += $valor_compra_5;
            $total_iva_10 += $valor_compra_10;
            $total_exenta += $valor_compra_exenta;
            $total_factura += $subtotal_producto;
            
            $iva_texto = '';
            if ($valor_compra_5 > 0) {
                $iva_texto = '5%';
            } elseif ($valor_compra_10 > 0) {
                $iva_texto = '10%';
            } else {
                $iva_texto = 'Exenta';
            }
            
            // Mostrar el subtotal sin IVA en la tabla (solo precio * cantidad)
            echo '<tr>';
            echo '<td>'.htmlspecialchars($d['nombre']).'</td>';
            echo '<td>'.number_format($cantidad, 2, ',', '.').'</td>';
            echo '<td>'.number_format($precio_unitario,0,',','.').'</td>';
            echo '<td>'.$iva_texto.'</td>';
            echo '<td>'.number_format($subtotal_sin_iva_producto,0,',','.').'</td>';
            echo '</tr>';
        }
        
        // Calcular total correcto: subtotal sin IVA + IVAs
        $total_factura = $subtotal_sin_iva + $total_iva_5 + $total_iva_10 + $total_exenta;
        $total_letras = numero_a_letras($total_factura);
        ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right;"><strong>Subtotal:</strong></td>
                <td><strong><?php echo number_format($subtotal_sin_iva,0,',','.'); ?></strong></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:right;"><strong>Exenta:</strong></td>
                <td><strong><?php echo number_format($total_exenta,0,',','.'); ?></strong></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:right;"><strong>IVA 5%:</strong></td>
                <td><strong><?php echo number_format($total_iva_5,0,',','.'); ?></strong></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:right;"><strong>IVA 10%:</strong></td>
                <td><strong><?php echo number_format($total_iva_10,0,',','.'); ?></strong></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:right;"><strong>TOTAL:</strong></td>
                <td><strong><?php echo number_format($total_factura,0,',','.'); ?></strong></td>
            </tr>
            <tr><td colspan="5" style="text-align:left; font-weight:bold;">TOTAL (en letras): <?php echo $total_letras; ?></td></tr>
        </tfoot>
    </table>

    <div class="footer">
        ** Documento de compra - Repuestos Doble A **<br>
        Generado por el sistema de gestión de Repuestos Doble A
    </div>

</div>

</body>
</html>

