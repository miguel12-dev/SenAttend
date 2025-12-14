/**
 * SENAttend Eventos - Registro JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    const documentoInput = document.getElementById('documento');
    const btnBuscar = document.getElementById('btnBuscar');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const step1 = document.getElementById('step1');
    const registroForm = document.getElementById('registroForm');
    const btnVolver = document.getElementById('btnVolver');
    const instructorInfo = document.getElementById('instructorInfo');
    
    // Form fields
    const formDocumento = document.getElementById('formDocumento');
    const formTipo = document.getElementById('formTipo');
    const nombreInput = document.getElementById('nombre');
    const apellidoInput = document.getElementById('apellido');
    const emailInput = document.getElementById('email');

    // Buscar instructor al hacer clic
    btnBuscar.addEventListener('click', buscarInstructor);
    
    // Buscar al presionar Enter
    documentoInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarInstructor();
        }
    });
    
    // Volver al paso 1
    btnVolver.addEventListener('click', function() {
        registroForm.style.display = 'none';
        step1.classList.add('active');
        documentoInput.focus();
    });

    async function buscarInstructor() {
        const documento = documentoInput.value.trim();
        
        if (!documento) {
            alert('Por favor, ingresa tu número de documento');
            documentoInput.focus();
            return;
        }
        
        if (!/^[0-9]{6,15}$/.test(documento)) {
            alert('El documento debe contener solo números (6-15 dígitos)');
            documentoInput.focus();
            return;
        }
        
        // Mostrar loading
        loadingIndicator.style.display = 'flex';
        btnBuscar.disabled = true;
        
        try {
            const response = await fetch('/eventos/buscar-instructor', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ documento: documento })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Pasar al formulario
                step1.classList.remove('active');
                registroForm.style.display = 'block';
                
                // Establecer documento
                formDocumento.value = documento;
                
                if (data.encontrado) {
                    // Instructor encontrado - llenar datos
                    instructorInfo.style.display = 'flex';
                    formTipo.value = 'instructor';
                    nombreInput.value = data.data.nombre;
                    apellidoInput.value = data.data.apellido;
                    emailInput.value = data.data.email;
                    
                    // Hacer campos readonly para instructores encontrados
                    nombreInput.readOnly = true;
                    apellidoInput.readOnly = true;
                    emailInput.readOnly = true;
                } else {
                    // No encontrado - formulario vacío para externos
                    instructorInfo.style.display = 'none';
                    formTipo.value = 'externo';
                    nombreInput.value = '';
                    apellidoInput.value = '';
                    emailInput.value = '';
                    
                    // Permitir edición
                    nombreInput.readOnly = false;
                    apellidoInput.readOnly = false;
                    emailInput.readOnly = false;
                    
                    // Focus en nombre
                    nombreInput.focus();
                }
            } else {
                alert(data.error || 'Error al buscar el documento');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error de conexión. Por favor, intenta nuevamente.');
        } finally {
            loadingIndicator.style.display = 'none';
            btnBuscar.disabled = false;
        }
    }
});

