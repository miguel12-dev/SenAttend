<?php

namespace App\GestionEquipos\Controllers;

use App\GestionEquipos\Services\EquipoRegistroService;
use App\GestionEquipos\Services\EquipoQRService;
use App\GestionEquipos\Services\AprendizEquipoService;
use App\Services\AuthService;
use App\Session\SessionManager;
use App\Support\Response;

class AprendizEquipoController
{
    private AuthService $authService;
    private EquipoRegistroService $equipoRegistroService;
    private EquipoQRService $equipoQRService;
    private AprendizEquipoService $aprendizEquipoService;
    private SessionManager $session;

    public function __construct(
        AuthService $authService,
        EquipoRegistroService $equipoRegistroService,
        EquipoQRService $equipoQRService,
        AprendizEquipoService $aprendizEquipoService,
        SessionManager $session
    ) {
        $this->authService = $authService;
        $this->equipoRegistroService = $equipoRegistroService;
        $this->equipoQRService = $equipoQRService;
        $this->aprendizEquipoService = $aprendizEquipoService;
        $this->session = $session;
    }

    /**
     * Lista todos los equipos del aprendiz
     * GET /aprendiz/equipos
     */
    public function index(): void
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || $user['rol'] !== 'aprendiz') {
            Response::redirect('/login');
        }

        // Prevent browser caching to ensure fresh data and flash messages work
        header('Cache-Control: no-store, no-cache, must-revalidate, proxy-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $this->session->start();
        $error = $this->session->getFlash('error');
        $message = $this->session->getFlash('message');
        $success = $this->session->getFlash('success');

        $equipos = $this->aprendizEquipoService->getEquiposDeAprendiz((int)$user['id']);
        $equiposEliminados = $this->aprendizEquipoService->getEquiposEliminados((int)$user['id']);

        require __DIR__ . '/../../../views/aprendiz/equipos/index.php';
    }

    /**
     * Formulario para registrar un nuevo equipo
     * GET /aprendiz/equipos/crear
     */
    public function create(): void
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || $user['rol'] !== 'aprendiz') {
            Response::redirect('/login');
        }

        $this->session->start();
        $error = $this->session->getFlash('error') ?? $this->session->getFlash('aprendiz_error');
        $message = $this->session->getFlash('message') ?? $this->session->getFlash('aprendiz_message');
        $old = $this->session->get('aprendiz_old', []);
        $this->session->remove('aprendiz_old');

        require __DIR__ . '/../../../views/aprendiz/equipos/create.php';
    }

    /**
     * Procesa el registro de un nuevo equipo
     * POST /aprendiz/equipos
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/aprendiz/panel');
        }

        $user = $this->authService->getCurrentUser();
        if (!$user || $user['rol'] !== 'aprendiz') {
            Response::redirect('/login');
        }
        $aprendiz = $user;

        $numeroSerial = trim(filter_input(INPUT_POST, 'numero_serial', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $marca = trim(filter_input(INPUT_POST, 'marca', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        // Manejo de imagen (opcional)
        $imagenPath = null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/equipos/';

            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }

            $tmpName = $_FILES['imagen']['tmp_name'];
            $originalName = $_FILES['imagen']['name'];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            $allowed = ['jpg', 'jpeg', 'png'];
            if (in_array($extension, $allowed, true)) {
                $safeName = 'equipo_' . $user['id'] . '_' . time() . '.' . $extension;
                $destPath = $uploadDir . $safeName;

                if (move_uploaded_file($tmpName, $destPath)) {
                    $imagenPath = 'uploads/equipos/' . $safeName;
                }
            }
        }

        $data = [
            'numero_serial' => $numeroSerial,
            'marca' => $marca,
            'imagen' => $imagenPath,
        ];

        $result = $this->equipoRegistroService->registrarEquipoParaAprendiz((int)$user['id'], $data);

        $this->session->start();

        if ($result['success']) {
            $this->session->flash('success', $result['message'] ?? 'Equipo registrado correctamente');
            Response::redirect('/aprendiz/panel');
        } else {
            $this->session->flash('error', implode('<br>', $result['errors'] ?? []));
            $this->session->set('aprendiz_old', $data);
            Response::redirect('/aprendiz/equipos/crear');
        }
    }

    /**
     * Muestra el QR del equipo para el aprendiz actual
     * GET /aprendiz/equipos/{id}/qr
     */
    public function showQR(int $equipoId): void
    {
        $user = $this->authService->getCurrentUser();
        if (!$user || $user['rol'] !== 'aprendiz') {
            Response::redirect('/login');
        }

        $result = $this->equipoQRService->obtenerQRBase64ParaEquipo($equipoId, (int)$user['id']);

        if (!$result['success']) {
            $this->session->start();
            $this->session->flash('error', $result['message'] ?? 'No fue posible obtener el QR.');
            Response::redirect('/aprendiz/panel');
        }

        $qrInfo = $result['data'];

        require __DIR__ . '/../../../views/aprendiz/equipos/qr.php';
    }

    /**
     * Elimina lógicamente un equipo (soft-delete)
     * POST /aprendiz/equipos/{id}/eliminar
     */
    public function eliminar(int $relacionId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/aprendiz/equipos');
        }

        $user = $this->authService->getCurrentUser();
        if (!$user || $user['rol'] !== 'aprendiz') {
            Response::redirect('/login');
        }

        $result = $this->aprendizEquipoService->eliminarEquipo($relacionId, (int)$user['id']);

        $this->session->start();
        if ($result['success']) {
            $this->session->flash('success', $result['message']);
        } else {
            $this->session->flash('error', $result['message']);
        }

        // Redirect with action param to force page reload on client side
        Response::redirect('/aprendiz/equipos?action=done');
    }

    /**
     * Restaura un equipo previamente eliminado
     * POST /aprendiz/equipos/{id}/restaurar
     */
    public function restaurar(int $relacionId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/aprendiz/equipos');
        }

        $user = $this->authService->getCurrentUser();
        if (!$user || $user['rol'] !== 'aprendiz') {
            Response::redirect('/login');
        }

        $result = $this->aprendizEquipoService->restaurarEquipo($relacionId, (int)$user['id']);

        $this->session->start();
        if ($result['success']) {
            $this->session->flash('success', $result['message']);
        } else {
            $this->session->flash('error', $result['message']);
        }

        // Redirect with action param to force page reload on client side
        Response::redirect('/aprendiz/equipos?action=done');
    }

    /**
     * API: Elimina lógicamente un equipo (soft-delete) - AJAX
     * POST /api/aprendiz/equipos/{id}/eliminar
     */
    public function apiEliminar(int $relacionId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método no permitido', 405);
        }

        $user = $this->authService->getCurrentUser();
        if (!$user || $user['rol'] !== 'aprendiz') {
            Response::error('No autorizado', 401);
        }

        $result = $this->aprendizEquipoService->eliminarEquipo($relacionId, (int)$user['id']);

        if ($result['success']) {
            Response::success([
                'relacion_id' => $relacionId,
                'equipo' => $result['equipo'] ?? null
            ], $result['message']);
        } else {
            Response::error($result['message'], 400);
        }
    }

    /**
     * API: Restaura un equipo previamente eliminado - AJAX
     * POST /api/aprendiz/equipos/{id}/restaurar
     */
    public function apiRestaurar(int $relacionId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método no permitido', 405);
        }

        $user = $this->authService->getCurrentUser();
        if (!$user || $user['rol'] !== 'aprendiz') {
            Response::error('No autorizado', 401);
        }

        $result = $this->aprendizEquipoService->restaurarEquipo($relacionId, (int)$user['id']);

        if ($result['success']) {
            Response::success([
                'relacion_id' => $relacionId,
                'equipo' => $result['equipo'] ?? null
            ], $result['message']);
        } else {
            Response::error($result['message'], 400);
        }
    }
}


