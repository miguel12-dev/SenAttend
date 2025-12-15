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
                        Volver al evento
                    </a>
                    <h2><i class="fas fa-edit"></i> Editar Evento</h2>
                    <p class="subtitle">Modifica la información del evento</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

                <div class="card">
                    <form action="/eventos/admin/<?= $evento['id'] ?>/actualizar" method="POST" enctype="multipart/form-data" class="form">
                <div class="form-section">
                            <h3><i class="fas fa-info-circle"></i> Información Básica</h3>
                    
                    <div class="form-group">
                        <label for="titulo">Título del Evento <span class="required">*</span></label>
                                <input type="text" id="titulo" name="titulo" class="form-control" required 
                               value="<?= htmlspecialchars($evento['titulo']) ?>" maxlength="200">
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción <span class="optional">(opcional)</span></label>
                                <textarea id="descripcion" name="descripcion" class="form-control" rows="4"><?= htmlspecialchars($evento['descripcion'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="imagen">Imagen del Evento <span class="optional">(opcional)</span></label>
                        <?php if ($evento['imagen_url']): ?>
                        <div class="current-image">
                                    <img src="<?= htmlspecialchars($evento['imagen_url']) ?>" alt="Imagen actual" style="max-width: 300px; border-radius: 8px; margin-bottom: 0.5rem;">
                                    <p style="color: var(--color-gray-600); font-size: 0.9rem;"><i class="fas fa-image"></i> Imagen actual (sube una nueva para reemplazarla)</p>
                        </div>
                        <?php endif; ?>
                                <input type="file" id="imagen" name="imagen" class="form-control" accept="image/*">
                            <div class="file-preview" id="imagePreview"></div>
                    </div>
                </div>

                <div class="form-section">
                            <h3><i class="fas fa-calendar-alt"></i> Fechas y Horarios</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_inicio">Fecha y Hora de Inicio <span class="required">*</span></label>
                                    <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" class="form-control" required
                                   value="<?= date('Y-m-d\TH:i', strtotime($evento['fecha_inicio'])) ?>">
                        </div>

                        <div class="form-group">
                            <label for="fecha_fin">Fecha y Hora de Fin <span class="required">*</span></label>
                                    <input type="datetime-local" id="fecha_fin" name="fecha_fin" class="form-control" required
                                   value="<?= date('Y-m-d\TH:i', strtotime($evento['fecha_fin'])) ?>">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                            <a href="/eventos/admin/<?= $evento['id'] ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Guardar Cambios
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

