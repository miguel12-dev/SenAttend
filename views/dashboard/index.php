<?php 
$title = 'Dashboard - SENAttend';
$styles = [
    'css/dashboard/dashboard.css',
    'css/dashboard-admin/dashboard.css'
];
$scripts = [
    'js/dashboard-admin/dashboard.js'
];

ob_start();
?>

<!-- Dashboard Content -->
<div class="wrapper">
    <?php 
    $currentPage = 'dashboard';
    require __DIR__ . '/../components/header.php'; 
    ?>

        <main class="main-content">
            <div class="container">
                <!-- Header del Dashboard -->
                <div class="dashboard-header">
                    <h2>
                        <i class="fas fa-<?= in_array($user['rol'], ['admin', 'administrativo']) ? 'cogs' : 'home' ?>"></i>
                        <?php
                        $titulos = [
                            'instructor' => 'Panel Principal',
                            'coordinador' => 'Panel Principal',
                            'admin' => 'Panel Administrativo',
                            'administrativo' => 'Panel Administrativo'
                        ];
                        echo $titulos[$user['rol']] ?? 'Panel Principal';
                        ?>
                    </h2>
                    <p class="subtitle">
                        <?php
                        $descripciones = [
                            'instructor' => 'Accede a las funciones principales para gestionar asistencias y fichas.',
                            'coordinador' => 'Accede a las funciones principales para gestionar asistencias y fichas.',
                            'admin' => 'Accede a las funciones administrativas principales del sistema.',
                            'administrativo' => 'Accede a las funciones administrativas principales del sistema.'
                        ];
                        echo $descripciones[$user['rol']] ?? 'Accede a las funciones principales del sistema.';
                        ?>
                    </p>
                </div>

                <!-- Acciones Rápidas -->
                <div class="actions-section">
                    <h3>Acciones Rápidas</h3>
                    
                    <div class="actions-grid-sena">
                        
                        <!-- Acciones para Instructor y Coordinador -->
                        <?php if (in_array($user['rol'], ['instructor', 'coordinador'])): ?>
                        

                        <!-- Escanear QR -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <h4>Escanear QR</h4>
                            <p>Registrar asistencia mediante código QR de aprendices.</p>
                            <div class="action-buttons">
                                <a href="/qr/escanear" class="btn-sena">
                                    <i class="fas fa-camera"></i>
                                    Escanear QR
                                </a>
                            </div>
                        </div>

                        <!-- Registro de Anomalías -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h4>Registro de Anomalías</h4>
                            <p>Registrar anomalías de asistencia por aprendiz o para la ficha en general.</p>
                            <div class="action-buttons">
                                <a href="/anomalias/registrar" class="btn-sena">
                                    <i class="fas fa-clipboard-list"></i>
                                    Registrar Anomalías
                                </a>
                            </div>
                        </div>

                        <!-- Exportar Reportes -->
                        <?php if ($user['rol'] === 'instructor'): ?>
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-file-excel"></i>
                            </div>
                            <h4>Exportar Reportes</h4>
                            <p>Generar reportes de asistencia en formato Excel para tus fichas.</p>
                            <div class="action-buttons">
                                <a href="/gestion-reportes" class="btn-sena">
                                    <i class="fas fa-file-export"></i>
                                    Exportar Reportes
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Boletas de Salida (Instructor) -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-file-signature"></i>
                            </div>
                            <h4>Boletas de Salida</h4>
                            <p>Revisar y aprobar solicitudes de boletas de salida de aprendices.</p>
                            <div class="action-buttons">
                                <a href="/instructor/boletas-salida" class="btn-sena">
                                    <i class="fas fa-tasks"></i>
                                    Revisar Solicitudes
                                </a>
                            </div>
                        </div>

                        <?php endif; ?>



                        <!-- Acciones para Admin y Administrativo -->
                        <?php if (in_array($user['rol'], ['admin', 'administrativo'])): ?>
                        
                        <!-- Gestión de Fichas -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-folder-open"></i>
                            </div>
                            <h4>Gestión de Fichas</h4>
                            <p>Administrar, crear y deshabilitar fichas.</p>
                            <div class="action-buttons">
                                <a href="/fichas/crear" class="btn-sena">
                                    <i class="fas fa-plus"></i>
                                    Crear Ficha
                                </a>
                                <a href="/fichas" class="btn-sena">
                                    <i class="fas fa-list"></i>
                                    Administrar Fichas
                                </a>
                            </div>
                        </div>

                        <!-- Gestionar Aprendices -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h4>Gestionar Aprendices</h4>
                            <p>Centraliza la administración de aprendices.</p>
                            <div class="action-buttons">
                                <a href="/aprendices" class="btn-sena">
                                    <i class="fas fa-users-cog"></i>
                                    Administrar Aprendices
                                </a>
                            </div>
                        </div>

                        <!-- Gestión de Instructores -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <h4>Gestión de Instructores</h4>
                            <p>Administrar instructores del sistema.</p>
                            <div class="action-buttons">
                                <a href="/gestion-instructores/crear" class="btn-sena">
                                    <i class="fas fa-plus"></i>
                                    Crear Instructor
                                </a>
                                <a href="/gestion-instructores" class="btn-sena">
                                    <i class="fas fa-list"></i>
                                    Administrar Instructores
                                </a>
                            </div>
                        </div>

                        <!-- Asignación de Fichas -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h4>Asignación de Fichas</h4>
                            <p>Asignar instructores a fichas de formación.</p>
                            <div class="action-buttons">
                                <a href="/instructor-fichas" class="btn-sena">
                                    <i class="fas fa-link"></i>
                                    Gestionar Asignaciones
                                </a>
                            </div>
                        </div>

                        <!-- Gestión de Porteros (Admin y Administrativo) -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <h4>Gestión de Porteros</h4>
                            <p>Administrar porteros del sistema.</p>
                            <div class="action-buttons">
                                <a href="/gestion-porteros" class="btn-sena">
                                    <i class="fas fa-list"></i>
                                    Administrar Porteros
                                </a>
                                <a href="/gestion-porteros/exportar-csv" class="btn-sena">
                                    <i class="fas fa-file-export"></i>
                                    Exportar CSV
                                </a>
                            </div>
                        </div>

                        <!-- Analítica y Reportes (Admin y Administrativo) -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h4>Analítica y Reportes</h4>
                            <p>Generar reportes estadísticos de asistencia semanales y mensuales.</p>
                            <div class="action-buttons">
                                <a href="/analytics" class="btn-sena">
                                    <i class="fas fa-file-excel"></i>
                                    Ver Analítica
                                </a>
                            </div>
                        </div>

                        <!-- Reporte de Equipos (Admin y Administrativo) -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <h4>Reporte de Equipos</h4>
                            <p>Consultar y exportar registros de ingresos y salidas de equipos por rango de fechas.</p>
                            <div class="action-buttons">
                                <a href="/reportes-equipos" class="btn-sena">
                                    <i class="fas fa-file-export"></i>
                                    Ver Reporte
                                </a>
                            </div>
                        </div>

                        <!-- Seguimiento Infracciones Equipos (Admin y Administrativo) -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-user-times"></i>
                            </div>
                            <h4>Infracciones de Equipos</h4>
                            <p>Seguimiento de aprendices con salidas no registradas y cierres automáticos.</p>
                            <div class="action-buttons">
                                <a href="/admin/seguimiento-equipos" class="btn-sena">
                                    <i class="fas fa-search"></i>
                                    Monitorear
                                </a>
                            </div>
                        </div>

                        <!-- Boletas de Salida (Admin) -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h4>Boletas de Salida</h4>
                            <p>Aprobar solicitudes de boletas de salida ya revisadas por instructores.</p>
                            <div class="action-buttons">
                                <a href="/admin/boletas-salida" class="btn-sena">
                                    <i class="fas fa-check-double"></i>
                                    Gestionar Boletas
                                </a>
                            </div>
                        </div>

                        <?php endif; ?>

                        <!-- Acciones solo para Admin -->
                        <?php if ($user['rol'] === 'admin'): ?>

                        <!-- Configurar Horarios de Asistencia -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h4>Configurar Horarios</h4>
                            <p>Gestionar turnos y límites de llegada.</p>
                            <div class="action-buttons">
                                <a href="/configuracion/horarios" class="btn-sena">
                                    <i class="fas fa-cog"></i>
                                    Configurar Turnos
                                </a>
                            </div>
                        </div>

                        <!-- Configurar Horarios de Equipos (Solo Admin) -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-laptop-code"></i>
                            </div>
                            <h4>Horarios de Equipos</h4>
                            <p>Configurar turnos Mañana / Tarde / Noche usados en el reporte de equipos.</p>
                            <div class="action-buttons">
                                <a href="/configuracion/turnos-equipos" class="btn-sena">
                                    <i class="fas fa-sliders-h"></i>
                                    Gestionar Horarios
                                </a>
                            </div>
                        </div>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/pwa-base.php';
?>
