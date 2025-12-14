<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestión de Eventos | SENAttend</title>
    <link rel="stylesheet" href="<?= asset('css/eventos/admin.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <div class="brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM9 10H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/>
                </svg>
            </div>
            <span>Gestión de Eventos</span>
        </div>
        <div class="navbar-user">
            <span class="user-name"><?= htmlspecialchars($user['nombre']) ?></span>
            <span class="user-role"><?= htmlspecialchars(ucfirst($user['rol'])) ?></span>
            <a href="/eventos/logout" class="btn-logout" title="Cerrar sesión">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                </svg>
            </a>
        </div>
    </nav>

    <main class="main-content">
        <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
            </svg>
            <span><?= htmlspecialchars($_GET['mensaje']) ?></span>
        </div>
        <?php endif; ?>

        <header class="page-header">
            <div class="header-content">
                <h1>Dashboard de Eventos</h1>
                <p>Gestiona los eventos del SENA</p>
            </div>
            <a href="/eventos/admin/crear" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                <span>Nuevo Evento</span>
            </a>
        </header>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card stat-total">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value"><?= $stats['total_eventos'] ?></span>
                    <span class="stat-label">Total Eventos</span>
                </div>
            </div>
            <div class="stat-card stat-active">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value"><?= $stats['eventos_activos'] ?></span>
                    <span class="stat-label">Eventos Activos</span>
                </div>
            </div>
            <div class="stat-card stat-finished">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
                    </svg>
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
                <h2>Todos los Eventos</h2>
                <a href="/eventos/qr/scanner" class="btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9.5 6.5v3h-3v-3h3M11 5H5v6h6V5zm-1.5 9.5v3h-3v-3h3M11 13H5v6h6v-6zm6.5-6.5v3h-3v-3h3M19 5h-6v6h6V5zm-6 8h1.5v1.5H13V13zm1.5 1.5H16V16h-1.5v-1.5zM16 13h1.5v1.5H16V13zm-3 3h1.5v1.5H13V16zm1.5 1.5H16V19h-1.5v-1.5zM16 16h1.5v1.5H16V16zm1.5-1.5H19V16h-1.5v-1.5zm0 3H19V19h-1.5v-1.5zM19 13h-1.5v1.5H19V13z"/>
                    </svg>
                    <span>Escáner QR</span>
                </a>
            </div>

            <?php if (empty($eventos)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/>
                    </svg>
                </div>
                <h3>No hay eventos registrados</h3>
                <p>Crea tu primer evento para comenzar</p>
                <a href="/eventos/admin/crear" class="btn-primary">Crear Evento</a>
            </div>
            <?php else: ?>
            <div class="table-container">
                <table class="events-table">
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
                                <small><?= htmlspecialchars(substr($evento['descripcion'], 0, 50)) ?>...</small>
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
                                <span class="participants-badge">
                                    <?= $evento['total_participantes'] ?? 0 ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $evento['estado'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $evento['estado'])) ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="/eventos/admin/<?= $evento['id'] ?>" class="btn-icon" title="Ver detalles">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                    </svg>
                                </a>
                                <a href="/eventos/admin/<?= $evento['id'] ?>/participantes" class="btn-icon" title="Ver participantes">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                                    </svg>
                                </a>
                                <a href="/eventos/admin/<?= $evento['id'] ?>/editar" class="btn-icon" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="<?= asset('js/eventos/admin.js') ?>"></script>
</body>
</html>

