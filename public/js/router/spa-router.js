/**
 * Router SPA - Sistema de enrutamiento client-side
 * Implementa navegación sin recargar página
 * 
 * @module Router
 * @version 1.0.0
 */

class Router {
  constructor() {
    if (Router.instance) {
      return Router.instance;
    }

    this.routes = new Map();
    this.middlewares = [];
    this.currentRoute = null;
    this.history = [];
    this.beforeNavigateHooks = [];
    this.afterNavigateHooks = [];
    
    this.init();
    Router.instance = this;
  }

  /**
   * Inicializa el router
   */
  init() {
    // Interceptar clicks en enlaces
    document.addEventListener('click', (e) => {
      const link = e.target.closest('a[data-spa-link]');
      if (link) {
        e.preventDefault();
        const href = link.getAttribute('href');
        this.navigate(href);
      }
    });

    // Manejar botón atrás/adelante del navegador
    window.addEventListener('popstate', (e) => {
      if (e.state && e.state.path) {
        this.loadRoute(e.state.path, false);
      }
    });

    // Cargar ruta inicial
    this.loadRoute(window.location.pathname, true);
  }

  /**
   * Registra una ruta
   */
  register(path, handler, options = {}) {
    const { middleware = [], meta = {} } = options;
    
    this.routes.set(path, {
      handler,
      middleware,
      meta,
      pattern: this.pathToRegex(path)
    });
  }

  /**
   * Convierte path a regex para matching
   */
  pathToRegex(path) {
    return new RegExp(
      '^' + path.replace(/:[^\s/]+/g, '([^/]+)') + '$'
    );
  }

  /**
   * Extrae parámetros del path
   */
  extractParams(route, path) {
    const match = path.match(route.pattern);
    if (!match) return {};

    const keys = route.handler.toString()
      .match(/\(([^)]*)\)/)[1]
      .split(',')
      .map(s => s.trim());

    const params = {};
    keys.forEach((key, index) => {
      if (key && match[index + 1]) {
        params[key] = match[index + 1];
      }
    });

    return params;
  }

  /**
   * Encuentra la ruta que coincide con el path
   */
  findRoute(path) {
    for (const [routePath, route] of this.routes) {
      if (route.pattern.test(path)) {
        return { ...route, path: routePath };
      }
    }
    return null;
  }

  /**
   * Navega a una ruta
   */
  async navigate(path, state = {}) {
    // Ejecutar hooks antes de navegar
    for (const hook of this.beforeNavigateHooks) {
      const result = await hook(path, state);
      if (result === false) {
        return; // Cancelar navegación
      }
    }

    // Agregar a historial del navegador
    window.history.pushState({ path, ...state }, '', path);
    
    // Cargar ruta
    await this.loadRoute(path, true);
  }

  /**
   * Carga y ejecuta una ruta
   */
  async loadRoute(path, addToHistory = true) {
    const route = this.findRoute(path);

    if (!route) {
      console.error('[Router] Ruta no encontrada:', path);
      this.navigate('/404');
      return;
    }

    // Extraer parámetros
    const params = this.extractParams(route, path);
    const query = this.parseQueryString(window.location.search);

    // Ejecutar middlewares globales
    for (const middleware of this.middlewares) {
      const result = await middleware({ path, params, query, route });
      if (result === false) {
        return; // Middleware canceló la navegación
      }
    }

    // Ejecutar middlewares de la ruta
    for (const middleware of route.middleware) {
      const result = await middleware({ path, params, query, route });
      if (result === false) {
        return;
      }
    }

    // Guardar ruta actual
    this.currentRoute = { path, params, query, route };
    
    // Agregar al historial interno
    if (addToHistory) {
      this.history.push(path);
    }

    // Ejecutar handler de la ruta
    try {
      await route.handler(params, query, route.meta);
      
      // Ejecutar hooks después de navegar
      for (const hook of this.afterNavigateHooks) {
        await hook(path, this.currentRoute);
      }
      
      // Scroll al inicio
      window.scrollTo(0, 0);
      
    } catch (error) {
      console.error('[Router] Error al cargar ruta:', error);
      this.navigate('/error');
    }
  }

  /**
   * Parsea query string
   */
  parseQueryString(search) {
    const params = new URLSearchParams(search);
    const query = {};
    
    for (const [key, value] of params) {
      query[key] = value;
    }
    
    return query;
  }

  /**
   * Agrega middleware global
   */
  use(middleware) {
    this.middlewares.push(middleware);
  }

  /**
   * Agrega hook antes de navegar
   */
  beforeNavigate(hook) {
    this.beforeNavigateHooks.push(hook);
  }

  /**
   * Agrega hook después de navegar
   */
  afterNavigate(hook) {
    this.afterNavigateHooks.push(hook);
  }

  /**
   * Vuelve atrás en el historial
   */
  back() {
    window.history.back();
  }

  /**
   * Avanza en el historial
   */
  forward() {
    window.history.forward();
  }

  /**
   * Reemplaza la ruta actual
   */
  replace(path, state = {}) {
    window.history.replaceState({ path, ...state }, '', path);
    this.loadRoute(path, false);
  }

  /**
   * Obtiene la ruta actual
   */
  getCurrentRoute() {
    return this.currentRoute;
  }

  /**
   * Verifica si una ruta está activa
   */
  isActive(path) {
    return this.currentRoute && this.currentRoute.path === path;
  }
}

// Instancia singleton
const router = new Router();

// Exportar
export default router;
