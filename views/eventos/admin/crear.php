<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Evento - Gestión de Eventos</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/eventos/admin.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'eventos-crear';
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
                        <i class="fas fa-plus-circle"></i>
                        Crear Nuevo Evento
                    </h2>
                    <p class="subtitle">Completa la información del evento</p>
                </div>

                <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form action="/eventos/admin/crear" method="POST" enctype="multipart/form-data" class="event-form">
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Información Básica</h3>
                        
                        <div class="form-group">
                            <label for="titulo">Título del Evento <span style="color: red;">*</span></label>
                            <input type="text" id="titulo" name="titulo" class="form-control" required 
                                   placeholder="Ej: Capacitación en Nuevas Tecnologías" maxlength="200">
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" class="form-control" rows="4" 
                                      placeholder="Describe brevemente el evento..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="imagen">Imagen del Evento</label>
                            <div class="file-upload">
                                <input type="file" id="imagen" name="imagen" accept="image/*" class="form-control">
                                <div class="file-preview" id="imagePreview"></div>
                            </div>
                            <small style="color: #666;">PNG, JPG o GIF (máx. 5MB)</small>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-calendar-alt"></i> Fechas y Horarios</h3>
                        
                        <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label for="fecha_inicio">Fecha y Hora de Inicio <span style="color: red;">*</span></label>
                                <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="fecha_fin">Fecha y Hora de Fin <span style="color: red;">*</span></label>
                                <input type="datetime-local" id="fecha_fin" name="fecha_fin" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-users"></i> Participantes</h3>
                        
                        <div class="form-group">
                            <label for="tipo_participantes">Tipo de Participantes</label>
                            <select id="tipo_participantes" name="tipo_participantes" class="form-control">
                                <option value="instructores" selected>Instructores</option>
                                <option value="aprendices">Aprendices</option>
                                <option value="todos">Todos</option>
                            </select>
                            <small style="color: #666;">Por el momento, el registro está habilitado para instructores.</small>
                        </div>
                    </div>

                    <div class="form-actions" style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                        <a href="/eventos/admin" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i>
                            Crear Evento
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
        // Preview de imagen
        const imageInput = document.getElementById('imagen');
        const preview = document.getElementById('imagePreview');

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 300px; border-radius: 8px; margin-top: 1rem;">`;
                    preview.style.display = 'block';
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

