<?php
date_default_timezone_set('America/Asuncion');
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('productos', 'ver');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos - Repuestos Doble A</title>
    <link rel="stylesheet" href="/repuestos/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<?php include $base_path . 'includes/header.php'; ?>

<?php
$stmt = $pdo->query("SELECT * FROM productos ORDER BY id ASC");
$productos = $stmt->fetchAll();
?>

<div class="container tabla-responsive">
    <h1>Productos</h1>
    <div class="form-actions-right">
        <a href="agregar_producto.php" class="btn-submit">+ Agregar Producto</a>
        <a href="exportar_productos_pdf.php" target="_blank" class="btn-export">📄 Exportar PDF</a>
    </div>

    <br><br>
    <table class="crud-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Cilindrada</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Stock Min</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if($productos){
            foreach($productos as $fila){
                echo '<tr>';
                echo '<td>'.htmlspecialchars($fila['codigo']).'</td>';
                echo '<td>'.htmlspecialchars($fila['nombre']).'</td>';
                echo '<td>'.htmlspecialchars($fila['categoria']).'</td>';
                echo '<td>'.htmlspecialchars($fila['marca']).'</td>';
                echo '<td>'.htmlspecialchars($fila['modelo']).'</td>';
                echo '<td>'.htmlspecialchars($fila['cilindrada']).'</td>';
                echo '<td>'.number_format($fila['precio'],0,',','.').'</td>';
                echo '<td>'.htmlspecialchars($fila['stock']).'</td>';
                echo '<td>'.htmlspecialchars($fila['stock_min']).'</td>';
                echo '<td class="acciones">';
                echo '<a href="editar_producto.php?id='.htmlspecialchars($fila['id']).'" class="btn btn-edit" data-tooltip="Editar">
                        <i class="fas fa-pencil-alt"></i>
                      </a>';
                echo '<a href="eliminar_producto.php?id='.htmlspecialchars($fila['id']).'" class="btn btn-delete" data-tooltip="Eliminar" onclick="return confirm(\'¿Seguro que deseas eliminar este producto?\')">
                        <i class="fas fa-trash"></i>
                      </a>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="10">No hay productos registrados.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
