<?php

namespace App\Controllers;

use App\Services\PasswordResetService;
use App\Session\SessionManager;
use App\Support\Response;

class PasswordResetController
{
    private PasswordResetService $passwordResetService;
    private SessionManager $session;

    public function __construct(
        PasswordResetService $passwordResetService,
        SessionManager $session
    ) {
        $this->passwordResetService = $passwordResetService;
        $this->session = $session;
    }

    public function showForgotPasswordForm(): void
    {
        $this->session->start();
        $error = $this->session->getFlash('error');
        $message = $this->session->getFlash('message');

        require __DIR__ . '/../../views/auth/forgot-password.php';
    }

    public function processForgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/password/forgot');
        }

        $emailOrDocument = filter_input(INPUT_POST, 'email_or_document', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (empty($emailOrDocument)) {
            $this->session->flash('error', 'Por favor ingresa tu correo electrónico o documento');
            Response::redirect('/password/forgot');
        }

        $result = $this->passwordResetService->requestPasswordReset($emailOrDocument);

        if ($result['success']) {
            $this->session->flash('message', $result['message']);
        } else {
            $this->session->flash('error', $result['message']);
        }

        Response::redirect('/password/forgot');
    }

    public function showResetPasswordForm(): void
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $this->session->flash('error', 'Token inválido');
            Response::redirect('/login');
        }

        $validation = $this->passwordResetService->validateToken($token);

        if (!$validation['valid']) {
            $this->session->flash('error', $validation['message']);
            Response::redirect('/login');
        }

        $this->session->start();
        $error = $this->session->getFlash('error');
        $message = $this->session->getFlash('message');

        require __DIR__ . '/../../views/auth/reset-password.php';
    }

    public function processResetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/login');
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($token) || empty($password) || empty($passwordConfirm)) {
            $this->session->flash('error', 'Por favor completa todos los campos');
            Response::redirect('/password/reset?token=' . urlencode($token));
        }

        if ($password !== $passwordConfirm) {
            $this->session->flash('error', 'Las contraseñas no coinciden');
            Response::redirect('/password/reset?token=' . urlencode($token));
        }

        $result = $this->passwordResetService->resetPassword($token, $password);

        if ($result['success']) {
            $this->session->flash('message', $result['message']);
            Response::redirect('/login');
        } else {
            $this->session->flash('error', $result['message']);
            Response::redirect('/password/reset?token=' . urlencode($token));
        }
    }
}
