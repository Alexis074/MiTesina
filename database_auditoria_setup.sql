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

-- Agregar restricción UNIQUE a numero_factura para garantizar que nunca se reutilice
-- Esto asegura que incluso si una factura es anulada, su número no puede ser usado por otra factura
-- NOTA: Si el índice ya existe, este comando mostrará un error. Eso está bien, significa que ya está configurado.
-- Si quieres verificar primero si existe, ejecuta: SHOW INDEX FROM cabecera_factura_ventas WHERE Key_name = 'idx_numero_factura_unique';

CREATE UNIQUE INDEX idx_numero_factura_unique ON cabecera_factura_ventas (numero_factura);

-- IMPORTANTE: Antes de crear el índice único, debemos actualizar los timbrados duplicados existentes
-- Si hay facturas con el mismo timbrado (ej: todas con '12345678'), las actualizamos a valores únicos

-- Paso 1: Actualizar timbrados existentes para que sean únicos basados en el ID + número base
-- El número base es 4571575, así que cada factura tendrá: timbrado_base + (id - 1)
UPDATE cabecera_factura_ventas 
SET timbrado = CAST(4571575 + (id - 1) AS CHAR) 
WHERE timbrado IS NULL OR timbrado = '' OR timbrado = '12345678' OR timbrado NOT REGEXP '^[0-9]+$';

-- Paso 2: Verificar que no haya duplicados (si hay, actualizar manualmente)
-- Si aún hay duplicados después del UPDATE, ejecuta esto para ver cuáles son:
-- SELECT timbrado, COUNT(*) as cantidad FROM cabecera_factura_ventas GROUP BY timbrado HAVING cantidad > 1;

-- Paso 3: Crear el índice único (solo después de actualizar los timbrados)
-- NOTA: Si el índice ya existe, este comando mostrará un error. Eso está bien.
CREATE UNIQUE INDEX idx_timbrado_unique ON cabecera_factura_ventas (timbrado);

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

