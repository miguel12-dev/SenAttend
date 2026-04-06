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
                                        CONCAT(observaciones, " | Cierre automático: Salida no registrada"))
                 WHERE fecha_salida IS NULL 
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
     * Obtiene los aprendices que tienen 3 o más infracciones (cierre automático) en el rango de fechas.
     */
    public function getRepeatOffenders(string $fechaInicio, string $fechaFin): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    a.id AS id_aprendiz,
                    a.documento,
                    CONCAT(a.nombre, " ", a.apellido) AS nombre_completo,
                    f.numero_ficha,
                    COUNT(ie.id) AS total_infracciones
                 FROM ingresos_equipos ie
                 INNER JOIN aprendices a ON ie.id_aprendiz = a.id
                 LEFT JOIN fichas f ON a.id_ficha = f.id
                 WHERE ie.fecha_salida IS NULL 
                   AND ie.observaciones LIKE "%Cierre automático: Salida no registrada%"
                   AND ie.fecha_ingreso BETWEEN :fecha_inicio AND :fecha_fin
                 GROUP BY a.id, a.documento, a.nombre, a.apellido, f.numero_ficha
                 HAVING COUNT(ie.id) >= 3
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
                    CONCAT(a.nombre, ' ', a.apellido) AS nombre_aprendiz,
                    f.numero_ficha,
                    e.marca AS marca_equipo,
                    e.numero_serial,
                    ie.observaciones
                 FROM ingresos_equipos ie
                 INNER JOIN aprendices a ON ie.id_aprendiz = a.id
                 LEFT JOIN fichas f ON a.id_ficha = f.id
                 INNER JOIN equipos e ON ie.id_equipo = e.id
                 WHERE ie.id_aprendiz IN ($inClause)
                   AND ie.fecha_salida IS NULL 
                   AND ie.observaciones LIKE '%Cierre automático: Salida no registrada%'
                   AND ie.fecha_ingreso BETWEEN ? AND ?
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
}
