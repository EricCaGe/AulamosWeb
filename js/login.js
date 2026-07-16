document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // Intercambio de Roles (Alumno / Docente)
    // ==========================================
    const rolButtons = document.querySelectorAll('.btn-role'); // Cambiado a '.btn-role' para coincidir con tus clases del HTML
    rolButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            rolButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Opcional: Detectar qué rol se seleccionó
            const esDocente = this.textContent.includes('Docente');
            console.log("Rol seleccionado:", esDocente ? "Docente" : "Alumno");
        });
    });

    // ==========================================
    // Interactividad de la Barra de Accesibilidad
    // ==========================================
    const accButtons = document.querySelectorAll('.acc-btn-opcion');
    
    accButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Al presionar un botón de accesibilidad, le alternamos la clase 'active-option'
            // Esto servirá para que el botón cambie de color (por ejemplo, ponerse morado o verde indicando que está encendido)
            this.classList.toggle('active-option');
            
            // Detectamos qué botón se presionó usando su texto
            const opcion = this.querySelector('span').textContent;
            console.log(`Funcionalidad activada/desactivada: ${opcion}`);
            
            // Aquí irá la lógica real de cada botón más adelante, por ejemplo:
            if (opcion === "Modo oscuro") {
                // document.body.classList.toggle('dark-mode');
            } else if (opcion === "Alto contraste") {
                // document.body.classList.toggle('high-contrast');
            }
        });
    });

    // Botón de "Abrir configuración" dentro de la barra
    const btnConfig = document.querySelector('.btn-abrir-config');
    if (btnConfig) {
        btnConfig.addEventListener('click', function() {
            console.log('Abriendo panel completo de configuración de accesibilidad...');
        });
    }
    
});