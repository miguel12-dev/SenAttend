/**
 * Sistema de notificaciones modal para errores y mensajes
 * Maneja scroll automático y modales visuales
 */

class NotificationModal {
    constructor() {
        this.init();
    }

    init() {
        this.createModalContainer();
        this.handleFormErrors();
    }

    /**
     * Crear contenedor de modales si no existe
     */
    createModalContainer() {
        if (!document.getElementById('notification-modal-container')) {
            const container = document.createElement('div');
            container.id = 'notification-modal-container';
            document.body.appendChild(container);
        }
    }

    /**
     * Mostrar modal con mensaje
     */
    show(options) {
        const {
            type = 'error', // error, success, warning, info
            title = '',
            message = '',
            messages = [], // Array de mensajes
            scrollToTop = true,
            autoClose = false,
            autoCloseDelay = 5000,
            onClose = null
        } = options;

        // Scroll al inicio si está habilitado
        if (scrollToTop) {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Crear modal
        const modal = this.createModal(type, title, message, messages);

        // Agregar al contenedor
        const container = document.getElementById('notification-modal-container');
        container.appendChild(modal);

        // Mostrar con animación
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);

        // Auto-cerrar si está configurado
        if (autoClose) {
            setTimeout(() => {
                this.close(modal, onClose);
            }, autoCloseDelay);
        }

        return modal;
    }

    /**
     * Crear estructura del modal
     */
    createModal(type, title, message, messages) {
        const modal = document.createElement('div');
        modal.className = `notification-modal notification-${type}`;

        const icons = {
            error: 'fa-exclamation-circle',
            success: 'fa-check-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const titles = {
            error: title || 'Error',
            success: title || 'Éxito',
            warning: title || 'Advertencia',
            info: title || 'Información'
        };

        let messagesHtml = '';
        if (messages.length > 0) {
            messagesHtml = '<ul class="notification-messages">';
            messages.forEach(msg => {
                messagesHtml += `<li>${this.escapeHtml(msg)}</li>`;
            });
            messagesHtml += '</ul>';
        } else if (message) {
            messagesHtml = `<p>${this.escapeHtml(message)}</p>`;
        }

        modal.innerHTML = `
            <div class="notification-overlay"></div>
            <div class="notification-content">
                <div class="notification-header">
                    <div class="notification-title-block">
                        <i class="fas ${icons[type]}"></i>
                        <h3>${titles[type]}</h3>
                    </div>
                    <button class="notification-close" aria-label="Cerrar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="notification-body">
                    ${messagesHtml}
                </div>
                <div class="notification-footer">
                    <button class="btn btn-primary notification-btn-close">Entendido</button>
                </div>
            </div>
        `;

        // Eventos de cierre
        const closeBtn = modal.querySelector('.notification-close');
        const btnClose = modal.querySelector('.notification-btn-close');
        const overlay = modal.querySelector('.notification-overlay');

        closeBtn.addEventListener('click', () => this.close(modal));
        btnClose.addEventListener('click', () => this.close(modal));
        overlay.addEventListener('click', () => this.close(modal));

        return modal;
    }

    /**
     * Cerrar modal
     */
    close(modal, onClose = null) {
        modal.classList.remove('show');
        
        setTimeout(() => {
            modal.remove();
            if (onClose && typeof onClose === 'function') {
                onClose();
            }
        }, 300);
    }

    /**
     * Manejar errores de formulario automáticamente
     */
    handleFormErrors() {
        document.addEventListener('DOMContentLoaded', () => {
            // Buscar alertas de error en la página
            const errorAlerts = document.querySelectorAll('.alert-error, .alert.alert-danger');
            
            if (errorAlerts.length > 0) {
                const messages = [];
                
                errorAlerts.forEach(alert => {
                    // Obtener texto del alert
                    const text = alert.textContent.trim();
                    
                    // Buscar lista de errores
                    const errorList = alert.querySelectorAll('li');
                    if (errorList.length > 0) {
                        errorList.forEach(li => {
                            messages.push(li.textContent.trim());
                        });
                    } else if (text) {
                        messages.push(text);
                    }
                    
                    // Ocultar el alert original (opcional)
                    // alert.style.display = 'none';
                });

                if (messages.length > 0) {
                    this.show({
                        type: 'error',
                        title: 'Error en el formulario',
                        messages: messages,
                        scrollToTop: true
                    });
                }
            }

            // Buscar alertas de éxito
            const successAlerts = document.querySelectorAll('.alert-success, .alert.alert-success');
            
            if (successAlerts.length > 0) {
                const messages = [];
                
                successAlerts.forEach(alert => {
                    const text = alert.textContent.trim();
                    if (text) {
                        messages.push(text);
                    }
                });

                if (messages.length > 0) {
                    this.show({
                        type: 'success',
                        messages: messages,
                        scrollToTop: true,
                        autoClose: true,
                        autoCloseDelay: 5000
                    });
                }
            }
        });
    }

    /**
     * Mostrar error
     */
    showError(message, messages = []) {
        return this.show({
            type: 'error',
            message: message,
            messages: messages,
            scrollToTop: true
        });
    }

    /**
     * Mostrar éxito
     */
    showSuccess(message, autoClose = true) {
        return this.show({
            type: 'success',
            message: message,
            scrollToTop: false,
            autoClose: autoClose,
            autoCloseDelay: 3000
        });
    }

    /**
     * Mostrar advertencia
     */
    showWarning(message) {
        return this.show({
            type: 'warning',
            message: message,
            scrollToTop: true
        });
    }

    /**
     * Mostrar información
     */
    showInfo(message) {
        return this.show({
            type: 'info',
            message: message,
            scrollToTop: false,
            autoClose: true,
            autoCloseDelay: 4000
        });
    }

    /**
     * Escapar HTML para prevenir XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Crear instancia global
window.notificationModal = new NotificationModal();

// Exponer métodos directos
window.showError = (message, messages = []) => window.notificationModal.showError(message, messages);
window.showSuccess = (message) => window.notificationModal.showSuccess(message);
window.showWarning = (message) => window.notificationModal.showWarning(message);
window.showInfo = (message) => window.notificationModal.showInfo(message);
