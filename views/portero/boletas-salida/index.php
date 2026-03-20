<?php
$title = 'Boletas de Salida - Validación';
$styles = [
    'css/common/notification-modal.css',
    'css/boletas-salida/boletas.css'
];
$scripts = [
    'js/common/notification-modal.js',
    'js/boletas-salida/portero-panel.js'
];
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
                <h1><i class="fas fa-door-open"></i> Validación de Salidas</h1>
                <p>Control de salidas y reingresos aprobados</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $contadores['pendientes_salida'] ?? 0 ?></h3>
                        <p>Salidas Pendientes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $contadores['pendientes_reingreso'] ?? 0 ?></h3>
                        <p>Reingresos Pendientes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $contadores['completados'] ?? 0 ?></h3>
                        <p>Completados Hoy</p>
                    </div>
                </div>
            </div>

            <div class="tabs-nav">
                <a href="#salidas" class="tab-link active" data-tab="salidas">
                    <i class="fas fa-sign-out-alt"></i> Salidas Aprobadas
                </a>
                <a href="#reingresos" class="tab-link" data-tab="reingresos">
                    <i class="fas fa-sign-in-alt"></i> Reingresos Pendientes
                </a>
            </div>

            <div id="tab-salidas" class="tab-content active">
                <div class="boletas-list">
                    <?php if (empty($boletasAprobadas)): ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-check"></i>
                            <h3>No hay salidas pendientes de validación</h3>
                            <p>Todas las salidas aprobadas han sido validadas.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($boletasAprobadas as $boleta): ?>
                            <div class="boleta-card">
                                <div class="boleta-header">
                                    <div class="boleta-title">
                                        <h3><?= htmlspecialchars($boleta['aprendiz_nombre'] . ' ' . $boleta['aprendiz_apellido']) ?></h3>
                                        <span class="badge badge-<?= $boleta['tipo_salida'] === 'temporal' ? 'info' : 'warning' ?>">
                                            <?= ucfirst($boleta['tipo_salida']) ?>
                                        </span>
                                    </div>
                                    <div class="boleta-date">
                                        <i class="far fa-calendar"></i>
                                        Solicitada: <?= date('d/m/Y H:i', strtotime($boleta['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="boleta-details">
                                    <div class="detail-row">
                                        <span class="detail-label">Documento:</span>
                                        <span class="detail-value"><?= htmlspecialchars($boleta['aprendiz_documento']) ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Ficha:</span>
                                        <span class="detail-value"><?= htmlspecialchars($boleta['numero_ficha']) ?></span>
                                    </div>
                                </div>
                                <div class="boleta-actions">
                                    <button type="button" class="btn btn-success btn-validar-salida" data-id="<?= $boleta['id'] ?>">
                                        <i class="fas fa-check"></i> Validar Salida
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-detalle" data-id="<?= $boleta['id'] ?>">
                                        <i class="fas fa-eye"></i> Ver Detalle
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div id="tab-reingresos" class="tab-content">
                <div class="boletas-list">
                    <?php if (empty($reingresosPendientes)): ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-check"></i>
                            <h3>No hay reingresos pendientes</h3>
                            <p>Todas las salidas temporales han sido completadas.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reingresosPendientes as $boleta): ?>
                            <div class="boleta-card">
                                <div class="boleta-header">
                                    <div class="boleta-title">
                                        <h3><?= htmlspecialchars($boleta['aprendiz_nombre'] . ' ' . $boleta['aprendiz_apellido']) ?></h3>
                                        <span class="badge badge-warning">Pendiente Reingreso</span>
                                    </div>
                                    <div class="boleta-date">
                                        <i class="far fa-clock"></i>
                                        Salida: <?= date('d/m/Y H:i', strtotime($boleta['fecha_salida_real'])) ?>
                                    </div>
                                </div>
                                <div class="boleta-details">
                                    <div class="detail-row">
                                        <span class="detail-label">Documento:</span>
                                        <span class="detail-value"><?= htmlspecialchars($boleta['aprendiz_documento']) ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Hora esperada:</span>
                                        <span class="detail-value"><?= date('H:i', strtotime($boleta['hora_reingreso_solicitada'])) ?></span>
                                    </div>
                                </div>
                                <div class="boleta-actions">
                                    <button type="button" class="btn btn-primary btn-validar-reingreso" data-id="<?= $boleta['id'] ?>">
                                        <i class="fas fa-sign-in-alt"></i> Validar Reingreso
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-detalle" data-id="<?= $boleta['id'] ?>">
                                        <i class="fas fa-eye"></i> Ver Detalle
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje. Todos los derechos reservados.</p>
        </div>
    </footer>
</div>

<!-- Modal de confirmación de salida -->
<div id="modalValidarSalida" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Validar Salida</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p>¿Confirma la salida física del aprendiz?</p>
            <p class="text-muted">Se registrará la hora actual de salida.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="btnCancelarSalida">Cancelar</button>
            <button type="button" class="btn btn-success" id="btnConfirmarSalida">Confirmar Salida</button>
        </div>
    </div>
</div>

<div id="modalReingreso" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Validar Reingreso</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p>Observaciones del reingreso (opcional):</p>
            <textarea id="observacionesReingreso" class="form-control" rows="3" placeholder="Escriba observaciones si las hay..."></textarea>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="btnCancelarReingreso">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnConfirmarReingreso">Confirmar Reingreso</button>
        </div>
    </div>
</div>

<div id="modalDetalle" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Detalle de Boleta</h3>
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
