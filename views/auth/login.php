<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SENAttend</title>
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
                <h1>SENAttend</h1>
                <p>Sistema de Asistencia SENA</p>
                <p class="login-subtitle">Acceso para personal y aprendices</p>
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

            <form action="/auth/login" method="POST" id="loginForm" class="login-form">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="ejemplo@sena.edu.co"
                        required
                        autofocus
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
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Ingresar
                </button>

                <div style="text-align: center; margin-top: 15px;">
                    <a href="/password/forgot" style="color: #39A900; text-decoration: none; font-size: 14px;">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
            </form>
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

    <?php if (isset($_SESSION['clear_pwa_cache']) && $_SESSION['clear_pwa_cache']): ?>
    <script>
        // Limpiar cache PWA al hacer logout
        (async function() {
            try {
                // Limpiar cache del Service Worker
                if ('serviceWorker' in navigator) {
                    const registration = await navigator.serviceWorker.ready;
                    if (registration.active) {
                        registration.active.postMessage({ type: 'CLEAR_CACHE' });
                    }
                }

                // Limpiar Cache Storage manualmente
                if ('caches' in window) {
                    const cacheNames = await caches.keys();
                    await Promise.all(
                        cacheNames.map(cacheName => caches.delete(cacheName))
                    );
                }

                // Limpiar IndexedDB
                if ('indexedDB' in window) {
                    const dbs = ['SENAttendDB'];
                    for (const dbName of dbs) {
                        indexedDB.deleteDatabase(dbName);
                    }
                }

                // Limpiar sessionStorage y localStorage (excepto configuración de instalación)
                const installPromptDismissed = localStorage.getItem('pwa_install_prompt_dismissed');
                sessionStorage.clear();
                localStorage.clear();
                if (installPromptDismissed) {
                    localStorage.setItem('pwa_install_prompt_dismissed', installPromptDismissed);
                }

                console.log('[PWA] Cache y datos locales limpiados exitosamente');
            } catch (error) {
                console.error('[PWA] Error al limpiar cache:', error);
            }
        })();
    </script>
    <?php 
        unset($_SESSION['clear_pwa_cache']); 
    ?>
    <?php endif; ?>
</body>
</html>

