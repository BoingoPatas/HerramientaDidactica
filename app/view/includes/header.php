<?php
// app/view/includes/header.php - Header compartido
require_once __DIR__ . '/../../lib/Titulos.php';
?>
<header class="topbar">
    <div class="welcome"><?php echo isset($headerMessage) ? $headerMessage : (isset($pageTitle) ? $pageTitle : generarSaludoBienvenida($rol ?? 'Usuario', $nombre_usuario)); ?></div>
    <div class="user-menu" aria-haspopup="true" aria-expanded="false">
        <div style="display:flex;gap:8px;align-items:center;">
            <button id="btn-toggle-lsv" type="button" class="btn btn-small lsv-toggle" aria-pressed="false" title="LSV  👋">👋 LSV</button>
            <div class="chip" aria-label="Usuario actual">
                <div class="line">
                    <span aria-hidden="true">👤</span>
                    <span><?php echo htmlspecialchars($nombre_usuario); ?></span>
                    <?php if (isset($dashboardData) && ($dashboardData['racha_dias'] ?? 0) > 0): ?>
                    <span class="streak-badge" title="Racha de días">🔥 <?php echo $dashboardData['racha_dias']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="rol"><?php echo htmlspecialchars($rol ?? 'Usuario'); ?></div>
            </div>
                <div class="dropdown" role="menu" aria-label="Menú del usuario">
                    <a href="index.php?page=home#configuracion" class="action-log" data-action="config_abrir" role="menuitem">⚙️ Configuración</a>
                    <a href="#" onclick="showLogoutConfirmation()" role="menuitem">🔓 Cerrar sesión</a>
                </div>
        </div>
    </div>
</header>

<!-- Modal de confirmación de cierre de sesión -->
<div id="logout-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 10001; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); padding: 24px; text-align: center;">
        <h3 style="margin: 0 0 16px 0; color: #1f2937;">Confirmar cierre de sesión</h3>
        <p style="margin: 0 0 20px 0; color: #6b7280;">¿Está seguro que quiere cerrar sesión?</p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button class="btn btn-secondary" id="cancel-logout">No</button>
            <button class="btn btn-danger" id="confirm-logout">Sí</button>
        </div>
    </div>
</div>

<script src="app/view/js/validation.js?v=1.0"></script>
<script>
// Función para mostrar confirmación de cierre de sesión
window.showLogoutConfirmation = function() {
    const logoutModal = document.getElementById('logout-modal');
    if (logoutModal) {
        logoutModal.style.display = 'flex';
    }
};

// Elementos del modal de logout
const logoutModal = document.getElementById('logout-modal');
const cancelLogout = document.getElementById('cancel-logout');
const confirmLogout = document.getElementById('confirm-logout');

// Cerrar modal de logout al hacer click en "No"
if (cancelLogout) {
    cancelLogout.addEventListener('click', function() {
        if (logoutModal) logoutModal.style.display = 'none';
    });
}

// Confirmar logout al hacer click en "Sí"
if (confirmLogout) {
    confirmLogout.addEventListener('click', function() {
        window.location.href = 'index.php?action=logout';
    });
}

// Cerrar modal al hacer click fuera
if (logoutModal) {
    logoutModal.addEventListener('click', function(e) {
        if (e.target === logoutModal) {
            logoutModal.style.display = 'none';
        }
    });
}
</script>
