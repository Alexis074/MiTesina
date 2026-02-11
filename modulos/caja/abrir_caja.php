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

// Validar si ya hay caja abierta
$stmt = $pdo->query("SELECT * FROM caja WHERE estado='Abierta' ORDER BY id DESC LIMIT 1");
$caja_abierta = $stmt->fetch();

// Procesar formulario (ANTES de cualquier output)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !$caja_abierta) {
    $monto_inicial = isset($_POST['monto_inicial']) ? (float)$_POST['monto_inicial'] : 0;
    $created_at = date("Y-m-d H:i:s");
    $fecha = date("Y-m-d");
    
    try {
        $sql = "INSERT INTO caja (fecha, monto_inicial, estado, created_at) VALUES (:fecha, :monto_inicial, 'Abierta', :created_at)";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute(['fecha'=>$fecha, 'monto_inicial'=>$monto_inicial,'created_at'=>$created_at])){
            // Obtener ID de la caja recién creada
            $caja_id = $pdo->lastInsertId();
            
            // Registrar en auditoría
            $detalle = "Caja abierta con monto inicial: " . number_format($monto_inicial, 0, ',', '.') . " Gs (ID Caja: " . $caja_id . ")";
            registrarAuditoria('abrir', 'caja', $detalle);
            
            // Redirigir antes de cualquier output
            header("Location: caja.php");
            exit;
        } else {
            $mensaje = "Error al abrir caja.";
        }
    } catch (PDOException $e) {
        $mensaje = "Error al abrir caja: " . $e->getMessage();
        error_log('Error en abrir_caja.php: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Apertura de Caja - Repuestos Doble A</title>
    <link rel="stylesheet" href="/repuestos/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<?php include $base_path . 'includes/header.php'; ?>

<div class="container form-container">
    <h1><i class="fas fa-lock-open"></i> Apertura de Caja</h1>
    
    <?php if($mensaje): ?>
        <div class="mensaje <?= strpos($mensaje,'Error')===false?'exito':'error' ?>">
            <i class="fas fa-<?= strpos($mensaje,'Error')===false?'check-circle':'exclamation-circle' ?>"></i> 
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php if($caja_abierta): ?>
        <div class="mensaje exito">
            <i class="fas fa-info-circle"></i> Ya existe una caja abierta con monto inicial: 
            <strong><?= number_format($caja_abierta['monto_inicial'],0,',','.') ?> Gs</strong>
        </div>
        <div class="form-actions">
            <a href="caja.php" class="btn-submit"><i class="fas fa-cash-register"></i> Ir a Caja</a>
        </div>
    <?php else: ?>
        <form method="POST" class="form-container">
            <label for="monto_inicial"><i class="fas fa-money-bill-wave"></i> Monto Inicial (Gs):</label>
            <input type="number" id="monto_inicial" name="monto_inicial" step="0.01" min="0" required autofocus>
            
            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-lock-open"></i> Abrir Caja
                </button>
                <a href="caja.php" class="btn-cancelar">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>
