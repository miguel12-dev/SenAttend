<?php

namespace App\GestionEquipos\Services;

use App\GestionEquipos\Repositories\AprendizEquipoRepository;

class AprendizEquipoService
{
    private AprendizEquipoRepository $aprendizEquipoRepository;

    public function __construct(AprendizEquipoRepository $aprendizEquipoRepository)
    {
        $this->aprendizEquipoRepository = $aprendizEquipoRepository;
    }

    /**
     * Devuelve los equipos del aprendiz en una estructura directa para la vista.
     */
    public function getEquiposDeAprendiz(int $aprendizId): array
    {
        return $this->aprendizEquipoRepository->findEquiposByAprendiz($aprendizId);
    }

    /**
     * Devuelve los equipos eliminados previamente por el aprendiz.
     */
    public function getEquiposEliminados(int $aprendizId): array
    {
        return $this->aprendizEquipoRepository->findEquiposEliminados($aprendizId);
    }

    /**
     * Realiza soft-delete de un equipo del aprendiz.
     */
    public function eliminarEquipo(int $relacionId, int $aprendizId): array
    {
        // Verificar que la relación exista y pertenezca al aprendiz
        $relacion = $this->aprendizEquipoRepository->findByIdAndAprendiz($relacionId, $aprendizId);
        
        if (!$relacion) {
            return [
                'success' => false,
                'message' => 'Equipo no encontrado o no te pertenece.'
            ];
        }

        if ($relacion['eliminado']) {
            return [
                'success' => false,
                'message' => 'El equipo ya fue eliminado anteriormente.'
            ];
        }

        $deleted = $this->aprendizEquipoRepository->softDelete($relacionId);

        if ($deleted) {
            return [
                'success' => true,
                'message' => 'Equipo eliminado correctamente. Podrás agregarlo nuevamente cuando lo desees.',
                'equipo' => [
                    'relacion_id' => $relacionId,
                    'equipo_id' => $relacion['equipo_id'],
                    'marca' => $relacion['marca'],
                    'numero_serial' => $relacion['numero_serial']
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al eliminar el equipo. Intenta nuevamente.'
        ];
    }

    /**
     * Restaura un equipo previamente eliminado (soft-delete).
     */
    public function restaurarEquipo(int $relacionId, int $aprendizId): array
    {
        // Verificar que la relación exista y pertenezca al aprendiz
        $relacion = $this->aprendizEquipoRepository->findByIdAndAprendiz($relacionId, $aprendizId);
        
        if (!$relacion) {
            return [
                'success' => false,
                'message' => 'Equipo no encontrado o no te pertenece.'
            ];
        }

        if (!$relacion['eliminado']) {
            return [
                'success' => false,
                'message' => 'El equipo no está eliminado, no es necesario restaurarlo.'
            ];
        }

        // Verificar si ya existe una relación activa con este equipo
        if ($this->aprendizEquipoRepository->hasActiveRelacion($aprendizId, $relacion['equipo_id'])) {
            return [
                'success' => false,
                'message' => 'Ya tienes una relación activa con este equipo.'
            ];
        }

        $restored = $this->aprendizEquipoRepository->restore($relacionId);

        if ($restored) {
            return [
                'success' => true,
                'message' => 'Equipo restaurado correctamente. Puedes usarlo nuevamente.',
                'equipo' => [
                    'relacion_id' => $relacionId,
                    'equipo_id' => $relacion['equipo_id'],
                    'marca' => $relacion['marca'],
                    'numero_serial' => $relacion['numero_serial']
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al restaurar el equipo. Intenta nuevamente.'
        ];
    }
}


