<?php
/** @var array $user */
/** @var array $ingresosActivos */
/** @var int $totalActivos */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Portero - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/portero/panel.css') ?>">
</head>

<body>
    <div class="wrapper">
        <?php
        $currentPage = 'portero-panel';
        require __DIR__ . '/../components/header.php';
        ?>

        <main class="main-content">
            <div class="container">
                <div class="portero-dashboard">
                    <section class="portero-dashboard-header">
                        <div>
                            <h1>Panel de Portero</h1>
                            <p>Gestión de ingresos y salidas de equipos en el CTA</p>
                        </div>
                        <div class="portero-actions">
                            <a href="/portero/escanear" class="btn btn-primary">
                                <i class="fas fa-qrcode"></i> Escanear QR
                            </a>
                        </div>
                    </section>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-info">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <?= $success ?>
                        </div>
                    <?php endif; ?>

                    <section class="portero-stats">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-laptop"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="totalEquipos"><?= $totalActivos ?></h3>
                                <p>Equipos dentro del CTA</p>
                            </div>
                        </div>
                    </section>

                    <!-- Acceso Rápido a Boletas de Salida -->
                    <section class="portero-quick-access">
                        <h3 style="margin-bottom: 1rem;"><i class="fas fa-th-large"></i> Accesos Rápidos</h3>
                        <div class="quick-access-grid">
                            <a href="/portero/boletas-salida" class="quick-access-card">
                                <div class="quick-icon">
                                    <i class="fas fa-file-export"></i>
                                </div>
                                <div class="quick-info">
                                    <h4>Boletas de Salida</h4>
                                    <p>Validar salidas y reingresos de aprendices</p>
                                </div>
                            </a>
                            <a href="/portero/escanear" class="quick-access-card">
                                <div class="quick-icon">
                                    <i class="fas fa-qrcode"></i>
                                </div>
                                <div class="quick-info">
                                    <h4>Escanear Equipos</h4>
                                    <p>Registrar ingreso de equipos</p>
                                </div>
                            </a>
                            <a href="/reportes-equipos" class="quick-access-card">
                                <div class="quick-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <div class="quick-info">
                                    <h4>Reporte de Equipos</h4>
                                    <p>Consultar y exportar ingresos/salidas</p>
                                </div>
                            </a>
                        </div>
                    </section>

                    <!-- Sección destacada de Reportes -->
                    <!-- <section class="portero-reports-section" style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
                        <div style="background-color: var(--sena-green, #39A900); color: white; padding: 1.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 6px rgba(0,0,0,0.1); flex-wrap: wrap; gap: 1rem;">
                            <div style="flex: 1; min-width: 250px;">
                                <h3 style="margin-top: 0; margin-bottom: 0.5rem; font-size: 1.25rem;"><i class="fas fa-chart-line"></i> Módulo de Reportes de Equipos</h3>
                                <p style="margin: 0; opacity: 0.9;">Genera y exporta reportes en Excel de ingresos y salidas de equipos filtrados por fecha y turno.</p>
                            </div>
                            <div>
                                <a href="/reportes-equipos" class="btn btn-outline" style="background-color: white; color: var(--sena-green, #39A900); border: none; font-weight: bold; padding: 0.5rem 1rem; border-radius: 0.375rem; text-decoration: none; display: inline-block;">
                                    Generar Reportes
                                </a>
                            </div>
                        </div>
                    </section> -->

                    <section class="portero-ingresos-card">
                        <div class="portero-ingresos-header">
                            <h2>Ingresos activos (sin salida)</h2>
                            <button type="button" class="btn btn-outline btn-sm" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Actualizar
                            </button>
                        </div>
                        <div class="portero-ingresos-list">
                            <?php if (!empty($ingresosActivos)): ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Hora ingreso</th>
                                            <th>Equipo</th>
                                            <th>Serial</th>
                                            <th>Aprendiz</th>
                                            <th>Documento</th>
                                            <th>Portero</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ingresosTableBody">
                                        <?php foreach ($ingresosActivos as $ingreso): ?>
                                            <tr>
                                                <td data-label="Hora ingreso">
                                                    <strong><?= htmlspecialchars($ingreso['fecha_ingreso']) ?></strong><br>
                                                    <small><?= htmlspecialchars($ingreso['hora_ingreso']) ?></small>
                                                </td>
                                                <td data-label="Equipo"><?= htmlspecialchars($ingreso['marca']) ?></td>
                                                <td data-label="Serial"><code><?= htmlspecialchars($ingreso['numero_serial']) ?></code></td>
                                                <td data-label="Aprendiz">
                                                    <?= htmlspecialchars($ingreso['aprendiz_nombre'] . ' ' . $ingreso['aprendiz_apellido']) ?>
                                                </td>
                                                <td data-label="Documento"><?= htmlspecialchars($ingreso['aprendiz_documento']) ?></td>
                                                <td data-label="Portero"><?= htmlspecialchars($ingreso['portero_nombre']) ?></td>
                                                <td data-label="Observaciones">
                                                    <?= !empty($ingreso['observaciones'])
                                                        ? htmlspecialchars($ingreso['observaciones'])
                                                        : '<span style="color:#999;">Sin observaciones</span>' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="empty-state">No hay equipos dentro del CTA en este momento.</p>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/portero/panel.js') ?>"></script>
</body>

</html>