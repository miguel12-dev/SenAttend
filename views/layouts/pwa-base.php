<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#39A900">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SENAttend">
    <meta name="mobile-web-app-capable" content="yes">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Sistema de gestión de asistencia para el SENA con registro mediante QR">
    <meta name="keywords" content="SENA, asistencia, QR, educación, gestión">
    <meta name="author" content="SENA">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="SENAttend - Sistema de Asistencia SENA">
    <meta property="og:description" content="Sistema de gestión de asistencia para el SENA">
    <meta property="og:image" content="<?= asset('assets/icons/web-app-manifest-512x512.png') ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="SENAttend">
    <meta name="twitter:description" content="Sistema de gestión de asistencia para el SENA">
    
    <title><?= $title ?? 'SENAttend - Sistema de Asistencia SENA' ?></title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- Favicons -->
    <link rel="icon" type="image/png" sizes="96x96" href="<?= asset('assets/icons/favicon-96x96.png') ?>">
    <link rel="shortcut icon" href="<?= asset('assets/icons/favicon-96x96.png') ?>">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="<?= asset('assets/icons/apple-touch-icon.png') ?>">
    
    <!-- Splash Screens iOS -->
    <!-- Las splash screens se pueden generar bajo demanda por iOS -->
    
    <!-- MS Tiles -->
    <meta name="msapplication-TileColor" content="#39A900">
    <meta name="msapplication-TileImage" content="<?= asset('assets/icons/web-app-manifest-192x192.png') ?>">
    
    <!-- Preconnect para performance -->
    <link rel="preconnect" href="<?= getEnv('APP_URL', 'http://localhost:8000') ?>">
    <link rel="dns-prefetch" href="<?= getEnv('APP_URL', 'http://localhost:8000') ?>">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/components.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/notification-modal.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/pwa/pwa-styles.css') ?>">
    
    <?php if (isset($styles) && is_array($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="<?= asset($style) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline Critical CSS para PWA -->
    <style>
        /* Critical CSS para carga inicial */
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
        }
        
        .pwa-loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            z-index: 99999;
        }
        
        .pwa-loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(57, 169, 0, 0.3);
            border-radius: 50%;
            border-top-color: #39A900;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading Screen PWA -->
    <div class="pwa-loading" id="pwa-loading">
        <div class="pwa-loading-spinner"></div>
    </div>
    
    <!-- Offline Indicator -->
    <div class="pwa-offline-indicator" id="pwa-offline-indicator">
        <i class="fas fa-wifi-slash"></i>
        <span>Sin conexión a internet</span>
    </div>
    
    <!-- Install Banner (se muestra automáticamente si está disponible) -->
    <div class="pwa-install-banner" id="pwa-install-banner" style="display: none;">
        <h3>
            <i class="fas fa-download"></i>
            Instalar SENAttend
        </h3>
        <p>Instala la aplicación para un acceso más rápido y funcionalidad offline.</p>
        <div class="pwa-install-banner-buttons">
            <button id="pwa-install-btn" class="btn-sena">
                <i class="fas fa-download"></i>
                Instalar
            </button>
            <button id="pwa-install-dismiss" class="btn-sena-secondary">
                Después
            </button>
        </div>
    </div>
    
    <!-- Main App Container -->
    <div class="app-container">
        <?= $content ?? '' ?>
    </div>
    
    <!-- Core Scripts (siempre cargados) -->
    <script>
        // Configuración global
        window.APP_CONFIG = {
            baseURL: '<?= getEnv('APP_URL', 'http://localhost:8000') ?>',
            apiURL: '<?= getEnv('API_URL', '/api') ?>',
            version: '1.0.0',
            env: '<?= APP_ENV ?>',
            isProduction: <?= APP_ENV === 'production' ? 'true' : 'false' ?>
        };
        
        // Detectar si está instalada como PWA
        window.isPWA = window.matchMedia('(display-mode: standalone)').matches ||
                       window.navigator.standalone === true;
        
        // Ocultar loading cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    document.getElementById('pwa-loading')?.remove();
                }, 500);
            });
        } else {
            setTimeout(() => {
                document.getElementById('pwa-loading')?.remove();
            }, 500);
        }
    </script>
    
    <!-- PWA Scripts (con type="module" para ES6) -->
    <script type="module">
        import PWAManager from '<?= asset('js/pwa/pwa-manager.js') ?>';
        import apiClient from '<?= asset('js/api/api-client.js') ?>';
        import router from '<?= asset('js/router/spa-router.js') ?>';
        import stateManager, { userStore, appStore } from '<?= asset('js/state/state-manager.js') ?>';
        
        // Hacer disponibles globalmente
        window.pwaManager = new PWAManager();
        window.apiClient = apiClient;
        window.router = router;
        window.stateManager = stateManager;
        window.userStore = userStore;
        window.appStore = appStore;
        
        // Setup offline/online indicators
        window.addEventListener('online', () => {
            document.getElementById('pwa-offline-indicator')?.classList.remove('show');
            appStore.setOnline(true);
        });
        
        window.addEventListener('offline', () => {
            document.getElementById('pwa-offline-indicator')?.classList.add('show');
            appStore.setOnline(false);
        });
        
        // Setup install banner dismiss
        document.getElementById('pwa-install-dismiss')?.addEventListener('click', () => {
            document.getElementById('pwa-install-banner').style.display = 'none';
        });
        
        console.log('[PWA] SENAttend inicializado');
    </script>
    
    <!-- Legacy Scripts (para compatibilidad) -->
    <script src="<?= asset('js/app.js') ?>" defer></script>
    <script src="<?= asset('js/common/app.js') ?>" defer></script>
    <script src="<?= asset('js/common/components.js') ?>" defer></script>
    <script src="<?= asset('js/components/back-button.js') ?>" defer></script>
    <script src="<?= asset('js/common/notification-modal.js') ?>"></script>
    
    <?php if (isset($scripts) && is_array($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= asset($script) ?>" defer></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Analytics (solo en producción) -->
    <?php if (APP_ENV === 'production'): ?>
    <script>
        // Agregar analytics aquí si es necesario
    </script>
    <?php endif; ?>
</body>
</html>
