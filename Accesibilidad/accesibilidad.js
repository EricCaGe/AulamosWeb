// ========================================== */
// ACCESIBILIDAD - LÓGICA COMPLETA            */
// ========================================== */

// ========================================== */
// TOGGLE BARRA CON CIERRE AL CLIC FUERA     */
// ========================================== */
function toggleBarraAccesibilidad() {
    const barra = document.getElementById('barra-accesibilidad');
    const btnFlotante = document.getElementById('btnAccesibilidadFlotante');
    const btnHeader = document.getElementById('btnAccesibilidad');

    if (barra) {
        barra.classList.toggle('accesibilidad-hidden');
        if (btnFlotante) btnFlotante.classList.toggle('active');
        if (btnHeader) btnHeader.classList.toggle('active');
        
        if (!barra.classList.contains('accesibilidad-hidden')) {
            setTimeout(() => {
                document.addEventListener('click', cerrarBarraClickFuera);
            }, 100);
        } else {
            document.removeEventListener('click', cerrarBarraClickFuera);
        }
    }
}

function cerrarBarraClickFuera(e) {
    const barra = document.getElementById('barra-accesibilidad');
    const btnFlotante = document.getElementById('btnAccesibilidadFlotante');
    const btnHeader = document.getElementById('btnAccesibilidad');
    
    if (barra && !barra.contains(e.target) && 
        !btnFlotante?.contains(e.target) && 
        !btnHeader?.contains(e.target)) {
        barra.classList.add('accesibilidad-hidden');
        if (btnFlotante) btnFlotante.classList.remove('active');
        if (btnHeader) btnHeader.classList.remove('active');
        document.removeEventListener('click', cerrarBarraClickFuera);
    }
}

// ========================================== */
// CARGAR PREFERENCIAS GUARDADAS             */
// ========================================== */
function cargarTodasPreferencias() {
    console.log('🔍 Cargando preferencias desde localStorage...');
    
    // Alto contraste
    const altoContraste = localStorage.getItem('alto-contraste');
    if (altoContraste === 'true' || altoContraste === '1') {
        document.body.classList.add('alto-contraste');
        const fondo = localStorage.getItem('contraste_fondo') || 'negro';
        const color = localStorage.getItem('contraste_color') || 'azul';
        document.body.classList.add('fondo-' + fondo);
        document.body.classList.add('color-' + color);
        console.log('✅ Alto contraste aplicado:', fondo, color);
    }
    
    // Modo oscuro
    if (localStorage.getItem('modo-oscuro') === 'true' || localStorage.getItem('modo-oscuro') === '1') {
        document.body.classList.add('modo-oscuro');
        console.log('✅ Modo oscuro aplicado');
    }
    
    // Texto grande
    if (localStorage.getItem('texto-grande') === 'true' || localStorage.getItem('texto-grande') === '1') {
        document.body.classList.add('texto-grande');
        console.log('✅ Texto grande aplicado');
    }
    if (localStorage.getItem('lector_pantalla') === 'true') {
        if (lector) {
            lector.activado = true;
            lector.aplicarEventos();
            lector.actualizarBotonLector();
        }
        console.log('✅ Lector de pantalla aplicado');
    }
    // Subtítulos
    if (localStorage.getItem('subtitulos') === 'true' || localStorage.getItem('subtitulos') === '1') {
        document.body.classList.add('subtitulos');
        console.log('✅ Subtítulos aplicados');
    }
    
    // Navegación por teclado
    if (localStorage.getItem('navegacion-teclado') === 'true' || localStorage.getItem('navegacion-teclado') === '1') {
        document.body.classList.add('navegacion-teclado');
        console.log('✅ Navegación por teclado aplicada');
    }
}

// ========================================== */
// GUARDAR PREFERENCIA EN BD                  */
// ========================================== */
function guardarPreferencia(campo, valor) {
    localStorage.setItem(campo, valor);

    const formData = new FormData();
    formData.append('campo', campo);
    formData.append('valor', valor);

    fetch('../Accesibilidad/guardar_preferencia.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.log('ℹ️ No se guardó en BD:', data.error || data.message);
        } else {
            console.log('✅ Guardado en BD:', campo, '=', valor);
        }
    })
    .catch(error => console.log('ℹ️ Error al guardar en BD:', error));
}

// ========================================== */
// FUNCIONES DE ACCESIBILIDAD                */
// ========================================== */

function toggleAltoContraste() {
    const activo = document.body.classList.toggle('alto-contraste');
    
    if (activo) {
        const fondo = localStorage.getItem('contraste_fondo') || 'negro';
        const color = localStorage.getItem('contraste_color') || 'azul';
        document.body.classList.remove('fondo-blanco', 'fondo-negro');
        document.body.classList.add('fondo-' + fondo);
        ['azul', 'amarillo', 'verde', 'rojo'].forEach(c => {
            document.body.classList.remove('color-' + c);
        });
        document.body.classList.add('color-' + color);
    } else {
        document.body.classList.remove('fondo-blanco', 'fondo-negro');
        ['azul', 'amarillo', 'verde', 'rojo'].forEach(c => {
            document.body.classList.remove('color-' + c);
        });
    }
    
    // ✅ GUARDAR EN LOCALSTORAGE Y BD
    localStorage.setItem('alto-contraste', activo ? 'true' : 'false');
    guardarPreferencia('alto_contraste', activo ? 1 : 0);
}

