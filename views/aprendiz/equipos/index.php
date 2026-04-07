<?php
/** @var array $user */
/** @var array $equipos */
/** @var array $equiposEliminados */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Equipos - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/aprendiz/panel.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/aprendiz/equipos/index.css') ?>">
    <style>
        /* Animaciones y mejoras UX */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .equipo-card {
            animation: slideIn 0.4s ease-out forwards;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .equipo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .equipo-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-delete:hover {
            background-color: #c82333;
            transform: scale(1.05);
        }

        .btn-restore {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-restore:hover {
            background-color: #218838;
            transform: scale(1.05);
        }

        /* Botones con dropdown-style */
        .dropdown-delete {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            min-width: 180px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            border-radius: 8px;
            z-index: 100;
            padding: 0.5rem;
            right: 0;
            top: 100%;
            margin-top: 5px;
        }

        .dropdown-delete:hover .dropdown-content {
            display: block;
            animation: fadeIn 0.2s ease;
        }

        .dropdown-content form {
            margin: 0;
        }

        .dropdown-content button {
            width: 100%;
            padding: 0.75rem;
            border: none;
            background: none;
            text-align: left;
            cursor: pointer;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        .dropdown-content button:hover {
            background-color: #f8f9fa;
        }

        .dropdown-item-danger {
            color: #dc3545;
        }

        .dropdown-item-danger:hover {
            background-color: #ffeef0;
        }

        /* Sección equipos eliminados */
        .equipos-eliminados-section {
            margin-top: 2rem;
            animation: fadeIn 0.5s ease;
        }

        .equipos-eliminados-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            padding: 1rem;
            background: #fff3cd;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }

        .equipos-eliminados-header h3 {
            margin: 0;
            color: #856404;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .equipos-eliminados-list {
            display: none;
            padding: 1rem 0;
        }

        .equipos-eliminados-list.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        .equipo-card.eliminado {
            opacity: 0.7;
            border: 2px dashed #ffc107;
            background: #fffdf5;
        }

        .badge-eliminado {
            background-color: #ffc107;
            color: #856404;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        /* Icono toggle */
        .toggle-icon {
            transition: transform 0.3s ease;
        }

        .toggle-icon.rotated {
            transform: rotate(180deg);
        }

        /* Botón principal */
        .btn-primary {
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        /* Confirmación delete */
        .confirm-delete-msg {
            font-size: 0.85rem;
            color: #666;
            padding: 0.5rem 0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'aprendiz-equipos';
        require __DIR__ . '/../../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <!-- Header -->
                <div class="dashboard-header">
                    <div>
                        <h2>
                            <i class="fas fa-laptop"></i>
                            Mis Equipos
                        </h2>
                        <p class="subtitle">
                            Gestiona todos tus equipos registrados y accede a sus códigos QR.
                        </p>
                    </div>
                    <div>
                        <?php 
                        $url = '/aprendiz/panel';
                        require __DIR__ . '/../../components/back-button.php'; 
                        ?>
                    </div>
                </div>

                <!-- Mensajes -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                

                <!-- Lista de Equipos -->
                <?php if (!empty($equipos) || !empty($equiposEliminados)): ?>
                <section class="aprendiz-equipos-card">
                    <div class="aprendiz-equipos-header">
                        <h2><i class="fas fa-list"></i> Equipos Registrados (<?= count($equipos) ?>)</h2>
                    </div>
                    <div class="aprendiz-equipos-list">
                        <div class="equipos-grid">
                            <?php foreach ($equipos as $index => $equipo): ?>
                                <div class="equipo-card" style="animation-delay: <?= $index * 0.1 ?>s">
                                    <div class="equipo-imagen">
                                        <?php if (!empty($equipo['imagen'])): ?>
                                            <img src="<?= asset($equipo['imagen']) ?>" alt="<?= htmlspecialchars($equipo['marca']) ?>">
                                        <?php else: ?>
                                            <div class="equipo-imagen-placeholder">
                                                <i class="fas fa-laptop"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="equipo-info">
                                        <h3><?= htmlspecialchars($equipo['marca']) ?></h3>
                                        <div class="equipo-details">
                                            <p><strong>Serial:</strong> <code><?= htmlspecialchars($equipo['numero_serial']) ?></code></p>
                                            <p><strong>Estado:</strong> 
                                                <span class="badge-<?= $equipo['estado'] === 'activo' ? 'activo' : 'inactivo' ?>">
                                                    <?= htmlspecialchars(ucfirst($equipo['estado'])) ?>
                                                </span>
                                            </p>
                                            <?php if (!empty($equipo['fecha_asignacion'])): ?>
                                                <p><strong>Registrado:</strong> <?= date('d/m/Y', strtotime($equipo['fecha_asignacion'])) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="equipo-actions">
                                            <a href="/aprendiz/equipos/<?= (int)$equipo['equipo_id'] ?>/qr" class="btn btn-primary">
                                                <i class="fas fa-qrcode"></i> Ver QR
                                            </a>
                                            <div class="dropdown-delete">
                                                <button type="button" class="btn-delete">
                                                    <i class="fas fa-trash-alt"></i> Eliminar <i class="fas fa-chevron-down"></i>
                                                </button>
                                                <div class="dropdown-content">
                                                    <p class="confirm-delete-msg">¿Estás seguro de eliminar este equipo?</p>
                                                    <form action="/aprendiz/equipos/<?= (int)$equipo['relacion_id'] ?>/eliminar" method="POST">
                                                        <button type="submit" class="dropdown-item-danger">
                                                            <i class="fas fa-times-circle"></i> Sí, eliminar equipo
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Equipos eliminados que pueden ser restaurados -->
                    <?php if (!empty($equiposEliminados)): ?>
                    <div class="equipos-eliminados-section">
                        <div class="equipos-eliminados-header" onclick="toggleEliminados()">
                            <h3><i class="fas fa-history"></i> Equipos eliminados (<?= count($equiposEliminados) ?>)</h3>
                            <i class="fas fa-chevron-down toggle-icon" id="toggleIcon"></i>
                        </div>
                        <div class="equipos-eliminados-list" id="eliminadosList">
                            <div class="equipos-grid">
                                <?php foreach ($equiposEliminados as $equipo): ?>
                                    <div class="equipo-card eliminado">
                                        <div class="equipo-imagen">
                                            <?php if (!empty($equipo['imagen'])): ?>
                                                <img src="<?= asset($equipo['imagen']) ?>" alt="<?= htmlspecialchars($equipo['marca']) ?>">
                                            <?php else: ?>
                                                <div class="equipo-imagen-placeholder">
                                                    <i class="fas fa-laptop"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="equipo-info">
                                            <h3><?= htmlspecialchars($equipo['marca']) ?></h3>
                                            <div class="equipo-details">
                                                <p><strong>Serial:</strong> <code><?= htmlspecialchars($equipo['numero_serial']) ?></code></p>
                                                <p><strong>Eliminado:</strong> 
                                                    <span class="badge-eliminado">
                                                        <?= date('d/m/Y', strtotime($equipo['fecha_eliminacion'])) ?>
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="equipo-actions">
                                                <form action="/aprendiz/equipos/<?= (int)$equipo['relacion_id'] ?>/restaurar" method="POST">
                                                    <button type="submit" class="btn-restore">
                                                        <i class="fas fa-undo"></i> Volver a agregar
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </section>
                <?php else: ?>
                <section class="aprendiz-equipos-card">
                    <div class="aprendiz-equipos-header">
                        <h2><i class="fas fa-laptop"></i> Mis Equipos</h2>
                    </div>
                    <div class="empty-state" style="text-align: center; padding: 3rem 1rem;">
                        <i class="fas fa-laptop" style="font-size: 4rem; color: #999; margin-bottom: 1rem;"></i>
                        <p style="color: #666; font-size: 1.1rem; margin-bottom: 1rem;">No tienes equipos registrados aún.</p>
                        <a href="/aprendiz/equipos/crear" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Registrar mi primer equipo
                        </a>
                    </div>
                </section>
                <?php endif; ?>
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
        function toggleEliminados() {
            const list = document.getElementById('eliminadosList');
            const icon = document.getElementById('toggleIcon');
            list.classList.toggle('show');
            icon.classList.toggle('rotated');
        }
    </script>
</body>
</html>

