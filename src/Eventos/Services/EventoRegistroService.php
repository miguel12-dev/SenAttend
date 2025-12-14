<?php

namespace App\Eventos\Services;

use App\Eventos\Repositories\EventoRepository;
use App\Eventos\Repositories\EventoParticipanteRepository;
use App\Repositories\UserRepository;
use App\Database\Connection;
use Exception;

/**
 * Servicio para el registro de participantes en eventos
 * Principio de Responsabilidad Única: Solo maneja el registro de participantes
 */
class EventoRegistroService
{
    private EventoRepository $eventoRepository;
    private EventoParticipanteRepository $participanteRepository;
    private EventoQRService $qrService;
    private EventoEmailService $emailService;
    private UserRepository $userRepository;

    public function __construct(
        EventoRepository $eventoRepository,
        EventoParticipanteRepository $participanteRepository,
        EventoQRService $qrService,
        EventoEmailService $emailService,
        UserRepository $userRepository
    ) {
        $this->eventoRepository = $eventoRepository;
        $this->participanteRepository = $participanteRepository;
        $this->qrService = $qrService;
        $this->emailService = $emailService;
        $this->userRepository = $userRepository;
    }

    /**
     * Busca un instructor por su documento
     */
    public function buscarInstructor(string $documento): ?array
    {
        $usuario = $this->userRepository->findByDocumento($documento);
        
        if (!$usuario) {
            return null;
        }

        // Solo permitir instructores
        if ($usuario['rol'] !== 'instructor') {
            return null;
        }

        return [
            'documento' => $usuario['documento'],
            'nombre' => $this->extraerNombre($usuario['nombre']),
            'apellido' => $this->extraerApellido($usuario['nombre']),
            'email' => $usuario['email'],
            'tipo' => 'instructor'
        ];
    }

    /**
     * Registra un participante en un evento
     */
    public function registrarParticipante(int $eventoId, array $datos): array
    {
        // Verificar que el evento existe y está disponible
        $evento = $this->eventoRepository->findById($eventoId);
        if (!$evento) {
            return ['success' => false, 'error' => 'Evento no encontrado'];
        }

        if (!in_array($evento['estado'], ['programado', 'en_curso'])) {
            return ['success' => false, 'error' => 'El evento no está disponible para registro'];
        }

        // Verificar si ya está registrado
        $existente = $this->participanteRepository->findByDocumentoAndEvento(
            $datos['documento'], 
            $eventoId
        );

        if ($existente) {
            return [
                'success' => false, 
                'error' => 'Ya estás registrado en este evento',
                'ya_registrado' => true
            ];
        }

        // Validar datos
        $errores = $this->validarDatos($datos);
        if (!empty($errores)) {
            return ['success' => false, 'errors' => $errores];
        }

        try {
            Connection::beginTransaction();

            // Crear participante
            $participanteId = $this->participanteRepository->create([
                'evento_id' => $eventoId,
                'documento' => $datos['documento'],
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'email' => $datos['email'],
                'tipo' => $datos['tipo'] ?? 'instructor',
                'estado' => 'registrado'
            ]);

            // Generar QR de ingreso
            $qrResult = $this->qrService->generarQRIngreso($participanteId);
            if (!$qrResult['success']) {
                throw new Exception('Error al generar el código QR');
            }

            // Enviar email con QR
            $emailResult = $this->emailService->enviarQRIngreso(
                $datos['email'],
                $datos['nombre'] . ' ' . $datos['apellido'],
                $evento['titulo'],
                $qrResult['data']['image_base64']
            );

            Connection::commit();

            return [
                'success' => true,
                'message' => 'Registro exitoso. El código QR ha sido enviado a tu correo.',
                'data' => [
                    'participante_id' => $participanteId,
                    'email_enmascarado' => $this->enmascararEmail($datos['email']),
                    'email_enviado' => $emailResult['success']
                ]
            ];
        } catch (Exception $e) {
            Connection::rollBack();
            error_log('EventoRegistroService::registrarParticipante error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al procesar el registro. Por favor, intenta nuevamente.'
            ];
        }
    }

    /**
     * Reenvía el QR de ingreso a un participante
     */
    public function reenviarQRIngreso(string $documento, int $eventoId): array
    {
        $participante = $this->participanteRepository->findByDocumentoAndEvento($documento, $eventoId);
        
        if (!$participante) {
            return ['success' => false, 'error' => 'No estás registrado en este evento'];
        }

        if ($participante['estado'] !== 'registrado') {
            return ['success' => false, 'error' => 'Ya has ingresado al evento o ha finalizado'];
        }

        $evento = $this->eventoRepository->findById($eventoId);

        // Generar nuevo QR
        $qrResult = $this->qrService->generarQRIngreso($participante['id']);
        if (!$qrResult['success']) {
            return ['success' => false, 'error' => 'Error al generar el código QR'];
        }

        // Enviar email
        $emailResult = $this->emailService->enviarQRIngreso(
            $participante['email'],
            $participante['nombre'] . ' ' . $participante['apellido'],
            $evento['titulo'],
            $qrResult['data']['image_base64']
        );

        return [
            'success' => true,
            'message' => 'El código QR ha sido reenviado a tu correo',
            'email_enmascarado' => $this->enmascararEmail($participante['email'])
        ];
    }

    /**
     * Valida los datos del participante
     */
    private function validarDatos(array $datos): array
    {
        $errores = [];

        if (empty($datos['documento'])) {
            $errores[] = 'El documento es obligatorio';
        } elseif (!preg_match('/^[0-9]{6,15}$/', $datos['documento'])) {
            $errores[] = 'El documento debe contener solo números (6-15 dígitos)';
        }

        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre es obligatorio';
        }

        if (empty($datos['apellido'])) {
            $errores[] = 'El apellido es obligatorio';
        }

        if (empty($datos['email'])) {
            $errores[] = 'El email es obligatorio';
        } elseif (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no es válido';
        }

        return $errores;
    }

    /**
     * Extrae el nombre del campo nombre completo
     */
    private function extraerNombre(string $nombreCompleto): string
    {
        $partes = explode(' ', trim($nombreCompleto));
        return $partes[0] ?? $nombreCompleto;
    }

    /**
     * Extrae el apellido del campo nombre completo
     */
    private function extraerApellido(string $nombreCompleto): string
    {
        $partes = explode(' ', trim($nombreCompleto));
        array_shift($partes);
        return implode(' ', $partes) ?: 'N/A';
    }

    /**
     * Enmascara un email para mostrar parcialmente
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

