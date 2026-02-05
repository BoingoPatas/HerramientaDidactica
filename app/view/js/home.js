(function() {
    // Código de Drag LSV (se mantiene)...

    // ----------------------------------------------------
    // INICIO: Lógica de Navegación y Carga de Secciones
    // ----------------------------------------------------
    
    // Selectores (resueltos después de DOMContentLoaded para evitar ejecuciones tempranas)
    let internalLinks = [];
    let sections = [];
    const byId = id => document.getElementById('section-' + id);
    
    // Variables de control
    window.manageJSLoading = false; // Controla la carga dinámica del script
    let isDocentesSectionInitialized = false; // Controla la inicialización del script

    function updateHeaderTitle(section) {
        const welcomeDiv = document.querySelector('.welcome');
        if (!welcomeDiv) return;

        let title;
        switch (section) {
            case 'inicio':
                title = "🏠 ¡Hola! Revisa tu progreso y comienza a aprender 📊";
                break;
            case 'contenido':
                title = "📚 Lee la teoría antes de practicar! 🤓";
                break;
            case 'evaluaciones':
                title = "📝 Demuestra lo que has aprendido en la evaluación! 🎓";
                break;
            case 'secciones':
                title = "📊 Revise las secciones y estadísticas 📈";
                break;
            case 'usuarios':
                title = "Lista de Usuarios asignados a su sección 👨‍🏫";
                break;
            case 'docentes':
                title = "Lista de Docentes asignados a la institución 🏫";
                break;
            case 'manual':
                title = "📖 Lee el manual de uso paso a paso 🤓";
                break;
            case 'reportes':
                title = "Reportes 📋 - Historial de acciones del sistema";
                break;
            default:
                title = "🏠 ¡Hola! Revisa tu progreso y comienza a aprender 📊";
        }

        welcomeDiv.textContent = title;
    }

    function setActive(section) {
        // Mostrar la sección solicitada en el home; no redirigir fuera del home
        // (Eliminar redirección a index.php?page=evaluation para evitar alternancia inesperada).
        const target = byId(section) || byId('inicio');
        sections.forEach(s => s.classList.toggle('hidden', s !== target));
        internalLinks.forEach(a => a.classList.toggle('active', a.getAttribute('data-section') === (target?.getAttribute('data-section'))));
        updateHeaderTitle(section);
    }
    
    let navigationInProgress = false;
    
    function handleHash() {
        if (navigationInProgress) return;
        const hash = (location.hash || '#inicio').replace('#','');
        setActive(hash);
        updateSidebarActive(hash);
    }

    function updateSidebarActive(hash) {
        // Remove active from all sidebar links
        document.querySelectorAll('aside.sidebar nav a').forEach(a => a.classList.remove('active'));

        // Add active to corresponding sidebar link
        const sidebarMap = {
            'inicio': 'nav-inicio',
            'contenido': 'nav-content',
            'evaluaciones': 'nav-evaluations',
            'manual': 'nav-manual',
            'configuracion': 'nav-settings',
            'usuarios': 'nav-users',
            'docentes': 'nav-docentes',
            'secciones': 'nav-secciones'
        };

        const sidebarId = sidebarMap[hash];
        if (sidebarId) {
            const sidebarLink = document.getElementById(sidebarId);
            if (sidebarLink) {
                sidebarLink.classList.add('active');
            }
        }
    }
    
    // Manejo de clicks en enlaces internos: se liga tras DOMContentLoaded
    function bindInternalLinks() {
        internalLinks = Array.from(document.querySelectorAll('nav.menu a[data-section]'));
        internalLinks.forEach(a => {
            // evitar múltiples bindings conservando referencia
            try { if (a._hd_click) a.removeEventListener('click', a._hd_click); } catch (e) {}
            a._hd_click = function(e) {
                e.preventDefault();
                const sec = a.getAttribute('data-section');
                setActive(sec);
                // Actualizar URL sin recargar página
                window.history.pushState(null, '', '#' + sec);
            };
            a.addEventListener('click', a._hd_click);
        });
    }
    
    // Manejar enlace de contenido (se mantiene)
    const contentLink = document.getElementById('content-link');
    if (contentLink) {
        contentLink.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            navigationInProgress = true;
                    setTimeout(() => {
                const url = this.getAttribute('href');
                try {
                    window.location.replace(url);
                } catch (e) {
                    window.location.href = url;
                }
            }, 50); 
            return false;
        }, true);
    }

    window.addEventListener('hashchange', handleHash);



    // Re-evaluar y ligar selectores después de que el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        try {
            // Re-resolver secciones y enlaces
            sections = Array.from(document.querySelectorAll('main.content > section'));
            bindInternalLinks();

            // Forzar visibilidad de topbar y sidebar en caso de que alguna lógica previa las haya ocultado
            const topbar = document.querySelector('.topbar');
            const sidebar = document.querySelector('.sidebar');
            if (topbar && topbar.classList.contains('hidden')) {
                topbar.classList.remove('hidden');
                console.warn('home.js: topbar recuperado (se removió clase hidden)');
            }
            if (sidebar && sidebar.classList.contains('hidden')) {
                sidebar.classList.remove('hidden');
                console.warn('home.js: sidebar recuperado (se removió clase hidden)');
            }

            // Ejecutar handleHash tras pequeña espera para permitir otras inicializaciones
            setTimeout(handleHash, 20);
        } catch (e) {
            console.error('home.js: Error inicializando navegación tras DOMContentLoaded', e);
        }
    });
    
    // ----------------------------------------------------
    // INICIO: Carga Dinámica Segura de manage.js (Docentes)
    // ----------------------------------------------------

    function initDocentesSection() {
        console.log('🎯 Inicializando sección de docentes...');
        
        // La bandera isDocentesSectionInitialized evita que se cargue manage.js más de una vez.
        // Después de la primera carga, solo se llama a renderDocentes() para refrescar la lista.
        if (isDocentesSectionInitialized) {
            console.log('ℹ️ Seccion de docentes ya inicializada, solo renderizando si la función existe.');
            if (window.renderDocentes && typeof window.renderDocentes === 'function') {
                window.renderDocentes(); 
            }
            return; 
        }

        const docentesList = document.getElementById('docentes-list');
        if (!docentesList) {
            console.error('❌ No se encontró el contenedor docentes-list');
            return;
        }

        // 1. Si manage.js ya está disponible, ejecutar inmediatamente.
        if (window.renderDocentes && typeof window.renderDocentes === 'function') {
            console.log('✅ renderDocentes disponible, ejecutando...');
            window.renderDocentes();
            isDocentesSectionInitialized = true; // Establecer bandera al inicio
            return;
        }
        
        // 2. Si ya está cargando, salir y esperar.
        if (window.manageJSLoading) {
            console.log('⏳ manage.js ya se está cargando, esperando...');
            return;
        }

        console.log('⚠️ manage.js no disponible, cargando dinámicamente...');
        
        // 3. Cargar dinámicamente.
        window.manageJSLoading = true;
        const script = document.createElement('script');
        script.src = 'app/view/js/manage.js?v=1.6';
        
        script.onload = function() {
            console.log('✅ manage.js cargado dinámicamente');
            window.manageJSLoading = false;

            setTimeout(checkRenderDocentes, 100);
        };

        function checkRenderDocentes() {
            if (window.renderDocentes && typeof window.renderDocentes === 'function') {
                console.log('✅ renderDocentes disponible después de carga, ejecutando...');
                window.renderDocentes();
                // Asegurar que los botones de la toolbar tengan listeners
                try {
                    if (window.setupDocenteButtonListeners) {
                        window.setupDocenteButtonListeners();
                    }
                } catch (e) {
                    console.debug('setupDocenteButtonListeners fallo:', e);
                }
                isDocentesSectionInitialized = true; // Establecer bandera después de la carga
            } else {
                console.error('❌ renderDocentes no se expuso después de la carga de manage.js');
                docentesList.innerHTML = '<p class="error">Error: No se pudo cargar la gestión de docentes</p>';
            }
        }
        
        script.onerror = function() {
            console.error('❌ Error cargando manage.js');
            window.manageJSLoading = false;
            docentesList.innerHTML = '<p class="error">Error al cargar el script de gestión</p>';
        };
        
        document.head.appendChild(script);
    }

    // ----------------------------------------------------
    // INICIO: Carga Dinámica para Usuarios (Docente)
    // ----------------------------------------------------
    let isUsuariosSectionInitialized = false;
    function initUsersSection() {
        console.log('🎯 Inicializando sección de usuarios...');
        if (isUsuariosSectionInitialized) {
            if (window.renderUsers && typeof window.renderUsers === 'function') window.renderUsers();
            return;
        }
        const usersList = document.getElementById('users-list');
        if (!usersList) { console.error('❌ No se encontró el contenedor users-list'); return; }

        if (window.renderUsers && typeof window.renderUsers === 'function') {
            console.log('✅ renderUsers disponible, ejecutando...');
            window.renderUsers();
            try { if (window.setupUserButtonListeners) window.setupUserButtonListeners(); } catch(e) { console.debug('setupUserButtonListeners error', e); }
            isUsuariosSectionInitialized = true;
            return;
        }

        if (window.manageJSLoading) { console.log('⏳ manage.js ya se está cargando, esperando...'); return; }
        console.log('⚠️ manage.js no disponible, cargando dinámicamente...');
        window.manageJSLoading = true;
        const script = document.createElement('script'); script.src = 'app/view/js/manage.js?v=1.6';
        script.onload = function() { window.manageJSLoading = false; setTimeout(() => {
            if (window.renderUsers && typeof window.renderUsers === 'function') {
                window.renderUsers(); if (window.setupUserButtonListeners) window.setupUserButtonListeners(); isUsuariosSectionInitialized = true;
            } else { 
                console.error('❌ renderUsers no disponible después de cargar manage.js');
                usersList.innerHTML = '<p class="error">Error: No se pudo cargar la gestión de usuarios</p>'; 
            }
        }, 100); };
        script.onerror = function(){ window.manageJSLoading = false; usersList.innerHTML = '<p class="error">Error al cargar el script de gestión</p>'; };
        document.head.appendChild(script);
    }

    // ----------------------------------------------------
    // INICIO: Carga Dinámica para Secciones (Administrador)
    // ----------------------------------------------------
    let isSectionsSectionInitialized = false;
    function initSectionsSection() {
        console.log('🎯 Inicializando sección de secciones...');
        if (isSectionsSectionInitialized) {
            if (window.renderSections && typeof window.renderSections === 'function') window.renderSections();
            return;
        }
        const sectionsGrid = document.getElementById('sections-grid');
        if (!sectionsGrid) { console.error('❌ No se encontró el contenedor sections-grid'); return; }

        if (window.renderSections && typeof window.renderSections === 'function') {
            console.log('✅ renderSections disponible, ejecutando...');
            window.renderSections();
            isSectionsSectionInitialized = true;
            return;
        }

        if (window.manageJSLoading) { console.log('⏳ manage.js ya se está cargando, esperando...'); return; }
        console.log('⚠️ manage.js no disponible, cargando dinámicamente...');
        window.manageJSLoading = true;
        const script = document.createElement('script'); script.src = 'app/view/js/manage.js?v=1.6';
        script.onload = function() { window.manageJSLoading = false; setTimeout(() => {
            if (window.renderSections && typeof window.renderSections === 'function') {
                window.renderSections(); isSectionsSectionInitialized = true;
            } else { sectionsGrid.innerHTML = '<div class="card"><p class="error">Error: No se pudo cargar la gestión de secciones</p></div>'; }
        }, 100); };
        script.onerror = function(){ window.manageJSLoading = false; sectionsGrid.innerHTML = '<div class="card"><p class="error">Error al cargar el script de gestión</p></div>'; };
        document.head.appendChild(script);
    }

    // ----------------------------------------------------
    // INICIO: Observador para detección de visibilidad (Único disparador de initDocentesSection)
    // ----------------------------------------------------

    const docentesSection = document.getElementById('section-docentes');
    if (docentesSection) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const isVisible = !docentesSection.classList.contains('hidden');
                    
                    if (isVisible) {
                        console.log('👀 Sección docentes se hizo visible - iniciando chequeo...');
                        // La llamada se hace sin timeout, confiando en la lógica de manejo de estado interno de initDocentesSection
                        initDocentesSection();
                    }
                }
            });
        });
        
        observer.observe(docentesSection, { 
            attributes: true, 
            attributeFilter: ['class']
        });
    }

    const usuariosSection = document.getElementById('section-usuarios');
    if (usuariosSection) {
        const observerU = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const isVisible = !usuariosSection.classList.contains('hidden');
                    if (isVisible) {
                        console.log('👀 Sección usuarios se hizo visible - iniciando chequeo...');
                        initUsersSection();
                    }
                }
            });
        });
        observerU.observe(usuariosSection, { attributes: true, attributeFilter: ['class'] });
    }

    const seccionesSection = document.getElementById('section-secciones');
    if (seccionesSection) {
        const observerS = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const isVisible = !seccionesSection.classList.contains('hidden');
                    if (isVisible) {
                        console.log('👀 Sección secciones se hizo visible - iniciando chequeo...');
                        initSectionsSection();
                    }
                }
            });
        });
        observerS.observe(seccionesSection, { attributes: true, attributeFilter: ['class'] });
    }

    // Manual paginación expandido con guía detallada
    const pages = [
        '<h3>Bienvenido a la Herramienta Didáctica con Enfoque Inclusivo 👋</h3>' +
        '<p>Este sistema está diseñado para aprender programación en C con un enfoque inclusivo para personas con discapacidad auditiva. Incluye soporte visual, Lengua de Señas Venezolana (LSV) y navegación accesible.</p>' +
        '<div class="manual-section"><strong>🎯 Objetivos principales:</strong><br>• Aprendizaje interactivo de programación<br>• Inclusión para personas sordas<br>• Seguimiento personalizado del progreso<br>• Evaluación de conocimientos</div>' +
        '<div class="manual-tip">💡 <strong>Consejo:</strong> Todas las explicaciones incluyen referencias visuales y textos descriptivos para mayor accesibilidad.</div>',

        '<h3>Acceso al Sistema 🔑</h3>' +
        '<p>Para comenzar, inicia sesión con tus credenciales proporcionadas por el docente.</p>' +
        '<div class="manual-visual">' +
        '<strong>Referencia visual:</strong> Pantalla de login con campos para usuario y contraseña, botón azul "Entrar".' +
        '<br><span class="emoji">👤</span> Campo Usuario: Ingresa tu nombre de usuario' +
        '<br><span class="emoji">🔒</span> Campo Contraseña: Ingresa tu contraseña' +
        '<br><span class="emoji">▶️</span> Botón azul: Haz clic para acceder' +
        '</div>' +
        '<div class="manual-section"><strong>🔐 Seguridad:</strong> Tus datos están protegidos. Si olvidas tu contraseña, contacta a tu docente.</div>',

        '<h3>Panel Principal (Inicio) 🏠</h3>' +
        '<p>Después del login, verás el dashboard principal con información resumida de tu progreso.</p>' +
        '<div class="manual-visual">' +
        '<strong>Elementos visibles:</strong>' +
        '<br>• Barra lateral izquierda con menú de navegación' +
        '<br>• Encabezado superior con nombre de usuario y controles' +
        '<br>• Área central con tarjetas de estadísticas' +
        '<br>• Ventana LSV flotante (opcional) en esquina inferior derecha' +
        '</div>' +
        '<div class="manual-section"><strong>📊 Estadísticas mostradas:</strong><br>• Unidades completadas (ej: 2/4)<br>• Última unidad en progreso<br>• Porcentaje total de progreso<br>• Racha de días de actividad</div>',

        '<h3>Menú de Navegación 📋</h3>' +
        '<p>El menú lateral te permite acceder a todas las secciones del sistema.</p>' +
        '<div class="manual-visual">' +
        '<strong>Iconos y secciones:</strong>' +
        '<br><span class="emoji">🏠</span> <strong>Inicio:</strong> Dashboard y estadísticas' +
        '<br><span class="emoji">📚</span> <strong>Contenido:</strong> Lecciones teóricas' +
        '<br><span class="emoji">💻</span> <strong>Prácticas:</strong> Ejercicios interactivos' +
        '<br><span class="emoji">📖</span> <strong>Manual:</strong> Esta guía de uso' +
        '<br><span class="emoji">📝</span> <strong>Evaluaciones:</strong> Tests de conocimiento' +
        '<br><span class="emoji">👥</span> <strong>Usuarios:</strong> Gestión (solo docentes)' +
        '<br><span class="emoji">📊</span> <strong>Secciones:</strong> Estadísticas detalladas' +
        '<br><span class="emoji">🏫</span> <strong>Docentes:</strong> Administración (solo admin)' +
        '</div>' +
        '<div class="manual-tip">💡 Navega haciendo clic en los íconos. Las secciones disponibles dependen de tu rol de usuario.</div>',

        '<h3>Sección Contenido 📚</h3>' +
        '<p>Accede a lecciones teóricas organizadas por unidades y módulos.</p>' +
        '<div class="manual-visual">' +
        '<strong>Interfaz:</strong>' +
        '<br>• Lista de unidades disponibles (botones grandes)' +
        '<br>• Indicadores de progreso por unidad (barras de avance)' +
        '<br>• Enlaces opcionales a prácticas y evaluaciones' +
        '<br>• Navegación por páginas dentro de cada unidad' +
        '</div>' +
        '<div class="manual-section"><strong>🎓 Cómo usar:</strong><br>1. Selecciona una unidad haciendo clic<br>2. Lee el contenido teórico paso a paso<br>3. Observa los ejemplos de código<br>4. Usa los controles anterior/siguiente para navegar<br>5. Realiza las prácticas sugeridas</div>' +
        '<div class="manual-inclusion">🧏 <strong>Inclusión:</strong> Todo el contenido incluye explicaciones visuales y soporte LSV cuando esté disponible.</div>',

        '<h3>Sección Prácticas 💻</h3>' +
        '<p>Ejercicios interactivos para aplicar los conceptos aprendidos.</p>' +
        '<div class="manual-visual">' +
        '<strong>Componentes:</strong>' +
        '<br>• Editor de código integrado (área de texto grande)' +
        '<br>• Botones de control: Ejecutar, Limpiar, Reset' +
        '<br>• Panel de resultados con salida del programa' +
        '<br>• Lista de ejercicios disponibles' +
        '<br>• Indicadores de estado (correcto/incorrecto)' +
        '</div>' +
        '<div class="manual-section"><strong>🚀 Proceso de práctica:</strong><br>1. Selecciona un ejercicio<br>2. Lee las instrucciones detalladamente<br>3. Escribe el código en el editor<br>4. Haz clic en "Ejecutar" para probar<br>5. Revisa los resultados y corrige si es necesario<br>6. Marca como completado cuando esté correcto</div>' +
        '<div class="manual-tip">💡 Los ejercicios se guardan automáticamente. Puedes volver a ellos en cualquier momento.</div>',

        '<h3>Ventana LSV (Lengua de Señas Venezolana) 🧏</h3>' +
        '<p>Herramienta de inclusión que traduce conceptos a lenguaje de señas.</p>' +
        '<div class="manual-visual">' +
        '<strong>Apariencia:</strong>' +
        '<br>• Ventana flotante azul/redondo en esquina inferior derecha' +
        '<br>• Barra de título arrastrable con icono de persona señando' +
        '<br>• Área de video o animaciones de señas' +
        '<br>• Controles: minimizar/maximizar (ícono ▶️/⏸️)' +
        '</div>' +
        '<div class="manual-section"><strong>⚙️ Funcionalidades:</strong><br>• Arrastrar para reposicionar<br>• Minimizar para ocultar temporalmente<br>• Maximizar para ver en pantalla completa<br>• Sincronización con contenido actual<br>• Reproduce señas automáticamente para conceptos clave</div>' +
        '<div class="manual-inclusion">🧏 <strong>Accesibilidad:</strong> Diseñado específicamente para usuarios con discapacidad auditiva. Las señas se activan en momentos clave del aprendizaje.</div>',

        '<h3>Sección Evaluaciones 📝</h3>' +
        '<p>Tests para medir tu comprensión de los conceptos aprendidos.</p>' +
        '<div class="manual-visual">' +
        '<strong>Tipos de evaluación:</strong>' +
        '<br>• Preguntas de opción múltiple (botones A, B, C, D)' +
        '<br>• Preguntas de verdadero/falso (Sí/No)' +
        '<br>• Preguntas de completar código (campos de texto)' +
        '<br>• Problemas de programación (editor integrado)' +
        '</div>' +
        '<div class="manual-section"><strong>📋 Proceso:</strong><br>1. Accede a "Evaluaciones" desde el menú<br>2. Selecciona la evaluación deseada<br>3. Lee cada pregunta atentamente<br>4. Selecciona o escribe tu respuesta<br>5. Navega con "Siguiente" o revisa con "Anterior"<br>6. Envía al finalizar<br>7. Revisa los resultados y retroalimentación</div>',

        '<h3>Roles y Permisos de Usuario 🔐</h3>' +
        '<p>El sistema tiene tres roles principales con diferentes niveles de acceso.</p>' +
        '<div class="manual-visual">' +
        '<strong>Roles disponibles:</strong>' +
        '<br><span class="emoji">🎓</span> <strong>Usuario Estudiante:</strong> Acceso básico de aprendizaje' +
        '<br><span class="emoji">👩‍🏫</span> <strong>Docente:</strong> Gestión de usuarios y contenido adicional' +
        '<br><span class="emoji">⚙️</span> <strong>Administrador:</strong> Control total del sistema' +
        '</div>' +
        '<div class="manual-section"><strong>👥 Permisos por rol:</strong>' +
        '<table style="width:100%; border-collapse:collapse; margin-top:10px;">' +
        '<tr><th style="border:1px solid #ddd; padding:8px;">Función</th><th style="border:1px solid #ddd; padding:8px;">Usuario</th><th style="border:1px solid #ddd; padding:8px;">Docente</th><th style="border:1px solid #ddd; padding:8px;">Admin</th></tr>' +
        '<tr><td style="border:1px solid #ddd; padding:8px;">Ver contenido</td><td style="border:1px solid #ddd; padding:8px;">✅</td><td style="border:1px solid #ddd; padding:8px;">✅</td><td style="border:1px solid #ddd; padding:8px;">✅</td></tr>' +
        '<tr><td style="border:1px solid #ddd; padding:8px;">Hacer prácticas</td><td style="border:1px solid #ddd; padding:8px;">✅</td><td style="border:1px solid #ddd; padding:8px;">✅</td><td style="border:1px solid #ddd; padding:8px;">✅</td></tr>' +
        '<tr><td style="border:1px solid #ddd; padding:8px;">Realizar evaluaciones</td><td style="border:1px solid #ddd; padding:8px;">✅</td><td style="border:1px solid #ddd; padding:8px;">✅</td><td style="border:1px solid #ddd; padding:8px;">✅</td></tr>' +
        '<tr><td style="border:1px solid #ddd; padding:8px;">Gestionar usuarios</td><td style="border:1px solid #ddd; padding:8px;">❌</td><td style="border:1px solid #ddd; padding:8px;">✅</td><td style="border:1px solid #ddd; padding:8px;">✅</td></tr>' +
        '<tr><td style="border:1px solid #ddd; padding:8px;">Crear reportes</td><td style="border:1px solid #ddd; padding:8px;">❌</td><td style="border:1px solid #ddd; padding:8px;">✅</td><td style="border:1px solid #ddd; padding:8px;">✅</td></tr>' +
        '<tr><td style="border:1px solid #ddd; padding:8px;">Gestionar docentes</td><td style="border:1px solid #ddd; padding:8px;">❌</td><td style="border:1px solid #ddd; padding:8px;">❌</td><td style="border:1px solid #ddd; padding:8px;">✅</td></tr>' +
        '</table></div>' +
        '<div class="manual-tip">💡 Tu rol se asigna automáticamente al crear tu cuenta. Contacta al administrador para cambios.</div>',

        '<h3>Gestión de Usuarios (Solo Docente) 👥</h3>' +
        '<p>Los docentes pueden administrar las cuentas de estudiantes.</p>' +
        '<div class="manual-visual">' +
        '<strong>Controles disponibles:</strong>' +
        '<br>• <span class="emoji">➕</span> Añadir usuario: Crear nueva cuenta estudiante' +
        '<br>• <span class="emoji">✏️</span> Modificar usuario: Editar información existente' +
        '<br>• <span class="emoji">🚫</span> Inhabilitar usuario: Desactivar cuenta temporalmente' +
        '<br>• Lista de usuarios activos con detalles' +
        '</div>' +
        '<div class="manual-section"><strong>📝 Proceso de gestión:</strong><br>1. Selecciona un usuario de la lista<br>2. Los botones de acción se activan automáticamente<br>3. Para añadir: Completa el formulario con nombre, usuario, email<br>4. Para modificar: Edita los campos necesarios<br>5. Confirma los cambios<br>6. La lista se actualiza automáticamente</div>' +
        '<div class="manual-tip">💡 Los usuarios inhabilitados no pueden acceder hasta ser reactivados.</div>',

        '<h3>Secciones y Estadísticas (Solo Docente) 📊</h3>' +
        '<p>Herramientas para monitorear el progreso de los estudiantes.</p>' +
        '<div class="manual-visual">' +
        '<strong>Tipos de reporte:</strong>' +
        '<br>• Reporte de progreso individual por estudiante' +
        '<br>• Estadísticas generales del curso' +
        '<br>• Resultados de evaluaciones grupales' +
        '<br>• Tiempos de actividad y participación' +
        '</div>' +
        '<div class="manual-section"><strong>📈 Funciones principales:</strong><br>• <strong>Crear reporte:</strong> Generar nuevo análisis<br>• <strong>Visualizar reportes:</strong> Ver reportes existentes<br>• <strong>Inhabilitar reporte:</strong> Ocultar reportes antiguos<br>• Exportar datos para análisis externo</div>' +
        '<div class="manual-tip">💡 Los reportes ayudan a identificar estudiantes que necesitan apoyo adicional.</div>',

        '<h3>Gestión de Docentes (Solo Administrador) 🏫</h3>' +
        '<p>Control total sobre las cuentas de docentes del sistema.</p>' +
        '<div class="manual-visual">' +
        '<strong>Operaciones disponibles:</strong>' +
        '<br>• <span class="emoji">➕</span> Añadir docente: Crear cuenta con permisos de docente' +
        '<br>• <span class="emoji">✏️</span> Modificar docente: Cambiar información del docente' +
        '<br>• <span class="emoji">🚫</span> Inhabilitar docente: Desactivar cuenta de docente' +
        '<br>• Lista completa de docentes activos' +
        '</div>' +
        '<div class="manual-section"><strong>⚙️ Administración avanzada:</strong><br>1. Selecciona docente de la lista<br>2. Usa botones para añadir, modificar o inhabilitar<br>3. Completa formularios con información requerida<br>4. Revisa cambios antes de confirmar<br>5. El sistema actualiza automáticamente las listas</div>',

        '<h3>Accesibilidad e Inclusión 🧏</h3>' +
        '<p>El sistema está diseñado con accesibilidad universal en mente.</p>' +
        '<div class="manual-inclusion">' +
        '<strong>Características inclusivas:</strong>' +
        '<br>• <span class="emoji">👁️</span> <strong>Interfaz visual clara:</strong> Contraste alto, fuentes legibles' +
        '<br>• <span class="emoji">🧏</span> <strong>Soporte LSV:</strong> Lengua de Señas Venezolana integrada' +
        '<br>• <span class="emoji">⌨️</span> <strong>Navegación por teclado:</strong> Todo operable sin mouse' +
        '<br>• <span class="emoji">📱</span> <strong>Responsive:</strong> Funciona en diferentes dispositivos' +
        '<br>• <span class="emoji">🎯</span> <strong>Texto alternativo:</strong> Descripciones para todas las imágenes' +
        '<br>• <span class="emoji">🔊</span> <strong>Señales visuales:</strong> Iconos y colores para indicar estados' +
        '</div>' +
        '<div class="manual-section"><strong>🎨 Elementos visuales:</strong><br>• Emojis descriptivos en toda la interfaz<br>• Colores diferenciados para cada sección<br>• Indicadores visuales de progreso<br>• Diagramas y esquemas explicativos<br>• Retroalimentación visual inmediata</div>',

        '<h3>Solución de Problemas 🔧</h3>' +
        '<p>Guía para resolver los problemas más comunes.</p>' +
        '<div class="manual-section">' +
        '<strong>❌ Problema: No puedo acceder al sistema</strong>' +
        '<br><strong>✅ Solución:</strong> Verifica usuario/contraseña. Si persiste, contacta al docente.' +
        '<br><br><strong>❌ Problema: El código no se ejecuta</strong>' +
        '<br><strong>✅ Solución:</strong> Revisa sintaxis, asegurate de guardar cambios antes de ejecutar.' +
        '<br><br><strong>❌ Problema: Ventana LSV no aparece</strong>' +
        '<br><strong>✅ Solución:</strong> Haz clic en el botón de toggle en el encabezado superior.' +
        '<br><br><strong>❌ Problema: Cambios no se guardan</strong>' +
        '<br><strong>✅ Solución:</strong> Los cambios se guardan automáticamente. Si no ves actualizaciones, refresca la página.' +
        '<br><br><strong>❌ Problema: Contenido no carga</strong>' +
        '<br><strong>✅ Solución:</strong> Verifica conexión a internet. Si persiste, contacta soporte técnico.' +
        '</div>' +
        '<div class="manual-tip">💡 Si encuentras un problema no listado aquí, anota los detalles y reporta a tu docente.</div>',

        '<h3>Consejos para un Aprendizaje Efectivo 📚</h3>' +
        '<p>Mejores prácticas para aprovechar al máximo el sistema.</p>' +
        '<div class="manual-section">' +
        '<strong>🎯 Estrategias de estudio:</strong>' +
        '<br>• Lee el contenido teórico antes de hacer prácticas' +
        '<br>• Practica regularmente, no dejes acumular ejercicios' +
        '<br>• Usa la ventana LSV para conceptos complejos' +
        '<br>• Revisa evaluaciones anteriores para identificar debilidades' +
        '<br>• Participa activamente en todas las secciones disponibles' +
        '<br><br><strong>⏰ Gestión del tiempo:</strong>' +
        '<br>• Dedica tiempo diario consistente al aprendizaje' +
        '<br>• Alterna entre teoría y práctica' +
        '<br>• Haz pausas cortas entre sesiones intensas' +
        '<br>• Revisa tu progreso semanalmente' +
        '<br><br><strong>🤝 Apoyo adicional:</strong>' +
        '<br>• Pregunta dudas a tu docente cuando las tengas' +
        '<br>• Colabora con otros estudiantes en conceptos difíciles' +
        '<br>• Utiliza todos los recursos disponibles (videos, diagramas)' +
        '<br>• Mantén un registro personal de conceptos aprendidos' +
        '</div>',

        '<h3>¡Felicidades! Has completado el Manual 🎉</h3>' +
        '<p>Ahora tienes todo el conocimiento necesario para usar el sistema efectivamente.</p>' +
        '<div class="manual-section">' +
        '<strong>📝 Resumen de lo aprendido:</strong>' +
        '<br>• Navegación por el sistema y sus secciones' +
        '<br>• Uso de todas las herramientas educativas' +
        '<br>• Entendimiento de roles y permisos' +
        '<br>• Características de accesibilidad e inclusión' +
        '<br>• Estrategias para un aprendizaje efectivo' +
        '<br><br><strong>🚀 Próximos pasos:</strong>' +
        '<br>1. Explora el contenido disponible en la sección Contenido' +
        '<br>2. Prueba algunos ejercicios en la sección Prácticas' +
        '<br>3. Realiza tu primera evaluación' +
        '<br>4. Activa la ventana LSV si la necesitas' +
        '<br>5. Revisa regularmente tu progreso en Inicio' +
        '</div>' +
        '<div class="manual-inclusion">🧏 <strong>Recuerda:</strong> Este sistema fue creado pensando en la inclusión. Todas las funciones están diseñadas para ser accesibles a personas con discapacidad auditiva.</div>' +
        '<div class="manual-tip">💡 ¡Disfruta aprendiendo programación de manera inclusiva e interactiva!</div>'
    ];
    let index = 0; 
    const el = document.getElementById('manual-pages'); 
    const prev = document.getElementById('btn-prev'); 
    const next = document.getElementById('btn-next'); 
    const cur = document.getElementById('page-current'); 
    const tot = document.getElementById('page-total');
    function render(){ if(!el) return; el.innerHTML = pages[index]; cur && (cur.textContent = (index+1)+'' ); tot && (tot.textContent = pages.length+'' ); if(prev) prev.disabled = index===0; if(next) next.disabled = index===pages.length-1; }
    prev && prev.addEventListener('click', ()=>{ if(index>0){ index--; render(); } });
    next && next.addEventListener('click', ()=>{ if(index<pages.length-1){ index++; render(); } });
    render();

    // Bitácora: envío de acciones (se mantiene)
    const metaCsrf = document.querySelector('meta[name="csrf-token"]');
    const csrf = metaCsrf ? metaCsrf.getAttribute('content') : '';
    async function logAction(action, detail='') {
        try {
                const logUrl = (window.APP_ROUTES && window.APP_ROUTES.action_log) ? window.APP_ROUTES.action_log : 'index.php?action=log';
                const res = await fetch(logUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action, detail, csrf_token: csrf }) });
            const data = await res.json();
            if (!data.success) console.warn('Log fallo:', data.error || data);
        } catch (e) { console.warn('Error enviando log:', e); }
    }
    // Manejar todos los clics en botones de acción (se mantiene)
    document.body.addEventListener('click', (e) => {
        const btn = e.target.closest('.action-log');
        if (!btn) return;

        const action = btn.getAttribute('data-action');
        const detail = btn.getAttribute('data-detail') || '';

        logAction(action, detail);

        switch(action) {
            case 'contenido_realizar_ejercicio':
                window.location.href = (window.APP_ROUTES && window.APP_ROUTES.content) ? window.APP_ROUTES.content : 'index.php?page=content';
                break;
            case 'contenido_realizar_evaluacion':
                window.location.href = (window.APP_ROUTES && window.APP_ROUTES.evaluation) ? window.APP_ROUTES.evaluation : 'index.php?page=evaluation';
                break;
            case 'config_abrir':
                break;
        }
    });

    // ----------------------------------------------------
    // INICIO: Evento final
    // ----------------------------------------------------
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🏠 home.js completamente cargado');
        
        window.initDocentesSection = initDocentesSection;
    window.initUsersSection = initUsersSection;
    window.initSectionsSection = initSectionsSection;
        
        // ✅ CORRECCIÓN FINAL: Si la URL ya tiene #docentes, no necesitamos una llamada extra a initDocentesSection.
        // El handleHash (que corre a los 20ms) se encarga de cambiar la clase, y el MutationObserver hará la llamada.
        if (window.location.hash === '#docentes') {
            console.log('📍 URL con #docentes detectada - El observador se encargará de inicializar.');
        }
        
            // ----------------------------------------------------
            // LSV: Arrastre y Minimizar
            // ----------------------------------------------------
            try {
                const lsvWindow = document.getElementById('lsv-window');
                const dragHandle = document.getElementById('lsv-drag-handle');
                const btnToggleTop = document.getElementById('btn-toggle-lsv');

                if (lsvWindow) {
                    // Restaurar estado previo (posición y minimizado)
                    try {
                        const pos = JSON.parse(localStorage.getItem('lsvPos') || 'null');
                        if (pos && pos.left && pos.top) {
                            lsvWindow.style.left = pos.left;
                            lsvWindow.style.top = pos.top;
                            lsvWindow.style.right = 'auto';
                            lsvWindow.style.bottom = 'auto';
                        }
                    } catch (e) { /* ignore */ }

                    const savedMin = localStorage.getItem('lsvMinimized');
                    if (savedMin === '1') {
                        lsvWindow.classList.add('minimized');
                        lsvWindow.style.display = 'none';
                    }

                    // Ajustar estado del botón superior si existe
                    if (btnToggleTop) {
                        if (savedMin === '1') {
                            btnToggleTop.classList.add('lsv-hidden');
                            btnToggleTop.setAttribute('aria-pressed', 'false'); // Invertido: false cuando está oculto
                        } else {
                            btnToggleTop.classList.add('active');
                            btnToggleTop.setAttribute('aria-pressed', 'true'); // Invertido: true cuando está activo/visible
                        }
                    }

                    let isDragging = false;
                    let offsetX = 0;
                    let offsetY = 0;

                    function onPointerDown(e) {
                        if (lsvWindow.classList.contains('minimized')) return; // no arrastrar si está minimizada
                        isDragging = true;
                        dragHandle.setPointerCapture && dragHandle.setPointerCapture(e.pointerId);
                        const rect = lsvWindow.getBoundingClientRect();
                        offsetX = e.clientX - rect.left;
                        offsetY = e.clientY - rect.top;
                        lsvWindow.classList.add('dragging');
                    }

                    function onPointerMove(e) {
                        if (!isDragging) return;
                        e.preventDefault();
                        const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
                        const vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);
                        let x = e.clientX - offsetX;
                        let y = e.clientY - offsetY;
                        // Constrain
                        x = Math.min(Math.max(8, x), vw - lsvWindow.offsetWidth - 8);
                        y = Math.min(Math.max(8, y), vh - lsvWindow.offsetHeight - 8);
                        lsvWindow.style.left = x + 'px';
                        lsvWindow.style.top = y + 'px';
                        lsvWindow.style.right = 'auto';
                        lsvWindow.style.bottom = 'auto';
                    }

                    function onPointerUp(e) {
                        if (!isDragging) return;
                        isDragging = false;
                        try { dragHandle.releasePointerCapture && dragHandle.releasePointerCapture(e.pointerId); } catch (e) {}
                        lsvWindow.classList.remove('dragging');
                        // Guardar posición
                        try { localStorage.setItem('lsvPos', JSON.stringify({ left: lsvWindow.style.left, top: lsvWindow.style.top })); } catch (e) {}
                    }

                    if (dragHandle) {
                        dragHandle.style.touchAction = 'none';
                        dragHandle.addEventListener('pointerdown', onPointerDown);
                    }

                    document.addEventListener('pointermove', onPointerMove);
                    document.addEventListener('pointerup', onPointerUp);

                    function toggleLSV(minimize) {
                        if (!lsvWindow) return;
                        const wantMin = typeof minimize === 'boolean' ? minimize : !lsvWindow.classList.contains('minimized');

                        if (wantMin) {
                            // Minimizar: ocultar completamente
                            lsvWindow.style.display = 'none';
                            lsvWindow.classList.add('minimized');
                            try { localStorage.setItem('lsvMinimized', '1'); } catch (e) {}
                            if (btnToggleTop) { btnToggleTop.setAttribute('aria-pressed', 'false'); btnToggleTop.classList.add('lsv-hidden'); btnToggleTop.classList.remove('active'); } // Invertido: false cuando se minimiza
                        } else {
                            // Restaurar: mostrar y llevar a posición inicial
                            lsvWindow.style.display = '';
                            lsvWindow.classList.remove('minimized');
                            // reset position to initial bottom-right
                            lsvWindow.style.right = '20px';
                            lsvWindow.style.bottom = '20px';
                            lsvWindow.style.left = 'auto';
                            lsvWindow.style.top = 'auto';
                            try { localStorage.setItem('lsvMinimized', '0'); localStorage.removeItem('lsvPos'); } catch (e) {}
                            if (btnToggleTop) { btnToggleTop.setAttribute('aria-pressed', 'true'); btnToggleTop.classList.remove('lsv-hidden'); btnToggleTop.classList.add('active'); } // Invertido: true cuando se restaura
                        }
                    }

                    if (btnToggleTop) btnToggleTop.addEventListener('click', function(e){ e.stopPropagation(); const currentlyHidden = lsvWindow.style.display === 'none' || lsvWindow.classList.contains('minimized'); toggleLSV(currentlyHidden ? false : true); });

                    // Hacer click en header cuando está minimizada para restaurar
                    if (dragHandle) {
                        dragHandle.addEventListener('click', function(e){ if (lsvWindow.classList.contains('minimized')) { toggleLSV(false); } });
                    }
                }
            } catch (err) { console.warn('LSV behaviour init error', err); }
    });
})();
