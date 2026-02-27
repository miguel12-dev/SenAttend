/**
 * Sistema de Logging para Producción
 * Permite activar/desactivar logs fácilmente
 */

const IS_PRODUCTION = window.location.hostname !== 'localhost' && 
                      window.location.hostname !== '127.0.0.1' &&
                      !window.location.hostname.includes('192.168');

class Logger {
  constructor() {
    this.enabled = !IS_PRODUCTION;
    this.errorReporting = true;
  }

  /**
   * Habilita o deshabilita logs
   */
  setEnabled(enabled) {
    this.enabled = enabled;
  }

  /**
   * Log normal - solo en desarrollo
   */
  log(...args) {
    if (this.enabled) {
      console.log(...args);
    }
  }

  /**
   * Log de información - solo en desarrollo
   */
  info(...args) {
    if (this.enabled) {
      console.info(...args);
    }
  }

  /**
   * Log de advertencia - solo en desarrollo
   */
  warn(...args) {
    if (this.enabled) {
      console.warn(...args);
    }
  }

  /**
   * Log de errores - siempre activo en producción
   * Puede enviar errores a un servicio de monitoreo
   */
  error(...args) {
    if (this.enabled) {
      console.error(...args);
    }
    
    // En producción, enviar errores a servicio de monitoreo
    if (IS_PRODUCTION && this.errorReporting) {
      this.reportError(...args);
    }
  }

  /**
   * Debug - solo en desarrollo
   */
  debug(...args) {
    if (this.enabled) {
      console.debug(...args);
    }
  }

  /**
   * Reporta errores a un servicio (puede ser implementado después)
   */
  reportError(...args) {
    // Aquí se puede implementar el envío a un servicio como Sentry
    // Por ahora, guardar en localStorage para revisión
    try {
      const errors = JSON.parse(localStorage.getItem('app_errors') || '[]');
      errors.push({
        timestamp: new Date().toISOString(),
        error: args.map(arg => 
          arg instanceof Error ? arg.message : String(arg)
        ).join(' ')
      });
      
      // Mantener solo los últimos 50 errores
      if (errors.length > 50) {
        errors.shift();
      }
      
      localStorage.setItem('app_errors', JSON.stringify(errors));
    } catch (e) {
      // Silently fail si no se puede guardar
    }
  }

  /**
   * Obtiene errores guardados
   */
  getStoredErrors() {
    try {
      return JSON.parse(localStorage.getItem('app_errors') || '[]');
    } catch {
      return [];
    }
  }

  /**
   * Limpia errores guardados
   */
  clearStoredErrors() {
    localStorage.removeItem('app_errors');
  }
}

// Crear instancia global
const logger = new Logger();

// Exportar como módulo ES6
export default logger;

// También hacer disponible globalmente para código legacy
if (typeof window !== 'undefined') {
  window.logger = logger;
}
