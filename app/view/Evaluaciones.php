<?php
// app/view/Evaluaciones.php - VISTA de evaluaciones
require_once __DIR__ . '/../config/Rutas.php';
require_once __DIR__ . '/../lib/Titulos.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo generarTituloPagina($rol ?? 'Usuario', 'evaluation'); ?></title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <link rel="stylesheet" href="app/view/css/home.css?v=1.1">
</head>
<script src="app/view/js/navigation.js?v=1.0"></script>
<script src="app/view/js/manage.js?v=1.0"></script>
<body data-rol="<?php echo htmlspecialchars($rol ?? 'Usuario'); ?>">
    <div class="dashboard">
        <?php
        // Configurar variables para el sidebar y header
        $activePage = 'evaluation';
        $pageTitle = generarSaludoBienvenida($rol ?? 'Usuario', $nombre_usuario);
        $headerMessage = generarMensajeHeader($rol ?? 'Usuario', 'evaluation');
        include __DIR__ . '/includes/sidebar.php';
        include __DIR__ . '/includes/header.php';
        ?>

        <main class="content exercise-content">
            <!-- Vista de lista de evaluaciones estilo Contenido -->
            <section id="eval-list-view" aria-labelledby="eval-list-title">
                <div class="page-header">
                    <div>
                        <h1 id="eval-list-title"><?php echo generarTituloEncabezado($rol ?? 'Usuario', 'evaluation'); ?></h1>
                        <p class="page-sub"><?php echo generarSubtituloPagina($rol ?? 'Usuario', 'evaluation'); ?></p>
                    </div>
                </div>
                <div class="units-grid" id="eval-grid"></div>
            </section>

            <!-- Vista de intento estilo Ejercicio -->
            <section id="eval-attempt-view" class="hidden" aria-labelledby="h-eval-attempt">
                <div class="page-header">
                    <div>
                        <button id="eval-back-header" class="btn btn-secondary" style="display: none;">← Volver</button>
                        <h1 id="eval-title-header" style="display: none;"></h1>
                    </div>
                </div>
                <div class="exercise-container">
                    <!-- Panel izquierdo: Instrucciones -->
                    <div class="exercise-panel left-panel">
                        <div class="panel-header">
                            <h2>Instrucciones</h2>
                        </div>
                        <div class="panel-content">
                            <div class="exercise-info">
                                <h3 id="eval-desc">Descripción</h3>
                            </div>
                            <div class="exercise-instructions">
                                <h4>Tu tarea <button id="edit-instructions" class="btn-small edit-btn" style="display:none;">Editar</button></h4>
                                <p id="eval-instructions">...</p>
                            </div>
                        <div class="exercise-example">
                            <h4>Ejemplo <button id="edit-example" class="btn-small edit-btn" style="display:none;">Editar</button></h4>
                            <pre><code id="eval-example"></code></pre>
                        </div>
                        </div>
                    </div>

                    <!-- Panel derecho: Editor y resultados -->
                    <div class="exercise-panel right-panel">
                        <div class="panel-header">
                            <h2>Editor de Código</h2>
                            <div class="editor-actions">
                                <button id="btn-clear" class="btn btn-secondary">Limpiar</button>
                                <button id="btn-submit" class="btn btn-primary">Enviar</button>
                                <button id="btn-retry" class="btn btn-secondary">Reintentar</button>
                            </div>
                        </div>
                        <div class="panel-content">
                            <div class="code-editor-container">
                                <div class="editor-header">
                                    <span class="file-name">evaluacion.c</span>
                                    <span class="language-indicator">C</span>
                                </div>
                                <div class="editor-textarea-container">
                                    <div class="line-numbers" id="line-numbers">1</div>
                                    <textarea id="eval-code" class="code-editor" placeholder="// Escribe tu código en C aquí...
// Ejemplo: int variable = 10;" spellcheck="false"></textarea>
                                </div>
                            </div>

                            <div class="results-panel" id="results-panel">
                                <div class="results-header">
                                    <h4>Resultados de la Evaluación</h4>
                                </div>
                                <div class="results-content" id="results-content">
                                    <p class="no-results">Escribe tu código y presiona "Enviar" para ver el puntaje y criterios.</p>
                                </div>
                            </div>
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
                <button style="display: none; background: #6b7280; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;" id="prev-tour-step">Anterior</button>
                <button style="background: #059669; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;" id="next-tour-step">Siguiente</button>
                <button style="display: none; background: #059669; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;" id="finish-tour">¡Comenzar!</button>
            </div>
        </div>
    </div>

    <!-- Overlay para highlights del tour -->
    <div id="tour-overlay" class="hidden" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); z-index: 10000; display: none;">
        <div id="tour-highlight" style="position: absolute; background: rgba(255,200,0,0.3); border: 3px solid #ffa500; border-radius: 8px; pointer-events: none;"></div>
    </div>

    <script src="app/view/js/routes_dynamic.php?v=1.0"></script>
    <script src="app/view/js/lsv.js?v=1.0"></script>
    <script src="app/view/js/evaluation.js?v=1.0"></script>
    <script src="app/view/js/tour.js?v=1.0"></script>
</body>
</html>
