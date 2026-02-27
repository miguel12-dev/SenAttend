/**
 * Configuración de Rutas SPA - SENAttend
 * Define todas las rutas client-side de la aplicación
 * 
 * @version 1.0.0
 */

import router from './router/spa-router.js';
import apiClient from './api/api-client.js';
import { userStore } from './state/state-manager.js';

/**
 * Middleware de autenticación
 */
const authMiddleware = async ({ path, route }) => {
  // Evitar bucle de redirección
  if (path === '/login') {
    return true;
  }
  
  if (!userStore.isAuthenticated()) {
    router.replace('/login');
    return false;
  }
  return true;
};

/**
 * Middleware de permisos
 */
const permissionMiddleware = (permission) => {
  return async ({ path, route }) => {
    if (!userStore.hasPermission(permission)) {
      router.replace('/dashboard');
      window.pwaManager?.showToast('No tienes permisos para esta acción', 'error');
      return false;
    }
    return true;
  };
};

/**
 * Registro de rutas públicas
 */

// Home/Welcome
router.register('/', async () => {
  // Si está autenticado, redirigir a dashboard
  if (userStore.isAuthenticated()) {
    router.replace('/dashboard');
    return;
  }
  
  // Cargar vista de bienvenida
  loadView('welcome');
});

// Login
router.register('/login', async () => {
  if (userStore.isAuthenticated()) {
    router.replace('/dashboard');
    return;
  }
  
  loadView('auth/login');
});

/**
 * Registro de rutas protegidas
 */

// Dashboard
router.register('/dashboard', async () => {
  try {
    window.appStore.setLoading(true);
    
    // Cargar datos del dashboard
    const user = userStore.getUser();
    
    // Renderizar dashboard según rol
    loadView('dashboard', { user });
    
  } catch (error) {
    window.pwaManager?.showToast('Error al cargar dashboard', 'error');
  } finally {
    window.appStore.setLoading(false);
  }
}, {
  middleware: [authMiddleware],
  meta: { title: 'Dashboard', requiresAuth: true }
});

// Fichas - Lista
router.register('/fichas', async (params, query) => {
  try {
    window.appStore.setLoading(true);
    
    const page = parseInt(query.page) || 1;
    const search = query.search || '';
    
    const response = await apiClient.fichas.getAll({ page, search });
    
    loadView('fichas/index', { 
      fichas: response.data.items,
      pagination: response.data.pagination 
    });
    
  } catch (error) {
    window.pwaManager?.showToast('Error al cargar fichas', 'error');
  } finally {
    window.appStore.setLoading(false);
  }
}, {
  middleware: [authMiddleware],
  meta: { title: 'Fichas' }
});

// Fichas - Detalle
router.register('/fichas/:id', async (params) => {
  try {
    window.appStore.setLoading(true);
    
    const ficha = await apiClient.fichas.getById(params.id);
    const aprendices = await apiClient.fichas.getAprendices(params.id);
    
    loadView('fichas/show', { ficha, aprendices });
    
  } catch (error) {
    window.pwaManager?.showToast('Ficha no encontrada', 'error');
    router.navigate('/fichas');
  } finally {
    window.appStore.setLoading(false);
  }
}, {
  middleware: [authMiddleware],
  meta: { title: 'Detalle de Ficha' }
});

// Aprendices - Lista
router.register('/aprendices', async (params, query) => {
  try {
    window.appStore.setLoading(true);
    
    const response = await apiClient.aprendices.getAll({
      page: query.page || 1,
      search: query.search || ''
    });
    
    loadView('aprendices/index', {
      aprendices: response.data.items,
      pagination: response.data.pagination
    });
    
  } catch (error) {
    window.pwaManager?.showToast('Error al cargar aprendices', 'error');
  } finally {
    window.appStore.setLoading(false);
  }
}, {
  middleware: [authMiddleware],
  meta: { title: 'Aprendices' }
});

// QR - Escanear
router.register('/qr/escanear', async () => {
  try {
    window.appStore.setLoading(true);
    
    // Verificar soporte de cámara
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      window.pwaManager?.showToast('Cámara no disponible', 'error');
      router.navigate('/dashboard');
      return;
    }
    
    loadView('qr/escanear');
    
  } catch (error) {
    window.pwaManager?.showToast('Error al cargar escáner QR', 'error');
  } finally {
    window.appStore.setLoading(false);
  }
}, {
  middleware: [authMiddleware],
  meta: { title: 'Escanear QR' }
});

// Anomalías - Registrar
router.register('/anomalias/registrar', async () => {
  try {
    window.appStore.setLoading(true);
    
    const user = userStore.getUser();
    
    // Obtener fichas del instructor si es instructor
    let fichas = [];
    if (user.rol === 'instructor') {
      const response = await apiClient.instructorFichas.getFichasInstructor(user.id);
      fichas = response.data;
    } else {
      // Admin/Coordinador ven todas
      const response = await apiClient.fichas.getAll();
      fichas = response.data.items;
    }
    
    loadView('anomalias/registrar', { fichas });
    
  } catch (error) {
    window.pwaManager?.showToast('Error al cargar registro de anomalías', 'error');
  } finally {
    window.appStore.setLoading(false);
  }
}, {
  middleware: [authMiddleware],
  meta: { title: 'Registrar Anomalías' }
});

