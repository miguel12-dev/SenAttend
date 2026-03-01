# Generador de Íconos PWA - SENAttend

Este directorio contiene scripts para generar automáticamente los íconos PWA faltantes a partir de los íconos existentes.

## Problema

El navegador Chrome/Edge en PC busca un ícono de 144x144 que no existe físicamente, causando el siguiente error:

```
Failed to load resource: the server responded with a status of 404
Error while trying to use the following icon from the Manifest: 
https://senattend.adso.pro/assets/icons/icon-144x144.png
```

## Solución

Hay dos opciones para resolver este problema:

### Opción 1: Solución Rápida (Ya Implementada) ✅

Se actualizó el `manifest.json` para que el navegador use el ícono de 192x192 redimensionado automáticamente cuando busque el de 144x144. Esta solución ya está aplicada y debería funcionar inmediatamente.

**No se requiere acción adicional** a menos que quieras los íconos físicos exactos.

### Opción 2: Generar Íconos Físicos (Opcional)

Si prefieres tener archivos físicos para cada tamaño, puedes usar los scripts incluidos:

#### Usando Python (requiere Pillow)

```bash
# 1. Instalar dependencias
pip install Pillow

# 2. Desde la raíz del proyecto, ejecutar:
python scripts/generate-icons.py
```

#### Usando Node.js (requiere sharp)

```bash
# 1. Instalar dependencias
cd scripts
npm install

# 2. Ejecutar script
npm run generate-icons
```

#### Usando una herramienta online

Alternativa sin instalar nada:

1. Ve a [favicon.io](https://favicon.io/) o [realfavicongenerator.net](https://realfavicongenerator.net/)
2. Sube `public/assets/icons/web-app-manifest-512x512.png`
3. Descarga los tamaños: 144x144, 128x128, 72x72, 48x48
4. Guárdalos en `public/assets/icons/` con nombres:
   - `icon-144x144.png`
   - `icon-128x128.png`
   - `icon-72x72.png`
   - `icon-48x48.png`

## Íconos que se Generarán

Los scripts crearán automáticamente:

- `icon-144x144.png` (desde web-app-manifest-192x192.png)
- `icon-128x128.png` (desde web-app-manifest-192x192.png)
- `icon-72x72.png` (desde favicon-96x96.png)
- `icon-48x48.png` (desde favicon-96x96.png)

## Actualizar manifest.json (Si generas íconos físicos)

Si decides generar los íconos físicos, actualiza el `manifest.json` para usar los nuevos archivos:

```json
{
  "src": "/assets/icons/icon-144x144.png",
  "sizes": "144x144",
  "type": "image/png",
  "purpose": "any"
}
```

## Verificación

Después de aplicar la solución (ya sea la rápida o generando íconos):

1. Limpia la cache del navegador
2. Recarga la aplicación
3. Abre DevTools > Console
4. NO deberías ver el error 404 del ícono 144x144
5. Ve a DevTools > Application > Manifest
6. Todos los íconos deben mostrarse correctamente

## Notas

- **Solución rápida (actual)**: El navegador redimensiona automáticamente el ícono de 192x192. Esto funciona perfectamente y no hay diferencia visual.
- **Íconos físicos**: Mejor rendimiento (evita redimensionamiento), pero requiere más espacio en disco y mantenimiento.

## Estado Actual

✅ **SOLUCIONADO** - El manifest.json ya está actualizado con la solución rápida.
- El error 404 ya no debería aparecer
- La PWA es instalable en móvil y PC
- Los scripts están disponibles por si quieres generar íconos físicos en el futuro
