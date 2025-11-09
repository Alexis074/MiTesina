<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
include $base_path . 'includes/auditoria.php';

// Verificar permisos
requerirPermiso('usuarios', 'editar');

$mensaje = '';
$error = '';

// Obtener ID del usuario
if (!isset($_GET['id'])) {
    header('Location: usuarios.php');
    exit();
}

$id = $_GET['id'];

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute(array($id));
$usuario_actual = $stmt->fetch();

if (!$usuario_actual) {
    $error = 'Usuario no encontrado.';
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $usuario_actual) {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $rol = isset($_POST['rol']) ? $_POST['rol'] : 'Vendedor';
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if (empty($usuario) || empty($nombre)) {
        $error = 'Por favor, complete todos los campos obligatorios.';
    } else {
        // Verificar si el usuario ya existe (excepto el actual)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
        $stmt->execute(array($usuario, $id));
        if ($stmt->fetch()) {
            $error = 'El usuario ya existe.';
        } else {
            // Obtener la contraseña actual si no se proporciona una nueva
            $password_hash = null;
            if (!empty($password)) {
                // Si se proporcionó una nueva contraseña, actualizarla
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
            } else {
                // Si no se proporciona contraseña, obtener la actual de la base de datos
                $stmt_password = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
                $stmt_password->execute(array($id));
                $usuario_db = $stmt_password->fetch();
                if ($usuario_db) {
                    $password_hash = $usuario_db['password']; // Mantener la contraseña actual
                }
            }
            
            // Actualizar usuario (siempre incluir password_hash, ya sea nueva o actual)
            if ($password_hash) {
                $stmt = $pdo->prepare("UPDATE usuarios SET usuario = ?, password = ?, nombre = ?, rol = ?, activo = ? WHERE id = ?");
                if ($stmt->execute(array($usuario, $password_hash, $nombre, $rol, $activo, $id))) {
                    // Registrar en auditoría
                    if (function_exists('registrarAuditoria')) {
                        $cambio_password = !empty($password) ? ' (contraseña cambiada)' : '';
                        registrarAuditoria('editar', 'usuarios', 'Usuario ' . $usuario . ' actualizado. Rol: ' . $rol . ', Activo: ' . ($activo ? 'Sí' : 'No') . $cambio_password);
                    }
                    
                    $mensaje = 'Usuario actualizado correctamente.';
                    // Actualizar datos en memoria
                    $usuario_actual['usuario'] = $usuario;
                    $usuario_actual['nombre'] = $nombre;
                    $usuario_actual['rol'] = $rol;
                    $usuario_actual['activo'] = $activo;
                } else {
                    $error = 'Error al actualizar el usuario.';
                }
            } else {
                $error = 'Error: No se pudo obtener la contraseña del usuario.';
            }
        }
    }
}

// Obtener roles disponibles
$roles = array('Administrador', 'Vendedor');

include $base_path . 'includes/header.php';
?>

<div class="container">
    <h1>Editar Usuario</h1>
    
    <div class="form-actions-right" style="margin-bottom: 20px;">
        <a href="/repuestos/modulos/usuarios/usuarios.php" class="btn-cancelar"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    
    <?php if ($mensaje): ?>
        <div class="mensaje exito"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="mensaje error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($usuario_actual): ?>
    <div class="form-container">
        <form method="POST">
            <label>Usuario:</label>
            <input type="text" name="usuario" value="<?php echo htmlspecialchars($usuario_actual['usuario']); ?>" required>
            
            <label>Contraseña (dejar en blanco para no cambiar):</label>
            <input type="password" name="password" placeholder="Nueva contraseña (opcional)">
            
            <label>Nombre Completo:</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario_actual['nombre']); ?>" required>
            
            <label>Rol:</label>
            <select name="rol" required>
                <?php foreach($roles as $r): ?>
                    <option value="<?php echo htmlspecialchars($r); ?>" <?php echo ($usuario_actual['rol'] === $r) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label class="checkbox-label">
                Usuario Activo <input type="checkbox" name="activo" <?php echo ($usuario_actual['activo'] == 1) ? 'checked' : ''; ?>>
            </label>
            
            <div class="form-actions">
                <button type="submit" class="btn-submit">Actualizar Usuario</button>
                <a href="usuarios.php" class="btn-cancelar">Cancelar</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

