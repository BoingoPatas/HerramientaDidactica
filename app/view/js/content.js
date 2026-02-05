(function() {
    // Objeto simulado, solo se mantiene para evitar errores si otras partes del código acceden a window.ContentPage.getProgress,
    // pero la verdad es cargado desde el servidor.
    const unitProgress = {
        'variables': 0,
        'operadores': 0,
        'condicionales': 0,
        'bucles': 0
    };

    /**
     * @NEW: Función para actualizar los íconos de estado de los ejercicios (🔒 -> ✅).
     * @param {HTMLElement} unitCard La tarjeta contenedora de la unidad.
     * @param {string[]} completedExercises Array de los números de ejercicio completados (ej: ["1", "2"]).
     */
    function updateExerciseStatus(unitCard, completedExercises) {
        const exerciseItems = unitCard.querySelectorAll('.exercise-item');
        
        // Usamos un Set para una búsqueda más rápida de ejercicios completados
        const completedSet = new Set(completedExercises.map(String));

        exerciseItems.forEach(item => {
            // Extraer el número del ejercicio de la URL (ejercicio=1 -> '1')
            const urlParams = new URLSearchParams(item.href.split('?')[1]);
            const exerciseNum = urlParams.get('exercise');
            
            const statusSpan = item.querySelector('.exercise-status');

            if (statusSpan && completedSet.has(exerciseNum)) {
                // Ejercicio completado
                statusSpan.textContent = '✅'; 
                item.classList.add('completed');
            } else if (statusSpan) {
                // Ejercicio pendiente
                statusSpan.textContent = '🔒';
                item.classList.remove('completed');
            }
        });
    }

    /**
     * @NEW: Función principal para renderizar el progreso visual.
     * Reemplaza la lógica anterior de updateProgressBars.
     * @param {string} unitKey Clave de la unidad (ej: 'variables').
     * @param {object} progressInfo Objeto con { percentage: number, completed_exercises: string[] }.
     */
    function renderProgressVisuals(unitKey, progressInfo) {
        const unitCard = document.querySelector(`.unit-card[data-unit="${unitKey}"]`);
        
        if (unitCard && progressInfo && typeof progressInfo.percentage === 'number') {
            const percentage = progressInfo.percentage;
            
            // 1. Actualizar la barra de progreso (progress-fill)
            const fillElement = unitCard.querySelector('.progress-fill');
            const textElement = unitCard.querySelector('.progress-text');

            if (fillElement && textElement) {
                fillElement.style.width = `${percentage}%`;
                // Asegura visibilidad si el progreso es pequeño pero > 0
                fillElement.style.minWidth = (percentage > 0 && percentage < 5) ? '5%' : '0'; 
                textElement.textContent = `${percentage}% completado`;
            }
            
            // 2. Actualizar el estado de los ejercicios (iconos)
            updateExerciseStatus(unitCard, progressInfo.completed_exercises || []);
        }
    }
    
    // Antiguo updateProgressBars eliminado ya que usaba data simulada.

    // Manejar clics en ejercicios
    /** Maneja los clics en los elementos de ejercicios para animación y registro. */
    function handleExerciseClicks() {
        const exerciseItems = document.querySelectorAll('.exercise-item');
        
        exerciseItems.forEach(item => {
            item.addEventListener('click', function(e) {
                this.style.transform = 'translateX(8px)';
                setTimeout(() => {
                    this.style.transform = 'translateX(4px)';
                }, 150);
                
                const unit = this.closest('[data-unit]')?.getAttribute('data-unit');
                const exerciseTitle = this.querySelector('.exercise-title')?.textContent;
                
                if (unit && exerciseTitle) {
                    logAction('ejercicio_acceso', `${unit} - ${exerciseTitle}`);
                }
            });
        });
    }

    // Animaciones de entrada para las tarjetas
    /** Anima la entrada de las tarjetas de unidades al cargar la página. */
    function animateCards() {
        const cards = document.querySelectorAll('.unit-card');
        
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    // Función para registrar acciones (reutilizada del home.js)
    /** Registra acciones del usuario en el servidor de manera asíncrona. */
    async function logAction(action, detail = '') {
        try {
            const metaCsrf = document.querySelector('meta[name="csrf-token"]');
            const csrf = metaCsrf ? metaCsrf.getAttribute('content') : '';
            
            const logUrl = (window.APP_ROUTES && window.APP_ROUTES.action_log) ? window.APP_ROUTES.action_log : 'index.php?action=log';
            const res = await fetch(logUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, detail, csrf_token: csrf })
            });
            
            const data = await res.json();
            if (!data.success) {
                console.warn('Log fallo:', data.error || data);
            }
        } catch (e) {
            console.warn('Error enviando log:', e);
        }
    }

    // Efectos hover mejorados
    /** Añade efectos de hover mejorados a las tarjetas de unidades. */
    function enhanceHoverEffects() {
        const unitCards = document.querySelectorAll('.unit-card');
        
        unitCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    }

    /**
     * @MODIFIED: Carga el progreso desde el servidor y llama a la función de renderizado.
     * Ahora espera un objeto de progreso completo (percentage + completed_exercises).
     */
    async function loadProgressFromServer() {
        try {
            // Asumiendo que el endpoint index.php?action=progress devuelve la estructura necesaria
            const progressUrl = (window.APP_ROUTES && window.APP_ROUTES.action_progress) ? window.APP_ROUTES.action_progress : 'index.php?action=progress';
            const res = await fetch(progressUrl);
            const data = await res.json();

            if (data && data.success && data.progress && typeof data.progress === 'object') {
                const serverProgress = data.progress;
                
                // Iterar sobre las unidades y renderizar
                for (const unitKey in serverProgress) {
                    if (serverProgress.hasOwnProperty(unitKey)) {
                        const progressInfo = serverProgress[unitKey];

                        // Actualizar el objeto de simulación (opcional, para compatibilidad)
                        if (unitProgress.hasOwnProperty(unitKey)) {
                            unitProgress[unitKey] = parseInt(progressInfo.percentage, 10) || 0;
                        }

                        // Llamada clave a la nueva función de renderizado
                        renderProgressVisuals(unitKey, progressInfo);
                    }
                }
            }
        } catch (e) {
            console.warn('Error cargando progreso desde servidor:', e);
        }
    }
    
    // Inicialización
    document.addEventListener('DOMContentLoaded', function() {
        // updateProgressBars(); <-- Removida la llamada a la función obsoleta
        handleExerciseClicks();
        animateCards();
        enhanceHoverEffects();
        loadProgressFromServer(); // <-- Carga el progreso real
        
        logAction('contenido_acceso', 'Acceso a página de contenido educativo');
    });

    // Guardar progreso (no se usa directamente aquí, lo hace exercise.js al completar)
    /** Guarda el progreso en el almacenamiento local (función obsoleta). */
    function saveProgressToStorage() {
        // Obsoleto: persistimos en servidor
    }

    // Exponer funciones globalmente si es necesario
    window.ContentPage = {
        // Mantiene la interfaz de updateProgress para posible compatibilidad si se usa externamente
        updateProgress: function(unit, progress) {
            // Este método ahora DEBERÍA llamar a loadProgressFromServer para obtener el estado completo
            console.warn("Usando ContentPage.updateProgress. Se recomienda usar ContentPage.reloadFromServer()");
            loadProgressFromServer(); 
        },
        
        getProgress: function(unit) {
            // Retorna el valor del objeto de simulación (que fue actualizado por loadProgressFromServer)
            return unitProgress[unit] || 0;
        }
        ,
        // Forzar recarga desde servidor
        reloadFromServer: function() {
            return loadProgressFromServer();
        }
    };
})();
