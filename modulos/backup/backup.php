<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('backup', 'ver');
include $base_path . 'includes/header.php';

$mensaje = "";
$error = "";

// Directorio de backups
$backup_dir = $base_path . 'backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Crear backup
if (isset($_POST['crear_backup'])) {
    try {
        $fecha = date('Y-m-d_H-i-s');
        $nombre_archivo = 'backup_' . $fecha . '.sql';
        $ruta_archivo = $backup_dir . $nombre_archivo;
        
        // Obtener configuración de base de datos
        $db_config = parse_ini_file($base_path . 'includes/conexion.php');
        // Alternativa: obtener desde variables de conexión
        $db_name = 'db_repuestos'; // Ajustar según tu base de datos
        
        // Crear archivo SQL
        $sql_content = "-- Backup de Base de Datos - Repuestos Doble A\n";
        $sql_content .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n\n";
        $sql_content .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql_content .= "SET time_zone = \"+00:00\";\n\n";
        
        // Obtener todas las tablas
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach($tables as $table) {
            $sql_content .= "\n-- Estructura de tabla `$table`\n";
            
            // Crear tabla
            $create_table = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
            $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql_content .= $create_table['Create Table'] . ";\n\n";
            
            // Datos de la tabla
            $sql_content .= "-- Volcado de datos de la tabla `$table`\n";
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $sql_content .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";
                
                $values = array();
                foreach($rows as $row) {
                    $row_values = array();
                    foreach($row as $value) {
                        if ($value === null) {
                            $row_values[] = 'NULL';
                        } else {
                            $row_values[] = $pdo->quote($value);
                        }
                    }
                    $values[] = "(" . implode(", ", $row_values) . ")";
                }
                $sql_content .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        // Guardar archivo
        file_put_contents($ruta_archivo, $sql_content);
        
        // Crear archivo de información
        $info_content = json_encode([
            'nombre' => $nombre_archivo,
            'fecha' => date('Y-m-d H:i:s'),
            'tamaño' => filesize($ruta_archivo),
            'tablas' => count($tables),
            'tipo' => 'base_datos'
        ], JSON_PRETTY_PRINT);
        file_put_contents($backup_dir . 'info_' . $fecha . '.json', $info_content);
        
        // Registrar en auditoría
        include $base_path . 'includes/auditoria.php';
        if (function_exists('registrarAuditoria')) {
            registrarAuditoria('crear', 'backup', 'Backup creado: ' . $nombre_archivo);
        }
        
        $mensaje = "Backup creado correctamente: " . $nombre_archivo;
        
        // Redirigir para evitar reenvío del formulario
        header("Location: backup.php?mensaje=" . urlencode($mensaje));
        exit();
    } catch (Exception $e) {
        $error = "Error al crear backup: " . $e->getMessage();
    }
}

// Si hay mensaje en GET (después de redirección)
if (isset($_GET['mensaje'])) {
    $mensaje = urldecode($_GET['mensaje']);
}

// Eliminar backup
if (isset($_GET['eliminar']) && isset($_GET['archivo']) && $_GET['eliminar'] == '1') {
    $archivo = basename($_GET['archivo']);
    
    // Validar que el archivo tenga el formato correcto
    if (preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $archivo)) {
        $ruta_archivo = $backup_dir . $archivo;
        
        if (file_exists($ruta_archivo) && is_file($ruta_archivo)) {
            // Verificar que esté dentro del directorio de backups (prevenir directory traversal)
            $ruta_real = realpath($ruta_archivo);
            $backup_dir_real = realpath($backup_dir);
            
            if ($ruta_real && $backup_dir_real && strpos($ruta_real, $backup_dir_real) === 0) {
                if (unlink($ruta_archivo)) {
                    // Eliminar archivo de info si existe
                    $info_archivo = $backup_dir . 'info_' . str_replace(['backup_', '.sql'], '', $archivo) . '.json';
                    if (file_exists($info_archivo)) {
                        unlink($info_archivo);
                    }
                    
                    // Registrar en auditoría
                    include $base_path . 'includes/auditoria.php';
                    if (function_exists('registrarAuditoria')) {
                        registrarAuditoria('eliminar', 'backup', 'Backup eliminado: ' . $archivo);
                    }
                    
                    $mensaje = "Backup eliminado correctamente.";
                    // Redirigir para evitar reenvío del formulario
                    header("Location: backup.php?mensaje=" . urlencode($mensaje));
                    exit();
                } else {
                    $error = "Error: No se pudo eliminar el archivo.";
                }
            } else {
                $error = "Error: Ruta de archivo no válida.";
            }
        } else {
            $error = "Error: El archivo no existe.";
        }
    } else {
        $error = "Error: Nombre de archivo no válido.";
    }
}

