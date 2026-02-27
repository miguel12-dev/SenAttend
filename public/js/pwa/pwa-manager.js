/**
 * PWA Manager - Gestión de funcionalidades PWA
 * Maneja registro de Service Worker, instalación y actualizaciones
 * 
 * @module PWAManager
 * @version 1.0.0
 */

class PWAManager {
  constructor() {
    this.swRegistration = null;
    this.deferredPrompt = null;
    this.isOnline = navigator.onLine;
    this.syncQueue = [];
    
    this.init();
  }

  /**
   * Inicializa el PWA Manager
   */
  async init() {
    if ('serviceWorker' in navigator) {
      await this.registerServiceWorker();
      this.setupUpdateListener();
    }
    
    this.setupInstallPrompt();
    this.setupNetworkListener();
    this.setupSyncManager();
    this.setupNotifications();
    
    console.log('[PWA] PWA Manager inicializado');
  }

  /**
   * Registra el Service Worker
   */
  async registerServiceWorker() {
    try {
      this.swRegistration = await navigator.serviceWorker.register('/sw.js', {
        scope: '/'
      });
      
      console.log('[PWA] Service Worker registrado:', this.swRegistration.scope);
      
      // Verificar actualizaciones cada hora
      setInterval(() => {
        this.swRegistration.update();
      }, 60 * 60 * 1000);
      
      return this.swRegistration;
    } catch (error) {
      console.error('[PWA] Error al registrar Service Worker:', error);
      throw error;
    }
  }

