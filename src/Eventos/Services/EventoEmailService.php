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
            // Configuración del servidor
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['username'];
            $this->mailer->Password = $this->config['password'];
            $this->mailer->SMTPSecure = $this->config['encryption'];
            $this->mailer->Port = $this->config['port'];
            $this->mailer->CharSet = 'UTF-8';

            // Remitente
            $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);

            // Configuración adicional
            $this->mailer->isHTML(true);
            
            // Log de configuración (solo en desarrollo, sin mostrar password completo)
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
     * Envía el QR de ingreso al participante
     */
    public function enviarQRIngreso(
        string $email,
        string $nombreCompleto,
        string $eventoTitulo,
        string $qrImageBase64
    ): array {
        return $this->enviarQR($email, $nombreCompleto, $eventoTitulo, $qrImageBase64, 'ingreso');
    }

    /**
     * Envía el QR de salida al participante
     */
    public function enviarQRSalida(
        string $email,
        string $nombreCompleto,
        string $eventoTitulo,
        string $qrImageBase64
    ): array {
        return $this->enviarQR($email, $nombreCompleto, $eventoTitulo, $qrImageBase64, 'salida');
    }

    /**
     * Envía un email con código QR
     */
    private function enviarQR(
        string $email,
        string $nombreCompleto,
        string $eventoTitulo,
        string $qrImageBase64,
        string $tipo
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

            // Adjuntar imagen QR
            $cid = null;
            if (!empty($qrImageBase64)) {
                try {
                    // Decodificar la imagen
                    $imageData = base64_decode($qrImageBase64, true);
                    if ($imageData !== false && !empty($imageData)) {
                        // Crear un CID único para la imagen
                        $cid = 'qr_evento_' . uniqid();
                        
                        // Crear archivo temporal para la imagen
                        $tempFile = sys_get_temp_dir() . '/' . $cid . '.png';
                        file_put_contents($tempFile, $imageData);
                        
                        // Agregar como imagen embebida (igual que EmailService principal)
                        $this->mailer->addEmbeddedImage($tempFile, $cid, 'qr_code.png', 'base64', 'image/png');
                        
                        if (defined('APP_ENV') && (APP_ENV === 'local' || APP_ENV === 'development')) {
                            error_log("EventoEmailService: QR image attached as embedded image with CID: " . $cid . ", file size: " . strlen($imageData));
                        }
                        
                        // Limpiar archivo temporal después de enviar
                        register_shutdown_function(function() use ($tempFile) {
                            if (file_exists($tempFile)) {
                                @unlink($tempFile);
                            }
                        });
                    } else {
                        error_log("EventoEmailService: Failed to decode QR image base64");
                    }
                } catch (Exception $e) {
                    error_log("EventoEmailService: Error attaching QR image: " . $e->getMessage());
                }
            }

            $this->mailer->Body = $this->generarCuerpoEmail(
                $nombreCompleto, 
                $eventoTitulo, 
                $tipo, 
                $cid
            );
            $this->mailer->AltBody = $this->generarTextoPlano($nombreCompleto, $eventoTitulo, $tipo);

            $this->mailer->send();

            return [
                'success' => true,
                'message' => "QR de {$tipo} enviado exitosamente a {$email}"
            ];
        } catch (Exception $e) {
            $errorInfo = $this->mailer->ErrorInfo;
            error_log("EventoEmailService: Error enviando email: " . $e->getMessage());
            error_log("EventoEmailService: PHPMailer ErrorInfo: " . $errorInfo);
            error_log("EventoEmailService: Config SMTP - Host: " . ($this->config['host'] ?? 'N/A') . ", Port: " . ($this->config['port'] ?? 'N/A') . ", User: " . (empty($this->config['username']) ? 'VACÍO' : substr($this->config['username'], 0, 3) . '***'));
            
            return [
                'success' => false,
                'message' => 'Error al enviar el correo: ' . $errorInfo
            ];
        }
    }

    /**
     * Genera el cuerpo HTML del email
     */
    private function generarCuerpoEmail(
        string $nombre,
        string $evento,
        string $tipo,
        ?string $cid
    ): string {
        $tipoTexto = $tipo === 'ingreso' ? 'INGRESO' : 'SALIDA';
        $instruccion = $tipo === 'ingreso' 
            ? 'Presenta este código QR al ingresar al evento.'
            : 'Presenta este código QR al salir del evento para completar tu asistencia.';

        // Generar el tag de imagen QR con formato optimizado para móviles y clientes de correo
        $qrImageTag = '';
        if ($cid) {
            // Usar tabla HTML para mejor compatibilidad con clientes de correo (como en EmailService)
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

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Reset para clientes de correo */
        body, table, td, p, a, li, blockquote { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        
        body { 
            margin: 0 !important; 
            padding: 0 !important; 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            background-color: #f4f4f4;
            width: 100% !important;
        }
        
        .wrapper {
            width: 100% !important;
            table-layout: fixed;
            background-color: #f4f4f4;
            padding: 0;
            margin: 0;
        }
        
        .container { 
            max-width: 600px; 
            width: 100% !important;
            margin: 0 auto; 
            padding: 0;
            background-color: #ffffff;
        }
        
        .header { 
            background: linear-gradient(135deg, #39A900 0%, #2d8a00 100%); 
            color: white; 
            padding: 30px 20px; 
            text-align: center; 
            width: 100%;
            box-sizing: border-box;
        }
        
        .header h1 {
            margin: 0;
            padding: 0;
            font-size: 24px;
        }
        
        .header p {
            margin: 10px 0 0 0;
            padding: 0;
            font-size: 14px;
            color: rgba(255,255,255,0.9);
        }
        
        .content { 
            padding: 30px 20px; 
            background-color: #ffffff; 
            width: 100%;
            box-sizing: border-box;
        }
        
        .content h2 {
            margin: 0 0 15px 0;
            padding: 0;
            font-size: 20px;
            color: #333;
        }
        
        .content p {
            margin: 0 0 15px 0;
            padding: 0;
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }
        
        .evento-info {
            background: #f8f9fa;
            border-left: 4px solid #39A900;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
            width: 100%;
            box-sizing: border-box;
        }
        
        .evento-info p {
            margin: 0;
            padding: 0;
        }
        
        .evento-info .evento-nombre {
            color: #333;
            font-weight: normal;
        }
        
        .evento-info .evento-tipo {
            color: #39A900;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .qr-container { 
            text-align: center; 
            margin: 20px auto; 
            width: 100%;
            max-width: 300px;
            box-sizing: border-box;
            padding: 20px;
            background: #fafafa;
            border-radius: 8px;
        }
        
        .warning { 
            background-color: #fff3cd; 
            border: 1px solid #ffc107; 
            padding: 15px; 
            margin: 20px 0;
            border-radius: 8px;
            width: 100%;
            box-sizing: border-box;
        }
        
        .warning p {
            margin: 0;
            padding: 0;
            color: #856404;
            font-size: 14px;
        }
        
        .footer { 
            text-align: center; 
            padding: 20px; 
            font-size: 12px; 
            color: #666;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            width: 100%;
            box-sizing: border-box;
        }
        
        .footer p {
            margin: 5px 0;
            padding: 0;
        }
        
        .footer .copyright {
            color: #999;
            font-size: 11px;
            margin-top: 10px;
        }
        
        /* Media queries para móviles */
        @media only screen and (max-width: 600px) {
            .container {
                width: 100% !important;
                max-width: 100% !important;
            }
            
            .content {
                padding: 20px 15px !important;
            }
            
            .header {
                padding: 20px 15px !important;
            }
            
            .header h1 {
                font-size: 20px !important;
            }
            
            .qr-container {
                max-width: 100% !important;
                margin: 15px 0 !important;
                padding: 15px !important;
            }
            
            .footer {
                padding: 15px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; width: 100%;">
    <table role="presentation" class="wrapper" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin: 0; padding: 0; background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td class="header" style="background: linear-gradient(135deg, #39A900 0%, #2d8a00 100%); color: white; padding: 30px 20px; text-align: center; width: 100%; box-sizing: border-box;">
                            <h1 style="margin: 0; padding: 0; font-size: 24px;">SENAttend Eventos</h1>
                            <p style="margin: 10px 0 0 0; padding: 0; font-size: 14px; color: rgba(255,255,255,0.9);">Sistema de Gestión de Eventos SENA</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td class="content" style="padding: 30px 20px; background-color: #ffffff; width: 100%; box-sizing: border-box;">
                            <h2 style="margin: 0 0 15px 0; padding: 0; font-size: 20px; color: #333;">Hola, {$nombre}</h2>
                            
                            <div class="evento-info" style="background: #f8f9fa; border-left: 4px solid #39A900; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0; width: 100%; box-sizing: border-box;">
                                <p class="evento-nombre" style="margin: 0; padding: 0; color: #333; font-size: 14px;"><strong>Evento:</strong> {$evento}</p>
                                <p class="evento-tipo" style="margin: 10px 0 0 0; padding: 0; color: #39A900; font-weight: bold; font-size: 14px;">Tipo: QR de {$tipoTexto}</p>
                            </div>
                            
                            <p style="margin: 0 0 15px 0; padding: 0; font-size: 14px; color: #666; line-height: 1.6;">{$instruccion}</p>
                            
                            <!-- QR Code Container -->
                            <div class="qr-container" style="text-align: center; margin: 20px auto; width: 100%; max-width: 300px; box-sizing: border-box; padding: 20px; background: #fafafa; border-radius: 8px;">
                                {$qrImageTag}
                            </div>
                            
                            <div class="warning" style="background-color: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 8px; width: 100%; box-sizing: border-box;">
                                <p style="margin: 0; padding: 0; color: #856404; font-size: 14px;">
                                    <strong>⚠️ Importante:</strong> Este código QR es personal e intransferible. No lo compartas con otras personas.
                                </p>
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td class="footer" style="text-align: center; padding: 20px; font-size: 12px; color: #666; background: #f8f9fa; border-top: 1px solid #eee; width: 100%; box-sizing: border-box;">
                            <p style="margin: 5px 0; padding: 0;">
                                Este es un correo automático del sistema SENAttend Eventos.<br>
                                Por favor no responda a este mensaje.
                            </p>
                            <p class="copyright" style="color: #999; font-size: 11px; margin: 10px 0 0 0; padding: 0;">
                                © 2025 SENA - Servicio Nacional de Aprendizaje
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Genera texto plano del email
     */
    private function generarTextoPlano(string $nombre, string $evento, string $tipo): string
    {
        $tipoTexto = $tipo === 'ingreso' ? 'INGRESO' : 'SALIDA';
        return <<<TEXT
SENAttend Eventos - Sistema de Gestión de Eventos SENA

Hola, {$nombre}

Evento: {$evento}
Tipo: QR de {$tipoTexto}

Por favor, revisa el correo en formato HTML para ver tu código QR.

IMPORTANTE: Este código QR es personal e intransferible. No lo compartas con otras personas.

---
Este es un correo automático del sistema SENAttend Eventos.
Por favor no responda a este mensaje.

© 2024 SENA - Servicio Nacional de Aprendizaje
TEXT;
    }
}

