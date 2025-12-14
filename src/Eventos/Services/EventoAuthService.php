<?php

namespace App\Eventos\Services;

use App\Eventos\Repositories\EventoUsuarioRepository;
use App\Session\SessionManager;

/**
 * Servicio de autenticación para el módulo de eventos
 * Sistema completamente aislado del login principal
 * Principio de Responsabilidad Única: Solo maneja autenticación de eventos
 */
class EventoAuthService
{
    private EventoUsuarioRepository $usuarioRepository;
    private SessionManager $session;
    private string $sessionPrefix = 'eventos_';

    public function __construct(
        EventoUsuarioRepository $usuarioRepository,
        SessionManager $session
    ) {
        $this->usuarioRepository = $usuarioRepository;
        $this->session = $session;
    }

    /**
     * Intenta autenticar un usuario del módulo de eventos
     */
    public function login(string $email, string $password): array|false
    {
        $user = $this->usuarioRepository->findByEmail($email);

        if (!$user) {
            return false;
        }

        if (!$user['activo']) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        unset($user['password_hash']);
        
        $this->createSession($user);

        return $user;
    }

    /**
     * Crea la sesión del usuario de eventos
     */
    private function createSession(array $user): void
    {
        $this->session->start();
        $this->session->regenerate();

        // Usar prefijo para no interferir con el sistema principal
        $this->session->set($this->sessionPrefix . 'user_id', $user['id']);
        $this->session->set($this->sessionPrefix . 'user_email', $user['email']);
        $this->session->set($this->sessionPrefix . 'user_nombre', $user['nombre']);
        $this->session->set($this->sessionPrefix . 'user_role', $user['rol']);
        $this->session->set($this->sessionPrefix . 'authenticated', true);
        $this->session->set($this->sessionPrefix . 'login_time', time());
    }

    /**
     * Cierra la sesión del usuario de eventos
     */
    public function logout(): void
    {
        $this->session->start();
        
        // Solo eliminar las claves del módulo de eventos
        $keysToRemove = [
            'user_id', 'user_email', 'user_nombre', 
            'user_role', 'authenticated', 'login_time'
        ];
        
        foreach ($keysToRemove as $key) {
            $this->session->remove($this->sessionPrefix . $key);
        }
    }

    /**
     * Obtiene el usuario actualmente autenticado en eventos
     */
    public function getCurrentUser(): ?array
    {
        $this->session->start();

        if (!$this->session->get($this->sessionPrefix . 'authenticated')) {
            return null;
        }

        $userId = $this->session->get($this->sessionPrefix . 'user_id');
        if (!$userId) {
            return null;
        }

        $user = $this->usuarioRepository->findById($userId);

        if (!$user || !$user['activo']) {
            $this->logout();
            return null;
        }

        return $user;
    }

    /**
     * Verifica si hay un usuario autenticado en eventos
     */
    public function isAuthenticated(): bool
    {
        $this->session->start();
        return (bool) $this->session->get($this->sessionPrefix . 'authenticated', false);
    }

    /**
     * Obtiene el rol del usuario actual de eventos
     */
    public function getCurrentUserRole(): ?string
    {
        $this->session->start();
        return $this->session->get($this->sessionPrefix . 'user_role');
    }

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public function hasRole(string $role): bool
    {
        return $this->getCurrentUserRole() === $role;
    }

    /**
     * Verifica si el usuario tiene alguno de los roles especificados
     */
    public function hasAnyRole(array $roles): bool
    {
        $userRole = $this->getCurrentUserRole();
        return $userRole && in_array($userRole, $roles, true);
    }

    /**
     * Registra un nuevo usuario de eventos
     */
    public function register(array $data): int
    {
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);

        return $this->usuarioRepository->create($data);
    }

    /**
     * Cambia la contraseña de un usuario
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->usuarioRepository->findByEmail(
            $this->session->get($this->sessionPrefix . 'user_email')
        );

        if (!$user) {
            return false;
        }

        if (!password_verify($currentPassword, $user['password_hash'])) {
            return false;
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->usuarioRepository->update($userId, ['password_hash' => $newHash]);
    }
}

