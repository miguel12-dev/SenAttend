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
    <style>
        .scanner-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem 0;
        }
        
        .scanner-header-info {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .scanner-header-info h1 {
            font-size: 1.75rem;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
        }
        
        .scanner-header-info .evento-titulo {
            font-size: 1.25rem;
            color: #39A900;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .scanner-header-info p {
            color: #666;
        }
        
        .scanner-status {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #39A900;
        }
        
        .scanner-status h3 {
            font-size: 1rem;
            color: #39A900;
            margin: 0;
            font-weight: 600;
        }
        
        .scanner-status p {
            margin: 0.5rem 0 0 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .scanner-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .scanner-container {
            text-align: center;
            padding: 1.5rem 0;
            width: 100%;
        }
        
        #reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        #reader video,
        #reader canvas {
            width: 100% !important;
            height: auto !important;
            max-width: 500px;
            display: block;
        }
        
        .scan-result {
            margin: 1.5rem auto;
            max-width: 500px;
            min-height: 50px;
        }
        
        .scan-result-message {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .scan-result-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .scan-result-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .scan-result-message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .scan-result-message i {
            font-size: 1.2rem;
        }
        
        .scanner-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #39A900 0%, #2d8a00 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(57, 169, 0, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        
        .ultimo-escaneo {
            background: #f0fff4;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: none;
        }
        
        .ultimo-escaneo.show {
            display: block;
        }
        
        .ultimo-escaneo h4 {
            margin: 0 0 0.5rem 0;
            color: #39A900;
            font-size: 1rem;
        }
        
        .ultimo-escaneo p {
            margin: 0.25rem 0;
            color: #333;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            #reader {
                max-width: 100%;
            }
        }
    </style>
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
    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
        /**
         * JavaScript para escaneo continuo de QR de eventos específicos
         * Detecta automáticamente si es entrada o salida
         */
        
        const EVENTO_ID = <?= $evento['id'] ?>;
        let html5QrCode = null;
        let isScanning = false;
        let ultimoQRProcesado = null;
        let tiempoUltimoProcesamiento = 0;
        
        // Elementos del DOM
        const btnIniciarScanner = document.getElementById('btnIniciarScanner');
        const btnDetenerScanner = document.getElementById('btnDetenerScanner');
        const scanResult = document.getElementById('scanResult');
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
            
            console.log('QR Escaneado:', decodedText);
            
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
                        tipo: tipo,
                        fecha: new Date().toLocaleDateString('es-CO'),
                        hora: new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
                    };
                    
                    const tipoTexto = tipo === 'ingreso' ? 'INGRESO' : 'SALIDA';
                    const nombreCompleto = `${registro.nombre} ${registro.apellido}`;
                    
                    mostrarMensaje(
                        `<i class="fas fa-check"></i> ${tipoTexto} registrado: ${nombreCompleto}`,
                        'success'
                    );
                    
                    // Mostrar último escaneo
                    mostrarUltimoEscaneo(registro);
                    
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
</body>
</html>

