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
    } else {
        $fecha = date("Y-m-d H:i:s");
        
        // Calcular total de la compra
        $total_compra = 0;
        foreach($carrito as $item) {
            $total_compra += $item['cantidad'] * $item['precio'];
        }

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
            
            // Insertar detalles de factura
            foreach($carrito as $item) {
                $subtotal = $item['cantidad'] * $item['precio'];
                $sql_detalle_factura = "INSERT INTO detalle_factura_compras 
                                        (factura_id, producto_id, cantidad, precio_unitario, subtotal)
                                        VALUES (:factura_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
                $stmt_detalle_factura = $pdo->prepare($sql_detalle_factura);
                $stmt_detalle_factura->execute([
                    'factura_id' => $factura_id,
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'subtotal' => $subtotal
                ]);
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
                    <th>Subtotal</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="carrito_body">
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:right;"><strong>TOTAL:</strong></td>
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
            </div>
            
            <div style="padding: 10px; background: #f0f9ff; border-radius: 5px; margin: 10px 0; font-size: 13px; color: #0369a1;">
                <i class="fas fa-info-circle"></i> <strong>Nota:</strong> El número de factura y timbrado se generarán automáticamente al confirmar la compra.
            </div>
            
            <div class="form-row-horizontal">
                <div class="form-group-horizontal">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-confirmar-venta">
                        <i class="fas fa-check"></i> Confirmar Compra
                    </button>
                </div>
            </div>
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
    padding: 12px 20px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s;
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
        precio: precio
    };
    
    carrito.push(producto);
    actualizarCarrito();
    
    // Limpiar campos
    selectProducto.selectedIndex = 0;
    inputCantidad.value = 1;
    inputPrecio.value = '';
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
        return;
    }
    
    carritoVacio.style.display = 'none';
    tablaCarrito.style.display = 'table';
    
    carrito.forEach(function(item, index) {
        var subtotal = item.cantidad * item.precio;
        totalCarrito += subtotal;
        
        var row = document.createElement('tr');
        row.innerHTML = 
            '<td>' + item.nombre + '</td>' +
            '<td>' + item.cantidad + '</td>' +
            '<td>' + item.precio.toLocaleString('es-PY') + ' Gs</td>' +
            '<td>' + subtotal.toLocaleString('es-PY') + ' Gs</td>' +
            '<td><button type="button" class="btn-eliminar-item" onclick="eliminarDelCarrito(' + index + ')"><i class="fas fa-trash"></i></button></td>';
        carritoBody.appendChild(row);
    });
    
    document.getElementById('total_carrito').textContent = totalCarrito.toLocaleString('es-PY') + ' Gs';
    
    // Actualizar JSON oculto
    document.getElementById('input_carrito_json').value = JSON.stringify(carrito);
    
    verificarCarrito();
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

// Prevenir envío del formulario de agregar
document.getElementById('form_agregar_producto').addEventListener('submit', function(e) {
    e.preventDefault();
});

// Inicializar
actualizarCarrito();
</script>

<?php include $base_path . 'includes/footer.php'; ?>
