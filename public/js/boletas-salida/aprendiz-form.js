/**
 * JavaScript para formulario de boletas de salida (Aprendiz)
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('boletaForm');
    const tipoSalidaRadios = document.querySelectorAll('input[name="tipo_salida"]');
    const motivoSelect = document.getElementById('motivo');
    const motivoOtroRow = document.getElementById('motivoOtroRow');
    const motivoOtroTextarea = document.getElementById('motivo_otro');
    const horaReingresoGroup = document.getElementById('horaReingresoGroup');
    const horaReingresoInput = document.getElementById('hora_reingreso_solicitada');
    const instructorSearch = document.getElementById('instructor_search');
    const instructorSelect = document.getElementById('instructor_id');
    const instructorResults = document.getElementById('instructor_results');

    // Actualizar hora actual cada minuto
    function actualizarHoraActual() {
        const ahora = new Date();
        const horas = ahora.getHours();
        const minutos = ahora.getMinutes();
        
        // Convertir a formato 12 horas
        let horas12 = horas % 12;
        if (horas12 === 0) horas12 = 12;
        const periodo = horas >= 12 ? 'PM' : 'AM';
        
        const horaStr = `${horas12}:${minutos.toString().padStart(2, '0')} ${periodo}`;
        const horaActualElement = document.getElementById('hora-actual');
        if (horaActualElement) {
            horaActualElement.textContent = horaStr;
        }
    }
    
    // Actualizar inmediatamente y luego cada minuto
    actualizarHoraActual();
    setInterval(actualizarHoraActual, 60000); // 60000ms = 1 minuto
    
    // Función para convertir hora 12h a 24h
    function convertirA24Horas(hora12, minuto, periodo) {
        let hora24 = parseInt(hora12);
        if (periodo === 'AM') {
            if (hora24 === 12) hora24 = 0;
        } else { // PM
            if (hora24 !== 12) hora24 += 12;
        }
        return `${hora24.toString().padStart(2, '0')}:${minuto.toString().padStart(2, '0')}`;
    }
    
    // Sincronizar campos de hora de salida
    const horaSalidaHora = document.getElementById('hora_salida_hora');
    const horaSalidaMinuto = document.getElementById('hora_salida_minuto');
    const horaSalidaPeriodo = document.getElementById('hora_salida_periodo');
    const horaSalidaHidden = document.getElementById('hora_salida_solicitada');
    
    function actualizarHoraSalida() {
        const hora = horaSalidaHora.value;
        const minuto = horaSalidaMinuto.value;
        const periodo = horaSalidaPeriodo.value;
        
        if (hora && minuto) {
            horaSalidaHidden.value = convertirA24Horas(hora, minuto, periodo);
        }
    }
    
    horaSalidaHora.addEventListener('input', actualizarHoraSalida);
    horaSalidaMinuto.addEventListener('input', actualizarHoraSalida);
    horaSalidaPeriodo.addEventListener('change', actualizarHoraSalida);
    
    // Sincronizar campos de hora de reingreso
    const horaReingresoHora = document.getElementById('hora_reingreso_hora');
    const horaReingresoMinuto = document.getElementById('hora_reingreso_minuto');
    const horaReingresoPeriodo = document.getElementById('hora_reingreso_periodo');
    const horaReingresoHidden = document.getElementById('hora_reingreso_solicitada');
    
    function actualizarHoraReingreso() {
        const hora = horaReingresoHora.value;
        const minuto = horaReingresoMinuto.value;
        const periodo = horaReingresoPeriodo.value;
        
        if (hora && minuto) {
            horaReingresoHidden.value = convertirA24Horas(hora, minuto, periodo);
        }
    }
    
    horaReingresoHora.addEventListener('input', actualizarHoraReingreso);
    horaReingresoMinuto.addEventListener('input', actualizarHoraReingreso);
    horaReingresoPeriodo.addEventListener('change', actualizarHoraReingreso);

    // Control de tipo de salida (temporal/definitiva)
    tipoSalidaRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'temporal') {
                horaReingresoGroup.style.display = 'block';
                horaReingresoHora.required = true;
                horaReingresoMinuto.required = true;
            } else {
                horaReingresoGroup.style.display = 'none';
                horaReingresoHora.required = false;
                horaReingresoMinuto.required = false;
                horaReingresoHora.value = '';
                horaReingresoMinuto.value = '';
                horaReingresoHidden.value = '';
            }
        });
    });

    // Control de motivo "otro"
    motivoSelect.addEventListener('change', function() {
        if (this.value === 'otro') {
            motivoOtroRow.style.display = 'block';
            motivoOtroTextarea.required = true;
        } else {
            motivoOtroRow.style.display = 'none';
            motivoOtroTextarea.required = false;
            motivoOtroTextarea.value = '';
        }
    });

    // Autocompletado de instructores
    let searchTimeout;
    instructorSearch.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            instructorResults.classList.remove('active');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            const fichaId = window.FICHA_ID || null;
            const url = fichaId 
                ? `/api/instructores/buscar?q=${encodeURIComponent(query)}&ficha_id=${fichaId}`
                : `/api/instructores/buscar?q=${encodeURIComponent(query)}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        renderInstructorResults(data.data);
                    } else {
                        instructorResults.innerHTML = '<div class="autocomplete-item" style="text-align: center; color: #999;">No se encontraron instructores</div>';
                        instructorResults.classList.add('active');
                    }
                })
                .catch(error => {
                    console.error('Error buscando instructores:', error);
                    instructorResults.innerHTML = '<div class="autocomplete-item" style="text-align: center; color: #dc3545;">Error al buscar instructores</div>';
                    instructorResults.classList.add('active');
                });
        }, 300);
    });

    function renderInstructorResults(instructores) {
        instructorResults.innerHTML = '';
        
        instructores.forEach(instructor => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            if (instructor.es_de_ficha == 1) {
                item.classList.add('selected');
            }
            item.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong>${instructor.nombre}</strong>
                        ${instructor.es_de_ficha == 1 ? '<span class="badge badge-success" style="margin-left: 0.5rem; font-size: 0.75rem;">Tu ficha</span>' : ''}
                        <br>
                        <small style="color: #666;">${instructor.email}</small>
                    </div>
                    <i class="fas fa-check-circle" style="color: var(--color-success); font-size: 1.25rem; display: ${instructor.es_de_ficha == 1 ? 'block' : 'none'};"></i>
                </div>
            `;
            item.addEventListener('click', () => {
                selectInstructor(instructor);
            });
            instructorResults.appendChild(item);
        });
        
        instructorResults.classList.add('active');
    }

    function selectInstructor(instructor) {
        // Verificar si ya existe en el select
        let optionExists = false;
        for (let option of instructorSelect.options) {
            if (option.value == instructor.id) {
                option.selected = true;
                optionExists = true;
                break;
            }
        }
        
        // Si no existe, agregarlo al select
        if (!optionExists) {
            const option = document.createElement('option');
            option.value = instructor.id;
            option.text = instructor.nombre;
            option.selected = true;
            
            // Si es de la misma ficha, agregarlo al optgroup, sino agregarlo al final
            if (instructor.es_de_ficha == 1) {
                const optgroup = instructorSelect.querySelector('optgroup');
                if (optgroup) {
                    optgroup.appendChild(option);
                } else {
                    instructorSelect.appendChild(option);
                }
            } else {
                instructorSelect.appendChild(option);
            }
        }
        
        // Limpiar el campo de búsqueda y cerrar resultados
        instructorSearch.value = '';
        instructorResults.classList.remove('active');
        
        // Mostrar retroalimentación visual
        instructorSelect.style.borderColor = 'var(--color-success)';
        setTimeout(() => {
            instructorSelect.style.borderColor = '';
        }, 1000);
    }

    // Cerrar resultados al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!instructorSearch.contains(e.target) && !instructorResults.contains(e.target)) {
            instructorResults.classList.remove('active');
        }
    });

    // Validación del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevenir envío normal del formulario
        
        const tipoSalida = document.querySelector('input[name="tipo_salida"]:checked').value;
        const horaSalida = horaSalidaHidden.value;
        const horaReingreso = horaReingresoHidden.value;
        const motivo = motivoSelect.value;
        const motivoOtro = motivoOtroTextarea.value.trim();
        const instructorId = instructorSelect.value;

        let errors = [];

        if (!tipoSalida) {
            errors.push('Debe seleccionar el tipo de salida');
        }

        if (!motivo) {
            errors.push('Debe seleccionar un motivo');
        }

        if (motivo === 'otro' && !motivoOtro) {
            errors.push('Debe especificar el motivo cuando selecciona "Otro"');
        }

        // Validar campos de hora de salida
        if (!horaSalidaHora.value || !horaSalidaMinuto.value) {
            errors.push('Debe especificar la hora de salida completa');
        } else {
            // Validar rango de hora y minutos
            const hora = parseInt(horaSalidaHora.value);
            const minuto = parseInt(horaSalidaMinuto.value);
            
            if (hora < 1 || hora > 12) {
                errors.push('La hora debe estar entre 1 y 12');
            }
            if (minuto < 0 || minuto > 59) {
                errors.push('Los minutos deben estar entre 0 y 59');
            }
            
            // Validar que la hora de salida sea futura (mínimo 5 minutos)
            const ahora = new Date();
            const [horaSalidaH, horaSalidaM] = horaSalida.split(':');
            const fechaHoraSalida = new Date();
            fechaHoraSalida.setHours(parseInt(horaSalidaH), parseInt(horaSalidaM), 0, 0);
            
            // Cambiar margen a 5 minutos
            const margenMinutos = 5;
            const ahoraConMargen = new Date(ahora.getTime() + margenMinutos * 60000);
            
            if (fechaHoraSalida <= ahoraConMargen) {
                const horasActual = ahora.getHours();
                let horas12 = horasActual % 12;
                if (horas12 === 0) horas12 = 12;
                const periodoActual = horasActual >= 12 ? 'PM' : 'AM';
                const horaActualStr = `${horas12}:${ahora.getMinutes().toString().padStart(2, '0')} ${periodoActual}`;
                errors.push(`La hora de salida debe ser al menos ${margenMinutos} minutos posterior a la hora actual (${horaActualStr})`);
            }
        }

        if (tipoSalida === 'temporal') {
            if (!horaReingresoHora.value || !horaReingresoMinuto.value) {
                errors.push('Debe especificar la hora de reingreso para salidas temporales');
            } else {
                // Validar rango
                const hora = parseInt(horaReingresoHora.value);
                const minuto = parseInt(horaReingresoMinuto.value);
                
                if (hora < 1 || hora > 12) {
                    errors.push('La hora de reingreso debe estar entre 1 y 12');
                }
                if (minuto < 0 || minuto > 59) {
                    errors.push('Los minutos de reingreso deben estar entre 0 y 59');
                }
            }
        }

        if (tipoSalida === 'temporal' && horaSalida && horaReingreso && horaReingreso <= horaSalida) {
            errors.push('La hora de reingreso debe ser posterior a la hora de salida');
        }

        if (!instructorId) {
            errors.push('Debe seleccionar un instructor');
        }

        if (errors.length > 0) {
            if (window.showError) {
                window.showError('Por favor corrija los siguientes errores:', errors);
            } else {
                alert('Por favor corrija los siguientes errores:\n\n' + errors.join('\n'));
            }
            return false;
        }

        // Mostrar indicador de carga
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        // Preparar datos del formulario
        const formData = new FormData(form);

        // Enviar solicitud via AJAX
        fetch('/api/aprendiz/boletas-salida/crear', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                if (window.showSuccess) {
                    window.showSuccess(data.message || 'Solicitud enviada exitosamente');
                }

                // Limpiar formulario
                form.reset();
                horaSalidaHidden.value = '';
                horaReingresoHidden.value = '';
                
                // Restablecer visibilidad de campos condicionales
                horaReingresoGroup.style.display = 'block';
                horaReingresoHora.required = true;
                horaReingresoMinuto.required = true;
                motivoOtroRow.style.display = 'none';
                motivoOtroTextarea.required = false;

                // Recargar la tabla de boletas
                recargarTablaBoletas();

                // Scroll al inicio para ver la notificación
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            } else {
                // Mostrar errores
                const errorMessages = data.errors || ['Error al procesar la solicitud'];
                if (window.showError) {
                    window.showError('Error en la solicitud:', errorMessages);
                } else {
                    alert('Error:\n\n' + errorMessages.join('\n'));
                }
            }
        })
        .catch(error => {
            console.error('Error al enviar solicitud:', error);
            if (window.showError) {
                window.showError('Error de conexión', ['No se pudo enviar la solicitud. Por favor intente nuevamente.']);
            } else {
                alert('Error de conexión. Por favor intente nuevamente.');
            }
        })
        .finally(() => {
            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });

        return false;
    });
});

// Función para ver detalle de boleta (modal)
function verDetalle(boletaId) {
    fetch(`/api/aprendiz/boletas-salida/${boletaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarModalDetalle(data.data);
            } else {
                alert('Error al cargar el detalle: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error al cargar detalle:', error);
            alert('Error al cargar el detalle de la boleta');
        });
}

function mostrarModalDetalle(boleta) {
    const motivos = {
        'cita_medica': 'Cita / Incapacidad médica',
        'diligencias_electorales': 'Diligencias electorales / Gubernamentales',
        'tramites_etapa_productiva': 'Trámites etapa productiva',
        'requerimientos_laborales': 'Requerimientos laborales',
        'caso_fortuito': 'Casos fortuitos / Fuerza mayor',
        'representacion_sena': 'Representación SENA (Académica, Cultural, Deportiva)',
        'diligencias_judiciales': 'Diligencias judiciales',
        'otro': 'Otro'
    };

    const estadoLabels = {
        'pendiente_instructor': 'Pendiente Instructor',
        'pendiente_admin': 'Pendiente Administración',
        'aprobada': 'Aprobada',
        'validada_porteria': 'En salida',
        'completada': 'Completada',
        'rechazada_instructor': 'Rechazada por Instructor',
        'rechazada_admin': 'Rechazada por Administración'
    };

    const estadoClasses = {
        'pendiente_instructor': 'warning',
        'pendiente_admin': 'info',
        'aprobada': 'success',
        'validada_porteria': 'primary',
        'completada': 'secondary',
        'rechazada_instructor': 'danger',
        'rechazada_admin': 'danger'
    };

    const motivoTexto = motivos[boleta.motivo] || boleta.motivo;
    const estadoTexto = estadoLabels[boleta.estado] || boleta.estado;
    const estadoClass = estadoClasses[boleta.estado] || 'default';

    const html = `
        <div class="boleta-detalle">
            <div class="detalle-section">
                <h4>Información General</h4>
                <div class="detalle-grid">
                    <div class="detalle-item">
                        <label>Estado:</label>
                        <span class="badge badge-${estadoClass}">${estadoTexto}</span>
                    </div>
                    <div class="detalle-item">
                        <label>Tipo de Salida:</label>
                        <span class="badge badge-${boleta.tipo_salida === 'temporal' ? 'info' : 'warning'}">
                            ${boleta.tipo_salida === 'temporal' ? 'Temporal' : 'Definitiva'}
                        </span>
                    </div>
                    <div class="detalle-item">
                        <label>Fecha de Solicitud:</label>
                        <span>${formatDateTime(boleta.created_at)}</span>
                    </div>
                </div>
            </div>

            <div class="detalle-section">
                <h4>Motivo de Salida</h4>
                <p><strong>${motivoTexto}</strong></p>
                ${boleta.motivo === 'otro' && boleta.motivo_otro ? `<p>${boleta.motivo_otro}</p>` : ''}
            </div>

            <div class="detalle-section">
                <h4>Horarios</h4>
                <div class="detalle-grid">
                    <div class="detalle-item">
                        <label>Hora de Salida Solicitada:</label>
                        <span>${formatTime(boleta.hora_salida_solicitada)}</span>
                    </div>
                    ${boleta.tipo_salida === 'temporal' && boleta.hora_reingreso_solicitada ? `
                        <div class="detalle-item">
                            <label>Hora de Reingreso Solicitada:</label>
                            <span>${formatTime(boleta.hora_reingreso_solicitada)}</span>
                        </div>
                    ` : ''}
                    ${boleta.fecha_salida_real ? `
                        <div class="detalle-item">
                            <label>Salida Real:</label>
                            <span>${formatDateTime(boleta.fecha_salida_real)}</span>
                        </div>
                    ` : ''}
                    ${boleta.fecha_reingreso_real ? `
                        <div class="detalle-item">
                            <label>Reingreso Real:</label>
                            <span>${formatDateTime(boleta.fecha_reingreso_real)}</span>
                        </div>
                    ` : ''}
                </div>
            </div>

            <div class="detalle-section">
                <h4>Aprobaciones</h4>
                <div class="detalle-grid">
                    <div class="detalle-item">
                        <label>Instructor Asignado:</label>
                        <span>${boleta.instructor_nombre || 'N/A'}</span>
                    </div>
                    ${boleta.instructor_aprobador_nombre ? `
                        <div class="detalle-item">
                            <label>Aprobado por Instructor:</label>
                            <span>${boleta.instructor_aprobador_nombre}</span>
                        </div>
                    ` : ''}
                    ${boleta.admin_aprobador_nombre ? `
                        <div class="detalle-item">
                            <label>Aprobado por Admin:</label>
                            <span>${boleta.admin_aprobador_nombre}</span>
                        </div>
                    ` : ''}
                </div>
            </div>

            ${boleta.estado.startsWith('rechazada') && boleta.motivo_rechazo ? `
                <div class="detalle-section">
                    <h4>Motivo de Rechazo</h4>
                    <p class="text-danger">${boleta.motivo_rechazo}</p>
                </div>
            ` : ''}
        </div>
    `;

    const modal = document.createElement('div');
    modal.className = 'modal active';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalle de Boleta de Salida</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                ${html}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Cerrar</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    const closeButtons = modal.querySelectorAll('.modal-close');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            modal.remove();
        });
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

function formatDateTime(dateTimeStr) {
    if (!dateTimeStr) return 'N/A';
    const date = new Date(dateTimeStr);
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

function formatTime(timeStr) {
    if (!timeStr) return 'N/A';
    const parts = timeStr.split(':');
    let hora = parseInt(parts[0]);
    const minuto = parts[1];
    
    let hora12 = hora % 12;
    if (hora12 === 0) hora12 = 12;
    const periodo = hora >= 12 ? 'PM' : 'AM';
    
    return `${hora12}:${minuto} ${periodo}`;
}

// Función para recargar la tabla de boletas sin recargar la página
function recargarTablaBoletas() {
    fetch('/api/aprendiz/boletas-salida?limit=20')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.boletas) {
                actualizarTablaBoletas(data.data.boletas);
            }
        })
        .catch(error => {
            console.error('Error al recargar boletas:', error);
        });
}

// Función para actualizar la tabla con nuevos datos
function actualizarTablaBoletas(boletas) {
    const tbody = document.querySelector('.boletas-table tbody');
    const emptyState = document.querySelector('.empty-state');
    
    if (!tbody) return;

    if (boletas.length === 0) {
        if (emptyState) {
            emptyState.style.display = 'block';
        }
        tbody.innerHTML = '';
        return;
    }

    if (emptyState) {
        emptyState.style.display = 'none';
    }

    const estadoClass = {
        'pendiente_instructor': 'warning',
        'pendiente_admin': 'info',
        'aprobada': 'success',
        'validada_porteria': 'primary',
        'completada': 'secondary',
        'rechazada_instructor': 'danger',
        'rechazada_admin': 'danger',
    };

    const estadoLabel = {
        'pendiente_instructor': 'Pendiente Instructor',
        'pendiente_admin': 'Pendiente Admin',
        'aprobada': 'Aprobada',
        'validada_porteria': 'En salida',
        'completada': 'Completada',
        'rechazada_instructor': 'Rechazada',
        'rechazada_admin': 'Rechazada',
    };

    tbody.innerHTML = boletas.map(boleta => {
        const fechaSolicitud = new Date(boleta.created_at);
        const fechaFormateada = fechaSolicitud.toLocaleDateString('es-CO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        const motivoTexto = boleta.motivo.replace(/_/g, ' ');
        const motivoCapitalizado = motivoTexto.charAt(0).toUpperCase() + motivoTexto.slice(1);

        return `
            <tr>
                <td>${fechaFormateada}</td>
                <td>
                    <span class="badge badge-${boleta.tipo_salida}">
                        ${boleta.tipo_salida === 'temporal' ? 'Temporal' : 'Definitiva'}
                    </span>
                </td>
                <td>${motivoCapitalizado}</td>
                <td>${boleta.instructor_nombre || 'N/A'}</td>
                <td>
                    <span class="badge badge-${estadoClass[boleta.estado] || 'default'}">
                        ${estadoLabel[boleta.estado] || boleta.estado}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-secondary" onclick="verDetalle(${boleta.id})">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}
