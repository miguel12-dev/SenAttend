<?php

namespace App\Eventos\Services;

use Exception;

/**
 * Servicio de cifrado para QR de eventos
 * Usa AES-256-GCM para cifrado seguro
 * Principio de Responsabilidad Única: Solo maneja cifrado/descifrado
 */
class EventoEncryptionService
{
    private string $encryptionKey;
    private string $cipher = 'aes-256-gcm';
    private int $tagLength = 16;

    public function __construct(?string $encryptionKey = null)
    {
        $this->encryptionKey = $encryptionKey ?? $this->getDefaultKey();
        
        if (strlen($this->encryptionKey) !== 32) {
            throw new Exception('La clave de cifrado debe tener exactamente 32 bytes (256 bits)');
        }
    }

    /**
     * Obtiene la clave de cifrado desde variables de entorno
     */
    private function getDefaultKey(): string
    {
        $key = getenv('EVENTOS_QR_ENCRYPTION_KEY');
        
        if ($key && strlen($key) === 32) {
            return $key;
        }

        // Clave por defecto para desarrollo (cambiar en producción)
        $defaultKey = 'SENA2024EVENTOSQRENCRYPTIONKEY32'; // 32 caracteres exactos
        
        error_log('ADVERTENCIA: Usando clave de cifrado por defecto para eventos.');
        
        return $defaultKey;
    }

    /**
     * Cifra los datos del QR
     */
    public function encrypt(array $data): string
    {
        try {
            $plaintext = json_encode($data, JSON_UNESCAPED_UNICODE);
            
            $ivLength = openssl_cipher_iv_length($this->cipher);
            if ($ivLength === false) {
                throw new Exception('No se pudo obtener la longitud del IV');
            }
            
            $iv = openssl_random_pseudo_bytes($ivLength);
            if ($iv === false) {
                throw new Exception('No se pudo generar el IV');
            }

            $tag = '';
            $encrypted = openssl_encrypt(
                $plaintext,
                $this->cipher,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                '',
                $this->tagLength
            );

            if ($encrypted === false || empty($tag)) {
                throw new Exception('Error al cifrar los datos');
            }

            $combined = $iv . $tag . $encrypted;
            return base64_encode($combined);
        } catch (Exception $e) {
            error_log('EventoEncryptionService::encrypt error: ' . $e->getMessage());
            throw new Exception('Error al cifrar los datos del QR: ' . $e->getMessage());
        }
    }

    /**
     * Descifra los datos del QR
     */
    public function decrypt(string $encryptedData): array
    {
        try {
            $combined = base64_decode($encryptedData, true);
            if ($combined === false) {
                throw new Exception('Datos cifrados inválidos (no es base64 válido)');
            }

            $ivLength = openssl_cipher_iv_length($this->cipher);
            if ($ivLength === false) {
                throw new Exception('No se pudo obtener la longitud del IV');
            }

            $iv = substr($combined, 0, $ivLength);
            $tag = substr($combined, $ivLength, $this->tagLength);
            $encrypted = substr($combined, $ivLength + $this->tagLength);

            if (strlen($iv) !== $ivLength || strlen($tag) !== $this->tagLength) {
                throw new Exception('Datos cifrados corruptos o incompletos');
            }

            $plaintext = openssl_decrypt(
                $encrypted,
                $this->cipher,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($plaintext === false) {
                throw new Exception('Error al descifrar. Verifique la clave de cifrado.');
            }

            $data = json_decode($plaintext, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
            }

            return $data;
        } catch (Exception $e) {
            error_log('EventoEncryptionService::decrypt error: ' . $e->getMessage());
            throw new Exception('Error al descifrar los datos del QR: ' . $e->getMessage());
        }
    }

    /**
     * Genera un token único
     */
    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}

