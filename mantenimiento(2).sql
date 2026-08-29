-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-08-2026 a las 19:26:18
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `mantenimiento`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_ActualizarKilometrajeVehiculo` (IN `p_id` INT, IN `p_nuevo_kilometraje` DECIMAL(10,2))   BEGIN
    UPDATE flota
    SET kilometraje = p_nuevo_kilometraje
    WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_InsertarUsuario` (IN `p_nombres` VARCHAR(100), IN `p_usuario` VARCHAR(20), IN `p_contrasena` VARCHAR(20), IN `p_cargo` VARCHAR(50))   BEGIN
    INSERT INTO usuario (nombres, usuario, contraseña, cargo)
    VALUES (p_nombres, p_usuario, p_contrasena, p_cargo);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_InsertarVehiculo` (IN `p_marca` VARCHAR(20), IN `p_modelo` VARCHAR(20), IN `p_placa` VARCHAR(20), IN `p_kilometraje` DECIMAL(10,2))   BEGIN
    INSERT INTO flota (marca, modelo, placa, kilometraje)
    VALUES (p_marca, p_modelo, p_placa, p_kilometraje);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_LoginUsuario` (IN `p_usuario` VARCHAR(20), IN `p_contrasena` VARCHAR(20))   BEGIN
    SELECT id, nombres, cargo, usuario 
    FROM usuario 
    WHERE usuario = p_usuario AND contraseña = p_contrasena;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_ObtenerMantenimientosPorVehiculo` (IN `p_id_vehiculo` VARCHAR(12))   BEGIN
    SELECT * FROM mantenimiento
    WHERE id_vehiculo = p_id_vehiculo;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_RegistrarMantenimiento` (IN `p_id_usuario` INT, IN `p_id_vehiculo` INT, IN `p_tipo_falla` VARCHAR(50), IN `p_comentario` TEXT)   BEGIN
    -- Asegúrate de cambiar 'id_tecnico' por el nombre exacto de la columna en tu tabla mantenimiento
    INSERT INTO mantenimiento (id_tecnico, id_vehiculo, tipo_falla, comentario, fecha)
    VALUES (p_id_usuario, p_id_vehiculo, p_tipo_falla, p_comentario, NOW());

    -- Cambiar el estado del vehículo automáticamente a 'Inactivo'
    UPDATE flota 
    SET estado = 'Inactivo' 
    WHERE id = p_id_vehiculo;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_VerEstadoVehiculos` ()   BEGIN
    SELECT id, marca, modelo, placa, kilometraje, estado 
    FROM flota;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `flota`
--

CREATE TABLE `flota` (
  `id` int(5) NOT NULL,
  `marca` varchar(20) NOT NULL,
  `modelo` varchar(20) NOT NULL,
  `placa` varchar(20) NOT NULL,
  `kilometraje` decimal(10,2) NOT NULL,
  `estado` varchar(20) DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `flota`
--

INSERT INTO `flota` (`id`, `marca`, `modelo`, `placa`, `kilometraje`, `estado`) VALUES
(1, 'Volvo', 'FH 540', 'A10BC2D', 0.00, 'Activo'),
(2, 'Scania', 'R 450', 'A11EF4G', 0.00, 'Activo'),
(3, 'Mercedes-Benz', 'Actros 2651', 'A12HI6J', 0.00, 'Activo'),
(4, 'MAN', 'TGX 18.500', 'A13KL8M', 0.00, 'Activo'),
(5, 'DAF', 'XF 530', 'A14NO0P', 0.00, 'Activo'),
(6, 'Iveco', 'Stralis Hi-Way', 'A15QR2S', 0.00, 'Activo'),
(7, 'Freightliner', 'Cascadia', 'A16TU4V', 0.00, 'Activo'),
(8, 'International', 'ProStar', 'A17WX6Y', 0.00, 'Activo'),
(9, 'Kenworth', 'T680', 'A18ZA8B', 0.00, 'Activo'),
(10, 'Volvo', 'FM 380', 'A19CD0E', 0.00, 'Activo'),
(11, 'Scania', 'G 410', 'A20FG2H', 0.00, 'Activo'),
(12, 'Mercedes-Benz', 'Atego 1726', 'A21JK4L', 0.00, 'Activo'),
(13, 'Volkswagen', 'Constellation 19.330', 'A22MN6O', 0.00, 'Activo'),
(14, 'Isuzu', 'NPR HD', 'A23PQ8R', 0.00, 'Activo'),
(15, 'Hino', '500 Series', 'A24ST0U', 0.00, 'Activo'),
(16, 'Ford', 'Cargo 1723', 'A25VW2X', 0.00, 'Activo'),
(17, 'Renault Trucks', 'T High 520', 'A26YZ4A', 0.00, 'Activo'),
(18, 'Volvo', 'FMX 460', 'A27BC6D', 0.00, 'Activo'),
(19, 'Scania', 'P 360', 'A28EF8G', 0.00, 'Activo'),
(20, 'Mercedes-Benz', 'Accelo 1016', 'A29HI0J', 50000.00, 'Activo'),
(21, 'MAN', 'TGM 15.290', 'A30KL2M', 0.00, 'Activo'),
(22, 'DAF', 'CF 450', 'A31NO4P', 0.00, 'Activo'),
(23, 'Iveco', 'Trakker 410', 'A32QR6S', 0.00, 'Activo'),
(24, 'Freightliner', 'M2 106', 'A33TU8V', 0.00, 'Activo'),
(25, 'Mazda', 'RX-7', 'A34WX0Y', 0.00, 'Activo'),
(26, 'Kenworth', 'KW T880', 'A35ZA2B', 0.00, 'Activo'),
(27, 'Volkswagen', 'Delivery 9.170', 'A36CD4E', 0.00, 'Activo'),
(28, 'Isuzu', 'FTR', 'A37FG6H', 0.00, 'Activo'),
(29, 'Hino', '300 Series', 'A38JK8L', 0.00, 'Activo'),
(30, 'Ford', 'F-Max', 'A39MN0O', 0.00, 'Activo'),
(31, 'Toyota', 'Hilux', 'ABC-123', 1500.50, 'Activo'),
(32, 'Toyota', 'Hilux', 'ABC-123', 1500.50, 'Activo'),
(33, 'Toyota', 'Hilux', 'ABC-123', 1500.50, 'Activo'),
(34, 'Toyota', 'Hilux', 'ABC-123', 1500.50, 'Activo'),
(35, 'Toyota', 'Hilux', 'ABC-123', 1500.50, 'Activo'),
(36, 'Toyota', 'Hilux', 'ABC-123', 1500.50, 'Activo'),
(37, 'Toyota', 'Hilux', 'ABC-123', 1500.50, 'Activo'),
(38, 'Toyota', 'Hilux', 'ABC-123', 1500.50, 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimiento`
--

