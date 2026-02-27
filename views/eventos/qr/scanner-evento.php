<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escáner QR - <?= htmlspecialchars($evento['titulo']) ?></title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/eventos/admin.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/eventos/scanner.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'eventos-scanner';
        require __DIR__ . '/../../components/header-eventos.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <div class="page-header">
                    <a href="/eventos/admin/<?= $evento['id'] ?>" class="btn-back">
                        <i class="fas fa-arrow-left"></i>
                        Volver al Evento
                    </a>
                    <h2>
                        <i class="fas fa-qrcode"></i>
                        Escáner QR - <?= htmlspecialchars($evento['titulo']) ?>
                    </h2>
                </div>

                <div class="scanner-wrapper">
                    <div class="scanner-header-info">
                        <div class="evento-titulo">
                            <i class="fas fa-calendar-check"></i>
                            <?= htmlspecialchars($evento['titulo']) ?>
                        </div>
                        <h1><i class="fas fa-qrcode"></i> Escanear Código QR</h1>
                        <p>Escanea el código QR del participante para registrar ingreso o salida automáticamente</p>
                    </div>

                    <div class="scanner-status">
                        <h3><i class="fas fa-magic"></i> Escáner Inteligente Activo</h3>
                        <p>Detecta automáticamente si es entrada o salida según el contexto del participante</p>
                    </div>

                    <!-- Último Escaneo -->
                    <div id="ultimoEscaneo" class="ultimo-escaneo">
                        <h4><i class="fas fa-check-circle"></i> Última operación registrada:</h4>
                        <p><strong>Participante:</strong> <span id="ultimoNombre"></span></p>
                        <p><strong>Tipo:</strong> <span id="ultimoTipo"></span></p>
                        <p><strong>Fecha/Hora:</strong> <span id="ultimoHora"></span></p>
                    </div>

                    <!-- Escáner QR -->
                    <div class="scanner-card">
                        <div class="scanner-container">
                            <div id="reader"></div>
                            <div id="scanResult" class="scan-result"></div>
                            <div class="scanner-controls">
                                <button type="button" id="btnIniciarScanner" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Iniciar Escáner
                                </button>
                                <button type="button" id="btnDetenerScanner" class="btn btn-danger" style="display: none;">
                                    <i class="fas fa-stop"></i> Detener
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="estadisticas-resumen" id="estadisticasContainer">
                        <div class="stat-card total">
                            <div class="stat-label">Total Operaciones</div>
                            <div class="stat-value" id="statTotal">0</div>
                        </div>
                        <div class="stat-card entradas">
                            <div class="stat-label">Ingresos Registrados</div>
                            <div class="stat-value" id="statEntradas">0</div>
                        </div>
                        <div class="stat-card salidas">
                            <div class="stat-label">Salidas Registradas</div>
                            <div class="stat-value" id="statSalidas">0</div>
                        </div>
                    </div>

                    <!-- Tablas de Entradas y Salidas -->
                    <div class="tablas-asistencia">
                        <!-- Tabla de Entradas -->
                        <div class="tabla-section entradas">
                            <h3>
                                <i class="fas fa-sign-in-alt"></i>
                                Entradas
                                <span class="tabla-count" id="countEntradas">0</span>
                            </h3>
                            <div id="listaEntradas">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>No hay entradas registradas aún</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de Salidas -->
                        <div class="tabla-section salidas">
                            <h3>
                                <i class="fas fa-sign-out-alt"></i>
                                Salidas
                                <span class="tabla-count" id="countSalidas">0</span>
                            </h3>
                            <div id="listaSalidas">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>No hay salidas registradas aún</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
    </div>

    <!-- Librería html5-qrcode -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        /**
         * JavaScript para escaneo continuo de QR de eventos específicos
         * Detecta automáticamente si es entrada o salida
         * Muestra historial COMPLETO del evento (no solo del día)
         */
        
        const EVENTO_ID = <?= $evento['id'] ?>;
        let html5QrCode = null;
        let isScanning = false;
        let historialEntradas = [];
        let historialSalidas = [];
        let ultimoQRProcesado = null;
        let tiempoUltimoProcesamiento = 0;
        
        // Elementos del DOM
        const btnIniciarScanner = document.getElementById('btnIniciarScanner');
        const btnDetenerScanner = document.getElementById('btnDetenerScanner');
        const scanResult = document.getElementById('scanResult');
        const listaEntradas = document.getElementById('listaEntradas');
        const listaSalidas = document.getElementById('listaSalidas');
        const ultimoEscaneo = document.getElementById('ultimoEscaneo');
        
        // Inicialización cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', () => {
            // Verificar si Html5Qrcode está disponible
            if (typeof Html5Qrcode === 'undefined') {
                console.error('Html5Qrcode no está cargado. Asegúrate de incluir la librería.');
                mostrarMensaje('Error: Librería de escaneo no disponible', 'error');
                return;
            }
            
            // Configurar botones
            if (btnIniciarScanner) {
                btnIniciarScanner.addEventListener('click', iniciarScanner);
            }
            if (btnDetenerScanner) {
                btnDetenerScanner.addEventListener('click', detenerScanner);
            }
            
            // Cargar historial completo del evento
            cargarHistorial();
        });
        
        // Iniciar escáner
        async function iniciarScanner() {
            try {
                html5QrCode = new Html5Qrcode("reader");
                
                const config = {
                    fps: 10,
                    qrbox: function(viewfinderWidth, viewfinderHeight) {
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
                
                mostrarMensaje('Escáner activo. Acerca el código QR del participante a la cámara.', 'info');
                
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

            // Procesar el QR sin detener el escáner
            await procesarQR(decodedText);
        }
        
        // Callback de errores de escaneo (no críticos)
        function onScanError(errorMessage) {
            // Ignorar errores continuos de lectura (es normal cuando no hay QR visible)
        }
        
        // Procesar código QR escaneado
        async function procesarQR(token) {
            try {
                mostrarMensaje('Procesando código QR...', 'info');
                
                const response = await fetch('/eventos/qr/procesar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        token: token,
                        evento_id: EVENTO_ID
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
                
                // Si la respuesta no fue exitosa, mostrar el mensaje de error
                if (!response.ok) {
                    const errorMessage = result.error || result.message || `Error ${response.status}`;
                    mostrarMensaje(`<i class="fas fa-exclamation-circle"></i> ${errorMessage}`, 'error');
                    console.error('Error del servidor:', result);
                    return;
                }
                
                if (result.success) {
                    const data = result.data || {};
                    const tipo = result.message && result.message.includes('Salida') ? 'salida' : 'ingreso';
                    
                    // Crear registro
                    const registro = {
                        id: data.participante_id || Date.now(),
                        nombre: data.nombre || 'Participante',
                        apellido: data.apellido || '',
                        documento: data.documento || 'N/A',
                        evento: '<?= htmlspecialchars($evento['titulo']) ?>',
                        tipo: tipo,
                        fecha: new Date().toLocaleDateString('es-CO'),
                        hora: new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
                    };
                    
                    // Añadir al historial correspondiente (al inicio para orden cronológico)
                    if (tipo === 'ingreso') {
                        historialEntradas.unshift(registro);
                    } else {
                        historialSalidas.unshift(registro);
                    }
                    
                    const tipoTexto = tipo === 'ingreso' ? 'INGRESO' : 'SALIDA';
                    const nombreCompleto = `${registro.nombre} ${registro.apellido}`;
                    
                    mostrarMensaje(
                        `<i class="fas fa-check"></i> ${tipoTexto} registrado: ${nombreCompleto}`,
                        'success'
                    );
                    
                    // Mostrar último escaneo
                    mostrarUltimoEscaneo(registro);
                    
                    // Actualizar tablas y estadísticas
                    actualizarTablas();
                    actualizarEstadisticas();
                    
                    // Reproducir sonido de éxito
                    reproducirSonidoExito();
                    
                } else {
                    mostrarMensaje(`<i class="fas fa-xmark"></i> Error: ${result.error || 'Error al procesar el QR'}`, 'error');
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
                'info': 'fa-info-circle'
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
        
        // Mostrar último escaneo
        function mostrarUltimoEscaneo(registro) {
            if (!ultimoEscaneo) return;
            
            document.getElementById('ultimoNombre').textContent = `${registro.nombre} ${registro.apellido}`;
            document.getElementById('ultimoTipo').textContent = registro.tipo.toUpperCase();
            document.getElementById('ultimoHora').textContent = `${registro.fecha} ${registro.hora}`;
            
            ultimoEscaneo.classList.add('show');
        }
        
        // Actualizar tablas de entradas y salidas
        function actualizarTablas() {
            // Actualizar contador de entradas
            document.getElementById('countEntradas').textContent = historialEntradas.length;
            
            // Actualizar lista de entradas
            if (historialEntradas.length === 0) {
                listaEntradas.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No hay entradas registradas aún</p>
                    </div>
                `;
            } else {
                const htmlEntradas = historialEntradas.map(registro => `
                    <div class="registro-item">
                        <div class="registro-nombre">${registro.nombre} ${registro.apellido}</div>
                        <div class="registro-documento">Doc: ${registro.documento}</div>
                        <div class="registro-info">
                            <span>${registro.fecha}</span>
                            <span class="registro-hora">${registro.hora}</span>
                        </div>
                    </div>
                `).join('');
                listaEntradas.innerHTML = htmlEntradas;
            }
            
            // Actualizar contador de salidas
            document.getElementById('countSalidas').textContent = historialSalidas.length;
            
            // Actualizar lista de salidas
            if (historialSalidas.length === 0) {
                listaSalidas.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No hay salidas registradas aún</p>
                    </div>
                `;
            } else {
                const htmlSalidas = historialSalidas.map(registro => `
                    <div class="registro-item">
                        <div class="registro-nombre">${registro.nombre} ${registro.apellido}</div>
                        <div class="registro-documento">Doc: ${registro.documento}</div>
                        <div class="registro-info">
                            <span>${registro.fecha}</span>
                            <span class="registro-hora">${registro.hora}</span>
                        </div>
                    </div>
                `).join('');
                listaSalidas.innerHTML = htmlSalidas;
            }
        }
        
        // Actualizar estadísticas
        function actualizarEstadisticas() {
            const total = historialEntradas.length + historialSalidas.length;
            
            document.getElementById('statTotal').textContent = total;
            document.getElementById('statEntradas').textContent = historialEntradas.length;
            document.getElementById('statSalidas').textContent = historialSalidas.length;
        }
        
        // Cargar historial completo del evento desde el servidor
        async function cargarHistorial() {
            try {
                const response = await fetch(`/eventos/qr/historial-evento/${EVENTO_ID}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    console.error('Error cargando historial:', response.status);
                    actualizarTablas();
                    actualizarEstadisticas();
                    return;
                }
                
                const result = await response.json();
                
                if (result.success && result.data) {
                    // Separar entradas y salidas (ya vienen ordenadas cronológicamente)
                    historialEntradas = result.data.filter(r => r.tipo === 'ingreso');
                    historialSalidas = result.data.filter(r => r.tipo === 'salida');
                    
                    actualizarTablas();
                    actualizarEstadisticas();
                }
            } catch (error) {
                console.error('Error cargando historial:', error);
                actualizarTablas();
                actualizarEstadisticas();
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
    </script>
    
    <!-- Script para el menú móvil -->
    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
