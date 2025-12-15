<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escáner QR - Eventos</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/eventos/admin.css') ?>">
    <style>
        .scanner-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .scanner-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .scanner-header h1 {
            font-size: 1.75rem;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
        }
        
        .scanner-header p {
            color: #666;
        }
        
        .scanner-input {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .scanner-input .form-group {
            margin-bottom: 1.5rem;
        }
        
        .scanner-input label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .scanner-input input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1.1rem;
            text-align: center;
            letter-spacing: 2px;
            transition: all 0.3s ease;
        }
        
        .scanner-input input:focus {
            outline: none;
            border-color: #39A900;
            box-shadow: 0 0 0 4px rgba(57, 169, 0, 0.1);
        }
        
        .scanner-actions {
            display: flex;
            gap: 1rem;
        }
        
        .scanner-actions button {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-scan {
            background: linear-gradient(135deg, #39A900 0%, #2d8a00 100%);
            color: white;
        }
        
        .btn-scan:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(57, 169, 0, 0.3);
        }
        
        .btn-clear {
            background: #f5f5f5;
            color: #666;
        }
        
        .btn-clear:hover {
            background: #e0e0e0;
        }
        
        .result-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            text-align: center;
            display: none;
        }
        
        .result-card.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        .result-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .result-icon svg {
            width: 40px;
            height: 40px;
        }
        
        .result-icon.success {
            background: #e8f5e9;
            color: #39A900;
        }
        
        .result-icon.error {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .result-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .result-card.success h3 { color: #39A900; }
        .result-card.error h3 { color: #d32f2f; }
        
        .result-details {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
        }
        
        .result-details p {
            margin: 0.5rem 0;
            display: flex;
            justify-content: space-between;
        }
        
        .result-details strong {
            color: #333;
        }
        
        .result-note {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-top: 1rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .loading-overlay.show {
            display: flex;
        }
        
        .loading-content {
            text-align: center;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #e0e0e0;
            border-top-color: #39A900;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
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
                    <a href="/eventos/admin" class="btn-back">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                    <h2>
                        <i class="fas fa-qrcode"></i>
                        Escáner QR de Eventos
                    </h2>
                </div>

                <div class="scanner-container">
            <div class="scanner-header">
                <h1>Validar Código QR</h1>
                <p>Ingresa o escanea el código QR del participante</p>
            </div>

            <div class="scanner-input">
                <div class="form-group">
                    <label for="qrToken">Código QR / Token</label>
                    <input type="text" id="qrToken" placeholder="Ingresa el código QR..." autofocus>
                </div>
                <div class="scanner-actions">
                    <button type="button" class="btn-clear" onclick="limpiar()">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
                        </svg>
                        Limpiar
                    </button>
                    <button type="button" class="btn-scan" onclick="procesarQR()">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                            <path d="M9.5 6.5v3h-3v-3h3M11 5H5v6h6V5zm-1.5 9.5v3h-3v-3h3M11 13H5v6h6v-6zm6.5-6.5v3h-3v-3h3M19 5h-6v6h6V5zm-6 8h1.5v1.5H13V13zm1.5 1.5H16V16h-1.5v-1.5zM16 13h1.5v1.5H16V13z"/>
                        </svg>
                        Procesar
                    </button>
                </div>
            </div>

            <!-- Resultado Exitoso -->
            <div id="resultSuccess" class="result-card success">
                <div class="result-icon success">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                    </svg>
                </div>
                <h3 id="resultTitle">¡Ingreso Registrado!</h3>
                <div class="result-details">
                    <p><strong>Participante:</strong> <span id="resultNombre"></span></p>
                    <p><strong>Evento:</strong> <span id="resultEvento"></span></p>
                    <p><strong>Email:</strong> <span id="resultEmail"></span></p>
                </div>
                <div id="qrSalidaNote" class="result-note">
                    El código QR de salida ha sido enviado al correo del participante.
                </div>
            </div>

            <!-- Resultado Error -->
            <div id="resultError" class="result-card error">
                <div class="result-icon error">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                </div>
                <h3>Error</h3>
                <p id="errorMessage"></p>
                </div>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p>Procesando código QR...</p>
        </div>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
        const tokenInput = document.getElementById('qrToken');
        const resultSuccess = document.getElementById('resultSuccess');
        const resultError = document.getElementById('resultError');
        const loadingOverlay = document.getElementById('loadingOverlay');

        // Auto-procesar al presionar Enter
        tokenInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                procesarQR();
            }
        });

        function limpiar() {
            tokenInput.value = '';
            resultSuccess.classList.remove('show');
            resultError.classList.remove('show');
            tokenInput.focus();
        }

        async function procesarQR() {
            const token = tokenInput.value.trim();
            
            if (!token) {
                mostrarError('Por favor, ingresa un código QR');
                return;
            }

            loadingOverlay.classList.add('show');
            resultSuccess.classList.remove('show');
            resultError.classList.remove('show');

            try {
                const response = await fetch('/eventos/qr/procesar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ token: token })
                });

                const data = await response.json();

                if (data.success) {
                    mostrarExito(data);
                } else {
                    mostrarError(data.error || 'Error desconocido');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión. Intenta nuevamente.');
            } finally {
                loadingOverlay.classList.remove('show');
            }
        }

        function mostrarExito(data) {
            const isIngreso = data.message && data.message.includes('Ingreso');
            
            document.getElementById('resultTitle').textContent = 
                isIngreso ? '¡Ingreso Registrado!' : '¡Salida Registrada!';
            
            document.getElementById('resultNombre').textContent = 
                data.data.nombre + ' ' + data.data.apellido;
            document.getElementById('resultEvento').textContent = data.data.evento_titulo;
            document.getElementById('resultEmail').textContent = data.data.email;
            
            document.getElementById('qrSalidaNote').style.display = 
                data.qr_salida_enviado ? 'block' : 'none';
            
            resultSuccess.classList.add('show');
            
            // Limpiar input después de 3 segundos
            setTimeout(limpiar, 5000);
        }

        function mostrarError(message) {
            document.getElementById('errorMessage').textContent = message;
            resultError.classList.add('show');
        }

        // Mantener foco en el input
        tokenInput.focus();
    </script>
</body>
</html>

