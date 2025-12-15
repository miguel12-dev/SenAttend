<?php
/**
 * Componente de Header para Módulo de Eventos
 * Incluye navegación al sistema principal
 */
$user = $user ?? null;
$currentPage = $currentPage ?? '';
?>
<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle" aria-label="Menú">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="logo">
                    <a href="/eventos/admin" class="logo-link-eventos">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="logo-text">Gestión de Eventos</span>
                    </a>
                </div>
                
                <?php if ($user): ?>
                <div class="nav-user-mobile">
                    <a href="/dashboard" class="btn-back-mobile" title="Volver al Sistema Principal">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <a href="/eventos/logout" class="logout-icon-link" title="Cerrar Sesión">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($user): ?>
            <nav class="nav" id="mainNav">
                <ul class="nav-menu">
                    <li><a href="/eventos/admin" class="<?= $currentPage === 'eventos-dashboard' ? 'active' : '' ?>">
                        <i class="fas fa-th-large"></i> Dashboard
                    </a></li>
                    <li><a href="/eventos/admin/crear" class="<?= $currentPage === 'eventos-crear' ? 'active' : '' ?>">
                        <i class="fas fa-plus-circle"></i> Crear Evento
                    </a></li>
                    <li><a href="/eventos/qr/scanner" class="<?= $currentPage === 'eventos-scanner' ? 'active' : '' ?>">
                        <i class="fas fa-qrcode"></i> Escáner QR
                    </a></li>
                    <li><a href="/dashboard" class="nav-link-back">
                        <i class="fas fa-arrow-left"></i> Sistema Principal
                    </a></li>
                </ul>
                
                <div class="nav-user">
                    <a href="/eventos/logout" class="logout-icon-link" title="Cerrar Sesión">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</header>

