/**
 * JavaScript para escaneo continuo de QR del portero
 * Similar al escáner de asistencia, escanea continuamente sin interrupciones
 * Actualizado con AutoRefresh para datos en tiempo real
 */

let html5QrCode = null;
let isScanning = false;
let historialIngresos = [];
let ultimoQRProcesado = null;
let tiempoUltimoProcesamiento = 0;
let autoRefreshHistorial = null;

// Paginación del historial
const HISTORIAL_POR_PAGINA = 20;
let historialPaginaActual = 1;
let historialTotalRegistros = 0;
let historialCargando = false;

// Función para mostrar el modal de operación en espera
function mostrarModalEspera(mensaje, ultimaOp, esperaHasta) {
    // Crear elementos del modal
    const modal = document.createElement('div');
    modal.id = 'modal-espera';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(2px);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    `;
    
    const contenido = document.createElement('div');
    contenido.style.cssText = `
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        max-width: 450px;
        width: 100%;
        padding: 24px;
        text-align: center;
        animation: slideDown 0.3s ease;
    `;
    
    const icono = document.createElement('i');
    icono.className = 'fas fa-hourglass-half';
    icono.style.cssText = `
        font-size: 48px;
        color: #ffc107;
        margin-bottom: 16px;
    `;
    
    const titulo = document.createElement('h3');
    titulo.textContent = '⏳ Operación en Espera';
    titulo.style.cssText = `
        margin: 0 0 16px 0;
        font-size: 20px;
        color: #333;
    `;
    
    const mensajeP = document.createElement('p');
    mensajeP.textContent = mensaje;
    mensajeP.style.cssText = `
        margin: 0 0 12px 0;
        color: #666;
        font-size: 15px;
    `;
    
    const lista = document.createElement('ul');
    lista.style.cssText = `
        text-align: left;
        margin: 0 0 20px 0;
        padding-left: 20px;
        color: #555;
        font-size: 14px;
    `;
    
    const item1 = document.createElement('li');
    item1.textContent = `Última operación: ${ultimaOp}`;
    
    const item2 = document.createElement('li');
    item2.textContent = esperaHasta 
        ? `Puede intentar después de: ${esperaHasta}` 
        : 'Por favor espere 5 minutos antes de intentar nuevamente';
    
    lista.appendChild(item1);
    lista.appendChild(item2);
    
    const boton = document.createElement('button');
    boton.textContent = 'Entendido';
    boton.style.cssText = `
        background: #ffc107;
        color: #333;
        border: none;
        padding: 12px 32px;
        font-size: 15px;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    `;
    boton.onmouseover = () => {
        boton.style.background = '#e0a800';
        boton.style.transform = 'translateY(-1px)';
    };
    boton.onmouseout = () => {
        boton.style.background = '#ffc107';
        boton.style.transform = 'translateY(0)';
    };
    boton.onclick = () => modal.remove();
    
    contenido.appendChild(icono);
    contenido.appendChild(titulo);
    contenido.appendChild(mensajeP);
    contenido.appendChild(lista);
    contenido.appendChild(boton);
    modal.appendChild(contenido);
    document.body.appendChild(modal);
    
    // Agregar animación
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideDown {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
    
    // Cerrar al hacer click en el overlay
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// Elementos del DOM
const btnIniciarScanner = document.getElementById('btnIniciarScanner');
const btnDetenerScanner = document.getElementById('btnDetenerScanner');
const scanResult = document.getElementById('scanResult');
const historialContainer = document.getElementById('historialContainer');
const estadisticasContainer = document.getElementById('estadisticasContainer');

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Verificar si Html5Qrcode está disponible
    if (typeof Html5Qrcode === 'undefined') {
        console.error('Html5Qrcode no está cargado. Asegúrate de incluir la librería.');
        return;
    }
    
    // Configurar botones
    if (btnIniciarScanner) {
        btnIniciarScanner.addEventListener('click', iniciarScanner);
    }
    if (btnDetenerScanner) {
        btnDetenerScanner.addEventListener('click', detenerScanner);
    }

    // Cargar historial inicial si existe
    cargarHistorialIngresos();

    // Auto-refresh cada 15 segundos para mantener el historial actualizado
    if (historialContainer) {
        // Verificar que AutoRefresh esté disponible
        if (typeof AutoRefresh === 'undefined') {
            console.error('AutoRefresh no está disponible. Verificar carga de components.js');
            return;
        }
        
        autoRefreshHistorial = new AutoRefresh({
            url: `/api/portero/ingresos-activos?limit=${HISTORIAL_POR_PAGINA}&page=1`,
            renderCallback: (data) => {
                // Normalizar datos: puede ser array directo o objeto con propiedad data
                let ingresos = [];
                let pagination = null;
                
                if (Array.isArray(data)) {
                    ingresos = data;
                } else if (data && Array.isArray(data.data)) {
                    ingresos = data.data;
                    pagination = data.pagination || null;
                }
                
                if (pagination) {
                    historialTotalRegistros = pagination.total || 0;
                    historialPaginaActual = pagination.page || 1;
                }
                
                if (ingresos.length > 0) {
                    historialIngresos = ingresos.map(ingreso => ({
                        id: ingreso.id,
                        marca: ingreso.marca,
                        numero_serial: ingreso.numero_serial,
                        aprendiz_nombre: ingreso.aprendiz_nombre,
                        aprendiz_apellido: ingreso.aprendiz_apellido,
                        tipo: ingreso.fecha_salida ? 'salida' : 'ingreso',
                        fecha: ingreso.fecha_ingreso || '',
                        hora: ingreso.hora_ingreso || '',
                        mensaje: 'Ingreso activo'
                    }));
                    actualizarHistorial();
                    actualizarEstadisticas();
                }
            },
            interval: 15000, // 15 segundos
            onError: (error) => {
                console.error('Error en auto-refresh del historial:', error);
            }
        });
    }
});

// Iniciar escáner
async function iniciarScanner() {
    try {
        html5QrCode = new Html5Qrcode("reader");
        
        const config = {
            fps: 10,
            qrbox: function(viewfinderWidth, viewfinderHeight) {
                // Usar 70% del ancho disponible, mínimo 250px, máximo 350px
                const minSize = 250;
                const maxSize = 350;
                const size = Math.min(Math.max(viewfinderWidth * 0.7, minSize), maxSize);
                return { width: size, height: size };
            },
            aspectRatio: 1.0
        };
        
        await html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanError
        );
        
        isScanning = true;
        if (btnIniciarScanner) btnIniciarScanner.style.display = 'none';
        if (btnDetenerScanner) btnDetenerScanner.style.display = 'inline-flex';
        
        mostrarMensaje('Escáner activo. Acerca el código QR del equipo a la cámara.', 'info');
        
    } catch (error) {
        console.error('Error iniciando escáner:', error);
        mostrarMensaje('No se pudo iniciar la cámara. Por favor verifica los permisos.', 'error');
    }
}

// Detener escáner
async function detenerScanner() {
    if (html5QrCode && isScanning) {
        try {
            await html5QrCode.stop();
            html5QrCode.clear();
            isScanning = false;
            if (btnIniciarScanner) btnIniciarScanner.style.display = 'inline-flex';
            if (btnDetenerScanner) btnDetenerScanner.style.display = 'none';
            mostrarMensaje('Escáner detenido', 'info');
        } catch (error) {
            console.error('Error deteniendo escáner:', error);
        }
    }
}

// Callback cuando se escanea exitosamente
async function onScanSuccess(decodedText, decodedResult) {
    const ahora = Date.now();
    
    // Evitar procesar el mismo QR múltiples veces (debounce de 2 segundos)
    if (ultimoQRProcesado === decodedText && (ahora - tiempoUltimoProcesamiento) < 2000) {
        return;
    }
    
    ultimoQRProcesado = decodedText;
    tiempoUltimoProcesamiento = ahora;
    
    console.log('QR Escaneado:', decodedText);
    
    // Procesar el QR sin detener el escáner
    await procesarQR(decodedText);
}

// Callback de errores de escaneo (no críticos)
function onScanError(errorMessage) {
    // Ignorar errores continuos de lectura (es normal cuando no hay QR visible)
}

// Procesar código QR escaneado
async function procesarQR(qrData) {
    try {
        mostrarMensaje('Procesando código QR...', 'info');
        
        const response = await fetch('/api/portero/procesar-qr', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                qr_data: qrData,
                observaciones: ''
            })
        });
        
        // Verificar si la respuesta es JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Respuesta no JSON:', text);
            mostrarMensaje('Error: El servidor no respondió correctamente', 'error');
            return;
        }
        
        const result = await response.json();
        
        // Verificar primero si es una respuesta de "hold" (operación en espera)
        // Esto funciona incluso cuando HTTP status es 400
        if (result.type === 'hold') {
            // Operación en espera por restricción de 5 minutos - mostrar como modal
            const esperaHasta = result.data?.en_espera_hasta || '';
            const ultimaOp = result.data?.ultima_operacion?.ultima_fecha_hora || 'desconocida';
            const mensaje = result.message || 'Debe esperar 5 minutos entre operaciones';
            
            // Usar el modal personalizado
            mostrarModalEspera(mensaje, ultimaOp, esperaHasta);
            return;
        }
        
        // Si la respuesta no fue exitosa, mostrar el mensaje de error
        if (!response.ok) {
            const errorMessage = result.message || `Error ${response.status}: ${response.statusText}`;
            mostrarMensaje(`<i class="fas fa-exclamation-circle"></i> ${errorMessage}`, 'error');
            console.error('Error del servidor:', result);
            return;
        }
        
        if (result.success) {
            const registro = result.data || {};
            
            // Añadir al historial
            historialIngresos.unshift({
                id: registro.id || Date.now(),
                equipo: registro.equipo || {},
                aprendiz: registro.aprendiz || {},
                tipo: registro.tipo || 'ingreso',
                fecha: registro.fecha || new Date().toLocaleDateString('es-CO'),
                hora: registro.hora || new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }),
                mensaje: result.message || 'Operación registrada correctamente'
            });
            
            const tipoTexto = registro.tipo === 'ingreso' ? 'INGRESO' : 'SALIDA';
            const nombreEquipo = registro.equipo?.marca || registro.marca || 'Equipo';
            const serialEquipo = registro.equipo?.numero_serial || registro.numero_serial || '';
            const nombreAprendiz = registro.aprendiz?.nombre || registro.aprendiz_nombre || 'Aprendiz';
            const apellidoAprendiz = registro.aprendiz?.apellido || registro.aprendiz_apellido || '';
            
            mostrarMensaje(
                `<i class="fas fa-check"></i> ${tipoTexto} registrado: ${nombreEquipo} (${serialEquipo}) - ${nombreAprendiz} ${apellidoAprendiz}`,
                'success'
            );
            
            actualizarHistorial();
            actualizarEstadisticas();
            
            // Reproducir sonido de éxito
            reproducirSonidoExito();
            
        } else {
            mostrarMensaje(`<i class="fas fa-xmark"></i> Error: ${result.message || 'Error al procesar el QR'}`, 'error');
        }
        
    } catch (error) {
        console.error('Error procesando QR:', error);
        mostrarMensaje('Error de conexión al procesar el QR. Por favor intenta nuevamente.', 'error');
    }
}

// Mostrar mensaje en el resultado del escaneo
function mostrarMensaje(mensaje, tipo) {
    if (!scanResult) return;
    
    const iconos = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'info': 'fa-info-circle',
        'warning': 'fa-hourglass-half'
    };
    
    scanResult.innerHTML = `
        <div class="scan-result-message ${tipo}">
            <i class="fas ${iconos[tipo] || 'fa-info-circle'}"></i>
            <span>${mensaje}</span>
        </div>
    `;
    
    // Auto-ocultar después de un tiempo
    setTimeout(() => {
        if (scanResult) {
            scanResult.innerHTML = '';
        }
    }, tipo === 'success' ? 3000 : 5000);
}

// Actualizar historial de ingresos con paginación
function actualizarHistorial() {
    if (!historialContainer) return;
    
    if (historialIngresos.length === 0) {
        historialContainer.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-qrcode"></i>
                <p>No hay registros aún. Escanea el código QR de un equipo para comenzar.</p>
            </div>
        `;
        // Ocultar paginación si no hay datos
        const pagNav = document.getElementById('historial-pagination');
        if (pagNav) pagNav.innerHTML = '';
        return;
    }
    
    // Calcular registros para la página actual
    const inicio = (historialPaginaActual - 1) * HISTORIAL_POR_PAGINA;
    const fin = inicio + HISTORIAL_POR_PAGINA;
    const registrosPagina = historialIngresos.slice(inicio, fin);
    const totalPaginas = Math.ceil(historialTotalRegistros / HISTORIAL_POR_PAGINA) || 1;
    
    const html = registrosPagina.map((registro, index) => {
        const tipoClass = registro.tipo === 'ingreso' ? 'success' : 'info';
        const tipoIcon = registro.tipo === 'ingreso' ? 'fa-sign-in-alt' : 'fa-sign-out-alt';
        const numeroGlobal = historialTotalRegistros - inicio - index;
        
        return `
            <div class="historial-item ${tipoClass}">
                <div class="historial-info">
                    <span class="historial-numero">#${numeroGlobal}</span>
                    <div class="historial-datos">
                        <strong>${escapeHtml(registro.marca || registro.equipo?.marca || 'Equipo')} - ${escapeHtml(registro.numero_serial || registro.equipo?.numero_serial || 'N/A')}</strong>
                        <span class="historial-doc">${escapeHtml(registro.aprendiz_nombre || registro.aprendiz?.nombre || '')} ${escapeHtml(registro.aprendiz_apellido || registro.aprendiz?.apellido || '')}</span>
                        ${registro.fecha ? `<span class="historial-fecha"><i class="fas fa-calendar"></i> ${registro.fecha}</span>` : ''}
                    </div>
                </div>
                <div class="historial-estado">
                    <span class="badge badge-${tipoClass}">
                        <i class="fas ${tipoIcon}"></i> ${registro.tipo?.toUpperCase() || 'OPERACIÓN'}
                    </span>
                    <span class="historial-hora"><i class="fas fa-clock"></i> ${registro.hora || '--'}</span>
                </div>
            </div>
        `;
    }).join('');
    
    historialContainer.innerHTML = html;
    
    // Renderizar controles de paginación
    renderizarPaginacion(totalPaginas);
}

