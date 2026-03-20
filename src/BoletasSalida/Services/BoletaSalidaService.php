<?php

namespace App\BoletasSalida\Services;

use App\BoletasSalida\Repositories\BoletaSalidaRepository;
use App\Repositories\AprendizRepository;
use App\Repositories\FichaRepository;
use App\Repositories\UserRepository;

/**
 * Servicio de lógica de negocio para boletas de salida
 */
class BoletaSalidaService
{
    private BoletaSalidaRepository $boletaRepository;
    private AprendizRepository $aprendizRepository;
    private FichaRepository $fichaRepository;
    private UserRepository $userRepository;
    private BoletaNotificationService $notificationService;

    public function __construct(
        BoletaSalidaRepository $boletaRepository,
        AprendizRepository $aprendizRepository,
        FichaRepository $fichaRepository,
        UserRepository $userRepository,
        BoletaNotificationService $notificationService
    ) {
        $this->boletaRepository = $boletaRepository;
        $this->aprendizRepository = $aprendizRepository;
        $this->fichaRepository = $fichaRepository;
        $this->userRepository = $userRepository;
        $this->notificationService = $notificationService;
    }

    /**
     * Crear nueva solicitud de boleta de salida
     */
    public function crearSolicitud(array $data): array
    {
        $errors = $this->validarSolicitud($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $boletaId = $this->boletaRepository->create($data);
            $boleta = $this->boletaRepository->findById($boletaId);

            $this->notificationService->notificarNuevaSolicitud($boleta);

            return [
                'success' => true,
                'message' => 'Solicitud de boleta de salida creada exitosamente',
                'boleta_id' => $boletaId,
            ];
        } catch (\Exception $e) {
            error_log("Error creando boleta de salida: " . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Error al crear la solicitud. Por favor intente nuevamente.'],
            ];
        }
    }

    /**
     * Validar datos de solicitud
     */
    private function validarSolicitud(array $data): array
    {
        $errors = [];

        if (empty($data['aprendiz_id'])) {
            $errors[] = 'El aprendiz es requerido';
        } else {
            $aprendiz = $this->aprendizRepository->findById($data['aprendiz_id']);
            if (!$aprendiz) {
                $errors[] = 'Aprendiz no encontrado';
            }
        }

        if (empty($data['instructor_id'])) {
            $errors[] = 'Debe seleccionar un instructor';
        } else {
            $instructor = $this->userRepository->findById($data['instructor_id']);
            if (!$instructor || $instructor['rol'] !== 'instructor') {
                $errors[] = 'Instructor no válido';
            }
        }

        if (empty($data['tipo_salida']) || !in_array($data['tipo_salida'], ['temporal', 'definitiva'])) {
            $errors[] = 'Tipo de salida no válido';
        }

        $motivosValidos = [
            'cita_medica',
            'diligencias_electorales',
            'tramites_etapa_productiva',
            'requerimientos_laborales',
            'caso_fortuito',
            'representacion_sena',
            'diligencias_judiciales',
            'otro',
        ];

        if (empty($data['motivo']) || !in_array($data['motivo'], $motivosValidos)) {
            $errors[] = 'Motivo de salida no válido';
        }

        if ($data['motivo'] === 'otro' && empty(trim($data['motivo_otro'] ?? ''))) {
            $errors[] = 'Debe especificar el motivo cuando selecciona "Otro"';
        }

        if (empty($data['hora_salida_solicitada'])) {
            $errors[] = 'La hora de salida es requerida';
        } else {
            // Validar que la hora de salida sea futura (hora de Colombia: America/Bogota)
            date_default_timezone_set('America/Bogota');
            $ahora = new \DateTime();
            $hoy = $ahora->format('Y-m-d');
            
            // Crear DateTime con la hora solicitada
            $horaSalidaStr = $hoy . ' ' . $data['hora_salida_solicitada'];
            $horaSalida = \DateTime::createFromFormat('Y-m-d H:i', $horaSalidaStr);
            
            if ($horaSalida === false) {
                $errors[] = 'Formato de hora de salida inválido';
            } else {
                // Cambiar margen a 5 minutos
                $margenMinutos = 5;
                $ahoraConMargen = clone $ahora;
                $ahoraConMargen->modify("+{$margenMinutos} minutes");
                
                if ($horaSalida <= $ahoraConMargen) {
                    // Formato 12 horas con AM/PM
                    $horasActual = (int)$ahora->format('H');
                    $horas12 = $horasActual % 12;
                    if ($horas12 === 0) $horas12 = 12;
                    $periodo = $horasActual >= 12 ? 'PM' : 'AM';
                    $horaActualStr = $horas12 . ':' . $ahora->format('i') . ' ' . $periodo;
                    $errors[] = "La hora de salida debe ser al menos {$margenMinutos} minutos posterior a la hora actual ({$horaActualStr})";
                }
            }
        }

        if ($data['tipo_salida'] === 'temporal') {
            if (empty($data['hora_reingreso_solicitada'])) {
                $errors[] = 'La hora de reingreso es requerida para salidas temporales';
            } elseif ($data['hora_reingreso_solicitada'] <= $data['hora_salida_solicitada']) {
                $errors[] = 'La hora de reingreso debe ser posterior a la hora de salida';
            }
        }

        if ($this->boletaRepository->existeBoletaPendienteHoy($data['aprendiz_id'])) {
            $errors[] = 'Ya tienes una solicitud de boleta de salida activa para hoy';
        }

        return $errors;
    }