// Obtener lista de backups
$backups = array();
if (is_dir($backup_dir)) {
    $archivos = scandir($backup_dir);
    foreach($archivos as $archivo) {
        if (strpos($archivo, 'backup_') === 0 && strpos($archivo, '.sql') !== false) {
            $ruta_completa = $backup_dir . $archivo;
            $info_archivo = $backup_dir . 'info_' . str_replace(['backup_', '.sql'], '', $archivo) . '.json';
            
            $info = array(
                'nombre' => $archivo,
                'fecha' => date('Y-m-d H:i:s', filemtime($ruta_completa)),
                'tamaño' => filesize($ruta_completa),
                'ruta' => $ruta_completa
            );
            
            if (file_exists($info_archivo)) {
                $info_json = json_decode(file_get_contents($info_archivo), true);
                if ($info_json) {
                    $info = array_merge($info, $info_json);
                }
            }
            
            $backups[] = $info;
        }
    }
    
    // Ordenar por fecha descendente
    usort($backups, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });
}

function formato_tamaño($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

<div class="container tabla-responsive">
    <h1><i class="fas fa-database"></i> Backup y Respaldo</h1>
    
    <?php if(isset($mensaje) && $mensaje != ""): ?>
        <div class="mensaje exito"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    
    <?php if(isset($error) && $error != ""): ?>
        <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- Formulario para crear backup -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h2><i class="fas fa-plus-circle"></i> Crear Nuevo Backup</h2>
        <form method="POST">
            <p style="margin-bottom: 15px;">El backup incluirá toda la base de datos en formato SQL para fácil importación posterior.</p>
            <button type="submit" name="crear_backup" class="btn-submit">
                <i class="fas fa-download"></i> Crear Backup de Base de Datos
            </button>
        </form>
    </div>
    
    <!-- Lista de backups -->
    <h2><i class="fas fa-list"></i> Backups Disponibles</h2>
    
    <?php if(empty($backups)): ?>
        <div class="mensaje" style="background: #f3f4f6; padding: 20px; border-radius: 8px; text-align: center;">
            <p>No hay backups disponibles. Crea uno para comenzar.</p>
        </div>
    <?php else: ?>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Nombre del Archivo</th>
                    <th>Fecha de Creación</th>
                    <th>Tamaño</th>
                    <th>Tablas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($backups as $backup): ?>
                    <tr>
                        <td>
                            <i class="fas fa-file-code"></i> 
                            <?= htmlspecialchars($backup['nombre']) ?>
                        </td>
                        <td><?= date('d/m/Y H:i:s', strtotime($backup['fecha'])) ?></td>
                        <td><?= formato_tamaño($backup['tamaño']) ?></td>
                        <td>
                            <?= isset($backup['tablas']) ? $backup['tablas'] : 'N/A' ?>
                        </td>
                        <td class="acciones">
                            <a href="/repuestos/backups/<?= htmlspecialchars($backup['nombre']) ?>" 
                               class="btn btn-edit" 
                               download
                               data-tooltip="Descargar">
                                <i class="fas fa-download"></i>
                            </a>
                            <a href="?eliminar=1&archivo=<?= urlencode($backup['nombre']) ?>" 
                               class="btn btn-delete" 
                               onclick="return confirm('¿Está seguro de eliminar este backup?')"
                               data-tooltip="Eliminar">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <!-- Información adicional -->
    <div style="margin-top: 30px; padding: 20px; background: #f3f4f6; border-radius: 8px;">
        <h3><i class="fas fa-info-circle"></i> Información</h3>
        <ul style="line-height: 1.8;">
            <li>Los backups se guardan en formato <strong>.sql</strong> para fácil importación en phpMyAdmin o línea de comandos.</li>
            <li>Cada backup incluye la estructura completa de todas las tablas y sus datos.</li>
            <li>Puedes descargar los backups para guardarlos en un lugar seguro.</li>
            <li>Se recomienda crear backups regularmente antes de realizar cambios importantes.</li>
            <li>Para restaurar un backup, importa el archivo .sql en phpMyAdmin o usando: <code>mysql -u usuario -p db_repuestos < backup.sql</code></li>
        </ul>
    </div>
</div>

<?php include $base_path . 'includes/footer.php'; ?>
