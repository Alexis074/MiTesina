<?php    
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';

$mensaje = "";
$factura_id = 0;

$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$productos = $pdo->query("SELECT * FROM productos ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

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
        $subtotal = round($cantidad * $precio_unitario);

        $valor_5 = $valor_10 = $valor_exenta = 0;
        $iva_val = isset($ivas[$i]) ? (string)$ivas[$i] : '10';
        if ($iva_val === '5') {
            $valor_5 = round($subtotal * 0.05);
            $subtotal += $valor_5;
        } elseif ($iva_val === '10') {
            $valor_10 = round($subtotal * 0.10);
            $subtotal += $valor_10;
        } else {
            $valor_exenta = $subtotal;
        }

        $total_venta += $subtotal;

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

    // Generar número de factura único basado en el ID máximo (incluye facturas anuladas)
    // Esto garantiza que los números de factura nunca se reutilicen, incluso si hay facturas anuladas
    // El ID es AUTO_INCREMENT, por lo que siempre será único y nunca se reutilizará
    $stmt_num = $pdo->query("SELECT MAX(id) as max_id FROM cabecera_factura_ventas");
    $row = $stmt_num->fetch(PDO::FETCH_ASSOC);
    $next_id = ($row && $row['max_id']) ? ((int)$row['max_id'] + 1) : 1;
    $numero_factura = sprintf('%03d-%03d-%06d',$serie_1,$serie_2,$next_id);
    
    // Verificar que el número de factura no exista (seguridad adicional)
    // Esto no debería ocurrir, pero es una medida de seguridad
    $stmt_verificar = $pdo->prepare("SELECT id FROM cabecera_factura_ventas WHERE numero_factura = ?");
    $stmt_verificar->execute(array($numero_factura));
    if ($stmt_verificar->fetch()) {
        // Si por alguna razón el número ya existe, usar el siguiente ID
        $next_id++;
        $numero_factura = sprintf('%03d-%03d-%06d',$serie_1,$serie_2,$next_id);
    }

    // Generar timbrado único auto-incremental desde un número base
    // El número base es 4571575, y cada factura tendrá un timbrado único incremental
    $timbrado_base = 4571575;
    
    // Obtener el timbrado máximo existente (incluye facturas anuladas para garantizar unicidad)
    // Compatible con MySQL 5.6: obtenemos todos los timbrados numéricos y calculamos el máximo en PHP
    $stmt_timbrado = $pdo->query("SELECT timbrado FROM cabecera_factura_ventas WHERE timbrado IS NOT NULL");
    $timbrados = $stmt_timbrado->fetchAll(PDO::FETCH_COLUMN);
    
    $max_timbrado = $timbrado_base - 1;
    foreach ($timbrados as $t) {
        // Verificar si es numérico
        if (is_numeric($t)) {
            $t_num = (int)$t;
            if ($t_num > $max_timbrado) {
                $max_timbrado = $t_num;
            }
        }
    }
    
    // Si hay timbrados existentes mayores o iguales al base, usar el máximo + 1, sino usar el número base
    if ($max_timbrado >= $timbrado_base) {
        $next_timbrado = $max_timbrado + 1;
    } else {
        $next_timbrado = $timbrado_base;
    }
    
    // Verificar que el timbrado no exista (seguridad adicional)
    $stmt_verificar_timbrado = $pdo->prepare("SELECT id FROM cabecera_factura_ventas WHERE timbrado = ?");
    $stmt_verificar_timbrado->execute(array($next_timbrado));
    if ($stmt_verificar_timbrado->fetch()) {
        // Si por alguna razón el timbrado ya existe, usar el siguiente
        $next_timbrado++;
    }
    
    $timbrado = (string)$next_timbrado;
    $inicio_vigencia = '2025-01-01';
    $fin_vigencia = '2025-12-31';

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

    $mensaje = "Venta registrada. Factura: $numero_factura Total: ".number_format($total_venta,0,',','.');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registrar Venta - Repuestos Doble A</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.container{padding:20px;margin-top:60px;}
.venta-form{background:white;padding:20px;border-radius:8px;box-shadow:0 4px 10px rgba(0,0,0,0.1);margin-bottom:20px;}
.producto_row{display:flex;gap:10px;margin-bottom:10px;align-items:flex-end;}
.producto_row select, .producto_row input{flex:1;}
.btn-add, .btn-delete{height:38px;}
.mensaje{padding:10px;margin-bottom:15px;border-radius:4px;text-align:center;background:#d1fae5;color:#065f46;}
#cuotas_container{display:none;margin-top:10px;}
</style>
</head>
<body>
<div class="container">
<h1>Registrar Venta</h1>

<?php if($mensaje!=""): ?>
<div class="mensaje"><?= $mensaje ?>
    <?php if($factura_id): ?>
        <br>
        <a href="/repuestos/modulos/ventas/ver_factura.php?id=<?= $factura_id ?>" class="btn btn-primary btn-sm" target="_blank">Ver Factura</a>
        <a href="/repuestos/modulos/ventas/imprimir_factura.php?id=<?= $factura_id ?>" class="btn btn-primary btn-sm" target="_blank">Imprimir Factura</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<form method="POST" class="venta-form">
    <div class="mb-3">
        <label>Cliente:</label>
        <select id="cliente_select" name="cliente_id" class="form-select" required onchange="llenarRUC()">
            <option value="">-- Seleccione Cliente --</option>
            <?php foreach($clientes as $c): ?>
                <option value="<?= $c['id'] ?>" data-ruc="<?= $c['ruc'] ?>"><?= $c['nombre'].' '.$c['apellido'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label>RUC:</label>
        <input type="text" name="ruc" id="ruc_input" class="form-control" placeholder="RUC del cliente" oninput="buscarClientePorRUC()">
    </div>

    <div class="mb-3 row">
        <div class="col-md-6">
            <label>Condición de venta:</label>
            <select name="condicion_venta" id="condicion_venta" class="form-select" required onchange="toggleCuotas()">
                <option value="Contado">Contado</option>
                <option value="Crédito">Crédito</option>
            </select>
        </div>
        <div class="col-md-6">
            <label>Forma de pago:</label>
            <select name="forma_pago" id="forma_pago" class="form-select" required onchange="toggleCuotas()">
                <option value="Efectivo">Efectivo</option>
                <option value="Tarjeta">Tarjeta</option>
                <option value="Transferencia">Transferencia</option>
            </select>
        </div>
    </div>

    <div id="cuotas_container">
        <label>Cuotas:</label>
        <select id="cuotas_select" class="form-select" onchange="calcularCuotas()">
            <?php for($i=2;$i<=12;$i++): ?>
                <option value="<?= $i ?>"><?= $i ?> meses</option>
            <?php endfor; ?>
        </select>
        <small id="monto_cuota" class="text-muted mt-1">Monto por cuota: 0</small>
    </div>

    <h2>Productos</h2>
    <div id="productos_container">
        <div class="producto_row">
            <select name="producto_id[]" class="form-select producto_select" required onchange="autocompletarPrecio(this)">
                <option value="">-- Seleccione producto --</option>
                <?php foreach($productos as $prod): ?>
                    <option value="<?= $prod['id'] ?>" data-precio="<?= round($prod['precio'],0) ?>"><?= $prod['nombre'] ?> (Stock: <?= $prod['stock'] ?>)</option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="cantidad[]" class="form-control" min="1" value="1" oninput="calcularTotal()">
            <input type="number" name="precio[]" class="form-control" placeholder="Precio" readonly>
            <select name="iva[]" class="form-select" onchange="calcularTotal()">
                <option value="5">5%</option>
                <option value="10">10%</option>
                <option value="exenta">Exenta</option>
            </select>
            <button type="button" class="btn btn-danger btn-delete" onclick="eliminarProducto(this)">X</button>
        </div>
    </div>
    <button type="button" class="btn btn-success btn-add my-2" onclick="agregarProducto()">+ Agregar producto</button>

    <h4>Total: <span id="total_display">0</span></h4>

    <button type="submit" class="btn btn-primary mt-3">Registrar Venta</button>
</form>
</div>

<script>
function llenarRUC(){
    var select=document.getElementById('cliente_select');
    var ruc=select.options[select.selectedIndex].dataset.ruc || '';
    document.getElementById('ruc_input').value=ruc;
}

// Detecta cliente automáticamente al escribir RUC
function buscarClientePorRUC(){
    var ruc=document.getElementById('ruc_input').value.trim();
    var select=document.getElementById('cliente_select');
    var encontrado=false;
    for(var i=0;i<select.options.length;i++){
        if(select.options[i].dataset.ruc===ruc){
            select.selectedIndex=i;
            encontrado=true;
            break;
        }
    }
    if(!encontrado){
        select.selectedIndex=0; // Si no coincide, deselecciona
    }
}

function autocompletarPrecio(select){
    var precio=parseInt(select.options[select.selectedIndex].dataset.precio) || 0;
    var row=select.closest('.producto_row');
    row.querySelector('input[name="precio[]"]').value=precio;
    calcularTotal();
}

function calcularTotal(){
    var total=0;
    document.querySelectorAll('.producto_row').forEach(row=>{
        var cantidad=parseInt(row.querySelector('input[name="cantidad[]"]').value) || 0;
        var precio=parseInt(row.querySelector('input[name="precio[]"]').value) || 0;
        var iva=row.querySelector('select[name="iva[]"]').value;
        var subtotal=cantidad*precio;
        if(iva=='5') subtotal=Math.round(subtotal*1.05);
        else if(iva=='10') subtotal=Math.round(subtotal*1.10);
        total+=subtotal;
    });
    document.getElementById('total_display').innerText=total;
    calcularCuotas();
}

function agregarProducto(){
    var container=document.getElementById('productos_container');
    var newRow=container.children[0].cloneNode(true);
    newRow.querySelectorAll('input').forEach(input=>input.value=input.name=='cantidad[]'?1:'' );
    newRow.querySelector('select[name="producto_id[]"]').selectedIndex=0;
    newRow.querySelector('select[name="iva[]"]').selectedIndex=0;
    container.appendChild(newRow);
}

function eliminarProducto(btn){
    var container=document.getElementById('productos_container');
    if(container.children.length>1){
        btn.closest('.producto_row').remove();
        calcularTotal();
    }
}

function toggleCuotas(){
    var forma=document.getElementById('forma_pago').value;
    var condicion=document.getElementById('condicion_venta').value;
    document.getElementById('cuotas_container').style.display=(forma=='Tarjeta' && condicion=='Crédito')?'block':'none';
    calcularCuotas();
}

function calcularCuotas(){
    var total=parseInt(document.getElementById('total_display').innerText) || 0;
    var cuotas=document.getElementById('cuotas_select').value || 1;
    if(document.getElementById('cuotas_container').style.display=='block'){
        var monto=Math.round(total/cuotas);
        document.getElementById('monto_cuota').innerText="Monto por cuota: "+monto;
    } else {
        document.getElementById('monto_cuota').innerText="Monto por cuota: 0";
    }
}

// Inicializa
document.querySelectorAll('.producto_select').forEach(sel=>{
    sel.addEventListener('change',()=>autocompletarPrecio(sel));
});
</script>
</body>
</html>

