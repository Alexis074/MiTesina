<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';

$mensaje = "";

// Consultar la caja abierta más reciente
$stmt = $pdo->query("SELECT * FROM caja WHERE estado='Abierta' ORDER BY id DESC LIMIT 1");
$caja = $stmt->fetch();

if(!$caja){
    $mensaje = "No hay caja abierta para cerrar.";
} else {
    // Calcular totales de ingresos y egresos
    $stmt = $pdo->prepare("SELECT * FROM caja_movimientos WHERE caja_id=:caja_id");
    $stmt->execute(['caja_id'=>$caja['id']]);
    $movimientos = $stmt->fetchAll();

    $total_ingresos = 0;
    $total_egresos = 0;
    foreach($movimientos as $m){
        if($m['tipo']=='Ingreso') $total_ingresos += $m['monto'];
        else $total_egresos += $m['monto'];
    }

    $monto_final = $caja['monto_inicial'] + $total_ingresos - $total_egresos;

    // Actualizar caja como cerrada
    $stmt = $pdo->prepare("UPDATE caja SET monto_final=:monto_final, estado='Cerrada' WHERE id=:id");
    if($stmt->execute(['monto_final'=>$monto_final,'id'=>$caja['id']])){
        $mensaje = "Caja cerrada correctamente. Monto final: " . number_format($monto_final,2,',','.') . " Gs";
    } else {
        $mensaje = "Error al cerrar la caja.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cierre de Caja - Repuestos Doble A</title>
<link rel="stylesheet" href="/repuestos/style.css">
<style>
.container { max-width:600px; margin:80px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); text-align:center; }
h1 { margin-bottom:20px; }
.mensaje { padding:10px; margin-bottom:15px; border-radius:4px; text-align:center; font-size:16px; }
.mensaje.exito { background-color:#d1fae5; color:#065f46; }
.mensaje.error { background-color:#fee2e2; color:#991b1b; }
.btn { padding:10px 20px; border-radius:4px; text-decoration:none; font-size:16px; background:#10b981; color:white; }
.btn:hover { background:#059669; }
</style>
</head>
<body>

<div class="container">
<h1>Cierre de Caja</h1>

<?php if($mensaje): ?>
<div class="mensaje <?= strpos($mensaje,'Error')===false?'exito':'error' ?>"><?= $mensaje ?></div>
<?php endif; ?>

<a href="caja.php" class="btn">Volver a Caja</a>

</div>

<?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>
