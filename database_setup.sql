-- ============================================
-- Sistema de Usuarios y Permisos
-- Repuestos Doble A
-- ============================================

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    rol VARCHAR(50) NOT NULL DEFAULT 'Vendedor',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Tabla de permisos por módulo
CREATE TABLE IF NOT EXISTS permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol VARCHAR(50) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    puede_ver TINYINT(1) DEFAULT 0,
    puede_crear TINYINT(1) DEFAULT 0,
    puede_editar TINYINT(1) DEFAULT 0,
    puede_eliminar TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_rol_modulo (rol, modulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Tabla de sesiones (opcional, para auditoría)
CREATE TABLE IF NOT EXISTS sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_logout TIMESTAMP NULL,
    ip_address VARCHAR(45),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insertar usuario administrador por defecto (password: admin)
-- La contraseña está hasheada con password_hash de PHP
-- NOTA: Si necesitas generar un nuevo hash, ejecuta: php generar_password.php
-- O usa este comando en PHP: echo password_hash('admin', PASSWORD_DEFAULT);
INSERT IGNORE INTO usuarios (usuario, password, nombre, rol, activo) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Administrador', 1);

-- Si el hash anterior no funciona, ejecuta esto después de crear las tablas:
-- UPDATE usuarios SET password = '$2y$10$TuHashGeneradoAqui' WHERE usuario = 'admin';

-- Insertar permisos para Administrador (todos los permisos)
INSERT IGNORE INTO permisos (rol, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar) VALUES
('Administrador', 'clientes', 1, 1, 1, 1),
('Administrador', 'proveedores', 1, 1, 1, 1),
('Administrador', 'productos', 1, 1, 1, 1),
('Administrador', 'caja', 1, 1, 1, 1),
('Administrador', 'ventas', 1, 1, 1, 1),
('Administrador', 'compras', 1, 1, 1, 1),
('Administrador', 'stock', 1, 1, 1, 1),
('Administrador', 'auditoria', 1, 1, 1, 1),
('Administrador', 'reportes', 1, 1, 1, 1),
('Administrador', 'backup', 1, 1, 1, 1),
('Administrador', 'facturacion', 1, 1, 1, 1),
('Administrador', 'usuarios', 1, 1, 1, 1);

-- Insertar permisos para Vendedor (solo ventas)
INSERT IGNORE INTO permisos (rol, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar) VALUES
('Vendedor', 'ventas', 1, 1, 0, 0),
('Vendedor', 'clientes', 1, 1, 1, 0),
('Vendedor', 'productos', 1, 0, 0, 0),
('Vendedor', 'stock', 1, 0, 0, 0);

