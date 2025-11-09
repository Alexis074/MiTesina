<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('auditoria', 'ver');
include $base_path . 'includes/header.php';

// Verificar si existe la tabla de auditoría, si no, intentar crearla
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM auditoria");
} catch (Exception $e) {
    // Tabla no existe, intentar crearla
    include $base_path . 'includes/auditoria.php';
    crearTablaAuditoria();
}

// Obtener registros de auditoría
$stmt = $pdo->query("SELECT * FROM auditoria ORDER BY fecha_hora DESC LIMIT 500");
$registros = $stmt->fetchAll();
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
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($registros && count($registros) > 0) {
            foreach ($registros as $registro) {
                echo '<tr>';
                echo '<td>'.htmlspecialchars($registro['id']).'</td>';
                echo '<td>'.htmlspecialchars($registro['fecha_hora']).'</td>';
                echo '<td>'.htmlspecialchars($registro['nombre_usuario']).'</td>';
                
                // Colorear según acción
                $accion_class = '';
                $accion_text = htmlspecialchars($registro['accion']);
                switch(strtolower($registro['accion'])) {
                    case 'crear':
                        $accion_class = 'style="background: #10b981; color: white; padding: 4px 8px; border-radius: 4px;"';
                        break;
                    case 'editar':
                        $accion_class = 'style="background: #2563eb; color: white; padding: 4px 8px; border-radius: 4px;"';
                        break;
                    case 'eliminar':
                    case 'anular':
                        $accion_class = 'style="background: #dc2626; color: white; padding: 4px 8px; border-radius: 4px;"';
                        break;
                    case 'login':
                        $accion_class = 'style="background: #059669; color: white; padding: 4px 8px; border-radius: 4px;"';
                        break;
                    case 'logout':
                        $accion_class = 'style="background: #7c3aed; color: white; padding: 4px 8px; border-radius: 4px;"';
                        break;
                    case 'abrir':
                        $accion_class = 'style="background: #06b6d4; color: white; padding: 4px 8px; border-radius: 4px;"'; // Cyan/Turquesa
                        break;
                    case 'cerrar':
                        $accion_class = 'style="background:rgb(128, 138, 122); color: white; padding: 4px 8px; border-radius: 4px;"'; // Naranja/Ámbar
                        break;
                    case 'pagar':
                        $accion_class = 'style="background: #eab308; color: white; padding: 4px 8px; border-radius: 4px;"'; // Amarillo/Dorado
                        break;
                }
                echo '<td><span '.$accion_class.'>'.$accion_text.'</span></td>';
                echo '<td>'.htmlspecialchars($registro['modulo']).'</td>';
                echo '<td style="text-align: left;">'.htmlspecialchars($registro['detalle']).'</td>';
                echo '<td>'.htmlspecialchars($registro['ip_address']).'</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7">No hay registros de auditoría aún.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

