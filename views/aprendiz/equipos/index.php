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
                                            <a href="/aprendiz/equipos/<?= (int)($equipo['equipo_id'] ?? 0) ?>/editar" class="btn-edit-icon" 
                                                title="Editar equipo">
                                                <i class="fas fa-edit"></i>
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
    <script src="<?= asset('js/common/notification-modal.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check for created param from redirect (AJAX success)
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('created')) {
                // Clear URL param without reload
                window.history.replaceState({}, '', '/aprendiz/equipos');
                
                // Show success notification
                if (window.showSuccess) {
                    window.showSuccess('Equipo registrado correctamente');
                }
            }

            // Check for updated param (from edit page)
            if (urlParams.has('updated')) {
                // Clear URL param without reload
                window.history.replaceState({}, '', '/aprendiz/equipos');
                
                // Show success notification
                if (window.showSuccess) {
                    window.showSuccess('Equipo actualizado correctamente');
                }
            }

            // Also check for flash messages from server
            const successMsg = <?= json_encode($success ?? '') ?>;
            const errorMsg = <?= json_encode($error ?? '') ?>;
            const messageMsg = <?= json_encode($message ?? '') ?>;
            
            if (successMsg && window.showSuccess) {
                window.showSuccess(successMsg);
            } else if (errorMsg && window.showError) {
                window.showError(errorMsg);
            } else if (messageMsg && window.showInfo) {
                window.showInfo(messageMsg);
            }

            // Utilizar una key única para la PWA que no conflte con otras funciones
            const PWA_RELOAD_KEY = 'equipos_ajax_loaded';

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

            // Confirmar eliminación con AJAX
            if (btnConfirmDelete) {
                btnConfirmDelete.addEventListener('click', function() {
                    if (currentDeleteId) {
                        eliminarEquipo(currentDeleteId);
                    }
                });
            }

            // Función para eliminar equipo vía AJAX
            async function eliminarEquipo(relacionId) {
                try {
                    btnConfirmDelete.disabled = true;
                    btnConfirmDelete.textContent = 'Eliminando...';

                    const response = await fetch('/api/aprendiz/equipos/' + relacionId + '/eliminar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });

                    const data = await response.json();

                    if (data.success) {
                        // ✅ Invalidate cache after mutation
                        invalidateEquiposCache();
                        
                        // Encontrar y ocultar la tarjeta del equipo
                        const card = document.querySelector('.btn-delete-icon[data-id="' + relacionId + '"]');
                        if (card) {
                            const equipoCard = card.closest('.equipo-card');
                            if (equipoCard) {
                                equipoCard.classList.add('eliminando');
                                setTimeout(function() {
                                    equipoCard.remove();
                                    actualizarContadores();
                                    agregarEquipoAEliminados(data.data.equipo);
                                }, 300);
                            }
                        }
                        closeDeleteModal();
                        mostrarFlash('success', data.message);
                    } else {
                        closeDeleteModal();
                        mostrarFlash('error', data.message);
                    }
                } catch (error) {
                    console.error('Error al eliminar equipo:', error);
                    closeDeleteModal();
                    mostrarFlash('error', 'Error de conexión. Intenta nuevamente.');
                } finally {
                    btnConfirmDelete.disabled = false;
                    btnConfirmDelete.textContent = 'Sí, eliminar';
                }
            }

            // Función para restaurar equipo vía AJAX
            async function restaurarEquipo(relacionId, boton) {
                try {
                    boton.disabled = true;
                    boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restaurando...';

                    const response = await fetch('/api/aprendiz/equipos/' + relacionId + '/restaurar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });

                    const data = await response.json();

                    if (data.success) {
                        // ✅ Invalidate cache after mutation
                        invalidateEquiposCache();
                        
                        // Encontrar y mover la tarjeta del equipo
                        const card = boton.closest('.equipo-card');
                        if (card) {
                            card.classList.add('restaurando');
                            setTimeout(function() {
                                card.remove();
                                actualizarContadores();
                                agregarEquipoAActivos(data.data.equipo);
                            }, 300);
                        }
                        mostrarFlash('success', data.message);
                    } else {
                        boton.disabled = false;
                        boton.innerHTML = '<i class="fas fa-undo"></i> Volver a agregar';
                        mostrarFlash('error', data.message);
                    }
                } catch (error) {
                    console.error('Error al restaurar equipo:', error);
                    boton.disabled = false;
                    boton.innerHTML = '<i class="fas fa-undo"></i> Volver a agregar';
                    mostrarFlash('error', 'Error de conexión. Intenta nuevamente.');
                }
            }

            // Configurar botones de restaurar existentes
            document.querySelectorAll('.btn-restore').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.closest('form');
                    if (form) {
                        const match = form.action.match(/\/aprendiz\/equipos\/(\d+)\/restaurar/);
                        if (match) {
                            restaurarEquipo(match[1], this);
                        }
                    }
                });
            });

            // Función para agregar equipo a la lista de eliminados
            function agregarEquipoAEliminados(equipo) {
                const equiposEliminadosSection = document.querySelector('.equipos-eliminados-section');
                const equiposGridEliminados = document.querySelector('.equipos-eliminados-list .equipos-grid');

                if (!equiposGridEliminados || !equipo) return;

                const tarjetaHtml = crearTarjetaEquipo(equipo, true);
                equiposGridEliminados.insertAdjacentHTML('beforeend', tarjetaHtml);

                // Agregar event listener al nuevo botón
                const nuevoBoton = equiposGridEliminados.lastElementChild.querySelector('.btn-restore');
                if (nuevoBoton) {
                    nuevoBoton.addEventListener('click', function(e) {
                        e.preventDefault();
                        const match = this.closest('form').action.match(/\/aprendiz\/equipos\/(\d+)\/restaurar/);
                        if (match) {
                            restaurarEquipo(match[1], this);
                        }
                    });
                }

                // Mostrar la sección si estaba oculta
                if (equiposEliminadosSection) {
                    equiposEliminadosSection.style.display = 'block';
                    const eliminadosList = document.getElementById('eliminadosList');
                    if (eliminadosList) {
                        eliminadosList.classList.add('show');
                    }
                }
            }

            // Función para agregar equipo a la lista de activos
            function agregarEquipoAActivos(equipo) {
                const equiposGrid = document.querySelector('.aprendiz-equipos-list .equipos-grid');

                if (!equiposGrid || !equipo) return;

                const tarjetaHtml = crearTarjetaEquipo(equipo, false);
                equiposGrid.insertAdjacentHTML('beforeend', tarjetaHtml);

                // Agregar event listener al nuevo botón de eliminar
                const nuevoBoton = equiposGrid.lastElementChild.querySelector('.btn-delete-icon');
                if (nuevoBoton) {
                    nuevoBoton.addEventListener('click', function() {
                        currentDeleteId = this.getAttribute('data-id');
                        const marca = this.getAttribute('data-marca');
                        const serial = this.getAttribute('data-serial');
                        
                        modalMarca.textContent = marca;
                        modalSerial.textContent = serial;
                        deleteModal.classList.add('show');
                    });
                }

                // Ocultar sección de eliminados si está vacía
                const eliminadosGrid = document.querySelector('.equipos-eliminados-list .equipos-grid');
                if (eliminadosGrid && eliminadosGrid.children.length === 0) {
                    const eliminadosSection = document.querySelector('.equipos-eliminados-section');
                    if (eliminadosSection) {
                        eliminadosSection.style.display = 'none';
                    }
                }
            }

            // Helper function to format dates
            function formatDate(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return day + '/' + month + '/' + year;
            }

            // Función para crear HTML de tarjeta de equipo
            function crearTarjetaEquipo(equipo, eliminado) {
                const imagenHtml = equipo.imagen && equipo.imagen !== 'uploads/equipos/'
                    ? '<img src="<?= asset("") ?>' + equipo.imagen + '" alt="' + equipo.marca + '">'
                    : '<div class="equipo-imagen-placeholder"><i class="fas fa-laptop"></i></div>';

                if (eliminado) {
                    return '<div class="equipo-card eliminado">' +
                        '<div class="equipo-imagen">' + imagenHtml + '</div>' +
                        '<div class="equipo-info">' +
                        '<h3>' + equipo.marca + '</h3>' +
                        '<div class="equipo-details">' +
                        '<p><strong>Serial:</strong> <code>' + equipo.numero_serial + '</code></p>' +
                        '<p><strong>Eliminado:</strong> <span class="badge-eliminado">Ahora</span></p>' +
                        '</div>' +
                        '<div class="equipo-actions">' +
                        '<form action="/aprendiz/equipos/' + equipo.relacion_id + '/restaurar" method="POST">' +
                        '<button type="submit" class="btn-restore">' +
                        '<i class="fas fa-undo"></i> Volver a agregar' +
                        '</button>' +
                        '</form>' +
                        '</div>' +
                        '</div>' +
                        '</div>';
                } else {
                    const fechaRegistro = equipo.fecha_asignacion 
                        ? '<p><strong>Registrado:</strong> ' + formatDate(equipo.fecha_asignacion) + '</p>' 
                        : '';
                    
                    return '<div class="equipo-card" data-animate-delay="0">' +
                        '<div class="equipo-imagen">' + imagenHtml + '</div>' +
                        '<div class="equipo-info">' +
                        '<h3>' + equipo.marca + '</h3>' +
                        '<div class="equipo-details">' +
                        '<p><strong>Serial:</strong> <code>' + equipo.numero_serial + '</code></p>' +
                        '<p><strong>Estado:</strong> <span class="badge-activo">Activo</span></p>' +
                        fechaRegistro +
                        '</div>' +
                        '<div class="equipo-actions">' +
                        '<a href="/aprendiz/equipos/' + equipo.equipo_id + '/qr" class="btn btn-primary btn-qr">' +
                        '<i class="fas fa-qrcode"></i> Ver QR' +
                        '</a>' +
                        '<a href="/aprendiz/equipos/' + equipo.equipo_id + '/editar" class="btn-edit-icon" ' +
                        'title="Editar equipo">' +
                        '<i class="fas fa-edit"></i>' +
                        '</a>' +
                        '<button type="button" class="btn-delete-icon" ' +
                        'data-id="' + equipo.relacion_id + '" ' +
                        'data-marca="' + equipo.marca + '" ' +
                        'data-serial="' + equipo.numero_serial + '" ' +
                        'title="Eliminar equipo">' +
                        '<i class="fas fa-trash-alt"></i>' +
                        '</button>' +
                        '</div>' +
                        '</div>' +
                        '</div>';
                }
            }

            // Función para actualizar contadores
            function actualizarContadores() {
                const activosGrid = document.querySelector('.aprendiz-equipos-list .equipos-grid');
                const eliminadosGrid = document.querySelector('.equipos-eliminados-list .equipos-grid');

                const activosCount = activosGrid ? activosGrid.children.length : 0;
                const eliminadosCount = eliminadosGrid ? eliminadosGrid.children.length : 0;

                // Actualizar título de activos
                const activosTitulo = document.querySelector('.aprendiz-equipos-header h2');
                if (activosTitulo) {
                    activosTitulo.innerHTML = '<i class="fas fa-list"></i> Equipos Registrados (' + activosCount + ')';
                }

                // Actualizar título de eliminados
                const eliminadosTitulo = document.querySelector('.equipos-eliminados-header h3');
                if (eliminadosTitulo) {
                    eliminadosTitulo.innerHTML = '<i class="fas fa-history"></i> Equipos eliminados (' + eliminadosCount + ')';
                }

                // Mostrar estado vacío si no hay equipos
                const container = document.querySelector('.container');
                if (activosCount === 0 && eliminadosCount === 0) {
                    const card = document.querySelector('.aprendiz-equipos-card');
                    const emptyState = document.querySelector('.empty-state');
                    
                    if (card && !emptyState) {
                        card.innerHTML = '<div class="aprendiz-equipos-header">' +
                            '<h2><i class="fas fa-laptop"></i> Mis Equipos</h2>' +
                            '</div>' +
                            '<div class="empty-state">' +
                            '<i class="fas fa-laptop empty-state-icon"></i>' +
                            '<p class="empty-state-text">No tienes equipos registrados aún.</p>' +
                            '<a href="/aprendiz/equipos/crear" class="btn btn-primary">' +
                            '<i class="fas fa-plus"></i> Registrar mi primer equipo' +
                            '</a>' +
                            '</div>';
                    }
                }
            }

            // Función para mostrar mensajes flash
            function mostrarFlash(tipo, mensaje) {
                const container = document.querySelector('.container');
                if (!container) return;

                // Crear elemento de alerta
                const alert = document.createElement('div');
                alert.className = 'alert alert-' + tipo;
                
                const icon = tipo === 'success' ? 'check-circle' : 'exclamation-circle';
                alert.innerHTML = '<i class="fas fa-' + icon + '"></i> ' + mensaje;

                // Insertar después del header
                const header = document.querySelector('.dashboard-header');
                if (header) {
                    header.parentNode.insertBefore(alert, header.nextSibling);
                } else {
                    container.insertBefore(alert, container.firstChild);
                }

                // Auto-remover después de 5 segundos
                setTimeout(function() {
                    alert.classList.add('fade-out');
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 5000);
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

            /**
             * Cache Invalidation Function
             * Clears Service Worker cache for equipos endpoint after mutations
             */
            async function invalidateEquiposCache() {
                console.log('[Cache] Invalidating equipos cache after mutation...');
                
                // Option A: Message to Service Worker
                if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                    navigator.serviceWorker.controller.postMessage({
                        type: 'INVALIDATE_CACHE',
                        patterns: ['/aprendiz/equipos', '/api/aprendiz/equipos']
                    });
                }
                
                // Option B: Clear localStorage cache
                localStorage.removeItem('senattend_equipos_cache');
                
                // Option C: Clear sessionStorage
                sessionStorage.removeItem('equipos_list');
                sessionStorage.removeItem('equipos_last_fetch');
                
                console.log('[Cache] Equipos cache invalidated');
            }
        });
    </script>
</body>
</html>