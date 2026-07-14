(function() {
    'use strict';

    // ----- FILTROS (tabs) -----
    const filtros = document.querySelectorAll('#filtros button');
    const tarjetas = document.querySelectorAll('.card-actividad');

    function aplicarFiltro(estado) {
        filtros.forEach(btn => btn.classList.remove('activo'));
        document.querySelector(`#filtros button[data-filtro="${estado}"]`).classList.add('activo');

        tarjetas.forEach(card => {
            const estadoCard = card.dataset.estado;
            if (estado === 'todas' || estadoCard === estado) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    filtros.forEach(btn => {
        btn.addEventListener('click', function() {
            const estado = this.dataset.filtro;
            aplicarFiltro(estado);
            // Actualizar URL para mantener filtro al recargar
            const url = new URL(window.location);
            url.searchParams.set('filtro', estado);
            window.history.pushState({}, '', url);
        });
    });

    // Cargar filtro desde URL
    const params = new URLSearchParams(window.location.search);
    const filtroInicial = params.get('filtro') || 'todas';
    aplicarFiltro(filtroInicial);

    // ----- SOLICITAR EXTENSIÓN -----
    document.querySelectorAll('.btn-ext').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const actividad = this.closest('.card-actividad').querySelector('.card-titulo').textContent;
            if (confirm(`¿Solicitar extensión de tiempo para "${actividad}"?`)) {
                alert('Solicitud enviada al docente. Recibirás notificación pronto.');
            }
        });
    });

    // ----- ASISTENTE VIRTUAL (ambos botones) -----
    document.getElementById('btnAsistente').addEventListener('click', function() {
        alert('🧠 Asistente Virtual: Estoy aquí para ayudarte con tus dudas sobre las actividades.');
    });
    const btnAsistente2 = document.getElementById('btnAsistente2');
    if (btnAsistente2) {
        btnAsistente2.addEventListener('click', function() {
            alert('🧠 Asistente Virtual: ¿En qué puedo ayudarte?');
        });
    }

    // ----- ACCESIBILIDAD (funciones que ya están en Inicio.js, pero las repetimos aquí para asegurar) -----
    // Si Inicio.js ya maneja estas funciones, puedes omitir esta parte, pero la dejamos por si acaso.
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
                // Marcar botones activos (según los IDs de Inicio.js)
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

})();