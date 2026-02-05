// app/view/js/unified-content.js - JavaScript consolidado y mejorado para la página de contenido
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ unified-content.js cargado correctamente');

    // --- 1. Elementos del DOM ---
    const trimestreActual = document.getElementById('trimestre-selector').value;
    const unitSelectionArea = document.getElementById('unit-selection-area');
    const unitLabel = document.querySelector('label[for="unit-dropdown"]');
    const toggleVisibilidad = document.getElementById('toggle-trimestre-visibility');
    const trimestreSelector = document.getElementById('trimestre-selector');
    const unitDropdown = document.getElementById('unit-dropdown');
    const unitContent = document.getElementById('unit-content');
    const noUnitSelected = document.getElementById('no-unit-selected');
    const unitTitle = document.getElementById('unit-title');
    const unitDescription = document.getElementById('unit-description');
    const generalTheoryMaterial = document.getElementById('general-theory-material');
    const unifiedTopicsContent = document.getElementById('unified-topics-content');
    
    // Modales
    const unitModalCreate = document.getElementById('unit-modal-create');
    const contentModal = document.getElementById('content-modal');
    const topicModal = document.getElementById('topic-modal');
    const exerciseModal = document.getElementById('exercise-modal');
    const evaluationModal = document.getElementById('evaluation-modal');
    const viewerModal = document.getElementById('content-viewer-modal');

    // --- 2. Estado Global ---
    const rol = document.body.getAttribute('data-rol') || 'Usuario';
    const isTeacher = ['Docente', 'Administrador'].includes(rol);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let currentUnitId = null;

    if (toggleVisibilidad) {
        toggleVisibilidad.addEventListener('change', async function() {
            // 1. Verificar qué trimestre tenemos seleccionado actualmente
            const trimestreActual = document.getElementById('trimestre-selector').value;
            const estaActivo = this.checked;

            console.log(`Enviando cambio: Trimestre ${trimestreActual}, Activo: ${estaActivo}`);

            try {
                const response = await fetch('index.php?action=toggle_trimestre_status', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        trimestre: trimestreActual,
                        activo: estaActivo,
                        csrf_token: csrfToken // Esta variable ya la tienes definida arriba en tu script
                    })
                });

                const data = await response.json();
                if (!data.success) {
                    alert('Error al guardar la visibilidad');
                    // Revertir el switch si falló
                    this.checked = !estaActivo;
                } else {
                    console.log('✅ Visibilidad actualizada en la DB');
                }
            } catch (error) {
                console.error('Error en la petición del toggle:', error);
            }
        });
    }

    if (trimestreSelector) {
        trimestreSelector.addEventListener('change', async function() {
            const selectedTrimestre = this.value;
            console.log('Cambiando al trimestre:', selectedTrimestre);
            
            try {
                // 1. Llamar al backend para obtener unidades y estado de visibilidad
                const response = await fetch(`index.php?action=get_units_by_trimestre&trimestre=${selectedTrimestre}`);
                const data = await response.json();
                
                if (data.success) {
                    const msgBloqueado = document.getElementById('trimestre-bloqueado-msg');
                    
                    // 2. Limpiar y repoblar el unitDropdown
                    if (unitDropdown) {
                        unitDropdown.innerHTML = '<option value="">Seleccione una unidad...</option>';
                        
                        // Solo llenamos si hay unidades (o si es profesor, para que vea la lista vacía)
                        if (data.units && data.units.length > 0) {
                            data.units.forEach(u => {
                                const option = document.createElement('option');
                                option.value = u.id;
                                option.textContent = u.titulo;
                                unitDropdown.appendChild(option);
                            });
                        }
                    }

                    // 3. Lógica del Mensaje de Bloqueo (Solo para alumnos)
                    if (msgBloqueado) {
                        // isTeacher ya está definido al inicio de tu archivo unified-content.js
                        if (!isTeacher && !data.isVisible) {
                            msgBloqueado.classList.remove('hidden');
                            if (unitDropdown) unitDropdown.classList.add('hidden'); 
                        } else {
                            msgBloqueado.classList.add('hidden');
                            if (unitDropdown) unitDropdown.classList.remove('hidden');
                        }
                    }

                    // 4. Actualizar el switch si eres docente
                    const toggleVisibilidad = document.getElementById('toggle-trimestre-visibility');
                    if (toggleVisibilidad) {
                        toggleVisibilidad.checked = data.isVisible;
                    }

                    // 5. Resetear la vista de contenido
                    if (unitContent) unitContent.classList.add('hidden');
                    if (noUnitSelected) noUnitSelected.classList.remove('hidden');
                }
            } catch (error) {
                console.error("Error al cargar unidades del trimestre:", error);
            }
        });
    }

    // --- 3. Utilidades Generales ---
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // --- 3.1. Estado de Unidad Helper ---
    function getCurrentUnitState() {
        const selectedOption = unitDropdown?.options[unitDropdown.selectedIndex];
        if (!selectedOption) {
            return null; // No unit selected
        }
        
        const isActive = selectedOption.getAttribute('data-active');
        // Handle cases where data-active might be missing or invalid
        if (isActive === null || isActive === undefined) {
            return true; // Default to active if attribute is missing
        }
        
        return parseInt(isActive) === 1;
    }

    function getCurrentUnitId() {
        const selectedOption = unitDropdown?.options[unitDropdown.selectedIndex];
        return selectedOption ? selectedOption.getAttribute('data-id') : null;
    }

    function generateSlug(text) {
        return text.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }

    function validateAndFormatUrl(url) {
        if (!url) return '';
        if (url.startsWith('http://') || url.startsWith('https://')) return url;
        return 'https://' + url;
    }

    function getCurrentUnitSlug() {
        return unitDropdown?.value || '';
    }

    // --- 4. Funciones de API y Carga ---
    
    async function checkTopicHasEvaluation(topicId) {
        try {
            const response = await fetch(`index.php?action=get_topic_exercises&topic_id=${topicId}&type=evaluacion`);
            const data = await response.json();
            return data.success && data.exercises && data.exercises.length > 0;
        } catch (err) { 
            console.error('Error checking evaluation:', err);
            return false; 
        }
    }

    async function loadContentForTopic(topicId) {
        const container = document.getElementById(`topic-content-${topicId}`);
        if (!container) return;

        try {
            const response = await fetch(`index.php?action=content_api&topic_id=${topicId}`);
            const data = await response.json();
            if (data.success && data.content) {
                container.innerHTML = data.content
                    .filter(item => item.tipo !== 'video')
                    .map(item => renderContentItem(item))
                    .join('');
            }
        } catch (error) {
            console.error(`Error loading content for topic ${topicId}:`, error);
        }
    }

    async function loadExercisesForTopic(topicId) {
        const container = document.getElementById(`exercise-links-${topicId}`);
        if (!container) return;

        try {
            const [pRes, eRes] = await Promise.all([
                fetch(`index.php?action=get_topic_exercises&topic_id=${topicId}&type=practica`),
                fetch(`index.php?action=get_topic_exercises&topic_id=${topicId}&type=evaluacion`)
            ]);

            const pData = await pRes.json();
            const eData = await eRes.json();

            let html = '';
            
            if (pData.success && pData.exercises) {
                pData.exercises.forEach(ex => {
                    const isActive = ex.activo !== undefined ? (parseInt(ex.activo) === 1) : true;
                    html += renderExerciseItem(ex, 'practica', isActive);
                });
            }

            if (eData.success && eData.exercises) {
                eData.exercises.forEach(ex => {
                    const isActive = ex.activo !== undefined ? (parseInt(ex.activo) === 1) : true;
                    html += renderExerciseItem(ex, 'evaluacion', isActive);
                });
            }

            container.innerHTML = html || '<small>Sin ejercicios ni evaluaciones.</small>';
        } catch (error) {
            container.innerHTML = '<small>Error al cargar ejercicios.</small>';
        }
    }

    async function loadVideosForTopic(topicId) {
        const container = document.getElementById(`video-links-${topicId}`);
        if (!container) return;

        try {
            const response = await fetch(`index.php?action=content_api&topic_id=${topicId}`);
            const data = await response.json();

            if (data.success && data.content) {
                const videos = data.content.filter(item => item.tipo === 'video');
                const toDisplay = isTeacher ? videos : videos.filter(v => (v.activo !== undefined ? (parseInt(v.activo) === 1) : true));

                if (toDisplay.length > 0) {
                    container.innerHTML = toDisplay.map(v => renderVideoItem(v)).join('');
                } else {
                    container.innerHTML = '<small>No hay videos disponibles.</small>';
                }
            }
        } catch (error) {
            container.innerHTML = '<small>Error al cargar videos.</small>';
        }
    }

    function populateTopicDropdown(topics) {
        const topicSelect = document.getElementById('content-topic');
        if (!topicSelect) return;

        // Guardar el valor actual por si estamos editando
        const currentValue = topicSelect.value;

        // Limpiar excepto la primera opción
        topicSelect.innerHTML = '<option value="">-- Sin tema específico (contenido general) --</option>';

        if (topics && topics.length > 0) {
            topics.forEach(topic => {
                const option = document.createElement('option');
                option.value = topic.id;
                option.textContent = topic.nombre;
                topicSelect.appendChild(option);
            });
        }

        // Restaurar valor si es posible
        if (currentValue) topicSelect.value = currentValue;
    }

    async function loadTopicsWithContent(unitId) {
        try {
            const response = await fetch(`index.php?action=topics&unit_id=${unitId}`);
            const data = await response.json();

            if (!data.success || !data.topics || data.topics.length === 0) {
                unifiedTopicsContent.innerHTML = '<p>No hay temas definidos en esta unidad.</p>';
                populateTopicDropdown([]); // Limpiar dropdown si no hay temas
                return;
            }

            // Actualizar el dropdown de temas en el modal de contenido
            populateTopicDropdown(data.topics);

            let topicsHtml = '';
            for (const topic of data.topics) {
                const hasEvaluation = await checkTopicHasEvaluation(topic.id);
                const isActive = topic.activo !== undefined ? (parseInt(topic.activo) === 1) : true;
                const topicClass = getTopicClasses(topic, hasEvaluation);
                const topicActions = getTeacherTopicActions(topic.id, isActive);

                topicsHtml += `
                    <div class="unified-topic-item ${topicClass}" data-topic-id="${topic.id}">
                        <div class="unified-topic-header">
                            <h4>${escapeHtml(topic.nombre)}</h4>
                            ${topicActions}
                        </div>
                        ${topic.descripcion ? `<p class="unified-topic-description">${escapeHtml(topic.descripcion)}</p>` : ''}
                        ${(!hasEvaluation && isTeacher) ? `
                            <div class="topic-warning">
                                <span class="warning-icon">⚠️</span>
                                <span>Este tema requiere al menos una (1) evaluación.</span>
                            </div>
                        ` : ''}
                        <div class="topic-content-area" id="topic-content-${topic.id}"></div>
                        <div class="topic-exercises-section">
                            <h5>💡 Ejercicios Prácticos</h5>
                            <div class="exercise-links-container" id="exercise-links-${topic.id}">
                                <small>Cargando ejercicios...</small>
                            </div>
                        </div>
                        <div class="topic-videos-section">
                            <h5>🎥 Videos Educativos</h5>
                            <div class="video-links-container" id="video-links-${topic.id}">
                                <small>Cargando videos...</small>
                            </div>
                        </div>
                    </div>
                `;
            }

            unifiedTopicsContent.innerHTML = topicsHtml;

            // Carga diferida de detalles
            data.topics.forEach(topic => {
                loadContentForTopic(topic.id);
                loadExercisesForTopic(topic.id);
                loadVideosForTopic(topic.id);
            });
        } catch (error) {
            console.error('Error loading topics:', error);
            unifiedTopicsContent.innerHTML = '<p>Error al cargar temas.</p>';
        }
    }

    async function loadUnitContent(unitId) {
        if (!unitId) return;
        currentUnitId = unitId;
        
        try {
            const response = await fetch(`index.php?action=content_api&unit_id=${unitId}`);
            const data = await response.json();

            if (data.success) {
                renderGeneralContent(data.content);
                await loadTopicsWithContent(unitId);
            } else {
                generalTheoryMaterial.innerHTML = '<p>Error al cargar el contenido.</p>';
            }
        } catch (error) {
            console.error('Error loading unit content:', error);
            generalTheoryMaterial.innerHTML = '<p>Error de conexión.</p>';
        }
    }

    // --- 5. Renderizadores de UI ---

    function getTopicClasses(topic, hasEvaluation) {
        let classes = [];
        const isActive = topic.activo !== undefined ? (parseInt(topic.activo) === 1) : true;
        if (!isActive) classes.push('disabled-item');
        if (!hasEvaluation) classes.push('topic-missing-evaluation');
        return classes.join(' ');
    }

    function getTeacherTopicActions(topicId, isActive) {
        if (!isTeacher) return '';
        return `
            <div class="unified-topic-actions">
                <button class="btn btn-small btn-edit" onclick="editTopic(${topicId})" title="Editar tema">✏️</button>
                <button class="btn btn-small btn-delete" onclick="deleteTopic(${topicId})" title="Eliminar tema">🗑️</button>
                <button class="btn btn-small btn-toggle" onclick="toggleTopicActive(${topicId}, ${isActive ? 0 : 1})" title="${isActive ? 'Inhabilitar' : 'Habilitar'}">${isActive ? '🔒' : '🔓'}</button>
                <button class="btn btn-small btn-add-exercise" onclick="showExerciseModal(${topicId})" title="Añadir ejercicio">➕ Ejercicio</button>
                <button class="btn btn-small btn-add-evaluation" onclick="showEvaluationModal(${topicId})" title="Añadir evaluación">➕ Evaluación</button>
                <button class="btn btn-small btn-add-content" onclick="showContentModalForTopic(${topicId})" title="Añadir contenido">➕ Contenido</button>
            </div>
        `;
    }

    function renderGeneralContent(contentItems) {
        if (!contentItems || contentItems.length === 0) {
            generalTheoryMaterial.innerHTML = '<p>No hay contenido general disponible.</p>';
            return;
        }

        const generalItems = contentItems.filter(item => !item.tema_id);
        generalTheoryMaterial.innerHTML = generalItems.length > 0 
            ? generalItems.map(item => renderContentItem(item)).join('')
            : '<p>No hay contenido general disponible.</p>';
    }

    function renderContentItem(item) {
        const icon = { texto: '📝', documento: '📄', video: '🎥', enlace: '🔗', imagen: '🖼️' }[item.tipo] || '📎';
        const label = { documento: 'Ver Documento', video: 'Ver Video', enlace: 'Visitar Enlace', imagen: 'Ver Imagen' }[item.tipo] || 'Abrir';
        const isActive = item.activo !== undefined ? (parseInt(item.activo) === 1) : true;
        
        let html = `<div class="content-item content-${item.tipo} ${!isActive ? 'disabled-item' : ''}" data-id="${item.id}">`;
        html += `<h4>${escapeHtml(item.titulo)} ${item.tipo !== 'texto' ? `<span class="content-type-badge-inline">${icon} ${item.tipo}</span>` : ''} ${!isActive ? '<span class="item-status-badge">[INACTIVO]</span>' : ''}</h4>`;

        if (item.tipo === 'texto') {
            html += `<div class="content-text">${item.contenido || ''}</div>`;
        } else if (['documento', 'imagen'].includes(item.tipo)) {
            if (item.contenido) html += `<div class="content-description">${escapeHtml(item.contenido)}</div>`;
            html += `<div class="content-link"><button class="content-title-button" onclick="viewContent('${item.url}', '${item.tipo}', '${item.titulo}')">${icon} ${label}</button></div>`;
        } else {
            if (item.contenido) html += `<div class="content-description">${escapeHtml(item.contenido)}</div>`;
            const url = validateAndFormatUrl(item.url);
            html += `<div class="content-link"><a href="${escapeHtml(url)}" target="_blank" class="external-link">${icon} ${label}</a></div>`;
        }

        if (isTeacher) {
            html += `
                <div class="content-actions">
                    <button class="btn btn-small btn-edit" onclick="editContent(${item.id})" aria-label="Editar contenido">✏️</button>
                    <button class="btn btn-small btn-delete" onclick="deleteContent(${item.id})" aria-label="Eliminar contenido">🗑️</button>
                    <button class="btn btn-small btn-toggle" onclick="toggleContentActive(${item.id}, ${isActive ? 0 : 1})" aria-label="${isActive ? 'Inhabilitar' : 'Habilitar'}">${isActive ? '🔒' : '🔓'}</button>
                </div>
            `;
        }
        html += '</div>';
        return html;
    }

    function renderExerciseItem(ex, type, isActive) {
        const url = (!isActive && !isTeacher) ? 'javascript:void(0)' : (type === 'practica' 
            ? `index.php?page=practices&unit=${getCurrentUnitSlug()}&exercise=${ex.slug}`
            : `index.php?page=evaluation&topic_id=${ex.tema_id}&exercise_id=${ex.id}`);
        
        const badge = type === 'practica' ? 'Práctica' : 'Evaluación';
        const icon = type === 'practica' ? '🎯' : '📝';

        return `
            <div class="exercise-item exercise-${type} ${!isActive ? 'disabled-item' : ''}">
                <a href="${url}" class="exercise-link" ${(!isActive && !isTeacher) ? 'onclick="showNotification(\'Esta evaluación aún no está disponible.\', \'warning\'); return false;"' : ''}>
                    ${icon} ${escapeHtml(ex.titulo)}
                    <span class="exercise-type-badge">${badge}</span>
                    ${!isActive ? '<span class="item-status-badge">[INACTIVO]</span>' : ''}
                </a>
                ${isTeacher ? `
                    <div class="exercise-actions">
                        <button class="btn btn-small btn-edit" onclick="${type === 'practica' ? `editExercise(${ex.id})` : `editEvaluation(${ex.id})`}" aria-label="Editar">✏️</button>
                        <button class="btn btn-small btn-delete" onclick="${type === 'practica' ? `deleteExercise(${ex.id})` : `deleteEvaluation(${ex.id})`}" aria-label="Eliminar">🗑️</button>
                        <button class="btn btn-small btn-toggle" onclick="${type === 'practica' ? `toggleExerciseActive` : `toggleEvaluationActive`}('${getCurrentUnitSlug()}', '${ex.slug}', ${isActive ? 0 : 1})" aria-label="${isActive ? 'Inhabilitar' : 'Habilitar'}">${isActive ? '🔒' : '🔓'}</button>
                    </div>
                ` : ''}
            </div>
        `;
    }

    function renderVideoItem(v) {
        const url = validateAndFormatUrl(v.url);
        const isActive = v.activo !== undefined ? (parseInt(v.activo) === 1) : true;
        return `
            <div class="exercise-item exercise-video ${!isActive ? 'disabled-item' : ''}">
                <a href="${escapeHtml(url)}" target="_blank" class="exercise-link">
                    🎥 ${escapeHtml(v.titulo)}
                    <span class="exercise-type-badge">Video</span>
                    ${!isActive ? '<span class="item-status-badge">[INACTIVO]</span>' : ''}
                </a>
                ${isTeacher ? `
                    <div class="exercise-actions">
                        <button class="btn btn-small btn-edit" onclick="editContent(${v.id})" aria-label="Editar">✏️</button>
                        <button class="btn btn-small btn-delete" onclick="deleteContent(${v.id})" aria-label="Eliminar">🗑️</button>
                        <button class="btn btn-small btn-toggle" onclick="toggleContentActive(${v.id}, ${isActive ? 0 : 1})" aria-label="${isActive ? 'Inhabilitar' : 'Habilitar'}">${isActive ? '🔒' : '🔓'}</button>
                    </div>
                ` : ''}
            </div>
        `;
    }

    // --- 6. Gestión de Entidades (Docentes) ---

    async function updateUnitField(field, value) {
        const selectedOption = unitDropdown.options[unitDropdown.selectedIndex];
        const unitId = selectedOption.getAttribute('data-id');
        if (!unitId) return;

        try {
            const response = await fetch('index.php?action=unit_api', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: unitId, fields: { [field]: value }, csrf_token: csrfToken })
            });
            const data = await response.json();
            if (data.success) {
                if (field === 'titulo') {
                    unitTitle.textContent = value;
                    selectedOption.textContent = value;
                    selectedOption.setAttribute('data-title', value);
                } else {
                    unitDescription.textContent = value;
                    selectedOption.setAttribute('data-description', value);
                }
                showNotification('Unidad actualizada');
            }
        } catch { showNotification('Error al actualizar unidad', 'error'); }
    }

    // --- 6.1. Gestión de Unidades (Ordenar, Inhabilitar) ---

    function initializeUnitEditing() {
        const btnEditTitle = document.getElementById('btn-edit-unit-title');
        const btnEditDescription = document.getElementById('btn-edit-unit-description');
        const btnChangeOrder = document.getElementById('btn-change-unit-order');
        const btnToggleActive = document.getElementById('btn-toggle-unit-active');

        if (btnEditTitle) {
            btnEditTitle.addEventListener('click', () => startEditingTitle());
        }

        if (btnEditDescription) {
            btnEditDescription.addEventListener('click', () => startEditingDescription());
        }

        // Conectar botones de orden e inhabilitar unidad
        if (btnChangeOrder) {
            btnChangeOrder.addEventListener('click', showOrderModal);
        }

        if (btnToggleActive) {
            btnToggleActive.addEventListener('click', toggleUnitActive);
        }
    }

    function startEditingTitle() {
        const titleElement = document.getElementById('unit-title');
        const currentTitle = titleElement.textContent.trim();

        // Create input field
        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentTitle;
        input.className = 'unit-edit-input';
        input.style.width = '100%';
        input.style.fontSize = '1.5em';
        input.style.fontWeight = 'bold';
        input.style.border = '2px solid #007bff';
        input.style.borderRadius = '4px';
        input.style.padding = '4px 8px';

        // Replace title with input
        titleElement.parentNode.replaceChild(input, titleElement);

        // Focus and select all text
        input.focus();
        input.select();

        // Handle save/cancel
        const saveEdit = async () => {
            const newTitle = input.value.trim();
            if (newTitle && newTitle !== currentTitle) {
                await updateUnitField('titulo', newTitle);
            }
            // Replace input back with title element
            input.parentNode.replaceChild(titleElement, input);
            titleElement.textContent = newTitle || currentTitle;
        };

        const cancelEdit = () => {
            input.parentNode.replaceChild(titleElement, input);
        };

        input.addEventListener('blur', saveEdit);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                saveEdit();
            } else if (e.key === 'Escape') {
                cancelEdit();
            }
        });
    }

    function startEditingDescription() {
        const descriptionElement = document.getElementById('unit-description');
        const currentDescription = descriptionElement.textContent.trim();

        // Create textarea
        const textarea = document.createElement('textarea');
        textarea.value = currentDescription;
        textarea.className = 'unit-edit-input';
        textarea.style.width = '100%';
        textarea.style.minHeight = '60px';
        textarea.style.border = '2px solid #007bff';
        textarea.style.borderRadius = '4px';
        textarea.style.padding = '4px 8px';
        textarea.style.resize = 'vertical';

        // Replace description with textarea
        descriptionElement.parentNode.replaceChild(textarea, descriptionElement);

        // Focus and select all text
        textarea.focus();
        textarea.select();

        // Handle save/cancel
        const saveEdit = async () => {
            const newDescription = textarea.value.trim();
            if (newDescription !== currentDescription) {
                await updateUnitField('descripcion', newDescription);
            }
            // Replace textarea back with description element
            textarea.parentNode.replaceChild(descriptionElement, textarea);
            descriptionElement.textContent = newDescription;
        };

        const cancelEdit = () => {
            textarea.parentNode.replaceChild(descriptionElement, textarea);
        };

        textarea.addEventListener('blur', saveEdit);
        textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && e.ctrlKey) {
                saveEdit();
            } else if (e.key === 'Escape') {
                cancelEdit();
            }
        });
    }

    async function showOrderModal(event) {
        // 1. Detener que el clic "suba" a otros elementos y dispare la función de nuevo
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        // 2. LIMPIEZA TOTAL: Borrar CUALQUIER modal de orden que exista antes de crear la nueva
        document.querySelectorAll('#order-modal-container').forEach(el => el.remove());

        const currentUnitId = getCurrentUnitId();
        if (!currentUnitId) return;

        try {
            const response = await fetch('index.php?action=get_all_units');
            const data = await response.json();
            if (!data.success) throw new Error(data.message);

            const units = data.units;
            const currentUnit = units.find(u => u.id == currentUnitId);

            const modalHtml = `
                <div id="order-modal-container" class="mg-modal-overlay">
                    <div class="mg-modal" style="z-index: 100000;">
                        <div class="mg-modal-title">Reordenar Unidades</div>
                        <div class="mg-modal-body">
                            <p>Moviendo: <strong>${currentUnit.titulo}</strong></p>
                            <div class="order-list-container">
                                ${units.map((u, index) => `
                                    <div class="order-item ${u.id == currentUnitId ? 'current' : ''}">
                                        <span class="order-number">${index + 1}.</span>
                                        <span class="order-name">${u.titulo}</span>
                                        ${u.id != currentUnitId ? 
                                            `<button type="button" class="btn btn-small btn-move-here" data-pos="${index + 1}">Mover aquí</button>` 
                                            : '<span>📍 Actual</span>'}
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        <div class="mg-modal-actions">
                            <button type="button" id="btn-close-order-force" class="btn btn-secondary">Cerrar</button>
                        </div>
                    </div>
                </div>`;

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // 3. ASIGNACIÓN DIRECTA DE EVENTOS (Sin delegación para probar)
            const container = document.getElementById('order-modal-container');
            
            // Botón cerrar
            document.getElementById('btn-close-order-force').onclick = () => container.remove();

            // Botones de mover
            container.querySelectorAll('.btn-move-here').forEach(btn => {
                btn.onclick = async function() {
                    const newPos = this.getAttribute('data-pos');
                    this.innerText = "Cambiando...";
                    this.disabled = true;
                    await changeUnitOrderToPosition(currentUnitId, newPos);
                    container.remove();
                    location.reload();
                };
            });

        } catch (error) {
            console.error("Error:", error);
        }
    }

    // Función para generar botones de posición
    function generatePositionButtons(currentOrder, totalUnits) {
        let buttons = '';
        for (let i = 1; i <= totalUnits; i++) {
            if (i === currentOrder) {
                buttons += `<button class="btn btn-secondary btn-disabled" disabled>📍 Posición ${i} (actual)</button>`;
            } else {
                buttons += `<button class="btn btn-secondary btn-position" data-position="${i}">🔢 Posición ${i}</button>`;
            }
        }
        return buttons;
    }

    // Función para cambiar el orden de la unidad a una posición específica
    /**
     * Envía la petición al servidor para cambiar el orden
     */
    async function changeUnitOrderToPosition(unitId, newOrder) {
        console.log(`Enviando petición: Unidad ${unitId} a posición ${newOrder}`);
        
        const formData = new FormData();
        formData.append('unit_id', unitId);
        formData.append('new_order', newOrder);
        // Si usas CSRF, asegúrate de que esté disponible
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrf) formData.append('csrf_token', csrf);

        try {
            const response = await fetch('index.php?action=change_unit_order', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || "Error desconocido en el servidor");
            }
            return true;
        } catch (error) {
            console.error("Error en la petición fetch:", error);
            alert("No se pudo cambiar el orden: " + error.message);
            throw error;
        }
    }

    // Función para habilitar/deshabilitar unidad
    async function toggleUnitActive() {
        // Obtener la opción seleccionada y verificar su estado al principio de la función
        const selectedOption = unitDropdown.options[unitDropdown.selectedIndex];
        const unitId = selectedOption.getAttribute('data-id'); // Obtener el ID de la unidad

        // Validación: detener ejecución si no hay unidad seleccionada
        if (!unitId) {
            showNotification('Selecciona una unidad para cambiar su estado.', 'error');
            return;
        }

        // Obtener el estado actual de la unidad usando el helper
        const isActive = getCurrentUnitState();
        const newStatus = isActive ? 0 : 1;
        const actionText = isActive ? 'inhabilitar' : 'habilitar';

        // Mensaje de confirmación
        const confirmMessage = isActive 
            ? '¿Está seguro de que desea inhabilitar esta unidad? Los estudiantes no podrán acceder al contenido.'
            : '¿Está seguro de que desea habilitar esta unidad? Los estudiantes podrán acceder al contenido.';

        // Mostrar modal de confirmación personalizado
        showConfirmationModal(confirmMessage, async () => {
            try {
                // Mostrar estado de carga
                const btnToggle = document.getElementById('btn-toggle-unit-active');
                if (btnToggle) {
                    btnToggle.disabled = true;
                    btnToggle.innerHTML = '<span class="loading-spinner"></span> Procesando...';
                }

                const response = await fetch('index.php?action=unit_api', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: unitId,
                        fields: { activo: newStatus },
                        csrf_token: csrfToken
                    })
                });

                const data = await response.json();

                if (data.success) {
                    const message = newStatus === 1 
                        ? 'Unidad habilitada exitosamente'
                        : 'Unidad inhabilitada exitosamente';
                    
                    showNotification(message, 'success');
                    
                    // Actualizar atributos de la opción seleccionada
                    selectedOption.setAttribute('data-active', newStatus);
                    
                    // Actualizar visualmente el botón
                    updateToggleButtonVisual(newStatus);
                    
                    // Si se inhabilita la unidad actual, limpiar el contenido
                    if (newStatus === 0) {
                        generalTheoryMaterial.innerHTML = '<p>Esta unidad está actualmente inhabilitada. No se puede acceder al contenido.</p>';
                        unifiedTopicsContent.innerHTML = '';
                        hideEmotesContainer();
                    } else {
                        // Recargar contenido si se habilita
                        loadUnitContent(unitId);
                    }
                } else {
                    showNotification(data.error || 'Error al cambiar el estado de la unidad', 'error');
                }
            } catch (error) {
                console.error('Error toggling unit active:', error);
                showNotification('Error de conexión', 'error');
            } finally {
                // Restaurar botón
                const btnToggle = document.getElementById('btn-toggle-unit-active');
                if (btnToggle) {
                    btnToggle.disabled = false;
                    btnToggle.innerHTML = '🔓';
                }
            }
        });
    }

    // Función para actualizar visualmente el botón de habilitar/deshabilitar
    function updateToggleButtonVisual(isActive) {
        const btnToggle = document.getElementById('btn-toggle-unit-active');
        if (!btnToggle) return;

        // Asegurar que el botón sea visible cuando hay unidad seleccionada
        btnToggle.style.display = 'inline-block';

        if (isActive) {
            btnToggle.innerHTML = '🔓';
            btnToggle.title = 'Inhabilitar unidad';
            btnToggle.style.background = '#10b981';
            btnToggle.style.color = 'white';
        } else {
            btnToggle.innerHTML = '🔒';
            btnToggle.title = 'Habilitar unidad';
            btnToggle.style.background = '#ef4444';
            btnToggle.style.color = 'white';
        }
    }

    // Función para recargar el dropdown de unidades (para reflejar cambios de orden)
    async function loadUnitsDropdown() {
        try {
            const response = await fetch('index.php?action=units_list');
            
            // Verificar si la respuesta es exitosa
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Verificar el tipo de contenido
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error(`Invalid content type: ${contentType}`);
            }

            // Intentar parsear el JSON
            let data;
            try {
                data = await response.json();
            } catch (jsonError) {
                throw new Error(`JSON parse error: ${jsonError.message}`);
            }

            if (data.success && data.units && Array.isArray(data.units)) {
                const currentUnitSlug = unitDropdown.value;
                
                // Limpiar dropdown
                unitDropdown.innerHTML = '<option value="">-- Elige una unidad --</option>';
                
                // Añadir unidades ordenadas
                data.units.forEach(unit => {
                    // Validar que cada unidad tenga los campos necesarios
                    if (unit && typeof unit === 'object' && unit.slug && unit.titulo) {
                        const option = document.createElement('option');
                        option.value = unit.slug;
                        option.textContent = unit.titulo;
                        option.setAttribute('data-id', unit.id || '');
                        option.setAttribute('data-title', unit.titulo);
                        option.setAttribute('data-description', unit.descripcion || '');
                        option.setAttribute('data-order', unit.orden || '0');
                        option.setAttribute('data-active', unit.activo || '1');
                        unitDropdown.appendChild(option);
                    }
                });

                // Restaurar unidad seleccionada
                if (currentUnitSlug) {
                    unitDropdown.value = currentUnitSlug;
                }
                
                // Mostrar notificación de éxito
                showNotification('Dropdown de unidades actualizado', 'success');
            } else {
                throw new Error('Respuesta inválida del servidor: datos faltantes o mal formados');
            }
        } catch (error) {
            console.error('Error reloading units dropdown:', error);
            
            // Mostrar notificación de error al usuario
            showNotification(`Error al cargar unidades: ${error.message}`, 'error');
            
            // Intentar recargar la página para recuperar el estado
            if (error.message.includes('JSON') || error.message.includes('Invalid content type')) {
                showNotification('Se detectó un problema con el formato de datos. Recargando...', 'warning');
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        }
    }

    function editTopic(topicId) {
        const topicItem = document.querySelector(`.unified-topic-item[data-topic-id="${topicId}"]`);
        if (!topicItem) return;

        const name = topicItem.querySelector('h4').textContent;
        const desc = topicItem.querySelector('.unified-topic-description')?.textContent || '';

        document.getElementById('topic-id').value = topicId;
        document.getElementById('topic-name').value = name;
        document.getElementById('topic-description').value = desc;
        document.getElementById('topic-modal-title').textContent = 'Editar Tema';
        topicModal.classList.remove('hidden');
    }

    async function deleteTopic(topicId) {
        if (!confirm('¿Eliminar este tema y todo su contenido asociado?')) return;
        try {
            const response = await fetch('index.php?action=manage', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_topic', id: topicId, csrf_token: csrfToken })
            });
            const data = await response.json();
            if (data.success) {
                showNotification('Tema eliminado');
                loadUnitContent(currentUnitId);
            }
        } catch { showNotification('Error de conexión', 'error'); }
    }

    async function toggleTopicActive(id, active) {
        try {
            const response = await fetch('index.php?action=manage', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'set_topic_active', id, active: active ? 1 : 0, csrf_token: csrfToken })
            });
            const data = await response.json();
            if (data.success) {
                showNotification(`Tema ${active ? 'habilitado' : 'inhabilitado'}`);
                loadUnitContent(currentUnitId);
            }
        } catch { showNotification('Error de conexión', 'error'); }
    }

    function showContentModalForTopic(topicId) {
        resetContentModal();
        document.getElementById('content-topic').value = topicId;
        contentModal.classList.remove('hidden');
    }

    async function editContent(id) {
        try {
            // Asegurarnos de que el modal esté limpio antes de cargar
            resetContentModal();
            
            const modalTitle = contentModal.querySelector('.mg-modal-title');
            if (modalTitle) modalTitle.textContent = 'Editar Contenido Didáctico ✏️';

            const response = await fetch(`index.php?action=content_api&content_id=${id}`);
            const data = await response.json();
            
            if (data.success && data.content) {
                const c = data.content;
                contentModal.dataset.editingId = id;
                
                // Asignar valores a los campos
                const typeEl = document.getElementById('content-type');
                const titleEl = document.getElementById('content-title');
                const textEl = document.getElementById('content-text');
                const urlEl = document.getElementById('content-url');
                const topicEl = document.getElementById('content-topic');

                if (typeEl) typeEl.value = c.tipo || '';
                if (titleEl) titleEl.value = c.titulo || '';
                if (textEl) textEl.value = c.contenido || '';
                if (urlEl) urlEl.value = c.url || '';
                if (topicEl) topicEl.value = c.tema_id || '';
                
                // Mostrar/ocultar secciones según el tipo
                const textSection = document.getElementById('content-text-section');
                const urlSection = document.getElementById('content-url-section');
                
                if (textSection) textSection.classList.toggle('hidden', c.tipo !== 'texto');
                if (urlSection) urlSection.classList.toggle('hidden', !['documento', 'video', 'enlace', 'imagen'].includes(c.tipo));
                
                contentModal.classList.remove('hidden');
            } else {
                showNotification(data.error || 'Error al cargar contenido', 'error');
            }
        } catch (error) { 
            console.error('Error in editContent:', error);
            showNotification('Error al cargar contenido', 'error'); 
        }
    }

    async function deleteContent(id) {
        if (!confirm('¿Eliminar este contenido?')) return;
        try {
            const response = await fetch('index.php?action=content_api', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, csrf_token: csrfToken })
            });
            const data = await response.json();
            if (data.success) {
                showNotification('Contenido eliminado');
                loadUnitContent(currentUnitId);
            }
        } catch { showNotification('Error de conexión', 'error'); }
    }

    function showExerciseModal(topicId) {
        resetExerciseModal();
        document.getElementById('exercise-topic-id').value = topicId;
        document.getElementById('exercise-unit-slug').value = getCurrentUnitSlug();
        exerciseModal.classList.remove('hidden');
    }

    function showEvaluationModal(topicId) {
        resetEvaluationModal();
        document.getElementById('evaluation-topic-id').value = topicId;
        document.getElementById('evaluation-unit-slug').value = getCurrentUnitSlug();
        evaluationModal.classList.remove('hidden');
    }

    async function editExercise(id) {
        try {
            const response = await fetch('index.php?action=manage', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_exercise', id, csrf_token: csrfToken })
            });
            const data = await response.json();
            if (data.success && data.exercise) {
                const ex = data.exercise;
                resetExerciseModal();
                exerciseModal.dataset.editingId = id;
                document.getElementById('exercise-title').value = ex.titulo || '';
                document.getElementById('exercise-slug').value = ex.slug || '';
                document.getElementById('exercise-slug-display').textContent = ex.slug || '';
                document.getElementById('exercise-instructions').value = ex.instrucciones || '';
                document.getElementById('exercise-example').value = ex.ejemplo || '';
                document.getElementById('exercise-expected-output').value = ex.salida_esperada || '';
                document.getElementById('exercise-solution').value = ex.solucion || '';
                exerciseModal.classList.remove('hidden');
            }
        } catch { showNotification('Error al cargar ejercicio', 'error'); }
    }

    async function editEvaluation(id) {
        try {
            const response = await fetch('index.php?action=manage', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_exercise', id, csrf_token: csrfToken })
            });
            const data = await response.json();
            if (data.success && data.exercise) {
                const ev = data.exercise;
                resetEvaluationModal();
                evaluationModal.dataset.editingId = id;
                document.getElementById('evaluation-title').value = ev.titulo || '';
                document.getElementById('evaluation-slug').value = ev.slug || '';
                document.getElementById('evaluation-slug-display').textContent = ev.slug || '';
                document.getElementById('evaluation-instructions').value = ev.instrucciones || '';
                document.getElementById('evaluation-example').value = ev.ejemplo || '';
                document.getElementById('evaluation-rubric').value = ev.rubrica || '';
                evaluationModal.classList.remove('hidden');
            }
        } catch { showNotification('Error al cargar evaluación', 'error'); }
    }

    async function deleteExercise(id) {
        if (!confirm('¿Eliminar este ejercicio?')) return;
        try {
            const response = await fetch('index.php?action=manage', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_exercise', unit_slug: getCurrentUnitSlug(), exercise_id: id, csrf_token: csrfToken })
            });
            const data = await response.json();
            if (data.success) {
                showNotification('Ejercicio eliminado');
                loadUnitContent(currentUnitId);
            }
        } catch { showNotification('Error de conexión', 'error'); }
    }

    async function deleteEvaluation(id) {
        if (!confirm('¿Eliminar esta evaluación?')) return;
        try {
            const response = await fetch('index.php?action=manage', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_evaluation', unit_slug: getCurrentUnitSlug(), exercise_id: id, csrf_token: csrfToken })
            });
            const data = await response.json();
            if (data.success) {
                showNotification('Evaluación eliminada');
                loadUnitContent(currentUnitId);
            }
        } catch { showNotification('Error de conexión', 'error'); }
    }

    async function toggleExerciseActive(unitSlug, exerciseSlug, active) {
        try {
            const response = await fetch('index.php?action=manage', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'set_exercise_active', unit_slug: unitSlug, exercise_slug: exerciseSlug, active: active ? 1 : 0, csrf_token: csrfToken })
            });
            const data = await response.json();
            if (data.success) {
                showNotification(`Ejercicio ${active ? 'habilitado' : 'inhabilitado'}`);
                loadUnitContent(currentUnitId);
            }
        } catch { showNotification('Error de conexión', 'error'); }
    }

    async function toggleEvaluationActive(unitSlug, exerciseSlug, active) {
        try {
            const response = await fetch('index.php?action=manage', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'set_evaluation_active', unit_slug: unitSlug, exercise_slug: exerciseSlug, active: active ? 1 : 0, csrf_token: csrfToken })
            });
            const data = await response.json();
            if (data.success) {
                showNotification(`Evaluación ${active ? 'habilitada' : 'inhabilitada'}`);
                loadUnitContent(currentUnitId);
            }
        } catch { showNotification('Error de conexión', 'error'); }
    }

    async function toggleContentActive(id, active) {
        try {
            const response = await fetch('index.php?action=content_api', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, fields: { activo: active ? 1 : 0 }, csrf_token: csrfToken })
            });
            const data = await response.json();
            if (data.success) {
                showNotification(`Contenido ${active ? 'habilitado' : 'inhabilitado'}`);
                loadUnitContent(currentUnitId);
            }
        } catch { showNotification('Error de conexión', 'error'); }
    }

    // --- 7. Rúbricas y Lógica de Evaluación ---
    function generateRubricFromCode(code) {
        const rubric = [];
        if (!code) return [{ label: 'Código base', points_total: 100, points_awarded: 0, regex: '.+', feedback: 'Entregado' }];

        const rules = [
            { reg: /\b(int|float|char|double)\s+\w+/g, label: 'Variables', pts: 20 },
            { reg: /if\s*\(|else/g, label: 'Condicionales', pts: 20 },
            { reg: /for\s*\(|while\s*\(/g, label: 'Bucles', pts: 20 },
            { reg: /printf\s*\(|scanf\s*\(/g, label: 'E/S Estándar', pts: 20 },
            { reg: /;/g, label: 'Sintaxis (;)', pts: 20 }
        ];

        rules.forEach(rule => {
            const regex = new RegExp(rule.reg);
            if (regex.test(code)) {
                rubric.push({ label: rule.label, points_total: rule.pts, points_awarded: 0, regex: rule.reg.source, feedback: `${rule.label} detectado` });
            }
        });

        return rubric.length > 0 ? rubric : [{ label: 'Código base', points_total: 100, points_awarded: 0, regex: '.+', feedback: 'Entregado' }];
    }

    // --- 8. Manejadores de Eventos de Modales ---
    
    document.getElementById('unit-create-save')?.addEventListener('click', async () => {
        const title = document.getElementById('unit-create-title').value.trim();
        const description = document.getElementById('unit-create-description').value.trim();
        const order = document.getElementById('unit-order-create')?.value || 1;
        const errorEl = document.getElementById('unit-create-error');
        
        // CAPTURAMOS EL TRIMESTRE ACTUAL AQUÍ
        const trimestreActual = document.getElementById('trimestre-selector').value;

        if (!title) {
            if (errorEl) {
                errorEl.textContent = 'El título es obligatorio';
                errorEl.classList.remove('hidden');
            } else {
                showNotification('El título es obligatorio', 'error');
            }
            return;
        }

        try {
            const response = await fetch('index.php?action=create_unit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    titulo: title,
                    descripcion: description,
                    orden: order,
                    trimestre: trimestreActual, // <--- CAMBIO IMPORTANTE: Enviamos el trimestre
                    csrf_token: csrfToken
                })
            });
            const data = await response.json();
            if (data.success) {
                unitModalCreate.classList.add('hidden');
                showNotification('Unidad creada correctamente. Recargando...', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                if (errorEl) {
                    errorEl.textContent = data.error || 'Error al crear unidad';
                    errorEl.classList.remove('hidden');
                } else {
                    showNotification(data.error || 'Error al crear unidad', 'error');
                }
            }
        } catch (err) { 
            console.error('Error creating unit:', err);
            showNotification('Error de conexión', 'error'); 
        }
    });

    document.getElementById('topic-save')?.addEventListener('click', async () => {
        const id = document.getElementById('topic-id').value;
        const name = document.getElementById('topic-name').value.trim();
        const desc = document.getElementById('topic-description').value.trim();

        if (!name) return showNotification('Nombre obligatorio', 'error');

        const body = id 
            ? { action: 'update_topic', id, fields: { nombre: name, descripcion: desc }, csrf_token: csrfToken }
            : { action: 'create_topic', unit_id: currentUnitId, name, description: desc, csrf_token: csrfToken };

        try {
            const response = await fetch('index.php?action=manage', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            const data = await response.json();
            if (data.success) {
                topicModal.classList.add('hidden');
                loadUnitContent(currentUnitId);
                showNotification('Tema guardado');
            }
        } catch { showNotification('Error de conexión', 'error'); }
    });

    document.getElementById('exercise-save')?.addEventListener('click', async () => {
        const editingId = exerciseModal.dataset.editingId;
        const topicId = document.getElementById('exercise-topic-id').value;
        const title = document.getElementById('exercise-title').value.trim();
        const slug = document.getElementById('exercise-slug').value;
        const instructions = document.getElementById('exercise-instructions').value.trim();
        const example = document.getElementById('exercise-example').value.trim();
        const output = document.getElementById('exercise-expected-output').value.trim();
        const solution = document.getElementById('exercise-solution').value.trim();

        if (!title || !instructions || !example || !output || !solution) return showNotification('Campos obligatorios faltantes', 'error');

        const action = editingId ? 'update_exercise' : 'create_exercise_for_topic';
        const body = { action, unit_slug: getCurrentUnitSlug(), csrf_token: csrfToken };

        if (editingId) {
            body.exercise = editingId;
            body.fields = { title, instructions, example, expected_output: output, solution };
        } else {
            Object.assign(body, { topic_id: topicId, slug, title, instructions, example, expected_output: output, solution });
        }

        try {
            const response = await fetch('index.php?action=manage', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            const data = await response.json();
            if (data.success) {
                exerciseModal.classList.add('hidden');
                loadUnitContent(currentUnitId);
                showNotification(editingId ? 'Ejercicio actualizado' : 'Ejercicio creado');
            }
        } catch { showNotification('Error de conexión', 'error'); }
    });

    document.getElementById('evaluation-save')?.addEventListener('click', async () => {
        const editingId = evaluationModal.dataset.editingId;
        const topicId = document.getElementById('evaluation-topic-id').value;
        const title = document.getElementById('evaluation-title').value.trim();
        const slug = document.getElementById('evaluation-slug').value;
        const instructions = document.getElementById('evaluation-instructions').value.trim();
        const example = document.getElementById('evaluation-example').value.trim();
        const expectedCode = document.getElementById('evaluation-rubric').value.trim();

        if (!title || !instructions || !expectedCode) return showNotification('Título, instrucciones y código son obligatorios', 'error');

        const action = editingId ? 'update_evaluation' : 'create_evaluation_for_topic';
        const rubric = generateRubricFromCode(expectedCode);
        const body = { action, unit_slug: getCurrentUnitSlug(), csrf_token: csrfToken };

        if (editingId) {
            body.key = editingId;
            body.fields = { title, instructions, example, rubric: JSON.stringify(rubric) };
        } else {
            Object.assign(body, { topic_id: topicId, slug, title, instructions, example, rubric: JSON.stringify(rubric), expected_code: expectedCode });
        }

        try {
            const response = await fetch('index.php?action=manage', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            const data = await response.json();
            if (data.success) {
                evaluationModal.classList.add('hidden');
                loadUnitContent(currentUnitId);
                showNotification(editingId ? 'Evaluación actualizada' : 'Evaluación creada');
            }
        } catch { showNotification('Error de conexión', 'error'); }
    });

    // --- 9. Inicialización de Eventos de UI ---

    // Inicializar funcionalidad de edición de unidades para docentes
    if (isTeacher) {
        initializeUnitEditing();
    }

    unitDropdown?.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (this.value) {
            unitTitle.textContent = selected.getAttribute('data-title');
            unitDescription.textContent = selected.getAttribute('data-description');
            loadUnitContent(selected.getAttribute('data-id'));
            unitContent.classList.remove('hidden');
            noUnitSelected.classList.add('hidden');
            
            // Inicializar el estado visual del botón de toggle cuando se cambia de unidad
            const isActive = getCurrentUnitState();
            if (isActive !== null) {
                updateToggleButtonVisual(isActive);
            }
        } else {
            unitContent.classList.add('hidden');
            noUnitSelected.classList.remove('hidden');
            
            // Ocultar el botón de toggle cuando no hay unidad seleccionada
            const btnToggle = document.getElementById('btn-toggle-unit-active');
            if (btnToggle) {
                btnToggle.style.display = 'none';
            }
        }
    });

    document.getElementById('btn-edit-unit-title')?.addEventListener('click', () => {
        const newVal = prompt('Nuevo título:', unitTitle.textContent);
        if (newVal && newVal !== unitTitle.textContent) updateUnitField('titulo', newVal);
    });

    document.getElementById('btn-edit-unit-description')?.addEventListener('click', () => {
        const newVal = prompt('Nueva descripción:', unitDescription.textContent);
        if (newVal !== null && newVal !== unitDescription.textContent) updateUnitField('descripcion', newVal);
    });

    document.getElementById('btn-add-unit')?.addEventListener('click', () => {
        resetUnitModal();
        unitModalCreate.classList.remove('hidden');
    });

    document.getElementById('btn-add-topic')?.addEventListener('click', () => {
        document.getElementById('topic-id').value = '';
        document.getElementById('topic-name').value = '';
        document.getElementById('topic-description').value = '';
        document.getElementById('topic-modal-title').textContent = 'Añadir Tema';
        topicModal.classList.remove('hidden');
    });

    document.getElementById('btn-add-theory-content')?.addEventListener('click', () => {
        resetContentModal();
        contentModal.classList.remove('hidden');
    });

    document.getElementById('exercise-title')?.addEventListener('input', function() {
        if (exerciseModal.dataset.editingId) return;
        const slug = generateSlug(this.value);
        document.getElementById('exercise-slug-display').textContent = slug || 'Se generará automáticamente';
        document.getElementById('exercise-slug').value = slug;
    });

    document.getElementById('evaluation-title')?.addEventListener('input', function() {
        if (evaluationModal.dataset.editingId) return;
        const slug = generateSlug(this.value);
        document.getElementById('evaluation-slug-display').textContent = slug || 'Se generará automáticamente';
        document.getElementById('evaluation-slug').value = slug;
    });

    document.getElementById('content-type')?.addEventListener('change', function() {
        const type = this.value;
        document.getElementById('content-text-section').classList.toggle('hidden', type !== 'texto');
        document.getElementById('content-url-section').classList.toggle('hidden', !['documento', 'video', 'enlace', 'imagen'].includes(type));
    });

    document.getElementById('content-save')?.addEventListener('click', async () => {
        const type = document.getElementById('content-type').value;
        const title = document.getElementById('content-title').value.trim();
        const text = document.getElementById('content-text').value.trim();
        const url = document.getElementById('content-url').value.trim();
        const topicId = document.getElementById('content-topic').value;
        const editingId = contentModal.dataset.editingId;

        // Validación básica
        if (!type) return showNotification('Debes seleccionar un tipo de contenido', 'error');
        if (!title) return showNotification('El título es obligatorio', 'error');
        
        if (type === 'texto' && !text) {
            return showNotification('El contenido de texto no puede estar vacío', 'error');
        }
        
        if (type !== 'texto' && !url) {
            return showNotification('La URL es obligatoria para este tipo de contenido', 'error');
        }

        const body = {
            csrf_token: csrfToken,
            unidad_id: currentUnitId,
            tipo: type,
            titulo: title,
            contenido: text,
            url: url,
            tema_id: topicId || null
        };

        const method = editingId ? 'PUT' : 'POST';
        const payload = editingId ? { id: editingId, fields: body, csrf_token: csrfToken } : body;

        try {
            const response = await fetch('index.php?action=content_api', {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            if (data.success) {
                contentModal.classList.add('hidden');
                loadUnitContent(currentUnitId);
                showNotification('Contenido guardado');
            }
        } catch { showNotification('Error de conexión', 'error'); }
    });

    document.querySelectorAll('.mg-modal-overlay, .modal').forEach(modal => {
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.add('hidden'); });
    });

    document.querySelectorAll('.btn[id$="-cancel"], .modal-close').forEach(btn => {
        btn.addEventListener('click', () => { btn.closest('.mg-modal-overlay, .modal').classList.add('hidden'); });
    });

    // --- 10. Funciones de Reseteo ---
    function resetUnitModal() {
        const titleEl = document.getElementById('unit-create-title');
        const descEl = document.getElementById('unit-create-description');
        const errorEl = document.getElementById('unit-create-error');
        
        if (titleEl) titleEl.value = '';
        if (descEl) descEl.value = '';
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
        }
    }

    function resetContentModal() {
        const fields = ['type', 'title', 'text', 'url', 'topic'];
        fields.forEach(id => {
            const el = document.getElementById(`content-${id}`);
            if (el) el.value = '';
        });
        
        const textSection = document.getElementById('content-text-section');
        const urlSection = document.getElementById('content-url-section');
        
        if (textSection) textSection.classList.add('hidden');
        if (urlSection) urlSection.classList.add('hidden');
        
        if (contentModal) {
            delete contentModal.dataset.editingId;
            // También limpiar cualquier título de edición si existiera
            const modalTitle = contentModal.querySelector('.mg-modal-title');
            if (modalTitle) modalTitle.textContent = 'Gestionar Contenido Didáctico';
        }
    }

    function resetExerciseModal() {
        ['title', 'slug', 'instructions', 'example', 'expected-output', 'solution'].forEach(id => {
            const el = document.getElementById(`exercise-${id}`);
            if (el) el.value = '';
        });
        document.getElementById('exercise-slug-display').textContent = 'Se generará automáticamente';
        delete exerciseModal.dataset.editingId;
    }

    function resetEvaluationModal() {
        ['title', 'slug', 'instructions', 'example', 'rubric'].forEach(id => {
            const el = document.getElementById(`evaluation-${id}`);
            if (el) el.value = '';
        });
        document.getElementById('evaluation-slug-display').textContent = 'Se generará automáticamente';
        delete evaluationModal.dataset.editingId;
    }

    // --- 11. Funciones Globales para HTML ---
    function viewContent(url, type, title) {
        const container = document.getElementById('content-viewer-container');
        document.getElementById('content-viewer-title').textContent = title;
        if (type === 'imagen') {
            container.innerHTML = `<img src="${url}" alt="${title}" style="max-width:100%; max-height:70vh; border-radius:8px;">`;
        } else if (type === 'documento') {
            container.innerHTML = `<iframe src="${url}" style="width:100%; height:70vh; border-radius:8px;" frameborder="0"></iframe>`;
        }
        viewerModal.classList.remove('hidden');
    }

    // --- 12. Emotes SVC ---
    
    // Función para ocultar el contenedor de emotes
    function hideEmotesContainer() {
        const emoteContainer = document.getElementById('unit-emotes');
        const emoteSummary = document.getElementById('unit-emote-summary');
        if (emoteContainer) {
            emoteContainer.style.display = 'none';
        }
        if (emoteSummary) {
            emoteSummary.style.display = 'none';
        }
    }

    // --- 13. Modal de Confirmación ---
    
    // Función para mostrar modal de confirmación personalizado
    function showConfirmationModal(message, callback) {
        const modal = document.getElementById('confirmation-modal');
        const messageEl = document.getElementById('confirmation-message');
        const confirmBtn = document.getElementById('confirmation-confirm');
        const cancelBtn = document.getElementById('confirmation-cancel');

        // Establecer el mensaje
        messageEl.textContent = message;

        // Mostrar el modal
        modal.classList.remove('hidden');

        // Manejar el botón de confirmar
        const handleConfirm = () => {
            modal.classList.add('hidden');
            // Ejecutar el callback solo si se confirma
            if (typeof callback === 'function') {
                callback();
            }
            // Limpiar eventos para evitar fugas
            confirmBtn.removeEventListener('click', handleConfirm);
            cancelBtn.removeEventListener('click', handleCancel);
            modal.removeEventListener('click', handleBackdropClick);
        };

        // Manejar el botón de cancelar
        const handleCancel = () => {
            modal.classList.add('hidden');
            // No ejecutar el callback si se cancela
            confirmBtn.removeEventListener('click', handleConfirm);
            cancelBtn.removeEventListener('click', handleCancel);
            modal.removeEventListener('click', handleBackdropClick);
        };

        // Manejar clic en el fondo para cerrar (similar a otros modales)
        const handleBackdropClick = (e) => {
            if (e.target === modal) {
                handleCancel();
            }
        };

        // Asignar eventos
        confirmBtn.addEventListener('click', handleConfirm);
        cancelBtn.addEventListener('click', handleCancel);
        modal.addEventListener('click', handleBackdropClick);
    }

    function initializeEmotesSVC() {
        if (isTeacher) {
            const container = document.getElementById('unit-emotes');
            if (container) container.style.display = 'none';
            return;
        }

        const buttons = document.querySelectorAll('.emote-button');
        buttons.forEach(btn => {
            btn.addEventListener('click', async () => {
                const emote = btn.dataset.emote;
                const unit = getCurrentUnitSlug();
                if (!unit) return;

                try {
                    const response = await fetch('index.php?action=emote_api', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ unit, emote, csrf_token: csrfToken })
                    });
                    const data = await response.json();
                    if (data.success) {
                        showNotification('¡Gracias por tu reacción!');
                        loadEmoteStats(unit);
                    }
                } catch { console.error('Error sending emote'); }
            });
        });
    }

    async function loadEmoteStats(unit) {
        const summary = document.getElementById('unit-emote-summary');
        if (!summary) return;

        try {
            const response = await fetch(`index.php?action=emote_api&unit=${unit}`);
            const data = await response.json();
            if (data.success && data.stats) {
                summary.innerHTML = Object.entries(data.stats)
                    .filter(([_, count]) => count > 0)
                    .map(([emote, count]) => `<span class="emote-stat">${emote}: ${count}</span>`)
                    .join('');
                summary.style.display = summary.innerHTML ? 'flex' : 'none';
            }
        } catch { console.error('Error loading emote stats'); }
    }

    // --- 13. Exportación Global ---
    Object.assign(window, {
        editTopic,
        deleteTopic,
        toggleTopicActive,
        showContentModalForTopic,
        editContent,
        deleteContent,
        showExerciseModal,
        showEvaluationModal,
        editExercise,
        editEvaluation,
        deleteExercise,
        deleteEvaluation,
        toggleExerciseActive,
        toggleEvaluationActive,
        toggleContentActive,
        viewContent,
        loadUnitContent,
        showOrderModal,
        toggleUnitActive,
        loadUnitsDropdown
    });

    // Delegación de eventos para botones dinámicos
document.addEventListener('click', function(e) {
    const btnOrder = e.target.closest('.btn-order');

    if (btnOrder) {
        e.preventDefault();
        e.stopPropagation(); // <--- EVITA QUE EL EVENTO SE DISPARE DOS VECES
        
        // Limpiar cualquier residuo antes de abrir la nueva
        const redundant = document.querySelectorAll('.mg-modal-overlay');
        redundant.forEach(el => el.remove());

        console.log("Abriendo modal de orden...");
        showOrderModal(e);
    }
});

    initializeEmotesSVC();
    
    // --- 14. Inicialización de Estado de Unidad ---
    // Asegurar la lectura inicial correcta del estado de unidad
    // Esta lógica se ejecuta después de que todos los elementos y eventos estén listos
    (function initializeUnitState() {
        // Verificar si hay una unidad preseleccionada al cargar la página
        if (unitDropdown && unitDropdown.value) {
            // Si hay una unidad seleccionada, inicializar el estado del botón de toggle
            const isActive = getCurrentUnitState();
            if (isActive !== null) {
                updateToggleButtonVisual(isActive);
                // Asegurar que el botón esté visible cuando hay una unidad seleccionada
                const btnToggle = document.getElementById('btn-toggle-unit-active');
                if (btnToggle) {
                    btnToggle.style.display = 'inline-block';
                }
            }
        } else {
            // Si no hay unidad seleccionada, ocultar el botón de toggle
            const btnToggle = document.getElementById('btn-toggle-unit-active');
            if (btnToggle) {
                btnToggle.style.display = 'none';
            }
        }
    })();

    if (trimestreSelector && toggleVisibilidad) {
        const initTrimestre = trimestreSelector.value;
        fetch(`index.php?action=get_units_by_trimestre&trimestre=${initTrimestre}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    toggleVisibilidad.checked = data.isVisible;
                }
            });
    }

});
