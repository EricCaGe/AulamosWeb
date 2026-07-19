// ========================================== //
// RECUPERAR - NAVEGACIÓN GLOBAL CON FLECHAS  //
// ========================================== //
document.addEventListener("DOMContentLoaded", function() {
    
    function obtenerElementosInteractivos() {
        const elementos = document.querySelectorAll(
            'button, a[href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        return Array.from(elementos).filter(function(el) {
            return el.offsetParent !== null && 
                   !el.disabled && 
                   el.getAttribute('aria-hidden') !== 'true';
        });
    }

    document.addEventListener('keydown', function(event) {
        const activo = document.activeElement;
        const tagName = activo ? activo.tagName.toLowerCase() : '';
        const esCampoTexto = ['input', 'textarea', 'select'].includes(tagName);
        
        // SI ESTAMOS EN UN CAMPO DE TEXTO, NO HACER NADA
        if (esCampoTexto) {
            return;
        }

        if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
            event.preventDefault();
            
            const interactivos = obtenerElementosInteractivos();
            if (interactivos.length === 0) return;
            
            const indexActual = interactivos.indexOf(activo);
            let nuevoIndex;
            
            if (indexActual === -1) {
                nuevoIndex = 0;
            } else if (event.key === 'ArrowRight') {
                nuevoIndex = (indexActual + 1) % interactivos.length;
            } else {
                nuevoIndex = (indexActual - 1 + interactivos.length) % interactivos.length;
            }
            
            interactivos[nuevoIndex].focus();
        }
    });

    // ========================================== //
    // PREVENIR ENVÍO DEL FORMULARIO
    // ========================================== //
    const form = document.querySelector('.login-form');
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            alert('Se enviará el enlace de recuperación a tu correo.');
        });
    }
});