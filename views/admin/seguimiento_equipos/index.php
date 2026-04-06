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
            <div class="reporte-header">
                <h2>
                    <i class="fas fa-user-times"></i>
                    Infracciones de Equipos (Sin salida)
                </h2>
                <p class="subtitle-page">
                    Aprendices que han salido 3 o más veces sin hacer el registro de retiro de su equipo.
                </p>
                <div style="margin-top: 15px;">
                    <button class="btn-sena-primary" id="btn-procesar-cierres">
                        <i class="fas fa-cogs"></i> Procesar Cierres Automáticos (Días Anteriores)
                    </button>
                    <small style="display:block; margin-top:5px; color:#555;">
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
                    Infractores encontrados: <strong><?= count($infractores) ?></strong>
                    &nbsp;|&nbsp;
                    Período: <strong><?= htmlspecialchars($fechaInicio) ?></strong>
                    al <strong><?= htmlspecialchars($fechaFin) ?></strong>
                </span>

                <button id="btn-exportar-excel-seguimiento" class="btn-sena-secondary" type="button">
                    <i class="fas fa-file-excel"></i> Exportar Detalle a Excel
                </button>
            </div>

            <?php if (empty($infractores)): ?>
                <div class="empty-reporte" style="margin-top: 2rem;">
                    <i class="fas fa-check-circle" style="color: #39A900; font-size: 3rem;"></i>
                    <p style="margin-top: 1rem;">No se encontraron aprendices con 3 o más infracciones en el rango seleccionado.</p>
                </div>
            <?php else: ?>

            <div class="reporte-table-wrapper" style="margin-top: 2rem;">
                <table class="reporte-table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Aprendiz</th>
                            <th>Ficha</th>
                            <th><i class="fas fa-exclamation-circle" style="color: red;"></i> Total Infracciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($infractores as $fila): ?>
                        <tr>
                            <td><?= htmlspecialchars($fila['documento']) ?></td>
                            <td><strong><?= htmlspecialchars($fila['nombre_completo']) ?></strong></td>
                            <td><?= htmlspecialchars($fila['numero_ficha'] ?? 'N/A') ?></td>
                            <td style="text-align: center; font-size: 1.1rem; color: #D32F2F; font-weight: bold;">
                                <?= htmlspecialchars($fila['total_infracciones']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <p style="margin-top:1rem; font-size:0.9rem; color:#666;">
                * Para ver el detalle (marcas de equipo, fechas específicas de cada infracción y observaciones), utiliza el botón <strong>Exportar Detalle a Excel</strong>.
            </p>

            <?php endif; ?>

        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje | <strong>SENAttend</strong></p>
        </div>
    </footer>
</div>

<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('assets/js/seguimiento-equipos.js') ?>"></script>
<!-- SweetAlert2 for notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
