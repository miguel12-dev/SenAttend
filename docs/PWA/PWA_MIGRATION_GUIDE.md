# 📱 Guía de Migración a PWA - SENAttend

## 🎯 Resumen Ejecutivo

Este documento describe la migración exitosa de SENAttend de una aplicación PHP nativa a una Progressive Web App (PWA) moderna, siguiendo principios SOLID y clean code.

## 📋 Tabla de Contenidos

1. [Arquitectura](#arquitectura)
2. [Componentes Principales](#componentes-principales)
3. [Estructura de Archivos](#estructura-de-archivos)
4. [Configuración](#configuración)
5. [Uso y Desarrollo](#uso-y-desarrollo)
6. [Deployment](#deployment)
7. [Mantenimiento](#mantenimiento)

---

## 🏗️ Arquitectura

### Principios Aplicados

#### SOLID
- **Single Responsibility**: Cada clase tiene una única responsabilidad
- **Open/Closed**: Extensible sin modificar código existente
- **Liskov Substitution**: Interfaces consistentes
- **Interface Segregation**: Interfaces específicas y pequeñas
- **Dependency Inversion**: Dependencias sobre abstracciones

#### Clean Code
- Nombres descriptivos y claros
- Funciones pequeñas y enfocadas
- Comentarios significativos solo cuando son necesarios
- Gestión de errores robusta
- Sin código duplicado (DRY)

### Capas de la Aplicación

```
┌─────────────────────────────────────────┐
│         PWA Layer (Frontend)            │
│  ┌───────────────────────────────────┐  │
│  │  Service Worker + Cache           │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  SPA Router + State Manager       │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  API Client + Sync Manager        │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  Components (Modular)             │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
                   ↕ HTTP/JSON
┌─────────────────────────────────────────┐
│         Backend Layer (PHP)             │
│  ┌───────────────────────────────────┐  │
│  │  Router (Front Controller)        │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  Controllers (Request Handlers)   │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  Services (Business Logic)        │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  Repositories (Data Access)       │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  Database (MySQL)                 │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

---

## 🧩 Componentes Principales

### 1. Service Worker (`/public/sw.js`)

**Responsabilidad**: Manejo de cache y funcionalidad offline

**Estrategias de Cache**:
- **Cache First**: Recursos estáticos (CSS, JS, fuentes)
- **Network First**: APIs y datos dinámicos
- **Stale While Revalidate**: Páginas HTML
- **Online Only**: Operaciones críticas (login, logout)

**Características**:
- Pre-caching de recursos críticos
- Background Sync para sincronización offline
- Actualización automática de versiones
- Limpieza de caches antiguas

```javascript
// Ejemplo de uso interno
const CACHE_VERSION = 'senattend-v1.0.0';
const STATIC_ASSETS = ['/', '/dashboard', '/manifest.json'];
```

### 2. PWA Manager (`/public/js/pwa/pwa-manager.js`)

**Responsabilidad**: Gestión de funcionalidades PWA

**Funciones**:
- Registro y actualización del Service Worker
- Prompt de instalación
- Gestión de notificaciones
- Sincronización en segundo plano
- Manejo de estado online/offline

```javascript
// Uso
window.pwaManager.showToast('Mensaje', 'success');
await window.pwaManager.addToSyncQueue('asistencias', data);
```

### 3. API Client (`/public/js/api/api-client.js`)

**Responsabilidad**: Comunicación con el backend

**Patrón**: Singleton

**Características**:
- Reintentos automáticos
- Timeouts configurables
- Interceptores de request/response
- Manejo de errores global
- Queue offline automático

```javascript
// Uso
import apiClient from '/js/api/api-client.js';

// Obtener fichas
const fichas = await apiClient.fichas.getAll();

// Crear aprendiz
const aprendiz = await apiClient.aprendices.create({
  nombre: 'Juan',
  documento: '1234567890'
});

// Guardar asistencia (con queue offline automático)
await apiClient.asistencias.guardar({
  aprendiz_id: 1,
  ficha_id: 1
});
```

### 4. SPA Router (`/public/js/router/spa-router.js`)

**Responsabilidad**: Navegación client-side sin recargas

**Características**:
- Rutas dinámicas con parámetros
- Middleware por ruta
- Hooks de navegación
- Historial de navegación
- Query strings

```javascript
// Uso
import router from '/js/router/spa-router.js';

// Registrar ruta
router.register('/fichas/:id', async (params) => {
  const ficha = await apiClient.fichas.getById(params.id);
  renderFicha(ficha);
}, {
  middleware: [authMiddleware],
  meta: { requiresAuth: true }
});

// Navegar
router.navigate('/fichas/123');

// Hook antes de navegar
router.beforeNavigate(async (path) => {
  if (!userStore.isAuthenticated()) {
    router.navigate('/login');
    return false; // Cancelar navegación
  }
});
```

### 5. State Manager (`/public/js/state/state-manager.js`)

**Responsabilidad**: Gestión de estado global reactivo

**Patrón**: Observer/PubSub

**Características**:
- Estado reactivo
- Suscripciones granulares
- Middleware de estado
- Persistencia en localStorage
- Historial de cambios

```javascript
// Uso
import stateManager, { userStore, appStore } from '/js/state/state-manager.js';

// Establecer valor
stateManager.set('user.name', 'Juan');

// Obtener valor
const name = stateManager.get('user.name');

// Suscribirse a cambios
const unsubscribe = stateManager.subscribe('user', (newValue, oldValue) => {
  console.log('Usuario cambió:', newValue);
});

// User Store
userStore.setUser({ id: 1, nombre: 'Juan', rol: 'instructor' });
const isAuth = userStore.isAuthenticated();
const hasPermission = userStore.hasPermission('create_fichas');

// App Store
appStore.setLoading(true);
appStore.addNotification({
  type: 'success',
  message: 'Operación exitosa'
});
```

### 6. Sync Manager (`/public/js/sync/sync-manager.js`)

**Responsabilidad**: Sincronización offline/online

**Características**:
- Cola de sincronización con IndexedDB
- Reintentos automáticos
- Sincronización periódica
- Cache de datos
- Historial de sincronizaciones

```javascript
// Uso
import syncManager from '/js/sync/sync-manager.js';

// Agregar a cola de sincronización
await syncManager.addToQueue('asistencias', {
  aprendiz_id: 1,
  ficha_id: 1,
  fecha: '2026-02-23'
});

// Sincronizar todo
await syncManager.syncAll();

// Estadísticas
const stats = await syncManager.getStats();
console.log(`Pendientes: ${stats.pending.total}`);

// Eventos
syncManager.on('sync_complete', (data) => {
  console.log('Sincronización completada:', data);
});

// Cache de datos
await syncManager.cacheData('fichas', fichas, 3600000); // 1 hora
const cachedFichas = await syncManager.getCachedData('fichas');
```

### 7. Base Component (`/public/js/components/base-component.js`)

**Responsabilidad**: Clase base para componentes reutilizables

**Características**:
- Ciclo de vida completo
- Estado local
- Event listeners automáticos
- Suscripciones a estado global
- Custom events

```javascript
// Uso
import Component, { RegisterComponent } from '/js/components/base-component.js';

@RegisterComponent('ficha-card')
class FichaCard extends Component {
  async render() {
    return `
      <div class="ficha-card">
        <h3>${this.props.ficha.numero}</h3>
        <p>${this.props.ficha.programa}</p>
        <button class="btn-ver">Ver Detalles</button>
      </div>
    `;
  }

  attachEventListeners() {
    this.addEventListener('.btn-ver', 'click', () => {
      this.emit('ficha:view', { id: this.props.ficha.id });
    });
  }

  async afterMount() {
    // Suscribirse a cambios
    this.subscribe('fichas', (fichas) => {
      this.update();
    });
  }
}

// Montar componente
const card = await ComponentFactory.mount('ficha-card', '#container', {
  ficha: { id: 1, numero: '2558347', programa: 'ADSI' }
});
```

### 8. API Response Handler (`/src/Http/ApiResponse.php`)

**Responsabilidad**: Respuestas HTTP/JSON estandarizadas

**Características**:
- Respuestas consistentes
- Códigos HTTP apropiados
- CORS configurado
- Headers de seguridad
- Paginación incluida

```php
// Uso en controladores
use App\Http\ApiResponse;

// Éxito
ApiResponse::success($data, 'Fichas obtenidas exitosamente');

// Error
ApiResponse::error('Error al procesar solicitud', 400);

// Validación
ApiResponse::validationError([
  'nombre' => 'El nombre es requerido',
  'email' => 'Email inválido'
]);

// No autorizado
ApiResponse::unauthorized();

// Recurso creado
ApiResponse::created($ficha, 'Ficha creada exitosamente');

// Paginado
ApiResponse::paginated($items, $total, $page, $perPage);

// CORS preflight
ApiResponse::handleCorsPreFlight();
```

---

## 📁 Estructura de Archivos

```
senattend/
├── config/
│   ├── config.php              # Configuración general
│   └── permissions_config.php  # Configuración RBAC
├── public/
│   ├── index.php               # Front controller
│   ├── manifest.json           # ✨ PWA Manifest
│   ├── sw.js                   # ✨ Service Worker
│   ├── assets/
│   │   ├── icons/              # ✨ Iconos PWA (múltiples tamaños)
│   │   ├── splash/             # ✨ Splash screens iOS
│   │   └── vendor/             # Librerías de terceros
│   ├── css/
│   │   ├── common/             # Estilos globales
│   │   ├── modules/            # Estilos por módulo
│   │   ├── pwa/                # ✨ Estilos PWA
│   │   └── components/         # Estilos de componentes
│   └── js/
│       ├── pwa/                # ✨ PWA Manager
│       ├── api/                # ✨ API Client
│       ├── router/             # ✨ SPA Router
│       ├── state/              # ✨ State Manager
│       ├── sync/               # ✨ Sync Manager
│       ├── components/         # ✨ Componentes base
│       ├── modules/            # Módulos específicos
│       └── common/             # Utilidades comunes
├── src/
│   ├── Controllers/            # Controladores
│   ├── Services/               # Lógica de negocio
│   ├── Repositories/           # Acceso a datos
│   ├── Middleware/             # Middleware
│   ├── Http/                   # ✨ API Response Handler
│   ├── Session/                # Gestión de sesiones
│   └── Exceptions/             # Excepciones personalizadas
├── views/
│   ├── layouts/
│   │   ├── base.php            # Layout tradicional
│   │   └── pwa-base.php        # ✨ Layout PWA
│   ├── components/             # Componentes de vista
│   └── [módulos]/              # Vistas por módulo
└── docs/                       # ✨ Documentación

✨ = Nuevo en la migración PWA
```

---

## ⚙️ Configuración

### 1. Variables de Entorno (`.env`)

```env
# Aplicación
APP_ENV=production
APP_URL=https://senattend.app

# Base de datos
DB_HOST=localhost
DB_NAME=sena_asistencia
DB_USER=root
DB_PASS=

# PWA
PWA_VERSION=1.0.0
SW_CACHE_VERSION=v1.0.0
```

### 2. Manifest PWA (`/public/manifest.json`)

Configuración ya creada con:
- Iconos en múltiples tamaños
- Splash screens
- Shortcuts a funciones principales
- Share target para QRs
- Theme colors del SENA

### 3. Service Worker (`/public/sw.js`)

Configurado con:
- Estrategias de cache apropiadas
- Background sync
- Gestión de versiones
- Limpieza automática

---

## 🚀 Uso y Desarrollo

### Desarrollo Local

```bash
# 1. Clonar repositorio
git clone [repo-url]
cd senattend

# 2. Instalar dependencias PHP
composer install

# 3. Configurar .env
cp .env.example .env
# Editar .env con tus credenciales

# 4. Iniciar servidor
php -S localhost:8000 -t public

# 5. Abrir en navegador
# http://localhost:8000
```

### Testing PWA

```javascript
// En DevTools Console

// Verificar Service Worker
navigator.serviceWorker.getRegistrations().then(regs => {
  console.log('Service Workers:', regs);
});

// Verificar estado PWA
console.log('Estado PWA:', window.pwaManager.getStatus());

// Verificar sincronización
syncManager.getStats().then(stats => {
  console.log('Stats Sync:', stats);
});

// Simular offline
// DevTools > Network > Offline checkbox

// Probar sincronización
await syncManager.addToQueue('asistencias', testData);
await syncManager.syncAll();
```

### Crear Nuevo Componente

```javascript
// /public/js/components/mi-componente.js

import Component, { RegisterComponent } from './base-component.js';

@RegisterComponent('mi-componente')
class MiComponente extends Component {
  constructor(selector, props) {
    super(selector, props);
    this.state = {
      contador: 0
    };
  }

  async render() {
    return `
      <div class="mi-componente">
        <h3>Contador: ${this.state.contador}</h3>
        <button class="btn-incrementar">Incrementar</button>
      </div>
    `;
  }

  attachEventListeners() {
    this.addEventListener('.btn-incrementar', 'click', () => {
      this.setState({ contador: this.state.contador + 1 });
    });
  }

  async afterMount() {
    console.log('Componente montado');
  }
}

export default MiComponente;
```

### Crear Nueva Ruta SPA

```javascript
// En tu archivo de rutas principal

import router from '/js/router/spa-router.js';
import apiClient from '/js/api/api-client.js';

router.register('/mi-ruta/:id', async (params, query) => {
  // Cargar datos
  const data = await apiClient.miRecurso.getById(params.id);
  
  // Renderizar
  const container = document.querySelector('#app-container');
  container.innerHTML = `
    <div class="mi-vista">
      <h1>${data.titulo}</h1>
      <!-- contenido -->
    </div>
  `;
}, {
  middleware: [authMiddleware],
  meta: {
    requiresAuth: true,
    title: 'Mi Ruta'
  }
});
```

---

## 📦 Deployment

### Checklist Pre-Deployment

- [ ] Cambiar `APP_ENV` a `production`
- [ ] Actualizar `APP_URL` en `.env`
- [ ] Verificar credenciales de base de datos
- [ ] Generar iconos PWA en todos los tamaños
- [ ] Crear splash screens iOS
- [ ] Actualizar versión en `manifest.json`
- [ ] Actualizar `CACHE_VERSION` en `sw.js`
- [ ] Minificar CSS y JS
- [ ] Optimizar imágenes
- [ ] Configurar HTTPS
- [ ] Configurar headers de seguridad

### Comandos de Deployment

```bash
# 1. Actualizar dependencias
composer install --no-dev --optimize-autoloader

# 2. Limpiar cache (si aplicable)
# ...

# 3. Permisos
chmod -R 755 public
chmod -R 775 logs

# 4. Verificar Service Worker
curl https://tu-dominio.com/sw.js

# 5. Verificar Manifest
curl https://tu-dominio.com/manifest.json
```

### Headers de Servidor (Apache)

```apache
# .htaccess

# HTTPS Redirect
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Service Worker
<Files "sw.js">
    Header set Service-Worker-Allowed "/"
    Header set Cache-Control "no-cache"
</Files>

# Manifest
<Files "manifest.json">
    Header set Content-Type "application/manifest+json"
    Header set Cache-Control "public, max-age=604800"
</Files>

# Security Headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "DENY"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
```

---

## 🔧 Mantenimiento

### Actualizar Service Worker

```javascript
// 1. Incrementar versión en sw.js
const CACHE_VERSION = 'senattend-v1.0.1'; // Cambiar versión

// 2. Usuarios existentes recibirán notificación de actualización automáticamente
// 3. Al hacer clic en "Actualizar", se recarga con nueva versión
```

### Monitoreo de Sincronización

```javascript
// Ver cola de sincronización
const stats = await syncManager.getStats();
console.log(`
  Asistencias pendientes: ${stats.pending.asistencias}
  Anomalías pendientes: ${stats.pending.anomalias}
  Última sincronización: ${stats.lastSync}
`);

// Ver historial
const history = await syncManager.getHistory(50);
console.table(history);
```

### Debugging

```javascript
// Habilitar logs detallados
localStorage.setItem('debug_pwa', 'true');

// Ver estado completo
console.log('State:', stateManager.export());
console.log('PWA Status:', pwaManager.getStatus());
console.log('API Config:', apiClient);

// Limpiar todo (desarrollo)
await syncManager.clearAll();
await pwaManager.clearAllCaches();
localStorage.clear();
```

### Performance

```javascript
// Métricas de PWA
if ('PerformanceObserver' in window) {
  const observer = new PerformanceObserver((list) => {
    for (const entry of list.getEntries()) {
      console.log(`${entry.name}: ${entry.duration}ms`);
    }
  });
  observer.observe({ entryTypes: ['navigation', 'resource'] });
}

// Lighthouse CI para auditorías automatizadas
// npm install -g @lhci/cli
// lhci autorun
```

---

## 📊 Beneficios de la Migración

### Antes (PHP Nativo)
- ❌ Recargas completas de página
- ❌ Sin funcionalidad offline
- ❌ Dependencia total de conexión
- ❌ No instalable
- ❌ Sin sincronización automática
- ❌ Gestión de estado global limitada

### Después (PWA)
- ✅ Navegación fluida sin recargas
- ✅ Funciona completamente offline
- ✅ Cola de sincronización automática
- ✅ Instalable como app nativa
- ✅ Actualizaciones automáticas
- ✅ Estado global reactivo
- ✅ Mejor UX y performance
- ✅ Push notifications (preparado)
- ✅ Arquitectura escalable y mantenible
- ✅ Código limpio y SOLID

---

## 🎓 Recursos Adicionales

### Documentación
- [MDN: Progressive Web Apps](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps)
- [Web.dev: PWA](https://web.dev/progressive-web-apps/)
- [Service Worker API](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)

### Herramientas
- [Lighthouse](https://developers.google.com/web/tools/lighthouse) - Auditoría PWA
- [Workbox](https://developers.google.com/web/tools/workbox) - Service Worker helpers
- [PWA Builder](https://www.pwabuilder.com/) - Generador de assets

---

## 📞 Soporte

Para preguntas o problemas:
1. Consultar esta documentación
2. Revisar logs en DevTools Console
3. Verificar estado con herramientas de debugging
4. Contactar al equipo de desarrollo

---

**Versión**: 1.0.0  
**Fecha**: Febrero 2026  
**Autor**: Equipo de Desarrollo SENAttend
