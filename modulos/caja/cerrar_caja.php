<?php
date_default_timezone_set('America/Asuncion');
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('caja', 'ver');
include $base_path . 'includes/auditoria.php';

$mensaje = "";

// Procesar cierre de caja si se recibió ID por GET
if (isset($_GET['id'])) {
    $caja_id = (int)$_GET['id'];
    
    try {
        // Consultar la caja
        $stmt = $pdo->prepare("SELECT * FROM caja WHERE id = ? AND estado = 'Abierta'");
        $stmt->execute([$caja_id]);
        $caja = $stmt->fetch();
        
        if($caja){
            // Calcular totales de ingresos y egresos
            // 1. Movimientos manuales
            $stmt_mov = $pdo->prepare("SELECT * FROM caja_movimientos WHERE caja_id = ?");
            $stmt_mov->execute([$caja_id]);
            $movimientos = $stmt_mov->fetchAll();

            $total_ingresos = 0;
            $total_egresos = 0;
            foreach($movimientos as $m){
                if($m['tipo']=='Ingreso') {
                    $total_ingresos += (float)$m['monto'];
                } else {
                    $total_egresos += (float)$m['monto'];
                }
            }
            
            // 2. Ventas (ingresos) - excluyendo anuladas
            $fecha_apertura = $caja['fecha'];
            $stmtVentas = $pdo->prepare("SELECT SUM(monto_total) as total_ventas 
                                         FROM cabecera_factura_ventas 
                                         WHERE fecha_hora >= ? AND (anulada = 0 OR anulada IS NULL)");
            $stmtVentas->execute([$fecha_apertura]);
            $ventas_data = $stmtVentas->fetch();
            $total_ventas = $ventas_data['total_ventas'] ? (float)$ventas_data['total_ventas'] : 0;
            $total_ingresos += $total_ventas;
            
            // 3. Compras (egresos) - de la tabla compras
            $stmtCompras = $pdo->prepare("SELECT SUM(total) as total_compras 
                                          FROM compras 
                                          WHERE fecha >= ?");
            $stmtCompras->execute([$fecha_apertura]);
            $compras_data = $stmtCompras->fetch();
            $total_compras = $compras_data['total_compras'] ? (float)$compras_data['total_compras'] : 0;
            $total_egresos += $total_compras;
            
            // 4. Facturas de compras (egresos) - si existen
            try {
                $stmtFacturasCompras = $pdo->prepare("SELECT SUM(monto_total) as total_facturas_compras 
                                                      FROM cabecera_factura_compras 
                                                      WHERE fecha_hora >= ?");
                $stmtFacturasCompras->execute([$fecha_apertura]);
                $facturas_compras_data = $stmtFacturasCompras->fetch();
                $total_facturas_compras = $facturas_compras_data['total_facturas_compras'] ? (float)$facturas_compras_data['total_facturas_compras'] : 0;
                $total_egresos += $total_facturas_compras;
            } catch (Exception $e) {
                // Tabla no existe, continuar
            }

            $monto_final = (float)$caja['monto_inicial'] + $total_ingresos - $total_egresos;
            
            // Calcular saldo
            $saldo = $monto_final - (float)$caja['monto_inicial'];

            // Actualizar caja como cerrada
            try {
                // Intentar con fecha_cierre
                $stmt_update = $pdo->prepare("UPDATE caja SET monto_final=?, estado='Cerrada', fecha_cierre=NOW() WHERE id=?");
                $stmt_update->execute([$monto_final, $caja_id]);
            } catch (Exception $e) {
                // Si fecha_cierre no existe, usar solo monto_final y estado
                $stmt_update = $pdo->prepare("UPDATE caja SET monto_final=?, estado='Cerrada' WHERE id=?");
                $stmt_update->execute([$monto_final, $caja_id]);
            }
            
            // Registrar en auditoría
            $detalle = "Caja cerrada - Monto inicial: " . number_format($caja['monto_inicial'], 0, ',', '.') . " Gs | Monto final: " . number_format($monto_final, 0, ',', '.') . " Gs | Saldo: " . number_format($saldo, 0, ',', '.') . " Gs (ID Caja: " . $caja_id . ")";
            registrarAuditoria('cerrar', 'caja', $detalle);
            
            // Redirigir después de cerrar
            header("Location: caja.php");
            exit;
        } else {
            $mensaje = "No se encontró una caja abierta con ese ID.";
        }
    } catch (PDOException $e) {
        $mensaje = "Error al cerrar la caja: " . $e->getMessage();
        error_log('Error en cerrar_caja.php: ' . $e->getMessage());
    }
} else {
    // Si no hay ID, consultar la caja abierta más reciente
    $stmt = $pdo->query("SELECT * FROM caja WHERE estado='Abierta' ORDER BY id DESC LIMIT 1");
    $caja = $stmt->fetch();
    
    if(!$caja){
        $mensaje = "No hay caja abierta para cerrar.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre de Caja - Repuestos Doble A</title>
    <link rel="stylesheet" href="/repuestos/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<?php include $base_path . 'includes/header.php'; ?>

<div class="container form-container">
    <h1><i class="fas fa-lock"></i> Cierre de Caja</h1>
    
    <?php if($mensaje): ?>
        <div class="mensaje <?= strpos($mensaje,'Error')===false?'exito':'error' ?>">
            <i class="fas fa-<?= strpos($mensaje,'Error')===false?'check-circle':'exclamation-circle' ?>"></i> 
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>
    
    <div class="form-actions">
        <a href="caja.php" class="btn-submit"><i class="fas fa-arrow-left"></i> Volver a Caja</a>
    </div>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>
