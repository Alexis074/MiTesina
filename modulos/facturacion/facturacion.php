<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('facturacion', 'ver');
include $base_path . 'includes/header.php';

// Obtener facturas de venta (incluyendo anuladas)
$stmt_ventas = $pdo->query("SELECT * FROM cabecera_factura_ventas ORDER BY fecha_hora DESC LIMIT 50");
$facturas_ventas = $stmt_ventas->fetchAll();

// Obtener facturas de compra (si existe la tabla)
$facturas_compras = [];
try {
    $stmt_compras = $pdo->query("SELECT * FROM cabecera_factura_compras ORDER BY fecha_hora DESC LIMIT 50");
    $facturas_compras = $stmt_compras->fetchAll();
} catch (Exception $e) {
    // Tabla no existe aún
}
?>

<div class="container tabla-responsive">
    <h1>Facturación</h1>
    <p>Gestión de facturas de venta y compra</p>

    <div class="form-actions-right">
        <a href="/repuestos/modulos/ventas/ventas.php" class="btn-submit"><i class="fas fa-plus"></i> Nueva Venta</a>
        <a href="/repuestos/modulos/compras/compras.php" class="btn-submit"><i class="fas fa-plus"></i> Nueva Compra</a>
    </div>

    <br><br>
    <h2>Facturas de Venta</h2>
    <table class="crud-table">
        <thead>
            <tr>
                <th>Número Factura</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Monto Total</th>
                <th>Condición</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if($facturas_ventas){
            foreach($facturas_ventas as $fila){
                // Obtener nombre del cliente
                $stmt_cliente = $pdo->prepare("SELECT nombre, apellido FROM clientes WHERE id = ?");
                $stmt_cliente->execute([$fila['cliente_id']]);
                $cliente = $stmt_cliente->fetch();
                $nombre_cliente = $cliente ? $cliente['nombre'] . ' ' . $cliente['apellido'] : 'N/A';

                $anulada = isset($fila['anulada']) && $fila['anulada'] == 1;
                $clase_fila = $anulada ? 'factura-anulada' : '';
                echo '<tr class="' . $clase_fila . '">';
                echo '<td>';
                if ($anulada) {
                    echo '<span style="text-decoration: line-through; opacity: 0.6;">' . htmlspecialchars($fila['numero_factura']) . '</span> ';
                    echo '<span style="background: #dc2626; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">ANULADA</span>';
                } else {
                    echo htmlspecialchars($fila['numero_factura']);
                }
                echo '</td>';
                echo '<td>'.htmlspecialchars($nombre_cliente).'</td>';
                echo '<td>'.htmlspecialchars($fila['fecha_hora']).'</td>';
                echo '<td>'.number_format($fila['monto_total'],0,',','.').'</td>';
                echo '<td>';
                if ($anulada) {
                    echo '<span style="text-decoration: line-through; opacity: 0.6;">' . htmlspecialchars($fila['condicion_venta']) . '</span>';
                } else {
                    echo htmlspecialchars($fila['condicion_venta']);
                }
                echo '</td>';
                echo '<td class="acciones">';
                echo '<a href="/repuestos/modulos/ventas/ver_factura.php?id='.htmlspecialchars($fila['id']).'" class="btn btn-edit" data-tooltip="Ver" target="_blank">
                        <i class="fas fa-eye"></i>
                        </a>';
                if (!$anulada) {
                    echo '<a href="/repuestos/modulos/ventas/imprimir_factura.php?id='.htmlspecialchars($fila['id']).'" class="btn btn-edit" data-tooltip="Imprimir" target="_blank">
                            <i class="fas fa-print"></i>
                            </a>';
                    echo '<a href="anular_factura.php?id='.htmlspecialchars($fila['id']).'" class="btn btn-delete" data-tooltip="Anular">
                            <i class="fas fa-ban"></i>
                            </a>';
                } else {
                    echo '<span style="color: #dc2626; font-size: 12px; padding: 5px;">Anulada</span>';
                }
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6">No hay facturas de venta registradas.</td></tr>';
        }
        ?>
        </tbody>
    </table>

    <?php if(!empty($facturas_compras)): ?>
    <br><br>
    <h2>Facturas de Compra</h2>
    <table class="crud-table">
        <thead>
            <tr>
                <th>Número Factura</th>
                <th>Proveedor</th>
                <th>Fecha</th>
                <th>Monto Total</th>
                <th>Condición</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        foreach($facturas_compras as $fila){
            echo '<tr>';
            echo '<td>'.htmlspecialchars($fila['numero_factura']).'</td>';
            echo '<td>'.htmlspecialchars($fila['proveedor_id']).'</td>';
            echo '<td>'.htmlspecialchars($fila['fecha_hora']).'</td>';
            echo '<td>'.number_format($fila['monto_total'],0,',','.').'</td>';
            echo '<td>'.htmlspecialchars($fila['condicion_compra']).'</td>';
            echo '<td class="acciones">';
            echo '<a href="#" class="btn btn-edit" data-tooltip="Ver">
                    <i class="fas fa-eye"></i>
                    </a>';
            echo '<a href="#" class="btn btn-delete" data-tooltip="Anular" onclick="return confirm(\'¿Seguro que deseas anular esta factura?\')">
                    <i class="fas fa-ban"></i>
                    </a>';
            echo '</td>';
            echo '</tr>';
        }
        ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

