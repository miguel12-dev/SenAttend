<?php
/** @var array $user */
/** @var array $old */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Equipo - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/aprendiz/panel.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/aprendiz/equipos/create.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'aprendiz-equipos-crear';
        require __DIR__ . '/../../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <div class="aprendiz-dashboard">
                    <section class="aprendiz-dashboard-header">
                        <div>
                            <h1>Registrar nuevo equipo</h1>
                            <p>Vincula tu equipo portátil para gestionar sus ingresos y salidas del CTA.</p>
                        </div>
                        <div class="aprendiz-actions">
                            <?php 
                            $url = '/aprendiz/equipos';
                            $text = 'Volver a Mis Equipos';
                            require __DIR__ . '/../../components/back-button.php'; 
                            ?>
                        </div>
                    </section>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>

                    <section class="aprendiz-equipos-card">
                        <form action="/aprendiz/equipos" method="POST" class="form" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="numero_serial">Número de serie del equipo <span class="required">*</span></label>
                                <input
                                    type="text"
                                    id="numero_serial"
                                    name="numero_serial"
                                    class="form-control"
                                    required
                                    placeholder="Ej: SN123456789"
                                    value="<?= htmlspecialchars($old['numero_serial'] ?? '') ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label for="marca">Marca del equipo <span class="required">*</span></label>
                                <div class="brand-select-wrapper">
                                    <select id="marca" name="marca" class="form-control" required>
                                        <option value="">Selecciona una marca</option>
                                        <option value="HP">HP</option>
                                        <option value="Dell">Dell</option>
                                        <option value="Lenovo">Lenovo</option>
                                        <option value="ASUS">ASUS</option>
                                        <option value="Acer">Acer</option>
                                        <option value="Apple">Apple (MacBook)</option>
                                        <option value="Toshiba">Toshiba</option>
                                        <option value="MSI">MSI</option>
                                        <option value="Microsoft">Microsoft (Surface)</option>
                                        <option value="Samsung">Samsung</option>
                                        <option value="Otro">Otra marca (especificar)</option>
                                    </select>
                                </div>
                                <div id="marca-otro-wrapper" class="marca-otro-wrapper" style="display: none;">
                                    <input
                                        type="text"
                                        id="marca_otro"
                                        name="marca_otro"
                                        class="form-control"
                                        placeholder="Especifica la marca"
                                        value="<?= htmlspecialchars($old['marca_otro'] ?? '') ?>"
                                    >
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="imagen">Imagen del equipo (opcional)</label>
                                <input
                                    type="file"
                                    id="imagen"
                                    name="imagen"
                                    class="form-control"
                                    accept="image/*"
                                >
                                <small style="color:#666;font-size:0.85rem;">
                                    Formatos permitidos: JPG, PNG. Tamaño máximo recomendado: 2MB.
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar equipo
                            </button>
                        </form>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const marcaSelect = document.getElementById('marca');
            const marcaOtroWrapper = document.getElementById('marca-otro-wrapper');
            const marcaOtroInput = document.getElementById('marca_otro');
            
            // Check initial state
            if (marcaSelect.value === 'Otro') {
                marcaOtroWrapper.style.display = 'block';
            }
            
            // Handle selection change
            marcaSelect.addEventListener('change', function() {
                if (this.value === 'Otro') {
                    marcaOtroWrapper.style.display = 'block';
                    marcaOtroInput.required = true;
                    // Clear the select value since we're using custom input
                    this.removeAttribute('name');
                    marcaOtroInput.setAttribute('name', 'marca');
                } else {
                    marcaOtroWrapper.style.display = 'none';
                    marcaOtroInput.required = false;
                    marcaOtroInput.removeAttribute('name');
                    marcaSelect.setAttribute('name', 'marca');
                    marcaOtroInput.value = '';
                }
            });
            
            // Handle form submission
            const form = marcaSelect.closest('form');
            form.addEventListener('submit', function(e) {
                if (marcaSelect.value === 'Otro' && marcaOtroInput.value.trim() === '') {
                    e.preventDefault();
                    marcaOtroInput.focus();
                    marcaOtroWrapper.classList.add('input-error');
                }
            });
        });
    </script>
</body>
</html>


