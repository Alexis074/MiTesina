-- ============================================
-- Sistema de Facturas de Compras
-- Repuestos Doble A
-- ============================================

-- Tabla de cabecera de facturas de compras
CREATE TABLE IF NOT EXISTS cabecera_factura_compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_factura VARCHAR(50) NOT NULL UNIQUE,
    proveedor_id INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    monto_total DECIMAL(15,2) NOT NULL,
    condicion_compra VARCHAR(20) DEFAULT 'Contado',
    forma_pago VARCHAR(20) DEFAULT 'Efectivo',
    timbrado VARCHAR(50),
    numero_factura_proveedor VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE,
    INDEX idx_fecha (fecha_hora),
    INDEX idx_proveedor (proveedor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Tabla de detalle de facturas de compras
CREATE TABLE IF NOT EXISTS detalle_factura_compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (factura_id) REFERENCES cabecera_factura_compras(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_factura (factura_id),
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

