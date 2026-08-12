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
// GUÍA DE USO DE AULAMOS
// =============================================

const modalGuia = document.getElementById('modalGuiaAulamos');
const cerrarGuia = document.getElementById('cerrarGuia');
const cerrarGuiaFooter = document.getElementById('cerrarGuiaFooter');

// Botones de las tarjetas
document.querySelectorAll('.help-card .help-btn').forEach(btn => {

    btn.addEventListener('click', function(e) {

        e.stopPropagation();

        const card = this.closest('.help-card');

        if (!card) return;

        const titulo = card.querySelector('h3')?.textContent.trim();

        // ==========================================
        // VER GUÍA
        // ==========================================
        if (titulo === 'Aprende a usar Aulamos') {

            if (modalGuia) {
                modalGuia.classList.add('mostrar');

                // Llevar el foco al botón cerrar
                if (cerrarGuia) {
                    cerrarGuia.focus();
                }
            }

            return;
        }

        // ==========================================
        // LECTOR DE PANTALLA
        // ==========================================
        if (titulo === 'Lee en voz alta') {

            if (!window.lector) {
                console.error(
                    '❌ El lector de pantalla no está cargado.'
                );
                return;
            }

            const activo = window.lector.toggle();

            this.textContent = activo
                ? 'Desactivar'
                : 'Activar';

            this.classList.toggle('active', activo);

            return;
        }

    });

});


// =============================================
// CERRAR GUÍA
// =============================================

function cerrarModalGuia() {

    if (modalGuia) {
        modalGuia.classList.remove('mostrar');
    }

}


// Botón X
if (cerrarGuia) {

    cerrarGuia.addEventListener(
        'click',
        cerrarModalGuia
    );

}


// Botón Cerrar
if (cerrarGuiaFooter) {

    cerrarGuiaFooter.addEventListener(
        'click',
        cerrarModalGuia
    );

}


// =============================================
// CERRAR AL HACER CLIC FUERA
// =============================================

if (modalGuia) {

    modalGuia.addEventListener('click', function(e) {

        if (e.target === modalGuia) {
            cerrarModalGuia();
        }

    });

}


// =============================================
// CERRAR CON ESC
// =============================================

document.addEventListener('keydown', function(e) {

    if (
        e.key === 'Escape' &&
        modalGuia &&
        modalGuia.classList.contains('mostrar')
    ) {

        cerrarModalGuia();

    }

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
})();