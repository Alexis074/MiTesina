<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('compras', 'ver');
include $base_path . 'includes/header.php';

$mensaje = "";

// Si existe mensaje de sesión, mostrarlo y luego eliminarlo
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

// Consultar proveedores
$proveedores_stmt = $pdo->query("SELECT * FROM proveedores ORDER BY empresa ASC");
$proveedores = $proveedores_stmt->fetchAll();

// Consultar productos
$productos_stmt = $pdo->query("SELECT * FROM productos ORDER BY nombre ASC");
$productos = $productos_stmt->fetchAll();

// Obtener caja abierta
$caja_stmt = $pdo->query("SELECT * FROM caja WHERE estado='Abierta' ORDER BY id DESC LIMIT 1");
$caja_abierta = $caja_stmt->fetch();

// Calcular saldo disponible en caja si está abierta
$saldo_disponible_caja = 0;
if ($caja_abierta) {
    $fecha_apertura = $caja_abierta['fecha'];
    
    // Calcular ingresos totales
    $total_ingresos = 0;
    $stmt_ingresos = $pdo->prepare("SELECT SUM(monto) as total FROM caja_movimientos WHERE caja_id=? AND tipo='Ingreso'");
    $stmt_ingresos->execute([$caja_abierta['id']]);
    $ingresos_manual = $stmt_ingresos->fetch();
    $total_ingresos += $ingresos_manual['total'] ? (float)$ingresos_manual['total'] : 0;
    
    $stmt_ventas = $pdo->prepare("SELECT SUM(monto_total) as total FROM cabecera_factura_ventas WHERE fecha_hora >= ? AND (anulada = 0 OR anulada IS NULL)");
    $stmt_ventas->execute([$fecha_apertura]);
    $ventas_data = $stmt_ventas->fetch();
    $total_ingresos += $ventas_data['total'] ? (float)$ventas_data['total'] : 0;
    
    // Calcular egresos totales
    $total_egresos = 0;
    $stmt_egresos = $pdo->prepare("SELECT SUM(monto) as total FROM caja_movimientos WHERE caja_id=? AND tipo='Egreso'");
    $stmt_egresos->execute([$caja_abierta['id']]);
    $egresos_manual = $stmt_egresos->fetch();
    $total_egresos += $egresos_manual['total'] ? (float)$egresos_manual['total'] : 0;
    
    $stmt_compras = $pdo->prepare("SELECT SUM(total) as total FROM compras WHERE fecha >= ?");
    $stmt_compras->execute([$fecha_apertura]);
    $compras_data = $stmt_compras->fetch();
    $total_egresos += $compras_data['total'] ? (float)$compras_data['total'] : 0;
    
    try {
        $stmt_facturas_compras = $pdo->prepare("SELECT SUM(monto_total) as total FROM cabecera_factura_compras WHERE fecha_hora >= ?");
        $stmt_facturas_compras->execute([$fecha_apertura]);
        $facturas_compras_data = $stmt_facturas_compras->fetch();
        $total_egresos += $facturas_compras_data['total'] ? (float)$facturas_compras_data['total'] : 0;
    } catch (Exception $e) {
        // Tabla no existe, continuar
    }
    
    $saldo_disponible_caja = (float)$caja_abierta['monto_inicial'] + $total_ingresos - $total_egresos;
}

