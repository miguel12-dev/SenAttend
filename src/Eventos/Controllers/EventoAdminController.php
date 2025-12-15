<?php

namespace App\Eventos\Controllers;

use App\Eventos\Services\EventoService;
use App\Eventos\Services\EventoAuthService;
use App\Eventos\Services\EventoUsuarioService;
use App\Eventos\Repositories\EventoParticipanteRepository;
use App\Eventos\Middleware\EventoAuthMiddleware;
use App\Session\SessionManager;

/**
 * Controlador de administración de eventos
 */
class EventoAdminController
{
    private EventoService $eventoService;
    private EventoAuthService $authService;
    private EventoUsuarioService $usuarioService;
    private EventoParticipanteRepository $participanteRepository;
    private EventoAuthMiddleware $authMiddleware;
    private SessionManager $session;

    public function __construct(
        EventoService $eventoService,
        EventoAuthService $authService,
        EventoUsuarioService $usuarioService,
        EventoParticipanteRepository $participanteRepository,
        EventoAuthMiddleware $authMiddleware,
        SessionManager $session
    ) {
        $this->eventoService = $eventoService;
        $this->authService = $authService;
        $this->usuarioService = $usuarioService;
        $this->participanteRepository = $participanteRepository;
        $this->authMiddleware = $authMiddleware;
        $this->session = $session;
    }

    /**
     * Dashboard principal de administración
     */
    public function dashboard(): void
    {
        $this->authMiddleware->handle();
        
        $user = $this->authService->getCurrentUser();
        $eventos = $this->eventoService->listarEventos();
        
        // Obtener estadísticas generales
        $stats = [
            'total_eventos' => count($eventos),
            'eventos_activos' => count(array_filter($eventos, fn($e) => in_array($e['estado'], ['programado', 'en_curso']))),
            'eventos_finalizados' => count(array_filter($eventos, fn($e) => $e['estado'] === 'finalizado'))
        ];

        require ROOT_PATH . '/views/eventos/admin/dashboard.php';
    }

    /**
     * Muestra el formulario de creación de evento
     */
    public function showCrearForm(): void
    {
        $this->authMiddleware->handle();
        
        $user = $this->authService->getCurrentUser();
        $this->session->start();
        $error = $this->session->get('evento_crear_error');
        $this->session->remove('evento_crear_error');

        require ROOT_PATH . '/views/eventos/admin/crear.php';
    }

    /**
     * Procesa la creación de un evento
     */
    public function crear(): void
    {
        $this->authMiddleware->handle();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /eventos/admin/crear');
            exit;
        }

        $user = $this->authService->getCurrentUser();

        // Procesar imagen si se subió
        $imagenUrl = null;
        if (!empty($_FILES['imagen']['name'])) {
            $uploadResult = $this->eventoService->subirImagen($_FILES['imagen']);
            if ($uploadResult['success']) {
                $imagenUrl = $uploadResult['url'];
            }
        }

