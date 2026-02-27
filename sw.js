/**
 * Service Worker para SENAttend PWA
 * Implementa estrategias de cache y funcionalidad offline
 * 
 * @version 1.0.0
 */

const CACHE_VERSION = 'senattend-v1.0.0';
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
  '/assets/vendor/fontawesome/css/all.min.css',
];

// Rutas que requieren estar online (no se cachean)
const ONLINE_ONLY_ROUTES = [
  '/auth/login',
  '/auth/logout',
  '/api/qr/procesar',
  '/asistencia/guardar',
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
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('[SW] Service Worker instalado correctamente');
        return self.skipWaiting();
      })
      .catch((error) => {
        console.error('[SW] Error al instalar Service Worker:', error);
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
        return self.clients.claim();
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
  
  if (!url.protocol.startsWith('http')) {
    return;
  }

  if (isOnlineOnlyRoute(url.pathname)) {
    event.respondWith(fetchOnlineOnly(request));
  } else if (isStaticAsset(request)) {
    event.respondWith(cacheFirst(request, CACHE_STATIC));
  } else if (isImage(request)) {
    event.respondWith(cacheFirst(request, CACHE_IMAGES));
  } else if (isAPIRequest(url.pathname)) {
    event.respondWith(networkFirst(request, CACHE_API));
  } else {
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
  try {
    const response = await fetch(request);
    
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
});

console.log('[SW] Service Worker cargado:', CACHE_VERSION);
