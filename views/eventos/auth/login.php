<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestión de Eventos</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components/header-public.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/auth/login.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/eventos/auth.css') ?>">
</head>
<body class="login-page eventos-login">
    <?php include __DIR__ . '/../../components/header-eventos-publico.php'; ?>
    
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Gestión de Eventos</h1>
                <p>Sistema de Eventos SENA</p>
                <p class="login-subtitle">Acceso para gestión de eventos</p>
            </div>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form action="/eventos/login" method="POST" id="loginForm" class="login-form">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="tu.correo@sena.edu.co"
                        required
                        autofocus
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Ingresar
                </button>
            </form>

            <div class="login-footer">
                <a href="/eventos" class="link-back">
                    <i class="fas fa-arrow-left"></i>
                    Volver a eventos públicos
                </a>
            </div>
        </div>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
        // Validación simple del formulario
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Por favor complete todos los campos');
                return false;
            }

            if (!email.includes('@')) {
                e.preventDefault();
                alert('Por favor ingrese un email válido');
                return false;
            }
        });
    </script>
</body>
</html>

