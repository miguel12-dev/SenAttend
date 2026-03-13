<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - SENAttend</title>
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
                <h1>Recuperar Contraseña</h1>
                <p class="login-subtitle">Ingresa tu correo electrónico o documento</p>
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

            <form action="/password/forgot" method="POST" id="forgotPasswordForm" class="login-form">
                <div class="form-group">
                    <label for="email_or_document">Correo Electrónico o Documento</label>
                    <input 
                        type="text" 
                        id="email_or_document" 
                        name="email_or_document" 
                        class="form-control" 
                        placeholder="ejemplo@sena.edu.co o 1234567890"
                        required
                        autofocus
                    >
                    <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                        Ingresa el correo o documento con el que estás registrado
                    </small>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-paper-plane"></i>
                    Enviar enlace de recuperación
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
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            const input = document.getElementById('email_or_document').value.trim();

            if (!input) {
                e.preventDefault();
                alert('Por favor ingresa tu correo electrónico o documento');
                return false;
            }
        });
    </script>
</body>
</html>
