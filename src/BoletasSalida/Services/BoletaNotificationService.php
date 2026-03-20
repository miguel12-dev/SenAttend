<?php

namespace App\BoletasSalida\Services;

use App\Services\EmailService;

/**
 * Servicio de notificaciones por email para boletas de salida
 */
class BoletaNotificationService
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Notificar al instructor sobre nueva solicitud
     */
    public function notificarNuevaSolicitud(array $boleta): void
    {
        try {
            $email = $boleta['instructor_email'];
            $nombreInstructor = $boleta['instructor_nombre'];
            $nombreAprendiz = $boleta['aprendiz_nombre'] . ' ' . $boleta['aprendiz_apellido'];
            $documentoAprendiz = $boleta['aprendiz_documento'];
            $numeroFicha = $boleta['numero_ficha'];
            $tipoSalida = $boleta['tipo_salida'] === 'temporal' ? 'Temporal' : 'Definitiva';
            $motivoLabel = $this->getMotivLabel($boleta['motivo']);

            // Agregar descripción si el motivo es "otro"
            $motivoDetalle = '';
            if ($boleta['motivo'] === 'otro' && !empty($boleta['motivo_otro'])) {
                $motivoDetalle = "<p><strong>Descripción:</strong> " . htmlspecialchars($boleta['motivo_otro']) . "</p>";
            }

            $subject = 'Nueva solicitud de boleta de salida - SENAttend';
            
            $body = $this->generateEmailTemplate(
                $nombreInstructor,
                'Nueva Solicitud de Boleta de Salida',
                "
                <p>Se ha recibido una nueva solicitud de boleta de salida que requiere tu revisión:</p>
                
                <div style='background-color: #f9f9f9; padding: 20px; margin: 20px 0; border-left: 4px solid #39A900;'>
                    <h3 style='margin-top: 0; color: #39A900;'>Datos del Aprendiz</h3>
                    <p><strong>Nombre:</strong> {$nombreAprendiz}</p>
                    <p><strong>Documento:</strong> {$documentoAprendiz}</p>
                    <p><strong>Ficha:</strong> {$numeroFicha}</p>
                </div>

                <div style='background-color: #f9f9f9; padding: 20px; margin: 20px 0; border-left: 4px solid #0056b3;'>
                    <h3 style='margin-top: 0; color: #0056b3;'>Detalles de la Solicitud</h3>
                    <p><strong>Tipo de salida:</strong> {$tipoSalida}</p>
                    <p><strong>Motivo:</strong> {$motivoLabel}</p>
                    {$motivoDetalle}
                    <p><strong>Hora de salida solicitada:</strong> {$boleta['hora_salida_solicitada']}</p>
                    " . ($boleta['hora_reingreso_solicitada'] ? "<p><strong>Hora de reingreso solicitada:</strong> {$boleta['hora_reingreso_solicitada']}</p>" : "") . "
                </div>

                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . $this->getAppUrl() . "/instructor/boletas-salida' 
                       style='display: inline-block; padding: 15px 30px; background-color: #39A900; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                        Revisar Solicitud
                    </a>
                </div>

                <p style='color: #666; font-size: 14px;'>Por favor, revisa y procesa esta solicitud a la brevedad posible.</p>
                "
            );

            $this->sendEmail($email, $nombreInstructor, $subject, $body);
        } catch (\Exception $e) {
            error_log("Error enviando notificación de nueva solicitud: " . $e->getMessage());
        }
    }

    /**
     * Notificar a admin sobre aprobación de instructor
     */
    public function notificarAprobacionInstructor(array $boleta): void
    {
        try {
            $admins = $this->getAdminEmails();
            
            $nombreAprendiz = $boleta['aprendiz_nombre'] . ' ' . $boleta['aprendiz_apellido'];
            $documentoAprendiz = $boleta['aprendiz_documento'];
            $numeroFicha = $boleta['numero_ficha'];
            $tipoSalida = $boleta['tipo_salida'] === 'temporal' ? 'Temporal' : 'Definitiva';
            $motivoLabel = $this->getMotivLabel($boleta['motivo']);
            $instructorAprobador = $boleta['instructor_aprobador_nombre'];

            $subject = 'Boleta de salida pendiente de aprobación - SENAttend';

            foreach ($admins as $admin) {
                $body = $this->generateEmailTemplate(
                    $admin['nombre'],
                    'Boleta de Salida Pendiente de Aprobación',
                    "
                    <p>Una boleta de salida ha sido aprobada por el instructor y requiere tu aprobación administrativa:</p>
                    
                    <div style='background-color: #f9f9f9; padding: 20px; margin: 20px 0; border-left: 4px solid #39A900;'>
                        <h3 style='margin-top: 0; color: #39A900;'>Datos del Aprendiz</h3>
                        <p><strong>Nombre:</strong> {$nombreAprendiz}</p>
                        <p><strong>Documento:</strong> {$documentoAprendiz}</p>
                        <p><strong>Ficha:</strong> {$numeroFicha}</p>
                    </div>

                    <div style='background-color: #f9f9f9; padding: 20px; margin: 20px 0; border-left: 4px solid #0056b3;'>
                        <h3 style='margin-top: 0; color: #0056b3;'>Detalles de la Solicitud</h3>
                        <p><strong>Tipo de salida:</strong> {$tipoSalida}</p>
                        <p><strong>Motivo:</strong> {$motivoLabel}</p>
                        <p><strong>Instructor aprobador:</strong> {$instructorAprobador}</p>
                    </div>

                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . $this->getAppUrl() . "/admin/boletas-salida' 
                           style='display: inline-block; padding: 15px 30px; background-color: #39A900; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                            Revisar Solicitud
                        </a>
                    </div>
                    "
                );

                $this->sendEmail($admin['email'], $admin['nombre'], $subject, $body);
            }
        } catch (\Exception $e) {
            error_log("Error enviando notificación a admin: " . $e->getMessage());
        }
    }

    /**
     * Notificar al aprendiz sobre rechazo
     */
    public function notificarRechazo(array $boleta, string $rol, string $motivo): void
    {
        try {
            $aprendiz = $this->getAprendizData($boleta['aprendiz_id']);
            
            if (empty($aprendiz['email'])) {
                error_log("Aprendiz {$boleta['aprendiz_id']} no tiene email configurado");
                return;
            }

            $nombreAprendiz = $boleta['aprendiz_nombre'] . ' ' . $boleta['aprendiz_apellido'];
            $rolNombre = $rol === 'instructor' ? 'instructor' : 'administración';
            $tipoSalida = $boleta['tipo_salida'] === 'temporal' ? 'temporal' : 'definitiva';

            $subject = 'Solicitud de boleta de salida rechazada - SENAttend';
            
            $body = $this->generateEmailTemplate(
                $nombreAprendiz,
                'Solicitud de Boleta de Salida Rechazada',
                "
                <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #856404;'>⚠️ Solicitud Rechazada</h3>
                    <p>Tu solicitud de boleta de salida {$tipoSalida} ha sido rechazada por {$rolNombre}.</p>
                </div>

                <div style='background-color: #f9f9f9; padding: 20px; margin: 20px 0;'>
                    <h3 style='margin-top: 0;'>Motivo del rechazo:</h3>
                    <p style='font-style: italic;'>{$motivo}</p>
                </div>

                <p>Si tienes dudas sobre este rechazo, por favor comunícate con tu instructor o con la coordinación académica.</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . $this->getAppUrl() . "/aprendiz/boletas-salida' 
                       style='display: inline-block; padding: 15px 30px; background-color: #39A900; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                        Ver Mi Historial
                    </a>
                </div>
                "
            );

            $this->sendEmail($aprendiz['email'], $nombreAprendiz, $subject, $body);
        } catch (\Exception $e) {
            error_log("Error enviando notificación de rechazo: " . $e->getMessage());
        }
    }

    /**
     * Notificar al aprendiz sobre aprobación final
     */
    public function notificarAprobacionFinal(array $boleta): void
    {
        try {
            $aprendiz = $this->getAprendizData($boleta['aprendiz_id']);
            
            if (empty($aprendiz['email'])) {
                error_log("Aprendiz {$boleta['aprendiz_id']} no tiene email configurado");
                return;
            }

            $nombreAprendiz = $boleta['aprendiz_nombre'] . ' ' . $boleta['aprendiz_apellido'];
            $tipoSalida = $boleta['tipo_salida'] === 'temporal' ? 'temporal' : 'definitiva';
            $motivoLabel = $this->getMotivLabel($boleta['motivo']);

            $subject = 'Boleta de salida aprobada - SENAttend';
            
            $body = $this->generateEmailTemplate(
                $nombreAprendiz,
                '✅ Boleta de Salida Aprobada',
                "
                <div style='background-color: #d4edda; border-left: 4px solid #28a745; padding: 20px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #155724;'>¡Tu solicitud ha sido aprobada!</h3>
                    <p>Tu boleta de salida {$tipoSalida} ha sido aprobada por la administración.</p>
                </div>

                <div style='background-color: #f9f9f9; padding: 20px; margin: 20px 0;'>
                    <h3 style='margin-top: 0;'>Detalles de tu boleta:</h3>
                    <p><strong>Tipo de salida:</strong> {$tipoSalida}</p>
                    <p><strong>Motivo:</strong> {$motivoLabel}</p>
                    <p><strong>Hora de salida solicitada:</strong> {$boleta['hora_salida_solicitada']}</p>
                    " . ($boleta['hora_reingreso_solicitada'] ? "<p><strong>Hora de reingreso solicitada:</strong> {$boleta['hora_reingreso_solicitada']}</p>" : "") . "
                </div>

                <div style='background-color: #d1ecf1; border-left: 4px solid #0c5460; padding: 20px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #0c5460;'>Próximos pasos:</h3>
                    <p>Debes presentarte en portería con tu documento de identidad para registrar tu salida del CTA.</p>
                    " . ($boleta['tipo_salida'] === 'temporal' ? "<p><strong>Recuerda:</strong> Debes reingresar antes de la hora indicada y volver a registrarte en portería.</p>" : "") . "
                </div>
                "
            );

            $this->sendEmail($aprendiz['email'], $nombreAprendiz, $subject, $body);
        } catch (\Exception $e) {
            error_log("Error enviando notificación de aprobación final: " . $e->getMessage());
        }
    }

    /**
     * Generar template HTML para email
     */
    private function generateEmailTemplate(string $nombre, string $titulo, string $contenido): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4;'>
            <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color: #f4f4f4;'>
                <tr>
                    <td align='center' style='padding: 20px 0;'>
                        <table role='presentation' width='600' cellpadding='0' cellspacing='0' border='0' style='max-width: 600px; width: 100%; background-color: #ffffff;'>
                            <tr>
                                <td style='background-color: #39A900; color: white; padding: 20px; text-align: center;'>
                                    <h1 style='margin: 0; font-size: 24px;'>SENAttend</h1>
                                    <p style='margin: 10px 0 0 0; font-size: 14px;'>Sistema de Gestión de Boletas de Salida</p>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 30px 20px;'>
                                    <h2 style='margin: 0 0 20px 0; color: #333;'>{$titulo}</h2>
                                    <p>Hola <strong>{$nombre}</strong>,</p>
                                    {$contenido}
                                </td>
                            </tr>
                            <tr>
                                <td style='text-align: center; padding: 20px; font-size: 12px; color: #666;'>
                                    <p>Este es un correo automático, por favor no respondas.</p>
                                    <p>&copy; " . date('Y') . " SENA - Servicio Nacional de Aprendizaje</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }

    /**
     * Obtener emails de administradores
     */
    private function getAdminEmails(): array
    {
        try {
            $stmt = \App\Database\Connection::prepare("SELECT nombre, email FROM usuarios WHERE rol IN ('admin', 'administrativo') AND email IS NOT NULL");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error obteniendo emails de admins: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener datos del aprendiz
     */
    private function getAprendizData(int $aprendizId): array
    {
        try {
            $stmt = \App\Database\Connection::prepare("SELECT nombre, apellido, email FROM aprendices WHERE id = :id");
            $stmt->execute([':id' => $aprendizId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            error_log("Error obteniendo datos del aprendiz: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener label del motivo
     */
    private function getMotivLabel(string $motivo): string
    {
        $motivos = [
            'cita_medica' => 'Cita / Incapacidad médica',
            'diligencias_electorales' => 'Diligencias electorales / Gubernamentales',
            'tramites_etapa_productiva' => 'Trámites etapa productiva',
            'requerimientos_laborales' => 'Requerimientos laborales',
            'caso_fortuito' => 'Casos fortuitos / Fuerza mayor',
            'representacion_sena' => 'Representación SENA (Académica, Cultural, Deportiva)',
            'diligencias_judiciales' => 'Diligencias judiciales',
            'otro' => 'Otro',
        ];

        return $motivos[$motivo] ?? $motivo;
    }

    /**
     * Obtener URL de la aplicación
     */
    private function getAppUrl(): string
    {
        return getenv('APP_URL') ?: 'https://senattend.adso.pro';
    }

    /**
     * Enviar email usando EmailService
     */
    private function sendEmail(string $email, string $nombre, string $subject, string $body): void
    {
        $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            $mailer->isSMTP();
            $mailer->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $mailer->SMTPAuth = true;
            $mailer->Username = getenv('SMTP_USERNAME');
            $mailer->Password = getenv('SMTP_PASSWORD');
            $mailer->SMTPSecure = getenv('SMTP_ENCRYPTION') ?: 'tls';
            $mailer->Port = (int)(getenv('SMTP_PORT') ?: 587);
            $mailer->CharSet = 'UTF-8';

            $mailer->setFrom(
                getenv('MAIL_FROM_EMAIL') ?: 'senattend@gmail.com',
                getenv('MAIL_FROM_NAME') ?: 'SENAttend - Sistema de Asistencia SENA'
            );

            $mailer->addAddress($email, $nombre);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $body;

            $mailer->send();
        } catch (\Exception $e) {
            error_log("Error enviando email: " . $mailer->ErrorInfo);
        }
    }
}
