<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Evento - Gestión de Eventos | SENAttend</title>
    <link rel="stylesheet" href="<?= asset('css/eventos/admin.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="/eventos/admin" class="back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                </svg>
            </a>
            <span>Crear Nuevo Evento</span>
        </div>
        <div class="navbar-user">
            <span class="user-name"><?= htmlspecialchars($user['nombre']) ?></span>
            <a href="/eventos/logout" class="btn-logout" title="Cerrar sesión">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                </svg>
            </a>
        </div>
    </nav>

    <main class="main-content">
        <div class="form-container">
            <div class="form-header">
                <h1>Crear Nuevo Evento</h1>
                <p>Completa la información del evento</p>
            </div>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form action="/eventos/admin/crear" method="POST" enctype="multipart/form-data" class="event-form">
                <div class="form-section">
                    <h3>Información Básica</h3>
                    
                    <div class="form-group">
                        <label for="titulo">Título del Evento <span class="required">*</span></label>
                        <input type="text" id="titulo" name="titulo" required 
                               placeholder="Ej: Capacitación en Nuevas Tecnologías" maxlength="200">
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción <span class="optional">(opcional)</span></label>
                        <textarea id="descripcion" name="descripcion" rows="4" 
                                  placeholder="Describe brevemente el evento..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="imagen">Imagen del Evento <span class="optional">(opcional)</span></label>
                        <div class="file-upload">
                            <input type="file" id="imagen" name="imagen" accept="image/*">
                            <div class="file-upload-label">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                                </svg>
                                <span>Arrastra una imagen o haz clic para seleccionar</span>
                                <small>PNG, JPG o GIF (máx. 5MB)</small>
                            </div>
                            <div class="file-preview" id="imagePreview"></div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Fechas y Horarios</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_inicio">Fecha y Hora de Inicio <span class="required">*</span></label>
                            <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" required>
                        </div>

                        <div class="form-group">
                            <label for="fecha_fin">Fecha y Hora de Fin <span class="required">*</span></label>
                            <input type="datetime-local" id="fecha_fin" name="fecha_fin" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Participantes</h3>
                    
                    <div class="form-group">
                        <label for="tipo_participantes">Tipo de Participantes</label>
                        <select id="tipo_participantes" name="tipo_participantes">
                            <option value="instructores" selected>Instructores</option>
                            <option value="aprendices">Aprendices</option>
                            <option value="todos">Todos</option>
                        </select>
                        <small class="form-help">Por el momento, el registro está habilitado para instructores.</small>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="/eventos/admin" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                        </svg>
                        <span>Crear Evento</span>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Preview de imagen
        const imageInput = document.getElementById('imagen');
        const preview = document.getElementById('imagePreview');
        const uploadLabel = document.querySelector('.file-upload-label');

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    preview.style.display = 'block';
                    uploadLabel.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });

        // Validación de fechas
        const fechaInicio = document.getElementById('fecha_inicio');
        const fechaFin = document.getElementById('fecha_fin');

        fechaInicio.addEventListener('change', function() {
            fechaFin.min = this.value;
        });

        fechaFin.addEventListener('change', function() {
            if (fechaInicio.value && this.value < fechaInicio.value) {
                alert('La fecha de fin debe ser posterior a la fecha de inicio');
                this.value = '';
            }
        });
    </script>
</body>
</html>

