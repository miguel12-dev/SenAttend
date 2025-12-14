<?php

namespace App\Eventos\Controllers;

use App\Eventos\Services\EventoService;
use App\Eventos\Services\EventoRegistroService;
use App\Eventos\Repositories\EventoRepository;
use App\Session\SessionManager;

/**
 * Controlador para la vista pública de eventos
 */
class EventoPublicoController
{
    private EventoService $eventoService;
    private EventoRegistroService $registroService;
    private EventoRepository $eventoRepository;
    private SessionManager $session;

    public function __construct(
        EventoService $eventoService,
        EventoRegistroService $registroService,
        EventoRepository $eventoRepository,
        SessionManager $session
    ) {
        $this->eventoService = $eventoService;
        $this->registroService = $registroService;
        $this->eventoRepository = $eventoRepository;
        $this->session = $session;
    }

    /**
     * Muestra la lista de eventos públicos disponibles
     */
    public function index(): void
    {
        $eventos = $this->eventoService->obtenerEventosPublicos();
        
        $this->session->start();
        $mensaje = $this->session->get('eventos_mensaje');
        $this->session->remove('eventos_mensaje');

        require ROOT_PATH . '/views/eventos/publico/lista.php';
    }

    /**
     * Muestra el formulario de registro para un evento
     */
    public function showRegistro(int $id): void
    {
        $evento = $this->eventoRepository->findById($id);

        if (!$evento || !in_array($evento['estado'], ['programado', 'en_curso'])) {
            header('Location: /eventos');
            exit;
        }

        $this->session->start();
        $error = $this->session->get('registro_evento_error');
        $datos = $this->session->get('registro_evento_datos');
        $this->session->remove('registro_evento_error');
        $this->session->remove('registro_evento_datos');

        require ROOT_PATH . '/views/eventos/publico/registro.php';
    }

    /**
     * Busca un instructor por documento (API)
     */
    public function buscarInstructor(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $documento = trim($data['documento'] ?? '');

        if (empty($documento)) {
            $this->jsonResponse(['success' => false, 'error' => 'Documento requerido']);
            return;
        }

        $instructor = $this->registroService->buscarInstructor($documento);

        if ($instructor) {
            $this->jsonResponse([
                'success' => true,
                'encontrado' => true,
                'data' => $instructor
            ]);
        } else {
            $this->jsonResponse([
                'success' => true,
                'encontrado' => false,
                'message' => 'No se encontró el documento. Por favor complete sus datos.'
            ]);
        }
    }

    /**
     * Procesa el registro de un participante
     */
    public function registrar(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /eventos/registro/' . $id);
            exit;
        }

        $datos = [
            'documento' => trim($_POST['documento'] ?? ''),
            'nombre' => trim($_POST['nombre'] ?? ''),
            'apellido' => trim($_POST['apellido'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'tipo' => $_POST['tipo'] ?? 'externo'
        ];

        $result = $this->registroService->registrarParticipante($id, $datos);

        $this->session->start();

        if (!$result['success']) {
            if (isset($result['ya_registrado']) && $result['ya_registrado']) {
                $this->session->set('eventos_mensaje', [
                    'tipo' => 'info',
                    'texto' => 'Ya estás registrado en este evento. Revisa tu correo para el código QR.'
                ]);
                header('Location: /eventos');
                exit;
            }

            $this->session->set('registro_evento_error', $result['error'] ?? implode(', ', $result['errors'] ?? ['Error desconocido']));
            $this->session->set('registro_evento_datos', $datos);
            header('Location: /eventos/registro/' . $id);
            exit;
        }

        // Éxito - mostrar modal con confirmación
        $this->session->set('eventos_mensaje', [
            'tipo' => 'success',
            'texto' => 'Registro exitoso. El código QR ha sido enviado a ' . $result['data']['email_enmascarado']
        ]);

        header('Location: /eventos');
        exit;
    }

    /**
     * Reenvía el QR de ingreso
     */
    public function reenviarQR(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $documento = trim($data['documento'] ?? '');

        if (empty($documento)) {
            $this->jsonResponse(['success' => false, 'error' => 'Documento requerido']);
            return;
        }

        $result = $this->registroService->reenviarQRIngreso($documento, $id);
        $this->jsonResponse($result);
    }

    /**
     * Envía respuesta JSON
     */
    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

