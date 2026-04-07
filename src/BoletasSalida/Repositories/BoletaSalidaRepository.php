<?php

namespace App\BoletasSalida\Repositories;

use App\Database\Connection;
use PDO;

/**
 * Repositorio para gestión de boletas de salida
 */
class BoletaSalidaRepository
{
    /**
     * Crear nueva boleta de salida
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO boletas_salida (
            aprendiz_id,
            ficha_id,
            instructor_id,
            tipo_salida,
            motivo,
            motivo_otro,
            hora_salida_solicitada,
            hora_reingreso_solicitada,
            estado
        ) VALUES (
            :aprendiz_id,
            :ficha_id,
            :instructor_id,
            :tipo_salida,
            :motivo,
            :motivo_otro,
            :hora_salida_solicitada,
            :hora_reingreso_solicitada,
            'pendiente_instructor'
        )";

        $stmt = Connection::prepare($sql);
        $stmt->execute([
            ':aprendiz_id' => $data['aprendiz_id'],
            ':ficha_id' => $data['ficha_id'],
            ':instructor_id' => $data['instructor_id'],
            ':tipo_salida' => $data['tipo_salida'],
            ':motivo' => $data['motivo'],
            ':motivo_otro' => $data['motivo_otro'] ?? null,
            ':hora_salida_solicitada' => $data['hora_salida_solicitada'],
            ':hora_reingreso_solicitada' => $data['hora_reingreso_solicitada'] ?? null,
        ]);

        return (int) Connection::lastInsertId();
    }

    /**
     * Obtener boleta por ID con información completa
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT 
            bs.*,
            a.nombre AS aprendiz_nombre,
            a.apellido AS aprendiz_apellido,
            a.documento AS aprendiz_documento,
            f.numero_ficha,
            f.nombre AS ficha_nombre,
            u.nombre AS instructor_nombre,
            u.email AS instructor_email,
            ia.nombre AS instructor_aprobador_nombre,
            aa.nombre AS admin_aprobador_nombre,
            pa.nombre AS portero_validador_nombre
        FROM boletas_salida bs
        INNER JOIN aprendices a ON bs.aprendiz_id = a.id
        INNER JOIN fichas f ON bs.ficha_id = f.id
        INNER JOIN usuarios u ON bs.instructor_id = u.id
        LEFT JOIN usuarios ia ON bs.instructor_aprobado_por = ia.id
        LEFT JOIN usuarios aa ON bs.admin_aprobado_por = aa.id
        LEFT JOIN usuarios pa ON bs.portero_validado_por = pa.id
        WHERE bs.id = :id";

        $stmt = Connection::prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtener boletas pendientes de un instructor específico
     */
    public function findPendientesByInstructor(int $instructorId): array
    {
        $sql = "SELECT 
            bs.*,
            a.nombre AS aprendiz_nombre,
            a.apellido AS aprendiz_apellido,
            a.documento AS aprendiz_documento,
            f.numero_ficha,
            f.nombre AS ficha_nombre
        FROM boletas_salida bs
        INNER JOIN aprendices a ON bs.aprendiz_id = a.id
        INNER JOIN fichas f ON bs.ficha_id = f.id
        WHERE bs.instructor_id = :instructor_id
        AND bs.estado = 'pendiente_instructor'
        ORDER BY bs.created_at DESC";

        $stmt = Connection::prepare($sql);
        $stmt->execute([':instructor_id' => $instructorId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener historial de boletas procesadas por un instructor
     */
    public function findHistorialByInstructor(int $instructorId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT 
            bs.*,
            a.nombre AS aprendiz_nombre,
            a.apellido AS aprendiz_apellido,
            a.documento AS aprendiz_documento,
            f.numero_ficha,
            f.nombre AS ficha_nombre
        FROM boletas_salida bs
        INNER JOIN aprendices a ON bs.aprendiz_id = a.id
        INNER JOIN fichas f ON bs.ficha_id = f.id
        WHERE bs.instructor_id = :instructor_id
        AND bs.estado != 'pendiente_instructor'
        ORDER BY bs.updated_at DESC
        LIMIT :limit OFFSET :offset";

        $stmt = Connection::prepare($sql);
        $stmt->bindValue(':instructor_id', $instructorId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener boletas pendientes para admin
     */
    public function findPendientesAdmin(): array
    {
        $sql = "SELECT 
            bs.*,
            a.nombre AS aprendiz_nombre,
            a.apellido AS aprendiz_apellido,
            a.documento AS aprendiz_documento,
            f.numero_ficha,
            f.nombre AS ficha_nombre,
            u.nombre AS instructor_nombre,
            ia.nombre AS instructor_aprobador_nombre,
            bs.instructor_aprobado_fecha
        FROM boletas_salida bs
        INNER JOIN aprendices a ON bs.aprendiz_id = a.id
        INNER JOIN fichas f ON bs.ficha_id = f.id
        INNER JOIN usuarios u ON bs.instructor_id = u.id
        LEFT JOIN usuarios ia ON bs.instructor_aprobado_por = ia.id
        WHERE bs.estado = 'pendiente_admin'
        ORDER BY bs.instructor_aprobado_fecha DESC";

        $stmt = Connection::prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener historial completo para admin
     */
    public function findHistorialAdmin(int $limit = 100, int $offset = 0, array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['estado'])) {
            $where[] = "bs.estado = :estado";
            $params[':estado'] = $filters['estado'];
        }

        if (!empty($filters['tipo_salida'])) {
            $where[] = "bs.tipo_salida = :tipo_salida";
            $params[':tipo_salida'] = $filters['tipo_salida'];
        }

        if (!empty($filters['fecha_desde'])) {
            $where[] = "DATE(bs.created_at) >= :fecha_desde";
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }

        if (!empty($filters['fecha_hasta'])) {
            $where[] = "DATE(bs.created_at) <= :fecha_hasta";
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT 
            bs.*,
            a.nombre AS aprendiz_nombre,
            a.apellido AS aprendiz_apellido,
            a.documento AS aprendiz_documento,
            f.numero_ficha,
            f.nombre AS ficha_nombre,
            u.nombre AS instructor_nombre,
            ia.nombre AS instructor_aprobador_nombre,
            aa.nombre AS admin_aprobador_nombre
        FROM boletas_salida bs
        INNER JOIN aprendices a ON bs.aprendiz_id = a.id
        INNER JOIN fichas f ON bs.ficha_id = f.id
        INNER JOIN usuarios u ON bs.instructor_id = u.id
        LEFT JOIN usuarios ia ON bs.instructor_aprobado_por = ia.id
        LEFT JOIN usuarios aa ON bs.admin_aprobado_por = aa.id
        {$whereClause}
        ORDER BY bs.created_at DESC
        LIMIT :limit OFFSET :offset";

        $stmt = Connection::prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener boletas aprobadas (listas para validación en portería)
     */
    public function findAprobadas(): array
    {
        $sql = "SELECT 
            bs.*,
            a.nombre AS aprendiz_nombre,
            a.apellido AS aprendiz_apellido,
            a.documento AS aprendiz_documento,
            f.numero_ficha,
            f.nombre AS ficha_nombre
        FROM boletas_salida bs
        INNER JOIN aprendices a ON bs.aprendiz_id = a.id
        INNER JOIN fichas f ON bs.ficha_id = f.id
        WHERE bs.estado = 'aprobada'
        ORDER BY bs.admin_aprobado_fecha DESC";

        $stmt = Connection::prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener boletas validadas en portería pendientes de reingreso (solo temporales)
     */
    public function findPendientesReingreso(): array
    {
        $sql = "SELECT 
            bs.*,
            a.nombre AS aprendiz_nombre,
            a.apellido AS aprendiz_apellido,
            a.documento AS aprendiz_documento,
            f.numero_ficha,
            f.nombre AS ficha_nombre
        FROM boletas_salida bs
        INNER JOIN aprendices a ON bs.aprendiz_id = a.id
        INNER JOIN fichas f ON bs.ficha_id = f.id
        WHERE bs.estado = 'validada_porteria'
        AND bs.tipo_salida = 'temporal'
        AND bs.fecha_reingreso_real IS NULL
        ORDER BY bs.fecha_salida_real ASC";

        $stmt = Connection::prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener historial de boletas de un aprendiz
     */
    public function findByAprendiz(int $aprendizId, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT 
            bs.*,
            f.numero_ficha,
            f.nombre AS ficha_nombre,
            u.nombre AS instructor_nombre
        FROM boletas_salida bs
        INNER JOIN fichas f ON bs.ficha_id = f.id
        INNER JOIN usuarios u ON bs.instructor_id = u.id
        WHERE bs.aprendiz_id = :aprendiz_id
        ORDER BY bs.created_at DESC
        LIMIT :limit OFFSET :offset";

        $stmt = Connection::prepare($sql);
        $stmt->bindValue(':aprendiz_id', $aprendizId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contar boletas de un aprendiz
     */
    public function countByAprendiz(int $aprendizId): int
    {
        $sql = "SELECT COUNT(*) as total FROM boletas_salida WHERE aprendiz_id = :aprendiz_id";
        $stmt = Connection::prepare($sql);
        $stmt->execute([':aprendiz_id' => $aprendizId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $result['total'];
    }

    /**
     * Actualizar estado de boleta (usado para aprobaciones/rechazos de instructor/admin)
     */
    public function updateEstado(int $id, string $estado, array $extraData = []): bool
    {
        $setClauses = ['estado = :estado', 'updated_at = NOW()'];
        $params = [
            ':id' => $id,
            ':estado' => $estado,
        ];

        foreach ($extraData as $key => $value) {
            $setClauses[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }

        $sql = "UPDATE boletas_salida SET " . implode(', ', $setClauses) . " WHERE id = :id";

        $stmt = Connection::prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Registrar salida física (portero valida salida)
     */
    public function registrarSalidaFisica(int $id, int $porteroId): bool
    {
        $sql = "UPDATE boletas_salida SET 
            estado = 'validada_porteria',
            portero_validado_por = :portero_id,
            portero_validado_fecha = NOW(),
            fecha_salida_real = NOW(),
            updated_at = NOW()
        WHERE id = :id AND estado = 'aprobada'";

        $stmt = Connection::prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':portero_id' => $porteroId,
        ]);
    }

    /**
     * Registrar reingreso físico (portero valida reingreso)
     */
    public function registrarReingresoFisico(int $id, int $porteroId, string $observaciones = null): bool
    {
        $sql = "UPDATE boletas_salida SET 
            estado = 'completada',
            fecha_reingreso_real = NOW(),
            portero_observaciones = :observaciones,
            updated_at = NOW()
        WHERE id = :id 
        AND estado = 'validada_porteria' 
        AND tipo_salida = 'temporal'";

        $stmt = Connection::prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':observaciones' => $observaciones,
        ]);
    }

    /**
     * Marcar salida definitiva como completada automáticamente
     */
    public function completarSalidaDefinitiva(int $id): bool
    {
        $sql = "UPDATE boletas_salida SET 
            estado = 'completada',
            updated_at = NOW()
        WHERE id = :id 
        AND estado = 'validada_porteria' 
        AND tipo_salida = 'definitiva'";

        $stmt = Connection::prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Contar boletas pendientes por instructor
     */
    public function countPendientesByInstructor(int $instructorId): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM boletas_salida 
                WHERE instructor_id = :instructor_id 
                AND estado = 'pendiente_instructor'";
        
        $stmt = Connection::prepare($sql);
        $stmt->execute([':instructor_id' => $instructorId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $result['total'];
    }

    /**
     * Contar boletas pendientes para admin
     */
    public function countPendientesAdmin(): int
    {
        $sql = "SELECT COUNT(*) as total FROM boletas_salida WHERE estado = 'pendiente_admin'";
        $stmt = Connection::prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $result['total'];
    }

    /**
     * Contar boletas aprobadas (pendientes en portería)
     */
    public function countAprobadas(): int
    {
        $sql = "SELECT COUNT(*) as total FROM boletas_salida WHERE estado = 'aprobada'";
        $stmt = Connection::prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $result['total'];
    }

    /**
     * Contar reingresos pendientes
     */
    public function countPendientesReingreso(): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM boletas_salida 
                WHERE estado = 'validada_porteria' 
                AND tipo_salida = 'temporal' 
                AND fecha_reingreso_real IS NULL";
        
        $stmt = Connection::prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $result['total'];
    }

    /**
     * Verificar si existe boleta pendiente del aprendiz en el día
     */
    public function existeBoletaPendienteHoy(int $aprendizId): bool
    {
        $sql = "SELECT COUNT(*) as total 
                FROM boletas_salida 
                WHERE aprendiz_id = :aprendiz_id 
                AND DATE(created_at) = CURDATE()
                AND estado IN ('pendiente_instructor', 'pendiente_admin', 'aprobada', 'validada_porteria')";
        
        $stmt = Connection::prepare($sql);
        $stmt->execute([':aprendiz_id' => $aprendizId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $result['total'] > 0;
    }
}
