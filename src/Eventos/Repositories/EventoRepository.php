<?php

namespace App\Eventos\Repositories;

use App\Database\Connection;
use PDO;

/**
 * Repositorio para gestión de eventos
 * Principio de Responsabilidad Única: Solo maneja operaciones de BD para eventos
 */
class EventoRepository
{
    /**
     * Busca un evento por su ID
     */
    public function findById(int $id): ?array
    {
        $stmt = Connection::prepare(
            "SELECT e.*, eu.nombre as creador_nombre 
             FROM eventos e
             LEFT JOIN eventos_usuarios eu ON e.creado_por = eu.id
             WHERE e.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Obtiene todos los eventos
     */
    public function findAll(array $filters = []): array
    {
        $sql = "SELECT e.*, eu.nombre as creador_nombre,
                (SELECT COUNT(*) FROM eventos_participantes WHERE evento_id = e.id) as total_participantes
                FROM eventos e
                LEFT JOIN eventos_usuarios eu ON e.creado_por = eu.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['estado'])) {
            $sql .= " AND e.estado = :estado";
            $params['estado'] = $filters['estado'];
        }
        
        if (!empty($filters['tipo_participantes'])) {
            $sql .= " AND e.tipo_participantes = :tipo_participantes";
            $params['tipo_participantes'] = $filters['tipo_participantes'];
        }
        
        $sql .= " ORDER BY e.fecha_inicio DESC";
        
        $stmt = Connection::prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene eventos públicos disponibles (programados o en curso)
     */
    public function findPublicosDisponibles(): array
    {
        $stmt = Connection::prepare(
            "SELECT e.*, 
                (SELECT COUNT(*) FROM eventos_participantes WHERE evento_id = e.id) as total_participantes
             FROM eventos e
             WHERE e.estado IN ('programado', 'en_curso')
             AND e.fecha_fin >= NOW()
             ORDER BY e.fecha_inicio ASC"
        );
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo evento
     */
    public function create(array $data): int
    {
        $stmt = Connection::prepare(
            "INSERT INTO eventos (titulo, descripcion, imagen_url, fecha_inicio, fecha_fin, 
                                  tipo_participantes, estado, creado_por)
             VALUES (:titulo, :descripcion, :imagen_url, :fecha_inicio, :fecha_fin,
                     :tipo_participantes, :estado, :creado_por)"
        );
        
        $stmt->execute([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'imagen_url' => $data['imagen_url'] ?? null,
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'tipo_participantes' => $data['tipo_participantes'] ?? 'instructores',
            'estado' => $data['estado'] ?? 'programado',
            'creado_por' => $data['creado_por'] ?? null
        ]);
        
        return (int) Connection::lastInsertId();
    }

    /**
     * Actualiza un evento existente
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['titulo', 'descripcion', 'imagen_url', 'fecha_inicio', 
                          'fecha_fin', 'tipo_participantes', 'estado'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE eventos SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = Connection::prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * Elimina un evento
     */
    public function delete(int $id): bool
    {
        $stmt = Connection::prepare("DELETE FROM eventos WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Actualiza el estado de eventos según las fechas
     */
    public function actualizarEstadosAutomaticos(): int
    {
        // Marcar como en_curso los eventos que ya iniciaron
        $stmt1 = Connection::prepare(
            "UPDATE eventos 
             SET estado = 'en_curso' 
             WHERE estado = 'programado' 
             AND fecha_inicio <= NOW() 
             AND fecha_fin > NOW()"
        );
        $stmt1->execute();
        $updated = $stmt1->rowCount();
        
        // Marcar como finalizado los eventos que ya terminaron
        $stmt2 = Connection::prepare(
            "UPDATE eventos 
             SET estado = 'finalizado' 
             WHERE estado IN ('programado', 'en_curso') 
             AND fecha_fin <= NOW()"
        );
        $stmt2->execute();
        $updated += $stmt2->rowCount();
        
        return $updated;
    }

    /**
     * Obtiene estadísticas de un evento
     */
    public function getEstadisticas(int $eventoId): array
    {
        $stmt = Connection::prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'registrado' THEN 1 ELSE 0 END) as registrados,
                SUM(CASE WHEN estado = 'ingreso' THEN 1 ELSE 0 END) as ingresados,
                SUM(CASE WHEN estado = 'salida' THEN 1 ELSE 0 END) as finalizados,
                SUM(CASE WHEN estado = 'ausente' THEN 1 ELSE 0 END) as ausentes,
                SUM(CASE WHEN estado = 'sin_salida' THEN 1 ELSE 0 END) as sin_salida
             FROM eventos_participantes
             WHERE evento_id = :evento_id"
        );
        $stmt->execute(['evento_id' => $eventoId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

