<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos SENA | SENAttend</title>
    <link rel="stylesheet" href="<?= asset('css/eventos/publico.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="hero">
        <div class="hero-background">
            <div class="bg-pattern"></div>
        </div>
        <div class="hero-content">
            <div class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM9 10H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/>
                </svg>
            </div>
            <h1>Eventos SENA</h1>
            <p>Regístrate a los eventos disponibles y participa en las actividades del SENA</p>
            <a href="/eventos/login" class="btn-admin">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
                <span>Acceso Administrativo</span>
            </a>
        </div>
    </header>

    <?php if (!empty($mensaje)): ?>
    <div class="notification notification-<?= $mensaje['tipo'] ?>">
        <div class="notification-content">
            <?php if ($mensaje['tipo'] === 'success'): ?>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
            </svg>
            <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            </svg>
            <?php endif; ?>
            <span><?= htmlspecialchars($mensaje['texto']) ?></span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">×</button>
    </div>
    <?php endif; ?>

    <main class="main-content">
        <section class="events-section">
            <h2>Eventos Disponibles</h2>
            
            <?php if (empty($eventos)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/>
                    </svg>
                </div>
                <h3>No hay eventos disponibles</h3>
                <p>Pronto habrá nuevos eventos. ¡Vuelve pronto!</p>
            </div>
            <?php else: ?>
            <div class="events-grid">
                <?php foreach ($eventos as $evento): ?>
                <article class="event-card">
                    <div class="event-image" <?php if ($evento['imagen_url']): ?>style="background-image: url('<?= htmlspecialchars($evento['imagen_url']) ?>')"<?php endif; ?>>
                        <span class="event-badge badge-<?= $evento['estado'] ?>">
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
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/>
                                </svg>
                                <span><?= date('d M Y', strtotime($evento['fecha_inicio'])) ?></span>
                            </div>
                            <div class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                                </svg>
                                <span><?= date('H:i', strtotime($evento['fecha_inicio'])) ?> - <?= date('H:i', strtotime($evento['fecha_fin'])) ?></span>
                            </div>
                            <div class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3z"/>
                                </svg>
                                <span><?= $evento['total_participantes'] ?? 0 ?> registrados</span>
                            </div>
                        </div>
                        <a href="/eventos/registro/<?= $evento['id'] ?>" class="btn-register">
                            Registrarme
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z"/>
                            </svg>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="footer">
        <p>© <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
        <p>Sistema de Gestión de Eventos - SENAttend</p>
    </footer>
</body>
</html>

