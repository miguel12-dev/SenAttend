<?php

/**
 * Script de Cron Job para el cierre automático de ingresos de equipos.
 * Este script busca equipos que entraron en días anteriores y los cierra,
 * agregando una observación indicando que hubo una salida no registrada.
 *
 * Configuración en crontab (ejemplo, para ejecutar todos los días a medianoche):
 * 0 0 * * * php /ruta/al/proyecto/cron_cierres_equipos.php
 * 0 12 * * * php /ruta/al/proyecto/cron_cierres_equipos.php # Para mediodía
 */

// Simular el entorno de cli
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la línea de comandos.");
}

// Cargar autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

// Cargar configuración
require_once __DIR__ . '/config/config.php';

use App\GestionEquipos\Repositories\SeguimientoEquiposRepository;
use App\GestionEquipos\Services\SeguimientoEquiposService;

try {
    echo "[" . date('Y-m-d H:i:s') . "] Iniciando proceso de cierres automáticos de equipos...\n";
    
    $repository = new SeguimientoEquiposRepository();
    // Instanciar el servicio directamente sin inyección compleja ya que no necesitamos auth
    $service = new SeguimientoEquiposService($repository);
    
    $cerrados = $service->procesarCierresAutomaticos();
    
    echo "[" . date('Y-m-d H:i:s') . "] Proceso completado. Registros cerrados automáticamente: $cerrados\n";

} catch (\Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Error fatal ejecutando el cron: " . $e->getMessage() . "\n";
    exit(1);
}
