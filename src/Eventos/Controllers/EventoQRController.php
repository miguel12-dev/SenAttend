<?php

namespace App\Eventos\Controllers;

use App\Eventos\Services\EventoQRService;
use App\Eventos\Services\EventoEmailService;
use App\Eventos\Services\EventoAuthService;
use App\Eventos\Repositories\EventoRepository;
use App\Eventos\Repositories\EventoParticipanteRepository;

/**
 * Controlador para validación de códigos QR de eventos
 */
class EventoQRController
{
    private EventoQRService $qrService;
    private EventoEmailService $emailService;
    private EventoAuthService $authService;
    private EventoRepository $eventoRepository;
    private EventoParticipanteRepository $participanteRepository;

    public function __construct(
        EventoQRService $qrService,
        EventoEmailService $emailService,
        EventoAuthService $authService,
        EventoRepository $eventoRepository,
        EventoParticipanteRepository $participanteRepository
    ) {
        $this->qrService = $qrService;
        $this->emailService = $emailService;
        $this->authService = $authService;
        $this->eventoRepository = $eventoRepository;
        $this->participanteRepository = $participanteRepository;
    }

    /**
     * Muestra la página del escáner QR
     */
    public function showScanner(): void
    {
        // Obtener usuario autenticado para el header
        $user = $this->authService->getCurrentUser();
        
        require ROOT_PATH . '/views/eventos/qr/scanner.php';
    }

    /**
     * Muestra la página del escáner QR para un evento específico
     */
    public function showScannerEvento(int $eventoId): void
    {
        $evento = $this->eventoRepository->findById($eventoId);
        
        if (!$evento) {
            header('Location: /eventos/admin');
            exit;
        }
        
        // Obtener usuario autenticado para el header
        $user = $this->authService->getCurrentUser();
        
        require ROOT_PATH . '/views/eventos/qr/scanner-evento.php';
    }

    /**
     * Valida un código QR sin procesarlo
     */
    public function validar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $token = trim($data['token'] ?? '');

        if (empty($token)) {
            $this->jsonResponse(['success' => false, 'error' => 'Token requerido']);
            return;
        }

        $result = $this->qrService->validarQR($token);
        $this->jsonResponse($result);
    }

    /**
     * Procesa el ingreso mediante código QR
     */
    public function procesarIngreso(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $token = trim($data['token'] ?? '');

        if (empty($token)) {
            $this->jsonResponse(['success' => false, 'error' => 'Token requerido']);
            return;
        }

        $result = $this->qrService->procesarIngreso($token);

        if ($result['success']) {
            // Generar y enviar QR de salida
            $participanteId = $result['data']['participante_id'];
            $this->generarYEnviarQRSalida($participanteId);
        }

        $this->jsonResponse($result);
    }

    /**
     * Procesa la salida mediante código QR
     */
    public function procesarSalida(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $token = trim($data['token'] ?? '');

        if (empty($token)) {
            $this->jsonResponse(['success' => false, 'error' => 'Token requerido']);
            return;
        }

        $result = $this->qrService->procesarSalida($token);
        $this->jsonResponse($result);
    }

    /**
     * Procesa un QR automáticamente detectando si es ingreso o salida
     * Si se proporciona evento_id, valida que el QR pertenezca a ese evento
     */
    public function procesar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $token = trim($data['token'] ?? '');
        $eventoId = isset($data['evento_id']) ? (int)$data['evento_id'] : null;

        if (empty($token)) {
            $this->jsonResponse(['success' => false, 'error' => 'Token requerido']);
            return;
        }

        // Primero validar el QR
        $validacion = $this->qrService->validarQR($token, $eventoId);

        if (!$validacion['success']) {
            $this->jsonResponse($validacion);
            return;
        }

        $tipo = $validacion['data']['tipo'];

        if ($tipo === 'ingreso') {
            $result = $this->qrService->procesarIngreso($token);
            
            if ($result['success']) {
                // Generar y enviar QR de salida
                $participanteId = $result['data']['participante_id'];
                $this->generarYEnviarQRSalida($participanteId);
                $result['qr_salida_enviado'] = true;
            }
        } else {
            $result = $this->qrService->procesarSalida($token);
        }

        $this->jsonResponse($result);
    }

    /**
     * Genera y envía el QR de salida por email
     */
    private function generarYEnviarQRSalida(int $participanteId): void
    {
        try {
            $qrResult = $this->qrService->generarQRSalida($participanteId);
            
            if ($qrResult['success']) {
                $participante = $this->participanteRepository->findById($participanteId);
                $evento = $this->eventoRepository->findById($participante['evento_id']);
                
                $this->emailService->enviarQRSalida(
                    $qrResult['data']['participante']['email'],
                    $qrResult['data']['participante']['nombre'] . ' ' . $qrResult['data']['participante']['apellido'],
                    $evento['titulo'],
                    $qrResult['data']['image_base64'],
                    [
                        'descripcion' => $evento['descripcion'] ?? '',
                        'imagen_url' => $evento['imagen_url'] ?? '',
                        'fecha_inicio' => $evento['fecha_inicio'] ?? '',
                        'fecha_fin' => $evento['fecha_fin'] ?? ''
                    ]
                );
            }
        } catch (\Exception $e) {
            error_log('EventoQRController: Error generando QR de salida: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene el historial de escaneos del día actual
     */
    public function historialHoy(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        try {
            // Obtener todos los participantes con ingreso o salida del día de hoy
            $participantes = $this->participanteRepository->getHistorialHoy();
            
            $historial = [];
            foreach ($participantes as $p) {
                $evento = $this->eventoRepository->findById($p['evento_id']);
                
                // Registrar entrada si tiene fecha_ingreso
                if (!empty($p['fecha_ingreso'])) {
                    $historial[] = [
                        'id' => $p['id'],
                        'nombre' => $p['nombre'],
                        'apellido' => $p['apellido'],
                        'documento' => $p['documento'],
                        'evento' => $evento['titulo'] ?? 'Evento',
                        'tipo' => 'ingreso',
                        'fecha' => date('d/m/Y', strtotime($p['fecha_ingreso'])),
                        'hora' => date('h:i:s A', strtotime($p['fecha_ingreso']))
                    ];
                }
                
                // Registrar salida si tiene fecha_salida
                if (!empty($p['fecha_salida'])) {
                    $historial[] = [
                        'id' => $p['id'] . '_salida',
                        'nombre' => $p['nombre'],
                        'apellido' => $p['apellido'],
                        'documento' => $p['documento'],
                        'evento' => $evento['titulo'] ?? 'Evento',
                        'tipo' => 'salida',
                        'fecha' => date('d/m/Y', strtotime($p['fecha_salida'])),
                        'hora' => date('h:i:s A', strtotime($p['fecha_salida']))
                    ];
                }
            }
            
            // Ordenar por fecha más reciente primero
            usort($historial, function($a, $b) {
                return strcmp($b['fecha'] . ' ' . $b['hora'], $a['fecha'] . ' ' . $a['hora']);
            });
            
            $this->jsonResponse([
                'success' => true,
                'data' => $historial
            ]);
        } catch (\Exception $e) {
            error_log('EventoQRController::historialHoy error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Error al cargar el historial']);
        }
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

