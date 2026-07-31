(function() {
    'use strict';

    // =============================================
    // ASISTENTE VIRTUAL
    // =============================================
    const btnAsistente = document.getElementById('btnAsistente');
    if (btnAsistente) {
        btnAsistente.addEventListener('click', function() {
            alert('🧠 Asistente Virtual: ¿Qué recurso buscas? Puedo ayudarte a encontrar material educativo.');
        });
    }

    // =============================================
    // NOTA: La funcionalidad de accesibilidad (modo oscuro, alto contraste, texto grande)
    // ya está en Inicio.js, así que no la repetimos aquí.
    // Solo añadimos funcionalidades específicas de la biblioteca si las hubiera.
    // =============================================

    // Ejemplo: si quisieras abrir el recurso al hacer clic en la tarjeta
    // document.querySelectorAll('.recurso-card').forEach(card => {
    //     card.addEventListener('click', function() {
    //         const titulo = this.querySelector('.recurso-titulo').textContent;
    //         alert('Abriendo recurso: ' + titulo);
    //     });
    // });

})();