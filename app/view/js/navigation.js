    // navigation.js - navegación central con protección de home
    (function() {
    'use strict';

    class NavigationManager {
        constructor() {
            this.init();
        }

        /** Inicializa el gestor de navegación configurando todos los manejadores de enlaces */
        init() {
            this.handleManualLinks();
            this.handleSectionLinks();
            this.handleExternalLinks();
            this.handleHashNavigation();
            this.adjustSidebarButtonHeights();
        }

        /** Ajusta la altura de los botones del sidebar según la cantidad visible */
        adjustSidebarButtonHeights() {
            const menu = document.querySelector('nav.menu');
            if (!menu) return;

            const buttons = menu.querySelectorAll('a');
            const visibleButtons = Array.from(buttons).filter(button => button.offsetParent !== null);

            // Si hay más de 5 botones visibles, quitar flex: 1 y ajustar padding para que se achiquen
            if (visibleButtons.length > 5) {
                visibleButtons.forEach(button => {
                    button.classList.add('compact-menu-button');
                    button.classList.remove('flex-menu-button');
                });
            } else {
                // Si hay 5 o menos, usar el comportamiento normal
                visibleButtons.forEach(button => {
                    button.classList.add('flex-menu-button');
                    button.classList.remove('compact-menu-button');
                });
            }
        }

        /** Maneja los enlaces manuales redirigiéndolos a la sección manual de la página home */
        handleManualLinks() {
            const manualLinks = document.querySelectorAll('a[href="#manual"]');
            manualLinks.forEach(link => link.addEventListener('click', (e) => {
                e.preventDefault(); this.navigateTo('index.php?page=home#manual');
            }));
        }

        /** Maneja los enlaces de sección en la página home que no tienen href específico */
        handleSectionLinks() {
            const sectionLinks = document.querySelectorAll('a[data-section]');
            sectionLinks.forEach(link => {
                // Solo interceptar enlaces sin href específico de página para no anular enlaces explícitos
                const href = link.getAttribute('href') || '';
                if (!href || href.startsWith('#')) {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const section = link.getAttribute('data-section');
                        this.navigateTo(`index.php?page=home#${section}`);
                    });
                }
            });
        }

        /** Maneja los enlaces externos como el enlace de contenido y registra clicks en enlaces index.php */
        handleExternalLinks() {
            const contentLink = document.getElementById('content-link');
            if (contentLink) {
                contentLink.addEventListener('click', (e) => {
                    e.preventDefault(); this.navigateTo(contentLink.getAttribute('href'));
                });
            }

            // Mantener registro simple para otros enlaces index.php
            const externalLinks = document.querySelectorAll('a[href*="index.php"]');
            externalLinks.forEach(link => link.addEventListener('click', () => {
                console.debug('Navigation: click', link.getAttribute('href'));
            }));
        }

        /** Maneja la navegación por hash configurando la navegación home si corresponde */
        handleHashNavigation() {
            // Solo configurar la navegación home si realmente estamos en home
            const params = new URLSearchParams(location.search || '');
            const pageParam = params.get('page');
            const hasHomeSections = document.querySelectorAll('main.content > section[data-section]').length >= 3;

            if (pageParam === 'home' || hasHomeSections) {
                this.setupHomeNavigation();
            } else {
                console.debug('Navigation: skipping home setup (not home)');
            }
        }

        /** Configura la navegación home con enlaces internos, hashchange y estado activo */
        setupHomeNavigation() {
            const internalLinks = Array.from(document.querySelectorAll('nav.menu a[data-section]'));

                /** Establece la sección activa en la interfaz, ocultando otras y actualizando el menú */
            const setActive = (section) => {
                const target = document.getElementById('section-' + section) || document.getElementById('section-inicio');
                const allSections = Array.from(document.querySelectorAll('main.content > section'));
                allSections.forEach(s => s.classList.toggle('hidden', s !== target));
                internalLinks.forEach(a => a.classList.toggle('active', a.getAttribute('data-section') === section));
            };

            internalLinks.forEach(a => a.addEventListener('click', (e) => {
                e.preventDefault(); const sec = a.getAttribute('data-section'); setActive(sec); window.history.pushState(null, '', '#' + sec);
            }));

            window.addEventListener('hashchange', () => {
                const hash = (location.hash || '#inicio').replace('#',''); setActive(hash);
            });

            setTimeout(() => {
                let hash = (location.hash || '').replace('#','');
                if (!hash) {
                    const search = (location.search || '');
                    const params = new URLSearchParams(search.startsWith('?') ? search : ('?' + search));
                    const page = params.get('page');
                    if (page === 'evaluation' || page === 'evaluacion') hash = 'evaluaciones';
                    else if (page === 'report' || page === 'reportes') hash = 'reportes';
                    else hash = 'inicio';
                }
                setActive(hash);
            }, 20);
        }

        /** Navega a la URL proporcionada con un pequeño retraso */
        navigateTo(url) {
            console.log('Navigation: go', url);
            setTimeout(() => { window.location.href = url; }, 10);
        }
    }

    document.addEventListener('DOMContentLoaded', () => { window.navigationManager = new NavigationManager(); });

})();
