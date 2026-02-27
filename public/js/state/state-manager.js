/**
 * State Manager - Gestión de estado global de la aplicación
 * Implementa patrón Observer/PubSub para reactividad
 * 
 * @module StateManager
 * @version 1.0.0
 */

class StateManager {
  constructor() {
    if (StateManager.instance) {
      return StateManager.instance;
    }

    this.state = {};
    this.subscribers = new Map();
    this.middleware = [];
    this.history = [];
    this.maxHistorySize = 50;
    
    StateManager.instance = this;
  }

  /**
   * Obtiene valor del estado
   */
  get(path) {
    return this.getNestedValue(this.state, path);
  }

  /**
   * Establece valor en el estado
   */
  set(path, value) {
    const oldValue = this.get(path);
    
    // Ejecutar middleware
    for (const mw of this.middleware) {
      const result = mw({ path, value, oldValue, state: this.state });
      if (result === false) {
        return; // Middleware canceló la actualización
      }
    }

    // Actualizar estado
    this.setNestedValue(this.state, path, value);
    
    // Agregar al historial
    this.addToHistory({ path, value, oldValue, timestamp: Date.now() });
    
    // Notificar suscriptores
    this.notify(path, value, oldValue);
  }

  /**
   * Actualiza múltiples valores
   */
  update(updates) {
    Object.entries(updates).forEach(([path, value]) => {
      this.set(path, value);
    });
  }

  /**
   * Suscribe un callback a cambios en un path
   */
  subscribe(path, callback) {
    if (!this.subscribers.has(path)) {
      this.subscribers.set(path, new Set());
    }
    
    this.subscribers.get(path).add(callback);
    
    // Retornar función para desuscribirse
    return () => {
      const subs = this.subscribers.get(path);
      if (subs) {
        subs.delete(callback);
      }
    };
  }

  /**
   * Notifica a los suscriptores de cambios
   */
  notify(path, newValue, oldValue) {
    // Notificar suscriptores exactos
    const exactSubs = this.subscribers.get(path);
    if (exactSubs) {
      exactSubs.forEach(callback => {
        try {
          callback(newValue, oldValue, path);
        } catch (error) {
          console.error('[State] Error en callback:', error);
        }
      });
    }

    // Notificar suscriptores de paths padres
    const parts = path.split('.');
    for (let i = parts.length - 1; i > 0; i--) {
      const parentPath = parts.slice(0, i).join('.');
      const parentSubs = this.subscribers.get(parentPath);
      
      if (parentSubs) {
        const parentValue = this.get(parentPath);
        parentSubs.forEach(callback => {
          try {
            callback(parentValue, parentValue, parentPath);
          } catch (error) {
            console.error('[State] Error en callback:', error);
          }
        });
      }
    }
  }

  /**
   * Agrega middleware
   */
  use(middleware) {
    this.middleware.push(middleware);
  }

  /**
   * Resetea el estado
   */
  reset(initialState = {}) {
    const oldState = { ...this.state };
    this.state = initialState;
    this.history = [];
    
    // Notificar a todos los suscriptores
    this.subscribers.forEach((subs, path) => {
      const newValue = this.get(path);
      const oldValue = this.getNestedValue(oldState, path);
      subs.forEach(callback => callback(newValue, oldValue, path));
    });
  }

  /**
   * Helpers para navegación de objetos anidados
   */
  getNestedValue(obj, path) {
    const parts = path.split('.');
    let current = obj;
    
    for (const part of parts) {
      if (current === undefined || current === null) {
        return undefined;
      }
      current = current[part];
    }
    
    return current;
  }

  setNestedValue(obj, path, value) {
    const parts = path.split('.');
    const last = parts.pop();
    let current = obj;
    
    for (const part of parts) {
      if (!(part in current)) {
        current[part] = {};
      }
      current = current[part];
    }
    
    current[last] = value;
  }

