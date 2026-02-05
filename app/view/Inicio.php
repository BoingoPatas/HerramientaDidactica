<!DOCTYPE html>
<?php
// Incluir las funciones de rutas y títulos
require_once __DIR__ . '/../config/Rutas.php';
require_once __DIR__ . '/../lib/Titulos.php';
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo generarTituloPagina($rol ?? 'Usuario', 'home'); ?></title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <link rel="stylesheet" href="app/view/css/home.css?v=1.1">
</head>
<script src="app/view/js/navigation.js?v=1.0"></script>
<body>
    <div class="dashboard">
        <?php
        // Configurar variables para el sidebar
        $activePage = 'home';
        $activeSection = 'inicio';
        $pageTitle = generarSaludoBienvenida($rol ?? 'Usuario', $nombre_usuario);
        include __DIR__ . '/includes/sidebar.php';
        ?>

        <?php include __DIR__ . '/includes/header.php'; ?>

        <main class="content">
            <!-- Inicio -->
            <section id="section-inicio" data-section="inicio" aria-labelledby="h-inicio">
                <div class="page-header">
                    <div>
                        <h1 id="h-inicio"><?php echo generarTituloEncabezado($rol ?? 'Usuario', 'home'); ?></h1>
                        <p class="page-sub"><?php echo generarSubtituloPagina($rol ?? 'Usuario', 'home'); ?></p>
                    </div>
                </div>
                <section class="stats-grid" aria-label="Estadísticas">
                    <div class="card" role="article" aria-label="Unidades completadas">
                        <p class="kpi"><span aria-hidden="true">🎯</span> Unidades completadas</p>
                        <div class="value"><?php echo htmlspecialchars($dashboardData['unidades_completadas'] ?? 0); ?>/4</div>
                        <div class="trend">Total del curso</div>
                    </div>
                    <div class="card" role="article" aria-label="Última unidad">
                        <p class="kpi"><span aria-hidden="true">🧩</span> Última unidad</p>
                        <div class="value"><?php echo htmlspecialchars($dashboardData['ultima_unidad'] ?? 'Ninguna'); ?></div>
                        <div class="trend">En progreso</div>
                    </div>
                    <div class="card" role="article" aria-label="Progreso total">
                        <p class="kpi"><span aria-hidden="true">⏱️</span> Progreso total</p>
                        <div class="value"><?php echo htmlspecialchars($dashboardData['progreso_total'] ?? 0); ?>%</div>
                        <div class="trend">Del curso completo</div>
                    </div>
                    <div class="card" role="article" aria-label="Racha de días">
                        <p class="kpi"><span aria-hidden="true">🔥</span> Racha de días</p>
                        <div class="value"><?php echo htmlspecialchars($dashboardData['racha_dias'] ?? 0); ?></div>
                        <div class="trend">Próximamente</div>
                    </div>
                </section>
            </section>

            <!-- Contenido (rol-dependiente) -->
            <section id="section-contenido" class="hidden" data-section="contenido" aria-labelledby="h-contenido">
                <div class="page-header">
                    <div>
                        <h1 id="h-contenido"><?php echo generarTituloEncabezado($rol ?? 'Usuario', 'content'); ?></h1>
                        <p class="page-sub"><?php echo generarSubtituloPagina($rol ?? 'Usuario', 'content'); ?></p>
                    </div>
                </div>
                <?php if (($rol ?? 'Usuario') === 'Usuario'): ?>
                <div class="grid">
                    <div class="card"><h3>Programación en C ✍️</h3><p>Aprende programación básica en C con ejercicios interactivos.</p><a href="index.php?page=content" class="btn">🚀 Comenzar</a></div>
                    <div class="card"><h3>Realizar evaluaciones 📝</h3><p>Evalúa tus conocimientos.</p><a href="index.php?page=evaluation" class="btn">Abrir</a></div>
                </div>
                <?php elseif (($rol ?? 'Usuario') === 'Docente'): ?>
                <div class="grid">
                    <div class="card"><h3>Añadir contenido ➕</h3><p>Crea nuevo material didáctico.</p><button class="btn action-log" data-action="contenido_agregar">Añadir</button></div>
                    <div class="card"><h3>Modificar contenido ✏️</h3><p>Edita materiales existentes.</p><button class="btn action-log" data-action="contenido_modificar">Modificar</button></div>
                    <div class="card"><h3>Organizar contenido 🗂️</h3><p>Ordena módulos y lecciones.</p><button class="btn action-log" data-action="contenido_organizar">Organizar</button></div>
                    <div class="card"><h3>Eliminar contenido 🗑️</h3><p>Retira contenido no necesario.</p><button class="btn btn-danger action-log" data-action="contenido_eliminar">Eliminar</button></div>
                </div>
                <?php else: ?>
                <div class="card">El rol <?php echo htmlspecialchars($rol); ?> no tiene acciones específicas en Contenido.</div>
                <?php endif; ?>
            </section>

            <!-- Manual (disponible para todos) -->
            <section id="section-manual" class="hidden" data-section="manual" aria-labelledby="h-manual">
                <div class="page-header">
                    <div>
                        <h1 id="h-manual"><?php echo generarTituloEncabezado($rol ?? 'Usuario', 'manual'); ?></h1>
                        <p class="page-sub"><?php echo generarSubtituloPagina($rol ?? 'Usuario', 'manual'); ?></p>
                    </div>
                    <div style="margin-left:auto;display:flex;gap:8px;align-items:center;">
                        <button id="btn-tour-manual" class="btn btn-primary" onclick="window.startTour()" style="background:#3b82f6;border-color:#2563eb;">🚀 Repetir Tour</button>
                    </div>
                </div>
                <div class="card" id="manual-pages" role="region" aria-live="polite" aria-atomic="true"></div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:10px;">
                    <button class="btn" id="btn-prev" type="button" aria-label="Página anterior">⬅️ Anterior</button>
                    <div style="color:var(--muted);">Página <span id="page-current">1</span>/<span id="page-total">1</span></div>
                    <button class="btn" id="btn-next" type="button" aria-label="Página siguiente">Siguiente ➡️</button>
                </div>
            </section>

            <!-- Usuarios (solo Docente) -->
            <?php if (($rol ?? 'Usuario') === 'Docente'): ?>
            <section id="section-usuarios" class="hidden" data-section="usuarios" aria-labelledby="h-usuarios">
                <div class="page-header"><h1 id="h-usuarios">Gestión de usuarios (rango Usuario) 👥</h1></div>

                <!-- Barra de herramientas unificada (igual que Docentes) -->
                <div class="toolbar" style="margin-bottom: 20px; display: flex; gap: 12px; align-items: center;">
                    <button class="btn btn-primary" id="btn-new-user">➕ Añadir usuario</button>
                    <button class="btn btn-primary" id="btn-edit-user" style="display: none;">✏️ Modificar usuario</button>
                    <button class="btn btn-success" id="btn-toggle-user" style="display: none;">🚫 Inhabilitar usuario</button>
                    <span id="selected-user" style="color: var(--muted); margin-left: auto;"></span>
                </div>

                <!-- Lista de usuarios -->
                <div id="users-list-container">
                    <div class="card">
                        <div id="users-list" style="min-height: 200px;">
                            <p style="text-align: center; color: var(--muted);">Cargando usuarios...</p>
                        </div>
                    </div>
                </div>

                

                <!-- Mensaje de selección -->
                <div id="user-actions-info" class="card" style="margin-top: 20px; background: var(--panel-2);">
                    <p style="text-align: center; color: var(--muted); margin: 0;">
                        💡 <strong>Instrucciones:</strong> Selecciona un usuario de la lista para habilitar las acciones de modificación e inhabilitación.
                    </p>
                </div>
            </section>
            <?php endif; ?>

            <!-- Evaluaciones (Usuario y Docente) -->
            <?php if (in_array(($rol ?? 'Usuario'), ['Usuario','Docente'], true)): ?>
            <section id="section-evaluaciones" class="hidden" data-section="evaluaciones" aria-labelledby="h-evaluaciones">
                <div class="page-header"><h1 id="h-evaluaciones">Evaluaciones 📝</h1></div>
                <?php if (($rol ?? 'Usuario') === 'Usuario'): ?>
                <div class="grid">
                    <div class="card"><h3>Realizar evaluación</h3><p>Comienza una evaluación.</p><a href="index.php?page=evaluation" class="btn">Iniciar</a></div>
                </div>
                <?php else: ?>
                <div class="grid">
                    <div class="card"><h3>Añadir evaluación ➕</h3><p>Crea una nueva evaluación.</p><button class="btn action-log" data-action="evaluacion_agregar">Añadir</button></div>
                    <div class="card"><h3>Modificar evaluación ✏️</h3><p>Edita evaluaciones existentes.</p><button class="btn action-log" data-action="evaluacion_modificar">Modificar</button></div>
                    <div class="card"><h3>Eliminar evaluación 🗑️</h3><p>Retira evaluaciones.</p><button class="btn btn-danger action-log" data-action="evaluacion_eliminar">Eliminar</button></div>
                </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>

                <!-- Secciones (solo Administrador) -->
                <?php if (($rol ?? 'Usuario') === 'Administrador'): ?>
                <section id="section-secciones" class="hidden" data-section="secciones" aria-labelledby="h-secciones">
                    <div class="page-header">
                        <div>
                            <h1 id="h-secciones">Secciones 📊</h1>
                            <p class="page-sub">Revisa las secciones y estadísticas 📈</p>
                        </div>
                    </div>

                    <div id="sections-grid" class="sections-grid">
                        <!-- Secciones cargadas dinámicamente -->
                    </div>
                </section>
                <?php endif; ?>

            <!-- Docentes (solo Administrador) -->
            <?php if (($rol ?? 'Usuario') === 'Administrador'): ?>
            <section id="section-docentes" class="hidden" data-section="docentes" aria-labelledby="h-docentes">
                <div class="page-header">
                    <div>
                        <h1 id="h-docentes">Gestión de docentes 🏫</h1>
                        <p class="page-sub">Administra los docentes del sistema</p>
                    </div>
                </div>

                <!-- Barra de herramientas unificada -->
                <div class="toolbar" style="margin-bottom: 20px; display: flex; gap: 12px; align-items: center;">
                    <button class="btn btn-primary" id="btn-new-docente">➕ Añadir docente</button>
                    <button class="btn btn-primary" id="btn-edit-docente" style="display: none;">✏️ Modificar docente</button>
                    <button class="btn btn-danger" id="btn-toggle-docente" style="display: none;">🚫 Inhabilitar docente</button>
                    <span id="selected-docente" style="color: var(--muted); margin-left: auto;"></span>
                </div>

                <!-- Lista de docentes -->
                <div id="docentes-list-container">
                    <div class="card">
                        <div id="docentes-list" style="min-height: 200px;">
                            <p style="text-align: center; color: var(--muted);">Cargando docentes...</p>
                        </div>
                    </div>
                </div>

                <!-- Mensaje de selección -->
                <div id="docente-actions-info" class="card" style="margin-top: 20px; background: var(--panel-2);">
                    <p style="text-align: center; color: var(--muted); margin: 0;">
                        💡 <strong>Instrucciones:</strong> Selecciona un docente de la lista para habilitar las acciones de modificación e inhabilitación.
                    </p>
                </div>
            </section>
            <?php endif; ?>

            <!-- Reportes (solo Administrador) -->
            <?php if (($rol ?? 'Usuario') === 'Administrador'): ?>
            <section id="section-reportes" class="hidden" data-section="reportes" aria-labelledby="h-reportes">

                <div class="card">
                    <h3>📋 Lista de acciones realizadas</h3>
                    <div id="bitacora-list" style="max-height: 600px; overflow-y: auto;">
                        <?php if (!empty($bitacoraData)): ?>
                            <table class="mg-table reports-table">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;">Usuario</th>
                                        <th style="width: 55%;">Acción</th>
                                        <th style="width: 20%;">Fecha y Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bitacoraData as $registro): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($registro['usuario']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($registro['accion']); ?></td>
                                            <td style="font-size: 0.9em; color: var(--muted);">
                                                <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($registro['fecha']))); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--muted); padding: 40px;">
                                No hay registros de acciones disponibles.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- Configuración (disponible para todos) -->
            <section id="section-configuracion" class="hidden" data-section="configuracion" aria-labelledby="h-configuracion">
                <div class="page-header">
                    <div>
                        <h1 id="h-configuracion">⚙️ Configuración de cuenta</h1>
                        <p class="page-sub">Gestiona tu nombre de usuario y contraseña</p>
                    </div>
                </div>

                <div class="settings-container">
                    <div class="settings-grid">
                        <!-- Cambiar nombre -->
                        <div class="card settings-card">
                            <h3>👤 Cambiar nombre de usuario</h3>
                            <p>Actualiza tu nombre de usuario. Este nombre es visible en la plataforma.</p>
                            <!-- COMENTADO ESTO QUE ERA EL "CAMPO DE NOMBRE ACTUAL" QUE ME DIJERON QUE QUITARA COMO 30 VECES
                            <div style="margin-top: 20px;">
                                <div style="margin-bottom: 10px;">
                                    <label for="current-name" style="display: block; margin-bottom: 5px; font-weight: 500;">Nombre actual:</label>
                                    <input type="text" id="current-name" class="form-input" value="<?php echo htmlspecialchars($nombre_usuario); ?>" readonly>
                                </div>
                                -->
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

    <?php include __DIR__ . '/includes/lsv_window.php'; ?>



    <!-- Modales para gestión de docentes son creados dinámicamente por manage.js -->
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
                <button type="button" class="btn btn-secondary" id="cancel-password-change">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirm-password-change">Confirmar cambios</button>
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

    <!-- Modal de bienvenida y tour -->
    <div id="welcome-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); padding: 24px;">
            <div style="margin-bottom: 24px;">
                <h3 style="margin: 0 0 16px 0; color: #1f2937;">🎉 ¡Bienvenido a la Herramienta Didáctica con Enfoque Inclusivo a Discapacidad Auditiva!</h3>
            </div>
            <div style="margin-bottom: 24px; line-height: 1.5;">
                <p style="margin: 0 0 16px 0;"><?php echo generarSaludoBienvenida($rol ?? 'Usuario', $nombre_usuario); ?></p>
                <p style="margin: 0 0 16px 0;">Esta plataforma está diseñada para enseñarte programación en C de manera accesible, especialmente considerando las necesidades de personas con discapacidad auditiva. Incluye elementos visuales, lenguajes de señas LSV y navegación intuitiva.</p>
                <div style="margin: 20px 0; padding: 15px; background: #f3f4f6; border-radius: 8px;">
                    <strong>¿Quieres que te guiemos paso a paso por todas las funciones disponibles?</strong>
                    <br><small style="color: #6b7280;">El tour es opcional y puedes verlo cuando quieras desde el menú Manual.</small>
                </div>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button style="background: #6b7280; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;" id="skip-tour">▶️ Empieza sin guía</button>
                <button style="background: #059669; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;" id="start-tour">✨ ¡Sí, guíame!</button>
            </div>
        </div>
    </div>

    <!-- Modal del tour -->
    <div id="tour-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 600px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); padding: 24px;">
            <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                <h3 id="tour-title" style="margin: 0; color: #1f2937;">Tour guiado</h3>
                <button class="modal-close" id="close-tour" style="background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer;">&times;</button>
            </div>
            <div style="margin-bottom: 20px;">
                <div id="tour-content" aria-live="polite" style="line-height: 1.5;">
                    <!-- Contenido del tour se carga dinámicamente -->
                </div>
                <div id="tour-progress" style="margin: 15px 0;">
                    <div style="display: flex; justify-content: center; gap: 5px;">
                        <!-- Puntos de progreso -->
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button class="btn btn-secondary" id="prev-tour-step" style="display: none;">⬅️ Anterior</button>
                <button class="btn btn-primary" id="next-tour-step">Siguiente ➡️</button>
                <button class="btn" id="finish-tour" style="display: none;">🎉 ¡Comenzar!</button>
            </div>
        </div>
    </div>



    <!-- Overlay para highlights del tour -->
    <div id="tour-overlay" class="hidden" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); z-index: 10000; display: none;">
        <div id="tour-highlight" style="position: absolute; background: rgba(255,200,0,0.3); border: 3px solid #ffa500; border-radius: 8px; pointer-events: none;"></div>
    </div>

    <script src="app/view/js/routes_dynamic.php?v=1.0"></script>
    <script src="app/view/js/validation.js?v=1.0"></script>
    <script src="app/view/js/home.js?v=1.0"></script>
    <script>
        // Mostrar modal de bienvenida para nuevos usuarios
        <?php if ($primera_vez): ?>
        const modal = document.getElementById('welcome-modal');
        if (modal) {
            modal.style.display = 'flex';
            modal.style.zIndex = '9999';
        }
        <?php endif; ?>
    </script>
    <script src="app/view/js/tour.js?v=1.0"></script>

    <!-- JS for configuracion section -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Elementos del modal de nombre
            const btnChangeName = document.getElementById('btn-change-name');
            const nameModal = document.getElementById('name-modal');
            const cancelNameChange = document.getElementById('cancel-name-change');
            const confirmNameChange = document.getElementById('confirm-name-change');
            const newNameInput = document.getElementById('new-name');

            // Elementos del modal de contraseña
            const btnChangePassword = document.getElementById('btn-change-password');
            const passwordModal = document.getElementById('password-modal');
            const cancelPasswordChange = document.getElementById('cancel-password-change');
            const confirmPasswordChange = document.getElementById('confirm-password-change');
            const oldPasswordInput = document.getElementById('old-password');
            const newPasswordInput = document.getElementById('new-password');
            const confirmPasswordInput = document.getElementById('confirm-password');
            const passwordFeedback = document.getElementById('password-feedback');

            // Modal de alerta
            const alertModal = document.getElementById('alert-modal');
            const alertTitle = document.getElementById('alert-title');
            const alertMessage = document.getElementById('alert-message');
            const alertClose = document.getElementById('alert-close');

            // Validación de contraseña en tiempo real
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                let feedback = '';

                const checks = [
                    { regex: /.{8,}/, message: 'Al menos 8 caracteres' },
                    { regex: /[A-Z]/, message: 'Una mayúscula' },
                    { regex: /[a-z]/, message: 'Una minúscula' },
                    { regex: /\d/, message: 'Un número' },
                    { regex: /[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/, message: 'Un carácter especial' }
                ];

                const passedChecks = checks.filter(check => check.regex.test(password)).length;
                const totalChecks = checks.length;

                if (password.length > 0) {
                    feedback = `${passedChecks}/${totalChecks} requisitos cumplidos`;
                    if (passedChecks === totalChecks) {
                        passwordFeedback.style.color = '#059669'; // verde
                    } else {
                        passwordFeedback.style.color = '#dc2626'; // rojo
                    }
                } else {
                    feedback = '';
                }

                passwordFeedback.textContent = feedback;
            });

            // Modal de nombre
            if (btnChangeName) {
                btnChangeName.addEventListener('click', function() {
                    newNameInput.value = '';
                    newNameInput.focus();
                    if (nameModal) nameModal.style.display = 'flex';
                });
            }

            if (cancelNameChange) {
                cancelNameChange.addEventListener('click', function() {
                    if (nameModal) nameModal.style.display = 'none';
                });
            }

            if (confirmNameChange) {
                confirmNameChange.addEventListener('click', async function() {
                    const newName = newNameInput.value.trim();

                    if (!newName) {
                        showAlert('Error', 'Por favor ingresa un nombre válido.');
                        return;
                    }

                    try {
                        const response = await fetch('index.php?action=update_name', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                name: newName,
                                csrf_token: csrfToken
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            showAlert('Éxito', 'Nombre de usuario actualizado correctamente.');
                            if (nameModal) nameModal.style.display = 'none';
                            // Actualizar el campo de nombre actual
                            const currentNameField = document.getElementById('current-name');
                            if (currentNameField) currentNameField.value = newName;
                            // Actualizar el header title
                            const headerTitle = document.querySelector('#h-inicio span');
                            if (headerTitle) headerTitle.textContent = newName;
                        } else {
                            showAlert('Error', result.error || 'Error al actualizar el nombre.');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showAlert('Error', 'Error de conexión. Inténtalo de nuevo.');
                    }
                });
            }

            // Modal de contraseña
            if (btnChangePassword) {
                btnChangePassword.addEventListener('click', function() {
                    oldPasswordInput.value = '';
                    newPasswordInput.value = '';
                    confirmPasswordInput.value = '';
                    passwordFeedback.textContent = '';
                    if (passwordModal) passwordModal.style.display = 'flex';
                    if (oldPasswordInput) oldPasswordInput.focus();
                });
            }

            if (cancelPasswordChange) {
                cancelPasswordChange.addEventListener('click', function() {
                    if (passwordModal) passwordModal.style.display = 'none';
                });
            }

            if (confirmPasswordChange) {
                confirmPasswordChange.addEventListener('click', async function() {
                    const oldPassword = oldPasswordInput.value;
                    const newPassword = newPasswordInput.value;
                    const confirmPassword = confirmPasswordInput.value;

                    if (!oldPassword || !newPassword || !confirmPassword) {
                        showAlert('Error', 'Todos los campos son obligatorios.');
                        return;
                    }

                    // Validar contraseña nueva
                    const checks = [
                        { regex: /.{8,}/, message: 'Al menos 8 caracteres' },
                        { regex: /[A-Z]/, message: 'Una mayúscula' },
                        { regex: /[a-z]/, message: 'Una minúscula' },
                        { regex: /\d/, message: 'Un número' },
                        { regex: /[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/, message: 'Un carácter especial' }
                    ];

                    const allChecksPass = checks.every(check => check.regex.test(newPassword));
                    if (!allChecksPass) {
                        showAlert('Error', 'La nueva contraseña no cumple con los requisitos mínimos.');
                        return;
                    }

                    if (newPassword !== confirmPassword) {
                        showAlert('Error', 'La nueva contraseña y la confirmación no coinciden.');
                        return;
                    }

                    try {
                        const response = await fetch('index.php?action=update_password', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                old_password: oldPassword,
                                new_password: newPassword,
                                confirm_password: confirmPassword,
                                csrf_token: csrfToken
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            showAlert('Éxito', 'Contraseña actualizada correctamente.');
                            if (passwordModal) passwordModal.style.display = 'none';
                        } else {
                            showAlert('Error', result.error || 'Error al actualizar la contraseña.');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showAlert('Error', 'Error de conexión. Inténtalo de nuevo.');
                    }
                });
            }

            // Función para mostrar alertas
            function showAlert(title, message) {
                if (alertTitle) alertTitle.textContent = title;
                if (alertMessage) alertMessage.textContent = message;
                if (alertModal) alertModal.style.display = 'flex';
            }

            // Cerrar modal de alerta
            if (alertClose) {
                alertClose.addEventListener('click', function() {
                    if (alertModal) alertModal.style.display = 'none';
                });
            }

            // Cerrar modales al hacer click fuera
            if (nameModal) {
                nameModal.addEventListener('click', function(e) {
                    if (e.target === nameModal) {
                        nameModal.style.display = 'none';
                    }
                });
            }

            if (passwordModal) {
                passwordModal.addEventListener('click', function(e) {
                    if (e.target === passwordModal) {
                        passwordModal.style.display = 'none';
                    }
                });
            }

            if (alertModal) {
                alertModal.addEventListener('click', function(e) {
                    if (e.target === alertModal) {
                        alertModal.style.display = 'none';
                    }
                });
            }


        });
    </script>
</body>
</html>