    /**
     * Aprobar boleta por instructor
     */
    public function aprobarPorInstructor(int $boletaId, int $instructorId): array
    {
        $boleta = $this->boletaRepository->findById($boletaId);
        
        if (!$boleta) {
            return ['success' => false, 'message' => 'Boleta no encontrada'];
        }

        if ($boleta['estado'] !== 'pendiente_instructor') {
            return ['success' => false, 'message' => 'Esta boleta no está pendiente de revisión'];
        }

        if ($boleta['instructor_id'] != $instructorId) {
            return ['success' => false, 'message' => 'No tiene permisos para aprobar esta boleta'];
        }

        try {
            $this->boletaRepository->updateEstado($boletaId, 'pendiente_admin', [
                'instructor_aprobado_por' => $instructorId,
                'instructor_aprobado_fecha' => date('Y-m-d H:i:s'),
            ]);

            $boletaActualizada = $this->boletaRepository->findById($boletaId);
            $this->notificationService->notificarAprobacionInstructor($boletaActualizada);

            return [
                'success' => true,
                'message' => 'Boleta aprobada y enviada a revisión administrativa',
            ];
        } catch (\Exception $e) {
            error_log("Error aprobando boleta: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al procesar la aprobación'];
        }
    }

    /**
     * Rechazar boleta por instructor
     */
    public function rechazarPorInstructor(int $boletaId, int $instructorId, string $motivo): array
    {
        if (empty(trim($motivo))) {
            return ['success' => false, 'message' => 'Debe proporcionar un motivo de rechazo'];
        }

        $boleta = $this->boletaRepository->findById($boletaId);
        
        if (!$boleta) {
            return ['success' => false, 'message' => 'Boleta no encontrada'];
        }

        if ($boleta['estado'] !== 'pendiente_instructor') {
            return ['success' => false, 'message' => 'Esta boleta no está pendiente de revisión'];
        }

        if ($boleta['instructor_id'] != $instructorId) {
            return ['success' => false, 'message' => 'No tiene permisos para rechazar esta boleta'];
        }

        try {
            $this->boletaRepository->updateEstado($boletaId, 'rechazada_instructor', [
                'instructor_aprobado_por' => $instructorId,
                'instructor_aprobado_fecha' => date('Y-m-d H:i:s'),
                'instructor_motivo_rechazo' => $motivo,
            ]);

            $boletaActualizada = $this->boletaRepository->findById($boletaId);
            $this->notificationService->notificarRechazo($boletaActualizada, 'instructor', $motivo);

            return [
                'success' => true,
                'message' => 'Boleta rechazada correctamente',
            ];
        } catch (\Exception $e) {
            error_log("Error rechazando boleta: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al procesar el rechazo'];
        }
    }

    /**
     * Aprobar boleta por admin
     */
    public function aprobarPorAdmin(int $boletaId, int $adminId): array
    {
        $boleta = $this->boletaRepository->findById($boletaId);
        
        if (!$boleta) {
            return ['success' => false, 'message' => 'Boleta no encontrada'];
        }

        if ($boleta['estado'] !== 'pendiente_admin') {
            return ['success' => false, 'message' => 'Esta boleta no está pendiente de revisión administrativa'];
        }

        try {
            $this->boletaRepository->updateEstado($boletaId, 'aprobada', [
                'admin_aprobado_por' => $adminId,
                'admin_aprobado_fecha' => date('Y-m-d H:i:s'),
            ]);

            $boletaActualizada = $this->boletaRepository->findById($boletaId);
            $this->notificationService->notificarAprobacionFinal($boletaActualizada);

            return [
                'success' => true,
                'message' => 'Boleta aprobada. Lista para validación en portería',
            ];
        } catch (\Exception $e) {
            error_log("Error aprobando boleta por admin: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al procesar la aprobación'];
        }
    }

    /**
     * Rechazar boleta por admin
     */
    public function rechazarPorAdmin(int $boletaId, int $adminId, string $motivo): array
    {
        if (empty(trim($motivo))) {
            return ['success' => false, 'message' => 'Debe proporcionar un motivo de rechazo'];
        }

        $boleta = $this->boletaRepository->findById($boletaId);
        
        if (!$boleta) {
            return ['success' => false, 'message' => 'Boleta no encontrada'];
        }

        if ($boleta['estado'] !== 'pendiente_admin') {
            return ['success' => false, 'message' => 'Esta boleta no está pendiente de revisión administrativa'];
        }

        try {
            $this->boletaRepository->updateEstado($boletaId, 'rechazada_admin', [
                'admin_aprobado_por' => $adminId,
                'admin_aprobado_fecha' => date('Y-m-d H:i:s'),
                'admin_motivo_rechazo' => $motivo,
            ]);

            $boletaActualizada = $this->boletaRepository->findById($boletaId);
            $this->notificationService->notificarRechazo($boletaActualizada, 'admin', $motivo);

            return [
                'success' => true,
                'message' => 'Boleta rechazada correctamente',
            ];
        } catch (\Exception $e) {
            error_log("Error rechazando boleta por admin: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al procesar el rechazo'];
        }
    }

    /**
     * Validar salida física en portería
     */
    public function validarSalida(int $boletaId, int $porteroId): array
    {
        $boleta = $this->boletaRepository->findById($boletaId);
        
        if (!$boleta) {
            return ['success' => false, 'message' => 'Boleta no encontrada'];
        }

        if ($boleta['estado'] !== 'aprobada') {
            return ['success' => false, 'message' => 'Esta boleta no está aprobada para salida'];
        }

        try {
            $this->boletaRepository->registrarSalidaFisica($boletaId, $porteroId);

            if ($boleta['tipo_salida'] === 'definitiva') {
                $this->boletaRepository->completarSalidaDefinitiva($boletaId);
            }

            return [
                'success' => true,
                'message' => $boleta['tipo_salida'] === 'temporal' 
                    ? 'Salida registrada. Esperando reingreso' 
                    : 'Salida definitiva registrada correctamente',
            ];
        } catch (\Exception $e) {
            error_log("Error validando salida: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al registrar la salida'];
        }
    }

    /**
     * Validar reingreso físico en portería
     */
    public function validarReingreso(int $boletaId, int $porteroId, string $observaciones = null): array
    {
        $boleta = $this->boletaRepository->findById($boletaId);
        
        if (!$boleta) {
            return ['success' => false, 'message' => 'Boleta no encontrada'];
        }

        if ($boleta['estado'] !== 'validada_porteria') {
            return ['success' => false, 'message' => 'Esta boleta no tiene salida registrada'];
        }

        if ($boleta['tipo_salida'] !== 'temporal') {
            return ['success' => false, 'message' => 'Solo las salidas temporales requieren reingreso'];
        }

        try {
            $this->boletaRepository->registrarReingresoFisico($boletaId, $porteroId, $observaciones);

            return [
                'success' => true,
                'message' => 'Reingreso registrado correctamente',
            ];
        } catch (\Exception $e) {
            error_log("Error validando reingreso: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al registrar el reingreso'];
        }
    }

    /**
     * Obtener contadores para dashboard
     */
    public function getContadores(int $userId, string $rol): array
    {
        $contadores = [];

        if ($rol === 'instructor') {
            $contadores['pendientes'] = $this->boletaRepository->countPendientesByInstructor($userId);
        } elseif (in_array($rol, ['admin', 'administrativo'])) {
            $contadores['pendientes'] = $this->boletaRepository->countPendientesAdmin();
        } elseif ($rol === 'portero') {
            $contadores['salidas_aprobadas'] = $this->boletaRepository->countAprobadas();
            $contadores['reingresos_pendientes'] = $this->boletaRepository->countPendientesReingreso();
        }

        return $contadores;
    }
}
