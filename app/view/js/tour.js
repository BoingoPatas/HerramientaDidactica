/**
 * Tour guiado para nuevos usuarios - Herramienta Didáctica con Enfoque Inclusivo a Discapacidad Auditiva
 */

(function() {
    'use strict';

    // Estado del tour
    let currentStep = 0;
    let tourSteps = [];
    let tourRol = 'Usuario'; // Se puede detectar dinámicamente

    // Elementos DOM
    const welcomeModal = document.getElementById('welcome-modal');
    const tourModal = document.getElementById('tour-modal');
    const tourOverlay = document.getElementById('tour-overlay');
    const tourHighlight = document.getElementById('tour-highlight');

    // Botones
    const skipTourBtn = document.getElementById('skip-tour');
    const startTourBtn = document.getElementById('start-tour');
    const prevTourStepBtn = document.getElementById('prev-tour-step');
    const nextTourStepBtn = document.getElementById('next-tour-step');
    const finishTourBtn = document.getElementById('finish-tour');
    const closeTourBtn = document.getElementById('close-tour');

    // Contenido
    const tourTitle = document.getElementById('tour-title');
    const tourContent = document.getElementById('tour-content');
    const tourProgress = document.getElementById('tour-progress');

    // Detectar rol
    function detectarRol() {
        const bodyRol = document.body.getAttribute('data-rol');
        return bodyRol || 'Usuario';
    }

    // Definir pasos del tour por rol
    function getTourSteps(rol) {
        const baseSteps = [
            {
                title: 'Inicio - Tu Panel Principal 🏠',
                content: `
                    <h4>¡Este es tu dashboard personal!</h4>
                    <p>Desde aquí puedes ver tu progreso en tiempo real y acceder a todas las funciones.</p>
                    <p><strong>Elementos destacados:</strong></p>
                    <ul>
                        <li><strong>4 tarjetas de estadísticas</strong>: Muestran unidades completadas, último progreso, etc.</li>
                        <li><strong>Menú lateral</strong>: Navegación intuitiva a todas las secciones</li>
                        <li><strong>LSV Window</strong>: Soporte visual para lenguaje de señas (opcional)</li>
                    </ul>
                    <p style="color: #059669;">💡 <em>Todo está diseñado con alto contraste y símbolos visuales claros.</em></p>
                `,
                target: '#section-inicio .stats-grid',
                position: 'center',
                showSection: 'inicio'
            }
        ];

        if (rol === 'Usuario') {
            baseSteps.push(
                {
                    title: 'Contenido - Aprende Programación en C 📚',
                    content: `
                        <h4>⚙️ Selector Interactivo de Unidades</h4>
                        <p>Esta opción te lleva a una página con:</p>
                        <div style="background: rgba(6, 182, 212, 0.1); padding: 10px; border-radius: 6px; margin: 10px 0;">
                            <strong>Acceso completo a:</strong>
                            <ul style="margin: 5px 0;">
                                <li>🎯 Selector desplegable de unidades disponibles</li>
                                <li>📖 Contenido teórico organizado por temas</li>
                                <li>📱 Interfaz responsive para todos los dispositivos</li>
                            </ul>
                        </div>
                        <p>Haz clic en <strong>"Contenido"</strong> para explorar el material teórico paso a paso.</p>
                        <p style="color: #059669;">📚 <em>Contenido diseñado específicamente para aprendizaje progresivo.</em></p>
                    `,
                    target: '#nav-content',
                    position: 'center',
                    showSection: null
                },
                {
                    title: 'Prácticas - Codifica Interactivamente 💻',
                    content: `
                        <h4>🛠️ Editor Interactivo y Lista de Ejercicios</h4>
                        <p>Esta opción te lleva al sistema de ejercicios con:</p>
                        <div style="background: rgba(59, 130, 246, 0.1); padding: 10px; border-radius: 6px; margin: 10px 0;">
                            <strong>Herramientas incluidas:</strong>
                            <ul style="margin: 5px 0;">
                                <li>💻 Editor C con resaltado de sintaxis</li>
                                <li>🎯 Verificación automática de código</li>
                                <li>✅ Retroalimentación inmediata</li>
                                <li>💾 Guardado automático mientras escribes</li>
                            </ul>
                        </div>
                        <p>Haz clic en <strong>"Prácticas"</strong> para resolver ejercicios interactivos.</p>
                        <p style="color: #dc2626;">⚡ <em>El sistema detecta tu progreso para evaluaciones.</em></p>
                    `,
                    target: '#nav-practices',
                    position: 'center',
                    showSection: null
                },
                {
                    title: 'Evaluaciones - Demuestra tu Progreso 📝',
                    content: `
                        <h4>📋 Sistema de Evaluación por Competencias</h4>
                        <p>Esta opción te lleva a las evaluaciones con:</p>
                        <div style="background: rgba(234, 179, 8, 0.1); padding: 10px; border-radius: 6px; margin: 10px 0;">
                            <strong>Características del sistema:</strong>
                            <ul style="margin: 5px 0;">
                                <li>📊 Puntaje inmediato al completar</li>
                                <li>💡 Retroalimentación detallada</li>
                                <li>🔄 Posibilidad de reintentar preguntas</li>
                                <li>🎯 Evaluaciones basadas en unidades completadas</li>
                            </ul>
                        </div>
                        <p>Haz clic en <strong>"Evaluaciones"</strong> para demostrar tus conocimientos.</p>
                        <p style="color: #059669;">🎯 <em>Solo aparecen cuando has completado los ejercicios previos.</em></p>
                    `,
                    target: '#nav-evaluations',
                    position: 'center',
                    showSection: null
                },
                {
                    title: 'Manual Completo - Guía Paso a Paso 📖',
                    content: `
                        <h4>📚 Guía Completa de 16 Páginas</h4>
                        <p>Esta sección incluye documentación detallada con:</p>
                        <div style="background: rgba(5, 150, 105, 0.1); padding: 10px; border-radius: 6px; margin: 10px 0;">
                            <strong>Capítulos incluidos:</strong>
                            <ul style="margin: 5px 0;">
                                <li>🎯 Instrucciones completas de uso</li>
                                <li>🧏 Soporte LSV con señas venezolanas</li>
                                <li>🎨 Diagramas y elementos visuales</li>
                                <li>⌨️ Navegación accesible por teclado</li>
                            </ul>
                        </div>
                        <p>Haz clic en <strong>"Manual"</strong> para acceder a la documentación completa.</p>
                        <p style="color: #059669;">📖 <em>Tu mejor aliado cuando tengas dudas.</em></p>
                    `,
                    target: '#nav-manual',
                    position: 'center',
                    showSection: null
                },
                {
                    title: 'Ventana LSV - Inclusión Auditiva 🧏',
                    content: `
                        <h4>🎯 Botón Especial en el Encabezado Superior</h4>
                        <p>Esta funcionalidad incluye soporte audiovisual:</p>
                        <div style="background: rgba(255, 193, 7, 0.1); padding: 10px; border-radius: 6px; margin: 10px 0;">
                            <strong>Sistema elegido por ti:</strong>
                            <ul style="margin: 5px 0;">
                                <li>📽️ Videos en Lenguaje de Señas Venezolanas</li>
                                <li>🧏 Contenido especialmente para personas sordas</li>
                                <li>🎨 Elementos visuales destacados</li>
                                <li>🔊 Indicadores no auditivos</li>
                            </ul>
                        </div>
                        <p>Busca el botón en la esquina superior derecha del sistema.</p>
                        <p style="color: #059669;">🧏 <em>Diseñado específicamente para accesibilidad auditiva.</em></p>
                    `,
                    target: '#btn-toggle-lsv',
                    position: 'center',
                    showSection: null
                }
            );
        } else if (rol === 'Docente') {
            baseSteps.push(
                {
                    title: 'Contenido - Gestiona Material Didáctico 📚',
                    content: `
                        <h4>⚙️ Panel de Gestión de Contenido Académico</h4>
                        <p>Como docente, tienes acceso completo a las herramientas de gestión:</p>
                        <div style="background: rgba(6, 182, 212, 0.1); padding: 10px; border-radius: 6px; margin: 10px 0;">
                            <strong>Herramientas disponibles:</strong>
                            <ul style="margin: 5px 0;">
                                <li>➕ <strong>Añadir contenido</strong>: Crear nuevo material didáctico</li>
                                <li>✏️ <strong>Modificar contenido</strong>: Editar materiales existentes</li>
                                <li>🗂️ <strong>Organizar contenido</strong>: Reordenar módulos y lecciones</li>
                                <li>🗑️ <strong>Eliminar contenido</strong>: Retirar material no necesario</li>
                            </ul>
                        </div>
                        <p>Haz clic en <strong>"Contenido"</strong> para acceder al panel de gestión académica.</p>
                        <p style="color: #059669;">📚 <em>Tú controlas todo el material educativo de tus estudiantes.</em></p>
                    `,
                    target: '#nav-content',
                    position: 'center',
                    showSection: 'contenido'
                },
                {
                    title: 'Prácticas - Supervisa Ejercicios Interactivos 💻',
                    content: `
                        <h4>🛠️ Sistema de Ejercicios para tus Estudiantes</h4>
                        <p>Esta opción te lleva al sistema de prácticas donde puedes:</p>
                        <div style="background: rgba(59, 130, 246, 0.1); padding: 10px; border-radius: 6px; margin: 10px 0;">
                            <strong>Funcionalidades docentes:</strong>
                            <ul style="margin: 5px 0;">
                                <li>📊 Monitorear el progreso de estudiantes</li>
                                <li>💻 Revisar códigos enviados por alumnos</li>
                                <li>✅ Verificar corrección automática de ejercicios</li>
                                <li>📈 Analizar estadísticas de rendimiento</li>
                            </ul>
                        </div>
                        <p>Haz clic en <strong>"Prácticas"</strong> para supervisar el aprendizaje práctico.</p>
                        <p style="color: #dc2626;">⚡ <em>El sistema evalúa automáticamente el progreso de tus estudiantes.</em></p>
                    `,
                    target: '#nav-practices',
                    position: 'center',
                    showSection: null
                },
                {
                    title: 'Evaluaciones - Administra Exámenes 📝',
                    content: `
                        <h4>📋 Centro de Control de Evaluaciones</h4>
                        <p>Como docente, administras todas las evaluaciones del curso:</p>
                        <div style="background: rgba(234, 179, 8, 0.1); padding: 10px; border-radius: 6px; margin: 10px 0;">
                            <strong>Herramientas de gestión:</strong>
                            <ul style="margin: 5px 0;">
                                <li>➕ <strong>Añadir evaluación</strong>: Crear nuevos exámenes</li>
                                <li>✏️ <strong>Modificar evaluación</strong>: Editar evaluaciones existentes</li>
                                <li>📊 Revisar calificaciones de estudiantes</li>
                                <li>🗑️ <strong>Eliminar evaluación</strong>: Retirar exámenes obsoletos</li>
                            </ul>
                        </div>
                        <p>Haz clic en <strong>"Evaluaciones"</strong> para gestionar las pruebas del curso.</p>
                        <p style="color: #059669;">🎯 <em>Tú defines las evaluaciones que medirán el aprendizaje.</em></p>
                    `,
                    target: '#nav-evaluations',
                    position: 'center',
                    showSection: 'evaluaciones'
                },
                {
                    title: 'Usuarios - Gestiona tus Estudiantes 👥',
                    content: `
                        <h4>🏫 Panel de Administración de Estudiantes</h4>
                        <p>Esta es tu herramienta principal para gestionar estudiantes:</p>
                        <div style="background: rgba(168, 85, 247, 0.1); padding: 10px; border-radius: 6px; margin: 10px 0;">
                            <strong>Funciones disponibles:</strong>
                            <ul style="margin: 5px 0;">
                                <li>➕ <strong>Añadir usuario</strong>: Registrar nuevos estudiantes</li>
                                <li>✏️ <strong>Modificar usuario</strong>: Actualizar información de estudiantes</li>
                                <li>🚫 <strong>Inhabilitar usuario</strong>: Controlar acceso de estudiantes</li>
                                <li>📋 Lista completa de estudiantes activos/inactivos</li>
                            </ul>
                        </div>
                        <p>Haz clic en <strong>"Usuarios"</strong> para administrar tu grupo de estudiantes.</p>
                        <p style="color: #7c3aed;">👥 <em>Tú controlas quién participa en tus clases.</em></p>
                    `,
                    target: '#nav-users',
                    position: 'center',
                    showSection: 'usuarios'
                },
                {
                    title: 'Manual Completo - Guía para Docentes 📖',
                    content: `
                        <h4>📚 Guía Completa de 16 Páginas para Educadores</h4>
                        <p>Esta documentación incluye secciones especiales para docentes:</p>
                        <div style="background: rgba(5, 150, 105, 0.1); padding: 10px; border-radius: 6px; margin: 10px 0;">
                            <strong>Contenido docente incluido:</strong>
                            <ul style="margin: 5px 0;">
                                <li>🎯 Funciones avanzadas de gestión académica</li>
                                <li>🧏 Soporte LSV con señas venezolanas</li>
                                <li>📊 Guías para análisis de resultados</li>
                                <li>⌨️ Navegación accesible por teclado</li>
                            </ul>
                        </div>
                        <p>Haz clic en <strong>"Manual"</strong> para acceder a la documentación docente completa.</p>
                        <p style="color: #059669;">📖 <em>Tu mejor recurso para dominar todas las herramientas.</em></p>
                    `,
                    target: '#nav-manual',
                    position: 'center',
                    showSection: null
                },
                {
                    title: 'Ventana LSV - Inclusión Auditiva 🧏',
                    content: `
                        <h4>🎯 Botón Especial en el Encabezado Superior</h4>
                        <p>Esta funcionalidad incluye soporte audiovisual para ti y tus estudiantes:</p>
                        <div style="background: rgba(255, 193, 7, 0.1); padding: 10px; border-radius: 6px; margin: 10px 0;">
                            <strong>Sistema disponible:</strong>
                            <ul style="margin: 5px 0;">
                                <li>📽️ Videos en Lenguaje de Señas Venezolanas</li>
                                <li>🧏 Contenido especialmente para estudiantes sordos</li>
                                <li>🎨 Elementos visuales destacados</li>
                                <li>🔊 Indicadores no auditivos</li>
                            </ul>
                        </div>
                        <p>Busca el botón en la esquina superior derecha del sistema.</p>
                        <p style="color: #059669;">🧏 <em>Apoya la inclusión auditiva en tu aula virtual.</em></p>
                    `,
                    target: '#btn-toggle-lsv',
                    position: 'center',
                    showSection: null
                }
            );
        }

        // Reemplazar placeholder {{totalPages}} si existe
        baseSteps.forEach(step => {
            if (step.content.includes('{{totalPages}}')) {
                step.content = step.content.replace('{{totalPages}}', '18');
            }
        });

        return baseSteps;
    }

    // Funciones de tour
    function initTour() {
        tourRol = detectarRol();
        tourSteps = getTourSteps(tourRol);

        // Manejadores de eventos del modal de bienvenida
        skipTourBtn?.addEventListener('click', skipTour);
        startTourBtn?.addEventListener('click', startTour);
        closeTourBtn?.addEventListener('click', closeTour);

        // Manejadores del tour
        prevTourStepBtn?.addEventListener('click', prevStep);
        nextTourStepBtn?.addEventListener('click', nextStep);
        finishTourBtn?.addEventListener('click', finishTour);
    }

    function skipTour() {
        if (welcomeModal) welcomeModal.style.display = 'none';
        // Marcar como completado en BD (primera_vez = 0)
        updatePrimeraVez(0);
    }

    function startTour() {
        if (welcomeModal) welcomeModal.style.display = 'none';
        if (tourModal) tourModal.style.display = 'flex';
        currentStep = 0;
        showTourStep();
    }

    function closeTour() {
        if (tourModal) tourModal.style.display = 'none';
        tourOverlay.style.display = 'none';
        // No marcar como completado si cierran manualmente
    }

    function showTourStep() {
        if (currentStep < 0 || currentStep >= tourSteps.length) return;

        const step = tourSteps[currentStep];

        // Si hay redirect, redirigir y continuar tour en la nueva página
        if (step.redirectTo) {
            // Guardar progreso del tour antes de redirigir
            localStorage.setItem('tourProgress', JSON.stringify({
                currentStep: currentStep,
                tourRol: tourRol
            }));

            // Redirigir
            window.location.href = 'index.php?page=' + step.redirectTo;
            return;
        }

        // Actualizar título y contenido
        tourTitle.textContent = step.title;
        tourContent.innerHTML = step.content;

        // Mostrar sección si necesario
        if (step.showSection) {
            setActive(step.showSection);
        }

        // Highlight del elemento objetivo
        if (step.target) {
            setTimeout(() => {
                highlightElement(step.target, step.position);
            }, 500);
        } else {
            tourOverlay.style.display = 'none';
        }

        // Actualizar controles de navegación
        updateNavigationButtons();

        // Actualizar indicador de progreso
        updateProgressIndicator();
    }

    function highlightElement(selector, position) {
        // Clear any existing animation from previous elements
        const previousElement = document.querySelector('.tour-highlight-glow');
        if (previousElement) {
            previousElement.classList.remove('tour-highlight-glow');
        }

        // Clear any existing clone
        tourHighlight.innerHTML = '';

        const element = document.querySelector(selector);
        if (!element) {
            tourOverlay.style.display = 'none';
            return;
        }

        const rect = element.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

        tourHighlight.style.top = (rect.top + scrollTop - 5) + 'px';
        tourHighlight.style.left = (rect.left + scrollLeft - 5) + 'px';
        tourHighlight.style.width = (rect.width + 10) + 'px';
        tourHighlight.style.height = (rect.height + 10) + 'px';

        // Ajustar posición del modal
        adjustModalPosition(position, rect);

        // Create a clone of the element and place it above the modal
        const clone = element.cloneNode(true);
        clone.style.position = 'absolute';
        clone.style.left = '5px';
        clone.style.top = '5px';
        clone.style.zIndex = '10002';
        clone.style.pointerEvents = 'none';
        clone.classList.add('tour-cloned-highlight');
        clone.classList.add('tour-highlight-glow');
        tourHighlight.appendChild(clone);

        tourOverlay.style.display = 'block';

        // Add glow animation to the original element
        element.classList.add('tour-highlight-glow');

        // Remove the animation class after animation completes (1.5s * 2 = 3s total)
        setTimeout(() => {
            element.classList.remove('tour-highlight-glow');
            const cloned = document.querySelector('.tour-cloned-highlight');
            if (cloned) {
                cloned.remove();
            }
        }, 3000);
    }

    function adjustModalPosition(position, targetRect) {
        // Para simplificar, mantendremos los modales centrados
        // Las posiciones dinámicas requieren un contenedor con clase modal-content
        // Por ahora, todos los modales se muestran centrados
        // Podríamos restaurar esto si cambiamos las clases
    }

    function updateNavigationButtons() {
        const isFirstStep = currentStep === 0;
        const isLastStep = currentStep === tourSteps.length - 1;

        prevTourStepBtn.style.display = isFirstStep ? 'none' : 'inline-block';
        nextTourStepBtn.style.display = isLastStep ? 'none' : 'inline-block';
        finishTourBtn.style.display = isLastStep ? 'inline-block' : 'none';
    }

    function updateProgressIndicator() {
        const progressContainer = tourProgress.querySelector('div');
        progressContainer.innerHTML = '';

        tourSteps.forEach((_, index) => {
            const dot = document.createElement('span');
            dot.className = `progress-dot ${index === currentStep ? 'active' : ''}`;
            dot.style.cssText = `
                width: 12px;
                height: 12px;
                border-radius: 50%;
                background: ${index === currentStep ? '#059669' : '#e5e7eb'};
                display: inline-block;
                margin: 0 3px;
                cursor: pointer;
            `;
            progressContainer.appendChild(dot);
        });
    }

    function nextStep() {
        if (currentStep < tourSteps.length - 1) {
            currentStep++;
            showTourStep();
        }
    }

    function prevStep() {
        if (currentStep > 0) {
            currentStep--;
            showTourStep();
        }
    }

    async function finishTour() {
        if (tourModal) tourModal.style.display = 'none';
        tourOverlay.style.display = 'none';

        // Mostrar mensaje de éxito
        mostrarMensajeExito();

        // Marcar primera_vez como completado
        await updatePrimeraVez(0);
    }

    function mostrarMensajeExito() {
        // Crear modal temporal de éxito
        const successModal = document.createElement('div');
        successModal.className = 'modal';
        successModal.innerHTML = `
            <div class="modal-content" style="max-width: 400px;">
                <div class="modal-body" style="text-align: center;">
                    <h3>🎉 ¡Tour completado!</h3>
                    <p>Ahora conoces todas las funciones principales.</p>
                    <p><strong>¿Listo para comenzar tu aprendizaje?</strong></p>
                    <button class="btn btn-primary" onclick="this.closest('.modal').remove()">¡Vamos! 🚀</button>
                </div>
            </div>
        `;
        document.body.appendChild(successModal);
        setTimeout(() => successModal.remove(), 5000);
    }

    async function updatePrimeraVez(estado) {
        try {
            const response = await fetch('index.php?action=update_primera_vez', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ primera_vez: estado })
            });

            if (!response.ok) {
                console.warn('No se pudo actualizar primera_vez en BD');
            }
        } catch (error) {
            console.warn('Error al actualizar primera_vez:', error);
        }
    }

    // Función helper para cambiar secciones (similar a la de home.js)
    function setActive(section) {
        const target = document.getElementById('section-' + section) || document.getElementById('section-inicio');

        // Ocultar todas las secciones
        document.querySelectorAll('main.content > section').forEach(s => {
            s.classList.add('hidden');
        });

        // Mostrar la sección objetivo
        target?.classList.remove('hidden');

        // Scroll to target
        target?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Inicializar cuando DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initTour();
    });

    // Exponer funciones globalmente si es necesario
    window.startTour = startTour;
    window.closeTour = closeTour;

})();
