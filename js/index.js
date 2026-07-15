// ========================================== //
// BLOQUE 1: INTERACCIÓN DE LA VISTA          //
// ========================================== //
document.addEventListener("DOMContentLoaded", function() {
    
    // Capturamos tus botones de la barra superior
    const btnAccesibilidadTexto = document.getElementById("global-accessibility-btn");
    const btnAccesibilidadRedondo = document.getElementById("global-accessibility-round");
    
    // Capturamos el contenedor de la nueva barra
    const barraAccesibilidad = document.getElementById("contenedor-barra-accesibilidad");

    // Función que solo quita y pone la barra en la vista
    function alternarBarraAccesibilidad() {
        if (barraAccesibilidad) {
            barraAccesibilidad.classList.toggle("mostrar-barra");
        }
    }

    // Eventos de clic
    if(btnAccesibilidadTexto) btnAccesibilidadTexto.addEventListener("click", alternarBarraAccesibilidad);
    if(btnAccesibilidadRedondo) btnAccesibilidadRedondo.addEventListener("click", alternarBarraAccesibilidad);

});