  /**
   * Agrega al historial
   */
  addToHistory(entry) {
    this.history.push(entry);
    
    if (this.history.length > this.maxHistorySize) {
      this.history.shift();
    }
  }

  /**
   * Obtiene el historial
   */
  getHistory() {
    return [...this.history];
  }

  /**
   * Exporta el estado actual
   */
  export() {
    return JSON.parse(JSON.stringify(this.state));
  }

  /**
   * Importa un estado
   */
  import(newState) {
    this.reset(newState);
  }

  /**
   * Persiste estado en localStorage
   */
  persist(key = 'senattend_state') {
    try {
      localStorage.setItem(key, JSON.stringify(this.state));
    } catch (error) {
      console.error('[State] Error al persistir:', error);
    }
  }

  /**
   * Restaura estado desde localStorage
   */
  restore(key = 'senattend_state') {
    try {
      const saved = localStorage.getItem(key);
      if (saved) {
        this.import(JSON.parse(saved));
        return true;
      }
    } catch (error) {
      console.error('[State] Error al restaurar:', error);
    }
    return false;
  }
}

/**
 * Store específico para usuario
 */
class UserStore {
  constructor(stateManager) {
    this.state = stateManager;
    this.init();
  }

  init() {
    // Inicializar estado de usuario
    this.state.set('user', {
      isAuthenticated: false,
      data: null,
      permissions: [],
      role: null
    });
  }

  setUser(userData) {
    this.state.update({
      'user.isAuthenticated': true,
      'user.data': userData,
      'user.role': userData.rol,
      'user.permissions': userData.permissions || []
    });
  }

  clearUser() {
    this.state.update({
      'user.isAuthenticated': false,
      'user.data': null,
      'user.role': null,
      'user.permissions': []
    });
  }

  getUser() {
    return this.state.get('user.data');
  }

  isAuthenticated() {
    return this.state.get('user.isAuthenticated');
  }

  hasRole(role) {
    return this.state.get('user.role') === role;
  }

  hasPermission(permission) {
    const permissions = this.state.get('user.permissions');
    return permissions && permissions.includes(permission);
  }
}

/**
 * Store para datos de la aplicación
 */
class AppStore {
  constructor(stateManager) {
    this.state = stateManager;
    this.init();
  }

  init() {
    this.state.set('app', {
      isLoading: false,
      isOnline: navigator.onLine,
      notifications: [],
      syncQueue: [],
      lastSync: null
    });
  }

  setLoading(isLoading) {
    this.state.set('app.isLoading', isLoading);
  }

  setOnline(isOnline) {
    this.state.set('app.isOnline', isOnline);
  }

  addNotification(notification) {
    const notifications = this.state.get('app.notifications') || [];
    notifications.push({
      id: Date.now(),
      ...notification,
      timestamp: new Date().toISOString()
    });
    this.state.set('app.notifications', notifications);
  }

  removeNotification(id) {
    const notifications = this.state.get('app.notifications') || [];
    this.state.set('app.notifications', notifications.filter(n => n.id !== id));
  }

  addToSyncQueue(item) {
    const queue = this.state.get('app.syncQueue') || [];
    queue.push(item);
    this.state.set('app.syncQueue', queue);
  }

  clearSyncQueue() {
    this.state.set('app.syncQueue', []);
  }

  setLastSync(timestamp) {
    this.state.set('app.lastSync', timestamp);
  }
}

// Instancia singleton
const stateManager = new StateManager();
const userStore = new UserStore(stateManager);
const appStore = new AppStore(stateManager);

// Middleware para logging (solo en desarrollo)
if (window.location.hostname === 'localhost') {
  stateManager.use(({ path, value, oldValue }) => {
    console.log('[State]', path, ':', oldValue, '->', value);
  });
}

// Persistir estado automáticamente
stateManager.subscribe('user', () => {
  stateManager.persist();
});

// Restaurar estado al cargar
stateManager.restore();

export { stateManager as default, userStore, appStore };
