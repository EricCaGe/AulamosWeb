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
// OBTENER CLAVE CON ID DE USUARIO           */
// ========================================== */

function obtenerClave(campo) {
    const idUsuario = window.idUsuario || 0;
    return 'accesibilidad_' + idUsuario + '_' + campo;
}

// ========================================== */
// CARGAR PREFERENCIAS GUARDADAS             */
// ========================================== */
function cargarTodasPreferencias() {
    console.log('🔍 Cargando preferencias desde localStorage...');
    
    const idUsuario = window.idUsuario || 0;
    console.log('👤 Usuario ID:', idUsuario);
    
    // Alto contraste
    const claveContraste = obtenerClave('alto_contraste');
    const altoContraste = localStorage.getItem(claveContraste);
    if (altoContraste === 'true' || altoContraste === '1') {
        document.body.classList.add('alto-contraste');
        const fondo = localStorage.getItem(obtenerClave('contraste_fondo')) || 'negro';
        const color = localStorage.getItem(obtenerClave('contraste_color')) || 'azul';
        document.body.classList.add('fondo-' + fondo);
        document.body.classList.add('color-' + color);
        console.log('✅ Alto contraste aplicado:', fondo, color);
    }
    
    // Modo oscuro
    const claveOscuro = obtenerClave('modo_oscuro');
    if (localStorage.getItem(claveOscuro) === 'true' || localStorage.getItem(claveOscuro) === '1') {
        document.body.classList.add('modo-oscuro');
        console.log('✅ Modo oscuro aplicado');
    }
    
    // Texto grande
    const claveTexto = obtenerClave('texto_grande');
    if (localStorage.getItem(claveTexto) === 'true' || localStorage.getItem(claveTexto) === '1') {
        document.body.classList.add('texto-grande');
        console.log('✅ Texto grande aplicado');
    }

    // Lector de pantalla - EL CONSTRUCTOR YA LO ACTIVA
    const claveLector = obtenerClave('lector_pantalla');
    if (localStorage.getItem(claveLector) === 'true') {
        if (window.lector) {
            window.lector.activado = true;
            window.lector.aplicarEventos();
            window.lector.actualizarBotonLector();
            console.log('✅ Lector de pantalla aplicado');
        }
    }
    
    // Subtítulos
    const claveSubtitulos = obtenerClave('subtitulos');
    if (localStorage.getItem(claveSubtitulos) === 'true' || localStorage.getItem(claveSubtitulos) === '1') {
        document.body.classList.add('subtitulos');
        console.log('✅ Subtítulos aplicados');
    }
    
    // Navegación por teclado
    const claveTeclado = obtenerClave('navegacion_teclado');
    if (localStorage.getItem(claveTeclado) === 'true' || localStorage.getItem(claveTeclado) === '1') {
        document.body.classList.add('navegacion-teclado');
        console.log('✅ Navegación por teclado aplicada');
    }
}

// ========================================== */
// GUARDAR PREFERENCIA EN BD                  */
// ========================================== */
function guardarPreferencia(campo, valor) {
    const idUsuario = window.idUsuario || 0;
    
    // Guardar en localStorage con clave única por usuario
    const clave = obtenerClave(campo);
    localStorage.setItem(clave, valor);

    // Guardar en la BD
    const formData = new FormData();
    formData.append('id_usuario', idUsuario);
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
        const fondo = localStorage.getItem(obtenerClave('contraste_fondo')) || 'negro';
        const color = localStorage.getItem(obtenerClave('contraste_color')) || 'azul';
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
    
    guardarPreferencia('alto_contraste', activo ? 'true' : 'false');
}

function toggleModoOscuro() {
    const activo = document.body.classList.toggle('modo-oscuro');
    guardarPreferencia('modo_oscuro', activo ? 'true' : 'false');
}

function toggleTextoGrande() {
    const activo = document.body.classList.toggle('texto-grande');
    const valor = activo ? 'true' : 'false';
    guardarPreferencia('texto_grande', valor);
}

function toggleSubtitulos() {
    const activo = document.body.classList.toggle('subtitulos');
    guardarPreferencia('subtitulos', activo ? 'true' : 'false');
}

function toggleNavegacionTeclado() {
    const activo = document.body.classList.toggle('navegacion-teclado');
    guardarPreferencia('navegacion_teclado', activo ? 'true' : 'false');
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
        const clave = obtenerClave('lector_pantalla');
        this.activado = localStorage.getItem(clave) === 'true';
        if (this.activado) {
            this.activar();
        }
        this.actualizarBotonLector();
    }

    activar() {
        this.activado = true;
        guardarPreferencia('lector_pantalla', 'true');
        this.aplicarEventos();
        this.anunciar('🔊 Lector de pantalla activado');
        this.actualizarBotonLector();
    }

    desactivar() {
        this.activado = false;
        guardarPreferencia('lector_pantalla', 'false');
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
        fondoSeleccionado = localStorage.getItem(obtenerClave('contraste_fondo')) || 'negro';
        colorSeleccionado = localStorage.getItem(obtenerClave('contraste_color')) || 'azul';
        
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
    guardarPreferencia('alto_contraste', 'false');
    
    document.querySelectorAll('.btn-opcion').forEach(btn => {
        btn.classList.remove('seleccionado');
    });
    
    cerrarModalContraste();
}

function aplicarPersonalizacion() {
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

// ✅ CREAR EL LECTOR Y EXPONERLO GLOBALMENTE
let lector = new LectorPantalla();
window.lector = lector;

// ✅ EJECUTAR CUANDO LA PÁGINA ESTÉ LISTA
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando accesibilidad...');
    
    // ✅ CONFIGURAR EL BOTÓN LECTOR
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
    
    // ✅ CARGAR PREFERENCIAS
    cargarTodasPreferencias();
    
    console.log('✅ Accesibilidad inicializada correctamente');
});