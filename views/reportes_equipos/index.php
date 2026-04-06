<?php
/**
 * Vista: Reporte de Ingresos/Salidas de Equipos
 *
 * Variables esperadas desde el controlador:
 *  @var array  $user
 *  @var array  $resultado  [ datos, total, pagina, por_pagina, total_paginas ]
 *  @var string $fechaInicio
 *  @var string $fechaFin
 *  @var string $csrfToken
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <title>Reporte de Equipos - SENAttend</title>

    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/reportes-equipos.css') ?>">
</head>
<body>
<div class="wrapper">
    <?php
    $currentPage = 'reportes-equipos';
    require __DIR__ . '/../components/header.php';
    ?>

    <main class="main-content">
        <div class="reporte-equipos-container">

            <!-- Encabezado -->
            <div class="reporte-header">
                <h2>
                    <i class="fas fa-clipboard-list"></i>
                    Reporte de Ingresos y Salidas de Equipos
                </h2>
                <p class="subtitle-page">
                    Visualiza y exporta los registros de entrada y salida de equipos del CTA
                    según el rango de fechas seleccionado.
                </p>
            </div>

            <!-- Panel de filtros -->
            <section class="filter-panel">
                <h3><i class="fas fa-filter"></i> Filtrar por Fecha</h3>

                <form id="form-filtros-reporte" method="GET" action="/reportes-equipos">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="fecha_inicio">Fecha Inicio</label>
                            <input
                                type="date"
                                id="fecha_inicio"
                                name="fecha_inicio"
                                value="<?= htmlspecialchars($fechaInicio) ?>"
                                max="<?= date('Y-m-d') ?>"
                                required>
                        </div>

                        <div class="filter-group">
                            <label for="fecha_fin">Fecha Fin</label>
                            <input
                                type="date"
                                id="fecha_fin"
                                name="fecha_fin"
                                value="<?= htmlspecialchars($fechaFin) ?>"
                                max="<?= date('Y-m-d') ?>"
                                min="<?= htmlspecialchars($fechaInicio) ?>"
                                required>
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn-sena-primary">
                                <i class="fas fa-search"></i> Consultar
                            </button>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Tabla de resultados -->
            <?php
            $datos        = $resultado['datos']         ?? [];
            $total        = $resultado['total']         ?? 0;
            $pagina       = $resultado['pagina']        ?? 1;
            $totalPaginas = $resultado['total_paginas'] ?? 1;
            $porPagina    = $resultado['por_pagina']    ?? 20;
            ?>

            <!-- Barra resumen + botón exportar -->
            <div class="result-bar">
                <span class="result-count">
                    Total de registros encontrados: <strong><?= number_format($total) ?></strong>
                    &nbsp;|&nbsp;
                    Período: <strong><?= htmlspecialchars($fechaInicio) ?></strong>
                    al <strong><?= htmlspecialchars($fechaFin) ?></strong>
                </span>

                <button id="btn-exportar-excel" class="btn-sena-secondary" type="button">
                    <i class="fas fa-file-excel"></i> Exportar a Excel
                </button>
            </div>

            <?php if (empty($datos)): ?>
                <div class="empty-reporte">
                    <i class="fas fa-inbox"></i>
                    <p>No se encontraron registros para el rango de fechas seleccionado.</p>
                </div>
            <?php else: ?>

            <div class="reporte-table-wrapper">
                <table class="reporte-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-calendar-day"></i> Fecha Ingreso</th>
                            <th><i class="fas fa-clock"></i> Hora Ingreso</th>
                            <th>Fecha Salida</th>
                            <th>Hora Salida</th>
                            <th><i class="fas fa-user-graduate"></i> Aprendiz</th>
                            <th>Documento</th>
                            <th><i class="fas fa-laptop"></i> Marca Equipo</th>
                            <th>N° Serial</th>
                            <th><i class="fas fa-user-shield"></i> Portero</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos as $fila): ?>
                        <tr>
                            <td><?= htmlspecialchars($fila['fecha_ingreso'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fila['hora_ingreso']  ?? '') ?></td>
                            <td>
                                <?php if (!empty($fila['fecha_salida'])): ?>
                                    <?= htmlspecialchars($fila['fecha_salida']) ?>
                                <?php else: ?>
                                    <span class="badge-sin-salida">Sin salida</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($fila['hora_salida'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fila['nombre_aprendiz'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fila['documento_aprendiz'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fila['marca_equipo'] ?? '') ?></td>
                            <td><code><?= htmlspecialchars($fila['numero_serial'] ?? '') ?></code></td>
                            <td><?= htmlspecialchars($fila['nombre_portero'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($fila['observaciones'])): ?>
                                    <?= htmlspecialchars($fila['observaciones']) ?>
                                <?php else: ?>
                                    <em>—</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($totalPaginas > 1): ?>
            <nav class="pagination-bar" aria-label="Paginación">

                <?php if ($pagina > 1): ?>
                    <a href="?fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>&pagina=1">
                        &laquo;
                    </a>
                    <a href="?fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>&pagina=<?= $pagina - 1 ?>">
                        &lsaquo;
                    </a>
                <?php else: ?>
                    <span class="disabled">&laquo;</span>
                    <span class="disabled">&lsaquo;</span>
                <?php endif; ?>

                <?php
                $rango = 2;
                $inicio = max(1, $pagina - $rango);
                $fin    = min($totalPaginas, $pagina + $rango);
                for ($p = $inicio; $p <= $fin; $p++):
                ?>
                    <?php if ($p === $pagina): ?>
                        <span class="active"><?= $p ?></span>
                    <?php else: ?>
                        <a href="?fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>&pagina=<?= $p ?>">
                            <?= $p ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagina < $totalPaginas): ?>
                    <a href="?fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>&pagina=<?= $pagina + 1 ?>">
                        &rsaquo;
                    </a>
                    <a href="?fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>&pagina=<?= $totalPaginas ?>">
                        &raquo;
                    </a>
                <?php else: ?>
                    <span class="disabled">&rsaquo;</span>
                    <span class="disabled">&raquo;</span>
                <?php endif; ?>

            </nav>
            <?php endif; ?>

            <?php endif; ?>

        </div><!-- /.reporte-equipos-container -->
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje | <strong>SENAttend</strong></p>
        </div>
    </footer>
</div>

<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('assets/js/reportes-equipos.js') ?>"></script>
</body>
</html>
