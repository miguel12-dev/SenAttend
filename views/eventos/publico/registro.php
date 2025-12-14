<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - <?= htmlspecialchars($evento['titulo']) ?> | SENAttend</title>
    <link rel="stylesheet" href="<?= asset('css/eventos/publico.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="registro-page">
    <header class="registro-header">
        <a href="/eventos" class="back-link">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
            </svg>
            <span>Volver a eventos</span>
        </a>
    </header>

    <main class="registro-main">
        <div class="registro-container">
            <!-- Info del evento -->
            <div class="evento-info">
                <div class="evento-image" <?php if ($evento['imagen_url']): ?>style="background-image: url('<?= htmlspecialchars($evento['imagen_url']) ?>')"<?php endif; ?>>
                    <div class="evento-overlay">
                        <span class="event-badge badge-<?= $evento['estado'] ?>">
                            <?= $evento['estado'] === 'en_curso' ? 'En curso' : 'Próximamente' ?>
                        </span>
                    </div>
                </div>
                <div class="evento-details">
                    <h1><?= htmlspecialchars($evento['titulo']) ?></h1>
                    <?php if ($evento['descripcion']): ?>
                    <p><?= nl2br(htmlspecialchars($evento['descripcion'])) ?></p>
                    <?php endif; ?>
                    <div class="evento-meta">
                        <div class="meta-row">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/>
                            </svg>
                            <div>
                                <strong>Fecha</strong>
                                <span><?= date('d \d\e F, Y', strtotime($evento['fecha_inicio'])) ?></span>
                            </div>
                        </div>
                        <div class="meta-row">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
                            </svg>
                            <div>
                                <strong>Horario</strong>
                                <span><?= date('h:i A', strtotime($evento['fecha_inicio'])) ?> - <?= date('h:i A', strtotime($evento['fecha_fin'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de registro -->
            <div class="registro-form-container">
                <div class="form-header">
                    <h2>Registrarse al evento</h2>
                    <p>Ingresa tu número de documento para verificar tu información</p>
                </div>

                <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endif; ?>

                <!-- Paso 1: Buscar documento -->
                <div id="step1" class="form-step active">
                    <div class="form-group">
                        <label for="documento">Número de Documento</label>
                        <div class="input-group">
                            <input type="text" id="documento" name="documento" 
                                   placeholder="Ingresa tu número de documento" 
                                   pattern="[0-9]{6,15}" required
                                   value="<?= htmlspecialchars($datos['documento'] ?? '') ?>">
                            <button type="button" id="btnBuscar" class="btn-search">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                                </svg>
                                <span>Buscar</span>
                            </button>
                        </div>
                        <small class="form-help">Ingresa tu documento para verificar si estás registrado en el sistema</small>
                    </div>
                    <div id="loadingIndicator" class="loading" style="display: none;">
                        <div class="spinner"></div>
                        <span>Buscando...</span>
                    </div>
                </div>

                <!-- Paso 2: Formulario completo -->
                <form id="registroForm" action="/eventos/registro/<?= $evento['id'] ?>" method="POST" class="form-step" style="display: none;">
                    <input type="hidden" name="documento" id="formDocumento">
                    <input type="hidden" name="tipo" id="formTipo" value="externo">

                    <div id="instructorInfo" class="info-box" style="display: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                        </svg>
                        <span>¡Te encontramos! Tus datos han sido completados automáticamente.</span>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombres <span class="required">*</span></label>
                            <input type="text" id="nombre" name="nombre" required
                                   value="<?= htmlspecialchars($datos['nombre'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="apellido">Apellidos <span class="required">*</span></label>
                            <input type="text" id="apellido" name="apellido" required
                                   value="<?= htmlspecialchars($datos['apellido'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Correo Electrónico <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required
                               placeholder="tu.correo@ejemplo.com"
                               value="<?= htmlspecialchars($datos['email'] ?? '') ?>">
                        <small class="form-help">El código QR de asistencia será enviado a este correo</small>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="btnVolver" class="btn-secondary">Volver</button>
                        <button type="submit" class="btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                            </svg>
                            <span>Confirmar Registro</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Modal de éxito -->
    <div id="successModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-icon success">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                </svg>
            </div>
            <h3>¡Registro Exitoso!</h3>
            <p>El código QR para tu ingreso ha sido enviado a:</p>
            <p class="email-masked" id="emailMasked"></p>
            <p class="modal-note">Presenta el código QR en la entrada del evento</p>
            <a href="/eventos" class="btn-primary">Volver a eventos</a>
        </div>
    </div>

    <script src="<?= asset('js/eventos/registro.js') ?>"></script>
    <script>
        const eventoId = <?= $evento['id'] ?>;
    </script>
</body>
</html>

