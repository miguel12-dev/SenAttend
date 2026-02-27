/**
 * PWA Install Manager
 * Maneja la instalación de la PWA siguiendo principios SOLID
 * 
 * Single Responsibility: Solo maneja la instalación de la PWA
 * Open/Closed: Extensible mediante eventos personalizados
 * Liskov Substitution: Implementa interfaz consistente
 * Interface Segregation: Interfaz específica para instalación
 * Dependency Inversion: Depende de abstracciones (eventos del navegador)
 */

class PWAInstallManager {
    constructor() {
        this.deferredPrompt = null;
        this.promptElement = null;
        this.acceptButton = null;
        this.dismissButton = null;
        this.closeButton = null;
        this.successElement = null;
        
        this.init();
    }
    
    /**
     * Inicializa el manager y registra event listeners
     */
    init() {
        this.cacheElements();
        this.registerServiceWorker();
        this.setupInstallPrompt();
        this.setupEventListeners();
        this.checkInstallStatus();
    }
    
    /**
     * Cachea referencias a elementos DOM
     */
    cacheElements() {
        this.promptElement = document.getElementById('pwa-install-prompt');
        this.acceptButton = document.getElementById('pwa-install-accept');
        this.dismissButton = document.getElementById('pwa-install-dismiss');
        this.closeButton = document.getElementById('pwa-install-close');
        this.successElement = document.getElementById('pwa-install-success');
    }
    
    /**
     * Registra el Service Worker
     */
    async registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            console.warn('[PWA] Service Worker no soportado');
            return;
        }
        
        try {
            const registration = await navigator.serviceWorker.register('/sw.js', {
                scope: '/'
            });
            
            console.log('[PWA] Service Worker registrado:', registration.scope);
            
            registration.addEventListener('updatefound', () => {
                console.log('[PWA] Nueva versión disponible');
                this.notifyUpdate();
            });
            
        } catch (error) {
            console.error('[PWA] Error registrando Service Worker:', error);
        }
    }
    
    /**
     * Configura el prompt de instalación
     */
    setupInstallPrompt() {
        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            this.deferredPrompt = event;
            this.showInstallPrompt();
            
            console.log('[PWA] Evento beforeinstallprompt capturado');
        });
        
        window.addEventListener('appinstalled', () => {
            console.log('[PWA] Aplicación instalada');
            this.hideInstallPrompt();
            this.showSuccessMessage();
            this.deferredPrompt = null;
        });
    }
    
    /**
     * Configura event listeners para botones
     */
    setupEventListeners() {
        if (this.acceptButton) {
            this.acceptButton.addEventListener('click', () => this.handleInstall());
        }
        
        if (this.dismissButton) {
            this.dismissButton.addEventListener('click', () => this.handleDismiss());
        }
        
        if (this.closeButton) {
            this.closeButton.addEventListener('click', () => this.handleClose());
        }
    }
    
    /**
     * Verifica si la app ya está instalada
     */
    checkInstallStatus() {
        const isInstalled = window.matchMedia('(display-mode: standalone)').matches ||
                           window.navigator.standalone === true;
        
        if (isInstalled) {
            console.log('[PWA] Aplicación ya instalada');
            this.hideInstallPrompt();
        }
    }
    
    /**
     * Muestra el prompt de instalación
     */
    showInstallPrompt() {
        if (!this.promptElement) return;
        
        const isDismissed = localStorage.getItem('pwa-install-dismissed');
        const dismissTime = localStorage.getItem('pwa-install-dismiss-time');
        
        if (isDismissed && dismissTime) {
            const hoursSinceDismiss = (Date.now() - parseInt(dismissTime)) / (1000 * 60 * 60);
            if (hoursSinceDismiss < 24) {
                console.log('[PWA] Prompt deshabilitado temporalmente');
                return;
            }
        }
        
        this.promptElement.style.display = 'block';
        this.promptElement.setAttribute('aria-hidden', 'false');
    }
    
    /**
     * Oculta el prompt de instalación
     */
    hideInstallPrompt() {
        if (!this.promptElement) return;
        
        this.promptElement.style.display = 'none';
        this.promptElement.setAttribute('aria-hidden', 'true');
    }
    
    /**
     * Maneja el evento de instalación
     */
    async handleInstall() {
        if (!this.deferredPrompt) {
            console.warn('[PWA] No hay prompt diferido disponible');
            return;
        }
        
        try {
            this.disableButtons();
            
            this.deferredPrompt.prompt();
            
            const { outcome } = await this.deferredPrompt.userChoice;
            
            console.log('[PWA] Resultado de instalación:', outcome);
            
            if (outcome === 'accepted') {
                this.hideInstallPrompt();
                this.showSuccessMessage();
            } else {
                this.handleDismiss();
            }
            
            this.deferredPrompt = null;
            
        } catch (error) {
            console.error('[PWA] Error en instalación:', error);
        } finally {
            this.enableButtons();
        }
    }
    
    /**
     * Maneja el evento de descarte temporal
     */
    handleDismiss() {
        localStorage.setItem('pwa-install-dismissed', 'true');
        localStorage.setItem('pwa-install-dismiss-time', Date.now().toString());
        this.hideInstallPrompt();
    }
    
    /**
     * Maneja el evento de cierre permanente
     */
    handleClose() {
        localStorage.setItem('pwa-install-dismissed', 'true');
        localStorage.setItem('pwa-install-dismiss-time', (Date.now() + 7 * 24 * 60 * 60 * 1000).toString());
        this.hideInstallPrompt();
    }
    
    /**
     * Muestra mensaje de éxito
     */
    showSuccessMessage() {
        if (!this.successElement) return;
        
        this.successElement.style.display = 'block';
        
        setTimeout(() => {
            this.successElement.style.display = 'none';
        }, 5000);
    }
    
    /**
     * Notifica sobre actualización disponible
     */
    notifyUpdate() {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Actualización disponible', {
                body: 'Una nueva versión de SENAttend está disponible',
                icon: '/public/assets/icons/web-app-manifest-192x192.png'
            });
        }
    }
    
    /**
     * Deshabilita botones durante instalación
     */
    disableButtons() {
        if (this.acceptButton) {
            this.acceptButton.disabled = true;
        }
        if (this.dismissButton) {
            this.dismissButton.disabled = true;
        }
    }
    
    /**
     * Habilita botones después de instalación
     */
    enableButtons() {
        if (this.acceptButton) {
            this.acceptButton.disabled = false;
        }
        if (this.dismissButton) {
            this.dismissButton.disabled = false;
        }
    }
    
    /**
     * Destruye el manager y limpia event listeners
     */
    destroy() {
        if (this.acceptButton) {
            this.acceptButton.removeEventListener('click', this.handleInstall);
        }
        if (this.dismissButton) {
            this.dismissButton.removeEventListener('click', this.handleDismiss);
        }
        if (this.closeButton) {
            this.closeButton.removeEventListener('click', this.handleClose);
        }
    }
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.pwaInstallManager = new PWAInstallManager();
    });
} else {
    window.pwaInstallManager = new PWAInstallManager();
}

// Exportar para uso como módulo
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PWAInstallManager;
}
