<?php

namespace App\Eventos\Middleware;

use App\Session\SessionManager;

/**
 * Middleware de autenticación para el módulo de eventos
 * Sistema completamente aislado del middleware principal
 */
class EventoAuthMiddleware
{
    private SessionManager $session;
    private string $sessionPrefix = 'eventos_';

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    /**
     * Verifica si el usuario está autenticado en el módulo de eventos
     */
    public function handle(): bool
    {
        $this->session->start();

        if (!$this->session->has($this->sessionPrefix . 'user_id') || 
            !$this->session->has($this->sessionPrefix . 'authenticated')) {
            
            // Guardar URL intentada
            $this->session->set('eventos_intended_url', $_SERVER['REQUEST_URI'] ?? '/eventos');
            
            header('Location: /eventos/login');
            exit;
        }

        return true;
    }

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public function hasRole(string $role): bool
    {
        $userRole = $this->session->get($this->sessionPrefix . 'user_role');
        return $userRole === $role;
    }

    /**
     * Verifica si el usuario tiene alguno de los roles especificados
     */
    public function hasAnyRole(array $roles): bool
    {
        $userRole = $this->session->get($this->sessionPrefix . 'user_role');
        return in_array($userRole, $roles, true);
    }

    /**
     * Obtiene el usuario actual de la sesión de eventos
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->session->has($this->sessionPrefix . 'user_id')) {
            return null;
        }

        return [
            'id' => $this->session->get($this->sessionPrefix . 'user_id'),
            'email' => $this->session->get($this->sessionPrefix . 'user_email'),
            'nombre' => $this->session->get($this->sessionPrefix . 'user_nombre'),
            'rol' => $this->session->get($this->sessionPrefix . 'user_role')
        ];
    }

    /**
     * Verifica si está autenticado sin redireccionar
     */
    public function isAuthenticated(): bool
    {
        $this->session->start();
        return (bool) $this->session->get($this->sessionPrefix . 'authenticated', false);
    }

    /**
     * Requiere un rol específico para acceder
     */
    public function requireRole(string $role): void
    {
        if (!$this->hasRole($role)) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'No tienes permisos para acceder a este recurso']);
            exit;
        }
    }

    /**
     * Requiere alguno de los roles especificados
     */
    public function requireAnyRole(array $roles): void
    {
        if (!$this->hasAnyRole($roles)) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'No tienes permisos para acceder a este recurso']);
            exit;
        }
    }
}

