document.addEventListener('DOMContentLoaded', function() {
    // --- Auto-reload: forced reload on first visit + 30s interval ---
    const AUTO_RELOAD_KEY = 'seguimiento_equipos_first_load';
    const AUTO_RELOAD_INTERVAL = 30000; // 30 seconds

    if (!sessionStorage.getItem(AUTO_RELOAD_KEY)) {
        // First visit: mark flag, then reload after a brief delay to force fresh data
        sessionStorage.setItem(AUTO_RELOAD_KEY, '1');
        setTimeout(function() {
            location.reload();
        }, 800);
        // Don't set up listeners yet — they'll initialize after reload
        return;
    }

    // After first reload: set up 30s auto-reload with pause-on-modal
    let reloadTimer = null;
    let modalOpen = false;
    let countdownValue = 30;
    let countdownEl = document.getElementById('reload-countdown');
    let indicatorEl = document.getElementById('auto-reload-indicator');

    function startAutoReload() {
        if (reloadTimer) clearInterval(reloadTimer);
        countdownValue = 30;
        reloadTimer = setInterval(function() {
            countdownValue--;
            if (countdownEl) countdownEl.textContent = countdownValue;
            if (countdownValue <= 0) {
                if (!modalOpen) {
                    location.reload();
                } else {
                    countdownValue = 30; // Reset if modal is open
                }
            }
        }, 1000);
    }

    function resetAutoReload() {
        if (reloadTimer) clearInterval(reloadTimer);
        startAutoReload();
    }

    startAutoReload();

    // --- Rest of the code ---
    const btnProcesar = document.getElementById('btn-procesar-cierres');
    const btnExportar = document.getElementById('btn-exportar-excel-seguimiento');
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

    // --- Botón Procesar Cierres ---
    if (btnProcesar) {
        btnProcesar.addEventListener('click', function() {
            Swal.fire({
                title: '¿Procesar Cierres Automáticamente?',
                text: "El sistema buscará registros de ingresos de fechas anteriores que nunca registraron su salida, y las marcará como 'Salida no registrada'.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#39A900',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, procesar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {

                    Swal.fire({
                        title: 'Procesando...',
                        html: 'Por favor espere mientras verificamos los registros.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('/api/seguimiento-equipos/procesar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            _token: csrfToken
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                '¡Completado!',
                                data.message,
                                'success'
                            ).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error',
                                data.error || 'Ocurrió un error al procesar las infracciones.',
                                'error'
                            );
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire(
                            'Error',
                            'Fallo de conexión al procesar la solicitud.',
                            'error'
                        );
                    });
                }
            });
        });
    }

    // --- Botón Exportar Excel ---
    if (btnExportar) {
        btnExportar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;

            if (!fechaInicio || !fechaFin) {
                Swal.fire('Atención', 'Debes tener ambas fechas seleccionadas.', 'warning');
                return;
            }

            const url = `/admin/seguimiento-equipos/exportar?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&_token=${csrfToken}`;
            window.location.href = url;

            Swal.fire({
                icon: 'success',
                title: 'Generando Reporte...',
                text: 'La descarga comenzará en breve.',
                timer: 3000,
                showConfirmButton: false
            });
        });
    }

    // --- Modal de Detalle de Infracciones ---
    const modal = document.getElementById('modal-detalle-infracciones');
    const modalCuerpo = document.getElementById('modal-cuerpo');
    const modalTitulo = document.getElementById('modal-titulo');
    const modalCerrar = document.getElementById('modal-cerrar');

    function abrirModal(aprendizId, nombre, documento) {
        modalOpen = true; // Pause auto-reload
        if (reloadTimer) clearInterval(reloadTimer);
        if (indicatorEl) {
            indicatorEl.innerHTML = '<i class="fas fa-pause-circle"></i> Auto-refresh <strong>PAUSED</strong> (modal abierto)';
            indicatorEl.classList.add('auto-reload-indicator--paused');
        }

        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;

        // Mostrar loading
        modalTitulo.innerHTML = '<i class="fas fa-clipboard-list"></i> Detalle: ' + escapeHtml(nombre);
        modalCuerpo.innerHTML =
            '<div class="modal-loading">' +
                '<i class="fas fa-spinner fa-spin modal-loading__icon"></i>' +
                '<p class="modal-loading__text">Cargando infracciones...</p>' +
            '</div>';
        modal.classList.add('active');

        // Fetch data
        const url = '/api/seguimiento-equipos/detalle-aprendiz?aprendiz_id=' + aprendizId
            + '&fecha_inicio=' + fechaInicio
            + '&fecha_fin=' + fechaFin
            + '&_token=' + csrfToken;

        fetch(url)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.infracciones.length > 0) {
                    renderDetalle(data.infracciones, nombre, documento);
                } else {
                    modalCuerpo.innerHTML =
                        '<div class="modal-info">' +
                            '<i class="fas fa-info-circle modal-info__icon"></i>' +
                            '<p class="modal-info__text">No se encontraron infracciones para este aprendiz en el rango seleccionado.</p>' +
                        '</div>';
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                modalCuerpo.innerHTML =
                    '<div class="modal-error">' +
                        '<i class="fas fa-exclamation-triangle modal-error__icon"></i>' +
                        '<p class="modal-error__text">Error al cargar el detalle. Intente nuevamente.</p>' +
                    '</div>';
            });
    }

    function renderDetalle(infracciones, nombre, documento) {
        var html =
            '<div class="modal-summary">' +
                '<strong>Aprendiz:</strong> ' + escapeHtml(nombre) + ' &nbsp;|&nbsp;' +
                '<strong>Documento:</strong> ' + escapeHtml(documento) + ' &nbsp;|&nbsp;' +
                '<strong>Total:</strong> <span class="modal-summary__total">' + infracciones.length + '</span>' +
            '</div>' +
            '<table class="detail-table">' +
                '<thead>' +
                    '<tr>' +
                        '<th>#</th>' +
                        '<th>Fecha Ingreso</th>' +
                        '<th>Hora Ingreso</th>' +
                        '<th>Fecha Salida</th>' +
                        '<th>Hora Salida</th>' +
                        '<th>Equipo</th>' +
                        '<th>Tipo</th>' +
                        '<th>Observaciones</th>' +
                    '</tr>' +
                '</thead>' +
                '<tbody>';

        infracciones.forEach(function(inf, index) {
            var badgeClass = inf.tipo_infraccion === 'Sin registro de salida'
                ? 'badge-infraccion--sin-salida'
                : 'badge-infraccion--con-observacion';

            var fechaSalida = inf.fecha_salida
                ? escapeHtml(inf.fecha_salida)
                : '<span class="detail-table__na">N/A</span>';

            var horaSalida = inf.hora_salida
                ? escapeHtml(inf.hora_salida)
                : '<span class="detail-table__na">N/A</span>';

            var serial = inf.numero_serial
                ? ' <small class="detail-table__serial">(' + escapeHtml(inf.numero_serial) + ')</small>'
                : '';

            var observaciones = inf.observaciones
                ? escapeHtml(inf.observaciones)
                : '<span class="detail-table__empty">—</span>';

            html +=
                    '<tr>' +
                        '<td>' + (index + 1) + '</td>' +
                        '<td>' + escapeHtml(inf.fecha_ingreso) + '</td>' +
                        '<td>' + escapeHtml(inf.hora_ingreso) + '</td>' +
                        '<td>' + fechaSalida + '</td>' +
                        '<td>' + horaSalida + '</td>' +
                        '<td>' + escapeHtml(inf.marca_equipo) + serial + '</td>' +
                        '<td><span class="badge-infraccion ' + badgeClass + '">' + escapeHtml(inf.tipo_infraccion) + '</span></td>' +
                        '<td>' + observaciones + '</td>' +
                    '</tr>';
        });

        html +=
                '</tbody>' +
            '</table>';

        modalCuerpo.innerHTML = html;
    }

    function cerrarModal() {
        modal.classList.remove('active');
        modalOpen = false; // Resume auto-reload
        if (indicatorEl) {
            indicatorEl.innerHTML = '<i class="fas fa-sync-alt"></i> Auto-refresh: <strong id="reload-countdown">30</strong>s';
            indicatorEl.classList.remove('auto-reload-indicator--paused');
            countdownEl = document.getElementById('reload-countdown');
        }
        startAutoReload();
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Event listeners para los contadores
    document.querySelectorAll('.infraccion-count').forEach(function(el) {
        el.addEventListener('click', function() {
            var id = this.getAttribute('data-aprendiz-id');
            var nombre = this.getAttribute('data-aprendiz-nombre');
            var doc = this.getAttribute('data-aprendiz-doc');
            abrirModal(id, nombre, doc);
        });
    });

    // Cerrar modal
    if (modalCerrar) {
        modalCerrar.addEventListener('click', cerrarModal);
    }

    // Cerrar al hacer click fuera del modal
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                cerrarModal();
            }
        });
    }

    // Cerrar con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            cerrarModal();
        }
    });
});
