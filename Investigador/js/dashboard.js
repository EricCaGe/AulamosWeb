// ========================================== */
// DASHBOARD - INVESTIGADOR                   */
// ========================================== */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔬 Dashboard de Investigador inicializado');
    
    // Establecer el porcentaje del círculo de progreso
    const circulo = document.querySelector('.circulo-progreso');
    if (circulo) {
        const progreso = circulo.dataset.progreso || 0;
        circulo.style.setProperty('--progreso', progreso + '%');
    }
});