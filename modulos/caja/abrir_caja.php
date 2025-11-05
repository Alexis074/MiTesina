<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auditoria.php';
include $base_path . 'includes/header.php';

$mensaje = "";

// Validar si ya hay caja abierta
$stmt = $pdo->query("SELECT * FROM caja WHERE estado='Abierta' ORDER BY id DESC LIMIT 1");
$caja_abierta = $stmt->fetch();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$caja_abierta) {
    $monto_inicial = $_POST['monto_inicial'];
    $created_at = date("Y-m-d H:i:s");
    $sql = "INSERT INTO caja (fecha, monto_inicial, estado, created_at) VALUES (CURDATE(), :monto_inicial, 'Abierta', :created_at)";
    $stmt = $pdo->prepare($sql);
    if($stmt->execute(['monto_inicial'=>$monto_inicial,'created_at'=>$created_at])){
        // Obtener ID de la caja recién creada
        $caja_id = $pdo->lastInsertId();
        
        // Registrar en auditoría
        $detalle = "Caja abierta con monto inicial: " . number_format($monto_inicial, 0, ',', '.') . " Gs (ID Caja: " . $caja_id . ")";
        registrarAuditoria('abrir', 'caja', $detalle);
        
        $mensaje = "Caja abierta correctamente.";
        // Refrescar para mostrar la caja abierta
        header("Location: caja.php");
        exit;
    } else {
        $mensaje = "Error al abrir caja.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Apertura de Caja - Repuestos Doble A</title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
.container { max-width:600px; margin:80px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
h1 { text-align:center; margin-bottom:20px; }
label { display:block; margin:10px 0 5px; }
input { width:100%; padding:8px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px; font-size:16px; }
button { width:100%; padding:10px; background:#2563eb; color:white; border:none; border-radius:4px; font-size:16px; cursor:pointer; }
button:hover { background:#1e40af; }
.mensaje { padding:10px; margin-bottom:15px; border-radius:4px; text-align:center; }
.mensaje.exito { background-color:#d1fae5; color:#065f46; }
.mensaje.error { background-color:#fee2e2; color:#991b1b; }
.btn { padding:5px 10px; border-radius:4px; text-decoration:none; font-size:14px; background:#10b981; color:white; }
.btn:hover { background:#059669; }
</style>
</head>
<body>

<div class="container">
<h1>Apertura de Caja</h1>

<?php if($mensaje): ?>
<div class="mensaje <?= strpos($mensaje,'Error')===false?'exito':'error' ?>"><?= $mensaje ?></div>
<?php endif; ?>

<?php if($caja_abierta): ?>
<p>Ya existe una caja abierta con monto inicial: <strong><?= number_format($caja_abierta['monto_inicial'],2,',','.') ?> Gs</strong></p>
<a href="caja.php" class="btn">Ir a Caja</a>
<?php else: ?>
<form method="POST">
    <label>Monto Inicial:</label>
    <input type="number" name="monto_inicial" step="0.01" required>
    <button type="submit">Abrir Caja</button>
</form>
<?php endif; ?>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>
