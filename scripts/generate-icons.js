/**
 * Script para generar íconos PWA faltantes desde íconos existentes
 * Requiere: npm install sharp
 */

const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

// Ruta base de los íconos
const ICONS_PATH = path.join(__dirname, '..', 'public', 'assets', 'icons');

// Configuración de íconos a generar
// formato: [archivo_fuente, tamaño_salida, nombre_salida]
const ICONS_TO_GENERATE = [
    ['web-app-manifest-192x192.png', 144, 'icon-144x144.png'],
    ['web-app-manifest-192x192.png', 128, 'icon-128x128.png'],
    ['favicon-96x96.png', 72, 'icon-72x72.png'],
    ['favicon-96x96.png', 48, 'icon-48x48.png'],
];

async function generateIcon(sourceFile, targetSize, outputFile) {
    const sourcePath = path.join(ICONS_PATH, sourceFile);
    const outputPath = path.join(ICONS_PATH, outputFile);
    
    try {
        // Verificar que existe el archivo fuente
        if (!fs.existsSync(sourcePath)) {
            console.log(`❌ Error: No se encontró ${sourceFile}`);
            return false;
        }

        // Redimensionar imagen
        await sharp(sourcePath)
            .resize(targetSize, targetSize, {
                fit: 'cover',
                position: 'center'
            })
            .png({ quality: 100, compressionLevel: 9 })
            .toFile(outputPath);
        
        console.log(`✅ Generado: ${outputFile} (${targetSize}x${targetSize})`);
        return true;
        
    } catch (error) {
        console.log(`❌ Error generando ${outputFile}: ${error.message}`);
        return false;
    }
}

async function main() {
    console.log('🎨 Generador de Íconos PWA - SENAttend\n');
    
    // Verificar que existe la carpeta de íconos
    if (!fs.existsSync(ICONS_PATH)) {
        console.log(`❌ Error: No se encuentra la carpeta ${ICONS_PATH}`);
        console.log('   Asegúrate de ejecutar este script desde la carpeta scripts/');
        return;
    }
    
    // Generar cada ícono
    let successCount = 0;
    for (const [source, size, output] of ICONS_TO_GENERATE) {
        const success = await generateIcon(source, size, output);
        if (success) successCount++;
    }
    
    console.log(`\n✨ Completado: ${successCount}/${ICONS_TO_GENERATE.length} íconos generados`);
    
    if (successCount === ICONS_TO_GENERATE.length) {
        console.log('\n📝 Nota: Actualiza el manifest.json para incluir los nuevos íconos.');
    } else {
        console.log('\n⚠️ Algunos íconos no se pudieron generar. Revisa los errores arriba.');
    }
}

// Ejecutar
main().catch(error => {
    console.error('\n❌ Error inesperado:', error.message);
    process.exit(1);
});
