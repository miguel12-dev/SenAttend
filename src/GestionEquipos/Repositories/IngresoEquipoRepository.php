<?php

namespace App\GestionEquipos\Repositories;

use App\Database\Connection;
use PDOException;

/**
 * Repositorio para gestión de ingresos y salidas de equipos
 */
class IngresoEquipoRepository
{
    /**
     * Crea un nuevo registro de ingreso
     */
    public function create(array $data): int
    {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO ingresos_equipos 
                 (id_equipo, id_aprendiz, fecha_ingreso, hora_ingreso, id_portero, observaciones, procesado)
                 VALUES 
                 (:id_equipo, :id_aprendiz, :fecha_ingreso, :hora_ingreso, :id_portero, :observaciones, :procesado)'
            );

            $stmt->execute([
                'id_equipo' => $data['id_equipo'],
                'id_aprendiz' => $data['id_aprendiz'],
                'fecha_ingreso' => $data['fecha_ingreso'],
                'hora_ingreso' => $data['hora_ingreso'],
                'id_portero' => $data['id_portero'],
                'observaciones' => $data['observaciones'] ?? null,
                'procesado' => 0 // FALSE
            ]);

            return (int) Connection::lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating ingreso_equipo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registra la salida de un equipo
     */
    public function registrarSalida(int $ingresoId, int $porteroId, ?string $observaciones = null): bool
    {
        try {
             $stmt = Connection::prepare(
                'UPDATE ingresos_equipos 
                 SET fecha_salida = CURDATE(),
                     hora_salida = CURTIME(),
                     observaciones = COALESCE(:observaciones, observaciones),
                     procesado = TRUE
                 WHERE id = :ingreso_id 
                   AND procesado = FALSE'
            );

            return $stmt->execute([
                'ingreso_id' => $ingresoId,
                'observaciones' => $observaciones,
            ]);
        } catch (PDOException $e) {
            error_log("Error registering salida: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca un ingreso activo (sin salida) para un equipo
     */
    public function findIngresoActivo(int $equipoId): ?array
    {
        try {
             $stmt = Connection::prepare(
                'SELECT * FROM ingresos_equipos 
                 WHERE id_equipo = :equipo_id 
                   AND procesado = FALSE
                 ORDER BY fecha_ingreso DESC, hora_ingreso DESC
                 LIMIT 1'
            );

            $stmt->execute(['equipo_id' => $equipoId]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("Error finding active ingreso: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene todos los ingresos activos (sin salida) filtrados opcionalmente por fecha
     */
    public function findIngresosActivos(int $limit = 50, int $offset = 0, ?string $fecha = null): array
    {
        try {
            $sql = 'SELECT 
                        ie.*,
                        e.numero_serial,
                        e.marca,
                        a.nombre as aprendiz_nombre,
                        a.apellido as aprendiz_apellido,
                        a.documento as aprendiz_documento,
                        u.nombre as portero_nombre
                     FROM ingresos_equipos ie
                     INNER JOIN equipos e ON ie.id_equipo = e.id
                     INNER JOIN aprendices a ON ie.id_aprendiz = a.id
                     INNER JOIN usuarios u ON ie.id_portero = u.id
                     WHERE ie.procesado = FALSE';
            
            if ($fecha) {
                $sql .= ' AND ie.fecha_ingreso = :fecha';
            }
            
            $sql .= ' ORDER BY ie.fecha_ingreso DESC, ie.hora_ingreso DESC
                     LIMIT :limit OFFSET :offset';

            $stmt = Connection::prepare($sql);

            if ($fecha) {
                $stmt->bindValue(':fecha', $fecha, \PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding active ingresos: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Cuenta ingresos activos filtrados por fecha
     */
    public function countIngresosActivos(?string $fecha = null): int
    {
        try {
            $fechaFiltro = $fecha ?: date('Y-m-d');

             $stmt = Connection::prepare(
                'SELECT COUNT(*) as total
                 FROM ingresos_equipos
                 WHERE procesado = FALSE
                   AND fecha_ingreso = :fecha_ingreso'
            );

            $stmt->execute(['fecha_ingreso' => $fechaFiltro]);
            $result = $stmt->fetch();
            return (int) ($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error counting active ingresos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene un ingreso por ID
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    ie.*,
                    e.numero_serial,
                    e.marca,
                    a.nombre as aprendiz_nombre,
                    a.apellido as aprendiz_apellido,
                    a.documento as aprendiz_documento,
                    u.nombre as portero_nombre
                 FROM ingresos_equipos ie
                 INNER JOIN equipos e ON ie.id_equipo = e.id
                 INNER JOIN aprendices a ON ie.id_aprendiz = a.id
                 INNER JOIN usuarios u ON ie.id_portero = u.id
                 WHERE ie.id = :id
                 LIMIT 1'
            );

            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("Error finding ingreso by id: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca la última operación (ingreso o salida) de un equipo en los últimos N minutos.
     *
     * Usa una comparación unificada de DATETIME para evitar falsos negativos causados
     * por separar fecha y hora en columnas distintas.
     *
     * @param int $equipoId ID del equipo
     * @param int $minutos  Ventana de tiempo en minutos (default: 5)
     * @return array|null  Registro encontrado o null si no hay operación reciente
     */
    public function findUltimaOperacionReciente(int $equipoId, int $minutos = 5): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    id,
                    id_equipo,
                    id_aprendiz,
                    fecha_ingreso,
                    hora_ingreso,
                    fecha_salida,
                    hora_salida,
                    procesado,
                    CASE 
                        WHEN procesado = FALSE AND fecha_salida IS NOT NULL AND hora_salida IS NOT NULL
                            THEN CONCAT(fecha_salida, " ", hora_salida)
                        WHEN procesado = FALSE
                            THEN CONCAT(fecha_ingreso, " ", hora_ingreso)
                        WHEN procesado = TRUE AND fecha_salida IS NOT NULL AND hora_salida IS NOT NULL
                            THEN CONCAT(fecha_salida, " ", hora_salida)
                        ELSE CONCAT(fecha_ingreso, " ", hora_ingreso)
                    END AS ultima_fecha_hora
                 FROM ingresos_equipos
                 WHERE id_equipo = :equipo_id
                   AND (
                       (procesado = FALSE AND CONCAT(fecha_ingreso, " ", hora_ingreso) >= NOW() - INTERVAL :minutos MINUTE)
                       OR
                       (procesado = TRUE AND fecha_salida IS NOT NULL AND hora_salida IS NOT NULL 
                        AND CONCAT(fecha_salida, " ", hora_salida) >= NOW() - INTERVAL :minutos2 MINUTE)
                   )
                 ORDER BY ultima_fecha_hora DESC
                 LIMIT 1'
            );

            $stmt->execute([
                'equipo_id' => $equipoId,
                'minutos' => $minutos,
                'minutos2' => $minutos,
            ]);

            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("Error finding recent operation: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene historial de ingresos/salidas con filtros
     */
    public function findHistorial(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        try {
            $conditions = [];
            $params = [];

            if (!empty($filters['fecha_desde'])) {
                $conditions[] = 'ie.fecha_ingreso >= :fecha_desde';
                $params['fecha_desde'] = $filters['fecha_desde'];
            }

            if (!empty($filters['fecha_hasta'])) {
                $conditions[] = 'ie.fecha_ingreso <= :fecha_hasta';
                $params['fecha_hasta'] = $filters['fecha_hasta'];
            }

            if (!empty($filters['equipo_id'])) {
                $conditions[] = 'ie.id_equipo = :equipo_id';
                $params['equipo_id'] = $filters['equipo_id'];
            }

            if (!empty($filters['aprendiz_id'])) {
                $conditions[] = 'ie.id_aprendiz = :aprendiz_id';
                $params['aprendiz_id'] = $filters['aprendiz_id'];
            }

            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

            $sql = "SELECT 
                        ie.*,
                        e.numero_serial,
                        e.marca,
                        a.nombre as aprendiz_nombre,
                        a.apellido as aprendiz_apellido,
                        a.documento as aprendiz_documento,
                        u.nombre as portero_nombre
                     FROM ingresos_equipos ie
                     INNER JOIN equipos e ON ie.id_equipo = e.id
                     INNER JOIN aprendices a ON ie.id_aprendiz = a.id
                     INNER JOIN usuarios u ON ie.id_portero = u.id
                     {$whereClause}
                     ORDER BY ie.fecha_ingreso DESC, ie.hora_ingreso DESC
                     LIMIT :limit OFFSET :offset";

            $stmt = Connection::prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value, \PDO::PARAM_STR);
            }

            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error finding historial: " . $e->getMessage());
            return [];
        }
    }
}

