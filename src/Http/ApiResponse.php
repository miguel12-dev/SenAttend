<?php

namespace App\Http;

/**
 * API Response Handler
 * Maneja respuestas HTTP/JSON de forma estandarizada
 * Siguiendo principios SOLID (Single Responsibility)
 * 
 * @package App\Http
 * @version 1.0.0
 */
class ApiResponse
{
    /**
     * Respuesta exitosa genérica
     * 
     * @param mixed $data Datos a retornar
     * @param string $message Mensaje opcional
     * @param int $statusCode Código HTTP
     * @return void
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ], $statusCode);
    }

    /**
     * Respuesta de error
     * 
     * @param string $message Mensaje de error
     * @param int $statusCode Código HTTP
     * @param array $errors Errores detallados opcionales
     * @return void
     */
    public static function error(string $message, int $statusCode = 400, array $errors = []): void
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('c')
        ], $statusCode);
    }

    /**
     * Respuesta de validación fallida
     * 
     * @param array $errors Errores de validación
     * @param string $message Mensaje principal
     * @return void
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): void
    {
        self::error($message, 422, $errors);
    }

    /**
     * Respuesta no autorizada
     * 
     * @param string $message Mensaje de error
     * @return void
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    /**
     * Respuesta prohibida
     * 
     * @param string $message Mensaje de error
     * @return void
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }

    /**
     * Recurso no encontrado
     * 
     * @param string $message Mensaje de error
     * @return void
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, 404);
    }

    /**
     * Error del servidor
     * 
     * @param string $message Mensaje de error
     * @return void
     */
    public static function serverError(string $message = 'Internal server error'): void
    {
        self::error($message, 500);
    }

    /**
     * Respuesta creada exitosamente
     * 
     * @param mixed $data Datos del recurso creado
     * @param string $message Mensaje
     * @return void
     */
    public static function created($data = null, string $message = 'Resource created successfully'): void
    {
        self::success($data, $message, 201);
    }

    /**
     * Respuesta de eliminación exitosa
     * 
     * @param string $message Mensaje
     * @return void
     */
    public static function deleted(string $message = 'Resource deleted successfully'): void
    {
        self::success(null, $message, 200);
    }

    /**
     * Respuesta sin contenido
     * 
     * @return void
     */
    public static function noContent(): void
    {
        http_response_code(204);
        exit;
    }

    /**
     * Respuesta de lista paginada
     * 
     * @param array $items Items de la página actual
     * @param int $total Total de items
     * @param int $page Página actual
     * @param int $perPage Items por página
     * @param string $message Mensaje opcional
     * @return void
     */
    public static function paginated(
        array $items,
        int $total,
        int $page = 1,
        int $perPage = 15,
        string $message = 'Success'
    ): void {
        $totalPages = ceil($total / $perPage);

        self::success([
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'count' => count($items),
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ], $message);
    }

    /**
     * Envía respuesta JSON
     * 
     * @param array $data Datos a enviar
     * @param int $statusCode Código HTTP
     * @return void
     */
    private static function json(array $data, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        
        // CORS headers para PWA
        self::setCorsHeaders();
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Configura headers CORS
     * 
     * @return void
     */
    private static function setCorsHeaders(): void
    {
        $allowedOrigins = [
            'http://localhost:8000',
            'http://localhost:3000',
            'https://senattend.app'
        ];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$origin}");
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * Maneja peticiones OPTIONS para CORS preflight
     * 
     * @return void
     */
    public static function handleCorsPreFlight(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            self::setCorsHeaders();
            exit;
        }
    }
}
