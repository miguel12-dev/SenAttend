/**
 * Gestión del panel de validación de boletas de salida para porteros
 */

document.addEventListener('DOMContentLoaded', function() {
    let boletaIdActual = null;

    const modalValidarSalida = document.getElementById('modalValidarSalida');
    const modalReingreso = document.getElementById('modalReingreso');
    const modalDetalle = document.getElementById('modalDetalle');
    const observacionesReingreso = document.getElementById('observacionesReingreso');
    const btnCancelarSalida = document.getElementById('btnCancelarSalida');
    const btnConfirmarSalida = document.getElementById('btnConfirmarSalida');
    const btnCancelarReingreso = document.getElementById('btnCancelarReingreso');
    const btnConfirmarReingreso = document.getElementById('btnConfirmarReingreso');

    const modalCloses = document.querySelectorAll('.modal-close');
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabSalidasList = document.querySelector('#tab-salidas .boletas-list');
    const tabReingresosList = document.querySelector('#tab-reingresos .boletas-list');

    // Navegación entre tabs
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

    // Confirmar salida
    btnConfirmarSalida.addEventListener('click', function() {
        modalValidarSalida.classList.remove('active');
        validarSalida(boletaIdActual);
    });

    // Cancelar salida
    btnCancelarSalida.addEventListener('click', function() {
        modalValidarSalida.classList.remove('active');
        boletaIdActual = null;
    });

    // Cancelar reingreso
    btnCancelarReingreso.addEventListener('click', function() {
        modalReingreso.classList.remove('active');
        boletaIdActual = null;
    });

    // Confirmar reingreso
    btnConfirmarReingreso.addEventListener('click', function() {
        const observaciones = observacionesReingreso.value.trim();
        validarReingreso(boletaIdActual, observaciones);
    });

    // Eventos delegados para botones dinámicos (render por AJAX)
    document.addEventListener('click', function(event) {
        const btnValidarSalida = event.target.closest('.btn-validar-salida');
        if (btnValidarSalida) {
            boletaIdActual = btnValidarSalida.dataset.id;
            modalValidarSalida.classList.add('active');
            return;
        }

        const btnValidarReingreso = event.target.closest('.btn-validar-reingreso');
        if (btnValidarReingreso) {
            boletaIdActual = btnValidarReingreso.dataset.id;
            observacionesReingreso.value = '';
            modalReingreso.classList.add('active');
            return;
        }

        const btnDetalle = event.target.closest('.btn-detalle');
        if (btnDetalle) {
            verDetalle(btnDetalle.dataset.id);
        }
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
                const horaActual = new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
                if (window.showSuccess) {
                    window.showSuccess(`Salida validada correctamente. Hora registrada: ${horaActual}`);
                }
                await recargarDatosPortero();
            } else {
                if (window.showError) {
                    window.showError('Error al validar', [result.message || 'No se pudo validar la salida']);
                } else {
                    alert('Error: ' + (result.message || 'No se pudo validar la salida'));
                }
            }
        } catch (error) {
            console.error('Error:', error);
            if (window.showError) {
                window.showError('Error de conexión', ['No se pudo procesar la validación']);
            } else {
                alert('Error al procesar la validación');
            }
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
                const horaActual = new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
                if (window.showSuccess) {
                    window.showSuccess(`Reingreso validado correctamente. Hora registrada: ${horaActual}`);
                }
                await recargarDatosPortero(true);
            } else {
                if (window.showError) {
                    window.showError('Error al validar', [result.message || 'No se pudo validar el reingreso']);
                } else {
                    alert('Error: ' + (result.message || 'No se pudo validar el reingreso'));
                }
            }
        } catch (error) {
            console.error('Error:', error);
            if (window.showError) {
                window.showError('Error de conexión', ['No se pudo procesar la validación']);
            } else {
                alert('Error al procesar la validación');
            }
        }
    }

    async function verDetalle(id) {
        try {
            const response = await fetch(`/api/portero/boletas-salida/${id}`);
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

    function mostrarEstadoVacio(tab) {
        const boletasList = document.querySelector(`#tab-${tab} .boletas-list`);
        if (boletasList) {
            const mensaje = tab === 'salidas' 
                ? 'No hay salidas pendientes de validación' 
                : 'No hay reingresos pendientes';
            const submensaje = tab === 'salidas'
                ? 'Todas las salidas aprobadas han sido validadas.'
                : 'Todas las salidas temporales han sido completadas.';
                
            boletasList.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-clipboard-check"></i>
                    <h3>${mensaje}</h3>
                    <p>${submensaje}</p>
                </div>
            `;
        }
    }

    async function recargarDatosPortero(incrementarCompletados = false) {
        try {
            const [salidasResponse, reingresosResponse] = await Promise.all([
                fetch('/api/portero/boletas-salida/aprobadas'),
                fetch('/api/portero/boletas-salida/reingresos-pendientes'),
            ]);

            const [salidasPayload, reingresosPayload] = await Promise.all([
                salidasResponse.json(),
                reingresosResponse.json(),
            ]);

            if (!salidasPayload.success || !reingresosPayload.success) {
                throw new Error('No se pudieron recargar los datos de portería');
            }

            const salidas = salidasPayload.data ?? [];
            const reingresos = reingresosPayload.data ?? [];

            renderTabSalidas(salidas);
            renderTabReingresos(reingresos);
            actualizarContadoresDesdeDatos(salidas.length, reingresos.length, incrementarCompletados);
        } catch (error) {
            console.error('Error recargando datos de portería:', error);
        }
    }

    function actualizarContadoresDesdeDatos(totalSalidas, totalReingresos, incrementarCompletados = false) {
        const salidasElement = document.querySelector('.stat-card:nth-child(1) .stat-info h3');
        const reingresosElement = document.querySelector('.stat-card:nth-child(2) .stat-info h3');
        const completadosElement = document.querySelector('.stat-card:nth-child(3) .stat-info h3');

        if (salidasElement) salidasElement.textContent = String(totalSalidas);
        if (reingresosElement) reingresosElement.textContent = String(totalReingresos);
        if (completadosElement && incrementarCompletados) {
            const actual = parseInt(completadosElement.textContent, 10) || 0;
            completadosElement.textContent = String(actual + 1);
        }
    }

    function renderTabSalidas(boletas) {
        if (!tabSalidasList) return;
        if (boletas.length === 0) {
            mostrarEstadoVacio('salidas');
            return;
        }

        tabSalidasList.innerHTML = boletas.map((boleta) => `
            <div class="boleta-card">
                <div class="boleta-header">
                    <div class="boleta-title">
                        <h3>${escapeHtml(`${boleta.aprendiz_nombre ?? ''} ${boleta.aprendiz_apellido ?? ''}`.trim())}</h3>
                        <span class="badge badge-${boleta.tipo_salida === 'temporal' ? 'info' : 'warning'}">
                            ${boleta.tipo_salida === 'temporal' ? 'Temporal' : 'Definitiva'}
                        </span>
                    </div>
                    <div class="boleta-date">
                        <i class="far fa-calendar"></i>
                        Solicitada: ${formatDateTime(boleta.created_at)}
                    </div>
                </div>
                <div class="boleta-details">
                    <div class="detail-row">
                        <span class="detail-label">Documento:</span>
                        <span class="detail-value">${escapeHtml(boleta.aprendiz_documento ?? '')}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Ficha:</span>
                        <span class="detail-value">${escapeHtml(boleta.numero_ficha ?? '')}</span>
                    </div>
                </div>
                <div class="boleta-actions">
                    <button type="button" class="btn btn-success btn-validar-salida" data-id="${boleta.id}">
                        <i class="fas fa-check"></i> Validar Salida
                    </button>
                    <button type="button" class="btn btn-secondary btn-detalle" data-id="${boleta.id}">
                        <i class="fas fa-eye"></i> Ver Detalle
                    </button>
                </div>
            </div>
        `).join('');
    }

    function renderTabReingresos(boletas) {
        if (!tabReingresosList) return;
        if (boletas.length === 0) {
            mostrarEstadoVacio('reingresos');
            return;
        }

        tabReingresosList.innerHTML = boletas.map((boleta) => `
            <div class="boleta-card">
                <div class="boleta-header">
                    <div class="boleta-title">
                        <h3>${escapeHtml(`${boleta.aprendiz_nombre ?? ''} ${boleta.aprendiz_apellido ?? ''}`.trim())}</h3>
                        <span class="badge badge-warning">Pendiente Reingreso</span>
                    </div>
                    <div class="boleta-date">
                        <i class="far fa-clock"></i>
                        Salida: ${formatDateTime(boleta.fecha_salida_real)}
                    </div>
                </div>
                <div class="boleta-details">
                    <div class="detail-row">
                        <span class="detail-label">Documento:</span>
                        <span class="detail-value">${escapeHtml(boleta.aprendiz_documento ?? '')}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Hora esperada:</span>
                        <span class="detail-value">${formatTime(boleta.hora_reingreso_solicitada)}</span>
                    </div>
                </div>
                <div class="boleta-actions">
                    <button type="button" class="btn btn-primary btn-validar-reingreso" data-id="${boleta.id}">
                        <i class="fas fa-sign-in-alt"></i> Validar Reingreso
                    </button>
                    <button type="button" class="btn btn-secondary btn-detalle" data-id="${boleta.id}">
                        <i class="fas fa-eye"></i> Ver Detalle
                    </button>
                </div>
            </div>
        `).join('');
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
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

    // Sincronizar datos al cargar pantalla
    recargarDatosPortero();
});
