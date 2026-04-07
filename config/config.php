<?php

/**
 * Configuración principal de la aplicación
 * Lee variables de entorno desde .env
 */

// Cargar variables de entorno desde .env
if (!function_exists('loadEnv')) {
    function loadEnv(string $path): void
    {
        if (!file_exists($path)) {
            throw new RuntimeException(".env file not found at: {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignorar comentarios
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Parsear línea KEY=VALUE
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remover comillas si existen
                $value = trim($value, '"\'');

                // Establecer variable de entorno
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    putenv("{$key}={$value}");
                }
            }
        }
    }
}

// Función helper para obtener variables de entorno
if (!function_exists('getEnv')) {
    function getEnv(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        // Siempre servir assets desde la raíz pública del dominio.
        // Esto evita errores cuando las rutas amigables cambian (por ejemplo /eventos/admin/9)
        // y garantiza que CSS/JS/imagenes se resuelvan correctamente.
        $assetPath = '/' . ltrim(str_replace('\\', '/', $path), '/');
        
        // Agregar versión basada en el timestamp del archivo para cache-busting
        $fullPath = __DIR__ . '/../public/' . ltrim($path, '/');
        if (file_exists($fullPath)) {
            $version = filemtime($fullPath);
            $assetPath .= '?v=' . $version;
        }
        
        return $assetPath;
    }
}

if (!function_exists('asset_css')) {
    function asset_css(string $path): string
    {
        return '<link rel="stylesheet" href="' . asset($path) . '">';
    }
}

if (!function_exists('asset_js')) {
    function asset_js(string $path): string
    {
        return '<script src="' . asset($path) . '"></script>';
    }
}

// Cargar .env si existe
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
}

// Definir constantes de la aplicación (solo si no están definidas)
if (!defined('APP_ENV')) {
    define('APP_ENV', getEnv('APP_ENV', 'production'));
}
if (!defined('DB_HOST')) {
    define('DB_HOST', getEnv('DB_HOST', '127.0.0.1'));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getEnv('DB_NAME', 'sena_asistencia'));
}
if (!defined('DB_USER')) {
    define('DB_USER', getEnv('DB_USER', 'root'));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getEnv('DB_PASS', ''));
}
if (!defined('DB_TIMEZONE')) {
    define('DB_TIMEZONE', getEnv('DB_TIMEZONE', 'America/Bogota'));
}
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', ROOT_PATH . '/public');
}

// Configuración de errores según entorno
if (APP_ENV === 'local' || APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/logs/php-error.log');
}

// Configuración de zona horaria
date_default_timezone_set('America/Bogota');

// Configuración de sesiones (solo si no hay sesión activa)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Strict');
}

return [
    'app' => [
        'name' => 'SENAttend',
        'env' => APP_ENV,
        'url' => getEnv('APP_URL', 'http://localhost:8000'),
    ],
    'database' => [
        'host' => DB_HOST,
        'name' => DB_NAME,
        'user' => DB_USER,
        'pass' => DB_PASS,
        'timezone' => DB_TIMEZONE,
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true,
        ],
    ],
];

