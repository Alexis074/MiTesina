<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('clientes', 'ver');
include $base_path . 'includes/header.php';
?>

<div class="container tabla-responsive">
    <h1>Clientes</h1>
    <div class="form-actions-right">
        <a href="agregar_cliente.php" class="btn-submit">+ Agregar Cliente</a>
    </div>


    <br><br>
    <table class="crud-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>RUC</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>Email</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->query("SELECT * FROM clientes ORDER BY id ASC");
        $clientes = $stmt->fetchAll();
        if($clientes){
            foreach($clientes as $fila){
                echo '<tr>';
                echo '<td>'.htmlspecialchars($fila['id']).'</td>';
                echo '<td>'.htmlspecialchars($fila['nombre']).'</td>';
                echo '<td>'.htmlspecialchars($fila['apellido']).'</td>';
                echo '<td>'.htmlspecialchars($fila['ruc']).'</td>';
                echo '<td>'.htmlspecialchars($fila['telefono']).'</td>';
                echo '<td>'.htmlspecialchars($fila['direccion']).'</td>';
                echo '<td>'.htmlspecialchars($fila['email']).'</td>';
                echo '<td class="acciones">';
                echo '<a href="editar_cliente.php?id='.htmlspecialchars($fila['id']).'" class="btn btn-edit" data-tooltip="Editar">
                        <i class="fas fa-pencil-alt"></i>
                      </a>';
                echo '<a href="eliminar_cliente.php?id='.htmlspecialchars($fila['id']).'" class="btn btn-delete" data-tooltip="Eliminar" onclick="return confirm(\'Seguro que deseas eliminar este cliente?\')">
                        <i class="fas fa-trash"></i>
                      </a>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="8">No hay clientes registrados.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
