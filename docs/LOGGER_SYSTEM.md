# Sistema de Logging para Producción

## Descripción

Sistema inteligente de logging que automáticamente desactiva logs en producción mientras mantiene el tracking de errores.

## Uso

### Importar el Logger

```javascript
import logger from './utils/logger.js';
```

### Métodos Disponibles

#### `logger.log(...args)`
Logs normales - Solo se muestran en desarrollo (localhost)

```javascript
logger.log('Usuario autenticado:', user);
// En producción: No se muestra nada
// En desarrollo: console.log('Usuario autenticado:', user)
```

#### `logger.info(...args)`
Logs informativos - Solo en desarrollo

```javascript
logger.info('[Router] Navegando a:', path);
```

#### `logger.warn(...args)`
Advertencias - Solo en desarrollo

```javascript
logger.warn('API lenta, tomó:', responseTime);
```

#### `logger.error(...args)`
Errores - Se guardan en localStorage en producción

```javascript
logger.error('[API] Error al cargar datos:', error);
// En producción: Guarda en localStorage, no muestra en consola
// En desarrollo: console.error('[API] Error al cargar datos:', error)
```

#### `logger.debug(...args)`
Debug detallado - Solo en desarrollo

```javascript
logger.debug('Estado actual:', state);
```

## Funciones Especiales

### Ver Errores Guardados

```javascript
const errors = logger.getStoredErrors();
console.table(errors);
```

### Limpiar Errores Guardados

```javascript
logger.clearStoredErrors();
```

### Habilitar/Deshabilitar Manualmente

```javascript
// Forzar habilitar en producción (para debugging)
logger.setEnabled(true);

// Deshabilitar en desarrollo
logger.setEnabled(false);
```

## Detección de Entorno

El logger detecta automáticamente si está en producción basándose en:

- `localhost` → Desarrollo
- `127.0.0.1` → Desarrollo
- `192.168.x.x` → Desarrollo (red local)
- Cualquier otro dominio → Producción

## Migración de Código Existente

### Antes
```javascript
console.log('[PWA] Service Worker registrado');
console.error('[Router] Error:', error);
```

### Después
```javascript
logger.log('[PWA] Service Worker registrado');
logger.error('[Router] Error:', error);
```

## Storage de Errores

Los errores en producción se guardan en `localStorage` con:

```javascript
{
  timestamp: "2026-02-27T15:30:00.000Z",
  error: "Error al cargar datos"
}
```

- Se mantienen los últimos 50 errores
- Los más antiguos se eliminan automáticamente
- Accesibles para debugging post-despliegue

## Ejemplo Completo

```javascript
import logger from './utils/logger.js';

class ApiClient {
  async fetchData() {
    logger.log('[API] Iniciando petición...');
    
    try {
      const response = await fetch('/api/data');
      
      if (!response.ok) {
        logger.warn('[API] Respuesta no OK:', response.status);
      }
      
      const data = await response.json();
      logger.debug('[API] Datos recibidos:', data);
      
      return data;
      
    } catch (error) {
      logger.error('[API] Error fatal:', error);
      throw error;
    }
  }
}
```

## Ventajas

✅ **Automático** - Detecta entorno sin configuración
✅ **Sin cambios masivos** - API similar a console
✅ **Tracking de errores** - Guarda errores en producción
✅ **Performance** - No ejecuta código de log en producción
✅ **Debugging** - Puede habilitarse manualmente si es necesario

## Próximas Mejoras

- [ ] Integración con Sentry/Bugsnag
- [ ] Niveles de log configurables
- [ ] Exportar logs guardados
- [ ] Envío automático de errores a servidor
- [ ] Sampling de errores (no guardar duplicados)

## Ver También

- [Mejoras de Seguridad](./SECURITY_IMPROVEMENTS.md)
- [Fix de Router Loop](./FIX_ROUTER_LOOP.md)
