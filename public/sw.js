/**
 * Service Worker para SENAttend PWA
 * Implementa estrategias de cache y funcionalidad offline
 * 
 * @version 1.0.1
 */

const CACHE_VERSION = 'senattend-v1.0.1';
const CACHE_STATIC = `${CACHE_VERSION}-static`;
const CACHE_DYNAMIC = `${CACHE_VERSION}-dynamic`;
const CACHE_IMAGES = `${CACHE_VERSION}-images`;
const CACHE_API = `${CACHE_VERSION}-api`;

// Recursos estáticos críticos que siempre deben estar disponibles offline
const STATIC_ASSETS = [
  '/',
  '/dashboard',
  '/manifest.json',
  '/css/common/style.css',
  '/css/common/components.css',
  '/css/dashboard/dashboard.css',
  '/js/app.js',
  '/js/common/app.js',
  '/js/common/components.js',
  '/js/components/back-button.js',
  '/assets/vendor/fontawesome/css/all.min.css',
];

// Rutas que requieren estar online (no se cachean)
const ONLINE_ONLY_ROUTES = [
  '/auth/login',
  '/auth/logout',
  '/api/qr/procesar',
  '/asistencia/guardar',
  // Rutas de equipos - crítico para datos frescos
  '/aprendiz/equipos',
  // Dashboard y datos privados por rol
  '/dashboard',
  '/admin',
  '/instructor',
  '/portero',
  '/aprendiz/panel',
  '/aprendiz/boletas-salida',
  '/aprendiz/asistencias',
  // APIs con datos sensibles
  '/api/aprendices',
  '/api/fichas',
  '/api/instructor-fichas',
  '/api/admin',
  '/api/instructor',
  '/api/portero',
  '/api/aprendiz',
  // Boletas de salida
  '/boletas-salida',
  // Perfil
  '/perfil',
];

// Rutas API que se pueden cachear temporalmente
const CACHEABLE_API_ROUTES = [
  '/api/fichas',
  '/api/aprendices',
  '/api/instructor-fichas',
  '/api/configuracion/turnos',
];

/**
 * Evento de instalación del Service Worker
 * Pre-cachea los recursos estáticos críticos
 */
self.addEventListener('install', (event) => {
  console.log('[SW] Instalando Service Worker...', CACHE_VERSION);
  
  event.waitUntil(
    caches.open(CACHE_STATIC)
      .then((cache) => {
        console.log('[SW] Pre-cacheando recursos estáticos');
        // Usar Promise.allSettled para que no falle si un recurso no existe
        return Promise.allSettled(
          STATIC_ASSETS.map(url => 
            cache.add(url).catch(err => {
              console.warn('[SW] No se pudo cachear:', url, err);
              return null;
            })
          )
        );
      })
      .then(() => {
        console.log('[SW] Service Worker instalado correctamente');
        return self.skipWaiting(); // Activar inmediatamente
      })
      .catch((error) => {
        console.error('[SW] Error al instalar Service Worker:', error);
        return self.skipWaiting(); // Continuar aunque falle el pre-cache
      })
  );
});

/**
 * Evento de activación del Service Worker
 * Limpia caches antiguas
 */
self.addEventListener('activate', (event) => {
  console.log('[SW] Activando Service Worker...', CACHE_VERSION);
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames
            .filter((cacheName) => {
              // Eliminar caches que no pertenecen a la versión actual
              return cacheName.startsWith('senattend-') && 
                     !cacheName.startsWith(CACHE_VERSION);
            })
            .map((cacheName) => {
              console.log('[SW] Eliminando cache antiguo:', cacheName);
              return caches.delete(cacheName);
            })
        );
      })
      .then(() => {
        console.log('[SW] Service Worker activado');
        return self.clients.claim(); // Tomar control inmediato
      })
  );
});

/**
 * Evento fetch - Intercepta todas las peticiones
 * Implementa estrategias de cache según el tipo de recurso
 */
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Ignorar peticiones no HTTP/HTTPS
  if (!url.protocol.startsWith('http')) {
    return;
  }

  // Estrategia según el tipo de recurso
  if (isOnlineOnlyRoute(url.pathname)) {
    // Online Only - No cachear
    event.respondWith(fetchOnlineOnly(request));
  } else if (isStaticAsset(request)) {
    // Cache First para recursos estáticos
    event.respondWith(cacheFirst(request, CACHE_STATIC));
  } else if (isImage(request)) {
    // Cache First para imágenes
    event.respondWith(cacheFirst(request, CACHE_IMAGES));
  } else if (isAPIRequest(url.pathname)) {
    // Network First para APIs (con fallback a cache)
    event.respondWith(networkFirst(request, CACHE_API));
  } else {
    // Stale While Revalidate para páginas HTML
    event.respondWith(staleWhileRevalidate(request, CACHE_DYNAMIC));
  }
});

/**
 * Estrategia: Cache First
 * Busca en cache primero, si no existe hace fetch
 */
async function cacheFirst(request, cacheName) {
  try {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }

    const response = await fetch(request);
    
    // Solo cachear respuestas exitosas
    if (response && response.status === 200) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    
    return response;
  } catch (error) {
    console.error('[SW] Error en cacheFirst:', error);
    return caches.match('/offline.html') || new Response('Sin conexión', {
      status: 503,
      statusText: 'Service Unavailable'
    });
  }
}

/**
 * Estrategia: Network First
 * Intenta red primero, si falla usa cache
 */
