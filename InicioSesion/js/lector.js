// ========================================== */
// LECTOR DE PANTALLA                         */
// ========================================== */

class LectorPantalla {
    constructor() {
        this.activado = localStorage.getItem('lector_pantalla') === 'true';
        this.sintesis = window.speechSynthesis;
        this.ultimoElemento = null;
        this.ultimoTexto = '';
        this.manejandoClick = false;
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
            '[tabindex]:not([tabindex="-1"]), .menu-item, .quick-btn, .btn, .card'
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
        if (texto) {
            this.leer(texto);
            this.manejandoClick = true;
            setTimeout(() => {
                this.manejandoClick = false;
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

let lector = null;
document.addEventListener('DOMContentLoaded', function() {
    lector = new LectorPantalla();
    window.lector = lector;
});