<?php

namespace App\BoletasSalida\Controllers;

use App\BoletasSalida\Services\BoletaSalidaService;
use App\BoletasSalida\Repositories\BoletaSalidaRepository;
use App\Services\AprendizAuthService;
use App\Repositories\InstructorFichaRepository;
use App\Support\Response;
use App\Support\CacheHeaders;
use App\Session\SessionManager;

/**
 * Controlador para gestión de boletas de salida (rol aprendiz)
 */
class AprendizBoletaController
{
    private BoletaSalidaService $boletaService;
    private BoletaSalidaRepository $boletaRepository;
    private AprendizAuthService $authService;
    private InstructorFichaRepository $instructorFichaRepository;
    private SessionManager $session;

    public function __construct(
        BoletaSalidaService $boletaService,
        BoletaSalidaRepository $boletaRepository,
        AprendizAuthService $authService,
        InstructorFichaRepository $instructorFichaRepository,
        SessionManager $session
    ) {
        $this->boletaService = $boletaService;
        $this->boletaRepository = $boletaRepository;
        $this->authService = $authService;
        $this->instructorFichaRepository = $instructorFichaRepository;
        $this->session = $session;
    }

    /**
     * Vista principal: formulario + historial
     * GET /aprendiz/boletas-salida
     */
    public function index(): void
    {
        $aprendiz = $this->authService->getCurrentAprendiz();

        if (!$aprendiz) {
            $this->session->set('error', 'Sesión inválida');
            Response::redirect('/aprendiz/login');
        }

        // Evitar caché de la página
        CacheHeaders::noCache();

        $instructoresFicha = [];
        if ($aprendiz['ficha_id']) {
            $instructoresFicha = $this->instructorFichaRepository->findInstructoresByFicha($aprendiz['ficha_id']);
        }

        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $boletas = $this->boletaRepository->findByAprendiz($aprendiz['id'], $limit, $offset);
        $total = $this->boletaRepository->countByAprendiz($aprendiz['id']);
        $totalPages = ceil($total / $limit);

        $success = $_SESSION['success'] ?? null;
        $error = $_SESSION['error'] ?? null;
        $errors = $_SESSION['errors'] ?? [];
        
        unset($_SESSION['success'], $_SESSION['error'], $_SESSION['errors']);

        // Preparar datos de usuario para el header
        $user = [
            'id' => $aprendiz['id'],
            'nombre' => $aprendiz['nombre'],
            'apellido' => $aprendiz['apellido'] ?? '',
            'email' => $aprendiz['email'],
            'documento' => $aprendiz['documento'],
            'rol' => 'aprendiz'
        ];

        require __DIR__ . '/../../../views/aprendiz/boletas-salida/index.php';
    }

    /**
     * Crear nueva solicitud de boleta
     * POST /aprendiz/boletas-salida
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/aprendiz/boletas-salida');
        }

        $aprendiz = $this->authService->getCurrentAprendiz();

        if (!$aprendiz) {
            $this->session->set('error', 'Sesión inválida');
            Response::redirect('/aprendiz/login');
        }

        $data = [
            'aprendiz_id' => $aprendiz['id'],
            'ficha_id' => $aprendiz['ficha_id'],
            'instructor_id' => filter_input(INPUT_POST, 'instructor_id', FILTER_VALIDATE_INT),
            'tipo_salida' => filter_input(INPUT_POST, 'tipo_salida', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'motivo' => filter_input(INPUT_POST, 'motivo', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'motivo_otro' => filter_input(INPUT_POST, 'motivo_otro', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'hora_salida_solicitada' => filter_input(INPUT_POST, 'hora_salida_solicitada', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'hora_reingreso_solicitada' => filter_input(INPUT_POST, 'hora_reingreso_solicitada', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        ];

        $result = $this->boletaService->crearSolicitud($data);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['errors'] = $result['errors'];
        }

        Response::redirect('/aprendiz/boletas-salida');
    }

    /**
     * API: Crear nueva solicitud de boleta (JSON)
     * POST /api/aprendiz/boletas-salida/crear
     */
    public function apiCrear(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['success' => false, 'error' => 'Método no permitido'], 405);
            return;
        }

        $aprendiz = $this->authService->getCurrentAprendiz();

        if (!$aprendiz) {
            Response::json(['success' => false, 'error' => 'No autorizado'], 401);
            return;
        }

        $data = [
            'aprendiz_id' => $aprendiz['id'],
            'ficha_id' => $aprendiz['ficha_id'],
            'instructor_id' => filter_input(INPUT_POST, 'instructor_id', FILTER_VALIDATE_INT),
            'tipo_salida' => filter_input(INPUT_POST, 'tipo_salida', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'motivo' => filter_input(INPUT_POST, 'motivo', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'motivo_otro' => filter_input(INPUT_POST, 'motivo_otro', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'hora_salida_solicitada' => filter_input(INPUT_POST, 'hora_salida_solicitada', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'hora_reingreso_solicitada' => filter_input(INPUT_POST, 'hora_reingreso_solicitada', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        ];

        $result = $this->boletaService->crearSolicitud($data);

        if ($result['success']) {
            // Obtener la boleta recién creada
            $boletaId = $result['boleta_id'] ?? null;
            $boleta = null;
            
            if ($boletaId) {
                $boleta = $this->boletaRepository->findById($boletaId);
            }

            Response::json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'boleta' => $boleta,
                ],
            ]);
        } else {
            Response::json([
                'success' => false,
                'errors' => $result['errors'],
            ], 400);
        }
    }

    /**
     * API: Obtener historial de boletas (JSON)
     * GET /api/aprendiz/boletas-salida
     */
    public function apiHistorial(): void
    {
        $aprendiz = $this->authService->getCurrentAprendiz();

        if (!$aprendiz) {
            Response::json(['success' => false, 'error' => 'No autorizado'], 401);
            return;
        }

        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 20;
        $offset = ($page - 1) * $limit;

        $boletas = $this->boletaRepository->findByAprendiz($aprendiz['id'], $limit, $offset);
        $total = $this->boletaRepository->countByAprendiz($aprendiz['id']);

        Response::json([
            'success' => true,
            'data' => [
                'boletas' => $boletas,
                'total' => $total,
                'page' => $page,
                'total_pages' => ceil($total / $limit),
            ],
        ]);
    }

    /**
     * API: Obtener detalle de una boleta específica
     * GET /api/aprendiz/boletas-salida/{id}
     */
    public function apiDetalle(int $id): void
    {
        $aprendiz = $this->authService->getCurrentAprendiz();

        if (!$aprendiz) {
            Response::json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $boleta = $this->boletaRepository->findById($id);

        if (!$boleta || $boleta['aprendiz_id'] !== $aprendiz['id']) {
            Response::json(['success' => false, 'message' => 'Boleta no encontrada'], 404);
            return;
        }

        Response::json(['success' => true, 'data' => $boleta]);
    }
}
