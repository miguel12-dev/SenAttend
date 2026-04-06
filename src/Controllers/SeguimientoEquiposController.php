<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\GestionEquipos\Services\SeguimientoEquiposService;
use App\Session\SessionManager;
use App\Support\Response;

/**
 * Controlador para el seguimiento de infracciones por salidas no registradas de equipos.
 */
class SeguimientoEquiposController
{
    private AuthService $authService;
    private SeguimientoEquiposService $seguimientoService;
    private SessionManager $session;

    private const ROLES_PERMITIDOS = ['admin', 'administrativo'];

    public function __construct(
        AuthService $authService,
        SeguimientoEquiposService $seguimientoService,
        SessionManager $session
    ) {
        $this->authService = $authService;
        $this->seguimientoService = $seguimientoService;
        $this->session = $session;
    }

    /**
     * Muestra la vista principal con infractores.
     */
    public function index(): void
    {
        $user = $this->verificarAcceso();

        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin    = $_GET['fecha_fin']    ?? date('Y-m-t');

        if (!$this->esRangoValido($fechaInicio, $fechaFin)) {
            $fechaInicio = date('Y-m-01');
            $fechaFin    = date('Y-m-t');
        }

        $infractores = $this->seguimientoService->obtenerAprendicesInfractores($fechaInicio, $fechaFin);

        $csrfToken = $this->ensureCsrfToken();

        require __DIR__ . '/../../views/admin/seguimiento_equipos/index.php';
    }

    /**
     * Endpoint API para procesar cierres automáticos pendientes.
     */
    public function procesarCierres(): void
    {
        error_log('Debug - Entrando a procesarCierres');
        $this->verificarAccesoJson();

        $this->session->start();
        $data = json_decode(file_get_contents('php://input'), true);
        $token = $data['_token'] ?? '';

        if (!$this->validarCsrfToken($token)) {
            Response::json(['success' => false, 'error' => 'Token CSRF inválido'], 403);
            return;
        }

        try {
            $cerrados = $this->seguimientoService->procesarCierresAutomaticos();
            Response::json([
                'success' => true,
                'message' => "Proceso completado. Se cerraron automáticamente $cerrados registros sin salida."
            ]);
        } catch (\Exception $e) {
            error_log('SeguimientoEquiposController::procesarCierres - ' . $e->getMessage());
            Response::json(['success' => false, 'error' => 'Error al procesar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Exporta el reporte de infracciones en Excel.
     */
    public function export(): void
    {
        $this->verificarAcceso();

        $this->session->start();
        $token = $_GET['_token'] ?? '';
        if (!$this->validarCsrfToken($token)) {
            http_response_code(403);
            echo 'Token CSRF inválido. Recarga la página.';
            return;
        }

        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin    = $_GET['fecha_fin']    ?? date('Y-m-t');

        if (!$this->esRangoValido($fechaInicio, $fechaFin)) {
            http_response_code(400);
            echo 'Rango de fechas inválido.';
            return;
        }

        try {
            $filePath = $this->seguimientoService->generarExcelInfractores($fechaInicio, $fechaFin);
            $fileName = basename($filePath);

            if (ob_get_level()) ob_end_clean();

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            readfile($filePath);
            exit;
        } catch (\Exception $e) {
            error_log('SeguimientoEquiposController::export - ' . $e->getMessage());
            http_response_code(500);
            echo 'Error generando el reporte Excel. Intente nuevamente.';
        }
    }

    // --- Métodos privados ---

    private function verificarAcceso(): array
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !in_array($user['rol'], self::ROLES_PERMITIDOS)) {
            header('Location: /dashboard');
            exit;
        }

        return $user;
    }

    private function verificarAccesoJson(): void
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !in_array($user['rol'], self::ROLES_PERMITIDOS)) {
            Response::json(['success' => false, 'error' => 'No autorizado'], 401);
            exit;
        }
    }

    private function esRangoValido(string $inicio, string $fin): bool
    {
        $re = '/^\d{4}-\d{2}-\d{2}$/';
        return preg_match($re, $inicio) && preg_match($re, $fin) && $inicio <= $fin;
    }

    private function ensureCsrfToken(): string
    {
        $this->session->start();
        $token = $this->session->get('_csrf_token');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $this->session->set('_csrf_token', $token);
        }
        return $token;
    }

    private function validarCsrfToken(string $token): bool
    {
        $this->session->start();
        $stored = $this->session->get('_csrf_token');
        return is_string($stored) && hash_equals($stored, $token);
    }
}
