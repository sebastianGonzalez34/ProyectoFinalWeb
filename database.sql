-- Base de datos: helpdesk_system
-- Ejecutar completo en phpMyAdmin o MySQL CLI

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS `helpdesk_system`;

-- Usar la base de datos
USE `helpdesk_system`;

-- Tabla categorias_ticket
CREATE TABLE IF NOT EXISTS `categorias_ticket` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar categorías por defecto
INSERT INTO `categorias_ticket` (`nombre`, `descripcion`) VALUES
('Soporte', 'Problemas técnicos, acceso a internet, correo electrónico'),
('Académicos', 'Solicitudes de créditos oficiales, reclamo de notas'),
('Otro', 'Otras solicitudes o consultas');

-- Tabla colaboradores
CREATE TABLE IF NOT EXISTS `colaboradores` (
  `id_colaborador` int(11) NOT NULL AUTO_INCREMENT,
  `primer_nombre` varchar(50) NOT NULL,
  `segundo_nombre` varchar(50) DEFAULT NULL,
  `primer_apellido` varchar(50) NOT NULL,
  `segundo_apellido` varchar(50) DEFAULT NULL,
  `sexo` enum('M','F','Otro') NOT NULL,
  `identificacion` varchar(20) NOT NULL,
  `username` varchar(20) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_colaborador`),
  UNIQUE KEY `identificacion` (`identificacion`),
  UNIQUE KEY `uq_colaborador_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla encuestas_satisfaccion
CREATE TABLE IF NOT EXISTS `encuestas_satisfaccion` (
  `id_encuesta` int(11) NOT NULL AUTO_INCREMENT,
  `id_ticket` int(11) NOT NULL,
  `nivel_satisfaccion` enum('Conforme','Inconforme','Solicitud no resuelta') NOT NULL,
  `comentario` text DEFAULT NULL,
  `fecha_encuesta` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_encuesta`),
  KEY `id_ticket` (`id_ticket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla tickets
CREATE TABLE IF NOT EXISTS `tickets` (
  `id_ticket` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_agente_asignado` int(11) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `estado` enum('En espera','En proceso','Cerrado') DEFAULT 'En espera',
  `ip_solicitud` varchar(45) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre` timestamp NULL DEFAULT NULL,
  `tiempo_esperado` time DEFAULT NULL,
  `comentario_cierre` text DEFAULT NULL,
  PRIMARY KEY (`id_ticket`),
  KEY `id_colaborador` (`id_colaborador`),
  KEY `id_categoria` (`id_categoria`),
  KEY `id_agente_asignado` (`id_agente_asignado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `rol` enum('admin','agente') NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar usuario administrador y agente por defecto
INSERT INTO `usuarios` (`username`, `password`, `email`, `rol`, `activo`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@helpdesk.com', 'admin', 1),
('agente1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agente1@helpdesk.com', 'agente', 1);

-- Restricciones de claves foráneas
ALTER TABLE `encuestas_satisfaccion`
  ADD CONSTRAINT `encuestas_satisfaccion_ibfk_1` 
  FOREIGN KEY (`id_ticket`) 
  REFERENCES `tickets` (`id_ticket`);

ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` 
  FOREIGN KEY (`id_colaborador`) 
  REFERENCES `colaboradores` (`id_colaborador`),
  ADD CONSTRAINT `tickets_ibfk_2` 
  FOREIGN KEY (`id_categoria`) 
  REFERENCES `categorias_ticket` (`id_categoria`),
  ADD CONSTRAINT `tickets_ibfk_3` 
  FOREIGN KEY (`id_agente_asignado`) 
  REFERENCES `usuarios` (`id_usuario`);