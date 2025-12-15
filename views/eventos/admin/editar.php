<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar - <?= htmlspecialchars($evento['titulo']) ?></title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/eventos/admin.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'eventos-dashboard';
        require __DIR__ . '/../../components/header-eventos.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <div class="page-header">
                    <a href="/eventos/admin/<?= $evento['id'] ?>" class="btn-back">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                </div>

    <main class="main-content">
        <div class="form-container">
            <div class="form-header">
                <h1>Editar Evento</h1>
                <p>Modifica la información del evento</p>
            </div>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form action="/eventos/admin/<?= $evento['id'] ?>/actualizar" method="POST" enctype="multipart/form-data" class="event-form">
                <div class="form-section">
                    <h3>Información Básica</h3>
                    
                    <div class="form-group">
                        <label for="titulo">Título del Evento <span class="required">*</span></label>
                        <input type="text" id="titulo" name="titulo" required 
                               value="<?= htmlspecialchars($evento['titulo']) ?>" maxlength="200">
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción <span class="optional">(opcional)</span></label>
                        <textarea id="descripcion" name="descripcion" rows="4"><?= htmlspecialchars($evento['descripcion'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="imagen">Imagen del Evento <span class="optional">(opcional)</span></label>
                        <?php if ($evento['imagen_url']): ?>
                        <div class="current-image">
                            <img src="<?= htmlspecialchars($evento['imagen_url']) ?>" alt="Imagen actual">
                            <span>Imagen actual (sube una nueva para reemplazarla)</span>
                        </div>
                        <?php endif; ?>
                        <div class="file-upload">
                            <input type="file" id="imagen" name="imagen" accept="image/*">
                            <div class="file-upload-label">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                                </svg>
                                <span>Subir nueva imagen</span>
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
                            <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" required
                                   value="<?= date('Y-m-d\TH:i', strtotime($evento['fecha_inicio'])) ?>">
                        </div>

                        <div class="form-group">
                            <label for="fecha_fin">Fecha y Hora de Fin <span class="required">*</span></label>
                            <input type="datetime-local" id="fecha_fin" name="fecha_fin" required
                                   value="<?= date('Y-m-d\TH:i', strtotime($evento['fecha_fin'])) ?>">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="/eventos/admin/<?= $evento['id'] ?>" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                        </svg>
                        <span>Guardar Cambios</span>
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
    </script>
</body>
</html>

