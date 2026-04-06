<?php

namespace App\Controllers;

use App\Services\ConfiguracionTurnosEquiposService;
use App\Services\AuthService;
use App\Support\Response;
use Exception;

/**
 * Controlador para gestión de horarios de turnos de equipos.
 * Solo accesible para el rol Admin.
 *
 * Rutas:
 *   GET  /configuracion/turnos-equipos
 *   POST /configuracion/turnos-equipos/actualizar-globales
 *   POST /configuracion/turnos-equipos/agregar-fecha
 *   POST /configuracion/turnos-equipos/eliminar-fecha
 *
 * @version 1.0
 */
class ConfiguracionTurnosEquiposController
{
    private ConfiguracionTurnosEquiposService $service;
    private AuthService $authService;

    public function __construct(
        ConfiguracionTurnosEquiposService $service,
        AuthService $authService
    ) {
        $this->service     = $service;
        $this->authService = $authService;
    }

    // ─── GET ─────────────────────────────────────────────────────────────────

    /**
     * Vista principal de gestión de horarios de equipos.
     * GET /configuracion/turnos-equipos
     */
    public function index(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            $this->soloAdmin($user);

            $turnosGlobales     = $this->service->obtenerHorariosGlobales();
            $excepcionesAgrupadas = $this->service->obtenerExcepcionesAgrupadas();

            $this->headersSeguridad();
            require __DIR__ . '/../../views/configuracion/turnos-equipos.php';

        } catch (Exception $e) {
            error_log('ConfiguracionTurnosEquiposController::index - ' . $e->getMessage());
            $_SESSION['errors'] = ['Error interno del sistema.'];
            Response::redirect('/dashboard');
        }
    }

    // ─── POST ────────────────────────────────────────────────────────────────

    /**
     * Actualiza los tres turnos globales.
     * POST /configuracion/turnos-equipos/actualizar-globales
     */
    public function actualizarGlobales(): void
    {
        try {
            $this->soloPost();
            $user = $this->authService->getCurrentUser();
            $this->soloAdmin($user);

            $datos = [];
            foreach (['Mañana', 'Tarde', 'Noche'] as $nombre) {
                $clave    = strtolower(str_replace('ñ', 'n', $nombre)); // mañana->manana
                $inicio   = $this->post($clave . '_inicio');
                $fin      = $this->post($clave . '_fin');
                $datos[]  = [
                    'turno'       => $nombre,
                    'inicio'      => $inicio,
                    'fin'         => $fin,
                    'descripcion' => "Horario global {$nombre}",
                ];
            }

            $resultado = $this->service->actualizarHorariosGlobales($datos);
            $this->flashResult($resultado);
            Response::redirect('/configuracion/turnos-equipos');

        } catch (Exception $e) {
            error_log('ConfiguracionTurnosEquiposController::actualizarGlobales - ' . $e->getMessage());
            $_SESSION['errors'] = ['Error interno del sistema.'];
            Response::redirect('/configuracion/turnos-equipos');
        }
    }

    /**
     * Agrega una excepción de fecha.
     * POST /configuracion/turnos-equipos/agregar-fecha
     */
    public function agregarFecha(): void
    {
        try {
            $this->soloPost();
            $user = $this->authService->getCurrentUser();
            $this->soloAdmin($user);

            $turno       = $this->post('exc_turno');
            $inicio      = $this->post('exc_inicio');
            $fin         = $this->post('exc_fin');
            $fecha       = $this->post('exc_fecha');
            $descripcion = $this->post('exc_descripcion');

            $resultado = $this->service->agregarExcepcionFecha($turno, $inicio, $fin, $fecha, $descripcion);
            $this->flashResult($resultado);
            Response::redirect('/configuracion/turnos-equipos');

        } catch (Exception $e) {
            error_log('ConfiguracionTurnosEquiposController::agregarFecha - ' . $e->getMessage());
            $_SESSION['errors'] = ['Error interno del sistema.'];
            Response::redirect('/configuracion/turnos-equipos');
        }
    }

    /**
     * Elimina una excepción de fecha.
     * POST /configuracion/turnos-equipos/eliminar-fecha
     */
    public function eliminarFecha(): void
    {
        try {
            $this->soloPost();
            $user = $this->authService->getCurrentUser();
            $this->soloAdmin($user);

            $id = (int) filter_input(INPUT_POST, 'exc_id', FILTER_SANITIZE_NUMBER_INT);
            if ($id <= 0) {
                $_SESSION['errors'] = ['ID de excepción inválido.'];
                Response::redirect('/configuracion/turnos-equipos');
                return;
            }

            $resultado = $this->service->eliminarExcepcion($id);
            $this->flashResult($resultado);
            Response::redirect('/configuracion/turnos-equipos');

        } catch (Exception $e) {
            error_log('ConfiguracionTurnosEquiposController::eliminarFecha - ' . $e->getMessage());
            $_SESSION['errors'] = ['Error interno del sistema.'];
            Response::redirect('/configuracion/turnos-equipos');
        }
    }

    // ─── Utilidades privadas ─────────────────────────────────────────────────

    private function soloAdmin(array $user): void
    {
        if ($user['rol'] !== 'admin') {
            $_SESSION['errors'] = ['Solo los administradores pueden acceder a esta sección.'];
            Response::redirect('/dashboard');
            exit;
        }
    }

    private function soloPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/configuracion/turnos-equipos');
            exit;
        }
    }

    private function post(string $campo): string
    {
        return trim((string) filter_input(INPUT_POST, $campo, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    }

    private function flashResult(array $resultado): void
    {
        if ($resultado['success']) {
            $_SESSION['success'] = $resultado['message'];
        } else {
            $_SESSION['errors'] = [$resultado['message']];
        }
    }

    private function headersSeguridad(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
    }
}