async function networkFirst(request, cacheName) {
  // Skip caching for non-GET requests (POST, PUT, DELETE, etc.)
  const method = request.method || 'GET';
  if (method !== 'GET') {
    try {
      return await fetch(request);
    } catch (error) {
      return new Response(JSON.stringify({ 
        error: 'Sin conexión',
        offline: true 
      }), {
        status: 503,
        headers: { 'Content-Type': 'application/json' }
      });
    }
  }
  
  try {
    const response = await fetch(request);
    
    // Cachear respuesta exitosa
    if (response && response.status === 200) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    
    return response;
  } catch (error) {
    console.log('[SW] Network failed, usando cache:', request.url);
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    return new Response(JSON.stringify({ 
      error: 'Sin conexión',
      offline: true 
    }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

/**
 * Estrategia: Stale While Revalidate
 * Devuelve cache inmediatamente y actualiza en segundo plano
 */
async function staleWhileRevalidate(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cachedResponse = await cache.match(request);
  
  const fetchPromise = fetch(request).then((response) => {
    if (response && response.status === 200) {
      cache.put(request, response.clone());
    }
    return response;
  }).catch(() => cachedResponse);
  
  return cachedResponse || fetchPromise;
}

/**
 * Estrategia: Online Only
 * Solo funciona con conexión, no cachea
 */
async function fetchOnlineOnly(request) {
  try {
    return await fetch(request);
  } catch (error) {
    return new Response(JSON.stringify({ 
      error: 'Esta funcionalidad requiere conexión a internet',
      offline: true 
    }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

/**
 * Utilidades de verificación
 */
function isOnlineOnlyRoute(pathname) {
  return ONLINE_ONLY_ROUTES.some(route => pathname.includes(route));
}

function isStaticAsset(request) {
  return request.destination === 'script' ||
         request.destination === 'style' ||
         request.destination === 'font';
}

function isImage(request) {
  return request.destination === 'image';
}

function isAPIRequest(pathname) {
  return pathname.startsWith('/api/') || 
         CACHEABLE_API_ROUTES.some(route => pathname.includes(route));
}

/**
 * Manejo de mensajes desde el cliente
 */
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (event.data && event.data.type === 'CLEAR_CACHE') {
    event.waitUntil(
      caches.keys().then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => caches.delete(cacheName))
        );
      })
    );
  }

  // 🚀 Cache Invalidation para operaciones de escritura
  if (event.data && event.data.type === 'INVALIDATE_CACHE') {
    event.waitUntil(invalidatePatterns(event.data.patterns));
  }
});

/**
 * Invalida patrones de caché específicos
 * @param {string[]} patterns - Patrones de URL a invalidar
 */
async function invalidatePatterns(patterns) {
  console.log('[SW] Invalidating cache patterns:', patterns);
  
  const cacheNames = await caches.keys();
  
  for (const cacheName of cacheNames) {
    // Solo procesar caches de nuestra app
    if (!cacheName.startsWith('senattend-')) continue;
    
    try {
      const cache = await caches.open(cacheName);
      const requests = await cache.keys();
      
      for (const request of requests) {
        const url = new URL(request.url);
        
        for (const pattern of patterns) {
          if (url.pathname.includes(pattern)) {
            console.log('[SW] Deleting cached:', url.pathname);
            await cache.delete(request);
          }
        }
      }
    } catch (error) {
      console.warn('[SW] Error processing cache:', cacheName, error);
    }
  }
  
  console.log('[SW] Cache invalidation complete');
}

/**
 * Manejo de sincronización en segundo plano
 */
self.addEventListener('sync', (event) => {
  console.log('[SW] Evento de sincronización:', event.tag);
  
  if (event.tag === 'sync-asistencias') {
    event.waitUntil(syncAsistencias());
  }
  
  if (event.tag === 'sync-anomalias') {
    event.waitUntil(syncAnomalias());
  }
});

/**
 * Sincronizar asistencias pendientes
 */
async function syncAsistencias() {
  try {
    // Recuperar asistencias pendientes del IndexedDB
    const db = await openDB();
    const pendingRecords = await getAllPendingRecords(db, 'asistencias');
    
    for (const record of pendingRecords) {
      try {
        const response = await fetch('/asistencia/guardar', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(record.data)
        });
        
        if (response.ok) {
          await deletePendingRecord(db, 'asistencias', record.id);
          console.log('[SW] Asistencia sincronizada:', record.id);
        }
      } catch (error) {
        console.error('[SW] Error al sincronizar asistencia:', error);
      }
    }
  } catch (error) {
    console.error('[SW] Error en syncAsistencias:', error);
  }
}

/**
 * Sincronizar anomalías pendientes
 */
async function syncAnomalias() {
  try {
    const db = await openDB();
    const pendingRecords = await getAllPendingRecords(db, 'anomalias');
    
    for (const record of pendingRecords) {
      try {
        const response = await fetch('/api/asistencia/anomalia/aprendiz', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(record.data)
        });
        
        if (response.ok) {
          await deletePendingRecord(db, 'anomalias', record.id);
          console.log('[SW] Anomalía sincronizada:', record.id);
        }
      } catch (error) {
        console.error('[SW] Error al sincronizar anomalía:', error);
      }
    }
  } catch (error) {
    console.error('[SW] Error en syncAnomalias:', error);
  }
}

/**
 * Helpers para IndexedDB
 */
function openDB() {
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
    };
  });
}

function getAllPendingRecords(db, storeName) {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction([storeName], 'readonly');
    const store = transaction.objectStore(storeName);
    const request = store.getAll();
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
  });
}

function deletePendingRecord(db, storeName, id) {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction([storeName], 'readwrite');
    const store = transaction.objectStore(storeName);
    const request = store.delete(id);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve();
  });
}

console.log('[SW] Service Worker cargado:', CACHE_VERSION);
