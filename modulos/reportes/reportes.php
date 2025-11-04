<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';
?>

<div class="container tabla-responsive">
    <h1>Reportes</h1>
    <p>Generación de reportes del sistema</p>

    <div class="form-actions-right">
        <a href="#" class="btn-submit"><i class="fas fa-file-alt"></i> Generar Reporte</a>
    </div>

    <br><br>
    <div class="form-container">
        <h2>Seleccionar Tipo de Reporte</h2>
        <form>
            <label>Tipo de Reporte:</label>
            <select>
                <option value="">-- Seleccione un reporte --</option>
                <option value="ventas">Reporte de Ventas</option>
                <option value="compras">Reporte de Compras</option>
                <option value="stock">Reporte de Stock</option>
                <option value="clientes">Reporte de Clientes</option>
                <option value="proveedores">Reporte de Proveedores</option>
            </select>

            <label>Fecha Desde:</label>
            <input type="date" name="fecha_desde">

            <label>Fecha Hasta:</label>
            <input type="date" name="fecha_hasta">

            <button type="submit" class="btn-submit">Generar Reporte</button>
        </form>
    </div>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

