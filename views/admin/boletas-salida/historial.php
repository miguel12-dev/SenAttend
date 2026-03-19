<?php
/**
 * Vista del historial completo de boletas de salida para administradores
 * Muestra todas las solicitudes del sistema con filtros
 */

$pageTitle = 'Boletas de Salida - Historial del Sistema';
require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="/css/boletas-salida/boletas.css">

<div class="admin-boletas-container">
    <div class="page-header">
        <h1><i class="fas fa-history"></i> Historial del Sistema</h1>
        <p>Todas las boletas de salida registradas</p>
    </div>

    <!-- Navegación de pestañas -->
    <div class="tabs-nav">
        <a href="/admin/boletas-salida" class="tab-link">
            <i class="fas fa-clock"></i> Pendientes
        </a>
        <a href="/admin/boletas-salida/historial" class="tab-link active">
            <i class="fas fa-history"></i> Historial
        </a>
    </div>

    <!-- Filtros -->
    <div class="filters-card">
        <form method="GET" action="/admin/boletas-salida/historial" class="filters-form">
            <div class="filter-group">
                <label for="estado">Estado:</label>
                <select name="estado" id="estado" class="form-control">
                    <option value="">Todos</option>
                    <option value="pendiente_instructor" <?= isset($_GET['estado']) && $_GET['estado'] === 'pendiente_instructor' ? 'selected' : '' ?>>Pendiente instructor</option>
                    <option value="aprobado_instructor" <?= isset($_GET['estado']) && $_GET['estado'] === 'aprobado_instructor' ? 'selected' : '' ?>>Aprobado instructor</option>
                    <option value="rechazado_instructor" <?= isset($_GET['estado']) && $_GET['estado'] === 'rechazado_instructor' ? 'selected' : '' ?>>Rechazado instructor</option>
                    <option value="pendiente_admin" <?= isset($_GET['estado']) && $_GET['estado'] === 'pendiente_admin' ? 'selected' : '' ?>>Pendiente admin</option>
                    <option value="aprobado_admin" <?= isset($_GET['estado']) && $_GET['estado'] === 'aprobado_admin' ? 'selected' : '' ?>>Aprobado admin</option>
                    <option value="rechazado_admin" <?= isset($_GET['estado']) && $_GET['estado'] === 'rechazado_admin' ? 'selected' : '' ?>>Rechazado admin</option>
                    <option value="aprobado_final" <?= isset($_GET['estado']) && $_GET['estado'] === 'aprobado_final' ? 'selected' : '' ?>>Aprobado final</option>
                    <option value="completado" <?= isset($_GET['estado']) && $_GET['estado'] === 'completado' ? 'selected' : '' ?>>Completado</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="tipo_salida">Tipo:</label>
                <select name="tipo_salida" id="tipo_salida" class="form-control">
                    <option value="">Todos</option>
                    <option value="temporal" <?= isset($_GET['tipo_salida']) && $_GET['tipo_salida'] === 'temporal' ? 'selected' : '' ?>>Temporal</option>
                    <option value="definitiva" <?= isset($_GET['tipo_salida']) && $_GET['tipo_salida'] === 'definitiva' ? 'selected' : '' ?>>Definitiva</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="fecha_desde">Desde:</label>
                <input type="date" name="fecha_desde" id="fecha_desde" class="form-control" value="<?= $_GET['fecha_desde'] ?? '' ?>">
            </div>
            <div class="filter-group">
                <label for="fecha_hasta">Hasta:</label>
                <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" value="<?= $_GET['fecha_hasta'] ?? '' ?>">
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

    <!-- Tabla de historial -->
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
                        <th>Fecha Solicitud</th>
                        <th>Aprendiz</th>
                        <th>Ficha</th>
                        <th>Tipo</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                        <th>Instructor</th>
                        <th>Admin</th>
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
                                <?php
                                $motivos = [
                                    'cita_medica' => 'Cita médica',
                                    'diligencia_electoral' => 'Dil. electoral',
                                    'tramite_etapa_productiva' => 'Trámite EP',
                                    'requerimiento_laboral' => 'Req. laboral',
                                    'fuerza_mayor' => 'Fuerza mayor',
                                    'representacion_sena' => 'Rep. SENA',
                                    'diligencia_judicial' => 'Dil. judicial',
                                    'otro' => 'Otro'
                                ];
                                echo $motivos[$boleta['motivo']] ?? $boleta['motivo'];
                                ?>
                            </td>
                            <td>
                                <?php
                                $estadoClasses = [
                                    'pendiente_instructor' => 'warning',
                                    'aprobado_instructor' => 'info',
                                    'rechazado_instructor' => 'danger',
                                    'pendiente_admin' => 'warning',
                                    'aprobado_admin' => 'info',
                                    'rechazado_admin' => 'danger',
                                    'aprobado_final' => 'success',
                                    'completado' => 'success',
                                ];
                                $estadoLabels = [
                                    'pendiente_instructor' => 'Pendiente instructor',
                                    'aprobado_instructor' => 'Aprobado instructor',
                                    'rechazado_instructor' => 'Rechazado instructor',
                                    'pendiente_admin' => 'Pendiente admin',
                                    'aprobado_admin' => 'Aprobado admin',
                                    'rechazado_admin' => 'Rechazado admin',
                                    'aprobado_final' => 'Aprobado final',
                                    'completado' => 'Completado',
                                ];
                                ?>
                                <span class="badge badge-<?= $estadoClasses[$boleta['estado']] ?? 'secondary' ?>">
                                    <?= $estadoLabels[$boleta['estado']] ?? $boleta['estado'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($boleta['instructor_aprobado_por']): ?>
                                    <span class="badge badge-success">Aprobó</span>
                                <?php elseif ($boleta['instructor_rechazado_por']): ?>
                                    <span class="badge badge-danger">Rechazó</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($boleta['admin_aprobado_por']): ?>
                                    <span class="badge badge-success">Aprobó</span>
                                <?php elseif ($boleta['admin_rechazado_por']): ?>
                                    <span class="badge badge-danger">Rechazó</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Pendiente</span>
                                <?php endif; ?>
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

<!-- Modal de detalle -->
<div id="modalDetalle" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Detalle de Solicitud</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body" id="detalleContent">
            <!-- Contenido dinámico -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary modal-close">Cerrar</button>
        </div>
    </div>
</div>

<script src="/js/boletas-salida/admin-historial.js"></script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
