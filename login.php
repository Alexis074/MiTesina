<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';

$error = '';

// Si ya está logueado, redirigir al inicio
if (estaLogueado()) {
    header('Location: /repuestos/index.php');
    exit();
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($usuario) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        $stmt = $pdo->prepare("SELECT id, usuario, password, nombre, rol, activo FROM usuarios WHERE usuario = ?");
        $stmt->execute(array($usuario));
        $user = $stmt->fetch();
        
        if ($user && $user['activo'] == 1) {
            // Verificar contraseña (PHP 5.6 compatible)
            if (password_verify($password, $user['password'])) {
                // Iniciar sesión
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario'] = $user['usuario'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['rol'] = $user['rol'];
                
                // Registrar sesión
                $stmt_sesion = $pdo->prepare("INSERT INTO sesiones (usuario_id, ip_address) VALUES (?, ?)");
                $stmt_sesion->execute(array($user['id'], $_SERVER['REMOTE_ADDR']));
                $_SESSION['sesion_id'] = $pdo->lastInsertId();
                
                // Registrar en auditoría
                include $base_path . 'includes/auditoria.php';
                registrarAuditoria('login', 'sistema', 'Usuario ' . $user['usuario'] . ' inició sesión');
                
                header('Location: /repuestos/index.php');
                exit();
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Repuestos Doble A</title>
    <link rel="stylesheet" href="/repuestos/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 100%;
        }
        .login-container h1 {
            text-align: center;
            color: #1e3a5f;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .login-container .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-container .logo i {
            font-size: 48px;
            color: #2563eb;
        }
        .login-form label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
        }
        .login-form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .login-form input:focus {
            outline: none;
            border-color: #2563eb;
        }
        .login-form button {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        .login-form button:hover {
            background: #1e40af;
        }
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .login-info-box {
            background: rgba(37, 99, 235, 0.1);
            border: 2px solid #2563eb;
            border-radius: 10px;
            padding: 15px 20px;
            margin-top: 25px;
        }
        .login-info-box h3 {
            color: #1e3a5f;
            margin: 0 0 12px 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .login-info-box .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 8px 0;
            color: #475569;
            font-size: 14px;
        }
        .login-info-box .info-item i {
            color: #2563eb;
            width: 18px;
        }
        .login-info-box .info-item strong {
            color: #1e3a5f;
            min-width: 90px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-cogs"></i>
        </div>
        <h1>Repuestos Doble A</h1>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <label for="usuario"><i class="fas fa-user"></i> Usuario:</label>
            <input type="text" id="usuario" name="usuario" required autofocus>
            
            <label for="password"><i class="fas fa-lock"></i> Contraseña:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>
        
        <div class="login-info-box">
            <h3><i class="fas fa-info-circle"></i> Usuario por defecto</h3>
            <div class="info-item">
                <i class="fas fa-user"></i>
                <strong>Usuario:</strong> <span>admin</span>
            </div>
            <div class="info-item">
                <i class="fas fa-lock"></i>
                <strong>Contraseña:</strong> <span>admin</span>
            </div>
        </div>
    </div>
</body>
</html>

