document.addEventListener("DOMContentLoaded", () => {
    
    // ==========================================
    // 1. ACCESIBILIDAD
    // ==========================================
    
    const btnContrast = document.getElementById("btn-contrast");
    const btnDarkMode = document.getElementById("btn-darkmode");
    const btnTextSize = document.getElementById("btn-text-size");

    // Alternar Alto Contraste
    if (btnContrast) {
        btnContrast.addEventListener("click", () => {
            document.body.classList.toggle("high-contrast-theme");
            // Quitamos el modo oscuro si se activa el alto contraste para que no choquen
            document.body.classList.remove("dark-theme"); 
        });
    }

    // Alternar Modo Oscuro
    if (btnDarkMode) {
        btnDarkMode.addEventListener("click", () => {
            document.body.classList.toggle("dark-theme");
            // Quitamos el alto contraste si se activa el modo oscuro
            document.body.classList.remove("high-contrast-theme");
        });
    }

    // Alternar Texto Grande
    if (btnTextSize) {
        btnTextSize.addEventListener("click", () => {
            document.body.classList.toggle("large-text-theme");
        });
    }

    // ==========================================
    // 2. ASISTENTE VIRTUAL
    // ==========================================
    
    const btnAsistente = document.getElementById("btn-asistente");
    
    if (btnAsistente) {
        btnAsistente.addEventListener("click", () => {
            // Aquí puedes lanzar un modal, un chatbot o una alerta simple
            alert("🤖 ¡Hola! Soy tu asistente virtual de Aulamos. ¿En qué te puedo ayudar hoy, Profesora Ana?");
        });
    }

    // ==========================================
    // 3. CALENDARIO DINÁMICO (BÁSICO)
    // ==========================================
    
    const prevMonthBtn = document.querySelector(".calendar-nav i.fa-chevron-left");
    const nextMonthBtn = document.querySelector(".calendar-nav i.fa-chevron-right");
    const monthTitle = document.querySelector(".month-title");

    // Fecha actual para iniciar el calendario
    let currentDate = new Date(); 

    // Función para actualizar el texto del mes y año
    function updateCalendarView(date) {
        const monthNames = [
            "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", 
            "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
        ];
        
        if (monthTitle) {
            monthTitle.textContent = `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
        }
        
        // Nota: Para repintar los números de los días (las bolitas) de forma 100% real,
        // se requeriría una lógica matemática más extensa para calcular el primer día del mes.
        // Por ahora, esto permite navegar entre los meses de forma visual en el título.
    }

    // Navegar al mes anterior
    if (prevMonthBtn) {
        prevMonthBtn.addEventListener("click", () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            updateCalendarView(currentDate);
        });
    }

    // Navegar al mes siguiente
    if (nextMonthBtn) {
        nextMonthBtn.addEventListener("click", () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            updateCalendarView(currentDate);
        });
    }

    // Inicializar el calendario con el mes actual al cargar la página
    updateCalendarView(currentDate);

});