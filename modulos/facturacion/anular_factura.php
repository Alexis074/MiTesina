<?php
date_default_timezone_set('America/Asuncion');
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
include $base_path . 'includes/auditoria.php';

requerirLogin();
requerirPermiso('facturacion', 'editar');

$mensaje = '';
$error = '';

// Obtener ID de factura
if (!isset($_GET['id'])) {
    header('Location: facturacion.php');
    exit();
}

$factura_id = (int)$_GET['id'];

// Obtener datos de la factura
$stmt = $pdo->prepare("SELECT * FROM cabecera_factura_ventas WHERE id = ?");
$stmt->execute(array($factura_id));
$factura = $stmt->fetch();

if (!$factura) {
    $error = 'Factura no encontrada.';
} elseif ($factura['anulada'] == 1) {
    $error = 'Esta factura ya está anulada.';
}

// Procesar anulación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $factura && $factura['anulada'] == 0) {
    $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : 'Sin motivo especificado';
    $usuario_id = obtenerUsuarioId();
    
    try {
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Anular factura
        $stmt = $pdo->prepare("UPDATE cabecera_factura_ventas 
                                SET anulada = 1, 
                                    fecha_anulacion = NOW(), 
                                    usuario_anulacion_id = ?,
                                    motivo_anulacion = ?
                                WHERE id = ?");
        $stmt->execute(array($usuario_id, $motivo, $factura_id));
        
        // Revertir stock (si se necesita)
        $stmt_detalle = $pdo->prepare("SELECT producto_id, cantidad FROM detalle_factura_ventas WHERE factura_id = ?");
        $stmt_detalle->execute(array($factura_id));
        $detalles = $stmt_detalle->fetchAll();
        
        foreach ($detalles as $detalle) {
            $stmt_stock = $pdo->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
            $stmt_stock->execute(array($detalle['cantidad'], $detalle['producto_id']));
        }
        
        // Registrar en auditoría
        registrarAuditoria('anular', 'facturacion', 
            'Factura #' . $factura['numero_factura'] . ' anulada. Motivo: ' . $motivo);
        
        $pdo->commit();
        $mensaje = 'Factura anulada correctamente. El stock ha sido revertido.';
        
        // Redirigir después de 2 segundos
        header('Refresh: 2; url=facturacion.php');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Error al anular la factura: ' . $e->getMessage();
    }
}

include $base_path . 'includes/header.php';
?>

<div class="container form-container">
    <h1>Anular Factura</h1>
    
    <?php if ($mensaje): ?>
        <div class="mensaje exito"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="mensaje error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($factura && $factura['anulada'] == 0): ?>
        <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h3>Información de la Factura</h3>
            <p><strong>Número:</strong> <?php echo htmlspecialchars($factura['numero_factura']); ?></p>
            <p><strong>Fecha:</strong> <?php echo htmlspecialchars($factura['fecha_hora']); ?></p>
            <p><strong>Monto Total:</strong> <?php echo number_format($factura['monto_total'], 0, ',', '.'); ?> Gs</p>
        </div>
        
        <form method="POST">
            <label>Motivo de Anulación:</label>
            <textarea name="motivo" rows="4" required placeholder="Ingrese el motivo de la anulación..."></textarea>
            
            <div class="form-actions">
                <button type="submit" class="btn-submit" style="background: #dc2626;" onclick="return confirm('¿Está seguro que desea anular esta factura? Esta acción revertirá el stock de productos.')">
                    Confirmar Anulación
                </button>
                <a href="facturacion.php" class="btn-cancelar">Cancelar</a>
            </div>
        </form>
    <?php elseif ($factura && $factura['anulada'] == 1): ?>
        <div class="mensaje error">
            <p>Esta factura ya está anulada.</p>
            <p><strong>Fecha de anulación:</strong> <?php echo htmlspecialchars($factura['fecha_anulacion']); ?></p>
            <?php if ($factura['motivo_anulacion']): ?>
                <p><strong>Motivo:</strong> <?php echo htmlspecialchars($factura['motivo_anulacion']); ?></p>
            <?php endif; ?>
        </div>
        <div class="form-actions">
            <a href="facturacion.php" class="btn-cancelar">Volver</a>
        </div>
    <?php endif; ?>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

