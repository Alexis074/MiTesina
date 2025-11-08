<?php    
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('ventas', 'ver');
include $base_path . 'includes/header.php';

$mensaje = "";
$factura_id = 0;

$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$productos = $pdo->query("SELECT * FROM productos ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// Verificar si hay caja abierta
$stmtCaja = $pdo->query("SELECT * FROM caja WHERE estado='Abierta' ORDER BY id DESC LIMIT 1");
$caja_abierta = $stmtCaja->fetch();

// Obtener ventas recientes (últimas 20, excluyendo anuladas)
$stmt_ventas_recientes = $pdo->query("SELECT fv.*, c.nombre, c.apellido 
                                      FROM cabecera_factura_ventas fv
                                      LEFT JOIN clientes c ON fv.cliente_id = c.id
                                      WHERE (fv.anulada = 0 OR fv.anulada IS NULL)
                                      ORDER BY fv.fecha_hora DESC 
                                      LIMIT 20");
$ventas_recientes = $stmt_ventas_recientes->fetchAll();

$serie_1 = 1;
$serie_2 = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = (int)$_POST['cliente_id'];
    $ruc_cliente = isset($_POST['ruc']) ? $_POST['ruc'] : '';
    $condicion_venta = isset($_POST['condicion_venta']) ? $_POST['condicion_venta'] : 'Contado';
    $forma_pago = isset($_POST['forma_pago']) ? $_POST['forma_pago'] : 'Efectivo';
    $numero_cuotas = isset($_POST['numero_cuotas']) ? (int)$_POST['numero_cuotas'] : 0;
    
    // Validar cuotas si es crédito
    $es_credito = ($condicion_venta == 'Crédito');
    if ($es_credito && ($numero_cuotas < 2 || $numero_cuotas > 6)) {
        $mensaje = "Error: El número de cuotas debe estar entre 2 y 6 meses.";
    }
    
    // Obtener productos del carrito desde JSON
    $carrito_json = isset($_POST['carrito']) ? $_POST['carrito'] : '[]';
    $carrito = json_decode($carrito_json, true);
    
    if (empty($carrito) || !is_array($carrito)) {
        $mensaje = "Error: El carrito está vacío.";
    } elseif (!$caja_abierta) {
        $mensaje = "Error: Debe abrir la caja antes de realizar una venta.";
    } else {
        $fecha = date("Y-m-d H:i:s");
        $total_venta = 0;
        $detalle = array();

        // Primero calcular totales sin IVA
        $total_sin_iva_venta = 0;
        $total_5_venta = 0;
        $total_exenta_venta = 0;
        
        foreach($carrito as $item) {
            $subtotal = round($item['cantidad'] * $item['precio']);
            $iva_val = isset($item['iva']) ? (string)$item['iva'] : '10';
            
            if ($iva_val === '5') {
                $total_5_venta += $subtotal;
            } elseif ($iva_val === 'exenta') {
                $total_exenta_venta += $subtotal;
            } else {
                // IVA 10%
                $total_sin_iva_venta += $subtotal;
            }
        }
        
        // Calcular IVA 10% como total_sin_iva / 11
        $iva_10_total_venta = round($total_sin_iva_venta / 11);
        // Calcular IVA 5% como total_5 / 21 (equivalente a / 1.05 * 0.05, pero usando método /21)
        $iva_5_total_venta = round($total_5_venta / 21);
        
        // Ahora calcular detalle con IVA proporcional
        foreach($carrito as $item) {
            $prod_id = (int)$item['producto_id'];
            $cantidad = (float)$item['cantidad'];
            $precio_unitario = (float)$item['precio'];
            $subtotal = round($cantidad * $precio_unitario);

            $valor_5 = $valor_10 = $valor_exenta = 0;
            $iva_val = isset($item['iva']) ? (string)$item['iva'] : '10';
            
            if ($iva_val === '5') {
                // Calcular IVA 5% proporcional
                if ($total_5_venta > 0) {
                    $proporcion = $subtotal / $total_5_venta;
                    $valor_5 = round($iva_5_total_venta * $proporcion);
                }
                $subtotal += $valor_5;
            } elseif ($iva_val === '10') {
                // Calcular IVA 10% proporcional
                if ($total_sin_iva_venta > 0) {
                    $proporcion = $subtotal / $total_sin_iva_venta;
                    $valor_10 = round($iva_10_total_venta * $proporcion);
                }
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

        // Generar número de factura único
        $stmt_num = $pdo->query("SELECT MAX(id) as max_id FROM cabecera_factura_ventas");
        $row = $stmt_num->fetch(PDO::FETCH_ASSOC);
        $next_id = ($row && $row['max_id']) ? ((int)$row['max_id'] + 1) : 1;
        $numero_factura = sprintf('%03d-%03d-%06d',$serie_1,$serie_2,$next_id);
        
        $stmt_verificar = $pdo->prepare("SELECT id FROM cabecera_factura_ventas WHERE numero_factura = ?");
        $stmt_verificar->execute(array($numero_factura));
        if ($stmt_verificar->fetch()) {
            $next_id++;
            $numero_factura = sprintf('%03d-%03d-%06d',$serie_1,$serie_2,$next_id);
        }

        // Generar timbrado único
        $timbrado_base = 4571575;
        $stmt_timbrado = $pdo->query("SELECT timbrado FROM cabecera_factura_ventas WHERE timbrado IS NOT NULL");
        $timbrados = $stmt_timbrado->fetchAll(PDO::FETCH_COLUMN);
        
        $max_timbrado = $timbrado_base - 1;
        foreach ($timbrados as $t) {
            if (is_numeric($t)) {
                $t_num = (int)$t;
                if ($t_num > $max_timbrado) {
                    $max_timbrado = $t_num;
                }
            }
        }
        
        if ($max_timbrado >= $timbrado_base) {
            $next_timbrado = $max_timbrado + 1;
        } else {
            $next_timbrado = $timbrado_base;
        }
        
        $stmt_verificar_timbrado = $pdo->prepare("SELECT id FROM cabecera_factura_ventas WHERE timbrado = ?");
        $stmt_verificar_timbrado->execute(array($next_timbrado));
        if ($stmt_verificar_timbrado->fetch()) {
            $next_timbrado++;
        }
        
        // Si es venta a crédito, NO crear factura, solo crear crédito y guardar productos
        if ($es_credito && $numero_cuotas >= 2 && $numero_cuotas <= 6) {
            // Verificar y crear tablas si no existen (compatible con MySQL 5.6)
            // Verificar cada tabla individualmente
            try {
                // Verificar y crear ventas_credito
                $stmt = $pdo->query("SHOW TABLES LIKE 'ventas_credito'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("CREATE TABLE ventas_credito (
                        id INT(11) NOT NULL AUTO_INCREMENT,
                        factura_id INT(11) NULL,
                        cliente_id INT(11) NOT NULL,
                        monto_total DECIMAL(15,2) NOT NULL,
                        numero_cuotas INT(11) NOT NULL,
                        monto_cuota DECIMAL(15,2) NOT NULL,
                        fecha_creacion DATETIME NOT NULL,
                        estado ENUM('Activa','Finalizada','Cancelada') DEFAULT 'Activa',
                        fecha_finalizacion DATETIME NULL,
                        PRIMARY KEY (id),
                        KEY factura_id (factura_id),
                        KEY cliente_id (cliente_id)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
                } else {
                    // Verificar y modificar la columna factura_id si no permite NULL
                    try {
                        $stmt_check = $pdo->query("SHOW COLUMNS FROM ventas_credito WHERE Field = 'factura_id'");
                        $columna = $stmt_check->fetch(PDO::FETCH_ASSOC);
                        if ($columna && strtoupper($columna['Null']) == 'NO') {
                            $pdo->exec("ALTER TABLE ventas_credito MODIFY factura_id INT(11) NULL");
                        }
                    } catch (Exception $e) {
                        error_log("Error al verificar/modificar columna factura_id: " . $e->getMessage());
                    }
                }
                
                // Verificar y crear cuotas_credito
                $stmt = $pdo->query("SHOW TABLES LIKE 'cuotas_credito'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("CREATE TABLE cuotas_credito (
                        id INT(11) NOT NULL AUTO_INCREMENT,
                        venta_credito_id INT(11) NOT NULL,
                        numero_cuota INT(11) NOT NULL,
                        monto DECIMAL(15,2) NOT NULL,
                        fecha_vencimiento DATE NOT NULL,
                        fecha_pago DATETIME NULL,
                        monto_pagado DECIMAL(15,2) DEFAULT 0,
                        estado ENUM('Pendiente','Pagada','Vencida') DEFAULT 'Pendiente',
                        observaciones TEXT NULL,
                        PRIMARY KEY (id),
                        KEY venta_credito_id (venta_credito_id)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
                }
                
                // Verificar y crear recibos_dinero
                $stmt = $pdo->query("SHOW TABLES LIKE 'recibos_dinero'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("CREATE TABLE recibos_dinero (
                        id INT(11) NOT NULL AUTO_INCREMENT,
                        numero_recibo VARCHAR(50) NOT NULL,
                        cliente_id INT(11) NOT NULL,
                        venta_credito_id INT(11) NULL,
                        cuota_id INT(11) NULL,
                        monto DECIMAL(15,2) NOT NULL,
                        fecha_pago DATETIME NOT NULL,
                        forma_pago VARCHAR(50) NOT NULL,
                        concepto VARCHAR(255) NOT NULL,
                        observaciones TEXT NULL,
                        usuario_id INT(11) NULL,
                        PRIMARY KEY (id),
                        UNIQUE KEY numero_recibo (numero_recibo),
                        KEY cliente_id (cliente_id),
                        KEY venta_credito_id (venta_credito_id),
                        KEY cuota_id (cuota_id)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
                }
                
                // Verificar y crear pagares
                $stmt = $pdo->query("SHOW TABLES LIKE 'pagares'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("CREATE TABLE pagares (
                        id INT(11) NOT NULL AUTO_INCREMENT,
                        venta_credito_id INT(11) NOT NULL,
                        numero_pagare VARCHAR(50) NOT NULL,
                        cliente_id INT(11) NOT NULL,
                        monto_total DECIMAL(15,2) NOT NULL,
                        fecha_emision DATE NOT NULL,
                        fecha_vencimiento DATE NOT NULL,
                        lugar_pago VARCHAR(255) NOT NULL,
                        estado ENUM('Vigente','Cancelado') DEFAULT 'Vigente',
                        observaciones TEXT NULL,
                        PRIMARY KEY (id),
                        UNIQUE KEY numero_pagare (numero_pagare),
                        KEY venta_credito_id (venta_credito_id),
                        KEY cliente_id (cliente_id)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
                }
                
                // Verificar y crear detalle_ventas_credito
                $stmt = $pdo->query("SHOW TABLES LIKE 'detalle_ventas_credito'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("CREATE TABLE detalle_ventas_credito (
                        id INT(11) NOT NULL AUTO_INCREMENT,
                        venta_credito_id INT(11) NOT NULL,
                        producto_id INT(11) NOT NULL,
                        cantidad DECIMAL(10,2) NOT NULL,
                        precio_unitario DECIMAL(15,2) NOT NULL,
                        valor_venta_5 DECIMAL(15,2) DEFAULT 0,
                        valor_venta_10 DECIMAL(15,2) DEFAULT 0,
                        valor_venta_exenta DECIMAL(15,2) DEFAULT 0,
                        total_parcial DECIMAL(15,2) NOT NULL,
                        PRIMARY KEY (id),
                        KEY venta_credito_id (venta_credito_id),
                        KEY producto_id (producto_id)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
                }
            } catch (Exception $e) {
                error_log("Error al crear/verificar tablas de crédito: " . $e->getMessage());
                $mensaje = "Error al verificar tablas de crédito: " . $e->getMessage();
            }
            
            // Verificar que las tablas necesarias existan antes de continuar
            if (empty($mensaje)) {
                try {
                    // Verificar que detalle_ventas_credito existe
                    $stmt = $pdo->query("SHOW TABLES LIKE 'detalle_ventas_credito'");
                    if ($stmt->rowCount() == 0) {
                        $mensaje = "Error: La tabla detalle_ventas_credito no existe y no se pudo crear.";
                    }
                } catch (Exception $e) {
                    $mensaje = "Error al verificar tabla detalle_ventas_credito: " . $e->getMessage();
                }
            }
            
            if (empty($mensaje)) {
                try {
                    $pdo->beginTransaction();
                
                // Descontar stock de productos
                $stmt_stock = $pdo->prepare("UPDATE productos SET stock=stock-:cantidad WHERE id=:producto_id");
                foreach($detalle as $item){
                    $stmt_stock->execute(array(
                        ':cantidad'=>$item['cantidad'],
                        ':producto_id'=>$item['producto_id']
                    ));
                }
                
                // Verificar y modificar la columna factura_id si no permite NULL
                try {
                    $stmt_check = $pdo->query("SHOW COLUMNS FROM ventas_credito WHERE Field = 'factura_id'");
                    $columna = $stmt_check->fetch(PDO::FETCH_ASSOC);
                    if ($columna && strtoupper($columna['Null']) == 'NO') {
                        // Modificar la columna para permitir NULL
                        $pdo->exec("ALTER TABLE ventas_credito MODIFY factura_id INT(11) NULL");
                    }
                } catch (Exception $e) {
                    error_log("Error al verificar/modificar columna factura_id: " . $e->getMessage());
                }
                
                // Insertar venta a crédito (sin factura_id todavía)
                $monto_cuota = round($total_venta / $numero_cuotas);
                $monto_ultima_cuota = $total_venta - ($monto_cuota * ($numero_cuotas - 1));
                
                // Insertar sin incluir factura_id (será NULL por defecto)
                $sql_credito = "INSERT INTO ventas_credito 
                               (cliente_id, monto_total, numero_cuotas, monto_cuota, fecha_creacion, estado)
                               VALUES (:cliente_id, :monto_total, :numero_cuotas, :monto_cuota, :fecha_creacion, 'Activa')";
                $stmt_credito = $pdo->prepare($sql_credito);
                $stmt_credito->execute([
                    'cliente_id' => $cliente_id,
                    'monto_total' => $total_venta,
                    'numero_cuotas' => $numero_cuotas,
                    'monto_cuota' => $monto_cuota,
                    'fecha_creacion' => $fecha
                ]);
                $venta_credito_id = $pdo->lastInsertId();
                
                // Guardar detalles de productos en detalle_ventas_credito
                $stmt_det_credito = $pdo->prepare("INSERT INTO detalle_ventas_credito
                    (venta_credito_id, producto_id, cantidad, precio_unitario, valor_venta_5, valor_venta_10, valor_venta_exenta, total_parcial)
                    VALUES (:venta_credito_id, :producto_id, :cantidad, :precio_unitario, :valor_venta_5, :valor_venta_10, :valor_venta_exenta, :total_parcial)");
                
                foreach($detalle as $item){
                    $stmt_det_credito->execute(array(
                        ':venta_credito_id'=>$venta_credito_id,
                        ':producto_id'=>$item['producto_id'],
                        ':cantidad'=>$item['cantidad'],
                        ':precio_unitario'=>$item['precio_unitario'],
                        ':valor_venta_5'=>$item['valor_venta_5'],
                        ':valor_venta_10'=>$item['valor_venta_10'],
                        ':valor_venta_exenta'=>$item['valor_venta_exenta'],
                        ':total_parcial'=>$item['total_parcial']
                    ));
                }
                
                // Crear cuotas (30 días entre cada una)
                $fecha_base = new DateTime($fecha);
                for ($i = 1; $i <= $numero_cuotas; $i++) {
                    $fecha_vencimiento = clone $fecha_base;
                    $fecha_vencimiento->modify('+' . ($i * 30) . ' days');
                    
                    $monto_cuota_actual = ($i == $numero_cuotas) ? $monto_ultima_cuota : $monto_cuota;
                    
                    $sql_cuota = "INSERT INTO cuotas_credito 
                                 (venta_credito_id, numero_cuota, monto, fecha_vencimiento, estado)
                                 VALUES (:venta_credito_id, :numero_cuota, :monto, :fecha_vencimiento, 'Pendiente')";
                    $stmt_cuota = $pdo->prepare($sql_cuota);
                    $stmt_cuota->execute([
                        'venta_credito_id' => $venta_credito_id,
                        'numero_cuota' => $i,
                        'monto' => $monto_cuota_actual,
                        'fecha_vencimiento' => $fecha_vencimiento->format('Y-m-d')
                    ]);
                }
                
                $pdo->commit();
                
                // Registrar en auditoría
                include $base_path . 'includes/auditoria.php';
                registrarAuditoria('crear', 'credito', 'Venta a crédito creada. Cliente ID: ' . $cliente_id . ', Total: ' . number_format($total_venta,0,',','.') . ', Cuotas: ' . $numero_cuotas);
                
                $mensaje = "Venta a crédito registrada. Total: ".number_format($total_venta,0,',','.')." Gs - Crédito a $numero_cuotas cuotas creado. Se generará factura al completar todos los pagos.";
                
                // Recargar para limpiar el formulario
                header("Location: ventas.php?success=1&credito_id=".$venta_credito_id);
                exit();
                
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log("Error al crear crédito: " . $e->getMessage());
                    $mensaje = "Error al crear crédito: " . $e->getMessage();
                }
            } else {
                // Si hubo error en la creación de tablas, no continuar
                error_log("Error: No se puede crear crédito porque faltan tablas. Mensaje: " . $mensaje);
            }
        } else {
            // Venta de contado - crear factura normalmente
            $timbrado = (string)$next_timbrado;
            // Generar fechas de vigencia (año completo: 01/01/YYYY al 31/12/YYYY)
            $anio_actual = date('Y');
            $inicio_vigencia = $anio_actual . '-01-01'; // Primer día del año
            $fin_vigencia = $anio_actual . '-12-31'; // Último día del año

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

            // Registrar en caja
            if ($caja_abierta) {
                $stmt_movimiento = $pdo->prepare("INSERT INTO caja_movimientos 
                                                 (caja_id, fecha, tipo, concepto, monto)
                                                 VALUES (?, NOW(), 'Ingreso', ?, ?)");
                $concepto_caja = "Venta - Factura #" . $numero_factura . " - Cliente ID: " . $cliente_id;
                $stmt_movimiento->execute([$caja_abierta['id'], $concepto_caja, $total_venta]);
            }

            // Registrar en auditoría
            include $base_path . 'includes/auditoria.php';
            registrarAuditoria('crear', 'ventas', 'Factura #' . $numero_factura . ' creada. Cliente ID: ' . $cliente_id . ', Total: ' . number_format($total_venta,0,',','.'));
            
            $mensaje = "Venta registrada. Factura: $numero_factura Total: ".number_format($total_venta,0,',','.');
            
            // Recargar para mostrar la nueva venta
            header("Location: ventas.php?success=1&factura_id=".$factura_id);
            exit();
        }
    }
}

// Si hay éxito en GET, mostrar mensaje
if (isset($_GET['success']) && isset($_GET['factura_id'])) {
    $factura_id = (int)$_GET['factura_id'];
    $mensaje = "Venta registrada correctamente.";
}
?>

<div class="container tabla-responsive">
    <h1>Registrar Venta</h1>

    <?php if($mensaje!=""): ?>
        <div class="mensaje <?= strpos($mensaje,'Error') === false ? 'exito' : 'error' ?>"><?= $mensaje ?>
            <?php if($factura_id): ?>
                <br><br>
                <a href="/repuestos/modulos/ventas/ver_factura.php?id=<?= $factura_id ?>" class="btn-export" target="_blank"><i class="fas fa-eye"></i> Ver Factura</a>
                <a href="/repuestos/modulos/ventas/imprimir_factura.php?id=<?= $factura_id ?>" class="btn-export" target="_blank"><i class="fas fa-print"></i> Imprimir Factura</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Formulario de selección rápida horizontal -->
    <div class="venta-rapida-container">
        <form id="form_agregar_producto" class="venta-rapida-form">
            <div class="form-row-horizontal">
                <div class="form-group-horizontal" style="flex: 1 1 200px;">
                    <label>Cliente:</label>
                    <div style="display: flex; gap: 5px; align-items: flex-end;">
                        <select id="select_cliente" class="form-select-horizontal" required style="flex: 1;">
                            <option value="">-- Seleccione --</option>
                            <?php foreach($clientes as $c): ?>
                                <option value="<?= $c['id'] ?>" data-ruc="<?= htmlspecialchars($c['ruc']) ?>" data-nombre="<?= htmlspecialchars($c['nombre'].' '.$c['apellido']) ?>">
                                    <?= htmlspecialchars($c['nombre'].' '.$c['apellido']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <a href="/repuestos/modulos/clientes/agregar_cliente.php" class="btn-agregar-carrito" style="width: auto; padding: 0 15px; flex-shrink: 0; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user-plus"></i> Agregar
                        </a>
                    </div>
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
                    <label>IVA:</label>
                    <select id="select_iva" class="form-select-horizontal">
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

    <!-- Información del cliente seleccionado -->
    <div id="info_cliente" class="info-cliente-box" style="display:none;">
        <div class="info-cliente-content">
            <strong>Cliente:</strong> <span id="nombre_cliente_seleccionado"></span> | 
            <strong>RUC:</strong> <span id="ruc_cliente_seleccionado"></span>
        </div>
    </div>

    <!-- Carrito de productos -->
    <div class="carrito-container">
        <h2><i class="fas fa-shopping-cart"></i> Carrito de Venta</h2>
        <div id="carrito_vacio" class="carrito-vacio">
            <p>No hay productos en el carrito. Seleccione cliente y productos para agregar.</p>
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

    <!-- Formulario de confirmación de venta -->
    <div id="form_confirmar_venta" class="form-confirmar-container" style="display:none;">
        <form method="POST" id="form_venta_final">
            <input type="hidden" name="cliente_id" id="input_cliente_id">
            <input type="hidden" name="ruc" id="input_ruc">
            <input type="hidden" name="carrito" id="input_carrito_json">
            
            <div class="form-row-horizontal">
                <div class="form-group-horizontal">
                    <label>Condición:</label>
                    <select name="condicion_venta" id="select_condicion" class="form-select-horizontal" required>
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
                
                <!-- Campo para número de cuotas (solo visible cuando es crédito) -->
                <div id="campo_cuotas" class="form-group-horizontal" style="display:none;">
                    <label>Número de Cuotas:</label>
                    <select name="numero_cuotas" id="select_cuotas" class="form-select-horizontal">
                        <option value="2">2 meses</option>
                        <option value="3">3 meses</option>
                        <option value="4">4 meses</option>
                        <option value="5">5 meses</option>
                        <option value="6" selected>6 meses</option>
                    </select>
                </div>
                
                <div class="form-group-horizontal">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-confirmar-venta">
                        <i class="fas fa-check"></i> Confirmar Venta
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Lista de ventas recientes -->
    <div class="ventas-recientes-container">
        <h2><i class="fas fa-history"></i> Ventas Recientes</h2>
        <div class="table-responsive-inline">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>N° Factura</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Condición</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($ventas_recientes && count($ventas_recientes) > 0): ?>
                    <?php foreach($ventas_recientes as $venta): ?>
                        <tr>
                            <td><?= htmlspecialchars($venta['numero_factura']) ?></td>
                            <td><?= htmlspecialchars((isset($venta['nombre']) ? $venta['nombre'] : '') . ' ' . (isset($venta['apellido']) && $venta['apellido'] ? $venta['apellido'] : 'N/A')) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($venta['fecha_hora'])) ?></td>
                            <td><?= number_format($venta['monto_total'],0,',','.') ?> Gs</td>
                            <td><?= htmlspecialchars($venta['condicion_venta']) ?></td>
                            <td class="acciones">
                                <a href="/repuestos/modulos/ventas/ver_factura.php?id=<?= $venta['id'] ?>" class="btn btn-edit" data-tooltip="Ver" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="/repuestos/modulos/ventas/imprimir_factura.php?id=<?= $venta['id'] ?>" class="btn btn-edit" data-tooltip="Imprimir" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No hay ventas recientes.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Estilos para venta rápida horizontal */
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

/* Carrito */
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

/* Formulario de confirmación */
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

/* Ventas recientes */
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
var clienteSeleccionado = null;

// Datos de productos y clientes desde PHP
var productos = <?php echo json_encode($productos); ?>;
var clientes = <?php echo json_encode($clientes); ?>;

// Cuando se selecciona un cliente
document.getElementById('select_cliente').addEventListener('change', function() {
    var select = this;
    var option = select.options[select.selectedIndex];
    if (option.value) {
        clienteSeleccionado = {
            id: option.value,
            nombre: option.dataset.nombre,
            ruc: option.dataset.ruc
        };
        document.getElementById('nombre_cliente_seleccionado').textContent = clienteSeleccionado.nombre;
        document.getElementById('ruc_cliente_seleccionado').textContent = clienteSeleccionado.ruc;
        document.getElementById('info_cliente').style.display = 'block';
        document.getElementById('input_cliente_id').value = clienteSeleccionado.id;
        document.getElementById('input_ruc').value = clienteSeleccionado.ruc;
        verificarCarrito();
    } else {
        clienteSeleccionado = null;
        document.getElementById('info_cliente').style.display = 'none';
        document.getElementById('input_cliente_id').value = '';
        document.getElementById('input_ruc').value = '';
        verificarCarrito();
    }
});

// Agregar producto al carrito
document.getElementById('btn_agregar_carrito').addEventListener('click', function() {
    var selectProducto = document.getElementById('select_producto');
    var inputCantidad = document.getElementById('input_cantidad');
    var selectIva = document.getElementById('select_iva');
    
    if (!clienteSeleccionado) {
        alert('Por favor, seleccione un cliente primero.');
        document.getElementById('select_cliente').focus();
        return;
    }
    
    if (!selectProducto.value) {
        alert('Por favor, seleccione un producto.');
        selectProducto.focus();
        return;
    }
    
    var option = selectProducto.options[selectProducto.selectedIndex];
    var cantidad = parseInt(inputCantidad.value) || 1;
    var precio = parseFloat(option.dataset.precio) || 0;
    var stock = parseInt(option.dataset.stock) || 0;
    
    if (cantidad > stock) {
        alert('No hay suficiente stock. Stock disponible: ' + stock);
        return;
    }
    
    var producto = {
        producto_id: option.value,
        nombre: option.dataset.nombre,
        cantidad: cantidad,
        precio: precio,
        iva: selectIva.value,
        stock: stock
    };
    
    carrito.push(producto);
    actualizarCarrito();
    
    // Limpiar campos
    selectProducto.selectedIndex = 0;
    inputCantidad.value = 1;
    selectIva.selectedIndex = 1;
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
        document.getElementById('form_confirmar_venta').style.display = 'none';
        return;
    }
    
    carritoVacio.style.display = 'none';
    tablaCarrito.style.display = 'table';
    
    // Calcular totales sin IVA primero
    var totalSinIva = 0;
    var total5 = 0;
    var totalExenta = 0;
    
    carrito.forEach(function(item) {
        var subtotal = item.cantidad * item.precio;
        if (item.iva === '5') {
            total5 += subtotal;
        } else if (item.iva === 'exenta') {
            totalExenta += subtotal;
        } else {
            // IVA 10%
            totalSinIva += subtotal;
        }
    });
    
    // Calcular IVA total
    var iva10 = Math.round(totalSinIva / 11);
    var iva5 = Math.round(total5 / 21);
    var totalConIva = totalSinIva + iva10 + total5 + iva5 + totalExenta;
    
    // Mostrar productos en la tabla
    carrito.forEach(function(item, index) {
        var subtotal = item.cantidad * item.precio;
        var ivaTexto = '';
        var subtotalMostrar = subtotal;
        
        if (item.iva === '5') {
            ivaTexto = '5%';
            // Calcular IVA 5% proporcional
            if (total5 > 0) {
                var proporcion = subtotal / total5;
                var ivaProporcional = Math.round(iva5 * proporcion);
                subtotalMostrar = subtotal + ivaProporcional;
            }
        } else if (item.iva === '10') {
            ivaTexto = '10%';
            // Calcular IVA 10% proporcional
            if (totalSinIva > 0) {
                var proporcion = subtotal / totalSinIva;
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
            '<td>' + item.precio.toLocaleString('es-PY') + '</td>' +
            '<td>' + ivaTexto + '</td>' +
            '<td>' + subtotalMostrar.toLocaleString('es-PY') + ' Gs</td>' +
            '<td><button type="button" class="btn-eliminar-item" onclick="eliminarDelCarrito(' + index + ')"><i class="fas fa-trash"></i></button></td>';
        carritoBody.appendChild(row);
    });
    
    document.getElementById('total_carrito').textContent = totalConIva.toLocaleString('es-PY') + ' Gs';
    
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

// Verificar si se puede confirmar venta
function verificarCarrito() {
    var formConfirmar = document.getElementById('form_confirmar_venta');
    if (clienteSeleccionado && carrito.length > 0) {
        formConfirmar.style.display = 'block';
    } else {
        formConfirmar.style.display = 'none';
    }
}

// Prevenir envío del formulario de agregar
document.getElementById('form_agregar_producto').addEventListener('submit', function(e) {
    e.preventDefault();
});

// Mostrar/ocultar campos de crédito
function actualizarCamposCredito() {
    var condicion = document.getElementById('select_condicion').value;
    var campoCuotas = document.getElementById('campo_cuotas');
    
    // Solo mostrar cuotas si la condición es "Crédito"
    if (condicion === 'Crédito') {
        campoCuotas.style.display = 'flex';
    } else {
        campoCuotas.style.display = 'none';
    }
    
    // Asegurar alineación correcta
    setTimeout(function() {
        var formGroups = document.querySelectorAll('.form-group-horizontal');
        formGroups.forEach(function(group) {
            if (group.style.display !== 'none') {
                group.style.display = 'flex';
                group.style.flexDirection = 'column';
                group.style.justifyContent = 'flex-end';
            }
        });
    }, 10);
}

document.getElementById('select_condicion').addEventListener('change', actualizarCamposCredito);

// Validar caja antes de confirmar venta
var cajaAbierta = <?php echo $caja_abierta ? 'true' : 'false'; ?>;
document.getElementById('form_venta_final').addEventListener('submit', function(e) {
    if (!cajaAbierta) {
        e.preventDefault();
        alert('ERROR: Debe abrir la caja antes de realizar una venta.\n\nPor favor, diríjase al módulo de Caja y ábrala primero.');
        window.location.href = '/repuestos/modulos/caja/caja.php';
        return false;
    }
    
    // Validar cuotas si es crédito
    var condicion = document.getElementById('select_condicion').value;
    var esCredito = (condicion === 'Crédito');
    
    if (esCredito) {
        var numeroCuotas = parseInt(document.getElementById('select_cuotas').value);
        if (numeroCuotas < 2 || numeroCuotas > 6) {
            e.preventDefault();
            alert('ERROR: El número de cuotas debe estar entre 2 y 6 meses.');
            return false;
        }
    }
});

// Inicializar
actualizarCarrito();
actualizarCamposCredito();
</script>

<?php include $base_path . 'includes/footer.php'; ?>
