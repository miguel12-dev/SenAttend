<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components/header-public.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/auth/login.css') ?>">
</head>
<body class="login-page">
    <?php include __DIR__ . '/../components/header-public.php'; ?>
    
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Nueva Contraseña</h1>
                <p class="login-subtitle">Ingresa tu nueva contraseña</p>
            </div>

            <?php if (isset($error) && $error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if (isset($message) && $message): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <form action="/password/reset" method="POST" id="resetPasswordForm" class="login-form">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">

                <div class="form-group">
                    <label for="password">Nueva Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Mínimo 6 caracteres"
                        required
                        minlength="6"
                        autofocus
                    >
                    <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                        La contraseña debe tener al menos 6 caracteres
                    </small>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirmar Contraseña</label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        class="form-control" 
                        placeholder="Confirma tu contraseña"
                        required
                        minlength="6"
                    >
                </div>

                <div id="password-match-message" style="display: none; margin-bottom: 10px; padding: 8px; border-radius: 4px;"></div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-key"></i>
                    Restablecer Contraseña
                </button>

                <div style="text-align: center; margin-top: 15px;">
                    <a href="/login" style="color: #39A900; text-decoration: none;">
                        <i class="fas fa-arrow-left"></i>
                        Volver al inicio de sesión
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirm');
        const messageDiv = document.getElementById('password-match-message');
        const form = document.getElementById('resetPasswordForm');

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;

            if (confirm.length === 0) {
                messageDiv.style.display = 'none';
                return;
            }

            if (password === confirm) {
                messageDiv.style.display = 'block';
                messageDiv.style.backgroundColor = '#d4edda';
                messageDiv.style.color = '#155724';
                messageDiv.style.border = '1px solid #c3e6cb';
                messageDiv.innerHTML = '<i class="fas fa-check-circle"></i> Las contraseñas coinciden';
            } else {
                messageDiv.style.display = 'block';
                messageDiv.style.backgroundColor = '#f8d7da';
                messageDiv.style.color = '#721c24';
                messageDiv.style.border = '1px solid #f5c6cb';
                messageDiv.innerHTML = '<i class="fas fa-times-circle"></i> Las contraseñas no coinciden';
            }
        }

        confirmInput.addEventListener('input', checkPasswordMatch);
        passwordInput.addEventListener('input', checkPasswordMatch);

        form.addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = confirmInput.value;

            if (password.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres');
                return false;
            }

            if (password !== confirm) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }
        });
    </script>
</body>
</html>
