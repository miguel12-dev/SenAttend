<?php

namespace App\Support;

/**
 * Helper para gestionar headers HTTP de cache
 */
class CacheHeaders
{
    /**
     * Deshabilitar todo tipo de cache para contenido privado
     * Usar en controladores que manejan datos sensibles del usuario
     */
    public static function noCache(): void
    {
        // HTTP 1.1
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        
        // HTTP 1.0
        header('Pragma: no-cache');
        
        // Proxies
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        
        // Prevenir cache en navegadores específicos
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        
        // Evitar cache en IE
        header('X-UA-Compatible: IE=edge,chrome=1');
    }

    /**
     * Permitir cache pero revalidar siempre (para recursos semi-estáticos)
     */
    public static function revalidate(int $maxAge = 0): void
    {
        header("Cache-Control: public, max-age={$maxAge}, must-revalidate");
        header('Pragma: public');
        
        if ($maxAge > 0) {
            $expires = gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT';
            header("Expires: {$expires}");
        }
    }

    /**
     * Cache público para recursos estáticos (CSS, JS, imágenes)
     */
    public static function publicCache(int $maxAge = 31536000): void // 1 año por defecto
    {
        header("Cache-Control: public, max-age={$maxAge}, immutable");
        header('Pragma: public');
        
        $expires = gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT';
        header("Expires: {$expires}");
    }

    /**
     * Cache privado (solo en navegador del usuario, no en proxies)
     */
    public static function privateCache(int $maxAge = 3600): void
    {
        header("Cache-Control: private, max-age={$maxAge}");
        
        $expires = gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT';
        header("Expires: {$expires}");
    }
}
