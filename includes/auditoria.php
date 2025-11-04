<?php
/**
 * Sistema de Auditoría - Registro automático de eventos
 * Repuestos Doble A
 */

if (!function_exists('registrarAuditoria')) {
    /**
     * Registra un evento en la tabla de auditoría
     * @param string $accion - Acción realizada (crear, editar, eliminar, anular, login, logout, etc.)
     * @param string $modulo - Módulo donde ocurrió la acción (ventas, compras, usuarios, etc.)
     * @param string $detalle - Detalle adicional del evento
     * @param int|null $usuario_id - ID del usuario (null para usar el usuario actual)
     */
    function registrarAuditoria($accion, $modulo, $detalle = '', $usuario_id = null) {
        global $pdo;
        
        // Obtener usuario actual si no se especifica
        if ($usuario_id === null) {
            if (isset($_SESSION['usuario_id'])) {
                $usuario_id = $_SESSION['usuario_id'];
            } else {
                $usuario_id = 0; // Sistema
            }
        }
        
        // Obtener nombre de usuario
        $nombre_usuario = 'Sistema';
        if ($usuario_id > 0) {
            try {
                $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
                $stmt->execute(array($usuario_id));
                $user = $stmt->fetch();
                if ($user) {
                    $nombre_usuario = $user['nombre'] . ' (' . (isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'N/A') . ')';
                }
            } catch (Exception $e) {
                $nombre_usuario = 'Usuario #' . $usuario_id;
            }
        }
        
        // Insertar en auditoría
        try {
            $stmt = $pdo->prepare("INSERT INTO auditoria (usuario_id, nombre_usuario, accion, modulo, detalle, fecha_hora, ip_address) 
                                    VALUES (?, ?, ?, ?, ?, NOW(), ?)");
            $stmt->execute(array(
                $usuario_id,
                $nombre_usuario,
                $accion,
                $modulo,
                $detalle,
                isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'N/A'
            ));
        } catch (Exception $e) {
            // Si la tabla no existe, intentar crearla
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                crearTablaAuditoria();
                // Intentar nuevamente
                try {
                    $stmt = $pdo->prepare("INSERT INTO auditoria (usuario_id, nombre_usuario, accion, modulo, detalle, fecha_hora, ip_address) 
                                            VALUES (?, ?, ?, ?, ?, NOW(), ?)");
                    $stmt->execute(array(
                        $usuario_id,
                        $nombre_usuario,
                        $accion,
                        $modulo,
                        $detalle,
                        isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'N/A'
                    ));
                } catch (Exception $e2) {
                    // Si falla, no hacer nada (no interrumpir el flujo)
                }
            }
        }
    }
}

if (!function_exists('crearTablaAuditoria')) {
    /**
     * Crea la tabla de auditoría si no existe
     */
    function crearTablaAuditoria() {
        global $pdo;
        try {
            $sql = "CREATE TABLE IF NOT EXISTS auditoria (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT DEFAULT 0,
                nombre_usuario VARCHAR(150) NOT NULL,
                accion VARCHAR(50) NOT NULL,
                modulo VARCHAR(50) NOT NULL,
                detalle TEXT,
                fecha_hora DATETIME NOT NULL,
                ip_address VARCHAR(45),
                INDEX idx_fecha (fecha_hora),
                INDEX idx_modulo (modulo),
                INDEX idx_accion (accion)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $pdo->exec($sql);
        } catch (Exception $e) {
            // Error al crear tabla
        }
    }
}

