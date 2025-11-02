<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';

// Compatibilidad para intdiv en PHP < 7 (PHP 5.6)  Modificar factura
if (!function_exists('intdiv')) {
    function intdiv($a, $b) {
        if ($b == 0) {
            trigger_error("Division by zero", E_USER_WARNING);
            return null;
        }
        // Comportamiento aproximado de intdiv (trunca hacia 0)
        $res = $a / $b;
        if ($res > 0) return (int)floor($res);
        return (int)ceil($res);
    }
}

$mensaje = "";
$factura_id = 0;

// Consultar clientes y productos
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$productos = $pdo->query("SELECT * FROM productos ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// Series
$serie_1 = 1;
$serie_2 = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = (int)$_POST['cliente_id'];
    $ruc_cliente = $_POST['ruc'] != '' ? $_POST['ruc'] : '';
    $condicion_venta = isset($_POST['condicion_venta']) ? $_POST['condicion_venta'] : 'Contado';
    $forma_pago = isset($_POST['forma_pago']) ? $_POST['forma_pago'] : 'Efectivo';
    $productos_ids = isset($_POST['producto_id']) ? $_POST['producto_id'] : array();
    $cantidades = isset($_POST['cantidad']) ? $_POST['cantidad'] : array();
    $precios = isset($_POST['precio']) ? $_POST['precio'] : array();
    $ivas = isset($_POST['iva']) ? $_POST['iva'] : array();
    $fecha = date("Y-m-d H:i:s");

    $total_venta = 0;
    $detalle = array();

    for ($i = 0; $i < count($productos_ids); $i++) {
        $prod_id = (int)$productos_ids[$i];
        $cantidad = (float)$cantidades[$i];
        $precio_unitario = (float)$precios[$i];
        $subtotal = round($cantidad * $precio_unitario,2);
        $total_venta += $subtotal;

        // Calcular el IVA como monto (5% o 10%) o marcar exenta
        $valor_5 = $valor_10 = $valor_exenta = 0;
        $iva_val = isset($ivas[$i]) ? (string)$ivas[$i] : '10';
        if ($iva_val === '5') {
            $valor_5 = round($subtotal * 0.05, 2);   // monto de IVA 5%
        } elseif ($iva_val === '10') {
            $valor_10 = round($subtotal * 0.10, 2);  // monto de IVA 10%
        } else {
            // exenta: no IVA, guardamos la base en valor_venta_exenta
            $valor_exenta = $subtotal;
        }

        $detalle[] = array(
            'producto_id' => $prod_id,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio_unitario,
            'valor_venta_5' => $valor_5,
            'valor_venta_10' => $valor_10,
            'valor_venta_exenta' => $valor_exenta,
            'total_parcial' => $subtotal
        );
    }

    // Número de factura secuencial
    $stmt_num = $pdo->query("SELECT MAX(id) as max_id FROM cabecera_factura_ventas");
    $row = $stmt_num->fetch(PDO::FETCH_ASSOC);
    $next_id = ($row && $row['max_id']) ? ((int)$row['max_id'] + 1) : 1;
    $numero_factura = sprintf('%03d-%03d-%06d',$serie_1,$serie_2,$next_id);

    // Timbrado
    $timbrado = '12345678';
    $inicio_vigencia = '2025-01-01';
    $fin_vigencia = '2025-12-31';

    // Insertar cabecera
    $sql_cab = "INSERT INTO cabecera_factura_ventas
        (numero_factura, condicion_venta, forma_pago, fecha_hora, cliente_id, monto_total, timbrado, inicio_vigencia, fin_vigencia)
        VALUES (:numero_factura,:condicion_venta,:forma_pago,:fecha_hora,:cliente_id,:monto_total,:timbrado,:inicio_vigencia,:fin_vigencia)";
    $stmt_cab = $pdo->prepare($sql_cab);
    $stmt_cab->execute(array(
        ':numero_factura'=>$numero_factura,
        ':condicion_venta'=>$condicion_venta,
        ':forma_pago'=>$forma_pago,
        ':fecha_hora'=>$fecha,
        ':cliente_id'=>$cliente_id,
        ':monto_total'=>$total_venta,
        ':timbrado'=>$timbrado,
        ':inicio_vigencia'=>$inicio_vigencia,
        ':fin_vigencia'=>$fin_vigencia
    ));
    $factura_id = (int)$pdo->lastInsertId();

    // Insertar detalle y actualizar stock
    $stmt_det = $pdo->prepare("INSERT INTO detalle_factura_ventas
        (factura_id, producto_id, cantidad, precio_unitario, valor_venta_5, valor_venta_10, valor_venta_exenta, total_parcial)
        VALUES (:factura_id,:producto_id,:cantidad,:precio_unitario,:valor_venta_5,:valor_venta_10,:valor_venta_exenta,:total_parcial)");
    $stmt_stock = $pdo->prepare("UPDATE productos SET stock=stock-:cantidad WHERE id=:producto_id");

    foreach($detalle as $item){
        $stmt_det->execute(array(
            ':factura_id'=>$factura_id,
            ':producto_id'=>$item['producto_id'],
            ':cantidad'=>$item['cantidad'],
            ':precio_unitario'=>$item['precio_unitario'],
            ':valor_venta_5'=>$item['valor_venta_5'],
            ':valor_venta_10'=>$item['valor_venta_10'],
            ':valor_venta_exenta'=>$item['valor_venta_exenta'],
            ':total_parcial'=>$item['total_parcial']
        ));
        $stmt_stock->execute(array(
            ':cantidad'=>$item['cantidad'],
            ':producto_id'=>$item['producto_id']
        ));
    }

    /* Inserta la función numero_a_letras (copiar la misma que usas en imprimir_factura.php)
       colócala cerca del inicio del archivo (antes de usarla) */
    function numero_a_letras($numero) {
        $numero = number_format((float)$numero, 2, '.', '');
        list($entero, $decimales) = explode('.', $numero);
        $entero = (int)$entero;

        $unidades = array('', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE');
        $decenas = array('', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA');
        $especiales = array(11=>'ONCE',12=>'DOCE',13=>'TRECE',14=>'CATORCE',15=>'QUINCE',20=>'VEINTE');

        $convert_hasta_mil = function($n) use ($unidades, $decenas, $especiales, &$convert_hasta_mil) {
            $n = (int)$n;
            if ($n == 0) return 'CERO';
            $str = '';
            if ($n >= 100) {
                $hund = intdiv($n,100);
                if ($hund == 1 && $n % 100 == 0) $str .= 'CIEN';
                else {
                    $map = array(1=>'CIENTO',2=>'DOSCIENTOS',3=>'TRESCIENTOS',4=>'CUATROCIENTOS',5=>'QUINIENTOS',6=>'SEISCIENTOS',7=>'SETECIENTOS',8=>'OCHOCIENTOS',9=>'NOVECIENTOS');
                    $str .= $map[$hund];
                }
                $n %= 100;
                if ($n) $str .= ' ';
            }
            if ($n > 10 && $n < 16) {
                $str .= $especiales[$n];
            } elseif ($n == 10 || $n >= 20) {
                $d = intdiv($n,10);
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

        $partes = array();
        if ($entero >= 1000000) {
            $millones = intdiv($entero, 1000000);
            $partes[] = ($millones == 1) ? 'UN MILLON' : numero_a_letras($millones) . ' MILLONES';
            $entero %= 1000000;
        }
        if ($entero >= 1000) {
            $miles = intdiv($entero, 1000);
            if ($miles == 1) $partes[] = 'MIL';
            else $partes[] = $convert_hasta_mil($miles) . ' MIL';
            $entero %= 1000;
        }
        if ($entero > 0) {
            $partes[] = $convert_hasta_mil($entero);
        }
        $texto_entero = $partes ? implode(' ', $partes) : 'CERO';
        // Devuelve solo el texto del entero (sin "CON xx/100")
        return trim($texto_entero);
    }

    // cambiar el mensaje de confirmación para incluir el total en letras
    $mensaje = "Venta registrada. Factura: $numero_factura Total: ".number_format($total_venta,2,',','.').
               " — ".numero_a_letras($total_venta);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registrar Venta - Repuestos Doble A</title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
.container{padding:20px;margin-top:60px;}
.venta-form{background:white;padding:20px;border-radius:8px;box-shadow:0 4px 10px rgba(0,0,0,0.1);margin-bottom:20px;}
.venta-form label{display:block;margin:10px 0 5px;}
.venta-form select,.venta-form input{width:100%;padding:8px;margin-bottom:10px;border-radius:4px;border:1px solid #ccc;}
.venta-form button{padding:10px;background:#2563eb;color:white;border:none;border-radius:4px;cursor:pointer;}
.venta-form button:hover{background:#1e40af;}
.mensaje{padding:10px;margin-bottom:15px;border-radius:4px;text-align:center;background:#d1fae5;color:#065f46;}
.mensaje a.btn{margin:5px;padding:8px 12px;background:#2563eb;color:white;text-decoration:none;border-radius:4px;display:inline-block;}
.mensaje a.btn:hover{background:#1e40af;}
</style>
</head>
<body>
<div class="container">
<h1>Registrar Venta</h1>

<?php if($mensaje!=""): ?>
<div class="mensaje exito"><?= $mensaje ?>
    <?php if($factura_id): ?>
        <br>
        <a href="/repuestos/modulos/ventas/ver_factura.php?id=<?= $factura_id ?>" class="btn" target="_blank">Ver Factura</a>
        <a href="/repuestos/modulos/ventas/imprimir_factura.php?id=<?= $factura_id ?>" class="btn" target="_blank">Imprimir Factura</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<form method="POST" class="venta-form">
    <label>Cliente:</label>
    <select id="cliente_select" name="cliente_id" required onchange="llenarRUC()">
        <option value="">-- Seleccione Cliente --</option>
        <?php foreach($clientes as $c): ?>
            <option value="<?= $c['id'] ?>" data-ruc="<?= $c['ruc'] ?>"><?= $c['nombre'].' '.$c['apellido'] ?></option>
        <?php endforeach; ?>
    </select>

    <label>RUC:</label>
    <input type="text" name="ruc" id="ruc_input" placeholder="RUC del cliente">

    <label>Condición de venta:</label>
    <select name="condicion_venta" required>
        <option value="Contado">Contado</option>
        <option value="Crédito">Crédito</option>
    </select>

    <label>Forma de pago:</label>
    <select name="forma_pago" required>
        <option value="Efectivo">Efectivo</option>
        <option value="Tarjeta">Tarjeta</option>
        <option value="Transferencia">Transferencia</option>
    </select>

    <h2>Productos</h2>
    <div id="productos_container">
        <div class="producto_row">
            <label>Producto:</label>
            <select name="producto_id[]" required>
                <option value="">-- Seleccione producto --</option>
                <?php foreach($productos as $prod): ?>
                    <option value="<?= $prod['id'] ?>" data-precio="<?= $prod['precio'] ?>"><?= $prod['nombre'] ?> (Stock: <?= $prod['stock'] ?>)</option>
                <?php endforeach; ?>
            </select>

            <label>Cantidad:</label>
            <input type="number" name="cantidad[]" min="1" required>

            <label>Precio Unitario:</label>
            <input type="number" name="precio[]" step="0.01" required>

            <label>IVA:</label>
            <select name="iva[]">
                <option value="5">5%</option>
                <option value="10">10%</option>
                <option value="exenta">Exenta</option>
            </select>
        </div>
    </div>

    <button type="button" id="add_producto" class="btn">+ Agregar otro producto</button>
    <br><br>
    <button type="submit" class="btn">Registrar Venta</button>
</form>
</div>

<script>
document.getElementById('add_producto').addEventListener('click', function(){
    var container = document.getElementById('productos_container');
    var newRow = container.children[0].cloneNode(true);
    newRow.querySelectorAll('input').forEach(function(input){ input.value = ''; });
    newRow.querySelector('select[name="producto_id[]"]').selectedIndex = 0;
    newRow.querySelector('select[name="iva[]"]').selectedIndex = 0;
    container.appendChild(newRow);

    // Activar autollenado de precio en la nueva fila
    activarAutoprecio(newRow.querySelector('select[name="producto_id[]"]'));
});

function llenarRUC() {
    var select = document.getElementById('cliente_select');
    var ruc = select.options[select.selectedIndex].dataset.ruc;
    document.getElementById('ruc_input').value = ruc;
}

// === NUEVO === Detecta el precio automáticamente ===
function activarAutoprecio(select) {
    select.addEventListener('change', function() {
        var precio = this.options[this.selectedIndex].dataset.precio || '';
        this.closest('.producto_row').querySelector('input[name="precio[]"]').value = precio;
    });
}

// Activar para todas las filas iniciales
document.querySelectorAll('select[name="producto_id[]"]').forEach(activarAutoprecio);
</script>
</body>
</html>
