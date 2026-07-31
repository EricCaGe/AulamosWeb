document.addEventListener("DOMContentLoaded", function() {

    // =============================================
    // ASISTENTE VIRTUAL
    // =============================================
    const btnAsistente = document.getElementById('btnAsistente');
    if (btnAsistente) {
        btnAsistente.addEventListener('click', function() {
            alert('🧠 Asistente Virtual: ¡Hola! Soy tu asistente de Aulamos. ¿En qué actividad te puedo ayudar hoy?');
        });
    }

    // =============================================
    // ACCESIBILIDAD (unificada con localStorage)
    // =============================================
    const body = document.body;

    function toggleClase(elemento, clase) {
        elemento.classList.toggle(clase);
        const activo = elemento.classList.contains(clase);
        localStorage.setItem(clase, activo ? 'true' : 'false');
    }

    function cargarPreferencias() {
        const preferencias = ['modo-oscuro', 'alto-contraste', 'texto-grande'];
        preferencias.forEach(clase => {
            if (localStorage.getItem(clase) === 'true') {
                body.classList.add(clase);
                // Marcar botón correspondiente
                const map = {
                    'modo-oscuro': 'btn-darkmode',
                    'alto-contraste': 'btn-contrast',
                    'texto-grande': 'btn-text-size'
                };
                const btn = document.getElementById(map[clase]);
                if (btn) btn.classList.add('active');
            }
        });
    }
    cargarPreferencias();

    // Botón: Modo oscuro
    const btnDark = document.getElementById('btn-darkmode');
    if (btnDark) {
        btnDark.addEventListener('click', function() {
            toggleClase(body, 'modo-oscuro');
            this.classList.toggle('active');
        });
    }

    // Botón: Alto contraste
    const btnContrast = document.getElementById('btn-contrast');
    if (btnContrast) {
        btnContrast.addEventListener('click', function() {
            toggleClase(body, 'alto-contraste');
            this.classList.toggle('active');
        });
    }

    // Botón: Texto grande
    const btnText = document.getElementById('btn-text-size');
    if (btnText) {
        btnText.addEventListener('click', function() {
            toggleClase(body, 'texto-grande');
            this.classList.toggle('active');
        });
    }

    // Botones informativos (simulación)
    const btnLeer = document.getElementById('btn-leer');
    const btnSubtitulos = document.getElementById('btn-subtitulos');
    const btnNavegacion = document.getElementById('btn-navegacion');
    const btnConfig = document.getElementById('btn-config');

    if (btnLeer) {
        btnLeer.addEventListener('click', function() {
            alert('🔊 Lectura de pantalla activada (simulación)');
        });
    }
    if (btnSubtitulos) {
        btnSubtitulos.addEventListener('click', function() {
            alert('📝 Subtítulos disponibles (simulación)');
        });
    }
    if (btnNavegacion) {
        btnNavegacion.addEventListener('click', function() {
            alert('⌨️ Navegación por teclado mejorada (simulación)');
        });
    }
    if (btnConfig) {
        btnConfig.addEventListener('click', function() {
            alert('⚙️ Abrir configuración completa de accesibilidad (simulación)');
        });
    }

    // =============================================
    // BOTONES "CONTINUAR" y "VER ACTIVIDAD"
    // =============================================
    const btnContinuar = document.querySelector('.btn-purple');
    if (btnContinuar) {
        btnContinuar.addEventListener('click', function() {
            alert('▶️ Continuando con la actividad...');
        });
    }

    const btnVerActividad = document.querySelector('.btn-orange');
    if (btnVerActividad) {
        btnVerActividad.addEventListener('click', function() {
            alert('👀 Abriendo la actividad próxima...');
        });
    }

});