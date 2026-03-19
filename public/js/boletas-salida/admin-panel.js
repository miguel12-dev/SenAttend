/**
 * Gestión del panel de boletas de salida para administradores
 */

document.addEventListener('DOMContentLoaded', function() {
    let boletaIdActual = null;

    const modalRechazar = document.getElementById('modalRechazar');
    const modalDetalle = document.getElementById('modalDetalle');
    const motivoRechazo = document.getElementById('motivoRechazo');
    const btnCancelarRechazo = document.getElementById('btnCancelarRechazo');
    const btnConfirmarRechazo = document.getElementById('btnConfirmarRechazo');

    const btnsAprobar = document.querySelectorAll('.btn-aprobar');
    const btnsRechazar = document.querySelectorAll('.btn-rechazar');
    const btnsDetalle = document.querySelectorAll('.btn-detalle');
    const modalCloses = document.querySelectorAll('.modal-close');

    btnsAprobar.forEach(btn => {
        btn.addEventListener('click', function() {
            const boletaId = this.dataset.id;
            if (confirm('¿Está seguro de aprobar esta solicitud de salida? Esta será la aprobación final.')) {
                aprobarBoleta(boletaId);
            }
        });
    });

    btnsRechazar.forEach(btn => {
        btn.addEventListener('click', function() {
            boletaIdActual = this.dataset.id;
            motivoRechazo.value = '';
            modalRechazar.classList.add('active');
        });
    });

    btnCancelarRechazo.addEventListener('click', function() {
        modalRechazar.classList.remove('active');
        boletaIdActual = null;
    });

    btnConfirmarRechazo.addEventListener('click', function() {
        const motivo = motivoRechazo.value.trim();
        if (!motivo) {
            alert('Debe especificar el motivo del rechazo');
            return;
        }
        rechazarBoleta(boletaIdActual, motivo);
    });

    btnsDetalle.forEach(btn => {
        btn.addEventListener('click', function() {
            const boletaId = this.dataset.id;
            verDetalle(boletaId);
        });
    });

    modalCloses.forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal').classList.remove('active');
        });
    });

    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });

    async function aprobarBoleta(id) {
        try {
            const response = await fetch(`/api/admin/boletas-salida/${id}/aprobar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const result = await response.json();

            if (result.success) {
                alert('Solicitud aprobada correctamente. La boleta está lista para validación en portería.');
                location.reload();
            } else {
                alert('Error: ' + (result.message || 'No se pudo aprobar la solicitud'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al procesar la solicitud');
        }
    }

    async function rechazarBoleta(id, motivo) {
        try {
            const response = await fetch(`/api/admin/boletas-salida/${id}/rechazar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ motivo }),
            });

            const result = await response.json();

            if (result.success) {
                modalRechazar.classList.remove('active');
                alert('Solicitud rechazada correctamente');
                location.reload();
            } else {
                alert('Error: ' + (result.message || 'No se pudo rechazar la solicitud'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al procesar la solicitud');
        }
    }

    async function verDetalle(id) {
        try {
            const response = await fetch(`/api/admin/boletas-salida/${id}`);
            const result = await response.json();

            if (result.success) {
                mostrarDetalle(result.data);
            } else {
                alert('Error: ' + (result.message || 'No se pudo obtener el detalle'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al cargar el detalle');
        }
    }

    function mostrarDetalle(boleta) {
        const motivos = {
            'cita_medica': 'Cita / incapacidad médica',
            'diligencia_electoral': 'Diligencias electorales / Gubernamentales',
            'tramite_etapa_productiva': 'Trámites etapa productiva',
            'requerimiento_laboral': 'Requerimientos laborales',
            'fuerza_mayor': 'Casos fortuitos / fuerza mayor',
            'representacion_sena': 'Representación SENA',
            'diligencia_judicial': 'Diligencias judiciales',
            'otro': 'Otro'
        };

        let html = `
            <div class="detalle-boleta">
                <div class="detail-row">
                    <span class="detail-label">Aprendiz:</span>
                    <span class="detail-value">${boleta.aprendiz_nombre} ${boleta.aprendiz_apellido}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Documento:</span>
                    <span class="detail-value">${boleta.aprendiz_documento}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Ficha:</span>
                    <span class="detail-value">${boleta.numero_ficha} - ${boleta.ficha_nombre}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Instructor asignado:</span>
                    <span class="detail-value">${boleta.instructor_nombre || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Aprobado por instructor:</span>
                    <span class="detail-value">${boleta.instructor_aprobador_nombre || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tipo de salida:</span>
                    <span class="detail-value">${boleta.tipo_salida === 'temporal' ? 'Temporal' : 'Definitiva'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Motivo:</span>
                    <span class="detail-value">${motivos[boleta.motivo] || boleta.motivo}</span>
                </div>
        `;

        if (boleta.motivo === 'otro' && boleta.motivo_otro) {
            html += `
                <div class="detail-row">
                    <span class="detail-label">Descripción:</span>
                    <span class="detail-value">${boleta.motivo_otro}</span>
                </div>
            `;
        }

        html += `
                <div class="detail-row">
                    <span class="detail-label">Hora de salida solicitada:</span>
                    <span class="detail-value">${formatTime(boleta.hora_salida_solicitada)}</span>
                </div>
        `;

        if (boleta.tipo_salida === 'temporal' && boleta.hora_reingreso_solicitada) {
            html += `
                <div class="detail-row">
                    <span class="detail-label">Hora de reingreso solicitada:</span>
                    <span class="detail-value">${formatTime(boleta.hora_reingreso_solicitada)}</span>
                </div>
            `;
        }

        html += `
                <div class="detail-row">
                    <span class="detail-label">Fecha de solicitud:</span>
                    <span class="detail-value">${formatDateTime(boleta.created_at)}</span>
                </div>
            </div>
        `;

        document.getElementById('detalleContent').innerHTML = html;
        modalDetalle.classList.add('active');
    }

    function formatTime(time) {
        if (!time) return 'N/A';
        const parts = time.split(':');
        return `${parts[0]}:${parts[1]}`;
    }

    function formatDateTime(dateTime) {
        if (!dateTime) return 'N/A';
        const date = new Date(dateTime);
        return date.toLocaleString('es-CO', { 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit', 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    }
});
