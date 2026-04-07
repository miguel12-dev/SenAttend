<?php

namespace App\GestionEquipos\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;

/**
 * Repository para el seguimiento de equipos huérfanos de salida (Infracciones).
 */
class SeguimientoEquiposRepository
{
    /**
     * Cierra automáticamente los registros que quedaron sin salida en días anteriores.
     */
    public function closePendingEntries(): int
    {
        try {
            $stmt = Connection::prepare(
                'UPDATE ingresos_equipos 
                 SET observaciones = IF(observaciones IS NULL OR observaciones = "", 
                                        "Cierre automático: Salida no registrada", 
                                        CONCAT(observaciones, " | Cierre automático: Salida no registrada")),
                     procesado = TRUE
                 WHERE procesado = FALSE 
                   AND fecha_ingreso < CURRENT_DATE()'
            );

            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('SeguimientoEquiposRepository::closePendingEntries - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene los aprendices que tienen 3 o más infracciones en el rango de fechas.
     * 
     * Una infracción se considera cuando:
     * 1. La salida NO fue registrada (fecha_salida IS NULL O hora_salida IS NULL)
     * 2. O existe una observación/anomalía registrada
     */
    public function getRepeatOffenders(string $fechaInicio, string $fechaFin): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    a.id AS id_aprendiz,
                    a.documento,
                    CONCAT_WS(" ", a.nombre, a.apellido) AS nombre_completo,
                    GROUP_CONCAT(DISTINCT f.numero_ficha SEPARATOR ", ") AS numero_ficha,
                    COUNT(ie.id) AS total_infracciones
                 FROM ingresos_equipos ie
                 INNER JOIN aprendices a ON ie.id_aprendiz = a.id
                 LEFT JOIN ficha_aprendiz fa ON a.id = fa.id_aprendiz
                 LEFT JOIN fichas f ON fa.id_ficha = f.id
                 WHERE ie.fecha_ingreso BETWEEN :fecha_inicio AND :fecha_fin
                   AND (
                        ie.fecha_salida IS NULL 
                        OR ie.hora_salida IS NULL
                        OR (ie.observaciones IS NOT NULL AND ie.observaciones != "")
                   )
                 GROUP BY a.id, a.documento, a.nombre, a.apellido
                 HAVING COUNT(ie.id) >= 1
                 ORDER BY total_infracciones DESC'
            );

            $stmt->execute([
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin'    => $fechaFin
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('SeguimientoEquiposRepository::getRepeatOffenders - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el detalle de las infracciones para exportar a Excel.
     * 
     * Incluye TODAS las infracciones (sin salida o con observación) para los aprendices infractores.
     */
    public function getViolationsDetails(string $fechaInicio, string $fechaFin): array
    {
        try {
            // First we identify the repeat offenders
            $offenders = $this->getRepeatOffenders($fechaInicio, $fechaFin);
            if (empty($offenders)) {
                return [];
            }
            
            $ids = array_column($offenders, 'id_aprendiz');
            $inClause = implode(',', array_fill(0, count($ids), '?'));

            $query = 
                "SELECT 
                    ie.fecha_ingreso,
                    ie.hora_ingreso,
                    a.documento AS documento_aprendiz,
                    CONCAT_WS(' ', a.nombre, a.apellido) AS nombre_aprendiz,
                    GROUP_CONCAT(DISTINCT f.numero_ficha SEPARATOR ', ') AS numero_ficha,
                    e.marca AS marca_equipo,
                    e.numero_serial,
                    ie.observaciones,
                    CASE 
                        WHEN ie.fecha_salida IS NULL OR ie.hora_salida IS NULL THEN 'Sin registro de salida'
                        ELSE 'Con observación'
                    END AS tipo_infraccion
                 FROM ingresos_equipos ie
                 INNER JOIN aprendices a ON ie.id_aprendiz = a.id
                 LEFT JOIN ficha_aprendiz fa ON a.id = fa.id_aprendiz
                 LEFT JOIN fichas f ON fa.id_ficha = f.id
                 INNER JOIN equipos e ON ie.id_equipo = e.id
                 WHERE ie.id_aprendiz IN ($inClause)
                   AND ie.fecha_ingreso BETWEEN ? AND ?
                   AND (
                        ie.fecha_salida IS NULL 
                        OR ie.hora_salida IS NULL
                        OR (ie.observaciones IS NOT NULL AND ie.observaciones != '')
                   )
                 GROUP BY ie.id, ie.fecha_ingreso, ie.hora_ingreso, a.documento, a.nombre, a.apellido, e.marca, e.numero_serial, ie.observaciones
                 ORDER BY a.nombre ASC, a.apellido ASC, ie.fecha_ingreso ASC";


            $stmt = Connection::prepare($query);
            
            $params = array_merge($ids, [$fechaInicio, $fechaFin]);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('SeguimientoEquiposRepository::getViolationsDetails - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las infracciones de un aprendiz específico para el modal de detalle.
     */
    public function getViolationsByAprendiz(int $aprendizId, string $fechaInicio, string $fechaFin): array
    {
        try {
            $stmt = Connection::prepare(
                "SELECT 
                    ie.id,
                    ie.fecha_ingreso,
                    ie.hora_ingreso,
                    ie.fecha_salida,
                    ie.hora_salida,
                    a.documento AS documento_aprendiz,
                    CONCAT_WS(' ', a.nombre, a.apellido) AS nombre_aprendiz,
                    GROUP_CONCAT(DISTINCT f.numero_ficha SEPARATOR ', ') AS numero_ficha,
                    e.marca AS marca_equipo,
                    e.numero_serial,
                    ie.observaciones,
                    CASE 
                        WHEN ie.fecha_salida IS NULL OR ie.hora_salida IS NULL THEN 'Sin registro de salida'
                        ELSE 'Con observación'
                    END AS tipo_infraccion
                 FROM ingresos_equipos ie
                 INNER JOIN aprendices a ON ie.id_aprendiz = a.id
                 LEFT JOIN ficha_aprendiz fa ON a.id = fa.id_aprendiz
                 LEFT JOIN fichas f ON fa.id_ficha = f.id
                 INNER JOIN equipos e ON ie.id_equipo = e.id
                 WHERE ie.id_aprendiz = ?
                   AND ie.fecha_ingreso BETWEEN ? AND ?
                   AND (
                        ie.fecha_salida IS NULL 
                        OR ie.hora_salida IS NULL
                        OR (ie.observaciones IS NOT NULL AND ie.observaciones != '')
                   )
                 GROUP BY ie.id, ie.fecha_ingreso, ie.hora_ingreso, ie.fecha_salida, ie.hora_salida, a.documento, a.nombre, a.apellido, e.marca, e.numero_serial, ie.observaciones
                 ORDER BY ie.fecha_ingreso DESC, ie.hora_ingreso DESC"
            );

            $stmt->execute([$aprendizId, $fechaInicio, $fechaFin]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('SeguimientoEquiposRepository::getViolationsByAprendiz - ' . $e->getMessage());
            return [];
        }
    }
}
