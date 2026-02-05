<?php
// app/view/includes/sidebar.php - Menú lateral compartido
?>
<aside class="sidebar" role="navigation" aria-label="Menú principal">
    <div class="brand">
        <div class="brand-title">Herramienta Didáctica del Saber Algorítmica y Programación del PNFI</div>
    </div>
    <div class="brand-logo">
        <img src="app/view/img/logodepa.png" alt="Logo Departamento" />
        <img src="app/view/img/LOGO UPTP.png" alt="Logo UPTP" />
    </div>
    <nav class="menu">
        <!-- Inicio: disponible para todos -->
        <a href="<?php echo isset($activeSection) && $activeSection === 'inicio' ? '#inicio' : 'index.php?page=home#inicio'; ?>"
           class="<?php echo (isset($activePage) && $activePage === 'home') ? 'active' : ''; ?>"
           id="nav-inicio"
           data-section="inicio"
           aria-label="Inicio">
            <span class="emoji">🏠</span>
            <span class="label">Inicio</span>
        </a>

        <!-- Contenido: Usuario y Docente -->
        <?php if (in_array($rol ?? 'Usuario', ['Usuario', 'Docente'])): ?>
        <a href="index.php?page=content"
           class="<?php echo (isset($activePage) && $activePage === 'content') ? 'active' : ''; ?>"
           id="nav-content"
           aria-label="Contenido teórico">
            <span class="emoji">📚</span>
            <span class="label">Contenido</span>
        </a>
        <?php endif; ?>

        <!-- Prácticas: Usuario y Docente -->
        <?php if (in_array($rol ?? 'Usuario', ['Usuario', 'Docente'])): ?>
        <a href="index.php?page=practices"
           class="<?php echo (isset($activePage) && $activePage === 'practices') ? 'active' : ''; ?>"
           id="nav-practices"
           aria-label="Prácticas interactivas">
            <span class="emoji">💻</span>
            <span class="label">Prácticas</span>
        </a>
        <?php endif; ?>

        <!-- Evaluaciones: Usuario y Docente -->
        <?php if (in_array($rol ?? 'Usuario', ['Usuario', 'Docente'])): ?>
        <a href="index.php?page=evaluation"
           class="<?php echo (isset($activePage) && $activePage === 'evaluation') ? 'active' : ''; ?>"
           id="nav-evaluations"
           aria-label="Evaluaciones">
            <span class="emoji">📝</span>
            <span class="label">Evaluaciones</span>
        </a>
        <?php endif; ?>

        <!-- Usuarios: solo Docente -->
        <?php if (($rol ?? 'Usuario') === 'Docente'): ?>
        <a href="<?php echo isset($activeSection) && $activeSection === 'usuarios' ? '#usuarios' : 'index.php?page=home#usuarios'; ?>"
           id="nav-users"
           data-section="usuarios"
           aria-label="Gestión de usuarios (Docente)">
            <span class="emoji">👥</span>
            <span class="label">Usuarios</span>
        </a>
        <?php endif; ?>

        <!-- Docentes: solo Administrador -->
        <?php if (($rol ?? 'Usuario') === 'Administrador'): ?>
        <a href="<?php echo isset($activeSection) && $activeSection === 'docentes' ? '#docentes' : 'index.php?page=home#docentes'; ?>"
           id="nav-docentes"
           data-section="docentes"
           aria-label="Gestión de docentes (Administrador)">
            <span class="emoji">🏫</span>
            <span class="label">Docentes</span>
        </a>
        <?php endif; ?>

        <!-- Secciones: solo Administrador -->
        <?php if (($rol ?? 'Usuario') === 'Administrador'): ?>
        <a href="<?php echo isset($activeSection) && $activeSection === 'secciones' ? '#secciones' : 'index.php?page=home#secciones'; ?>"
           id="nav-secciones"
           data-section="secciones"
           aria-label="Secciones (Administrador)">
            <span class="emoji">📊</span>
            <span class="label">Secciones</span>
        </a>
        <?php endif; ?>

        <!-- Reportes: solo Administrador -->
        <?php if (($rol ?? 'Usuario') === 'Administrador'): ?>
        <a href="<?php echo isset($activeSection) && $activeSection === 'reportes' ? '#reportes' : 'index.php?page=home#reportes'; ?>"
           id="nav-reportes"
           data-section="reportes"
           aria-label="Reportes (Administrador)">
            <span class="emoji">📋</span>
            <span class="label">Reportes</span>
        </a>
        <?php endif; ?>

        <!-- Manual: disponible para todos -->
        <a href="<?php echo isset($activeSection) && $activeSection === 'manual' ? '#manual' : 'index.php?page=home#manual'; ?>"
           id="nav-manual"
           data-section="manual"
           aria-label="Manual de uso">
            <span class="emoji">📖</span>
            <span class="label">Manual</span>
        </a>
    </nav>
</aside>
