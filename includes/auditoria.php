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
                $stmt->execute([$usuario_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && isset($user['nombre'])) {
                    $session_usuario = $_SESSION['usuario'] ?? 'N/A';
                    $nombre_usuario = $user['nombre'] . ' (' . $session_usuario . ')';
                }
            } catch (Exception $e) {
                $nombre_usuario = 'Usuario #' . $usuario_id;
                error_log('Error en auditoria al obtener nombre de usuario: ' . $e->getMessage());
            }
        }
        
        // Insertar en auditoría
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
            $stmt = $pdo->prepare("INSERT INTO auditoria (usuario_id, nombre_usuario, accion, modulo, detalle, fecha_hora, ip_address) 
                                    VALUES (?, ?, ?, ?, ?, NOW(), ?)");
            $stmt->execute([
                $usuario_id,
                $nombre_usuario,
                $accion,
                $modulo,
                $detalle,
                $ip_address
            ]);
        } catch (Exception $e) {
            // Si la tabla no existe, intentar crearla
            if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "does not exist") !== false) {
                crearTablaAuditoria();
                // Intentar nuevamente
                try {
                    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
                    $stmt = $pdo->prepare("INSERT INTO auditoria (usuario_id, nombre_usuario, accion, modulo, detalle, fecha_hora, ip_address) 
                                            VALUES (?, ?, ?, ?, ?, NOW(), ?)");
                    $stmt->execute([
                        $usuario_id,
                        $nombre_usuario,
                        $accion,
                        $modulo,
                        $detalle,
                        $ip_address
                    ]);
                } catch (Exception $e2) {
                    // Si falla, no hacer nada (no interrumpir el flujo)
                    error_log('Error en auditoria al insertar registro: ' . $e2->getMessage());
                }
            } else {
                error_log('Error en auditoria: ' . $e->getMessage());
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

