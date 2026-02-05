<!DOCTYPE html>
<?php
require_once __DIR__ . '/../config/Rutas.php';
require_once __DIR__ . '/../lib/Titulos.php';
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo generarTituloPagina($rol ?? 'Usuario', 'settings'); ?></title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <link rel="stylesheet" href="app/view/css/home.css?v=1.1">
</head>
<script src="app/view/js/navigation.js?v=1.0"></script>
<body>
    <div class="dashboard">
        <?php
        // Configurar variables para el sidebar
        $activePage = 'settings';
        $pageTitle = generarSaludoBienvenida($rol ?? 'Usuario', $nombre_usuario);
        $headerMessage = generarMensajeHeader($rol ?? 'Usuario', 'settings');
        include __DIR__ . '/includes/sidebar.php';
        include __DIR__ . '/includes/header.php';
        ?>

        <main class="content">
            <section class="content-main">
                <div class="page-header">
                    <div>
                        <h1><?php echo generarTituloEncabezado($rol ?? 'Usuario', 'settings'); ?></h1>
                        <p class="page-sub"><?php echo generarSubtituloPagina($rol ?? 'Usuario', 'settings'); ?></p>
                    </div>
                </div>

                <div class="settings-container">
                    <div class="settings-grid">
                        <!-- Cambiar nombre -->
                        <div class="card settings-card">
                            <h3>👤 Cambiar nombre de usuario</h3>
                            <p>Actualiza tu nombre de usuario. Este nombre es visible en la plataforma.</p>
                            <div style="margin-top: 20px;">
                                <div style="margin-bottom: 10px;">
                                    <label for="current-name" style="display: block; margin-bottom: 5px; font-weight: 500;">Nombre actual:</label>
                                    <input type="text" id="current-name" class="form-input" value="<?php echo htmlspecialchars($userData['nombre_usuario'] ?? $_SESSION['usuario']); ?>" readonly>
                                </div>
                                <button class="btn btn-primary" id="btn-change-name">✏️ Cambiar nombre</button>
                            </div>
                        </div>

                        <!-- Cambiar contraseña -->
                        <div class="card settings-card">
                            <h3>🔐 Cambiar contraseña</h3>
                            <p>Actualiza tu contraseña. Debe tener al menos 8 caracteres, incluyendo mayúscula, minúscula, número y carácter especial.</p>
                            <button class="btn btn-primary" id="btn-change-password">🗝️ Cambiar contraseña</button>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal para cambiar nombre -->
    <div id="name-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); padding: 24px;">
            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 16px 0; color: #1f2937;">Cambiar nombre de usuario</h3>
                <p style="margin: 0; color: #6b7280; font-size: 14px;">Ingresa tu nuevo nombre de usuario.</p>
            </div>
            <div style="margin-bottom: 24px;">
                <label for="new-name" style="display: block; margin-bottom: 8px; font-weight: 500;">Nuevo nombre:</label>
                <input type="text" id="new-name" class="form-input" placeholder="Ingresa el nuevo nombre" style="width: 100%;">
                <small style="color: #6b7280; margin-top: 5px; display: block;">El nombre debe ser único en la plataforma.</small>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button class="btn btn-secondary" id="cancel-name-change">Cancelar</button>
                <button class="btn btn-primary" id="confirm-name-change">Confirmar cambios</button>
            </div>
        </div>
    </div>

    <!-- Modal para cambiar contraseña -->
    <div id="password-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); padding: 24px;">
            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 16px 0; color: #1f2937;">Cambiar contraseña</h3>
                <p style="margin: 0; color: #6b7280; font-size: 14px;">Ingresa tu contraseña actual y la nueva.</p>
            </div>
            <form id="password-change-form" style="margin-bottom: 24px;">
                <div style="margin-bottom: 16px;">
                    <label for="old-password" style="display: block; margin-bottom: 8px; font-weight: 500;">Contraseña actual:</label>
                    <input type="password" id="old-password" class="form-input" placeholder="Ingresa tu contraseña actual" style="width: 100%;">
                </div>
                <div style="margin-bottom: 16px;">
                    <label for="new-password" style="display: block; margin-bottom: 8px; font-weight: 500;">Nueva contraseña:</label>
                    <input type="password" id="new-password" class="form-input" placeholder="Ingresa la nueva contraseña" style="width: 100%;">
                    <small id="password-feedback" style="color: #6b7280; margin-top: 5px; display: block;"></small>
                </div>
                <div>
                    <label for="confirm-password" style="display: block; margin-bottom: 8px; font-weight: 500;">Confirmar nueva contraseña:</label>
                    <input type="password" id="confirm-password" class="form-input" placeholder="Repite la nueva contraseña" style="width: 100%;">
                </div>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 8px; margin-top: 16px;">
                    <small style="color: #374151; line-height: 1.4;">
                        <strong>Requisitos:</strong><br>
                        • Al menos 8 caracteres<br>
                        • Una letra mayúscula<br>
                        • Una letra minúscula<br>
                        • Un número<br>
                        • Un carácter especial (!@#$%^&*...)
                    </small>
                </div>
            </form>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button class="btn btn-secondary" id="cancel-password-change">Cancelar</button>
                <button class="btn btn-primary" id="confirm-password-change">Confirmar cambios</button>
            </div>
        </div>
    </div>

    <!-- Modal de alerta general -->
    <div id="alert-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); padding: 24px; text-align: center;">
            <h3 id="alert-title" style="margin: 0 0 16px 0; color: #1f2937;"></h3>
            <p id="alert-message" style="margin: 0 0 20px 0; color: #6b7280;"></p>
            <button class="btn btn-primary" id="alert-close">Aceptar</button>
        </div>
    </div>

    <script src="app/view/js/routes_dynamic.php?v=1.0"></script>
    <script src="app/view/js/configuracion.js?v=1.0"></script>
</body>
</html>
