<?php
/** @var array $aprendiz */
/** @var array $instructoresFicha */
/** @var array $boletas */
/** @var int $page */
/** @var int $totalPages */
/** @var string|null $success */
/** @var string|null $error */
/** @var array $errors */

$title = 'Boletas de Salida - SENAttend';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/notification-modal.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/boletas-salida/boletas.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'aprendiz-boletas';
        require __DIR__ . '/../../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <div class="dashboard-header">
                    <h2><i class="fas fa-file-export"></i> Boletas de Salida</h2>
                    <p class="subtitle">Solicita permisos de salida temporal o definitiva del CTA</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Formulario de Solicitud -->
                <section class="boleta-form-card">
                    <div class="card-header">
                        <h3><i class="fas fa-plus-circle"></i> Nueva Solicitud de Salida</h3>
                    </div>
                    <div class="card-body">
                        <form action="/aprendiz/boletas-salida" method="POST" id="boletaForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="tipo_salida" class="required">Tipo de Salida</label>
                                    <div class="radio-group">
                                        <label class="radio-label">
                                            <input type="radio" name="tipo_salida" value="temporal" required checked>
                                            <span>Temporal</span>
                                        </label>
                                        <label class="radio-label">
                                            <input type="radio" name="tipo_salida" value="definitiva" required>
                                            <span>Definitiva</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="motivo" class="required">Motivo de la Salida</label>
                                    <select name="motivo" id="motivo" class="form-control" required>
                                        <option value="">Seleccione un motivo...</option>
                                        <option value="cita_medica">Cita / Incapacidad médica</option>
                                        <option value="diligencias_electorales">Diligencias electorales / Gubernamentales</option>
                                        <option value="tramites_etapa_productiva">Trámites etapa productiva</option>
                                        <option value="requerimientos_laborales">Requerimientos laborales</option>
                                        <option value="caso_fortuito">Casos fortuitos / Fuerza mayor</option>
                                        <option value="representacion_sena">Representación SENA (Académica, Cultural, Deportiva)</option>
                                        <option value="diligencias_judiciales">Diligencias judiciales</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row" id="motivoOtroRow" style="display: none;">
                                <div class="form-group">
                                    <label for="motivo_otro" class="required">Especifique el motivo</label>
                                    <textarea name="motivo_otro" id="motivo_otro" class="form-control" rows="3" placeholder="Describa el motivo de su salida..."></textarea>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="hora_salida_solicitada" class="required">Hora de Salida Solicitada</label>
                                    <div class="time-input-group">
                                        <input type="number" id="hora_salida_hora" class="form-control time-input" min="1" max="12" placeholder="HH" required>
                                        <span class="time-separator">:</span>
                                        <input type="number" id="hora_salida_minuto" class="form-control time-input" min="0" max="59" placeholder="MM" required>
                                        <select id="hora_salida_periodo" class="form-control time-period" required>
                                            <option value="AM">AM</option>
                                            <option value="PM">PM</option>
                                        </select>
                                        <input type="hidden" name="hora_salida_solicitada" id="hora_salida_solicitada">
                                    </div>
                                    <small class="form-hint">
                                        <i class="fas fa-clock"></i> Hora actual: <strong id="hora-actual">--:--</strong> (mínimo 5 minutos después)
                                    </small>
                                </div>

                                <div class="form-group" id="horaReingresoGroup">
                                    <label for="hora_reingreso_solicitada" class="required">Hora de Reingreso Solicitada</label>
                                    <div class="time-input-group">
                                        <input type="number" id="hora_reingreso_hora" class="form-control time-input" min="1" max="12" placeholder="HH">
                                        <span class="time-separator">:</span>
                                        <input type="number" id="hora_reingreso_minuto" class="form-control time-input" min="0" max="59" placeholder="MM">
                                        <select id="hora_reingreso_periodo" class="form-control time-period">
                                            <option value="AM">AM</option>
                                            <option value="PM">PM</option>
                                        </select>
                                        <input type="hidden" name="hora_reingreso_solicitada" id="hora_reingreso_solicitada">
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="instructor_id" class="required">Instructor para Aprobación</label>
                                    <select name="instructor_id" id="instructor_id" class="form-control" required>
                                        <option value="">Seleccione un instructor...</option>
                                        <?php if (!empty($instructoresFicha)): ?>
                                            <optgroup label="Instructores de tu ficha">
                                                <?php foreach ($instructoresFicha as $instructor): ?>
                                                    <option value="<?= (int)$instructor['instructor_id'] ?>">
                                                        <?= htmlspecialchars($instructor['instructor_nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                    </select>
                                    <small class="form-hint">O busca otro instructor:</small>
                                    <input type="text" id="instructor_search" class="form-control" placeholder="Buscar instructor por nombre...">
                                    <div id="instructor_results" class="autocomplete-results"></div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Enviar Solicitud
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- Historial de Boletas -->
                <section class="boleta-historial-card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Mi Historial de Boletas</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($boletas)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No tienes boletas de salida registradas</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="boletas-table">
                                    <thead>
                                        <tr>
                                            <th>Fecha Solicitud</th>
                                            <th>Tipo</th>
                                            <th>Motivo</th>
                                            <th>Instructor</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($boletas as $boleta): ?>
                                            <tr>
                                                <td><?= date('d/m/Y H:i', strtotime($boleta['created_at'])) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $boleta['tipo_salida'] ?>">
                                                        <?= ucfirst($boleta['tipo_salida']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $boleta['motivo']))) ?></td>
                                                <td><?= htmlspecialchars($boleta['instructor_nombre']) ?></td>
                                                <td>
                                                    <?php
                                                    $estadoClass = [
                                                        'pendiente_instructor' => 'warning',
                                                        'pendiente_admin' => 'info',
                                                        'aprobada' => 'success',
                                                        'validada_porteria' => 'primary',
                                                        'completada' => 'secondary',
                                                        'rechazada_instructor' => 'danger',
                                                        'rechazada_admin' => 'danger',
                                                    ];
                                                    $estadoLabel = [
                                                        'pendiente_instructor' => 'Pendiente Instructor',
                                                        'pendiente_admin' => 'Pendiente Admin',
                                                        'aprobada' => 'Aprobada',
                                                        'validada_porteria' => 'En salida',
                                                        'completada' => 'Completada',
                                                        'rechazada_instructor' => 'Rechazada',
                                                        'rechazada_admin' => 'Rechazada',
                                                    ];
                                                    ?>
                                                    <span class="badge badge-<?= $estadoClass[$boleta['estado']] ?? 'default' ?>">
                                                        <?= $estadoLabel[$boleta['estado']] ?? $boleta['estado'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-secondary" onclick="verDetalle(<?= $boleta['id'] ?>)">
                                                        <i class="fas fa-eye"></i> Ver
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($totalPages > 1): ?>
                                <div class="pagination">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
    </div>

    <script>
        // Variable global con el ficha_id del aprendiz
        window.FICHA_ID = <?= (int)$aprendiz['ficha_id'] ?>;
    </script>
    <script src="<?= asset('js/common/notification-modal.js') ?>"></script>
    <script src="<?= asset('js/boletas-salida/aprendiz-form.js') ?>"></script>
</body>
</html>
