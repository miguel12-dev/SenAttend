# Fix: Bucle Infinito en Router SPA

## Problema
El router SPA (`spa-router.js`) entraba en un bucle infinito de navegación, causando un error:
```
RangeError: Failed to execute 'pushState' on 'History': Maximum call stack size exceeded
```

## Causa
1. **Redirecciones circulares**: Los middlewares de autenticación podían causar redirecciones infinitas
2. **Navegación recursiva**: La función `loadRoute` llamaba a `navigate`, que a su vez llamaba a `loadRoute`
3. **Caché del navegador**: El navegador mantenía versiones antiguas del código con bugs

## Soluciones Implementadas

### 1. Protección contra bucles de navegación
Agregado contador de profundidad de navegación en `spa-router.js`:
```javascript
this.navigationDepth = 0;
this.MAX_NAVIGATION_DEPTH = 10;
```

Si se excede el límite, la navegación se detiene automáticamente.

### 2. Mejora en manejo de errores
Eliminada la navegación a `/error` desde el catch de `loadRoute` para evitar bucles:
```javascript
catch (error) {
  console.error('[Router] Error al cargar ruta:', error);
  // Mostrar toast en lugar de navegar
  if (window.pwaManager?.showToast) {
    window.pwaManager.showToast('Error al cargar la página', 'error');
  }
}
```

### 3. Prevención de redirecciones circulares
Mejora en el middleware de autenticación:
```javascript
const authMiddleware = async ({ path, route }) => {
  // Evitar bucle de redirección
  if (path === '/login') {
    return true;
  }
  
  if (!userStore.isAuthenticated()) {
    router.navigate('/login');
    return false;
  }
  return true;
};
```

### 4. Navegación asíncrona en rutas
Uso de `setTimeout` para romper la cadena de llamadas sincrónicas:
```javascript
router.register('/', async () => {
  if (userStore.isAuthenticated()) {
    setTimeout(() => router.navigate('/dashboard'), 0);
    return;
  }
  loadView('welcome');
});
```

### 5. Cache-busting automático
Modificada la función `asset()` en `config.php` para agregar versionado automático:
```php
function asset(string $path): string {
    $assetPath = '/' . ltrim(str_replace('\\', '/', $path), '/');
    
    $fullPath = __DIR__ . '/../public/' . ltrim($path, '/');
    if (file_exists($fullPath)) {
        $version = filemtime($fullPath);
        $assetPath .= '?v=' . $version;
    }
    
    return $assetPath;
}
```

Esto fuerza al navegador a recargar los archivos JS cuando cambian.

## Solución Inmediata para el Usuario

1. **Limpiar caché del navegador**: Ctrl + Shift + Delete y seleccionar "Caché e imágenes"
2. **Recarga forzada**: Ctrl + F5 o Shift + Recarga
3. **Reiniciar el servidor**: `.\start-server.ps1` (los archivos ahora incluirán versión)

## Prevención Futura

- El sistema ahora detecta automáticamente bucles de navegación
- Los archivos JS se versionan automáticamente para evitar caché antigua
- Los middlewares tienen protección contra redirecciones circulares
- Las navegaciones usan `setTimeout` para evitar recursión directa

## Verificación

Para verificar que el problema está resuelto:

1. Abrir DevTools > Console
2. Recargar la página con Ctrl + F5
3. No deberían aparecer errores de "Maximum call stack size exceeded"
4. La navegación debería funcionar normalmente

## Archivos Modificados

- `public/js/router/spa-router.js` - Protección contra bucles
- `public/js/routes.js` - Navegación asíncrona y middleware mejorado
- `config/config.php` - Cache-busting automático

## Fecha
27 de febrero, 2026
