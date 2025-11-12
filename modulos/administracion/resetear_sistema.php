<?php
date_default_timezone_set('America/Asuncion');
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('usuarios', 'crear'); // Solo administradores pueden resetear
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resetear Sistema - Repuestos Doble A</title>
    <link rel="stylesheet" href="/repuestos/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<?php include $base_path . 'includes/header.php'; ?>

<?php
$mensaje = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar_reseteo'])) {
    $tipo_reseteo = isset($_POST['tipo_reseteo']) ? $_POST['tipo_reseteo'] : '';
    
    try {
        $pdo->beginTransaction();
        
        if ($tipo_reseteo == 'cajas_cerradas') {
            // Opción 1: Solo limpiar cajas cerradas (mantiene ventas y compras)
            $pdo->exec("DELETE FROM caja_movimientos WHERE caja_id IN (SELECT id FROM caja WHERE estado='Cerrada')");
            $pdo->exec("DELETE FROM caja WHERE estado='Cerrada'");
            $mensaje = "Cajas cerradas eliminadas correctamente. Las ventas y compras se mantienen.";
            
        } elseif ($tipo_reseteo == 'todo_excepto_datos') {
            // Opción 2: Limpiar cajas, ventas, compras, pero mantener clientes, productos, proveedores
            $pdo->exec("DELETE FROM caja_movimientos");
            $pdo->exec("DELETE FROM caja");
            $pdo->exec("DELETE FROM detalle_factura_ventas");
            $pdo->exec("DELETE FROM cabecera_factura_ventas");
            $pdo->exec("DELETE FROM detalle_factura_compras");
            $pdo->exec("DELETE FROM cabecera_factura_compras");
            $pdo->exec("DELETE FROM compras");
            $pdo->exec("DELETE FROM auditoria");
            $mensaje = "Datos de caja, ventas y compras eliminados. Clientes, productos y proveedores se mantienen.";
            
        } elseif ($tipo_reseteo == 'completo') {
            // Opción 3: Reset completo (ADVERTENCIA: Esto elimina TODO excepto usuarios)
            $pdo->exec("DELETE FROM caja_movimientos");
            $pdo->exec("DELETE FROM caja");
            $pdo->exec("DELETE FROM detalle_factura_ventas");
            $pdo->exec("DELETE FROM cabecera_factura_ventas");
            $pdo->exec("DELETE FROM detalle_factura_compras");
            $pdo->exec("DELETE FROM cabecera_factura_compras");
            $pdo->exec("DELETE FROM compras");
            $pdo->exec("DELETE FROM auditoria");
            $pdo->exec("DELETE FROM clientes");
            $pdo->exec("DELETE FROM productos");
            $pdo->exec("DELETE FROM proveedores");
            $pdo->exec("DELETE FROM stock");
            $mensaje = "Sistema reseteado completamente. Solo se mantienen los usuarios del sistema.";
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al resetear: " . $e->getMessage();
    }
}

include $base_path . 'includes/header.php';
?>

