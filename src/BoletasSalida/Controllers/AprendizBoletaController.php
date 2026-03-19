<?php

namespace App\BoletasSalida\Controllers;

use App\BoletasSalida\Services\BoletaSalidaService;
use App\BoletasSalida\Repositories\BoletaSalidaRepository;
use App\Services\AprendizAuthService;
use App\Repositories\InstructorFichaRepository;
use App\Support\Response;
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
}
