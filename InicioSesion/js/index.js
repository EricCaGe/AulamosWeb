// ========================================== //
// BLOQUE 1: NAVEGACIÓN GLOBAL CON FLECHAS    //
// ========================================== //
document.addEventListener("DOMContentLoaded", function() {
    
    // ========================================== //
    // Obtener todos los elementos interactivos
    // ========================================== //
    function obtenerElementosInteractivos() {
        const elementos = document.querySelectorAll(
            'button, a[href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        return Array.from(elementos).filter(el => {
            return el.offsetParent !== null && 
                   !el.disabled && 
                   el.getAttribute('aria-hidden') !== 'true';
        });
    }

    // ========================================== //
    // Navegación global con flechas ← →
    // ========================================== //
    document.addEventListener("keydown", function(event) {
        // No interferir con campos de texto
        const tagName = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        const esCampoTexto = ['input', 'textarea', 'select'].includes(tagName);
        if (esCampoTexto) return;

        if (event.key === "ArrowRight" || event.key === "ArrowLeft") {
            event.preventDefault();
            
            const interactivos = obtenerElementosInteractivos();
            if (interactivos.length === 0) return;
            
            const activo = document.activeElement;
            const indexActual = interactivos.indexOf(activo);
            let nuevoIndex;
            
            if (indexActual === -1) {
                nuevoIndex = 0;
            } else if (event.key === "ArrowRight") {
                nuevoIndex = (indexActual + 1) % interactivos.length;
            } else { // ArrowLeft
                nuevoIndex = (indexActual - 1 + interactivos.length) % interactivos.length;
            }
            
            interactivos[nuevoIndex].focus();
        }
    });
});