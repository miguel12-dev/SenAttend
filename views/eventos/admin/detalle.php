<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($evento['titulo']) ?> - Gestión de Eventos | SENAttend</title>
    <link rel="stylesheet" href="<?= asset('css/eventos/admin.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="/eventos/admin" class="back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                </svg>
            </a>
            <span>Detalle del Evento</span>
        </div>
        <div class="navbar-user">
            <span class="user-name"><?= htmlspecialchars($user['nombre']) ?></span>
            <a href="/eventos/logout" class="btn-logout" title="Cerrar sesión">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                </svg>
            </a>
        </div>
    </nav>

    <main class="main-content">
        <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
            </svg>
            <span><?= htmlspecialchars($_GET['mensaje']) ?></span>
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
                        <div class="event-meta">
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/>
                                </svg>
                                <?= date('d/m/Y H:i', strtotime($evento['fecha_inicio'])) ?> - <?= date('d/m/Y H:i', strtotime($evento['fecha_fin'])) ?>
                            </span>
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                                </svg>
                                <?= ucfirst($evento['tipo_participantes']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="stats-grid detail-stats">
                <div class="stat-card">
                    <div class="stat-content">
                        <span class="stat-value"><?= $evento['estadisticas']['total'] ?? 0 ?></span>
                        <span class="stat-label">Total Registrados</span>
                    </div>
                </div>
                <div class="stat-card stat-active">
                    <div class="stat-content">
                        <span class="stat-value"><?= $evento['estadisticas']['ingresados'] ?? 0 ?></span>
                        <span class="stat-label">Ingresaron</span>
                    </div>
                </div>
                <div class="stat-card stat-finished">
                    <div class="stat-content">
                        <span class="stat-value"><?= $evento['estadisticas']['finalizados'] ?? 0 ?></span>
                        <span class="stat-label">Finalizaron</span>
                    </div>
                </div>
                <div class="stat-card stat-pending">
                    <div class="stat-content">
                        <span class="stat-value"><?= $evento['estadisticas']['ausentes'] ?? 0 ?></span>
                        <span class="stat-label">Ausentes</span>
                    </div>
                </div>
            </div>

            <!-- Descripción y acciones -->
            <div class="event-body">
                <div class="event-description">
                    <h3>Descripción</h3>
                    <p><?= $evento['descripcion'] ? nl2br(htmlspecialchars($evento['descripcion'])) : 'Sin descripción.' ?></p>
                </div>

                <div class="event-info">
                    <h3>Información</h3>
                    <ul>
                        <li>
                            <strong>Creado por:</strong>
                            <span><?= htmlspecialchars($evento['creador_nombre'] ?? 'N/A') ?></span>
                        </li>
                        <li>
                            <strong>Creado:</strong>
                            <span><?= date('d/m/Y H:i', strtotime($evento['created_at'])) ?></span>
                        </li>
                        <li>
                            <strong>Última actualización:</strong>
                            <span><?= date('d/m/Y H:i', strtotime($evento['updated_at'])) ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Acciones -->
            <div class="event-actions">
                <a href="/eventos/admin/<?= $evento['id'] ?>/participantes" class="btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3z"/>
                    </svg>
                    <span>Ver Participantes</span>
                </a>
                <a href="/eventos/admin/<?= $evento['id'] ?>/editar" class="btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"/>
                    </svg>
                    <span>Editar Evento</span>
                </a>
                
                <?php if ($evento['estado'] === 'programado'): ?>
                <button class="btn-success" onclick="cambiarEstado(<?= $evento['id'] ?>, 'en_curso')">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    <span>Iniciar Evento</span>
                </button>
                <?php elseif ($evento['estado'] === 'en_curso'): ?>
                <button class="btn-warning" onclick="cambiarEstado(<?= $evento['id'] ?>, 'finalizado')">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6 6h12v12H6z"/>
                    </svg>
                    <span>Finalizar Evento</span>
                </button>
                <?php endif; ?>
                
                <button class="btn-danger" onclick="confirmarEliminar(<?= $evento['id'] ?>)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                    </svg>
                    <span>Eliminar</span>
                </button>
            </div>
        </div>
    </main>

    <!-- Modal de confirmación -->
    <div id="confirmModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>¿Estás seguro?</h3>
            <p id="modalMessage"></p>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <button type="submit" class="btn-danger">Confirmar</button>
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
</body>
</html>

