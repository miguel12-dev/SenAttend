<!-- Componente de Instalación PWA -->
<div class="pwa-install-prompt" id="pwa-install-prompt" style="display: none;">
    <div class="pwa-install-prompt__content">
        <div class="pwa-install-prompt__icon">
            <i class="fas fa-mobile-alt"></i>
        </div>
        <div class="pwa-install-prompt__text">
            <h3 class="pwa-install-prompt__title">Instala SENAttend</h3>
            <p class="pwa-install-prompt__description">
                Para una mejor experiencia, instala la aplicación en tu dispositivo. 
                Tendrás acceso rápido, mejor gestión de sesión y almacenamiento de datos local.
            </p>
        </div>
        <div class="pwa-install-prompt__actions">
            <button class="pwa-install-prompt__btn pwa-install-prompt__btn--primary" id="pwa-install-accept">
                <i class="fas fa-download"></i>
                <span>Instalar Aplicación</span>
            </button>
            <button class="pwa-install-prompt__btn pwa-install-prompt__btn--secondary" id="pwa-install-dismiss">
                <i class="fas fa-times"></i>
                <span>Ahora no</span>
            </button>
        </div>
    </div>
    <button class="pwa-install-prompt__close" id="pwa-install-close" aria-label="Cerrar">
        <i class="fas fa-times"></i>
    </button>
</div>

<!-- Indicador de instalación exitosa -->
<div class="pwa-install-success" id="pwa-install-success" style="display: none;">
    <div class="pwa-install-success__content">
        <i class="fas fa-check-circle pwa-install-success__icon"></i>
        <p class="pwa-install-success__message">¡Aplicación instalada correctamente!</p>
    </div>
</div>
