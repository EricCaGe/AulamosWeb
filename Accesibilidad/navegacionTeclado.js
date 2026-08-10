// ========================================== */
// NAVEGACIÓN POR TECLADO - FÍSICO           */
// ========================================== */

class NavegacionTeclado {
    constructor() {
        this.activado = false;
        this.elementos = [];
        this.indiceActual = 0;
        this.inicializar();
    }

    inicializar() {
        const clave = obtenerClave('navegacion_teclado');
        this.activado = localStorage.getItem(clave) === 'true';
        
        if (this.activado) {
            this.activar();
        }
    }

    activar() {
        this.activado = true;
        document.body.classList.add('navegacion-teclado');
        document.addEventListener('keydown', this.manejarTecla.bind(this));
        console.log('⌨️ Navegación por teclado activada');
    }

    desactivar() {
        this.activado = false;
        document.body.classList.remove('navegacion-teclado');
        document.removeEventListener('keydown', this.manejarTecla.bind(this));
        console.log('⌨️ Navegación por teclado desactivada');
    }

    toggle() {
        if (this.activado) {
            this.desactivar();
        } else {
            this.activar();
        }
        return this.activado;
    }

    obtenerElementos() {
        return document.querySelectorAll(
            'button, a[href], input, select, textarea, ' +
            '[tabindex]:not([tabindex="-1"]), ' +
            '.menu-item, .quick-btn, .btn, .card, ' +
            '.stat-card, .modulo-item, .actividad-item'
        );
    }

    manejarTecla(e) {
        if (!this.activado) return;

        // Ignorar si el usuario está escribiendo en un input
        if (document.activeElement && 
            (document.activeElement.tagName === 'INPUT' || 
             document.activeElement.tagName === 'TEXTAREA' || 
             document.activeElement.tagName === 'SELECT')) {
            return;
        }

        this.elementos = this.obtenerElementos();
        const elementos = this.elementos;
        const total = elementos.length;

        if (total === 0) return;

        // Encontrar el índice del elemento actualmente enfocado
        let currentIndex = -1;
        if (document.activeElement) {
            for (let i = 0; i < total; i++) {
                if (elementos[i] === document.activeElement) {
                    currentIndex = i;
                    break;
                }
            }
        }

        switch(e.key) {
            case 'Tab':
                // El Tab funciona por defecto
                break;

            case 'ArrowDown':
            case 'ArrowRight':
                e.preventDefault();
                if (currentIndex < total - 1) {
                    elementos[currentIndex + 1].focus();
                    this.anunciarElemento(elementos[currentIndex + 1]);
                } else if (total > 0) {
                    elementos[0].focus();
                    this.anunciarElemento(elementos[0]);
                }
                break;

            case 'ArrowUp':
            case 'ArrowLeft':
                e.preventDefault();
                if (currentIndex > 0) {
                    elementos[currentIndex - 1].focus();
                    this.anunciarElemento(elementos[currentIndex - 1]);
                } else if (total > 0) {
                    elementos[total - 1].focus();
                    this.anunciarElemento(elementos[total - 1]);
                }
                break;

            case 'Enter':
            case ' ':
                if (document.activeElement && 
                    (document.activeElement.tagName === 'BUTTON' || 
                     document.activeElement.tagName === 'A' ||
                     document.activeElement.classList.contains('menu-item') ||
                     document.activeElement.classList.contains('btn'))) {
                    e.preventDefault();
                    document.activeElement.click();
                }
                break;

            case 'Home':
                e.preventDefault();
                if (total > 0) {
                    elementos[0].focus();
                    this.anunciarElemento(elementos[0]);
                }
                break;

            case 'End':
                e.preventDefault();
                if (total > 0) {
                    elementos[total - 1].focus();
                    this.anunciarElemento(elementos[total - 1]);
                }
                break;

            case 'Escape':
                // Cerrar modales o salir de la navegación
                const modales = document.querySelectorAll('.modal-contraste-overlay:not(.modal-contraste-hidden)');
                if (modales.length > 0) {
                    modales.forEach(modal => {
                        const cerrar = modal.querySelector('.btn-cerrar-modal');
                        if (cerrar) cerrar.click();
                    });
                }
                break;
        }
    }

    anunciarElemento(el) {
        if (!el) return;
        
        let texto = '';
        if (el.tagName === 'BUTTON' || el.tagName === 'A') {
            texto = el.textContent.trim() || el.getAttribute('aria-label') || '';
        } else if (el.classList.contains('menu-item')) {
            texto = el.textContent.trim();
        } else if (el.classList.contains('stat-card')) {
            const number = el.querySelector('.stat-number');
            const label = el.querySelector('.stat-label');
            if (number && label) {
                texto = number.textContent.trim() + ' ' + label.textContent.trim();
            }
        } else if (el.tagName === 'INPUT') {
            const label = document.querySelector(`label[for="${el.id}"]`);
            texto = label ? label.textContent.trim() : el.placeholder || el.type;
        } else {
            texto = el.textContent.trim().substring(0, 100);
        }

        if (texto && window.lector && window.lector.activado) {
            window.lector.leer(texto);
        }
    }
}

// ========================================== */
// INICIALIZAR NAVEGACIÓN POR TECLADO        */
// ========================================== */

let navegacionTeclado = null;

document.addEventListener('DOMContentLoaded', function() {
    navegacionTeclado = new NavegacionTeclado();
    window.navegacionTeclado = navegacionTeclado;
    
    // Conectar con el botón de la barra de accesibilidad
    const btnTeclado = document.querySelector('[onclick="toggleNavegacionTeclado()"]');
    if (btnTeclado) {
        btnTeclado.addEventListener('click', function(e) {
            // La función toggleNavegacionTeclado ya está en accesibilidad.js
        });
    }
    
    console.log('⌨️ Navegación por teclado inicializada');
});