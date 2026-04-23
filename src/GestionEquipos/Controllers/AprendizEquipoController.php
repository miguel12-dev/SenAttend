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

        // Aggressive cache prevention headers
        header('Cache-Control: no-store, no-cache, must-revalidate, proxy-revalidate, private');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('ETag: "' . md5(time() . microtime()) . '"');
        header('X-Accel-Expires: 0');

        $this->session->start();
        $error = $this->session->getFlash('error');
        $message = $this->session->getFlash('message');
        $success = $this->session->getFlash('success');
        
        // Check for created param from redirect
        $created = isset($_GET['created']) && is_numeric($_GET['created']);

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

        // Aggressive cache prevention headers
        header('Cache-Control: no-store, no-cache, must-revalidate, proxy-revalidate, private');
        header('Pragma: no-cache');
        header('Expires: 0');

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
            
            // Check if AJAX request - return JSON
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'] ?? 'Equipo registrado correctamente',
                    'redirect' => '/aprendiz/equipos?created=' . time()
                ]);
                exit;
            }
            
            Response::redirect('/aprendiz/equipos?created=' . time());
        } else {
            $this->session->flash('error', implode('<br>', $result['errors'] ?? []));
            $this->session->set('aprendiz_old', $data);
            
            // Check if AJAX request - return JSON error
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => implode('<br>', $result['errors'] ?? ['Error al registrar equipo']),
                    'errors' => $result['errors'] ?? []
                ]);
                exit;
            }
            
            Response::redirect('/aprendiz/equipos/crear');
        }
    }
    
    /**
     * Check if request is AJAX (XMLHttpRequest)
     * @return bool
     */
    private function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
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
     * Formulario para editar un equipo
     * GET /aprendiz/equipos/{id}/editar
     */
    public function edit(int $equipoId): void
    {
        $user = $this->authService->getCurrentUser();
        if (!$user || $user['rol'] !== 'aprendiz') {
            Response::redirect('/login');
        }

        // Aggressive cache prevention headers
        header('Cache-Control: no-store, no-cache, must-revalidate, proxy-revalidate, private');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Obtener los datos del equipo del aprendiz
        $equipo = $this->aprendizEquipoService->getEquipoById($equipoId, (int)$user['id']);

        if (!$equipo) {
            $this->session->start();
            $this->session->flash('error', 'Equipo no encontrado');
            Response::redirect('/aprendiz/equipos');
        }

        $this->session->start();
        $error = $this->session->getFlash('error');
        $message = $this->session->getFlash('message');
        $old = $this->session->get('equipo_old', []);
        $this->session->remove('equipo_old');

        // Pass variables to view
        require __DIR__ . '/../../../views/aprendiz/equipos/edit.php';
    }

    /**
     * Actualiza un equipo
     * POST /aprendiz/equipos/{id}/actualizar
     */
    public function actualizar(int $equipoId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/aprendiz/panel');
        }

        $user = $this->authService->getCurrentUser();
        if (!$user || $user['rol'] !== 'aprendiz') {
            Response::redirect('/login');
        }

        $marca = trim(filter_input(INPUT_POST, 'marca', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        // Manejo de imagen (opcional)
        $imagenPath = null;
        $equipoActual = $this->aprendizEquipoService->getEquipoById($equipoId, (int)$user['id']);
        
        if (!$equipoActual) {
            $this->session->start();
            $this->session->flash('error', 'Equipo no encontrado');
            Response::redirect('/aprendiz/equipos');
        }

        // Si hay nueva imagen, procesarla
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
                // Eliminar imagen anterior si existe
                if (!empty($equipoActual['imagen'])) {
                    $oldPath = __DIR__ . '/../../../public/' . $equipoActual['imagen'];
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $safeName = 'equipo_' . $user['id'] . '_' . time() . '.' . $extension;
                $destPath = $uploadDir . $safeName;

                if (move_uploaded_file($tmpName, $destPath)) {
                    $imagenPath = 'uploads/equipos/' . $safeName;
                }
            }
        }

        $data = [
            'marca' => $marca,
            'imagen' => $imagenPath
        ];

        $result = $this->aprendizEquipoService->actualizarEquipo($equipoId, (int)$user['id'], $data);

        $this->session->start();

        if ($result['success']) {
            $this->session->flash('success', $result['message'] ?? 'Equipo actualizado correctamente');
            
            // Check if AJAX request - return JSON
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'] ?? 'Equipo actualizado correctamente',
                    'redirect' => '/aprendiz/equipos?updated=' . time()
                ]);
                exit;
            }
            
            Response::redirect('/aprendiz/equipos?updated=' . time());
        } else {
            $this->session->flash('error', implode('<br>', $result['errors'] ?? []));
            $this->session->set('equipo_old', $data);
            
            // Check if AJAX request - return JSON error
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => implode('<br>', $result['errors'] ?? ['Error al actualizar equipo']),
                    'errors' => $result['errors'] ?? []
                ]);
                exit;
            }
            
            Response::redirect('/aprendiz/equipos/' . $equipoId . '/editar');
        }
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


