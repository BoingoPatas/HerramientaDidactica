<!DOCTYPE html>
<?php
require_once __DIR__ . '/../config/Rutas.php';
require_once __DIR__ . '/../lib/Titulos.php';
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo generarTituloPagina($rol ?? 'Usuario', 'content'); ?></title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <link rel="stylesheet" href="app/view/css/home.css?v=1.1">
    <link rel="stylesheet" href="app/view/css/content-view.css?v=1.5">
</head>
<script src="app/view/js/navigation.js?v=1.0"></script>
<script src="app/view/js/manage.js?v=1.0"></script>
<body data-rol="<?php echo htmlspecialchars($rol ?? 'Usuario'); ?>">
<?php 
$isTeacher = isset($rol) && ($rol === 'Docente' || $rol === 'Administrador'); 
?>
    <div class="dashboard">
        <?php
        // Configurar variables para el sidebar
        $activePage = 'content';
        $pageTitle = generarSaludoBienvenida($rol ?? 'Usuario', $nombre_usuario);
        $headerMessage = generarMensajeHeader($rol ?? 'Usuario', 'content');
        include __DIR__ . '/includes/sidebar.php';
        include __DIR__ . '/includes/header.php';
        ?>

        <main class="content">
            <section class="content-main">
                <div class="page-header">
                    <div>
                        <h1><?php echo generarTituloEncabezado($rol ?? 'Usuario', 'content'); ?></h1>
                        <p class="page-sub"><?php echo generarSubtituloPagina($rol ?? 'Usuario', 'content'); ?></p>
                    </div>
                    <div style="margin-left:auto;display:flex;gap:8px;align-items:center;">
                        <?php if (($rol ?? 'Usuario') === 'Docente' || ($rol ?? '') === 'Administrador'): ?>
                        <button id="btn-add-unit" class="btn btn-primary" aria-label="Añadir nueva unidad">➕ Añadir Unidad</button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="units-container">
                    <div class="unit-selector">
                        <div class="form-group">
                            <div class="content-selector">
    <div class="trimestre-filter-container" style="margin-bottom: 20px;">
        <label for="trimestre-selector" style="font-weight: bold; display: block; margin-bottom: 5px;">Trimestre Actual:</label>
        <select id="trimestre-selector" class="unit-dropdown" style="width: 100%; max-width: 300px; margin-bottom: 10px;">
            <option value="1">1er Trimestre</option>
            <option value="2">2do Trimestre</option>
            <option value="3">3er Trimestre</option>
        </select>

        <?php if ($isTeacher): ?>
        <div class="trimestre-actions" style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">
            <label class="switch">
                <input type="checkbox" id="toggle-trimestre-visibility">
                <span class="slider round"></span>
            </label>
            <span style="font-size: 0.85rem; color: #667085;">Trimestre Visible para Estudiantes</span>
        </div>
        <?php endif; ?>
    </div>
        <div id="unit-selection-area">
            <label for="unit-dropdown">Selecciona una unidad para ver su contenido:</label>
            <select id="unit-dropdown" class="unit-dropdown" aria-controls="unit-content">
                <option value="">-- Elige una unidad --</option>
                <?php foreach ($units as $unit): ?>
                    <option value="<?php echo htmlspecialchars($unit['slug']); ?>"
                            data-id="<?php echo htmlspecialchars($unit['id']); ?>"
                            data-title="<?php echo htmlspecialchars($unit['titulo']); ?>"
                            data-description="<?php echo htmlspecialchars($unit['descripcion']); ?>">
                        <?php echo htmlspecialchars($unit['titulo']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="trimestre-bloqueado-msg" class="hidden">
            <i class="fas fa-lock"></i> Este trimestre aún no está disponible.
        </div>
    </div>
                        </div>

                    <div id="unit-content" class="unit-content hidden" aria-live="polite">
                        <div class="content-header">
                            <div class="unit-title-container">
                                <h2 id="unit-title"></h2>
                                <?php if (($rol ?? 'Usuario') === 'Docente' || ($rol ?? '') === 'Administrador'): ?>
                                <button id="btn-edit-unit-title" class="btn btn-small btn-edit" title="Editar título" aria-label="Editar título de la unidad">✏️</button>
                                <button id="btn-change-unit-order" class="btn btn-small btn-edit btn-order" title="Cambiar orden" aria-label="Cambiar orden de la unidad">🔢</button>
                                <button id="btn-toggle-unit-active" class="btn btn-small btn-toggle btn-toggle-active" title="Inhabilitar/Habilitar" aria-label="Inhabilitar o habilitar unidad">🔓</button>
                                <?php endif; ?>
                            </div>
                            <div class="unit-description-container">
                                <p id="unit-description" class="unit-description"></p>
                                <?php if (($rol ?? 'Usuario') === 'Docente' || ($rol ?? '') === 'Administrador'): ?>
                                <button id="btn-edit-unit-description" class="btn btn-small btn-edit" title="Editar descripción" aria-label="Editar descripción de la unidad">✏️</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="theory-content">
                            <!-- Sección Unificada de Contenido por Temas -->
                            <div class="unified-theory-section">
                                <div class="section-header">
                                    <h3>📚 Contenido de la Unidad</h3>
                                    <?php if (($rol ?? 'Usuario') === 'Docente' || ($rol ?? '') === 'Administrador'): ?>
                                    <div class="teacher-actions">
                                        <button id="btn-add-topic" class="btn btn-primary btn-small" aria-label="Añadir nuevo tema">
                                            ➕ Añadir Tema
                                        </button>
                                        <button id="btn-add-theory-content" class="btn btn-primary btn-small" aria-label="Añadir contenido general">
                                            ➕ Añadir Contenido General
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Contenido General (no asociado a temas) -->
                                <div id="general-theory-material" class="general-theory-material">
                                    <!-- El contenido teórico general se cargará aquí -->
                                </div>

                                <!-- Contenido por Temas (unificado) -->
                                <div id="unified-topics-content" class="unified-topics-content">
                                    <!-- Los temas con su contenido se cargarán aquí dinámicamente -->
                                </div>
                            </div>


                            <!-- Emotes SVC Container for Units -->
                            <div class="emotes-container" id="unit-emotes">
                                <button class="emote-button" data-emote="like" title="Me gusta" aria-label="Reaccionar con Me gusta">
                                    <img src="app/view/img/SVC Emotes Like@112.png" alt="Like">
                                    <span class="emote-tooltip">Me gusta</span>
                                </button>
                                <button class="emote-button" data-emote="happy" title="Feliz" aria-label="Reaccionar con Feliz">
                                    <img src="app/view/img/SVC Emotes Happy 1@112.png" alt="Happy">
                                    <span class="emote-tooltip">Feliz</span>
                                </button>
                                <button class="emote-button" data-emote="wow" title="Wow" aria-label="Reaccionar con Wow">
                                    <img src="app/view/img/SVC Emotes Wow =O 2@112.png" alt="Wow">
                                    <span class="emote-tooltip">Wow</span>
                                </button>
                                <button class="emote-button" data-emote="think" title="Pensar" aria-label="Reaccionar con Pensar">
                                    <img src="app/view/img/SVC Emotes Think Question@112.png" alt="Think">
                                    <span class="emote-tooltip">Pensar</span>
                                </button>
                                <button class="emote-button" data-emote="shock" title="Sorprendido" aria-label="Reaccionar con Sorprendido">
                                    <img src="app/view/img/SVC Emotes Shock@112.png" alt="Shock">
                                    <span class="emote-tooltip">Sorprendido</span>
                                </button>
                            </div>
                            <div class="emote-summary" id="unit-emote-summary" style="display: none;">
                                <!-- Emote summary will be populated dynamically -->
                            </div>
                        </div>
                    </div>

                    <div id="no-unit-selected" class="no-unit-selected">
                        <div class="placeholder-content">
                            <div class="placeholder-icon">📚</div>
                            <h3>Selecciona una unidad</h3>
                            <p>Elige una unidad del menú desplegable para ver el contenido teórico correspondiente.</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <?php include __DIR__ . '/includes/lsv_window.php'; ?>

    <!-- Modal del tour (solo para usuarios en tour) -->
    <div id="tour-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 600px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); padding: 24px;">
            <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                <h3 id="tour-title" style="margin: 0; color: #1f2937;">Tour guiado</h3>
                <button class="modal-close" id="close-tour" style="background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer;" aria-label="Cerrar tour">&times;</button>
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
                <button style="display: none; background: #6b7280; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;" id="prev-tour-step">⬅️ Anterior</button>
                <button style="background: #059669; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;" id="next-tour-step">Siguiente ➡️</button>
                <button style="display: none; background: #059669; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;" id="finish-tour">🎉 ¡Comenzar!</button>
            </div>
        </div>
    </div>

    <!-- Overlay para highlights del tour -->
    <div id="tour-overlay" class="hidden" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); z-index: 10000; display: none;">
        <div id="tour-highlight" style="position: absolute; background: rgba(255,200,0,0.3); border: 3px solid #ffa500; border-radius: 8px; pointer-events: none;"></div>
    </div>

    <!-- Modal para añadir contenido -->
    <div id="content-modal" class="mg-modal-overlay hidden">
        <div class="mg-modal">
            <div class="mg-modal-title">Gestionar Contenido Didáctico</div>
            <div class="mg-modal-body">
                <label for="content-type">Tipo de contenido:</label>
                <select id="content-type" required>
                    <option value="">-- Seleccionar tipo --</option>
                    <option value="texto">📝 Texto (Contenido teórico)</option>
                    <option value="documento">📄 Documento (PDF, Word, etc.)</option>
                    <option value="video">🎥 Video (YouTube, etc.)</option>
                    <option value="enlace">🔗 Enlace general</option>
                    <option value="imagen">🖼️ Imagen</option>
                </select>

                <label for="content-title">Título:</label>
                <input type="text" id="content-title" placeholder="Título del contenido" required>

                <div id="content-topic-section" class="content-section">
                    <label for="content-topic">Asociar a tema (opcional):</label>
                    <select id="content-topic" class="content-topic-select">
                        <option value="">-- Sin tema específico (contenido general) --</option>
                        <!-- Los temas se cargarán aquí dinámicamente -->
                    </select>
                    <small class="form-hint">Selecciona un tema para asociar este contenido a un tema específico</small>
                </div>

                <div id="content-text-section" class="content-section hidden">
                    <label for="content-text">Contenido:</label>
                    <textarea id="content-text" placeholder="Escribe el contenido teórico aquí..." rows="8"></textarea>
                </div>

                <div id="content-url-section" class="content-section hidden">
                    <label for="content-url">URL:</label>
                    <input type="url" id="content-url" placeholder="https://ejemplo.com/documento.pdf">
                    <small class="form-hint">El enlace se abrirá en una nueva ventana</small>
                </div>
            </div>
            <div class="mg-modal-actions">
                <button id="content-cancel" class="btn">Cancelar</button>
                <button id="content-save" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>

    <!-- Modal para visualizar contenido -->
    <div id="content-viewer-modal" class="modal hidden">
        <div class="modal-content content-viewer-modal">
            <div class="modal-header">
                <h3 id="content-viewer-title">Contenido</h3>
                <button class="modal-close" id="content-viewer-close" aria-label="Cerrar visor de contenido">&times;</button>
            </div>
            <div class="modal-body">
                <div id="content-viewer-container">
                    <!-- El contenido se cargará aquí dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para gestionar unidades -->
    <div id="unit-modal-create" class="mg-modal-overlay hidden">
        <div class="mg-modal">
            <div class="mg-modal-title">Añadir Nueva Unidad</div>
            <div class="mg-modal-body">
                <label for="unit-create-title">Título de la unidad:</label>
                <input type="text" id="unit-create-title" placeholder="Ej: Introducción a C" required>
                <small class="form-hint" style="color: var(--primary); font-weight: 600;">Nota: El prefijo "Unidad X:" se añadirá automáticamente.</small>

                <label for="unit-create-order">Orden (Posición):</label>
                <input type="number" id="unit-create-order" min="1" value="1" required>
                <small class="form-hint">Si el orden ya existe, las unidades posteriores se desplazarán y renombrarán automáticamente.</small>

                <label for="unit-create-description">Descripción (opcional):</label>
                <textarea id="unit-create-description" placeholder="Breve descripción de los objetivos de la unidad..." rows="4"></textarea>
                
                <div id="unit-create-error" class="error-message hidden"></div>
            </div>
            <div class="mg-modal-actions">
                <button id="unit-create-cancel" class="btn">Cancelar</button>
                <button id="unit-create-save" class="btn btn-primary">Crear Unidad</button>
            </div>
        </div>
    </div>

    <!-- Modal para gestionar temas -->
    <div id="topic-modal" class="mg-modal-overlay hidden">
        <div class="mg-modal">
            <div class="mg-modal-title" id="topic-modal-title">Añadir Tema</div>
            <div class="mg-modal-body">
                <input type="hidden" id="topic-id" value="">

                <label for="topic-name">Nombre del tema:</label>
                <input type="text" id="topic-name" placeholder="Ej: Variables Enteras" required>
                <small class="form-hint" style="color: var(--primary); font-weight: 600;">Nota: El prefijo "Tema X:" se añadirá automáticamente.</small>

                <label for="topic-order">Orden (Posición):</label>
                <input type="number" id="topic-order" min="1" value="1" required>
                <small class="form-hint">Si el orden ya existe, los temas posteriores se desplazarán y renombrarán automáticamente.</small>

                <label for="topic-description">Descripción (opcional):</label>
                <textarea id="topic-description" placeholder="Descripción breve del tema..." rows="3"></textarea>

                <div id="topic-error" class="error-message hidden"></div>
            </div>
            <div class="mg-modal-actions">
                <button id="topic-cancel" class="btn">Cancelar</button>
                <button id="topic-save" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>

    <!-- Modal para añadir ejercicios a temas -->
    <div id="exercise-modal" class="mg-modal-overlay hidden">
        <div class="mg-modal exercise-modal-grid" style="max-width: 90%; width: 800px; max-height: 90vh; overflow-y: auto;">
            <div class="mg-modal-title" id="exercise-modal-title">Añadir Ejercicio al Tema</div>
            <div class="mg-modal-body exercise-form-grid">
                <input type="hidden" id="exercise-topic-id" value="">
                <input type="hidden" id="exercise-unit-slug" value="">

                    <div class="form-column">
                        <div class="form-group">
                            <label for="exercise-title">Título del ejercicio:</label>
                            <input type="text" id="exercise-title" placeholder="Ej: Declaración de Variables" required maxlength="96">
                            <small class="form-hint">Máximo 96 caracteres</small>
                        </div>

                        <div class="form-group">
                            <label>Slug (identificador único):</label>
                            <div id="exercise-slug-display" class="slug-display">Se generará automáticamente desde el título</div>
                            <input type="hidden" id="exercise-slug" value="">
                            <small class="form-hint">El slug se genera automáticamente a partir del título del ejercicio</small>
                        </div>

                        <div class="form-group full-width">
                            <label for="exercise-instructions">Instrucciones:</label>
                            <textarea id="exercise-instructions" placeholder="Describe lo que el estudiante debe hacer..." rows="4" required></textarea>
                        </div>
                    </div>

                    <div class="form-column">
                        <div class="form-group full-width">
                            <label for="exercise-example">Ejemplo:</label>
                            <textarea id="exercise-example" placeholder="Proporciona un ejemplo de código..." rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="exercise-expected-output">Salida esperada:</label>
                            <textarea id="exercise-expected-output" placeholder="Ejemplo: 'El resultado es: 15'" rows="2"></textarea>
                            <small class="form-hint">Describe qué debe mostrar el programa cuando se ejecute correctamente</small>
                        </div>

                        <div class="form-group full-width">
                            <label for="exercise-solution">Solución:</label>
                            <textarea id="exercise-solution" placeholder="Proporciona la solución correcta..." rows="3"></textarea>
                        </div>
                    </div>

                <div id="exercise-error" class="error-message hidden"></div>
            </div>
            <div class="mg-modal-actions">
                <button id="exercise-cancel" class="btn">Cancelar</button>
                <button id="exercise-save" class="btn btn-primary">Guardar Ejercicio</button>
            </div>
        </div>
    </div>

    <!-- Modal para añadir evaluaciones a temas -->
    <div id="evaluation-modal" class="mg-modal-overlay hidden">
        <div class="mg-modal evaluation-modal-grid" style="max-width: 90%; width: 800px; max-height: 90vh; overflow-y: auto;">
            <div class="mg-modal-title" id="evaluation-modal-title">Añadir Evaluación al Tema</div>
            <div class="mg-modal-body evaluation-form-grid">
                <input type="hidden" id="evaluation-topic-id" value="">
                <input type="hidden" id="evaluation-unit-slug" value="">

                    <div class="form-column">
                        <div class="form-group">
                            <label for="evaluation-title">Título de la evaluación:</label>
                            <input type="text" id="evaluation-title" placeholder="Ej: Evaluación de Variables" required maxlength="96">
                            <small class="form-hint">Máximo 96 caracteres</small>
                        </div>

                        <div class="form-group">
                            <label>Slug (identificador único):</label>
                            <div id="evaluation-slug-display" class="slug-display">Se generará automáticamente desde el título</div>
                            <input type="hidden" id="evaluation-slug" value="">
                            <small class="form-hint">El slug se genera automáticamente a partir del título de la evaluación</small>
                        </div>

                        <div class="form-group full-width">
                            <label for="evaluation-instructions">Instrucciones:</label>
                            <textarea id="evaluation-instructions" placeholder="Describe lo que el estudiante debe hacer en la evaluación..." rows="4" required></textarea>
                        </div>
                    </div>

                    <div class="form-column">
                        <div class="form-group full-width">
                            <label for="evaluation-example">Ejemplo:</label>
                            <textarea id="evaluation-example" placeholder="Proporciona un ejemplo de código..." rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="evaluation-rubric">Código esperado:</label>
                            <textarea id="evaluation-rubric" placeholder='Ejemplo de código C que el estudiante debe producir...' rows="6"></textarea>
                            <small class="form-hint">Escribe el código solución. El sistema generará automáticamente la rúbrica de evaluación.</small>
                        </div>
                    </div>

                <div id="evaluation-error" class="error-message hidden"></div>
            </div>
            <div class="mg-modal-actions">
                <button id="evaluation-cancel" class="btn">Cancelar</button>
                <button id="evaluation-save" class="btn btn-primary">Guardar Evaluación</button>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para habilitar/deshabilitar unidades -->
    <div id="confirmation-modal" class="mg-modal-overlay hidden">
        <div class="mg-modal">
            <div class="mg-modal-title" id="confirmation-modal-title">Confirmar Acción</div>
            <div class="mg-modal-body">
                <div id="confirmation-message" class="confirmation-message">
                    <!-- El mensaje de confirmación se cargará aquí dinámicamente -->
                </div>
            </div>
            <div class="mg-modal-actions">
                <button id="confirmation-cancel" class="btn">Cancelar</button>
                <button id="confirmation-confirm" class="btn btn-primary">Confirmar</button>
            </div>
        </div>
    </div>

    <script src="app/view/js/routes_dynamic.php?v=1.0"></script>
    <script src="app/view/js/lsv.js?v=1.0"></script>
    <script src="app/view/js/unified-content.js?v=1.5"></script>
    <script src="app/view/js/tour.js?v=1.0"></script>
</body>
</html>