// Instructor-Fichas
router.register('/instructor-fichas', async () => {
  try {
    window.appStore.setLoading(true);
    
    const estadisticas = await apiClient.instructorFichas.getEstadisticas();
    const instructores = await apiClient.instructorFichas.getInstructores();
    
    loadView('instructor-fichas/index', { estadisticas, instructores });
    
  } catch (error) {
    window.pwaManager?.showToast('Error al cargar asignaciones', 'error');
  } finally {
    window.appStore.setLoading(false);
  }
}, {
  middleware: [authMiddleware, permissionMiddleware('manage_instructor_fichas')],
  meta: { title: 'Asignación de Fichas' }
});

// Analytics
router.register('/analytics', async () => {
  try {
    window.appStore.setLoading(true);
    
    loadView('analytics/index');
    
  } catch (error) {
    window.pwaManager?.showToast('Error al cargar analytics', 'error');
  } finally {
    window.appStore.setLoading(false);
  }
}, {
  middleware: [authMiddleware, permissionMiddleware('view_analytics')],
  meta: { title: 'Analítica y Reportes' }
});

// Perfil
router.register('/perfil', async () => {
  try {
    window.appStore.setLoading(true);
    
    const user = userStore.getUser();
    loadView('profile/index', { user });
    
  } catch (error) {
    window.pwaManager?.showToast('Error al cargar perfil', 'error');
  } finally {
    window.appStore.setLoading(false);
  }
}, {
  middleware: [authMiddleware],
  meta: { title: 'Mi Perfil' }
});

// 404
router.register('/404', async () => {
  loadView('errors/404');
}, {
  meta: { title: 'Página no encontrada' }
});

// Error genérico
router.register('/error', async () => {
  loadView('errors/500');
}, {
  meta: { title: 'Error' }
});

/**
 * Hooks globales
 */

// Después de navegar - Actualizar título
router.afterNavigate((path, route) => {
  if (route?.route?.meta?.title) {
    document.title = `${route.route.meta.title} - SENAttend`;
  }
  
  // Actualizar estado de navegación activa en el header
  updateActiveNavigation(path);
});

/**
 * Helper para cargar vistas
 */
async function loadView(viewPath, data = {}) {
  const container = document.querySelector('#app-container') || 
                    document.querySelector('.main-content') ||
                    document.querySelector('main');
  
  if (!container) {
    return;
  }
  
  try {
    // En producción, las vistas pueden cargarse dinámicamente
    // Por ahora, usamos el sistema de vistas PHP existente
    
    // Si la vista ya está cargada (SSR), solo actualizar datos
    if (container.dataset.view === viewPath) {
      updateViewData(container, data);
      return;
    }
    
    // Cargar vista nueva
    const response = await fetch(`/views/${viewPath}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    
    if (!response.ok) {
      throw new Error('Error cargando vista');
    }
    
    const html = await response.text();
    container.innerHTML = html;
    container.dataset.view = viewPath;
    
    // Inicializar componentes de la vista
    initializeViewComponents(container, data);
    
  } catch (error) {
    container.innerHTML = `
      <div class="error-message">
        <i class="fas fa-exclamation-triangle"></i>
        <p>Error al cargar la vista</p>
      </div>
    `;
  }
}

/**
 * Actualizar datos de vista existente
 */
function updateViewData(container, data) {
  // Emitir evento para que los componentes se actualicen
  const event = new CustomEvent('view:update', { detail: data });
  container.dispatchEvent(event);
}

/**
 * Inicializar componentes de una vista
 */
function initializeViewComponents(container, data) {
  // Buscar y montar componentes
  const components = container.querySelectorAll('[data-component]');
  
  components.forEach(async (element) => {
    const componentName = element.dataset.component;
    const componentProps = { ...data, ...JSON.parse(element.dataset.props || '{}') };
    
    try {
      await ComponentFactory.mount(componentName, element, componentProps);
    } catch (error) {
      // Error al montar componente - continuar con otros componentes
    }
  });
  
  // Ejecutar scripts de la vista
  const scripts = container.querySelectorAll('script[data-view-script]');
  scripts.forEach(script => {
    eval(script.textContent);
  });
}

/**
 * Actualizar navegación activa en header
 */
function updateActiveNavigation(path) {
  const navLinks = document.querySelectorAll('[data-nav-link]');
  
  navLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (path === href || path.startsWith(href + '/')) {
      link.classList.add('active');
    } else {
      link.classList.remove('active');
    }
  });
}

/**
 * Convertir enlaces normales en SPA links
 */
document.addEventListener('DOMContentLoaded', () => {
  // Convertir enlaces internos automáticamente
  document.body.addEventListener('click', (e) => {
    const link = e.target.closest('a');
    
    if (!link) return;
    
    const href = link.getAttribute('href');
    
    // Ignorar enlaces externos y con target
    if (!href || 
        href.startsWith('http') || 
        href.startsWith('#') ||
        link.hasAttribute('target') ||
        link.hasAttribute('download')) {
      return;
    }
    
    // Convertir a navegación SPA
    e.preventDefault();
    router.navigate(href);
  });
});

export default router;
