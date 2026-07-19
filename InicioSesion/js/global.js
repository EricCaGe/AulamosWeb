// ========================================== */
// BOTÓN DE ACCESIBILIDAD (GLOBAL)            */
// ========================================== */

document.addEventListener('DOMContentLoaded', function() {
    const btnAccesibilidad = document.getElementById('btnAccesibilidad');
    
    if (btnAccesibilidad) {
        btnAccesibilidad.addEventListener('click', function() {
            // Mostrar alerta o abrir panel de accesibilidad
            alert('🔊 Panel de accesibilidad abierto');
            
            // Aquí puedes agregar funciones de accesibilidad:
            // - Aumentar tamaño de letra
            // - Cambiar contraste
            // - Activar modo lectura
            // - Etc.
        });
    }
});