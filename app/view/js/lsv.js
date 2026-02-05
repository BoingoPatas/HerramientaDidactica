// lsv.js - Funcionalidad LSV reutilizable para todas las vistas
(function() {
    'use strict';

    class LSVManager {
        constructor() {
            this.isDragging = false;
            this.offsetX = 0;
            this.offsetY = 0;
            this.init();
        }

        init() {
            this.setupLSVWindow();
            this.setupToggleButton();
            this.restoreState();
        }

        setupLSVWindow() {
            const lsvWindow = document.getElementById('lsv-window');
            const dragHandle = document.getElementById('lsv-drag-handle');

            if (!lsvWindow || !dragHandle) return;

            // Configurar arrastre
            dragHandle.style.touchAction = 'none';
            dragHandle.addEventListener('pointerdown', this.onPointerDown.bind(this));
            document.addEventListener('pointermove', this.onPointerMove.bind(this));
            document.addEventListener('pointerup', this.onPointerUp.bind(this));

            // Click en header para restaurar cuando está minimizada
            dragHandle.addEventListener('click', (e) => {
                if (lsvWindow.classList.contains('minimized')) {
                    this.toggleLSV(false);
                }
            });
        }

        setupToggleButton() {
            const btnToggle = document.getElementById('btn-toggle-lsv');
            if (!btnToggle) return;

            btnToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const lsvWindow = document.getElementById('lsv-window');
                if (!lsvWindow) return;

                const currentlyHidden = lsvWindow.style.display === 'none' || lsvWindow.classList.contains('minimized');
                this.toggleLSV(currentlyHidden ? false : true);
            });
        }

        restoreState() {
            const lsvWindow = document.getElementById('lsv-window');
            const btnToggle = document.getElementById('btn-toggle-lsv');
            
            if (!lsvWindow) return;

            // Restaurar posición
            try {
                const pos = JSON.parse(localStorage.getItem('lsvPos') || 'null');
                if (pos && pos.left && pos.top) {
                    lsvWindow.style.left = pos.left;
                    lsvWindow.style.top = pos.top;
                    lsvWindow.style.right = 'auto';
                    lsvWindow.style.bottom = 'auto';
                }
            } catch (e) { /* ignore */ }

            // Restaurar estado minimizado
            const savedMin = localStorage.getItem('lsvMinimized');
            if (savedMin === '1') {
                lsvWindow.classList.add('minimized');
                lsvWindow.style.display = 'none';
            }

            // Ajustar estado del botón
            if (btnToggle) {
                if (savedMin === '1') {
                    btnToggle.classList.add('lsv-hidden');
                    btnToggle.setAttribute('aria-pressed', 'false');
                } else {
                    btnToggle.classList.add('active');
                    btnToggle.setAttribute('aria-pressed', 'true');
                }
            }
        }

        onPointerDown(e) {
            const lsvWindow = document.getElementById('lsv-window');
            if (!lsvWindow || lsvWindow.classList.contains('minimized')) return;

            this.isDragging = true;
            const dragHandle = document.getElementById('lsv-drag-handle');
            dragHandle.setPointerCapture && dragHandle.setPointerCapture(e.pointerId);
            
            const rect = lsvWindow.getBoundingClientRect();
            this.offsetX = e.clientX - rect.left;
            this.offsetY = e.clientY - rect.top;
            lsvWindow.classList.add('dragging');
        }

        onPointerMove(e) {
            if (!this.isDragging) return;
            e.preventDefault();

            const lsvWindow = document.getElementById('lsv-window');
            if (!lsvWindow) return;

            const vw = window.innerWidth;
            const vh = window.innerHeight;

            let x = e.clientX - this.offsetX;
            let y = e.clientY - this.offsetY;

            // Constrain to viewport
            x = Math.min(Math.max(8, x), vw - lsvWindow.offsetWidth - 8);
            y = Math.min(Math.max(8, y), vh - lsvWindow.offsetHeight - 8);

            lsvWindow.style.left = x + 'px';
            lsvWindow.style.top = y + 'px';
            lsvWindow.style.right = 'auto';
            lsvWindow.style.bottom = 'auto';
        }

        onPointerUp(e) {
            if (!this.isDragging) return;
            this.isDragging = false;

            const dragHandle = document.getElementById('lsv-drag-handle');
            const lsvWindow = document.getElementById('lsv-window');
            
            try { 
                dragHandle.releasePointerCapture && dragHandle.releasePointerCapture(e.pointerId); 
            } catch (e) {}
            
            lsvWindow.classList.remove('dragging');
            
            // Guardar posición
            try { 
                localStorage.setItem('lsvPos', JSON.stringify({ 
                    left: lsvWindow.style.left, 
                    top: lsvWindow.style.top 
                })); 
            } catch (e) {}
        }

        toggleLSV(minimize) {
            const lsvWindow = document.getElementById('lsv-window');
            const btnToggle = document.getElementById('btn-toggle-lsv');
            
            if (!lsvWindow) return;

            const wantMin = typeof minimize === 'boolean' ? minimize : !lsvWindow.classList.contains('minimized');

            if (wantMin) {
                // Minimizar: ocultar completamente
                lsvWindow.style.display = 'none';
                lsvWindow.classList.add('minimized');
                try { localStorage.setItem('lsvMinimized', '1'); } catch (e) {}
                
                if (btnToggle) { 
                    btnToggle.setAttribute('aria-pressed', 'false'); 
                    btnToggle.classList.add('lsv-hidden'); 
                    btnToggle.classList.remove('active'); 
                }
            } else {
                // Restaurar: mostrar y llevar a posición inicial
                lsvWindow.style.display = '';
                lsvWindow.classList.remove('minimized');
                // Reset position to initial bottom-right
                lsvWindow.style.right = '20px';
                lsvWindow.style.bottom = '20px';
                lsvWindow.style.left = 'auto';
                lsvWindow.style.top = 'auto';
                
                try { 
                    localStorage.setItem('lsvMinimized', '0'); 
                    localStorage.removeItem('lsvPos'); 
                } catch (e) {}
                
                if (btnToggle) { 
                    btnToggle.setAttribute('aria-pressed', 'true'); 
                    btnToggle.classList.remove('lsv-hidden'); 
                    btnToggle.classList.add('active'); 
                }
            }
        }
    }

    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', () => {
        window.lsvManager = new LSVManager();
    });

})();
