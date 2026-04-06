<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;

/**
 * Repository para CRUD de configuracion_turnos_equipos.
 * Gestiona horarios globales y excepciones por fecha específica.
 *
 * @version 1.0
 */
class ConfiguracionTurnosEquiposRepository
{
    /**
     * Obtiene todos los horarios globales (fecha_especifica IS NULL).
     */
    public function obtenerHorariosGlobales(): array
    {
        try {
            $stmt = Connection::prepare(
                "SELECT id, turno, hora_inicio, hora_fin, descripcion
                 FROM configuracion_turnos_equipos
                 WHERE fecha_especifica IS NULL
                 ORDER BY FIELD(turno, 'Mañana', 'Tarde', 'Noche')"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('ConfiguracionTurnosEquiposRepository::obtenerHorariosGlobales - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los horarios con fecha específica (excepciones).
     */
    public function obtenerExcepcionesFechas(): array
    {
        try {
            $stmt = Connection::prepare(
                "SELECT id, turno, hora_inicio, hora_fin, fecha_especifica, descripcion
                 FROM configuracion_turnos_equipos
                 WHERE fecha_especifica IS NOT NULL
                 ORDER BY fecha_especifica ASC, FIELD(turno, 'Mañana', 'Tarde', 'Noche')"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('ConfiguracionTurnosEquiposRepository::obtenerExcepcionesFechas - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualiza un horario global (fecha_especifica IS NULL) por turno.
     *
     * @param string $turno    Nombre del turno: Mañana, Tarde o Noche
     * @param string $inicio   Hora inicio HH:MM:SS
     * @param string $fin      Hora fin HH:MM:SS
     * @param string $descripcion
     */
    public function actualizarHorarioGlobal(string $turno, string $inicio, string $fin, string $descripcion = ''): bool
    {
        try {
            $stmt = Connection::prepare(
                "UPDATE configuracion_turnos_equipos
                 SET hora_inicio = :inicio, hora_fin = :fin, descripcion = :descripcion
                 WHERE turno = :turno AND fecha_especifica IS NULL"
            );
            $stmt->execute([
                'turno'       => $turno,
                'inicio'      => $inicio,
                'fin'         => $fin,
                'descripcion' => $descripcion,
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('ConfiguracionTurnosEquiposRepository::actualizarHorarioGlobal - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Inserta o reemplaza un horario para una fecha específica y turno.
     */
    public function upsertExcepcionFecha(
        string $turno,
        string $inicio,
        string $fin,
        string $fecha,
        string $descripcion = ''
    ): bool {
        try {
            $stmt = Connection::prepare(
                "INSERT INTO configuracion_turnos_equipos
                     (turno, hora_inicio, hora_fin, fecha_especifica, descripcion)
                 VALUES (:turno, :inicio, :fin, :fecha, :descripcion)
                 ON DUPLICATE KEY UPDATE
                     hora_inicio  = VALUES(hora_inicio),
                     hora_fin     = VALUES(hora_fin),
                     descripcion  = VALUES(descripcion)"
            );
            $stmt->execute([
                'turno'       => $turno,
                'inicio'      => $inicio,
                'fin'         => $fin,
                'fecha'       => $fecha,
                'descripcion' => $descripcion,
            ]);
            return true;
        } catch (PDOException $e) {
            error_log('ConfiguracionTurnosEquiposRepository::upsertExcepcionFecha - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un registro de excepción por su ID.
     */
    public function eliminarExcepcion(int $id): bool
    {
        try {
            $stmt = Connection::prepare(
                "DELETE FROM configuracion_turnos_equipos
                 WHERE id = :id AND fecha_especifica IS NOT NULL"
            );
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('ConfiguracionTurnosEquiposRepository::eliminarExcepcion - ' . $e->getMessage());
            return false;
        }
    }
}
