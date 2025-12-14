<?php

namespace App\Eventos\Services;

use App\Eventos\Repositories\EventoQRRepository;
use App\Eventos\Repositories\EventoParticipanteRepository;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Exception;

/**
 * Servicio para generación y validación de códigos QR de eventos
 * Principio de Responsabilidad Única: Solo maneja QR de eventos
 */
class EventoQRService
{
    private EventoQRRepository $qrRepository;
    private EventoParticipanteRepository $participanteRepository;
    private EventoEncryptionService $encryptionService;

    public function __construct(
        EventoQRRepository $qrRepository,
        EventoParticipanteRepository $participanteRepository,
        EventoEncryptionService $encryptionService
    ) {
        $this->qrRepository = $qrRepository;
        $this->participanteRepository = $participanteRepository;
        $this->encryptionService = $encryptionService;
    }

    /**
     * Genera un QR de ingreso para un participante
     */
    public function generarQRIngreso(int $participanteId): array
    {
        $participante = $this->participanteRepository->findById($participanteId);
        if (!$participante) {
            return ['success' => false, 'error' => 'Participante no encontrado'];
        }

        // Invalidar QRs anteriores de ingreso
        $this->qrRepository->invalidarAnteriores($participanteId, 'ingreso');

        // Generar token único
        $token = $this->encryptionService->generateToken();

        // Crear payload cifrado
        $payload = [
            'participante_id' => $participanteId,
            'evento_id' => $participante['evento_id'],
            'tipo' => 'ingreso',
            'documento' => $participante['documento'],
            'timestamp' => time()
        ];

        $qrData = $this->encryptionService->encrypt($payload);

        // Guardar en base de datos
        $qrId = $this->qrRepository->create([
            'participante_id' => $participanteId,
            'token' => $token,
            'tipo' => 'ingreso',
            'qr_data' => $qrData
        ]);

        // Generar imagen QR
        $qrImage = $this->generarImagenQR($token);

        return [
            'success' => true,
            'data' => [
                'qr_id' => $qrId,
                'token' => $token,
                'tipo' => 'ingreso',
                'image_base64' => $qrImage,
                'participante' => [
                    'nombre' => $participante['nombre'],
                    'apellido' => $participante['apellido'],
                    'email' => $participante['email']
                ]
            ]
        ];
    }

    /**
     * Genera un QR de salida para un participante
     */
    public function generarQRSalida(int $participanteId): array
    {
        $participante = $this->participanteRepository->findById($participanteId);
        if (!$participante) {
            return ['success' => false, 'error' => 'Participante no encontrado'];
        }

        if ($participante['estado'] !== 'ingreso') {
            return ['success' => false, 'error' => 'El participante no ha ingresado al evento'];
        }

        // Invalidar QRs anteriores de salida
        $this->qrRepository->invalidarAnteriores($participanteId, 'salida');

        // Generar token único
        $token = $this->encryptionService->generateToken();

        // Crear payload cifrado
        $payload = [
            'participante_id' => $participanteId,
            'evento_id' => $participante['evento_id'],
            'tipo' => 'salida',
            'documento' => $participante['documento'],
            'timestamp' => time()
        ];

        $qrData = $this->encryptionService->encrypt($payload);

        // Guardar en base de datos
        $qrId = $this->qrRepository->create([
            'participante_id' => $participanteId,
            'token' => $token,
            'tipo' => 'salida',
            'qr_data' => $qrData
        ]);

        // Generar imagen QR
        $qrImage = $this->generarImagenQR($token);

        return [
            'success' => true,
            'data' => [
                'qr_id' => $qrId,
                'token' => $token,
                'tipo' => 'salida',
                'image_base64' => $qrImage,
                'participante' => [
                    'nombre' => $participante['nombre'],
                    'apellido' => $participante['apellido'],
                    'email' => $participante['email']
                ]
            ]
        ];
    }

