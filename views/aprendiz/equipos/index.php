<?php
/** @var array $user */
/** @var array $equipos */
/** @var array $equiposEliminados */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Equipos - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/aprendiz/panel.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/aprendiz/equipos/index.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'aprendiz-equipos';
        require __DIR__ . '/../../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <!-- Header -->
                <div class="dashboard-header">
                    <div>
                        <h2>
                            <i class="fas fa-laptop"></i>
                            Mis Equipos
                        </h2>
                        <p class="subtitle">
                            Gestiona todos tus equipos registrados y accede a sus códigos QR.
                        </p>
                    </div>
                    <div>
                        <?php 
                        $url = '/aprendiz/panel';
                        require __DIR__ . '/../../components/back-button.php'; 
                        ?>
                    </div>
                </div>

                <!-- Mensajes -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <!-- Lista de Equipos -->
                <?php if (!empty($equipos) || !empty($equiposEliminados)): ?>
                <section class="aprendiz-equipos-card">
                    <div class="aprendiz-equipos-header">
                        <h2><i class="fas fa-list"></i> Equipos Registrados (<?= count($equipos) ?>)</h2>
                    </div>
                    <div class="aprendiz-equipos-list">
                        <div class="equipos-grid">
                            <?php foreach ($equipos as $index => $equipo): ?>
                                <div class="equipo-card" data-animate-delay="<?= $index * 0.1 ?>">
                                    <div class="equipo-imagen">
                                        <?php if (!empty($equipo['imagen'])): ?>
                                            <img src="<?= asset(htmlspecialchars($equipo['imagen'], ENT_QUOTES, 'UTF-8')) ?>" alt="<?= htmlspecialchars($equipo['marca'] ?? 'Equipo', ENT_QUOTES, 'UTF-8') ?>">
                                        <?php else: ?>
                                            <div class="equipo-imagen-placeholder">
                                                <i class="fas fa-laptop"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="equipo-info">
                                        <h3><?= htmlspecialchars($equipo['marca'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
                                        <div class="equipo-details">
                                            <p><strong>Serial:</strong> <code><?= htmlspecialchars($equipo['numero_serial'] ?? '', ENT_QUOTES, 'UTF-8') ?></code></p>
                                            <p><strong>Estado:</strong> 
                                                <span class="badge-<?= ($equipo['estado'] ?? '') === 'activo' ? 'activo' : 'inactivo' ?>">
                                                    <?= htmlspecialchars(ucfirst($equipo['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </p>
                                            <?php if (!empty($equipo['fecha_asignacion'])): ?>
                                                <p><strong>Registrado:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($equipo['fecha_asignacion'])), ENT_QUOTES, 'UTF-8') ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="equipo-actions">
                                            <a href="/aprendiz/equipos/<?= (int)($equipo['equipo_id'] ?? 0) ?>/qr" class="btn btn-primary btn-qr">
                                                <i class="fas fa-qrcode"></i> Ver QR
                                            </a>
                                            <button type="button" class="btn-delete-icon" 
                                                data-id="<?= (int)($equipo['relacion_id'] ?? 0) ?>" 
                                                data-marca="<?= htmlspecialchars($equipo['marca'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                                data-serial="<?= htmlspecialchars($equipo['numero_serial'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                title="Eliminar equipo">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Equipos eliminados que pueden ser restaurados -->
                    <?php if (!empty($equiposEliminados)): ?>
                    <div class="equipos-eliminados-section">
                        <div class="equipos-eliminados-header" id="eliminadosHeader">
                            <h3><i class="fas fa-history"></i> Equipos eliminados (<?= count($equiposEliminados) ?>)</h3>
                            <i class="fas fa-chevron-down toggle-icon" id="toggleIcon"></i>
                        </div>
                        <div class="equipos-eliminados-list" id="eliminadosList">
                            <div class="equipos-grid">
                                <?php foreach ($equiposEliminados as $equipo): ?>
                                    <div class="equipo-card eliminado">
                                        <div class="equipo-imagen">
                                            <?php if (!empty($equipo['imagen'])): ?>
                                                <img src="<?= asset(htmlspecialchars($equipo['imagen'], ENT_QUOTES, 'UTF-8')) ?>" alt="<?= htmlspecialchars($equipo['marca'] ?? 'Equipo', ENT_QUOTES, 'UTF-8') ?>">
                                            <?php else: ?>
                                                <div class="equipo-imagen-placeholder">
                                                    <i class="fas fa-laptop"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="equipo-info">
                                            <h3><?= htmlspecialchars($equipo['marca'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
                                            <div class="equipo-details">
                                                <p><strong>Serial:</strong> <code><?= htmlspecialchars($equipo['numero_serial'] ?? '', ENT_QUOTES, 'UTF-8') ?></code></p>
                                                <p><strong>Eliminado:</strong> 
                                                    <span class="badge-eliminado">
                                                        <?= htmlspecialchars(!empty($equipo['fecha_eliminacion']) ? date('d/m/Y', strtotime($equipo['fecha_eliminacion'])) : '', ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="equipo-actions">
                                                <form action="/aprendiz/equipos/<?= (int)($equipo['relacion_id'] ?? 0) ?>/restaurar" method="POST">
                                                    <button type="submit" class="btn-restore">
                                                        <i class="fas fa-undo"></i> Volver a agregar
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </section>
                <?php else: ?>
                <section class="aprendiz-equipos-card">
                    <div class="aprendiz-equipos-header">
                        <h2><i class="fas fa-laptop"></i> Mis Equipos</h2>
                    </div>
                    <div class="empty-state">
                        <i class="fas fa-laptop empty-state-icon"></i>
                        <p class="empty-state-text">No tienes equipos registrados aún.</p>
                        <a href="/aprendiz/equipos/crear" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Registrar mi primer equipo
                        </a>
                    </div>
                </section>
                <?php endif; ?>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="modal-title">¿Eliminar equipo?</h3>
            <p class="modal-message">
                ¿Estás seguro de que deseas eliminar el equipo<br>
                <strong id="modalMarca"></strong><br>
                <code id="modalSerial"></code>?
            </p>
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" id="btnCancelDelete">
                    Cancelar
                </button>
                <button type="button" class="btn-modal-confirm" id="btnConfirmDelete">
                    Sí, eliminar
                </button>
            </div>
        </div>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle equipos eliminados
            const eliminadosHeader = document.getElementById('eliminadosHeader');
            const eliminadosList = document.getElementById('eliminadosList');
            const toggleIcon = document.getElementById('toggleIcon');

            if (eliminadosHeader && eliminadosList) {
                eliminadosHeader.addEventListener('click', function() {
                    eliminadosList.classList.toggle('show');
                    if (toggleIcon) {
                        toggleIcon.classList.toggle('rotated');
                    }
                });
            }

            // Modal de confirmación de eliminación
            const deleteModal = document.getElementById('deleteModal');
            const modalMarca = document.getElementById('modalMarca');
            const modalSerial = document.getElementById('modalSerial');
            const btnCancelDelete = document.getElementById('btnCancelDelete');
            const btnConfirmDelete = document.getElementById('btnConfirmDelete');
            let currentDeleteId = null;

            // Abrir modal al hacer clic en el botón de eliminar
            document.querySelectorAll('.btn-delete-icon').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    currentDeleteId = this.getAttribute('data-id');
                    const marca = this.getAttribute('data-marca');
                    const serial = this.getAttribute('data-serial');
                    
                    modalMarca.textContent = marca;
                    modalSerial.textContent = serial;
                    deleteModal.classList.add('show');
                });
            });

            // Cerrar modal
            function closeDeleteModal() {
                deleteModal.classList.remove('show');
                currentDeleteId = null;
            }

            if (btnCancelDelete) {
                btnCancelDelete.addEventListener('click', closeDeleteModal);
            }

            // Confirmar eliminación
            if (btnConfirmDelete) {
                btnConfirmDelete.addEventListener('click', function() {
                    if (currentDeleteId) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/aprendiz/equipos/' + currentDeleteId + '/eliminar';
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }

            // Cerrar modal al hacer clic fuera
            deleteModal.addEventListener('click', function(e) {
                if (e.target === deleteModal) {
                    closeDeleteModal();
                }
            });

            // Cerrar modal con tecla Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && deleteModal.classList.contains('show')) {
                    closeDeleteModal();
                }
            });

            // Aplicar animation-delay desde data attribute
            document.querySelectorAll('.equipo-card[data-animate-delay]').forEach(function(card) {
                const delay = card.getAttribute('data-animate-delay');
                card.style.animationDelay = delay + 's';
            });
        });
    </script>
</body>
</html>