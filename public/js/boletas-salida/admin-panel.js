/**
 * Gestión del panel de boletas de salida para administradores
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

    const btnsAprobar = document.querySelectorAll('.btn-aprobar');
    const btnsRechazar = document.querySelectorAll('.btn-rechazar');
    const btnsDetalle = document.querySelectorAll('.btn-detalle');
    const modalCloses = document.querySelectorAll('.modal-close');

    // Manejar clic en aprobar
    btnsAprobar.forEach(btn => {
        btn.addEventListener('click', function() {
            boletaIdActual = this.dataset.id;
            modalAprobar.classList.add('active');
        });
    });

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

    // Manejar clic en rechazar
    btnsRechazar.forEach(btn => {
        btn.addEventListener('click', function() {
            boletaIdActual = this.dataset.id;
            motivoRechazo.value = '';
            modalRechazar.classList.add('active');
        });
    });

    // Cancelar rechazo
    btnCancelarRechazo.addEventListener('click', function() {
        modalRechazar.classList.remove('active');
        boletaIdActual = null;
    });

    // Confirmar rechazo
    btnConfirmarRechazo.addEventListener('click', function() {
        const motivo = motivoRechazo.value.trim();
        if (!motivo) {
            if (window.showWarning) {
                window.showWarning('Debe especificar el motivo del rechazo');
            } else {
                alert('Debe especificar el motivo del rechazo');
            }
            return;
        }
        rechazarBoleta(boletaIdActual, motivo);
    });

    // Ver detalle
    btnsDetalle.forEach(btn => {
        btn.addEventListener('click', function() {
            const boletaId = this.dataset.id;
            verDetalle(boletaId);
        });
    });

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
            const response = await fetch(`/api/admin/boletas-salida/${id}/aprobar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const result = await response.json();

            if (result.success) {
                if (window.showSuccess) {
                    window.showSuccess(result.message || 'Solicitud aprobada. La boleta está lista para validación en portería.');
                }
                
                // Remover la tarjeta de la boleta aprobada del DOM
                const boletaCard = document.querySelector(`.boleta-card .btn-aprobar[data-id="${id}"]`)?.closest('.boleta-card');
                if (boletaCard) {
                    boletaCard.style.opacity = '0';
                    boletaCard.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        boletaCard.remove();
                        
                        // Verificar si quedan boletas
                        const remainingBoletas = document.querySelectorAll('.boleta-card');
                        if (remainingBoletas.length === 0) {
                            mostrarEstadoVacio();
                        }
                        
                        // Actualizar contadores
                        actualizarContadores();
                    }, 300);
                }
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
                if (window.showSuccess) {
                    window.showSuccess(result.message || 'Solicitud rechazada correctamente');
                }
                
                // Remover la tarjeta de la boleta rechazada del DOM
                const boletaCard = document.querySelector(`.boleta-card .btn-rechazar[data-id="${id}"]`)?.closest('.boleta-card');
                if (boletaCard) {
                    boletaCard.style.opacity = '0';
                    boletaCard.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        boletaCard.remove();
                        
                        // Verificar si quedan boletas
                        const remainingBoletas = document.querySelectorAll('.boleta-card');
                        if (remainingBoletas.length === 0) {
                            mostrarEstadoVacio();
                        }
                        
                        // Actualizar contadores
                        actualizarContadores();
                    }, 300);
                }
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

    async function verDetalle(id) {
        try {
            const response = await fetch(`/api/admin/boletas-salida/${id}`);
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

    function mostrarEstadoVacio() {
        const boletasList = document.querySelector('.boletas-list');
        if (boletasList) {
            boletasList.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-clipboard-check"></i>
                    <h3>No hay solicitudes pendientes</h3>
                    <p>Todas las solicitudes han sido procesadas.</p>
                </div>
            `;
        }
    }

    function actualizarContadores() {
        // Actualizar el contador de pendientes
        const pendientesElement = document.querySelector('.stat-card:first-child .stat-info h3');
        if (pendientesElement) {
            const currentCount = parseInt(pendientesElement.textContent) || 0;
            if (currentCount > 0) {
                pendientesElement.textContent = currentCount - 1;
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
});
