# 🎨 Generador de Iconos PWA - SENAttend

## Instrucciones para Generar Iconos

Para que la PWA funcione correctamente, necesitas generar iconos en múltiples tamaños.

### Opción 1: Herramientas Online (Recomendado)

#### PWA Asset Generator
1. Visita: https://www.pwabuilder.com/imageGenerator
2. Sube tu logo del SENA (mínimo 512x512px)
3. Descarga el paquete completo
4. Extrae los archivos en `/public/assets/icons/`

#### Favicon Generator
1. Visita: https://realfavicongenerator.net/
2. Sube tu logo
3. Configura colores tema (#39A900 para SENA)
4. Descarga y extrae en `/public/assets/icons/`

### Opción 2: ImageMagick (Línea de Comandos)

Si tienes ImageMagick instalado, puedes usar este script:

```bash
#!/bin/bash

# Script para generar iconos PWA desde una imagen fuente
# Requisito: ImageMagick instalado

SOURCE_IMAGE="sena-logo.png"  # Tu imagen fuente (mínimo 512x512px)
OUTPUT_DIR="../public/assets/icons"

# Crear directorio si no existe
mkdir -p $OUTPUT_DIR

# Tamaños para iconos PWA
SIZES=(16 32 72 96 128 144 152 180 192 384 512)

echo "🎨 Generando iconos PWA..."

for SIZE in "${SIZES[@]}"
do
    echo "Generando icon-${SIZE}x${SIZE}.png"
    convert $SOURCE_IMAGE -resize ${SIZE}x${SIZE} "$OUTPUT_DIR/icon-${SIZE}x${SIZE}.png"
done

# Generar favicon
echo "Generando favicons..."
convert $SOURCE_IMAGE -resize 16x16 "$OUTPUT_DIR/favicon-16x16.png"
convert $SOURCE_IMAGE -resize 32x32 "$OUTPUT_DIR/favicon-32x32.png"
convert $SOURCE_IMAGE -resize 180x180 "$OUTPUT_DIR/apple-touch-icon.png"

# Generar badge para notificaciones
echo "Generando badge..."
convert $SOURCE_IMAGE -resize 72x72 "$OUTPUT_DIR/badge-72x72.png"

echo "✅ Iconos generados exitosamente en $OUTPUT_DIR"

# Generar splash screens para iOS (opcional)
SPLASH_DIR="../public/assets/splash"
mkdir -p $SPLASH_DIR

echo "🖼️  Generando splash screens iOS..."

# iPhone SE
convert $SOURCE_IMAGE -resize 640x1136 -gravity center -background "#39A900" -extent 640x1136 "$SPLASH_DIR/splash-640x1136.png"

# iPhone 8
convert $SOURCE_IMAGE -resize 750x1334 -gravity center -background "#39A900" -extent 750x1334 "$SPLASH_DIR/splash-750x1334.png"

# iPhone 8 Plus
convert $SOURCE_IMAGE -resize 1242x2208 -gravity center -background "#39A900" -extent 1242x2208 "$SPLASH_DIR/splash-1242x2208.png"

echo "✅ Splash screens generados en $SPLASH_DIR"
```

### Opción 3: Photoshop/GIMP (Manual)

Si prefieres crear manualmente:

1. Abre tu logo en Photoshop/GIMP
2. Exporta en los siguientes tamaños:
   - 16x16px (favicon-16x16.png)
   - 32x32px (favicon-32x32.png)
   - 72x72px (icon-72x72.png)
   - 96x96px (icon-96x96.png)
   - 128x128px (icon-128x128.png)
   - 144x144px (icon-144x144.png)
   - 152x152px (icon-152x152.png)
   - 180x180px (apple-touch-icon.png)
   - 192x192px (icon-192x192.png)
   - 384x384px (icon-384x384.png)
   - 512x512px (icon-512x512.png)

3. Guarda todos en `/public/assets/icons/`

### Opción 4: Node.js Script

Si tienes Node.js instalado:

```bash
# Instalar sharp (procesamiento de imágenes)
npm install sharp

# Crear script
node generate-icons.js
```

```javascript
// generate-icons.js
const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const SOURCE = 'sena-logo.png';
const OUTPUT_DIR = './public/assets/icons';
const THEME_COLOR = '#39A900';

const SIZES = [16, 32, 72, 96, 128, 144, 152, 180, 192, 384, 512];

// Crear directorio
if (!fs.existsSync(OUTPUT_DIR)) {
  fs.mkdirSync(OUTPUT_DIR, { recursive: true });
}

// Generar iconos
console.log('🎨 Generando iconos PWA...');

SIZES.forEach(async (size) => {
  await sharp(SOURCE)
    .resize(size, size, {
      fit: 'contain',
      background: { r: 57, g: 169, b: 0, alpha: 1 }
    })
    .toFile(path.join(OUTPUT_DIR, `icon-${size}x${size}.png`));
  
  console.log(`✓ icon-${size}x${size}.png`);
});

// Favicons
sharp(SOURCE)
  .resize(16, 16)
  .toFile(path.join(OUTPUT_DIR, 'favicon-16x16.png'));

sharp(SOURCE)
  .resize(32, 32)
  .toFile(path.join(OUTPUT_DIR, 'favicon-32x32.png'));

sharp(SOURCE)
  .resize(180, 180)
  .toFile(path.join(OUTPUT_DIR, 'apple-touch-icon.png'));

console.log('✅ Iconos generados exitosamente');
```

## Estructura de Archivos Esperada

```
public/
└── assets/
    ├── icons/
    │   ├── favicon-16x16.png
    │   ├── favicon-32x32.png
    │   ├── icon-72x72.png
    │   ├── icon-96x96.png
    │   ├── icon-128x128.png
    │   ├── icon-144x144.png
    │   ├── icon-152x152.png
    │   ├── icon-192x192.png
    │   ├── icon-384x384.png
    │   ├── icon-512x512.png
    │   ├── apple-touch-icon.png
    │   ├── badge-72x72.png
    │   ├── qr-icon-96x96.png          # Icono para shortcut QR
    │   ├── alert-icon-96x96.png       # Icono para shortcut anomalías
    │   └── home-icon-96x96.png        # Icono para shortcut home
    └── splash/
        ├── splash-640x1136.png        # iPhone SE
        ├── splash-750x1334.png        # iPhone 8
        └── splash-1242x2208.png       # iPhone 8 Plus
```

## Recomendaciones

### Tamaño de Archivo
- Optimiza las imágenes para web (usa TinyPNG o similar)
- Los iconos no deberían superar 50KB cada uno
- Usa PNG con transparencia

### Diseño
- Logo centrado con padding
- Fondo con color tema SENA (#39A900)
- Contraste adecuado para visibilidad
- Versión simplificada del logo para tamaños pequeños

### Testing
Después de generar los iconos, verifica:

1. **Chrome DevTools**
   - F12 > Application > Manifest
   - Verificar que todos los iconos cargan

2. **Lighthouse**
   - F12 > Lighthouse > PWA
   - Debe mostrar "✓ Provides a valid apple-touch-icon"
   - Debe mostrar "✓ Has a maskable icon"

3. **Firefox**
   - F12 > Storage > Manifest
   - Verificar iconos

4. **Safari (iOS)**
   - Agregar a Home Screen
   - Verificar que el icono se ve correctamente

## Iconos de Shortcuts

Para los shortcuts del manifest, necesitas iconos adicionales:

```bash
# QR Icon
# Usa un icono de QR code o scanner

# Alert Icon  
# Usa un icono de alerta/warning

# Home Icon
# Usa un icono de casa/home
```

Puedes obtenerlos de:
- Font Awesome (exportar como PNG)
- Material Icons
- Heroicons
- O crearlos custom

## Placeholder Temporal

Si no tienes el logo del SENA disponible, puedes usar temporalmente:

```html
<!-- Usar un SVG simple -->
<svg width="512" height="512" xmlns="http://www.w3.org/2000/svg">
  <rect width="512" height="512" fill="#39A900"/>
  <text x="256" y="280" font-size="120" fill="white" 
        text-anchor="middle" font-family="Arial">
    SENA
  </text>
</svg>
```

Guarda este SVG y conviértelo a PNG en los tamaños requeridos.

## Verificación Final

```bash
# Verificar que todos los archivos existen
ls -lh public/assets/icons/
ls -lh public/assets/splash/

# Verificar tamaños (deben estar optimizados)
du -h public/assets/icons/

# Probar manifest
curl http://localhost:8000/manifest.json | jq '.icons'
```

---

**Nota**: Los iconos son críticos para la funcionalidad PWA. Asegúrate de generarlos antes del deployment a producción.
