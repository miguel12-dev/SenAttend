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

    // Control de tipo de salida (temporal/definitiva)
    tipoSalidaRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'temporal') {
                horaReingresoGroup.style.display = 'block';
                horaReingresoInput.required = true;
            } else {
                horaReingresoGroup.style.display = 'none';
                horaReingresoInput.required = false;
                horaReingresoInput.value = '';
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
            fetch(`/api/instructores/buscar?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        renderInstructorResults(data.data);
                    } else {
                        instructorResults.innerHTML = '<div class="autocomplete-item">No se encontraron instructores</div>';
                        instructorResults.classList.add('active');
                    }
                })
                .catch(error => {
                    console.error('Error buscando instructores:', error);
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
                <strong>${instructor.nombre}</strong>
                ${instructor.es_de_ficha == 1 ? '<span class="badge badge-success" style="margin-left: 0.5rem;">Tu ficha</span>' : ''}
                <br>
                <small style="color: #666;">${instructor.email}</small>
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
        
        // Si no existe, agregarlo
        if (!optionExists) {
            const option = document.createElement('option');
            option.value = instructor.id;
            option.text = instructor.nombre;
            option.selected = true;
            instructorSelect.appendChild(option);
        }
        
        instructorSearch.value = '';
        instructorResults.classList.remove('active');
    }

    // Cerrar resultados al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!instructorSearch.contains(e.target) && !instructorResults.contains(e.target)) {
            instructorResults.classList.remove('active');
        }
    });

    // Validación del formulario
    form.addEventListener('submit', function(e) {
        const tipoSalida = document.querySelector('input[name="tipo_salida"]:checked').value;
        const horaSalida = document.getElementById('hora_salida_solicitada').value;
        const horaReingreso = horaReingresoInput.value;
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

        if (!horaSalida) {
            errors.push('Debe especificar la hora de salida');
        }

        if (tipoSalida === 'temporal' && !horaReingreso) {
            errors.push('Debe especificar la hora de reingreso para salidas temporales');
        }

        if (tipoSalida === 'temporal' && horaSalida && horaReingreso && horaReingreso <= horaSalida) {
            errors.push('La hora de reingreso debe ser posterior a la hora de salida');
        }

        if (!instructorId) {
            errors.push('Debe seleccionar un instructor');
        }

        if (errors.length > 0) {
            e.preventDefault();
            alert('Por favor corrija los siguientes errores:\n\n' + errors.join('\n'));
        }
    });
});

// Función para ver detalle de boleta (modal)
function verDetalle(boletaId) {
    alert('Detalle de boleta #' + boletaId + ' (funcionalidad a implementar con modal)');
}
