<?php

namespace App\GestionEquipos\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;

class AprendizEquipoRepository
{
    /**
     * Obtiene los equipos asociados a un aprendiz (con estado en la relación).
     * Filtra equipos eliminados lógicamente.
     */
    public function findEquiposByAprendiz(int $aprendizId): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT ae.id as relacion_id,
                        ae.estado,
                        ae.fecha_asignacion,
                        e.id as equipo_id,
                        e.numero_serial,
                        e.marca,
                        e.imagen,
                        e.activo
                 FROM aprendiz_equipo ae
                 INNER JOIN equipos e ON ae.id_equipo = e.id
                 WHERE ae.id_aprendiz = :aprendiz_id
                 AND ae.eliminado IS NULL
                 AND e.eliminado IS NULL
                 ORDER BY ae.fecha_asignacion DESC'
            );

            $stmt->execute(['aprendiz_id' => $aprendizId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching equipos by aprendiz: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene equipos eliminados previamente por el aprendiz que pueden ser restaurados.
     */
    public function findEquiposEliminados(int $aprendizId): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT ae.id as relacion_id,
                        ae.estado,
                        ae.fecha_asignacion,
                        ae.eliminado as fecha_eliminacion,
                        e.id as equipo_id,
                        e.numero_serial,
                        e.marca,
                        e.imagen,
                        e.activo
                 FROM aprendiz_equipo ae
                 INNER JOIN equipos e ON ae.id_equipo = e.id
                 WHERE ae.id_aprendiz = :aprendiz_id
                 AND ae.eliminado IS NOT NULL
                 ORDER BY ae.eliminado DESC'
            );

            $stmt->execute(['aprendiz_id' => $aprendizId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching deleted equipos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Realiza soft-delete de la relación aprendiz-equipo.
     * Establece la fecha de eliminación en lugar de borrar el registro.
     */
    public function softDelete(int $relacionId): bool
    {
        try {
            $stmt = Connection::prepare(
                'UPDATE aprendiz_equipo 
                 SET eliminado = NOW() 
                 WHERE id = :relacion_id 
                 AND eliminado IS NULL'
            );

            $stmt->execute(['relacion_id' => $relacionId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error soft-deleting aprendiz_equipo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Restaura un equipo previamente eliminado (soft-delete).
     * Limpia la fecha de eliminación para reactivarlo.
     */
    public function restore(int $relacionId): bool
    {
        try {
            $stmt = Connection::prepare(
                'UPDATE aprendiz_equipo 
                 SET eliminado = NULL 
                 WHERE id = :relacion_id 
                 AND eliminado IS NOT NULL'
            );

            $stmt->execute(['relacion_id' => $relacionId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error restoring aprendiz_equipo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica que la relación aprendiz-equipo exista y pertenezca al aprendiz.
     */
    public function findByIdAndAprendiz(int $relacionId, int $aprendizId): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT ae.id as relacion_id,
                        ae.estado,
                        ae.eliminado,
                        e.id as equipo_id,
                        e.numero_serial,
                        e.marca
                 FROM aprendiz_equipo ae
                 INNER JOIN equipos e ON ae.id_equipo = e.id
                 WHERE ae.id = :relacion_id 
                 AND ae.id_aprendiz = :aprendiz_id'
            );

            $stmt->execute(['relacion_id' => $relacionId, 'aprendiz_id' => $aprendizId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching aprendiz_equipo: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica si el aprendiz ya tiene una relación activa con el equipo.
     */
    public function hasActiveRelacion(int $aprendizId, int $equipoId): bool
    {
        try {
            $stmt = Connection::prepare(
                'SELECT COUNT(*) as total
                 FROM aprendiz_equipo
                 WHERE id_aprendiz = :aprendiz_id
                 AND id_equipo = :equipo_id
                 AND estado = "activo"
                 AND eliminado IS NULL'
            );

            $stmt->execute(['aprendiz_id' => $aprendizId, 'equipo_id' => $equipoId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0) > 0;
        } catch (PDOException $e) {
            error_log("Error checking active relacion: " . $e->getMessage());
            return false;
        }
    }

    public function createRelacion(int $aprendizId, int $equipoId, string $estado = 'activo'): int
    {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO aprendiz_equipo (id_aprendiz, id_equipo, estado) 
                 VALUES (:aprendiz_id, :equipo_id, :estado)'
            );

            $stmt->execute([
                'aprendiz_id' => $aprendizId,
                'equipo_id' => $equipoId,
                'estado' => $estado,
            ]);

            return (int) Connection::lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating relacion aprendiz_equipo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cuenta el número total de equipos activos de un aprendiz
     */
    public function countEquiposByAprendiz(int $aprendizId): int
    {
        try {
            $stmt = Connection::prepare(
                'SELECT COUNT(*) as total
                 FROM aprendiz_equipo
                 WHERE id_aprendiz = :aprendiz_id
                 AND estado = "activo"'
            );

            $stmt->execute(['aprendiz_id' => $aprendizId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error counting equipos by aprendiz: " . $e->getMessage());
            return 0;
        }
    }
}


