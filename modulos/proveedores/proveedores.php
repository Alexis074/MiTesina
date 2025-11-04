<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';
?>

<div class="container tabla-responsive">
    <h1>Proveedores</h1>
    <div class="form-actions-right">
        <a href="agregar_proveedor.php" class="btn-submit">+ Agregar Proveedor</a>
    </div>


    <br><br>
    <table class="crud-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Empresa</th>
                <th>Contacto</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Dirección</th>
                <th>Fecha de Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->query("SELECT * FROM proveedores ORDER BY id ASC");
        $proveedores = $stmt->fetchAll();
        if($proveedores){
            foreach($proveedores as $fila){
                echo '<tr>';
                echo '<td>'.htmlspecialchars($fila['id']).'</td>';
                echo '<td>'.htmlspecialchars($fila['empresa']).'</td>';
                echo '<td>'.htmlspecialchars($fila['contacto']).'</td>';
                echo '<td>'.htmlspecialchars($fila['telefono']).'</td>';
                echo '<td>'.htmlspecialchars($fila['email']).'</td>';
                echo '<td>'.htmlspecialchars($fila['direccion']).'</td>';
                echo '<td>'.htmlspecialchars($fila['created_at']).'</td>';
                echo '<td class="acciones">';
                echo '<a href="editar_proveedor.php?id='.htmlspecialchars($fila['id']).'" class="btn btn-edit" data-tooltip="Editar">
                        <i class="fas fa-pencil-alt"></i>
                      </a>';
                echo '<a href="eliminar_proveedor.php?id='.htmlspecialchars($fila['id']).'" class="btn btn-delete" data-tooltip="Eliminar" onclick="return confirm(\'¿Seguro que deseas eliminar este proveedor?\')">
                        <i class="fas fa-trash"></i>
                      </a>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="8">No hay proveedores registrados.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
