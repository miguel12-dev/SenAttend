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
     * AHORA TAMBIÉN VALIDA SI YA ESTÁ REGISTRADO EN EL EVENTO
     */
    public function buscarInstructor(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $documento = trim($data['documento'] ?? '');
        $eventoId = (int)($data['evento_id'] ?? 0);

        if (empty($documento)) {
            $this->jsonResponse(['success' => false, 'error' => 'Documento requerido']);
            return;
        }

        if ($eventoId <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Evento no válido']);
            return;
        }

        // PRIMERO: Verificar si ya está registrado en este evento
        $yaRegistrado = $this->registroService->getParticipanteByDocumento($documento, $eventoId);
        
        if ($yaRegistrado) {
            // Ya está registrado - no permitir avanzar
            $emailEnmascarado = $this->enmascararEmail($yaRegistrado['email']);
            
            $this->jsonResponse([
                'success' => false,
                'ya_registrado' => true,
                'estado' => $yaRegistrado['estado'],
                'email_enmascarado' => $emailEnmascarado,
                'error' => '⚠️ Ya estás registrado en este evento. Puedes reenviar tu código QR si lo necesitas.'
            ]);
            return;
        }

        // No está registrado - continuar con la búsqueda de instructor
        $instructor = $this->registroService->buscarInstructor($documento);

        if ($instructor) {
            // Enmascarar el email para mostrar parcialmente
            $emailEnmascarado = $this->enmascararEmail($instructor['email']);
            
            $this->jsonResponse([
                'success' => true,
                'encontrado' => true,
                'es_instructor' => true,
                'data' => [
                    'documento' => $instructor['documento'],
                    'nombre' => $instructor['nombre'], // Nombre completo
                    'email' => $instructor['email'],
                    'email_enmascarado' => $emailEnmascarado,
                    'tipo' => 'instructor'
                ]
            ]);
        } else {
            $this->jsonResponse([
                'success' => true,
                'encontrado' => false,
                'es_instructor' => false,
                'message' => 'No se encontró el documento como instructor. Por favor complete sus datos.'
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

        $tipo = $_POST['tipo'] ?? 'externo';
        
        $datos = [
            'documento' => trim($_POST['documento'] ?? ''),
            'nombre' => trim($_POST['nombre'] ?? ''),
            'apellido' => trim($_POST['apellido'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'tipo' => $tipo
        ];
        
        // Si es instructor y no viene apellido, dejar vacío (el nombre completo ya está en nombre)
        if ($tipo === 'instructor' && empty($datos['apellido'])) {
            $datos['apellido'] = '';
        }

        $result = $this->registroService->registrarParticipante($id, $datos);

        $this->session->start();

        if (!$result['success']) {
            if (isset($result['ya_registrado']) && $result['ya_registrado']) {
                // Obtener el participante para mostrar su email enmascarado
                $participante = $this->registroService->buscarInstructor($datos['documento']);
                if (!$participante) {
                    // Si no es instructor, buscar en participantes de eventos
                    $participanteEvento = $this->registroService->getParticipanteByDocumento($datos['documento'], $id);
                    if ($participanteEvento) {
                        $emailEnmascarado = $this->enmascararEmail($participanteEvento['email']);
                    }
                } else {
                    $emailEnmascarado = $this->enmascararEmail($participante['email']);
                }
                
                // Quedarse en la página de registro con opción de reenviar QR
                $datos['_duplicado'] = true;
                $datos['_email_enmascarado'] = $emailEnmascarado ?? '***';
                $this->session->set('registro_evento_error', 'Ya estás registrado en este evento. Puedes reenviar tu código QR si lo necesitas.');
                $this->session->set('registro_evento_datos', $datos);
                header('Location: /eventos/registro/' . $id);
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
     * Enmascara un email para mostrar parcialmente
     * Formato: mig*****32@gmail.com (primeros 3 caracteres + asteriscos + últimos 2 caracteres)
     */
    private function enmascararEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return str_repeat('*', 5) . '@***';
        }

        $local = $parts[0];
        $domain = $parts[1];
        $localLength = strlen($local);

        if ($localLength <= 3) {
            // Si es muy corto, mostrar solo el primer carácter
            $maskedLocal = $local[0] . str_repeat('*', min(5, $localLength - 1));
        } else if ($localLength <= 5) {
            // Si tiene 4-5 caracteres, mostrar primeros 2 y asteriscos
            $maskedLocal = substr($local, 0, 2) . str_repeat('*', $localLength - 2);
        } else {
            // Si tiene más de 5 caracteres, mostrar primeros 3, asteriscos, y últimos 2
            $maskedLocal = substr($local, 0, 3) . str_repeat('*', max(3, $localLength - 5)) . substr($local, -2);
        }

        return $maskedLocal . '@' . $domain;
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

