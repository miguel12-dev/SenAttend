# 🚀 Inicio Rápido - SENAttend PWA

## ⏱️ En 5 Minutos

### 1️⃣ Verificar Instalación (30 segundos)

```bash
# Verificar PHP
php --version  # Debe ser >= 8.1

# Verificar Composer
composer --version

# Verificar que estás en el directorio correcto
pwd
# Debe mostrar: c:\wamp64\www\senattend (o tu ruta)
```

### 2️⃣ Iniciar Servidor (1 minuto)

```bash
# Iniciar servidor PHP integrado
php -S localhost:8000 -t public

# Deberías ver:
# [Mon Feb 23 12:00:00 2026] PHP 8.2.28 Development Server (http://localhost:8000) started
```

### 3️⃣ Abrir en Navegador (30 segundos)

1. Abre Chrome o Edge
2. Navega a: **http://localhost:8000**
3. Deberías ver la página de bienvenida de SENAttend

### 4️⃣ Verificar PWA (2 minutos)

**Opción A: Chrome DevTools**
```
1. Presiona F12 para abrir DevTools
2. Ve a la pestaña "Application"
3. En el menú lateral:
   ├─ Manifest → Deberías ver "SENAttend" con iconos
   └─ Service Workers → Debería aparecer "activated and running"
```

**Opción B: Lighthouse**
```
1. F12 → Pestaña "Lighthouse"
2. Selecciona solo "Progressive Web App"
3. Click "Analyze page load"
4. Espera el resultado (debería ser >80/100)
```

### 5️⃣ Probar Funcionalidad Offline (1 minuto)

```
1. Con DevTools abierto (F12)
2. Network tab
3. Marca el checkbox "Offline"
4. Navega por la aplicación
5. ¡Debería seguir funcionando! ✨
```

---

## 🎯 Verificación Completa

### Checklist de Funcionalidad

Prueba cada una de estas funcionalidades:

#### ✅ PWA Básica
- [ ] La aplicación carga correctamente
- [ ] No hay errores en la consola
- [ ] El manifest.json se carga (Application > Manifest)
- [ ] El Service Worker está activo (Application > Service Workers)

#### ✅ Navegación SPA
- [ ] Click en enlaces no recarga la página
- [ ] La URL cambia correctamente
- [ ] Botón atrás del navegador funciona
- [ ] No hay "flash" al cambiar de página

#### ✅ Funcionalidad Offline
- [ ] Con network offline, la app sigue navegable
- [ ] Los recursos estáticos cargan desde cache
- [ ] Se muestra indicador "Sin conexión"
- [ ] Las operaciones se agregan a la cola de sincronización

#### ✅ Sincronización
- [ ] Al volver online, se sincroniza automáticamente
- [ ] Se muestra notificación de sincronización
- [ ] Los datos pendientes se envían al servidor

#### ✅ Instalación
- [ ] Aparece icono de instalación en la barra de direcciones
- [ ] Al hacer click, muestra diálogo de instalación
- [ ] Después de instalar, abre en ventana standalone

---

## 🐛 Solución de Problemas

### Problema: El Service Worker no se registra

**Solución:**
```javascript
// Verificar en consola
navigator.serviceWorker.getRegistrations().then(regs => {
  console.log('Registrations:', regs);
});

// Si está vacío, verificar que el archivo existe:
// http://localhost:8000/sw.js
```

### Problema: "Manifest not found"

**Solución:**
```bash
# Verificar que el archivo existe
ls public/manifest.json

# Verificar que se sirve correctamente
curl http://localhost:8000/manifest.json
```

### Problema: Los iconos no aparecen

**Causa:** Los iconos PWA aún no han sido generados.

**Solución:**
1. Lee `docs/ICON_GENERATION.md`
2. Usa https://www.pwabuilder.com/imageGenerator
3. Descarga y extrae en `public/assets/icons/`

### Problema: "Service Worker registration failed"

**Posible causa:** Ejecutando desde HTTP (no HTTPS).

**Solución:**
```
Para desarrollo local, localhost está permitido.
Para producción, HTTPS es OBLIGATORIO.
```

### Problema: Errores en consola

**Solución:**
```javascript
// Verificar estado completo
console.log({
  pwa: window.pwaManager?.getStatus(),
  sw: await navigator.serviceWorker.getRegistration(),
  online: navigator.onLine
});
```

---

## 📱 Probar en Móvil

### Android (Chrome)

```
1. Conecta tu dispositivo Android al PC
2. Habilita "Depuración USB" en opciones de desarrollador
3. En Chrome PC: chrome://inspect
4. Click en "Port forwarding"
5. Agregar: 8000 → localhost:8000
6. En el móvil, abre Chrome y navega a: localhost:8000
7. Deberías ver el banner "Agregar a pantalla de inicio"
```

### iOS (Safari)

