/**
 * Gestión del panel de validación de boletas de salida para porteros
 */

document.addEventListener('DOMContentLoaded', function() {
    let boletaIdActual = null;

    const modalReingreso = document.getElementById('modalReingreso');
    const modalDetalle = document.getElementById('modalDetalle');
    const observacionesReingreso = document.getElementById('observacionesReingreso');
    const btnCancelarReingreso = document.getElementById('btnCancelarReingreso');
    const btnConfirmarReingreso = document.getElementById('btnConfirmarReingreso');

    const btnsValidarSalida = document.querySelectorAll('.btn-validar-salida');
    const btnsValidarReingreso = document.querySelectorAll('.btn-validar-reingreso');
    const btnsDetalle = document.querySelectorAll('.btn-detalle');
    const modalCloses = document.querySelectorAll('.modal-close');
    const tabLinks = document.querySelectorAll('.tab-link');

    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tab = this.dataset.tab;
            
            document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(`tab-${tab}`).classList.add('active');
        });
    });

    btnsValidarSalida.forEach(btn => {
        btn.addEventListener('click', function() {
            const boletaId = this.dataset.id;
            if (confirm('¿Confirma la salida física del aprendiz?')) {
                validarSalida(boletaId);
            }
        });
    });

    btnsValidarReingreso.forEach(btn => {
        btn.addEventListener('click', function() {
            boletaIdActual = this.dataset.id;
            observacionesReingreso.value = '';
            modalReingreso.classList.add('active');
        });
    });

    btnCancelarReingreso.addEventListener('click', function() {
        modalReingreso.classList.remove('active');
        boletaIdActual = null;
    });

    btnConfirmarReingreso.addEventListener('click', function() {
        const observaciones = observacionesReingreso.value.trim();
        validarReingreso(boletaIdActual, observaciones);
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

    async function validarSalida(id) {
        try {
            const response = await fetch(`/api/portero/boletas-salida/${id}/validar-salida`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const result = await response.json();

            if (result.success) {
                alert('Salida validada correctamente. Hora registrada: ' + new Date().toLocaleTimeString('es-CO'));
                location.reload();
            } else {
                alert('Error: ' + (result.message || 'No se pudo validar la salida'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al procesar la validación');
        }
    }

    async function validarReingreso(id, observaciones) {
        try {
            const response = await fetch(`/api/portero/boletas-salida/${id}/validar-reingreso`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ observaciones }),
            });

            const result = await response.json();

            if (result.success) {
                modalReingreso.classList.remove('active');
                alert('Reingreso validado correctamente. Hora registrada: ' + new Date().toLocaleTimeString('es-CO'));
                location.reload();
            } else {
                alert('Error: ' + (result.message || 'No se pudo validar el reingreso'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al procesar la validación');
        }
    }

    async function verDetalle(id) {
        try {
            const response = await fetch(`/api/portero/boletas-salida/${id}`);
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

        if (boleta.instructor_aprobador_nombre) {
            html += `
                <div class="detail-row">
                    <span class="detail-label">Aprobado por instructor:</span>
                    <span class="detail-value">${boleta.instructor_aprobador_nombre}</span>
                </div>
            `;
        }

        if (boleta.admin_aprobador_nombre) {
            html += `
                <div class="detail-row">
                    <span class="detail-label">Aprobado por admin:</span>
                    <span class="detail-value">${boleta.admin_aprobador_nombre}</span>
                </div>
            `;
        }

        if (boleta.fecha_salida_real) {
            html += `
                <div class="detail-row">
                    <span class="detail-label">Salida registrada:</span>
                    <span class="detail-value">${formatDateTime(boleta.fecha_salida_real)}</span>
                </div>
            `;
        }

        if (boleta.fecha_reingreso_real) {
            html += `
                <div class="detail-row">
                    <span class="detail-label">Reingreso registrado:</span>
                    <span class="detail-value">${formatDateTime(boleta.fecha_reingreso_real)}</span>
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
