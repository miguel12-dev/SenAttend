-- Migración: Agregar campo 'procesado' a ingresos_equipos
-- Fecha: 2026-04-06
-- Descripción:
--   Agrega una columna booleana para marcar si un ingreso de equipo ya fue finalizado
--   (ya sea por salida normal o por cierre automático). Esto resuelve el problema
--   de que equipos cerrados automáticamente sigan apareciendo como 'dentro'.

ALTER TABLE ingresos_equipos 
ADD COLUMN procesado BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Indica si el proceso de ingreso-salida ha terminado';

-- Crear índice para mejorar rendimiento de los filtros por este campo
CREATE INDEX idx_ingresos_procesado ON ingresos_equipos(procesado);

-- Actualizar registros existentes:
-- 1. Los que ya tienen fecha de salida ya están procesados
UPDATE ingresos_equipos 
SET procesado = TRUE 
WHERE fecha_salida IS NOT NULL;

-- 2. Los que tienen observación de 'Cierre automático' también están procesados
UPDATE ingresos_equipos 
SET procesado = TRUE 
WHERE observaciones LIKE '%Cierre automático%';
