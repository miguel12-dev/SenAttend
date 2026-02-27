<?php

namespace App\Eventos\Controllers;

use App\Eventos\Services\EventoAuthService;
use App\Session\SessionManager;

/**
 * Controlador de autenticación para el módulo de eventos
 */
class EventoAuthController
{
    private EventoAuthService $authService;
    private SessionManager $session;

    public function __construct(
        EventoAuthService $authService,
        SessionManager $session
    ) {
        $this->authService = $authService;
        $this->session = $session;
    }

    /**
     * Muestra el formulario de login
     */
    public function showLoginForm(): void
    {
        // Si ya está autenticado, redirigir al dashboard
        if ($this->authService->isAuthenticated()) {
            header('Location: /eventos/admin');
            exit;
        }

        $this->session->start();
        $error = $this->session->get('eventos_login_error');
        $this->session->remove('eventos_login_error');

        require ROOT_PATH . '/views/eventos/auth/login.php';
    }

    /**
     * Procesa el login
     */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /eventos/login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validaciones básicas
        if (empty($email) || empty($password)) {
            $this->setLoginError('Por favor, complete todos los campos');
            header('Location: /eventos/login');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->setLoginError('El correo electrónico no es válido');
            header('Location: /eventos/login');
            exit;
        }

        // Intentar login
        $user = $this->authService->login($email, $password);

        if (!$user) {
            $this->setLoginError('Credenciales incorrectas');
            header('Location: /eventos/login');
            exit;
        }

        // Redirigir a la URL intentada o al dashboard
        $this->session->start();
        $intendedUrl = $this->session->get('eventos_intended_url', '/eventos/admin');
        $this->session->remove('eventos_intended_url');

        header('Location: ' . $intendedUrl);
        exit;
    }

    /**
     * Cierra la sesión
     */
    public function logout(): void
    {
        $this->authService->logout();
        header('Location: /eventos/login');
        exit;
    }

    /**
     * Establece un mensaje de error para el login
     */
    private function setLoginError(string $message): void
    {
        $this->session->start();
        $this->session->set('eventos_login_error', $message);
    }
}