```
1. Asegúrate que tu PC y iPhone estén en la misma red WiFi
2. Encuentra tu IP local:
   Windows: ipconfig
   Busca "IPv4" (ej: 192.168.1.100)
3. En iPhone Safari: http://TU-IP:8000
   Ejemplo: http://192.168.1.100:8000
4. Toca el botón compartir
5. "Agregar a pantalla de inicio"
```

---

## 🎮 Comandos Útiles

### Desarrollo

```bash
# Iniciar servidor con logs detallados
php -S localhost:8000 -t public 2>&1 | tee logs/server.log

# Ver logs en tiempo real
tail -f logs/server.log

# Limpiar cache de Composer
composer clear-cache
composer dump-autoload

# Verificar sintaxis PHP
php -l public/index.php
```

### Testing

```javascript
// En consola del navegador

// Ver estado PWA
window.pwaManager.getStatus()

// Ver estado de sincronización
await window.syncManager.getStats()

// Ver estado global
window.stateManager.export()

// Simular registro de asistencia offline
await window.syncManager.addToQueue('asistencias', {
  aprendiz_id: 1,
  ficha_id: 1,
  fecha: new Date().toISOString()
})
```

### Debugging

```javascript
// Habilitar logs detallados
localStorage.setItem('debug_pwa', 'true')
location.reload()

// Limpiar todo (reset completo)
await window.pwaManager.clearAllCaches()
await window.syncManager.clearAll()
localStorage.clear()
sessionStorage.clear()
location.reload()

// Ver todos los Service Workers
navigator.serviceWorker.getRegistrations().then(regs => {
  regs.forEach(reg => console.log(reg))
})
```

---

## 📊 Métricas de Performance

### Lighthouse (Objetivos)

```
Performance:    > 90/100
Accessibility:  > 95/100
Best Practices: > 95/100
SEO:           > 90/100
PWA:           > 90/100 ⭐
```

### Cómo medir

```bash
# Opción 1: Chrome DevTools
F12 > Lighthouse > Generate report

# Opción 2: CLI (requiere Node.js)
npm install -g lighthouse
lighthouse http://localhost:8000 --view

# Opción 3: CI/CD (para automatizar)
npm install -g @lhci/cli
lhci autorun
```

---

## 🎨 Personalización Rápida

### Cambiar colores del tema

```javascript
// En manifest.json
{
  "theme_color": "#39A900",        // Verde SENA
  "background_color": "#39A900"    // Verde SENA
}

// Para usar colores custom:
{
  "theme_color": "#YOUR_COLOR",
  "background_color": "#YOUR_COLOR"
}
```

### Cambiar nombre de la app

```javascript
// En manifest.json
{
  "name": "Tu Nombre Largo de la App",
  "short_name": "NombreCorto"
}
```

### Agregar shortcuts adicionales

```javascript
// En manifest.json > shortcuts
{
  "name": "Nueva Función",
  "short_name": "Función",
  "description": "Descripción de la función",
  "url": "/ruta/a/funcion",
  "icons": [{ "src": "/assets/icons/funcion-96x96.png", "sizes": "96x96" }]
}
```

---

## 🔗 Recursos Rápidos

### Documentación
- **Guía Completa:** `docs/PWA_MIGRATION_GUIDE.md`
- **Generación de Iconos:** `docs/ICON_GENERATION.md`
- **README Principal:** `README_PWA.md`
- **Resumen de Migración:** `MIGRATION_SUMMARY.md`

### Herramientas Online
- **PWA Builder:** https://www.pwabuilder.com/
- **Lighthouse:** https://developers.google.com/web/tools/lighthouse
- **Manifest Generator:** https://www.simicart.com/manifest-generator.html/
- **Icon Generator:** https://realfavicongenerator.net/

### Referencias
- **MDN PWA:** https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps
- **Web.dev PWA:** https://web.dev/progressive-web-apps/
- **Service Worker API:** https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API

---

## 🎯 Siguiente Paso

Una vez que todo funcione localmente:

**→ Lee `MIGRATION_SUMMARY.md` para entender qué se ha creado**

Contiene:
- ✅ Lista completa de archivos creados
- ✅ Explicación de cada componente
- ✅ Beneficios de la migración
- ✅ Instrucciones de deployment

---

## 💬 ¿Todo Funcionando?

Si todo está funcionando correctamente, deberías poder:

1. ✅ Navegar por la aplicación sin recargas
2. ✅ Ver el Service Worker activo en DevTools
3. ✅ Funcionar offline
4. ✅ Ver el botón de instalación
5. ✅ Instalar la app y abrirla en ventana standalone

**¡Felicidades! Tu PWA está lista.** 🎉

---

## 🚨 Si Algo No Funciona

1. Revisa la sección "Solución de Problemas" arriba
2. Verifica la consola del navegador (F12)
3. Asegúrate que el servidor PHP está corriendo
4. Consulta `docs/PWA_MIGRATION_GUIDE.md` para más detalles

---

**¿Listo para producción?**  
→ Lee `MIGRATION_SUMMARY.md` sección "Próximos Pasos"

---

Desarrollado con ❤️ para el SENA  
Febrero 2026
