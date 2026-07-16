document.addEventListener("DOMContentLoaded", function() {
    
    // 1. FUNCIONALIDAD PARA EL BOTÓN MODO OSCURO
    const btnDarkMode = document.getElementById("btn-darkmode");
    
    btnDarkMode.addEventListener("click", function() {
        // Intercambia la clase 'dark-theme' en el body
        document.body.classList.toggle("dark-theme");
        console.log("Modo oscuro alternado");
    });

    // 2. FUNCIONALIDAD PARA EL BOTÓN ALTO CONTRASTE
    const btnContrast = document.getElementById("btn-contrast");
    btnContrast.addEventListener("click", function() {
        alert("Activando filtros visuales de alto contraste para accesibilidad de baja visión.");
    });

    // 3. FUNCIONALIDAD PARA EL BOTÓN TEXTO GRANDE
    const btnTextSize = document.getElementById("btn-text-size");
    btnTextSize.addEventListener("click", function() {
        document.body.classList.toggle("large-text");
        console.log("Tamaño de texto alternado");
    });

    // 4. EXTRA: SALUDO DEL ASISTENTE VIRTUAL
    const btnAsistente = document.getElementById("btn-asistente");
    btnAsistente.addEventListener("click", function() {
        alert("¡Hola! Soy tu Asistente Virtual de Aulamos. ¿En qué actividad te puedo ayudar hoy?");
    });

});