<?php

namespace App\Services;

use App\Repositories\PasswordResetTokenRepository;
use App\Repositories\UserRepository;
use App\Repositories\AprendizRepository;

class PasswordResetService
{
    private const TOKEN_EXPIRY_HOURS = 1;
    
    private PasswordResetTokenRepository $tokenRepository;
    private UserRepository $userRepository;
    private AprendizRepository $aprendizRepository;
    private EmailService $emailService;

    public function __construct(
        PasswordResetTokenRepository $tokenRepository,
        UserRepository $userRepository,
        AprendizRepository $aprendizRepository,
        EmailService $emailService
    ) {
        $this->tokenRepository = $tokenRepository;
        $this->userRepository = $userRepository;
        $this->aprendizRepository = $aprendizRepository;
        $this->emailService = $emailService;
    }

    public function requestPasswordReset(string $emailOrDocument): array
    {
        $result = $this->findUserByEmailOrDocument($emailOrDocument);

        if (!$result) {
            return [
                'success' => true,
                'message' => 'Si existe una cuenta con ese correo o documento, recibirás un email con instrucciones'
            ];
        }

        $user = $result['user'];
        $userType = $result['type'];

        $token = $this->generateSecureToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRY_HOURS . ' hour'));

        $this->tokenRepository->invalidateAllUserTokens($user['id'], $userType);

        $created = $this->tokenRepository->create(
            $user['id'],
            $userType,
            $user['email'],
            $token,
            $expiresAt
        );

        if (!$created) {
            return [
                'success' => false,
                'message' => 'Error al crear el token de recuperación'
            ];
        }

        $nombre = $userType === 'aprendiz' 
            ? trim($user['nombre'] . ' ' . ($user['apellido'] ?? ''))
            : $user['nombre'];

        $emailResult = $this->emailService->enviarTokenRecuperacion(
            $user['email'],
            $nombre,
            $token
        );

        if (!$emailResult['success']) {
            error_log("Error enviando email de recuperación: " . $emailResult['message']);
        }

        return [
            'success' => true,
            'message' => 'Si existe una cuenta con ese correo o documento, recibirás un email con instrucciones'
        ];
    }

    public function validateToken(string $token): array
    {
        $tokenData = $this->tokenRepository->findByToken($token);

        if (!$tokenData) {
            return [
                'valid' => false,
                'message' => 'El token es inválido o ha expirado'
            ];
        }

        return [
            'valid' => true,
            'user_id' => $tokenData['user_id'],
            'user_type' => $tokenData['user_type'],
            'email' => $tokenData['email']
        ];
    }

    public function resetPassword(string $token, string $newPassword): array
    {
        $validation = $this->validateToken($token);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }

        if (strlen($newPassword) < 6) {
            return [
                'success' => false,
                'message' => 'La contraseña debe tener al menos 6 caracteres'
            ];
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

        $updated = false;
        if ($validation['user_type'] === 'usuario') {
            $updated = $this->userRepository->update(
                $validation['user_id'],
                ['password_hash' => $passwordHash]
            );
        } else {
            $updated = $this->aprendizRepository->update(
                $validation['user_id'],
                ['password_hash' => $passwordHash]
            );
        }

        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Error al actualizar la contraseña'
            ];
        }

        $this->tokenRepository->markAsUsed($token);

        return [
            'success' => true,
            'message' => 'Contraseña actualizada exitosamente'
        ];
    }

    private function findUserByEmailOrDocument(string $identifier): ?array
    {
        $identifier = trim($identifier);

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $usuario = $this->userRepository->findByEmail($identifier);
            if ($usuario) {
                return ['user' => $usuario, 'type' => 'usuario'];
            }

            $aprendiz = $this->aprendizRepository->findByEmail($identifier);
            if ($aprendiz) {
                return ['user' => $aprendiz, 'type' => 'aprendiz'];
            }
        } else {
            $usuario = $this->userRepository->findByDocumento($identifier);
            if ($usuario) {
                return ['user' => $usuario, 'type' => 'usuario'];
            }

            $aprendiz = $this->aprendizRepository->findByDocumento($identifier);
            if ($aprendiz) {
                return ['user' => $aprendiz, 'type' => 'aprendiz'];
            }
        }

        return null;
    }

    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function cleanExpiredTokens(): int
    {
        return $this->tokenRepository->deleteExpiredTokens();
    }
}
