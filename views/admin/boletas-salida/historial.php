<?php
$title = 'Boletas de Salida - Historial del Sistema';
$styles = ['css/boletas-salida/boletas.css'];
$scripts = ['js/boletas-salida/admin-historial.js'];
ob_start();
?>

<div class="wrapper">
    <?php 
    $currentPage = 'boletas-salida';
    require __DIR__ . '/../../components/header.php'; 
    ?>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-history"></i> Historial del Sistema</h1>
                <p>Todas las boletas de salida registradas</p>
            </div>

            <div class="tabs-nav">
                <a href="/admin/boletas-salida" class="tab-link">
                    <i class="fas fa-clock"></i> Pendientes
                </a>
                <a href="/admin/boletas-salida/historial" class="tab-link active">
                    <i class="fas fa-history"></i> Historial
                </a>
            </div>

            <div class="filters-card">
                <form method="GET" action="/admin/boletas-salida/historial" class="filters-form">
                    <div class="filter-group">
                        <label for="estado">Estado:</label>
                        <select name="estado" id="estado" class="form-control">
                            <option value="">Todos</option>
                            <option value="pendiente_instructor">Pendiente instructor</option>
                            <option value="aprobado_instructor">Aprobado instructor</option>
                            <option value="rechazado_instructor">Rechazado instructor</option>
                            <option value="pendiente_admin">Pendiente admin</option>
                            <option value="aprobado_admin">Aprobado admin</option>
                            <option value="rechazado_admin">Rechazado admin</option>
                            <option value="aprobado_final">Aprobado final</option>
                            <option value="completado">Completado</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="tipo_salida">Tipo:</label>
                        <select name="tipo_salida" id="tipo_salida" class="form-control">
                            <option value="">Todos</option>
                            <option value="temporal">Temporal</option>
                            <option value="definitiva">Definitiva</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="fecha_desde">Desde:</label>
                        <input type="date" name="fecha_desde" id="fecha_desde" class="form-control">
                    </div>
                    <div class="filter-group">
                        <label for="fecha_hasta">Hasta:</label>
                        <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="/admin/boletas-salida/historial" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <?php if (empty($boletas)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3>No hay resultados</h3>
                        <p>No se encontraron boletas con los filtros aplicados.</p>
                    </div>
                <?php else: ?>
                    <table class="boletas-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Aprendiz</th>
                                <th>Ficha</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($boletas as $boleta): ?>
                                <tr>
                                    <td><?= $boleta['id'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($boleta['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($boleta['aprendiz_nombre'] . ' ' . $boleta['aprendiz_apellido']) ?></td>
                                    <td><?= htmlspecialchars($boleta['numero_ficha']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $boleta['tipo_salida'] === 'temporal' ? 'info' : 'warning' ?>">
                                            <?= ucfirst($boleta['tipo_salida']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge">
                                            <?= $boleta['estado'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-secondary btn-detalle" data-id="<?= $boleta['id'] ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje. Todos los derechos reservados.</p>
        </div>
    </footer>
</div>

<div id="modalDetalle" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Detalle de Solicitud</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body" id="detalleContent"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary modal-close">Cerrar</button>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/pwa-base.php';
?>
