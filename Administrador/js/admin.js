document.addEventListener("DOMContentLoaded", function() {

    // ASISTENTE VIRTUAL
    const btnAsistente = document.getElementById('btn-asistente');
    if (btnAsistente) {
        btnAsistente.addEventListener('click', function() {
            alert('🤖 Asistente Virtual: ¡Hola! Soy tu asistente de Aulamos. ¿En qué puedo ayudarte como administrador?');
        });
    }

    // ACCESIBILIDAD
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

    const btnDark = document.getElementById('btn-darkmode');
    if (btnDark) {
        btnDark.addEventListener('click', function() {
            toggleClase(body, 'modo-oscuro');
            this.classList.toggle('active');
        });
    }

    const btnContrast = document.getElementById('btn-contrast');
    if (btnContrast) {
        btnContrast.addEventListener('click', function() {
            toggleClase(body, 'alto-contraste');
            this.classList.toggle('active');
        });
    }

    const btnText = document.getElementById('btn-text-size');
    if (btnText) {
        btnText.addEventListener('click', function() {
            toggleClase(body, 'texto-grande');
            this.classList.toggle('active');
        });
    }

    // NOTIFICACIONES
    const iconBell = document.querySelector('.icon-bell');
    if (iconBell) {
        iconBell.addEventListener('click', function() {
            alert('🔔 No tienes notificaciones nuevas.');
        });
    }

    // ACCESIBILIDAD HEADER
    const btnAccHeader = document.querySelector('.btn-accessibility-header');
    if (btnAccHeader) {
        btnAccHeader.addEventListener('click', function() {
            alert('♿ Abriendo panel de accesibilidad...');
        });
    }

    // BOTONES ACCESIBILIDAD INFERIOR
    document.querySelectorAll('.acc-opt-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const texto = this.querySelector('span')?.textContent || 'Opción';
            alert('🔊 ' + texto + ' activado (simulación)');
        });
    });

    // BOTÓN CONFIGURACIÓN
    const btnConfig = document.querySelector('.btn-open-config');
    if (btnConfig) {
        btnConfig.addEventListener('click', function() {
            alert('⚙️ Abrir configuración completa de accesibilidad');
        });
    }

    // BOTÓN CONFIGURACIÓN DE GESTIÓN ACADÉMICA
    const btnConfigGestion = document.querySelector('.btn-configuracion');
    if (btnConfigGestion) {
        btnConfigGestion.addEventListener('click', function(e) {
            e.preventDefault();
            alert('⚙️ Abriendo configuración del ciclo escolar...');
        });
    }

});