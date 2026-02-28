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
// Nota: Solo incluir recursos que existen y son críticos
const STATIC_ASSETS = [
  '/manifest.json',
  '/offline.html',
  // Los demás archivos se cachearán bajo demanda
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
        return self.skipWaiting();
      })
      .catch((error) => {
        console.error('[SW] Error al instalar Service Worker:', error);
        // Aún así continuar con la instalación
        return self.skipWaiting();
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
  
  // Solo interceptar peticiones HTTP/HTTPS
  if (!url.protocol.startsWith('http')) {
    return;
  }
  
  // Solo interceptar peticiones del mismo origen o assets conocidos
  if (url.origin !== location.origin) {
    return;
  }
  
  // No interceptar peticiones POST/PUT/DELETE/PATCH en APIs críticas
  if (request.method !== 'GET') {
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
    
    // Intentar obtener desde cache una vez más
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Si es un recurso HTML, intentar devolver la página offline
    if (request.destination === 'document') {
      const offlinePage = await caches.match('/offline.html');
      if (offlinePage) {
        return offlinePage;
      }
    }
    
    // Último recurso: devolver una respuesta genérica
    return new Response('Sin conexión - Recurso no disponible offline', {
      status: 503,
      statusText: 'Service Unavailable',
      headers: { 'Content-Type': 'text/plain; charset=utf-8' }
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
  
  const fetchPromise = fetch(request)
    .then((response) => {
      if (response && response.status === 200) {
        cache.put(request, response.clone());
      }
      return response;
    })
    .catch((error) => {
      console.log('[SW] Fetch failed en staleWhileRevalidate:', error);
      return cachedResponse;
    });
  
  // Si hay respuesta en cache, devolverla inmediatamente
  // Si no, esperar por la red
  if (cachedResponse) {
    // Actualizar en segundo plano pero no esperar
    fetchPromise.catch(() => {});
    return cachedResponse;
  }
  
  return fetchPromise;
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
