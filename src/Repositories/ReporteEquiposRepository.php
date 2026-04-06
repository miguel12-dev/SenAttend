<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;

/**
 * Repository para consultas del reporte de ingresos y salidas de equipos.
 * Realiza JOINs entre ingresos_equipos, equipos, aprendices y usuarios.
 *
 * @version 1.0
 */
class ReporteEquiposRepository
{
    /**
     * Obtiene registros paginados del reporte con filtro de fechas.
     *
     * @param string $fechaInicio Fecha inicio en formato Y-m-d
     * @param string $fechaFin    Fecha fin en formato Y-m-d
     * @param int    $limit       Registros por página
     * @param int    $offset      Desplazamiento
     * @return array
     */
    public function getReportePaginated(
        string $fechaInicio,
        string $fechaFin,
        int $limit = 20,
        int $offset = 0
    ): array {
        try {
            $stmt = Connection::prepare(
                'SELECT
                    ie.id,
                    ie.fecha_ingreso,
                    ie.hora_ingreso,
                    ie.fecha_salida,
                    ie.hora_salida,
                    ie.observaciones,
                    CONCAT(a.nombre, \' \', a.apellido) AS nombre_aprendiz,
                    a.documento AS documento_aprendiz,
                    e.marca   AS marca_equipo,
                    e.numero_serial,
                    u.nombre  AS nombre_portero
                FROM ingresos_equipos ie
                INNER JOIN equipos    e ON ie.id_equipo   = e.id
                INNER JOIN aprendices a ON ie.id_aprendiz = a.id
                INNER JOIN usuarios   u ON ie.id_portero  = u.id
                WHERE ie.fecha_ingreso BETWEEN :fecha_inicio AND :fecha_fin
                ORDER BY ie.fecha_ingreso ASC, ie.hora_ingreso ASC
                LIMIT :limit OFFSET :offset'
            );

            $stmt->bindValue(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin',    $fechaFin,    PDO::PARAM_STR);
            $stmt->bindValue(':limit',        $limit,       PDO::PARAM_INT);
            $stmt->bindValue(':offset',       $offset,      PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('ReporteEquiposRepository::getReportePaginated - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta el total de registros para calcular la paginación.
     *
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return int
     */
    public function getTotalRegistros(string $fechaInicio, string $fechaFin): int
    {
        try {
            $stmt = Connection::prepare(
                'SELECT COUNT(*) AS total
                 FROM ingresos_equipos ie
                 WHERE ie.fecha_ingreso BETWEEN :fecha_inicio AND :fecha_fin'
            );

            $stmt->execute([
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $fechaFin,
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($row['total'] ?? 0);
        } catch (PDOException $e) {
            error_log('ReporteEquiposRepository::getTotalRegistros - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene TODOS los registros del período (sin paginación) para exportar a Excel.
     *
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return array
     */
    public function getAllParaExportar(string $fechaInicio, string $fechaFin): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT
                    ie.fecha_ingreso,
                    ie.hora_ingreso,
                    ie.fecha_salida,
                    ie.hora_salida,
                    ie.observaciones,
                    CONCAT(a.nombre, \' \', a.apellido) AS nombre_aprendiz,
                    a.documento AS documento_aprendiz,
                    e.marca   AS marca_equipo,
                    e.numero_serial,
                    u.nombre  AS nombre_portero
                FROM ingresos_equipos ie
                INNER JOIN equipos    e ON ie.id_equipo   = e.id
                INNER JOIN aprendices a ON ie.id_aprendiz = a.id
                INNER JOIN usuarios   u ON ie.id_portero  = u.id
                WHERE ie.fecha_ingreso BETWEEN :fecha_inicio AND :fecha_fin
                ORDER BY ie.fecha_ingreso ASC, ie.hora_ingreso ASC'
            );

            $stmt->execute([
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $fechaFin,
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('ReporteEquiposRepository::getAllParaExportar - ' . $e->getMessage());
            return [];
        }
    }
}
