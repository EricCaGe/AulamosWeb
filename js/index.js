// ========================================== //
// BLOQUE 1: INTERACCIÓN DE LA VISTA (INDEX)  //
// ========================================== //
document.addEventListener("DOMContentLoaded", function() {
    
    // Capturamos tus botones de la barra superior
    const btnAccesibilidadTexto = document.getElementById("global-accessibility-btn");
    const btnAccesibilidadRedondo = document.getElementById("global-accessibility-round");
    
    // Capturamos el contenedor de la barra de accesibilidad y sus opciones
    const barraAccesibilidad = document.getElementById("contenedor-barra-accesibilidad");
    const contenedorBotones = document.querySelector(".acc-opciones-botones");
    
    // Convertimos los botones de opciones en un array para poder buscar sus índices fácilmente
    const botonesOpciones = contenedorBotones ? Array.from(contenedorBotones.querySelectorAll(".acc-btn-opcion")) : [];

    // Función para abrir/cerrar la barra de accesibilidad
    function alternarBarraAccesibilidad(event) {
        if (event) event.stopPropagation();
        
        if (barraAccesibilidad) {
            const seMuestra = barraAccesibilidad.classList.toggle("mostrar-barra");
            
            // WCAG: Si la barra se abre, llevamos el foco de manera inteligente al primer botón
            if (seMuestra && botonesOpciones.length > 0) {
                // Pequeño retardo para dar tiempo a la animación CSS de opacidad
                setTimeout(() => {
                    botonesOpciones[0].focus();
                }, 150);
            }
        }
    }

    // Eventos de clic para abrir y cerrar
    if (btnAccesibilidadTexto) {
        btnAccesibilidadTexto.addEventListener("click", alternarBarraAccesibilidad);
    }
    if (btnAccesibilidadRedondo) {
        btnAccesibilidadRedondo.addEventListener("click", alternarBarraAccesibilidad);
    }

    // ========================================== //
    // NORMATIVAS WCAG & ACCESIBILIDAD AVANZADA   //
    // ========================================== //

    // 1. CERRAR AL HACER CLIC AFUERA (Evita que estorbe en la navegación general)
    document.addEventListener("click", function(event) {
        if (barraAccesibilidad && barraAccesibilidad.classList.contains("mostrar-barra")) {
            const clickDentroDeBarra = barraAccesibilidad.contains(event.target);
            const clickEnBotones = (btnAccesibilidadTexto && btnAccesibilidadTexto.contains(event.target)) || 
                                   (btnAccesibilidadRedondo && btnAccesibilidadRedondo.contains(event.target));
            
            if (!clickDentroDeBarra && !clickEnBotones) {
                barraAccesibilidad.classList.remove("mostrar-barra");
            }
        }
    });

    // 2. SOPORTE DE TECLADO GENERAL (Cerrar con la tecla Escape)
    document.addEventListener("keydown", function(event) {
        if (event.key === "Escape" && barraAccesibilidad && barraAccesibilidad.classList.contains("mostrar-barra")) {
            barraAccesibilidad.classList.remove("mostrar-barra");
            // Devolvemos el enfoque al botón principal para no perder al usuario del teclado
            if (btnAccesibilidadTexto) btnAccesibilidadTexto.focus();
        }
    });

    // 3. NAVEGACIÓN INTUITIVA CON FLECHAS DE DIRECCIÓN (← y →)
    if (contenedorBotones) {
        contenedorBotones.addEventListener("keydown", function(event) {
            // Buscamos cuál de los botones de la barra tiene el foco actualmente
            const indexActivo = botonesOpciones.indexOf(document.activeElement);
            
            // Si el foco no está en ninguno de nuestros botones, no hacemos nada
            if (indexActivo === -1) return;

            if (event.key === "ArrowRight") {
                // Evitamos el scroll horizontal por defecto del navegador
                event.preventDefault(); 
                
                // Calculamos el siguiente índice. Si llega al final, regresa al principio (bucle infinito circular)
                const siguienteIndex = (indexActivo + 1) % botonesOpciones.length;
                botonesOpciones[siguienteIndex].focus();
                
            } else if (event.key === "ArrowLeft") {
                event.preventDefault();
                
                // Calculamos el índice anterior. Si va hacia atrás del inicio, salta al último
                const anteriorIndex = (indexActivo - 1 + botonesOpciones.length) % botonesOpciones.length;
                botonesOpciones[anteriorIndex].focus();
            }
        });
    }
});