<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';


// Compatibilidad para intdiv en PHP antiguo (PHP 5.6)
if (!function_exists('intdiv_compat')) {
    function intdiv_compat($a, $b) {
        if ($b == 0) return 0;
        return ($a >= 0) ? (int)floor($a / $b) : (int)ceil($a / $b);
    }
}

// Convierte número a letras en español (sin centavos)
if (!function_exists('numero_a_letras')) {
    function numero_a_letras($numero) {
        $numero = number_format((float)$numero, 2, '.', '');
        list($entero, $decimales) = explode('.', $numero);
        $entero = (int)$entero;

        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $especiales = [11=>'ONCE',12=>'DOCE',13=>'TRECE',14=>'CATORCE',15=>'QUINCE',20=>'VEINTE'];

        $convert_hasta_mil = function($n) use ($unidades, $decenas, $especiales, &$convert_hasta_mil) {
            $n = (int)$n;
            if ($n == 0) return 'CERO';
            $str = '';
            if ($n >= 100) {
                $hund = intdiv_compat($n, 100);
                if ($hund == 1 && $n % 100 == 0) $str .= 'CIEN';
                else {
                    $map = [1=>'CIENTO',2=>'DOSCIENTOS',3=>'TRESCIENTOS',4=>'CUATROCIENTOS',5=>'QUINIENTOS',6=>'SEISCIENTOS',7=>'SETECIENTOS',8=>'OCHOCIENTOS',9=>'NOVECIENTOS'];
                    $str .= $map[$hund];
                }
                $n %= 100;
                if ($n) $str .= ' ';
            }
            if ($n > 10 && $n < 16) {
                $str .= $especiales[$n];
            } elseif ($n == 10 || $n >= 20) {
                $d = intdiv_compat($n, 10);
                $u = $n % 10;
                if ($d == 2 && $u > 0) {
                    $str .= 'VEINTI' . strtolower($unidades[$u]);
                } else {
                    $str .= $decenas[$d];
                    if ($u) $str .= ' Y ' . $unidades[$u];
                }
            } else {
                $str .= $unidades[$n];
            }
            return strtoupper($str);
        };

        $partes = [];
        if ($entero >= 1000000) {
            $millones = intdiv_compat($entero, 1000000);
            $partes[] = ($millones == 1) ? 'UN MILLON' : numero_a_letras($millones) . ' MILLONES';
            $entero %= 1000000;
        }
        if ($entero >= 1000) {
            $miles = intdiv_compat($entero, 1000);
            if ($miles == 1) $partes[] = 'MIL';
            else $partes[] = $convert_hasta_mil($miles) . ' MIL';
            $entero %= 1000;
        }
        if ($entero > 0) {
            $partes[] = $convert_hasta_mil($entero);
        }
        $texto_entero = $partes ? implode(' ', $partes) : 'CERO';
        return trim($texto_entero);
    }
}

if(!isset($_GET['id'])) {
    echo "Factura no especificada.";
    exit;
}

$factura_id = (int)$_GET['id'];

// Datos de la empresa
$empresa = array(
    'nombre' => 'Repuestos Doble A',
    'ruc' => '80012345-6',
    'direccion' => 'Av. Principal 123, Asunción, Paraguay',
    'telefono' => '(021) 123-4567',
    'email' => 'contacto@repuestosdoblea.com',
    'timbrado' => '12345678',
    'inicio_vigencia' => '01/01/2025',
    'fin_vigencia' => '31/12/2025'
);

// Obtener cabecera de factura
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
body { font-family: Arial, sans-serif; margin:0; padding:0; background: #f7f7f7; }
.factura-container { width: 800px; margin: 20px auto; background: white; padding: 30px; border: 1px solid #ccc; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
.encabezado { display: flex; justify-content: space-between; align-items: center; }
.encabezado img { height: 80px; }
.empresa-datos { text-align: right; font-size: 13px; }
.titulo { text-align:center; background:#0b3d91; color:white; padding:5px 0; margin-top:10px; font-size:18px; border-radius:4px; }
.datos-cliente { display:flex; justify-content:space-between; margin-top:15px; font-size:13px; }
.factura-table { width:100%; border-collapse:collapse; margin-top:15px; font-size:13px; }
.factura-table th, .factura-table td { border:1px solid #000; padding:6px; text-align:center; }
.factura-table th { background:#e8e8e8; }
tfoot td { font-weight:bold; }
.timbrado { margin-top:10px; font-size:12px; text-align:left; }
.footer { text-align:center; font-size:12px; margin-top:20px; color:#555; }
@media print { .btn-print { display:none; } body{background:white;} }
.btn-print { display:inline-block; padding:8px 15px; background:#0b3d91; color:white; text-decoration:none; border-radius:4px; margin-bottom:15px; }
.titulo { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
</style>
</head>
<body>

<body onload="window.print();"> 

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
            <strong>Cliente:</strong> <?php echo htmlspecialchars($factura['nombre'].' '.$factura['apellido']); ?><br>
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
        $base_exenta = 0.0;
        $base_5 = 0.0;
        $base_10 = 0.0;

        foreach($detalle as $d) {
            $v5 = isset($d['valor_venta_5']) ? (float)$d['valor_venta_5'] : 0.0;
            $v10 = isset($d['valor_venta_10']) ? (float)$d['valor_venta_10'] : 0.0;
            $vex = isset($d['valor_venta_exenta']) ? (float)$d['valor_venta_exenta'] : 0.0;

            if($v5>0) $base_5 += (float)$d['total_parcial'];
            elseif($v10>0) $base_10 += (float)$d['total_parcial'];
            else $base_exenta += $vex;

            echo '<tr>';
            echo '<td>'.htmlspecialchars($d['nombre']).'</td>';
            echo '<td>'.(int)$d['cantidad'].'</td>';
            echo '<td>'.number_format((float)$d['precio_unitario'],0,',','.').'</td>';
            if($v5>0) $iva_text='5%'; elseif($v10>0) $iva_text='10%'; else $iva_text='Exenta';
            echo '<td>'.$iva_text.'</td>';
            echo '<td>'.number_format((float)$d['total_parcial'],0,',','.').'</td>';
            echo '</tr>';
        }

        $iva_5 = $base_5 * 0.05;
        $iva_10 = $base_10 * 0.10;

        $total_factura = $base_exenta + $base_5 + $iva_5 + $base_10 + $iva_10;
        $total_letras = numero_a_letras($total_factura);
        ?>
        </tbody>
        <tfoot>
            <tr><td colspan="4" style="text-align:right;">Total Exentas:</td><td><?php echo number_format($base_exenta,0,',','.'); ?></td></tr>
            <tr><td colspan="4" style="text-align:right;">IVA 5%:</td><td><?php echo number_format($iva_5,0,',','.'); ?></td></tr>
            <tr><td colspan="4" style="text-align:right;">IVA 10%:</td><td><?php echo number_format($iva_10,0,',','.'); ?></td></tr>
            <tr><td colspan="4" style="text-align:right;"><strong>TOTAL:</strong></td><td><strong><?php echo number_format($total_factura,0,',','.'); ?></strong></td></tr>
            <tr><td colspan="5" style="text-align:left; font-weight:bold;">TOTAL(en letras):<?php echo $total_letras; ?></td></tr>
        </tfoot>
    </table>


    <div class="footer">
        ** Documento no válido como comprobante fiscal **<br>
        Generado por el sistema de gestión de Repuestos Doble A
    </div>

</div>

</body>
</html>
