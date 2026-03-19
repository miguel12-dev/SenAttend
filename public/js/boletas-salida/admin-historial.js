/**
 * Gestión del historial completo de boletas de salida para administradores
 */

document.addEventListener('DOMContentLoaded', function() {
    const modalDetalle = document.getElementById('modalDetalle');
    const btnsDetalle = document.querySelectorAll('.btn-detalle');
    const modalCloses = document.querySelectorAll('.modal-close');

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

        const estados = {
            'pendiente_instructor': 'Pendiente instructor',
            'aprobado_instructor': 'Aprobado por instructor',
            'rechazado_instructor': 'Rechazado por instructor',
            'pendiente_admin': 'Pendiente admin',
            'aprobado_admin': 'Aprobado por admin',
            'rechazado_admin': 'Rechazado por admin',
            'aprobado_final': 'Aprobado final',
            'completado': 'Completado'
        };

        let html = `
            <div class="detalle-boleta">
                <div class="detail-row">
                    <span class="detail-label">ID:</span>
                    <span class="detail-value">${boleta.id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Estado:</span>
                    <span class="detail-value">${estados[boleta.estado] || boleta.estado}</span>
                </div>
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

        if (boleta.instructor_aprobador_nombre) {
            html += `
                <div class="detail-row">
                    <span class="detail-label">Aprobado por instructor:</span>
                    <span class="detail-value">${boleta.instructor_aprobador_nombre}</span>
                </div>
            `;
        }

        if (boleta.instructor_rechazado_por && boleta.instructor_motivo_rechazo) {
            html += `
                <div class="detail-row">
                    <span class="detail-label">Rechazado por instructor:</span>
                    <span class="detail-value">${boleta.instructor_aprobador_nombre}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Motivo rechazo instructor:</span>
                    <span class="detail-value">${boleta.instructor_motivo_rechazo}</span>
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

        if (boleta.admin_rechazado_por && boleta.admin_motivo_rechazo) {
            html += `
                <div class="detail-row">
                    <span class="detail-label">Rechazado por admin:</span>
                    <span class="detail-value">${boleta.admin_aprobador_nombre}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Motivo rechazo admin:</span>
                    <span class="detail-value">${boleta.admin_motivo_rechazo}</span>
                </div>
            `;
        }

        if (boleta.fecha_salida_real) {
            html += `
                <div class="detail-row">
                    <span class="detail-label">Salida validada:</span>
                    <span class="detail-value">${formatDateTime(boleta.fecha_salida_real)}</span>
                </div>
            `;
        }

        if (boleta.fecha_reingreso_real) {
            html += `
                <div class="detail-row">
                    <span class="detail-label">Reingreso validado:</span>
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
