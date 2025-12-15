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
                body: JSON.stringify({ 
                    documento: documento,
                    evento_id: eventoId
                })
            });
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('El servidor no respondió con JSON válido');
            }
            
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
                // Verificar si es porque ya está registrado
                if (data.ya_registrado) {
                    // Mostrar mensaje de ya registrado con opción de reenvío
                    const mensaje = data.error || 'Ya estás registrado en este evento.';
                    const emailEnmascarado = data.email_enmascarado || '***';
                    const estado = data.estado || 'registrado';
                    
                    // Determinar tipo de QR según el estado
                    let tipoQR = 'ingreso';
                    let estadoTexto = 'registrado';
                    
                    if (estado === 'ingreso' || estado === 'sin_salida') {
                        tipoQR = 'salida';
                        estadoTexto = 'ya registraste tu entrada';
                    } else if (estado === 'salida') {
                        estadoTexto = 'ya completaste el evento';
                    }
                    
                    const html = `
                        <div style="text-align: left; padding: 1rem;">
                            <p style="margin-bottom: 1rem; font-weight: bold; color: #ff9800;">
                                ⚠️ ${mensaje}
                            </p>
                            <p style="margin-bottom: 0.5rem; color: #666;">
                                <strong>Estado actual:</strong> ${estadoTexto}
                            </p>
                            <p style="margin-bottom: 1rem; color: #666;">
                                <strong>Email registrado:</strong> ${emailEnmascarado}
                            </p>
                            ${estado !== 'salida' ? `
                                <button onclick="reenviarQRDirecto('${documento}')" 
                                        style="width: 100%; padding: 0.75rem; background: #39A900; color: white; 
                                               border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                                    📧 Reenviar código QR de ${tipoQR.toUpperCase()}
                                </button>
                            ` : `
                                <p style="color: #28a745; font-weight: 600;">
                                    ✓ Ya completaste tu asistencia al evento.
                                </p>
                            `}
                        </div>
                    `;
                    
                    // Crear un div temporal para el mensaje
                    const alertDiv = document.createElement('div');
                    alertDiv.innerHTML = html;
                    alertDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); ' +
                        'background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); ' +
                        'z-index: 10000; max-width: 500px; width: 90%;';
                    
                    // Añadir botón de cerrar
                    const btnCerrar = document.createElement('button');
                    btnCerrar.innerHTML = '✕';
                    btnCerrar.style.cssText = 'position: absolute; top: 10px; right: 10px; background: none; ' +
                        'border: none; font-size: 1.5rem; cursor: pointer; color: #666;';
                    btnCerrar.onclick = () => {
                        document.body.removeChild(alertDiv);
                        document.body.removeChild(overlay);
                    };
                    alertDiv.appendChild(btnCerrar);
                    
                    // Overlay
                    const overlay = document.createElement('div');
                    overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; ' +
                        'background: rgba(0,0,0,0.5); z-index: 9999;';
                    overlay.onclick = () => {
                        document.body.removeChild(alertDiv);
                        document.body.removeChild(overlay);
                    };
                    
                    document.body.appendChild(overlay);
                    document.body.appendChild(alertDiv);
                    
                } else {
                    alert(data.error || 'Error al buscar el documento');
                }
            }
        } catch (error) {
            alert('Error de conexión. Por favor, intenta nuevamente.');
        } finally {
            loadingIndicator.style.display = 'none';
            btnBuscar.disabled = false;
        }
    }
    
    // Función global para reenviar QR directamente desde el modal
    window.reenviarQRDirecto = async function(documento) {
        try {
            const response = await fetch(`/eventos/${eventoId}/reenviar-qr`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ documento: documento })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('✓ Código QR reenviado exitosamente a: ' + (data.email_enmascarado || ''));
                window.location.href = '/eventos';
            } else {
                alert('Error: ' + (data.error || 'No se pudo reenviar el código QR'));
            }
        } catch (error) {
            alert('Error de conexión. Por favor, intenta nuevamente.');
        }
    };
    
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
            const response = await fetch(`/eventos/${eventoId}/reenviar-qr`, {
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
            alert('Error de conexión. Por favor, intenta nuevamente.');
            btnReenviarQR.disabled = false;
            btnReenviarQR.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 18px; height: 18px;"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg><span>Reenviar código QR</span>';
        }
    }
});

