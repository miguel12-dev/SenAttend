<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestión de Eventos</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/auth/login.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/eventos/auth.css') ?>">
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="logo-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h1>Gestión de Eventos</h1>
                    <p class="subtitle">Sistema de Eventos SENA</p>
                </div>

                <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form action="/eventos/login" method="POST" class="login-form">
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Correo electrónico
                        </label>
                        <input type="email" id="email" name="email" class="form-control" required 
                               placeholder="tu.correo@sena.edu.co" autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Contraseña
                        </label>
                        <input type="password" id="password" name="password" class="form-control" required 
                               placeholder="••••••••" autocomplete="current-password">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i>
                        Iniciar Sesión
                    </button>
                </form>

                <div class="login-footer">
                    <a href="/eventos" class="btn-back">
                        <i class="fas fa-arrow-left"></i>
                        Volver a eventos públicos
                    </a>
                    <a href="/" class="btn-back" style="margin-top: 0.5rem;">
                        <i class="fas fa-home"></i>
                        Ir al sistema principal
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

