<?php
/** @var array $user */
/** @var array $equipo */
/** @var array $old */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Equipo - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/aprendiz/panel.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/aprendiz/equipos/create.css') ?>">
    <style>
        .equipo-current-image {
            margin-bottom: 1rem;
        }
        .equipo-current-image img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            border: 2px solid #ddd;
        }
        .equipo-current-image p {
            margin: 0.5rem 0;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'aprendiz-equipos-editar';
        require __DIR__ . '/../../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <div class="aprendiz-dashboard">
                    <section class="aprendiz-dashboard-header">
                        <div>
                            <h1>Editar equipo</h1>
                            <p>Actualiza la marca o imagen de tu equipo.</p>
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
                        <form action="/aprendiz/equipos/<?= (int)($equipo['equipo_id'] ?? 0) ?>/actualizar" method="POST" class="form" enctype="multipart/form-data">
                            <!-- Número de serie (solo lectura) -->
                            <div class="form-group">
                                <label for="numero_serial">Número de serie</label>
                                <input
                                    type="text"
                                    id="numero_serial"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipo['numero_serial'] ?? '') ?>"
                                    readonly
                                    style="background-color: #f5f5f5; cursor: not-allowed;"
                                >
                                <small style="color:#666;font-size:0.85rem;">
                                    El número de serie no se puede modificar.
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="marca">Marca del equipo <span class="required">*</span></label>
                                <div class="brand-select-wrapper">
                                    <select id="marca" name="marca" class="form-control" required>
                                        <option value="">Selecciona una marca</option>
                                        <option value="HP" <?= ($equipo['marca'] ?? $old['marca'] ?? '') === 'HP' ? 'selected' : '' ?>>HP</option>
                                        <option value="Dell" <?= ($equipo['marca'] ?? $old['marca'] ?? '') === 'Dell' ? 'selected' : '' ?>>Dell</option>
                                        <option value="Lenovo" <?= ($equipo['marca'] ?? $old['marca'] ?? '') === 'Lenovo' ? 'selected' : '' ?>>Lenovo</option>
                                        <option value="ASUS" <?= ($equipo['marca'] ?? $old['marca'] ?? '') === 'ASUS' ? 'selected' : '' ?>>ASUS</option>
                                        <option value="Acer" <?= ($equipo['marca'] ?? $old['marca'] ?? '') === 'Acer' ? 'selected' : '' ?>>Acer</option>
                                        <option value="Apple" <?= ($equipo['marca'] ?? $old['marca'] ?? '') === 'Apple' ? 'selected' : '' ?>>Apple (MacBook)</option>
                                        <option value="Toshiba" <?= ($equipo['marca'] ?? $old['marca'] ?? '') === 'Toshiba' ? 'selected' : '' ?>>Toshiba</option>
                                        <option value="MSI" <?= ($equipo['marca'] ?? $old['marca'] ?? '') === 'MSI' ? 'selected' : '' ?>>MSI</option>
                                        <option value="Microsoft" <?= ($equipo['marca'] ?? $old['marca'] ?? '') === 'Microsoft' ? 'selected' : '' ?>>Microsoft (Surface)</option>
                                        <option value="Samsung" <?= ($equipo['marca'] ?? $old['marca'] ?? '') === 'Samsung' ? 'selected' : '' ?>>Samsung</option>
                                        <option value="Otro" <?= ($equipo['marca'] ?? $old['marca'] ?? '') === 'Otro' ? 'selected' : '' ?>>Otra marca (especificar)</option>
                                    </select>
                                </div>
                                <div id="marca-otro-wrapper" class="marca-otro-wrapper" style="display: <?= ($equipo['marca'] ?? $old['marca'] ?? '') === 'Otro' ? 'block' : 'none' ?>;">
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
                                
                                <!-- Mostrar imagen actual -->
                                <?php if (!empty($equipo['imagen'])): ?>
                                    <div class="equipo-current-image">
                                        <p>Imagen actual:</p>
                                        <img src="<?= asset(htmlspecialchars($equipo['imagen'], ENT_QUOTES, 'UTF-8')) ?>" alt="Imagen actual del equipo">
                                        <p style="margin-top: 0.5rem;">Sube una nueva imagen para reemplazar la actual.</p>
                                    </div>
                                <?php endif; ?>
                                
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
                                <i class="fas fa-save"></i> Actualizar equipo
                            </button>
                        </form>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/common/notification-modal.js') ?>"></script>
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
            
            // Handle form submission - AJAX with cache invalidation
            const form = marcaSelect.closest('form');
            
            form.addEventListener('submit', async function(e) {
                // Prevent default only if JavaScript enabled (graceful degradation)
                e.preventDefault();
                
                if (marcaSelect.value === 'Otro' && marcaOtroInput.value.trim() === '') {
                    marcaOtroInput.focus();
                    marcaOtroWrapper.classList.add('input-error');
                    return;
                }

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnContent = submitBtn.innerHTML;
                
                // Disable button and show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
                
                try {
                    // Use FormData for file uploads
                    const formData = new FormData(form);
                    
                    // Submit via AJAX
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });
                    
                    // Check content type
                    const contentType = response.headers.get('content-type');
                    let result;
                    
                    if (contentType && contentType.includes('application/json')) {
                        result = await response.json();
                        
                        if (!result.success) {
                            throw new Error(result.message || result.error || 'Error al actualizar');
                        }
                    } else if (response.ok || response.redirected) {
                        // Traditional redirect - will handle on client
                        result = { success: true, redirected: true };
                    } else {
                        throw new Error('Error en la solicitud');
                    }
                    
                    // ✅ CACHE INVALIDATION - Key Step!
                    await invalidateEquiposCache();
                    
                    // Show success notification
                    if (window.showSuccess) {
                        window.showSuccess('Equipo actualizado correctamente');
                    } else if (window.pwaManager) {
                        pwaManager.showToast('Equipo actualizado correctamente', 'success');
                    }
                    
                    // Navigate to list (soft redirect to avoid full page reload issues)
                    setTimeout(() => {
                        window.location.href = '/aprendiz/equipos?updated=' + Date.now();
                    }, 500);
                    
                } catch (error) {
                    console.error('Error al actualizar equipo:', error);
                    
                    // Show error notification
                    if (window.showError) {
                        window.showError(error.message || 'Error al actualizar el equipo');
                    } else if (window.pwaManager) {
                        pwaManager.showToast(error.message || 'Error al actualizar el equipo', 'error');
                    }
                    
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnContent;
                }
            });
            
            // Remove error class on input
            marcaOtroInput.addEventListener('input', function() {
                marcaOtroWrapper.classList.remove('input-error');
            });
        });

        /**
         * Cache Invalidation Function
         * Clears Service Worker cache for equipos endpoint
         */
        async function invalidateEquiposCache() {
            console.log('[Cache] Invalidating equipos cache...');
            
            // Option A: Message to Service Worker
            if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({
                    type: 'INVALIDATE_CACHE',
                    patterns: ['/aprendiz/equipos', '/api/aprendiz/equipos']
                });
            }
            
            // Option B: Clear localStorage cache
            localStorage.removeItem('senattend_equipos_cache');
            
            // Option C: Clear sessionStorage
            sessionStorage.removeItem('equipos_list');
            sessionStorage.removeItem('equipos_last_fetch');
            
            console.log('[Cache] Equipos cache invalidated');
        }
    </script>
</body>
</html>