<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($evento['titulo']) ?> - Gestión de Eventos</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/eventos/admin.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'eventos-dashboard';
        require __DIR__ . '/../../components/header-eventos.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <div class="page-header">
                    <a href="/eventos/admin" class="btn-back">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                </div>
                <?php if (isset($_GET['mensaje'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($_GET['mensaje']) ?>
                </div>
                <?php endif; ?>

                <div class="event-detail">
                    <!-- Header con imagen -->
                    <div class="event-header" <?php if ($evento['imagen_url']): ?>style="background-image: url('<?= htmlspecialchars($evento['imagen_url']) ?>')"<?php endif; ?>>
                        <div class="event-header-overlay">
                            <div class="event-header-content">
                                <span class="status-badge status-<?= $evento['estado'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $evento['estado'])) ?>
                                </span>
                                <h1><?= htmlspecialchars($evento['titulo']) ?></h1>
                                <div class="event-meta-header">
                                    <span>
                                        <i class="fas fa-calendar"></i>
                                        <?= date('d/m/Y H:i', strtotime($evento['fecha_inicio'])) ?> - <?= date('d/m/Y H:i', strtotime($evento['fecha_fin'])) ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-users"></i>
                                        <?= ucfirst($evento['tipo_participantes']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="stats-grid detail-stats">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?= $evento['estadisticas']['total'] ?? 0 ?></span>
                                <span class="stat-label">Total Registrados</span>
                            </div>
                        </div>
                        <div class="stat-card stat-active">
                            <div class="stat-icon">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?= $evento['estadisticas']['ingresados'] ?? 0 ?></span>
                                <span class="stat-label">Ingresaron</span>
                            </div>
                        </div>
                        <div class="stat-card stat-finished">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?= $evento['estadisticas']['finalizados'] ?? 0 ?></span>
                                <span class="stat-label">Finalizaron</span>
                            </div>
                        </div>
                        <div class="stat-card stat-pending">
                            <div class="stat-icon">
                                <i class="fas fa-user-times"></i>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?= $evento['estadisticas']['ausentes'] ?? 0 ?></span>
                                <span class="stat-label">Ausentes</span>
                            </div>
                        </div>
                    </div>

                    <!-- Descripción y acciones -->
                    <div class="event-body">
                        <div class="event-description">
                            <h3><i class="fas fa-info-circle"></i> Descripción</h3>
                            <p><?= $evento['descripcion'] ? nl2br(htmlspecialchars($evento['descripcion'])) : 'Sin descripción.' ?></p>
                        </div>

                        <div class="event-info">
                            <h3><i class="fas fa-clipboard-list"></i> Información</h3>
                            <ul>
                                <li>
                                    <i class="fas fa-user"></i>
                                    <strong>Creado por:</strong>
                                    <span><?= htmlspecialchars($evento['creador_nombre'] ?? 'N/A') ?></span>
                                </li>
                                <li>
                                    <i class="fas fa-calendar-plus"></i>
                                    <strong>Creado:</strong>
                                    <span><?= date('d/m/Y H:i', strtotime($evento['created_at'])) ?></span>
                                </li>
                                <li>
                                    <i class="fas fa-clock"></i>
                                    <strong>Última actualización:</strong>
                                    <span><?= date('d/m/Y H:i', strtotime($evento['updated_at'])) ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="event-actions">
                        <a href="/eventos/admin/<?= $evento['id'] ?>/participantes" class="btn btn-primary">
                            <i class="fas fa-users"></i>
                            Ver Participantes
                        </a>
                        <a href="/eventos/qr/scanner/<?= $evento['id'] ?>" class="btn btn-primary" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                            <i class="fas fa-qrcode"></i>
                            Escanear QR del Evento
                        </a>
                        <a href="/eventos/admin/<?= $evento['id'] ?>/editar" class="btn btn-secondary">
                            <i class="fas fa-edit"></i>
                            Editar Evento
                        </a>
                        
                        <?php if ($evento['estado'] === 'programado'): ?>
                        <button class="btn btn-success" onclick="cambiarEstado(<?= $evento['id'] ?>, 'en_curso')">
                            <i class="fas fa-play"></i>
                            Iniciar Evento
                        </button>
                        <?php elseif ($evento['estado'] === 'en_curso'): ?>
                        <button class="btn btn-warning" onclick="cambiarEstado(<?= $evento['id'] ?>, 'finalizado')">
                            <i class="fas fa-stop"></i>
                            Finalizar Evento
                        </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-danger" onclick="confirmarEliminar(<?= $evento['id'] ?>)">
                            <i class="fas fa-trash"></i>
                            Eliminar
                        </button>
                    </div>
                </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
    </div>

    <!-- Modal de confirmación -->
    <div id="confirmModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>¿Estás seguro?</h3>
            <p id="modalMessage"></p>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <button type="submit" class="btn btn-primary" style="background: var(--color-danger);">Confirmar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function cambiarEstado(eventoId, estado) {
            if (!confirm('¿Estás seguro de cambiar el estado del evento?')) {
                return;
            }

            fetch(`/eventos/admin/${eventoId}/estado`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ estado: estado })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Error al cambiar el estado');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            });
        }

        function confirmarEliminar(eventoId) {
            document.getElementById('modalMessage').textContent = '¿Deseas eliminar este evento? Esta acción no se puede deshacer.';
            document.getElementById('deleteForm').action = `/eventos/admin/${eventoId}/eliminar`;
            document.getElementById('confirmModal').style.display = 'flex';
        }

        function cerrarModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }
    </script>

    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>

