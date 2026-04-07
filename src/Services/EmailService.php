<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Servicio para envío de correos electrónicos
 * Utiliza PHPMailer para el envío de correos
 */
class EmailService
{
    private PHPMailer $mailer;
    private array $config;

    public function __construct()
    {
        $this->config = $this->loadConfig();
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    /**
     * Carga la configuración de correo desde variables de entorno
     */
    private function loadConfig(): array
    {
        return [
            'smtp_host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
            'smtp_port' => (int)(getenv('SMTP_PORT') ?: 587),
            'smtp_username' => getenv('SMTP_USERNAME') ?: '',
            'smtp_password' => getenv('SMTP_PASSWORD') ?: '',
            'smtp_encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
            'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'senattend@gmail.com',
            'from_name' => getenv('MAIL_FROM_NAME') ?: 'SENAttend - Sistema de Asistencia SENA',
        ];
    }

    /**
     * Adjunta el logo del SENA como imagen embebida
     */
    private function attachSenaLogo(): ?string
    {
        try {
            $logoPath = __DIR__ . '/../../public/images/logo_sena_blanco.png';
            
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
     * Configura el objeto PHPMailer
     */
    private function configureMailer(): void
    {
        try {
            // Configuración del servidor
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp_host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['smtp_username'];
            $this->mailer->Password = $this->config['smtp_password'];
            $this->mailer->SMTPSecure = $this->config['smtp_encryption'];
            $this->mailer->Port = $this->config['smtp_port'];
            $this->mailer->CharSet = 'UTF-8';

            // Remitente
            $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);

            // Configuración adicional
            $this->mailer->isHTML(true);
        } catch (Exception $e) {
            error_log("Error configuring mailer: " . $e->getMessage());
        }
    }

    /**
     * Envía un código QR por correo electrónico
     */
    public function enviarCodigoQR(string $email, string $nombreAprendiz, string $qrData, string $qrImageBase64 = null): array
    {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'El correo electrónico no es válido'
                ];
            }

            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($email, $nombreAprendiz);

            $this->mailer->Subject = 'Tu código QR de asistencia - SENAttend';

            $logoCid = $this->attachSenaLogo();

            $cid = null;
            if ($qrImageBase64 && !empty(trim($qrImageBase64))) {
                try {
                    $imageData = base64_decode($qrImageBase64, true);
                    if ($imageData !== false && !empty($imageData)) {
                        $cid = 'qr_code_' . uniqid();
                        
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
                    error_log("Error attaching QR image: " . $e->getMessage());
                }
            }

            $this->mailer->Body = $this->generateQREmailBody($nombreAprendiz, $qrData, $qrImageBase64, $cid, $logoCid);
            $this->mailer->AltBody = $this->generateQREmailPlainText($nombreAprendiz, $qrData);

            $this->mailer->send();

            return [
                'success' => true,
                'message' => 'Código QR enviado exitosamente a ' . $email
            ];
        } catch (Exception $e) {
            error_log("Error sending QR email: " . $this->mailer->ErrorInfo);
            return [
                'success' => false,
                'message' => 'Error al enviar el correo: ' . $this->mailer->ErrorInfo
            ];
        }
    }

