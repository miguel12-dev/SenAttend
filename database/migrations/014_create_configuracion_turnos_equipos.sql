CREATE TABLE IF NOT EXISTS `configuracion_turnos_equipos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `turno` ENUM('Mañana', 'Tarde', 'Noche') NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fin` TIME NOT NULL,
  `fecha_especifica` DATE NULL DEFAULT NULL,
  `descripcion` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_turno_fecha` (`turno`, `fecha_especifica`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserting default seed data for global schedules (fecha_especifica = NULL)
INSERT IGNORE INTO `configuracion_turnos_equipos` (`turno`, `hora_inicio`, `hora_fin`, `fecha_especifica`, `descripcion`) VALUES
('Mañana', '05:30:00', '11:00:00', NULL, 'Horario global Mañana'),
('Tarde', '11:30:00', '17:00:00', NULL, 'Horario global Tarde'),
('Noche', '17:00:00', '23:59:59', NULL, 'Horario global Noche');
