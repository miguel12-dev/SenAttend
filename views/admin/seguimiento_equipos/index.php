<?php
/**
 * Vista: Seguimiento de Infracciones - Equipos que no salieron
 *
 * Variables esperadas desde el controlador:
 *  @var array  $user
 *  @var array  $infractores
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
    <title>Infracciones de Equipos - SENAttend</title>

    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/reportes-equipos.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin/seguimiento-equipos.css') ?>">
</head>
<body>
<div class="wrapper">
    <?php
    $currentPage = 'seguimiento-equipos';
    require __DIR__ . '/../../components/header.php';
    ?>

    <main class="main-content">
        <div class="reporte-equipos-container">

            <!-- Encabezado -->
            <div class="reporte-header seguimiento-header">
                <h2>
                    <i class="fas fa-user-times"></i>
                    Infracciones de Equipos
                </h2>
                <p class="subtitle-page">
                    Aprendices que han registrado <strong>1 o más infracciones</strong> en el período seleccionado.
                    <br><small>Una infracción se cuenta cuando: <em>(a)</em> no se registró la salida del equipo, <em>(b)</em> existe una observación/anomalía, o <em>(c)</em> fue cerrada automáticamente.</small>
                </p>
                <div>
                    <button class="btn-sena-primary" id="btn-procesar-cierres">
                        <i class="fas fa-cogs"></i> Procesar Cierres Automáticos (Días Anteriores)
                    </button>
                    <small class="subtitle-hint">
                        Busca ingresos de días pasados que nunca salieron y los cierra con una nota de infracción. Todo esto también ocurre automáticamente a media noche.
                    </small>
                </div>
            </div>

            <!-- Panel de filtros -->
            <section class="filter-panel">
                <h3><i class="fas fa-filter"></i> Filtrar por Rango de Revisión</h3>

                <form id="form-filtros-seguimiento" method="GET" action="/admin/seguimiento-equipos">
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

            <!-- Barra resumen + botón exportar -->
            <div class="result-bar">
                <span class="result-count">
                    Aprendices con infracciones: <strong><?= count($infractores) ?></strong>
                    &nbsp;|&nbsp;
                    Período: <strong><?= htmlspecialchars($fechaInicio) ?></strong>
                    al <strong><?= htmlspecialchars($fechaFin) ?></strong>
                </span>

                <span id="auto-reload-indicator" class="auto-reload-indicator" title="La página se recarga automáticamente cada 30 segundos">
                    <i class="fas fa-sync-alt"></i> Auto-refresh: <strong id="reload-countdown">30</strong>s
                </span>

                <button id="btn-exportar-excel-seguimiento" class="btn-sena-secondary" type="button">
                    <i class="fas fa-file-excel"></i> Exportar Detalle a Excel
                </button>
            </div>

            <?php if (empty($infractores)): ?>
                <div class="empty-reporte empty-reporte--seguimiento">
                    <i class="fas fa-check-circle empty-icon"></i>
                    <p class="empty-text">No se encontraron aprendices con infracciones en el rango seleccionado.</p>
                </div>
            <?php else: ?>

            <div class="reporte-table-wrapper reporte-table-wrapper--seguimiento">
                <table class="reporte-table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Aprendiz</th>
                            <th>Ficha</th>
                            <th>Severidad</th>
                            <th class="th-infracciones">
                                <i class="fas fa-exclamation-circle"></i>
                                Infracciones
                                <small>(click para detalle)</small>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($infractores as $fila): ?>
                        <?php
                            $count = (int) $fila['total_infracciones'];
                            if ($count >= 10) {
                                $sevClass = 'severity-badge--critical';
                                $sevLabel = 'Crítico';
                            } elseif ($count >= 6) {
                                $sevClass = 'severity-badge--high';
                                $sevLabel = 'Alto';
                            } elseif ($count >= 3) {
                                $sevClass = 'severity-badge--medium';
                                $sevLabel = 'Medio';
                            } else {
                                $sevClass = 'severity-badge--low';
                                $sevLabel = 'Bajo';
                            }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($fila['documento']) ?></td>
                            <td><strong><?= htmlspecialchars($fila['nombre_completo']) ?></strong></td>
                            <td><?= htmlspecialchars($fila['numero_ficha'] ?? 'N/A') ?></td>
                            <td>
                                <span class="severity-badge <?= $sevClass ?>"><?= $sevLabel ?></span>
                            </td>
                            <td class="td-infracciones-count">
                                <span class="infraccion-count"
                                      data-aprendiz-id="<?= htmlspecialchars($fila['id_aprendiz']) ?>"
                                      data-aprendiz-nombre="<?= htmlspecialchars($fila['nombre_completo']) ?>"
                                      data-aprendiz-doc="<?= htmlspecialchars($fila['documento']) ?>"
                                      title="Click para ver detalle de infracciones">
                                    <i class="fas fa-eye"></i> <?= htmlspecialchars($fila['total_infracciones']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php endif; ?>

        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje | <strong>SENAttend</strong></p>
        </div>
    </footer>
</div>

<!-- Modal de Detalle de Infracciones -->
<div class="modal-backdrop" id="modal-detalle-infracciones">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-titulo">
                <i class="fas fa-clipboard-list"></i> Detalle de Infracciones
            </h3>
            <button class="modal-close" id="modal-cerrar" title="Cerrar">&times;</button>
        </div>
        <div class="modal-body" id="modal-cuerpo">
            <div class="modal-loading">
                <i class="fas fa-spinner fa-spin modal-loading__icon"></i>
                <p class="modal-loading__text">Cargando infracciones...</p>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('assets/js/seguimiento-equipos.js') ?>"></script>
<!-- SweetAlert2 for notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
