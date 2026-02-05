/**
 * Gestión de temas para docentes en la vista de contenido
 */
(function() {
    // Variables globales
    let currentUnitId = null;
    let topicModal = null;
    let topicForm = null;

    // Inicialización
    document.addEventListener('DOMContentLoaded', function() {
        initializeTopics();
    });

    function initializeTopics() {
        console.log('initializeTopics called');

        // Solo inicializar si el usuario es docente/administrador
        const rol = document.body.getAttribute('data-rol') || 'Usuario';
        console.log('User role:', rol);

        if (!['Docente', 'Administrador'].includes(rol)) {
            console.log('User is not docente/admin - skipping topics initialization');
            return;
        }

        // Obtener elementos del DOM
        topicModal = document.getElementById('topic-modal');
        console.log('topicModal element:', topicModal);

        topicForm = {
            id: document.getElementById('topic-id'),
            name: document.getElementById('topic-name'),
            description: document.getElementById('topic-description'),
            error: document.getElementById('topic-error'),
            saveBtn: document.getElementById('topic-save'),
            cancelBtn: document.getElementById('topic-cancel'),
            title: document.getElementById('topic-modal-title')
        };

        console.log('topicForm elements:', topicForm);

        // Configurar event listeners
        setupEventListeners();

        // Escuchar cambios en la unidad seleccionada
        const unitDropdown = document.getElementById('unit-dropdown');
        if (unitDropdown) {
            unitDropdown.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                currentUnitId = selectedOption ? selectedOption.getAttribute('data-id') : null;
                if (currentUnitId) {
                    loadTopicsForUnit(currentUnitId);
                } else {
                    clearTopicsList();
                }
            });
        }
    }

    function setupEventListeners() {
        // Botón añadir tema
        const addTopicBtn = document.getElementById('btn-add-topic');
        if (addTopicBtn) {
            addTopicBtn.addEventListener('click', () => showTopicModal());
        }

        // Botones del modal
        if (topicForm.cancelBtn) {
            topicForm.cancelBtn.addEventListener('click', hideTopicModal);
        }

        if (topicForm.saveBtn) {
            topicForm.saveBtn.addEventListener('click', saveTopic);
        }

        // Cerrar modal al hacer clic fuera - REMOVIDO para evitar pérdida accidental de datos
        // Los modales solo se pueden cerrar mediante los botones de cancelar/cerrar

        // Cerrar modal con tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && topicModal && !topicModal.classList.contains('hidden')) {
                hideTopicModal();
            }
        });
    }

    function loadTopicsForUnit(unitId) {
        if (!unitId) {
            clearTopicsList();
            return;
        }

        const topicsList = document.getElementById('topics-list');
        if (!topicsList) return;

        // Mostrar loading
        topicsList.innerHTML = '<div class="loading">Cargando temas...</div>';

        // Hacer petición AJAX
        fetch(`index.php?action=topics&unit_id=${unitId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTopicsList(data.topics || []);
            } else {
                topicsList.innerHTML = '<div class="error">Error al cargar temas</div>';
            }
        })
        .catch(error => {
            console.error('Error loading topics:', error);
            topicsList.innerHTML = '<div class="error">Error de conexión</div>';
        });
    }

    function renderTopicsList(topics) {
        const topicsList = document.getElementById('topics-list');
        if (!topicsList) return;

        if (topics.length === 0) {
            topicsList.innerHTML = '<div class="no-topics">No hay temas definidos para esta unidad</div>';
            return;
        }

        let html = '';
        topics.forEach(topic => {
            html += `
                <div class="topic-item" data-id="${topic.id}">
                    <div class="topic-header">
                        <h4>${escapeHtml(topic.nombre)}</h4>
                        <div class="topic-actions">
                            <button class="btn btn-small btn-edit" onclick="editTopic(${topic.id})" title="Editar">✏️</button>
                            <button class="btn btn-small btn-delete" onclick="deleteTopic(${topic.id})" title="Eliminar">🗑️</button>
                            <button class="btn btn-small btn-add-exercise" onclick="showExerciseModal(${topic.id})" title="Añadir ejercicio">➕ Ejercicio</button>
                        </div>
                    </div>
                    ${topic.descripcion ? `<p class="topic-description">${escapeHtml(topic.descripcion)}</p>` : ''}
                    <div class="topic-exercises" id="topic-exercises-${topic.id}">
                        <!-- Los ejercicios se cargarán aquí -->
                    </div>
                </div>
            `;
        });

        topicsList.innerHTML = html;

        // Cargar ejercicios para cada tema
        topics.forEach(topic => {
            loadExercisesForTopic(topic.id);
        });
    }

    function loadExercisesForTopic(topicId) {
        const container = document.getElementById(`topic-exercises-${topicId}`);
        if (!container) return;

        container.innerHTML = '<small>Cargando ejercicios...</small>';

        fetch(`index.php?action=topic_exercises&topic_id=${topicId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const exercises = data.exercises || [];
                if (exercises.length > 0) {
                    let html = '<small>Ejercicios:</small><ul>';
                    exercises.forEach(exercise => {
                        html += `<li>${escapeHtml(exercise.titulo)}</li>`;
                    });
                    html += '</ul>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<small>Sin ejercicios prácticos asignados a este tema</small>';
                }
            } else {
                container.innerHTML = '<small>Error al cargar ejercicios</small>';
            }
        })
        .catch(error => {
            console.error('Error loading exercises:', error);
            container.innerHTML = '<small>Error de conexión</small>';
        });
    }

    function clearTopicsList() {
        const topicsList = document.getElementById('topics-list');
        if (topicsList) {
            topicsList.innerHTML = '';
        }
    }

    function showTopicModal(topicId = null) {
        console.log('showTopicModal called with topicId:', topicId);
        console.log('currentUnitId:', currentUnitId);
        console.log('topicModal:', topicModal);

        if (!topicModal) {
            console.error('topicModal is null - modal not found in DOM');
            alert('Error: Modal no encontrado. Recarga la página.');
            return;
        }

        if (!currentUnitId) {
            console.warn('No unit selected');
            alert('Selecciona una unidad primero');
            return;
        }

        // Resetear formulario
        resetTopicForm();

        // Si es edición, cargar datos del tema
        if (topicId) {
            loadTopicData(topicId);
            topicForm.title.textContent = 'Editar Tema';
        } else {
            topicForm.title.textContent = 'Añadir Tema';
        }

        // Mostrar modal
        console.log('Removing hidden class from modal');
        topicModal.classList.remove('hidden');
        if (topicForm.name) {
            topicForm.name.focus();
        }
    }

    function hideTopicModal() {
        if (topicModal) {
            topicModal.classList.add('hidden');
        }
        resetTopicForm();
    }

    function resetTopicForm() {
        if (topicForm) {
            topicForm.id.value = '';
            topicForm.name.value = '';
            topicForm.description.value = '';
            topicForm.error.textContent = '';
            topicForm.error.classList.add('hidden');
        }
    }

    function loadTopicData(topicId) {
        // Cargar datos del tema para edición
        const topics = document.querySelectorAll('.topic-item');
        topics.forEach(topic => {
            if (topic.dataset.id == topicId) {
                const name = topic.querySelector('h4').textContent;
                const description = topic.querySelector('.topic-description') ?
                    topic.querySelector('.topic-description').textContent : '';

                topicForm.id.value = topicId;
                topicForm.name.value = name;
                topicForm.description.value = description;
            }
        });
    }

    function saveTopic() {
        if (!topicForm || !currentUnitId) return;

        const formData = {
            id: topicForm.id.value,
            unit_id: currentUnitId,
            name: topicForm.name.value.trim(),
            description: topicForm.description.value.trim(),
            csrf_token: getCSRFToken()
        };

        // Validación básica
        if (!formData.name) {
            showTopicError('El nombre del tema es obligatorio');
            return;
        }

        // Deshabilitar botón
        topicForm.saveBtn.disabled = true;
        topicForm.saveBtn.textContent = 'Guardando...';

        // Determinar acción (crear o actualizar)
        const action = formData.id ? 'update_topic' : 'create_topic';

        // Preparar datos según la acción
        const requestData = {
            action: action,
            csrf_token: formData.csrf_token
        };

        if (action === 'create_topic') {
            requestData.unit_id = formData.unit_id;
            requestData.name = formData.name;
            requestData.description = formData.description;
        } else { // update_topic
            requestData.id = formData.id;
            requestData.fields = {
                nombre: formData.name,
                descripcion: formData.description
            };
        }

        // Enviar petición
        fetch('index.php?action=manage', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                hideTopicModal();
                loadTopicsForUnit(currentUnitId);
                // Actualizar la sección unificada si existe
                if (typeof loadTopicsWithContent === 'function') {
                    loadTopicsWithContent(currentUnitId);
                }
                // También actualizar la sección de ejemplos prácticos si existe
                if (typeof loadExercisesForExamplesSection === 'function') {
                    loadExercisesForExamplesSection(currentUnitId);
                }
                showNotification(data.message || 'Tema guardado correctamente', 'success');
            } else {
                showTopicError(data.error || 'Error al guardar el tema');
            }
        })
        .catch(error => {
            console.error('Error saving topic:', error);
            showTopicError('Error de conexión');
        })
        .finally(() => {
            topicForm.saveBtn.disabled = false;
            topicForm.saveBtn.textContent = 'Guardar';
        });
    }

    // Funciones globales para usar desde HTML
    window.editTopic = function(topicId) {
        showTopicModal(topicId);
    };

    window.deleteTopic = function(topicId) {
        if (confirm('¿Estás seguro de que quieres eliminar este tema?')) {
            fetch('index.php?action=manage', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete_topic',
                    id: topicId,
                    csrf_token: getCSRFToken()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadTopicsForUnit(currentUnitId);
                    // Actualizar la sección unificada si existe
                    if (typeof loadTopicsWithContent === 'function') {
                        loadTopicsWithContent(currentUnitId);
                    }
                    // También actualizar la sección de ejemplos prácticos si existe
                    if (typeof loadExercisesForExamplesSection === 'function') {
                        loadExercisesForExamplesSection(currentUnitId);
                    }
                    showNotification('Tema eliminado correctamente', 'success');
                } else {
                    showNotification(data.error || 'Error al eliminar el tema', 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting topic:', error);
                showNotification('Error de conexión', 'error');
            });
        }
    };

    // Exponer la función showExerciseModal globalmente para que pueda ser llamada desde HTML
    window.showExerciseModal = function(topicId) {
        const exerciseModal = document.getElementById('exercise-modal');
        if (!exerciseModal) {
            console.error('Exercise modal not found');
            alert('Error: Modal de ejercicio no encontrado');
            return;
        }

        // Obtener el slug de la unidad actual
        const unitDropdown = document.getElementById('unit-dropdown');
        const selectedOption = unitDropdown.options[unitDropdown.selectedIndex];
        const unitSlug = selectedOption ? selectedOption.value : '';

        if (!unitSlug) {
            alert('Selecciona una unidad primero');
            return;
        }

        // Configurar el modal
        document.getElementById('exercise-topic-id').value = topicId;
        document.getElementById('exercise-unit-slug').value = unitSlug;
        document.getElementById('exercise-title').value = '';
        document.getElementById('exercise-slug').value = '';
        document.getElementById('exercise-instructions').value = '';
        document.getElementById('exercise-example').value = '';
        document.getElementById('exercise-expected-output').value = '';
        document.getElementById('exercise-solution').value = '';
        document.getElementById('exercise-error').classList.add('hidden');

        // Mostrar el modal
        exerciseModal.classList.remove('hidden');

        // Configurar event listeners para el modal de ejercicios
        setupExerciseModalListeners();

        // Configurar validaciones en tiempo real
        setupRealTimeValidations();
    };

    function showTopicError(message) {
        if (topicForm.error) {
            topicForm.error.textContent = message;
            topicForm.error.classList.remove('hidden');
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

    function showNotification(message, type = 'info') {
        // Usar el sistema de notificaciones existente si está disponible
        if (window.manageJS && typeof window.manageJS.showNotification === 'function') {
            window.manageJS.showNotification(message, type);
        } else {
            // Fallback: crear notificación DOM como en otros archivos del sistema
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }
    }

    // Funciones para manejar ejercicios (ahora definidas como funciones internas)
    function setupExerciseModalListeners() {
        const exerciseModal = document.getElementById('exercise-modal');
        const cancelBtn = document.getElementById('exercise-cancel');
        const saveBtn = document.getElementById('exercise-save');

        if (cancelBtn) {
            cancelBtn.onclick = function() {
                exerciseModal.classList.add('hidden');
            };
        }

        if (saveBtn) {
            saveBtn.onclick = function() {
                saveExercise();
            };
        }

        // Cerrar modal al hacer clic fuera - REMOVIDO para evitar pérdida accidental de datos
        // Los modales solo se pueden cerrar mediante los botones de cancelar/cerrar
    }

    function saveExercise() {
        const topicId = document.getElementById('exercise-topic-id').value;
        const unitSlug = document.getElementById('exercise-unit-slug').value;
        const title = document.getElementById('exercise-title').value.trim();
        const slug = document.getElementById('exercise-slug').value.trim();
        const instructions = document.getElementById('exercise-instructions').value.trim();
        const example = document.getElementById('exercise-example').value.trim();
        const expectedOutput = document.getElementById('exercise-expected-output').value.trim();
        const solution = document.getElementById('exercise-solution').value.trim();

        // Validación básica
        if (!title || !slug) {
            showExerciseError('Título y slug son obligatorios');
            return;
        }

        // Validación de campos obligatorios
        if (!example) {
            showExerciseError('El campo Ejemplo es obligatorio');
            return;
        }

        if (!expectedOutput) {
            showExerciseError('El campo Salida esperada es obligatorio');
            return;
        }

        if (!solution) {
            showExerciseError('El campo Solución es obligatorio');
            return;
        }

        // Deshabilitar botón de guardar
        const saveBtn = document.getElementById('exercise-save');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Guardando...';

        // Enviar petición para crear el ejercicio
        fetch('index.php?action=manage', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'create_exercise_for_topic',
                unit_slug: unitSlug,
                topic_id: parseInt(topicId),
                slug: slug || title.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').trim(),
                title: title,
                instructions: instructions,
                example: example,
                expected_output: expectedOutput,
                solution: solution,
                csrf_token: getCSRFToken()
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cerrar modal
                document.getElementById('exercise-modal').classList.add('hidden');

                // Recargar los temas para mostrar el nuevo ejercicio
                loadTopicsForUnit(currentUnitId);

                // Actualizar la sección unificada si existe
                if (typeof loadTopicsWithContent === 'function') {
                    loadTopicsWithContent(currentUnitId);
                }
                // También actualizar la sección de ejemplos prácticos si existe
                if (typeof loadExercisesForExamplesSection === 'function') {
                    loadExercisesForExamplesSection(currentUnitId);
                }

                showNotification(data.message || 'Ejercicio creado correctamente', 'success');
            } else {
                showExerciseError(data.error || 'Error al crear el ejercicio');
            }
        })
        .catch(error => {
            console.error('Error creating exercise:', error);
            showExerciseError('Error de conexión');
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Guardar Ejercicio';
        });
    }

    function showExerciseError(message) {
        const errorElement = document.getElementById('exercise-error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
        }
    }

    /**
     * Configura validaciones en tiempo real para el formulario de ejercicios
     *
     * Estas validaciones son complementarias a las de Validacion.php:
     * - Validacion.php: Valida reglas de negocio y datos del lado del servidor
     * - setupRealTimeValidations: Proporciona feedback inmediato de UX (campos obligatorios, formatos)
     *
     * Ambas son necesarias:
     * 1. Validaciones en tiempo real mejoran la experiencia de usuario
     * 2. Validacion.php garantiza la integridad de los datos en el backend
     */
    function setupRealTimeValidations() {
        const titleInput = document.getElementById('exercise-title');
        const slugInput = document.getElementById('exercise-slug');
        const exampleInput = document.getElementById('exercise-example');
        const instructionsInput = document.getElementById('exercise-instructions');

        // Generar slug automáticamente desde el título
        titleInput.addEventListener('input', function() {
            const title = titleInput.value.trim();
            const slugDisplay = document.getElementById('exercise-slug-display');
            const slugInput = document.getElementById('exercise-slug');
            
            if (title) {
                // Generar slug: convertir a minúsculas, reemplazar espacios con guiones, eliminar caracteres especiales
                const slug = title
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '') // Eliminar caracteres especiales
                    .replace(/\s+/g, '-') // Reemplazar espacios con guiones
                    .replace(/-+/g, '-') // Reemplazar múltiples guiones con uno solo
                    .trim();

                // Actualizar el display del slug
                if (slugDisplay) {
                    slugDisplay.textContent = slug || 'Se generará automáticamente desde el título';
                }
                
                // Actualizar el campo oculto
                if (slugInput) {
                    slugInput.value = slug;
                }
            } else {
                if (slugDisplay) {
                    slugDisplay.textContent = 'Se generará automáticamente desde el título';
                }
                if (slugInput) {
                    slugInput.value = '';
                }
            }
        });

        // Validación en tiempo real para campos obligatorios
        const validateField = (field, isRequired = true) => {
            const value = field.value.trim();
            const errorElement = field.nextElementSibling;

            // Crear elemento de error si no existe
            if (!errorElement || !errorElement.classList.contains('validation-error')) {
                const errorSpan = document.createElement('span');
                errorSpan.className = 'validation-error';
                errorSpan.style.color = '#dc3545';
                errorSpan.style.fontSize = '12px';
                errorSpan.style.marginTop = '4px';
                errorSpan.style.display = 'none';
                field.parentNode.insertBefore(errorSpan, field.nextSibling);
            }

            const nextErrorElement = field.nextElementSibling;
            if (isRequired && !value) {
                nextErrorElement.textContent = 'Este campo es obligatorio';
                nextErrorElement.style.display = 'block';
                field.style.borderColor = '#dc3545';
                return false;
            } else {
                nextErrorElement.style.display = 'none';
                field.style.borderColor = '#ddd';
                return true;
            }
        };

        // Validar título en tiempo real
        titleInput.addEventListener('blur', function() {
            validateField(titleInput, true);
        });

        // Validar longitud del título (máximo 96 caracteres)
        titleInput.addEventListener('input', function() {
            const value = titleInput.value;
            const errorElement = titleInput.nextElementSibling;
            
            if (!errorElement || !errorElement.classList.contains('validation-error')) {
                const errorSpan = document.createElement('span');
                errorSpan.className = 'validation-error';
                errorSpan.style.color = '#dc3545';
                errorSpan.style.fontSize = '12px';
                errorSpan.style.marginTop = '4px';
                errorSpan.style.display = 'none';
                titleInput.parentNode.insertBefore(errorSpan, titleInput.nextSibling);
            }

            const nextErrorElement = titleInput.nextElementSibling;
            if (value.length > 96) {
                nextErrorElement.textContent = 'El título no puede exceder 96 caracteres';
                nextErrorElement.style.display = 'block';
                titleInput.style.borderColor = '#dc3545';
            } else {
                nextErrorElement.style.display = 'none';
                titleInput.style.borderColor = '#ddd';
            }
        });

        // Validar ejemplo en tiempo real (ahora obligatorio)
        exampleInput.addEventListener('blur', function() {
            validateField(exampleInput, true);
        });

        // Validar salida esperada en tiempo real (ahora obligatorio)
        const expectedOutputInput = document.getElementById('exercise-expected-output');
        expectedOutputInput.addEventListener('blur', function() {
            validateField(expectedOutputInput, true);
        });

        // Validar solución en tiempo real (ahora obligatorio)
        const solutionInput = document.getElementById('exercise-solution');
        solutionInput.addEventListener('blur', function() {
            validateField(solutionInput, true);
        });

        // Validar instrucciones en tiempo real
        instructionsInput.addEventListener('blur', function() {
            validateField(instructionsInput, true);
        });

        // Validar slug en tiempo real (usando el campo oculto)
        slugInput.addEventListener('blur', function() {
            const value = slugInput.value.trim();
            const errorElement = slugInput.nextElementSibling;

            if (!errorElement || !errorElement.classList.contains('validation-error')) {
                const errorSpan = document.createElement('span');
                errorSpan.className = 'validation-error';
                errorSpan.style.color = '#dc3545';
                errorSpan.style.fontSize = '12px';
                errorSpan.style.marginTop = '4px';
                errorSpan.style.display = 'none';
                slugInput.parentNode.insertBefore(errorSpan, slugInput.nextSibling);
            }

            const nextErrorElement = slugInput.nextElementSibling;
            if (!value) {
                nextErrorElement.textContent = 'El slug es obligatorio';
                nextErrorElement.style.display = 'block';
                slugInput.style.borderColor = '#dc3545';
                return false;
            } else if (!/^[a-z0-9-]+$/.test(value)) {
                nextErrorElement.textContent = 'El slug solo puede contener letras minúsculas, números y guiones';
                nextErrorElement.style.display = 'block';
                slugInput.style.borderColor = '#dc3545';
                return false;
            } else {
                nextErrorElement.style.display = 'none';
                slugInput.style.borderColor = '#ddd';
                return true;
            }
        });

        // Mostrar información sobre los campos
        const infoMessages = {
            title: 'El título debe ser claro y descriptivo del ejercicio',
            slug: 'El slug se genera automáticamente desde el título (solo minúsculas, números y guiones)',
            instructions: 'Describe claramente lo que el estudiante debe hacer',
            example: 'Proporciona un ejemplo de código que ilustre la solución (obligatorio)',
            expected_output: 'Describe qué salida debe producir el código (obligatorio)',
            solution: 'La solución completa para que el sistema pueda verificar automáticamente (obligatorio)'
        };

        // Añadir tooltips de información
        Object.entries(infoMessages).forEach(([fieldId, message]) => {
            const field = document.getElementById(`exercise-${fieldId}`);
            if (field) {
                const label = field.previousElementSibling;
                if (label && label.tagName === 'LABEL') {
                    label.title = message;
                    label.style.cursor = 'help';
                    label.style.position = 'relative';
                }
            }
        });
    }

    // Exponer funciones globalmente si es necesario
    window.TopicsManager = {
        loadTopicsForUnit,
        showTopicModal,
        hideTopicModal,
        showExerciseModal
    };
})();
