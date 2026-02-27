/**
 * Sync Manager - Gestión de sincronización offline/online
 * Maneja la cola de operaciones pendientes y sincronización con el servidor
 * 
 * @module SyncManager
 * @version 1.0.0
 */

class SyncManager {
  constructor() {
    if (SyncManager.instance) {
      return SyncManager.instance;
    }

    this.db = null;
    this.syncInProgress = false;
    this.syncQueue = [];
    this.retryAttempts = 3;
    this.retryDelay = 2000;
    this.listeners = new Map();
    
    this.init();
    SyncManager.instance = this;
  }

  /**
   * Inicializa el Sync Manager
   */
  async init() {
    await this.openDatabase();
    this.setupNetworkListeners();
    this.setupPeriodicSync();
    
    // Sincronizar al iniciar si hay conexión
    if (navigator.onLine) {
      this.syncAll();
    }
    
    console.log('[Sync] Sync Manager inicializado');
  }

  /**
   * Abre la base de datos IndexedDB
   */
  async openDatabase() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open('SENAttendSyncDB', 2);
      
      request.onerror = () => reject(request.error);
      request.onsuccess = () => {
        this.db = request.result;
        resolve(this.db);
      };
      
      request.onupgradeneeded = (event) => {
        const db = event.target.result;
        
        // Store para asistencias pendientes
        if (!db.objectStoreNames.contains('asistencias_pending')) {
          const store = db.createObjectStore('asistencias_pending', { 
            keyPath: 'id', 
            autoIncrement: true 
          });
          store.createIndex('timestamp', 'timestamp', { unique: false });
          store.createIndex('status', 'status', { unique: false });
        }
        
        // Store para anomalías pendientes
        if (!db.objectStoreNames.contains('anomalias_pending')) {
          const store = db.createObjectStore('anomalias_pending', { 
            keyPath: 'id', 
            autoIncrement: true 
          });
          store.createIndex('timestamp', 'timestamp', { unique: false });
          store.createIndex('status', 'status', { unique: false });
        }
        
        // Store para historial de sincronización
        if (!db.objectStoreNames.contains('sync_history')) {
          const store = db.createObjectStore('sync_history', { 
            keyPath: 'id', 
            autoIncrement: true 
          });
          store.createIndex('timestamp', 'timestamp', { unique: false });
          store.createIndex('type', 'type', { unique: false });
        }
        
        // Store para cache de datos
        if (!db.objectStoreNames.contains('data_cache')) {
          const store = db.createObjectStore('data_cache', { keyPath: 'key' });
          store.createIndex('expires', 'expires', { unique: false });
        }
      };
    });
  }

  /**
   * Configura listeners de red
   */
  setupNetworkListeners() {
    window.addEventListener('online', () => {
      console.log('[Sync] Conexión restaurada, iniciando sincronización...');
      this.syncAll();
    });
  }

  /**
   * Configura sincronización periódica
   */
  setupPeriodicSync() {
    // Sincronizar cada 5 minutos si hay conexión
    setInterval(() => {
      if (navigator.onLine && !this.syncInProgress) {
        this.syncAll();
      }
    }, 5 * 60 * 1000);
  }

  /**
   * Agrega operación a la cola de sincronización
   */
  async addToQueue(type, data, priority = 'normal') {
    const record = {
      type,
      data,
      priority,
      status: 'pending',
      attempts: 0,
      timestamp: new Date().toISOString(),
      createdAt: Date.now()
    };

    try {
      const id = await this.saveToStore(`${type}_pending`, record);
      record.id = id;
      
      console.log(`[Sync] Agregado a cola: ${type} #${id}`);
      
      // Notificar listeners
      this.notify('queue_add', { type, id, record });
      
      // Intentar sincronizar inmediatamente si hay conexión
      if (navigator.onLine) {
        await this.syncItem(type, record);
      }
      
      return id;
    } catch (error) {
      console.error('[Sync] Error al agregar a cola:', error);
      throw error;
    }
  }

  /**
   * Sincroniza todas las operaciones pendientes
   */
  async syncAll() {
    if (this.syncInProgress) {
      console.log('[Sync] Sincronización ya en progreso');
      return;
    }

    if (!navigator.onLine) {
      console.log('[Sync] Sin conexión, sincronización cancelada');
      return;
    }

    this.syncInProgress = true;
    this.notify('sync_start', {});

    try {
      // Sincronizar asistencias
      await this.syncType('asistencias');
      
      // Sincronizar anomalías
      await this.syncType('anomalias');
      
      // Limpiar cache expirado
      await this.cleanExpiredCache();
      
      this.notify('sync_complete', { success: true });
      console.log('[Sync] Sincronización completada');
      
    } catch (error) {
      console.error('[Sync] Error en sincronización:', error);
      this.notify('sync_error', { error });
    } finally {
      this.syncInProgress = false;
    }
  }

  /**
   * Sincroniza un tipo específico de operaciones
   */
  async syncType(type) {
    const storeName = `${type}_pending`;
    const pendingRecords = await this.getAllFromStore(storeName);
    
    if (pendingRecords.length === 0) {
      return;
    }

    console.log(`[Sync] Sincronizando ${pendingRecords.length} ${type}...`);
    
    const results = {
      success: 0,
      failed: 0,
      total: pendingRecords.length
    };

    for (const record of pendingRecords) {
      try {
        await this.syncItem(type, record);
        results.success++;
      } catch (error) {
        console.error(`[Sync] Error al sincronizar ${type} #${record.id}:`, error);
        results.failed++;
      }
    }

    this.notify('sync_type_complete', { type, results });
    return results;
  }

  /**
   * Sincroniza un item individual
   */
  async syncItem(type, record) {
    const endpoints = {
      asistencias: '/asistencia/guardar',
      anomalias: '/api/asistencia/anomalia/aprendiz'
    };

    const endpoint = endpoints[type];
    if (!endpoint) {
      throw new Error(`Tipo de sincronización no soportado: ${type}`);
    }

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(record.data)
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const result = await response.json();
      
      // Eliminar de la cola
      await this.deleteFromStore(`${type}_pending`, record.id);
      
      // Guardar en historial
      await this.addToHistory({
        type,
        recordId: record.id,
        status: 'success',
        data: record.data,
        result
      });
      
      console.log(`[Sync] ${type} #${record.id} sincronizado exitosamente`);
      this.notify('item_synced', { type, id: record.id, result });
      
      return result;
      
    } catch (error) {
      // Actualizar intentos
      record.attempts++;
      record.lastAttempt = new Date().toISOString();
      record.lastError = error.message;
      
      if (record.attempts >= this.retryAttempts) {
        record.status = 'failed';
        this.notify('item_failed', { type, id: record.id, error });
      } else {
        record.status = 'retry';
      }
      
      await this.updateInStore(`${type}_pending`, record);
      throw error;
    }
  }

  /**
   * Operaciones con IndexedDB
   */
  async saveToStore(storeName, data) {
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const request = store.add(data);
      
      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result);
    });
  }

  async updateInStore(storeName, data) {
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const request = store.put(data);
      
      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result);
    });
  }

  async deleteFromStore(storeName, id) {
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const request = store.delete(id);
      
      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve();
    });
  }

  async getAllFromStore(storeName) {
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([storeName], 'readonly');
      const store = transaction.objectStore(storeName);
      const request = store.getAll();
      
      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result);
    });
  }

  async getFromStore(storeName, id) {
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([storeName], 'readonly');
      const store = transaction.objectStore(storeName);
      const request = store.get(id);
      
      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result);
    });
  }

  /**
   * Gestión de cache
   */
  async cacheData(key, data, ttl = 3600000) {
    const cacheEntry = {
      key,
      data,
      expires: Date.now() + ttl,
      timestamp: Date.now()
    };

    await this.updateInStore('data_cache', cacheEntry);
  }

  async getCachedData(key) {
    const entry = await this.getFromStore('data_cache', key);
    
    if (!entry) return null;
    
    if (entry.expires < Date.now()) {
      await this.deleteFromStore('data_cache', key);
      return null;
    }
    
    return entry.data;
  }

  async cleanExpiredCache() {
    const allCache = await this.getAllFromStore('data_cache');
    const now = Date.now();
    
    for (const entry of allCache) {
      if (entry.expires < now) {
        await this.deleteFromStore('data_cache', entry.key);
      }
    }
  }

  /**
   * Historial de sincronización
   */
  async addToHistory(entry) {
    const historyEntry = {
      ...entry,
      timestamp: new Date().toISOString()
    };

    await this.saveToStore('sync_history', historyEntry);
  }

  async getHistory(limit = 100) {
    const all = await this.getAllFromStore('sync_history');
    return all.slice(-limit).reverse();
  }

  /**
   * Estadísticas
   */
  async getStats() {
    const asistenciasPending = await this.getAllFromStore('asistencias_pending');
    const anomaliasPending = await this.getAllFromStore('anomalias_pending');
    const history = await this.getHistory(50);

    return {
      pending: {
        asistencias: asistenciasPending.length,
        anomalias: anomaliasPending.length,
        total: asistenciasPending.length + anomaliasPending.length
      },
      history: history.length,
      lastSync: history[0]?.timestamp || null,
      isOnline: navigator.onLine,
      syncInProgress: this.syncInProgress
    };
  }

  /**
   * Sistema de eventos
   */
  on(event, callback) {
    if (!this.listeners.has(event)) {
      this.listeners.set(event, new Set());
    }
    this.listeners.get(event).add(callback);
    
    return () => {
      const listeners = this.listeners.get(event);
      if (listeners) {
        listeners.delete(callback);
      }
    };
  }

  notify(event, data) {
    const listeners = this.listeners.get(event);
    if (listeners) {
      listeners.forEach(callback => {
        try {
          callback(data);
        } catch (error) {
          console.error('[Sync] Error en listener:', error);
        }
      });
    }
  }

  /**
   * Limpieza
   */
  async clearAll() {
    const stores = [
      'asistencias_pending',
      'anomalias_pending',
      'sync_history',
      'data_cache'
    ];

    for (const storeName of stores) {
      const transaction = this.db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      await store.clear();
    }

    console.log('[Sync] Todos los datos locales eliminados');
  }
}

// Instancia singleton
const syncManager = new SyncManager();

// Exportar
export default syncManager;