    /**
     * Genera el cuerpo HTML del correo con el código QR
     */
    private function generateQREmailBody(string $nombreAprendiz, string $qrData, ?string $qrImageBase64, ?string $cid = null, ?string $logoCid = null): string
    {
        $logoHtml = '';
        if ($logoCid) {
            $logoHtml = '<div style="text-align: center; margin-bottom: 15px;">
                <img src="cid:' . htmlspecialchars($logoCid) . '" alt="Logo SENA" style="max-width: 150px; height: auto;">
            </div>';
        }

        $qrImageTag = '';
        if ($cid) {
            $qrImageTag = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin: 0 auto;">
                <tr>
                    <td align="center" style="padding: 0;">
                        <img src="cid:' . htmlspecialchars($cid) . '" alt="Código QR" style="max-width: 300px; width: 100%; height: auto; margin: 0 auto; border: 1px solid #ddd; padding: 15px; background: white; border-radius: 8px; display: block; box-sizing: border-box;">
                    </td>
                </tr>
            </table>';
        } elseif ($qrImageBase64 && !empty(trim($qrImageBase64))) {
            $qrImageBase64 = trim($qrImageBase64);
            $decoded = @base64_decode($qrImageBase64, true);
            if ($decoded !== false && strpos($decoded, '<svg') !== false) {
                $svgEncoded = base64_encode($decoded);
                $qrImageTag = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin: 0 auto;">
                    <tr>
                        <td align="center" style="padding: 0;">
                            <img src="data:image/svg+xml;base64,' . $svgEncoded . '" alt="Código QR" style="max-width: 300px; width: 100%; height: auto; margin: 0 auto; border: 1px solid #ddd; padding: 15px; background: white; border-radius: 8px; display: block; box-sizing: border-box;">
                        </td>
                    </tr>
                </table>';
            } else {
                if (strlen($qrImageBase64) > 100) {
                    $qrImageTag = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin: 0 auto;">
                        <tr>
                            <td align="center" style="padding: 0;">
                                <img src="data:image/png;base64,' . $qrImageBase64 . '" alt="Código QR" style="max-width: 300px; width: 100%; height: auto; margin: 0 auto; border: 1px solid #ddd; padding: 15px; background: white; border-radius: 8px; display: block; box-sizing: border-box;">
                            </td>
                        </tr>
                    </table>';
                } else {
                    $qrImageTag = '<div style="background: #f0f0f0; padding: 20px; text-align: center; margin: 20px 0; border: 2px dashed #ccc;">
                        <p style="margin: 0; font-family: monospace; font-size: 14px; word-break: break-all;">' . htmlspecialchars($qrData) . '</p>
                        <p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">Escanea este código con una app de QR</p>
                    </div>';
                }
            }
        } else {
            $qrImageTag = '<div style="background: #f0f0f0; padding: 20px; text-align: center; margin: 20px 0; border: 2px dashed #ccc;">
                <p style="margin: 0; font-family: monospace; font-size: 14px; word-break: break-all;">' . htmlspecialchars($qrData) . '</p>
                <p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">Escanea este código con una app de QR o genera el QR desde el sistema</p>
            </div>';
        }

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; width: 100%;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; background-color: #f4f4f4;">
                <tr>
                    <td align="center" style="padding: 20px 0;">
                        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff;">
                            <tr>
                                <td style="background-color: #39A900; color: white; padding: 20px; text-align: center;">
                                    ' . $logoHtml . '
                                    <h1 style="margin: 0; padding: 0; font-size: 24px;">SENAttend</h1>
                                    <p style="margin: 10px 0 0 0; padding: 0; font-size: 14px;">Sistema de Asistencia SENA</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 20px; background-color: #f9f9f9;">
                                    <h2 style="margin: 0 0 15px 0; font-size: 20px;">Hola ' . htmlspecialchars($nombreAprendiz) . ',</h2>
                                    <p style="margin: 0 0 15px 0; font-size: 14px;">Se ha generado tu código QR de asistencia. Este código tiene una validez de <strong>3 minutos</strong> desde su generación.</p>
                                    
                                    <div style="text-align: center; margin: 15px auto; max-width: 300px;">
                                        ' . $qrImageTag . '
                                    </div>

                                    <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
                                        <strong>⚠️ Importante:</strong>
                                        <ul style="margin: 10px 0 0 20px; padding: 0;">
                                            <li style="margin: 5px 0;">Este código QR expira en <strong>3 minutos</strong></li>
                                            <li style="margin: 5px 0;">Una vez escaneado, el código se invalidará</li>
                                            <li style="margin: 5px 0;">No compartas este código con otras personas</li>
                                        </ul>
                                    </div>

                                    <p style="margin: 0 0 15px 0; font-size: 14px;">Presenta este código QR a tu instructor para registrar tu asistencia.</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center; padding: 20px; font-size: 12px; color: #666;">
                                    <p style="margin: 5px 0;">Este es un correo automático, por favor no respondas.</p>
                                    <p style="margin: 5px 0;">&copy; ' . date('Y') . ' SENA - Servicio Nacional de Aprendizaje</p>
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
     * Genera la versión en texto plano del correo
     */
    private function generateQREmailPlainText(string $nombreAprendiz, string $qrData): string
    {
        return "Hola {$nombreAprendiz},\n\n" .
               "Se ha generado tu código QR de asistencia.\n\n" .
               "Datos del código: {$qrData}\n\n" .
               "IMPORTANTE: Este código expira en 3 minutos.\n\n" .
               "Presenta este código QR a tu instructor para registrar tu asistencia.\n\n" .
               "Este es un correo automático, por favor no respondas.\n\n" .
               "© " . date('Y') . " SENA - Servicio Nacional de Aprendizaje" .
               $this->getLegalDisclaimerPlainText();
    }

    /**
     * Envía un correo con token de recuperación de contraseña
     */
    public function enviarTokenRecuperacion(string $email, string $nombre, string $token): array
    {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'El correo electrónico no es válido'
                ];
            }

            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($email, $nombre);

            $this->mailer->Subject = 'Recuperación de contraseña - SENAttend';

            $logoCid = $this->attachSenaLogo();

