-- =====================================================
-- Módulo de Eventos - Migración de Base de Datos
-- Sistema aislado para gestión de eventos
-- =====================================================

-- Tabla: eventos_usuarios
-- Almacena usuarios administrativos del módulo de eventos
CREATE TABLE IF NOT EXISTS eventos_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'administrativo') NOT NULL DEFAULT 'administrativo',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol (rol),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: eventos
-- Almacena los eventos creados en el sistema
CREATE TABLE IF NOT EXISTS eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    imagen_url VARCHAR(500),
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    tipo_participantes ENUM('instructores', 'aprendices', 'todos') NOT NULL DEFAULT 'instructores',
    estado ENUM('programado', 'en_curso', 'finalizado', 'cancelado') NOT NULL DEFAULT 'programado',
    creado_por INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES eventos_usuarios(id) ON DELETE SET NULL,
    INDEX idx_estado (estado),
    INDEX idx_fecha_inicio (fecha_inicio),
    INDEX idx_fecha_fin (fecha_fin),
    INDEX idx_tipo_participantes (tipo_participantes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: eventos_participantes
-- Almacena los participantes registrados en cada evento
CREATE TABLE IF NOT EXISTS eventos_participantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    documento VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    tipo ENUM('instructor', 'aprendiz', 'externo') NOT NULL DEFAULT 'instructor',
    estado ENUM('registrado', 'ingreso', 'salida', 'ausente', 'sin_salida') NOT NULL DEFAULT 'registrado',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_ingreso DATETIME,
    fecha_salida DATETIME,
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participante_evento (evento_id, documento),
    INDEX idx_evento (evento_id),
    INDEX idx_documento (documento),
    INDEX idx_estado (estado),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: eventos_qr
-- Almacena los códigos QR generados para ingreso/salida
CREATE TABLE IF NOT EXISTS eventos_qr (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participante_id INT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    tipo ENUM('ingreso', 'salida') NOT NULL,
    qr_data TEXT NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_uso DATETIME,
    FOREIGN KEY (participante_id) REFERENCES eventos_participantes(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_participante (participante_id),
    INDEX idx_tipo (tipo),
    INDEX idx_usado (usado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuario admin por defecto (password: admin123)
-- IMPORTANTE: Cambiar esta contraseña en producción
INSERT INTO eventos_usuarios (email, password_hash, nombre, rol, activo)
VALUES ('admin.eventos@sena.edu.co', '$2y$10$XJPXOsFjBF0wKerujtN2n.OkrbnAkFdkPqGVTkZToXAze0pRLWRQy', 'Administrador Eventos', 'admin', TRUE)
ON DUPLICATE KEY UPDATE nombre = nombre;

