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

        $qrImage = $cid 
            ? "<img src='cid:{$cid}' alt='Código QR' style='width:250px;height:250px;'>"
            : '<p style="color:#666;">El código QR no pudo ser generado.</p>';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #39A900 0%, #2d8a00 100%); padding: 30px; text-align: center;">
            <h1 style="color: white; margin: 0; font-size: 24px;">SENAttend Eventos</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0;">Sistema de Gestión de Eventos SENA</p>
        </div>
        
        <!-- Content -->
        <div style="padding: 30px;">
            <h2 style="color: #333; margin-top: 0;">Hola, {$nombre}</h2>
            
            <div style="background: #f8f9fa; border-left: 4px solid #39A900; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0;">
                <p style="margin: 0; color: #333;"><strong>Evento:</strong> {$evento}</p>
                <p style="margin: 10px 0 0 0; color: #39A900; font-weight: bold;">Tipo: QR de {$tipoTexto}</p>
            </div>
            
            <p style="color: #666; line-height: 1.6;">{$instruccion}</p>
            
            <!-- QR Code -->
            <div style="text-align: center; padding: 20px; background: #fafafa; border-radius: 8px; margin: 20px 0;">
                {$qrImage}
            </div>
            
            <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin-top: 20px;">
                <p style="margin: 0; color: #856404; font-size: 14px;">
                    <strong>⚠️ Importante:</strong> Este código QR es personal e intransferible. No lo compartas con otras personas.
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div style="background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;">
            <p style="color: #666; font-size: 12px; margin: 0;">
                Este es un correo automático del sistema SENAttend Eventos.<br>
                Por favor no responda a este mensaje.
            </p>
            <p style="color: #999; font-size: 11px; margin: 10px 0 0 0;">
                © 2024 SENA - Servicio Nacional de Aprendizaje
            </p>
        </div>
    </div>
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

