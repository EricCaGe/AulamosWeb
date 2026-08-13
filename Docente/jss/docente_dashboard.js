document.addEventListener("DOMContentLoaded", () => {
    
    // ==========================================
    // 1. ACCESIBILIDAD
    // ==========================================
    const btnContrast = document.getElementById("btn-contrast");
    const btnDarkMode = document.getElementById("btn-darkmode");
    const btnTextSize = document.getElementById("btn-text-size");

    if (btnContrast) {
        btnContrast.addEventListener("click", () => {
            document.body.classList.toggle("high-contrast-theme");
            document.body.classList.remove("dark-theme"); 
        });
    }

    if (btnDarkMode) {
        btnDarkMode.addEventListener("click", () => {
            document.body.classList.toggle("dark-theme");
            document.body.classList.remove("high-contrast-theme");
        });
    }

    if (btnTextSize) {
        btnTextSize.addEventListener("click", () => {
            document.body.classList.toggle("large-text-theme");
        });
    }

   

    // ==========================================
    // 3. CALENDARIO DINÁMICO
    // ==========================================
    const monthYearTitle = document.getElementById("month-year-title");
    const daysContainer = document.getElementById("calendar-days");
    const prevMonthBtn = document.getElementById("prev-month");
    const nextMonthBtn = document.getElementById("next-month");
    const prevYearBtn = document.getElementById("prev-year");
    const nextYearBtn = document.getElementById("next-year");

    // Validar que el calendario exista en la página actual
    if (monthYearTitle && daysContainer) {
        let currentDate = new Date(); 
        const today = new Date(); 
        const monthNames = [
            "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", 
            "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
        ];

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();

            monthYearTitle.textContent = `${monthNames[month]} ${year}`;
            daysContainer.innerHTML = "";

            const firstDayIndex = new Date(year, month, 1).getDay();
            const lastDay = new Date(year, month + 1, 0).getDate();

            for (let i = 0; i < firstDayIndex; i++) {
                const emptyDiv = document.createElement("div");
                emptyDiv.classList.add("day", "empty");
                daysContainer.appendChild(emptyDiv);
            }

            for (let i = 1; i <= lastDay; i++) {
                const dayDiv = document.createElement("div");
                dayDiv.classList.add("day");
                dayDiv.textContent = i;

                if (i === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                    dayDiv.classList.add("today");
                }

                dayDiv.addEventListener("click", () => {
                    console.log(`Seleccionaste el: ${i} de ${monthNames[month]} de ${year}`);
                });

                daysContainer.appendChild(dayDiv);
            }
        }

        // Botones de navegación
        if(prevMonthBtn) prevMonthBtn.addEventListener("click", () => { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(); });
        if(nextMonthBtn) nextMonthBtn.addEventListener("click", () => { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(); });
        if(prevYearBtn) prevYearBtn.addEventListener("click", () => { currentDate.setFullYear(currentDate.getFullYear() - 1); renderCalendar(); });
        if(nextYearBtn) nextYearBtn.addEventListener("click", () => { currentDate.setFullYear(currentDate.getFullYear() + 1); renderCalendar(); });

        renderCalendar();
    }
});