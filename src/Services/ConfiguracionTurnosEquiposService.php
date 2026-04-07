<?php

namespace App\Services;

use App\Repositories\ConfiguracionTurnosEquiposRepository;
use Exception;

/**
 * Servicio de negocio para la gestión de turnos/horarios de equipos.
 * Valida datos y delega persistencia al Repository.
 *
 * @version 1.0
 */
class ConfiguracionTurnosEquiposService
{
    private ConfiguracionTurnosEquiposRepository $repo;

    public function __construct(ConfiguracionTurnosEquiposRepository $repo)
    {
        $this->repo = $repo;
    }

    /** Devuelve los tres turnos globales. */
    public function obtenerHorariosGlobales(): array
    {
        return $this->repo->obtenerHorariosGlobales();
    }

    /** Devuelve las excepciones de fecha agrupadas por fecha. */
    public function obtenerExcepcionesAgrupadas(): array
    {
        $filas = $this->repo->obtenerExcepcionesFechas();
        $agrupado = [];
        foreach ($filas as $fila) {
            $agrupado[$fila['fecha_especifica']][] = $fila;
        }
        return $agrupado;
    }

    /**
     * Actualiza los tres turnos globales.
     *
     * @param array $datos [['turno'=>'Mañana','inicio'=>'05:30','fin'=>'11:00'], ...]
     */
    public function actualizarHorariosGlobales(array $datos): array
    {
        foreach ($datos as $turno) {
            $validacion = $this->validarHora($turno['inicio'], $turno['fin']);
            if (!$validacion['ok']) {
                return ['success' => false, 'message' => "Turno {$turno['turno']}: " . $validacion['error']];
            }
        }

        foreach ($datos as $turno) {
            $this->repo->actualizarHorarioGlobal(
                $turno['turno'],
                $this->normalizar($turno['inicio']),
                $this->normalizar($turno['fin']),
                $turno['descripcion'] ?? "Horario global {$turno['turno']}"
            );
        }

        return ['success' => true, 'message' => 'Horarios globales actualizados correctamente.'];
    }

    /**
     * Agrega o reemplaza un horario para una fecha específica.
     */
    public function agregarExcepcionFecha(
        string $turno,
        string $inicio,
        string $fin,
        string $fecha,
        string $descripcion = ''
    ): array {
        if (!in_array($turno, ['Mañana', 'Tarde', 'Noche'], true)) {
            return ['success' => false, 'message' => 'Turno inválido.'];
        }

        if (!$this->esDateValida($fecha)) {
            return ['success' => false, 'message' => 'Fecha inválida. Use el formato YYYY-MM-DD.'];
        }

        $validacion = $this->validarHora($inicio, $fin);
        if (!$validacion['ok']) {
            return ['success' => false, 'message' => $validacion['error']];
        }

        $ok = $this->repo->upsertExcepcionFecha(
            $turno,
            $this->normalizar($inicio),
            $this->normalizar($fin),
            $fecha,
            $descripcion
        );

        return $ok
            ? ['success' => true, 'message' => "Excepción registrada para el {$fecha}."]
            : ['success' => false, 'message' => 'Error al guardar la excepción en la base de datos.'];
    }

    /**
     * Elimina una excepción de fecha por ID.
     */
    public function eliminarExcepcion(int $id): array
    {
        $ok = $this->repo->eliminarExcepcion($id);
        return $ok
            ? ['success' => true, 'message' => 'Excepción eliminada.']
            : ['success' => false, 'message' => 'No se encontró el registro o no se pudo eliminar.'];
    }

    // ─── Utilidades privadas ─────────────────────────────────────────────────

    private function validarHora(string $inicio, string $fin): array
    {
        if (empty($inicio) || empty($fin)) {
            return ['ok' => false, 'error' => 'Las horas de inicio y fin son obligatorias.'];
        }
        if ($inicio >= $fin) {
            return ['ok' => false, 'error' => 'La hora de inicio debe ser anterior a la hora de fin.'];
        }
        return ['ok' => true];
    }

    private function normalizar(string $hora): string
    {
        // Convierte HH:MM a HH:MM:SS
        return strlen($hora) === 5 ? $hora . ':00' : $hora;
    }

    private function esDateValida(string $fecha): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
}
