-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-09-2026 a las 05:06:51
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

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

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_login_usuario` (IN `p_usuario` VARCHAR(50))   BEGIN
    SELECT id, nombres, cargo, usuario, contraseña 
    FROM usuario 
    WHERE usuario = p_usuario;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_ObtenerMantenimientosPorVehiculo` (IN `p_id_vehiculo` INT)   BEGIN
    SELECT * FROM mantenimiento
    WHERE id_vehiculo = p_id_vehiculo;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_RegistrarMantenimiento` (IN `p_id_usuario` INT, IN `p_id_vehiculo` INT, IN `p_tipo_falla` VARCHAR(50), IN `p_comentario` TEXT)   BEGIN
    INSERT INTO mantenimiento (id_chofer, id_vehiculo, tipo_falla, comentario, fecha, estado_mantenimiento)
    VALUES (p_id_usuario, p_id_vehiculo, p_tipo_falla, p_comentario, NOW(), 'En Espera');

    UPDATE flota 
    SET estado = 'Inactivo' 
    WHERE id = p_id_vehiculo;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_VerEstadoVehiculos` ()   BEGIN
    SELECT id, marca, modelo, placa, kilometraje, estado, id_chofer 
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
  `estado` varchar(20) DEFAULT 'Activo',
  `id_chofer` int(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `flota`
--

INSERT INTO `flota` (`id`, `marca`, `modelo`, `placa`, `kilometraje`, `estado`, `id_chofer`) VALUES
(1, 'Mack', 'Granite (Gandola)', 'A10AB0A', 145000.00, 'Activo', 27),
(2, 'Chevrolet', 'NPR Turbo', 'A11CD1B', 89200.50, 'Activo', 23),
(3, 'Ford', 'Cargo 1721', 'A12EF2C', 120300.00, 'Activo', 7),
(4, 'Ford', 'F-350 Super Duty', 'A13GH3D', 65400.00, 'Activo', 10),
(5, 'Toyota', 'Hilux 4x4', 'A14IJ4E', 42100.00, 'Activo', 3),
(6, 'Chevrolet', 'Silverado C3500', 'A15KL5F', 51000.00, 'Activo', 22),
(7, 'Iveco', 'Stralis 490', 'A16MN6G', 210000.00, 'Activo', 2),
(8, 'Freightliner', 'Cascadia', 'A17OP7H', 180500.00, 'Activo', 5),
(9, 'Kenworth', 'T800 (Gandola)', 'A18QR8I', 195000.00, 'Activo', 15),
(10, 'Chevrolet', 'Kodiak C70', 'A19ST9J', 134000.00, 'Activo', 26),
(11, 'Mitsubishi', 'Canter FE', 'A20UV0K', 98000.00, 'Activo', 19),
(12, 'Isuzu', 'NQR 75L', 'A21WX1L', 76000.00, 'Activo', 16),
(13, 'Ford', 'F-350 Triton', 'A22YZ2M', 88500.00, 'Activo', 4),
(14, 'Toyota', 'Machito Pick-Up', 'A23AB3N', 31200.00, 'Activo', 30),
(15, 'Chevrolet', 'LUV D-Max 4x4', 'A24CD4O', 64000.00, 'Activo', 28),
(16, 'International', '7600 WorkStar', 'A25EF5P', 167000.00, 'Activo', 9),
(17, 'JAC', 'HFC 1061', 'A26GH6Q', 45000.00, 'Activo', 8),
(18, 'Ford', 'F-7000', 'A27IJ7R', 155000.00, 'Activo', 17),
(19, 'Hino', '500 Series', 'A28KL8S', 112000.00, 'Activo', 31),
(20, 'Nissan', 'Frontier 4x4', 'A29MN9T', 38900.00, 'Activo', 11),
(21, 'Mack', 'Vision CH613', 'A30OP0U', 230000.00, 'Activo', 14),
(22, 'Pegaso', '1231T (Gandola)', 'A31QR1V', 280000.00, 'Activo', 21),
(23, 'Chevrolet', 'C-70 Chasis', 'A32ST2W', 142000.00, 'Activo', 29),
(24, 'Dodge', 'Ram 2500', 'A33UV3X', 59000.00, 'Activo', 6),
(25, 'Volvo', 'FH12 420', 'A34WX4Y', 175000.00, 'Activo', 18),
(26, 'Ford', 'F-150 FX4', 'A35YZ5Z', 48000.00, 'Activo', 24),
(27, 'Mitsubishi', 'FK617', 'A36AB6A', 105000.00, 'Activo', 25),
(28, 'Dongfeng', 'Duolika', 'A37CD7B', 33000.00, 'Activo', 20),
(29, 'JAC', 'Gallop Heavy', 'A38EF8C', 190000.00, 'Activo', 12),
(30, 'Toyota', 'Dyna 4.0', 'A39GH9D', 72000.00, 'Activo', 13);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimiento`
--

CREATE TABLE `mantenimiento` (
  `id` int(5) NOT NULL,
  `id_chofer` int(5) NOT NULL,
  `id_tecnico_asignado` int(5) DEFAULT NULL,
  `id_vehiculo` int(5) NOT NULL,
  `tipo_falla` varchar(50) NOT NULL,
  `comentario` text NOT NULL,
  `trabajo_realizado` text DEFAULT NULL,
  `estado_mantenimiento` varchar(20) NOT NULL DEFAULT 'En Espera',
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(5) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `contraseña` varchar(20) NOT NULL,
  `cargo` varchar(50) NOT NULL,
  `usuario` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `nombres`, `contraseña`, `cargo`, `usuario`) VALUES
(1, 'Administrador General', '123', 'Administrador', 'admin'),
(2, 'Carlos Pérez', '123', 'Chofer', 'cperez'),
(3, 'María Gómez', '123', 'Chofer', 'mgómez'),
(4, 'Juan Rodríguez', '123', 'Chofer', 'jrod'),
(5, 'Ana López', '123', 'Chofer', 'alópez'),
(6, 'Luis Martínez', '123', 'Chofer', 'lmartínez'),
(7, 'Sofía García', '123', 'Chofer', 'sgarcía'),
(8, 'Pedro Sánchez', '123', 'Chofer', 'psánchez'),
(9, 'Lucía Fernández', '123', 'Chofer', 'lfernández'),
(10, 'Jorge Torres', '123', 'Chofer', 'jtorres'),
(11, 'Carmen Ramírez', '123', 'Chofer', 'cramírez'),
(12, 'Miguel Flores', '123', 'Chofer', 'mflores'),
(13, 'Elena Ruiz', '123', 'Chofer', 'eruiz'),
(14, 'Alejandro Jiménez', '123', 'Chofer', 'ajiménez'),
(15, 'Rosa Morales', '123', 'Chofer', 'rmorales'),
(16, 'David Ortiz', '123', 'Chofer', 'dortiz'),
(17, 'Paula Gutiérrez', '123', 'Chofer', 'pgutiérrez'),
(18, 'Javier Castro', '123', 'Chofer', 'jcastro'),
(19, 'Beatriz Vargas', '123', 'Chofer', 'bvargas'),
(20, 'Francisco Ramos', '123', 'Chofer', 'framos'),
(21, 'Teresa Castillo', '123', 'Chofer', 'tcastillo'),
(22, 'Manuel Santos', '123', 'Chofer', 'msantos'),
(23, 'Gloria Mendoza', '123', 'Chofer', 'gmendoza'),
(24, 'Antonio Silva', '123', 'Chofer', 'asilva'),
(25, 'Cristina Medina', '123', 'Chofer', 'cmedina'),
(26, 'Alberto Herrera', '123', 'Chofer', 'aherrera'),
(27, 'Patricia Aguilar', '123', 'Chofer', 'paguilar'),
(28, 'Rafael Cruz', '123', 'Chofer', 'rcruz'),
(29, 'Raquel Vega', '123', 'Chofer', 'rvega'),
(30, 'Sergio Navarro', '123', 'Chofer', 'snavarro'),
(31, 'Claudia Campos', '123', 'Chofer', 'ccampos'),
(32, 'Daniel Reyes', '123', 'Supervisor', 'dreyes'),
(33, 'Adriana Molina', '123', 'Supervisor', 'amolina'),
(34, 'Hugo Ríos', '123', 'Supervisor', 'hríos'),
(35, 'Mónica León', '123', 'Supervisor', 'mleón'),
(36, 'Ricardo Peña', '123', 'Supervisor', 'rpeña'),
(37, 'Natalia Salas', '123', 'Técnico', 'nsalas'),
(38, 'Eduardo Cárdenas', '123', 'Técnico', 'ecárdenas'),
(39, 'Verónica Fuentes', '123', 'Técnico', 'vfuentes'),
(40, 'Fernando Benítez', '123', 'Técnico', 'fbenítez'),
(41, 'Gabriela Soto', '123', 'Técnico', 'gsoto'),
(42, 'Roberto Iglesias', '123', 'Técnico', 'riglesias'),
(43, 'Irene Cabrera', '123', 'Técnico', 'icabrera'),
(44, 'Gabriel Duarte', '123', 'Técnico', 'gduarte'),
(45, 'Silvia Pacheco', '123', 'Técnico', 'spacheco'),
(46, 'Mario Espinoza', '123', 'Técnico', 'mespinoza'),
(47, 'Marcela Acuña', '123', 'Técnico', 'macuña'),
(48, 'Adrián Valenzuela', '123', 'Técnico', 'avalenzuela'),
(49, 'Jimena Sandoval', '123', 'Técnico', 'jsandoval'),
(50, 'Tomás Figueroa', '123', 'Técnico', 'tfigueroa'),
(51, 'Yolanda Paredes', '123', 'Administrador', 'yparedes'),
(52, 'Miguel M. Rodriguez', '644835', 'Administrador', 'mmrodriguez');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_historicos`
--

CREATE TABLE `usuarios_historicos` (
  `id` int(5) NOT NULL,
  `id_usuario_original` int(5) NOT NULL,
  `nombres` varchar(100) DEFAULT NULL,
  `usuario` varchar(20) NOT NULL,
  `contraseña` varchar(20) NOT NULL,
  `cargo` varchar(50) NOT NULL,
  `eliminado_por` varchar(100) DEFAULT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `fecha_eliminacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculos_historicos`
--

CREATE TABLE `vehiculos_historicos` (
  `id` int(5) NOT NULL,
  `id_vehiculo_original` int(5) NOT NULL,
  `marca` varchar(20) NOT NULL,
  `modelo` varchar(20) NOT NULL,
  `placa` varchar(20) NOT NULL,
  `kilometraje` decimal(10,2) NOT NULL,
  `estado` varchar(20) DEFAULT NULL,
  `id_chofer` int(5) DEFAULT NULL,
  `eliminado_por` varchar(100) DEFAULT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `fecha_eliminacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

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
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `usuarios_historicos`
--
ALTER TABLE `usuarios_historicos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `vehiculos_historicos`
--
ALTER TABLE `vehiculos_historicos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `flota`
--
ALTER TABLE `flota`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT de la tabla `usuarios_historicos`
--
ALTER TABLE `usuarios_historicos`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vehiculos_historicos`
--
ALTER TABLE `vehiculos_historicos`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
