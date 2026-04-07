-- =============================================================================
-- MIGRACIÓN: Crear tabla boletas_salida
-- Fecha: 2026-03-19
-- Descripción: Sistema de gestión de boletas de salida temporal/definitiva
--              con flujo de aprobación: Aprendiz → Instructor → Admin → Portería
-- =============================================================================

-- Crear tabla boletas_salida
CREATE TABLE IF NOT EXISTS boletas_salida (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Datos principales
    aprendiz_id INT NOT NULL COMMENT 'Aprendiz que solicita la salida',
    ficha_id INT NOT NULL COMMENT 'Ficha del aprendiz',
    instructor_id INT NOT NULL COMMENT 'Instructor asignado para aprobar',
    
    -- Tipo y motivo de salida
    tipo_salida ENUM('temporal', 'definitiva') NOT NULL COMMENT 'Tipo de salida del CTA',
    motivo ENUM(
        'cita_medica',
        'diligencias_electorales',
        'tramites_etapa_productiva',
        'requerimientos_laborales',
        'caso_fortuito',
        'representacion_sena',
        'diligencias_judiciales',
        'otro'
    ) NOT NULL COMMENT 'Motivo de la salida',
    motivo_otro TEXT NULL COMMENT 'Descripción personalizada cuando motivo es "otro"',
    
    -- Horarios solicitados por el aprendiz
    hora_salida_solicitada TIME NOT NULL COMMENT 'Hora de salida solicitada',
    hora_reingreso_solicitada TIME NULL COMMENT 'Hora de reingreso (solo para salidas temporales)',
    
    -- Fechas reales registradas por el servidor
    fecha_salida_real DATETIME NULL COMMENT 'Timestamp real de salida física validada por portero',
    fecha_reingreso_real DATETIME NULL COMMENT 'Timestamp real de reingreso físico validado por portero',
    
    -- Estado del flujo de aprobación
    estado ENUM(
        'pendiente_instructor',
        'rechazada_instructor',
        'pendiente_admin',
        'rechazada_admin',
        'aprobada',
        'validada_porteria',
        'completada',
        'cancelada'
    ) NOT NULL DEFAULT 'pendiente_instructor' COMMENT 'Estado actual de la boleta',
    
    -- Datos de aprobación del instructor
    instructor_aprobado_por INT NULL COMMENT 'Usuario instructor que procesó la solicitud',
    instructor_aprobado_fecha DATETIME NULL COMMENT 'Fecha de aprobación/rechazo por instructor',
    instructor_motivo_rechazo TEXT NULL COMMENT 'Motivo de rechazo del instructor',
    
    -- Datos de aprobación del admin
    admin_aprobado_por INT NULL COMMENT 'Usuario admin que procesó la solicitud',
    admin_aprobado_fecha DATETIME NULL COMMENT 'Fecha de aprobación/rechazo por admin',
    admin_motivo_rechazo TEXT NULL COMMENT 'Motivo de rechazo del admin',
    
    -- Datos de validación del portero
    portero_validado_por INT NULL COMMENT 'Usuario portero que validó salida/reingreso',
    portero_validado_fecha DATETIME NULL COMMENT 'Fecha de validación por portero',
    portero_observaciones TEXT NULL COMMENT 'Observaciones del portero',
    
    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación de la solicitud',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última actualización',
    
    -- Claves foráneas
    CONSTRAINT fk_boleta_salida_aprendiz 
        FOREIGN KEY (aprendiz_id) 
        REFERENCES aprendices(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_boleta_salida_ficha 
        FOREIGN KEY (ficha_id) 
        REFERENCES fichas(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_boleta_salida_instructor 
        FOREIGN KEY (instructor_id) 
        REFERENCES usuarios(id) 
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_boleta_salida_instructor_aprobador 
        FOREIGN KEY (instructor_aprobado_por) 
        REFERENCES usuarios(id) 
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_boleta_salida_admin_aprobador 
        FOREIGN KEY (admin_aprobado_por) 
        REFERENCES usuarios(id) 
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_boleta_salida_portero_validador 
        FOREIGN KEY (portero_validado_por) 
        REFERENCES usuarios(id) 
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    
    -- Índices para optimización de consultas
    INDEX idx_aprendiz (aprendiz_id),
    INDEX idx_instructor (instructor_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha_salida (fecha_salida_real),
    INDEX idx_created_at (created_at),
    INDEX idx_tipo_salida (tipo_salida),
    INDEX idx_ficha (ficha_id),
    INDEX idx_instructor_estado (instructor_id, estado),
    INDEX idx_aprendiz_estado (aprendiz_id, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Gestión de boletas de salida temporal y definitiva del CTA';

-- =============================================================================
-- COMENTARIOS Y DOCUMENTACIÓN
-- =============================================================================
/*
FLUJO DE ESTADOS:
1. pendiente_instructor: Solicitud creada, esperando revisión del instructor
2. rechazada_instructor: Instructor rechazó la solicitud → se notifica al aprendiz
3. pendiente_admin: Instructor aprobó, esperando revisión del admin
4. rechazada_admin: Admin rechazó la solicitud → se notifica al aprendiz
5. aprobada: Admin aprobó, lista para validación en portería
6. validada_porteria: Portero validó la salida física (se registra fecha_salida_real)
7. completada: Proceso finalizado (salida definitiva o reingreso temporal registrado)
8. cancelada: Boleta cancelada manualmente

CONSIDERACIONES:
- hora_salida_solicitada y hora_reingreso_solicitada son proporcionadas por el aprendiz
- fecha_salida_real y fecha_reingreso_real se registran automáticamente (NOW()) cuando el portero valida
- Para salidas temporales, hora_reingreso_solicitada es obligatoria
- Para salidas definitivas, hora_reingreso_solicitada debe ser NULL
- Cada transición de estado envía notificación por email
- Los rechazos requieren motivo obligatorio
- El portero solo puede validar, no rechazar

INDICES:
- idx_instructor_estado: Consultas de instructor para ver sus pendientes
- idx_aprendiz_estado: Historial de boletas del aprendiz
- idx_estado: Consultas admin de todas las pendientes
- idx_fecha_salida: Reportes por fecha de salida
*/
