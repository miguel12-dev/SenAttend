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
<body data-evento-id="<?= $evento['id'] ?>">
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

                <!-- Paso 2A: Instructor encontrado -->
                <form id="registroFormInstructor" action="/eventos/registro/<?= $evento['id'] ?>" method="POST" class="form-step" style="display: none;">
                    <input type="hidden" name="documento" id="formDocumentoInstructor">
                    <input type="hidden" name="tipo" value="instructor">
                    <input type="hidden" name="nombre" id="formNombreInstructor">
                    <input type="hidden" name="apellido" value="">
                    <input type="hidden" name="email" id="formEmailInstructorOriginal">

                    <div class="info-box success" style="display: flex; margin-bottom: 1.5rem;">
                        <i class="fas fa-check-circle" style="margin-right: 0.75rem; color: #28a745;"></i>
                        <span>¡Te encontramos en el sistema como instructor!</span>
                    </div>

                    <div class="form-group">
                        <label>Nombre</label>
                        <div class="info-display" id="displayNombreInstructor" style="padding: 0.75rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
                            <!-- Se llenará con JavaScript -->
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div class="info-display" id="displayEmailEnmascarado" style="flex: 1; padding: 0.75rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
                                <!-- Se llenará con JavaScript -->
                            </div>
                            <button type="button" id="btnCambiarEmail" class="btn btn-secondary" style="white-space: nowrap;">
                                <i class="fas fa-edit"></i>
                                Cambiar
                            </button>
                        </div>
                        <small class="form-help">El código QR será enviado a este correo</small>
                    </div>

                    <div id="emailEditContainer" style="display: none;">
                        <div class="form-group">
                            <label for="emailInstructor">Nuevo Correo Electrónico <span class="required">*</span></label>
                            <input type="email" id="emailInstructor" name="email" required
                                   placeholder="tu.correo@ejemplo.com">
                            <small class="form-help">Ingresa el correo donde deseas recibir el código QR</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="btnVolverInstructor" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Volver
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i>
                            Confirmar Registro
                        </button>
                    </div>
                </form>

                <!-- Paso 2B: Formulario completo (no instructor) -->
                <form id="registroForm" action="/eventos/registro/<?= $evento['id'] ?>" method="POST" class="form-step" style="display: none;">
                    <input type="hidden" name="documento" id="formDocumento">
                    <input type="hidden" name="tipo" id="formTipo" value="externo">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre Completo <span class="required">*</span></label>
                            <input type="text" id="nombre" name="nombre" required
                                   placeholder="Ingresa tu nombre completo"
                                   value="<?= htmlspecialchars($datos['nombre'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="apellido">Apellidos <span class="required">*</span></label>
                            <input type="text" id="apellido" name="apellido" required
                                   placeholder="Ingresa tus apellidos"
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
    <script>
        // IMPORTANTE: Definir eventoId ANTES de cargar registro.js (y exponerlo en dataset)
        window.eventoId = <?= $evento['id'] ?>;
        document.body.dataset.eventoId = String(window.eventoId);
    </script>
    <script src="<?= asset('js/eventos/registro.js') ?>"></script>
</body>
</html>

