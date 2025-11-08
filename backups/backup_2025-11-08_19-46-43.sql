-- Backup de Base de Datos - Repuestos Doble A
-- Fecha: 2025-11-08 19:46:43

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
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8;

-- Volcado de datos de la tabla `auditoria`
INSERT INTO `auditoria` (`id`, `usuario_id`, `nombre_usuario`, `accion`, `modulo`, `detalle`, `fecha_hora`, `ip_address`) VALUES
('29', '1', 'Administrador (admin)', 'abrir', 'caja', 'Caja abierta con monto inicial: 1.000.000 Gs (ID Caja: 10)', '2025-11-04 22:47:22', '127.0.0.1'),
('30', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000001 creada. Cliente ID: 4, Total: 132.000', '2025-11-04 22:47:34', '127.0.0.1'),
('31', '1', 'Administrador (admin)', 'cerrar', 'caja', 'Caja cerrada - Monto inicial: 1.000.000 Gs | Monto final: 1.132.000 Gs | Saldo: 132.000 Gs | Ingresos: 132.000 Gs | Egresos: 0 Gs (ID Caja: 10)', '2025-11-04 22:47:55', '127.0.0.1'),
('32', '1', 'Administrador (admin)', 'abrir', 'caja', 'Caja abierta con monto inicial: 4 Gs (ID Caja: 11)', '2025-11-04 22:48:07', '127.0.0.1'),
('33', '1', 'Administrador (admin)', 'cerrar', 'caja', 'Caja cerrada - Monto inicial: 4 Gs | Monto final: 132.004 Gs | Saldo: 132.000 Gs | Ingresos: 132.000 Gs | Egresos: 0 Gs (ID Caja: 11)', '2025-11-04 22:48:16', '127.0.0.1'),
('34', '1', 'Administrador (admin)', 'logout', 'sistema', 'Usuario admin cerró sesión', '2025-11-04 22:56:18', '127.0.0.1'),
('35', '1', 'Administrador (admin)', 'login', 'sistema', 'Usuario admin inició sesión', '2025-11-04 22:57:46', '127.0.0.1'),
('36', '1', 'Administrador (admin)', 'abrir', 'caja', 'Caja abierta con monto inicial: 2.000.000 Gs (ID Caja: 12)', '2025-11-04 22:58:12', '127.0.0.1'),
('37', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000061 creada. Cliente ID: 16, Total: 4.279.000', '2025-11-04 22:58:33', '127.0.0.1'),
('38', '1', 'Administrador (admin)', 'login', 'sistema', 'Usuario admin inició sesión', '2025-11-05 16:39:51', '127.0.0.1'),
('39', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000062 creada. Cliente ID: 16, Total: 313.500', '2025-11-05 16:44:14', '127.0.0.1'),
('40', '1', 'Administrador (admin)', 'crear', 'compras', 'Compra #20 creada. Proveedor: Moto Cave Tuning Racing, Total: 145.000', '2025-11-05 16:45:16', '127.0.0.1'),
('41', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000063 creada. Cliente ID: 3, Total: 4.207.500', '2025-11-05 16:47:30', '127.0.0.1'),
('42', '1', 'Administrador (admin)', 'crear', 'backup', 'Backup creado: backup_2025-11-05_16-49-19.sql', '2025-11-05 16:49:19', '127.0.0.1'),
('43', '1', 'Administrador (admin)', 'anular', 'facturacion', 'Factura #001-001-000062 anulada. Motivo: Error de tipeo', '2025-11-05 16:49:48', '127.0.0.1'),
('44', '1', 'Administrador (admin)', 'logout', 'sistema', 'Usuario admin cerró sesión', '2025-11-05 16:50:47', '127.0.0.1'),
('45', '2', 'Rafael Espinola Guzman (vendedor)', 'login', 'sistema', 'Usuario vendedor inició sesión', '2025-11-05 16:50:52', '127.0.0.1'),
('46', '2', 'Rafael Espinola Guzman (vendedor)', 'logout', 'sistema', 'Usuario vendedor cerró sesión', '2025-11-05 16:51:17', '127.0.0.1'),
('47', '1', 'Administrador (admin)', 'login', 'sistema', 'Usuario admin inició sesión', '2025-11-05 16:51:23', '127.0.0.1'),
('48', '1', 'Administrador (admin)', 'logout', 'sistema', 'Usuario admin cerró sesión', '2025-11-05 16:52:21', '127.0.0.1'),
('49', '1', 'Administrador (admin)', 'login', 'sistema', 'Usuario admin inició sesión', '2025-11-07 12:51:54', '127.0.0.1'),
('50', '2', 'Rafael Espinola Guzman (vendedor)', 'login', 'sistema', 'Usuario vendedor inició sesión', '2025-11-07 13:58:31', '192.168.1.249'),
('51', '2', 'Rafael Espinola Guzman (vendedor)', 'login', 'sistema', 'Usuario vendedor inició sesión', '2025-11-07 14:04:43', '127.0.0.1'),
('52', '2', 'Rafael Espinola Guzman (vendedor)', 'logout', 'sistema', 'Usuario vendedor cerró sesión', '2025-11-07 14:04:49', '127.0.0.1'),
('53', '1', 'Administrador (admin)', 'login', 'sistema', 'Usuario admin inició sesión', '2025-11-07 14:04:55', '127.0.0.1'),
('54', '1', 'Administrador (admin)', 'login', 'sistema', 'Usuario admin inició sesión', '2025-11-07 14:05:54', '127.0.0.1'),
('55', '2', 'Rafael Espinola Guzman (vendedor)', 'logout', 'sistema', 'Usuario vendedor cerró sesión', '2025-11-07 14:08:52', '192.168.1.249'),
('56', '1', 'Administrador (admin)', 'login', 'sistema', 'Usuario admin inició sesión', '2025-11-07 14:09:03', '192.168.1.249'),
('57', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000064 creada. Cliente ID: 2, Total: 154.000', '2025-11-07 14:09:43', '192.168.1.249'),
('58', '1', 'Administrador (admin)', 'logout', 'sistema', 'Usuario admin cerró sesión', '2025-11-07 14:09:59', '192.168.1.249'),
('59', '1', 'Administrador (admin)', 'logout', 'sistema', 'Usuario admin cerró sesión', '2025-11-07 14:19:07', '127.0.0.1'),
('60', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000065 creada. Cliente ID: 3, Total: 154.000', '2025-11-07 14:27:03', '127.0.0.1'),
('61', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000066 creada. Cliente ID: 16, Total: 132.000', '2025-11-07 14:28:03', '127.0.0.1'),
('62', '1', 'Administrador (admin)', 'crear', 'compras', 'Compra #21 creada. Proveedor: Motopartes Paraguay, Total: 2.400.000', '2025-11-07 14:29:53', '127.0.0.1'),
('63', '1', 'Administrador (admin)', 'crear', 'compras', 'Compra #22 creada. Proveedor: MotoCenter S.A., Total: 240.000', '2025-11-07 14:30:10', '127.0.0.1'),
('64', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000067 creada. Cliente ID: 3, Total: 152.727', '2025-11-07 14:36:36', '127.0.0.1'),
('65', '1', 'Administrador (admin)', 'crear', 'compras', 'Compra #23 creada. Proveedor: MotoCenter S.A., Total: 158.182', '2025-11-07 14:46:20', '127.0.0.1'),
('66', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000068 creada. Cliente ID: 16, Total: 436.363', '2025-11-07 14:50:46', '127.0.0.1'),
('67', '1', 'Administrador (admin)', 'crear', 'compras', 'Compra #24 creada. Proveedor: Moto Premium, Total: 486.840', '2025-11-07 14:53:49', '127.0.0.1'),
('68', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000069 creada. Cliente ID: 16, Total: 152.727', '2025-11-07 14:58:12', '127.0.0.1'),
('69', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000070 creada. Cliente ID: 16, Total: 81.818', '2025-11-07 15:02:00', '127.0.0.1'),
('70', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000071 creada. Cliente ID: 3, Total: 228.008', '2025-11-07 15:02:25', '127.0.0.1'),
('71', '1', 'Administrador (admin)', 'crear', 'compras', 'Compra #25 creada. Proveedor: Moto Cave Tuning Racing, Total: 87.273', '2025-11-07 15:02:46', '127.0.0.1'),
('72', '1', 'Administrador (admin)', 'crear', 'compras', 'Compra #26 creada. Proveedor: Moto Cave Tuning Racing, Total: 499.913', '2025-11-07 15:03:22', '127.0.0.1'),
('73', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000072 creada. Cliente ID: 7, Total: 1.707.273', '2025-11-07 15:04:45', '127.0.0.1'),
('74', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000073 creada. Cliente ID: 5, Total: 1.418.182', '2025-11-07 15:05:24', '127.0.0.1'),
('75', '1', 'Administrador (admin)', 'cerrar', 'caja', 'Caja cerrada - Monto inicial: 2.000.000 Gs | Monto final: 3.183.974 Gs | Saldo: 1.183.974 Gs | Ingresos: 13.235.598 Gs | Egresos: 12.051.624 Gs (ID Caja: 12)', '2025-11-07 15:07:42', '127.0.0.1'),
('76', '1', 'Administrador (admin)', 'login', 'sistema', 'Usuario admin inició sesión', '2025-11-08 19:25:01', '127.0.0.1'),
('77', '1', 'Administrador (admin)', 'abrir', 'caja', 'Caja abierta con monto inicial: 5.000.000 Gs (ID Caja: 13)', '2025-11-08 19:34:16', '127.0.0.1'),
('78', '1', 'Administrador (admin)', 'anular', 'facturacion', 'Factura #001-001-000071 anulada. Motivo: error de tipeo', '2025-11-08 19:34:37', '127.0.0.1'),
('79', '1', 'Administrador (admin)', 'crear', 'ventas', 'Factura #001-001-000074 creada. Cliente ID: 5, Total: 240.000', '2025-11-08 19:35:21', '127.0.0.1');


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
  `inicio_vigencia` date DEFAULT NULL,
  `fin_vigencia` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_factura` (`numero_factura`),
  KEY `idx_fecha` (`fecha_hora`),
  KEY `idx_proveedor` (`proveedor_id`),
  CONSTRAINT `cabecera_factura_compras_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- Volcado de datos de la tabla `cabecera_factura_compras`
INSERT INTO `cabecera_factura_compras` (`id`, `numero_factura`, `proveedor_id`, `fecha_hora`, `monto_total`, `condicion_compra`, `forma_pago`, `timbrado`, `numero_factura_proveedor`, `created_at`, `inicio_vigencia`, `fin_vigencia`) VALUES
('1', '001-001-000050', '2', '2025-11-05 16:45:16', '145000.00', 'Contado', 'Efectivo', '84513122', '', '2025-11-05 16:45:16', '2025-01-01', '2025-12-31'),
('2', '001-001-000006', '4', '2025-11-07 14:29:53', '2400000.00', 'Contado', 'Efectivo', '27692657', '', '2025-11-07 14:29:53', '2025-01-01', '2025-12-31'),
('3', '001-001-000059', '5', '2025-11-07 14:30:10', '240000.00', 'Contado', 'Efectivo', '91812438', '', '2025-11-07 14:30:10', '2025-01-01', '2025-12-31'),
('4', '001-001-000100', '5', '2025-11-07 14:46:20', '158182.00', 'Contado', 'Efectivo', '03296142', '', '2025-11-07 14:46:20', '2025-01-01', '2025-12-31'),
('5', '001-001-000008', '9', '2025-11-07 14:53:49', '486840.00', 'Contado', 'Efectivo', '98616271', '', '2025-11-07 14:53:49', '2025-01-01', '2025-12-31'),
('6', '001-001-000082', '2', '2025-11-07 15:02:46', '87273.00', 'Contado', 'Efectivo', '68011108', '', '2025-11-07 15:02:46', '2025-01-01', '2025-12-31'),
('7', '001-001-000016', '2', '2025-11-07 15:03:22', '499913.00', 'Contado', 'Efectivo', '56500183', '', '2025-11-07 15:03:22', '2025-01-01', '2025-12-31');


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
) ENGINE=MyISAM AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `cabecera_factura_ventas`
INSERT INTO `cabecera_factura_ventas` (`id`, `numero_factura`, `condicion_venta`, `forma_pago`, `fecha_hora`, `cliente_id`, `monto_total`, `timbrado`, `inicio_vigencia`, `fin_vigencia`, `anulada`, `fecha_anulacion`, `usuario_anulacion_id`, `motivo_anulacion`) VALUES
('60', '001-001-000001', 'Contado', 'Efectivo', '2025-11-04 22:47:34', '4', '132000.00', '4571575', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('61', '001-001-000061', 'Contado', 'Efectivo', '2025-11-04 22:58:33', '16', '4279000.00', '4571576', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('62', '001-001-000062', 'Contado', 'Efectivo', '2025-11-05 16:44:14', '16', '313500.00', '4571577', '2025-01-01', '2025-12-31', '1', '2025-11-05 16:49:48', '1', 'Error de tipeo'),
('63', '001-001-000063', 'Contado', 'Efectivo', '2025-11-05 16:47:30', '3', '4207500.00', '4571578', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('64', '001-001-000064', 'Contado', 'Efectivo', '2025-11-07 14:09:43', '2', '154000.00', '4571579', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('65', '001-001-000065', 'Crédito', 'Tarjeta', '2025-11-07 14:27:03', '3', '154000.00', '4571580', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('66', '001-001-000066', 'Crédito', 'Tarjeta', '2025-11-07 14:28:03', '16', '132000.00', '4571581', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('67', '001-001-000067', 'Crédito', 'Tarjeta', '2025-11-07 14:36:36', '3', '152727.00', '4571582', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('68', '001-001-000068', 'Crédito', 'Tarjeta', '2025-11-07 14:50:46', '16', '436363.00', '4571583', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('69', '001-001-000069', 'Contado', 'Efectivo', '2025-11-07 14:58:12', '16', '152727.00', '4571584', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('70', '001-001-000070', 'Contado', 'Efectivo', '2025-11-07 15:02:00', '16', '81818.00', '4571585', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('71', '001-001-000071', 'Contado', 'Efectivo', '2025-11-07 15:02:25', '3', '228008.00', '4571586', '2025-01-01', '2025-12-31', '1', '2025-11-08 19:34:37', '1', 'error de tipeo'),
('72', '001-001-000072', 'Contado', 'Efectivo', '2025-11-07 15:04:45', '7', '1707273.00', '4571587', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('73', '001-001-000073', 'Contado', 'Efectivo', '2025-11-07 15:05:24', '5', '1418182.00', '4571588', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL),
('74', '001-001-000074', 'Crédito', 'Tarjeta', '2025-11-08 19:35:21', '5', '240000.00', '4571589', '2025-01-01', '2025-12-31', '0', NULL, NULL, NULL);


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
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `caja`
INSERT INTO `caja` (`id`, `fecha`, `monto_inicial`, `monto_final`, `estado`, `created_at`) VALUES
('10', '2025-11-04', '1000000.00', '1132000.00', 'Cerrada', '2025-11-04 22:47:22'),
('11', '2025-11-04', '4.00', '132004.00', 'Cerrada', '2025-11-04 22:48:07'),
('12', '2025-11-04', '2000000.00', '3183974.00', 'Cerrada', '2025-11-04 22:58:12'),
('13', '2025-11-08', '5000000.00', NULL, 'Abierta', '2025-11-08 19:34:16');


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
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `caja_movimientos`
INSERT INTO `caja_movimientos` (`id`, `caja_id`, `fecha`, `tipo`, `concepto`, `monto`, `created_at`) VALUES
('25', '12', '2025-11-05 16:45:16', 'Egreso', 'Compra a proveedor ID 2, compra ID 20', '145000.00', '2025-11-05 16:45:16'),
('26', '12', '2025-11-07 14:29:53', 'Egreso', 'Compra a proveedor ID 4, compra ID 21', '2400000.00', '2025-11-07 14:29:53'),
('27', '12', '2025-11-07 14:30:10', 'Egreso', 'Compra a proveedor ID 5, compra ID 22', '240000.00', '2025-11-07 14:30:10'),
('28', '12', '2025-11-07 14:46:20', 'Egreso', 'Compra a proveedor ID 5, compra ID 23', '158182.00', '2025-11-07 14:46:20'),
('29', '12', '2025-11-07 14:53:49', 'Egreso', 'Compra a proveedor ID 9, compra ID 24', '486840.00', '2025-11-07 14:53:49'),
('30', '12', '2025-11-07 15:02:46', 'Egreso', 'Compra a proveedor ID 2, compra ID 25', '87273.00', '2025-11-07 15:02:46'),
('31', '12', '2025-11-07 15:03:22', 'Egreso', 'Compra a proveedor ID 2, compra ID 26', '499913.00', '2025-11-07 15:03:22');


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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4;

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
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `compras`
INSERT INTO `compras` (`id`, `proveedor_id`, `fecha`, `total`, `estado`, `created_at`) VALUES
('20', '2', '2025-11-05 16:45:16', '145000.00', 'Pendiente', '2025-11-05 16:45:16'),
('21', '4', '2025-11-07 14:29:53', '2400000.00', 'Pendiente', '2025-11-07 14:29:53'),
('22', '5', '2025-11-07 14:30:10', '240000.00', 'Pendiente', '2025-11-07 14:30:10'),
('23', '5', '2025-11-07 14:46:20', '158182.00', 'Pendiente', '2025-11-07 14:46:20'),
('24', '9', '2025-11-07 14:53:49', '486840.00', 'Pendiente', '2025-11-07 14:53:49'),
('25', '2', '2025-11-07 15:02:46', '87273.00', 'Pendiente', '2025-11-07 15:02:46'),
('26', '2', '2025-11-07 15:03:22', '499913.00', 'Pendiente', '2025-11-07 15:03:22');


-- Estructura de tabla `cuotas_credito`
DROP TABLE IF EXISTS `cuotas_credito`;
CREATE TABLE `cuotas_credito` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venta_credito_id` int(11) NOT NULL,
  `numero_cuota` int(11) NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `fecha_pago` datetime DEFAULT NULL,
  `monto_pagado` decimal(15,2) DEFAULT '0.00',
  `estado` enum('Pendiente','Pagada','Vencida','Cancelada') DEFAULT 'Pendiente',
  `observaciones` text,
  PRIMARY KEY (`id`),
  KEY `idx_venta_credito` (`venta_credito_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_vencimiento` (`fecha_vencimiento`),
  CONSTRAINT `cuotas_credito_ibfk_1` FOREIGN KEY (`venta_credito_id`) REFERENCES `ventas_credito` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `cuotas_credito`

-- Estructura de tabla `detalle_factura_compras`
DROP TABLE IF EXISTS `detalle_factura_compras`;
CREATE TABLE `detalle_factura_compras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `factura_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `valor_compra_10` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Valor con IVA 10%',
  `valor_compra_exenta` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Valor exento de IVA',
  `valor_compra_5` decimal(15,2) DEFAULT '0.00' COMMENT 'Valor con IVA 5%',
  PRIMARY KEY (`id`),
  KEY `idx_factura` (`factura_id`),
  KEY `idx_producto` (`producto_id`),
  CONSTRAINT `detalle_factura_compras_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `cabecera_factura_compras` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detalle_factura_compras_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;

-- Volcado de datos de la tabla `detalle_factura_compras`
INSERT INTO `detalle_factura_compras` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`, `valor_compra_10`, `valor_compra_exenta`, `valor_compra_5`) VALUES
('1', '1', '49', '1.00', '145000.00', '145000.00', '145000.00', '0.00', '0.00'),
('2', '2', '75', '20.00', '120000.00', '2618182.00', '218182.00', '0.00', '0.00'),
('3', '3', '46', '1.00', '220000.00', '240000.00', '20000.00', '0.00', '0.00'),
('4', '4', '49', '1.00', '145000.00', '158182.00', '13182.00', '0.00', '0.00'),
('5', '5', '48', '1.00', '140000.00', '152727.00', '12727.00', '0.00', '0.00'),
('6', '5', '55', '1.00', '95000.00', '103637.00', '8637.00', '0.00', '0.00'),
('7', '5', '46', '1.00', '220000.00', '230476.00', '0.00', '0.00', '10476.00'),
('8', '6', '36', '1.00', '80000.00', '87273.00', '7273.00', '0.00', '0.00'),
('9', '7', '49', '1.00', '145000.00', '158182.00', '13182.00', '0.00', '0.00'),
('10', '7', '49', '1.00', '145000.00', '151905.00', '0.00', '0.00', '6905.00'),
('11', '7', '47', '1.00', '25000.00', '26190.00', '0.00', '0.00', '1190.00'),
('12', '7', '86', '1.00', '150000.00', '163636.00', '13636.00', '0.00', '0.00');


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
) ENGINE=MyISAM AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `detalle_factura_ventas`
INSERT INTO `detalle_factura_ventas` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `valor_venta_5`, `valor_venta_10`, `valor_venta_exenta`, `total_parcial`) VALUES
('93', '60', '65', '1', '120000.00', '0.00', '12000.00', '0.00', '132000.00'),
('94', '61', '47', '50', '25000.00', '0.00', '125000.00', '0.00', '1375000.00'),
('95', '61', '46', '12', '220000.00', '0.00', '264000.00', '0.00', '2904000.00'),
('96', '62', '49', '1', '145000.00', '0.00', '14500.00', '0.00', '159500.00'),
('97', '62', '80', '1', '140000.00', '0.00', '14000.00', '0.00', '154000.00'),
('98', '63', '74', '45', '85000.00', '0.00', '382500.00', '0.00', '4207500.00'),
('99', '64', '80', '1', '140000.00', '0.00', '14000.00', '0.00', '154000.00'),
('100', '65', '48', '1', '140000.00', '0.00', '14000.00', '0.00', '154000.00'),
('101', '66', '65', '1', '120000.00', '0.00', '12000.00', '0.00', '132000.00'),
('102', '67', '48', '1', '140000.00', '0.00', '12727.00', '0.00', '152727.00'),
('103', '68', '80', '1', '140000.00', '0.00', '12727.00', '0.00', '152727.00'),
('104', '68', '65', '1', '120000.00', '0.00', '10909.00', '0.00', '130909.00'),
('105', '68', '48', '1', '140000.00', '0.00', '12727.00', '0.00', '152727.00'),
('106', '69', '48', '1', '140000.00', '0.00', '12727.00', '0.00', '152727.00'),
('107', '70', '43', '1', '75000.00', '0.00', '6818.00', '0.00', '81818.00'),
('108', '71', '43', '1', '75000.00', '0.00', '6818.00', '0.00', '81818.00'),
('109', '71', '43', '1', '75000.00', '0.00', '6818.00', '0.00', '81818.00'),
('110', '71', '62', '1', '35000.00', '0.00', '3182.00', '0.00', '38182.00'),
('111', '71', '47', '1', '25000.00', '1190.00', '0.00', '0.00', '26190.00'),
('112', '72', '48', '1', '140000.00', '0.00', '12727.00', '0.00', '152727.00'),
('113', '72', '55', '15', '95000.00', '0.00', '129546.00', '0.00', '1554546.00'),
('114', '73', '44', '20', '65000.00', '0.00', '118182.00', '0.00', '1418182.00'),
('115', '74', '46', '1', '220000.00', '0.00', '20000.00', '0.00', '240000.00');


-- Estructura de tabla `pagares`
DROP TABLE IF EXISTS `pagares`;
CREATE TABLE `pagares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venta_credito_id` int(11) NOT NULL,
  `numero_pagare` varchar(50) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `monto_total` decimal(15,2) NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `lugar_pago` varchar(255) DEFAULT 'San Ignacio, Paraguay',
  `estado` enum('Vigente','Cancelado','Vencido') DEFAULT 'Vigente',
  `observaciones` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_pagare` (`numero_pagare`),
  KEY `idx_venta_credito` (`venta_credito_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `pagares_ibfk_1` FOREIGN KEY (`venta_credito_id`) REFERENCES `ventas_credito` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pagares_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `pagares`

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
('36', 'RE001', 'Aceite motor 10w40', 'Motor', 'Honda', 'CG 125', '125', '80000.00', '95', '10', '1', '2025-09-25 20:00:00'),
('37', 'RE002', 'Filtro aire', 'Motor', 'Honda', 'Titan 150', '150', '25000.00', '150', '15', '1', '2025-09-25 20:00:00'),
('38', 'RE003', 'Filtro aceite', 'Motor', 'Honda', 'CB 200', '200', '30000.00', '153', '12', '2', '2025-09-25 20:00:00'),
('39', 'RE004', 'Pastilla freno delantera', 'Frenos', 'Leopard', 'LE 150', '150', '40000.00', '80', '8', '2', '2025-09-25 20:00:00'),
('40', 'RE005', 'Pastilla freno trasera', 'Frenos', 'Leopard', 'LE 150', '150', '38000.00', '30', '9', '2', '2025-09-25 20:00:00'),
('41', 'RE006', 'Disco freno delantero', 'Frenos', 'Kenton', 'KT 200', '200', '120000.00', '156', '5', '3', '2025-09-25 20:00:00'),
('42', 'RE007', 'Disco freno trasero', 'Frenos', 'Kenton', 'KT 200', '200', '115000.00', '60', '6', '3', '2025-09-25 20:00:00'),
('43', 'RE008', 'Cadena transmision', 'Transmision', 'Honda', 'CG 125', '125', '75000.00', '64', '6', '3', '2025-09-25 20:00:00'),
('44', 'RE009', 'Corona transmision', 'Transmision', 'Honda', 'Titan 150', '150', '65000.00', '36', '7', '3', '2025-09-25 20:00:00'),
('45', 'RE010', 'Kit transmision Riffel', 'Transmision', 'Honda', 'CB1 125', '125', '120000.00', '80', '8', '3', '2025-09-25 20:00:00'),
('46', 'RE011', 'Bateria 12v', 'Electrico', 'Taiga', 'TG 125', '125', '220000.00', '23', '4', '4', '2025-09-25 20:00:00'),
('47', 'RE012', 'Bujia', 'Motor', 'Honda', 'CG 125', '125', '25000.00', '61', '12', '1', '2025-09-25 20:00:00'),
('48', 'RE013', 'Amortiguador trasero', 'Suspension', 'Leopard', 'LE 150', '150', '140000.00', '44', '5', '2', '2025-09-25 20:00:00'),
('49', 'RE014', 'Amortiguador delantero', 'Suspension', 'Kenton', 'KT 200', '200', '145000.00', '57', '5', '3', '2025-09-25 20:00:00'),
('50', 'RE015', 'Neumatico delantero 2.75-18', 'Llantas', 'Honda', 'CB 200', '200', '95000.00', '40', '4', '1', '2025-09-25 20:00:00'),
('51', 'RE016', 'Neumatico trasero 3.00-18', 'Llantas', 'Honda', 'CG 125', '125', '105000.00', '40', '4', '1', '2025-09-25 20:00:00'),
('52', 'RE017', 'Espejo retrovisor', 'Accesorios', 'Kenton', 'KT 200', '200', '45000.00', '90', '9', '2', '2025-09-25 20:00:00'),
('53', 'RE018', 'Manilla embrague', 'Accesorios', 'Honda', 'Titan 150', '150', '38000.00', '80', '8', '1', '2025-09-25 20:00:00'),
('54', 'RE019', 'Manilla freno', 'Accesorios', 'Leopard', 'LE 150', '150', '38000.00', '80', '8', '2', '2025-09-25 20:00:00'),
('55', 'RE020', 'Bomba freno', 'Frenos', 'Honda', 'CB 200', '200', '95000.00', '24', '5', '3', '2025-09-25 20:00:00'),
('56', 'RE021', 'Valvula gasolina', 'Motor', 'Honda', 'CG 125', '125', '35000.00', '100', '10', '1', '2025-09-25 20:00:00'),
('57', 'RE022', 'Carburador', 'Motor', 'Honda', 'Titan 150', '150', '120000.00', '12', '3', '1', '2025-09-25 20:00:00'),
('58', 'RE023', 'Escape completo', 'Motor', 'Honda', 'CB 200', '200', '180000.00', '67', '2', '3', '2025-09-25 20:00:00'),
('59', 'RE024', 'Luces delanteras', 'Electrico', 'Taiga', 'TG 125', '125', '75000.00', '70', '7', '4', '2025-09-25 20:00:00'),
('60', 'RE025', 'Luces traseras', 'Electrico', 'Taiga', 'TG 125', '125', '65000.00', '60', '6', '4', '2025-09-25 20:00:00'),
('61', 'RE026', 'Bocina', 'Electrico', 'Honda', 'CG 125', '125', '35000.00', '88', '9', '1', '2025-09-25 20:00:00'),
('62', 'RE027', 'Claxon', 'Electrico', 'Honda', 'Titan 150', '150', '35000.00', '29', '9', '1', '2025-09-25 20:00:00'),
('63', 'RE028', 'Porta placa', 'Accesorios', 'Leopard', 'LE 150', '150', '25000.00', '100', '10', '2', '2025-09-25 20:00:00'),
('64', 'RE029', 'Porta baul', 'Accesorios', 'Kenton', 'KT 200', '200', '45000.00', '80', '8', '3', '2025-09-25 20:00:00'),
('65', 'RE030', 'Baul trasero', 'Accesorios', 'Honda', 'CB 200', '200', '120000.00', '24', '3', '3', '2025-09-25 20:00:00'),
('66', 'RE031', 'Juego embrague', 'Transmision', 'Honda', 'CG 125', '125', '55000.00', '70', '7', '1', '2025-09-25 20:00:00'),
('67', 'RE032', 'Radiador', 'Motor', 'Honda', 'Titan 150', '150', '140000.00', '20', '2', '1', '2025-09-25 20:00:00'),
('68', 'RE033', 'Termostato', 'Motor', 'Honda', 'CB 200', '200', '30000.00', '60', '6', '3', '2025-09-25 20:00:00'),
('69', 'RE034', 'Juego bujias', 'Motor', 'Honda', 'CG 125', '125', '75000.00', '100', '10', '1', '2025-09-25 20:00:00'),
('70', 'RE035', 'Aceite caja', 'Transmision', 'Honda', 'Titan 150', '150', '50000.00', '79', '8', '1', '2025-09-25 20:00:00'),
('71', 'RE036', 'Correa tiempo', 'Motor', 'Honda', 'CB 200', '200', '95000.00', '196', '4', '3', '2025-09-25 20:00:00'),
('72', 'RE037', 'Filtro combustible', 'Motor', 'Honda', 'CG 125', '125', '20000.00', '130', '13', '1', '2025-09-25 20:00:00'),
('73', 'RE038', 'Pastilla freno trasera', 'Frenos', 'Honda', 'Titan 150', '150', '38000.00', '90', '9', '1', '2025-09-25 20:00:00'),
('74', 'RE039', 'Kit cadena', 'Transmision', 'Honda', 'CB 200', '200', '85000.00', '5', '5', '3', '2025-09-25 20:00:00'),
('75', 'RE040', 'Llanta rin 18', 'Llantas', 'Honda', 'CG 125', '125', '120000.00', '50', '3', '1', '2025-09-25 20:00:00'),
('76', 'RE041', 'Llanta rin 17', 'Llantas', 'Honda', 'Titan 150', '150', '115000.00', '30', '3', '1', '2025-09-25 20:00:00'),
('77', 'RE042', 'Pastillas freno delanteras', 'Frenos', 'Honda', 'CG 125', '125', '40000.00', '80', '8', '3', '2025-09-25 20:00:00'),
('78', 'RE043', 'Bujia NGK', 'Motor', 'Honda', 'Titan 150', '150', '25000.00', '131', '12', '3', '2025-09-25 20:00:00'),
('79', 'RE044', 'Cadena de transmision', 'Transmision', 'Honda', 'CB 200', '200', '75000.00', '53', '6', '3', '2025-09-25 20:00:00'),
('80', 'RE045', 'Amortiguador trasero', 'Suspension', 'Honda', 'CG 125', '125', '140000.00', '60', '5', '3', '2025-09-25 20:00:00'),
('81', 'RE046', 'Espejos retrovisores', 'Accesorios', 'Honda', 'Titan 150', '150', '45000.00', '90', '9', '3', '2025-09-25 20:00:00'),
('82', 'RE047', 'Filtro de aceite', 'Motor', 'Honda', 'CB 200', '200', '30000.00', '110', '11', '3', '2025-09-25 20:00:00'),
('83', 'RE048', 'Placa de embrague', 'Transmision', 'Honda', 'CG 125', '125', '55000.00', '70', '7', '3', '2025-09-25 20:00:00'),
('84', 'RE049', 'Filtro de combustible', 'Motor', 'Honda', 'Titan 150', '150', '20000.00', '130', '13', '3', '2025-09-25 20:00:00'),
('86', 'RE051', 'Cadena ', 'Transmision', 'Honda', 'CB1', '125', '150000.00', '64', '5', NULL, '2025-10-15 18:22:29');


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
  `ruc` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `proveedores`
INSERT INTO `proveedores` (`id`, `empresa`, `contacto`, `telefono`, `email`, `direccion`, `created_at`, `ruc`) VALUES
('1', 'Motos Mendes S.A.', 'Luis Gonzalez', '0981234567', 'ventas@motosmendes.com.py', 'Ruta Transchaco Km 20, La Paloma', '2025-10-21 09:00:00', '457412-1'),
('2', 'Moto Cave Tuning Racing', 'Maria Perez', '0971345678', 'info@motocave.com.py', 'Acceso Sur casi, Fernando de la Mora 2300, Asunción', '2025-10-21 09:15:00', '457412-2'),
('3', 'JDM Asuncion', 'Pedro Lopez', '0972456789', 'contacto@jdmshop.com.py', 'Av. Eusebio Ayala 4183, Asunción', '2025-10-21 09:30:00', '457412-3'),
('4', 'Motopartes Paraguay', 'Ana Martinez', '0982567890', 'ventas@motopartes.com.py', 'Avda. Mariscal López 1234, Asunción', '2025-10-21 09:45:00', '457412-4'),
('5', 'MotoCenter S.A.', 'Carlos Fernandez', '0973678901', 'info@motocenter.com.py', 'Avda. España 4567, Asunción', '2025-10-21 10:00:00', '457412-5'),
('6', 'MotoRep S.R.L.', 'Laura Diaz', '0983789012', 'ventas@motorep.com.py', 'Ruta Mcal. López km 15, San Lorenzo', '2025-10-21 10:15:00', '457412-6'),
('7', 'Motorbike Store', 'Juan Martinez', '0974890123', 'contacto@motorbikestore.com.py', 'Avda. Eusebio Ayala 2345, Asunción', '2025-10-21 10:30:00', '654542-7'),
('8', 'Paraguay Moto Parts', 'Luis Ramirez', '0984901234', 'info@paraguaymotoparts.com.py', 'Avda. España 789, Asunción', '2025-10-21 10:45:00', '957412-4'),
('9', 'Moto Premium', 'Marcos Sanchez', '0975012345', 'ventas@motopremium.com.py', 'Avda. Mariscal López 6789, Asunción', '2025-10-21 11:00:00', '257412-8');


-- Estructura de tabla `recibos_dinero`
DROP TABLE IF EXISTS `recibos_dinero`;
CREATE TABLE `recibos_dinero` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_recibo` varchar(50) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `venta_credito_id` int(11) DEFAULT NULL,
  `cuota_id` int(11) DEFAULT NULL,
  `monto` decimal(15,2) NOT NULL,
  `fecha_pago` datetime NOT NULL,
  `forma_pago` enum('Efectivo','Tarjeta','Transferencia','Cheque') DEFAULT 'Efectivo',
  `concepto` varchar(255) DEFAULT NULL,
  `observaciones` text,
  `usuario_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_recibo` (`numero_recibo`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_venta_credito` (`venta_credito_id`),
  KEY `idx_cuota` (`cuota_id`),
  KEY `idx_fecha` (`fecha_pago`),
  CONSTRAINT `recibos_dinero_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recibos_dinero_ibfk_2` FOREIGN KEY (`venta_credito_id`) REFERENCES `ventas_credito` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recibos_dinero_ibfk_3` FOREIGN KEY (`cuota_id`) REFERENCES `cuotas_credito` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `recibos_dinero`

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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;

-- Volcado de datos de la tabla `sesiones`
INSERT INTO `sesiones` (`id`, `usuario_id`, `fecha_login`, `fecha_logout`, `ip_address`) VALUES
('1', '1', '2025-11-04 13:52:13', '2025-11-04 14:02:15', '127.0.0.1'),
('2', '1', '2025-11-04 14:07:41', '2025-11-04 14:32:03', '127.0.0.1'),
('3', '2', '2025-11-04 14:32:13', '2025-11-04 14:32:30', '127.0.0.1'),
('4', '1', '2025-11-04 14:32:34', NULL, '127.0.0.1'),
('5', '1', '2025-11-04 19:02:22', '2025-11-04 22:56:18', '127.0.0.1'),
('6', '1', '2025-11-04 22:57:46', NULL, '127.0.0.1'),
('7', '1', '2025-11-05 16:39:51', '2025-11-05 16:50:47', '127.0.0.1'),
('8', '2', '2025-11-05 16:50:52', '2025-11-05 16:51:17', '127.0.0.1'),
('9', '1', '2025-11-05 16:51:23', '2025-11-05 16:52:21', '127.0.0.1'),
('10', '1', '2025-11-07 12:51:54', NULL, '127.0.0.1'),
('11', '2', '2025-11-07 13:58:31', '2025-11-07 14:08:52', '192.168.1.249'),
('12', '2', '2025-11-07 14:04:43', '2025-11-07 14:04:49', '127.0.0.1'),
('13', '1', '2025-11-07 14:04:55', '2025-11-07 14:19:07', '127.0.0.1'),
('14', '1', '2025-11-07 14:05:54', NULL, '127.0.0.1'),
('15', '1', '2025-11-07 14:09:03', '2025-11-07 14:09:59', '192.168.1.249'),
('16', '1', '2025-11-08 19:25:01', NULL, '127.0.0.1');


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
('1', 'admin', '$2y$10$/4/LkhAlRUihMjSizr0FMujX9LhCLlOHNY/3nVRjfZ8zNfZOZ4jg2', 'Administrador', 'Administrador', '1', '2025-11-04 13:52:07', '2025-11-07 14:18:34'),
('2', 'vendedor', '$2y$10$zEkOaRwUnvYKFTGy.JQ6NuPF4NWypquSsNBS94zhqz0rAkvYJLyBO', 'Rafael Espinola Guzman', 'Vendedor', '1', '2025-11-04 14:08:32', '2025-11-04 14:43:05');


-- Estructura de tabla `ventas_credito`
DROP TABLE IF EXISTS `ventas_credito`;
CREATE TABLE `ventas_credito` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `factura_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `monto_total` decimal(15,2) NOT NULL,
  `numero_cuotas` int(11) NOT NULL,
  `monto_cuota` decimal(15,2) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` enum('Activa','Cancelada','Finalizada') DEFAULT 'Activa',
  `fecha_finalizacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_factura` (`factura_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `ventas_credito_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `cabecera_factura_ventas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ventas_credito_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

-- Volcado de datos de la tabla `ventas_credito`
