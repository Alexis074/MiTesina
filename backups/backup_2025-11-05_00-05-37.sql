-- Backup de Base de Datos - Repuestos Doble A
-- Fecha: 2025-11-05 00:05:37

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


-- Estructura de tabla `auditoria`
DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE `auditoria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT '0',
  `nombre_usuario` varchar(150) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `detalle` text,
  `fecha_hora` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fecha` (`fecha_hora`),
  KEY `idx_modulo` (`modulo`),
  KEY `idx_accion` (`accion`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- Volcado de datos de la tabla `auditoria`
INSERT INTO `auditoria` (`id`, `usuario_id`, `nombre_usuario`, `accion`, `modulo`, `detalle`, `fecha_hora`, `ip_address`) VALUES
('1', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000050 creada. Cliente ID: 4, Total: 104.500', '2025-11-04 15:02:39', '127.0.0.1'),
('2', '1', 'Administrador (admin)', 'anular', 'facturacion', 'Factura #001-001-000050 anulada. Motivo: error tipogria', '2025-11-04 15:07:13', '127.0.0.1'),
('3', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000051 creada. Cliente ID: 3, Total: 968.000', '2025-11-04 16:14:54', '127.0.0.1'),
('4', '1', 'Administrador (admin)', 'login', 'sistema', 'Usuario admin inició sesión', '2025-11-04 19:02:22', '127.0.0.1'),
('5', '1', 'Administrador (admin)', 'crear', 'compras', 'Compra #9 creada. Proveedor: JDM Asunción, Total: 335.000', '2025-11-04 19:05:21', '127.0.0.1'),
('6', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000052 creada. Cliente ID: 7, Total: 3.776.500', '2025-11-04 19:09:32', '127.0.0.1'),
('7', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000053 creada. Cliente ID: 16, Total: 159.500', '2025-11-04 19:10:01', '127.0.0.1'),
('8', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000054 creada. Cliente ID: 2, Total: 4.554.000', '2025-11-04 19:11:29', '127.0.0.1'),
('9', '1', 'Administrador (admin)', 'crear', 'backup', 'Backup creado: backup_2025-11-05_00-05-30.sql', '2025-11-04 20:05:30', '127.0.0.1');


-- Estructura de tabla `cabecera_factura_compras`
DROP TABLE IF EXISTS `cabecera_factura_compras`;
CREATE TABLE `cabecera_factura_compras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_factura` varchar(50) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `monto_total` decimal(15,2) NOT NULL,
  `condicion_compra` varchar(20) DEFAULT 'Contado',
  `forma_pago` varchar(20) DEFAULT 'Efectivo',
  `timbrado` varchar(50) DEFAULT NULL,
  `numero_factura_proveedor` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_factura` (`numero_factura`),
  KEY `idx_fecha` (`fecha_hora`),
  KEY `idx_proveedor` (`proveedor_id`),
  CONSTRAINT `cabecera_factura_compras_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Volcado de datos de la tabla `cabecera_factura_compras`

-- Estructura de tabla `cabecera_factura_ventas`
DROP TABLE IF EXISTS `cabecera_factura_ventas`;
CREATE TABLE `cabecera_factura_ventas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_factura` varchar(20) NOT NULL,
  `condicion_venta` enum('Contado','Crédito') NOT NULL,
  `forma_pago` enum('Efectivo','Tarjeta','Transferencia') NOT NULL,
  `fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  `cliente_id` int(11) NOT NULL,
  `monto_total` decimal(15,2) NOT NULL,
  `timbrado` varchar(20) DEFAULT '',
  `inicio_vigencia` date DEFAULT NULL,
  `fin_vigencia` date DEFAULT NULL,
  `anulada` tinyint(1) DEFAULT '0',
  `fecha_anulacion` datetime DEFAULT NULL,
  `usuario_anulacion_id` int(11) DEFAULT NULL,
  `motivo_anulacion` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_numero_factura_unique` (`numero_factura`),
  UNIQUE KEY `idx_timbrado_unique` (`timbrado`),
  KEY `cliente_id` (`cliente_id`)
) ENGINE=MyISAM AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `cabecera_factura_ventas`
INSERT INTO `cabecera_factura_ventas` (`id`, `numero_factura`, `condicion_venta`, `forma_pago`, `fecha_hora`, `cliente_id`, `monto_total`, `timbrado`, `inicio_vigencia`, `fin_vigencia`, `anulada`, `fecha_anulacion`, `usuario_anulacion_id`, `motivo_anulacion`) VALUES
('1', 'FV-000001', 'Contado', 'Efectivo', '2025-10-22 16:13:15', '13', '80000.00', '4571575', NULL, NULL, '0', NULL, NULL, NULL),
('2', 'FV-000002', 'Contado', 'Efectivo', '2025-10-22 16:30:07', '13', '160000.00', '4571576', NULL, NULL, '0', NULL, NULL, NULL),
('3', 'FV-000003', 'Contado', 'Efectivo', '2025-10-22 16:33:36', '13', '160000.00', '4571577', NULL, NULL, '0', NULL, NULL, NULL),
('4', 'FV-000004', 'Contado', 'Efectivo', '2025-10-22 16:33:41', '13', '160000.00', '4571578', NULL, NULL, '0', NULL, NULL, NULL),
('5', 'FV-000005', 'Contado', 'Efectivo', '2025-10-22 16:34:14', '13', '50000.00', '4571579', NULL, NULL, '0', NULL, NULL, NULL),
('6', 'FV-000006', 'Contado', 'Efectivo', '2025-10-22 16:39:35', '13', '100000.00', '4571580', NULL, NULL, '0', NULL, NULL, NULL),
('7', '001-001-000007', 'Contado', 'Efectivo', '2025-10-22 18:09:43', '4', '50000.00', '4571581', NULL, NULL, '0', NULL, NULL, NULL),
('8', '001-001-000008', 'Contado', 'Efectivo', '2025-10-22 18:31:10', '13', '120000.00', '4571582', NULL, NULL, '0', NULL, NULL, NULL),
('9', '001-001-000009', 'Contado', 'Efectivo', '2025-10-22 13:34:31', '13', '120000.00', '4571583', NULL, NULL, '0', NULL, NULL, NULL),
('10', '001-001-000010', 'Contado', 'Efectivo', '2025-10-22 13:44:49', '13', '120000.00', '4571584', NULL, NULL, '0', NULL, NULL, NULL),
('11', '001-001-000011', 'Contado', 'Efectivo', '2025-10-22 19:54:52', '4', '220000.00', '4571585', NULL, NULL, '0', NULL, NULL, NULL),
('12', '001-001-000012', 'Contado', 'Efectivo', '2025-10-22 20:04:16', '4', '220000.00', '4571586', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('13', '001-001-000013', 'Contado', 'Efectivo', '2025-10-22 20:34:27', '4', '220000.00', '4571587', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('14', '001-001-000014', 'Contado', 'Efectivo', '2025-10-22 20:39:31', '4', '220000.00', '4571588', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('15', '001-001-000015', 'Contado', 'Efectivo', '2025-10-22 15:45:31', '4', '220000.00', '4571589', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('16', '001-001-000016', 'Contado', 'Efectivo', '2025-10-22 15:57:01', '3', '250000.00', '4571590', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('17', '001-001-000017', 'Contado', 'Efectivo', '2025-10-29 18:37:02', '13', '25000.00', '4571591', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('18', '001-001-000018', 'Contado', 'Efectivo', '2025-10-29 18:42:16', '13', '855000.00', '4571592', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('19', '001-001-000019', 'Contado', 'Efectivo', '2025-10-29 18:45:14', '4', '920000.00', '4571593', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('20', '001-001-000020', 'Contado', 'Efectivo', '2025-10-29 19:18:25', '4', '555000.00', '4571594', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('21', '001-001-000021', 'Contado', 'Efectivo', '2025-10-29 19:21:49', '4', '130000.00', '4571595', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('22', '001-001-000022', 'Contado', 'Efectivo', '2025-11-01 21:10:04', '3', '2090000.00', '4571596', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('23', '001-001-000023', 'Contado', 'Efectivo', '2025-11-01 21:16:03', '3', '220000.00', '4571597', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('24', '001-001-000024', 'Contado', 'Efectivo', '2025-11-01 21:18:07', '3', '900000.00', '4571598', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('25', '001-001-000025', 'Contado', 'Efectivo', '2025-11-01 21:22:55', '3', '150000.00', '4571599', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('26', '001-001-000026', 'Contado', 'Efectivo', '2025-11-01 21:23:45', '4', '440000.00', '4571600', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('27', '001-001-000027', 'Contado', 'Efectivo', '2025-11-01 21:25:30', '4', '75000.00', '4571601', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('28', '001-001-000028', 'Contado', 'Efectivo', '2025-11-01 21:29:17', '4', '75000.00', '4571602', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('29', '001-001-000029', 'Contado', 'Efectivo', '2025-11-01 21:29:27', '4', '75000.00', '4571603', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('30', '001-001-000030', 'Contado', 'Efectivo', '2025-11-01 21:30:07', '4', '65000.00', '4571604', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('31', '001-001-000031', 'Contado', 'Efectivo', '2025-11-01 21:37:55', '3', '35000.00', '4571605', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('32', '001-001-000032', 'Contado', 'Efectivo', '2025-11-01 21:41:22', '3', '35000.00', '4571606', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('33', '001-001-000033', 'Contado', 'Efectivo', '2025-11-01 21:41:57', '10', '35000.00', '4571607', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('34', '001-001-000034', 'Contado', 'Efectivo', '2025-11-01 21:43:25', '13', '120000.00', '4571608', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('35', '001-001-000035', 'Contado', 'Efectivo', '2025-11-01 21:46:40', '13', '120000.00', '4571609', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('36', '001-001-000036', 'Contado', 'Efectivo', '2025-11-01 21:51:22', '13', '120000.00', '4571610', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('37', '001-001-000037', 'Contado', 'Efectivo', '2025-11-01 21:51:41', '3', '35000.00', '4571611', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('38', '001-001-000038', 'Contado', 'Efectivo', '2025-11-01 21:52:38', '4', '35000.00', '4571612', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('39', '001-001-000039', 'Contado', 'Efectivo', '2025-11-01 21:57:21', '4', '65000.00', '4571613', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('40', '001-001-000040', 'Contado', 'Efectivo', '2025-11-01 22:04:49', '3', '480000.00', '4571614', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('41', '001-001-000041', 'Contado', 'Efectivo', '2025-11-01 22:09:36', '4', '310000.00', '4571615', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('42', '001-001-000042', 'Contado', 'Efectivo', '2025-11-02 11:51:15', '13', '375000.00', '4571616', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('43', '001-001-000043', 'Contado', 'Efectivo', '2025-11-02 12:22:15', '2', '4920000.00', '4571617', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('44', '001-001-000044', 'Contado', 'Efectivo', '2025-11-02 12:38:34', '2', '4920000.00', '4571618', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('45', '001-001-000045', 'Contado', 'Efectivo', '2025-11-02 14:55:29', '9', '678500.00', '4571619', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('46', '001-001-000046', 'Contado', 'Efectivo', '2025-11-02 15:21:59', '9', '678500.00', '4571620', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('47', '001-001-000047', 'Contado', 'Efectivo', '2025-11-02 15:22:20', '3', '1881000.00', '4571621', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('48', '001-001-000048', 'Contado', 'Efectivo', '2025-11-02 15:25:22', '3', '1881000.00', '4571622', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('49', '001-001-000049', 'Contado', 'Efectivo', '2025-11-02 18:01:49', '3', '36750.00', '4571623', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('50', '001-001-000050', 'Contado', 'Efectivo', '2025-11-04 15:02:39', '4', '104500.00', '4571624', '2025-01-01', '2025-12-31', '1', '2025-11-04 15:07:13', '1', 'error tipogria'),
('51', '001-001-000051', 'Contado', 'Efectivo', '2025-11-04 16:14:54', '3', '968000.00', '4571625', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('52', '001-001-000052', 'Contado', 'Efectivo', '2025-11-04 19:09:32', '7', '3776500.00', '4571626', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('53', '001-001-000053', 'Crédito', 'Tarjeta', '2025-11-04 19:10:01', '16', '159500.00', '4571627', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('54', '001-001-000054', 'Contado', 'Efectivo', '2025-11-04 19:11:29', '2', '4554000.00', '4571628', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL);


-- Estructura de tabla `caja`
DROP TABLE IF EXISTS `caja`;
CREATE TABLE `caja` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `monto_inicial` decimal(12,2) NOT NULL,
  `monto_final` decimal(12,2) DEFAULT NULL,
  `estado` enum('Abierta','Cerrada') NOT NULL DEFAULT 'Abierta',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `caja`
INSERT INTO `caja` (`id`, `fecha`, `monto_inicial`, `monto_final`, `estado`, `created_at`) VALUES
('1', '2025-10-21', '2500000.00', '2500000.00', 'Cerrada', '2025-10-22 03:29:16'),
('2', '2025-10-21', '3000000.00', '3000000.00', 'Cerrada', '2025-10-22 03:31:03'),
('3', '2025-10-21', '250000.00', '250000.00', 'Cerrada', '2025-10-22 03:39:26'),
('4', '2025-10-21', '500000.00', '-1244000.00', 'Cerrada', '2025-10-22 04:34:34'),
('5', '2025-10-29', '2500000.00', '2280000.00', 'Cerrada', '2025-10-29 22:36:11'),
('6', '2025-11-04', '5000000.00', NULL, 'Abierta', '2025-11-04 23:04:36');


-- Estructura de tabla `caja_movimientos`
DROP TABLE IF EXISTS `caja_movimientos`;
CREATE TABLE `caja_movimientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caja_id` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `tipo` enum('Ingreso','Egreso') NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `caja_id` (`caja_id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `caja_movimientos`
INSERT INTO `caja_movimientos` (`id`, `caja_id`, `fecha`, `tipo`, `concepto`, `monto`, `created_at`) VALUES
('1', '4', '2025-10-22 05:30:05', 'Egreso', 'Compra a proveedor ID 4, compra ID 2', '1344000.00', '2025-10-22 00:30:05'),
('2', '4', '2025-10-22 05:30:22', 'Egreso', 'Compra a proveedor ID 11, compra ID 3', '400000.00', '2025-10-22 00:30:22'),
('3', '1', '2025-10-22 16:13:15', 'Ingreso', 'Venta factura FV-000001', '80000.00', '2025-10-22 11:13:15'),
('4', '1', '2025-10-22 16:30:07', 'Ingreso', 'Venta factura FV-000002', '160000.00', '2025-10-22 11:30:07'),
('5', '1', '2025-10-22 16:33:36', 'Ingreso', 'Venta factura FV-000003', '160000.00', '2025-10-22 11:33:36'),
('6', '1', '2025-10-22 16:33:41', 'Ingreso', 'Venta factura FV-000004', '160000.00', '2025-10-22 11:33:41'),
('7', '1', '2025-10-22 16:34:14', 'Ingreso', 'Venta factura FV-000005', '50000.00', '2025-10-22 11:34:14'),
('8', '1', '2025-10-22 16:39:35', 'Ingreso', 'Venta factura FV-000006', '100000.00', '2025-10-22 11:39:35'),
('9', '1', '2025-10-22 18:09:43', 'Ingreso', 'Venta factura 001-001-000007', '50000.00', '2025-10-22 13:09:43'),
('10', '1', '2025-10-22 18:31:10', 'Ingreso', 'Venta factura 001-001-000008', '120000.00', '2025-10-22 13:31:10'),
('11', '1', '2025-10-22 13:34:31', 'Ingreso', 'Venta factura 001-001-000009', '120000.00', '2025-10-22 13:34:31'),
('12', '1', '2025-10-22 13:44:49', 'Ingreso', 'Venta factura 001-001-000010', '120000.00', '2025-10-22 13:44:49'),
('13', '1', '2025-10-22 19:54:52', 'Ingreso', 'Venta factura 001-001-000011', '220000.00', '2025-10-22 14:54:52'),
('14', '5', '2025-10-29 22:36:38', 'Egreso', 'Compra a proveedor ID 11, compra ID 5', '85000.00', '2025-10-29 18:36:38'),
('15', '5', '2025-10-29 23:15:30', 'Egreso', 'Compra a proveedor ID 2, compra ID 6', '135000.00', '2025-10-29 19:15:30'),
('16', '6', '2025-11-04 23:05:20', 'Egreso', 'Compra a proveedor ID 3, compra ID 9', '335000.00', '2025-11-04 19:05:21');


-- Estructura de tabla `clientes`
DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `ruc` varchar(20) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `clientes`
INSERT INTO `clientes` (`id`, `nombre`, `apellido`, `ruc`, `telefono`, `email`, `direccion`, `created_at`) VALUES
('2', 'Maria', 'Lopez', '80023456-2', '0972234567', 'maria.lopez@gmail.com', 'San Lorenzo, Calle 12', '2025-09-30 22:25:04'),
('3', 'Carlos', 'Rodriguez', '80034567-3', '0973345678', 'carlos.rodriguez@gmail.com', 'Luque, Avenida Central', '2025-09-30 22:25:04'),
('4', 'Ana', 'Martinez', '80045678-4', '0974456789', 'ana.martinez@gmail.com', 'Encarnacion, Calle Principal', '2025-09-30 22:25:04'),
('5', 'Pedro', 'Vargas', '80056789-5', '0975567890', 'pedro.vargas@gmail.com', 'Ciudad del Este, Barrio Centro', '2025-09-30 22:25:04'),
('7', 'Miguel', 'Alvarez', '80078901-7', '0977789012', 'miguel.alvarez@gmail.com', 'Fernando de la Mora, Zona 1', '2025-09-30 22:25:04'),
('8', 'Sofia', 'Diaz', '80089012-8', '0978890123', 'sofia.diaz@gmail.com', 'Villa Elisa, Calle 5', '2025-09-30 22:25:04'),
('16', 'Elias', 'Gonzalez', '555884442-8', '0975485634', 'rafaespsssssinola60@gmail.com', 'Tuyuti Primera Proyectada', '2025-11-03 02:12:21');


-- Estructura de tabla `compras`
DROP TABLE IF EXISTS `compras`;
CREATE TABLE `compras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proveedor_id` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `estado` enum('Pendiente','Completada') NOT NULL DEFAULT 'Pendiente',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `proveedor_id` (`proveedor_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `compras`
INSERT INTO `compras` (`id`, `proveedor_id`, `fecha`, `total`, `estado`, `created_at`) VALUES
('1', '4', '2025-10-22 05:28:25', '1344000.00', 'Pendiente', '2025-10-22 00:28:25'),
('2', '4', '2025-10-22 05:30:05', '1344000.00', 'Pendiente', '2025-10-22 00:30:05'),
('3', '11', '2025-10-22 05:30:22', '400000.00', 'Pendiente', '2025-10-22 00:30:22'),
('4', '11', '2025-10-22 13:11:18', '160000.00', 'Pendiente', '2025-10-22 08:11:18'),
('5', '11', '2025-10-29 22:36:38', '85000.00', 'Pendiente', '2025-10-29 18:36:38'),
('6', '2', '2025-10-29 23:15:30', '135000.00', 'Pendiente', '2025-10-29 19:15:30'),
('7', '2', '2025-11-02 22:01:11', '1.00', 'Pendiente', '2025-11-02 18:01:11'),
('8', '3', '2025-11-04 17:17:30', '6840000.00', 'Pendiente', '2025-11-04 13:17:30'),
('9', '3', '2025-11-04 23:05:20', '335000.00', 'Pendiente', '2025-11-04 19:05:20');


-- Estructura de tabla `compras_detalle`
DROP TABLE IF EXISTS `compras_detalle`;
CREATE TABLE `compras_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compra_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `compra_id` (`compra_id`),
  KEY `producto_id` (`producto_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `compras_detalle`
INSERT INTO `compras_detalle` (`id`, `compra_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
('1', '2', '43', '12', '112000.00', '1344000.00'),
('2', '3', '70', '5', '80000.00', '400000.00'),
('3', '4', '36', '2', '80000.00', '160000.00'),
('4', '5', '43', '1', '85000.00', '85000.00'),
('5', '6', '36', '1', '85000.00', '85000.00'),
('6', '6', '86', '2', '25000.00', '50000.00'),
('7', '7', '57', '1', '1.00', '1.00'),
('8', '8', '41', '152', '45000.00', '6840000.00'),
('9', '9', '70', '1', '50000.00', '50000.00'),
('10', '9', '49', '1', '145000.00', '145000.00'),
('11', '9', '80', '1', '140000.00', '140000.00');


-- Estructura de tabla `detalle_factura_compras`
DROP TABLE IF EXISTS `detalle_factura_compras`;
CREATE TABLE `detalle_factura_compras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `factura_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_factura` (`factura_id`),
  KEY `idx_producto` (`producto_id`),
  CONSTRAINT `detalle_factura_compras_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `cabecera_factura_compras` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detalle_factura_compras_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

-- Volcado de datos de la tabla `detalle_factura_compras`
INSERT INTO `detalle_factura_compras` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
('1', '1', '3', '10.00', '25000.00', '250000.00'),
('2', '1', '5', '4.00', '120000.00', '480000.00'),
('3', '1', '7', '6.00', '15000.00', '90000.00'),
('4', '2', '2', '2.00', '350000.00', '700000.00'),
('5', '2', '4', '1.00', '180000.00', '180000.00'),
('6', '3', '1', '3.00', '60000.00', '180000.00'),
('7', '3', '8', '2.00', '450000.00', '900000.00'),
('8', '4', '6', '5.00', '20000.00', '100000.00'),
('9', '4', '9', '3.00', '85000.00', '255000.00'),
('10', '5', '10', '2.00', '320000.00', '640000.00');


-- Estructura de tabla `detalle_factura_ventas`
DROP TABLE IF EXISTS `detalle_factura_ventas`;
CREATE TABLE `detalle_factura_ventas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `factura_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(15,2) NOT NULL,
  `valor_venta_5` decimal(15,2) DEFAULT '0.00',
  `valor_venta_10` decimal(15,2) DEFAULT '0.00',
  `valor_venta_exenta` decimal(15,2) DEFAULT '0.00',
  `total_parcial` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `factura_id` (`factura_id`),
  KEY `producto_id` (`producto_id`)
) ENGINE=MyISAM AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `detalle_factura_ventas`
INSERT INTO `detalle_factura_ventas` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `valor_venta_5`, `valor_venta_10`, `valor_venta_exenta`, `total_parcial`) VALUES
('1', '1', '36', '1', '80000.00', '0.00', '80000.00', '0.00', '80000.00'),
('2', '2', '36', '2', '80000.00', '0.00', '160000.00', '0.00', '160000.00'),
('3', '3', '36', '2', '80000.00', '0.00', '160000.00', '0.00', '160000.00'),
('4', '4', '36', '2', '80000.00', '0.00', '160000.00', '0.00', '160000.00'),
('5', '5', '70', '1', '50000.00', '0.00', '50000.00', '0.00', '50000.00'),
('6', '6', '79', '1', '75000.00', '0.00', '75000.00', '0.00', '75000.00'),
('7', '6', '47', '1', '25000.00', '0.00', '25000.00', '0.00', '25000.00'),
('8', '7', '70', '1', '50000.00', '0.00', '50000.00', '0.00', '50000.00'),
('9', '8', '41', '1', '120000.00', '0.00', '120000.00', '0.00', '120000.00'),
('10', '9', '41', '1', '120000.00', '0.00', '120000.00', '0.00', '120000.00'),
('11', '10', '41', '1', '120000.00', '0.00', '120000.00', '0.00', '120000.00'),
('12', '11', '46', '1', '220000.00', '0.00', '220000.00', '0.00', '220000.00'),
('13', '12', '46', '1', '220000.00', '0.00', '220000.00', '0.00', '220000.00'),
('14', '13', '46', '1', '220000.00', '0.00', '220000.00', '0.00', '220000.00'),
('15', '14', '46', '1', '220000.00', '0.00', '220000.00', '0.00', '220000.00'),
('16', '15', '46', '1', '220000.00', '0.00', '220000.00', '0.00', '220000.00'),
('17', '16', '70', '5', '50000.00', '0.00', '250000.00', '0.00', '250000.00'),
('18', '17', '78', '1', '25000.00', '25000.00', '0.00', '0.00', '25000.00'),
('19', '18', '47', '2', '25000.00', '0.00', '50000.00', '0.00', '50000.00'),
('20', '18', '57', '2', '120000.00', '0.00', '240000.00', '0.00', '240000.00'),
('21', '18', '57', '2', '120000.00', '0.00', '240000.00', '0.00', '240000.00'),
('22', '18', '44', '5', '65000.00', '0.00', '325000.00', '0.00', '325000.00'),
('23', '19', '44', '1', '65000.00', '0.00', '65000.00', '0.00', '65000.00'),
('24', '19', '79', '2', '75000.00', '0.00', '150000.00', '0.00', '150000.00'),
('25', '19', '47', '5', '25000.00', '0.00', '125000.00', '0.00', '125000.00'),
('26', '19', '48', '2', '140000.00', '0.00', '280000.00', '0.00', '280000.00'),
('27', '19', '86', '2', '150000.00', '0.00', '300000.00', '0.00', '300000.00'),
('28', '20', '47', '2', '25000.00', '0.00', '50000.00', '0.00', '50000.00'),
('29', '20', '48', '2', '140000.00', '0.00', '280000.00', '0.00', '280000.00'),
('30', '20', '43', '3', '75000.00', '0.00', '225000.00', '0.00', '225000.00'),
('31', '21', '44', '2', '65000.00', '0.00', '130000.00', '0.00', '130000.00'),
('32', '22', '62', '45', '35000.00', '0.00', '1575000.00', '0.00', '1575000.00'),
('33', '22', '57', '4', '120000.00', '480000.00', '0.00', '0.00', '480000.00'),
('34', '22', '61', '1', '35000.00', '0.00', '0.00', '35000.00', '35000.00'),
('35', '23', '36', '1', '80000.00', '0.00', '80000.00', '0.00', '80000.00'),
('36', '23', '62', '4', '35000.00', '0.00', '140000.00', '0.00', '140000.00'),
('37', '24', '86', '6', '150000.00', '900000.00', '0.00', '0.00', '900000.00'),
('38', '25', '43', '2', '75000.00', '0.00', '150000.00', '0.00', '150000.00'),
('39', '26', '79', '4', '75000.00', '0.00', '300000.00', '0.00', '300000.00'),
('40', '26', '62', '4', '35000.00', '140000.00', '0.00', '0.00', '140000.00'),
('41', '27', '43', '1', '75000.00', '0.00', '75000.00', '0.00', '75000.00'),
('42', '28', '43', '1', '75000.00', '0.00', '75000.00', '0.00', '75000.00'),
('43', '29', '43', '1', '75000.00', '0.00', '75000.00', '0.00', '75000.00'),
('44', '30', '44', '1', '65000.00', '0.00', '65000.00', '0.00', '65000.00'),
('45', '31', '62', '1', '35000.00', '0.00', '35000.00', '0.00', '35000.00'),
('46', '32', '62', '1', '35000.00', '0.00', '35000.00', '0.00', '35000.00'),
('47', '33', '62', '1', '35000.00', '0.00', '35000.00', '0.00', '35000.00'),
('48', '34', '57', '1', '120000.00', '0.00', '120000.00', '0.00', '120000.00'),
('49', '35', '57', '1', '120000.00', '0.00', '120000.00', '0.00', '120000.00'),
('50', '36', '57', '1', '120000.00', '0.00', '12000.00', '0.00', '120000.00'),
('51', '37', '62', '1', '35000.00', '0.00', '3500.00', '0.00', '35000.00'),
('52', '38', '62', '1', '35000.00', '0.00', '3500.00', '0.00', '35000.00'),
('53', '39', '44', '1', '65000.00', '0.00', '6500.00', '0.00', '65000.00'),
('54', '40', '57', '4', '120000.00', '0.00', '48000.00', '0.00', '480000.00'),
('55', '41', '62', '1', '35000.00', '0.00', '3500.00', '0.00', '35000.00'),
('56', '41', '62', '1', '35000.00', '1750.00', '0.00', '0.00', '35000.00'),
('57', '41', '57', '2', '120000.00', '0.00', '0.00', '240000.00', '240000.00'),
('58', '42', '55', '1', '95000.00', '0.00', '9500.00', '0.00', '95000.00'),
('59', '42', '44', '2', '65000.00', '6500.00', '0.00', '0.00', '130000.00'),
('60', '42', '86', '1', '150000.00', '0.00', '0.00', '150000.00', '150000.00'),
('61', '43', '41', '41', '120000.00', '0.00', '492000.00', '0.00', '4920000.00'),
('62', '44', '41', '41', '120000.00', '0.00', '492000.00', '0.00', '4920000.00'),
('63', '45', '79', '1', '75000.00', '0.00', '7500.00', '0.00', '82500.00'),
('64', '45', '57', '1', '120000.00', '0.00', '12000.00', '0.00', '132000.00'),
('65', '45', '71', '4', '95000.00', '19000.00', '0.00', '0.00', '399000.00'),
('66', '45', '44', '1', '65000.00', '0.00', '0.00', '65000.00', '65000.00'),
('67', '46', '79', '1', '75000.00', '0.00', '7500.00', '0.00', '82500.00'),
('68', '46', '57', '1', '120000.00', '0.00', '12000.00', '0.00', '132000.00'),
('69', '46', '71', '4', '95000.00', '19000.00', '0.00', '0.00', '399000.00'),
('70', '46', '44', '1', '65000.00', '0.00', '0.00', '65000.00', '65000.00'),
('71', '47', '71', '18', '95000.00', '0.00', '171000.00', '0.00', '1881000.00'),
('72', '48', '71', '18', '95000.00', '0.00', '171000.00', '0.00', '1881000.00'),
('73', '49', '62', '1', '35000.00', '1750.00', '0.00', '0.00', '36750.00'),
('74', '50', '71', '1', '95000.00', '0.00', '9500.00', '0.00', '104500.00'),
('75', '51', '49', '3', '145000.00', '0.00', '43500.00', '0.00', '478500.00'),
('76', '51', '49', '1', '145000.00', '0.00', '14500.00', '0.00', '159500.00'),
('77', '51', '36', '1', '80000.00', '0.00', '8000.00', '0.00', '88000.00'),
('78', '51', '46', '1', '220000.00', '0.00', '22000.00', '0.00', '242000.00'),
('79', '52', '49', '1', '145000.00', '0.00', '14500.00', '0.00', '159500.00'),
('80', '52', '70', '1', '50000.00', '0.00', '5000.00', '0.00', '55000.00'),
('81', '52', '65', '3', '120000.00', '0.00', '36000.00', '0.00', '396000.00'),
('82', '52', '49', '12', '145000.00', '87000.00', '0.00', '0.00', '1827000.00'),
('83', '52', '80', '2', '140000.00', '14000.00', '0.00', '0.00', '294000.00'),
('84', '52', '55', '11', '95000.00', '0.00', '0.00', '1045000.00', '1045000.00'),
('85', '53', '49', '1', '145000.00', '0.00', '14500.00', '0.00', '159500.00'),
('86', '54', '58', '23', '180000.00', '0.00', '414000.00', '0.00', '4554000.00');


-- Estructura de tabla `permisos`
DROP TABLE IF EXISTS `permisos`;
CREATE TABLE `permisos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rol` varchar(50) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `puede_ver` tinyint(1) DEFAULT '0',
  `puede_crear` tinyint(1) DEFAULT '0',
  `puede_editar` tinyint(1) DEFAULT '0',
  `puede_eliminar` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rol_modulo` (`rol`,`modulo`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;

-- Volcado de datos de la tabla `permisos`
INSERT INTO `permisos` (`id`, `rol`, `modulo`, `puede_ver`, `puede_crear`, `puede_editar`, `puede_eliminar`) VALUES
('1', 'Administrador', 'clientes', '1', '1', '1', '1'),
('2', 'Administrador', 'proveedores', '1', '1', '1', '1'),
('3', 'Administrador', 'productos', '1', '1', '1', '1'),
('4', 'Administrador', 'caja', '1', '1', '1', '1'),
('5', 'Administrador', 'ventas', '1', '1', '1', '1'),
('6', 'Administrador', 'compras', '1', '1', '1', '1'),
('7', 'Administrador', 'stock', '1', '1', '1', '1'),
('8', 'Administrador', 'auditoria', '1', '1', '1', '1'),
('9', 'Administrador', 'reportes', '1', '1', '1', '1'),
('10', 'Administrador', 'backup', '1', '1', '1', '1'),
('11', 'Administrador', 'facturacion', '1', '1', '1', '1'),
('12', 'Administrador', 'usuarios', '1', '1', '1', '1'),
('13', 'Vendedor', 'ventas', '1', '1', '0', '0'),
('14', 'Vendedor', 'clientes', '1', '1', '1', '0'),
('15', 'Vendedor', 'productos', '1', '0', '0', '0'),
('16', 'Vendedor', 'stock', '1', '0', '0', '0');


-- Estructura de tabla `productos`
DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `cilindrada` varchar(10) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock` int(11) NOT NULL DEFAULT '0',
  `stock_min` int(11) NOT NULL DEFAULT '0',
  `proveedor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `proveedor_id` (`proveedor_id`),
  CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `productos`
INSERT INTO `productos` (`id`, `codigo`, `nombre`, `categoria`, `marca`, `modelo`, `cilindrada`, `precio`, `stock`, `stock_min`, `proveedor_id`, `created_at`) VALUES
('36', 'RE001', 'Aceite motor 10w40', 'Motor', 'Honda', 'CG 125', '125', '80000.00', '94', '10', '1', '2025-09-25 20:00:00'),
('37', 'RE002', 'Filtro aire', 'Motor', 'Honda', 'Titan 150', '150', '25000.00', '150', '15', '1', '2025-09-25 20:00:00'),
('38', 'RE003', 'Filtro aceite', 'Motor', 'Honda', 'CB 200', '200', '30000.00', '120', '12', '2', '2025-09-25 20:00:00'),
('39', 'RE004', 'Pastilla freno delantera', 'Frenos', 'Leopard', 'LE 150', '150', '40000.00', '80', '8', '2', '2025-09-25 20:00:00'),
('40', 'RE005', 'Pastilla freno trasera', 'Frenos', 'Leopard', 'LE 150', '150', '38000.00', '30', '9', '2', '2025-09-25 20:00:00'),
('41', 'RE006', 'Disco freno delantero', 'Frenos', 'Kenton', 'KT 200', '200', '120000.00', '117', '5', '3', '2025-09-25 20:00:00'),
('42', 'RE007', 'Disco freno trasero', 'Frenos', 'Kenton', 'KT 200', '200', '115000.00', '60', '6', '3', '2025-09-25 20:00:00'),
('43', 'RE008', 'Cadena transmision', 'Transmision', 'Honda', 'CG 125', '125', '75000.00', '65', '6', '3', '2025-09-25 20:00:00'),
('44', 'RE009', 'Corona transmision', 'Transmision', 'Honda', 'Titan 150', '150', '65000.00', '56', '7', '3', '2025-09-25 20:00:00'),
('45', 'RE010', 'Kit transmision Riffel', 'Transmision', 'Honda', 'CB1 125', '125', '120000.00', '80', '8', '3', '2025-09-25 20:00:00'),
('46', 'RE011', 'Bateria 12v', 'Electrico', 'Taiga', 'TG 125', '125', '220000.00', '34', '4', '4', '2025-09-25 20:00:00'),
('47', 'RE012', 'Bujia', 'Motor', 'Honda', 'CG 125', '125', '25000.00', '110', '12', '1', '2025-09-25 20:00:00'),
('48', 'RE013', 'Amortiguador trasero', 'Suspension', 'Leopard', 'LE 150', '150', '140000.00', '46', '5', '2', '2025-09-25 20:00:00'),
('49', 'RE014', 'Amortiguador delantero', 'Suspension', 'Kenton', 'KT 200', '200', '145000.00', '33', '5', '3', '2025-09-25 20:00:00'),
('50', 'RE015', 'Neumatico delantero 2.75-18', 'Llantas', 'Honda', 'CB 200', '200', '95000.00', '40', '4', '1', '2025-09-25 20:00:00'),
('51', 'RE016', 'Neumatico trasero 3.00-18', 'Llantas', 'Honda', 'CG 125', '125', '105000.00', '40', '4', '1', '2025-09-25 20:00:00'),
('52', 'RE017', 'Espejo retrovisor', 'Accesorios', 'Kenton', 'KT 200', '200', '45000.00', '90', '9', '2', '2025-09-25 20:00:00'),
('53', 'RE018', 'Manilla embrague', 'Accesorios', 'Honda', 'Titan 150', '150', '38000.00', '80', '8', '1', '2025-09-25 20:00:00'),
('54', 'RE019', 'Manilla freno', 'Accesorios', 'Leopard', 'LE 150', '150', '38000.00', '80', '8', '2', '2025-09-25 20:00:00'),
('55', 'RE020', 'Bomba freno', 'Frenos', 'Honda', 'CB 200', '200', '95000.00', '38', '5', '3', '2025-09-25 20:00:00'),
('56', 'RE021', 'Valvula gasolina', 'Motor', 'Honda', 'CG 125', '125', '35000.00', '100', '10', '1', '2025-09-25 20:00:00'),
('57', 'RE022', 'Carburador', 'Motor', 'Honda', 'Titan 150', '150', '120000.00', '12', '3', '1', '2025-09-25 20:00:00'),
('58', 'RE023', 'Escape completo', 'Motor', 'Honda', 'CB 200', '200', '180000.00', '2', '2', '3', '2025-09-25 20:00:00'),
('59', 'RE024', 'Luces delanteras', 'Electrico', 'Taiga', 'TG 125', '125', '75000.00', '70', '7', '4', '2025-09-25 20:00:00'),
('60', 'RE025', 'Luces traseras', 'Electrico', 'Taiga', 'TG 125', '125', '65000.00', '60', '6', '4', '2025-09-25 20:00:00'),
('61', 'RE026', 'Bocina', 'Electrico', 'Honda', 'CG 125', '125', '35000.00', '89', '9', '1', '2025-09-25 20:00:00'),
('62', 'RE027', 'Claxon', 'Electrico', 'Honda', 'Titan 150', '150', '35000.00', '29', '9', '1', '2025-09-25 20:00:00'),
('63', 'RE028', 'Porta placa', 'Accesorios', 'Leopard', 'LE 150', '150', '25000.00', '100', '10', '2', '2025-09-25 20:00:00'),
('64', 'RE029', 'Porta baul', 'Accesorios', 'Kenton', 'KT 200', '200', '45000.00', '80', '8', '3', '2025-09-25 20:00:00'),
('65', 'RE030', 'Baul trasero', 'Accesorios', 'Honda', 'CB 200', '200', '120000.00', '27', '3', '3', '2025-09-25 20:00:00'),
('66', 'RE031', 'Juego embrague', 'Transmision', 'Honda', 'CG 125', '125', '55000.00', '70', '7', '1', '2025-09-25 20:00:00'),
('67', 'RE032', 'Radiador', 'Motor', 'Honda', 'Titan 150', '150', '140000.00', '20', '2', '1', '2025-09-25 20:00:00'),
('68', 'RE033', 'Termostato', 'Motor', 'Honda', 'CB 200', '200', '30000.00', '60', '6', '3', '2025-09-25 20:00:00'),
('69', 'RE034', 'Juego bujias', 'Motor', 'Honda', 'CG 125', '125', '75000.00', '100', '10', '1', '2025-09-25 20:00:00'),
('70', 'RE035', 'Aceite caja', 'Transmision', 'Honda', 'Titan 150', '150', '50000.00', '78', '8', '1', '2025-09-25 20:00:00'),
('71', 'RE036', 'Correa tiempo', 'Motor', 'Honda', 'CB 200', '200', '95000.00', '-4', '4', '3', '2025-09-25 20:00:00'),
('72', 'RE037', 'Filtro combustible', 'Motor', 'Honda', 'CG 125', '125', '20000.00', '130', '13', '1', '2025-09-25 20:00:00'),
('73', 'RE038', 'Pastilla freno trasera', 'Frenos', 'Honda', 'Titan 150', '150', '38000.00', '90', '9', '1', '2025-09-25 20:00:00'),
('74', 'RE039', 'Kit cadena', 'Transmision', 'Honda', 'CB 200', '200', '85000.00', '50', '5', '3', '2025-09-25 20:00:00'),
('75', 'RE040', 'Llanta rin 18', 'Llantas', 'Honda', 'CG 125', '125', '120000.00', '30', '3', '1', '2025-09-25 20:00:00'),
('76', 'RE041', 'Llanta rin 17', 'Llantas', 'Honda', 'Titan 150', '150', '115000.00', '30', '3', '1', '2025-09-25 20:00:00'),
('77', 'RE042', 'Pastillas freno delanteras', 'Frenos', 'Honda', 'CG 125', '125', '40000.00', '80', '8', '3', '2025-09-25 20:00:00'),
('78', 'RE043', 'Bujia NGK', 'Motor', 'Honda', 'Titan 150', '150', '25000.00', '119', '12', '3', '2025-09-25 20:00:00'),
('79', 'RE044', 'Cadena de transmision', 'Transmision', 'Honda', 'CB 200', '200', '75000.00', '51', '6', '3', '2025-09-25 20:00:00'),
('80', 'RE045', 'Amortiguador trasero', 'Suspension', 'Honda', 'CG 125', '125', '140000.00', '49', '5', '3', '2025-09-25 20:00:00'),
('81', 'RE046', 'Espejos retrovisores', 'Accesorios', 'Honda', 'Titan 150', '150', '45000.00', '90', '9', '3', '2025-09-25 20:00:00'),
('82', 'RE047', 'Filtro de aceite', 'Motor', 'Honda', 'CB 200', '200', '30000.00', '110', '11', '3', '2025-09-25 20:00:00'),
('83', 'RE048', 'Placa de embrague', 'Transmision', 'Honda', 'CG 125', '125', '55000.00', '70', '7', '3', '2025-09-25 20:00:00'),
('84', 'RE049', 'Filtro de combustible', 'Motor', 'Honda', 'Titan 150', '150', '20000.00', '130', '13', '3', '2025-09-25 20:00:00'),
('86', 'RE051', 'Cadena ', 'Transmision', 'Honda', 'CB1', '125', '150000.00', '63', '5', NULL, '2025-10-15 18:22:29');


-- Estructura de tabla `proveedores`
DROP TABLE IF EXISTS `proveedores`;
CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa` varchar(150) NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `proveedores`
INSERT INTO `proveedores` (`id`, `empresa`, `contacto`, `telefono`, `email`, `direccion`, `created_at`) VALUES
('1', 'Motos Mendes S.A.', 'Luis Gonzalez', '0981234567', 'ventas@motosmendes.com.py', 'Ruta Transchaco Km 20, La Paloma', '2025-10-21 09:00:00'),
('2', 'Moto Cave Tuning Racing', 'Maria Perez', '0971345678', 'info@motocave.com.py', 'Acceso Sur casi, Fernando de la Mora 2300, Asunción', '2025-10-21 09:15:00'),
('3', 'JDM Asunción', 'Pedro Lopez', '0972456789', 'contacto@jdmshop.com.py', 'Av. Eusebio Ayala 4183, Asunción', '2025-10-21 09:30:00'),
('4', 'Motopartes Paraguay', 'Ana Martinez', '0982567890', 'ventas@motopartes.com.py', 'Avda. Mariscal López 1234, Asunción', '2025-10-21 09:45:00'),
('5', 'MotoCenter S.A.', 'Carlos Fernandez', '0973678901', 'info@motocenter.com.py', 'Avda. España 4567, Asunción', '2025-10-21 10:00:00'),
('6', 'MotoRep S.R.L.', 'Laura Diaz', '0983789012', 'ventas@motorep.com.py', 'Ruta Mcal. López km 15, San Lorenzo', '2025-10-21 10:15:00'),
('7', 'Motorbike Store', 'Juan Martinez', '0974890123', 'contacto@motorbikestore.com.py', 'Avda. Eusebio Ayala 2345, Asunción', '2025-10-21 10:30:00'),
('8', 'Paraguay Moto Parts', 'Luis Ramirez', '0984901234', 'info@paraguaymotoparts.com.py', 'Avda. España 789, Asunción', '2025-10-21 10:45:00'),
('9', 'Moto Premium', 'Marcos Sanchez', '0975012345', 'ventas@motopremium.com.py', 'Avda. Mariscal López 6789, Asunción', '2025-10-21 11:00:00');


-- Estructura de tabla `sesiones`
DROP TABLE IF EXISTS `sesiones`;
CREATE TABLE `sesiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `fecha_login` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_logout` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- Volcado de datos de la tabla `sesiones`
INSERT INTO `sesiones` (`id`, `usuario_id`, `fecha_login`, `fecha_logout`, `ip_address`) VALUES
('1', '1', '2025-11-04 13:52:13', '2025-11-04 14:02:15', '127.0.0.1'),
('2', '1', '2025-11-04 14:07:41', '2025-11-04 14:32:03', '127.0.0.1'),
('3', '2', '2025-11-04 14:32:13', '2025-11-04 14:32:30', '127.0.0.1'),
('4', '1', '2025-11-04 14:32:34', NULL, '127.0.0.1'),
('5', '1', '2025-11-04 19:02:22', NULL, '127.0.0.1');


-- Estructura de tabla `usuarios`
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `rol` varchar(50) NOT NULL DEFAULT 'Vendedor',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- Volcado de datos de la tabla `usuarios`
INSERT INTO `usuarios` (`id`, `usuario`, `password`, `nombre`, `rol`, `activo`, `created_at`, `updated_at`) VALUES
('1', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Administrador', '1', '2025-11-04 13:52:07', '2025-11-04 13:52:07'),
('2', 'vendedor', '$2y$10$zEkOaRwUnvYKFTGy.JQ6NuPF4NWypquSsNBS94zhqz0rAkvYJLyBO', 'Rafael Espinola Guzman', 'Vendedor', '1', '2025-11-04 14:08:32', '2025-11-04 14:43:05');