// Obtener compras recientes (últimas 20)
$stmt_compras_recientes = $pdo->query("SELECT c.*, p.empresa as nombre_proveedor 
                                       FROM compras c
                                       LEFT JOIN proveedores p ON c.proveedor_id = p.id
                                       ORDER BY c.fecha DESC 
                                       LIMIT 20");
$compras_recientes = $stmt_compras_recientes->fetchAll();

// Obtener facturas de compras recientes (últimas 20)
$facturas_compras_recientes = [];
try {
    $stmt_facturas_compras = $pdo->query("SELECT fc.*, p.empresa as nombre_proveedor 
                                          FROM cabecera_factura_compras fc
                                          LEFT JOIN proveedores p ON fc.proveedor_id = p.id
                                          ORDER BY fc.fecha_hora DESC 
                                          LIMIT 20");
    $facturas_compras_recientes = $stmt_facturas_compras->fetchAll();
} catch (Exception $e) {
    // Tabla no existe aún
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proveedor_id = (int)$_POST['proveedor_id'];
    
    // Obtener productos del carrito desde JSON
    $carrito_json = isset($_POST['carrito']) ? $_POST['carrito'] : '[]';
    $carrito = json_decode($carrito_json, true);
    
    if (empty($carrito) || !is_array($carrito)) {
        $mensaje = "Error: El carrito está vacío.";
    } elseif (!$caja_abierta) {
        $mensaje = "Error: Debe abrir la caja antes de realizar una compra.";
    } else {
        $fecha = date("Y-m-d H:i:s");
        
        // Calcular total de la compra con IVA
        // Primero sumar todos los subtotales sin IVA
        $total_5 = 0;
        $total_10 = 0;
        $total_exenta = 0;
        foreach($carrito as $item) {
            $subtotal = $item['cantidad'] * $item['precio'];
            $iva_val = isset($item['iva']) ? (string)$item['iva'] : '10';
            
            if ($iva_val === 'exenta') {
                $total_exenta += $subtotal;
            } elseif ($iva_val === '5') {
                $total_5 += $subtotal;
            } else {
                // IVA 10%
                $total_10 += $subtotal;
            }
        }
        
        // Calcular IVA 5% como total_5 / 21
        $iva_5 = round($total_5 / 21);
        // Calcular IVA 10% como total_10 / 11
        $iva_10 = round($total_10 / 11);
        
        // Total final = subtotales sin IVA + IVA 5% + IVA 10% + exentas
        $total_compra = $total_5 + $iva_5 + $total_10 + $iva_10 + $total_exenta;

        // Validar saldo disponible en caja
        $fecha_apertura = $caja_abierta['fecha'];
        
        // Calcular ingresos totales (movimientos manuales + ventas)
        $total_ingresos = 0;
        $stmt_ingresos = $pdo->prepare("SELECT SUM(monto) as total FROM caja_movimientos WHERE caja_id=? AND tipo='Ingreso'");
        $stmt_ingresos->execute([$caja_abierta['id']]);
        $ingresos_manual = $stmt_ingresos->fetch();
        $total_ingresos += $ingresos_manual['total'] ? (float)$ingresos_manual['total'] : 0;
        
        $stmt_ventas = $pdo->prepare("SELECT SUM(monto_total) as total FROM cabecera_factura_ventas WHERE fecha_hora >= ? AND (anulada = 0 OR anulada IS NULL)");
        $stmt_ventas->execute([$fecha_apertura]);
        $ventas_data = $stmt_ventas->fetch();
        $total_ingresos += $ventas_data['total'] ? (float)$ventas_data['total'] : 0;
        
        // Calcular egresos totales (movimientos manuales + compras + facturas de compras)
        $total_egresos = 0;
        $stmt_egresos = $pdo->prepare("SELECT SUM(monto) as total FROM caja_movimientos WHERE caja_id=? AND tipo='Egreso'");
        $stmt_egresos->execute([$caja_abierta['id']]);
        $egresos_manual = $stmt_egresos->fetch();
        $total_egresos += $egresos_manual['total'] ? (float)$egresos_manual['total'] : 0;
        
        $stmt_compras = $pdo->prepare("SELECT SUM(total) as total FROM compras WHERE fecha >= ?");
        $stmt_compras->execute([$fecha_apertura]);
        $compras_data = $stmt_compras->fetch();
        $total_egresos += $compras_data['total'] ? (float)$compras_data['total'] : 0;
        
        try {
            $stmt_facturas_compras = $pdo->prepare("SELECT SUM(monto_total) as total FROM cabecera_factura_compras WHERE fecha_hora >= ?");
            $stmt_facturas_compras->execute([$fecha_apertura]);
            $facturas_compras_data = $stmt_facturas_compras->fetch();
            $total_egresos += $facturas_compras_data['total'] ? (float)$facturas_compras_data['total'] : 0;
        } catch (Exception $e) {
            // Tabla no existe, continuar
        }
        
        // Calcular saldo disponible
        $saldo_disponible = (float)$caja_abierta['monto_inicial'] + $total_ingresos - $total_egresos;
        
        // Validar que el saldo disponible sea suficiente
        if ($total_compra > $saldo_disponible) {
            $mensaje = "Error: Saldo insuficiente en caja. Saldo disponible: " . number_format($saldo_disponible, 0, ',', '.') . " Gs. Monto de compra: " . number_format($total_compra, 0, ',', '.') . " Gs.";
        } else {
            // Insertar compra incluyendo total
        $sql_compra = "INSERT INTO compras (proveedor_id, fecha, total) VALUES (:proveedor_id, :fecha, :total)";
        $stmt_compra = $pdo->prepare($sql_compra);
        $stmt_compra->execute([
            'proveedor_id' => $proveedor_id,
            'fecha' => $fecha,
            'total' => $total_compra
        ]);
        $compra_id = $pdo->lastInsertId();
        
        // Obtener datos del proveedor
        $stmt_prov = $pdo->prepare("SELECT * FROM proveedores WHERE id = ?");
        $stmt_prov->execute([$proveedor_id]);
        $proveedor = $stmt_prov->fetch();
        $nombre_proveedor = $proveedor ? $proveedor['empresa'] : 'ID ' . $proveedor_id;
        
        // Generar número de factura único en formato paraguayo (001-001-000054)
        $numero_factura = '';
        $intentos = 0;
        do {
            // Generar número aleatorio pero secuencial basado en el ID
            $stmt_num = $pdo->query("SELECT MAX(id) as max_id FROM cabecera_factura_compras");
            $row = $stmt_num->fetch(PDO::FETCH_ASSOC);
            $base_num = ($row && $row['max_id']) ? ((int)$row['max_id'] + 1) : 1;
            
            // Formato: 001-001-000054 (establecimiento-punto-emision-numero)
            $establecimiento = '001';
            $punto_emision = '001';
            $numero_secuencial = str_pad($base_num + $intentos + rand(0, 100), 6, '0', STR_PAD_LEFT);
            $numero_factura = $establecimiento . '-' . $punto_emision . '-' . $numero_secuencial;
            
            // Verificar que el número de factura no exista
            $stmt_verificar = $pdo->prepare("SELECT id FROM cabecera_factura_compras WHERE numero_factura = ?");
            $stmt_verificar->execute(array($numero_factura));
            $intentos++;
        } while ($stmt_verificar->fetch() && $intentos < 100);
        
        // Generar timbrado aleatorio (8 dígitos)
        $timbrado_proveedor = str_pad(rand(1000000, 99999999), 8, '0', STR_PAD_LEFT);
        
        // Generar fechas de vigencia (año completo: 01/01/2025 al 31/12/2025)
        $anio_actual = date('Y');
        $fecha_inicio_vigencia = $anio_actual . '-01-01'; // Primer día del año
        $fecha_fin_vigencia = $anio_actual . '-12-31'; // Último día del año
        
        // Obtener datos de condición de compra y forma de pago
        $condicion_compra = isset($_POST['condicion_compra']) ? $_POST['condicion_compra'] : 'Contado';
        $forma_pago = isset($_POST['forma_pago']) ? $_POST['forma_pago'] : 'Efectivo';
        $numero_factura_proveedor = ''; // Ya no se pide al usuario
        
        // Crear factura de compra
        try {
            $sql_factura = "INSERT INTO cabecera_factura_compras 
                            (numero_factura, proveedor_id, fecha_hora, monto_total, condicion_compra, forma_pago, timbrado, numero_factura_proveedor, inicio_vigencia, fin_vigencia)
                            VALUES (:numero_factura, :proveedor_id, :fecha_hora, :monto_total, :condicion_compra, :forma_pago, :timbrado, :numero_factura_proveedor, :inicio_vigencia, :fin_vigencia)";
            $stmt_factura = $pdo->prepare($sql_factura);
            $stmt_factura->execute([
                'numero_factura' => $numero_factura,
                'proveedor_id' => $proveedor_id,
                'fecha_hora' => $fecha,
                'monto_total' => $total_compra,
                'condicion_compra' => $condicion_compra,
                'forma_pago' => $forma_pago,
                'timbrado' => $timbrado_proveedor,
                'numero_factura_proveedor' => $numero_factura_proveedor,
                'inicio_vigencia' => $fecha_inicio_vigencia,
                'fin_vigencia' => $fecha_fin_vigencia
            ]);
            $factura_id = $pdo->lastInsertId();
            
            // Calcular totales para IVA (sumar todos los productos)
            $total_5_factura = 0;
            $total_10_factura = 0;
            $total_exenta_factura = 0;
            foreach($carrito as $item) {
                $subtotal = $item['cantidad'] * $item['precio'];
                $iva_val = isset($item['iva']) ? (string)$item['iva'] : '10';
                
                if ($iva_val === 'exenta') {
                    $total_exenta_factura += $subtotal;
                } elseif ($iva_val === '5') {
                    $total_5_factura += $subtotal;
                } else {
                    $total_10_factura += $subtotal;
                }
            }
            
            // Calcular IVA 5% total como total_5 / 21
            $iva_5_total = round($total_5_factura / 21);
            // Calcular IVA 10% total como total_10 / 11
            $iva_10_total = round($total_10_factura / 11);
            
            // Insertar detalles de factura con IVA proporcional
            foreach($carrito as $item) {
                $subtotal = $item['cantidad'] * $item['precio'];
                $iva_val = isset($item['iva']) ? (string)$item['iva'] : '10';
                
                $valor_compra_5 = 0;
                $valor_compra_10 = 0;
                $valor_compra_exenta = 0;
                $subtotal_final = $subtotal;
                
                if ($iva_val === '5' && $total_5_factura > 0) {
                    // Calcular IVA 5% proporcional para este producto
                    $proporcion = $subtotal / $total_5_factura;
                    $valor_compra_5 = round($iva_5_total * $proporcion);
                    $subtotal_final = $subtotal + $valor_compra_5;
                } elseif ($iva_val === '10' && $total_10_factura > 0) {
                    // Calcular IVA 10% proporcional para este producto
                    $proporcion = $subtotal / $total_10_factura;
                    $valor_compra_10 = round($iva_10_total * $proporcion);
                    $subtotal_final = $subtotal + $valor_compra_10;
                } else {
                    // Exenta
                    $valor_compra_exenta = $subtotal;
                }
                
                try {
                    $sql_detalle_factura = "INSERT INTO detalle_factura_compras 
                                            (factura_id, producto_id, cantidad, precio_unitario, subtotal, valor_compra_5, valor_compra_10, valor_compra_exenta)
                                            VALUES (:factura_id, :producto_id, :cantidad, :precio_unitario, :subtotal, :valor_compra_5, :valor_compra_10, :valor_compra_exenta)";
                    $stmt_detalle_factura = $pdo->prepare($sql_detalle_factura);
                    $stmt_detalle_factura->execute([
                        'factura_id' => $factura_id,
                        'producto_id' => $item['producto_id'],
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $item['precio'],
                        'subtotal' => $subtotal_final,
                        'valor_compra_5' => $valor_compra_5,
                        'valor_compra_10' => $valor_compra_10,
                        'valor_compra_exenta' => $valor_compra_exenta
                    ]);
                } catch (PDOException $e) {
                    // Si las columnas de IVA no existen, intentar sin valor_compra_5 (compatibilidad)
                    try {
                        $sql_detalle_factura = "INSERT INTO detalle_factura_compras 
                                                (factura_id, producto_id, cantidad, precio_unitario, subtotal, valor_compra_10, valor_compra_exenta)
                                                VALUES (:factura_id, :producto_id, :cantidad, :precio_unitario, :subtotal, :valor_compra_10, :valor_compra_exenta)";
                        $stmt_detalle_factura = $pdo->prepare($sql_detalle_factura);
                        $stmt_detalle_factura->execute([
                            'factura_id' => $factura_id,
                            'producto_id' => $item['producto_id'],
                            'cantidad' => $item['cantidad'],
                            'precio_unitario' => $item['precio'],
                            'subtotal' => $subtotal_final,
                            'valor_compra_10' => $valor_compra_10,
                            'valor_compra_exenta' => $valor_compra_exenta
                        ]);
                    } catch (PDOException $e2) {
                        // Si tampoco funciona, insertar sin columnas de IVA (compatibilidad máxima)
                        try {
                            $sql_detalle_factura = "INSERT INTO detalle_factura_compras 
                                                    (factura_id, producto_id, cantidad, precio_unitario, subtotal)
                                                    VALUES (:factura_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
                            $stmt_detalle_factura = $pdo->prepare($sql_detalle_factura);
                            $stmt_detalle_factura->execute([
                                'factura_id' => $factura_id,
                                'producto_id' => $item['producto_id'],
                                'cantidad' => $item['cantidad'],
                                'precio_unitario' => $item['precio'],
                                'subtotal' => $subtotal_final
                            ]);
                        } catch (PDOException $e3) {
                            error_log("Error al insertar detalle de factura de compra: " . $e3->getMessage());
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Si la tabla no existe, continuar sin crear factura (se creará después)
        }
        
        // Registrar en auditoría
        include $base_path . 'includes/auditoria.php';
        registrarAuditoria('crear', 'compras', 'Compra #' . $compra_id . ' creada. Proveedor: ' . $nombre_proveedor . ', Total: ' . number_format($total_compra,0,',','.'));

        // Insertar detalles y actualizar stock
        foreach($carrito as $item) {
            $subtotal = $item['cantidad'] * $item['precio'];

            // Insertar detalle incluyendo subtotal
            try {
                $sql_detalle = "INSERT INTO compras_detalle (compra_id, producto_id, cantidad, precio_unitario, subtotal)
                                VALUES (:compra_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
                $stmt_detalle = $pdo->prepare($sql_detalle);
                $stmt_detalle->execute([
                    'compra_id' => $compra_id,
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'subtotal' => $subtotal
                ]);
            } catch (PDOException $e) {
                // Si falla, intentar sin el campo subtotal (compatibilidad con tablas antiguas)
                try {
                    $sql_detalle = "INSERT INTO compras_detalle (compra_id, producto_id, cantidad, precio_unitario)
                                    VALUES (:compra_id, :producto_id, :cantidad, :precio_unitario)";
                    $stmt_detalle = $pdo->prepare($sql_detalle);
                    $stmt_detalle->execute([
                        'compra_id' => $compra_id,
                        'producto_id' => $item['producto_id'],
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $item['precio']
                    ]);
                } catch (PDOException $e2) {
                    // Si sigue fallando, mostrar error pero continuar
                    error_log("Error al insertar detalle de compra: " . $e2->getMessage());
                }
            }

            // Actualizar stock
            $sql_stock = "UPDATE productos SET stock = stock + :cantidad WHERE id=:producto_id";
            $stmt_stock = $pdo->prepare($sql_stock);
            $stmt_stock->execute([
                'cantidad' => $item['cantidad'],
                'producto_id' => $item['producto_id']
            ]);
        }

        // Registrar egreso en caja
        if ($caja_abierta) {
            $concepto = "Compra a proveedor ID $proveedor_id, compra ID $compra_id";
            $stmt_egreso = $pdo->prepare("
                INSERT INTO caja_movimientos 
                (caja_id, fecha, tipo, concepto, monto)
                VALUES (:caja_id, :fecha, 'Egreso', :concepto, :monto)
            ");
            $stmt_egreso->execute([
                'caja_id' => $caja_abierta['id'],
                'fecha' => $fecha,
                'concepto' => $concepto,
                'monto' => $total_compra
            ]);
        }

            // Guardar mensaje en sesión y redirigir
            $_SESSION['mensaje'] = "Compra registrada correctamente. Total: " . number_format($total_compra, 0, ',', '.') . " Gs";
            header("Location: compras.php?success=1&compra_id=".$compra_id);
            exit();
        }
    }
}

// Si hay éxito en GET, mostrar mensaje
if (isset($_GET['success']) && isset($_GET['compra_id'])) {
    $mensaje = "Compra registrada correctamente.";
}
?>

<div class="container tabla-responsive">
    <h1>Registrar Compra</h1>

    <?php if($mensaje != ""): ?>
        <div class="mensaje <?= strpos($mensaje,'Error')===false?'exito':'error' ?>"><?= $mensaje ?></div>
    <?php endif; ?>

    <!-- Formulario de selección rápida horizontal -->
    <div class="venta-rapida-container">
        <form id="form_agregar_producto" class="venta-rapida-form">
            <div class="form-row-horizontal">
                <div class="form-group-horizontal">
                    <label>Proveedor:</label>
                    <select id="select_proveedor" class="form-select-horizontal" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach($proveedores as $p): ?>
                            <option value="<?= $p['id'] ?>" data-nombre="<?= htmlspecialchars($p['empresa']) ?>">
                                <?= htmlspecialchars($p['empresa']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group-horizontal">
                    <label>Producto:</label>
                    <select id="select_producto" class="form-select-horizontal" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach($productos as $prod): ?>
                            <option value="<?= $prod['id'] ?>" 
                                    data-precio="<?= round($prod['precio'],0) ?>" 
                                    data-nombre="<?= htmlspecialchars($prod['nombre']) ?>"
                                    data-stock="<?= $prod['stock'] ?>">
                                <?= htmlspecialchars($prod['nombre']) ?> (Stock: <?= $prod['stock'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group-horizontal">
                    <label>Cantidad:</label>
                    <input type="number" id="input_cantidad" class="form-input-horizontal" min="1" value="1" required>
                </div>
                
                <div class="form-group-horizontal">
                    <label>Precio Unitario:</label>
                    <input type="number" id="input_precio" class="form-input-horizontal" step="0.01" min="0" required>
                </div>
                
        <div class="form-group-horizontal">
            <label>IVA:</label>
            <select id="select_iva" class="form-select-horizontal" required>
                <option value="5">5%</option>
                <option value="10" selected>10%</option>
                <option value="exenta">Exenta</option>
            </select>
        </div>
                
                <div class="form-group-horizontal">
                    <label>&nbsp;</label>
                    <button type="button" id="btn_agregar_carrito" class="btn-agregar-carrito">
                        <i class="fas fa-cart-plus"></i> Agregar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Información del proveedor seleccionado -->
    <div id="info_proveedor" class="info-cliente-box" style="display:none;">
        <div class="info-cliente-content">
            <strong>Proveedor:</strong> <span id="nombre_proveedor_seleccionado"></span>
        </div>
    </div>

    <!-- Carrito de productos -->
    <div class="carrito-container">
        <h2><i class="fas fa-shopping-cart"></i> Carrito de Compra</h2>
        <div id="carrito_vacio" class="carrito-vacio">
            <p>No hay productos en el carrito. Seleccione proveedor y productos para agregar.</p>
        </div>
        <table id="tabla_carrito" class="carrito-table" style="display:none;">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>IVA</th>
                    <th>Subtotal</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="carrito_body">
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right;"><strong>TOTAL:</strong></td>
                    <td id="total_carrito" style="font-weight:bold; font-size:18px;">0</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Formulario de confirmación de compra -->
    <div id="form_confirmar_compra" class="form-confirmar-container" style="display:none;">
        <form method="POST" id="form_compra_final">
            <input type="hidden" name="proveedor_id" id="input_proveedor_id">
            <input type="hidden" name="carrito" id="input_carrito_json">
            
            <div class="form-row-horizontal">
                <div class="form-group-horizontal">
                    <label>Condición:</label>
                    <select name="condicion_compra" id="select_condicion" class="form-select-horizontal" required>
                        <option value="Contado" selected>Contado</option>
                        <option value="Crédito">Crédito</option>
                    </select>
                </div>
                
                <div class="form-group-horizontal">
                    <label>Forma de Pago:</label>
                    <select name="forma_pago" id="select_forma_pago" class="form-select-horizontal" required>
                        <option value="Efectivo" selected>Efectivo</option>
                        <option value="Tarjeta">Tarjeta</option>
                        <option value="Transferencia">Transferencia</option>
                    </select>
                </div>
                
                <div class="form-group-horizontal">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-confirmar-venta">
                        <i class="fas fa-check"></i> Confirmar Compra
                    </button>
                </div>
            </div>
            
            <div style="padding: 10px; background: #f0f9ff; border-radius: 5px; margin: 10px 0; font-size: 13px; color: #0369a1;">
                <i class="fas fa-info-circle"></i> <strong>Nota:</strong> El número de factura y timbrado se generarán automáticamente al confirmar la compra.
            </div>
            
            <?php if ($caja_abierta): ?>
            <div id="info_saldo_caja" style="padding: 10px; background: #fff7ed; border-left: 4px solid #f59e0b; border-radius: 5px; margin: 10px 0; font-size: 13px; color: #92400e;">
                <i class="fas fa-wallet"></i> <strong>Saldo disponible en caja:</strong> <span id="saldo_disponible_texto"><?= number_format($saldo_disponible_caja, 0, ',', '.') ?> Gs</span>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Lista de facturas de compras -->
    <?php if(!empty($facturas_compras_recientes)): ?>
    <div class="ventas-recientes-container">
        <h2><i class="fas fa-file-invoice"></i> Facturas de Compras</h2>
        <div class="table-responsive-inline">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>N° Factura</th>
                        <th>Proveedor</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Condición</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($facturas_compras_recientes as $factura): ?>
                        <tr>
                            <td><?= htmlspecialchars($factura['numero_factura']) ?></td>
                            <td><?= htmlspecialchars(isset($factura['nombre_proveedor']) && $factura['nombre_proveedor'] ? $factura['nombre_proveedor'] : 'N/A') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($factura['fecha_hora'])) ?></td>
                            <td><?= number_format($factura['monto_total'],0,',','.') ?> Gs</td>
                            <td><?= htmlspecialchars($factura['condicion_compra']) ?></td>
                            <td class="acciones">
                                <a href="/repuestos/modulos/compras/ver_factura_compra.php?id=<?= $factura['id'] ?>" class="btn btn-edit" data-tooltip="Ver" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="/repuestos/modulos/compras/imprimir_factura_compra.php?id=<?= $factura['id'] ?>" class="btn btn-edit" data-tooltip="Imprimir" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Lista de compras recientes -->
    <div class="ventas-recientes-container">
        <h2><i class="fas fa-history"></i> Compras Recientes</h2>
        <div class="table-responsive-inline">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Proveedor</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($compras_recientes && count($compras_recientes) > 0): ?>
                    <?php foreach($compras_recientes as $compra): ?>
                        <tr>
                            <td><?= htmlspecialchars($compra['id']) ?></td>
                            <td><?= htmlspecialchars(isset($compra['nombre_proveedor']) && $compra['nombre_proveedor'] ? $compra['nombre_proveedor'] : 'N/A') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($compra['fecha'])) ?></td>
                            <td><?= number_format($compra['total'],0,',','.') ?> Gs</td>
                            <td class="acciones">
                                <a href="#" class="btn btn-edit" data-tooltip="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No hay compras recientes.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Reutilizar estilos de ventas.php */
.venta-rapida-container {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.venta-rapida-form {
    margin: 0;
}

.form-row-horizontal {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.form-group-horizontal {
    flex: 1;
    min-width: 150px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}

.form-group-horizontal label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    font-size: 13px;
    color: #333;
    height: 20px;
    line-height: 20px;
    flex-shrink: 0;
}

.form-select-horizontal,
.form-input-horizontal {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
    height: 42px;
    margin: 0;
    vertical-align: bottom;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

input[type="number"].form-input-horizontal {
    -moz-appearance: textfield;
}

input[type="number"].form-input-horizontal::-webkit-outer-spin-button,
input[type="number"].form-input-horizontal::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.form-select-horizontal:focus,
.form-input-horizontal:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.btn-agregar-carrito {
    width: 100%;
    padding: 0;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s;
    height: 42px;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    margin: 0;
}

.btn-agregar-carrito:hover {
    background: #059669;
}

.btn-agregar-carrito i {
    margin-right: 5px;
}

.info-cliente-box {
    background: #e0f2fe;
    padding: 12px 20px;
    border-radius: 6px;
    margin-bottom: 20px;
    border-left: 4px solid #2563eb;
}

.info-cliente-content {
    font-size: 14px;
    color: #1e40af;
}

.carrito-container {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.carrito-container h2 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #2563eb;
}

.carrito-vacio {
    text-align: center;
    padding: 40px;
    color: #6b7280;
    font-style: italic;
}

.carrito-table {
    width: 100%;
    border-collapse: collapse;
}

.carrito-table th {
    background: #2563eb;
    color: white;
    padding: 12px;
    text-align: left;
    font-weight: bold;
}

.carrito-table td {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
}

.carrito-table tbody tr:hover {
    background: #f9fafb;
}

.carrito-table tfoot {
    background: #f3f4f6;
    font-weight: bold;
}

.btn-eliminar-item {
    background: #dc2626;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.btn-eliminar-item:hover {
    background: #b91c1c;
}

.form-confirmar-container {
    background: #f0f9ff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    border: 2px solid #2563eb;
}

.btn-confirmar-venta {
    width: 100%;
    padding: 10px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    margin: 0;
    line-height: 1;
}

.btn-confirmar-venta:hover {
    background: #1e40af;
}

.btn-confirmar-venta i {
    margin-right: 5px;
}

.ventas-recientes-container {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.ventas-recientes-container h2 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #2563eb;
}

.table-responsive-inline {
    overflow-x: auto;
}

@media (max-width: 768px) {
    .form-row-horizontal {
        flex-direction: column;
    }
    
    .form-group-horizontal {
        min-width: 100%;
    }
}
</style>

<script>
// Variables globales
var carrito = [];
var proveedorSeleccionado = null;
var saldoDisponible = <?php echo $saldo_disponible_caja; ?>;

// Datos de productos y proveedores desde PHP
var productos = <?php echo json_encode($productos); ?>;
var proveedores = <?php echo json_encode($proveedores); ?>;

// Cuando se selecciona un proveedor
document.getElementById('select_proveedor').addEventListener('change', function() {
    var select = this;
    var option = select.options[select.selectedIndex];
    if (option.value) {
        proveedorSeleccionado = {
            id: option.value,
            nombre: option.dataset.nombre
        };
        document.getElementById('nombre_proveedor_seleccionado').textContent = proveedorSeleccionado.nombre;
        document.getElementById('info_proveedor').style.display = 'block';
        document.getElementById('input_proveedor_id').value = proveedorSeleccionado.id;
        verificarCarrito();
    } else {
        proveedorSeleccionado = null;
        document.getElementById('info_proveedor').style.display = 'none';
        document.getElementById('input_proveedor_id').value = '';
        verificarCarrito();
    }
});

// Cuando se selecciona un producto, autocompletar precio
document.getElementById('select_producto').addEventListener('change', function() {
    var select = this;
    var option = select.options[select.selectedIndex];
    if (option.value && option.dataset.precio) {
        document.getElementById('input_precio').value = option.dataset.precio;
    }
});

// Agregar producto al carrito
document.getElementById('btn_agregar_carrito').addEventListener('click', function() {
    var selectProducto = document.getElementById('select_producto');
    var inputCantidad = document.getElementById('input_cantidad');
    var inputPrecio = document.getElementById('input_precio');
    var selectIva = document.getElementById('select_iva');
    
    if (!proveedorSeleccionado) {
        alert('Por favor, seleccione un proveedor primero.');
        document.getElementById('select_proveedor').focus();
        return;
    }
    
    if (!selectProducto.value) {
        alert('Por favor, seleccione un producto.');
        selectProducto.focus();
        return;
    }
    
    var cantidad = parseFloat(inputCantidad.value) || 1;
    var precio = parseFloat(inputPrecio.value) || 0;
    
    if (cantidad <= 0) {
        alert('La cantidad debe ser mayor a 0.');
        return;
    }
    
    if (precio <= 0) {
        alert('El precio debe ser mayor a 0.');
        return;
    }
    
    var option = selectProducto.options[selectProducto.selectedIndex];
    
    var producto = {
        producto_id: option.value,
        nombre: option.dataset.nombre,
        cantidad: cantidad,
        precio: precio,
        iva: selectIva.value
    };
    
    carrito.push(producto);
    actualizarCarrito();
    
    // Limpiar campos
    selectProducto.selectedIndex = 0;
    inputCantidad.value = 1;
    inputPrecio.value = '';
    selectIva.selectedIndex = 0;
});

// Actualizar vista del carrito
function actualizarCarrito() {
    var carritoBody = document.getElementById('carrito_body');
    var carritoVacio = document.getElementById('carrito_vacio');
    var tablaCarrito = document.getElementById('tabla_carrito');
    var totalCarrito = 0;
    
    carritoBody.innerHTML = '';
    
    if (carrito.length === 0) {
        carritoVacio.style.display = 'block';
        tablaCarrito.style.display = 'none';
        document.getElementById('form_confirmar_compra').style.display = 'none';
        // Actualizar saldo disponible
        actualizarSaldoDisponible(0);
        return;
    }
    
    carritoVacio.style.display = 'none';
    tablaCarrito.style.display = 'table';
    
    // Calcular totales sin IVA primero
    var total5 = 0;
    var total10 = 0;
    var totalExenta = 0;
    
    carrito.forEach(function(item) {
        var subtotal = item.cantidad * item.precio;
        if (item.iva === 'exenta') {
            totalExenta += subtotal;
        } else if (item.iva === '5') {
            total5 += subtotal;
        } else {
            total10 += subtotal;
        }
    });
    
    // Calcular IVA 5% como total5 / 21
    var iva5 = Math.round(total5 / 21);
    // Calcular IVA 10% como total10 / 11
    var iva10 = Math.round(total10 / 11);
    var totalConIva = total5 + iva5 + total10 + iva10 + totalExenta;
    
    // Mostrar productos en la tabla
    carrito.forEach(function(item, index) {
        var subtotal = item.cantidad * item.precio;
        var ivaTexto = '';
        var subtotalMostrar = subtotal;
        
        if (item.iva === '5') {
            ivaTexto = '5%';
            // Calcular IVA 5% proporcional para este producto
            if (total5 > 0) {
                var proporcion = subtotal / total5;
                var ivaProporcional = Math.round(iva5 * proporcion);
                subtotalMostrar = subtotal + ivaProporcional;
            }
        } else if (item.iva === '10') {
            ivaTexto = '10%';
            // Calcular IVA 10% proporcional para este producto
            if (total10 > 0) {
                var proporcion = subtotal / total10;
                var ivaProporcional = Math.round(iva10 * proporcion);
                subtotalMostrar = subtotal + ivaProporcional;
            }
        } else {
            ivaTexto = 'Exenta';
        }
        
        var row = document.createElement('tr');
        row.innerHTML = 
            '<td>' + item.nombre + '</td>' +
            '<td>' + item.cantidad + '</td>' +
            '<td>' + number_format(item.precio, 0, ',', '.') + ' Gs</td>' +
            '<td>' + ivaTexto + '</td>' +
            '<td>' + number_format(subtotalMostrar, 0, ',', '.') + ' Gs</td>' +
            '<td><button type="button" class="btn-eliminar-item" onclick="eliminarDelCarrito(' + index + ')"><i class="fas fa-trash"></i></button></td>';
        carritoBody.appendChild(row);
    });
    
    document.getElementById('total_carrito').textContent = number_format(totalConIva, 0, ',', '.') + ' Gs';
    
    // Actualizar saldo disponible
    actualizarSaldoDisponible(totalConIva);
    
    // Actualizar JSON oculto
    document.getElementById('input_carrito_json').value = JSON.stringify(carrito);
    
    verificarCarrito();
}

// Actualizar visualización del saldo disponible
function actualizarSaldoDisponible(totalCompra) {
    var saldoInfo = document.getElementById('info_saldo_caja');
    if (!saldoInfo) return;
    
    var saldoTexto = document.getElementById('saldo_disponible_texto');
    var saldoRestante = saldoDisponible - totalCompra;
    
    if (saldoTexto) {
        saldoTexto.textContent = number_format(saldoDisponible, 0, ',', '.') + ' Gs';
    }
    
    if (totalCompra > 0) {
        saldoInfo.style.display = 'block';
        if (saldoRestante < 0) {
            saldoInfo.style.background = '#fee2e2';
            saldoInfo.style.borderLeftColor = '#dc2626';
            saldoInfo.style.color = '#991b1b';
            saldoInfo.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <strong>Saldo insuficiente:</strong> Saldo disponible: ' + number_format(saldoDisponible, 0, ',', '.') + ' Gs. Total compra: ' + number_format(totalCompra, 0, ',', '.') + ' Gs. <strong>Faltan: ' + number_format(Math.abs(saldoRestante), 0, ',', '.') + ' Gs</strong>';
        } else {
            saldoInfo.style.background = '#f0fdf4';
            saldoInfo.style.borderLeftColor = '#10b981';
            saldoInfo.style.color = '#065f46';
            saldoInfo.innerHTML = '<i class="fas fa-wallet"></i> <strong>Saldo disponible:</strong> ' + number_format(saldoDisponible, 0, ',', '.') + ' Gs. Total compra: ' + number_format(totalCompra, 0, ',', '.') + ' Gs. <strong>Saldo restante: ' + number_format(saldoRestante, 0, ',', '.') + ' Gs</strong>';
        }
    } else {
        saldoInfo.style.background = '#fff7ed';
        saldoInfo.style.borderLeftColor = '#f59e0b';
        saldoInfo.style.color = '#92400e';
        saldoInfo.innerHTML = '<i class="fas fa-wallet"></i> <strong>Saldo disponible en caja:</strong> <span id="saldo_disponible_texto">' + number_format(saldoDisponible, 0, ',', '.') + ' Gs</span>';
    }
}

// Función para formatear números
function number_format(number, decimals, dec_point, thousands_sep) {
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number;
    var prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
    var sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep;
    var dec = (typeof dec_point === 'undefined') ? '.' : dec_point;
    var s = '';
    var toFixedFix = function(n, prec) {
        var k = Math.pow(10, prec);
        return '' + Math.round(n * k) / k;
    };
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}

// Eliminar del carrito
function eliminarDelCarrito(index) {
    if (confirm('¿Eliminar este producto del carrito?')) {
        carrito.splice(index, 1);
        actualizarCarrito();
    }
}

// Verificar si se puede confirmar compra
function verificarCarrito() {
    var formConfirmar = document.getElementById('form_confirmar_compra');
    if (proveedorSeleccionado && carrito.length > 0) {
        formConfirmar.style.display = 'block';
    } else {
        formConfirmar.style.display = 'none';
    }
}

// Validar antes de confirmar compra
document.getElementById('form_compra_final').addEventListener('submit', function(e) {
    var totalCarrito = 0;
    carrito.forEach(function(item) {
        totalCarrito += item.cantidad * item.precio;
    });
    
    if (totalCarrito > saldoDisponible) {
        e.preventDefault();
        alert('ERROR: Saldo insuficiente en caja.\n\nSaldo disponible: ' + number_format(saldoDisponible, 0, ',', '.') + ' Gs\nMonto de compra: ' + number_format(totalCarrito, 0, ',', '.') + ' Gs\n\nFaltan: ' + number_format(totalCarrito - saldoDisponible, 0, ',', '.') + ' Gs');
        return false;
    }
});

// Prevenir envío del formulario de agregar
document.getElementById('form_agregar_producto').addEventListener('submit', function(e) {
    e.preventDefault();
});

// Inicializar
actualizarCarrito();
</script>

<?php include $base_path . 'includes/footer.php'; ?>
