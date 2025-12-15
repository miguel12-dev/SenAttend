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
    const btnReenviarQR = document.getElementById('btnReenviarQR');
    
    // Form fields
    const formDocumento = document.getElementById('formDocumento');
    const formTipo = document.getElementById('formTipo');
    const nombreInput = document.getElementById('nombre');
    const apellidoInput = document.getElementById('apellido');
    const emailInput = document.getElementById('email');

    // Buscar instructor al hacer clic
    if (btnBuscar) {
        btnBuscar.addEventListener('click', buscarInstructor);
    }
    
    // Buscar al presionar Enter
    if (documentoInput) {
        documentoInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarInstructor();
            }
        });
    }
    
    // Volver al paso 1
    if (btnVolver) {
        btnVolver.addEventListener('click', function() {
            registroForm.style.display = 'none';
            step1.classList.add('active');
            documentoInput.focus();
        });
    }
    
    // Reenviar QR
    if (btnReenviarQR) {
        btnReenviarQR.addEventListener('click', reenviarQR);
    }

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
    
    async function reenviarQR() {
        const documento = btnReenviarQR.dataset.documento;
        
        if (!documento) {
            alert('No se pudo obtener el número de documento');
            return;
        }
        
        // Deshabilitar botón
        btnReenviarQR.disabled = true;
        btnReenviarQR.innerHTML = '<span>Enviando...</span>';
        
        try {
            const response = await fetch(`/eventos/reenviar-qr/${eventoId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ documento: documento })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('✓ Código QR reenviado exitosamente. Revisa tu correo: ' + (data.email_enmascarado || ''));
                // Remover el alert después de 2 segundos y redirigir
                setTimeout(() => {
                    window.location.href = '/eventos';
                }, 2000);
            } else {
                alert('Error: ' + (data.error || 'No se pudo reenviar el código QR'));
                btnReenviarQR.disabled = false;
                btnReenviarQR.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 18px; height: 18px;"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg><span>Reenviar código QR</span>';
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error de conexión. Por favor, intenta nuevamente.');
            btnReenviarQR.disabled = false;
            btnReenviarQR.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 18px; height: 18px;"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg><span>Reenviar código QR</span>';
        }
    }
});