function toggleModoOscuro() {
    const activo = document.body.classList.toggle('modo-oscuro');
    localStorage.setItem('modo-oscuro', activo ? 'true' : 'false');
    guardarPreferencia('modo_oscuro', activo ? 1 : 0);
}

function toggleTextoGrande() {
    const activo = document.body.classList.toggle('texto-grande');
    const valor = activo ? 'Grande' : 'Normal';
    localStorage.setItem('tamano_texto', valor);
    guardarPreferencia('tamano_texto', valor);
}

function toggleSubtitulos() {
    const activo = document.body.classList.toggle('subtitulos');
    localStorage.setItem('subtitulos', activo ? 'true' : 'false');
    guardarPreferencia('subtitulos', activo ? 1 : 0);
}

function toggleNavegacionTeclado() {
    const activo = document.body.classList.toggle('navegacion-teclado');
    localStorage.setItem('navegacion_teclado', activo ? 'true' : 'false');
    guardarPreferencia('navegacion_teclado', activo ? 1 : 0);
}

function abrirConfiguracion() {
    alert('⚙️ Abriendo configuración...');
}

// ========================================== */
// LECTOR DE PANTALLA                        */
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
        this.actualizarBotonLector();
    }

    activar() {
        this.activado = true;
        localStorage.setItem('lector_pantalla', 'true');
        guardarPreferencia('lector_pantalla', 1);
        this.aplicarEventos();
        this.anunciar('🔊 Lector de pantalla activado');
        this.actualizarBotonLector();
    }

    desactivar() {
        this.activado = false;
        localStorage.setItem('lector_pantalla', 'false');
        guardarPreferencia('lector_pantalla', 0);
        this.removerEventos();
        this.sintesis.cancel();
        this.anunciar('🔇 Lector de pantalla desactivado');
        this.actualizarBotonLector();
    }

    actualizarBotonLector() {
        const btnLeer = document.getElementById('btnLectorPantalla');
        if (btnLeer) {
            btnLeer.classList.toggle('active', this.activado);
            const icon = btnLeer.querySelector('i');
            if (icon) {
                icon.className = this.activado ? 'fa-solid fa-volume-high' : 'fa-solid fa-volume-xmark';
            }
            const span = btnLeer.querySelector('span');
            if (span) {
                span.textContent = this.activado ? 'Lector activo' : 'Leer pantalla';
            }
        }
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
            '[tabindex]:not([tabindex="-1"]), ' +
            '.menu-item, .quick-btn, .btn, .card'
        );
    }

    obtenerTextoElemento(el) {
        let texto = '';

        if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT') {
            const label = document.querySelector(`label[for="${el.id}"]`);
            if (label) {
                texto = label.textContent.trim() + ', ';
            }
            if (el.placeholder) {
                texto += el.placeholder + ', ';
            }
            if (el.value) {
                texto += 'valor: ' + el.value + ', ';
            }
            if (el.type) {
                const tipos = {
                    'text': 'campo de texto',
                    'password': 'campo de contraseña',
                    'email': 'campo de correo electrónico',
                    'number': 'campo numérico',
                    'date': 'campo de fecha',
                    'checkbox': 'casilla de verificación',
                    'radio': 'botón de opción',
                    'file': 'selector de archivos'
                };
                texto += tipos[el.type] || 'campo de entrada';
            }
            return texto.trim();
        }

        if (el.tagName === 'BUTTON' || el.tagName === 'A') {
            const span = el.querySelector('span');
            if (span) {
                texto = span.textContent.trim();
            }
            if (!texto) {
                texto = el.textContent.trim();
            }
            if (el.hasAttribute('aria-label')) {
                texto = el.getAttribute('aria-label');
            }
            if (el.hasAttribute('title')) {
                texto = el.getAttribute('title');
            }
            if (el.tagName === 'A') {
                texto += ', enlace';
            }
            return texto.trim();
        }

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

        if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT') {
            const label = document.querySelector(`label[for="${el.id}"]`);
            let texto = '';
            if (label) {
                texto = label.textContent.trim() + ', ';
            }
            if (el.value) {
                texto += 'valor: ' + el.value + ', ';
            }
            if (el.placeholder) {
                texto += el.placeholder;
            }
            if (texto) {
                this.leer(texto);
            }
            return;
        }

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
                if (el.tagName === 'A' || el.tagName === 'BUTTON') {
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
// MODAL PERSONALIZAR CONTRASTE               */
// ========================================== */

let fondoSeleccionado = 'negro';
let colorSeleccionado = 'azul';

function abrirModalContraste() {
    const modal = document.getElementById('modal-contraste');
    if (modal) {
        modal.classList.remove('modal-contraste-hidden');
        fondoSeleccionado = localStorage.getItem('contraste_fondo') || 'negro';
        colorSeleccionado = localStorage.getItem('contraste_color') || 'azul';
        
        document.querySelectorAll('.btn-opcion[data-grupo="fondo"]').forEach(btn => {
            btn.classList.toggle('seleccionado', btn.dataset.fondo === fondoSeleccionado);
        });
        document.querySelectorAll('.btn-opcion[data-grupo="borde"]').forEach(btn => {
            btn.classList.toggle('seleccionado', btn.dataset.color === colorSeleccionado);
        });
    }
}

function cerrarModalContraste() {
    const modal = document.getElementById('modal-contraste');
    if (modal) {
        modal.classList.add('modal-contraste-hidden');
    }
}

function seleccionarBoton(elemento) {
    const grupo = elemento.dataset.grupo;
    const contenedor = elemento.closest('.opciones-botones');
    const botones = contenedor.querySelectorAll('.btn-opcion');
    
    botones.forEach(btn => btn.classList.remove('seleccionado'));
    elemento.classList.add('seleccionado');
    
    if (grupo === 'fondo') {
        fondoSeleccionado = elemento.dataset.fondo;
    } else if (grupo === 'borde') {
        colorSeleccionado = elemento.dataset.color;
    }
}

function limpiarSeleccionesVisuales() {
    document.body.classList.remove('alto-contraste');
    document.body.classList.remove('fondo-blanco', 'fondo-negro');
    ['azul', 'amarillo', 'verde', 'rojo'].forEach(c => {
        document.body.classList.remove('color-' + c);
    });
    localStorage.setItem('alto-contraste', 'false');
    guardarPreferencia('alto_contraste', 0);
    
    document.querySelectorAll('.btn-opcion').forEach(btn => {
        btn.classList.remove('seleccionado');
    });
    
    cerrarModalContraste();
}

function aplicarPersonalizacion() {
    localStorage.setItem('contraste_fondo', fondoSeleccionado);
    localStorage.setItem('contraste_color', colorSeleccionado);
    
    guardarPreferencia('contraste_fondo', fondoSeleccionado);
    guardarPreferencia('contraste_color', colorSeleccionado);
    
    if (document.body.classList.contains('alto-contraste')) {
        document.body.classList.remove('fondo-blanco', 'fondo-negro');
        document.body.classList.add('fondo-' + fondoSeleccionado);
        ['azul', 'amarillo', 'verde', 'rojo'].forEach(c => {
            document.body.classList.remove('color-' + c);
        });
        document.body.classList.add('color-' + colorSeleccionado);
    }
    
    cerrarModalContraste();
}

// ========================================== */
// INICIALIZAR - CON CARGA DE PREFERENCIAS   */
// ========================================== */

let lector = new LectorPantalla();
window.lector = lector;

// ✅ EJECUTAR CUANDO LA PÁGINA ESTÉ LISTA
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando accesibilidad...');
    
    // 1. ✅ PRIMERO CREAR EL LECTOR
    
    // 2. ✅ CONFIGURAR EL BOTÓN LECTOR
    const btnLeer = document.getElementById('btnLectorPantalla');
    if (btnLeer) {
        const nuevoBtn = btnLeer.cloneNode(true);
        btnLeer.parentNode.replaceChild(nuevoBtn, btnLeer);
        const btnLeerNuevo = document.getElementById('btnLectorPantalla');
        
        btnLeerNuevo.addEventListener('click', function(e) {
            e.stopPropagation();
            const estado = lector.toggle();
            this.classList.toggle('active');
            const icon = this.querySelector('i');
            if (icon) {
                icon.className = estado ? 'fa-solid fa-volume-high' : 'fa-solid fa-volume-xmark';
            }
            const span = this.querySelector('span');
            if (span) {
                span.textContent = estado ? 'Lector activo' : 'Leer pantalla';
            }
        });
    }
    
    // 3. ✅ CARGAR PREFERENCIAS (AHORA EL LECTOR YA EXISTE)
    cargarTodasPreferencias();
    
    console.log('✅ Accesibilidad inicializada correctamente');
});

// ✅ TAMBIÉN EJECUTAR SI LA PÁGINA YA ESTABA CARGADA
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    // Si el DOM ya está listo, ejecutar inmediatamente
    setTimeout(() => {
        if (!window._accesibilidadInicializada) {
            window._accesibilidadInicializada = true;
            console.log('🚀 Inicializando accesibilidad (carga rápida)...');
            
            // Inicializar lector si no existe
            if (!lector) {
                lector = new LectorPantalla();
                window.lector = lector;
            }
            
            cargarTodasPreferencias();
        }
    }, 50);
}