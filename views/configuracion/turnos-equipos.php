<?php
/**
 * Vista: Configuración de Horarios de Turnos de Equipos
 * Solo accesible para el rol Admin.
 */

if (!isset($user) || $user['rol'] !== 'admin') {
    header('Location: /dashboard');
    exit;
}

$title       = 'Horarios de Equipos - SENAttend';
$currentPage = 'configuracion';
$showHeader  = true;
$additionalStyles = '';
$additionalScripts = '';

ob_start();
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" style="margin-bottom:1.5rem;">
        <ol class="breadcrumb" style="background:#f8f9fa;padding:.75rem 1rem;border-radius:.375rem;">
            <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Horarios de Equipos</li>
        </ol>
    </nav>

    <!-- Alertas -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <ul style="margin:0;padding-left:1.5rem;">
                <?php foreach ($_SESSION['errors'] as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>

    <!-- ── SECCIÓN 1: Horarios Globales ─────────────────────────────────── -->
    <div class="card" style="box-shadow:0 2px 4px rgba(0,0,0,.1);border-radius:8px;margin-bottom:2rem;">
        <div class="card-header" style="background:var(--color-primary);color:white;padding:1rem 1.5rem;border-radius:8px 8px 0 0;">
            <h5 style="margin:0;"><i class="fas fa-cog"></i> Horarios Globales de Turnos</h5>
        </div>
        <div class="card-body" style="padding:1.5rem;">
            <div class="alert alert-info" style="margin-bottom:1.5rem;">
                <i class="fas fa-info-circle"></i>
                <strong>Nota:</strong> Estos horarios se aplican a <em>todos</em> los días que no tengan una excepción específica registrada. Los cambios afectan únicamente los reportes de equipos, <strong>no</strong> los registros de asistencia de aprendices.
            </div>

            <form method="POST" action="/configuracion/turnos-equipos/actualizar-globales" id="formGlobales" novalidate>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:20%;">Turno</th>
                                <th style="width:35%;">Hora Inicio</th>
                                <th style="width:35%;">Hora Fin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $iconos  = ['Mañana' => 'fa-sun',       'Tarde' => 'fa-cloud-sun', 'Noche' => 'fa-moon'];
                            $colores = ['Mañana' => 'badge-warning', 'Tarde' => 'badge-info',   'Noche' => 'badge-dark'];

                            // Indexar por turno para acceso fácil
                            $globalPorTurno = [];
                            foreach ($turnosGlobales as $tg) {
                                $globalPorTurno[$tg['turno']] = $tg;
                            }

                            foreach (['Mañana', 'Tarde', 'Noche'] as $nombre):
                                $clave = strtolower(str_replace('ñ', 'n', $nombre));
                                $t     = $globalPorTurno[$nombre] ?? ['hora_inicio' => '', 'hora_fin' => ''];
                            ?>
                            <tr>
                                <td>
                                    <span class="badge <?= $colores[$nombre] ?>" style="font-size:.95rem;padding:.45rem .75rem;">
                                        <i class="fas <?= $iconos[$nombre] ?>"></i> <?= $nombre ?>
                                    </span>
                                </td>
                                <td>
                                    <input type="time" class="form-control"
                                           id="<?= $clave ?>_inicio"
                                           name="<?= $clave ?>_inicio"
                                           value="<?= htmlspecialchars(substr($t['hora_inicio'], 0, 5)) ?>"
                                           step="60" required>
                                </td>
                                <td>
                                    <input type="time" class="form-control"
                                           id="<?= $clave ?>_fin"
                                           name="<?= $clave ?>_fin"
                                           value="<?= htmlspecialchars(substr($t['hora_fin'], 0, 5)) ?>"
                                           step="60" required>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.5rem;">
                    <a href="/dashboard" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver al Dashboard
                    </a>
                    <button type="submit" class="btn btn-primary" id="btnGuardarGlobales">
                        <i class="fas fa-save"></i> Guardar Horarios Globales
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── SECCIÓN 2: Excepciones por Fecha ─────────────────────────────── -->
    <div class="card" style="box-shadow:0 2px 4px rgba(0,0,0,.1);border-radius:8px;margin-bottom:2rem;">
        <div class="card-header" style="background:#495057;color:white;padding:1rem 1.5rem;border-radius:8px 8px 0 0;">
            <h5 style="margin:0;"><i class="fas fa-calendar-alt"></i> Excepciones por Fecha Específica</h5>
        </div>
        <div class="card-body" style="padding:1.5rem;">
            <p style="color:#6c757d;margin-bottom:1.5rem;">
                Registra horarios especiales para fechas puntuales (festivos, eventos, etc.).
                El reporte usará estos horarios <em>solo</em> para el día indicado.
            </p>

            <!-- Formulario para agregar excepción -->
            <form method="POST" action="/configuracion/turnos-equipos/agregar-fecha"
                  id="formAgregarFecha" style="background:#f8f9fa;padding:1.25rem;border-radius:6px;margin-bottom:1.5rem;">
                <h6 style="margin-bottom:1rem;"><i class="fas fa-plus-circle"></i> Agregar excepción</h6>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;align-items:end;">
                    <div>
                        <label for="exc_fecha" style="font-weight:600;">Fecha</label>
                        <input type="date" class="form-control" id="exc_fecha" name="exc_fecha"
                               min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div>
                        <label for="exc_turno" style="font-weight:600;">Turno</label>
                        <select class="form-control" id="exc_turno" name="exc_turno" required>
                            <option value="">-- Seleccionar --</option>
                            <option value="Mañana">🌅 Mañana</option>
                            <option value="Tarde">🌤 Tarde</option>
                            <option value="Noche">🌙 Noche</option>
                        </select>
                    </div>
                    <div>
                        <label for="exc_inicio" style="font-weight:600;">Hora Inicio</label>
                        <input type="time" class="form-control" id="exc_inicio" name="exc_inicio" step="60" required>
                    </div>
                    <div>
                        <label for="exc_fin" style="font-weight:600;">Hora Fin</label>
                        <input type="time" class="form-control" id="exc_fin" name="exc_fin" step="60" required>
                    </div>
                    <div>
                        <label for="exc_descripcion" style="font-weight:600;">Descripción (opcional)</label>
                        <input type="text" class="form-control" id="exc_descripcion"
                               name="exc_descripcion" maxlength="200" placeholder="Ej: Día festivo">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="width:100%;">
                            <i class="fas fa-plus"></i> Agregar
                        </button>
                    </div>
                </div>
            </form>

            <!-- Tabla de excepciones existentes -->
            <?php if (empty($excepcionesAgrupadas)): ?>
                <p style="color:#6c757d;text-align:center;padding:1.5rem;">
                    <i class="fas fa-calendar-times"></i> No hay excepciones registradas.
                </p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Turno</th>
                                <th>Hora Inicio</th>
                                <th>Hora Fin</th>
                                <th>Descripción</th>
                                <th style="width:80px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($excepcionesAgrupadas as $fecha => $excepciones): ?>
                                <?php foreach ($excepciones as $i => $exc): ?>
                                <tr>
                                    <?php if ($i === 0): ?>
                                    <td rowspan="<?= count($excepciones) ?>"
                                        style="font-weight:700;vertical-align:middle;background:#f1f3f5;">
                                        <i class="fas fa-calendar-day"></i>
                                        <?= date('d/m/Y', strtotime($fecha)) ?>
                                    </td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="badge <?= $colores[$exc['turno']] ?? '' ?>" style="padding:.35rem .65rem;">
                                            <i class="fas <?= $iconos[$exc['turno']] ?? 'fa-clock' ?>"></i>
                                            <?= htmlspecialchars($exc['turno']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(substr($exc['hora_inicio'], 0, 5)) ?></td>
                                    <td><?= htmlspecialchars(substr($exc['hora_fin'], 0, 5)) ?></td>
                                    <td><?= htmlspecialchars($exc['descripcion'] ?? '-') ?></td>
                                    <td>
                                        <form method="POST" action="/configuracion/turnos-equipos/eliminar-fecha"
                                              onsubmit="return confirm('¿Eliminar esta excepción?');">
                                            <input type="hidden" name="exc_id" value="<?= (int)$exc['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                    title="Eliminar" style="padding:.25rem .6rem;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ayuda -->
    <div class="card" style="box-shadow:0 2px 4px rgba(0,0,0,.1);border-radius:8px;">
        <div class="card-header" style="background:#f8f9fa;padding:1rem 1.5rem;border-radius:8px 8px 0 0;">
            <h6 style="margin:0;"><i class="fas fa-question-circle"></i> Ayuda</h6>
        </div>
        <div class="card-body" style="padding:1.5rem;">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.5rem;">
                <div>
                    <h6 style="color:var(--color-primary);"><i class="fas fa-globe"></i> Horarios Globales</h6>
                    <p class="text-muted" style="font-size:.9rem;">
                        Son los horarios base. Se aplican en cualquier día que no tenga una excepción definida.
                    </p>
                </div>
                <div>
                    <h6 style="color:var(--color-primary);"><i class="fas fa-calendar-alt"></i> Excepciones</h6>
                    <p class="text-muted" style="font-size:.9rem;">
                        Permiten definir horarios distintos para un día puntual. Si un día tiene excepción, esa tiene prioridad sobre los globales.
                    </p>
                </div>
                <div>
                    <h6 style="color:var(--color-primary);"><i class="fas fa-file-excel"></i> Efecto en Reportes</h6>
                    <p class="text-muted" style="font-size:.9rem;">
                        Los reportes de equipos usan estos horarios para clasificar cada ingreso por turno (Mañana / Tarde / Noche).
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // Validar horarios globales antes de enviar
    document.getElementById('formGlobales').addEventListener('submit', function (e) {
        const pares = [
            ['manana_inicio', 'manana_fin', 'Mañana'],
            ['tarde_inicio',  'tarde_fin',  'Tarde'],
            ['noche_inicio',  'noche_fin',  'Noche'],
        ];
        for (const [ini, fin, nombre] of pares) {
            const vi = document.getElementById(ini).value;
            const vf = document.getElementById(fin).value;
            if (vi && vf && vi >= vf) {
                alert(`Turno ${nombre}: La hora de inicio debe ser anterior a la hora de fin.`);
                e.preventDefault();
                return;
            }
        }
    });

    // Validar excepción de fecha
    document.getElementById('formAgregarFecha').addEventListener('submit', function (e) {
        const ini = document.getElementById('exc_inicio').value;
        const fin = document.getElementById('exc_fin').value;
        if (ini && fin && ini >= fin) {
            alert('La hora de inicio debe ser anterior a la hora de fin.');
            e.preventDefault();
        }
    });
})();
</script>

<style>
.breadcrumb { list-style:none; display:flex; gap:.5rem; }
.breadcrumb-item + .breadcrumb-item::before { content:"/"; padding-right:.5rem; color:#6c757d; }
.breadcrumb-item.active { color:#6c757d; }
.card { background:white; border:1px solid #dee2e6; }
.card-header { border-bottom:1px solid #dee2e6; }
.text-muted { color:#6c757d; }
.badge-warning { background:#ffc107; color:#000; }
.badge-info    { background:#17a2b8; color:#fff; }
.badge-dark    { background:#343a40; color:#fff; }
.btn-danger    { background:#dc3545; color:#fff; border:none; border-radius:4px; cursor:pointer; }
.btn-danger:hover { background:#c82333; }
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
