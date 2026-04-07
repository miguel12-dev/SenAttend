<?php
$title = 'Boletas de Salida - Historial';
$styles = ['css/boletas-salida/boletas.css'];
$scripts = ['js/boletas-salida/instructor-historial.js'];
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
                <h1><i class="fas fa-history"></i> Historial de Boletas de Salida</h1>
                <p>Todas las solicitudes procesadas</p>
            </div>

            <div class="tabs-nav">
                <a href="/instructor/boletas-salida" class="tab-link">
                    <i class="fas fa-clock"></i> Pendientes
                </a>
                <a href="/instructor/boletas-salida/historial" class="tab-link active">
                    <i class="fas fa-history"></i> Historial
                </a>
            </div>

            <div class="table-responsive">
                <?php if (empty($boletas)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3>No hay historial disponible</h3>
                        <p>No se han procesado solicitudes aún.</p>
                    </div>
                <?php else: ?>
                    <table class="boletas-table">
                        <thead>
                            <tr>
                                <th>Fecha Solicitud</th>
                                <th>Aprendiz</th>
                                <th>Ficha</th>
                                <th>Tipo</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                                <th>Decisión</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($boletas as $boleta): ?>
                                <tr>
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
                                            'pendiente_instructor' => 'Pendiente',
                                            'aprobado_instructor' => 'Aprobado por instructor',
                                            'rechazado_instructor' => 'Rechazado',
                                            'pendiente_admin' => 'Pendiente admin',
                                            'aprobado_admin' => 'Aprobado admin',
                                            'rechazado_admin' => 'Rechazado por admin',
                                            'aprobado_final' => 'Aprobado',
                                            'completado' => 'Completado',
                                        ];
                                        ?>
                                        <span class="badge badge-<?= $estadoClasses[$boleta['estado']] ?? 'secondary' ?>">
                                            <?= $estadoLabels[$boleta['estado']] ?? $boleta['estado'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $estadoActual = (string)($boleta['estado'] ?? '');
                                        $estadoNormalizado = strtolower($estadoActual);
                                        $esRechazada = str_contains($estadoNormalizado, 'rechazada') || str_contains($estadoNormalizado, 'rechazado');

                                        if ($esRechazada):
                                        ?>
                                            <span class="badge badge-danger">Rechazada</span>
                                        <?php elseif (!empty($boleta['instructor_aprobado_por'])): ?>
                                            <span class="badge badge-success">Aprobada</span>
                                        <?php elseif (!empty($boleta['instructor_rechazado_por'])): ?>
                                            <span class="badge badge-danger">Rechazada</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">-</span>
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