            $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
                . "://" . $_SERVER['HTTP_HOST'] . "/password/reset?token=" . urlencode($token);

            $this->mailer->Body = $this->generatePasswordResetEmailBody($nombre, $resetUrl, $token, $logoCid);
            $this->mailer->AltBody = $this->generatePasswordResetEmailPlainText($nombre, $resetUrl);

            $this->mailer->send();

            return [
                'success' => true,
                'message' => 'Correo de recuperación enviado exitosamente'
            ];
        } catch (Exception $e) {
            error_log("Error sending password reset email: " . $this->mailer->ErrorInfo);
            return [
                'success' => false,
                'message' => 'Error al enviar el correo: ' . $this->mailer->ErrorInfo
            ];
        }
    }

    private function generatePasswordResetEmailBody(string $nombre, string $resetUrl, string $token, ?string $logoCid = null): string
    {
        $logoHtml = '';
        if ($logoCid) {
            $logoHtml = '<div style="text-align: center; margin-bottom: 15px;">
                <img src="cid:' . htmlspecialchars($logoCid) . '" alt="Logo SENA" style="max-width: 150px; height: auto;">
            </div>';
        }

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; width: 100%;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f4;">
                <tr>
                    <td align="center" style="padding: 20px 0;">
                        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff;">
                            <tr>
                                <td style="background-color: #39A900; color: white; padding: 20px; text-align: center;">
                                    ' . $logoHtml . '
                                    <h1 style="margin: 0;">SENAttend</h1>
                                    <p style="margin: 10px 0 0 0;">Sistema de Asistencia SENA</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 30px 20px;">
                                    <h2 style="margin: 0 0 20px 0; color: #333;">Recuperación de Contraseña</h2>
                                    <p>Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
                                    <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.</p>
                                    <p>Para continuar, haz clic en el siguiente botón:</p>
                                    <div style="text-align: center;">
                                        <a href="' . htmlspecialchars($resetUrl) . '" style="display: inline-block; padding: 12px 30px; margin: 20px 0; background-color: #39A900; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">Restablecer Contraseña</a>
                                    </div>
                                    <p>O copia y pega el siguiente enlace en tu navegador:</p>
                                    <div style="background-color: #f5f5f5; padding: 15px; margin: 20px 0; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; word-break: break-all;">
                                        <a href="' . htmlspecialchars($resetUrl) . '" style="color: #39A900; word-break: break-all;">' . htmlspecialchars($resetUrl) . '</a>
                                    </div>
                                    <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
                                        <strong>⚠️ Importante:</strong>
                                        <ul style="margin: 10px 0 0 20px; padding: 0;">
                                            <li>Este enlace es válido por <strong>1 hora</strong></li>
                                            <li>Si no solicitaste este cambio, ignora este correo</li>
                                            <li>Tu contraseña no cambiará hasta que accedas al enlace y completes el proceso</li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center; padding: 20px; font-size: 12px; color: #666;">
                                    <p>Este es un correo automático, por favor no respondas.</p>
                                    <p>&copy; ' . date('Y') . ' SENA - Servicio Nacional de Aprendizaje</p>
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

    private function generatePasswordResetEmailPlainText(string $nombre, string $resetUrl): string
    {
        return "Hola {$nombre},\n\n" .
               "Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.\n\n" .
               "Para continuar, accede al siguiente enlace:\n{$resetUrl}\n\n" .
               "IMPORTANTE:\n" .
               "- Este enlace es válido por 1 hora\n" .
               "- Si no solicitaste este cambio, ignora este correo\n" .
               "- Tu contraseña no cambiará hasta que accedas al enlace y completes el proceso\n\n" .
               "Este es un correo automático, por favor no respondas.\n\n" .
               "© " . date('Y') . " SENA - Servicio Nacional de Aprendizaje" .
               $this->getLegalDisclaimerPlainText();
    }

    /**
     * Envía un correo de prueba
     */
    public function enviarCorreoPrueba(string $email): array
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = 'Correo de prueba - SENAttend';
            $this->mailer->Body = '<h1>Correo de prueba</h1><p>Si recibes este correo, la configuración de email está funcionando correctamente.</p>';
            $this->mailer->AltBody = 'Correo de prueba - Si recibes este correo, la configuración de email está funcionando correctamente.';

            $this->mailer->send();

            return [
                'success' => true,
                'message' => 'Correo de prueba enviado exitosamente'
            ];
        } catch (Exception $e) {
            error_log("Error sending test email: " . $this->mailer->ErrorInfo);
            return [
                'success' => false,
                'message' => 'Error al enviar el correo de prueba: ' . $this->mailer->ErrorInfo
            ];
        }
    }
}

