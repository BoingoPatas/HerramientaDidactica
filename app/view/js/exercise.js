(function() {
    // Variables globales
    let codeEditor;
    let lineNumbers;
    let checkButton;
    let clearButton;
    let resultsContent;
    let successModal;
    let isChecking = false;

    // Inicialización
    document.addEventListener('DOMContentLoaded', function() {
        initializeElements();
        setupEventListeners();
        setupCodeEditor();
        setupCustomScrollbars();
        logExerciseAccess();
        enableInlineEditForExercise();
    });

    function initializeElements() {
        codeEditor = document.getElementById('code-editor');
        lineNumbers = document.getElementById('line-numbers');
        checkButton = document.getElementById('check-code');
        clearButton = document.getElementById('clear-code');
        resultsContent = document.getElementById('results-content');
        successModal = document.getElementById('success-modal');
    }

    function setupEventListeners() {
        // Botón verificar código
        if (checkButton) {
            checkButton.addEventListener('click', checkCode);
        }

        // Botón limpiar código
        if (clearButton) {
            clearButton.addEventListener('click', clearCode);
        }

        // Editor de código
        if (codeEditor) {
            codeEditor.addEventListener('input', updateAndResizeIfNeeded);
            codeEditor.addEventListener('keydown', handleKeyDown);
        }

        // Modal de éxito
        setupModalEvents();
    }

    function setupCodeEditor() {
        if (!codeEditor) return;

        // Configurar el editor
        codeEditor.style.fontFamily = 'Consolas, Monaco, "Courier New", monospace';
        codeEditor.style.fontSize = '14px';
        codeEditor.style.lineHeight = '1.5';

        // Actualizar números de línea iniciales
        const initialLineCount = updateLineNumbers();
        currentLineCount = initialLineCount;

        // Auto-resize textarea
        autoResizeCodeEditor();

        // Configurar contenedor para scroll
        const container = codeEditor.parentElement;
        if (container) {
            container.addEventListener('scroll', syncLineNumbers);
        }


    }

    function updateAndResizeIfNeeded() {
        const newLineCount = updateLineNumbers();
        currentLineCount = newLineCount;
        autoResizeCodeEditor();
    }

    function updateLineNumbers() {
        if (!codeEditor || !lineNumbers) return;

        const lines = codeEditor.value.split('\n');
        const lineCount = lines.length;

        let numbersHTML = '';
        for (let i = 1; i <= lineCount; i++) {
            numbersHTML += i + '\n';
        }

        lineNumbers.textContent = numbersHTML;

        return lineCount;
    }

    function syncLineNumbers() {
        if (!lineNumbers || !codeEditor) return;
        lineNumbers.scrollTop = codeEditor.scrollTop;
    }

    function autoResizeCodeEditor() {
        if (!codeEditor) return;

        const container = codeEditor.parentElement;

        // Calculate height based on number of lines (let it grow naturally)
        const minHeight = 64; // Minimum editor height, matching CSS min-height
        const lineHeight = 21; // 14px font size * 1.5 line height
        const newHeight = Math.max(minHeight, minHeight + (currentLineCount - 1) * lineHeight);

        // Set the textarea height to accommodate all content
        codeEditor.style.height = 'auto';
        codeEditor.style.height = Math.max(newHeight, codeEditor.scrollHeight) + 'px';

        // Set line numbers height to match
        if (lineNumbers) {
            lineNumbers.style.height = codeEditor.style.height;
        }

        // Set container to fixed height - it will show scrollbar when contents exceed
        const headerHeight = 45; // Height of .editor-header
        const maxContainerHeight = headerHeight + 20 + 100; // 100px is 25% of the original 400px editor area
        container.style.height = maxContainerHeight + 'px';
    }



    function handleKeyDown(e) {
        // Manejar tabulación
        if (e.key === 'Tab') {
            e.preventDefault();
            const start = codeEditor.selectionStart;
            const end = codeEditor.selectionEnd;
            
            // Insertar 4 espacios
            const value = codeEditor.value;
            codeEditor.value = value.substring(0, start) + '    ' + value.substring(end);
            
            // Mantener cursor en posición correcta
            codeEditor.selectionStart = codeEditor.selectionEnd = start + 4;
        }
        
        // Manejar auto-completado básico
        if (e.key === '{') {
            setTimeout(() => {
                const start = codeEditor.selectionStart;
                const value = codeEditor.value;
                const beforeCursor = value.substring(0, start);
                const afterCursor = value.substring(start);
                
                if (!afterCursor.startsWith('}')) {
                    codeEditor.value = beforeCursor + '\n    \n}' + afterCursor;
                    codeEditor.selectionStart = codeEditor.selectionEnd = start + 5;
                }
            }, 0);
        }
    }

    function clearCode() {
        if (!codeEditor) return;

        codeEditor.value = '';
        updateAndResizeIfNeeded();
        clearResults();

        // Log de la acción
        logAction('codigo_limpiar', 'Usuario limpió el editor de código');
    }

    async function checkCode() {
        if (!codeEditor || isChecking) return;

        const code = codeEditor.value.trim();
        if (!code) {
            showError('Por favor, escribe algo de código antes de verificar.');
            return;
        }

        // Obtener datos del ejercicio
        const unit = codeEditor.getAttribute('data-unit');
        const exercise = codeEditor.getAttribute('data-exercise');

        if (!unit || !exercise) {
            showError('Error: No se pudo identificar el ejercicio.');
            return;
        }

        // Deshabilitar botón y mostrar loading
        isChecking = true;
        checkButton.disabled = true;
        checkButton.textContent = '🔄 Verificando...';

        try {
            // Enviar código al servidor
            const checkUrl = (window.APP_ROUTES && window.APP_ROUTES.action_check_code) ? window.APP_ROUTES.action_check_code : 'index.php?action=check_code';
            const response = await fetch(checkUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    code: code,
                    unit: unit,
                    exercise: exercise,
                    csrf_token: getCSRFToken()
                })
            });

            // Try to parse JSON response; if server returned plain text (warnings/errors), handle gracefully
            const rawText = await response.text();
            let result = null;
            try {
                result = JSON.parse(rawText);
            } catch (err) {
                console.error('Respuesta no-JSON del servidor:', rawText);
                // Mostrar texto del servidor si viene algo útil, o un mensaje genérico
                showError([rawText || 'Respuesta inesperada del servidor. Por favor, intenta de nuevo.']);
                return;
            }

            if (result && result.success) {
                showSuccess(result.feedback);
                updateProgress(unit, exercise);
                
                // Mostrar modal de éxito después de un breve delay
                setTimeout(() => {
                    showSuccessModal();
                }, 1000);
            } else {
                // Mostrar feedback si existe, si no mostrar el campo error como fallback
                const feedback = result.feedback || (result.error ? [result.error] : ['Error procesando la solicitud. Por favor, intenta de nuevo.']);
                showError(feedback, result.expected);
            }

        } catch (error) {
            console.error('Error verificando código:', error);
            showError(['Error de conexión. Por favor, intenta de nuevo.']);
        } finally {
            // Rehabilitar botón
            isChecking = false;
            checkButton.disabled = false;
            checkButton.textContent = '✅ Verificar Código';
        }
    }

    function showSuccess(feedback) {
        if (!resultsContent) return;

        let html = '<div class="result-success">';
        html += '<h5>🎉 ¡Código correcto!</h5>';
        
        if (Array.isArray(feedback) && feedback.length > 0) {
            html += '<ul class="feedback-list">';
            feedback.forEach(item => {
                html += `<li class="success">${escapeHtml(item)}</li>`;
            });
            html += '</ul>';
        }
        
        html += '</div>';
        resultsContent.innerHTML = html;
    }

    function showError(feedback, expected = null) {
        if (!resultsContent) return;

        let html = '<div class="result-error">';
        html += '<h5>❌ Código incorrecto</h5>';
        
        if (Array.isArray(feedback) && feedback.length > 0) {
            html += '<ul class="feedback-list">';
            feedback.forEach(item => {
                html += `<li>${escapeHtml(item)}</li>`;
            });
            html += '</ul>';
        } else if (typeof feedback === 'string') {
            html += `<p>${escapeHtml(feedback)}</p>`;
        }
        
        if (expected) {
            html += '<div class="expected-solution">';
            html += '<h5>💡 Solución esperada:</h5>';
            html += `<pre><code>${escapeHtml(expected)}</code></pre>`;
            html += '</div>';
        }
        
        html += '</div>';
        resultsContent.innerHTML = html;
    }

    function clearResults() {
        if (!resultsContent) return;
        resultsContent.innerHTML = '<p class="no-results">Escribe tu código y presiona "Verificar Código" para ver los resultados.</p>';
    }

    function showSuccessModal() {
        if (!successModal) return;
        successModal.classList.remove('hidden');
    }

    function hideSuccessModal() {
        if (!successModal) return;
        successModal.classList.add('hidden');
    }

    function setupModalEvents() {
        // Botón cerrar modal
        const closeBtn = document.getElementById('close-success-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', hideSuccessModal);
        }

        // Botón siguiente ejercicio
        const nextBtn = document.getElementById('next-exercise');
        if (nextBtn) {
            nextBtn.addEventListener('click', goToNextExercise);
        }

        // Cerrar modal solo al hacer clic en el botón de cerrar
        // Se eliminó el cierre al hacer clic fuera para evitar pérdida accidental de datos
        // Los modales solo se pueden cerrar mediante los botones de cancelar/cerrar
    }

    function goToNextExercise() {
    const unit = codeEditor?.getAttribute('data-unit');
    const currentExercise = parseInt(codeEditor?.getAttribute('data-exercise') || '1');

    if (!unit) return;

    // Determinar siguiente ejercicio
    const nextExercise = currentExercise + 1;

    // Verificar si existe el siguiente ejercicio (esto se podría mejorar con datos del servidor)
    const maxExercises = {
        'variables': 2,
        'operadores': 1,
        'condicionales': 1,
        'bucles': 1
    };

    if (nextExercise <= (maxExercises[unit] || 1)) {
        // Ir al siguiente ejercicio a través del router index.php
        window.location.href = `index.php?page=exercise&unit=${encodeURIComponent(unit)}&exercise=${encodeURIComponent(nextExercise)}`;
    } else {
        // Ir de vuelta al contenido mediante el router
        if (window.APP_ROUTES && window.APP_ROUTES.content) {
            window.location.href = window.APP_ROUTES.content;
        } else {
            window.location.href = 'index.php?page=content';
        }
    }
    }

    async function updateProgress(unit, exercise) {
        try {
            // Persistir ejercicio completado en servidor
            const progressUrl = (window.APP_ROUTES && window.APP_ROUTES.action_progress) ? window.APP_ROUTES.action_progress : 'index.php?action=progress';
            const res = await fetch(progressUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ unit, exercise, completed: true, csrf_token: getCSRFToken() })
            });
            const data = await res.json();
            if (!data.success) {
                console.warn('No se pudo guardar progreso en servidor:', data.error || data);
            }
            // Log del progreso
            logAction('ejercicio_completado', `${unit} - ejercicio ${exercise}`);
            // Si la página de contenido está abierta en otra pestaña/ventana, intenta recargar su progreso
            if (window.ContentPage && typeof window.ContentPage.reloadFromServer === 'function') {
                try { window.ContentPage.reloadFromServer(); } catch(e) { /* noop */ }
            }
        } catch (e) {
            console.warn('Error actualizando progreso en servidor:', e);
        }
    }

    function getCSRFToken() {
        const metaCsrf = document.querySelector('meta[name="csrf-token"]');
        return metaCsrf ? metaCsrf.getAttribute('content') : '';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function logExerciseAccess() {
        const unit = codeEditor?.getAttribute('data-unit');
        const exercise = codeEditor?.getAttribute('data-exercise');
        
        if (unit && exercise) {
            logAction('ejercicio_inicio', `${unit} - ejercicio ${exercise}`);
        }
    }

    function enableInlineEditForExercise(){
        const rol = document.body.getAttribute('data-rol') || 'Usuario';
        if (!['Docente', 'Administrador'].includes(rol)) return;
        document.querySelectorAll('.edit-btn').forEach(btn => btn.style.display = 'inline-block');
        document.getElementById('edit-instructions')?.addEventListener('click', e => startInlineEdit('exercise-instructions'));
        document.getElementById('edit-example')?.addEventListener('click', e => startInlineEdit('exercise-example'));
    }

    async function startInlineEdit(id) {
        const field = id.replace('exercise-', '');
        const el = document.getElementById(id);
        if (!el || el._editing) return; el._editing = true;
        const original = el.tagName === 'UL' ? el.innerHTML : el.textContent;
        const input = document.createElement('textarea'); input.value = original; input.style.width = '100%';
        const actions = document.createElement('div'); actions.style.display = 'flex'; actions.style.justifyContent = 'flex-end'; actions.style.gap = '8px'; actions.style.marginTop = '8px';
        const btnCancel = document.createElement('button'); btnCancel.className = 'btn'; btnCancel.textContent = 'Cancelar';
        const btnSave = document.createElement('button'); btnSave.className = 'btn btn-primary'; btnSave.textContent = 'Guardar';
        actions.appendChild(btnCancel); actions.appendChild(btnSave);
        el.style.display = 'none';
        el.after(input, actions);
        btnCancel.addEventListener('click', () => { input.remove(); actions.remove(); el.style.display = ''; el._editing = false; });
        btnSave.addEventListener('click', async () => {
            const newValue = input.value.trim();
            el.textContent = newValue;
            input.remove(); actions.remove(); el.style.display = ''; el._editing = false;
            try {
                const unit = codeEditor.getAttribute('data-unit');
                const exercise = codeEditor.getAttribute('data-exercise');
                const manageUrl = (window.APP_ROUTES && window.APP_ROUTES.action_manage) ? window.APP_ROUTES.action_manage : 'index.php?action=manage';
                const res = await fetch(manageUrl, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({
                        action: 'update_exercise', unit, exercise, fields: { [field]: newValue }, csrf_token: getCSRFToken()
                    })
                });
                const data = await res.json();
                if (data.success) {
                    if (window.manageJS && typeof window.manageJS.showNotification === 'function') window.manageJS.showNotification('Ejercicio actualizado', 'success');
                } else {
                    if (window.manageJS) window.manageJS.showNotification('Error al guardar: ' + (data.error || 'Error'), 'error');
                    el.textContent = original; // revert
                }
            } catch (err) {
                if (window.manageJS) window.manageJS.showNotification('Error de conexión', 'error');
                el.textContent = original;
            }
        });
    }



    // Configuración de scrollbars personalizados
    function setupCustomScrollbars() {
        const textareaContainer = document.querySelector('.editor-textarea-container');
        const editorContainer = document.querySelector('.code-editor-container');
        if (!textareaContainer || !editorContainer || !codeEditor) return;

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
        codeEditor.addEventListener('input', updateScrollbars);
        codeEditor.addEventListener('scroll', () => {
            updateScrollbars();
            syncLineNumbers();
            // Actualizar scrollbars si existen
            if (typeof updateVerticalScrollbar === 'function') {
                const container = document.querySelector('.editor-textarea-container');
                const verticalScrollbar = container?.querySelector('.custom-scrollbar-vertical');
                const horizontalScrollbar = document.querySelector('.code-editor-container .custom-scrollbar-horizontal');
                if (container && verticalScrollbar && horizontalScrollbar) {
                    updateVerticalScrollbar(container, verticalScrollbar);
                    updateHorizontalScrollbar(container, horizontalScrollbar);
                }
            }
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
            const newScrollTop = (clickY / scrollbarHeight) * codeEditor.scrollHeight;

            codeEditor.scrollTop = newScrollTop;
            updateVerticalScrollbar(container, scrollbar);
        });

        // Eventos de arrastre del thumb
        thumb.addEventListener('mousedown', (e) => {
            isDragging = true;
            startY = e.clientY;
            startScrollTop = codeEditor.scrollTop;
            thumb.classList.add('dragging');
            e.preventDefault();
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;

            const deltaY = e.clientY - startY;
            const scrollbarHeight = scrollbar.getBoundingClientRect().height;
            const scrollRatio = codeEditor.scrollHeight / scrollbarHeight;
            const newScrollTop = startScrollTop + (deltaY * scrollRatio);

            codeEditor.scrollTop = Math.max(0, Math.min(newScrollTop, codeEditor.scrollHeight - codeEditor.clientHeight));
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
            const newScrollLeft = (clickX / scrollbarWidth) * codeEditor.scrollWidth;

            codeEditor.scrollLeft = newScrollLeft;
            updateHorizontalScrollbar(container, scrollbar);
        });

        // Eventos de arrastre del thumb
        thumb.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX;
            startScrollLeft = codeEditor.scrollLeft;
            thumb.classList.add('dragging');
            e.preventDefault();
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;

            const deltaX = e.clientX - startX;
            const scrollbarWidth = scrollbar.getBoundingClientRect().width;
            const scrollRatio = codeEditor.scrollWidth / scrollbarWidth;
            const newScrollLeft = startScrollLeft + (deltaX * scrollRatio);

            codeEditor.scrollLeft = Math.max(0, Math.min(newScrollLeft, codeEditor.scrollWidth - codeEditor.clientWidth));
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

        if (codeEditor.scrollHeight <= codeEditor.clientHeight) {
            scrollbar.style.display = 'none';
            return;
        }

        scrollbar.style.display = 'block';

        const scrollRatio = codeEditor.clientHeight / codeEditor.scrollHeight;
        const thumbHeight = Math.max(30, scrollRatio * scrollbar.clientHeight);
        const scrollTopRatio = codeEditor.scrollTop / (codeEditor.scrollHeight - codeEditor.clientHeight);
        const thumbTop = scrollTopRatio * (scrollbar.clientHeight - thumbHeight);

        thumb.style.height = thumbHeight + 'px';
        thumb.style.transform = `translateY(${thumbTop}px)`;
    }

    function updateHorizontalScrollbar(container, scrollbar) {
        const thumb = scrollbar.querySelector('.custom-scrollbar-thumb-horizontal');

        if (codeEditor.scrollWidth <= codeEditor.clientWidth) {
            scrollbar.style.display = 'none';
            return;
        }

        scrollbar.style.display = 'block';

        const scrollRatio = codeEditor.clientWidth / codeEditor.scrollWidth;
        const thumbWidth = Math.max(30, scrollRatio * scrollbar.clientWidth);
        const scrollLeftRatio = codeEditor.scrollLeft / (codeEditor.scrollWidth - codeEditor.clientWidth);
        const thumbLeft = scrollLeftRatio * (scrollbar.clientWidth - thumbWidth);

        thumb.style.width = thumbWidth + 'px';
        thumb.style.transform = `translateX(${thumbLeft}px)`;
    }

    // Función para registrar acciones
    async function logAction(action, detail = '') {
        try {
            const logUrl = (window.APP_ROUTES && window.APP_ROUTES.action_log) ? window.APP_ROUTES.action_log : 'index.php?action=log';
            const res = await fetch(logUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action,
                    detail,
                    csrf_token: getCSRFToken()
                })
            });

            const data = await res.json();
            if (!data.success) {
                console.warn('Log fallo:', data.error || data);
            }
        } catch (e) {
            console.warn('Error enviando log:', e);
        }
    }

    // Exponer funciones globalmente si es necesario
    window.ExercisePage = {
        checkCode,
        clearCode,
        showSuccessModal,
        hideSuccessModal
    };
})();
