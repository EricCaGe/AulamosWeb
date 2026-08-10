(function() {
    'use strict';

    // =============================================
    // FILTROS (tabs)
    // =============================================
    const filtros = document.querySelectorAll('#filtros button');
    const tarjetas = document.querySelectorAll('.card-actividad');

    function aplicarFiltro(estado) {
        // Actualizar clases activas en botones
        filtros.forEach(btn => btn.classList.remove('activo'));
        const btnActivo = document.querySelector(`#filtros button[data-filtro="${estado}"]`);
        if (btnActivo) btnActivo.classList.add('activo');

        // Mostrar/ocultar tarjetas
        tarjetas.forEach(card => {
            const estadoCard = card.dataset.estado;
            let mostrar = false;
            if (estado === 'todas') {
                mostrar = true;
            } else if (estado === 'pendiente') {
                mostrar = (estadoCard === 'pendiente' || estadoCard === 'atrasada');
            } else if (estado === 'proceso') {
                mostrar = (estadoCard === 'en_proceso');
            } else if (estado === 'completada') {
                mostrar = (estadoCard === 'completada' || estadoCard === 'calificada');
            }
            card.style.display = mostrar ? 'flex' : 'none';
        });
    }

    // Event listeners para los filtros
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

    // Cargar filtro desde URL al iniciar
    const params = new URLSearchParams(window.location.search);
    const filtroInicial = params.get('filtro') || 'todas';
    aplicarFiltro(filtroInicial);

    // =============================================
    // SOLICITAR EXTENSIÓN
    // =============================================
    document.querySelectorAll('.btn-ext').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const actividad = this.closest('.card-actividad').querySelector('.card-titulo').textContent;
            if (confirm(`¿Solicitar extensión de tiempo para "${actividad}"?`)) {
                alert('Solicitud enviada al docente. Recibirás notificación pronto.');
                // Aquí podrías hacer una petición AJAX para registrar la solicitud
            }
        });
    });

    // =============================================
    // ASISTENTE VIRTUAL (solo el de la cabecera)
    // =============================================
    const btnAsistente = document.getElementById('btnAsistente');
    if (btnAsistente) {
        btnAsistente.addEventListener('click', function() {
            alert('🧠 Asistente Virtual: Estoy aquí para ayudarte con tus dudas sobre las actividades.');
        });
    }

})();