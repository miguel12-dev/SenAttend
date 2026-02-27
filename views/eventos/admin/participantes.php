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
                <div class="page-header-participantes">
                    <div>
                        <a href="/eventos/admin/<?= $evento['id'] ?>" class="btn-back">
                            <i class="fas fa-arrow-left"></i>
                            Volver al evento
                        </a>
                        <h2>
                            <i class="fas fa-users"></i>
                            Participantes
                        </h2>
                        <p class="subtitle"><?= htmlspecialchars($evento['titulo']) ?></p>
            </div>
                    <button class="btn btn-secondary" onclick="actualizarLista()">
                        <i class="fas fa-sync-alt"></i>
                        Actualizar
            </button>
                </div>

        <!-- Stats Cards -->
                <div class="stats-grid participantes-stats">
            <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                <div class="stat-content">
                    <span class="stat-value"><?= array_sum($estadisticas) ?></span>
                    <span class="stat-label">Total Registrados</span>
                </div>
            </div>
            <div class="stat-card stat-info">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                <div class="stat-content">
                    <span class="stat-value"><?= $estadisticas['registrado'] ?? 0 ?></span>
                    <span class="stat-label">Pendientes</span>
                </div>
            </div>
            <div class="stat-card stat-active">
                        <div class="stat-icon">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                <div class="stat-content">
                    <span class="stat-value"><?= $estadisticas['ingreso'] ?? 0 ?></span>
                    <span class="stat-label">Ingresaron</span>
                </div>
            </div>
            <div class="stat-card stat-finished">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                <div class="stat-content">
                    <span class="stat-value"><?= $estadisticas['salida'] ?? 0 ?></span>
                    <span class="stat-label">Finalizaron</span>
                </div>
            </div>
            <div class="stat-card stat-warning">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                <div class="stat-content">
                    <span class="stat-value"><?= $estadisticas['sin_salida'] ?? 0 ?></span>
                    <span class="stat-label">Sin Salida</span>
                </div>
            </div>
            <div class="stat-card stat-danger">
                        <div class="stat-icon">
                            <i class="fas fa-user-times"></i>
                        </div>
                <div class="stat-content">
                    <span class="stat-value"><?= $estadisticas['ausente'] ?? 0 ?></span>
                    <span class="stat-label">Ausentes</span>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-bar">
            <div class="filter-group">
                        <label><i class="fas fa-filter"></i> Filtrar por estado:</label>
                        <select id="filterEstado" class="form-control" onchange="filtrarParticipantes()">
                    <option value="">Todos</option>
                    <option value="registrado">Registrados</option>
                    <option value="ingreso">Ingresaron</option>
                    <option value="salida">Finalizaron</option>
                    <option value="ausente">Ausentes</option>
                    <option value="sin_salida">Sin Salida</option>
                </select>
            </div>
            <div class="filter-group">
                        <label><i class="fas fa-search"></i> Buscar:</label>
                        <input type="text" id="searchInput" class="form-control" placeholder="Buscar por nombre o documento..." onkeyup="filtrarParticipantes()">
            </div>
        </div>

        <!-- Tabla de participantes -->
        <?php if (empty($participantes)): ?>
                <div class="empty-state-participantes">
                    <i class="fas fa-users"></i>
            <h3>No hay participantes registrados</h3>
            <p>Los participantes aparecerán aquí cuando se registren al evento</p>
        </div>
        <?php else: ?>
                <div class="card">
                    <div class="table-wrapper">
                        <table class="table participantes-table" id="participantesTable">
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
                            <td><code><?= htmlspecialchars($p['documento']) ?></code></td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                        <td>
                                <span class="badge type-badge type-<?= $p['tipo'] ?>">
                                <?= ucfirst($p['tipo']) ?>
                            </span>
                        </td>
                        <td>
                                <span class="badge status-badge status-<?= $p['estado'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $p['estado'])) ?>
                            </span>
                        </td>
                        <td>
                            <small><?= date('d/m/Y H:i', strtotime($p['fecha_registro'])) ?></small>
                        </td>
                        <td>
                            <?php if ($p['fecha_ingreso']): ?>
                                <small class="time-badge"><i class="fas fa-clock"></i> <?= date('H:i', strtotime($p['fecha_ingreso'])) ?></small>
                            <?php else: ?>
                            <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['fecha_salida']): ?>
                                <small class="time-badge"><i class="fas fa-clock"></i> <?= date('H:i', strtotime($p['fecha_salida'])) ?></small>
                            <?php else: ?>
                            <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
                    </div>
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

