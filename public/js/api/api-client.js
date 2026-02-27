/**
 * API Client - Cliente HTTP para comunicación con el backend
 * Implementa patrón Singleton y manejo de errores robusto
 * Soporte offline con cola de sincronización
 * 
 * @module ApiClient
 * @version 1.0.0
 */

class ApiClient {
  constructor() {
    if (ApiClient.instance) {
      return ApiClient.instance;
    }

    this.baseURL = window.location.origin;
    this.defaultHeaders = {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    };
    
    this.requestInterceptors = [];
    this.responseInterceptors = [];
    this.errorHandlers = [];
    
    ApiClient.instance = this;
  }

  /**
   * Configuración de headers por defecto
   */
  setDefaultHeader(key, value) {
    this.defaultHeaders[key] = value;
  }

  /**
   * Agrega un interceptor de request
   */
  addRequestInterceptor(callback) {
    this.requestInterceptors.push(callback);
  }

  /**
   * Agrega un interceptor de response
   */
  addResponseInterceptor(callback) {
    this.responseInterceptors.push(callback);
  }

  /**
   * Agrega un manejador de errores global
   */
  addErrorHandler(callback) {
    this.errorHandlers.push(callback);
  }

  /**
   * Realiza petición HTTP genérica
   */
  async request(method, endpoint, options = {}) {
    const {
      data = null,
      headers = {},
      params = {},
      timeout = 30000,
      retries = 3,
      retryDelay = 1000,
      offlineQueue = true
    } = options;

    // Construir URL con query params
    const url = new URL(endpoint, this.baseURL);
    Object.keys(params).forEach(key => {
      url.searchParams.append(key, params[key]);
    });

    // Construir config de fetch
    const config = {
      method,
      headers: {
        ...this.defaultHeaders,
        ...headers
      },
      credentials: 'same-origin'
    };

    // Agregar body si existe
    if (data) {
      if (data instanceof FormData) {
        delete config.headers['Content-Type']; // Dejar que el browser lo establezca
        config.body = data;
      } else {
        config.body = JSON.stringify(data);
      }
    }

    // Ejecutar interceptores de request
    for (const interceptor of this.requestInterceptors) {
      await interceptor(config);
    }

    // Intentar petición con reintentos
    let lastError;
    for (let attempt = 0; attempt < retries; attempt++) {
      try {
        const response = await this.fetchWithTimeout(url.toString(), config, timeout);
        
        // Ejecutar interceptores de response
        for (const interceptor of this.responseInterceptors) {
          await interceptor(response);
        }

        // Parsear respuesta
        const result = await this.parseResponse(response);
        
        if (!response.ok) {
          throw new ApiError(result.message || 'Request failed', response.status, result);
        }

        return result;
        
      } catch (error) {
        lastError = error;
        
        // Si es el último intento o no es un error de red, lanzar error
        if (attempt === retries - 1 || !this.isNetworkError(error)) {
          break;
        }
        
        // Esperar antes del siguiente intento
        await this.delay(retryDelay * Math.pow(2, attempt));
      }
    }

    // Si estamos offline y está habilitada la cola
    if (offlineQueue && !navigator.onLine) {
      console.log('[API] Sin conexión, agregando a cola:', endpoint);
      await this.addToOfflineQueue(method, endpoint, data);
      throw new OfflineError('Sin conexión. La operación se sincronizará automáticamente.');
    }

    // Manejar error
    this.handleError(lastError);
    throw lastError;
  }

  /**
   * Fetch con timeout
   */
  fetchWithTimeout(url, config, timeout) {
    return Promise.race([
      fetch(url, config),
      new Promise((_, reject) =>
        setTimeout(() => reject(new Error('Request timeout')), timeout)
      )
    ]);
  }

  /**
   * Parsea la respuesta según el content-type
   */
  async parseResponse(response) {
    const contentType = response.headers.get('content-type');
    
    if (contentType && contentType.includes('application/json')) {
      return await response.json();
    }
    
    if (contentType && contentType.includes('text/')) {
      return await response.text();
    }
    
    return await response.blob();
  }