<div class="container tabla-responsive">
    <h1><i class="fas fa-exclamation-triangle"></i> Resetear Sistema</h1>
    
    <?php if($mensaje): ?>
        <div class="mensaje-exito" style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="mensaje-error" style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <i class="fas fa-times-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="form-container" style="max-width: 800px;">
        <div style="background: #fee2e2; border-left: 4px solid #dc2626; padding: 20px; margin-bottom: 30px; border-radius: 5px;">
            <h3 style="margin-top: 0; color: #991b1b;"><i class="fas fa-exclamation-triangle"></i> Advertencia Importante</h3>
            <p style="margin-bottom: 10px; color: #7f1d1d;">
                <strong>⚠️ Atención: Las acciones de reseteo son IRREVERSIBLES</strong>
            </p>
            <ul style="color: #7f1d1d; margin-left: 20px;">
                <li>Si reseteas las <strong>cajas cerradas</strong>, perderás el historial de cajas pero mantendrás todas las ventas y compras.</li>
                <li>Si reseteas las <strong>transacciones</strong>, perderás todas las ventas, compras y facturas, pero mantendrás clientes, productos y proveedores.</li>
                <li>Si haces un <strong>reset completo</strong>, perderás TODOS los datos excepto los usuarios del sistema.</li>
                <li><strong>RECOMENDACIÓN:</strong> Crea un backup antes de realizar cualquier reseteo.</li>
            </ul>
            <p style="margin-top: 15px; color: #991b1b; font-weight: bold;">
                ⚠️ Estas acciones NO se pueden deshacer. Asegúrate de tener un backup antes de continuar.
            </p>
        </div>
        
        <form method="POST" onsubmit="return confirmarReseteo();">
            <h2 style="margin-top: 30px; margin-bottom: 20px;">Opciones de Reseteo</h2>
            
            <div style="background: white; border: 2px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 16px;">
                    <input type="radio" name="tipo_reseteo" value="cajas_cerradas" required style="margin-right: 10px;">
                    Opción 1: Limpiar solo cajas cerradas
                </label>
                <p style="margin-left: 30px; color: #6b7280; margin-bottom: 10px;">
                    Elimina todas las cajas cerradas y sus movimientos manuales. 
                    <strong>Mantiene:</strong> Ventas, compras, facturas, clientes, productos, proveedores.
                </p>
                <p style="margin-left: 30px; color: #dc2626; font-weight: bold;">
                    ⚠️ Esto permitirá empezar con una nueva caja desde cero.
                </p>
            </div>
            
            <div style="background: white; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 16px;">
                    <input type="radio" name="tipo_reseteo" value="todo_excepto_datos" required style="margin-right: 10px;">
                    Opción 2: Limpiar transacciones (mantener datos maestros)
                </label>
                <p style="margin-left: 30px; color: #6b7280; margin-bottom: 10px;">
                    Elimina todas las cajas, ventas, compras y facturas.
                    <strong>Mantiene:</strong> Clientes, productos, proveedores, usuarios.
                </p>
                <p style="margin-left: 30px; color: #dc2626; font-weight: bold;">
                    ⚠️ Se perderán todas las ventas y compras registradas.
                </p>
            </div>
            
            <div style="background: white; border: 2px solid #dc2626; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 16px; color: #dc2626;">
                    <input type="radio" name="tipo_reseteo" value="completo" required style="margin-right: 10px;">
                    Opción 3: Reset completo (PELIGROSO)
                </label>
                <p style="margin-left: 30px; color: #6b7280; margin-bottom: 10px;">
                    Elimina <strong>TODO</strong> excepto usuarios del sistema.
                    <strong>Se eliminará:</strong> Cajas, ventas, compras, facturas, clientes, productos, proveedores, stock, auditoría.
                </p>
                <p style="margin-left: 30px; color: #dc2626; font-weight: bold;">
                    ⚠️⚠️⚠️ ESTA ACCIÓN ES IRREVERSIBLE. SOLO SE MANTIENEN LOS USUARIOS.
                </p>
            </div>
            
            <div class="form-actions" style="margin-top: 30px; display: flex; gap: 10px; justify-content: flex-end; align-items: center;">
                <a href="reportes.php" class="btn-cancelar" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center; height: 42px; padding: 0 20px;">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" name="confirmar_reseteo" class="btn-submit" style="background: #dc2626; height: 42px; padding: 0 20px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fas fa-exclamation-triangle"></i> Confirmar Reseteo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmarReseteo() {
    var tipo = document.querySelector('input[name="tipo_reseteo"]:checked').value;
    var mensaje = "";
    
    if (tipo == 'cajas_cerradas') {
        mensaje = "¿Estás seguro de eliminar todas las cajas cerradas?\n\nEsto permitirá empezar con una nueva caja desde cero.\n\nLas ventas y compras se mantendrán.";
    } else if (tipo == 'todo_excepto_datos') {
        mensaje = "¿Estás SEGURO de eliminar todas las ventas, compras y facturas?\n\nSe perderán TODOS los registros de transacciones.\n\nSolo se mantendrán clientes, productos y proveedores.";
    } else if (tipo == 'completo') {
        mensaje = "⚠️ PELIGRO ⚠️\n\n¿Estás ABSOLUTAMENTE SEGURO de hacer un reset completo?\n\nEsto eliminará:\n- Todas las cajas\n- Todas las ventas y compras\n- Todas las facturas\n- Todos los clientes\n- Todos los productos\n- Todos los proveedores\n- Todo el stock\n- Toda la auditoría\n\nSOLO se mantendrán los usuarios.\n\nESTA ACCIÓN ES IRREVERSIBLE.\n\n¿Continuar?";
    }
    
    return confirm(mensaje);
}
</script>

<?php include $base_path . 'includes/footer.php'; ?>

