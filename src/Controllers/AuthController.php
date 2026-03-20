<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Session\SessionManager;
use App\Support\Response;

/**
 * Controlador de autenticación
 */
class AuthController
{
    private AuthService $authService;
    private SessionManager $session;

    public function __construct(AuthService $authService, SessionManager $session)
    {
        $this->authService = $authService;
        $this->session = $session;
    }

    /**
     * Muestra la vista de login
     */
    public function viewLogin(): void
    {
        // Si ya está autenticado, redirigir según el rol
        if ($this->authService->isAuthenticated()) {
            $user = $this->authService->getCurrentUser();
            $userRole = $user['rol'] ?? null;
            
            // Redirigir según el rol
            switch ($userRole) {
                case 'portero':
                    Response::redirect('/portero/panel');
                    break;
                case 'aprendiz':
                    Response::redirect('/aprendiz/panel');
                    break;
                default:
                    Response::redirect('/dashboard');
                    break;
            }
        }

        $this->session->start();
        $error = $this->session->getFlash('error');
        $message = $this->session->getFlash('message');

        // Incluir la vista
        require __DIR__ . '/../../views/auth/login.php';
    }

    /**
     * Procesa el login (POST)
     */
    public function login(): void
    {
        // Solo aceptar POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/login');
        }

        // Obtener y sanitizar datos
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        // Validaciones básicas
        if (empty($email) || empty($password)) {
            $this->session->flash('error', 'Por favor complete todos los campos');
            Response::redirect('/login');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error', 'Email inválido');
            Response::redirect('/login');
        }

        // Intentar autenticar
        $user = $this->authService->login($email, $password);

        if (!$user) {
            // Credenciales inválidas - mensaje genérico por seguridad
            $this->session->flash('error', 'Credenciales incorrectas');
            Response::redirect('/login');
        }

        // Login exitoso - obtener el rol desde la sesión (ya está establecido por AuthService)
        $this->session->start();
        $userRole = $this->session->get('user_role') ?? $user['rol'] ?? null;
        
        // Redirigir al panel específico según el rol (ignorar intended_url para evitar conflictos)
        switch ($userRole) {
            case 'portero':
                $redirectUrl = '/portero/panel';
                break;
            case 'aprendiz':
                $redirectUrl = '/aprendiz/panel';
                break;
            case 'admin':
            case 'administrativo':
            case 'instructor':
                $redirectUrl = '/dashboard';
                break;
            default:
                // Si el rol no es reconocido, cerrar sesión y volver a login
                $this->authService->logout();
                $this->session->start();
                $this->session->flash('error', 'Rol de usuario no válido');
                Response::redirect('/login');
                return;
        }

        Response::redirect($redirectUrl);
    }

    /**
     * Cierra la sesión (logout)
     */
    public function logout(): void
    {
        // Iniciar sesión primero
        $this->session->start();
        
        // Cerrar sesión
        $this->authService->logout();
        
        // Flash message debe establecerse DESPUÉS de iniciar nueva sesión
        $this->session->start();
        $this->session->flash('message', 'Sesión cerrada exitosamente');
        
        // Agregar flag para limpiar cache PWA en el cliente
        $this->session->set('clear_pwa_cache', true);
        
        Response::redirect('/login');
    }
}
