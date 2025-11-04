<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('auditoria', 'ver');
include $base_path . 'includes/header.php';
?>

<div class="container tabla-responsive">
    <h1>Auditoría</h1>
    <p>Registro de todos los movimientos del sistema</p>

    <br><br>
    <table class="crud-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha y Hora</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Módulo</th>
                <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="6">Sistema de auditoría en desarrollo. Aquí se mostrarán todos los movimientos del sistema.</td>
            </tr>
        </tbody>
    </table>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

