/**
 * Gestión del panel de boletas de salida para instructores
 */

document.addEventListener('DOMContentLoaded', function() {
    let boletaIdActual = null;

    const modalAprobar = document.getElementById('modalAprobar');
    const modalRechazar = document.getElementById('modalRechazar');
    const modalDetalle = document.getElementById('modalDetalle');
    const motivoRechazo = document.getElementById('motivoRechazo');
    const btnCancelarAprobacion = document.getElementById('btnCancelarAprobacion');
    const btnConfirmarAprobacion = document.getElementById('btnConfirmarAprobacion');
    const btnCancelarRechazo = document.getElementById('btnCancelarRechazo');
    const btnConfirmarRechazo = document.getElementById('btnConfirmarRechazo');

    const boletasList = document.querySelector('.boletas-list');
    const modalCloses = document.querySelectorAll('.modal-close');

    // Confirmar aprobación
    btnConfirmarAprobacion.addEventListener('click', function() {
        modalAprobar.classList.remove('active');
        aprobarBoleta(boletaIdActual);
    });

    // Cancelar aprobación
    btnCancelarAprobacion.addEventListener('click', function() {
        modalAprobar.classList.remove('active');
        boletaIdActual = null;
    });


    // Cancelar rechazo
    btnCancelarRechazo.addEventListener('click', function() {
        modalRechazar.classList.remove('active');
        boletaIdActual = null;
    });

    // Confirmar rechazo
    btnConfirmarRechazo.addEventListener('click', function() {
        const motivo = motivoRechazo.value.trim();
        
        // Validar que el motivo no esté vacío
        if (!motivo) {
            motivoRechazo.style.borderColor = '#e74c3c';
            motivoRechazo.focus();
            if (window.showWarning) {
                window.showWarning('Debe especificar el motivo del rechazo');
            } else {
                alert('Debe especificar el motivo del rechazo');
            }
            return;
        }
        
        // Validar longitud mínima (al menos 10 caracteres)
        if (motivo.length < 10) {
            motivoRechazo.style.borderColor = '#e74c3c';
            motivoRechazo.focus();
            if (window.showWarning) {
                window.showWarning('El motivo debe tener al menos 10 caracteres');
            } else {
                alert('El motivo debe tener al menos 10 caracteres');
            }
            return;
        }
        
        // Restablecer el estilo del borde
        motivoRechazo.style.borderColor = '';
        rechazarBoleta(boletaIdActual, motivo);
    });
    
    // Remover borde rojo al escribir
    motivoRechazo.addEventListener('input', function() {
        if (this.value.trim().length > 0) {
            this.style.borderColor = '';
        }
    });

    // Eventos delegados para botones dinámicos (AJAX refresh)
    if (boletasList) {
        boletasList.addEventListener('click', function(event) {
            const btnAprobar = event.target.closest('.btn-aprobar');
            if (btnAprobar) {
                boletaIdActual = btnAprobar.dataset.id;
                modalAprobar.classList.add('active');
                return;
            }

            const btnRechazar = event.target.closest('.btn-rechazar');
            if (btnRechazar) {
                boletaIdActual = btnRechazar.dataset.id;
                motivoRechazo.value = '';
                modalRechazar.classList.add('active');
                return;
            }

            const btnDetalle = event.target.closest('.btn-detalle');
            if (btnDetalle) {
                verDetalle(btnDetalle.dataset.id);
            }
        });
    }

    // Cerrar modales
    modalCloses.forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal').classList.remove('active');
        });
    });

    // Cerrar modal al hacer clic fuera
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });

    async function aprobarBoleta(id) {
        try {
            const response = await fetch(`/api/instructor/boletas-salida/${id}/aprobar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const result = await response.json();

            if (result.success) {
                if (window.showSuccess) {
                    window.showSuccess(result.message || 'Solicitud aprobada correctamente');
                }
                await recargarPendientes();
            } else {
                if (window.showError) {
                    window.showError('Error al aprobar', [result.message || 'No se pudo aprobar la solicitud']);
                } else {
                    alert('Error: ' + (result.message || 'No se pudo aprobar la solicitud'));
                }
            }
        } catch (error) {
            console.error('Error:', error);
            if (window.showError) {
                window.showError('Error de conexión', ['No se pudo procesar la solicitud']);
            } else {
                alert('Error al procesar la solicitud');
            }
        }
    }

    async function rechazarBoleta(id, motivo) {
        try {
            const response = await fetch(`/api/instructor/boletas-salida/${id}/rechazar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ motivo }),
            });

            const result = await response.json();

            if (result.success) {
                modalRechazar.classList.remove('active');
                if (window.showSuccess) {
                    window.showSuccess(result.message || 'Solicitud rechazada correctamente');
                }
                await recargarPendientes();
            } else {
                if (window.showError) {
                    window.showError('Error al rechazar', [result.message || 'No se pudo rechazar la solicitud']);
                } else {
                    alert('Error: ' + (result.message || 'No se pudo rechazar la solicitud'));
                }
            }
        } catch (error) {
            console.error('Error:', error);
            if (window.showError) {
                window.showError('Error de conexión', ['No se pudo procesar la solicitud']);
            } else {
                alert('Error al procesar la solicitud');
            }
        }
    }

    function mostrarEstadoVacio() {
        if (!boletasList) return;
        boletasList.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-clipboard-check"></i>
                <h3>No hay solicitudes pendientes</h3>
                <p>Todas las solicitudes han sido procesadas.</p>
            </div>
        `;
    }

    async function recargarPendientes() {
        if (!boletasList) return;
        try {
            const response = await fetch('/api/instructor/boletas-salida/pendientes');
            const payload = await response.json();
            if (!payload.success) {
                throw new Error(payload.message || 'No se pudieron obtener pendientes');
            }

            const boletas = payload.data?.boletas ?? [];
            if (boletas.length === 0) {
                mostrarEstadoVacio();
                return;
            }

            boletasList.innerHTML = boletas.map((boleta) => renderBoletaCard(boleta)).join('');
        } catch (error) {
            console.error('Error recargando pendientes de instructor:', error);
        }
    }

    function renderBoletaCard(boleta) {
        const tipoBadge = boleta.tipo_salida === 'temporal' ? 'info' : 'warning';
        const tipoLabel = boleta.tipo_salida === 'temporal' ? 'Temporal' : 'Definitiva';
        const fecha = formatDateTime(boleta.created_at);
        const motivo = getMotivoTexto(boleta.motivo);

        const descripcionOtro = boleta.motivo === 'otro' && boleta.motivo_otro
            ? `
                <div class="detail-row">
                    <span class="detail-label">Descripción:</span>
                    <span class="detail-value">${escapeHtml(boleta.motivo_otro)}</span>
                </div>
            `
            : '';

        const reingreso = boleta.tipo_salida === 'temporal' && boleta.hora_reingreso_solicitada
            ? `
                <div class="detail-row">
                    <span class="detail-label">Hora de reingreso:</span>
                    <span class="detail-value">${formatTime(boleta.hora_reingreso_solicitada)}</span>
                </div>
            `
            : '';

        return `
            <div class="boleta-card">
                <div class="boleta-header">
                    <div class="boleta-title">
                        <h3>${escapeHtml(`${boleta.aprendiz_nombre ?? ''} ${boleta.aprendiz_apellido ?? ''}`.trim())}</h3>
                        <span class="badge badge-${tipoBadge}">${tipoLabel}</span>
                    </div>
                    <div class="boleta-date">
                        <i class="far fa-calendar"></i>
                        ${fecha}
                    </div>
                </div>
                <div class="boleta-details">
                    <div class="detail-row">
                        <span class="detail-label">Documento:</span>
                        <span class="detail-value">${escapeHtml(boleta.aprendiz_documento ?? '')}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Ficha:</span>
                        <span class="detail-value">${escapeHtml(`${boleta.numero_ficha ?? ''} - ${boleta.ficha_nombre ?? ''}`)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Motivo:</span>
                        <span class="detail-value">${escapeHtml(motivo)}</span>
                    </div>
                    ${descripcionOtro}
                    <div class="detail-row">
                        <span class="detail-label">Hora de salida:</span>
                        <span class="detail-value">${formatTime(boleta.hora_salida_solicitada)}</span>
                    </div>
                    ${reingreso}
                </div>
                <div class="boleta-actions">
                    <button type="button" class="btn btn-success btn-aprobar" data-id="${boleta.id}">
                        <i class="fas fa-check"></i> Aprobar
                    </button>
                    <button type="button" class="btn btn-danger btn-rechazar" data-id="${boleta.id}">
                        <i class="fas fa-times"></i> Rechazar
                    </button>
                    <button type="button" class="btn btn-secondary btn-detalle" data-id="${boleta.id}">
                        <i class="fas fa-eye"></i> Ver Detalle
                    </button>
                </div>
            </div>
        `;
    }

    function getMotivoTexto(motivo) {
        const motivos = {
            cita_medica: 'Cita / incapacidad médica',
            diligencia_electoral: 'Diligencias electorales / Gubernamentales',
            tramite_etapa_productiva: 'Trámites etapa productiva',
            requerimiento_laboral: 'Requerimientos laborales',
            fuerza_mayor: 'Casos fortuitos / fuerza mayor',
            representacion_sena: 'Representación SENA',
            diligencia_judicial: 'Diligencias judiciales',
            otro: 'Otro',
        };
        return motivos[motivo] || motivo || '';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    async function verDetalle(id) {
        try {
            const response = await fetch(`/api/instructor/boletas-salida/${id}`);
            const result = await response.json();

            if (result.success) {
                mostrarDetalle(result.data);
            } else {
                if (window.showError) {
                    window.showError('Error', [result.message || 'No se pudo obtener el detalle']);
                } else {
                    alert('Error: ' + (result.message || 'No se pudo obtener el detalle'));
                }
            }
        } catch (error) {
            console.error('Error:', error);
            if (window.showError) {
                window.showError('Error de conexión', ['No se pudo cargar el detalle']);
            } else {
                alert('Error al cargar el detalle');
            }
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
        let hora = parseInt(parts[0]);
        const minuto = parts[1];
        
        let hora12 = hora % 12;
        if (hora12 === 0) hora12 = 12;
        const periodo = hora >= 12 ? 'PM' : 'AM';
        
        return `${hora12}:${minuto} ${periodo}`;
    }

    function formatDateTime(dateTime) {
        if (!dateTime) return 'N/A';
        const date = new Date(dateTime);
        const horas = date.getHours();
        let horas12 = horas % 12;
        if (horas12 === 0) horas12 = 12;
        const periodo = horas >= 12 ? 'PM' : 'AM';
        
        return date.toLocaleDateString('es-CO', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        }) + ' ' + horas12 + ':' + date.getMinutes().toString().padStart(2, '0') + ' ' + periodo;
    }

    // Sincronizar listado al cargar para evitar datos obsoletos
    recargarPendientes();
});
