(function() {
    'use strict';

    // ----- ASISTENTE VIRTUAL -----
    const btnAsistente1 = document.getElementById('btnAsistente');

    if (btnAsistente1) {
        btnAsistente1.addEventListener('click', function() {
            alert('🧠 Asistente Virtual: ¿Qué recurso buscas? Puedo ayudarte a encontrar material.');
        });
    }

    // ----- ACCESIBILIDAD (si no está ya en Inicio.js, se repite) -----
    // Si ya tienes estas funciones en Inicio.js, puedes omitir este bloque.
    // Pero lo incluimos por si acaso.
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
            }
        });
    }
    cargarPreferencias();

    const btnDark = document.getElementById('btn-darkmode');
    const btnContrast = document.getElementById('btn-contrast');
    const btnText = document.getElementById('btn-text-size');

    if (btnDark) {
        btnDark.addEventListener('click', function() {
            toggleClase(body, 'modo-oscuro');
            this.classList.toggle('active');
        });
    }
    if (btnContrast) {
        btnContrast.addEventListener('click', function() {
            toggleClase(body, 'alto-contraste');
            this.classList.toggle('active');
        });
    }
    if (btnText) {
        btnText.addEventListener('click', function() {
            toggleClase(body, 'texto-grande');
            this.classList.toggle('active');
        });
    }

    // Botones informativos
    document.getElementById('btn-leer')?.addEventListener('click', function() {
        alert('🔊 Lectura de pantalla activada (simulación)');
    });
    document.getElementById('btn-subtitulos')?.addEventListener('click', function() {
        alert('📝 Subtítulos disponibles (simulación)');
    });
    document.getElementById('btn-navegacion')?.addEventListener('click', function() {
        alert('⌨️ Navegación por teclado mejorada (simulación)');
    });
    document.getElementById('btn-config')?.addEventListener('click', function() {
        alert('⚙️ Abrir configuración completa de accesibilidad (simulación)');
    });

    // Marcar botones activos si ya estaban activados
    if (body.classList.contains('modo-oscuro')) btnDark?.classList.add('active');
    if (body.classList.contains('alto-contraste')) btnContrast?.classList.add('active');
    if (body.classList.contains('texto-grande')) btnText?.classList.add('active');

})();