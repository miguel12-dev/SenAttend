<?php
/**
 * Vista del panel de boletas de salida para instructores
 * Muestra solicitudes pendientes de aprobación
 */

$pageTitle = 'Boletas de Salida - Solicitudes Pendientes';
$title = 'Boletas de Salida - Solicitudes Pendientes';
$styles = [
    'css/boletas-salida/boletas.css'
];
$scripts = [
    'js/boletas-salida/instructor-panel.js'
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
            <div class="instructor-boletas-container">
                <div class="page-header">
                    <h1><i class="fas fa-file-export"></i> Boletas de Salida</h1>
                    <p>Solicitudes pendientes de aprobación</p>
                </div>

                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $contadores['pendientes'] ?? 0 ?></h3>
                            <p>Pendientes</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $contadores['aprobadas'] ?? 0 ?></h3>
                            <p>Aprobadas</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-danger">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $contadores['rechazadas'] ?? 0 ?></h3>
                            <p>Rechazadas</p>
                        </div>
                    </div>
                </div>

                <!-- Navegación de pestañas -->
                <div class="tabs-nav">
                    <a href="/instructor/boletas-salida" class="tab-link active">
                        <i class="fas fa-clock"></i> Pendientes
                    </a>
                    <a href="/instructor/boletas-salida/historial" class="tab-link">
                        <i class="fas fa-history"></i> Historial
                    </a>
                </div>

                <!-- Listado de boletas pendientes -->
                <div class="boletas-list">
                    <?php if (empty($boletasPendientes)): ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-check"></i>
                            <h3>No hay solicitudes pendientes</h3>
                            <p>Todas las solicitudes han sido procesadas.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($boletasPendientes as $boleta): ?>
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
                                        <?= date('d/m/Y H:i', strtotime($boleta['created_at'])) ?>
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
                                                'diligencia_electoral' => 'Diligencias electorales / Gubernamentales',
                                                'tramite_etapa_productiva' => 'Trámites etapa productiva',
                                                'requerimiento_laboral' => 'Requerimientos laborales',
                                                'fuerza_mayor' => 'Casos fortuitos / fuerza mayor',
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
                                        <span class="detail-label">Hora de salida:</span>
                                        <span class="detail-value"><?= date('H:i', strtotime($boleta['hora_salida_solicitada'])) ?></span>
                                    </div>
                                    <?php if ($boleta['tipo_salida'] === 'temporal' && $boleta['hora_reingreso_solicitada']): ?>
                                        <div class="detail-row">
                                            <span class="detail-label">Hora de reingreso:</span>
                                            <span class="detail-value"><?= date('H:i', strtotime($boleta['hora_reingreso_solicitada'])) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="boleta-actions">
                                    <button type="button" class="btn btn-success btn-aprobar" data-id="<?= $boleta['id'] ?>">
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                    <button type="button" class="btn btn-danger btn-rechazar" data-id="<?= $boleta['id'] ?>">
                                        <i class="fas fa-times"></i> Rechazar
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

<!-- Modal de rechazo -->
<div id="modalRechazar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Rechazar Solicitud</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p>Por favor, indique el motivo del rechazo:</p>
            <textarea id="motivoRechazo" class="form-control" rows="4" placeholder="Escriba el motivo..."></textarea>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="btnCancelarRechazo">Cancelar</button>
            <button type="button" class="btn btn-danger" id="btnConfirmarRechazo">Confirmar Rechazo</button>
        </div>
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

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/pwa-base.php';
?>
