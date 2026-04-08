-- Migración: Agregar soporte para soft-delete en aprendiz_equipo
-- Fecha: 2026-04-06
-- Descripción:
--   Agrega columna 'eliminado' para permitir que aprendices eliminen equipos
--   sin eliminar los registros de la base de datos (soft-delete).
--   También permite restaurar equipos previamente eliminados.

-- ============================================================================
-- Tabla: aprendiz_equipo
-- Agregar columna 'eliminado' para soft-delete
-- ============================================================================

ALTER TABLE aprendiz_equipo 
ADD COLUMN eliminado DATETIME NULL DEFAULT NULL 
COMMENT 'Fecha de eliminación lógica (soft-delete)' 
AFTER estado;

-- Añadir índice para filtrar equipos no eliminados eficientemente
CREATE INDEX idx_aprendiz_equipo_no_eliminado 
ON aprendiz_equipo (id_aprendiz, eliminado);

-- ============================================================================
-- Tabla: equipos
-- Agregar columna 'eliminado' para soft-delete a nivel de equipo
-- ============================================================================

ALTER TABLE equipos 
ADD COLUMN eliminado DATETIME NULL DEFAULT NULL 
COMMENT 'Fecha de eliminación lógica (soft-delete)' 
AFTER activo;

CREATE INDEX idx_equipos_no_eliminado 
ON equipos (eliminado);

-- ============================================================================
-- Tabla: qr_equipos
-- Agregar columna 'eliminado' para soft-delete de QR codes
-- ============================================================================

ALTER TABLE qr_equipos 
ADD COLUMN eliminado DATETIME NULL DEFAULT NULL 
COMMENT 'Fecha de eliminación lógica (soft-delete)' 
AFTER activo;

CREATE INDEX idx_qr_equipos_no_eliminado 
ON qr_equipos (id_equipo, eliminado);