/**
 * Componente de botón atrás con soporte para recarga automática
 * Detecta la navegación hacia atrás y opcionalmente recarga la página
 */

// Lista de rutas que deben recargarse al volver
const AUTO_RELOAD_ROUTES = [
    '/portero/escanear',
    '/portero/panel',
    '/portero/boletas-salida',
    '/admin/boletas-salida',
    '/admin/boletas-salida/historial'
];

/**
 * Verifica si la URL actual está en la lista de rutas de recarga automática
 * @returns {boolean}
 */
function shouldAutoReload() {
    const currentPath = window.location.pathname;
    return AUTO_RELOAD_ROUTES.some(route => currentPath === route || currentPath.startsWith(route));
}

/**
 * Recarga la página si estamos en una ruta que lo requiere
 */
function handleBackNavigation() {
    if (shouldAutoReload()) {
        console.log('Navegación detectada hacia página dinámica, recargando...');
        window.location.reload();
    } else {
        window.history.back();
    }
}

// Event listener para botones de volver sin URL
document.addEventListener('DOMContentLoaded', function() {
    // Botones con data-back="true"
    document.querySelectorAll('.btn-back[data-back="true"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            handleBackNavigation();
        });
    });

    // Detectar navegación hacia atrás mediante popstate
    window.addEventListener('popstate', function(event) {
        // El popstate se dispara DESPUÉS de que la página ya cambió
        // No podemos detectar la página anterior fácilmente
        // Por eso usamos un flag en sessionStorage
    });

    // Antes de salir de la página, guardamos un flag
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (link && link.href && link.href.includes(window.location.host)) {
            const targetPath = new URL(link.href).pathname;
            // Si vamos a una página que requiere reload, marcarlo
            if (AUTO_RELOAD_ROUTES.some(route => targetPath === route)) {
                sessionStorage.setItem('shouldReloadOnBack', 'true');
            }
        }
    });

    // Al cargar la página, verificar si debemos recargar
    if (sessionStorage.getItem('shouldReloadOnBack') === 'true') {
        sessionStorage.removeItem('shouldReloadOnBack');
        // Pequeño delay para asegurar que todo esté listo
        setTimeout(() => {
            window.location.reload();
        }, 100);
    }
});

// Exportar función para uso manual
window.handleBackNavigation = handleBackNavigation;

