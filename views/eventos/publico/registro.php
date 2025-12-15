<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - <?= htmlspecialchars($evento['titulo']) ?></title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components/header-public.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/eventos/publico.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php require __DIR__ . '/../../components/header-eventos-publico.php'; ?>

        <main class="main-content">
            <div class="container">
                <div class="page-header" style="margin-bottom: 2rem;">
                    <a href="/eventos" class="btn-back">
                        <i class="fas fa-arrow-left"></i>
                        Volver a eventos
                    </a>
                </div>

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
                            <i class="fas fa-calendar"></i>
                            <div>
                                <strong>Fecha</strong>
                                <span><?= date('d \d\e F, Y', strtotime($evento['fecha_inicio'])) ?></span>
                            </div>
                        </div>
                        <div class="meta-row">
                            <i class="fas fa-clock"></i>
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
                <div class="alert alert-<?= !empty($datos['_duplicado']) ? 'warning' : 'error' ?>">
                    <i class="fas fa-exclamation-circle"></i>
                    <div style="flex: 1;">
                        <span><?= htmlspecialchars($error) ?></span>
                        <?php if (!empty($datos['_duplicado']) && !empty($datos['_email_enmascarado'])): ?>
                        <div style="margin-top: 0.75rem;">
                            <button type="button" id="btnReenviarQR" class="btn-reenviar" data-documento="<?= htmlspecialchars($datos['documento'] ?? '') ?>">
                                <i class="fas fa-paper-plane"></i>
                                <span>Reenviar código QR a <?= htmlspecialchars($datos['_email_enmascarado']) ?></span>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
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
                            <button type="button" id="btnBuscar" class="btn btn-primary btn-search">
                                <i class="fas fa-search"></i>
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
                        <button type="button" id="btnVolver" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Volver
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i>
                            Confirmar Registro
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
    </div>

    <!-- Modal de éxito -->
    <div id="successModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>¡Registro Exitoso!</h3>
            <p>El código QR para tu ingreso ha sido enviado a:</p>
            <p class="email-masked" id="emailMasked"></p>
            <p class="modal-note">Presenta el código QR en la entrada del evento</p>
            <a href="/eventos" class="btn btn-primary">Volver a eventos</a>
        </div>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/common/header-public.js') ?>"></script>
    <script>
        // IMPORTANTE: Definir eventoId ANTES de cargar registro.js
        const eventoId = <?= $evento['id'] ?>;
    </script>
    <script src="<?= asset('js/eventos/registro.js') ?>"></script>
</body>
</html>

