<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participantes - <?= htmlspecialchars($evento['titulo']) ?></title>
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
                <div class="page-header">
                    <a href="/eventos/admin/<?= $evento['id'] ?>" class="btn-back">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                    <h2>
                        <i class="fas fa-users"></i>
                        <?= htmlspecialchars($evento['titulo']) ?>
                    </h2>
                    <p class="subtitle">Lista de participantes registrados</p>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
                    <button class="btn btn-secondary" onclick="actualizarLista()">
                        <i class="fas fa-sync"></i>
                        Actualizar
                    </button>
                </div>

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
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
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

