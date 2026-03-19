<?php
/**
 * Vista del panel de boletas de salida para porteros
 * Valida salidas físicas y reingresos
 */

$pageTitle = 'Boletas de Salida - Validación';
require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="/css/boletas-salida/boletas.css">

<div class="portero-boletas-container">
    <div class="page-header">
        <h1><i class="fas fa-door-open"></i> Validación de Salidas</h1>
        <p>Control de salidas y reingresos aprobados</p>
    </div>

    <!-- Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <div class="stat-info">
                <h3><?= $contadores['pendientes_salida'] ?? 0 ?></h3>
                <p>Salidas Pendientes</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <div class="stat-info">
                <h3><?= $contadores['pendientes_reingreso'] ?? 0 ?></h3>
                <p>Reingresos Pendientes</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?= $contadores['completados'] ?? 0 ?></h3>
                <p>Completados Hoy</p>
            </div>
        </div>
    </div>

    <!-- Navegación de pestañas -->
    <div class="tabs-nav">
        <a href="#salidas" class="tab-link active" data-tab="salidas">
            <i class="fas fa-sign-out-alt"></i> Salidas Aprobadas
        </a>
        <a href="#reingresos" class="tab-link" data-tab="reingresos">
            <i class="fas fa-sign-in-alt"></i> Reingresos Pendientes
        </a>
    </div>

    <!-- Tab: Salidas Aprobadas -->
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
                                <span class="detail-value"><?= htmlspecialchars($boleta['numero_ficha']) ?> - <?= htmlspecialchars($boleta['ficha_nombre']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Motivo:</span>
                                <span class="detail-value">
                                    <?php
                                    $motivos = [
                                        'cita_medica' => 'Cita / incapacidad médica',
                                        'diligencia_electoral' => 'Diligencias electorales',
                                        'tramite_etapa_productiva' => 'Trámites etapa productiva',
                                        'requerimiento_laboral' => 'Requerimientos laborales',
                                        'fuerza_mayor' => 'Fuerza mayor',
                                        'representacion_sena' => 'Representación SENA',
                                        'diligencia_judicial' => 'Diligencias judiciales',
                                        'otro' => 'Otro'
                                    ];
                                    echo $motivos[$boleta['motivo']] ?? $boleta['motivo'];
                                    ?>
                                </span>
                            </div>
                            <?php if ($boleta['motivo'] === 'otro' && !empty($boleta['motivo_otro'])): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Descripción:</span>
                                    <span class="detail-value"><?= htmlspecialchars($boleta['motivo_otro']) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <span class="detail-label">Hora de salida solicitada:</span>
                                <span class="detail-value"><?= date('H:i', strtotime($boleta['hora_salida_solicitada'])) ?></span>
                            </div>
                            <?php if ($boleta['tipo_salida'] === 'temporal' && $boleta['hora_reingreso_solicitada']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Hora de reingreso solicitada:</span>
                                    <span class="detail-value"><?= date('H:i', strtotime($boleta['hora_reingreso_solicitada'])) ?></span>
                                </div>
                            <?php endif; ?>
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

    <!-- Tab: Reingresos Pendientes -->
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
                                <span class="detail-label">Ficha:</span>
                                <span class="detail-value"><?= htmlspecialchars($boleta['numero_ficha']) ?> - <?= htmlspecialchars($boleta['ficha_nombre']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Hora de reingreso esperada:</span>
                                <span class="detail-value"><?= date('H:i', strtotime($boleta['hora_reingreso_solicitada'])) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Salida validada por:</span>
                                <span class="detail-value"><?= htmlspecialchars($boleta['portero_validador_nombre'] ?? 'N/A') ?></span>
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

<!-- Modal de validación de reingreso -->
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

<!-- Modal de detalle -->
<div id="modalDetalle" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Detalle de Boleta</h3>
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

<script src="/js/boletas-salida/portero-panel.js"></script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