        $data = [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? '') ?: null,
            'imagen_url' => $imagenUrl,
            'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
            'fecha_fin' => $_POST['fecha_fin'] ?? '',
            'tipo_participantes' => $_POST['tipo_participantes'] ?? 'instructores',
            'creado_por' => $user['id']
        ];

        $result = $this->eventoService->crearEvento($data);

        if (!$result['success']) {
            $this->session->start();
            $this->session->set('evento_crear_error', implode(', ', $result['errors']));
            header('Location: /eventos/admin/crear');
            exit;
        }

        header('Location: /eventos/admin?mensaje=Evento creado exitosamente');
        exit;
    }

    /**
     * Muestra el detalle de un evento
     */
    public function detalle(int $id): void
    {
        $this->authMiddleware->handle();
        
        $user = $this->authService->getCurrentUser();
        $evento = $this->eventoService->obtenerEvento($id);

        if (!$evento) {
            header('Location: /eventos/admin');
            exit;
        }

        require ROOT_PATH . '/views/eventos/admin/detalle.php';
    }

    /**
     * Muestra los participantes de un evento
     */
    public function participantes(int $id): void
    {
        $this->authMiddleware->handle();
        
        $user = $this->authService->getCurrentUser();
        $evento = $this->eventoService->obtenerEvento($id);

        if (!$evento) {
            header('Location: /eventos/admin');
            exit;
        }

        $participantes = $this->participanteRepository->findByEvento($id);
        $estadisticas = $this->participanteRepository->countByEstado($id);

        require ROOT_PATH . '/views/eventos/admin/participantes.php';
    }

    /**
     * Muestra el formulario de edición
     */
    public function showEditarForm(int $id): void
    {
        $this->authMiddleware->handle();
        
        $user = $this->authService->getCurrentUser();
        $evento = $this->eventoService->obtenerEvento($id);

        if (!$evento) {
            header('Location: /eventos/admin');
            exit;
        }

        $this->session->start();
        $error = $this->session->get('evento_editar_error');
        $this->session->remove('evento_editar_error');

        require ROOT_PATH . '/views/eventos/admin/editar.php';
    }

    /**
     * Procesa la actualización de un evento
     */
    public function actualizar(int $id): void
    {
        $this->authMiddleware->handle();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /eventos/admin/' . $id . '/editar');
            exit;
        }

        // Procesar imagen si se subió una nueva
        $imagenUrl = null;
        if (!empty($_FILES['imagen']['name'])) {
            $uploadResult = $this->eventoService->subirImagen($_FILES['imagen']);
            if ($uploadResult['success']) {
                $imagenUrl = $uploadResult['url'];
            }
        }

        $data = [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? '') ?: null,
            'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
            'fecha_fin' => $_POST['fecha_fin'] ?? ''
        ];

        if ($imagenUrl) {
            $data['imagen_url'] = $imagenUrl;
        }

        $result = $this->eventoService->actualizarEvento($id, $data);

        if (!$result['success']) {
            $this->session->start();
            $this->session->set('evento_editar_error', implode(', ', $result['errors']));
            header('Location: /eventos/admin/' . $id . '/editar');
            exit;
        }

        header('Location: /eventos/admin/' . $id . '?mensaje=Evento actualizado exitosamente');
        exit;
    }

    /**
     * Cambia el estado de un evento
     */
    public function cambiarEstado(int $id): void
    {
        $this->authMiddleware->handle();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $estado = $data['estado'] ?? '';

        $result = $this->eventoService->cambiarEstado($id, $estado);

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    /**
     * Elimina un evento
     */
    public function eliminar(int $id): void
    {
        $this->authMiddleware->handle();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /eventos/admin');
            exit;
        }

        $this->eventoService->eliminarEvento($id);
        
        header('Location: /eventos/admin?mensaje=Evento eliminado');
        exit;
    }

    /**
     * Listado y formulario de usuarios administrativos de eventos
     */
    public function usuarios(): void
    {
        $this->authMiddleware->handle();
        $this->authMiddleware->requireRole('admin');

        $user = $this->authService->getCurrentUser();
        $usuarios = $this->usuarioService->listar();

        $this->session->start();
        $error = $this->session->get('evento_usuario_error');
        $success = $this->session->get('evento_usuario_success');
        $this->session->remove('evento_usuario_error');
        $this->session->remove('evento_usuario_success');

        require ROOT_PATH . '/views/eventos/admin/usuarios.php';
    }

    /**
     * Crea un usuario del módulo de eventos y envía credenciales
     */
    public function crearUsuario(): void
    {
        $this->authMiddleware->handle();
        $this->authMiddleware->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /eventos/admin/usuarios');
            exit;
        }

        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'documento' => trim($_POST['documento'] ?? ''),
            'rol' => $_POST['rol'] ?? 'administrativo'
        ];

        $result = $this->usuarioService->crearUsuario($data);

        $this->session->start();
        if (!$result['success']) {
            $mensaje = isset($result['errors']) ? implode(', ', $result['errors']) : 'No se pudo crear el usuario';
            $this->session->set('evento_usuario_error', $mensaje);
        } else {
            $mensaje = 'Usuario creado correctamente.';
            if (isset($result['email_enviado']) && !$result['email_enviado']) {
                $mensaje .= ' Sin embargo, no se pudo enviar el correo de credenciales.';
            }
            $this->session->set('evento_usuario_success', $mensaje);
        }

        header('Location: /eventos/admin/usuarios');
        exit;
    }

    /**
     * API: Obtiene participantes en formato JSON
     */
    public function apiParticipantes(int $id): void
    {
        $this->authMiddleware->handle();

        $participantes = $this->participanteRepository->findByEvento($id);
        $estadisticas = $this->participanteRepository->countByEstado($id);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'participantes' => $participantes,
                'estadisticas' => $estadisticas
            ]
        ]);
        exit;
    }
}

