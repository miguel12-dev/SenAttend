# Sistema de Recuperación de Contraseña - SENAttend

## Resumen Técnico

Se ha implementado un sistema completo de recuperación de contraseña siguiendo los principios SOLID y Clean Code.

## Arquitectura

### Capa de Datos
- **Migración**: `database/migrations/012_create_password_reset_tokens_table.sql`
  - Tabla `password_reset_tokens` con tokens únicos
  - Tokens con expiración de 1 hora
  - Control de uso mediante campo `used`
  - Relación con tabla `usuarios`

### Capa de Repositorio
- **PasswordResetTokenRepository** (`src/Repositories/PasswordResetTokenRepository.php`)
  - `create()`: Crea token de recuperación
  - `findByToken()`: Valida token y verifica expiración
  - `markAsUsed()`: Invalida token tras uso
  - `invalidateAllUserTokens()`: Invalida todos los tokens de un usuario
  - `deleteExpiredTokens()`: Limpieza de tokens expirados

### Capa de Servicio
- **PasswordResetService** (`src/Services/PasswordResetService.php`)
  - `requestPasswordReset()`: Solicita recuperación por email o documento
  - `validateToken()`: Valida token antes de reseteo
  - `resetPassword()`: Cambia contraseña validando token
  - `cleanExpiredTokens()`: Mantenimiento de tokens
  - Busca usuarios tanto en tabla `usuarios` como en `aprendices`
  - Tokens seguros generados con `random_bytes(32)`

- **EmailService** (extendido)
  - `enviarTokenRecuperacion()`: Envía email con enlace de recuperación
  - Email HTML responsive con branding SENA
  - Incluye advertencias de seguridad

### Capa de Controlador
- **PasswordResetController** (`src/Controllers/PasswordResetController.php`)
  - `showForgotPasswordForm()`: Muestra formulario de solicitud
  - `processForgotPassword()`: Procesa solicitud y envía email
  - `showResetPasswordForm()`: Muestra formulario de cambio
  - `processResetPassword()`: Procesa cambio de contraseña
  - Validaciones de seguridad
  - Flash messages para feedback

### Capa de Vista
- **forgot-password.php**: Formulario para solicitar recuperación
  - Acepta correo electrónico o documento
  - Validación JavaScript
  - Enlace de retorno al login

- **reset-password.php**: Formulario para cambiar contraseña
  - Validación de coincidencia de contraseñas en tiempo real
  - Indicador visual de estado
  - Validación de longitud mínima (6 caracteres)

- **login.php** (actualizado): Añadido enlace "¿Olvidaste tu contraseña?"

## Flujo de Recuperación

1. Usuario accede a `/password/forgot` desde el login
2. Ingresa correo o documento
3. Sistema busca usuario (en `usuarios` o `aprendices`)
4. Se genera token seguro de 64 caracteres
5. Token se guarda en BD con expiración de 1 hora
6. Se envía email con enlace de recuperación
7. Usuario hace clic en enlace
8. Sistema valida token (existencia, expiración, uso)
9. Usuario ingresa nueva contraseña
10. Se actualiza contraseña con hash bcrypt
11. Token se marca como usado
12. Redirección a login con mensaje de éxito

## Rutas Implementadas

### GET
- `/password/forgot`: Formulario de solicitud
- `/password/reset?token=XXX`: Formulario de cambio

### POST
- `/password/forgot`: Procesa solicitud
- `/password/reset`: Procesa cambio de contraseña

## Seguridad

- Tokens criptográficamente seguros (64 caracteres hex)
- Expiración automática (1 hora)
- Un solo uso por token
- Invalidación de tokens anteriores al generar uno nuevo
- Contraseñas hasheadas con bcrypt
- Validación de token en cada paso
- Mensajes genéricos para evitar enumeración de usuarios
- Sanitización de entradas

## Instalación

1. Ejecutar migración:
```bash
php database/migrations/run_password_reset_migration.php
```

2. Verificar configuración SMTP en `.env`:
```
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=tu_email@gmail.com
SMTP_PASSWORD=tu_app_password
SMTP_ENCRYPTION=tls
MAIL_FROM_EMAIL=senattend@gmail.com
MAIL_FROM_NAME=SENAttend - Sistema de Asistencia SENA
```

## Mantenimiento

Los tokens expirados se pueden limpiar ejecutando:
```php
$passwordResetService->cleanExpiredTokens();
```

Se recomienda ejecutar esta limpieza mediante cron job diario.

## Compatibilidad

- Funciona con usuarios del sistema principal
- Funciona con aprendices (busca por documento)
- Compatible con toda la arquitectura existente
- Sin dependencias externas adicionales
