<?php

namespace App\Eventos\Repositories;

use App\Database\Connection;
use PDO;

/**
 * Repositorio para gestión de códigos QR de eventos
 * Principio de Responsabilidad Única: Solo maneja operaciones de BD para QR
 */
class EventoQRRepository
{
    /**
     * Busca un QR por su token
     */
    public function findByToken(string $token): ?array
    {
        $stmt = Connection::prepare(
            "SELECT eq.*, ep.documento, ep.nombre, ep.apellido, ep.email, ep.evento_id,
                    e.titulo as evento_titulo, e.estado as evento_estado
             FROM eventos_qr eq
             JOIN eventos_participantes ep ON eq.participante_id = ep.id
             JOIN eventos e ON ep.evento_id = e.id
             WHERE eq.token = :token"
        );
        $stmt->execute(['token' => $token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Busca QR activo por participante y tipo
     */
    public function findActiveByParticipanteAndTipo(int $participanteId, string $tipo): ?array
    {
        $stmt = Connection::prepare(
            "SELECT * FROM eventos_qr 
             WHERE participante_id = :participante_id 
             AND tipo = :tipo 
             AND usado = FALSE
             ORDER BY fecha_generacion DESC
             LIMIT 1"
        );
        $stmt->execute([
            'participante_id' => $participanteId,
            'tipo' => $tipo
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Crea un nuevo registro de QR
     */
    public function create(array $data): int
    {
        $stmt = Connection::prepare(
            "INSERT INTO eventos_qr (participante_id, token, tipo, qr_data, usado)
             VALUES (:participante_id, :token, :tipo, :qr_data, :usado)"
        );
        
        $stmt->execute([
            'participante_id' => $data['participante_id'],
            'token' => $data['token'],
            'tipo' => $data['tipo'],
            'qr_data' => $data['qr_data'],
            'usado' => $data['usado'] ?? false
        ]);
        
        return (int) Connection::lastInsertId();
    }

    /**
     * Marca un QR como usado
     */
    public function marcarUsado(int $id): bool
    {
        $stmt = Connection::prepare(
            "UPDATE eventos_qr 
             SET usado = TRUE, fecha_uso = NOW() 
             WHERE id = :id"
        );
        
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Invalida todos los QR anteriores de un participante para un tipo específico
     */
    public function invalidarAnteriores(int $participanteId, string $tipo): int
    {
        $stmt = Connection::prepare(
            "UPDATE eventos_qr 
             SET usado = TRUE 
             WHERE participante_id = :participante_id 
             AND tipo = :tipo 
             AND usado = FALSE"
        );
        $stmt->execute([
            'participante_id' => $participanteId,
            'tipo' => $tipo
        ]);
        
        return $stmt->rowCount();
    }

    /**
     * Obtiene historial de QR de un participante
     */
    public function findByParticipante(int $participanteId): array
    {
        $stmt = Connection::prepare(
            "SELECT * FROM eventos_qr 
             WHERE participante_id = :participante_id 
             ORDER BY fecha_generacion DESC"
        );
        $stmt->execute(['participante_id' => $participanteId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Elimina QRs de un participante
     */
    public function deleteByParticipante(int $participanteId): bool
    {
        $stmt = Connection::prepare(
            "DELETE FROM eventos_qr WHERE participante_id = :participante_id"
        );
        
        return $stmt->execute(['participante_id' => $participanteId]);
    }
}

