<?php
/**
 * Componente de Header Público para Módulo de Eventos
 * Para páginas públicas de eventos
 */
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
$currentPath = strtok($currentPath, '?'); // Remover query string
$currentPath = rtrim($currentPath, '/') ?: '/';
?>
<header class="header-public">
    <div class="container">
        <div class="header-content">
            <div class="header-left">
                <button class="menu-toggle-public" id="menuTogglePublic" aria-label="Menú">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="logo">
                    <a href="/eventos" class="logo-link-public">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="logo-text">Eventos SENA</span>
                    </a>
                </div>
            </div>
            
            <nav class="nav-public" id="mainNavPublic">
                <ul class="nav-menu-public">
                    <li><a href="/eventos" class="nav-link-public <?= $currentPath === '/eventos' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt"></i> Eventos
                    </a></li>
                    <li><a href="/" class="nav-link-public <?= $currentPath === '/' ? 'active' : '' ?>">
                        <i class="fas fa-home"></i> Sistema Principal
                    </a></li>
                    <li>
                        <a href="/eventos/login" class="btn-login-public <?= $currentPath === '/eventos/login' ? 'active' : '' ?>">
                            <i class="fas fa-user-shield"></i> Administrar Eventos
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</header>

