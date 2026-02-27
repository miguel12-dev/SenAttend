<?php
/**
 * Router personalizado para el servidor PHP integrado
 * Maneja peticiones especiales para archivos PWA
 * 
 * Uso: php -S localhost:8000 -t public router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = urldecode($uri);

// Servir archivos PWA desde la raíz del proyecto
if ($uri === '/manifest.json' || $uri === '/sw.js') {
    $filePath = __DIR__ . '/' . basename($uri);
    
    if (file_exists($filePath)) {
        // Establecer Content-Type correcto
        if ($uri === '/manifest.json') {
            header('Content-Type: application/manifest+json; charset=utf-8');
        } else {
            header('Content-Type: application/javascript; charset=utf-8');
        }
        
        // Headers adicionales para PWA
        header('Cache-Control: public, max-age=0');
        header('Service-Worker-Allowed: /');
        header('Access-Control-Allow-Origin: *');
        
        // Leer y enviar el archivo
        readfile($filePath);
        return true;
    }
}

// Redirigir /assets/* a /public/assets/*
if (strpos($uri, '/assets/') === 0) {
    $filePath = __DIR__ . '/public' . $uri;
    
    if (file_exists($filePath) && is_file($filePath)) {
        // Determinar Content-Type basado en extensión
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $contentTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
        ];
        
        if (isset($contentTypes[$ext])) {
            header('Content-Type: ' . $contentTypes[$ext]);
        }
        
        header('Cache-Control: public, max-age=31536000');
        readfile($filePath);
        return true;
    }
}

// Para archivos estáticos en /public, dejar que PHP los sirva
$filePath = __DIR__ . '/public' . $uri;
if (file_exists($filePath) && is_file($filePath)) {
    return false;
}

// Para todo lo demás, usar el router principal
require __DIR__ . '/public/index.php';
