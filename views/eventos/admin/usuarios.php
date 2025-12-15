<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios de Eventos - Gestión</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/eventos/admin.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'eventos-usuarios';
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
                        <i class="fas fa-user-shield"></i>
                        Usuarios Administrativos
                    </h2>
                    <p class="subtitle">Gestiona quienes pueden administrar el módulo de eventos.</p>
                </div>

                <section class="events-section">
                    <div class="section-header">
                        <h3><i class="fas fa-users-cog"></i> Usuarios del módulo</h3>
                        <button class="btn btn-primary" id="btnNuevoUsuario">
                            <i class="fas fa-user-plus"></i>
                            Nuevo usuario
                        </button>
                    </div>

                    <div class="table-wrapper">
                        <table class="table events-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Creado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:1.5rem;">Sin usuarios registrados.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($u['nombre']) ?></strong></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <span class="badge <?= $u['rol'] === 'admin' ? 'badge-primary' : 'badge-secondary' ?>">
                                            <?= $u['rol'] === 'admin' ? 'Administrador' : 'Administrativo' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($u['activo']): ?>
                                            <span class="badge badge-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
    </div>

    <!-- Modal Crear Usuario -->
    <div class="modal" id="modalUsuario" aria-hidden="true">
        <div class="modal-overlay" data-close-modal></div>
        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div class="modal-header">
                <h3 id="modalTitle"><i class="fas fa-user-plus"></i> Nuevo Usuario</h3>
                <button class="close-btn" type="button" data-close-modal aria-label="Cerrar">&times;</button>
            </div>
            <form action="/eventos/admin/usuarios" method="POST" class="event-form" style="padding: 0 1.25rem 1rem;">
                <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="margin-top: 1rem;">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                <div class="alert alert-error" style="margin-top: 1rem;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="nombre">Nombre completo <span style="color:red">*</span></label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required maxlength="120" placeholder="Ej: María Pérez">
                </div>
                <div class="form-group">
                    <label for="email">Correo <span style="color:red">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" required maxlength="120" placeholder="correo@sena.edu.co">
                </div>
                <div class="form-group">
                    <label for="documento">Documento (se usará como contraseña inicial) <span style="color:red">*</span></label>
                    <input type="text" id="documento" name="documento" class="form-control" required maxlength="30" placeholder="Documento de identidad">
                </div>
                <div class="form-group">
                    <label for="rol">Rol</label>
                    <select id="rol" name="rol" class="form-control">
                        <option value="administrativo" selected>Administrativo</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <div class="form-actions" style="display:flex; gap:0.75rem; justify-content:flex-end; margin-top:1.5rem;">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Crear y enviar credenciales
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
        const modal = document.getElementById('modalUsuario');
        const openBtn = document.getElementById('btnNuevoUsuario');
        const closeEls = modal.querySelectorAll('[data-close-modal]');
        const hasMessage = <?= (!empty($success) || !empty($error)) ? 'true' : 'false' ?>;

        const openModal = () => {
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };

        const closeModal = () => {
            window.location.href = '/eventos/admin';
        };

        if (openBtn) {
            openBtn.addEventListener('click', openModal);
        }
        closeEls.forEach(el => el.addEventListener('click', closeModal));
        modal.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                closeModal();
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('open')) {
                closeModal();
            }
        });

        // Abrir modal automáticamente si hay mensajes de éxito o error
        if (hasMessage) {
            openModal();
        }
    </script>
</body>
</html>

