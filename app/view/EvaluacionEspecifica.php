<?php
// app/view/EvaluacionEspecifica.php - Vista para evaluaciones específicas de temas
require_once __DIR__ . '/../config/Rutas.php';
require_once __DIR__ . '/../lib/Titulos.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <link rel="stylesheet" href="app/view/css/home.css?v=1.1">
</head>
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
            <div class="page-header">
                <div>
                    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <?php if (isset($evaluationData['topic'])): ?>
                        <p class="page-sub">Tema: <?php echo htmlspecialchars($evaluationData['topic']['nombre']); ?></p>
                    <?php endif; ?>
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
                            <h3><?php echo htmlspecialchars($evaluationData['exercise']['titulo']); ?></h3>
                        </div>
                        <div class="exercise-instructions">
                            <h4>Tu tarea</h4>
                            <p><?php echo htmlspecialchars($evaluationData['exercise']['instrucciones']); ?></p>
                        </div>
                        <div class="exercise-example">
                            <h4>Ejemplo</h4>
                            <pre><code><?php echo htmlspecialchars($evaluationData['exercise']['ejemplo']); ?></code></pre>
                        </div>
                    </div>
                </div>

                <!-- Panel derecho: Editor y resultados -->
                <div class="exercise-panel right-panel">
                    <div class="panel-header">
                        <h2>Editor de Evaluación</h2>
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
                                <textarea id="eval-code" class="code-editor" placeholder="// Escribe tu código en C aquí..." spellcheck="false"></textarea>
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
        </main>
    </div>

    <?php include __DIR__ . '/includes/lsv_window.php'; ?>

    <script src="app/view/js/routes_dynamic.php?v=1.0"></script>
    <script src="app/view/js/lsv.js?v=1.0"></script>
    <script>
        // JavaScript para la evaluación específica
        document.addEventListener('DOMContentLoaded', function() {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const evalCode = document.getElementById('eval-code');
            const lineNumbers = document.getElementById('line-numbers');
            const btnClear = document.getElementById('btn-clear');
            const btnSubmit = document.getElementById('btn-submit');
            const btnRetry = document.getElementById('btn-retry');
            const resultsContent = document.getElementById('results-content');
            let submitting = false;

            // Configuración de la evaluación
            const evaluationData = <?php echo json_encode($evaluationData); ?>;
            const topicId = evaluationData.topicId;
            const exerciseId = evaluationData.exerciseId;

            // Actualizar números de línea
            function updateLineNumbers() {
                const lines = evalCode.value.split('\n').length;
                let txt = '';
                for (let i = 1; i <= lines; i++) txt += i + '\n';
                lineNumbers.textContent = txt || '1';
            }

            // Sincronizar scroll de números de línea
            function syncLineNumbers() {
                if (!lineNumbers || !evalCode) return;
                lineNumbers.scrollTop = evalCode.scrollTop;
            }

            // Manejar tecla Tab
            function handleKeyDown(e) {
                if (e.key === 'Tab') {
                    e.preventDefault();
                    const start = evalCode.selectionStart;
                    const end = evalCode.selectionEnd;
                    const val = evalCode.value;
                    evalCode.value = val.substring(0, start) + '    ' + val.substring(end);
                    evalCode.selectionStart = evalCode.selectionEnd = start + 4;
                    updateLineNumbers();
                }
            }

            // Limpiar resultados
            function clearResults() {
                resultsContent.innerHTML = '<p class="no-results">Escribe tu código y presiona "Enviar" para ver el puntaje y criterios.</p>';
            }

            // Mostrar resultados
            function showScore(score, max, details) {
                const pct = Math.round((score / Math.max(1, max)) * 100);
                let html = '<div class="result-success"><h5>🎉 Resultado</h5>';
                html += `<p><b>Puntaje:</b> ${score}/${max} (${pct}%)</p></div>`;
                html += '<div class="expected-solution"><h5>Rúbrica</h5><ul class="feedback-list">';
                (details || []).forEach(d => {
                    const cls = d.correct ? 'success' : '';
                    html += `<li class="${cls}">${escapeHtml(d.label || '')}: ${d.correct ? '✅' : '❌'} (${d.points_awarded}/${d.points_total} pts) ${!d.correct && d.feedback ? '• ' + escapeHtml(d.feedback) : ''}</li>`;
                });
                html += '</ul></div>';
                resultsContent.innerHTML = html;
            }

            // Escapar HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Enviar evaluación
            async function submitEvaluation() {
                if (submitting) return;
                submitting = true;
                btnSubmit.disabled = true;

                const code = evalCode.value.trim();
                if (!code) {
                    alert('Escribe tu código antes de enviar.');
                    submitting = false;
                    return;
                }

                try {
                    const response = await fetch('index.php?action=evaluation_api', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'submit',
                            topic_id: topicId,
                            exercise_id: exerciseId,
                            code: code,
                            csrf_token: csrf
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        showScore(data.score, data.max, data.details);
                    } else {
                        alert(data.error || 'Error al enviar la evaluación');
                    }
                } catch (e) {
                    console.error('Error enviando evaluación:', e);
                    alert('Error de conexión');
                } finally {
                    submitting = false;
                    btnSubmit.disabled = false;
                }
            }

            // Reiniciar evaluación
            async function resetEvaluation() {
                try {
                    const response = await fetch('index.php?action=evaluation_api', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'reset',
                            topic_id: topicId,
                            exercise_id: exerciseId,
                            csrf_token: csrf
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        evalCode.value = '';
                        updateLineNumbers();
                        clearResults();
                    } else {
                        alert(data.error || 'Error al reiniciar');
                    }
                } catch (e) {
                    console.error('Error reiniciando evaluación:', e);
                    alert('Error de conexión');
                }
            }

            // Eventos
            evalCode.addEventListener('input', updateLineNumbers);
            evalCode.addEventListener('scroll', syncLineNumbers);
            evalCode.addEventListener('keydown', handleKeyDown);
            btnClear.addEventListener('click', () => {
                evalCode.value = '';
                updateLineNumbers();
                clearResults();
            });
            btnSubmit.addEventListener('click', submitEvaluation);
            btnRetry.addEventListener('click', resetEvaluation);

            // Configuración de scrollbars personalizados
            setupCustomScrollbars();
        });

        // Configuración de scrollbars personalizados
        function setupCustomScrollbars() {
            const textareaContainer = document.querySelector('.editor-textarea-container');
            const editorContainer = document.querySelector('.code-editor-container');
            if (!textareaContainer || !editorContainer || !evalCode) return;

            // Crear scrollbar vertical
            const verticalScrollbar = document.createElement('div');
            verticalScrollbar.className = 'custom-scrollbar custom-scrollbar-vertical';
            verticalScrollbar.innerHTML = '<div class="custom-scrollbar-thumb custom-scrollbar-thumb-vertical"></div>';

            // Crear scrollbar horizontal
            const horizontalScrollbar = document.createElement('div');
            horizontalScrollbar.className = 'custom-scrollbar custom-scrollbar-horizontal';
            horizontalScrollbar.innerHTML = '<div class="custom-scrollbar-thumb custom-scrollbar-thumb-horizontal"></div>';

            // Agregar scrollbar vertical al contenedor del textarea
            textareaContainer.appendChild(verticalScrollbar);

            // Agregar scrollbar horizontal al contenedor del editor (para que esté siempre abajo)
            editorContainer.appendChild(horizontalScrollbar);

            // Configurar eventos para scrollbar vertical
            setupVerticalScrollbar(textareaContainer, verticalScrollbar);

            // Configurar eventos para scrollbar horizontal
            setupHorizontalScrollbar(textareaContainer, horizontalScrollbar);

            // Actualizar scrollbars cuando cambie el contenido
            const updateScrollbars = () => {
                updateVerticalScrollbar(textareaContainer, verticalScrollbar);
                updateHorizontalScrollbar(textareaContainer, horizontalScrollbar);
            };

            // Observar cambios en el textarea
            evalCode.addEventListener('input', updateScrollbars);
            evalCode.addEventListener('scroll', () => {
                updateScrollbars();
                syncLineNumbers();
            });

            // Actualización inicial
            updateScrollbars();
        }

        function setupVerticalScrollbar(container, scrollbar) {
            const thumb = scrollbar.querySelector('.custom-scrollbar-thumb-vertical');
            let isDragging = false;
            let startY = 0;
            let startScrollTop = 0;

            // Evento de clic en el scrollbar
            scrollbar.addEventListener('click', (e) => {
                if (e.target === thumb) return;

                const rect = scrollbar.getBoundingClientRect();
                const clickY = e.clientY - rect.top;
                const scrollbarHeight = rect.height;
                const newScrollTop = (clickY / scrollbarHeight) * evalCode.scrollHeight;

                evalCode.scrollTop = newScrollTop;
                updateVerticalScrollbar(container, scrollbar);
            });

            // Eventos de arrastre del thumb
            thumb.addEventListener('mousedown', (e) => {
                isDragging = true;
                startY = e.clientY;
                startScrollTop = evalCode.scrollTop;
                thumb.classList.add('dragging');
                e.preventDefault();
            });

            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;

                const deltaY = e.clientY - startY;
                const scrollbarHeight = scrollbar.getBoundingClientRect().height;
                const scrollRatio = evalCode.scrollHeight / scrollbarHeight;
                const newScrollTop = startScrollTop + (deltaY * scrollRatio);

                evalCode.scrollTop = Math.max(0, Math.min(newScrollTop, evalCode.scrollHeight - evalCode.clientHeight));
                updateVerticalScrollbar(container, scrollbar);
            });

            document.addEventListener('mouseup', () => {
                if (isDragging) {
                    isDragging = false;
                    thumb.classList.remove('dragging');
                }
            });
        }

        function setupHorizontalScrollbar(container, scrollbar) {
            const thumb = scrollbar.querySelector('.custom-scrollbar-thumb-horizontal');
            let isDragging = false;
            let startX = 0;
            let startScrollLeft = 0;

            // Evento de clic en el scrollbar
            scrollbar.addEventListener('click', (e) => {
                if (e.target === thumb) return;

                const rect = scrollbar.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const scrollbarWidth = rect.width;
                const newScrollLeft = (clickX / scrollbarWidth) * evalCode.scrollWidth;

                evalCode.scrollLeft = newScrollLeft;
                updateHorizontalScrollbar(container, scrollbar);
            });

            // Eventos de arrastre del thumb
            thumb.addEventListener('mousedown', (e) => {
                isDragging = true;
                startX = e.clientX;
                startScrollLeft = evalCode.scrollLeft;
                thumb.classList.add('dragging');
                e.preventDefault();
            });

            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;

                const deltaX = e.clientX - startX;
                const scrollbarWidth = scrollbar.getBoundingClientRect().width;
                const scrollRatio = evalCode.scrollWidth / scrollbarWidth;
                const newScrollLeft = startScrollLeft + (deltaX * scrollRatio);

                evalCode.scrollLeft = Math.max(0, Math.min(newScrollLeft, evalCode.scrollWidth - evalCode.clientWidth));
                updateHorizontalScrollbar(container, scrollbar);
            });

            document.addEventListener('mouseup', () => {
                if (isDragging) {
                    isDragging = false;
                    thumb.classList.remove('dragging');
                }
            });
        }

        function updateVerticalScrollbar(container, scrollbar) {
            const thumb = scrollbar.querySelector('.custom-scrollbar-thumb-vertical');

            if (evalCode.scrollHeight <= evalCode.clientHeight) {
                scrollbar.style.display = 'none';
                return;
            }

            scrollbar.style.display = 'block';

            const scrollRatio = evalCode.clientHeight / evalCode.scrollHeight;
            const thumbHeight = Math.max(30, scrollRatio * scrollbar.clientHeight);
            const scrollTopRatio = evalCode.scrollTop / (evalCode.scrollHeight - evalCode.clientHeight);
            const thumbTop = scrollTopRatio * (scrollbar.clientHeight - thumbHeight);

            thumb.style.height = thumbHeight + 'px';
            thumb.style.transform = `translateY(${thumbTop}px)`;
        }

        function updateHorizontalScrollbar(container, scrollbar) {
            const thumb = scrollbar.querySelector('.custom-scrollbar-thumb-horizontal');

            if (evalCode.scrollWidth <= evalCode.clientWidth) {
                scrollbar.style.display = 'none';
                return;
            }

            scrollbar.style.display = 'block';

            const scrollRatio = evalCode.clientWidth / evalCode.scrollWidth;
            const thumbWidth = Math.max(30, scrollRatio * scrollbar.clientWidth);
            const scrollLeftRatio = evalCode.scrollLeft / (evalCode.scrollWidth - evalCode.clientWidth);
            const thumbLeft = scrollLeftRatio * (scrollbar.clientWidth - thumbWidth);

            thumb.style.width = thumbWidth + 'px';
            thumb.style.transform = `translateX(${thumbLeft}px)`;
        }
    </script>
</body>
</html>