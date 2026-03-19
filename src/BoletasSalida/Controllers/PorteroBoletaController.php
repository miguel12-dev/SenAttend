<?php

namespace App\BoletasSalida\Controllers;

use App\BoletasSalida\Services\BoletaSalidaService;
use App\BoletasSalida\Repositories\BoletaSalidaRepository;
use App\Services\AuthService;
use App\Support\Response;

/**
 * Controlador para gestión de boletas de salida (rol portero)
 */
class PorteroBoletaController
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
     * Panel principal: salidas aprobadas y reingresos pendientes
     * GET /portero/boletas-salida
     */
    public function index(): void
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || $user['rol'] !== 'portero') {
            Response::notFound();
        }

        $boletasAprobadas = $this->boletaRepository->findAprobadas();
        $reingresosPendientes = $this->boletaRepository->findPendientesReingreso();
        $contadores = $this->boletaService->getContadores($user['id'], 'portero');

        require __DIR__ . '/../../../views/portero/boletas-salida/index.php';
    }

    /**
     * API: Validar salida física
     * POST /api/portero/boletas-salida/{id}/validar-salida
     */
    public function apiValidarSalida(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $user = $this->authService->getCurrentUser();

        if (!$user || $user['rol'] !== 'portero') {
            Response::json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $result = $this->boletaService->validarSalida($id, $user['id']);

        Response::json($result, $result['success'] ? 200 : 400);
    }

    /**
     * API: Validar reingreso físico
     * POST /api/portero/boletas-salida/{id}/validar-reingreso
     */
    public function apiValidarReingreso(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $user = $this->authService->getCurrentUser();

        if (!$user || $user['rol'] !== 'portero') {
            Response::json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $observaciones = $data['observaciones'] ?? null;

        $result = $this->boletaService->validarReingreso($id, $user['id'], $observaciones);

        Response::json($result, $result['success'] ? 200 : 400);
    }

    /**
     * API: Obtener detalle de boleta
     * GET /api/portero/boletas-salida/{id}
     */
    public function apiDetalle(int $id): void
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || $user['rol'] !== 'portero') {
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
     * API: Obtener lista de boletas aprobadas
     * GET /api/portero/boletas-salida/aprobadas
     */
    public function apiAprobadas(): void
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || $user['rol'] !== 'portero') {
            Response::json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $boletas = $this->boletaRepository->findAprobadas();

        Response::json(['success' => true, 'data' => $boletas]);
    }

    /**
     * API: Obtener lista de reingresos pendientes
     * GET /api/portero/boletas-salida/reingresos-pendientes
     */
    public function apiReingresosPendientes(): void
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || $user['rol'] !== 'portero') {
            Response::json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $boletas = $this->boletaRepository->findPendientesReingreso();

        Response::json(['success' => true, 'data' => $boletas]);
    }
}
