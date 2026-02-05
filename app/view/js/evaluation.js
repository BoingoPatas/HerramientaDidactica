// app/view/js/evaluation.js - JavaScript para el sistema de evaluaciones
(function(){
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const grid = document.getElementById('eval-grid');
    const listView = document.getElementById('eval-list-view');
    const attemptView = document.getElementById('eval-attempt-view');
    const evalBack = document.getElementById('eval-back-header');

    // Elementos del intento
    const evalDesc = document.getElementById('eval-desc');
    const evalInstr = document.getElementById('eval-instructions');
    const evalExample = document.getElementById('eval-example');
    const evalCode = document.getElementById('eval-code');
    const lineNumbers = document.getElementById('line-numbers');
    const btnClear = document.getElementById('btn-clear');
    const btnSubmit = document.getElementById('btn-submit');
    const btnRetry = document.getElementById('btn-retry');
    const resultsContent = document.getElementById('results-content');

    let currentKey = '';
    let currentDef = null;
    let currentState = { attempt_used: 0, score: 0 };
let submitting = false;

/** Muestra la vista de lista de evaluaciones. */
function showList(){
        listView.classList.remove('hidden');
        attemptView.classList.add('hidden');
        updateHeaderForList();
    }
    /** Muestra la vista de intento de evaluación. */
    function showAttempt(){
        listView.classList.add('hidden');
        attemptView.classList.remove('hidden');
        // el encabezado se actualiza en openAttempt
    }

    /** Devuelve el ícono correspondiente a una unidad dada. */
    function unitIcon(unit){
        switch(unit){
            case 'variables': return '📊';
            case 'operadores': return '🔢';
            case 'condicionales': return '🔀';
            case 'bucles': return '🔄';
            default: return '📝';
        }
    }

    /** Convierte el estado en un porcentaje de progreso. */
    function stateToProgress(state){
        if (!state || !state.attempt_used) return 0;
        const s = parseInt(state.score || 0, 10);
        return Math.max(0, Math.min(100, s));
    }

    function stateLabel(state){
        if (!state) return 'No iniciado';
        if (state.attempt_used) return `Completada • Puntaje: ${state.score}/100`;
        return 'No iniciado';
    }

    async function loadList(){
        grid.innerHTML = '';
        try {
            const evalApiUrl = (window.APP_ROUTES && window.APP_ROUTES.action_evaluation_api) ? window.APP_ROUTES.action_evaluation_api : 'index.php?action=evaluation_api';
            const res = await fetch(evalApiUrl);
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Error');
            // Fusionar con las ediciones de evaluación guardadas localmente (localStorage)
            const localKey = 'hd_evaluations_local';
            let local = {};
            try { local = JSON.parse(localStorage.getItem(localKey) || '{}'); } catch(e){ local = {}; }
            const items = (data.evaluations || []).map(ev => {
                if (local[ev.key]) {
                    // fusionar título/descripción local si está presente
                    return Object.assign({}, ev, local[ev.key]);
                }
                return ev;
            });
            renderGrid(items || []);
        } catch(e){
            console.error('Error cargando evaluaciones:', e);
            const card = document.createElement('div');
            card.className = 'info-card';
            card.innerHTML = '<b>Error</b> cargando evaluaciones: ' + e.message;
            grid.appendChild(card);
        }
    }

    function renderGrid(items){
        grid.innerHTML = '';
        items.forEach((item, idx) => {
            const card = document.createElement('div');
            card.className = 'unit-card';

            // header
            const header = document.createElement('div');
            header.className = 'unit-header';
            const icon = document.createElement('div');
            icon.className = 'unit-icon';
            icon.textContent = unitIcon(item.unit);
            const info = document.createElement('div');
            info.className = 'unit-info';
            const h3 = document.createElement('h3');
            h3.textContent = item.title;
            const p = document.createElement('p');
            p.className = 'unit-description';
            p.textContent = item.description || '';
            info.appendChild(h3); info.appendChild(p);
            header.appendChild(icon); header.appendChild(info);

            // Si es Docente, habilitar edición inline del título/descripción y persistir localmente
            const canEdit = Boolean(document.getElementById('btn-add-eval'));
            if (canEdit) {
                h3.style.cursor = 'text'; p.style.cursor = 'text';
                h3.addEventListener('click', ()=> startEvalInlineEdit(item.key, h3, p));
                p.addEventListener('click', ()=> startEvalInlineEdit(item.key, h3, p));
            }

            // progress
            const progressWrap = document.createElement('div');
            progressWrap.className = 'unit-progress';
            const bar = document.createElement('div'); bar.className = 'progress-bar';
            const fill = document.createElement('div'); fill.className = 'progress-fill';
            const pct = stateToProgress(item.state);
            fill.style.width = pct + '%';
            bar.appendChild(fill);
            const text = document.createElement('span'); text.className = 'progress-text';
            text.textContent = item.state && item.state.attempt_used ? `Puntaje: ${item.state.score}/100` : 'No iniciado';
            progressWrap.appendChild(bar); progressWrap.appendChild(text);

            // actions (like exercise list)
            const list = document.createElement('div');
            list.className = 'unit-exercises';
            const h4 = document.createElement('h4'); h4.textContent = 'Evaluación disponible:';
            const exList = document.createElement('div'); exList.className = 'exercise-list';
            const link = document.createElement('a'); link.href = '#'; link.className = 'exercise-item';
            link.addEventListener('click', (e) => { e.preventDefault(); openAttempt(item.key); });
            const num = document.createElement('span'); num.className = 'exercise-number'; num.textContent = (idx+1)+'';
            const title = document.createElement('span'); title.className = 'exercise-title'; title.textContent = 'Evaluación de la unidad';
            const status = document.createElement('span'); status.className = 'exercise-status'; status.textContent = item.state && item.state.attempt_used ? '🔓' : '🔒';
            link.appendChild(num); link.appendChild(title); link.appendChild(status);
            exList.appendChild(link);
            list.appendChild(h4); list.appendChild(exList);

            card.appendChild(header);
            card.appendChild(progressWrap);
            card.appendChild(list);
            grid.appendChild(card);
        });
    }

    // Editor inline para evaluaciones: guarda en localStorage bajo la clave 'hd_evaluations_local'
    function startEvalInlineEdit(key, titleEl, descEl){
        // prevenir editores múltiples
        if (titleEl._editing) return; titleEl._editing = true;
        const originalTitle = titleEl.textContent.trim();
        const originalDesc = descEl.textContent.trim();

        const titleInput = document.createElement('input'); titleInput.type = 'text'; titleInput.value = originalTitle; titleInput.style.width = '100%';
        const descTextarea = document.createElement('textarea'); descTextarea.value = originalDesc; descTextarea.style.width = '100%'; descTextarea.style.minHeight='80px';

        const container = titleEl.parentNode; // unit-info
        titleEl.style.display = 'none'; descEl.style.display = 'none';
        const editor = document.createElement('div'); editor.className = 'unit-editor'; editor.style.marginTop='8px';
        editor.appendChild(titleInput); editor.appendChild(descTextarea);
        const actions = document.createElement('div'); actions.style.display='flex'; actions.style.justifyContent='flex-end'; actions.style.gap='8px';
        const btnCancel = document.createElement('button'); btnCancel.className='btn'; btnCancel.textContent='Cancelar';
        const btnSave = document.createElement('button'); btnSave.className='btn btn-primary'; btnSave.textContent='Guardar';
        actions.appendChild(btnCancel); actions.appendChild(btnSave); editor.appendChild(actions);
        container.appendChild(editor);

        btnCancel.addEventListener('click', ()=>{ editor.remove(); titleEl.style.display=''; descEl.style.display=''; titleEl._editing=false; });
        btnSave.addEventListener('click', ()=>{
            const newTitle = titleInput.value.trim(); const newDesc = descTextarea.value.trim();
            if (!newTitle) { alert('El título no puede quedar vacío'); return; }
            // UI optimista
            titleEl.textContent = newTitle; descEl.textContent = newDesc;
            // guardar localmente
            try {
                const localKey = 'hd_evaluations_local';
                const store = JSON.parse(localStorage.getItem(localKey) || '{}');
                store[key] = { title: newTitle, description: newDesc };
                localStorage.setItem(localKey, JSON.stringify(store));
                // mostrar notificación
                if (window.manageJS && typeof window.manageJS.showNotification === 'function') window.manageJS.showNotification('Evaluación guardada localmente', 'success');
                else alert('Evaluación guardada localmente');
            } catch(e){ console.error('Error guardando localmente', e); alert('No se pudo guardar localmente: '+e.message); }
            editor.remove(); titleEl.style.display=''; descEl.style.display=''; titleEl._editing=false;
        });
    }

    async function openAttempt(key){
        currentKey = key;
        try {
            const evalApiUrl2 = (window.APP_ROUTES && window.APP_ROUTES.action_evaluation_api) ? window.APP_ROUTES.action_evaluation_api : 'index.php?action=evaluation_api';
            const res = await fetch(evalApiUrl2 + '&evaluation=' + encodeURIComponent(key));
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Error');
            currentDef = data.evaluation;
            currentState = data.state || { attempt_used: 0, score: 0 };
            updateHeaderForAttempt(`${currentDef.title} (${currentDef.unit})`);
            evalDesc.textContent = currentDef.description || '';
            evalInstr.textContent = currentDef.instructions || '';
            evalExample.textContent = currentDef.example || '';
            evalCode.value = '';
            updateLineNumbers();
            updateStateUI();
            clearResults();
            showAttempt();
            enableInlineEditForAttempt();
        } catch(e){
            console.error('Error abriendo evaluación:', e);
            alert('No se pudo abrir la evaluación: ' + e.message);
        }
    }

    function updateStateUI(){
        const used = currentState && currentState.attempt_used === 1;
        evalCode.disabled = used;
        btnSubmit.disabled = used;
        btnRetry.disabled = !used;
    }

    function updateHeaderForList(){
        document.getElementById('eval-list-title').style.display = 'inline';
        document.getElementById('eval-back-header').style.display = 'none';
        document.getElementById('eval-title-header').style.display = 'none';
    }

    function updateHeaderForAttempt(title){
        document.getElementById('eval-list-title').style.display = 'none';
        document.getElementById('eval-back-header').style.display = 'inline';
        document.getElementById('eval-title-header').style.display = 'inline';
        document.getElementById('eval-title-header').textContent = title;
    }

    // Editor: números de línea, scroll, teclas
    function updateLineNumbers(){
        const lines = evalCode.value.split('\n').length;
        let txt = '';
        for (let i=1;i<=lines;i++) txt += i+'\n';
        lineNumbers.textContent = txt || '1';
    }
    function syncLineNumbers(){ if (!lineNumbers || !evalCode) return; lineNumbers.scrollTop = evalCode.scrollTop; }
    function handleKeyDown(e){
        if (e.key === 'Tab') {
            e.preventDefault();
            const start = evalCode.selectionStart;
            const end = evalCode.selectionEnd;
            const val = evalCode.value;
            evalCode.value = val.substring(0,start) + '    ' + val.substring(end);
            evalCode.selectionStart = evalCode.selectionEnd = start + 4;
            updateLineNumbers();
        }
    }

    function clearResults(){
        resultsContent.innerHTML = '<p class="no-results">Escribe tu código y presiona "Enviar" para ver el puntaje y criterios.</p>';
    }
    function showScore(score, max, details){
        const pct = Math.round((score/Math.max(1,max))*100);
        let html = '<div class="result-success"><h5>🎉 Resultado</h5>';
        html += `<p><b>Puntaje:</b> ${score}/${max} (${pct}%)</p></div>`;
        html += '<div class="expected-solution"><h5>Rúbrica</h5><ul class="feedback-list">';
        (details||[]).forEach(d => {
            const cls = d.correct ? 'success' : '';
            html += `<li class="${cls}">${escapeHtml(d.label || '')}: ${d.correct ? '✅' : '❌'} (${d.points_awarded}/${d.points_total} pts) ${!d.correct && d.feedback ? '• '+escapeHtml(d.feedback) : ''}</li>`;
        });
        html += '</ul></div>';
        resultsContent.innerHTML = html;
    }

    async function submitAttempt(){
        if (submitting) return; submitting = true;
        btnSubmit.disabled = true;
        const code = evalCode.value.trim();
        if (!code){ alert('Escribe tu código antes de enviar.'); submitting=false; updateStateUI(); return; }
        try {
            const evalApiUrl3 = (window.APP_ROUTES && window.APP_ROUTES.action_evaluation_api) ? window.APP_ROUTES.action_evaluation_api : 'index.php?action=evaluation_api';
            const res = await fetch(evalApiUrl3, {
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'submit', evaluation: currentKey, code, csrf_token: csrf })
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Error');
            currentState = { attempt_used: 1, score: data.score };
            updateStateUI();
            showScore(data.score, data.max, data.details);
        } catch(e){
            console.error('Error enviando evaluación:', e);
            alert(e.message || 'No se pudo enviar la evaluación');
        } finally {
            submitting = false;
            updateStateUI();
        }
    }

    async function resetAttempt(){
        try {
            const evalApiUrl4 = (window.APP_ROUTES && window.APP_ROUTES.action_evaluation_api) ? window.APP_ROUTES.action_evaluation_api : 'index.php?action=evaluation_api';
            const res = await fetch(evalApiUrl4, {
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reset', evaluation: currentKey, csrf_token: csrf })
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Error');
            // Reabrir intento limpio
            openAttempt(currentKey);
        } catch(e){
            console.error('Error reiniciando evaluación:', e);
            alert(e.message || 'No se pudo reiniciar');
        }
    }

    // Eventos
    document.addEventListener('DOMContentLoaded', loadList);
    document.addEventListener('DOMContentLoaded', updateHeaderForList);
    document.addEventListener('DOMContentLoaded', setupCustomScrollbars);
    // Conectar botones de Docente con fallback elegante si manage.js no está cargado
    document.addEventListener('DOMContentLoaded', ()=>{
        const be = document.getElementById('btn-add-eval');
        if (be) {
            be.addEventListener('click', ()=>{
                if (window.openEvaluationEditor) return window.openEvaluationEditor('create');
                alert('La funcionalidad de edición no está disponible. Recarga la página o contacta al administrador.');
            });
        }
        // Cuando las evaluaciones cambian, recargar lista
        document.addEventListener('evaluations:changed', loadList);
        document.addEventListener('evaluations:changed', updateHeaderForList);
    });
    if (evalBack) evalBack.addEventListener('click', (e)=>{ e.preventDefault(); showList(); loadList(); });
    const evalBackHeader = document.getElementById('eval-back-header');
    if (evalBackHeader) evalBackHeader.addEventListener('click', (e)=>{ e.preventDefault(); showList(); loadList(); });
    if (evalCode) evalCode.addEventListener('input', updateLineNumbers);
    if (evalCode) evalCode.addEventListener('scroll', (e) => {
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
    if (evalCode) evalCode.addEventListener('keydown', handleKeyDown);
    if (btnClear) btnClear.addEventListener('click', ()=>{ evalCode.value=''; updateLineNumbers(); clearResults(); });
    if (btnSubmit) btnSubmit.addEventListener('click', submitAttempt);
    if (btnRetry) btnRetry.addEventListener('click', resetAttempt);

    // Edición inline para el intento
    function enableInlineEditForAttempt(){
        const rol = document.body.getAttribute('data-rol') || 'Usuario';
        if (!['Docente', 'Administrador'].includes(rol)) return;
        document.querySelectorAll('.edit-btn').forEach(btn => btn.style.display = 'inline-block');
        document.getElementById('edit-instructions')?.addEventListener('click', e => startInlineEdit('eval-instructions', 'instructions'));
        document.getElementById('edit-example')?.addEventListener('click', e => startInlineEdit('eval-example', 'example'));
    }

    async function startInlineEdit(id, field) {
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
            if (field === 'tips') return; // local only
            try {
                const res = await fetch((window.APP_ROUTES && window.APP_ROUTES.action_evaluation_api) ? window.APP_ROUTES.action_evaluation_api : 'index.php?action=evaluation_api', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({
                        action: 'update', evaluation: currentKey, fields: { [field]: newValue }, csrf_token: csrf
                    })
                });
                const data = await res.json();
                if (data.success) {
                    if (window.manageJS && typeof window.manageJS.showNotification === 'function') window.manageJS.showNotification('Evaluación actualizada', 'success');
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

    function escapeHtml(t){ const d=document.createElement('div'); d.textContent=t; return d.innerHTML; }

    // ============================================
    // FUNCIONALIDAD EMOTES SVC PARA EVALUACIONES
    // ============================================

    // Inicializar Emotes SVC para evaluaciones
    function initializeEmotesSVC() {
        // Verificar si hay emotes en la página
        const emoteContainers = document.querySelectorAll('.emotes-container');
        if (emoteContainers.length === 0) return;

        // Configuración de emotes
        const emoteConfig = {
            like: {
                name: 'Me gusta',
                icon: '👍',
                color: '#10b981'
            },
            happy: {
                name: 'Feliz',
                icon: '😊',
                color: '#f59e0b'
            },
            wow: {
                name: 'Wow',
                icon: '😲',
                color: '#8b5cf6'
            },
            think: {
                name: 'Pensar',
                icon: '🤔',
                color: '#3b82f6'
            },
            shock: {
                name: 'Sorprendido',
                icon: '😳',
                color: '#ef4444'
            }
        };

        // Estado de emotes (simulado - en producción se guardaría en base de datos)
        let emoteState = {};

        // Inicializar estado de emotes
        emoteContainers.forEach(container => {
            const containerId = container.id || 'emotes-' + Math.random().toString(36).substr(2, 9);
            container.setAttribute('data-emote-id', containerId);
            emoteState[containerId] = {
                like: 0,
                happy: 0,
                wow: 0,
                think: 0,
                shock: 0,
                userReaction: null
            };
        });

        // Manejar clics en emotes
        document.addEventListener('click', function(e) {
            const emoteButton = e.target.closest('.emote-button');
            if (emoteButton) {
                const emoteType = emoteButton.getAttribute('data-emote');
                const container = emoteButton.closest('.emotes-container');
                const containerId = container.getAttribute('data-emote-id');

                handleEmoteClick(emoteType, containerId, emoteButton);
            }
        });

        // Función para manejar clics en emotes
        function handleEmoteClick(emoteType, containerId, button) {
            const state = emoteState[containerId];

            // Si el usuario ya reaccionó con este emote, quitar la reacción
            if (state.userReaction === emoteType) {
                state[emoteType] = Math.max(0, state[emoteType] - 1);
                state.userReaction = null;
                button.classList.remove('active');
                showEmoteFeedback(`Reacción "${emoteConfig[emoteType].name}" removida`, emoteConfig[emoteType].color);
            } else {
                // Si el usuario ya reaccionó con otro emote, quitar esa reacción primero
                if (state.userReaction) {
                    const prevEmote = state.userReaction;
                    state[prevEmote] = Math.max(0, state[prevEmote] - 1);
                    const prevButton = container.querySelector(`.emote-button[data-emote="${prevEmote}"]`);
                    if (prevButton) prevButton.classList.remove('active');
                }

                // Añadir nueva reacción
                state[emoteType] = state[emoteType] + 1;
                state.userReaction = emoteType;
                button.classList.add('active');
                showEmoteFeedback(`¡Reacción "${emoteConfig[emoteType].name}" añadida!`, emoteConfig[emoteType].color);
            }

            // Actualizar resumen de emotes
            updateEmoteSummary(containerId);

            // Animación de emote
            button.classList.add('emote-animation');
            setTimeout(() => button.classList.remove('emote-animation'), 300);
        }

        // Función para mostrar feedback de emote
        function showEmoteFeedback(message, color) {
            let feedbackElement = document.getElementById('emote-feedback');
            if (!feedbackElement) {
                feedbackElement = document.createElement('div');
                feedbackElement.id = 'emote-feedback';
                feedbackElement.className = 'emote-feedback';
                document.body.appendChild(feedbackElement);
            }

            feedbackElement.textContent = message;
            feedbackElement.style.background = color;
            feedbackElement.classList.add('show');

            // Ocultar después de 3 segundos
            setTimeout(() => {
                feedbackElement.classList.remove('show');
            }, 3000);
        }

        // Función para actualizar resumen de emotes
        function updateEmoteSummary(containerId) {
            const state = emoteState[containerId];
            const summaryContainer = document.getElementById(containerId + '-summary') ||
                                   document.querySelector(`[data-emote-summary="${containerId}"]`);

            if (summaryContainer) {
                let summaryHtml = '';
                let hasReactions = false;

                // Contar reacciones totales
                const totalReactions = Object.keys(emoteConfig).reduce((sum, emote) => {
                    return sum + state[emote];
                }, 0);

                if (totalReactions > 0) {
                    hasReactions = true;

                    // Mostrar emotes con reacciones
                    Object.keys(emoteConfig).forEach(emote => {
                        if (state[emote] > 0) {
                            const emoteData = emoteConfig[emote];
                            summaryHtml += `
                                <div class="emote-summary-item">
                                    <img src="app/view/img/SVC Emotes ${emoteData.name.split(' ')[0]}@112.png" alt="${emoteData.name}">
                                    <span>${emoteData.icon} ${state[emote]}</span>
                                </div>
                            `;
                        }
                    });

                    // Mostrar reacción del usuario si existe
                    if (state.userReaction) {
                        const userEmote = emoteConfig[state.userReaction];
                        summaryHtml += `
                            <div class="emote-summary-item" style="margin-left: auto; font-weight: 600; color: ${userEmote.color};">
                                <span>Tu reacción: ${userEmote.icon}</span>
                            </div>
                        `;
                    }
                }

                summaryContainer.innerHTML = summaryHtml;
                summaryContainer.style.display = hasReactions ? 'flex' : 'none';
            }
        }

        // Inicializar resúmenes de emotes
        emoteContainers.forEach(container => {
            const containerId = container.getAttribute('data-emote-id');
            updateEmoteSummary(containerId);
        });

        // Añadir tooltips a emotes
        addEmoteTooltips();
    }

    // Función para añadir tooltips a emotes
    function addEmoteTooltips() {
        const emoteButtons = document.querySelectorAll('.emote-button');
        emoteButtons.forEach(button => {
            const tooltip = button.querySelector('.emote-tooltip');
            if (tooltip) {
                // Posicionar tooltip correctamente
                const rect = button.getBoundingClientRect();
                tooltip.style.left = '50%';
                tooltip.style.bottom = '100%';
                tooltip.style.transform = 'translateX(-50%)';
            }
        });
    }

    // Inicializar emotes cuando el contenido esté listo
    document.addEventListener('DOMContentLoaded', initializeEmotesSVC);
})();
