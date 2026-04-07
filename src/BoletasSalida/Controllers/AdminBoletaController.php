<?php

namespace App\BoletasSalida\Controllers;

use App\BoletasSalida\Services\BoletaSalidaService;
use App\BoletasSalida\Repositories\BoletaSalidaRepository;
use App\Services\AuthService;
use App\Support\Response;
use App\Support\CacheHeaders;

/**
 * Controlador para gestión de boletas de salida (rol admin/administrativo)
 */
class AdminBoletaController
{
    private BoletaSalidaService $boletaService;
    private BoletaSalidaRepository $boletaRepository;
    private AuthService $authService;

    public function __construct(
        BoletaSalidaService $boletaService,
        BoletaSalidaRepository $boletaRepository,
        AuthService $authService
    ) {
        $this->boletaService = $boletaService;
        $this->boletaRepository = $boletaRepository;
        $this->authService = $authService;
    }

    /**
     * Vista principal: solicitudes pendientes de aprobación
     * GET /admin/boletas-salida
     */
    public function index(): void
    {
        $user = $this->authService->getCurrentUser();

        if (!$user) {
            Response::redirect('/login');
            return;
        }

        // Evitar caché de la página
        CacheHeaders::noCache();

        $boletasPendientes = $this->boletaRepository->findPendientesAdmin();
        $contadores = $this->boletaService->getContadores($user['id'], 'admin');

        require __DIR__ . '/../../../views/admin/boletas-salida/index.php';
    }

    /**
     * Vista de historial completo del sistema
     * GET /admin/boletas-salida/historial
     */
    public function historial(): void
    {
        $user = $this->authService->getCurrentUser();

        if (!$user) {
            Response::redirect('/login');
            return;
        }

        // Evitar caché de la página
        CacheHeaders::noCache();

        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $limit = 100;
        $offset = ($page - 1) * $limit;

        $filters = [
            'estado' => filter_input(INPUT_GET, 'estado', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'tipo_salida' => filter_input(INPUT_GET, 'tipo_salida', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'fecha_desde' => filter_input(INPUT_GET, 'fecha_desde', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'fecha_hasta' => filter_input(INPUT_GET, 'fecha_hasta', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        ];

        $filters = array_filter($filters);

        $boletas = $this->boletaRepository->findHistorialAdmin($limit, $offset, $filters);

        require __DIR__ . '/../../../views/admin/boletas-salida/historial.php';
    }

    /**
     * API: Aprobar boleta
     * POST /api/admin/boletas-salida/{id}/aprobar
     */
    public function apiAprobar(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $user = $this->authService->getCurrentUser();

        if (!$user) {
            Response::json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $result = $this->boletaService->aprobarPorAdmin($id, $user['id']);

        Response::json($result, $result['success'] ? 200 : 400);
    }

    /**
     * API: Rechazar boleta
     * POST /api/admin/boletas-salida/{id}/rechazar
     */
    public function apiRechazar(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $user = $this->authService->getCurrentUser();

        if (!$user) {
            Response::json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $motivo = $data['motivo'] ?? '';

        $result = $this->boletaService->rechazarPorAdmin($id, $user['id'], $motivo);

        Response::json($result, $result['success'] ? 200 : 400);
    }

    /**
     * API: Obtener detalle de boleta
     * GET /api/admin/boletas-salida/{id}
     */
    public function apiDetalle(int $id): void
    {
        $user = $this->authService->getCurrentUser();

        if (!$user) {
            Response::json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $boleta = $this->boletaRepository->findById($id);

        if (!$boleta) {
            Response::json(['success' => false, 'message' => 'Boleta no encontrada'], 404);
            return;
        }

        Response::json(['success' => true, 'data' => $boleta]);
    }

    /**
     * API: Estadísticas para dashboard
     * GET /api/admin/boletas-salida/estadisticas
     */
    public function apiEstadisticas(): void
    {
        $user = $this->authService->getCurrentUser();

        if (!$user) {
            Response::json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $estadisticas = [
            'pendientes' => $this->boletaRepository->countPendientesAdmin(),
            'aprobadas' => $this->boletaRepository->countAprobadas(),
        ];

        Response::json(['success' => true, 'data' => $estadisticas]);
    }
}
