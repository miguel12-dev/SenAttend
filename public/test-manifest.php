<?php
/**
 * Script de diagnóstico para probar el manifest.json
 * Acceder a: https://senattend.adso.pro/test-manifest.php
 */

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>';
echo '<html lang="es">';
echo '<head><meta charset="utf-8"><title>Test PWA Manifest</title></head>';
echo '<body style="font-family: monospace; padding: 20px;">';
echo '<h1>🔍 Diagnóstico PWA - SENAttend</h1>';

// Test 1: Verificar que el archivo manifest.json existe
echo '<h2>1. Verificación de Archivo</h2>';
$manifestPath = __DIR__ . '/../manifest.json';
echo '<p><strong>Ruta del archivo:</strong> ' . $manifestPath . '</p>';

if (file_exists($manifestPath)) {
    echo '<p style="color: green;">✅ El archivo manifest.json existe</p>';
    
    // Test 2: Verificar permisos de lectura
    if (is_readable($manifestPath)) {
        echo '<p style="color: green;">✅ El archivo es legible</p>';
        
        // Test 3: Leer contenido
        $content = file_get_contents($manifestPath);
        if ($content !== false) {
            echo '<p style="color: green;">✅ Contenido leído correctamente</p>';
            echo '<p><strong>Tamaño:</strong> ' . strlen($content) . ' bytes</p>';
            
            // Test 4: Validar JSON
            $json = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo '<p style="color: green;">✅ JSON válido</p>';
                echo '<h3>Contenido del Manifest:</h3>';
                echo '<pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;">';
                echo htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                echo '</pre>';
            } else {
                echo '<p style="color: red;">❌ JSON inválido: ' . json_last_error_msg() . '</p>';
            }
        } else {
            echo '<p style="color: red;">❌ Error al leer el contenido</p>';
        }
    } else {
        echo '<p style="color: red;">❌ El archivo no es legible (problema de permisos)</p>';
    }
} else {
    echo '<p style="color: red;">❌ El archivo manifest.json NO existe</p>';
}

// Test 5: Verificar headers que se enviarían
echo '<h2>2. Headers HTTP</h2>';
echo '<p>Si accedes directamente a <a href="/manifest.json">/manifest.json</a>, deberían enviarse estos headers:</p>';
echo '<ul>';
echo '<li>Content-Type: application/manifest+json</li>';
echo '<li>Cache-Control: public, max-age=0</li>';
echo '</ul>';

// Test 6: Verificar PHP version y configuración
echo '<h2>3. Configuración del Servidor</h2>';
echo '<p><strong>PHP Version:</strong> ' . phpversion() . '</p>';
echo '<p><strong>Server Software:</strong> ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . '</p>';
echo '<p><strong>Document Root:</strong> ' . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . '</p>';

// Test 7: Verificar error_reporting
echo '<h2>4. Error Reporting</h2>';
echo '<p><strong>display_errors:</strong> ' . ini_get('display_errors') . '</p>';
echo '<p><strong>error_reporting:</strong> ' . error_reporting() . '</p>';

// Test 8: Intentar servir el manifest manualmente
echo '<h2>5. Prueba de Servir Manifest</h2>';
echo '<p>Intentando servir el manifest directamente...</p>';

try {
    if (file_exists($manifestPath)) {
        $manifestContent = file_get_contents($manifestPath);
        $json = json_decode($manifestContent);
        
        if ($json !== null) {
            echo '<p style="color: green;">✅ El manifest se puede servir correctamente</p>';
            echo '<p><a href="/manifest.json" target="_blank">Abrir manifest.json</a></p>';
        } else {
            throw new Exception('JSON decode failed');
        }
    } else {
        throw new Exception('File not found');
    }
} catch (Exception $e) {
    echo '<p style="color: red;">❌ Error: ' . $e->getMessage() . '</p>';
}

// Test 9: Verificar .htaccess
echo '<h2>6. Configuración .htaccess</h2>';
$htaccessPath = __DIR__ . '/.htaccess';
if (file_exists($htaccessPath)) {
    echo '<p style="color: green;">✅ .htaccess existe</p>';
    echo '<details>';
    echo '<summary>Ver contenido de .htaccess</summary>';
    echo '<pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;">';
    echo htmlspecialchars(file_get_contents($htaccessPath));
    echo '</pre>';
    echo '</details>';
} else {
    echo '<p style="color: orange;">⚠️ .htaccess no encontrado</p>';
}

// Test 10: Verificar iconos de la PWA
echo '<h2>7. Iconos PWA</h2>';
$icons = [
    '/assets/icons/favicon-96x96.png',
    '/assets/icons/web-app-manifest-192x192.png',
    '/assets/icons/web-app-manifest-512x512.png',
    '/assets/icons/apple-touch-icon.png'
];

foreach ($icons as $icon) {
    $iconPath = __DIR__ . $icon;
    if (file_exists($iconPath)) {
        echo '<p style="color: green;">✅ ' . $icon . ' (existe)</p>';
    } else {
        echo '<p style="color: red;">❌ ' . $icon . ' (no encontrado)</p>';
    }
}

echo '<hr>';
echo '<p><small>Script de diagnóstico - ' . date('Y-m-d H:i:s') . '</small></p>';
echo '</body>';
echo '</html>';
