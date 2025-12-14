<?php

namespace App\Eventos\Controllers;

use App\Eventos\Services\EventoQRService;
use App\Eventos\Services\EventoEmailService;
use App\Eventos\Repositories\EventoRepository;
use App\Eventos\Repositories\EventoParticipanteRepository;

/**
 * Controlador para validación de códigos QR de eventos
 */
class EventoQRController
{
    private EventoQRService $qrService;
    private EventoEmailService $emailService;
    private EventoRepository $eventoRepository;
    private EventoParticipanteRepository $participanteRepository;

    public function __construct(
        EventoQRService $qrService,
        EventoEmailService $emailService,
        EventoRepository $eventoRepository,
        EventoParticipanteRepository $participanteRepository
    ) {
        $this->qrService = $qrService;
        $this->emailService = $emailService;
        $this->eventoRepository = $eventoRepository;
        $this->participanteRepository = $participanteRepository;
    }

    /**
     * Muestra la página del escáner QR
     */
    public function showScanner(): void
    {
        require ROOT_PATH . '/views/eventos/qr/scanner.php';
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
     */
    public function procesar(): void
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

        // Primero validar el QR
        $validacion = $this->qrService->validarQR($token);

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
                    $qrResult['data']['image_base64']
                );
            }
        } catch (\Exception $e) {
            error_log('EventoQRController: Error generando QR de salida: ' . $e->getMessage());
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

