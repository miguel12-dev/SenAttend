<?php

namespace App\Eventos\Repositories;

use App\Database\Connection;
use PDO;

/**
 * Repositorio para gestión de participantes de eventos
 * Principio de Responsabilidad Única: Solo maneja operaciones de BD para participantes
 */
class EventoParticipanteRepository
{
    /**
     * Busca un participante por su ID
     */
    public function findById(int $id): ?array
    {
        $stmt = Connection::prepare(
            "SELECT ep.*, e.titulo as evento_titulo, e.fecha_inicio, e.fecha_fin
             FROM eventos_participantes ep
             JOIN eventos e ON ep.evento_id = e.id
             WHERE ep.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Busca un participante por documento y evento
     */
    public function findByDocumentoAndEvento(string $documento, int $eventoId): ?array
    {
        $stmt = Connection::prepare(
            "SELECT * FROM eventos_participantes 
             WHERE documento = :documento AND evento_id = :evento_id"
        );
        $stmt->execute([
            'documento' => $documento,
            'evento_id' => $eventoId
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Obtiene todos los participantes de un evento
     */
    public function findByEvento(int $eventoId, array $filters = []): array
    {
        $sql = "SELECT * FROM eventos_participantes WHERE evento_id = :evento_id";
        $params = ['evento_id' => $eventoId];
        
        if (!empty($filters['estado'])) {
            $sql .= " AND estado = :estado";
            $params['estado'] = $filters['estado'];
        }
        
        if (!empty($filters['tipo'])) {
            $sql .= " AND tipo = :tipo";
            $params['tipo'] = $filters['tipo'];
        }
        
        $sql .= " ORDER BY fecha_registro DESC";
        
        $stmt = Connection::prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra un nuevo participante
     */
    public function create(array $data): int
    {
        $stmt = Connection::prepare(
            "INSERT INTO eventos_participantes 
                (evento_id, documento, nombre, apellido, email, tipo, estado)
             VALUES 
                (:evento_id, :documento, :nombre, :apellido, :email, :tipo, :estado)"
        );
        
        $stmt->execute([
            'evento_id' => $data['evento_id'],
            'documento' => $data['documento'],
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'tipo' => $data['tipo'] ?? 'instructor',
            'estado' => $data['estado'] ?? 'registrado'
        ]);
        
        return (int) Connection::lastInsertId();
    }

    /**
     * Actualiza un participante
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['estado', 'fecha_ingreso', 'fecha_salida'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE eventos_participantes SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = Connection::prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * Registra el ingreso de un participante
     */
    public function registrarIngreso(int $id): bool
    {
        $stmt = Connection::prepare(
            "UPDATE eventos_participantes 
             SET estado = 'ingreso', fecha_ingreso = NOW() 
             WHERE id = :id AND estado = 'registrado'"
        );
        
        return $stmt->execute(['id' => $id]) && $stmt->rowCount() > 0;
    }

    /**
     * Registra la salida de un participante
     */
    public function registrarSalida(int $id): bool
    {
        $stmt = Connection::prepare(
            "UPDATE eventos_participantes 
             SET estado = 'salida', fecha_salida = NOW() 
             WHERE id = :id AND estado = 'ingreso'"
        );
        
        return $stmt->execute(['id' => $id]) && $stmt->rowCount() > 0;
    }

    /**
     * Marca como ausentes a los participantes que no ingresaron al evento finalizado
     */
    public function marcarAusentesEvento(int $eventoId): int
    {
        $stmt = Connection::prepare(
            "UPDATE eventos_participantes 
             SET estado = 'ausente' 
             WHERE evento_id = :evento_id AND estado = 'registrado'"
        );
        $stmt->execute(['evento_id' => $eventoId]);
        
        return $stmt->rowCount();
    }

    /**
     * Marca como sin_salida a los participantes que ingresaron pero no salieron
     */
    public function marcarSinSalidaEvento(int $eventoId): int
    {
        $stmt = Connection::prepare(
            "UPDATE eventos_participantes 
             SET estado = 'sin_salida' 
             WHERE evento_id = :evento_id AND estado = 'ingreso'"
        );
        $stmt->execute(['evento_id' => $eventoId]);
        
        return $stmt->rowCount();
    }

    /**
     * Cuenta participantes por estado en un evento
     */
    public function countByEstado(int $eventoId): array
    {
        $stmt = Connection::prepare(
            "SELECT estado, COUNT(*) as cantidad 
             FROM eventos_participantes 
             WHERE evento_id = :evento_id 
             GROUP BY estado"
        );
        $stmt->execute(['evento_id' => $eventoId]);
        
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['estado']] = (int) $row['cantidad'];
        }
        
        return $result;
    }

    /**
     * Obtiene el historial de participantes con ingreso o salida del día actual
     */
    public function getHistorialHoy(): array
    {
        $stmt = Connection::prepare(
            "SELECT * FROM eventos_participantes 
             WHERE (DATE(fecha_ingreso) = CURDATE() OR DATE(fecha_salida) = CURDATE())
             AND estado IN ('ingreso', 'salida', 'sin_salida')
             ORDER BY 
                GREATEST(
                    COALESCE(fecha_ingreso, '1900-01-01'), 
                    COALESCE(fecha_salida, '1900-01-01')
                ) DESC"
        );
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Elimina un participante
     */
    public function delete(int $id): bool
    {
        $stmt = Connection::prepare("DELETE FROM eventos_participantes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