  /**
   * Verifica si es un error de red
   */
  isNetworkError(error) {
    return error instanceof TypeError ||
           error.message === 'Failed to fetch' ||
           error.message === 'Network request failed' ||
           error.message === 'Request timeout';
  }

  /**
   * Maneja errores globalmente
   */
  handleError(error) {
    console.error('[API] Error:', error);
    
    for (const handler of this.errorHandlers) {
      handler(error);
    }
  }

  /**
   * Delay helper
   */
  delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  /**
   * Agrega operación a la cola offline
   */
  async addToOfflineQueue(method, endpoint, data) {
    if (window.pwaManager) {
      const operation = this.getOperationType(endpoint);
      if (operation) {
        await window.pwaManager.addToSyncQueue(operation, {
          method,
          endpoint,
          data
        });
      }
    }
  }

  /**
   * Determina el tipo de operación para la cola
   */
  getOperationType(endpoint) {
    if (endpoint.includes('/asistencia/guardar')) return 'asistencias';
    if (endpoint.includes('/anomalia')) return 'anomalias';
    return null;
  }

  /**
   * Métodos HTTP shortcuts
   */
  get(endpoint, options = {}) {
    return this.request('GET', endpoint, options);
  }

  post(endpoint, data, options = {}) {
    return this.request('POST', endpoint, { ...options, data });
  }

  put(endpoint, data, options = {}) {
    return this.request('PUT', endpoint, { ...options, data });
  }

  delete(endpoint, options = {}) {
    return this.request('DELETE', endpoint, options);
  }

  patch(endpoint, data, options = {}) {
    return this.request('PATCH', endpoint, { ...options, data });
  }

  /**
   * API Resources - Métodos específicos por recurso
   */

  // Fichas
  fichas = {
    getAll: (params) => this.get('/api/fichas', { params }),
    getById: (id) => this.get(`/api/fichas/${id}`),
    create: (data) => this.post('/api/fichas', data),
    update: (id, data) => this.put(`/api/fichas/${id}`, data),
    delete: (id) => this.delete(`/api/fichas/${id}`),
    getAprendices: (id) => this.get(`/api/fichas/${id}/aprendices`),
    getEstadisticas: () => this.get('/api/fichas/estadisticas'),
    search: (query) => this.get('/api/fichas/search', { params: { q: query } }),
    cambiarEstado: (id, estado) => this.post(`/api/fichas/${id}/estado`, { estado }),
    importarCSV: (file) => {
      const formData = new FormData();
      formData.append('file', file);
      return this.post('/api/fichas/importar', formData);
    }
  };

  // Aprendices
  aprendices = {
    getAll: (params) => this.get('/api/aprendices', { params }),
    getById: (id) => this.get(`/api/aprendices/${id}`),
    create: (data) => this.post('/api/aprendices', data),
    update: (id, data) => this.put(`/api/aprendices/${id}`, data),
    delete: (id) => this.delete(`/api/aprendices/${id}`),
    getEstadisticas: () => this.get('/api/aprendices/estadisticas'),
    cambiarEstado: (id, estado) => this.post(`/api/aprendices/${id}/estado`, { estado }),
    vincularFicha: (id, fichaId) => this.post(`/api/aprendices/${id}/vincular`, { ficha_id: fichaId }),
    desvincularFicha: (id) => this.post(`/api/aprendices/${id}/desvincular`),
    vincularMultiples: (aprendizIds, fichaId) => this.post('/api/aprendices/vincular-multiples', {
      aprendiz_ids: aprendizIds,
      ficha_id: fichaId
    }),
    importarCSV: (file) => {
      const formData = new FormData();
      formData.append('file', file);
      return this.post('/api/aprendices/importar', formData);
    }
  };

