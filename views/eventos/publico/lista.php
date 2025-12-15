<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos SENA</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components/header-public.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/eventos/publico.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php require __DIR__ . '/../../components/header-eventos-publico.php'; ?>

        <div class="hero-eventos">
            <div class="hero-content-eventos">
                <i class="fas fa-calendar-alt" style="font-size: 5rem; color: white; margin-bottom: 1.5rem; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));"></i>
                <h1>Eventos SENA</h1>
                <p>Regístrate a los eventos disponibles y participa en las actividades del SENA</p>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <i class="fas fa-calendar-check" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                        <div>Eventos Disponibles</div>
                    </div>
                    <div class="hero-stat">
                        <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                        <div>Participa Fácilmente</div>
                    </div>
                    <div class="hero-stat">
                        <i class="fas fa-qrcode" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                        <div>Acceso con QR</div>
                    </div>
                </div>
            </div>
        </div>

        <main class="main-content">
            <div class="container">
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?= $mensaje['tipo'] === 'success' ? 'success' : 'error' ?>">
                    <i class="fas fa-<?= $mensaje['tipo'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($mensaje['texto']) ?>
                </div>
                <?php endif; ?>

                <section class="events-section">
                    <h2>
                        <i class="fas fa-calendar-alt"></i>
                        Eventos Disponibles
                    </h2>
                    
                    <?php if (empty($eventos)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h3>No hay eventos disponibles</h3>
                        <p>Pronto habrá nuevos eventos. ¡Vuelve pronto!</p>
                        <div class="empty-decoration">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="events-grid">
                        <?php foreach ($eventos as $evento): ?>
                        <article class="event-card">
                            <div class="event-image" <?php if ($evento['imagen_url']): ?>style="background-image: url('<?= htmlspecialchars($evento['imagen_url']) ?>')"<?php endif; ?>>
                                <span class="badge event-badge badge-<?= $evento['estado'] ?>">
                                    <?= $evento['estado'] === 'en_curso' ? 'En curso' : 'Próximamente' ?>
                                </span>
                            </div>
                            <div class="event-content">
                                <h3><?= htmlspecialchars($evento['titulo']) ?></h3>
                                <?php if ($evento['descripcion']): ?>
                                <p class="event-description"><?= htmlspecialchars(substr($evento['descripcion'], 0, 100)) ?>...</p>
                                <?php endif; ?>
                                <div class="event-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?= date('d M Y', strtotime($evento['fecha_inicio'])) ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?= date('H:i', strtotime($evento['fecha_inicio'])) ?> - <?= date('H:i', strtotime($evento['fecha_fin'])) ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-users"></i>
                                        <span><?= $evento['total_participantes'] ?? 0 ?> registrados</span>
                                    </div>
                                </div>
                                <a href="/eventos/registro/<?= $evento['id'] ?>" class="btn btn-primary btn-register">
                                    <i class="fas fa-user-plus"></i>
                                    Registrarme
                                </a>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>

