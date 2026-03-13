-- Migración: Crear tabla password_reset_tokens
-- Almacena tokens de recuperación de contraseña para usuarios y aprendices

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('usuario', 'aprendiz') NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    email VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_user (user_id, user_type),
    INDEX idx_expires_at (expires_at),
    INDEX idx_used (used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
