<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('backup', 'ver');
include $base_path . 'includes/header.php';
?>

<div class="container tabla-responsive">
    <h1>Backup del Sistema</h1>
    <p>Gestión de respaldos de la base de datos</p>

    <div class="form-actions-right">
        <a href="#" class="btn-submit"><i class="fas fa-download"></i> Crear Backup</a>
    </div>

    <br><br>
    <table class="crud-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha de Backup</th>
                <th>Archivo</th>
                <th>Tamaño</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="5">Sistema de backup en desarrollo. Aquí se mostrarán los backups realizados.</td>
            </tr>
        </tbody>
    </table>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

