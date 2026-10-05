-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 05-10-2026 a las 05:28:59
-- Versión del servidor: 11.8.9-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u118470389_PruebaHpanel`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradores`
--

CREATE TABLE `administradores` (
  `id_admin` int(10) UNSIGNED NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'password_hash() de PHP (bcrypt)',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_acceso` datetime DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios del panel de administración';

--
-- Volcado de datos para la tabla `administradores`
--

INSERT INTO `administradores` (`id_admin`, `usuario`, `nombre`, `password_hash`, `activo`, `ultimo_acceso`, `creado_en`) VALUES
(1, 'rodolfo', 'Rodolfo', '$2y$12$/ijJKFlK6qfhhdHubso4ZeLH5ZtpjWAlVY4U7HgQGtjS.L37ej7y.', 1, NULL, '2026-10-05 02:07:25'),
(2, 'vicente', 'Vicente', '$2y$12$VU1XVfr9HTznLd/f2XGNQ.olJSvhx7Z02EYwWKtvs697Ru2a068tS', 1, NULL, '2026-10-05 02:07:25'),
(3, 'martin', 'Martin', '$2y$12$nTbXRQ9QVZjeHauDtogjHesWKENE6HSrn5mM8rs61ox7.AbtqEOsi', 1, NULL, '2026-10-05 02:07:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `avisos_stock`
--

CREATE TABLE `avisos_stock` (
  `id_aviso` int(10) UNSIGNED NOT NULL,
  `id_repuesto` int(10) UNSIGNED NOT NULL,
  `contacto` varchar(150) NOT NULL COMMENT 'Correo o teléfono',
  `sesion_id` varchar(64) DEFAULT NULL,
  `notificado` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Solicitudes de aviso de reposición';

--
-- Volcado de datos para la tabla `avisos_stock`
--

INSERT INTO `avisos_stock` (`id_aviso`, `id_repuesto`, `contacto`, `sesion_id`, `notificado`, `creado_en`) VALUES
(1, 421, 'diego.fuentes@example.com', NULL, 0, '2026-10-03 04:55:18'),
(2, 461, '+56900000002', NULL, 0, '2026-10-01 22:45:18'),
(3, 367, 'francisca.diaz@example.com', NULL, 0, '2026-09-30 03:25:18'),
(4, 87, '+56900000006', NULL, 0, '2026-10-04 01:55:18'),
(5, 534, 'benjamin.soto@example.com', NULL, 0, '2026-10-04 19:25:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(10) UNSIGNED NOT NULL,
  `id_categoria_padre` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = categoría principal',
  `codigo` char(3) NOT NULL COMMENT 'Prefijo del SKU, ej: FRE',
  `nombre` varchar(60) NOT NULL,
  `slug` varchar(70) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categorías de repuestos (jerárquicas)';

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `id_categoria_padre`, `codigo`, `nombre`, `slug`, `descripcion`, `orden`) VALUES
(1, NULL, 'MOT', 'Motor', 'motor', 'Distribución, empaquetaduras y correas', 1),
(2, NULL, 'FIL', 'Filtros', 'filtros', 'Filtros de aceite, aire, combustible y cabina', 2),
(3, NULL, 'ENC', 'Encendido', 'encendido', 'Bujías y bobinas', 3),
(4, NULL, 'ELE', 'Eléctrico', 'electrico', 'Baterías, alternadores, motores de partida y sensores', 4),
(5, NULL, 'REF', 'Refrigeración', 'refrigeracion', 'Radiadores, bombas de agua y termostatos', 5),
(6, NULL, 'FRE', 'Frenos', 'frenos', 'Pastillas, discos y balatas', 6),
(7, NULL, 'SUS', 'Suspensión', 'suspension', 'Amortiguadores, bandejas y bieletas', 7),
(8, NULL, 'DIR', 'Dirección', 'direccion', 'Terminales y rótulas axiales', 8),
(9, NULL, 'TRA', 'Transmisión', 'transmision', 'Embragues y homocinéticas', 9),
(10, NULL, 'CAR', 'Carrocería e Iluminación', 'carroceria-iluminacion', 'Ópticas y piezas de carrocería', 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(10) UNSIGNED NOT NULL,
  `rut` varchar(12) NOT NULL COMMENT 'Formato 12345678-9, sin puntos',
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefono` varchar(20) NOT NULL COMMENT 'Formato +569XXXXXXXX',
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Clientes que han comprado';

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `rut`, `nombre`, `apellido`, `email`, `telefono`, `creado_en`, `actualizado_en`) VALUES
(1, '11111111-1', 'Camila', 'Rojas', 'camila.rojas@example.com', '+56900000001', '2026-10-05 05:25:18', '2026-10-05 05:25:18'),
(2, '22222222-2', 'Matías', 'González', 'matias.gonzalez@example.com', '+56900000002', '2026-10-05 05:25:18', '2026-10-05 05:25:18'),
(3, '33333333-3', 'Valentina', 'Muñoz', 'valentina.munoz@example.com', '+56900000003', '2026-10-05 05:25:18', '2026-10-05 05:25:18'),
(4, '44444444-4', 'Benjamín', 'Soto', 'benjamin.soto@example.com', '+56900000004', '2026-10-05 05:25:18', '2026-10-05 05:25:18'),
(5, '55555555-5', 'Francisca', 'Díaz', 'francisca.diaz@example.com', '+56900000005', '2026-10-05 05:25:18', '2026-10-05 05:25:18'),
(6, '66666666-6', 'Joaquín', 'Pérez', 'joaquin.perez@example.com', '+56900000006', '2026-10-05 05:25:18', '2026-10-05 05:25:18'),
(7, '77777777-7', 'Catalina', 'Silva', 'catalina.silva@example.com', '+56900000007', '2026-10-05 05:25:18', '2026-10-05 05:25:18'),
(8, '88888888-8', 'Diego', 'Fuentes', 'diego.fuentes@example.com', '+56900000008', '2026-10-05 05:25:18', '2026-10-05 05:25:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `codigos_descuento`
--

CREATE TABLE `codigos_descuento` (
  `id_codigo` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `tipo` enum('porcentaje','monto') NOT NULL DEFAULT 'porcentaje',
  `valor` decimal(10,2) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `vigente_hasta` date DEFAULT NULL COMMENT 'NULL = sin fecha de término',
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Volcado de datos para la tabla `codigos_descuento`
--

INSERT INTO `codigos_descuento` (`id_codigo`, `codigo`, `tipo`, `valor`, `activo`, `vigente_hasta`, `creado_en`) VALUES
(1, 'BIENVENIDO10', 'porcentaje', 10.00, 1, NULL, '2026-10-05 02:07:25'),
(2, 'GMG5000', 'monto', 5000.00, 1, NULL, '2026-10-05 05:25:18'),
(3, 'FRENOS15', 'porcentaje', 15.00, 1, '2026-12-04', '2026-10-05 05:25:18'),
(4, 'INVIERNO20', 'porcentaje', 20.00, 0, '2026-08-31', '2026-10-05 05:25:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compatibilidades`
--

CREATE TABLE `compatibilidades` (
  `id_repuesto` int(10) UNSIGNED NOT NULL,
  `id_vehiculo` int(10) UNSIGNED NOT NULL,
  `cantidad` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Unidades que usa el vehículo, ej: 4 bujías',
  `observacion` varchar(150) DEFAULT NULL COMMENT 'Restricción, ej: solo caja mecánica'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Qué repuesto sirve para qué vehículo';

--
-- Volcado de datos para la tabla `compatibilidades`
--

INSERT INTO `compatibilidades` (`id_repuesto`, `id_vehiculo`, `cantidad`, `observacion`) VALUES
(1, 1, NULL, NULL),
(2, 1, 3, NULL),
(3, 1, NULL, NULL),
(4, 1, NULL, NULL),
(5, 1, NULL, NULL),
(6, 1, NULL, NULL),
(7, 1, NULL, NULL),
(8, 1, NULL, NULL),
(9, 1, NULL, NULL),
(10, 1, NULL, NULL),
(11, 1, NULL, NULL),
(12, 2, NULL, NULL),
(12, 4, NULL, NULL),
(13, 2, 3, NULL),
(13, 4, 3, NULL),
(14, 2, NULL, NULL),
(14, 4, NULL, NULL),
(15, 2, NULL, NULL),
(15, 4, NULL, NULL),
(16, 2, NULL, NULL),
(16, 4, NULL, NULL),
(17, 2, NULL, NULL),
(17, 4, NULL, NULL),
(18, 2, NULL, NULL),
(18, 4, NULL, NULL),
(19, 2, NULL, NULL),
(19, 4, NULL, NULL),
(20, 2, NULL, NULL),
(20, 4, NULL, NULL),
(21, 2, NULL, NULL),
(21, 4, NULL, NULL),
(22, 2, NULL, NULL),
(22, 4, NULL, NULL),
(23, 3, NULL, NULL),
(23, 5, NULL, NULL),
(24, 3, 3, NULL),
(24, 5, 3, NULL),
(25, 3, NULL, NULL),
(25, 5, NULL, NULL),
(26, 3, NULL, NULL),
(26, 5, NULL, NULL),
(27, 3, NULL, NULL),
(27, 5, NULL, NULL),
(28, 3, NULL, NULL),
(28, 5, NULL, NULL),
(29, 3, NULL, NULL),
(29, 5, NULL, NULL),
(30, 3, NULL, NULL),
(30, 5, NULL, NULL),
(31, 3, NULL, NULL),
(31, 5, NULL, NULL),
(32, 3, NULL, NULL),
(32, 5, NULL, NULL),
(33, 3, NULL, NULL),
(33, 5, NULL, NULL),
(34, 9, NULL, NULL),
(35, 9, 3, NULL),
(36, 9, NULL, NULL),
(37, 9, NULL, NULL),
(38, 9, NULL, NULL),
(39, 9, NULL, NULL),
(40, 9, NULL, NULL),
(41, 9, NULL, NULL),
(42, 9, NULL, NULL),
(43, 9, NULL, NULL),
(44, 9, NULL, NULL),
(45, 6, NULL, NULL),
(46, 6, 4, NULL),
(47, 6, NULL, NULL),
(48, 6, NULL, NULL),
(49, 6, NULL, NULL),
(50, 6, NULL, NULL),
(51, 6, NULL, NULL),
(52, 6, NULL, NULL),
(53, 6, NULL, NULL),
(54, 6, NULL, NULL),
(55, 6, NULL, NULL),
(56, 10, NULL, NULL),
(56, 11, NULL, NULL),
(57, 10, 4, NULL),
(57, 11, 4, NULL),
(58, 10, NULL, NULL),
(58, 11, NULL, NULL),
(59, 10, NULL, NULL),
(59, 11, NULL, NULL),
(60, 10, NULL, NULL),
(60, 11, NULL, NULL),
(61, 10, NULL, NULL),
(61, 11, NULL, NULL),
(62, 10, NULL, NULL),
(62, 11, NULL, NULL),
(63, 10, NULL, NULL),
(63, 11, NULL, NULL),
(64, 10, NULL, NULL),
(64, 11, NULL, NULL),
(65, 10, NULL, NULL),
(65, 11, NULL, NULL),
(66, 10, NULL, NULL),
(66, 11, NULL, NULL),
(67, 7, NULL, NULL),
(67, 12, NULL, NULL),
(67, 15, NULL, NULL),
(67, 17, NULL, NULL),
(68, 7, 4, NULL),
(68, 12, 4, NULL),
(68, 15, 4, NULL),
(68, 17, 4, NULL),
(69, 7, NULL, NULL),
(69, 12, NULL, NULL),
(69, 15, NULL, NULL),
(69, 17, NULL, NULL),
(70, 7, NULL, NULL),
(70, 12, NULL, NULL),
(70, 15, NULL, NULL),
(70, 17, NULL, NULL),
(71, 7, NULL, NULL),
(71, 12, NULL, NULL),
(71, 15, NULL, NULL),
(71, 17, NULL, NULL),
(72, 7, NULL, NULL),
(72, 12, NULL, NULL),
(72, 15, NULL, NULL),
(72, 17, NULL, NULL),
(73, 7, NULL, NULL),
(73, 12, NULL, NULL),
(73, 15, NULL, NULL),
(73, 17, NULL, NULL),
(74, 7, NULL, NULL),
(74, 12, NULL, NULL),
(74, 15, NULL, NULL),
(74, 17, NULL, NULL),
(75, 7, NULL, NULL),
(75, 12, NULL, NULL),
(75, 15, NULL, NULL),
(75, 17, NULL, NULL),
(76, 7, NULL, NULL),
(76, 12, NULL, NULL),
(76, 15, NULL, NULL),
(76, 17, NULL, NULL),
(77, 7, NULL, NULL),
(77, 12, NULL, NULL),
(77, 15, NULL, NULL),
(77, 17, NULL, NULL),
(78, 13, NULL, NULL),
(78, 16, NULL, NULL),
(78, 20, NULL, NULL),
(78, 27, NULL, NULL),
(79, 13, 4, NULL),
(79, 16, 4, NULL),
(79, 20, 4, NULL),
(79, 27, 4, NULL),
(80, 13, NULL, NULL),
(80, 16, NULL, NULL),
(80, 20, NULL, NULL),
(80, 27, NULL, NULL),
(81, 13, NULL, NULL),
(81, 16, NULL, NULL),
(81, 20, NULL, NULL),
(81, 27, NULL, NULL),
(82, 13, NULL, NULL),
(82, 16, NULL, NULL),
(82, 20, NULL, NULL),
(82, 27, NULL, NULL),
(83, 13, NULL, NULL),
(83, 16, NULL, NULL),
(83, 20, NULL, NULL),
(83, 27, NULL, NULL),
(84, 13, NULL, NULL),
(84, 16, NULL, NULL),
(84, 20, NULL, NULL),
(84, 27, NULL, NULL),
(85, 13, NULL, NULL),
(85, 16, NULL, NULL),
(85, 20, NULL, NULL),
(85, 27, NULL, NULL),
(86, 13, NULL, NULL),
(86, 16, NULL, NULL),
(86, 20, NULL, NULL),
(86, 27, NULL, NULL),
(87, 13, NULL, NULL),
(87, 16, NULL, NULL),
(87, 20, NULL, NULL),
(87, 27, NULL, NULL),
(88, 13, NULL, NULL),
(88, 16, NULL, NULL),
(88, 20, NULL, NULL),
(88, 27, NULL, NULL),
(89, 14, NULL, NULL),
(90, 14, 4, NULL),
(91, 14, NULL, NULL),
(92, 14, NULL, NULL),
(93, 14, NULL, NULL),
(94, 14, NULL, NULL),
(95, 14, NULL, NULL),
(96, 14, NULL, NULL),
(97, 14, NULL, NULL),
(98, 14, NULL, NULL),
(99, 14, NULL, NULL),
(100, 26, NULL, NULL),
(101, 26, 4, NULL),
(102, 26, NULL, NULL),
(103, 26, NULL, NULL),
(104, 26, NULL, NULL),
(105, 26, NULL, NULL),
(106, 26, NULL, NULL),
(107, 26, NULL, NULL),
(108, 26, NULL, NULL),
(109, 26, NULL, NULL),
(110, 26, NULL, NULL),
(111, 8, NULL, NULL),
(111, 21, NULL, NULL),
(111, 22, NULL, NULL),
(112, 8, 4, NULL),
(112, 21, 4, NULL),
(112, 22, 4, NULL),
(113, 8, NULL, NULL),
(113, 21, NULL, NULL),
(113, 22, NULL, NULL),
(114, 8, NULL, NULL),
(114, 21, NULL, NULL),
(114, 22, NULL, NULL),
(115, 8, NULL, NULL),
(115, 21, NULL, NULL),
(115, 22, NULL, NULL),
(116, 8, NULL, NULL),
(116, 21, NULL, NULL),
(116, 22, NULL, NULL),
(117, 8, NULL, NULL),
(117, 21, NULL, NULL),
(117, 22, NULL, NULL),
(118, 8, NULL, NULL),
(118, 21, NULL, NULL),
(118, 22, NULL, NULL),
(119, 8, NULL, NULL),
(119, 21, NULL, NULL),
(119, 22, NULL, NULL),
(120, 8, NULL, NULL),
(120, 21, NULL, NULL),
(120, 22, NULL, NULL),
(121, 8, NULL, NULL),
(121, 21, NULL, NULL),
(121, 22, NULL, NULL),
(122, 18, NULL, NULL),
(123, 18, 4, NULL),
(124, 18, NULL, NULL),
(125, 18, NULL, NULL),
(126, 18, NULL, NULL),
(127, 18, NULL, NULL),
(128, 18, NULL, NULL),
(129, 18, NULL, NULL),
(130, 18, NULL, NULL),
(131, 18, NULL, NULL),
(132, 18, NULL, NULL),
(133, 19, NULL, NULL),
(134, 19, 4, NULL),
(135, 19, NULL, NULL),
(136, 19, NULL, NULL),
(137, 19, NULL, NULL),
(138, 19, NULL, NULL),
(139, 19, NULL, NULL),
(140, 19, NULL, NULL),
(141, 19, NULL, NULL),
(142, 19, NULL, NULL),
(143, 19, NULL, NULL),
(144, 24, NULL, NULL),
(144, 25, NULL, NULL),
(145, 24, 4, NULL),
(145, 25, 4, NULL),
(146, 24, NULL, NULL),
(146, 25, NULL, NULL),
(147, 24, NULL, NULL),
(147, 25, NULL, NULL),
(148, 24, NULL, NULL),
(148, 25, NULL, NULL),
(149, 24, NULL, NULL),
(149, 25, NULL, NULL),
(150, 24, NULL, NULL),
(150, 25, NULL, NULL),
(151, 24, NULL, NULL),
(151, 25, NULL, NULL),
(152, 24, NULL, NULL),
(152, 25, NULL, NULL),
(153, 24, NULL, NULL),
(153, 25, NULL, NULL),
(154, 24, NULL, NULL),
(154, 25, NULL, NULL),
(155, 23, NULL, NULL),
(156, 23, 4, NULL),
(157, 23, NULL, NULL),
(158, 23, NULL, NULL),
(159, 23, NULL, NULL),
(160, 23, NULL, NULL),
(161, 23, NULL, NULL),
(162, 23, NULL, NULL),
(163, 23, NULL, NULL),
(164, 23, NULL, NULL),
(165, 23, NULL, NULL),
(166, 1, 1, NULL),
(167, 1, 1, NULL),
(168, 1, 1, NULL),
(169, 1, 2, NULL),
(170, 1, 1, NULL),
(171, 1, 1, NULL),
(172, 1, 2, NULL),
(173, 1, 2, NULL),
(174, 1, 2, NULL),
(175, 1, NULL, NULL),
(176, 1, NULL, NULL),
(177, 1, NULL, NULL),
(178, 2, 1, NULL),
(179, 2, 1, NULL),
(180, 2, 1, NULL),
(181, 2, 2, NULL),
(182, 2, 1, NULL),
(183, 2, 1, NULL),
(184, 2, 2, NULL),
(185, 2, 2, NULL),
(186, 2, 2, NULL),
(187, 2, NULL, NULL),
(188, 2, NULL, NULL),
(189, 2, NULL, NULL),
(190, 3, 1, NULL),
(191, 3, 1, NULL),
(192, 3, 1, NULL),
(193, 3, 2, NULL),
(194, 3, 1, NULL),
(195, 3, 1, NULL),
(196, 3, 2, NULL),
(197, 3, 2, NULL),
(198, 3, 2, NULL),
(199, 3, NULL, NULL),
(200, 3, NULL, NULL),
(201, 3, NULL, NULL),
(202, 4, 1, NULL),
(202, 5, 1, NULL),
(203, 4, 1, NULL),
(203, 5, 1, NULL),
(204, 4, 1, NULL),
(204, 5, 1, NULL),
(205, 4, 2, NULL),
(205, 5, 2, NULL),
(206, 4, 1, NULL),
(206, 5, 1, NULL),
(207, 4, 1, NULL),
(207, 5, 1, NULL),
(208, 4, 2, NULL),
(208, 5, 2, NULL),
(209, 4, 2, NULL),
(209, 5, 2, NULL),
(210, 4, 2, NULL),
(210, 5, 2, NULL),
(211, 4, NULL, NULL),
(211, 5, NULL, NULL),
(212, 4, NULL, NULL),
(212, 5, NULL, NULL),
(213, 4, NULL, NULL),
(213, 5, NULL, NULL),
(214, 6, 1, NULL),
(214, 7, 1, NULL),
(215, 6, 1, NULL),
(215, 7, 1, NULL),
(216, 6, 1, NULL),
(216, 7, 1, NULL),
(217, 6, 2, NULL),
(217, 7, 2, NULL),
(218, 6, 2, NULL),
(218, 7, 2, NULL),
(219, 6, 1, NULL),
(219, 7, 1, NULL),
(220, 6, 1, NULL),
(220, 7, 1, NULL),
(221, 6, 2, NULL),
(221, 7, 2, NULL),
(222, 6, 2, NULL),
(222, 7, 2, NULL),
(223, 6, 2, NULL),
(223, 7, 2, NULL),
(224, 6, NULL, NULL),
(224, 7, NULL, NULL),
(225, 6, NULL, 'Versiones con aire acondicionado'),
(225, 7, NULL, 'Versiones con aire acondicionado'),
(226, 6, NULL, NULL),
(226, 7, NULL, NULL),
(227, 6, NULL, NULL),
(227, 7, NULL, NULL),
(228, 8, 1, NULL),
(229, 8, 1, NULL),
(230, 8, 1, NULL),
(231, 8, 2, NULL),
(232, 8, 2, NULL),
(233, 8, 1, NULL),
(234, 8, 1, NULL),
(235, 8, 2, NULL),
(236, 8, 2, NULL),
(237, 8, 2, NULL),
(238, 8, NULL, NULL),
(239, 8, NULL, 'Versiones con aire acondicionado'),
(240, 8, NULL, NULL),
(241, 8, NULL, NULL),
(242, 9, 1, 'Verificar diámetro de disco en versión 1.0 Turbo'),
(242, 10, 1, NULL),
(243, 9, 1, 'Verificar diámetro de disco en versión 1.0 Turbo'),
(243, 10, 1, NULL),
(244, 9, 1, NULL),
(244, 10, 1, NULL),
(245, 9, 2, NULL),
(245, 10, 2, NULL),
(246, 9, 2, NULL),
(246, 10, 2, NULL),
(247, 9, 1, NULL),
(247, 10, 1, NULL),
(248, 9, 1, NULL),
(248, 10, 1, NULL),
(249, 9, 2, NULL),
(249, 10, 2, NULL),
(250, 9, 2, NULL),
(250, 10, 2, NULL),
(251, 9, 2, NULL),
(251, 10, 2, NULL),
(252, 9, NULL, NULL),
(252, 10, NULL, NULL),
(253, 9, NULL, 'Versiones con aire acondicionado'),
(253, 10, NULL, 'Versiones con aire acondicionado'),
(254, 9, NULL, NULL),
(254, 10, NULL, NULL),
(255, 9, NULL, NULL),
(255, 10, NULL, NULL),
(256, 11, 1, NULL),
(256, 12, 1, NULL),
(257, 11, 1, NULL),
(257, 12, 1, NULL),
(258, 11, 1, NULL),
(258, 12, 1, NULL),
(259, 11, 2, NULL),
(259, 12, 2, NULL),
(260, 11, 2, NULL),
(260, 12, 2, NULL),
(261, 11, 1, NULL),
(261, 12, 1, NULL),
(262, 11, 1, NULL),
(262, 12, 1, NULL),
(263, 11, 2, NULL),
(263, 12, 2, NULL),
(264, 11, 2, NULL),
(264, 12, 2, NULL),
(265, 11, 2, NULL),
(265, 12, 2, NULL),
(266, 11, NULL, NULL),
(266, 12, NULL, NULL),
(267, 11, NULL, 'Versiones con aire acondicionado'),
(267, 12, NULL, 'Versiones con aire acondicionado'),
(268, 11, NULL, NULL),
(268, 12, NULL, NULL),
(269, 11, NULL, NULL),
(269, 12, NULL, NULL),
(270, 13, 1, NULL),
(271, 13, 1, NULL),
(272, 13, 1, NULL),
(273, 13, 2, NULL),
(274, 13, 2, NULL),
(275, 13, 1, NULL),
(276, 13, 1, NULL),
(277, 13, 2, NULL),
(278, 13, 2, NULL),
(279, 13, 2, NULL),
(280, 13, NULL, NULL),
(281, 13, NULL, 'Versiones con aire acondicionado'),
(282, 13, NULL, NULL),
(283, 13, NULL, NULL),
(284, 14, 1, NULL),
(285, 14, 1, NULL),
(286, 14, 1, NULL),
(287, 14, 2, NULL),
(288, 14, 2, NULL),
(289, 14, 1, NULL),
(290, 14, 1, NULL),
(291, 14, 2, NULL),
(292, 14, 2, NULL),
(293, 14, 2, NULL),
(294, 14, NULL, NULL),
(295, 14, NULL, 'Versiones con aire acondicionado'),
(296, 14, NULL, NULL),
(297, 14, NULL, NULL),
(298, 15, 1, NULL),
(299, 15, 1, NULL),
(300, 15, 1, NULL),
(301, 15, 2, NULL),
(302, 15, 2, NULL),
(303, 15, 1, NULL),
(304, 15, 1, NULL),
(305, 15, 2, NULL),
(306, 15, 2, NULL),
(307, 15, 2, NULL),
(308, 15, NULL, NULL),
(309, 15, NULL, 'Versiones con aire acondicionado'),
(310, 15, NULL, NULL),
(311, 15, NULL, NULL),
(312, 16, 1, NULL),
(313, 16, 1, NULL),
(314, 16, 1, NULL),
(315, 16, 2, NULL),
(316, 16, 2, NULL),
(317, 16, 1, NULL),
(318, 16, 1, NULL),
(319, 16, 2, NULL),
(320, 16, 2, NULL),
(321, 16, 2, NULL),
(322, 16, NULL, NULL),
(323, 16, NULL, 'Versiones con aire acondicionado'),
(324, 16, NULL, NULL),
(325, 16, NULL, NULL),
(326, 17, 1, NULL),
(327, 17, 1, NULL),
(328, 17, 1, NULL),
(329, 17, 2, NULL),
(330, 17, 2, NULL),
(331, 17, 1, NULL),
(332, 17, 1, NULL),
(333, 17, 2, NULL),
(334, 17, 2, NULL),
(335, 17, 2, NULL),
(336, 17, NULL, NULL),
(337, 17, NULL, 'Versiones con aire acondicionado'),
(338, 17, NULL, NULL),
(339, 17, NULL, NULL),
(340, 18, 1, NULL),
(340, 19, 1, NULL),
(341, 18, 1, NULL),
(341, 19, 1, NULL),
(342, 18, 1, NULL),
(342, 19, 1, NULL),
(343, 18, 2, NULL),
(343, 19, 2, NULL),
(344, 18, 1, NULL),
(344, 19, 1, NULL),
(345, 18, 1, NULL),
(345, 19, 1, NULL),
(346, 18, 2, NULL),
(346, 19, 2, NULL),
(347, 18, 2, NULL),
(347, 19, 2, NULL),
(348, 18, NULL, NULL),
(348, 19, NULL, NULL),
(349, 18, NULL, NULL),
(349, 19, NULL, NULL),
(350, 18, NULL, NULL),
(350, 19, NULL, NULL),
(351, 20, 1, NULL),
(352, 20, 1, NULL),
(353, 20, 1, NULL),
(354, 20, 2, NULL),
(355, 20, 1, NULL),
(356, 20, 1, NULL),
(357, 20, 2, NULL),
(358, 20, 2, NULL),
(359, 20, NULL, NULL),
(360, 20, NULL, NULL),
(361, 20, NULL, NULL),
(362, 21, 1, NULL),
(363, 21, 1, NULL),
(364, 21, 1, 'Versiones con freno trasero de tambor'),
(365, 21, 2, NULL),
(366, 21, 2, NULL),
(367, 21, 1, NULL),
(368, 21, 1, NULL),
(369, 21, 2, NULL),
(370, 21, 2, NULL),
(371, 21, 2, NULL),
(372, 21, NULL, NULL),
(373, 21, NULL, 'Versiones con aire acondicionado'),
(374, 21, NULL, NULL),
(375, 21, NULL, NULL),
(376, 22, 1, NULL),
(376, 23, 1, NULL),
(376, 25, 1, NULL),
(377, 22, 1, NULL),
(377, 23, 1, NULL),
(377, 25, 1, NULL),
(378, 22, 1, NULL),
(378, 23, 1, NULL),
(378, 25, 1, NULL),
(379, 22, 2, NULL),
(379, 23, 2, NULL),
(379, 25, 2, NULL),
(380, 22, 2, NULL),
(380, 23, 2, NULL),
(380, 25, 2, NULL),
(381, 22, 2, NULL),
(381, 23, 2, NULL),
(381, 25, 2, NULL),
(382, 22, 2, NULL),
(382, 23, 2, NULL),
(382, 25, 2, NULL),
(383, 22, 2, NULL),
(383, 23, 2, NULL),
(383, 25, 2, NULL),
(384, 22, NULL, NULL),
(384, 23, NULL, NULL),
(384, 25, NULL, NULL),
(385, 22, NULL, 'Versiones con aire acondicionado'),
(385, 23, NULL, 'Versiones con aire acondicionado'),
(385, 25, NULL, 'Versiones con aire acondicionado'),
(386, 22, NULL, 'Verificar diseño de óptica según año (renovación 2009)'),
(386, 23, NULL, 'Verificar diseño de óptica según año (renovación 2009)'),
(386, 25, NULL, 'Verificar diseño de óptica según año (renovación 2009)'),
(387, 22, NULL, 'Verificar diseño de óptica según año (renovación 2009)'),
(387, 23, NULL, 'Verificar diseño de óptica según año (renovación 2009)'),
(387, 25, NULL, 'Verificar diseño de óptica según año (renovación 2009)'),
(388, 24, 1, NULL),
(389, 24, 1, NULL),
(390, 24, 1, NULL),
(391, 24, 2, NULL),
(392, 24, 2, NULL),
(393, 24, 1, NULL),
(394, 24, 1, NULL),
(395, 24, 2, NULL),
(396, 24, 2, NULL),
(397, 24, 2, NULL),
(398, 24, NULL, NULL),
(399, 24, NULL, NULL),
(400, 24, NULL, NULL),
(401, 26, 1, NULL),
(402, 26, 1, NULL),
(403, 26, 1, NULL),
(404, 26, 1, NULL),
(405, 26, 1, NULL),
(406, 26, 2, NULL),
(407, 26, 2, NULL),
(408, 26, NULL, NULL),
(409, 26, NULL, NULL),
(410, 26, NULL, NULL),
(411, 27, 1, NULL),
(412, 27, 1, NULL),
(413, 27, 1, NULL),
(414, 27, 1, NULL),
(415, 27, 1, NULL),
(416, 27, 2, NULL),
(417, 27, 2, NULL),
(418, 27, NULL, NULL),
(419, 27, NULL, 'Versiones con aire acondicionado'),
(420, 27, NULL, NULL),
(421, 27, NULL, NULL),
(422, 1, NULL, NULL),
(423, 1, NULL, NULL),
(424, 1, NULL, NULL),
(425, 1, NULL, 'Solo versiones con caja mecánica'),
(426, 2, NULL, NULL),
(427, 2, NULL, NULL),
(428, 2, NULL, NULL),
(429, 2, NULL, 'Solo versiones con caja mecánica'),
(430, 3, NULL, NULL),
(431, 3, NULL, NULL),
(432, 3, NULL, NULL),
(433, 3, NULL, 'Solo versiones con caja mecánica'),
(434, 4, NULL, NULL),
(435, 4, NULL, NULL),
(436, 4, NULL, NULL),
(437, 4, NULL, 'Solo versiones con caja mecánica'),
(438, 5, NULL, NULL),
(439, 5, NULL, NULL),
(440, 5, NULL, NULL),
(441, 5, NULL, 'Solo versiones con caja mecánica'),
(442, 6, NULL, NULL),
(443, 6, NULL, NULL),
(444, 6, NULL, NULL),
(445, 6, NULL, 'Solo versiones con caja mecánica'),
(446, 7, NULL, NULL),
(447, 7, NULL, NULL),
(448, 7, NULL, NULL),
(449, 7, NULL, 'Solo versiones con caja mecánica'),
(450, 8, NULL, NULL),
(451, 8, NULL, NULL),
(452, 8, NULL, NULL),
(453, 8, NULL, 'Solo versiones con caja mecánica'),
(454, 9, NULL, NULL),
(455, 9, NULL, NULL),
(456, 9, NULL, NULL),
(457, 9, NULL, 'Solo versiones con caja mecánica'),
(458, 10, NULL, NULL),
(459, 10, NULL, NULL),
(460, 10, NULL, NULL),
(461, 10, NULL, 'Solo versiones con caja mecánica'),
(462, 11, NULL, NULL),
(463, 11, NULL, NULL),
(464, 11, NULL, NULL),
(465, 11, NULL, 'Solo versiones con caja mecánica'),
(466, 12, NULL, NULL),
(467, 12, NULL, NULL),
(468, 12, NULL, NULL),
(469, 12, NULL, 'Solo versiones con caja mecánica'),
(470, 13, NULL, NULL),
(471, 13, NULL, NULL),
(472, 13, NULL, NULL),
(473, 13, NULL, 'Solo versiones con caja mecánica'),
(474, 14, NULL, NULL),
(475, 14, NULL, NULL),
(476, 14, NULL, NULL),
(477, 14, NULL, 'Solo versiones con caja mecánica'),
(478, 15, NULL, NULL),
(479, 15, NULL, NULL),
(480, 15, NULL, NULL),
(481, 15, NULL, 'Solo versiones con caja mecánica'),
(482, 16, NULL, NULL),
(483, 16, NULL, NULL),
(484, 16, NULL, NULL),
(485, 16, NULL, 'Solo versiones con caja mecánica'),
(486, 17, NULL, NULL),
(487, 17, NULL, NULL),
(488, 17, NULL, NULL),
(489, 17, NULL, 'Solo versiones con caja mecánica'),
(490, 18, NULL, NULL),
(491, 18, NULL, NULL),
(492, 18, NULL, NULL),
(493, 18, NULL, 'Solo versiones con caja mecánica'),
(494, 19, NULL, NULL),
(495, 19, NULL, NULL),
(496, 19, NULL, NULL),
(497, 19, NULL, 'Solo versiones con caja mecánica'),
(498, 20, NULL, NULL),
(499, 20, NULL, NULL),
(500, 20, NULL, NULL),
(501, 20, NULL, 'Solo versiones con caja mecánica'),
(502, 21, NULL, NULL),
(503, 21, NULL, NULL),
(504, 21, NULL, NULL),
(505, 21, NULL, 'Solo versiones con caja mecánica'),
(506, 22, NULL, NULL),
(507, 22, NULL, NULL),
(508, 22, NULL, NULL),
(509, 22, NULL, 'Solo versiones con caja mecánica'),
(510, 22, NULL, NULL),
(511, 22, NULL, NULL),
(512, 23, NULL, NULL),
(513, 23, NULL, NULL),
(514, 23, NULL, NULL),
(515, 23, NULL, 'Solo versiones con caja mecánica'),
(516, 23, NULL, NULL),
(517, 23, NULL, NULL),
(518, 24, NULL, NULL),
(519, 24, NULL, NULL),
(520, 24, NULL, NULL),
(521, 24, NULL, 'Solo versiones con caja mecánica'),
(522, 25, NULL, NULL),
(523, 25, NULL, NULL),
(524, 25, NULL, NULL),
(525, 25, NULL, 'Solo versiones con caja mecánica'),
(526, 25, NULL, NULL),
(527, 25, NULL, NULL),
(528, 26, NULL, NULL),
(529, 26, NULL, NULL),
(530, 26, NULL, NULL),
(531, 26, NULL, 'Solo versiones con caja mecánica'),
(532, 27, NULL, NULL),
(533, 27, NULL, NULL),
(534, 27, NULL, NULL),
(535, 27, NULL, 'Solo versiones con caja mecánica'),
(536, 1, NULL, NULL),
(536, 2, NULL, NULL),
(536, 3, NULL, NULL),
(536, 4, NULL, NULL),
(536, 5, NULL, NULL),
(537, 6, NULL, NULL),
(537, 7, NULL, NULL),
(537, 8, NULL, NULL),
(537, 9, NULL, NULL),
(537, 10, NULL, NULL),
(537, 11, NULL, NULL),
(537, 12, NULL, NULL),
(537, 13, NULL, NULL),
(537, 14, NULL, NULL),
(537, 15, NULL, NULL),
(537, 16, NULL, NULL),
(537, 17, NULL, NULL),
(537, 18, NULL, NULL),
(537, 19, NULL, NULL),
(537, 20, NULL, NULL),
(537, 26, NULL, NULL),
(537, 27, NULL, NULL),
(538, 21, NULL, NULL),
(538, 22, NULL, NULL),
(538, 23, NULL, NULL),
(538, 24, NULL, NULL),
(538, 25, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conversaciones_chat`
--

CREATE TABLE `conversaciones_chat` (
  `id_conversacion` int(10) UNSIGNED NOT NULL,
  `sesion_id` varchar(64) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimo_mensaje_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sesiones del asistente virtual';

--
-- Volcado de datos para la tabla `conversaciones_chat`
--

INSERT INTO `conversaciones_chat` (`id_conversacion`, `sesion_id`, `creado_en`, `ultimo_mensaje_en`) VALUES
(1, 'demo-chat-0001', '2026-10-01 21:05:18', '2026-10-01 21:11:18'),
(2, 'demo-chat-0002', '2026-10-03 04:45:18', '2026-10-03 04:51:18'),
(3, 'demo-chat-0003', '2026-10-04 19:25:18', '2026-10-04 19:27:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `marcas`
--

CREATE TABLE `marcas` (
  `id_marca` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `slug` varchar(60) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Marcas de vehículos';

--
-- Volcado de datos para la tabla `marcas`
--

INSERT INTO `marcas` (`id_marca`, `nombre`, `slug`, `activo`) VALUES
(1, 'Suzuki', 'suzuki', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_chat`
--

CREATE TABLE `mensajes_chat` (
  `id_mensaje` int(10) UNSIGNED NOT NULL,
  `id_conversacion` int(10) UNSIGNED NOT NULL,
  `rol` enum('user','bot') NOT NULL,
  `contenido` text NOT NULL,
  `feedback` enum('positivo','negativo') DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Mensajes de cada conversación';

--
-- Volcado de datos para la tabla `mensajes_chat`
--

INSERT INTO `mensajes_chat` (`id_mensaje`, `id_conversacion`, `rol`, `contenido`, `feedback`, `creado_en`) VALUES
(1, 1, 'user', 'Hola, necesito pastillas de freno delanteras para un Swift 2019 1.2', NULL, '2026-10-01 21:05:18'),
(2, 1, 'bot', '¡Hola! Para tu Suzuki Swift 1.2 K12M (2017-2024) tengo las Pastillas de freno delanteras (juego) a $23.990, con stock disponible. Si también vas a cambiar discos, el par de discos delanteros está a $54.990. ¿Te ayudo con la compra?', 'positivo', '2026-10-01 21:07:18'),
(3, 1, 'user', 'Solo las pastillas, gracias', NULL, '2026-10-01 21:09:18'),
(4, 1, 'bot', 'Perfecto. Agrégalas al carrito con el botón \"Añadir al carrito\" y paga con Mercado Pago. Si necesitas ayuda con la instalación, escríbenos por WhatsApp.', NULL, '2026-10-01 21:11:18'),
(5, 2, 'user', 'Tienen el foco delantero derecho del Jimny 2021?', NULL, '2026-10-03 04:45:18'),
(6, 2, 'bot', 'Sí lo tenemos en el catálogo: Óptica delantera derecha Jimny 4ª gen. a $123.990, pero en este momento está sin stock. La izquierda sí está disponible a $123.990. ¿Quieres que te avise cuando llegue la derecha?', NULL, '2026-10-03 04:47:18'),
(7, 2, 'user', 'Sí, avísame a diego.fuentes@example.com', NULL, '2026-10-03 04:49:18'),
(8, 2, 'bot', '¡Listo! Te avisaremos a ese correo apenas vuelva a haber stock.', 'positivo', '2026-10-03 04:51:18'),
(9, 3, 'user', 'Qué batería le sirve a un Celerio 2023?', NULL, '2026-10-04 19:25:18'),
(10, 3, 'bot', 'Para el Celerio 1.0 K10C (2022-hoy) normalmente se usa una batería 12V 40Ah o 45Ah. Tenemos la Batería 12V 40Ah a $64.990 y la 12V 45Ah a $74.990. Revisa la polaridad y las medidas de la bandeja antes de comprar.', 'negativo', '2026-10-04 19:27:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensaje_repuestos`
--

CREATE TABLE `mensaje_repuestos` (
  `id_mensaje` int(10) UNSIGNED NOT NULL,
  `id_repuesto` int(10) UNSIGNED NOT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Repuestos que el chatbot mostró en cada mensaje';

--
-- Volcado de datos para la tabla `mensaje_repuestos`
--

INSERT INTO `mensaje_repuestos` (`id_mensaje`, `id_repuesto`, `orden`) VALUES
(2, 242, 1),
(2, 243, 2),
(6, 420, 2),
(6, 421, 1),
(10, 536, 1),
(10, 537, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modelos`
--

CREATE TABLE `modelos` (
  `id_modelo` int(10) UNSIGNED NOT NULL,
  `id_marca` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL COMMENT 'Sin la marca, ej: Swift',
  `slug` varchar(60) NOT NULL COMMENT 'Para URL, ej: grand-vitara',
  `tipo_carroceria` enum('Hatchback','Sedán','Crossover','SUV','Todoterreno','Minivan','Furgón','Camioneta') NOT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Orden en la portada',
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Modelos de vehículo';

--
-- Volcado de datos para la tabla `modelos`
--

INSERT INTO `modelos` (`id_modelo`, `id_marca`, `nombre`, `slug`, `tipo_carroceria`, `imagen_url`, `orden`, `activo`) VALUES
(1, 1, 'Alto', 'alto', 'Hatchback', 'img/alto.jpg', 1, 1),
(2, 1, 'Celerio', 'celerio', 'Hatchback', 'img/celerio.jpg', 2, 1),
(3, 1, 'S-Presso', 's-presso', 'Crossover', 'img/spresso.jpg', 3, 1),
(4, 1, 'Swift', 'swift', 'Hatchback', 'img/swift.jpg', 4, 1),
(5, 1, 'Baleno', 'baleno', 'Hatchback', 'img/baleno.jpg', 5, 1),
(6, 1, 'Fronx', 'fronx', 'Crossover', NULL, 6, 1),
(7, 1, 'Ertiga', 'ertiga', 'Minivan', 'img/ertiga.jpg', 7, 1),
(8, 1, 'Ciaz', 'ciaz', 'Sedán', NULL, 8, 1),
(9, 1, 'APV', 'apv', 'Furgón', NULL, 9, 1),
(10, 1, 'Carry', 'carry', 'Camioneta', NULL, 10, 1),
(11, 1, 'Vitara', 'vitara', 'SUV', NULL, 11, 1),
(12, 1, 'Grand Vitara', 'grand-vitara', 'SUV', NULL, 12, 1),
(13, 1, 'Grand Nomade', 'grand-nomade', 'SUV', NULL, 13, 1),
(14, 1, 'Jimny', 'jimny', 'Todoterreno', 'img/jimny.jpg', 14, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `motores`
--

CREATE TABLE `motores` (
  `id_motor` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL COMMENT 'Código de fábrica, ej: K12M',
  `variante` varchar(40) NOT NULL DEFAULT '' COMMENT 'Distingue motores con el mismo código, ej: Boosterjet Turbo',
  `cilindrada_cc` smallint(5) UNSIGNED NOT NULL,
  `cilindrada_litros` decimal(2,1) GENERATED ALWAYS AS (round(`cilindrada_cc` / 1000,1)) VIRTUAL COMMENT 'Calculada, no se escribe',
  `cilindros` tinyint(3) UNSIGNED NOT NULL,
  `valvulas` tinyint(3) UNSIGNED NOT NULL,
  `combustible` enum('Bencina','Diésel') NOT NULL DEFAULT 'Bencina',
  `aspiracion` enum('Atmosférico','Turbo') NOT NULL DEFAULT 'Atmosférico',
  `hibrido` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = micro híbrido (Smart Hybrid)',
  `tipo_distribucion` enum('Cadena','Correa') NOT NULL
) ;

--
-- Volcado de datos para la tabla `motores`
--

INSERT INTO `motores` (`id_motor`, `codigo`, `variante`, `cilindrada_cc`, `cilindros`, `valvulas`, `combustible`, `aspiracion`, `hibrido`, `tipo_distribucion`) VALUES
(1, 'F8D', '', 796, 3, 12, 'Bencina', 'Atmosférico', 0, 'Correa'),
(2, 'K10B', '', 998, 3, 12, 'Bencina', 'Atmosférico', 0, 'Cadena'),
(3, 'K10C', 'Dualjet', 998, 3, 12, 'Bencina', 'Atmosférico', 0, 'Cadena'),
(4, 'K10C', 'Boosterjet Turbo', 998, 3, 12, 'Bencina', 'Turbo', 0, 'Cadena'),
(5, 'K12B', '', 1197, 4, 16, 'Bencina', 'Atmosférico', 0, 'Cadena'),
(6, 'K12M', '', 1197, 4, 16, 'Bencina', 'Atmosférico', 0, 'Cadena'),
(7, 'K14B', '', 1373, 4, 16, 'Bencina', 'Atmosférico', 0, 'Cadena'),
(8, 'K15B', '', 1462, 4, 16, 'Bencina', 'Atmosférico', 0, 'Cadena'),
(9, 'K15C', 'Smart Hybrid', 1462, 4, 16, 'Bencina', 'Atmosférico', 1, 'Cadena'),
(10, 'M13A', '', 1328, 4, 16, 'Bencina', 'Atmosférico', 0, 'Cadena'),
(11, 'M16A', '', 1586, 4, 16, 'Bencina', 'Atmosférico', 0, 'Cadena'),
(12, 'G15A', '', 1493, 4, 16, 'Bencina', 'Atmosférico', 0, 'Correa'),
(13, 'G16A', '', 1590, 4, 16, 'Bencina', 'Atmosférico', 0, 'Correa'),
(14, 'J20A', '', 1995, 4, 16, 'Bencina', 'Atmosférico', 0, 'Cadena'),
(15, 'J24B', '', 2393, 4, 16, 'Bencina', 'Atmosférico', 0, 'Cadena');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id_pedido` int(10) UNSIGNED NOT NULL,
  `id_cliente` int(10) UNSIGNED NOT NULL,
  `id_codigo_descuento` int(10) UNSIGNED DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('pendiente','aprobado','rechazado','error') NOT NULL DEFAULT 'pendiente' COMMENT 'Revisión del administrador',
  `pago_estado` enum('pendiente','recibido','rechazado') NOT NULL DEFAULT 'pendiente' COMMENT 'Resultado informado por la pasarela',
  `metodo_pago` varchar(30) NOT NULL DEFAULT 'Mercado Pago',
  `referencia_pago` varchar(100) DEFAULT NULL COMMENT 'payment_id de Mercado Pago',
  `descuento_monto` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Foto del descuento aplicado al momento del pago',
  `total` decimal(10,2) NOT NULL COMMENT 'Desnormalización intencional: SUM(cantidad*precio_unitario) - descuento_monto al pagar. Es el monto cobrado por Mercado Pago y no debe cambiar'
) ;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id_pedido`, `id_cliente`, `id_codigo_descuento`, `fecha`, `estado`, `pago_estado`, `metodo_pago`, `referencia_pago`, `descuento_monto`, `total`) VALUES
(1, 1, 1, '2026-09-23 00:25:18', 'aprobado', 'recibido', 'Mercado Pago', 'DEMO-0001', 3997.00, 35973.00),
(2, 2, NULL, '2026-09-23 19:05:18', 'aprobado', 'recibido', 'Mercado Pago', 'DEMO-0002', 0.00, 74990.00),
(3, 3, NULL, '2026-09-25 02:55:18', 'aprobado', 'recibido', 'Mercado Pago', 'DEMO-0003', 0.00, 29950.00),
(4, 4, NULL, '2026-09-25 21:25:18', 'rechazado', 'rechazado', 'Mercado Pago', 'DEMO-0004', 0.00, 116990.00),
(5, 5, 2, '2026-09-27 02:05:18', 'aprobado', 'recibido', 'Mercado Pago', 'DEMO-0005', 5000.00, 97980.00),
(6, 6, NULL, '2026-09-28 03:55:18', 'aprobado', 'recibido', 'Mercado Pago', 'DEMO-0006', 0.00, 35960.00),
(7, 7, NULL, '2026-09-28 17:45:18', 'aprobado', 'recibido', 'Mercado Pago', 'DEMO-0007', 0.00, 157980.00),
(8, 8, 1, '2026-09-29 23:55:18', 'aprobado', 'recibido', 'Mercado Pago', 'DEMO-0008', 9499.00, 85491.00),
(9, 1, NULL, '2026-09-30 22:35:18', 'aprobado', 'recibido', 'Mercado Pago', 'DEMO-0009', 0.00, 58950.00),
(10, 3, NULL, '2026-10-02 01:05:18', 'aprobado', 'recibido', 'Mercado Pago', 'DEMO-0010', 0.00, 53960.00),
(11, 2, NULL, '2026-10-02 20:45:18', 'error', 'pendiente', 'Mercado Pago', 'DEMO-0011', 0.00, 94990.00),
(12, 5, NULL, '2026-10-03 18:45:18', 'aprobado', 'recibido', 'Mercado Pago', 'DEMO-0012', 0.00, 29950.00),
(13, 6, 3, '2026-10-04 03:50:18', 'pendiente', 'recibido', 'Mercado Pago', 'DEMO-0013', 8997.00, 50983.00),
(14, 4, NULL, '2026-10-05 00:25:18', 'pendiente', 'recibido', 'Mercado Pago', 'DEMO-0014', 0.00, 142990.00),
(15, 8, NULL, '2026-10-05 04:10:18', 'pendiente', 'pendiente', 'Mercado Pago', 'DEMO-0015', 0.00, 31980.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_detalle`
--

CREATE TABLE `pedido_detalle` (
  `id_detalle` int(10) UNSIGNED NOT NULL,
  `id_pedido` int(10) UNSIGNED NOT NULL,
  `id_repuesto` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL si el repuesto se eliminó después',
  `sku` varchar(30) NOT NULL,
  `nombre_repuesto` varchar(150) NOT NULL,
  `cantidad` smallint(5) UNSIGNED NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ;

--
-- Volcado de datos para la tabla `pedido_detalle`
--

INSERT INTO `pedido_detalle` (`id_detalle`, `id_pedido`, `id_repuesto`, `sku`, `nombre_repuesto`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 56, 'FIL-K12M-ACE', 'Filtro de aceite motor K12M 1.2', 1, 5990.00),
(2, 1, 458, 'FIL-SWI3-K12M-AIR', 'Filtro de aire Swift 1.2 K12M', 1, 9990.00),
(3, 1, 242, 'FRE-SWI3-PDL', 'Pastillas de freno delanteras (juego) Swift 3ª gen.', 1, 23990.00),
(4, 2, 537, 'ELE-BAT-45AH', 'Batería 12V 45Ah', 1, 74990.00),
(5, 3, 57, 'ENC-K12M-BUJ', 'Bujía de encendido motor K12M 1.2', 4, 5990.00),
(6, 3, 56, 'FIL-K12M-ACE', 'Filtro de aceite motor K12M 1.2', 1, 5990.00),
(7, 4, 414, 'SUS-JIM4-ADL', 'Amortiguadores delanteros (par) Jimny 4ª gen.', 1, 116990.00),
(8, 5, 412, 'FRE-JIM4-DDL', 'Discos de freno delanteros (par) Jimny 4ª gen.', 1, 71990.00),
(9, 5, 411, 'FRE-JIM4-PDL', 'Pastillas de freno delanteras (juego) Jimny 4ª gen.', 1, 30990.00),
(10, 6, 419, 'FIL-JIM4-CAB', 'Filtro de cabina (polen) Jimny 4ª gen.', 1, 10990.00),
(11, 6, 532, 'FIL-JIM4-K15B-AIR', 'Filtro de aire Jimny 1.5 K15B', 1, 12990.00),
(12, 6, 78, 'FIL-K15B-ACE', 'Filtro de aceite motor K15B 1.5', 2, 5990.00),
(13, 7, 59, 'MOT-K12M-DIS', 'Kit de cadena de distribución motor K12M 1.2', 1, 119990.00),
(14, 7, 62, 'REF-K12M-BAG', 'Bomba de agua motor K12M 1.2', 1, 37990.00),
(15, 8, 268, 'CAR-BAL2-OPI', 'Óptica delantera izquierda Baleno 2ª gen.', 1, 94990.00),
(16, 9, 58, 'ENC-K12M-BOB', 'Bobina de encendido motor K12M 1.2', 1, 34990.00),
(17, 9, 57, 'ENC-K12M-BUJ', 'Bujía de encendido motor K12M 1.2', 4, 5990.00),
(18, 10, 249, 'DIR-SWI3-TER', 'Terminal de dirección exterior Swift 3ª gen.', 2, 14990.00),
(19, 10, 246, 'SUS-SWI3-BIE', 'Bieleta de barra estabilizadora Swift 3ª gen.', 2, 11990.00),
(20, 11, 538, 'ELE-BAT-60AH', 'Batería 12V 60Ah', 1, 94990.00),
(21, 12, 79, 'ENC-K15B-BUJ', 'Bujía de encendido motor K15B 1.5', 4, 5990.00),
(22, 12, 78, 'FIL-K15B-ACE', 'Filtro de aceite motor K15B 1.5', 1, 5990.00),
(23, 13, 413, 'FRE-JIM4-BTR', 'Balatas de freno traseras (juego) Jimny 4ª gen.', 1, 28990.00),
(24, 13, 411, 'FRE-JIM4-PDL', 'Pastillas de freno delanteras (juego) Jimny 4ª gen.', 1, 30990.00),
(25, 14, 535, 'TRA-JIM4-K15B-EMB', 'Kit de embrague Jimny 1.5 K15B', 1, 142990.00),
(26, 15, 267, 'FIL-BAL2-CAB', 'Filtro de cabina (polen) Baleno 2ª gen.', 1, 7990.00),
(27, 15, 256, 'FRE-BAL2-PDL', 'Pastillas de freno delanteras (juego) Baleno 2ª gen.', 1, 23990.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repuestos`
--

CREATE TABLE `repuestos` (
  `id_repuesto` int(10) UNSIGNED NOT NULL,
  `id_categoria` int(10) UNSIGNED NOT NULL,
  `sku` varchar(30) NOT NULL COMMENT 'Código interno CAT-GRUPO-TIPO, ej: FRE-SWI3-PDL (CAT = categorias.codigo)',
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `codigo_oem` varchar(40) DEFAULT NULL COMMENT 'Número de parte original Suzuki',
  `marca_fabricante` varchar(60) DEFAULT NULL COMMENT 'Marca del repuesto, ej: Suzuki Genuine, NGK, Bosch',
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'CLP, IVA incluido',
  `stock` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` smallint(5) UNSIGNED NOT NULL DEFAULT 3 COMMENT 'Bajo este número aparece en alertas del dashboard',
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 = oculto en la tienda',
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Volcado de datos para la tabla `repuestos`
--

INSERT INTO `repuestos` (`id_repuesto`, `id_categoria`, `sku`, `nombre`, `descripcion`, `codigo_oem`, `marca_fabricante`, `precio`, `stock`, `stock_minimo`, `activo`, `creado_en`, `actualizado_en`) VALUES
(1, 2, 'FIL-F8D-ACE', 'Filtro de aceite motor F8D 0.8', 'Filtro de aceite para motor F8D 0.8 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wega', 5990.00, 41, 8, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(2, 3, 'ENC-F8D-BUJ', 'Bujía de encendido motor F8D 0.8', 'Bujía de encendido para motor F8D 0.8 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 4990.00, 42, 12, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(3, 3, 'ENC-F8D-BOB', 'Bobina de encendido motor F8D 0.8', 'Bobina de encendido para motor F8D 0.8 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 31990.00, 6, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(4, 1, 'MOT-F8D-DIS', 'Kit de correa de distribución motor F8D 0.8', 'Kit de correa de distribución para motor F8D 0.8 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 58990.00, 12, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(5, 1, 'MOT-F8D-EMP', 'Empaquetadura de culata motor F8D 0.8', 'Empaquetadura de culata para motor F8D 0.8 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Victor Reinz', 24990.00, 11, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(6, 1, 'MOT-F8D-TVA', 'Empaquetadura tapa de válvulas motor F8D 0.8', 'Empaquetadura tapa de válvulas para motor F8D 0.8 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Victor Reinz', 12990.00, 14, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(7, 5, 'REF-F8D-BAG', 'Bomba de agua motor F8D 0.8', 'Bomba de agua para motor F8D 0.8 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Aisin', 33990.00, 6, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(8, 5, 'REF-F8D-TER', 'Termostato motor F8D 0.8', 'Termostato para motor F8D 0.8 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wahler', 15990.00, 8, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(9, 4, 'ELE-F8D-SOX', 'Sensor de oxígeno (sonda lambda) motor F8D 0.8', 'Sensor de oxígeno (sonda lambda) para motor F8D 0.8 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 40990.00, 6, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(10, 4, 'ELE-F8D-ALT', 'Alternador motor F8D 0.8', 'Alternador para motor F8D 0.8 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 143990.00, 6, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(11, 4, 'ELE-F8D-MPA', 'Motor de partida motor F8D 0.8', 'Motor de partida para motor F8D 0.8 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 125990.00, 10, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(12, 2, 'FIL-K10B-ACE', 'Filtro de aceite motor K10B 1.0', 'Filtro de aceite para motor K10B 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 5990.00, 14, 8, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(13, 3, 'ENC-K10B-BUJ', 'Bujía de encendido motor K10B 1.0', 'Bujía de encendido para motor K10B 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 4990.00, 29, 12, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(14, 3, 'ENC-K10B-BOB', 'Bobina de encendido motor K10B 1.0', 'Bobina de encendido para motor K10B 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 31990.00, 9, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(15, 1, 'MOT-K10B-DIS', 'Kit de cadena de distribución motor K10B 1.0', 'Kit de cadena de distribución para motor K10B 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Febi', 107990.00, 9, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(16, 1, 'MOT-K10B-EMP', 'Empaquetadura de culata motor K10B 1.0', 'Empaquetadura de culata para motor K10B 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Ajusa', 24990.00, 10, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(17, 1, 'MOT-K10B-TVA', 'Empaquetadura tapa de válvulas motor K10B 1.0', 'Empaquetadura tapa de válvulas para motor K10B 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Victor Reinz', 12990.00, 12, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(18, 5, 'REF-K10B-BAG', 'Bomba de agua motor K10B 1.0', 'Bomba de agua para motor K10B 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Aisin', 33990.00, 5, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(19, 5, 'REF-K10B-TER', 'Termostato motor K10B 1.0', 'Termostato para motor K10B 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Aisin', 15990.00, 0, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(20, 4, 'ELE-K10B-SOX', 'Sensor de oxígeno (sonda lambda) motor K10B 1.0', 'Sensor de oxígeno (sonda lambda) para motor K10B 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 40990.00, 9, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(21, 4, 'ELE-K10B-ALT', 'Alternador motor K10B 1.0', 'Alternador para motor K10B 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 143990.00, 6, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(22, 4, 'ELE-K10B-MPA', 'Motor de partida motor K10B 1.0', 'Motor de partida para motor K10B 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 125990.00, 5, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(23, 2, 'FIL-K10C-ACE', 'Filtro de aceite motor K10C Dualjet 1.0', 'Filtro de aceite para motor K10C Dualjet 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 5990.00, 20, 8, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(24, 3, 'ENC-K10C-BUJ', 'Bujía de encendido motor K10C Dualjet 1.0', 'Bujía de encendido para motor K10C Dualjet 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 4990.00, 45, 12, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(25, 3, 'ENC-K10C-BOB', 'Bobina de encendido motor K10C Dualjet 1.0', 'Bobina de encendido para motor K10C Dualjet 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 31990.00, 13, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(26, 1, 'MOT-K10C-DIS', 'Kit de cadena de distribución motor K10C Dualjet 1.0', 'Kit de cadena de distribución para motor K10C Dualjet 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Febi', 107990.00, 8, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(27, 1, 'MOT-K10C-EMP', 'Empaquetadura de culata motor K10C Dualjet 1.0', 'Empaquetadura de culata para motor K10C Dualjet 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Ajusa', 24990.00, 6, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(28, 1, 'MOT-K10C-TVA', 'Empaquetadura tapa de válvulas motor K10C Dualjet 1.0', 'Empaquetadura tapa de válvulas para motor K10C Dualjet 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Ajusa', 12990.00, 8, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(29, 5, 'REF-K10C-BAG', 'Bomba de agua motor K10C Dualjet 1.0', 'Bomba de agua para motor K10C Dualjet 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Aisin', 33990.00, 7, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(30, 5, 'REF-K10C-TER', 'Termostato motor K10C Dualjet 1.0', 'Termostato para motor K10C Dualjet 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wahler', 15990.00, 12, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(31, 4, 'ELE-K10C-SOX', 'Sensor de oxígeno (sonda lambda) motor K10C Dualjet 1.0', 'Sensor de oxígeno (sonda lambda) para motor K10C Dualjet 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 40990.00, 5, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(32, 4, 'ELE-K10C-ALT', 'Alternador motor K10C Dualjet 1.0', 'Alternador para motor K10C Dualjet 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 143990.00, 9, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(33, 4, 'ELE-K10C-MPA', 'Motor de partida motor K10C Dualjet 1.0', 'Motor de partida para motor K10C Dualjet 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 125990.00, 8, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(34, 2, 'FIL-K10CT-ACE', 'Filtro de aceite motor K10C Boosterjet Turbo 1.0', 'Filtro de aceite para motor K10C Boosterjet Turbo 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 5990.00, 41, 8, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(35, 3, 'ENC-K10CT-BUJ', 'Bujía de encendido motor K10C Boosterjet Turbo 1.0', 'Bujía de encendido para motor K10C Boosterjet Turbo 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 5990.00, 29, 12, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(36, 3, 'ENC-K10CT-BOB', 'Bobina de encendido motor K10C Boosterjet Turbo 1.0', 'Bobina de encendido para motor K10C Boosterjet Turbo 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 39990.00, 8, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(37, 1, 'MOT-K10CT-DIS', 'Kit de cadena de distribución motor K10C Boosterjet Turbo 1.0', 'Kit de cadena de distribución para motor K10C Boosterjet Turbo 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 134990.00, 6, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(38, 1, 'MOT-K10CT-EMP', 'Empaquetadura de culata motor K10C Boosterjet Turbo 1.0', 'Empaquetadura de culata para motor K10C Boosterjet Turbo 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Ajusa', 24990.00, 9, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(39, 1, 'MOT-K10CT-TVA', 'Empaquetadura tapa de válvulas motor K10C Boosterjet Turbo 1.0', 'Empaquetadura tapa de válvulas para motor K10C Boosterjet Turbo 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 12990.00, 13, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(40, 5, 'REF-K10CT-BAG', 'Bomba de agua motor K10C Boosterjet Turbo 1.0', 'Bomba de agua para motor K10C Boosterjet Turbo 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Aisin', 33990.00, 6, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(41, 5, 'REF-K10CT-TER', 'Termostato motor K10C Boosterjet Turbo 1.0', 'Termostato para motor K10C Boosterjet Turbo 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wahler', 15990.00, 0, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(42, 4, 'ELE-K10CT-SOX', 'Sensor de oxígeno (sonda lambda) motor K10C Boosterjet Turbo 1.0', 'Sensor de oxígeno (sonda lambda) para motor K10C Boosterjet Turbo 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 40990.00, 7, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(43, 4, 'ELE-K10CT-ALT', 'Alternador motor K10C Boosterjet Turbo 1.0', 'Alternador para motor K10C Boosterjet Turbo 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 143990.00, 7, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(44, 4, 'ELE-K10CT-MPA', 'Motor de partida motor K10C Boosterjet Turbo 1.0', 'Motor de partida para motor K10C Boosterjet Turbo 1.0 (3 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 125990.00, 10, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(45, 2, 'FIL-K12B-ACE', 'Filtro de aceite motor K12B 1.2', 'Filtro de aceite para motor K12B 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 5990.00, 28, 8, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(46, 3, 'ENC-K12B-BUJ', 'Bujía de encendido motor K12B 1.2', 'Bujía de encendido para motor K12B 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 5990.00, 23, 12, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(47, 3, 'ENC-K12B-BOB', 'Bobina de encendido motor K12B 1.2', 'Bobina de encendido para motor K12B 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 34990.00, 11, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(48, 1, 'MOT-K12B-DIS', 'Kit de cadena de distribución motor K12B 1.2', 'Kit de cadena de distribución para motor K12B 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Febi', 119990.00, 8, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(49, 1, 'MOT-K12B-EMP', 'Empaquetadura de culata motor K12B 1.2', 'Empaquetadura de culata para motor K12B 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Elring', 27990.00, 11, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(50, 1, 'MOT-K12B-TVA', 'Empaquetadura tapa de válvulas motor K12B 1.2', 'Empaquetadura tapa de válvulas para motor K12B 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 13990.00, 7, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(51, 5, 'REF-K12B-BAG', 'Bomba de agua motor K12B 1.2', 'Bomba de agua para motor K12B 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GMB', 37990.00, 8, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(52, 5, 'REF-K12B-TER', 'Termostato motor K12B 1.2', 'Termostato para motor K12B 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wahler', 17990.00, 14, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(53, 4, 'ELE-K12B-SOX', 'Sensor de oxígeno (sonda lambda) motor K12B 1.2', 'Sensor de oxígeno (sonda lambda) para motor K12B 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 44990.00, 7, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(54, 4, 'ELE-K12B-ALT', 'Alternador motor K12B 1.2', 'Alternador para motor K12B 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 159990.00, 10, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(55, 4, 'ELE-K12B-MPA', 'Motor de partida motor K12B 1.2', 'Motor de partida para motor K12B 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 139990.00, 9, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(56, 2, 'FIL-K12M-ACE', 'Filtro de aceite motor K12M 1.2', 'Filtro de aceite para motor K12M 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 5990.00, 31, 8, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(57, 3, 'ENC-K12M-BUJ', 'Bujía de encendido motor K12M 1.2', 'Bujía de encendido para motor K12M 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 5990.00, 52, 12, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(58, 3, 'ENC-K12M-BOB', 'Bobina de encendido motor K12M 1.2', 'Bobina de encendido para motor K12M 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 34990.00, 14, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(59, 1, 'MOT-K12M-DIS', 'Kit de cadena de distribución motor K12M 1.2', 'Kit de cadena de distribución para motor K12M 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 119990.00, 6, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(60, 1, 'MOT-K12M-EMP', 'Empaquetadura de culata motor K12M 1.2', 'Empaquetadura de culata para motor K12M 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 27990.00, 10, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(61, 1, 'MOT-K12M-TVA', 'Empaquetadura tapa de válvulas motor K12M 1.2', 'Empaquetadura tapa de válvulas para motor K12M 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Victor Reinz', 13990.00, 10, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(62, 5, 'REF-K12M-BAG', 'Bomba de agua motor K12M 1.2', 'Bomba de agua para motor K12M 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 37990.00, 7, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(63, 5, 'REF-K12M-TER', 'Termostato motor K12M 1.2', 'Termostato para motor K12M 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 17990.00, 8, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(64, 4, 'ELE-K12M-SOX', 'Sensor de oxígeno (sonda lambda) motor K12M 1.2', 'Sensor de oxígeno (sonda lambda) para motor K12M 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 44990.00, 10, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(65, 4, 'ELE-K12M-ALT', 'Alternador motor K12M 1.2', 'Alternador para motor K12M 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 159990.00, 7, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(66, 4, 'ELE-K12M-MPA', 'Motor de partida motor K12M 1.2', 'Motor de partida para motor K12M 1.2 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 139990.00, 7, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(67, 2, 'FIL-K14B-ACE', 'Filtro de aceite motor K14B 1.4', 'Filtro de aceite para motor K14B 1.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 5990.00, 37, 8, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(68, 3, 'ENC-K14B-BUJ', 'Bujía de encendido motor K14B 1.4', 'Bujía de encendido para motor K14B 1.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 5990.00, 49, 12, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(69, 3, 'ENC-K14B-BOB', 'Bobina de encendido motor K14B 1.4', 'Bobina de encendido para motor K14B 1.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 34990.00, 9, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(70, 1, 'MOT-K14B-DIS', 'Kit de cadena de distribución motor K14B 1.4', 'Kit de cadena de distribución para motor K14B 1.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 119990.00, 3, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(71, 1, 'MOT-K14B-EMP', 'Empaquetadura de culata motor K14B 1.4', 'Empaquetadura de culata para motor K14B 1.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Elring', 27990.00, 14, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(72, 1, 'MOT-K14B-TVA', 'Empaquetadura tapa de válvulas motor K14B 1.4', 'Empaquetadura tapa de válvulas para motor K14B 1.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Elring', 13990.00, 0, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(73, 5, 'REF-K14B-BAG', 'Bomba de agua motor K14B 1.4', 'Bomba de agua para motor K14B 1.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 37990.00, 14, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(74, 5, 'REF-K14B-TER', 'Termostato motor K14B 1.4', 'Termostato para motor K14B 1.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 17990.00, 9, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(75, 4, 'ELE-K14B-SOX', 'Sensor de oxígeno (sonda lambda) motor K14B 1.4', 'Sensor de oxígeno (sonda lambda) para motor K14B 1.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 44990.00, 11, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(76, 4, 'ELE-K14B-ALT', 'Alternador motor K14B 1.4', 'Alternador para motor K14B 1.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 159990.00, 5, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(77, 4, 'ELE-K14B-MPA', 'Motor de partida motor K14B 1.4', 'Motor de partida para motor K14B 1.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 139990.00, 8, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(78, 2, 'FIL-K15B-ACE', 'Filtro de aceite motor K15B 1.5', 'Filtro de aceite para motor K15B 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 5990.00, 31, 8, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(79, 3, 'ENC-K15B-BUJ', 'Bujía de encendido motor K15B 1.5', 'Bujía de encendido para motor K15B 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 5990.00, 59, 12, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(80, 3, 'ENC-K15B-BOB', 'Bobina de encendido motor K15B 1.5', 'Bobina de encendido para motor K15B 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 34990.00, 5, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(81, 1, 'MOT-K15B-DIS', 'Kit de cadena de distribución motor K15B 1.5', 'Kit de cadena de distribución para motor K15B 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 119990.00, 5, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(82, 1, 'MOT-K15B-EMP', 'Empaquetadura de culata motor K15B 1.5', 'Empaquetadura de culata para motor K15B 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Elring', 27990.00, 14, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(83, 1, 'MOT-K15B-TVA', 'Empaquetadura tapa de válvulas motor K15B 1.5', 'Empaquetadura tapa de válvulas para motor K15B 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Elring', 13990.00, 6, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(84, 5, 'REF-K15B-BAG', 'Bomba de agua motor K15B 1.5', 'Bomba de agua para motor K15B 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Aisin', 37990.00, 5, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(85, 5, 'REF-K15B-TER', 'Termostato motor K15B 1.5', 'Termostato para motor K15B 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wahler', 17990.00, 13, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(86, 4, 'ELE-K15B-SOX', 'Sensor de oxígeno (sonda lambda) motor K15B 1.5', 'Sensor de oxígeno (sonda lambda) para motor K15B 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 44990.00, 10, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(87, 4, 'ELE-K15B-ALT', 'Alternador motor K15B 1.5', 'Alternador para motor K15B 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 159990.00, 0, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(88, 4, 'ELE-K15B-MPA', 'Motor de partida motor K15B 1.5', 'Motor de partida para motor K15B 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 139990.00, 6, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(89, 2, 'FIL-K15C-ACE', 'Filtro de aceite motor K15C Smart Hybrid 1.5', 'Filtro de aceite para motor K15C Smart Hybrid 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wega', 5990.00, 30, 8, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(90, 3, 'ENC-K15C-BUJ', 'Bujía de encendido motor K15C Smart Hybrid 1.5', 'Bujía de encendido para motor K15C Smart Hybrid 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 5990.00, 46, 12, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(91, 3, 'ENC-K15C-BOB', 'Bobina de encendido motor K15C Smart Hybrid 1.5', 'Bobina de encendido para motor K15C Smart Hybrid 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 34990.00, 2, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(92, 1, 'MOT-K15C-DIS', 'Kit de cadena de distribución motor K15C Smart Hybrid 1.5', 'Kit de cadena de distribución para motor K15C Smart Hybrid 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Febi', 119990.00, 5, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(93, 1, 'MOT-K15C-EMP', 'Empaquetadura de culata motor K15C Smart Hybrid 1.5', 'Empaquetadura de culata para motor K15C Smart Hybrid 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Victor Reinz', 27990.00, 11, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(94, 1, 'MOT-K15C-TVA', 'Empaquetadura tapa de válvulas motor K15C Smart Hybrid 1.5', 'Empaquetadura tapa de válvulas para motor K15C Smart Hybrid 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Elring', 13990.00, 10, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(95, 5, 'REF-K15C-BAG', 'Bomba de agua motor K15C Smart Hybrid 1.5', 'Bomba de agua para motor K15C Smart Hybrid 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GMB', 37990.00, 8, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(96, 5, 'REF-K15C-TER', 'Termostato motor K15C Smart Hybrid 1.5', 'Termostato para motor K15C Smart Hybrid 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 17990.00, 11, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(97, 4, 'ELE-K15C-SOX', 'Sensor de oxígeno (sonda lambda) motor K15C Smart Hybrid 1.5', 'Sensor de oxígeno (sonda lambda) para motor K15C Smart Hybrid 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 44990.00, 12, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(98, 4, 'ELE-K15C-ALT', 'Generador ISG (sistema Smart Hybrid) motor K15C Smart Hybrid 1.5', 'Generador ISG (sistema Smart Hybrid) para motor K15C Smart Hybrid 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 259990.00, 0, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(99, 4, 'ELE-K15C-MPA', 'Motor de partida motor K15C Smart Hybrid 1.5', 'Motor de partida para motor K15C Smart Hybrid 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 139990.00, 5, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(100, 2, 'FIL-M13A-ACE', 'Filtro de aceite motor M13A 1.3', 'Filtro de aceite para motor M13A 1.3 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 5990.00, 15, 8, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(101, 3, 'ENC-M13A-BUJ', 'Bujía de encendido motor M13A 1.3', 'Bujía de encendido para motor M13A 1.3 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 5990.00, 47, 12, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(102, 3, 'ENC-M13A-BOB', 'Bobina de encendido motor M13A 1.3', 'Bobina de encendido para motor M13A 1.3 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 34990.00, 5, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(103, 1, 'MOT-M13A-DIS', 'Kit de cadena de distribución motor M13A 1.3', 'Kit de cadena de distribución para motor M13A 1.3 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 119990.00, 2, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(104, 1, 'MOT-M13A-EMP', 'Empaquetadura de culata motor M13A 1.3', 'Empaquetadura de culata para motor M13A 1.3 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Elring', 27990.00, 5, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(105, 1, 'MOT-M13A-TVA', 'Empaquetadura tapa de válvulas motor M13A 1.3', 'Empaquetadura tapa de válvulas para motor M13A 1.3 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Elring', 13990.00, 6, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(106, 5, 'REF-M13A-BAG', 'Bomba de agua motor M13A 1.3', 'Bomba de agua para motor M13A 1.3 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GMB', 37990.00, 13, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(107, 5, 'REF-M13A-TER', 'Termostato motor M13A 1.3', 'Termostato para motor M13A 1.3 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wahler', 17990.00, 6, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(108, 4, 'ELE-M13A-SOX', 'Sensor de oxígeno (sonda lambda) motor M13A 1.3', 'Sensor de oxígeno (sonda lambda) para motor M13A 1.3 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 44990.00, 11, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(109, 4, 'ELE-M13A-ALT', 'Alternador motor M13A 1.3', 'Alternador para motor M13A 1.3 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 159990.00, 9, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(110, 4, 'ELE-M13A-MPA', 'Motor de partida motor M13A 1.3', 'Motor de partida para motor M13A 1.3 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 139990.00, 9, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(111, 2, 'FIL-M16A-ACE', 'Filtro de aceite motor M16A 1.6', 'Filtro de aceite para motor M16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wega', 6990.00, 41, 8, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(112, 3, 'ENC-M16A-BUJ', 'Bujía de encendido motor M16A 1.6', 'Bujía de encendido para motor M16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 6990.00, 24, 12, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(113, 3, 'ENC-M16A-BOB', 'Bobina de encendido motor M16A 1.6', 'Bobina de encendido para motor M16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 39990.00, 9, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(114, 1, 'MOT-M16A-DIS', 'Kit de cadena de distribución motor M16A 1.6', 'Kit de cadena de distribución para motor M16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 137990.00, 7, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(115, 1, 'MOT-M16A-EMP', 'Empaquetadura de culata motor M16A 1.6', 'Empaquetadura de culata para motor M16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Ajusa', 31990.00, 14, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(116, 1, 'MOT-M16A-TVA', 'Empaquetadura tapa de válvulas motor M16A 1.6', 'Empaquetadura tapa de válvulas para motor M16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Elring', 15990.00, 12, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(117, 5, 'REF-M16A-BAG', 'Bomba de agua motor M16A 1.6', 'Bomba de agua para motor M16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 43990.00, 9, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(118, 5, 'REF-M16A-TER', 'Termostato motor M16A 1.6', 'Termostato para motor M16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wahler', 20990.00, 10, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(119, 4, 'ELE-M16A-SOX', 'Sensor de oxígeno (sonda lambda) motor M16A 1.6', 'Sensor de oxígeno (sonda lambda) para motor M16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 51990.00, 13, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(120, 4, 'ELE-M16A-ALT', 'Alternador motor M16A 1.6', 'Alternador para motor M16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 183990.00, 10, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(121, 4, 'ELE-M16A-MPA', 'Motor de partida motor M16A 1.6', 'Motor de partida para motor M16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 160990.00, 7, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(122, 2, 'FIL-G15A-ACE', 'Filtro de aceite motor G15A 1.5', 'Filtro de aceite para motor G15A 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 5990.00, 15, 8, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(123, 3, 'ENC-G15A-BUJ', 'Bujía de encendido motor G15A 1.5', 'Bujía de encendido para motor G15A 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 5990.00, 23, 12, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(124, 3, 'ENC-G15A-BOB', 'Bobina de encendido motor G15A 1.5', 'Bobina de encendido para motor G15A 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 34990.00, 13, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(125, 1, 'MOT-G15A-DIS', 'Kit de correa de distribución motor G15A 1.5', 'Kit de correa de distribución para motor G15A 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Febi', 64990.00, 10, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(126, 1, 'MOT-G15A-EMP', 'Empaquetadura de culata motor G15A 1.5', 'Empaquetadura de culata para motor G15A 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Elring', 27990.00, 8, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(127, 1, 'MOT-G15A-TVA', 'Empaquetadura tapa de válvulas motor G15A 1.5', 'Empaquetadura tapa de válvulas para motor G15A 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Elring', 13990.00, 10, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(128, 5, 'REF-G15A-BAG', 'Bomba de agua motor G15A 1.5', 'Bomba de agua para motor G15A 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Aisin', 37990.00, 8, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(129, 5, 'REF-G15A-TER', 'Termostato motor G15A 1.5', 'Termostato para motor G15A 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 17990.00, 14, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(130, 4, 'ELE-G15A-SOX', 'Sensor de oxígeno (sonda lambda) motor G15A 1.5', 'Sensor de oxígeno (sonda lambda) para motor G15A 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 44990.00, 13, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(131, 4, 'ELE-G15A-ALT', 'Alternador motor G15A 1.5', 'Alternador para motor G15A 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 159990.00, 7, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(132, 4, 'ELE-G15A-MPA', 'Motor de partida motor G15A 1.5', 'Motor de partida para motor G15A 1.5 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 139990.00, 10, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(133, 2, 'FIL-G16A-ACE', 'Filtro de aceite motor G16A 1.6', 'Filtro de aceite para motor G16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wega', 6990.00, 2, 8, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(134, 3, 'ENC-G16A-BUJ', 'Bujía de encendido motor G16A 1.6', 'Bujía de encendido para motor G16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 6990.00, 40, 12, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(135, 3, 'ENC-G16A-BOB', 'Bobina de encendido motor G16A 1.6', 'Bobina de encendido para motor G16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'NGK', 39990.00, 13, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(136, 1, 'MOT-G16A-DIS', 'Kit de correa de distribución motor G16A 1.6', 'Kit de correa de distribución para motor G16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 74990.00, 11, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(137, 1, 'MOT-G16A-EMP', 'Empaquetadura de culata motor G16A 1.6', 'Empaquetadura de culata para motor G16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Victor Reinz', 31990.00, 0, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(138, 1, 'MOT-G16A-TVA', 'Empaquetadura tapa de válvulas motor G16A 1.6', 'Empaquetadura tapa de válvulas para motor G16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Victor Reinz', 15990.00, 15, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(139, 5, 'REF-G16A-BAG', 'Bomba de agua motor G16A 1.6', 'Bomba de agua para motor G16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GMB', 43990.00, 12, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(140, 5, 'REF-G16A-TER', 'Termostato motor G16A 1.6', 'Termostato para motor G16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Aisin', 20990.00, 1, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(141, 4, 'ELE-G16A-SOX', 'Sensor de oxígeno (sonda lambda) motor G16A 1.6', 'Sensor de oxígeno (sonda lambda) para motor G16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 51990.00, 12, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(142, 4, 'ELE-G16A-ALT', 'Alternador motor G16A 1.6', 'Alternador para motor G16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 183990.00, 0, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(143, 4, 'ELE-G16A-MPA', 'Motor de partida motor G16A 1.6', 'Motor de partida para motor G16A 1.6 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 160990.00, 5, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(144, 2, 'FIL-J20A-ACE', 'Filtro de aceite motor J20A 2.0', 'Filtro de aceite para motor J20A 2.0 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 7990.00, 0, 8, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(145, 3, 'ENC-J20A-BUJ', 'Bujía de encendido motor J20A 2.0', 'Bujía de encendido para motor J20A 2.0 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 7990.00, 36, 12, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(146, 3, 'ENC-J20A-BOB', 'Bobina de encendido motor J20A 2.0', 'Bobina de encendido para motor J20A 2.0 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 46990.00, 0, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(147, 1, 'MOT-J20A-DIS', 'Kit de cadena de distribución motor J20A 2.0', 'Kit de cadena de distribución para motor J20A 2.0 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 161990.00, 10, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(148, 1, 'MOT-J20A-EMP', 'Empaquetadura de culata motor J20A 2.0', 'Empaquetadura de culata para motor J20A 2.0 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Victor Reinz', 37990.00, 6, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(149, 1, 'MOT-J20A-TVA', 'Empaquetadura tapa de válvulas motor J20A 2.0', 'Empaquetadura tapa de válvulas para motor J20A 2.0 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Elring', 18990.00, 8, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(150, 5, 'REF-J20A-BAG', 'Bomba de agua motor J20A 2.0', 'Bomba de agua para motor J20A 2.0 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GMB', 51990.00, 7, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(151, 5, 'REF-J20A-TER', 'Termostato motor J20A 2.0', 'Termostato para motor J20A 2.0 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wahler', 24990.00, 15, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(152, 4, 'ELE-J20A-SOX', 'Sensor de oxígeno (sonda lambda) motor J20A 2.0', 'Sensor de oxígeno (sonda lambda) para motor J20A 2.0 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 60990.00, 5, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(153, 4, 'ELE-J20A-ALT', 'Alternador motor J20A 2.0', 'Alternador para motor J20A 2.0 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 215990.00, 0, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(154, 4, 'ELE-J20A-MPA', 'Motor de partida motor J20A 2.0', 'Motor de partida para motor J20A 2.0 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 188990.00, 8, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(155, 2, 'FIL-J24B-ACE', 'Filtro de aceite motor J24B 2.4', 'Filtro de aceite para motor J24B 2.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 7990.00, 0, 8, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(156, 3, 'ENC-J24B-BUJ', 'Bujía de encendido motor J24B 2.4', 'Bujía de encendido para motor J24B 2.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 7990.00, 53, 12, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(157, 3, 'ENC-J24B-BOB', 'Bobina de encendido motor J24B 2.4', 'Bobina de encendido para motor J24B 2.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 46990.00, 7, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(158, 1, 'MOT-J24B-DIS', 'Kit de cadena de distribución motor J24B 2.4', 'Kit de cadena de distribución para motor J24B 2.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Febi', 161990.00, 11, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(159, 1, 'MOT-J24B-EMP', 'Empaquetadura de culata motor J24B 2.4', 'Empaquetadura de culata para motor J24B 2.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Ajusa', 37990.00, 6, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(160, 1, 'MOT-J24B-TVA', 'Empaquetadura tapa de válvulas motor J24B 2.4', 'Empaquetadura tapa de válvulas para motor J24B 2.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 18990.00, 6, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(161, 5, 'REF-J24B-BAG', 'Bomba de agua motor J24B 2.4', 'Bomba de agua para motor J24B 2.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Aisin', 51990.00, 3, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(162, 5, 'REF-J24B-TER', 'Termostato motor J24B 2.4', 'Termostato para motor J24B 2.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Aisin', 24990.00, 0, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(163, 4, 'ELE-J24B-SOX', 'Sensor de oxígeno (sonda lambda) motor J24B 2.4', 'Sensor de oxígeno (sonda lambda) para motor J24B 2.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 60990.00, 10, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(164, 4, 'ELE-J24B-ALT', 'Alternador motor J24B 2.4', 'Alternador para motor J24B 2.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 215990.00, 9, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(165, 4, 'ELE-J24B-MPA', 'Motor de partida motor J24B 2.4', 'Motor de partida para motor J24B 2.4 (4 cilindros). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 188990.00, 5, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(166, 6, 'FRE-ALTO-PDL', 'Pastillas de freno delanteras (juego) Alto 800', 'Pastillas de freno delanteras (juego) para Suzuki Alto 800 (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 20990.00, 28, 5, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(167, 6, 'FRE-ALTO-DDL', 'Discos de freno delanteros (par) Alto 800', 'Discos de freno delanteros (par) para Suzuki Alto 800 (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 46990.00, 5, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(168, 6, 'FRE-ALTO-BTR', 'Balatas de freno traseras (juego) Alto 800', 'Balatas de freno traseras (juego) para Suzuki Alto 800 (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 18990.00, 12, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(169, 7, 'SUS-ALTO-BAN', 'Bandeja de suspensión delantera Alto 800', 'Bandeja de suspensión delantera para Suzuki Alto 800 (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Moog', 32990.00, 6, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(170, 7, 'SUS-ALTO-ADL', 'Amortiguadores delanteros (par) Alto 800', 'Amortiguadores delanteros (par) para Suzuki Alto 800 (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sachs', 76990.00, 8, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(171, 7, 'SUS-ALTO-ATR', 'Amortiguadores traseros (par) Alto 800', 'Amortiguadores traseros (par) para Suzuki Alto 800 (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Monroe', 59990.00, 2, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(172, 8, 'DIR-ALTO-TER', 'Terminal de dirección exterior Alto 800', 'Terminal de dirección exterior para Suzuki Alto 800 (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 12990.00, 15, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(173, 8, 'DIR-ALTO-AXI', 'Rótula axial de dirección Alto 800', 'Rótula axial de dirección para Suzuki Alto 800 (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 15990.00, 12, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(174, 9, 'TRA-ALTO-HOM', 'Homocinética lado rueda Alto 800', 'Homocinética lado rueda para Suzuki Alto 800 (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GKN', 37990.00, 7, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(175, 2, 'FIL-ALTO-COM', 'Filtro de combustible Alto 800', 'Filtro de combustible para Suzuki Alto 800 (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 12990.00, 17, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(176, 10, 'CAR-ALTO-OPI', 'Óptica delantera izquierda Alto 800', 'Óptica delantera izquierda para Suzuki Alto 800 (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 80990.00, 5, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(177, 10, 'CAR-ALTO-OPD', 'Óptica delantera derecha Alto 800', 'Óptica delantera derecha para Suzuki Alto 800 (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'TYC', 80990.00, 10, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(178, 6, 'FRE-CEL1-PDL', 'Pastillas de freno delanteras (juego) Celerio 1ª gen.', 'Pastillas de freno delanteras (juego) para Suzuki Celerio 1ª gen. (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 20990.00, 17, 5, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17');
INSERT INTO `repuestos` (`id_repuesto`, `id_categoria`, `sku`, `nombre`, `descripcion`, `codigo_oem`, `marca_fabricante`, `precio`, `stock`, `stock_minimo`, `activo`, `creado_en`, `actualizado_en`) VALUES
(179, 6, 'FRE-CEL1-DDL', 'Discos de freno delanteros (par) Celerio 1ª gen.', 'Discos de freno delanteros (par) para Suzuki Celerio 1ª gen. (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 46990.00, 10, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(180, 6, 'FRE-CEL1-BTR', 'Balatas de freno traseras (juego) Celerio 1ª gen.', 'Balatas de freno traseras (juego) para Suzuki Celerio 1ª gen. (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Akebono', 18990.00, 11, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(181, 7, 'SUS-CEL1-BAN', 'Bandeja de suspensión delantera Celerio 1ª gen.', 'Bandeja de suspensión delantera para Suzuki Celerio 1ª gen. (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Moog', 32990.00, 3, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(182, 7, 'SUS-CEL1-ADL', 'Amortiguadores delanteros (par) Celerio 1ª gen.', 'Amortiguadores delanteros (par) para Suzuki Celerio 1ª gen. (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sachs', 76990.00, 7, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(183, 7, 'SUS-CEL1-ATR', 'Amortiguadores traseros (par) Celerio 1ª gen.', 'Amortiguadores traseros (par) para Suzuki Celerio 1ª gen. (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sachs', 59990.00, 6, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(184, 8, 'DIR-CEL1-TER', 'Terminal de dirección exterior Celerio 1ª gen.', 'Terminal de dirección exterior para Suzuki Celerio 1ª gen. (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 12990.00, 14, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(185, 8, 'DIR-CEL1-AXI', 'Rótula axial de dirección Celerio 1ª gen.', 'Rótula axial de dirección para Suzuki Celerio 1ª gen. (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Moog', 15990.00, 6, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(186, 9, 'TRA-CEL1-HOM', 'Homocinética lado rueda Celerio 1ª gen.', 'Homocinética lado rueda para Suzuki Celerio 1ª gen. (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GKN', 37990.00, 3, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(187, 2, 'FIL-CEL1-COM', 'Filtro de combustible Celerio 1ª gen.', 'Filtro de combustible para Suzuki Celerio 1ª gen. (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 12990.00, 13, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(188, 10, 'CAR-CEL1-OPI', 'Óptica delantera izquierda Celerio 1ª gen.', 'Óptica delantera izquierda para Suzuki Celerio 1ª gen. (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 80990.00, 8, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(189, 10, 'CAR-CEL1-OPD', 'Óptica delantera derecha Celerio 1ª gen.', 'Óptica delantera derecha para Suzuki Celerio 1ª gen. (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'TYC', 80990.00, 8, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(190, 6, 'FRE-CEL2-PDL', 'Pastillas de freno delanteras (juego) Celerio 2ª gen.', 'Pastillas de freno delanteras (juego) para Suzuki Celerio 2ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 20990.00, 24, 5, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(191, 6, 'FRE-CEL2-DDL', 'Discos de freno delanteros (par) Celerio 2ª gen.', 'Discos de freno delanteros (par) para Suzuki Celerio 2ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 46990.00, 13, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(192, 6, 'FRE-CEL2-BTR', 'Balatas de freno traseras (juego) Celerio 2ª gen.', 'Balatas de freno traseras (juego) para Suzuki Celerio 2ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Akebono', 18990.00, 0, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(193, 7, 'SUS-CEL2-BAN', 'Bandeja de suspensión delantera Celerio 2ª gen.', 'Bandeja de suspensión delantera para Suzuki Celerio 2ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 32990.00, 6, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(194, 7, 'SUS-CEL2-ADL', 'Amortiguadores delanteros (par) Celerio 2ª gen.', 'Amortiguadores delanteros (par) para Suzuki Celerio 2ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Monroe', 76990.00, 7, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(195, 7, 'SUS-CEL2-ATR', 'Amortiguadores traseros (par) Celerio 2ª gen.', 'Amortiguadores traseros (par) para Suzuki Celerio 2ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sachs', 59990.00, 11, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(196, 8, 'DIR-CEL2-TER', 'Terminal de dirección exterior Celerio 2ª gen.', 'Terminal de dirección exterior para Suzuki Celerio 2ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 12990.00, 6, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(197, 8, 'DIR-CEL2-AXI', 'Rótula axial de dirección Celerio 2ª gen.', 'Rótula axial de dirección para Suzuki Celerio 2ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 15990.00, 8, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(198, 9, 'TRA-CEL2-HOM', 'Homocinética lado rueda Celerio 2ª gen.', 'Homocinética lado rueda para Suzuki Celerio 2ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GSP', 37990.00, 6, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(199, 2, 'FIL-CEL2-COM', 'Filtro de combustible Celerio 2ª gen.', 'Filtro de combustible para Suzuki Celerio 2ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 12990.00, 10, 3, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(200, 10, 'CAR-CEL2-OPI', 'Óptica delantera izquierda Celerio 2ª gen.', 'Óptica delantera izquierda para Suzuki Celerio 2ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 80990.00, 8, 2, 1, '2026-10-05 02:07:14', '2026-10-05 05:25:17'),
(201, 10, 'CAR-CEL2-OPD', 'Óptica delantera derecha Celerio 2ª gen.', 'Óptica delantera derecha para Suzuki Celerio 2ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 80990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(202, 6, 'FRE-SPR-PDL', 'Pastillas de freno delanteras (juego) S-Presso', 'Pastillas de freno delanteras (juego) para Suzuki S-Presso (2020-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 20990.00, 20, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(203, 6, 'FRE-SPR-DDL', 'Discos de freno delanteros (par) S-Presso', 'Discos de freno delanteros (par) para Suzuki S-Presso (2020-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 46990.00, 13, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(204, 6, 'FRE-SPR-BTR', 'Balatas de freno traseras (juego) S-Presso', 'Balatas de freno traseras (juego) para Suzuki S-Presso (2020-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 18990.00, 12, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(205, 7, 'SUS-SPR-BAN', 'Bandeja de suspensión delantera S-Presso', 'Bandeja de suspensión delantera para Suzuki S-Presso (2020-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 32990.00, 7, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(206, 7, 'SUS-SPR-ADL', 'Amortiguadores delanteros (par) S-Presso', 'Amortiguadores delanteros (par) para Suzuki S-Presso (2020-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Monroe', 76990.00, 11, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(207, 7, 'SUS-SPR-ATR', 'Amortiguadores traseros (par) S-Presso', 'Amortiguadores traseros (par) para Suzuki S-Presso (2020-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sachs', 59990.00, 12, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(208, 8, 'DIR-SPR-TER', 'Terminal de dirección exterior S-Presso', 'Terminal de dirección exterior para Suzuki S-Presso (2020-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 12990.00, 10, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(209, 8, 'DIR-SPR-AXI', 'Rótula axial de dirección S-Presso', 'Rótula axial de dirección para Suzuki S-Presso (2020-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Moog', 15990.00, 13, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(210, 9, 'TRA-SPR-HOM', 'Homocinética lado rueda S-Presso', 'Homocinética lado rueda para Suzuki S-Presso (2020-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GKN', 37990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(211, 2, 'FIL-SPR-COM', 'Filtro de combustible S-Presso', 'Filtro de combustible para Suzuki S-Presso (2020-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 12990.00, 18, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(212, 10, 'CAR-SPR-OPI', 'Óptica delantera izquierda S-Presso', 'Óptica delantera izquierda para Suzuki S-Presso (2020-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 80990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(213, 10, 'CAR-SPR-OPD', 'Óptica delantera derecha S-Presso', 'Óptica delantera derecha para Suzuki S-Presso (2020-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'TYC', 80990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(214, 6, 'FRE-SWI2-PDL', 'Pastillas de freno delanteras (juego) Swift 2ª gen.', 'Pastillas de freno delanteras (juego) para Suzuki Swift 2ª gen. (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 23990.00, 30, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(215, 6, 'FRE-SWI2-DDL', 'Discos de freno delanteros (par) Swift 2ª gen.', 'Discos de freno delanteros (par) para Suzuki Swift 2ª gen. (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 54990.00, 5, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(216, 6, 'FRE-SWI2-BTR', 'Balatas de freno traseras (juego) Swift 2ª gen.', 'Balatas de freno traseras (juego) para Suzuki Swift 2ª gen. (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 21990.00, 13, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(217, 7, 'SUS-SWI2-BAN', 'Bandeja de suspensión delantera Swift 2ª gen.', 'Bandeja de suspensión delantera para Suzuki Swift 2ª gen. (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 37990.00, 2, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(218, 7, 'SUS-SWI2-BIE', 'Bieleta de barra estabilizadora Swift 2ª gen.', 'Bieleta de barra estabilizadora para Suzuki Swift 2ª gen. (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 11990.00, 17, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(219, 7, 'SUS-SWI2-ADL', 'Amortiguadores delanteros (par) Swift 2ª gen.', 'Amortiguadores delanteros (par) para Suzuki Swift 2ª gen. (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sachs', 89990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(220, 7, 'SUS-SWI2-ATR', 'Amortiguadores traseros (par) Swift 2ª gen.', 'Amortiguadores traseros (par) para Suzuki Swift 2ª gen. (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'KYB', 69990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(221, 8, 'DIR-SWI2-TER', 'Terminal de dirección exterior Swift 2ª gen.', 'Terminal de dirección exterior para Suzuki Swift 2ª gen. (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 14990.00, 9, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(222, 8, 'DIR-SWI2-AXI', 'Rótula axial de dirección Swift 2ª gen.', 'Rótula axial de dirección para Suzuki Swift 2ª gen. (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 17990.00, 11, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(223, 9, 'TRA-SWI2-HOM', 'Homocinética lado rueda Swift 2ª gen.', 'Homocinética lado rueda para Suzuki Swift 2ª gen. (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GKN', 44990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(224, 2, 'FIL-SWI2-COM', 'Filtro de combustible Swift 2ª gen.', 'Filtro de combustible para Suzuki Swift 2ª gen. (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 14990.00, 19, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(225, 2, 'FIL-SWI2-CAB', 'Filtro de cabina (polen) Swift 2ª gen.', 'Filtro de cabina (polen) para Suzuki Swift 2ª gen. (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wega', 7990.00, 19, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(226, 10, 'CAR-SWI2-OPI', 'Óptica delantera izquierda Swift 2ª gen.', 'Óptica delantera izquierda para Suzuki Swift 2ª gen. (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'TYC', 94990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(227, 10, 'CAR-SWI2-OPD', 'Óptica delantera derecha Swift 2ª gen.', 'Óptica delantera derecha para Suzuki Swift 2ª gen. (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 94990.00, 5, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(228, 6, 'FRE-SWIS-PDL', 'Pastillas de freno delanteras (juego) Swift Sport 2ª gen.', 'Pastillas de freno delanteras (juego) para Suzuki Swift Sport 2ª gen. (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 23990.00, 3, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(229, 6, 'FRE-SWIS-DDL', 'Discos de freno delanteros (par) Swift Sport 2ª gen.', 'Discos de freno delanteros (par) para Suzuki Swift Sport 2ª gen. (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 54990.00, 8, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(230, 6, 'FRE-SWIS-PTR', 'Pastillas de freno traseras (juego) Swift Sport 2ª gen.', 'Pastillas de freno traseras (juego) para Suzuki Swift Sport 2ª gen. (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 25990.00, 2, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(231, 7, 'SUS-SWIS-BAN', 'Bandeja de suspensión delantera Swift Sport 2ª gen.', 'Bandeja de suspensión delantera para Suzuki Swift Sport 2ª gen. (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 37990.00, 9, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(232, 7, 'SUS-SWIS-BIE', 'Bieleta de barra estabilizadora Swift Sport 2ª gen.', 'Bieleta de barra estabilizadora para Suzuki Swift Sport 2ª gen. (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 11990.00, 20, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(233, 7, 'SUS-SWIS-ADL', 'Amortiguadores delanteros (par) Swift Sport 2ª gen.', 'Amortiguadores delanteros (par) para Suzuki Swift Sport 2ª gen. (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 89990.00, 12, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(234, 7, 'SUS-SWIS-ATR', 'Amortiguadores traseros (par) Swift Sport 2ª gen.', 'Amortiguadores traseros (par) para Suzuki Swift Sport 2ª gen. (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Monroe', 69990.00, 12, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(235, 8, 'DIR-SWIS-TER', 'Terminal de dirección exterior Swift Sport 2ª gen.', 'Terminal de dirección exterior para Suzuki Swift Sport 2ª gen. (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 14990.00, 18, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(236, 8, 'DIR-SWIS-AXI', 'Rótula axial de dirección Swift Sport 2ª gen.', 'Rótula axial de dirección para Suzuki Swift Sport 2ª gen. (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 17990.00, 10, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(237, 9, 'TRA-SWIS-HOM', 'Homocinética lado rueda Swift Sport 2ª gen.', 'Homocinética lado rueda para Suzuki Swift Sport 2ª gen. (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GSP', 44990.00, 11, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(238, 2, 'FIL-SWIS-COM', 'Filtro de combustible Swift Sport 2ª gen.', 'Filtro de combustible para Suzuki Swift Sport 2ª gen. (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sakura', 14990.00, 18, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(239, 2, 'FIL-SWIS-CAB', 'Filtro de cabina (polen) Swift Sport 2ª gen.', 'Filtro de cabina (polen) para Suzuki Swift Sport 2ª gen. (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 7990.00, 29, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(240, 10, 'CAR-SWIS-OPI', 'Óptica delantera izquierda Swift Sport 2ª gen.', 'Óptica delantera izquierda para Suzuki Swift Sport 2ª gen. (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'TYC', 94990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(241, 10, 'CAR-SWIS-OPD', 'Óptica delantera derecha Swift Sport 2ª gen.', 'Óptica delantera derecha para Suzuki Swift Sport 2ª gen. (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 94990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(242, 6, 'FRE-SWI3-PDL', 'Pastillas de freno delanteras (juego) Swift 3ª gen.', 'Pastillas de freno delanteras (juego) para Suzuki Swift 3ª gen. (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 23990.00, 14, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(243, 6, 'FRE-SWI3-DDL', 'Discos de freno delanteros (par) Swift 3ª gen.', 'Discos de freno delanteros (par) para Suzuki Swift 3ª gen. (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 54990.00, 14, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(244, 6, 'FRE-SWI3-BTR', 'Balatas de freno traseras (juego) Swift 3ª gen.', 'Balatas de freno traseras (juego) para Suzuki Swift 3ª gen. (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Akebono', 21990.00, 1, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(245, 7, 'SUS-SWI3-BAN', 'Bandeja de suspensión delantera Swift 3ª gen.', 'Bandeja de suspensión delantera para Suzuki Swift 3ª gen. (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 37990.00, 14, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(246, 7, 'SUS-SWI3-BIE', 'Bieleta de barra estabilizadora Swift 3ª gen.', 'Bieleta de barra estabilizadora para Suzuki Swift 3ª gen. (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 11990.00, 6, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(247, 7, 'SUS-SWI3-ADL', 'Amortiguadores delanteros (par) Swift 3ª gen.', 'Amortiguadores delanteros (par) para Suzuki Swift 3ª gen. (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Monroe', 89990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(248, 7, 'SUS-SWI3-ATR', 'Amortiguadores traseros (par) Swift 3ª gen.', 'Amortiguadores traseros (par) para Suzuki Swift 3ª gen. (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'KYB', 69990.00, 10, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(249, 8, 'DIR-SWI3-TER', 'Terminal de dirección exterior Swift 3ª gen.', 'Terminal de dirección exterior para Suzuki Swift 3ª gen. (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 14990.00, 14, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(250, 8, 'DIR-SWI3-AXI', 'Rótula axial de dirección Swift 3ª gen.', 'Rótula axial de dirección para Suzuki Swift 3ª gen. (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 17990.00, 13, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(251, 9, 'TRA-SWI3-HOM', 'Homocinética lado rueda Swift 3ª gen.', 'Homocinética lado rueda para Suzuki Swift 3ª gen. (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GKN', 44990.00, 0, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(252, 2, 'FIL-SWI3-COM', 'Filtro de combustible Swift 3ª gen.', 'Filtro de combustible para Suzuki Swift 3ª gen. (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 14990.00, 14, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(253, 2, 'FIL-SWI3-CAB', 'Filtro de cabina (polen) Swift 3ª gen.', 'Filtro de cabina (polen) para Suzuki Swift 3ª gen. (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 7990.00, 21, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(254, 10, 'CAR-SWI3-OPI', 'Óptica delantera izquierda Swift 3ª gen.', 'Óptica delantera izquierda para Suzuki Swift 3ª gen. (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'TYC', 94990.00, 0, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(255, 10, 'CAR-SWI3-OPD', 'Óptica delantera derecha Swift 3ª gen.', 'Óptica delantera derecha para Suzuki Swift 3ª gen. (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 94990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(256, 6, 'FRE-BAL2-PDL', 'Pastillas de freno delanteras (juego) Baleno 2ª gen.', 'Pastillas de freno delanteras (juego) para Suzuki Baleno 2ª gen. (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 23990.00, 17, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(257, 6, 'FRE-BAL2-DDL', 'Discos de freno delanteros (par) Baleno 2ª gen.', 'Discos de freno delanteros (par) para Suzuki Baleno 2ª gen. (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 54990.00, 7, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(258, 6, 'FRE-BAL2-BTR', 'Balatas de freno traseras (juego) Baleno 2ª gen.', 'Balatas de freno traseras (juego) para Suzuki Baleno 2ª gen. (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Akebono', 21990.00, 15, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(259, 7, 'SUS-BAL2-BAN', 'Bandeja de suspensión delantera Baleno 2ª gen.', 'Bandeja de suspensión delantera para Suzuki Baleno 2ª gen. (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Moog', 37990.00, 13, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(260, 7, 'SUS-BAL2-BIE', 'Bieleta de barra estabilizadora Baleno 2ª gen.', 'Bieleta de barra estabilizadora para Suzuki Baleno 2ª gen. (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 11990.00, 17, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(261, 7, 'SUS-BAL2-ADL', 'Amortiguadores delanteros (par) Baleno 2ª gen.', 'Amortiguadores delanteros (par) para Suzuki Baleno 2ª gen. (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'KYB', 89990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(262, 7, 'SUS-BAL2-ATR', 'Amortiguadores traseros (par) Baleno 2ª gen.', 'Amortiguadores traseros (par) para Suzuki Baleno 2ª gen. (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Monroe', 69990.00, 11, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(263, 8, 'DIR-BAL2-TER', 'Terminal de dirección exterior Baleno 2ª gen.', 'Terminal de dirección exterior para Suzuki Baleno 2ª gen. (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Moog', 14990.00, 18, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(264, 8, 'DIR-BAL2-AXI', 'Rótula axial de dirección Baleno 2ª gen.', 'Rótula axial de dirección para Suzuki Baleno 2ª gen. (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Moog', 17990.00, 7, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(265, 9, 'TRA-BAL2-HOM', 'Homocinética lado rueda Baleno 2ª gen.', 'Homocinética lado rueda para Suzuki Baleno 2ª gen. (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GKN', 44990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(266, 2, 'FIL-BAL2-COM', 'Filtro de combustible Baleno 2ª gen.', 'Filtro de combustible para Suzuki Baleno 2ª gen. (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 14990.00, 21, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(267, 2, 'FIL-BAL2-CAB', 'Filtro de cabina (polen) Baleno 2ª gen.', 'Filtro de cabina (polen) para Suzuki Baleno 2ª gen. (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 7990.00, 26, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(268, 10, 'CAR-BAL2-OPI', 'Óptica delantera izquierda Baleno 2ª gen.', 'Óptica delantera izquierda para Suzuki Baleno 2ª gen. (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 94990.00, 10, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(269, 10, 'CAR-BAL2-OPD', 'Óptica delantera derecha Baleno 2ª gen.', 'Óptica delantera derecha para Suzuki Baleno 2ª gen. (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'TYC', 94990.00, 0, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(270, 6, 'FRE-BAL3-PDL', 'Pastillas de freno delanteras (juego) Baleno 3ª gen.', 'Pastillas de freno delanteras (juego) para Suzuki Baleno 3ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 23990.00, 23, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(271, 6, 'FRE-BAL3-DDL', 'Discos de freno delanteros (par) Baleno 3ª gen.', 'Discos de freno delanteros (par) para Suzuki Baleno 3ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 54990.00, 14, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(272, 6, 'FRE-BAL3-BTR', 'Balatas de freno traseras (juego) Baleno 3ª gen.', 'Balatas de freno traseras (juego) para Suzuki Baleno 3ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Akebono', 21990.00, 13, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(273, 7, 'SUS-BAL3-BAN', 'Bandeja de suspensión delantera Baleno 3ª gen.', 'Bandeja de suspensión delantera para Suzuki Baleno 3ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 37990.00, 10, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(274, 7, 'SUS-BAL3-BIE', 'Bieleta de barra estabilizadora Baleno 3ª gen.', 'Bieleta de barra estabilizadora para Suzuki Baleno 3ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 11990.00, 19, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(275, 7, 'SUS-BAL3-ADL', 'Amortiguadores delanteros (par) Baleno 3ª gen.', 'Amortiguadores delanteros (par) para Suzuki Baleno 3ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Monroe', 89990.00, 12, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(276, 7, 'SUS-BAL3-ATR', 'Amortiguadores traseros (par) Baleno 3ª gen.', 'Amortiguadores traseros (par) para Suzuki Baleno 3ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Monroe', 69990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(277, 8, 'DIR-BAL3-TER', 'Terminal de dirección exterior Baleno 3ª gen.', 'Terminal de dirección exterior para Suzuki Baleno 3ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 14990.00, 2, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(278, 8, 'DIR-BAL3-AXI', 'Rótula axial de dirección Baleno 3ª gen.', 'Rótula axial de dirección para Suzuki Baleno 3ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 17990.00, 10, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(279, 9, 'TRA-BAL3-HOM', 'Homocinética lado rueda Baleno 3ª gen.', 'Homocinética lado rueda para Suzuki Baleno 3ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 44990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(280, 2, 'FIL-BAL3-COM', 'Filtro de combustible Baleno 3ª gen.', 'Filtro de combustible para Suzuki Baleno 3ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 14990.00, 12, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(281, 2, 'FIL-BAL3-CAB', 'Filtro de cabina (polen) Baleno 3ª gen.', 'Filtro de cabina (polen) para Suzuki Baleno 3ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wega', 7990.00, 14, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(282, 10, 'CAR-BAL3-OPI', 'Óptica delantera izquierda Baleno 3ª gen.', 'Óptica delantera izquierda para Suzuki Baleno 3ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'TYC', 94990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(283, 10, 'CAR-BAL3-OPD', 'Óptica delantera derecha Baleno 3ª gen.', 'Óptica delantera derecha para Suzuki Baleno 3ª gen. (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'TYC', 94990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(284, 6, 'FRE-FRX-PDL', 'Pastillas de freno delanteras (juego) Fronx', 'Pastillas de freno delanteras (juego) para Suzuki Fronx (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Akebono', 23990.00, 16, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(285, 6, 'FRE-FRX-DDL', 'Discos de freno delanteros (par) Fronx', 'Discos de freno delanteros (par) para Suzuki Fronx (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 54990.00, 14, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(286, 6, 'FRE-FRX-BTR', 'Balatas de freno traseras (juego) Fronx', 'Balatas de freno traseras (juego) para Suzuki Fronx (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 21990.00, 13, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(287, 7, 'SUS-FRX-BAN', 'Bandeja de suspensión delantera Fronx', 'Bandeja de suspensión delantera para Suzuki Fronx (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 37990.00, 5, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(288, 7, 'SUS-FRX-BIE', 'Bieleta de barra estabilizadora Fronx', 'Bieleta de barra estabilizadora para Suzuki Fronx (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 11990.00, 15, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(289, 7, 'SUS-FRX-ADL', 'Amortiguadores delanteros (par) Fronx', 'Amortiguadores delanteros (par) para Suzuki Fronx (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'KYB', 89990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(290, 7, 'SUS-FRX-ATR', 'Amortiguadores traseros (par) Fronx', 'Amortiguadores traseros (par) para Suzuki Fronx (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'KYB', 69990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(291, 8, 'DIR-FRX-TER', 'Terminal de dirección exterior Fronx', 'Terminal de dirección exterior para Suzuki Fronx (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 14990.00, 0, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(292, 8, 'DIR-FRX-AXI', 'Rótula axial de dirección Fronx', 'Rótula axial de dirección para Suzuki Fronx (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 17990.00, 6, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(293, 9, 'TRA-FRX-HOM', 'Homocinética lado rueda Fronx', 'Homocinética lado rueda para Suzuki Fronx (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GSP', 44990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(294, 2, 'FIL-FRX-COM', 'Filtro de combustible Fronx', 'Filtro de combustible para Suzuki Fronx (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 14990.00, 12, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(295, 2, 'FIL-FRX-CAB', 'Filtro de cabina (polen) Fronx', 'Filtro de cabina (polen) para Suzuki Fronx (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wega', 7990.00, 12, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(296, 10, 'CAR-FRX-OPI', 'Óptica delantera izquierda Fronx', 'Óptica delantera izquierda para Suzuki Fronx (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'TYC', 94990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(297, 10, 'CAR-FRX-OPD', 'Óptica delantera derecha Fronx', 'Óptica delantera derecha para Suzuki Fronx (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'TYC', 94990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(298, 6, 'FRE-ERT1-PDL', 'Pastillas de freno delanteras (juego) Ertiga 1ª gen.', 'Pastillas de freno delanteras (juego) para Suzuki Ertiga 1ª gen. (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 23990.00, 8, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(299, 6, 'FRE-ERT1-DDL', 'Discos de freno delanteros (par) Ertiga 1ª gen.', 'Discos de freno delanteros (par) para Suzuki Ertiga 1ª gen. (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 54990.00, 0, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(300, 6, 'FRE-ERT1-BTR', 'Balatas de freno traseras (juego) Ertiga 1ª gen.', 'Balatas de freno traseras (juego) para Suzuki Ertiga 1ª gen. (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Akebono', 21990.00, 10, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(301, 7, 'SUS-ERT1-BAN', 'Bandeja de suspensión delantera Ertiga 1ª gen.', 'Bandeja de suspensión delantera para Suzuki Ertiga 1ª gen. (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 37990.00, 13, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(302, 7, 'SUS-ERT1-BIE', 'Bieleta de barra estabilizadora Ertiga 1ª gen.', 'Bieleta de barra estabilizadora para Suzuki Ertiga 1ª gen. (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 11990.00, 15, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(303, 7, 'SUS-ERT1-ADL', 'Amortiguadores delanteros (par) Ertiga 1ª gen.', 'Amortiguadores delanteros (par) para Suzuki Ertiga 1ª gen. (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'KYB', 89990.00, 10, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(304, 7, 'SUS-ERT1-ATR', 'Amortiguadores traseros (par) Ertiga 1ª gen.', 'Amortiguadores traseros (par) para Suzuki Ertiga 1ª gen. (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sachs', 69990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(305, 8, 'DIR-ERT1-TER', 'Terminal de dirección exterior Ertiga 1ª gen.', 'Terminal de dirección exterior para Suzuki Ertiga 1ª gen. (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 14990.00, 1, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(306, 8, 'DIR-ERT1-AXI', 'Rótula axial de dirección Ertiga 1ª gen.', 'Rótula axial de dirección para Suzuki Ertiga 1ª gen. (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 17990.00, 12, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(307, 9, 'TRA-ERT1-HOM', 'Homocinética lado rueda Ertiga 1ª gen.', 'Homocinética lado rueda para Suzuki Ertiga 1ª gen. (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GSP', 44990.00, 5, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(308, 2, 'FIL-ERT1-COM', 'Filtro de combustible Ertiga 1ª gen.', 'Filtro de combustible para Suzuki Ertiga 1ª gen. (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sakura', 14990.00, 10, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(309, 2, 'FIL-ERT1-CAB', 'Filtro de cabina (polen) Ertiga 1ª gen.', 'Filtro de cabina (polen) para Suzuki Ertiga 1ª gen. (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 7990.00, 0, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(310, 10, 'CAR-ERT1-OPI', 'Óptica delantera izquierda Ertiga 1ª gen.', 'Óptica delantera izquierda para Suzuki Ertiga 1ª gen. (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 94990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(311, 10, 'CAR-ERT1-OPD', 'Óptica delantera derecha Ertiga 1ª gen.', 'Óptica delantera derecha para Suzuki Ertiga 1ª gen. (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 94990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(312, 6, 'FRE-ERT2-PDL', 'Pastillas de freno delanteras (juego) Ertiga 2ª gen.', 'Pastillas de freno delanteras (juego) para Suzuki Ertiga 2ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Akebono', 23990.00, 9, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(313, 6, 'FRE-ERT2-DDL', 'Discos de freno delanteros (par) Ertiga 2ª gen.', 'Discos de freno delanteros (par) para Suzuki Ertiga 2ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 54990.00, 9, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(314, 6, 'FRE-ERT2-BTR', 'Balatas de freno traseras (juego) Ertiga 2ª gen.', 'Balatas de freno traseras (juego) para Suzuki Ertiga 2ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 21990.00, 10, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(315, 7, 'SUS-ERT2-BAN', 'Bandeja de suspensión delantera Ertiga 2ª gen.', 'Bandeja de suspensión delantera para Suzuki Ertiga 2ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 37990.00, 9, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(316, 7, 'SUS-ERT2-BIE', 'Bieleta de barra estabilizadora Ertiga 2ª gen.', 'Bieleta de barra estabilizadora para Suzuki Ertiga 2ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 11990.00, 6, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(317, 7, 'SUS-ERT2-ADL', 'Amortiguadores delanteros (par) Ertiga 2ª gen.', 'Amortiguadores delanteros (par) para Suzuki Ertiga 2ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 89990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(318, 7, 'SUS-ERT2-ATR', 'Amortiguadores traseros (par) Ertiga 2ª gen.', 'Amortiguadores traseros (par) para Suzuki Ertiga 2ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Monroe', 69990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(319, 8, 'DIR-ERT2-TER', 'Terminal de dirección exterior Ertiga 2ª gen.', 'Terminal de dirección exterior para Suzuki Ertiga 2ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 14990.00, 6, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(320, 8, 'DIR-ERT2-AXI', 'Rótula axial de dirección Ertiga 2ª gen.', 'Rótula axial de dirección para Suzuki Ertiga 2ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Moog', 17990.00, 5, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(321, 9, 'TRA-ERT2-HOM', 'Homocinética lado rueda Ertiga 2ª gen.', 'Homocinética lado rueda para Suzuki Ertiga 2ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GKN', 44990.00, 11, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(322, 2, 'FIL-ERT2-COM', 'Filtro de combustible Ertiga 2ª gen.', 'Filtro de combustible para Suzuki Ertiga 2ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 14990.00, 19, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(323, 2, 'FIL-ERT2-CAB', 'Filtro de cabina (polen) Ertiga 2ª gen.', 'Filtro de cabina (polen) para Suzuki Ertiga 2ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wega', 7990.00, 12, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(324, 10, 'CAR-ERT2-OPI', 'Óptica delantera izquierda Ertiga 2ª gen.', 'Óptica delantera izquierda para Suzuki Ertiga 2ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 94990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(325, 10, 'CAR-ERT2-OPD', 'Óptica delantera derecha Ertiga 2ª gen.', 'Óptica delantera derecha para Suzuki Ertiga 2ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 94990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(326, 6, 'FRE-CIAZ-PDL', 'Pastillas de freno delanteras (juego) Ciaz', 'Pastillas de freno delanteras (juego) para Suzuki Ciaz (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Akebono', 23990.00, 8, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(327, 6, 'FRE-CIAZ-DDL', 'Discos de freno delanteros (par) Ciaz', 'Discos de freno delanteros (par) para Suzuki Ciaz (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 54990.00, 9, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(328, 6, 'FRE-CIAZ-BTR', 'Balatas de freno traseras (juego) Ciaz', 'Balatas de freno traseras (juego) para Suzuki Ciaz (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 21990.00, 15, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(329, 7, 'SUS-CIAZ-BAN', 'Bandeja de suspensión delantera Ciaz', 'Bandeja de suspensión delantera para Suzuki Ciaz (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 37990.00, 7, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(330, 7, 'SUS-CIAZ-BIE', 'Bieleta de barra estabilizadora Ciaz', 'Bieleta de barra estabilizadora para Suzuki Ciaz (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 11990.00, 18, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(331, 7, 'SUS-CIAZ-ADL', 'Amortiguadores delanteros (par) Ciaz', 'Amortiguadores delanteros (par) para Suzuki Ciaz (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 89990.00, 11, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(332, 7, 'SUS-CIAZ-ATR', 'Amortiguadores traseros (par) Ciaz', 'Amortiguadores traseros (par) para Suzuki Ciaz (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 69990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(333, 8, 'DIR-CIAZ-TER', 'Terminal de dirección exterior Ciaz', 'Terminal de dirección exterior para Suzuki Ciaz (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Moog', 14990.00, 12, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(334, 8, 'DIR-CIAZ-AXI', 'Rótula axial de dirección Ciaz', 'Rótula axial de dirección para Suzuki Ciaz (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 17990.00, 16, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(335, 9, 'TRA-CIAZ-HOM', 'Homocinética lado rueda Ciaz', 'Homocinética lado rueda para Suzuki Ciaz (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GSP', 44990.00, 11, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(336, 2, 'FIL-CIAZ-COM', 'Filtro de combustible Ciaz', 'Filtro de combustible para Suzuki Ciaz (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 14990.00, 6, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(337, 2, 'FIL-CIAZ-CAB', 'Filtro de cabina (polen) Ciaz', 'Filtro de cabina (polen) para Suzuki Ciaz (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 7990.00, 10, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(338, 10, 'CAR-CIAZ-OPI', 'Óptica delantera izquierda Ciaz', 'Óptica delantera izquierda para Suzuki Ciaz (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'TYC', 94990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(339, 10, 'CAR-CIAZ-OPD', 'Óptica delantera derecha Ciaz', 'Óptica delantera derecha para Suzuki Ciaz (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 94990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(340, 6, 'FRE-APV-PDL', 'Pastillas de freno delanteras (juego) APV', 'Pastillas de freno delanteras (juego) para Suzuki APV (2006-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Akebono', 23990.00, 19, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(341, 6, 'FRE-APV-DDL', 'Discos de freno delanteros (par) APV', 'Discos de freno delanteros (par) para Suzuki APV (2006-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 54990.00, 11, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(342, 6, 'FRE-APV-BTR', 'Balatas de freno traseras (juego) APV', 'Balatas de freno traseras (juego) para Suzuki APV (2006-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 21990.00, 0, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(343, 7, 'SUS-APV-BAN', 'Bandeja de suspensión delantera APV', 'Bandeja de suspensión delantera para Suzuki APV (2006-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Moog', 37990.00, 12, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(344, 7, 'SUS-APV-ADL', 'Amortiguadores delanteros (par) APV', 'Amortiguadores delanteros (par) para Suzuki APV (2006-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Monroe', 89990.00, 12, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(345, 7, 'SUS-APV-ATR', 'Amortiguadores traseros (par) APV', 'Amortiguadores traseros (par) para Suzuki APV (2006-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sachs', 69990.00, 5, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(346, 8, 'DIR-APV-TER', 'Terminal de dirección exterior APV', 'Terminal de dirección exterior para Suzuki APV (2006-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 14990.00, 13, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(347, 8, 'DIR-APV-AXI', 'Rótula axial de dirección APV', 'Rótula axial de dirección para Suzuki APV (2006-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 17990.00, 6, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(348, 2, 'FIL-APV-COM', 'Filtro de combustible APV', 'Filtro de combustible para Suzuki APV (2006-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sakura', 14990.00, 9, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(349, 10, 'CAR-APV-OPI', 'Óptica delantera izquierda APV', 'Óptica delantera izquierda para Suzuki APV (2006-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 94990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17');
INSERT INTO `repuestos` (`id_repuesto`, `id_categoria`, `sku`, `nombre`, `descripcion`, `codigo_oem`, `marca_fabricante`, `precio`, `stock`, `stock_minimo`, `activo`, `creado_en`, `actualizado_en`) VALUES
(350, 10, 'CAR-APV-OPD', 'Óptica delantera derecha APV', 'Óptica delantera derecha para Suzuki APV (2006-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 94990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(351, 6, 'FRE-CARRY-PDL', 'Pastillas de freno delanteras (juego) Carry', 'Pastillas de freno delanteras (juego) para Suzuki Carry (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Akebono', 23990.00, 29, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(352, 6, 'FRE-CARRY-DDL', 'Discos de freno delanteros (par) Carry', 'Discos de freno delanteros (par) para Suzuki Carry (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 54990.00, 13, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(353, 6, 'FRE-CARRY-BTR', 'Balatas de freno traseras (juego) Carry', 'Balatas de freno traseras (juego) para Suzuki Carry (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Akebono', 21990.00, 14, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(354, 7, 'SUS-CARRY-BAN', 'Bandeja de suspensión delantera Carry', 'Bandeja de suspensión delantera para Suzuki Carry (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 37990.00, 8, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(355, 7, 'SUS-CARRY-ADL', 'Amortiguadores delanteros (par) Carry', 'Amortiguadores delanteros (par) para Suzuki Carry (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sachs', 89990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(356, 7, 'SUS-CARRY-ATR', 'Amortiguadores traseros (par) Carry', 'Amortiguadores traseros (par) para Suzuki Carry (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 69990.00, 5, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(357, 8, 'DIR-CARRY-TER', 'Terminal de dirección exterior Carry', 'Terminal de dirección exterior para Suzuki Carry (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 14990.00, 9, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(358, 8, 'DIR-CARRY-AXI', 'Rótula axial de dirección Carry', 'Rótula axial de dirección para Suzuki Carry (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Moog', 17990.00, 12, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(359, 2, 'FIL-CARRY-COM', 'Filtro de combustible Carry', 'Filtro de combustible para Suzuki Carry (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sakura', 14990.00, 3, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(360, 10, 'CAR-CARRY-OPI', 'Óptica delantera izquierda Carry', 'Óptica delantera izquierda para Suzuki Carry (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 94990.00, 5, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(361, 10, 'CAR-CARRY-OPD', 'Óptica delantera derecha Carry', 'Óptica delantera derecha para Suzuki Carry (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'TYC', 94990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(362, 6, 'FRE-VIT4-PDL', 'Pastillas de freno delanteras (juego) Vitara 4ª gen.', 'Pastillas de freno delanteras (juego) para Suzuki Vitara 4ª gen. (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 30990.00, 15, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(363, 6, 'FRE-VIT4-DDL', 'Discos de freno delanteros (par) Vitara 4ª gen.', 'Discos de freno delanteros (par) para Suzuki Vitara 4ª gen. (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 71990.00, 9, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(364, 6, 'FRE-VIT4-BTR', 'Balatas de freno traseras (juego) Vitara 4ª gen.', 'Balatas de freno traseras (juego) para Suzuki Vitara 4ª gen. (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Akebono', 28990.00, 12, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(365, 7, 'SUS-VIT4-BAN', 'Bandeja de suspensión delantera Vitara 4ª gen.', 'Bandeja de suspensión delantera para Suzuki Vitara 4ª gen. (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Moog', 49990.00, 10, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(366, 7, 'SUS-VIT4-BIE', 'Bieleta de barra estabilizadora Vitara 4ª gen.', 'Bieleta de barra estabilizadora para Suzuki Vitara 4ª gen. (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 15990.00, 12, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(367, 7, 'SUS-VIT4-ADL', 'Amortiguadores delanteros (par) Vitara 4ª gen.', 'Amortiguadores delanteros (par) para Suzuki Vitara 4ª gen. (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'KYB', 116990.00, 0, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(368, 7, 'SUS-VIT4-ATR', 'Amortiguadores traseros (par) Vitara 4ª gen.', 'Amortiguadores traseros (par) para Suzuki Vitara 4ª gen. (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'KYB', 90990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(369, 8, 'DIR-VIT4-TER', 'Terminal de dirección exterior Vitara 4ª gen.', 'Terminal de dirección exterior para Suzuki Vitara 4ª gen. (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 19990.00, 10, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(370, 8, 'DIR-VIT4-AXI', 'Rótula axial de dirección Vitara 4ª gen.', 'Rótula axial de dirección para Suzuki Vitara 4ª gen. (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 23990.00, 16, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(371, 9, 'TRA-VIT4-HOM', 'Homocinética lado rueda Vitara 4ª gen.', 'Homocinética lado rueda para Suzuki Vitara 4ª gen. (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GSP', 58990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(372, 2, 'FIL-VIT4-COM', 'Filtro de combustible Vitara 4ª gen.', 'Filtro de combustible para Suzuki Vitara 4ª gen. (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 19990.00, 8, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(373, 2, 'FIL-VIT4-CAB', 'Filtro de cabina (polen) Vitara 4ª gen.', 'Filtro de cabina (polen) para Suzuki Vitara 4ª gen. (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wega', 10990.00, 12, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(374, 10, 'CAR-VIT4-OPI', 'Óptica delantera izquierda Vitara 4ª gen.', 'Óptica delantera izquierda para Suzuki Vitara 4ª gen. (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 123990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(375, 10, 'CAR-VIT4-OPD', 'Óptica delantera derecha Vitara 4ª gen.', 'Óptica delantera derecha para Suzuki Vitara 4ª gen. (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'TYC', 123990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(376, 6, 'FRE-GV3-PDL', 'Pastillas de freno delanteras (juego) Grand Vitara / Grand Nomade 3ª gen.', 'Pastillas de freno delanteras (juego) para Suzuki Grand Vitara / Grand Nomade 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 30990.00, 28, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(377, 6, 'FRE-GV3-DDL', 'Discos de freno delanteros (par) Grand Vitara / Grand Nomade 3ª gen.', 'Discos de freno delanteros (par) para Suzuki Grand Vitara / Grand Nomade 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 71990.00, 12, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(378, 6, 'FRE-GV3-BTR', 'Balatas de freno traseras (juego) Grand Vitara / Grand Nomade 3ª gen.', 'Balatas de freno traseras (juego) para Suzuki Grand Vitara / Grand Nomade 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 28990.00, 7, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(379, 7, 'SUS-GV3-BAN', 'Bandeja de suspensión delantera Grand Vitara / Grand Nomade 3ª gen.', 'Bandeja de suspensión delantera para Suzuki Grand Vitara / Grand Nomade 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 49990.00, 12, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(380, 7, 'SUS-GV3-BIE', 'Bieleta de barra estabilizadora Grand Vitara / Grand Nomade 3ª gen.', 'Bieleta de barra estabilizadora para Suzuki Grand Vitara / Grand Nomade 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 15990.00, 20, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(381, 8, 'DIR-GV3-TER', 'Terminal de dirección exterior Grand Vitara / Grand Nomade 3ª gen.', 'Terminal de dirección exterior para Suzuki Grand Vitara / Grand Nomade 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 19990.00, 15, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(382, 8, 'DIR-GV3-AXI', 'Rótula axial de dirección Grand Vitara / Grand Nomade 3ª gen.', 'Rótula axial de dirección para Suzuki Grand Vitara / Grand Nomade 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 23990.00, 13, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(383, 9, 'TRA-GV3-HOM', 'Homocinética lado rueda Grand Vitara / Grand Nomade 3ª gen.', 'Homocinética lado rueda para Suzuki Grand Vitara / Grand Nomade 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GSP', 58990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(384, 2, 'FIL-GV3-COM', 'Filtro de combustible Grand Vitara / Grand Nomade 3ª gen.', 'Filtro de combustible para Suzuki Grand Vitara / Grand Nomade 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 19990.00, 17, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(385, 2, 'FIL-GV3-CAB', 'Filtro de cabina (polen) Grand Vitara / Grand Nomade 3ª gen.', 'Filtro de cabina (polen) para Suzuki Grand Vitara / Grand Nomade 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wega', 10990.00, 19, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(386, 10, 'CAR-GV3-OPI', 'Óptica delantera izquierda Grand Vitara / Grand Nomade 3ª gen.', 'Óptica delantera izquierda para Suzuki Grand Vitara / Grand Nomade 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 123990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(387, 10, 'CAR-GV3-OPD', 'Óptica delantera derecha Grand Vitara / Grand Nomade 3ª gen.', 'Óptica delantera derecha para Suzuki Grand Vitara / Grand Nomade 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 123990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(388, 6, 'FRE-GN2-PDL', 'Pastillas de freno delanteras (juego) Grand Nomade 2ª gen.', 'Pastillas de freno delanteras (juego) para Suzuki Grand Nomade 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 30990.00, 10, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(389, 6, 'FRE-GN2-DDL', 'Discos de freno delanteros (par) Grand Nomade 2ª gen.', 'Discos de freno delanteros (par) para Suzuki Grand Nomade 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 71990.00, 13, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(390, 6, 'FRE-GN2-BTR', 'Balatas de freno traseras (juego) Grand Nomade 2ª gen.', 'Balatas de freno traseras (juego) para Suzuki Grand Nomade 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 28990.00, 7, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(391, 7, 'SUS-GN2-BAN', 'Bandeja de suspensión delantera Grand Nomade 2ª gen.', 'Bandeja de suspensión delantera para Suzuki Grand Nomade 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, '555', 49990.00, 0, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(392, 7, 'SUS-GN2-BIE', 'Bieleta de barra estabilizadora Grand Nomade 2ª gen.', 'Bieleta de barra estabilizadora para Suzuki Grand Nomade 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Moog', 15990.00, 12, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(393, 7, 'SUS-GN2-ADL', 'Amortiguadores delanteros (par) Grand Nomade 2ª gen.', 'Amortiguadores delanteros (par) para Suzuki Grand Nomade 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'KYB', 116990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(394, 7, 'SUS-GN2-ATR', 'Amortiguadores traseros (par) Grand Nomade 2ª gen.', 'Amortiguadores traseros (par) para Suzuki Grand Nomade 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'KYB', 90990.00, 10, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(395, 8, 'DIR-GN2-TER', 'Terminal de dirección exterior Grand Nomade 2ª gen.', 'Terminal de dirección exterior para Suzuki Grand Nomade 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'CTR', 19990.00, 12, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(396, 8, 'DIR-GN2-AXI', 'Rótula axial de dirección Grand Nomade 2ª gen.', 'Rótula axial de dirección para Suzuki Grand Nomade 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 23990.00, 9, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(397, 9, 'TRA-GN2-HOM', 'Homocinética lado rueda Grand Nomade 2ª gen.', 'Homocinética lado rueda para Suzuki Grand Nomade 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 58990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(398, 2, 'FIL-GN2-COM', 'Filtro de combustible Grand Nomade 2ª gen.', 'Filtro de combustible para Suzuki Grand Nomade 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 19990.00, 0, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(399, 10, 'CAR-GN2-OPI', 'Óptica delantera izquierda Grand Nomade 2ª gen.', 'Óptica delantera izquierda para Suzuki Grand Nomade 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 123990.00, 10, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(400, 10, 'CAR-GN2-OPD', 'Óptica delantera derecha Grand Nomade 2ª gen.', 'Óptica delantera derecha para Suzuki Grand Nomade 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 123990.00, 10, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(401, 6, 'FRE-JIM3-PDL', 'Pastillas de freno delanteras (juego) Jimny 3ª gen.', 'Pastillas de freno delanteras (juego) para Suzuki Jimny 3ª gen. (2005-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 30990.00, 16, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(402, 6, 'FRE-JIM3-DDL', 'Discos de freno delanteros (par) Jimny 3ª gen.', 'Discos de freno delanteros (par) para Suzuki Jimny 3ª gen. (2005-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 71990.00, 5, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(403, 6, 'FRE-JIM3-BTR', 'Balatas de freno traseras (juego) Jimny 3ª gen.', 'Balatas de freno traseras (juego) para Suzuki Jimny 3ª gen. (2005-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 28990.00, 10, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(404, 7, 'SUS-JIM3-ADL', 'Amortiguadores delanteros (par) Jimny 3ª gen.', 'Amortiguadores delanteros (par) para Suzuki Jimny 3ª gen. (2005-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sachs', 116990.00, 10, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(405, 7, 'SUS-JIM3-ATR', 'Amortiguadores traseros (par) Jimny 3ª gen.', 'Amortiguadores traseros (par) para Suzuki Jimny 3ª gen. (2005-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Monroe', 90990.00, 11, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(406, 8, 'DIR-JIM3-TER', 'Terminal de dirección exterior Jimny 3ª gen.', 'Terminal de dirección exterior para Suzuki Jimny 3ª gen. (2005-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Moog', 19990.00, 16, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(407, 9, 'TRA-JIM3-HOM', 'Homocinética lado rueda Jimny 3ª gen.', 'Homocinética lado rueda para Suzuki Jimny 3ª gen. (2005-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GKN', 58990.00, 5, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(408, 2, 'FIL-JIM3-COM', 'Filtro de combustible Jimny 3ª gen.', 'Filtro de combustible para Suzuki Jimny 3ª gen. (2005-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 19990.00, 8, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(409, 10, 'CAR-JIM3-OPI', 'Óptica delantera izquierda Jimny 3ª gen.', 'Óptica delantera izquierda para Suzuki Jimny 3ª gen. (2005-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 123990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(410, 10, 'CAR-JIM3-OPD', 'Óptica delantera derecha Jimny 3ª gen.', 'Óptica delantera derecha para Suzuki Jimny 3ª gen. (2005-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'TYC', 123990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(411, 6, 'FRE-JIM4-PDL', 'Pastillas de freno delanteras (juego) Jimny 4ª gen.', 'Pastillas de freno delanteras (juego) para Suzuki Jimny 4ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Akebono', 30990.00, 15, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(412, 6, 'FRE-JIM4-DDL', 'Discos de freno delanteros (par) Jimny 4ª gen.', 'Discos de freno delanteros (par) para Suzuki Jimny 4ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Brembo', 71990.00, 11, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(413, 6, 'FRE-JIM4-BTR', 'Balatas de freno traseras (juego) Jimny 4ª gen.', 'Balatas de freno traseras (juego) para Suzuki Jimny 4ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 28990.00, 2, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(414, 7, 'SUS-JIM4-ADL', 'Amortiguadores delanteros (par) Jimny 4ª gen.', 'Amortiguadores delanteros (par) para Suzuki Jimny 4ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sachs', 116990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(415, 7, 'SUS-JIM4-ATR', 'Amortiguadores traseros (par) Jimny 4ª gen.', 'Amortiguadores traseros (par) para Suzuki Jimny 4ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Monroe', 90990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(416, 8, 'DIR-JIM4-TER', 'Terminal de dirección exterior Jimny 4ª gen.', 'Terminal de dirección exterior para Suzuki Jimny 4ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 19990.00, 8, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(417, 9, 'TRA-JIM4-HOM', 'Homocinética lado rueda Jimny 4ª gen.', 'Homocinética lado rueda para Suzuki Jimny 4ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'GKN', 58990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(418, 2, 'FIL-JIM4-COM', 'Filtro de combustible Jimny 4ª gen.', 'Filtro de combustible para Suzuki Jimny 4ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 19990.00, 8, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(419, 2, 'FIL-JIM4-CAB', 'Filtro de cabina (polen) Jimny 4ª gen.', 'Filtro de cabina (polen) para Suzuki Jimny 4ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 10990.00, 15, 5, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(420, 10, 'CAR-JIM4-OPI', 'Óptica delantera izquierda Jimny 4ª gen.', 'Óptica delantera izquierda para Suzuki Jimny 4ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 123990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(421, 10, 'CAR-JIM4-OPD', 'Óptica delantera derecha Jimny 4ª gen.', 'Óptica delantera derecha para Suzuki Jimny 4ª gen. (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Depo', 123990.00, 0, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(422, 2, 'FIL-ALTO-F8D-AIR', 'Filtro de aire Alto 0.8 F8D', 'Filtro de aire para Suzuki Alto 0.8 F8D (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 8990.00, 22, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(423, 1, 'MOT-ALTO-F8D-CAC', 'Correa de accesorios (alternador) Alto 0.8 F8D', 'Correa de accesorios (alternador) para Suzuki Alto 0.8 F8D (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 9990.00, 9, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(424, 5, 'REF-ALTO-F8D-RAD', 'Radiador Alto 0.8 F8D', 'Radiador para Suzuki Alto 0.8 F8D (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 64990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(425, 9, 'TRA-ALTO-F8D-EMB', 'Kit de embrague Alto 0.8 F8D', 'Kit de embrague para Suzuki Alto 0.8 F8D (2013-2023). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'LuK', 83990.00, 11, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(426, 2, 'FIL-CEL1-K10B-AIR', 'Filtro de aire Celerio 1.0 K10B', 'Filtro de aire para Suzuki Celerio 1.0 K10B (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 8990.00, 21, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(427, 1, 'MOT-CEL1-K10B-CAC', 'Correa de accesorios (alternador) Celerio 1.0 K10B', 'Correa de accesorios (alternador) para Suzuki Celerio 1.0 K10B (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bando', 9990.00, 15, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(428, 5, 'REF-CEL1-K10B-RAD', 'Radiador Celerio 1.0 K10B', 'Radiador para Suzuki Celerio 1.0 K10B (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 64990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(429, 9, 'TRA-CEL1-K10B-EMB', 'Kit de embrague Celerio 1.0 K10B', 'Kit de embrague para Suzuki Celerio 1.0 K10B (2014-2021). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 83990.00, 12, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(430, 2, 'FIL-CEL2-K10C-AIR', 'Filtro de aire Celerio 1.0 K10C', 'Filtro de aire para Suzuki Celerio 1.0 K10C (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 8990.00, 18, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(431, 1, 'MOT-CEL2-K10C-CAC', 'Correa de accesorios (alternador) Celerio 1.0 K10C', 'Correa de accesorios (alternador) para Suzuki Celerio 1.0 K10C (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bando', 9990.00, 11, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(432, 5, 'REF-CEL2-K10C-RAD', 'Radiador Celerio 1.0 K10C', 'Radiador para Suzuki Celerio 1.0 K10C (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Koyorad', 64990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(433, 9, 'TRA-CEL2-K10C-EMB', 'Kit de embrague Celerio 1.0 K10C', 'Kit de embrague para Suzuki Celerio 1.0 K10C (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'LuK', 83990.00, 5, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(434, 2, 'FIL-SPR-K10B-AIR', 'Filtro de aire S-Presso 1.0 K10B', 'Filtro de aire para Suzuki S-Presso 1.0 K10B (2020-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sakura', 8990.00, 23, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(435, 1, 'MOT-SPR-K10B-CAC', 'Correa de accesorios (alternador) S-Presso 1.0 K10B', 'Correa de accesorios (alternador) para Suzuki S-Presso 1.0 K10B (2020-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Dayco', 9990.00, 18, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(436, 5, 'REF-SPR-K10B-RAD', 'Radiador S-Presso 1.0 K10B', 'Radiador para Suzuki S-Presso 1.0 K10B (2020-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Koyorad', 64990.00, 0, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(437, 9, 'TRA-SPR-K10B-EMB', 'Kit de embrague S-Presso 1.0 K10B', 'Kit de embrague para Suzuki S-Presso 1.0 K10B (2020-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'LuK', 83990.00, 11, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(438, 2, 'FIL-SPR-K10C-AIR', 'Filtro de aire S-Presso 1.0 K10C', 'Filtro de aire para Suzuki S-Presso 1.0 K10C (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 8990.00, 33, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(439, 1, 'MOT-SPR-K10C-CAC', 'Correa de accesorios (alternador) S-Presso 1.0 K10C', 'Correa de accesorios (alternador) para Suzuki S-Presso 1.0 K10C (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 9990.00, 19, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(440, 5, 'REF-SPR-K10C-RAD', 'Radiador S-Presso 1.0 K10C', 'Radiador para Suzuki S-Presso 1.0 K10C (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Koyorad', 64990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(441, 9, 'TRA-SPR-K10C-EMB', 'Kit de embrague S-Presso 1.0 K10C', 'Kit de embrague para Suzuki S-Presso 1.0 K10C (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Exedy', 83990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(442, 2, 'FIL-SWI2-K12B-AIR', 'Filtro de aire Swift 1.2 K12B', 'Filtro de aire para Suzuki Swift 1.2 K12B (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wega', 9990.00, 30, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(443, 1, 'MOT-SWI2-K12B-CAC', 'Correa de accesorios (alternador) Swift 1.2 K12B', 'Correa de accesorios (alternador) para Suzuki Swift 1.2 K12B (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 11990.00, 15, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(444, 5, 'REF-SWI2-K12B-RAD', 'Radiador Swift 1.2 K12B', 'Radiador para Suzuki Swift 1.2 K12B (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 84990.00, 0, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(445, 9, 'TRA-SWI2-K12B-EMB', 'Kit de embrague Swift 1.2 K12B', 'Kit de embrague para Suzuki Swift 1.2 K12B (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Exedy', 109990.00, 10, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(446, 2, 'FIL-SWI2-K14B-AIR', 'Filtro de aire Swift 1.4 K14B', 'Filtro de aire para Suzuki Swift 1.4 K14B (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sakura', 9990.00, 22, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(447, 1, 'MOT-SWI2-K14B-CAC', 'Correa de accesorios (alternador) Swift 1.4 K14B', 'Correa de accesorios (alternador) para Suzuki Swift 1.4 K14B (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Dayco', 11990.00, 19, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(448, 5, 'REF-SWI2-K14B-RAD', 'Radiador Swift 1.4 K14B', 'Radiador para Suzuki Swift 1.4 K14B (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 84990.00, 0, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(449, 9, 'TRA-SWI2-K14B-EMB', 'Kit de embrague Swift 1.4 K14B', 'Kit de embrague para Suzuki Swift 1.4 K14B (2011-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Exedy', 109990.00, 12, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(450, 2, 'FIL-SWIS-M16A-AIR', 'Filtro de aire Swift 1.6 M16A', 'Filtro de aire para Suzuki Swift 1.6 M16A (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sakura', 9990.00, 34, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(451, 1, 'MOT-SWIS-M16A-CAC', 'Correa de accesorios (alternador) Swift 1.6 M16A', 'Correa de accesorios (alternador) para Suzuki Swift 1.6 M16A (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bando', 11990.00, 13, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(452, 5, 'REF-SWIS-M16A-RAD', 'Radiador Swift 1.6 M16A', 'Radiador para Suzuki Swift 1.6 M16A (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Koyorad', 97990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(453, 9, 'TRA-SWIS-M16A-EMB', 'Kit de embrague Swift 1.6 M16A', 'Kit de embrague para Suzuki Swift 1.6 M16A (2012-2017). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 126990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(454, 2, 'FIL-SWI3-K10CT-AIR', 'Filtro de aire Swift 1.0 Turbo K10C', 'Filtro de aire para Suzuki Swift 1.0 Turbo K10C (2017-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 9990.00, 31, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(455, 1, 'MOT-SWI3-K10CT-CAC', 'Correa de accesorios (alternador) Swift 1.0 Turbo K10C', 'Correa de accesorios (alternador) para Suzuki Swift 1.0 Turbo K10C (2017-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 11990.00, 12, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(456, 5, 'REF-SWI3-K10CT-RAD', 'Radiador Swift 1.0 Turbo K10C', 'Radiador para Suzuki Swift 1.0 Turbo K10C (2017-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Koyorad', 76990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(457, 9, 'TRA-SWI3-K10CT-EMB', 'Kit de embrague Swift 1.0 Turbo K10C', 'Kit de embrague para Suzuki Swift 1.0 Turbo K10C (2017-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 98990.00, 5, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(458, 2, 'FIL-SWI3-K12M-AIR', 'Filtro de aire Swift 1.2 K12M', 'Filtro de aire para Suzuki Swift 1.2 K12M (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sakura', 9990.00, 12, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(459, 1, 'MOT-SWI3-K12M-CAC', 'Correa de accesorios (alternador) Swift 1.2 K12M', 'Correa de accesorios (alternador) para Suzuki Swift 1.2 K12M (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Dayco', 11990.00, 18, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(460, 5, 'REF-SWI3-K12M-RAD', 'Radiador Swift 1.2 K12M', 'Radiador para Suzuki Swift 1.2 K12M (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Koyorad', 84990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(461, 9, 'TRA-SWI3-K12M-EMB', 'Kit de embrague Swift 1.2 K12M', 'Kit de embrague para Suzuki Swift 1.2 K12M (2017-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 109990.00, 0, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(462, 2, 'FIL-BAL2-K12M-AIR', 'Filtro de aire Baleno 1.2 K12M', 'Filtro de aire para Suzuki Baleno 1.2 K12M (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 9990.00, 0, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(463, 1, 'MOT-BAL2-K12M-CAC', 'Correa de accesorios (alternador) Baleno 1.2 K12M', 'Correa de accesorios (alternador) para Suzuki Baleno 1.2 K12M (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 11990.00, 17, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(464, 5, 'REF-BAL2-K12M-RAD', 'Radiador Baleno 1.2 K12M', 'Radiador para Suzuki Baleno 1.2 K12M (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 84990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(465, 9, 'TRA-BAL2-K12M-EMB', 'Kit de embrague Baleno 1.2 K12M', 'Kit de embrague para Suzuki Baleno 1.2 K12M (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 109990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(466, 2, 'FIL-BAL2-K14B-AIR', 'Filtro de aire Baleno 1.4 K14B', 'Filtro de aire para Suzuki Baleno 1.4 K14B (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 9990.00, 27, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(467, 1, 'MOT-BAL2-K14B-CAC', 'Correa de accesorios (alternador) Baleno 1.4 K14B', 'Correa de accesorios (alternador) para Suzuki Baleno 1.4 K14B (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Dayco', 11990.00, 17, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(468, 5, 'REF-BAL2-K14B-RAD', 'Radiador Baleno 1.4 K14B', 'Radiador para Suzuki Baleno 1.4 K14B (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Koyorad', 84990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(469, 9, 'TRA-BAL2-K14B-EMB', 'Kit de embrague Baleno 1.4 K14B', 'Kit de embrague para Suzuki Baleno 1.4 K14B (2016-2022). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 109990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(470, 2, 'FIL-BAL3-K15B-AIR', 'Filtro de aire Baleno 1.5 K15B', 'Filtro de aire para Suzuki Baleno 1.5 K15B (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 9990.00, 12, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(471, 1, 'MOT-BAL3-K15B-CAC', 'Correa de accesorios (alternador) Baleno 1.5 K15B', 'Correa de accesorios (alternador) para Suzuki Baleno 1.5 K15B (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 11990.00, 10, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(472, 5, 'REF-BAL3-K15B-RAD', 'Radiador Baleno 1.5 K15B', 'Radiador para Suzuki Baleno 1.5 K15B (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 84990.00, 0, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(473, 9, 'TRA-BAL3-K15B-EMB', 'Kit de embrague Baleno 1.5 K15B', 'Kit de embrague para Suzuki Baleno 1.5 K15B (2022-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'LuK', 109990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(474, 2, 'FIL-FRX-K15C-AIR', 'Filtro de aire Fronx 1.5 K15C', 'Filtro de aire para Suzuki Fronx 1.5 K15C (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 9990.00, 22, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(475, 1, 'MOT-FRX-K15C-CAC', 'Correa de accesorios (alternador) Fronx 1.5 K15C', 'Correa de accesorios (alternador) para Suzuki Fronx 1.5 K15C (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Dayco', 11990.00, 9, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(476, 5, 'REF-FRX-K15C-RAD', 'Radiador Fronx 1.5 K15C', 'Radiador para Suzuki Fronx 1.5 K15C (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 84990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(477, 9, 'TRA-FRX-K15C-EMB', 'Kit de embrague Fronx 1.5 K15C', 'Kit de embrague para Suzuki Fronx 1.5 K15C (2023-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 109990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(478, 2, 'FIL-ERT1-K14B-AIR', 'Filtro de aire Ertiga 1.4 K14B', 'Filtro de aire para Suzuki Ertiga 1.4 K14B (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sakura', 9990.00, 3, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(479, 1, 'MOT-ERT1-K14B-CAC', 'Correa de accesorios (alternador) Ertiga 1.4 K14B', 'Correa de accesorios (alternador) para Suzuki Ertiga 1.4 K14B (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bando', 11990.00, 20, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(480, 5, 'REF-ERT1-K14B-RAD', 'Radiador Ertiga 1.4 K14B', 'Radiador para Suzuki Ertiga 1.4 K14B (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Koyorad', 84990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(481, 9, 'TRA-ERT1-K14B-EMB', 'Kit de embrague Ertiga 1.4 K14B', 'Kit de embrague para Suzuki Ertiga 1.4 K14B (2016-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'LuK', 109990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(482, 2, 'FIL-ERT2-K15B-AIR', 'Filtro de aire Ertiga 1.5 K15B', 'Filtro de aire para Suzuki Ertiga 1.5 K15B (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 9990.00, 29, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(483, 1, 'MOT-ERT2-K15B-CAC', 'Correa de accesorios (alternador) Ertiga 1.5 K15B', 'Correa de accesorios (alternador) para Suzuki Ertiga 1.5 K15B (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 11990.00, 15, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(484, 5, 'REF-ERT2-K15B-RAD', 'Radiador Ertiga 1.5 K15B', 'Radiador para Suzuki Ertiga 1.5 K15B (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Koyorad', 84990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(485, 9, 'TRA-ERT2-K15B-EMB', 'Kit de embrague Ertiga 1.5 K15B', 'Kit de embrague para Suzuki Ertiga 1.5 K15B (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'LuK', 109990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(486, 2, 'FIL-CIAZ-K14B-AIR', 'Filtro de aire Ciaz 1.4 K14B', 'Filtro de aire para Suzuki Ciaz 1.4 K14B (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wega', 9990.00, 14, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(487, 1, 'MOT-CIAZ-K14B-CAC', 'Correa de accesorios (alternador) Ciaz 1.4 K14B', 'Correa de accesorios (alternador) para Suzuki Ciaz 1.4 K14B (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 11990.00, 16, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(488, 5, 'REF-CIAZ-K14B-RAD', 'Radiador Ciaz 1.4 K14B', 'Radiador para Suzuki Ciaz 1.4 K14B (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 84990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(489, 9, 'TRA-CIAZ-K14B-EMB', 'Kit de embrague Ciaz 1.4 K14B', 'Kit de embrague para Suzuki Ciaz 1.4 K14B (2015-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 109990.00, 12, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(490, 2, 'FIL-APV-G15A-AIR', 'Filtro de aire APV 1.5 G15A', 'Filtro de aire para Suzuki APV 1.5 G15A (2006-2012). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 9990.00, 22, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(491, 1, 'MOT-APV-G15A-CAC', 'Correa de accesorios (alternador) APV 1.5 G15A', 'Correa de accesorios (alternador) para Suzuki APV 1.5 G15A (2006-2012). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 11990.00, 17, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(492, 5, 'REF-APV-G15A-RAD', 'Radiador APV 1.5 G15A', 'Radiador para Suzuki APV 1.5 G15A (2006-2012). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 84990.00, 10, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(493, 9, 'TRA-APV-G15A-EMB', 'Kit de embrague APV 1.5 G15A', 'Kit de embrague para Suzuki APV 1.5 G15A (2006-2012). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 109990.00, 12, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(494, 2, 'FIL-APV-G16A-AIR', 'Filtro de aire APV 1.6 G16A', 'Filtro de aire para Suzuki APV 1.6 G16A (2008-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 9990.00, 23, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(495, 1, 'MOT-APV-G16A-CAC', 'Correa de accesorios (alternador) APV 1.6 G16A', 'Correa de accesorios (alternador) para Suzuki APV 1.6 G16A (2008-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 11990.00, 15, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(496, 5, 'REF-APV-G16A-RAD', 'Radiador APV 1.6 G16A', 'Radiador para Suzuki APV 1.6 G16A (2008-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Koyorad', 97990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(497, 9, 'TRA-APV-G16A-EMB', 'Kit de embrague APV 1.6 G16A', 'Kit de embrague para Suzuki APV 1.6 G16A (2008-2020). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 126990.00, 10, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(498, 2, 'FIL-CARRY-K15B-AIR', 'Filtro de aire Carry 1.5 K15B', 'Filtro de aire para Suzuki Carry 1.5 K15B (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sakura', 9990.00, 17, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(499, 1, 'MOT-CARRY-K15B-CAC', 'Correa de accesorios (alternador) Carry 1.5 K15B', 'Correa de accesorios (alternador) para Suzuki Carry 1.5 K15B (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 11990.00, 17, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(500, 5, 'REF-CARRY-K15B-RAD', 'Radiador Carry 1.5 K15B', 'Radiador para Suzuki Carry 1.5 K15B (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 84990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(501, 9, 'TRA-CARRY-K15B-EMB', 'Kit de embrague Carry 1.5 K15B', 'Kit de embrague para Suzuki Carry 1.5 K15B (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'LuK', 109990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(502, 2, 'FIL-VIT4-M16A-AIR', 'Filtro de aire Vitara 1.6 M16A', 'Filtro de aire para Suzuki Vitara 1.6 M16A (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bosch', 12990.00, 2, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(503, 1, 'MOT-VIT4-M16A-CAC', 'Correa de accesorios (alternador) Vitara 1.6 M16A', 'Correa de accesorios (alternador) para Suzuki Vitara 1.6 M16A (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 15990.00, 0, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(504, 5, 'REF-VIT4-M16A-RAD', 'Radiador Vitara 1.6 M16A', 'Radiador para Suzuki Vitara 1.6 M16A (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 126990.00, 0, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(505, 9, 'TRA-VIT4-M16A-EMB', 'Kit de embrague Vitara 1.6 M16A', 'Kit de embrague para Suzuki Vitara 1.6 M16A (2015-2024). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Exedy', 164990.00, 11, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(506, 2, 'FIL-GV3-M16A-AIR', 'Filtro de aire Grand Vitara 1.6 M16A', 'Filtro de aire para Suzuki Grand Vitara 1.6 M16A (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Wega', 12990.00, 0, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(507, 1, 'MOT-GV3-M16A-CAC', 'Correa de accesorios (alternador) Grand Vitara 1.6 M16A', 'Correa de accesorios (alternador) para Suzuki Grand Vitara 1.6 M16A (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 15990.00, 8, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(508, 5, 'REF-GV3-M16A-RAD', 'Radiador Grand Vitara 1.6 M16A', 'Radiador para Suzuki Grand Vitara 1.6 M16A (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 126990.00, 11, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(509, 9, 'TRA-GV3-M16A-EMB', 'Kit de embrague Grand Vitara 1.6 M16A', 'Kit de embrague para Suzuki Grand Vitara 1.6 M16A (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'LuK', 164990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(510, 7, 'SUS-GV3-M16A-ADL', 'Amortiguadores delanteros (par) Grand Vitara 1.6 M16A', 'Amortiguadores delanteros (par) para Suzuki Grand Vitara 1.6 M16A (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Monroe', 116990.00, 10, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(511, 7, 'SUS-GV3-M16A-ATR', 'Amortiguadores traseros (par) Grand Vitara 1.6 M16A', 'Amortiguadores traseros (par) para Suzuki Grand Vitara 1.6 M16A (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Monroe', 90990.00, 9, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(512, 2, 'FIL-GV3-J24B-AIR', 'Filtro de aire Grand Vitara 2.4 J24B', 'Filtro de aire para Suzuki Grand Vitara 2.4 J24B (2009-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 12990.00, 17, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(513, 1, 'MOT-GV3-J24B-CAC', 'Correa de accesorios (alternador) Grand Vitara 2.4 J24B', 'Correa de accesorios (alternador) para Suzuki Grand Vitara 2.4 J24B (2009-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 15990.00, 19, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(514, 5, 'REF-GV3-J24B-RAD', 'Radiador Grand Vitara 2.4 J24B', 'Radiador para Suzuki Grand Vitara 2.4 J24B (2009-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 148990.00, 12, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(515, 9, 'TRA-GV3-J24B-EMB', 'Kit de embrague Grand Vitara 2.4 J24B', 'Kit de embrague para Suzuki Grand Vitara 2.4 J24B (2009-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Exedy', 192990.00, 12, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(516, 7, 'SUS-GV3-J24B-ADL', 'Amortiguadores delanteros (par) Grand Vitara 2.4 J24B', 'Amortiguadores delanteros (par) para Suzuki Grand Vitara 2.4 J24B (2009-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sachs', 116990.00, 10, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(517, 7, 'SUS-GV3-J24B-ATR', 'Amortiguadores traseros (par) Grand Vitara 2.4 J24B', 'Amortiguadores traseros (par) para Suzuki Grand Vitara 2.4 J24B (2009-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sachs', 90990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(518, 2, 'FIL-GN2-J20A-AIR', 'Filtro de aire Grand Nomade 2.0 J20A 2ª gen.', 'Filtro de aire para Suzuki Grand Nomade 2.0 J20A 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 12990.00, 15, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(519, 1, 'MOT-GN2-J20A-CAC', 'Correa de accesorios (alternador) Grand Nomade 2.0 J20A 2ª gen.', 'Correa de accesorios (alternador) para Suzuki Grand Nomade 2.0 J20A 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Bando', 15990.00, 20, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(520, 5, 'REF-GN2-J20A-RAD', 'Radiador Grand Nomade 2.0 J20A 2ª gen.', 'Radiador para Suzuki Grand Nomade 2.0 J20A 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 148990.00, 5, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17');
INSERT INTO `repuestos` (`id_repuesto`, `id_categoria`, `sku`, `nombre`, `descripcion`, `codigo_oem`, `marca_fabricante`, `precio`, `stock`, `stock_minimo`, `activo`, `creado_en`, `actualizado_en`) VALUES
(521, 9, 'TRA-GN2-J20A-EMB', 'Kit de embrague Grand Nomade 2.0 J20A 2ª gen.', 'Kit de embrague para Suzuki Grand Nomade 2.0 J20A 2ª gen. (1999-2005). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Valeo', 192990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(522, 2, 'FIL-GN3-J20A-AIR', 'Filtro de aire Grand Nomade 2.0 J20A 3ª gen.', 'Filtro de aire para Suzuki Grand Nomade 2.0 J20A 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Mann-Filter', 12990.00, 13, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(523, 1, 'MOT-GN3-J20A-CAC', 'Correa de accesorios (alternador) Grand Nomade 2.0 J20A 3ª gen.', 'Correa de accesorios (alternador) para Suzuki Grand Nomade 2.0 J20A 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 15990.00, 13, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(524, 5, 'REF-GN3-J20A-RAD', 'Radiador Grand Nomade 2.0 J20A 3ª gen.', 'Radiador para Suzuki Grand Nomade 2.0 J20A 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Koyorad', 148990.00, 7, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(525, 9, 'TRA-GN3-J20A-EMB', 'Kit de embrague Grand Nomade 2.0 J20A 3ª gen.', 'Kit de embrague para Suzuki Grand Nomade 2.0 J20A 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 192990.00, 11, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(526, 7, 'SUS-GN3-J20A-ADL', 'Amortiguadores delanteros (par) Grand Nomade 2.0 J20A 3ª gen.', 'Amortiguadores delanteros (par) para Suzuki Grand Nomade 2.0 J20A 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 116990.00, 5, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(527, 7, 'SUS-GN3-J20A-ATR', 'Amortiguadores traseros (par) Grand Nomade 2.0 J20A 3ª gen.', 'Amortiguadores traseros (par) para Suzuki Grand Nomade 2.0 J20A 3ª gen. (2006-2015). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'KYB', 90990.00, 1, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(528, 2, 'FIL-JIM3-M13A-AIR', 'Filtro de aire Jimny 1.3 M13A', 'Filtro de aire para Suzuki Jimny 1.3 M13A (2005-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sakura', 12990.00, 1, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(529, 1, 'MOT-JIM3-M13A-CAC', 'Correa de accesorios (alternador) Jimny 1.3 M13A', 'Correa de accesorios (alternador) para Suzuki Jimny 1.3 M13A (2005-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Gates', 15990.00, 9, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(530, 5, 'REF-JIM3-M13A-RAD', 'Radiador Jimny 1.3 M13A', 'Radiador para Suzuki Jimny 1.3 M13A (2005-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Koyorad', 110990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(531, 9, 'TRA-JIM3-M13A-EMB', 'Kit de embrague Jimny 1.3 M13A', 'Kit de embrague para Suzuki Jimny 1.3 M13A (2005-2018). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Exedy', 142990.00, 6, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(532, 2, 'FIL-JIM4-K15B-AIR', 'Filtro de aire Jimny 1.5 K15B', 'Filtro de aire para Suzuki Jimny 1.5 K15B (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Sakura', 12990.00, 15, 6, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(533, 1, 'MOT-JIM4-K15B-CAC', 'Correa de accesorios (alternador) Jimny 1.5 K15B', 'Correa de accesorios (alternador) para Suzuki Jimny 1.5 K15B (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Suzuki Genuine', 15990.00, 19, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(534, 5, 'REF-JIM4-K15B-RAD', 'Radiador Jimny 1.5 K15B', 'Radiador para Suzuki Jimny 1.5 K15B (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Denso', 110990.00, 0, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(535, 9, 'TRA-JIM4-K15B-EMB', 'Kit de embrague Jimny 1.5 K15B', 'Kit de embrague para Suzuki Jimny 1.5 K15B (2019-hoy). Confirmar compatibilidad con el VIN o el código OEM antes de comprar.', NULL, 'Exedy', 142990.00, 8, 2, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(536, 4, 'ELE-BAT-40AH', 'Batería 12V 40Ah', 'Batería 12V 40Ah. Verificar polaridad y medidas de la bandeja antes de comprar.', NULL, 'Bosch', 64990.00, 6, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(537, 4, 'ELE-BAT-45AH', 'Batería 12V 45Ah', 'Batería 12V 45Ah. Verificar polaridad y medidas de la bandeja antes de comprar.', NULL, 'Varta', 74990.00, 6, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17'),
(538, 4, 'ELE-BAT-60AH', 'Batería 12V 60Ah', 'Batería 12V 60Ah. Verificar polaridad y medidas de la bandeja antes de comprar.', NULL, 'Suzuki Genuine', 94990.00, 16, 3, 1, '2026-10-05 02:07:15', '2026-10-05 05:25:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repuesto_imagenes`
--

CREATE TABLE `repuesto_imagenes` (
  `id_imagen` int(10) UNSIGNED NOT NULL,
  `id_repuesto` int(10) UNSIGNED NOT NULL,
  `url` varchar(255) NOT NULL,
  `texto_alt` varchar(150) DEFAULT NULL COMMENT 'Texto alternativo (accesibilidad y SEO)',
  `orden` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 = imagen principal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fotos de cada repuesto';

--
-- Volcado de datos para la tabla `repuesto_imagenes`
--

INSERT INTO `repuesto_imagenes` (`id_imagen`, `id_repuesto`, `url`, `texto_alt`, `orden`) VALUES
(1, 1, 'img/repuestos/fil-ace.webp', 'Filtro de aceite motor F8D 0.8', 1),
(2, 2, 'img/repuestos/enc-buj.webp', 'Bujía de encendido motor F8D 0.8', 1),
(3, 3, 'img/repuestos/enc-bob.webp', 'Bobina de encendido motor F8D 0.8', 1),
(4, 4, 'img/repuestos/mot-dis-correa.webp', 'Kit de correa de distribución motor F8D 0.8', 1),
(5, 5, 'img/repuestos/mot-emp-3.webp', 'Empaquetadura de culata motor F8D 0.8', 1),
(6, 6, 'img/repuestos/mot-tva.webp', 'Empaquetadura tapa de válvulas motor F8D 0.8', 1),
(7, 7, 'img/repuestos/ref-bag.webp', 'Bomba de agua motor F8D 0.8', 1),
(8, 8, 'img/repuestos/ref-ter.webp', 'Termostato motor F8D 0.8', 1),
(9, 9, 'img/repuestos/ele-sox.webp', 'Sensor de oxígeno (sonda lambda) motor F8D 0.8', 1),
(10, 10, 'img/repuestos/ele-alt.webp', 'Alternador motor F8D 0.8', 1),
(11, 11, 'img/repuestos/ele-mpa.webp', 'Motor de partida motor F8D 0.8', 1),
(12, 12, 'img/repuestos/fil-ace.webp', 'Filtro de aceite motor K10B 1.0', 1),
(13, 13, 'img/repuestos/enc-buj.webp', 'Bujía de encendido motor K10B 1.0', 1),
(14, 14, 'img/repuestos/enc-bob.webp', 'Bobina de encendido motor K10B 1.0', 1),
(15, 15, 'img/repuestos/mot-dis-cadena.webp', 'Kit de cadena de distribución motor K10B 1.0', 1),
(16, 16, 'img/repuestos/mot-emp-3.webp', 'Empaquetadura de culata motor K10B 1.0', 1),
(17, 17, 'img/repuestos/mot-tva.webp', 'Empaquetadura tapa de válvulas motor K10B 1.0', 1),
(18, 18, 'img/repuestos/ref-bag.webp', 'Bomba de agua motor K10B 1.0', 1),
(19, 19, 'img/repuestos/ref-ter.webp', 'Termostato motor K10B 1.0', 1),
(20, 20, 'img/repuestos/ele-sox.webp', 'Sensor de oxígeno (sonda lambda) motor K10B 1.0', 1),
(21, 21, 'img/repuestos/ele-alt.webp', 'Alternador motor K10B 1.0', 1),
(22, 22, 'img/repuestos/ele-mpa.webp', 'Motor de partida motor K10B 1.0', 1),
(23, 23, 'img/repuestos/fil-ace.webp', 'Filtro de aceite motor K10C Dualjet 1.0', 1),
(24, 24, 'img/repuestos/enc-buj.webp', 'Bujía de encendido motor K10C Dualjet 1.0', 1),
(25, 25, 'img/repuestos/enc-bob.webp', 'Bobina de encendido motor K10C Dualjet 1.0', 1),
(26, 26, 'img/repuestos/mot-dis-cadena.webp', 'Kit de cadena de distribución motor K10C Dualjet 1.0', 1),
(27, 27, 'img/repuestos/mot-emp-3.webp', 'Empaquetadura de culata motor K10C Dualjet 1.0', 1),
(28, 28, 'img/repuestos/mot-tva.webp', 'Empaquetadura tapa de válvulas motor K10C Dualjet 1.0', 1),
(29, 29, 'img/repuestos/ref-bag.webp', 'Bomba de agua motor K10C Dualjet 1.0', 1),
(30, 30, 'img/repuestos/ref-ter.webp', 'Termostato motor K10C Dualjet 1.0', 1),
(31, 31, 'img/repuestos/ele-sox.webp', 'Sensor de oxígeno (sonda lambda) motor K10C Dualjet 1.0', 1),
(32, 32, 'img/repuestos/ele-alt.webp', 'Alternador motor K10C Dualjet 1.0', 1),
(33, 33, 'img/repuestos/ele-mpa.webp', 'Motor de partida motor K10C Dualjet 1.0', 1),
(34, 34, 'img/repuestos/fil-ace.webp', 'Filtro de aceite motor K10C Boosterjet Turbo 1.0', 1),
(35, 35, 'img/repuestos/enc-buj.webp', 'Bujía de encendido motor K10C Boosterjet Turbo 1.0', 1),
(36, 36, 'img/repuestos/enc-bob.webp', 'Bobina de encendido motor K10C Boosterjet Turbo 1.0', 1),
(37, 37, 'img/repuestos/mot-dis-cadena.webp', 'Kit de cadena de distribución motor K10C Boosterjet Turbo 1.0', 1),
(38, 38, 'img/repuestos/mot-emp-3.webp', 'Empaquetadura de culata motor K10C Boosterjet Turbo 1.0', 1),
(39, 39, 'img/repuestos/mot-tva.webp', 'Empaquetadura tapa de válvulas motor K10C Boosterjet Turbo 1.0', 1),
(40, 40, 'img/repuestos/ref-bag.webp', 'Bomba de agua motor K10C Boosterjet Turbo 1.0', 1),
(41, 41, 'img/repuestos/ref-ter.webp', 'Termostato motor K10C Boosterjet Turbo 1.0', 1),
(42, 42, 'img/repuestos/ele-sox.webp', 'Sensor de oxígeno (sonda lambda) motor K10C Boosterjet Turbo 1.0', 1),
(43, 43, 'img/repuestos/ele-alt.webp', 'Alternador motor K10C Boosterjet Turbo 1.0', 1),
(44, 44, 'img/repuestos/ele-mpa.webp', 'Motor de partida motor K10C Boosterjet Turbo 1.0', 1),
(45, 45, 'img/repuestos/fil-ace.webp', 'Filtro de aceite motor K12B 1.2', 1),
(46, 46, 'img/repuestos/enc-buj.webp', 'Bujía de encendido motor K12B 1.2', 1),
(47, 47, 'img/repuestos/enc-bob.webp', 'Bobina de encendido motor K12B 1.2', 1),
(48, 48, 'img/repuestos/mot-dis-cadena.webp', 'Kit de cadena de distribución motor K12B 1.2', 1),
(49, 49, 'img/repuestos/mot-emp-4.webp', 'Empaquetadura de culata motor K12B 1.2', 1),
(50, 50, 'img/repuestos/mot-tva.webp', 'Empaquetadura tapa de válvulas motor K12B 1.2', 1),
(51, 51, 'img/repuestos/ref-bag.webp', 'Bomba de agua motor K12B 1.2', 1),
(52, 52, 'img/repuestos/ref-ter.webp', 'Termostato motor K12B 1.2', 1),
(53, 53, 'img/repuestos/ele-sox.webp', 'Sensor de oxígeno (sonda lambda) motor K12B 1.2', 1),
(54, 54, 'img/repuestos/ele-alt.webp', 'Alternador motor K12B 1.2', 1),
(55, 55, 'img/repuestos/ele-mpa.webp', 'Motor de partida motor K12B 1.2', 1),
(56, 56, 'img/repuestos/fil-ace.webp', 'Filtro de aceite motor K12M 1.2', 1),
(57, 57, 'img/repuestos/enc-buj.webp', 'Bujía de encendido motor K12M 1.2', 1),
(58, 58, 'img/repuestos/enc-bob.webp', 'Bobina de encendido motor K12M 1.2', 1),
(59, 59, 'img/repuestos/mot-dis-cadena.webp', 'Kit de cadena de distribución motor K12M 1.2', 1),
(60, 60, 'img/repuestos/mot-emp-4.webp', 'Empaquetadura de culata motor K12M 1.2', 1),
(61, 61, 'img/repuestos/mot-tva.webp', 'Empaquetadura tapa de válvulas motor K12M 1.2', 1),
(62, 62, 'img/repuestos/ref-bag.webp', 'Bomba de agua motor K12M 1.2', 1),
(63, 63, 'img/repuestos/ref-ter.webp', 'Termostato motor K12M 1.2', 1),
(64, 64, 'img/repuestos/ele-sox.webp', 'Sensor de oxígeno (sonda lambda) motor K12M 1.2', 1),
(65, 65, 'img/repuestos/ele-alt.webp', 'Alternador motor K12M 1.2', 1),
(66, 66, 'img/repuestos/ele-mpa.webp', 'Motor de partida motor K12M 1.2', 1),
(67, 67, 'img/repuestos/fil-ace.webp', 'Filtro de aceite motor K14B 1.4', 1),
(68, 68, 'img/repuestos/enc-buj.webp', 'Bujía de encendido motor K14B 1.4', 1),
(69, 69, 'img/repuestos/enc-bob.webp', 'Bobina de encendido motor K14B 1.4', 1),
(70, 70, 'img/repuestos/mot-dis-cadena.webp', 'Kit de cadena de distribución motor K14B 1.4', 1),
(71, 71, 'img/repuestos/mot-emp-4.webp', 'Empaquetadura de culata motor K14B 1.4', 1),
(72, 72, 'img/repuestos/mot-tva.webp', 'Empaquetadura tapa de válvulas motor K14B 1.4', 1),
(73, 73, 'img/repuestos/ref-bag.webp', 'Bomba de agua motor K14B 1.4', 1),
(74, 74, 'img/repuestos/ref-ter.webp', 'Termostato motor K14B 1.4', 1),
(75, 75, 'img/repuestos/ele-sox.webp', 'Sensor de oxígeno (sonda lambda) motor K14B 1.4', 1),
(76, 76, 'img/repuestos/ele-alt.webp', 'Alternador motor K14B 1.4', 1),
(77, 77, 'img/repuestos/ele-mpa.webp', 'Motor de partida motor K14B 1.4', 1),
(78, 78, 'img/repuestos/fil-ace.webp', 'Filtro de aceite motor K15B 1.5', 1),
(79, 79, 'img/repuestos/enc-buj.webp', 'Bujía de encendido motor K15B 1.5', 1),
(80, 80, 'img/repuestos/enc-bob.webp', 'Bobina de encendido motor K15B 1.5', 1),
(81, 81, 'img/repuestos/mot-dis-cadena.webp', 'Kit de cadena de distribución motor K15B 1.5', 1),
(82, 82, 'img/repuestos/mot-emp-4.webp', 'Empaquetadura de culata motor K15B 1.5', 1),
(83, 83, 'img/repuestos/mot-tva.webp', 'Empaquetadura tapa de válvulas motor K15B 1.5', 1),
(84, 84, 'img/repuestos/ref-bag.webp', 'Bomba de agua motor K15B 1.5', 1),
(85, 85, 'img/repuestos/ref-ter.webp', 'Termostato motor K15B 1.5', 1),
(86, 86, 'img/repuestos/ele-sox.webp', 'Sensor de oxígeno (sonda lambda) motor K15B 1.5', 1),
(87, 87, 'img/repuestos/ele-alt.webp', 'Alternador motor K15B 1.5', 1),
(88, 88, 'img/repuestos/ele-mpa.webp', 'Motor de partida motor K15B 1.5', 1),
(89, 89, 'img/repuestos/fil-ace.webp', 'Filtro de aceite motor K15C Smart Hybrid 1.5', 1),
(90, 90, 'img/repuestos/enc-buj.webp', 'Bujía de encendido motor K15C Smart Hybrid 1.5', 1),
(91, 91, 'img/repuestos/enc-bob.webp', 'Bobina de encendido motor K15C Smart Hybrid 1.5', 1),
(92, 92, 'img/repuestos/mot-dis-cadena.webp', 'Kit de cadena de distribución motor K15C Smart Hybrid 1.5', 1),
(93, 93, 'img/repuestos/mot-emp-4.webp', 'Empaquetadura de culata motor K15C Smart Hybrid 1.5', 1),
(94, 94, 'img/repuestos/mot-tva.webp', 'Empaquetadura tapa de válvulas motor K15C Smart Hybrid 1.5', 1),
(95, 95, 'img/repuestos/ref-bag.webp', 'Bomba de agua motor K15C Smart Hybrid 1.5', 1),
(96, 96, 'img/repuestos/ref-ter.webp', 'Termostato motor K15C Smart Hybrid 1.5', 1),
(97, 97, 'img/repuestos/ele-sox.webp', 'Sensor de oxígeno (sonda lambda) motor K15C Smart Hybrid 1.5', 1),
(98, 98, 'img/repuestos/ele-alt.webp', 'Generador ISG (sistema Smart Hybrid) motor K15C Smart Hybrid 1.5', 1),
(99, 99, 'img/repuestos/ele-mpa.webp', 'Motor de partida motor K15C Smart Hybrid 1.5', 1),
(100, 100, 'img/repuestos/fil-ace.webp', 'Filtro de aceite motor M13A 1.3', 1),
(101, 101, 'img/repuestos/enc-buj.webp', 'Bujía de encendido motor M13A 1.3', 1),
(102, 102, 'img/repuestos/enc-bob.webp', 'Bobina de encendido motor M13A 1.3', 1),
(103, 103, 'img/repuestos/mot-dis-cadena.webp', 'Kit de cadena de distribución motor M13A 1.3', 1),
(104, 104, 'img/repuestos/mot-emp-4.webp', 'Empaquetadura de culata motor M13A 1.3', 1),
(105, 105, 'img/repuestos/mot-tva.webp', 'Empaquetadura tapa de válvulas motor M13A 1.3', 1),
(106, 106, 'img/repuestos/ref-bag.webp', 'Bomba de agua motor M13A 1.3', 1),
(107, 107, 'img/repuestos/ref-ter.webp', 'Termostato motor M13A 1.3', 1),
(108, 108, 'img/repuestos/ele-sox.webp', 'Sensor de oxígeno (sonda lambda) motor M13A 1.3', 1),
(109, 109, 'img/repuestos/ele-alt.webp', 'Alternador motor M13A 1.3', 1),
(110, 110, 'img/repuestos/ele-mpa.webp', 'Motor de partida motor M13A 1.3', 1),
(111, 111, 'img/repuestos/fil-ace.webp', 'Filtro de aceite motor M16A 1.6', 1),
(112, 112, 'img/repuestos/enc-buj.webp', 'Bujía de encendido motor M16A 1.6', 1),
(113, 113, 'img/repuestos/enc-bob.webp', 'Bobina de encendido motor M16A 1.6', 1),
(114, 114, 'img/repuestos/mot-dis-cadena.webp', 'Kit de cadena de distribución motor M16A 1.6', 1),
(115, 115, 'img/repuestos/mot-emp-4.webp', 'Empaquetadura de culata motor M16A 1.6', 1),
(116, 116, 'img/repuestos/mot-tva.webp', 'Empaquetadura tapa de válvulas motor M16A 1.6', 1),
(117, 117, 'img/repuestos/ref-bag.webp', 'Bomba de agua motor M16A 1.6', 1),
(118, 118, 'img/repuestos/ref-ter.webp', 'Termostato motor M16A 1.6', 1),
(119, 119, 'img/repuestos/ele-sox.webp', 'Sensor de oxígeno (sonda lambda) motor M16A 1.6', 1),
(120, 120, 'img/repuestos/ele-alt.webp', 'Alternador motor M16A 1.6', 1),
(121, 121, 'img/repuestos/ele-mpa.webp', 'Motor de partida motor M16A 1.6', 1),
(122, 122, 'img/repuestos/fil-ace.webp', 'Filtro de aceite motor G15A 1.5', 1),
(123, 123, 'img/repuestos/enc-buj.webp', 'Bujía de encendido motor G15A 1.5', 1),
(124, 124, 'img/repuestos/enc-bob.webp', 'Bobina de encendido motor G15A 1.5', 1),
(125, 125, 'img/repuestos/mot-dis-correa.webp', 'Kit de correa de distribución motor G15A 1.5', 1),
(126, 126, 'img/repuestos/mot-emp-4.webp', 'Empaquetadura de culata motor G15A 1.5', 1),
(127, 127, 'img/repuestos/mot-tva.webp', 'Empaquetadura tapa de válvulas motor G15A 1.5', 1),
(128, 128, 'img/repuestos/ref-bag.webp', 'Bomba de agua motor G15A 1.5', 1),
(129, 129, 'img/repuestos/ref-ter.webp', 'Termostato motor G15A 1.5', 1),
(130, 130, 'img/repuestos/ele-sox.webp', 'Sensor de oxígeno (sonda lambda) motor G15A 1.5', 1),
(131, 131, 'img/repuestos/ele-alt.webp', 'Alternador motor G15A 1.5', 1),
(132, 132, 'img/repuestos/ele-mpa.webp', 'Motor de partida motor G15A 1.5', 1),
(133, 133, 'img/repuestos/fil-ace.webp', 'Filtro de aceite motor G16A 1.6', 1),
(134, 134, 'img/repuestos/enc-buj.webp', 'Bujía de encendido motor G16A 1.6', 1),
(135, 135, 'img/repuestos/enc-bob.webp', 'Bobina de encendido motor G16A 1.6', 1),
(136, 136, 'img/repuestos/mot-dis-correa.webp', 'Kit de correa de distribución motor G16A 1.6', 1),
(137, 137, 'img/repuestos/mot-emp-4.webp', 'Empaquetadura de culata motor G16A 1.6', 1),
(138, 138, 'img/repuestos/mot-tva.webp', 'Empaquetadura tapa de válvulas motor G16A 1.6', 1),
(139, 139, 'img/repuestos/ref-bag.webp', 'Bomba de agua motor G16A 1.6', 1),
(140, 140, 'img/repuestos/ref-ter.webp', 'Termostato motor G16A 1.6', 1),
(141, 141, 'img/repuestos/ele-sox.webp', 'Sensor de oxígeno (sonda lambda) motor G16A 1.6', 1),
(142, 142, 'img/repuestos/ele-alt.webp', 'Alternador motor G16A 1.6', 1),
(143, 143, 'img/repuestos/ele-mpa.webp', 'Motor de partida motor G16A 1.6', 1),
(144, 144, 'img/repuestos/fil-ace.webp', 'Filtro de aceite motor J20A 2.0', 1),
(145, 145, 'img/repuestos/enc-buj.webp', 'Bujía de encendido motor J20A 2.0', 1),
(146, 146, 'img/repuestos/enc-bob.webp', 'Bobina de encendido motor J20A 2.0', 1),
(147, 147, 'img/repuestos/mot-dis-cadena.webp', 'Kit de cadena de distribución motor J20A 2.0', 1),
(148, 148, 'img/repuestos/mot-emp-4.webp', 'Empaquetadura de culata motor J20A 2.0', 1),
(149, 149, 'img/repuestos/mot-tva.webp', 'Empaquetadura tapa de válvulas motor J20A 2.0', 1),
(150, 150, 'img/repuestos/ref-bag.webp', 'Bomba de agua motor J20A 2.0', 1),
(151, 151, 'img/repuestos/ref-ter.webp', 'Termostato motor J20A 2.0', 1),
(152, 152, 'img/repuestos/ele-sox.webp', 'Sensor de oxígeno (sonda lambda) motor J20A 2.0', 1),
(153, 153, 'img/repuestos/ele-alt.webp', 'Alternador motor J20A 2.0', 1),
(154, 154, 'img/repuestos/ele-mpa.webp', 'Motor de partida motor J20A 2.0', 1),
(155, 155, 'img/repuestos/fil-ace.webp', 'Filtro de aceite motor J24B 2.4', 1),
(156, 156, 'img/repuestos/enc-buj.webp', 'Bujía de encendido motor J24B 2.4', 1),
(157, 157, 'img/repuestos/enc-bob.webp', 'Bobina de encendido motor J24B 2.4', 1),
(158, 158, 'img/repuestos/mot-dis-cadena.webp', 'Kit de cadena de distribución motor J24B 2.4', 1),
(159, 159, 'img/repuestos/mot-emp-4.webp', 'Empaquetadura de culata motor J24B 2.4', 1),
(160, 160, 'img/repuestos/mot-tva.webp', 'Empaquetadura tapa de válvulas motor J24B 2.4', 1),
(161, 161, 'img/repuestos/ref-bag.webp', 'Bomba de agua motor J24B 2.4', 1),
(162, 162, 'img/repuestos/ref-ter.webp', 'Termostato motor J24B 2.4', 1),
(163, 163, 'img/repuestos/ele-sox.webp', 'Sensor de oxígeno (sonda lambda) motor J24B 2.4', 1),
(164, 164, 'img/repuestos/ele-alt.webp', 'Alternador motor J24B 2.4', 1),
(165, 165, 'img/repuestos/ele-mpa.webp', 'Motor de partida motor J24B 2.4', 1),
(166, 166, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Alto 800', 1),
(167, 167, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Alto 800', 1),
(168, 168, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Alto 800', 1),
(169, 169, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Alto 800', 1),
(170, 170, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Alto 800', 1),
(171, 171, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Alto 800', 1),
(172, 172, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Alto 800', 1),
(173, 173, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Alto 800', 1),
(174, 174, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Alto 800', 1),
(175, 175, 'img/repuestos/fil-com.webp', 'Filtro de combustible Alto 800', 1),
(176, 176, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Alto 800', 1),
(177, 177, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Alto 800', 1),
(178, 178, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Celerio 1ª gen.', 1),
(179, 179, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Celerio 1ª gen.', 1),
(180, 180, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Celerio 1ª gen.', 1),
(181, 181, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Celerio 1ª gen.', 1),
(182, 182, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Celerio 1ª gen.', 1),
(183, 183, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Celerio 1ª gen.', 1),
(184, 184, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Celerio 1ª gen.', 1),
(185, 185, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Celerio 1ª gen.', 1),
(186, 186, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Celerio 1ª gen.', 1),
(187, 187, 'img/repuestos/fil-com.webp', 'Filtro de combustible Celerio 1ª gen.', 1),
(188, 188, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Celerio 1ª gen.', 1),
(189, 189, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Celerio 1ª gen.', 1),
(190, 190, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Celerio 2ª gen.', 1),
(191, 191, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Celerio 2ª gen.', 1),
(192, 192, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Celerio 2ª gen.', 1),
(193, 193, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Celerio 2ª gen.', 1),
(194, 194, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Celerio 2ª gen.', 1),
(195, 195, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Celerio 2ª gen.', 1),
(196, 196, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Celerio 2ª gen.', 1),
(197, 197, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Celerio 2ª gen.', 1),
(198, 198, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Celerio 2ª gen.', 1),
(199, 199, 'img/repuestos/fil-com.webp', 'Filtro de combustible Celerio 2ª gen.', 1),
(200, 200, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Celerio 2ª gen.', 1),
(201, 201, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Celerio 2ª gen.', 1),
(202, 202, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) S-Presso', 1),
(203, 203, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) S-Presso', 1),
(204, 204, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) S-Presso', 1),
(205, 205, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera S-Presso', 1),
(206, 206, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) S-Presso', 1),
(207, 207, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) S-Presso', 1),
(208, 208, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior S-Presso', 1),
(209, 209, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección S-Presso', 1),
(210, 210, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda S-Presso', 1),
(211, 211, 'img/repuestos/fil-com.webp', 'Filtro de combustible S-Presso', 1),
(212, 212, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda S-Presso', 1),
(213, 213, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha S-Presso', 1),
(214, 214, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Swift 2ª gen.', 1),
(215, 215, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Swift 2ª gen.', 1),
(216, 216, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Swift 2ª gen.', 1),
(217, 217, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Swift 2ª gen.', 1),
(218, 218, 'img/repuestos/sus-bie.webp', 'Bieleta de barra estabilizadora Swift 2ª gen.', 1),
(219, 219, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Swift 2ª gen.', 1),
(220, 220, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Swift 2ª gen.', 1),
(221, 221, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Swift 2ª gen.', 1),
(222, 222, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Swift 2ª gen.', 1),
(223, 223, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Swift 2ª gen.', 1),
(224, 224, 'img/repuestos/fil-com.webp', 'Filtro de combustible Swift 2ª gen.', 1),
(225, 225, 'img/repuestos/fil-cab.webp', 'Filtro de cabina (polen) Swift 2ª gen.', 1),
(226, 226, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Swift 2ª gen.', 1),
(227, 227, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Swift 2ª gen.', 1),
(228, 228, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Swift Sport 2ª gen.', 1),
(229, 229, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Swift Sport 2ª gen.', 1),
(230, 230, 'img/repuestos/fre-ptr.webp', 'Pastillas de freno traseras (juego) Swift Sport 2ª gen.', 1),
(231, 231, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Swift Sport 2ª gen.', 1),
(232, 232, 'img/repuestos/sus-bie.webp', 'Bieleta de barra estabilizadora Swift Sport 2ª gen.', 1),
(233, 233, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Swift Sport 2ª gen.', 1),
(234, 234, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Swift Sport 2ª gen.', 1),
(235, 235, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Swift Sport 2ª gen.', 1),
(236, 236, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Swift Sport 2ª gen.', 1),
(237, 237, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Swift Sport 2ª gen.', 1),
(238, 238, 'img/repuestos/fil-com.webp', 'Filtro de combustible Swift Sport 2ª gen.', 1),
(239, 239, 'img/repuestos/fil-cab.webp', 'Filtro de cabina (polen) Swift Sport 2ª gen.', 1),
(240, 240, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Swift Sport 2ª gen.', 1),
(241, 241, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Swift Sport 2ª gen.', 1),
(242, 242, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Swift 3ª gen.', 1),
(243, 243, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Swift 3ª gen.', 1),
(244, 244, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Swift 3ª gen.', 1),
(245, 245, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Swift 3ª gen.', 1),
(246, 246, 'img/repuestos/sus-bie.webp', 'Bieleta de barra estabilizadora Swift 3ª gen.', 1),
(247, 247, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Swift 3ª gen.', 1),
(248, 248, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Swift 3ª gen.', 1),
(249, 249, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Swift 3ª gen.', 1),
(250, 250, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Swift 3ª gen.', 1),
(251, 251, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Swift 3ª gen.', 1),
(252, 252, 'img/repuestos/fil-com.webp', 'Filtro de combustible Swift 3ª gen.', 1),
(253, 253, 'img/repuestos/fil-cab.webp', 'Filtro de cabina (polen) Swift 3ª gen.', 1),
(254, 254, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Swift 3ª gen.', 1),
(255, 255, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Swift 3ª gen.', 1),
(256, 256, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Baleno 2ª gen.', 1),
(257, 257, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Baleno 2ª gen.', 1),
(258, 258, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Baleno 2ª gen.', 1),
(259, 259, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Baleno 2ª gen.', 1),
(260, 260, 'img/repuestos/sus-bie.webp', 'Bieleta de barra estabilizadora Baleno 2ª gen.', 1),
(261, 261, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Baleno 2ª gen.', 1),
(262, 262, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Baleno 2ª gen.', 1),
(263, 263, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Baleno 2ª gen.', 1),
(264, 264, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Baleno 2ª gen.', 1),
(265, 265, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Baleno 2ª gen.', 1),
(266, 266, 'img/repuestos/fil-com.webp', 'Filtro de combustible Baleno 2ª gen.', 1),
(267, 267, 'img/repuestos/fil-cab.webp', 'Filtro de cabina (polen) Baleno 2ª gen.', 1),
(268, 268, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Baleno 2ª gen.', 1),
(269, 269, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Baleno 2ª gen.', 1),
(270, 270, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Baleno 3ª gen.', 1),
(271, 271, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Baleno 3ª gen.', 1),
(272, 272, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Baleno 3ª gen.', 1),
(273, 273, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Baleno 3ª gen.', 1),
(274, 274, 'img/repuestos/sus-bie.webp', 'Bieleta de barra estabilizadora Baleno 3ª gen.', 1),
(275, 275, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Baleno 3ª gen.', 1),
(276, 276, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Baleno 3ª gen.', 1),
(277, 277, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Baleno 3ª gen.', 1),
(278, 278, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Baleno 3ª gen.', 1),
(279, 279, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Baleno 3ª gen.', 1),
(280, 280, 'img/repuestos/fil-com.webp', 'Filtro de combustible Baleno 3ª gen.', 1),
(281, 281, 'img/repuestos/fil-cab.webp', 'Filtro de cabina (polen) Baleno 3ª gen.', 1),
(282, 282, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Baleno 3ª gen.', 1),
(283, 283, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Baleno 3ª gen.', 1),
(284, 284, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Fronx', 1),
(285, 285, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Fronx', 1),
(286, 286, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Fronx', 1),
(287, 287, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Fronx', 1),
(288, 288, 'img/repuestos/sus-bie.webp', 'Bieleta de barra estabilizadora Fronx', 1),
(289, 289, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Fronx', 1),
(290, 290, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Fronx', 1),
(291, 291, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Fronx', 1),
(292, 292, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Fronx', 1),
(293, 293, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Fronx', 1),
(294, 294, 'img/repuestos/fil-com.webp', 'Filtro de combustible Fronx', 1),
(295, 295, 'img/repuestos/fil-cab.webp', 'Filtro de cabina (polen) Fronx', 1),
(296, 296, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Fronx', 1),
(297, 297, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Fronx', 1),
(298, 298, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Ertiga 1ª gen.', 1),
(299, 299, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Ertiga 1ª gen.', 1),
(300, 300, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Ertiga 1ª gen.', 1),
(301, 301, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Ertiga 1ª gen.', 1),
(302, 302, 'img/repuestos/sus-bie.webp', 'Bieleta de barra estabilizadora Ertiga 1ª gen.', 1),
(303, 303, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Ertiga 1ª gen.', 1),
(304, 304, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Ertiga 1ª gen.', 1),
(305, 305, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Ertiga 1ª gen.', 1),
(306, 306, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Ertiga 1ª gen.', 1),
(307, 307, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Ertiga 1ª gen.', 1),
(308, 308, 'img/repuestos/fil-com.webp', 'Filtro de combustible Ertiga 1ª gen.', 1),
(309, 309, 'img/repuestos/fil-cab.webp', 'Filtro de cabina (polen) Ertiga 1ª gen.', 1),
(310, 310, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Ertiga 1ª gen.', 1),
(311, 311, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Ertiga 1ª gen.', 1),
(312, 312, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Ertiga 2ª gen.', 1),
(313, 313, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Ertiga 2ª gen.', 1),
(314, 314, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Ertiga 2ª gen.', 1),
(315, 315, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Ertiga 2ª gen.', 1),
(316, 316, 'img/repuestos/sus-bie.webp', 'Bieleta de barra estabilizadora Ertiga 2ª gen.', 1),
(317, 317, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Ertiga 2ª gen.', 1),
(318, 318, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Ertiga 2ª gen.', 1),
(319, 319, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Ertiga 2ª gen.', 1),
(320, 320, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Ertiga 2ª gen.', 1),
(321, 321, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Ertiga 2ª gen.', 1),
(322, 322, 'img/repuestos/fil-com.webp', 'Filtro de combustible Ertiga 2ª gen.', 1),
(323, 323, 'img/repuestos/fil-cab.webp', 'Filtro de cabina (polen) Ertiga 2ª gen.', 1),
(324, 324, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Ertiga 2ª gen.', 1),
(325, 325, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Ertiga 2ª gen.', 1),
(326, 326, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Ciaz', 1),
(327, 327, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Ciaz', 1),
(328, 328, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Ciaz', 1),
(329, 329, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Ciaz', 1),
(330, 330, 'img/repuestos/sus-bie.webp', 'Bieleta de barra estabilizadora Ciaz', 1),
(331, 331, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Ciaz', 1),
(332, 332, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Ciaz', 1),
(333, 333, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Ciaz', 1),
(334, 334, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Ciaz', 1),
(335, 335, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Ciaz', 1),
(336, 336, 'img/repuestos/fil-com.webp', 'Filtro de combustible Ciaz', 1),
(337, 337, 'img/repuestos/fil-cab.webp', 'Filtro de cabina (polen) Ciaz', 1),
(338, 338, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Ciaz', 1),
(339, 339, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Ciaz', 1),
(340, 340, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) APV', 1),
(341, 341, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) APV', 1),
(342, 342, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) APV', 1),
(343, 343, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera APV', 1),
(344, 344, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) APV', 1),
(345, 345, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) APV', 1),
(346, 346, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior APV', 1),
(347, 347, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección APV', 1),
(348, 348, 'img/repuestos/fil-com.webp', 'Filtro de combustible APV', 1),
(349, 349, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda APV', 1),
(350, 350, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha APV', 1),
(351, 351, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Carry', 1),
(352, 352, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Carry', 1),
(353, 353, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Carry', 1),
(354, 354, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Carry', 1),
(355, 355, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Carry', 1),
(356, 356, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Carry', 1),
(357, 357, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Carry', 1),
(358, 358, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Carry', 1),
(359, 359, 'img/repuestos/fil-com.webp', 'Filtro de combustible Carry', 1),
(360, 360, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Carry', 1),
(361, 361, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Carry', 1),
(362, 362, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Vitara 4ª gen.', 1),
(363, 363, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Vitara 4ª gen.', 1),
(364, 364, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Vitara 4ª gen.', 1),
(365, 365, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Vitara 4ª gen.', 1),
(366, 366, 'img/repuestos/sus-bie.webp', 'Bieleta de barra estabilizadora Vitara 4ª gen.', 1),
(367, 367, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Vitara 4ª gen.', 1),
(368, 368, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Vitara 4ª gen.', 1),
(369, 369, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Vitara 4ª gen.', 1),
(370, 370, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Vitara 4ª gen.', 1),
(371, 371, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Vitara 4ª gen.', 1),
(372, 372, 'img/repuestos/fil-com.webp', 'Filtro de combustible Vitara 4ª gen.', 1),
(373, 373, 'img/repuestos/fil-cab.webp', 'Filtro de cabina (polen) Vitara 4ª gen.', 1),
(374, 374, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Vitara 4ª gen.', 1),
(375, 375, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Vitara 4ª gen.', 1),
(376, 376, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Grand Vitara / Grand Nomade 3ª gen.', 1),
(377, 377, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Grand Vitara / Grand Nomade 3ª gen.', 1),
(378, 378, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Grand Vitara / Grand Nomade 3ª gen.', 1),
(379, 379, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Grand Vitara / Grand Nomade 3ª gen.', 1),
(380, 380, 'img/repuestos/sus-bie.webp', 'Bieleta de barra estabilizadora Grand Vitara / Grand Nomade 3ª gen.', 1),
(381, 381, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Grand Vitara / Grand Nomade 3ª gen.', 1),
(382, 382, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Grand Vitara / Grand Nomade 3ª gen.', 1),
(383, 383, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Grand Vitara / Grand Nomade 3ª gen.', 1),
(384, 384, 'img/repuestos/fil-com.webp', 'Filtro de combustible Grand Vitara / Grand Nomade 3ª gen.', 1),
(385, 385, 'img/repuestos/fil-cab.webp', 'Filtro de cabina (polen) Grand Vitara / Grand Nomade 3ª gen.', 1),
(386, 386, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Grand Vitara / Grand Nomade 3ª gen.', 1),
(387, 387, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Grand Vitara / Grand Nomade 3ª gen.', 1),
(388, 388, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Grand Nomade 2ª gen.', 1),
(389, 389, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Grand Nomade 2ª gen.', 1),
(390, 390, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Grand Nomade 2ª gen.', 1),
(391, 391, 'img/repuestos/sus-ban.webp', 'Bandeja de suspensión delantera Grand Nomade 2ª gen.', 1),
(392, 392, 'img/repuestos/sus-bie.webp', 'Bieleta de barra estabilizadora Grand Nomade 2ª gen.', 1),
(393, 393, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Grand Nomade 2ª gen.', 1),
(394, 394, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Grand Nomade 2ª gen.', 1),
(395, 395, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Grand Nomade 2ª gen.', 1),
(396, 396, 'img/repuestos/dir-axi.webp', 'Rótula axial de dirección Grand Nomade 2ª gen.', 1),
(397, 397, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Grand Nomade 2ª gen.', 1),
(398, 398, 'img/repuestos/fil-com.webp', 'Filtro de combustible Grand Nomade 2ª gen.', 1),
(399, 399, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Grand Nomade 2ª gen.', 1),
(400, 400, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Grand Nomade 2ª gen.', 1),
(401, 401, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Jimny 3ª gen.', 1),
(402, 402, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Jimny 3ª gen.', 1),
(403, 403, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Jimny 3ª gen.', 1),
(404, 404, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Jimny 3ª gen.', 1),
(405, 405, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Jimny 3ª gen.', 1),
(406, 406, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Jimny 3ª gen.', 1),
(407, 407, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Jimny 3ª gen.', 1),
(408, 408, 'img/repuestos/fil-com.webp', 'Filtro de combustible Jimny 3ª gen.', 1),
(409, 409, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Jimny 3ª gen.', 1),
(410, 410, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Jimny 3ª gen.', 1),
(411, 411, 'img/repuestos/fre-pdl.webp', 'Pastillas de freno delanteras (juego) Jimny 4ª gen.', 1),
(412, 412, 'img/repuestos/fre-ddl.webp', 'Discos de freno delanteros (par) Jimny 4ª gen.', 1),
(413, 413, 'img/repuestos/fre-btr.webp', 'Balatas de freno traseras (juego) Jimny 4ª gen.', 1),
(414, 414, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Jimny 4ª gen.', 1),
(415, 415, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Jimny 4ª gen.', 1),
(416, 416, 'img/repuestos/dir-ter.webp', 'Terminal de dirección exterior Jimny 4ª gen.', 1),
(417, 417, 'img/repuestos/tra-hom.webp', 'Homocinética lado rueda Jimny 4ª gen.', 1),
(418, 418, 'img/repuestos/fil-com.webp', 'Filtro de combustible Jimny 4ª gen.', 1),
(419, 419, 'img/repuestos/fil-cab.webp', 'Filtro de cabina (polen) Jimny 4ª gen.', 1),
(420, 420, 'img/repuestos/car-opi.webp', 'Óptica delantera izquierda Jimny 4ª gen.', 1),
(421, 421, 'img/repuestos/car-opd.webp', 'Óptica delantera derecha Jimny 4ª gen.', 1),
(422, 422, 'img/repuestos/fil-air.webp', 'Filtro de aire Alto 0.8 F8D', 1),
(423, 423, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Alto 0.8 F8D', 1),
(424, 424, 'img/repuestos/ref-rad.webp', 'Radiador Alto 0.8 F8D', 1),
(425, 425, 'img/repuestos/tra-emb.webp', 'Kit de embrague Alto 0.8 F8D', 1),
(426, 426, 'img/repuestos/fil-air.webp', 'Filtro de aire Celerio 1.0 K10B', 1),
(427, 427, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Celerio 1.0 K10B', 1),
(428, 428, 'img/repuestos/ref-rad.webp', 'Radiador Celerio 1.0 K10B', 1),
(429, 429, 'img/repuestos/tra-emb.webp', 'Kit de embrague Celerio 1.0 K10B', 1),
(430, 430, 'img/repuestos/fil-air.webp', 'Filtro de aire Celerio 1.0 K10C', 1),
(431, 431, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Celerio 1.0 K10C', 1),
(432, 432, 'img/repuestos/ref-rad.webp', 'Radiador Celerio 1.0 K10C', 1),
(433, 433, 'img/repuestos/tra-emb.webp', 'Kit de embrague Celerio 1.0 K10C', 1),
(434, 434, 'img/repuestos/fil-air.webp', 'Filtro de aire S-Presso 1.0 K10B', 1),
(435, 435, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) S-Presso 1.0 K10B', 1),
(436, 436, 'img/repuestos/ref-rad.webp', 'Radiador S-Presso 1.0 K10B', 1),
(437, 437, 'img/repuestos/tra-emb.webp', 'Kit de embrague S-Presso 1.0 K10B', 1),
(438, 438, 'img/repuestos/fil-air.webp', 'Filtro de aire S-Presso 1.0 K10C', 1),
(439, 439, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) S-Presso 1.0 K10C', 1),
(440, 440, 'img/repuestos/ref-rad.webp', 'Radiador S-Presso 1.0 K10C', 1),
(441, 441, 'img/repuestos/tra-emb.webp', 'Kit de embrague S-Presso 1.0 K10C', 1),
(442, 442, 'img/repuestos/fil-air.webp', 'Filtro de aire Swift 1.2 K12B', 1),
(443, 443, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Swift 1.2 K12B', 1),
(444, 444, 'img/repuestos/ref-rad.webp', 'Radiador Swift 1.2 K12B', 1),
(445, 445, 'img/repuestos/tra-emb.webp', 'Kit de embrague Swift 1.2 K12B', 1),
(446, 446, 'img/repuestos/fil-air.webp', 'Filtro de aire Swift 1.4 K14B', 1),
(447, 447, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Swift 1.4 K14B', 1),
(448, 448, 'img/repuestos/ref-rad.webp', 'Radiador Swift 1.4 K14B', 1),
(449, 449, 'img/repuestos/tra-emb.webp', 'Kit de embrague Swift 1.4 K14B', 1),
(450, 450, 'img/repuestos/fil-air.webp', 'Filtro de aire Swift 1.6 M16A', 1),
(451, 451, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Swift 1.6 M16A', 1),
(452, 452, 'img/repuestos/ref-rad.webp', 'Radiador Swift 1.6 M16A', 1),
(453, 453, 'img/repuestos/tra-emb.webp', 'Kit de embrague Swift 1.6 M16A', 1),
(454, 454, 'img/repuestos/fil-air.webp', 'Filtro de aire Swift 1.0 Turbo K10C', 1),
(455, 455, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Swift 1.0 Turbo K10C', 1),
(456, 456, 'img/repuestos/ref-rad.webp', 'Radiador Swift 1.0 Turbo K10C', 1),
(457, 457, 'img/repuestos/tra-emb.webp', 'Kit de embrague Swift 1.0 Turbo K10C', 1),
(458, 458, 'img/repuestos/fil-air.webp', 'Filtro de aire Swift 1.2 K12M', 1),
(459, 459, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Swift 1.2 K12M', 1),
(460, 460, 'img/repuestos/ref-rad.webp', 'Radiador Swift 1.2 K12M', 1),
(461, 461, 'img/repuestos/tra-emb.webp', 'Kit de embrague Swift 1.2 K12M', 1),
(462, 462, 'img/repuestos/fil-air.webp', 'Filtro de aire Baleno 1.2 K12M', 1),
(463, 463, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Baleno 1.2 K12M', 1),
(464, 464, 'img/repuestos/ref-rad.webp', 'Radiador Baleno 1.2 K12M', 1),
(465, 465, 'img/repuestos/tra-emb.webp', 'Kit de embrague Baleno 1.2 K12M', 1),
(466, 466, 'img/repuestos/fil-air.webp', 'Filtro de aire Baleno 1.4 K14B', 1),
(467, 467, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Baleno 1.4 K14B', 1),
(468, 468, 'img/repuestos/ref-rad.webp', 'Radiador Baleno 1.4 K14B', 1),
(469, 469, 'img/repuestos/tra-emb.webp', 'Kit de embrague Baleno 1.4 K14B', 1),
(470, 470, 'img/repuestos/fil-air.webp', 'Filtro de aire Baleno 1.5 K15B', 1),
(471, 471, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Baleno 1.5 K15B', 1),
(472, 472, 'img/repuestos/ref-rad.webp', 'Radiador Baleno 1.5 K15B', 1),
(473, 473, 'img/repuestos/tra-emb.webp', 'Kit de embrague Baleno 1.5 K15B', 1),
(474, 474, 'img/repuestos/fil-air.webp', 'Filtro de aire Fronx 1.5 K15C', 1),
(475, 475, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Fronx 1.5 K15C', 1),
(476, 476, 'img/repuestos/ref-rad.webp', 'Radiador Fronx 1.5 K15C', 1),
(477, 477, 'img/repuestos/tra-emb.webp', 'Kit de embrague Fronx 1.5 K15C', 1),
(478, 478, 'img/repuestos/fil-air.webp', 'Filtro de aire Ertiga 1.4 K14B', 1),
(479, 479, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Ertiga 1.4 K14B', 1),
(480, 480, 'img/repuestos/ref-rad.webp', 'Radiador Ertiga 1.4 K14B', 1),
(481, 481, 'img/repuestos/tra-emb.webp', 'Kit de embrague Ertiga 1.4 K14B', 1),
(482, 482, 'img/repuestos/fil-air.webp', 'Filtro de aire Ertiga 1.5 K15B', 1),
(483, 483, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Ertiga 1.5 K15B', 1),
(484, 484, 'img/repuestos/ref-rad.webp', 'Radiador Ertiga 1.5 K15B', 1),
(485, 485, 'img/repuestos/tra-emb.webp', 'Kit de embrague Ertiga 1.5 K15B', 1),
(486, 486, 'img/repuestos/fil-air.webp', 'Filtro de aire Ciaz 1.4 K14B', 1),
(487, 487, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Ciaz 1.4 K14B', 1),
(488, 488, 'img/repuestos/ref-rad.webp', 'Radiador Ciaz 1.4 K14B', 1),
(489, 489, 'img/repuestos/tra-emb.webp', 'Kit de embrague Ciaz 1.4 K14B', 1),
(490, 490, 'img/repuestos/fil-air.webp', 'Filtro de aire APV 1.5 G15A', 1),
(491, 491, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) APV 1.5 G15A', 1),
(492, 492, 'img/repuestos/ref-rad.webp', 'Radiador APV 1.5 G15A', 1),
(493, 493, 'img/repuestos/tra-emb.webp', 'Kit de embrague APV 1.5 G15A', 1),
(494, 494, 'img/repuestos/fil-air.webp', 'Filtro de aire APV 1.6 G16A', 1),
(495, 495, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) APV 1.6 G16A', 1),
(496, 496, 'img/repuestos/ref-rad.webp', 'Radiador APV 1.6 G16A', 1),
(497, 497, 'img/repuestos/tra-emb.webp', 'Kit de embrague APV 1.6 G16A', 1),
(498, 498, 'img/repuestos/fil-air.webp', 'Filtro de aire Carry 1.5 K15B', 1),
(499, 499, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Carry 1.5 K15B', 1),
(500, 500, 'img/repuestos/ref-rad.webp', 'Radiador Carry 1.5 K15B', 1),
(501, 501, 'img/repuestos/tra-emb.webp', 'Kit de embrague Carry 1.5 K15B', 1),
(502, 502, 'img/repuestos/fil-air.webp', 'Filtro de aire Vitara 1.6 M16A', 1),
(503, 503, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Vitara 1.6 M16A', 1),
(504, 504, 'img/repuestos/ref-rad.webp', 'Radiador Vitara 1.6 M16A', 1),
(505, 505, 'img/repuestos/tra-emb.webp', 'Kit de embrague Vitara 1.6 M16A', 1),
(506, 506, 'img/repuestos/fil-air.webp', 'Filtro de aire Grand Vitara 1.6 M16A', 1),
(507, 507, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Grand Vitara 1.6 M16A', 1),
(508, 508, 'img/repuestos/ref-rad.webp', 'Radiador Grand Vitara 1.6 M16A', 1),
(509, 509, 'img/repuestos/tra-emb.webp', 'Kit de embrague Grand Vitara 1.6 M16A', 1),
(510, 510, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Grand Vitara 1.6 M16A', 1),
(511, 511, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Grand Vitara 1.6 M16A', 1),
(512, 512, 'img/repuestos/fil-air.webp', 'Filtro de aire Grand Vitara 2.4 J24B', 1),
(513, 513, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Grand Vitara 2.4 J24B', 1),
(514, 514, 'img/repuestos/ref-rad.webp', 'Radiador Grand Vitara 2.4 J24B', 1),
(515, 515, 'img/repuestos/tra-emb.webp', 'Kit de embrague Grand Vitara 2.4 J24B', 1),
(516, 516, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Grand Vitara 2.4 J24B', 1),
(517, 517, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Grand Vitara 2.4 J24B', 1),
(518, 518, 'img/repuestos/fil-air.webp', 'Filtro de aire Grand Nomade 2.0 J20A 2ª gen.', 1),
(519, 519, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Grand Nomade 2.0 J20A 2ª gen.', 1),
(520, 520, 'img/repuestos/ref-rad.webp', 'Radiador Grand Nomade 2.0 J20A 2ª gen.', 1),
(521, 521, 'img/repuestos/tra-emb.webp', 'Kit de embrague Grand Nomade 2.0 J20A 2ª gen.', 1),
(522, 522, 'img/repuestos/fil-air.webp', 'Filtro de aire Grand Nomade 2.0 J20A 3ª gen.', 1),
(523, 523, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Grand Nomade 2.0 J20A 3ª gen.', 1),
(524, 524, 'img/repuestos/ref-rad.webp', 'Radiador Grand Nomade 2.0 J20A 3ª gen.', 1),
(525, 525, 'img/repuestos/tra-emb.webp', 'Kit de embrague Grand Nomade 2.0 J20A 3ª gen.', 1),
(526, 526, 'img/repuestos/sus-adl.webp', 'Amortiguadores delanteros (par) Grand Nomade 2.0 J20A 3ª gen.', 1),
(527, 527, 'img/repuestos/sus-atr.webp', 'Amortiguadores traseros (par) Grand Nomade 2.0 J20A 3ª gen.', 1),
(528, 528, 'img/repuestos/fil-air.webp', 'Filtro de aire Jimny 1.3 M13A', 1),
(529, 529, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Jimny 1.3 M13A', 1),
(530, 530, 'img/repuestos/ref-rad.webp', 'Radiador Jimny 1.3 M13A', 1),
(531, 531, 'img/repuestos/tra-emb.webp', 'Kit de embrague Jimny 1.3 M13A', 1),
(532, 532, 'img/repuestos/fil-air.webp', 'Filtro de aire Jimny 1.5 K15B', 1),
(533, 533, 'img/repuestos/mot-cac.webp', 'Correa de accesorios (alternador) Jimny 1.5 K15B', 1),
(534, 534, 'img/repuestos/ref-rad.webp', 'Radiador Jimny 1.5 K15B', 1),
(535, 535, 'img/repuestos/tra-emb.webp', 'Kit de embrague Jimny 1.5 K15B', 1),
(536, 536, 'img/repuestos/ele-bat-40ah.webp', 'Batería 12V 40Ah', 1),
(537, 537, 'img/repuestos/ele-bat-45ah.webp', 'Batería 12V 45Ah', 1),
(538, 538, 'img/repuestos/ele-bat-60ah.webp', 'Batería 12V 60Ah', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculos`
--

CREATE TABLE `vehiculos` (
  `id_vehiculo` int(10) UNSIGNED NOT NULL,
  `id_modelo` int(10) UNSIGNED NOT NULL,
  `id_motor` int(10) UNSIGNED NOT NULL,
  `generacion` varchar(60) DEFAULT NULL COMMENT 'Ej: 3ª gen.',
  `anio_desde` smallint(5) UNSIGNED NOT NULL,
  `anio_hasta` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'NULL = sigue a la venta',
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Volcado de datos para la tabla `vehiculos`
--

INSERT INTO `vehiculos` (`id_vehiculo`, `id_modelo`, `id_motor`, `generacion`, `anio_desde`, `anio_hasta`, `activo`) VALUES
(1, 1, 1, 'Alto 800', 2013, 2023, 1),
(2, 2, 2, '1ª gen.', 2014, 2021, 1),
(3, 2, 3, '2ª gen.', 2022, NULL, 1),
(4, 3, 2, '1ª gen.', 2020, 2022, 1),
(5, 3, 3, '1ª gen. (renovación 2023)', 2023, NULL, 1),
(6, 4, 5, '2ª gen.', 2011, 2017, 1),
(7, 4, 7, '2ª gen.', 2011, 2017, 1),
(8, 4, 11, 'Swift Sport 2ª gen.', 2012, 2017, 1),
(9, 4, 4, '3ª gen.', 2017, 2020, 1),
(10, 4, 6, '3ª gen.', 2017, 2024, 1),
(11, 5, 6, '2ª gen.', 2016, 2022, 1),
(12, 5, 7, '2ª gen.', 2016, 2022, 1),
(13, 5, 8, '3ª gen.', 2022, NULL, 1),
(14, 6, 9, '1ª gen.', 2023, NULL, 1),
(15, 7, 7, '1ª gen.', 2016, 2018, 1),
(16, 7, 8, '2ª gen.', 2019, NULL, 1),
(17, 8, 7, '1ª gen.', 2015, 2020, 1),
(18, 9, 12, '1ª gen.', 2006, 2012, 1),
(19, 9, 13, '1ª gen.', 2008, 2020, 1),
(20, 10, 8, 'Nueva Carry', 2019, NULL, 1),
(21, 11, 11, '4ª gen.', 2015, 2024, 1),
(22, 12, 11, '3ª gen.', 2006, 2015, 1),
(23, 12, 15, '3ª gen.', 2009, 2015, 1),
(24, 13, 14, '2ª gen.', 1999, 2005, 1),
(25, 13, 14, '3ª gen.', 2006, 2015, 1),
(26, 14, 10, '3ª gen.', 2005, 2018, 1),
(27, 14, 8, '4ª gen.', 2019, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_catalogo`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_catalogo` (
`id_repuesto` int(10) unsigned
,`sku` varchar(30)
,`nombre` varchar(150)
,`categoria` varchar(60)
,`codigo_oem` varchar(40)
,`precio` decimal(10,2)
,`stock` int(11)
,`activo` tinyint(1)
,`imagen_url` varchar(255)
,`id_vehiculo` int(10) unsigned
,`modelo` varchar(50)
,`modelo_slug` varchar(60)
,`motor_codigo` varchar(20)
,`cilindrada_litros` decimal(2,1)
,`anio_desde` smallint(5) unsigned
,`anio_hasta` smallint(5) unsigned
,`vehiculo` varchar(96)
,`vehiculo_activo` int(1)
,`cantidad` tinyint(3) unsigned
,`observacion` varchar(150)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_vehiculos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_vehiculos` (
`id_vehiculo` int(10) unsigned
,`marca` varchar(50)
,`id_modelo` int(10) unsigned
,`modelo` varchar(50)
,`modelo_slug` varchar(60)
,`id_motor` int(10) unsigned
,`motor_codigo` varchar(20)
,`motor_variante` varchar(40)
,`cilindrada_litros` decimal(2,1)
,`generacion` varchar(60)
,`anio_desde` smallint(5) unsigned
,`anio_hasta` smallint(5) unsigned
,`descripcion` varchar(96)
,`activo` int(1)
);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `uq_administradores_usuario` (`usuario`);

--
-- Indices de la tabla `avisos_stock`
--
ALTER TABLE `avisos_stock`
  ADD PRIMARY KEY (`id_aviso`),
  ADD KEY `idx_avisos_repuesto` (`id_repuesto`,`notificado`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `uq_categorias_codigo` (`codigo`),
  ADD UNIQUE KEY `uq_categorias_nombre` (`nombre`),
  ADD UNIQUE KEY `uq_categorias_slug` (`slug`),
  ADD KEY `idx_categorias_padre` (`id_categoria_padre`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `uq_clientes_rut` (`rut`),
  ADD KEY `idx_clientes_email` (`email`);

--
-- Indices de la tabla `codigos_descuento`
--
ALTER TABLE `codigos_descuento`
  ADD PRIMARY KEY (`id_codigo`),
  ADD UNIQUE KEY `uq_codigos_codigo` (`codigo`);

--
-- Indices de la tabla `compatibilidades`
--
ALTER TABLE `compatibilidades`
  ADD PRIMARY KEY (`id_repuesto`,`id_vehiculo`),
  ADD KEY `idx_compat_vehiculo` (`id_vehiculo`);

--
-- Indices de la tabla `conversaciones_chat`
--
ALTER TABLE `conversaciones_chat`
  ADD PRIMARY KEY (`id_conversacion`),
  ADD UNIQUE KEY `uq_conversaciones_sesion` (`sesion_id`);

--
-- Indices de la tabla `marcas`
--
ALTER TABLE `marcas`
  ADD PRIMARY KEY (`id_marca`),
  ADD UNIQUE KEY `uq_marcas_nombre` (`nombre`),
  ADD UNIQUE KEY `uq_marcas_slug` (`slug`);

--
-- Indices de la tabla `mensajes_chat`
--
ALTER TABLE `mensajes_chat`
  ADD PRIMARY KEY (`id_mensaje`),
  ADD KEY `idx_mensajes_conversacion` (`id_conversacion`,`creado_en`);

--
-- Indices de la tabla `mensaje_repuestos`
--
ALTER TABLE `mensaje_repuestos`
  ADD PRIMARY KEY (`id_mensaje`,`id_repuesto`),
  ADD KEY `idx_msgrep_repuesto` (`id_repuesto`);

--
-- Indices de la tabla `modelos`
--
ALTER TABLE `modelos`
  ADD PRIMARY KEY (`id_modelo`),
  ADD UNIQUE KEY `uq_modelos_marca_nombre` (`id_marca`,`nombre`),
  ADD UNIQUE KEY `uq_modelos_slug` (`slug`);

--
-- Indices de la tabla `motores`
--
ALTER TABLE `motores`
  ADD PRIMARY KEY (`id_motor`),
  ADD UNIQUE KEY `uq_motores_codigo_variante` (`codigo`,`variante`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `idx_pedidos_cliente` (`id_cliente`),
  ADD KEY `idx_pedidos_codigo` (`id_codigo_descuento`),
  ADD KEY `idx_pedidos_fecha` (`fecha`),
  ADD KEY `idx_pedidos_estados` (`estado`,`pago_estado`),
  ADD KEY `idx_pedidos_referencia` (`referencia_pago`);

--
-- Indices de la tabla `pedido_detalle`
--
ALTER TABLE `pedido_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD UNIQUE KEY `uq_detalle_pedido_repuesto` (`id_pedido`,`id_repuesto`),
  ADD KEY `idx_detalle_repuesto` (`id_repuesto`);

--
-- Indices de la tabla `repuestos`
--
ALTER TABLE `repuestos`
  ADD PRIMARY KEY (`id_repuesto`),
  ADD UNIQUE KEY `uq_repuestos_sku` (`sku`),
  ADD KEY `idx_repuestos_categoria` (`id_categoria`),
  ADD KEY `idx_repuestos_oem` (`codigo_oem`),
  ADD KEY `idx_repuestos_activo_stock` (`activo`,`stock`);
ALTER TABLE `repuestos` ADD FULLTEXT KEY `ft_repuestos_busqueda` (`nombre`,`descripcion`);

--
-- Indices de la tabla `repuesto_imagenes`
--
ALTER TABLE `repuesto_imagenes`
  ADD PRIMARY KEY (`id_imagen`),
  ADD UNIQUE KEY `uq_imagenes_repuesto_orden` (`id_repuesto`,`orden`);

--
-- Indices de la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  ADD PRIMARY KEY (`id_vehiculo`),
  ADD UNIQUE KEY `uq_vehiculos_modelo_motor_anio` (`id_modelo`,`id_motor`,`anio_desde`),
  ADD KEY `idx_vehiculos_motor` (`id_motor`),
  ADD KEY `idx_vehiculos_anios` (`anio_desde`,`anio_hasta`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id_admin` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `avisos_stock`
--
ALTER TABLE `avisos_stock`
  MODIFY `id_aviso` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `codigos_descuento`
--
ALTER TABLE `codigos_descuento`
  MODIFY `id_codigo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `conversaciones_chat`
--
ALTER TABLE `conversaciones_chat`
  MODIFY `id_conversacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `marcas`
--
ALTER TABLE `marcas`
  MODIFY `id_marca` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `mensajes_chat`
--
ALTER TABLE `mensajes_chat`
  MODIFY `id_mensaje` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `modelos`
--
ALTER TABLE `modelos`
  MODIFY `id_modelo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `motores`
--
ALTER TABLE `motores`
  MODIFY `id_motor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedido_detalle`
--
ALTER TABLE `pedido_detalle`
  MODIFY `id_detalle` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `repuestos`
--
ALTER TABLE `repuestos`
  MODIFY `id_repuesto` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `repuesto_imagenes`
--
ALTER TABLE `repuesto_imagenes`
  MODIFY `id_imagen` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1024;

--
-- AUTO_INCREMENT de la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  MODIFY `id_vehiculo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_catalogo`
--
DROP TABLE IF EXISTS `vw_catalogo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u118470389_prueba_bd`@`127.0.0.1` SQL SECURITY INVOKER VIEW `vw_catalogo`  AS SELECT `r`.`id_repuesto` AS `id_repuesto`, `r`.`sku` AS `sku`, `r`.`nombre` AS `nombre`, `c`.`nombre` AS `categoria`, `r`.`codigo_oem` AS `codigo_oem`, `r`.`precio` AS `precio`, `r`.`stock` AS `stock`, `r`.`activo` AS `activo`, `i`.`url` AS `imagen_url`, `vv`.`id_vehiculo` AS `id_vehiculo`, `vv`.`modelo` AS `modelo`, `vv`.`modelo_slug` AS `modelo_slug`, `vv`.`motor_codigo` AS `motor_codigo`, `vv`.`cilindrada_litros` AS `cilindrada_litros`, `vv`.`anio_desde` AS `anio_desde`, `vv`.`anio_hasta` AS `anio_hasta`, `vv`.`descripcion` AS `vehiculo`, `vv`.`activo` AS `vehiculo_activo`, `co`.`cantidad` AS `cantidad`, `co`.`observacion` AS `observacion` FROM ((((`repuestos` `r` join `categorias` `c` on(`c`.`id_categoria` = `r`.`id_categoria`)) join `compatibilidades` `co` on(`co`.`id_repuesto` = `r`.`id_repuesto`)) join `vw_vehiculos` `vv` on(`vv`.`id_vehiculo` = `co`.`id_vehiculo`)) left join `repuesto_imagenes` `i` on(`i`.`id_repuesto` = `r`.`id_repuesto` and `i`.`orden` = 1)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_vehiculos`
--
DROP TABLE IF EXISTS `vw_vehiculos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u118470389_prueba_bd`@`127.0.0.1` SQL SECURITY INVOKER VIEW `vw_vehiculos`  AS SELECT `v`.`id_vehiculo` AS `id_vehiculo`, `ma`.`nombre` AS `marca`, `mo`.`id_modelo` AS `id_modelo`, `mo`.`nombre` AS `modelo`, `mo`.`slug` AS `modelo_slug`, `mt`.`id_motor` AS `id_motor`, `mt`.`codigo` AS `motor_codigo`, `mt`.`variante` AS `motor_variante`, `mt`.`cilindrada_litros` AS `cilindrada_litros`, `v`.`generacion` AS `generacion`, `v`.`anio_desde` AS `anio_desde`, `v`.`anio_hasta` AS `anio_hasta`, concat(`mo`.`nombre`,' ',`mt`.`cilindrada_litros`,if(`mt`.`aspiracion` = 'Turbo',' Turbo',''),' ',`mt`.`codigo`,' (',`v`.`anio_desde`,'-',coalesce(`v`.`anio_hasta`,'hoy'),')') AS `descripcion`, `v`.`activo`<> 0 and `mo`.`activo` <> 0 and `ma`.`activo` <> 0 AS `activo` FROM (((`vehiculos` `v` join `modelos` `mo` on(`mo`.`id_modelo` = `v`.`id_modelo`)) join `marcas` `ma` on(`ma`.`id_marca` = `mo`.`id_marca`)) join `motores` `mt` on(`mt`.`id_motor` = `v`.`id_motor`)) ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `avisos_stock`
--
ALTER TABLE `avisos_stock`
  ADD CONSTRAINT `fk_avisos_repuesto` FOREIGN KEY (`id_repuesto`) REFERENCES `repuestos` (`id_repuesto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `fk_categorias_padre` FOREIGN KEY (`id_categoria_padre`) REFERENCES `categorias` (`id_categoria`);

--
-- Filtros para la tabla `compatibilidades`
--
ALTER TABLE `compatibilidades`
  ADD CONSTRAINT `fk_compat_repuesto` FOREIGN KEY (`id_repuesto`) REFERENCES `repuestos` (`id_repuesto`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_compat_vehiculo` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `mensajes_chat`
--
ALTER TABLE `mensajes_chat`
  ADD CONSTRAINT `fk_mensajes_conversacion` FOREIGN KEY (`id_conversacion`) REFERENCES `conversaciones_chat` (`id_conversacion`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `mensaje_repuestos`
--
ALTER TABLE `mensaje_repuestos`
  ADD CONSTRAINT `fk_msgrep_mensaje` FOREIGN KEY (`id_mensaje`) REFERENCES `mensajes_chat` (`id_mensaje`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_msgrep_repuesto` FOREIGN KEY (`id_repuesto`) REFERENCES `repuestos` (`id_repuesto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `modelos`
--
ALTER TABLE `modelos`
  ADD CONSTRAINT `fk_modelos_marca` FOREIGN KEY (`id_marca`) REFERENCES `marcas` (`id_marca`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedidos_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_codigo` FOREIGN KEY (`id_codigo_descuento`) REFERENCES `codigos_descuento` (`id_codigo`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedido_detalle`
--
ALTER TABLE `pedido_detalle`
  ADD CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_repuesto` FOREIGN KEY (`id_repuesto`) REFERENCES `repuestos` (`id_repuesto`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `repuestos`
--
ALTER TABLE `repuestos`
  ADD CONSTRAINT `fk_repuestos_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `repuesto_imagenes`
--
ALTER TABLE `repuesto_imagenes`
  ADD CONSTRAINT `fk_imagenes_repuesto` FOREIGN KEY (`id_repuesto`) REFERENCES `repuestos` (`id_repuesto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  ADD CONSTRAINT `fk_vehiculos_modelo` FOREIGN KEY (`id_modelo`) REFERENCES `modelos` (`id_modelo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vehiculos_motor` FOREIGN KEY (`id_motor`) REFERENCES `motores` (`id_motor`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
