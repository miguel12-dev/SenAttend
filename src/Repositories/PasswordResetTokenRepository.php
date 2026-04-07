<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;

class PasswordResetTokenRepository
{
    public function create(int $userId, string $userType, string $email, string $token, string $expiresAt): bool
    {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO password_reset_tokens (user_id, user_type, email, token, expires_at) 
                 VALUES (:user_id, :user_type, :email, :token, :expires_at)'
            );

            return $stmt->execute([
                'user_id' => $userId,
                'user_type' => $userType,
                'email' => $email,
                'token' => $token,
                'expires_at' => $expiresAt
            ]);
        } catch (PDOException $e) {
            error_log("Error creating password reset token: " . $e->getMessage());
            return false;
        }
    }

    public function findByToken(string $token): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT * FROM password_reset_tokens 
                 WHERE token = :token 
                 AND used = FALSE 
                 AND expires_at > NOW()
                 LIMIT 1'
            );

            $stmt->execute(['token' => $token]);
            $result = $stmt->fetch();

            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error finding token: " . $e->getMessage());
            return null;
        }
    }

    public function markAsUsed(string $token): bool
    {
        try {
            $stmt = Connection::prepare(
                'UPDATE password_reset_tokens 
                 SET used = TRUE 
                 WHERE token = :token'
            );

            return $stmt->execute(['token' => $token]);
        } catch (PDOException $e) {
            error_log("Error marking token as used: " . $e->getMessage());
            return false;
        }
    }

    public function deleteExpiredTokens(): int
    {
        try {
            $stmt = Connection::prepare(
                'DELETE FROM password_reset_tokens 
                 WHERE expires_at < NOW()'
            );

            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error deleting expired tokens: " . $e->getMessage());
            return 0;
        }
    }

    public function invalidateAllUserTokens(int $userId, string $userType): bool
    {
        try {
            $stmt = Connection::prepare(
                'UPDATE password_reset_tokens 
                 SET used = TRUE 
                 WHERE user_id = :user_id 
                 AND user_type = :user_type
                 AND used = FALSE'
            );

            return $stmt->execute([
                'user_id' => $userId,
                'user_type' => $userType
            ]);
        } catch (PDOException $e) {
            error_log("Error invalidating user tokens: " . $e->getMessage());
            return false;
        }
    }
}
