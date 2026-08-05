// ========================================== */
// LECTOR DE PANTALLA (ESTILO CORTANA)        */
// ========================================== */

class LectorPantalla {
    constructor() {
        this.activado = false;
        this.sintesis = window.speechSynthesis;
        this.ultimoElemento = null;
        this.ultimoTexto = '';
        this.manejandoClick = false;
        this.inicializar();
    }

    inicializar() {
        this.activado = localStorage.getItem('lector_pantalla') === 'true';
        if (this.activado) {
            this.activar();
        }
    }

    activar() {
        this.activado = true;
        localStorage.setItem('lector_pantalla', 'true');
        this.aplicarEventos();
        this.anunciar('🔊 Lector de pantalla activado');
    }

    desactivar() {
        this.activado = false;
        localStorage.setItem('lector_pantalla', 'false');
        this.removerEventos();
        this.sintesis.cancel();
        this.anunciar('🔇 Lector de pantalla desactivado');
    }

    aplicarEventos() {
        const elementos = this.obtenerElementos();
        elementos.forEach(el => {
            // Eliminar eventos anteriores para evitar duplicados
            el.removeEventListener('mouseenter', this.handleMouseEnter);
            el.removeEventListener('focus', this.handleFocus);
            el.removeEventListener('click', this.handleClick);
            
            el.addEventListener('mouseenter', this.handleMouseEnter.bind(this));
            el.addEventListener('focus', this.handleFocus.bind(this));
            el.addEventListener('click', this.handleClick.bind(this));
        });

        if (this.observer) {
            this.observer.disconnect();
        }
        this.observer = new MutationObserver(() => {
            this.removerEventos();
            this.aplicarEventos();
        });
        this.observer.observe(document.body, { childList: true, subtree: true });
    }

    removerEventos() {
        const elementos = this.obtenerElementos();
        elementos.forEach(el => {
            el.removeEventListener('mouseenter', this.handleMouseEnter);
            el.removeEventListener('focus', this.handleFocus);
            el.removeEventListener('click', this.handleClick);
        });
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    obtenerElementos() {
        return document.querySelectorAll(
            'button, a[href], input, select, textarea, ' +
            '[tabindex]:not([tabindex="-1"]), ' +
            '.menu-item, .quick-btn, .btn, .card'
        );
    }

    obtenerTextoElemento(el) {
        let texto = '';

        if (el.hasAttribute('aria-label')) {
            texto = el.getAttribute('aria-label');
        } else if (el.hasAttribute('title')) {
            texto = el.getAttribute('title');
        } else {
            const hijos = el.querySelectorAll('span, strong, h1, h2, h3, h4, p, div');
            if (hijos.length > 0) {
                hijos.forEach(h => {
                    const t = h.textContent.trim();
                    if (t && t.length > 0) {
                        texto += t + ' ';
                    }
                });
            }
            if (!texto || texto.trim() === '') {
                texto = el.textContent.trim();
            }
            texto = texto.replace(/[^\w\sáéíóúÁÉÍÓÚñÑ.,!?()\-/]/g, '').trim();
        }

        if (!texto || texto === '') {
            texto = el.tagName.toLowerCase();
        }

        return texto;
    }

    leer(texto) {
        if (!this.activado) return;
        if (!texto || texto.trim() === '') return;

        this.sintesis.cancel();

        const utterance = new SpeechSynthesisUtterance(texto);
        utterance.lang = 'es-MX';
        utterance.rate = 0.9;
        utterance.pitch = 1;
        utterance.volume = 1;

        const voces = this.sintesis.getVoices();
        const vozEsp = voces.find(v => v.lang.startsWith('es'));
        if (vozEsp) {
            utterance.voice = vozEsp;
        }

        this.sintesis.speak(utterance);
        this.ultimoTexto = texto;
    }

    handleMouseEnter(e) {
        const el = e.currentTarget;
        if (el === this.ultimoElemento) return;
        this.ultimoElemento = el;

        const texto = this.obtenerTextoElemento(el);
        if (texto) {
            this.leer(texto);
        }
    }

    handleFocus(e) {
        const el = e.currentTarget;
        if (el === this.ultimoElemento) return;
        this.ultimoElemento = el;

        const texto = this.obtenerTextoElemento(el);
        if (texto) {
            this.leer(texto);
        }
    }

    handleClick(e) {
        if (!this.activado) return;

        const el = e.currentTarget;
        const texto = this.obtenerTextoElemento(el);

        // Si es un enlace o botón, leer y permitir acción normal después
        if (texto) {
            // Leer el texto
            this.leer(texto);
            
            // Marcar que se está manejando clic
            this.manejandoClick = true;

            // Después de la lectura (800ms), permitir el clic
            setTimeout(() => {
                this.manejandoClick = false;
                // Si el elemento es un enlace o botón, ejecutar su acción
                if (el.tagName === 'A' || el.tagName === 'BUTTON') {
                    // No hacer preventDefault, dejar que el clic normal ocurra
                    // Pero si el clic fue prevenido, lo reejecutamos
                    if (e.defaultPrevented) {
                        el.click();
                    }
                }
            }, 800);
        }
    }

    anunciar(mensaje) {
        if (!this.activado) return;
        const utterance = new SpeechSynthesisUtterance(mensaje);
        utterance.lang = 'es-MX';
        utterance.rate = 0.9;
        this.sintesis.speak(utterance);
    }

    toggle() {
        if (this.activado) {
            this.desactivar();
        } else {
            this.activar();
        }
        return this.activado;
    }
}

// ========================================== */
// INICIALIZAR                                */
// ========================================== */
let lector = null;

document.addEventListener('DOMContentLoaded', function() {
    lector = new LectorPantalla();
    window.lector = lector;

    // Conectar con el botón de "Leer pantalla"
    const btnLeer = document.querySelector('.acc-opt-btn .fa-volume-high')?.closest('.acc-opt-btn');
    if (btnLeer) {
        btnLeer.addEventListener('click', function(e) {
            e.stopPropagation();
            const estado = lector.toggle();
            this.classList.toggle('active');
            const icon = this.querySelector('i');
            if (icon) {
                if (estado) {
                    icon.className = 'fa-solid fa-volume-high';
                    this.querySelector('span').textContent = 'Lector activo';
                } else {
                    icon.className = 'fa-solid fa-volume-xmark';
                    this.querySelector('span').textContent = 'Lector inactivo';
                }
            }
        });

        if (lector.activado) {
            btnLeer.classList.add('active');
            const icon = btnLeer.querySelector('i');
            if (icon) {
                icon.className = 'fa-solid fa-volume-high';
                btnLeer.querySelector('span').textContent = 'Lector activo';
            }
        }
    }
});