// Renderizar controles de paginación del historial
function renderizarPaginacion(totalPaginas) {
    let pagNav = document.getElementById('historial-pagination');
    
    if (!pagNav) {
        pagNav = document.createElement('nav');
        pagNav.id = 'historial-pagination';
        pagNav.className = 'historial-pagination';
        pagNav.setAttribute('aria-label', 'Paginación del historial');
        historialContainer.parentNode.insertBefore(pagNav, historialContainer.nextSibling);
    }
    
    if (totalPaginas <= 1) {
        pagNav.innerHTML = '';
        return;
    }
    
    const mostrarDesde = (historialPaginaActual - 1) * HISTORIAL_POR_PAGINA + 1;
    const mostrarHasta = Math.min(historialPaginaActual * HISTORIAL_POR_PAGINA, historialTotalRegistros);
    
    let html = `<span class="pagination-info">Mostrando ${mostrarDesde}-${mostrarHasta} de ${historialTotalRegistros}</span>`;
    
    // Botón primera página
    if (historialPaginaActual > 1) {
        html += `<button type="button" class="pag-btn" data-page="1" aria-label="Primera página">&laquo;</button>`;
        html += `<button type="button" class="pag-btn" data-page="${historialPaginaActual - 1}" aria-label="Página anterior">&lsaquo;</button>`;
    } else {
        html += `<span class="pag-btn disabled">&laquo;</span>`;
        html += `<span class="pag-btn disabled">&lsaquo;</span>`;
    }
    
    // Páginas cercanas
    const rango = 1;
    const inicio = Math.max(1, historialPaginaActual - rango);
    const fin = Math.min(totalPaginas, historialPaginaActual + rango);
    
    for (let p = inicio; p <= fin; p++) {
        if (p === historialPaginaActual) {
            html += `<span class="pag-btn active">${p}</span>`;
        } else {
            html += `<button type="button" class="pag-btn" data-page="${p}">${p}</button>`;
        }
    }
    
    // Botón siguiente y última
    if (historialPaginaActual < totalPaginas) {
        html += `<button type="button" class="pag-btn" data-page="${historialPaginaActual + 1}" aria-label="Página siguiente">&rsaquo;</button>`;
        html += `<button type="button" class="pag-btn" data-page="${totalPaginas}" aria-label="Última página">&raquo;</button>`;
    } else {
        html += `<span class="pag-btn disabled">&rsaquo;</span>`;
        html += `<span class="pag-btn disabled">&raquo;</span>`;
    }
    
    pagNav.innerHTML = html;
    
    // Agregar event listeners a los botones
    pagNav.querySelectorAll('.pag-btn[data-page]').forEach(btn => {
        btn.addEventListener('click', () => {
            const pagina = parseInt(btn.dataset.page);
            if (pagina !== historialPaginaActual) {
                irAPagina(pagina);
            }
        });
    });
}