  // Asistencias
  asistencias = {
    guardar: (data) => this.post('/asistencia/guardar', data, { offlineQueue: true }),
    getAprendices: (fichaId) => this.get(`/api/asistencia/aprendices/${fichaId}`),
    getAnomalias: (params) => this.get('/api/asistencia/anomalias', { params }),
    getTiposAnomalias: () => this.get('/api/asistencia/anomalias/tipos'),
    registrarAnomaliaAprendiz: (data) => this.post('/api/asistencia/anomalia/aprendiz', data, { 
      offlineQueue: true 
    }),
    registrarAnomaliaFicha: (data) => this.post('/api/asistencia/anomalia/ficha', data, {
      offlineQueue: true
    })
  };

  // QR
  qr = {
    buscarAprendiz: (params) => this.get('/api/qr/buscar', { params }),
    procesar: (data) => this.post('/api/qr/procesar', data),
    historialDiario: (params) => this.get('/api/qr/historial-diario', { params })
  };

  // Instructor-Fichas
  instructorFichas = {
    getEstadisticas: () => this.get('/api/instructor-fichas/estadisticas'),
    getInstructores: () => this.get('/api/instructores'),
    getFichasInstructor: (id) => this.get(`/api/instructor-fichas/instructor/${id}/fichas`),
    getInstructoresFicha: (id) => this.get(`/api/instructor-fichas/ficha/${id}/instructores`),
    getFichasDisponibles: (instructorId) => 
      this.get(`/api/instructor-fichas/fichas-disponibles/${instructorId}`),
    getInstructoresDisponibles: (fichaId) => 
      this.get(`/api/instructor-fichas/instructores-disponibles/${fichaId}`),
    asignarFichas: (instructorId, fichaIds) => 
      this.post('/api/instructor-fichas/asignar-fichas', { instructor_id: instructorId, ficha_ids: fichaIds }),
    asignarInstructores: (fichaId, instructorIds) => 
      this.post('/api/instructor-fichas/asignar-instructores', { ficha_id: fichaId, instructor_ids: instructorIds }),
    eliminarAsignacion: (data) => this.post('/api/instructor-fichas/eliminar', data),
    getLiderFicha: (id) => this.get(`/api/instructor-fichas/ficha/${id}/lider`),
    getFichasLider: (id) => this.get(`/api/instructor-fichas/lideres/${id}/fichas`),
    eliminarLider: (data) => this.post('/api/instructor-fichas/lideres/eliminar', data),
    sincronizarFichas: (instructorId) => 
      this.post('/api/instructor-fichas/sincronizar', { instructor_id: instructorId })
  };

  // Configuración de turnos
  configuracion = {
    getTurnos: () => this.get('/api/configuracion/turnos'),
    getTurnoActual: () => this.get('/api/configuracion/turno-actual')
  };

  // Portero
  portero = {
    getIngresosActivos: () => this.get('/api/portero/ingresos-activos'),
    procesarQR: (data) => this.post('/api/portero/procesar-qr', data)
  };

  // Analytics
  analytics = {
    generarSemanal: (data) => this.post('/analytics/generar-semanal', data),
    generarMensual: (data) => this.post('/analytics/generar-mensual', data)
  };
}

/**
 * Error personalizado para la API
 */
class ApiError extends Error {
  constructor(message, status, data) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.data = data;
  }
}

/**
 * Error de conexión offline
 */
class OfflineError extends Error {
  constructor(message) {
    super(message);
    this.name = 'OfflineError';
  }
}

// Instancia singleton
const apiClient = new ApiClient();

// Configurar interceptores globales
apiClient.addErrorHandler((error) => {
  if (error instanceof ApiError) {
    if (error.status === 401) {
      console.log('[API] No autorizado, redirigiendo al login...');
      window.location.href = '/login';
    } else if (error.status === 403) {
      console.log('[API] Acceso prohibido');
      if (window.pwaManager) {
        window.pwaManager.showToast('No tienes permisos para esta acción', 'error');
      }
    } else if (error.status >= 500) {
      console.log('[API] Error del servidor');
      if (window.pwaManager) {
        window.pwaManager.showToast('Error del servidor. Intenta más tarde', 'error');
      }
    }
  } else if (error instanceof OfflineError) {
    if (window.pwaManager) {
      window.pwaManager.showToast(error.message, 'warning');
    }
  }
});

// Exportar
export { apiClient as default, ApiError, OfflineError };
