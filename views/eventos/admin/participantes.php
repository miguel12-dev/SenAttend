<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participantes - <?= htmlspecialchars($evento['titulo']) ?> | SENAttend</title>
    <link rel="stylesheet" href="<?= asset('css/eventos/admin.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="/eventos/admin/<?= $evento['id'] ?>" class="back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                </svg>
            </a>
            <span>Participantes del Evento</span>
        </div>
        <div class="navbar-user">
            <span class="user-name"><?= htmlspecialchars($user['nombre']) ?></span>
            <a href="/eventos/logout" class="btn-logout" title="Cerrar sesión">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                </svg>
            </a>
        </div>
    </nav>

    <main class="main-content">
        <header class="page-header">
            <div class="header-content">
                <h1><?= htmlspecialchars($evento['titulo']) ?></h1>
                <p>Lista de participantes registrados</p>
            </div>
            <button class="btn-secondary" onclick="actualizarLista()">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                </svg>
                <span>Actualizar</span>
            </button>
        </header>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-value"><?= array_sum($estadisticas) ?></span>
                    <span class="stat-label">Total Registrados</span>
                </div>
            </div>
            <div class="stat-card stat-info">
                <div class="stat-content">
                    <span class="stat-value"><?= $estadisticas['registrado'] ?? 0 ?></span>
                    <span class="stat-label">Pendientes</span>
                </div>
            </div>
            <div class="stat-card stat-active">
                <div class="stat-content">
                    <span class="stat-value"><?= $estadisticas['ingreso'] ?? 0 ?></span>
                    <span class="stat-label">Ingresaron</span>
                </div>
            </div>
            <div class="stat-card stat-finished">
                <div class="stat-content">
                    <span class="stat-value"><?= $estadisticas['salida'] ?? 0 ?></span>
                    <span class="stat-label">Finalizaron</span>
                </div>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-content">
                    <span class="stat-value"><?= $estadisticas['sin_salida'] ?? 0 ?></span>
                    <span class="stat-label">Sin Salida</span>
                </div>
            </div>
            <div class="stat-card stat-danger">
                <div class="stat-content">
                    <span class="stat-value"><?= $estadisticas['ausente'] ?? 0 ?></span>
                    <span class="stat-label">Ausentes</span>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-bar">
            <div class="filter-group">
                <label>Filtrar por estado:</label>
                <select id="filterEstado" onchange="filtrarParticipantes()">
                    <option value="">Todos</option>
                    <option value="registrado">Registrados</option>
                    <option value="ingreso">Ingresaron</option>
                    <option value="salida">Finalizaron</option>
                    <option value="ausente">Ausentes</option>
                    <option value="sin_salida">Sin Salida</option>
                </select>
            </div>
            <div class="filter-group">
                <input type="text" id="searchInput" placeholder="Buscar por nombre o documento..." onkeyup="filtrarParticipantes()">
            </div>
        </div>

        <!-- Tabla de participantes -->
        <?php if (empty($participantes)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3z"/>
                </svg>
            </div>
            <h3>No hay participantes registrados</h3>
            <p>Los participantes aparecerán aquí cuando se registren al evento</p>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="events-table" id="participantesTable">
                <thead>
                    <tr>
                        <th>Participante</th>
                        <th>Documento</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Fecha Registro</th>
                        <th>Ingreso</th>
                        <th>Salida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participantes as $p): ?>
                    <tr data-estado="<?= $p['estado'] ?>" data-nombre="<?= strtolower($p['nombre'] . ' ' . $p['apellido']) ?>" data-documento="<?= $p['documento'] ?>">
                        <td>
                            <strong><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($p['documento']) ?></td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                        <td>
                            <span class="type-badge type-<?= $p['tipo'] ?>">
                                <?= ucfirst($p['tipo']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $p['estado'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $p['estado'])) ?>
                            </span>
                        </td>
                        <td>
                            <small><?= date('d/m/Y H:i', strtotime($p['fecha_registro'])) ?></small>
                        </td>
                        <td>
                            <?php if ($p['fecha_ingreso']): ?>
                            <small><?= date('H:i', strtotime($p['fecha_ingreso'])) ?></small>
                            <?php else: ?>
                            <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['fecha_salida']): ?>
                            <small><?= date('H:i', strtotime($p['fecha_salida'])) ?></small>
                            <?php else: ?>
                            <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>

    <script>
        function filtrarParticipantes() {
            const estado = document.getElementById('filterEstado').value.toLowerCase();
            const busqueda = document.getElementById('searchInput').value.toLowerCase();
            const filas = document.querySelectorAll('#participantesTable tbody tr');

            filas.forEach(fila => {
                const estadoFila = fila.dataset.estado;
                const nombre = fila.dataset.nombre;
                const documento = fila.dataset.documento;

                const coincideEstado = !estado || estadoFila === estado;
                const coincideBusqueda = !busqueda || nombre.includes(busqueda) || documento.includes(busqueda);

                fila.style.display = coincideEstado && coincideBusqueda ? '' : 'none';
            });
        }

        function actualizarLista() {
            location.reload();
        }

        // Auto-refresh cada 30 segundos si el evento está en curso
        <?php if ($evento['estado'] === 'en_curso'): ?>
        setInterval(actualizarLista, 30000);
        <?php endif; ?>
    </script>
</body>
</html>

