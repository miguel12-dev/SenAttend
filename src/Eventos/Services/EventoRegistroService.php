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
     * Devuelve solo el nombre completo (sin separar nombre/apellido)
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
            'nombre' => $usuario['nombre'], // Nombre completo tal como está en BD
            'email' => $usuario['email'],
            'tipo' => 'instructor'
        ];
    }

    /**
     * Obtiene un participante por documento y evento
     */
    public function getParticipanteByDocumento(string $documento, int $eventoId): ?array
    {
        return $this->participanteRepository->findByDocumentoAndEvento($documento, $eventoId);
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

            // Si es instructor y no tiene apellido separado, usar nombre completo
            $nombre = $datos['nombre'];
            $apellido = $datos['apellido'] ?? '';
            
            // Si es instructor y apellido está vacío, dejar apellido vacío (el nombre completo ya está en nombre)
            if (($datos['tipo'] ?? '') === 'instructor' && empty($apellido)) {
                $apellido = ''; // Dejar vacío para instructores
            }

            // Crear participante
            $participanteId = $this->participanteRepository->create([
                'evento_id' => $eventoId,
                'documento' => $datos['documento'],
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $datos['email'],
                'tipo' => $datos['tipo'] ?? 'instructor',
                'estado' => 'registrado'
            ]);

            // Generar QR de ingreso
            $qrResult = $this->qrService->generarQRIngreso($participanteId);
            if (!$qrResult['success']) {
                throw new Exception('Error al generar el código QR');
            }

            // Construir nombre completo para el email
            $nombreCompleto = $nombre;
            if (!empty($apellido)) {
                $nombreCompleto .= ' ' . $apellido;
            }
            
            // Enviar email con QR (incluyendo información completa del evento)
            $emailResult = $this->emailService->enviarQRIngreso(
                $datos['email'],
                $nombreCompleto,
                $evento['titulo'],
                $qrResult['data']['image_base64'],
                [
                    'descripcion' => $evento['descripcion'] ?? '',
                    'imagen_url' => $evento['imagen_url'] ?? '',
                    'fecha_inicio' => $evento['fecha_inicio'] ?? '',
                    'fecha_fin' => $evento['fecha_fin'] ?? ''
                ]
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
     * Reenvía el QR correspondiente a un participante
     * VALIDACIÓN AUTOMÁTICA: Si ya tiene entrada registrada, envía QR de salida
     */
    public function reenviarQRIngreso(string $documento, int $eventoId): array
    {
        $participante = $this->participanteRepository->findByDocumentoAndEvento($documento, $eventoId);
        
        if (!$participante) {
            return ['success' => false, 'error' => 'No estás registrado en este evento'];
        }

        $evento = $this->eventoRepository->findById($eventoId);

        // VALIDACIÓN AUTOMÁTICA DEL CONTEXTO
        // Si el participante ya tiene entrada registrada (estado: ingreso, salida, sin_salida)
        // entonces debe recibir el QR de SALIDA, no el de ingreso
        $estadosConIngreso = ['ingreso', 'salida', 'sin_salida'];
        
        if (in_array($participante['estado'], $estadosConIngreso)) {
            // Ya registró entrada, debe usar QR de SALIDA
            if ($participante['estado'] === 'salida') {
                return [
                    'success' => false, 
                    'error' => 'Ya has registrado tu salida del evento'
                ];
            }
            
            // Generar QR de SALIDA
            $qrResult = $this->qrService->generarQRSalida($participante['id']);
            if (!$qrResult['success']) {
                return ['success' => false, 'error' => 'Error al generar el código QR de salida'];
            }

            // Enviar email con QR de SALIDA (incluyendo información completa del evento)
            $emailResult = $this->emailService->enviarQRSalida(
                $participante['email'],
                $participante['nombre'] . ' ' . $participante['apellido'],
                $evento['titulo'],
                $qrResult['data']['image_base64'],
                [
                    'descripcion' => $evento['descripcion'] ?? '',
                    'imagen_url' => $evento['imagen_url'] ?? '',
                    'fecha_inicio' => $evento['fecha_inicio'] ?? '',
                    'fecha_fin' => $evento['fecha_fin'] ?? ''
                ]
            );

            return [
                'success' => true,
                'message' => 'Ya registraste tu entrada. El código QR de SALIDA ha sido enviado a tu correo',
                'tipo_enviado' => 'salida',
                'email_enmascarado' => $this->enmascararEmail($participante['email'])
            ];
        }
        
        // Estado: 'registrado' - Aún no ha ingresado, enviar QR de INGRESO
        $qrResult = $this->qrService->generarQRIngreso($participante['id']);
        if (!$qrResult['success']) {
            return ['success' => false, 'error' => 'Error al generar el código QR'];
        }

        // Enviar email con QR de INGRESO (incluyendo información completa del evento)
        $emailResult = $this->emailService->enviarQRIngreso(
            $participante['email'],
            $participante['nombre'] . ' ' . $participante['apellido'],
            $evento['titulo'],
            $qrResult['data']['image_base64'],
            [
                'descripcion' => $evento['descripcion'] ?? '',
                'imagen_url' => $evento['imagen_url'] ?? '',
                'fecha_inicio' => $evento['fecha_inicio'] ?? '',
                'fecha_fin' => $evento['fecha_fin'] ?? ''
            ]
        );

        return [
            'success' => true,
            'message' => 'El código QR de INGRESO ha sido reenviado a tu correo',
            'tipo_enviado' => 'ingreso',
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

        // Apellido solo es obligatorio si NO es instructor
        $esInstructor = ($datos['tipo'] ?? '') === 'instructor';
        if (!$esInstructor && empty($datos['apellido'])) {
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

