<?php

namespace App\Eventos\Services;

use App\Eventos\Repositories\EventoUsuarioRepository;
use PDOException;

/**
 * Servicio para la gestión de usuarios del módulo de eventos.
 * Separa validaciones, creación y notificación de credenciales.
 */
class EventoUsuarioService
{
    private EventoUsuarioRepository $usuarioRepository;
    private EventoEmailService $emailService;

    public function __construct(
        EventoUsuarioRepository $usuarioRepository,
        EventoEmailService $emailService
    ) {
        $this->usuarioRepository = $usuarioRepository;
        $this->emailService = $emailService;
    }

    /**
     * Lista todos los usuarios activos del módulo de eventos.
     */
    public function listar(): array
    {
        return $this->usuarioRepository->findAll();
    }

    /**
     * Crea un usuario administrativo del módulo de eventos y envía sus credenciales.
     */
    public function crearUsuario(array $input): array
    {
        $nombre = trim($input['nombre'] ?? '');
        $email = trim($input['email'] ?? '');
        $documento = trim($input['documento'] ?? '');
        $rol = $input['rol'] ?? 'administrativo';

        $errores = $this->validarDatos($nombre, $email, $documento, $rol);
        if (!empty($errores)) {
            return ['success' => false, 'errors' => $errores];
        }

        // Evitar duplicados antes de insertar.
        if ($this->usuarioRepository->findByEmail($email)) {
            return ['success' => false, 'errors' => ['Ya existe un usuario con ese correo']];
        }

        $passwordPlano = $documento; // Política solicitada: contraseña = documento.

        try {
            $userId = $this->usuarioRepository->create([
                'email' => $email,
                'password_hash' => password_hash($passwordPlano, PASSWORD_DEFAULT),
                'nombre' => $nombre,
                'rol' => $rol,
                'activo' => true
            ]);
        } catch (PDOException $e) {
            error_log('EventoUsuarioService::crearUsuario error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['No se pudo crear el usuario.']];
        }

        $emailResult = $this->emailService->enviarCredencialesUsuarioEvento(
            $email,
            $nombre,
            $passwordPlano,
            $rol
        );

        return [
            'success' => true,
            'user_id' => $userId,
            'email_enviado' => $emailResult['success'] ?? false,
            'email_message' => $emailResult['message'] ?? null
        ];
    }

    /**
     * Validación básica de datos requeridos.
     */
    private function validarDatos(string $nombre, string $email, string $documento, string $rol): array
    {
        $errores = [];

        if ($nombre === '') {
            $errores[] = 'El nombre es obligatorio';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo electrónico no es válido';
        }

        if ($documento === '') {
            $errores[] = 'El documento es obligatorio';
        }

        if (!in_array($rol, ['admin', 'administrativo'], true)) {
            $errores[] = 'Rol no permitido';
        }

        return $errores;
    }
}


