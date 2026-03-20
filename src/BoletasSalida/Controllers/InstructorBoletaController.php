<?php

namespace App\BoletasSalida\Controllers;

use App\BoletasSalida\Services\BoletaSalidaService;
use App\BoletasSalida\Repositories\BoletaSalidaRepository;
use App\Services\AuthService;
use App\Support\Response;
use App\Support\CacheHeaders;

/**
 * Controlador para gestión de boletas de salida (rol instructor)
 */
class InstructorBoletaController
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
     * Vista principal: solicitudes pendientes
     * GET /instructor/boletas-salida
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

        $boletasPendientes = $this->boletaRepository->findPendientesByInstructor($user['id']);
        $contadores = $this->boletaService->getContadores($user['id'], 'instructor');

        require __DIR__ . '/../../../views/instructor/boletas-salida/index.php';
    }

    /**
     * Vista de historial de solicitudes procesadas
     * GET /instructor/boletas-salida/historial
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
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $boletas = $this->boletaRepository->findHistorialByInstructor($user['id'], $limit, $offset);

        require __DIR__ . '/../../../views/instructor/boletas-salida/historial.php';
    }

    /**
     * API: Aprobar boleta
     * POST /api/instructor/boletas-salida/{id}/aprobar
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

        $result = $this->boletaService->aprobarPorInstructor($id, $user['id']);

        Response::json($result, $result['success'] ? 200 : 400);
    }

    /**
     * API: Rechazar boleta
     * POST /api/instructor/boletas-salida/{id}/rechazar
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

        $result = $this->boletaService->rechazarPorInstructor($id, $user['id'], $motivo);

        Response::json($result, $result['success'] ? 200 : 400);
    }

    /**
     * API: Obtener detalle de boleta
     * GET /api/instructor/boletas-salida/{id}
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

        if ($boleta['instructor_id'] != $user['id']) {
            Response::json(['success' => false, 'message' => 'No tiene permisos para ver esta boleta'], 403);
            return;
        }

        Response::json(['success' => true, 'data' => $boleta]);
    }

    /**
     * API: Obtener solicitudes pendientes del instructor autenticado
     * GET /api/instructor/boletas-salida/pendientes
     */
    public function apiPendientes(): void
    {
        $user = $this->authService->getCurrentUser();

        if (!$user) {
            Response::json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $boletas = $this->boletaRepository->findPendientesByInstructor((int)$user['id']);

        Response::json([
            'success' => true,
            'data' => [
                'boletas' => $boletas,
            ],
        ]);
    }
}