CREATE TABLE `mantenimiento` (
  `id` int(5) NOT NULL,
  `id_tenico` varchar(12) NOT NULL,
  `id_vehiculo` varchar(12) NOT NULL,
  `tipo_falla` varchar(12) NOT NULL,
  `comentario` varchar(100) NOT NULL,
  `estado` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `mantenimiento`
--

INSERT INTO `mantenimiento` (`id`, `id_tenico`, `id_vehiculo`, `tipo_falla`, `comentario`, `estado`) VALUES
(1, '101', '4', 'Inspeccion', 'sonido en rueda delantera izquierda', ''),
(2, '101', '4', 'Correctiva', 'sonido en rueda delantera izquierda', ''),
(3, '103', '1', 'Correctiva', 'freno trasero derecho desgastado', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(5) NOT NULL,
  `nombres` varchar(100) DEFAULT NULL,
  `contraseña` varchar(20) NOT NULL,
  `cargo` varchar(50) NOT NULL,
  `usuario` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `nombres`, `contraseña`, `cargo`, `usuario`) VALUES
(101, 'Carlos Pérez', '123', 'Chofer', 'cperez'),
(102, 'María Gómez', '', 'Chofer', ''),
(103, 'Juan Rodríguez', '123', 'Chofer', 'jrod'),
(104, 'Ana López', '', 'Chofer', ''),
(105, 'Luis Martínez', '', 'Chofer', ''),
(106, 'Sofía García', '', 'Chofer', ''),
(107, 'Pedro Sánchez', '', 'Chofer', ''),
(108, 'Lucía Fernández', '', 'Chofer', ''),
(109, 'Jorge Torres', '', 'Chofer', ''),
(110, 'Carmen Ramírez', '', 'Chofer', ''),
(111, 'Miguel Flores', '', 'Chofer', ''),
(112, 'Elena Ruiz', '', 'Chofer', ''),
(113, 'Alejandro Jiménez', '', 'Chofer', ''),
(114, 'Rosa Morales', '', 'Chofer', ''),
(115, 'David Ortiz', '', 'Chofer', ''),
(116, 'Paula Gutiérrez', '', 'Chofer', ''),
(117, 'Javier Castro', '', 'Chofer', ''),
(118, 'Beatriz Vargas', '', 'Chofer', ''),
(119, 'Francisco Ramos', '', 'Chofer', ''),
(120, 'Teresa Castillo', '', 'Chofer', ''),
(121, 'Manuel Santos', '', 'Chofer', ''),
(122, 'Gloria Mendoza', '', 'Chofer', ''),
(123, 'Antonio Silva', '', 'Chofer', ''),
(124, 'Cristina Medina', '', 'Chofer', ''),
(125, 'Alberto Herrera', '', 'Chofer', ''),
(126, 'Patricia Aguilar', '', 'Chofer', ''),
(127, 'Rafael Cruz', '', 'Chofer', ''),
(128, 'Raquel Vega', '', 'Chofer', ''),
(129, 'Sergio Navarro', '', 'Chofer', ''),
(130, 'Claudia Campos', '', 'Chofer', ''),
(131, 'Daniel Reyes', '123', 'Supervisor', 'dreyes'),
(132, 'Adriana Molina', '', 'Supervisor', ''),
(133, 'Hugo Ríos', '', 'Supervisor', ''),
(134, 'Mónica León', '', 'Supervisor', ''),
(135, 'Ricardo Peña', '', 'Supervisor', ''),
(136, 'Natalia Salas', '', 'Técnico', ''),
(137, 'Eduardo Cárdenas', '', 'Técnico', ''),
(138, 'Verónica Fuentes', '', 'Técnico', ''),
(139, 'Fernando Benítez', '', 'Técnico', ''),
(140, 'Gabriela Soto', '', 'Técnico', ''),
(141, 'Roberto Iglesias', '', 'Técnico', ''),
(142, 'Irene Cabrera', '', 'Técnico', ''),
(143, 'Gabriel Duarte', '', 'Técnico', ''),
(144, 'Silvia Pacheco', '', 'Técnico', ''),
(145, 'Mario Espinoza', '', 'Técnico', ''),
(146, 'Marcela Acuña', '', 'Técnico', ''),
(147, 'Adrián Valenzuela', '', 'Técnico', ''),
(148, 'Jimena Sandoval', '', 'Técnico', ''),
(149, 'Tomás Figueroa', '', 'Técnico', ''),
(150, 'Yolanda Paredes', '', 'Técnico', ''),
(151, 'Juan', '123', 'Administrador', '');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `flota`
--
ALTER TABLE `flota`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `flota`
--
ALTER TABLE `flota`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=152;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
