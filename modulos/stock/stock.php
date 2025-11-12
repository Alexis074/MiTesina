<?php
date_default_timezone_set('America/Asuncion');
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('stock', 'ver');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Stock - Repuestos Doble A</title>
    <link rel="stylesheet" href="/repuestos/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<?php include $base_path . 'includes/header.php'; ?>

<?php
$stmt = $pdo->query("SELECT * FROM productos ORDER BY stock ASC");
$productos = $stmt->fetchAll();
?>

<div class="container tabla-responsive">
    <h1>Control de Stock</h1>

    <br><br>
    <table class="crud-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Stock</th>
                <th>Stock Mínimo</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if($productos){
            foreach($productos as $fila){
                $clase = ($fila['stock'] <= $fila['stock_min']) ? 'low-stock' : '';
                echo '<tr class="'.$clase.'">';
                echo '<td>'.htmlspecialchars($fila['codigo']).'</td>';
                echo '<td>'.htmlspecialchars($fila['nombre']).'</td>';
                echo '<td>'.htmlspecialchars($fila['categoria']).'</td>';
                echo '<td>'.htmlspecialchars($fila['stock']).'</td>';
                echo '<td>'.htmlspecialchars($fila['stock_min']).'</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5">No hay productos registrados.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
