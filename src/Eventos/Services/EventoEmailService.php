<?php

namespace App\Eventos\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Servicio de envío de emails para el módulo de eventos
 * Principio de Responsabilidad Única: Solo maneja envío de emails de eventos
 */
class EventoEmailService
{
    private PHPMailer $mailer;
    private array $config;

    public function __construct()
    {
        $this->config = $this->loadConfig();
        $this->mailer = new PHPMailer(true);
        $this->configurarMailer();
    }

    /**
     * Carga la configuración de correo desde variables de entorno
     * Usa las mismas variables que el EmailService principal
     */
    private function loadConfig(): array
    {
        // Intentar leer desde $_ENV primero (cargado desde .env)
        $smtpHost = $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $smtpPort = (int)($_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 587);
        $smtpUsername = $_ENV['SMTP_USERNAME'] ?? getenv('SMTP_USERNAME') ?: '';
        $smtpPassword = $_ENV['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?: '';
        $smtpEncryption = $_ENV['SMTP_ENCRYPTION'] ?? getenv('SMTP_ENCRYPTION') ?: 'tls';
        $fromEmail = $_ENV['MAIL_FROM_EMAIL'] ?? getenv('MAIL_FROM_EMAIL') ?: 'noreply@sena.edu.co';
        $fromName = $_ENV['MAIL_FROM_NAME'] ?? getenv('MAIL_FROM_NAME') ?: 'SENAttend Eventos';
        
        return [
            'host' => $smtpHost,
            'port' => $smtpPort,
            'username' => $smtpUsername,
            'password' => $smtpPassword,
            'encryption' => $smtpEncryption,
            'from_email' => $fromEmail,
            'from_name' => $fromName
        ];
    }

    /**
     * Configura el mailer con SMTP
     */
    private function configurarMailer(): void
    {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['username'];
            $this->mailer->Password = $this->config['password'];
            $this->mailer->SMTPSecure = $this->config['encryption'];
            $this->mailer->Port = $this->config['port'];
            $this->mailer->CharSet = 'UTF-8';

            $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);

            $this->mailer->isHTML(true);
            
            if (defined('APP_ENV') && (APP_ENV === 'local' || APP_ENV === 'development')) {
                error_log("EventoEmailService: Config SMTP - Host: {$this->config['host']}, Port: {$this->config['port']}, User: " . 
                    (empty($this->config['username']) ? 'VACÍO' : substr($this->config['username'], 0, 5) . '***') . 
                    ", Pass: " . (empty($this->config['password']) ? 'VACÍO' : '***') . 
                    ", Encryption: {$this->config['encryption']}");
            }
        } catch (Exception $e) {
            error_log("EventoEmailService: Error configurando mailer: " . $e->getMessage());
        }
    }

    /**
     * Adjunta el logo del SENA como imagen embebida
     */
    private function attachSenaLogo(): ?string
    {
        try {
            $logoPath = __DIR__ . '/../../../public/images/logo_sena_blanco.png';
            
            if (!file_exists($logoPath)) {
                error_log("Logo SENA no encontrado en: {$logoPath}");
                return null;
            }

            $cid = 'logo_sena_' . uniqid();
            $this->mailer->addEmbeddedImage($logoPath, $cid, 'logo_sena_blanco.png', 'base64', 'image/png');
            
            return $cid;
        } catch (Exception $e) {
            error_log("Error adjuntando logo SENA: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Genera el mensaje legal del SENA
     */
    private function getLegalDisclaimer(): string
    {
        return '<div style="background-color: #f8f9fa; border-top: 2px solid #dee2e6; padding: 20px; margin-top: 20px; font-size: 11px; color: #6c757d; line-height: 1.5;">
            <p style="margin: 0 0 10px 0; font-weight: bold; color: #495057;">Aviso Legal:</p>
            <p style="margin: 0 0 10px 0; text-align: justify;">
                Este mensaje y cualquier archivo adjunto pueden contener información pública clasificada y/o reservada bajo custodia o propiedad del SENA, 
                destinada exclusivamente a su(s) destinatario(s). Dicha información debe ser utilizada únicamente para la finalidad con la que fue enviada 
                y en cumplimiento de la normativa aplicable.
            </p>
            <p style="margin: 0 0 10px 0; text-align: justify;">
                Si usted no es el destinatario autorizado o ha recibido este mensaje por error, le solicitamos que omita su contenido, informe de inmediato 
                al remitente por correo electrónico con copia a <a href="mailto:servicioalciudadano@sena.edu.co" style="color: #39A900;">servicioalciudadano@sena.edu.co</a> 
                y elimine el mensaje. La retención, difusión, distribución o copia de este mensaje está prohibida y puede acarrear sanciones legales.
            </p>
            <p style="margin: 0; text-align: justify;">
                Para más información, consulte nuestras Políticas de Seguridad y Privacidad de la Información y las Políticas de Tratamiento para la 
                Protección de Datos Personales, disponibles en el sitio web del SENA.
            </p>
        </div>';
    }

    /**
     * Genera el texto plano del mensaje legal
     */
    private function getLegalDisclaimerPlainText(): string
    {
        return "\n\nAVISO LEGAL:\n\n" .
               "Este mensaje y cualquier archivo adjunto pueden contener información pública clasificada y/o reservada bajo custodia o propiedad del SENA, " .
               "destinada exclusivamente a su(s) destinatario(s). Dicha información debe ser utilizada únicamente para la finalidad con la que fue enviada " .
               "y en cumplimiento de la normativa aplicable.\n\n" .
               "Si usted no es el destinatario autorizado o ha recibido este mensaje por error, le solicitamos que omita su contenido, informe de inmediato " .
               "al remitente por correo electrónico con copia a servicioalciudadano@sena.edu.co y elimine el mensaje. La retención, difusión, distribución " .
               "o copia de este mensaje está prohibida y puede acarrear sanciones legales.\n\n" .
               "Para más información, consulte nuestras Políticas de Seguridad y Privacidad de la Información y las Políticas de Tratamiento para la " .
               "Protección de Datos Personales, disponibles en el sitio web del SENA.";
    }

    /**
     * Envía el QR de ingreso al participante
     */
    public function enviarQRIngreso(
        string $email,
        string $nombreCompleto,
        string $eventoTitulo,
        string $qrImageBase64,
        array $eventoInfo = []
    ): array {
        return $this->enviarQR($email, $nombreCompleto, $eventoTitulo, $qrImageBase64, 'ingreso', $eventoInfo);
    }

    /**
     * Envía el QR de salida al participante
     */
    public function enviarQRSalida(
        string $email,
        string $nombreCompleto,
        string $eventoTitulo,
        string $qrImageBase64,
        array $eventoInfo = []
    ): array {
        return $this->enviarQR($email, $nombreCompleto, $eventoTitulo, $qrImageBase64, 'salida', $eventoInfo);
    }

    /**
     * Envía las credenciales de acceso a un nuevo usuario del módulo de eventos.
     */
    public function enviarCredencialesUsuarioEvento(
        string $email,
        string $nombreCompleto,
        string $passwordPlano,
        string $rol = 'administrativo',
        string $loginPath = '/eventos'
    ): array {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Email no válido'];
            }

            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            $this->mailer->addAddress($email, $nombreCompleto);
            $this->mailer->Subject = "Acceso al Módulo de Eventos | SENAttend";

            $logoCid = $this->attachSenaLogo();

            $baseUrl = $_ENV['APP_URL'] ?? getenv('APP_URL') ?? 'https://senattend.adso.pro';
            $loginUrl = $loginPath;
            if (!empty($baseUrl) && !preg_match('#^https?://#i', $loginPath)) {
                $loginUrl = rtrim($baseUrl, '/') . $loginPath;
            }

            $this->mailer->Body = $this->generarCuerpoCredenciales($nombreCompleto, $email, $passwordPlano, $rol, $loginUrl, $logoCid);
            $this->mailer->AltBody = $this->generarTextoPlanoCredenciales($nombreCompleto, $email, $passwordPlano, $rol, $loginUrl);

            $this->mailer->send();

            return [
                'success' => true,
                'message' => "Credenciales enviadas a {$email}"
            ];
        } catch (Exception $e) {
            $errorInfo = $this->mailer->ErrorInfo;
            error_log("EventoEmailService: Error enviando credenciales: " . $e->getMessage());
            error_log("EventoEmailService: PHPMailer ErrorInfo: " . $errorInfo);

            return [
                'success' => false,
                'message' => 'Error al enviar las credenciales: ' . $errorInfo
            ];
        }
    }

    /**
     * Envía un email con código QR
     * @param array $eventoInfo Información adicional del evento: ['descripcion', 'imagen_url', 'fecha_inicio', 'fecha_fin']
     */
    private function enviarQR(
        string $email,
        string $nombreCompleto,
        string $eventoTitulo,
        string $qrImageBase64,
        string $tipo,
        array $eventoInfo = []
    ): array {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Email no válido'];
            }

            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            $this->mailer->addAddress($email, $nombreCompleto);

            $tipoTexto = $tipo === 'ingreso' ? 'Ingreso' : 'Salida';
            $this->mailer->Subject = "QR de {$tipoTexto} - {$eventoTitulo} | SENAttend Eventos";

            $logoCid = $this->attachSenaLogo();

            $cid = null;
            if (!empty($qrImageBase64)) {
                try {
                    $imageData = base64_decode($qrImageBase64, true);
                    if ($imageData !== false && !empty($imageData)) {
                        $cid = 'qr_evento_' . uniqid();
                        
                        $tempFile = sys_get_temp_dir() . '/' . $cid . '.png';
                        file_put_contents($tempFile, $imageData);
                        
                        $this->mailer->addEmbeddedImage($tempFile, $cid, 'qr_code.png', 'base64', 'image/png');
                        
                        register_shutdown_function(function() use ($tempFile) {
                            if (file_exists($tempFile)) {
                                @unlink($tempFile);
                            }
                        });
                    }
                } catch (Exception $e) {
                    error_log("EventoEmailService: Error attaching QR image: " . $e->getMessage());
                }
            }

            $this->mailer->Body = $this->generarCuerpoEmail(
                $nombreCompleto, 
                $eventoTitulo, 
                $tipo, 
                $cid,
                $eventoInfo,
                $logoCid
            );
            $this->mailer->AltBody = $this->generarTextoPlano($nombreCompleto, $eventoTitulo, $tipo, $eventoInfo);

            $this->mailer->send();

            return [
                'success' => true,
                'message' => "QR de {$tipo} enviado exitosamente a {$email}"
            ];
        } catch (Exception $e) {
            $errorInfo = $this->mailer->ErrorInfo;
            error_log("EventoEmailService: Error enviando email: " . $e->getMessage());
            error_log("EventoEmailService: PHPMailer ErrorInfo: " . $errorInfo);
            
            return [
                'success' => false,
                'message' => 'Error al enviar el correo: ' . $errorInfo
            ];
        }
    }

    /**
     * Genera el cuerpo HTML del email
     * @param array $eventoInfo Información adicional del evento
     */
    private function generarCuerpoEmail(
        string $nombre,
        string $evento,
        string $tipo,
        ?string $cid,
        array $eventoInfo = [],
        ?string $logoCid = null
    ): string {
        $tipoTexto = $tipo === 'ingreso' ? 'INGRESO' : 'SALIDA';
        $instruccion = $tipo === 'ingreso' 
            ? 'Presenta este código QR al ingresar al evento.'
            : 'Presenta este código QR al salir del evento para completar tu asistencia.';

        $logoHtml = '';
        if ($logoCid) {
            $logoHtml = '<div style="text-align: center; margin-bottom: 15px;">
                <img src="cid:' . htmlspecialchars($logoCid) . '" alt="Logo SENA" style="max-width: 150px; height: auto;">
            </div>';
        }

        $descripcion = $eventoInfo['descripcion'] ?? '';
        $imagenUrl = $eventoInfo['imagen_url'] ?? '';
        $fechaInicio = isset($eventoInfo['fecha_inicio']) ? date('d/m/Y H:i', strtotime($eventoInfo['fecha_inicio'])) : '';
        $fechaFin = isset($eventoInfo['fecha_fin']) ? date('d/m/Y H:i', strtotime($eventoInfo['fecha_fin'])) : '';
        $horaInicio = isset($eventoInfo['fecha_inicio']) ? date('h:i A', strtotime($eventoInfo['fecha_inicio'])) : '';
        $horaFin = isset($eventoInfo['fecha_fin']) ? date('h:i A', strtotime($eventoInfo['fecha_fin'])) : '';

        $qrImageTag = '';
        if ($cid) {
            $qrImageTag = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin: 0 auto;">
                <tr>
                    <td align="center" style="padding: 0;">
                        <img src="cid:' . htmlspecialchars($cid) . '" alt="Código QR" style="max-width: 300px; width: 100%; height: auto; margin: 0 auto; border: 1px solid #ddd; padding: 15px; background: white; border-radius: 8px; display: block; box-sizing: border-box;">
                    </td>
                </tr>
            </table>';
        } else {
            $qrImageTag = '<p style="color:#666; margin: 0; padding: 15px; text-align: center;">El código QR no pudo ser generado.</p>';
        }

        $eventoImagenTag = '';
        if (!empty($imagenUrl)) {
            $eventoImagenTag = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin: 20px 0;">
                <tr>
                    <td align="center" style="padding: 0;">
                        <img src="' . htmlspecialchars($imagenUrl) . '" alt="' . htmlspecialchars($evento) . '" style="max-width: 100%; width: 100%; height: auto; border-radius: 8px; display: block; box-sizing: border-box;">
                    </td>
                </tr>
            </table>';
        }

        $descripcionHtml = '';
        if (!empty($descripcion)) {
            $descripcionHtml = '<p style="margin: 0 0 15px 0; padding: 0; color: #666; font-size: 14px; line-height: 1.6;">' . nl2br(htmlspecialchars($descripcion)) . '</p>';
        }

        $fechaHtml = '';
        if (!empty($fechaInicio) && !empty($fechaFin)) {
            $fechaHtml = '<p style="margin: 0 0 8px 0; padding: 0; color: #333; font-size: 14px;">
                <strong>Fecha:</strong> ' . htmlspecialchars($fechaInicio) . ' - ' . htmlspecialchars($fechaFin) . '
            </p>';
        }

        $horaHtml = '';
        if (!empty($horaInicio) && !empty($horaFin)) {
            $horaHtml = '<p style="margin: 0 0 8px 0; padding: 0; color: #333; font-size: 14px;">
                <strong>Horario:</strong> ' . htmlspecialchars($horaInicio) . ' - ' . htmlspecialchars($horaFin) . '
            </p>';
        }

        return '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; width: 100%;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden;">
                    <tr>
                        <td style="background: linear-gradient(135deg, #39A900 0%, #2d8a00 100%); color: white; padding: 30px 20px; text-align: center;">
                            ' . $logoHtml . '
                            <h1 style="margin: 0; padding: 0; font-size: 24px;">SENAttend Eventos</h1>
                            <p style="margin: 10px 0 0 0; padding: 0; font-size: 14px; color: rgba(255,255,255,0.9);">Sistema de Gestión de Eventos SENA</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px 20px;">
                            <h2 style="margin: 0 0 15px 0; font-size: 20px; color: #333;">Hola, ' . htmlspecialchars($nombre) . '</h2>
                            
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #333;">Te has inscrito exitosamente al siguiente evento:</p>
                            
                            ' . $eventoImagenTag . '
                            
                            <div style="background: #f8f9fa; border-left: 4px solid #39A900; padding: 20px; margin: 20px 0; border-radius: 0 8px 8px 0;">
                                <p style="margin: 0 0 10px 0; color: #333; font-size: 18px; font-weight: bold;">' . htmlspecialchars($evento) . '</p>
                                
                                ' . $descripcionHtml . '
                                
                                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
                                    ' . $fechaHtml . '
                                    ' . $horaHtml . '
                                    
                                    <p style="margin: 10px 0 0 0; color: #39A900; font-weight: bold; font-size: 14px;">
                                        Tipo: QR de ' . $tipoTexto . '
                                    </p>
                                </div>
                            </div>
                            
                            <p style="margin: 0 0 15px 0; font-size: 14px; color: #666;">' . $instruccion . '</p>
                            
                            <div style="text-align: center; margin: 20px auto; max-width: 300px; padding: 20px; background: #fafafa; border-radius: 8px;">
                                ' . $qrImageTag . '
                            </div>
                            
                            <div style="background-color: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 8px;">
                                <p style="margin: 0; color: #856404; font-size: 14px;">
                                    <strong>⚠️ Importante:</strong> Este código QR es personal e intransferible. No lo compartas con otras personas.
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: center; padding: 20px; font-size: 12px; color: #666; background: #f8f9fa; border-top: 1px solid #eee;">
                            <p style="margin: 5px 0;">Este es un correo automático del sistema SENAttend Eventos.</p>
                            <p style="margin: 5px 0;">Por favor no responda a este mensaje.</p>
                            <p style="color: #999; font-size: 11px; margin: 10px 0 0 0;">© ' . date('Y') . ' SENA - Servicio Nacional de Aprendizaje</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            ' . $this->getLegalDisclaimer() . '
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Genera texto plano del email
     */
    private function generarTextoPlano(string $nombre, string $evento, string $tipo, array $eventoInfo = []): string
    {
        $tipoTexto = $tipo === 'ingreso' ? 'INGRESO' : 'SALIDA';
        $descripcion = $eventoInfo['descripcion'] ?? '';
        $fechaInicio = isset($eventoInfo['fecha_inicio']) ? date('d/m/Y H:i', strtotime($eventoInfo['fecha_inicio'])) : '';
        $fechaFin = isset($eventoInfo['fecha_fin']) ? date('d/m/Y H:i', strtotime($eventoInfo['fecha_fin'])) : '';
        $horaInicio = isset($eventoInfo['fecha_inicio']) ? date('h:i A', strtotime($eventoInfo['fecha_inicio'])) : '';
        $horaFin = isset($eventoInfo['fecha_fin']) ? date('h:i A', strtotime($eventoInfo['fecha_fin'])) : '';
        
        $texto = "SENAttend Eventos - Sistema de Gestión de Eventos SENA\n\n";
        $texto .= "Hola, {$nombre}\n\n";
        $texto .= "Te has inscrito exitosamente al siguiente evento:\n\n";
        $texto .= "Evento: {$evento}\n";
        
        if (!empty($descripcion)) {
            $texto .= "Descripción: " . strip_tags($descripcion) . "\n";
        }
        
        if (!empty($fechaInicio) && !empty($fechaFin)) {
            $texto .= "Fecha: {$fechaInicio} - {$fechaFin}\n";
        }
        
        if (!empty($horaInicio) && !empty($horaFin)) {
            $texto .= "Horario: {$horaInicio} - {$horaFin}\n";
        }
        
        $texto .= "Tipo: QR de {$tipoTexto}\n\n";
        $texto .= "Por favor, revisa el correo en formato HTML para ver tu código QR.\n\n";
        $texto .= "IMPORTANTE: Este código QR es personal e intransferible. No lo compartas con otras personas.\n\n";
        $texto .= "---\n";
        $texto .= "Este es un correo automático del sistema SENAttend Eventos.\n";
        $texto .= "Por favor no responda a este mensaje.\n\n";
        $texto .= "© " . date('Y') . " SENA - Servicio Nacional de Aprendizaje";
        $texto .= $this->getLegalDisclaimerPlainText();
        
        return $texto;
    }

    /**
     * Genera el cuerpo HTML para credenciales de usuario de eventos.
     */
    private function generarCuerpoCredenciales(
        string $nombre,
        string $email,
        string $password,
        string $rol,
        string $loginUrl,
        ?string $logoCid = null
    ): string {
        $rolLabel = $rol === 'admin' ? 'Administrador' : 'Administrativo';

        $logoHtml = '';
        if ($logoCid) {
            $logoHtml = '<div style="text-align: center; margin-bottom: 15px;">
                <img src="cid:' . htmlspecialchars($logoCid) . '" alt="Logo SENA" style="max-width: 150px; height: auto;">
            </div>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background: #f4f4f4; color:#333;">
    <div style="width:100%; padding:20px 0; background:#f4f4f4;">
        <div style="max-width: 640px; margin:0 auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 6px 18px rgba(0,0,0,0.1);">
            <div style="background: linear-gradient(135deg, #39A900 0%, #2d8a00 100%); color:#fff; padding:24px; text-align:center;">
                {$logoHtml}
                <h1 style="margin:0; font-size:22px;">Acceso a Gestión de Eventos</h1>
                <p style="margin:8px 0 0 0; color: rgba(255,255,255,0.9); font-size:14px;">SENAttend</p>
            </div>
            <div style="padding:24px;">
                <span style="display:inline-block; padding:6px 12px; border-radius:20px; background:#eef6ff; color:#0b64c0; font-weight:600; font-size:12px; text-transform: uppercase; margin-bottom:12px;">Nuevo usuario {$rolLabel}</span>
                <h2 style="margin:0 0 10px 0; font-size:18px;">Hola, {$nombre}</h2>
                <p style="margin:0 0 12px 0; color:#4b5563;">Te han creado una cuenta para gestionar los Eventos SENA. Usa estas credenciales para ingresar.</p>
                <div style="margin:16px 0; padding:16px; border-radius:10px; background:#f0fff4; border:1px solid #c6f6d5;">
                    <strong>Credenciales de acceso</strong>
                    <div style="display:block; background:#fff; border:1px dashed #a0aec0; padding:10px; border-radius:8px; margin-top:8px; font-family: 'Courier New', monospace;">Usuario: {$email}<br>Contraseña: {$password}</div>
                    <p style="margin:10px 0 0 0; color:#22543d; font-size:13px;">Por seguridad, cambia tu contraseña al ingresar.</p>
                </div>
                <div style="background:#f8f9fa; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin:16px 0;">
                    <p style="margin:6px 0;"><strong>Rol asignado:</strong> {$rolLabel}</p>
                    <p style="margin:6px 0;"><strong>URL de ingreso:</strong> <a href="{$loginUrl}" style="color:#0b64c0;">{$loginUrl}</a></p>
                    <p style="margin:8px 0 0 0; color:#6b7280;">Si no solicitaste este acceso, contacta al administrador.</p>
                </div>
                <a href="{$loginUrl}" style="display:inline-block; padding:12px 18px; background:#39A900; color:#fff; border-radius:8px; text-decoration:none; font-weight:700;">Ir al módulo de eventos</a>
            </div>
            <div style="text-align:center; padding:18px; font-size:12px; color:#6b7280; background:#f8f9fa;">
                Correo automático de SENAttend Eventos. No responder.<br>
                © {$this->getCurrentYear()} SENA - Servicio Nacional de Aprendizaje
            </div>
            <div>
                {$this->getLegalDisclaimer()}
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getCurrentYear(): string
    {
        return date('Y');
    }

    /**
     * Genera texto plano para credenciales de usuario de eventos.
     */
    private function generarTextoPlanoCredenciales(
        string $nombre,
        string $email,
        string $password,
        string $rol,
        string $loginUrl
    ): string {
        $rolLabel = $rol === 'admin' ? 'Administrador' : 'Administrativo';

        $texto = "SENAttend - Acceso a Gestión de Eventos\n";
        $texto .= "Hola, {$nombre}\n\n";
        $texto .= "Se creó una cuenta para ti con rol {$rolLabel}.\n\n";
        $texto .= "Usuario: {$email}\n";
        $texto .= "Contraseña: {$password}\n";
        $texto .= "URL de ingreso: {$loginUrl}\n\n";
        $texto .= "Cambia tu contraseña al iniciar sesión.\n";
        $texto .= "---\n";
        $texto .= "Correo automático de SENAttend Eventos. No responder.\n";
        $texto .= "© " . date('Y') . " SENA - Servicio Nacional de Aprendizaje";
        $texto .= $this->getLegalDisclaimerPlainText();

        return $texto;
    }
}