// Navegar a una página específica del historial
async function irAPagina(pagina) {
    if (historialCargando) return;
    historialCargando = true;
    
    historialPaginaActual = pagina;
    mostrarCargandoHistorial(true);
    
    try {
        const response = await fetch(`/api/portero/ingresos-activos?limit=${HISTORIAL_POR_PAGINA}&page=${pagina}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const result = await response.json();
        
        if (result.success && result.data && Array.isArray(result.data)) {
            historialIngresos = result.data.map(ingreso => ({
                id: ingreso.id,
                marca: ingreso.marca,
                numero_serial: ingreso.numero_serial,
                aprendiz_nombre: ingreso.aprendiz_nombre,
                aprendiz_apellido: ingreso.aprendiz_apellido,
                tipo: ingreso.fecha_salida ? 'salida' : 'ingreso',
                fecha: ingreso.fecha_ingreso || '',
                hora: ingreso.hora_ingreso || '',
                mensaje: 'Ingreso activo'
            }));
            
            if (result.pagination) {
                historialTotalRegistros = result.pagination.total || 0;
            }
            
            actualizarHistorial();
        }
    } catch (error) {
        console.error('Error cargando página del historial:', error);
    } finally {
        historialCargando = false;
        mostrarCargandoHistorial(false);
    }
}

// Mostrar/ocultar indicador de carga del historial
function mostrarCargandoHistorial(mostrar) {
    if (!historialContainer) return;
    
    if (mostrar) {
        historialContainer.innerHTML = `
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Cargando historial...</p>
            </div>
        `;
    }
}

// Actualizar estadísticas
function actualizarEstadisticas() {
    if (!estadisticasContainer) return;
    
    const total = historialIngresos.length;
    const ingresos = historialIngresos.filter(r => r.tipo === 'ingreso').length;
    const salidas = historialIngresos.filter(r => r.tipo === 'salida').length;
    
    estadisticasContainer.innerHTML = `
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Total Operaciones</span>
                <span class="stat-value">${total}</span>
            </div>
            <div class="stat-item success">
                <span class="stat-label">Ingresos</span>
                <span class="stat-value">${ingresos}</span>
            </div>
            <div class="stat-item info">
                <span class="stat-label">Salidas</span>
                <span class="stat-value">${salidas}</span>
            </div>
        </div>
    `;
}

// Cargar historial de ingresos desde el servidor
async function cargarHistorialIngresos() {
    try {
        mostrarCargandoHistorial(true);
        
        const response = await fetch(`/api/portero/ingresos-activos?limit=${HISTORIAL_POR_PAGINA}&page=1`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.data && Array.isArray(result.data)) {
            // Convertir ingresos activos a formato de historial
            historialIngresos = result.data.map(ingreso => ({
                id: ingreso.id,
                marca: ingreso.marca,
                numero_serial: ingreso.numero_serial,
                aprendiz_nombre: ingreso.aprendiz_nombre,
                aprendiz_apellido: ingreso.aprendiz_apellido,
                tipo: ingreso.fecha_salida ? 'salida' : 'ingreso',
                fecha: ingreso.fecha_ingreso || '',
                hora: ingreso.hora_ingreso || '',
                mensaje: 'Ingreso activo'
            }));
            
            if (result.pagination) {
                historialTotalRegistros = result.pagination.total || 0;
                historialPaginaActual = result.pagination.page || 1;
            }
            
            actualizarHistorial();
            actualizarEstadisticas();
        }
    } catch (error) {
        console.error('Error cargando historial:', error);
    } finally {
        mostrarCargandoHistorial(false);
    }
}

// Reproducir sonido de éxito
function reproducirSonidoExito() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.1);
    } catch (e) {
        // Silenciar errores de audio
    }
}

// Limpiar al salir
window.addEventListener('beforeunload', () => {
    if (isScanning) {
        detenerScanner();
    }
});

/**
 * Escapa caracteres HTML para prevenir XSS
 * @param {string} text 
 * @returns {string}
 */
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
