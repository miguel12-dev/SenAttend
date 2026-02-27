<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestión de Eventos</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/eventos/admin.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'eventos-dashboard';
        require __DIR__ . '/../../components/header-eventos.php'; 
        ?>

    <main class="main-content">
            <div class="container">
        <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($_GET['mensaje']) ?>
        </div>
        <?php endif; ?>

                <div class="dashboard-header">
                    <div>
                        <h2>
                            <i class="fas fa-calendar-alt"></i>
                            Dashboard de Eventos
                        </h2>
                        <p class="subtitle">Gestiona los eventos del SENA</p>
            </div>
                    <a href="/eventos/admin/crear" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i>
                        Nuevo Evento
            </a>
                </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card stat-total">
                <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-value"><?= $stats['total_eventos'] ?></span>
                    <span class="stat-label">Total Eventos</span>
                </div>
            </div>
            <div class="stat-card stat-active">
                <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-value"><?= $stats['eventos_activos'] ?></span>
                    <span class="stat-label">Eventos Activos</span>
                </div>
            </div>
            <div class="stat-card stat-finished">
                <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-value"><?= $stats['eventos_finalizados'] ?></span>
                    <span class="stat-label">Finalizados</span>
                </div>
            </div>
        </div>

        <!-- Events Table -->
        <section class="events-section">
            <div class="section-header">
                        <h3>
                            <i class="fas fa-list"></i>
                            Todos los Eventos
                        </h3>
                        <a href="/eventos/qr/scanner" class="btn btn-secondary">
                            <i class="fas fa-qrcode"></i>
                            Escáner QR
                </a>
            </div>

            <?php if (empty($eventos)): ?>
                    <div class="empty-state" style="text-align: center; padding: 3rem 1rem;">
                        <i class="fas fa-calendar-alt" style="font-size: 4rem; color: #999; margin-bottom: 1rem;"></i>
                <h3>No hay eventos registrados</h3>
                        <p style="color: #666;">Crea tu primer evento para comenzar</p>
                        <a href="/eventos/admin/crear" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-plus-circle"></i>
                            Crear Evento
                        </a>
            </div>
            <?php else: ?>
                    <div class="table-wrapper">
                        <table class="table events-table">
                    <thead>
                        <tr>
                            <th>Evento</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Participantes</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventos as $evento): ?>
                        <tr>
                            <td class="event-title">
                                <strong><?= htmlspecialchars($evento['titulo']) ?></strong>
                                <?php if ($evento['descripcion']): ?>
                                        <br><small style="color: #666;"><?= htmlspecialchars(substr($evento['descripcion'], 0, 50)) ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="date-badge">
                                    <?= date('d/m/Y H:i', strtotime($evento['fecha_inicio'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="date-badge">
                                    <?= date('d/m/Y H:i', strtotime($evento['fecha_fin'])) ?>
                                </span>
                            </td>
                            <td>
                                        <span class="badge badge-success">
                                    <?= $evento['total_participantes'] ?? 0 ?>
                                </span>
                            </td>
                            <td>
                                        <span class="badge status-badge status-<?= $evento['estado'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $evento['estado'])) ?>
                                </span>
                            </td>
                            <td class="actions">
                                        <div class="actions-row">
                                            <a href="/eventos/admin/<?= $evento['id'] ?>" class="btn btn-sm btn-primary" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                </a>
                                            <a href="/eventos/admin/<?= $evento['id'] ?>/participantes" class="btn btn-sm btn-primary" title="Ver participantes">
                                                <i class="fas fa-users"></i>
                                            </a>
                                        </div>
                                        <a href="/eventos/admin/<?= $evento['id'] ?>/editar" class="btn btn-sm btn-secondary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>
            </div>
    </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/eventos/admin.js') ?>"></script>
</body>
</html>