    /**
     * Valida un QR escaneado
     */
    public function validarQR(string $token): array
    {
        $qr = $this->qrRepository->findByToken($token);

        if (!$qr) {
            return ['success' => false, 'error' => 'Código QR no válido'];
        }

        if ($qr['usado']) {
            return ['success' => false, 'error' => 'Este código QR ya fue utilizado'];
        }

        // Verificar que el evento esté en curso
        if (!in_array($qr['evento_estado'], ['programado', 'en_curso'])) {
            return ['success' => false, 'error' => 'El evento no está activo'];
        }

        // Descifrar datos para verificación adicional
        try {
            $payload = $this->encryptionService->decrypt($qr['qr_data']);
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error al validar el código QR'];
        }

        return [
            'success' => true,
            'data' => [
                'qr_id' => $qr['id'],
                'tipo' => $qr['tipo'],
                'participante_id' => $qr['participante_id'],
                'documento' => $qr['documento'],
                'nombre' => $qr['nombre'],
                'apellido' => $qr['apellido'],
                'email' => $qr['email'],
                'evento_id' => $qr['evento_id'],
                'evento_titulo' => $qr['evento_titulo']
            ]
        ];
    }

    /**
     * Procesa el escaneo de un QR de ingreso
     */
    public function procesarIngreso(string $token): array
    {
        $validacion = $this->validarQR($token);
        if (!$validacion['success']) {
            return $validacion;
        }

        $data = $validacion['data'];

        if ($data['tipo'] !== 'ingreso') {
            return ['success' => false, 'error' => 'Este no es un código de ingreso'];
        }

        // Registrar ingreso del participante
        $resultado = $this->participanteRepository->registrarIngreso($data['participante_id']);
        if (!$resultado) {
            return ['success' => false, 'error' => 'El participante ya ingresó al evento'];
        }

        // Marcar QR como usado
        $this->qrRepository->marcarUsado($data['qr_id']);

        return [
            'success' => true,
            'message' => 'Ingreso registrado exitosamente',
            'data' => [
                'participante_id' => $data['participante_id'],
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'email' => $this->enmascararEmail($data['email']),
                'evento_titulo' => $data['evento_titulo']
            ]
        ];
    }

    /**
     * Procesa el escaneo de un QR de salida
     */
    public function procesarSalida(string $token): array
    {
        $validacion = $this->validarQR($token);
        if (!$validacion['success']) {
            return $validacion;
        }

        $data = $validacion['data'];

        if ($data['tipo'] !== 'salida') {
            return ['success' => false, 'error' => 'Este no es un código de salida'];
        }

        // Registrar salida del participante
        $resultado = $this->participanteRepository->registrarSalida($data['participante_id']);
        if (!$resultado) {
            return ['success' => false, 'error' => 'El participante no ha ingresado o ya realizó la salida'];
        }

        // Marcar QR como usado
        $this->qrRepository->marcarUsado($data['qr_id']);

        return [
            'success' => true,
            'message' => 'Salida registrada exitosamente',
            'data' => [
                'participante_id' => $data['participante_id'],
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'email' => $this->enmascararEmail($data['email']),
                'evento_titulo' => $data['evento_titulo']
            ]
        ];
    }

    /**
     * Genera la imagen del código QR
     */
    private function generarImagenQR(string $data): string
    {
        try {
            // Verificar si GD está habilitado
            if (!extension_loaded('gd')) {
                error_log("GD extension is not loaded. Using SVG for QR generation.");
                return $this->generarQRComoSVG($data);
            }

            $builder = new Builder(
                writer: new PngWriter(),
                data: $data,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10
            );
            
            $result = $builder->build();
            $imageString = $result->getString();
            
            if (empty($imageString)) {
                error_log("QR image string is empty, trying SVG");
                return $this->generarQRComoSVG($data);
            }
            
            return base64_encode($imageString);
        } catch (Exception $e) {
            error_log('EventoQRService::generarImagenQR error: ' . $e->getMessage());
            // Intentar usar SVG como alternativa
            return $this->generarQRComoSVG($data);
        }
    }

    /**
     * Genera QR como SVG (no requiere GD)
     */
    private function generarQRComoSVG(string $data): string
    {
        try {
            $builder = new Builder(
                writer: new SvgWriter(),
                data: $data,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10
            );
            
            $result = $builder->build();
            $svgContent = $result->getString();
            
            return base64_encode($svgContent);
        } catch (Exception $e) {
            error_log("EventoQRService::generarQRComoSVG error: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Enmascara un email para mostrar parcialmente
     * Ejemplo: juan.perez@sena.edu.co -> jua*****@sena.edu.co
     */
    private function enmascararEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return str_repeat('*', 5) . '@***';
        }

        $local = $parts[0];
        $domain = $parts[1];

        if (strlen($local) <= 3) {
            $maskedLocal = $local[0] . str_repeat('*', 5);
        } else {
            $maskedLocal = substr($local, 0, 3) . str_repeat('*', 5);
        }

        return $maskedLocal . '@' . $domain;
    }
}

