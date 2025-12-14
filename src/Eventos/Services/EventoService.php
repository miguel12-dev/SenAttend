<?php

namespace App\Eventos\Services;

use App\Eventos\Repositories\EventoRepository;
use App\Eventos\Repositories\EventoParticipanteRepository;
use App\Database\Connection;
use Exception;

/**
 * Servicio principal para gestión de eventos
 * Principio de Responsabilidad Única: Lógica de negocio de eventos
 */
class EventoService
{
    private EventoRepository $eventoRepository;
    private EventoParticipanteRepository $participanteRepository;

    public function __construct(
        EventoRepository $eventoRepository,
        EventoParticipanteRepository $participanteRepository
    ) {
        $this->eventoRepository = $eventoRepository;
        $this->participanteRepository = $participanteRepository;
    }

    /**
     * Crea un nuevo evento
     */
    public function crearEvento(array $data): array
    {
        $errors = $this->validarEvento($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $eventoId = $this->eventoRepository->create($data);
            $evento = $this->eventoRepository->findById($eventoId);

            return [
                'success' => true,
                'message' => 'Evento creado exitosamente',
                'data' => $evento
            ];
        } catch (Exception $e) {
            error_log('EventoService::crearEvento error: ' . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Error al crear el evento: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Actualiza un evento existente
     */
    public function actualizarEvento(int $id, array $data): array
    {
        $evento = $this->eventoRepository->findById($id);
        if (!$evento) {
            return ['success' => false, 'errors' => ['Evento no encontrado']];
        }

        $errors = $this->validarEvento($data, true);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $this->eventoRepository->update($id, $data);
            $eventoActualizado = $this->eventoRepository->findById($id);

            return [
                'success' => true,
                'message' => 'Evento actualizado exitosamente',
                'data' => $eventoActualizado
            ];
        } catch (Exception $e) {
            error_log('EventoService::actualizarEvento error: ' . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Error al actualizar el evento: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Elimina un evento
     */
    public function eliminarEvento(int $id): array
    {
        $evento = $this->eventoRepository->findById($id);
        if (!$evento) {
            return ['success' => false, 'errors' => ['Evento no encontrado']];
        }

        try {
            $this->eventoRepository->delete($id);
            return [
                'success' => true,
                'message' => 'Evento eliminado exitosamente'
            ];
        } catch (Exception $e) {
            error_log('EventoService::eliminarEvento error: ' . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Error al eliminar el evento: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Obtiene un evento por su ID con estadísticas
     */
    public function obtenerEvento(int $id): ?array
    {
        $evento = $this->eventoRepository->findById($id);
        if (!$evento) {
            return null;
        }

        $evento['estadisticas'] = $this->eventoRepository->getEstadisticas($id);
        
        return $evento;
    }

    /**
     * Obtiene todos los eventos con filtros opcionales
     */
    public function listarEventos(array $filters = []): array
    {
        return $this->eventoRepository->findAll($filters);
    }

    /**
     * Obtiene eventos públicos disponibles
     */
    public function obtenerEventosPublicos(): array
    {
        // Primero actualizar estados automáticos
        $this->eventoRepository->actualizarEstadosAutomaticos();
        
        return $this->eventoRepository->findPublicosDisponibles();
    }

    /**
     * Cambia el estado de un evento
     */
    public function cambiarEstado(int $id, string $estado): array
    {
        $estadosValidos = ['programado', 'en_curso', 'finalizado', 'cancelado'];
        if (!in_array($estado, $estadosValidos)) {
            return ['success' => false, 'errors' => ['Estado inválido']];
        }

        $evento = $this->eventoRepository->findById($id);
        if (!$evento) {
            return ['success' => false, 'errors' => ['Evento no encontrado']];
        }

        try {
            // Si se finaliza el evento, marcar ausentes y sin salida
            if ($estado === 'finalizado') {
                $this->participanteRepository->marcarAusentesEvento($id);
                $this->participanteRepository->marcarSinSalidaEvento($id);
            }

            $this->eventoRepository->update($id, ['estado' => $estado]);

            return [
                'success' => true,
                'message' => 'Estado del evento actualizado'
            ];
        } catch (Exception $e) {
            error_log('EventoService::cambiarEstado error: ' . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Error al cambiar el estado: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Sube la imagen del evento
     */
    public function subirImagen(array $file): array
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'error' => 'Tipo de archivo no permitido'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'El archivo es demasiado grande (máx 5MB)'];
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'evento_' . uniqid() . '.' . $extension;
        $uploadDir = PUBLIC_PATH . '/uploads/eventos/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => true,
                'url' => '/uploads/eventos/' . $filename
            ];
        }

        return ['success' => false, 'error' => 'Error al subir la imagen'];
    }

    /**
     * Valida los datos del evento
     */
    private function validarEvento(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        if (!$isUpdate || isset($data['titulo'])) {
            if (empty($data['titulo'])) {
                $errors[] = 'El título es obligatorio';
            } elseif (strlen($data['titulo']) > 200) {
                $errors[] = 'El título no puede exceder 200 caracteres';
            }
        }

        if (!$isUpdate || isset($data['fecha_inicio'])) {
            if (empty($data['fecha_inicio'])) {
                $errors[] = 'La fecha de inicio es obligatoria';
            }
        }

        if (!$isUpdate || isset($data['fecha_fin'])) {
            if (empty($data['fecha_fin'])) {
                $errors[] = 'La fecha de fin es obligatoria';
            }
        }

        if (!empty($data['fecha_inicio']) && !empty($data['fecha_fin'])) {
            $inicio = strtotime($data['fecha_inicio']);
            $fin = strtotime($data['fecha_fin']);
            
            if ($fin <= $inicio) {
                $errors[] = 'La fecha de fin debe ser posterior a la fecha de inicio';
            }
        }

        return $errors;
    }
}

