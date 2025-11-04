-- ============================================
-- Sistema de Auditoría y Facturas Anuladas
-- Repuestos Doble A
-- ============================================

-- Tabla de auditoría
CREATE TABLE IF NOT EXISTS auditoria (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Agregar campo anulada a cabecera_factura_ventas
ALTER TABLE cabecera_factura_ventas 
ADD COLUMN IF NOT EXISTS anulada TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS fecha_anulacion DATETIME NULL,
ADD COLUMN IF NOT EXISTS usuario_anulacion_id INT NULL,
ADD COLUMN IF NOT EXISTS motivo_anulacion TEXT NULL;

-- Agregar campo anulada a cabecera_factura_compras (si existe la tabla)
-- ALTER TABLE cabecera_factura_compras 
-- ADD COLUMN IF NOT EXISTS anulada TINYINT(1) DEFAULT 0,
-- ADD COLUMN IF NOT EXISTS fecha_anulacion DATETIME NULL,
-- ADD COLUMN IF NOT EXISTS usuario_anulacion_id INT NULL,
-- ADD COLUMN IF NOT EXISTS motivo_anulacion TEXT NULL;

-- Nota: Si MySQL no soporta "IF NOT EXISTS" en ALTER TABLE, usar este script alternativo:
-- ALTER TABLE cabecera_factura_ventas ADD COLUMN anulada TINYINT(1) DEFAULT 0;
-- ALTER TABLE cabecera_factura_ventas ADD COLUMN fecha_anulacion DATETIME NULL;
-- ALTER TABLE cabecera_factura_ventas ADD COLUMN usuario_anulacion_id INT NULL;
-- ALTER TABLE cabecera_factura_ventas ADD COLUMN motivo_anulacion TEXT NULL;

