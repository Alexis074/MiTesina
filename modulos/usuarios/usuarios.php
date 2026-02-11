<?php
date_default_timezone_set('America/Asuncion');
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('usuarios', 'ver');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios - Repuestos Doble A</title>
    <link rel="stylesheet" href="/repuestos/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<?php include $base_path . 'includes/header.php'; ?>

<?php
// Obtener usuarios
$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY id ASC");
$usuarios = $stmt->fetchAll();
?>

<div class="container tabla-responsive">
    <h1>Usuarios</h1>
    <div class="form-actions-right">
        <?php if (tienePermiso('usuarios', 'crear')): ?>
            <a href="agregar_usuario.php" class="btn-submit">+ Agregar Usuario</a>
        <?php endif; ?>
    </div>

    <br><br>
    <table class="crud-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Nombre</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if($usuarios){
            foreach($usuarios as $fila){
                echo '<tr>';
                echo '<td>'.htmlspecialchars($fila['id']).'</td>';
                echo '<td>'.htmlspecialchars($fila['usuario']).'</td>';
                echo '<td>'.htmlspecialchars($fila['nombre']).'</td>';
                echo '<td>'.htmlspecialchars($fila['rol']).'</td>';
                echo '<td>'.($fila['activo'] == 1 ? 'Activo' : 'Inactivo').'</td>';
                echo '<td class="acciones">';
                if (tienePermiso('usuarios', 'editar')) {
                    echo '<a href="editar_usuario.php?id='.htmlspecialchars($fila['id']).'" class="btn btn-edit" data-tooltip="Editar">
                            <i class="fas fa-pencil-alt"></i>
                            </a>';
                }
                if (tienePermiso('usuarios', 'eliminar') && $fila['id'] != obtenerUsuarioId()) {
                    echo '<a href="eliminar_usuario.php?id='.htmlspecialchars($fila['id']).'" class="btn btn-delete" data-tooltip="Eliminar" onclick="return confirm(\'¿Seguro que deseas eliminar este usuario?\')">
                            <i class="fas fa-trash"></i>
                            </a>';
                }
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6">No hay usuarios registrados.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

