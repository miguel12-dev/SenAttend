<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\ReporteEquiposService;
use App\Session\SessionManager;

/**
 * Controlador para el módulo de Reporte de Ingresos/Salidas de Equipos.
 * Acceso permitido a roles: admin, administrativo, portero.
 *
 * @version 1.0
 */
class ReporteEquiposController
{
    private AuthService           $authService;
    private ReporteEquiposService $reporteService;
    private SessionManager        $session;

    private const ROLES_PERMITIDOS = ['admin', 'administrativo', 'portero'];
    private const REGISTROS_POR_PAGINA = 20;

    public function __construct(
        AuthService $authService,
        ReporteEquiposService $reporteService,
        SessionManager $session
    ) {
        $this->authService    = $authService;
        $this->reporteService = $reporteService;
        $this->session        = $session;
    }

    /**
     * Vista principal: filtros de fecha + tabla paginada.
     */
    public function index(): void
    {
        $user = $this->verificarAcceso();

        // Leer parámetros GET
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
        $fechaFin    = $_GET['fecha_fin']    ?? date('Y-m-d');
        $pagina      = max(1, (int) ($_GET['pagina'] ?? 1));

        // Validar fechas
        if (!$this->esRangoValido($fechaInicio, $fechaFin)) {
            $fechaInicio = date('Y-m-d', strtotime('-7 days'));
            $fechaFin    = date('Y-m-d');
        }

        $resultado   = $this->reporteService->getDatosPaginados(
            $fechaInicio,
            $fechaFin,
            $pagina,
            self::REGISTROS_POR_PAGINA
        );

        $csrfToken = $this->ensureCsrfToken();

        require __DIR__ . '/../../views/reportes_equipos/index.php';
    }

    /**
     * Genera y descarga el archivo Excel.
     */
    public function export(): void
    {
        $this->verificarAcceso();

        // Validar CSRF
        $this->session->start();
        $token = $_GET['_token'] ?? '';
        if (!$this->validarCsrfToken($token)) {
            http_response_code(419);
            echo 'Token inválido. Recarga la página.';
            return;
        }

        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
        $fechaFin    = $_GET['fecha_fin']    ?? date('Y-m-d');

        if (!$this->esRangoValido($fechaInicio, $fechaFin)) {
            http_response_code(400);
            echo 'Rango de fechas inválido.';
            return;
        }

        try {
            $filePath = $this->reporteService->generarExcel($fechaInicio, $fechaFin);
            $fileName = basename($filePath);

            if (ob_get_level()) {
                ob_end_clean();
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            readfile($filePath);
            exit;
        } catch (\Exception $e) {
            error_log('ReporteEquiposController::export - ' . $e->getMessage());
            http_response_code(500);
            echo 'Error al generar el reporte. Intenta nuevamente.';
        }
    }

    // ─── Privados ─────────────────────────────────────────────────────────────

    /** Verifica acceso, redirige si no autorizado. Retorna el usuario. */
    private function verificarAcceso(): array
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !in_array($user['rol'], self::ROLES_PERMITIDOS)) {
            header('Location: /dashboard');
            exit;
        }

        return $user;
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
