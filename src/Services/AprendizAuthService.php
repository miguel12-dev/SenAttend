<?php

namespace App\Services;

use App\Repositories\AprendizRepository;
use App\Session\SessionManager;

/**
 * Servicio de autenticación para Aprendices
 * Permite login usando email y contraseña basada en documento.
 */
class AprendizAuthService
{
    private AprendizRepository $aprendizRepository;
    private SessionManager $session;

    public function __construct(AprendizRepository $aprendizRepository, SessionManager $session)
    {
        $this->aprendizRepository = $aprendizRepository;
        $this->session = $session;
    }

    /**
     * Intenta autenticar un aprendiz por email y contraseña.
     * Retorna los datos del aprendiz sin el hash si tiene éxito, false si falla.
     */
    public function login(string $email, string $password): array|false
    {
        $aprendiz = $this->aprendizRepository->findByEmail($email);

        if (!$aprendiz || empty($aprendiz['password_hash']) || $aprendiz['estado'] !== 'activo') {
            return false;
        }

        if (!password_verify($password, $aprendiz['password_hash'])) {
            return false;
        }

        unset($aprendiz['password_hash']);

        $this->createSession($aprendiz);

        return $aprendiz;
    }

    /**
     * Crea la sesión específica de aprendiz (no interfiere con la de usuarios internos).
     */
    private function createSession(array $aprendiz): void
    {
        $this->session->start();
        $this->session->regenerate();

        $this->session->set('aprendiz_authenticated', true);
        $this->session->set('aprendiz_id', $aprendiz['id']);
        $this->session->set('aprendiz_email', $aprendiz['email']);
        $this->session->set('aprendiz_nombre', $aprendiz['nombre']);
        $this->session->set('aprendiz_apellido', $aprendiz['apellido']);
        $this->session->set('aprendiz_documento', $aprendiz['documento']);
        $this->session->set('aprendiz_login_time', time());
    }

    public function logout(): void
    {
        $this->session->start();
        $this->session->remove('aprendiz_authenticated');
        $this->session->remove('aprendiz_id');
        $this->session->remove('aprendiz_email');
        $this->session->remove('aprendiz_nombre');
        $this->session->remove('aprendiz_apellido');
        $this->session->remove('aprendiz_documento');
        $this->session->remove('aprendiz_login_time');

        // Compatibilidad: si la sesión fue creada por AuthService (sistema unificado),
        // limpia también las claves unificadas para evitar bucles de redirect.
        $this->session->remove('authenticated');
        $this->session->remove('user_id');
        $this->session->remove('user_email');
        $this->session->remove('user_nombre');
        $this->session->remove('user_role');
        $this->session->remove('user_documento');
        $this->session->remove('login_time');
    }

    public function isAuthenticated(): bool
    {
        $this->session->start();
        if ($this->session->get('aprendiz_authenticated', false)) {
            return true;
        }

        // Compatibilidad con el sistema de sesión unificado (AuthService)
        return (bool)(
            $this->session->get('authenticated', false)
            && $this->session->get('user_role') === 'aprendiz'
        );
    }

    public function getCurrentAprendiz(): ?array
    {
        $this->session->start();

        if (!$this->session->get('aprendiz_authenticated')) {
            // Compatibilidad con el sistema de sesión unificado (AuthService)
            $isUnifiedAprendiz = $this->session->get('authenticated', false)
                && $this->session->get('user_role') === 'aprendiz';

            if (!$isUnifiedAprendiz) {
                return null;
            }

            $id = $this->session->get('user_id');
            if (!$id) {
                return null;
            }

            $aprendiz = $this->aprendizRepository->findById((int)$id);
            if (!$aprendiz) {
                $this->logout();
                return null;
            }

            // Hydrate ficha_id para compatibilidad con AprendizBoletaController
            $fichas = $this->aprendizRepository->getFichas((int)$id);
            $fichaId = null;
            foreach ($fichas as $ficha) {
                $estado = $ficha['estado'] ?? null;
                if ($estado && in_array($estado, ['activo', 'activa'], true)) {
                    $fichaId = (int) ($ficha['id'] ?? 0);
                    break;
                }
            }
            if ($fichaId === null && !empty($fichas) && isset($fichas[0]['id'])) {
                $fichaId = (int) $fichas[0]['id'];
            }

            $aprendiz['ficha_id'] = $fichaId;
            return $aprendiz;
        }

        $id = $this->session->get('aprendiz_id');
        if (!$id) {
            return null;
        }

        $aprendiz = $this->aprendizRepository->findById((int)$id);
        if (!$aprendiz) {
            $this->logout();
            return null;
        }

        // Hydrate ficha_id para compatibilidad con AprendizBoletaController
        $fichas = $this->aprendizRepository->getFichas((int)$id);
        $fichaId = null;
        foreach ($fichas as $ficha) {
            $estado = $ficha['estado'] ?? null;
            if ($estado && in_array($estado, ['activo', 'activa'], true)) {
                $fichaId = (int) ($ficha['id'] ?? 0);
                break;
            }
        }
        if ($fichaId === null && !empty($fichas) && isset($fichas[0]['id'])) {
            $fichaId = (int) $fichas[0]['id'];
        }

        $aprendiz['ficha_id'] = $fichaId;
        return $aprendiz;
    }
}


