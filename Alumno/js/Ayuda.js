(function() {
    'use strict';

    // =============================================
    // ASISTENTE VIRTUAL
    // =============================================
    const btnAsistente = document.getElementById('btnAsistente');
    if (btnAsistente) {
        btnAsistente.addEventListener('click', function() {
            alert('🧠 Asistente Virtual: ¡Hola! ¿En qué puedo ayudarte? Puedo guiarte por la plataforma o resolver tus dudas.');
        });
    }

    // =============================================
    // BOTONES DE GUÍAS
    // =============================================
    document.querySelectorAll('.help-card .help-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const card = this.closest('.help-card');
            const titulo = card.querySelector('h3').textContent;
            alert('🔍 Abriendo guía: "' + titulo + '"\n(Esta funcionalidad estará disponible próximamente)');
        });
    });

    // =============================================
    // BOTONES DE ARTÍCULOS
    // =============================================
    document.querySelectorAll('.article-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const item = this.closest('.help-article-item');
            const titulo = item.querySelector('h3').textContent;
            alert('📖 Leyendo artículo: "' + titulo + '"\n(El contenido completo se mostrará aquí)');
        });
    });

    // =============================================
    // BOTÓN DE SOPORTE
    // =============================================
    const supportBtn = document.querySelector('.support-btn');
    if (supportBtn) {
        supportBtn.addEventListener('click', function() {
            alert('💬 Conectando con soporte...\nUn asesor te atenderá en breve. ¡Estamos para ayudarte!');
        });
    }

    // =============================================
    // NOTA: La funcionalidad de accesibilidad (modo oscuro, alto contraste, texto grande)
    // ya está en Inicio.js, así que no la repetimos aquí para evitar duplicación.
    // Si por algún motivo Inicio.js no se carga, puedes descomentar el bloque de abajo.
    // =============================================

    /*
    // ----- ACCESIBILIDAD (solo si Inicio.js no está presente) -----
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
    */
})();