  /**
   * Configura el listener para actualizaciones del SW
   */
  setupUpdateListener() {
    if (!this.swRegistration) return;
    
    this.swRegistration.addEventListener('updatefound', () => {
      const newWorker = this.swRegistration.installing;
      
      newWorker.addEventListener('statechange', () => {
        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
          this.showUpdateNotification();
        }
      });
    });
  }

  /**
   * Muestra notificación de actualización disponible
   */
  showUpdateNotification() {
    const notification = document.createElement('div');
    notification.className = 'pwa-update-notification';
    notification.innerHTML = `
      <div class="pwa-update-content">
        <i class="fas fa-sync-alt"></i>
        <span>Nueva versión disponible</span>
        <button id="pwa-update-btn" class="btn-sena">Actualizar</button>
        <button id="pwa-dismiss-btn" class="btn-sena-secondary">Después</button>
      </div>
    `;
    
    document.body.appendChild(notification);
    
    document.getElementById('pwa-update-btn').addEventListener('click', () => {
      this.updateServiceWorker();
      notification.remove();
    });
    
    document.getElementById('pwa-dismiss-btn').addEventListener('click', () => {
      notification.remove();
    });
  }

  /**
   * Actualiza el Service Worker
   */
  async updateServiceWorker() {
    if (!this.swRegistration || !this.swRegistration.waiting) return;
    
    this.swRegistration.waiting.postMessage({ type: 'SKIP_WAITING' });
    
    window.location.reload();
  }

  /**
   * Configura el prompt de instalación
   */
  setupInstallPrompt() {
    window.addEventListener('beforeinstallprompt', (event) => {
      event.preventDefault();
      this.deferredPrompt = event;
      
      // Mostrar botón de instalación personalizado
      this.showInstallButton();
      
      console.log('[PWA] Prompt de instalación disponible');
    });

    window.addEventListener('appinstalled', () => {
      console.log('[PWA] App instalada exitosamente');
      this.deferredPrompt = null;
      this.hideInstallButton();
      
      this.showToast('¡SENAttend instalado exitosamente!', 'success');
    });
  }

  /**
   * Muestra botón de instalación
   */
  showInstallButton() {
    const installBtn = document.getElementById('pwa-install-btn');
    if (installBtn) {
      installBtn.style.display = 'block';
      installBtn.addEventListener('click', () => this.promptInstall());
    }
  }

  /**
   * Oculta botón de instalación
   */
  hideInstallButton() {
    const installBtn = document.getElementById('pwa-install-btn');
    if (installBtn) {
      installBtn.style.display = 'none';
    }
  }

  /**
   * Solicita instalación de la PWA
   */
  async promptInstall() {
    if (!this.deferredPrompt) {
      console.log('[PWA] No hay prompt de instalación disponible');
      return;
    }

    this.deferredPrompt.prompt();
    
    const { outcome } = await this.deferredPrompt.userChoice;
    console.log('[PWA] Resultado de instalación:', outcome);
    
    if (outcome === 'accepted') {
      this.deferredPrompt = null;
    }
  }

  /**
   * Configura listeners de red
   */
  setupNetworkListener() {
    window.addEventListener('online', () => {
      console.log('[PWA] Conectado a internet');
      this.isOnline = true;
      this.showToast('Conexión restaurada', 'success');
      this.processSyncQueue();
    });

    window.addEventListener('offline', () => {
      console.log('[PWA] Sin conexión a internet');
      this.isOnline = false;
      this.showToast('Sin conexión - Los datos se guardarán localmente', 'warning');
    });
  }

  /**
   * Configura el Background Sync Manager
   */
  setupSyncManager() {
    if ('sync' in this.swRegistration) {
      console.log('[PWA] Background Sync disponible');
    } else {
      console.log('[PWA] Background Sync no disponible, usando fallback');
    }
  }

  /**
   * Agrega operación a la cola de sincronización
   */
  async addToSyncQueue(operation, data) {
    const record = {
      id: Date.now(),
      operation,
      data,
      timestamp: new Date().toISOString()
    };
    
    // Guardar en IndexedDB
    await this.saveToIndexedDB(operation, record);
    
    // Intentar sincronizar con Background Sync API
    if ('sync' in this.swRegistration) {
      try {
        await this.swRegistration.sync.register(`sync-${operation}`);
        console.log('[PWA] Sincronización registrada:', operation);
      } catch (error) {
        console.error('[PWA] Error al registrar sincronización:', error);
        this.syncQueue.push(record);
      }
    } else {
      // Fallback: agregar a cola en memoria
      this.syncQueue.push(record);
    }
    
    return record.id;
  }

  /**
   * Procesa la cola de sincronización
   */
  async processSyncQueue() {
    if (!this.isOnline || this.syncQueue.length === 0) return;
    
    console.log('[PWA] Procesando cola de sincronización:', this.syncQueue.length);
    
    const queue = [...this.syncQueue];
    this.syncQueue = [];
    
    for (const record of queue) {
      try {
        await this.syncRecord(record);
      } catch (error) {
        console.error('[PWA] Error al sincronizar registro:', error);
        this.syncQueue.push(record);
      }
    }
  }

  /**
   * Sincroniza un registro individual
   */
  async syncRecord(record) {
    const endpoints = {
      asistencias: '/asistencia/guardar',
      anomalias: '/api/asistencia/anomalia/aprendiz'
    };
    
    const endpoint = endpoints[record.operation];
    if (!endpoint) return;
    
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(record.data)
    });
    
    if (!response.ok) {
      throw new Error('Error al sincronizar');
    }
    
    await this.deleteFromIndexedDB(record.operation, record.id);
    console.log('[PWA] Registro sincronizado:', record.id);
  }

  /**
   * Configura notificaciones push
   */
  async setupNotifications() {
    if (!('Notification' in window)) {
      console.log('[PWA] Notificaciones no soportadas');
      return;
    }

    if (Notification.permission === 'default') {
      // No solicitar automáticamente, esperar acción del usuario
      console.log('[PWA] Permisos de notificación no solicitados aún');
    } else if (Notification.permission === 'granted') {
      console.log('[PWA] Permisos de notificación concedidos');
    }
  }

  /**
   * Solicita permisos de notificación
   */
  async requestNotificationPermission() {
    if (!('Notification' in window)) return false;

    const permission = await Notification.requestPermission();
    return permission === 'granted';
  }

  /**
   * Muestra una notificación
   */
  async showNotification(title, options = {}) {
    if (!this.swRegistration || Notification.permission !== 'granted') {
      return;
    }

    await this.swRegistration.showNotification(title, {
      icon: '/assets/icons/icon-192x192.png',
      badge: '/assets/icons/badge-72x72.png',
      vibrate: [200, 100, 200],
      ...options
    });
  }

  /**
   * Helpers para IndexedDB
   */
  async openDB() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open('SENAttendDB', 1);
      
      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result);
      
      request.onupgradeneeded = (event) => {
        const db = event.target.result;
        
        if (!db.objectStoreNames.contains('asistencias')) {
          db.createObjectStore('asistencias', { keyPath: 'id', autoIncrement: true });
        }
        
        if (!db.objectStoreNames.contains('anomalias')) {
          db.createObjectStore('anomalias', { keyPath: 'id', autoIncrement: true });
        }
        
        if (!db.objectStoreNames.contains('cache')) {
          db.createObjectStore('cache', { keyPath: 'key' });
        }
      };
    });
  }

  async saveToIndexedDB(storeName, data) {
    const db = await this.openDB();
    return new Promise((resolve, reject) => {
      const transaction = db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const request = store.add(data);
      
      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result);
    });
  }

  async deleteFromIndexedDB(storeName, id) {
    const db = await this.openDB();
    return new Promise((resolve, reject) => {
      const transaction = db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const request = store.delete(id);
      
      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve();
    });
  }

  async getAllFromIndexedDB(storeName) {
    const db = await this.openDB();
    return new Promise((resolve, reject) => {
      const transaction = db.transaction([storeName], 'readonly');
      const store = transaction.objectStore(storeName);
      const request = store.getAll();
      
      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result);
    });
  }

  /**
   * Muestra un toast de notificación
   */
  showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `pwa-toast pwa-toast-${type}`;
    toast.innerHTML = `
      <i class="fas fa-${this.getToastIcon(type)}"></i>
      <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 100);
    
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  getToastIcon(type) {
    const icons = {
      success: 'check-circle',
      error: 'times-circle',
      warning: 'exclamation-triangle',
      info: 'info-circle'
    };
    return icons[type] || 'info-circle';
  }

  /**
   * Limpia todos los caches
   */
  async clearAllCaches() {
    if ('serviceWorker' in navigator && this.swRegistration) {
      this.swRegistration.active.postMessage({ type: 'CLEAR_CACHE' });
      
      // Limpiar IndexedDB
      const db = await this.openDB();
      const stores = ['asistencias', 'anomalias', 'cache'];
      
      for (const storeName of stores) {
        const transaction = db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);
        await store.clear();
      }
      
      console.log('[PWA] Caches limpiados');
      this.showToast('Datos locales eliminados', 'success');
    }
  }

  /**
   * Verifica si la app está instalada
   */
  isInstalled() {
    return window.matchMedia('(display-mode: standalone)').matches ||
           window.navigator.standalone === true;
  }

  /**
   * Obtiene información del estado de la PWA
   */
  getStatus() {
    return {
      isInstalled: this.isInstalled(),
      isOnline: this.isOnline,
      hasServiceWorker: !!this.swRegistration,
      canInstall: !!this.deferredPrompt,
      pendingSync: this.syncQueue.length
    };
  }
}

// Inicializar PWA Manager cuando el DOM esté listo
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    window.pwaManager = new PWAManager();
  });
} else {
  window.pwaManager = new PWAManager();
}

export default PWAManager;
