<?php
// Protección contra acceso directo a la vista
if (!defined('APP_LOADED')) { header('Location: index.php'); exit(); }
require_once __DIR__ . '/../lib/Titulos.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo generarTituloPagina($rol ?? 'Usuario', 'exercise', htmlspecialchars($exerciseData['title'])); ?></title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <link rel="stylesheet" href="app/view/css/home.css?v=1.1">
</head>
<body data-rol="<?php echo htmlspecialchars($rol ?? 'Usuario'); ?>">
    <div class="dashboard">
        <?php
        // Configurar variables para el sidebar y header
        $activePage = 'practices';
        $pageTitle = generarSaludoBienvenida($rol ?? 'Usuario', $nombre_usuario);
        include __DIR__ . '/includes/sidebar.php';
        include __DIR__ . '/includes/header.php';
        ?>

        <main class="content exercise-content">
            <div class="exercise-container">
                <!-- Panel izquierdo: Instrucciones del ejercicio -->
                <div class="exercise-panel left-panel">
                    <div class="panel-header">
                        <h2><?php echo esLenguajeInformal($rol ?? 'Usuario') ? '📋 Instrucciones del ejercicio' : '📋 Instrucciones del Ejercicio'; ?></h2>
                    </div>
                    
                    <div class="panel-content">
                        <div class="exercise-info">
                            <h3><?php echo htmlspecialchars($exerciseData['title']); ?></h3>
                            <p class="exercise-description"><?php echo htmlspecialchars($exerciseData['description']); ?></p>
                        </div>

                        <div class="exercise-instructions">
                            <h4>🎯 Tu tarea: <button id="edit-instructions" class="btn-small edit-btn" style="display:none;">✏️</button></h4>
                            <p id="exercise-instructions"><?php echo htmlspecialchars($exerciseData['instructions']); ?></p>
                        </div>

                        <?php if (!empty($exerciseData['example'])): ?>
                        <div class="exercise-example">
                            <h4>💡 Ejemplo: <button id="edit-example" class="btn-small edit-btn" style="display:none;">✏️</button></h4>
                            <pre><code id="exercise-example"><?php echo htmlspecialchars($exerciseData['example']); ?></code></pre>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Panel derecho: Editor de código -->
                <div class="exercise-panel right-panel">
                    <div class="panel-header">
                        <h2>💻 Editor de Código</h2>
                        <div class="editor-actions">
                            <button id="clear-code" class="btn btn-secondary">🗑️ Limpiar</button>
                            <button id="check-code" class="btn btn-primary">✅ Verificar Código</button>
                        </div>
                    </div>
                    
                    <div class="panel-content">
                        <div class="code-editor-container">
                            <div class="editor-header">
                                <span class="file-name">ejercicio.c</span>
                                <span class="language-indicator">C</span>
                            </div>
                            <div class="editor-textarea-container">
                                <div class="line-numbers" id="line-numbers">1</div>
                                <textarea
                                    id="code-editor"
                                    class="code-editor"
                                    placeholder="// Escribe tu código aquí...
// Ejemplo: int variable = 10;"
                                    spellcheck="false"
                                    autocomplete="off"
                                    data-unit="<?php echo htmlspecialchars($unit); ?>"
                                    data-exercise="<?php echo htmlspecialchars($exercise); ?>"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Panel de resultados -->
                        <div class="results-panel" id="results-panel">
                            <div class="results-header">
                                <h4>📝 Resultados de la Verificación</h4>
                            </div>
                            <div class="results-content" id="results-content">
                                <p class="no-results">Escribe tu código y presiona "Verificar Código" para ver los resultados.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/includes/lsv_window.php'; ?>

    <!-- Modal de éxito -->
    <div id="success-modal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🎉 ¡Excelente trabajo!</h3>
                <button class="modal-close" id="close-success-modal">&times;</button>
            </div>
                <div class="modal-body">
                    <p>Has completado el ejercicio correctamente. ¿Qué te gustaría hacer ahora?</p>
                    <div class="modal-actions">
                        <a href="index.php?page=practices" class="btn btn-secondary">← Volver a las Unidades</a>
                        <button id="next-exercise" class="btn btn-primary">➡️ Siguiente ejercicio</button>
                    </div>
                </div>
        </div>
    </div>

    <script src="app/view/js/navigation.js?v=1.0"></script>
    <script src="app/view/js/routes_dynamic.php?v=1.0"></script>
    <script src="app/view/js/lsv.js?v=1.0"></script>
    <script src="app/view/js/exercise.js?v=1.0"></script>
</body>
</html>
