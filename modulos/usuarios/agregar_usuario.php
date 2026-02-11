<?php
$base_url = '/repuestos/';
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();
requerirPermiso('usuarios', 'crear');

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $rol = isset($_POST['rol']) ? $_POST['rol'] : 'Vendedor';
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if (empty($usuario) || empty($password) || empty($nombre)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        // Verificar si el usuario ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $error = 'El usuario ya existe.';
        } else {
            // Hash de la contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar usuario
            $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, password, nombre, rol, activo) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$usuario, $password_hash, $nombre, $rol, $activo])) {
                // Registrar en auditoría
                include $base_path . 'includes/auditoria.php';
                registrarAuditoria('crear', 'usuarios', 'Usuario ' . $usuario . ' creado. Rol: ' . $rol);
                
                $mensaje = 'Usuario creado correctamente.';
            } else {
                $error = 'Error al crear el usuario.';
            }
        }
    }
}

// Obtener roles disponibles
$roles = array('Administrador', 'Vendedor');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Usuario - Repuestos Doble A</title>
    <link rel="stylesheet" href="<?= $base_url ?>style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<?php include $base_path . 'includes/header.php'; ?>

<div class="container">
    <h1>Agregar Usuario</h1>
    
    <div class="form-actions-right" style="margin-bottom: 20px;">
        <a href="<?= $base_url ?>modulos/usuarios/usuarios.php" class="btn-cancelar"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    
    <?php if ($mensaje): ?>
        <div class="mensaje exito"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="mensaje error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="form-container">
        <form method="POST">
            <label>Usuario:</label>
            <input type="text" name="usuario" required>
            
            <label>Contraseña:</label>
            <input type="password" name="password" required>
            
            <label>Nombre Completo:</label>
            <input type="text" name="nombre" required>
            
            <label>Rol:</label>
            <select name="rol" required>
                <?php foreach($roles as $r): ?>
                    <option value="<?php echo htmlspecialchars($r); ?>"><?php echo htmlspecialchars($r); ?></option>
                <?php endforeach; ?>
            </select>
            
            <label class="checkbox-label">
                Usuario Activo <input type="checkbox" name="activo" checked>
            </label>
            
            <div class="form-actions">
                <button type="submit" class="btn-submit">Crear Usuario</button>
                <a href="<?= $base_url ?>modulos/usuarios/usuarios.php" class="btn-cancelar">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

