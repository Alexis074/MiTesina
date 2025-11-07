<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';

// Compatibilidad para intdiv en PHP antiguo (PHP 5.6)
if (!function_exists('intdiv_compat')) {
    function intdiv_compat($a, $b) {
        if ($b == 0) return 0;
        return ($a >= 0) ? (int)floor($a / $b) : (int)ceil($a / $b);
    }
}

// Función para convertir número a letras en español (solo enteros)
function numero_a_letras($numero) {
    $numero = round($numero); // eliminamos decimales
    if ($numero == 0) return 'CERO';

    $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    $especiales = [11=>'ONCE',12=>'DOCE',13=>'TRECE',14=>'CATORCE',15=>'QUINCE'];

    $convert_hasta_999 = function($n) use ($unidades, $decenas, $centenas, $especiales) {
        $s = '';
        if ($n == 100) return 'CIEN';
        if ($n > 100) {
            $c = intdiv_compat($n, 100); // <-- USAR intdiv_compat
            $s .= $centenas[$c] . ' ';
            $n = $n % 100;
        }
        if ($n >= 11 && $n <= 15) {
            $s .= $especiales[$n];
        } elseif ($n >= 16 && $n <= 19) {
            $s .= 'DIECI' . $unidades[$n - 10];
        } elseif ($n == 10 || $n == 20 || $n == 30 || $n == 40 || $n == 50 || $n == 60 || $n == 70 || $n == 80 || $n == 90) {
            $d = intdiv_compat($n, 10); // <-- USAR intdiv_compat
            $s .= $decenas[$d];
        } elseif ($n > 20 && $n < 30) {
            $s .= 'VEINTI' . $unidades[$n - 20];
        } elseif ($n > 30 && $n < 100) {
            $d = intdiv_compat($n, 10); // <-- USAR intdiv_compat
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
        $millones = intdiv_compat($numero, 1000000); // <-- USAR intdiv_compat
        $partes[] = ($millones == 1 ? 'UN MILLON' : numero_a_letras($millones) . ' MILLONES');
        $numero = $numero % 1000000;
    }
    if ($numero >= 1000) {
        $miles = intdiv_compat($numero, 1000); // <-- USAR intdiv_compat
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

// Obtener cabecera de factura (incluye timbrado y fechas de vigencia)
$stmt_cab = $pdo->prepare("SELECT fv.*, c.nombre, c.apellido, c.ruc, c.direccion, c.telefono  
                           FROM cabecera_factura_ventas fv
                           JOIN clientes c ON fv.cliente_id = c.id
                           WHERE fv.id = :id");
$stmt_cab->execute(array('id' => $factura_id));
$factura = $stmt_cab->fetch(PDO::FETCH_ASSOC);

if(!$factura) {
    echo "Factura no encontrada.";
    exit;
}

// Datos de la empresa (usar timbrado y fechas de vigencia de la factura)
$empresa = array(
    'nombre' => 'Repuestos Doble A',
    'ruc' => '80012345-6',
    'direccion' => 'Av. Principal 123, Asunción, Paraguay',
    'telefono' => '(021) 123-4567',
    'email' => 'contacto@repuestosdoblea.com',
    'timbrado' => isset($factura['timbrado']) && !empty($factura['timbrado']) ? $factura['timbrado'] : '12345678',
    'inicio_vigencia' => isset($factura['inicio_vigencia']) && !empty($factura['inicio_vigencia']) ? date('d/m/Y', strtotime($factura['inicio_vigencia'])) : '01/01/2025',
    'fin_vigencia' => isset($factura['fin_vigencia']) && !empty($factura['fin_vigencia']) ? date('d/m/Y', strtotime($factura['fin_vigencia'])) : '31/12/2025'
);

// Obtener detalle de productos
$stmt_det = $pdo->prepare("SELECT d.*, p.nombre 
                           FROM detalle_factura_ventas d
                           JOIN productos p ON d.producto_id = p.id
                           WHERE d.factura_id = :id");
$stmt_det->execute(array('id' => $factura_id));
$detalle = $stmt_det->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Factura N° <?php echo htmlspecialchars($factura['numero_factura']); ?></title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
body { font-family: Arial, sans-serif; margin: 50px; padding:0; background: #f7f7f7; }
.factura-container { width: 800px; margin: 20px auto; background: white; padding: 30px; border: 1px solid #ccc; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
.encabezado { display: flex; justify-content: space-between; align-items: center; }
.encabezado img { height: 80px; }
.empresa-datos { text-align: right; font-size: 13px; }
.titulo { text-align:center; background:#0b3d91; color:white; padding:5px 0; margin-top:10px; font-size:18px; border-radius:4px; }
.datos-cliente { display:flex; justify-content:space-between; margin-top:15px; font-size:13px; }
.factura-table { width:100%; border-collapse:collapse; margin-top:15px; font-size:13px; }
.factura-table th, .factura-table td { border:1px solid #000; padding:6px; text-align:center;color: #000}
.factura-table th { background:#e8e8e8; }
tfoot td { font-weight:bold; text-align:left; }
.timbrado { margin-top:10px; font-size:12px; text-align:left; }
.footer { text-align:center; font-size:12px; margin-top:20px; color:#555; }
@media print { .btn-print { display:none; } body{background:white;} }
.btn-print { display:inline-block; padding:8px 15px; background:#0b3d91; color:white; text-decoration:none; border-radius:4px; margin-bottom:15px; }
</style>
</head>
<body>

<div class="factura-container">

    <div class="encabezado">
        <img src="/repuestos/img/logo3.png" alt="Logo Empresa">
        <div class="empresa-datos">
            <strong><?php echo htmlspecialchars($empresa['nombre']); ?></strong><br>
            RUC: <?php echo htmlspecialchars($empresa['ruc']); ?><br>
            <?php echo htmlspecialchars($empresa['direccion']); ?><br>
            Tel: <?php echo htmlspecialchars($empresa['telefono']); ?><br>
            Email: <?php echo htmlspecialchars($empresa['email']); ?>
        </div>
    </div>

    <div class="titulo">FACTURA</div>

    <div class="timbrado">
        <strong>Timbrado:</strong> <?php echo $empresa['timbrado']; ?> |
        <strong>Vigencia:</strong> <?php echo $empresa['inicio_vigencia']; ?> al <?php echo $empresa['fin_vigencia']; ?><br>
        <strong>N° de Factura:</strong> <?php echo htmlspecialchars($factura['numero_factura']); ?>
    </div>

    <div class="datos-cliente">
        <div>
            <strong>Nombre:</strong> <?php echo htmlspecialchars($factura['nombre'].' '.$factura['apellido']); ?><br>
            <strong>RUC:</strong> <?php echo htmlspecialchars($factura['ruc']); ?><br> 
            <strong>Dirección:</strong> <?php echo htmlspecialchars($factura['direccion']); ?><br>
            <strong>Teléfono:</strong> <?php echo htmlspecialchars($factura['telefono']); ?><br>
        </div>
        <div style="text-align:right;">
            <strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($factura['fecha_hora'])); ?><br>
            <strong>Condición:</strong> <?php echo htmlspecialchars($factura['condicion_venta']); ?><br>
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
            $v5 = isset($d['valor_venta_5']) ? (float)$d['valor_venta_5'] : 0.0;
            $v10 = isset($d['valor_venta_10']) ? (float)$d['valor_venta_10'] : 0.0;
            $vex = isset($d['valor_venta_exenta']) ? (float)$d['valor_venta_exenta'] : 0.0;
            
            // Calcular base sin IVA (precio * cantidad)
            $precio_unitario = (float)$d['precio_unitario'];
            $cantidad = (int)$d['cantidad'];
            $subtotal_sin_iva_producto = $precio_unitario * $cantidad;
            $subtotal_sin_iva += $subtotal_sin_iva_producto;
            
            $total_iva_5 += $v5;
            $total_iva_10 += $v10;
            $total_exenta += $vex;
            $total_factura += (float)$d['total_parcial'];

            // Mostrar el subtotal sin IVA en la tabla (solo precio * cantidad)
            echo '<tr>';
            echo '<td>'.htmlspecialchars($d['nombre']).'</td>';
            echo '<td>'.(int)$d['cantidad'].'</td>';
            echo '<td>'.number_format((float)$d['precio_unitario'],0,',','.').'</td>';
            if($v5>0) $iva_text='5%'; elseif($v10>0) $iva_text='10%'; else $iva_text='Exenta';
            echo '<td>'.$iva_text.'</td>';
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
            <?php if ($total_iva_5 > 0): ?>
            <tr>
                <td colspan="4" style="text-align:right;"><strong>IVA 5%:</strong></td>
                <td><strong><?php echo number_format($total_iva_5,0,',','.'); ?></strong></td>
            </tr>
            <?php endif; ?>
            <?php if ($total_iva_10 > 0): ?>
            <tr>
                <td colspan="4" style="text-align:right;"><strong>IVA 10%:</strong></td>
                <td><strong><?php echo number_format($total_iva_10,0,',','.'); ?></strong></td>
            </tr>
            <?php endif; ?>
            <?php if ($total_exenta > 0): ?>
            <tr>
                <td colspan="4" style="text-align:right;"><strong>Exenta:</strong></td>
                <td><strong><?php echo number_format($total_exenta,0,',','.'); ?></strong></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td colspan="4" style="text-align:right;"><strong>TOTAL:</strong></td>
                <td><strong><?php echo number_format($total_factura,0,',','.'); ?></strong></td>
            </tr>
            <tr><td colspan="5" style="text-align:left; font-weight:bold;">TOTAL (en letras): <?php echo $total_letras; ?></td></tr>
        </tfoot>
    </table>

    <a href="/repuestos/modulos/ventas/imprimir_factura.php?id=<?php echo $factura_id; ?>" target="_blank" class="btn-print">Imprimir Factura</a>

    <div class="footer">
        ** Documento no válido como comprobante fiscal **<br>
        Generado por el sistema de gestión de Repuestos Doble A
    </div>

</div>

</body>
</html>
