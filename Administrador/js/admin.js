document.addEventListener("DOMContentLoaded", function() {

    // ==========================================
    // VARIABLE GLOBAL (Controla si el lector está activo)
    // ==========================================
    let lectorActivo = false; 

    // ==========================================
    // LECTOR DE PANTALLA (Usa el div del HTML)
    // ==========================================
    const lectorDiv = document.getElementById('lector-anuncios');

    function leerEnVozAlta(mensaje) {
        // Si el lector está apagado o no existe el div, no hacer nada
        if (!lectorActivo || !lectorDiv) return;

        // Truco infalible: Limpiar y forzar relectura con setTimeout
        lectorDiv.textContent = ''; 
        setTimeout(() => {
            lectorDiv.textContent = mensaje;
        }, 50);
    }

    // ==========================================
    // BOTÓN DE ENCENDIDO/APAGADO
    // ==========================================
    const btnLectorPantalla = document.getElementById('btn-lector-pantalla');
    if (btnLectorPantalla) {
        btnLectorPantalla.addEventListener('click', function() {
            lectorActivo = !lectorActivo; 
            this.classList.toggle('active');
            
            if (lectorActivo) {
                leerEnVozAlta('Modo lector activado');
            }
        });
    }

    // ==========================================
    // ACCESIBILIDAD (Tus funciones originales)
    // ==========================================
    const body = document.body;

    function toggleClase(elemento, clase) {
        elemento.classList.toggle(clase);
        const activo = elemento.classList.contains(clase);
        localStorage.setItem(clase, activo ? 'true' : 'false');
    }

    function cargarPreferencias() {
        const preferencias = ['modo-oscuro', 'alto-contraste', 'texto-grande'];
        preferencias.forEach(clase => {
            if (localStorage.getItem(clase) === 'true') {
                body.classList.add(clase);
                const map = {
                    'modo-oscuro': 'btn-darkmode',
                    'alto-contraste': 'btn-contrast',
                    'texto-grande': 'btn-text-size'
                };
                const btn = document.getElementById(map[clase]);
                if (btn) btn.classList.add('active');
            }
        });
    }
    cargarPreferencias();

    const btnDark = document.getElementById('btn-darkmode');
    if (btnDark) {
        btnDark.addEventListener('click', function() {
            toggleClase(body, 'modo-oscuro');
            this.classList.toggle('active');
            if(lectorActivo) leerEnVozAlta('Modo oscuro activado');
        });
    }

    const btnContrast = document.getElementById('btn-contrast');
    if (btnContrast) {
        btnContrast.addEventListener('click', function() {
            toggleClase(body, 'alto-contraste');
            this.classList.toggle('active');
            if(lectorActivo) leerEnVozAlta('Alto contraste activado');
        });
    }

    const btnText = document.getElementById('btn-text-size');
    if (btnText) {
        btnText.addEventListener('click', function() {
            toggleClase(body, 'texto-grande');
            this.classList.toggle('active');
            if(lectorActivo) leerEnVozAlta('Texto grande activado');
        });
    }

    // ==========================================
    // ASISTENTE Y NOTIFICACIONES (Sin alert)
    // ==========================================
    const btnAsistente = document.getElementById('btn-asistente');
    if (btnAsistente) {
        btnAsistente.addEventListener('click', function() {
            if(lectorActivo) leerEnVozAlta('Abriendo asistente virtual');
        });
    }

    const iconBell = document.querySelector('.icon-bell');
    if (iconBell) {
        iconBell.addEventListener('click', function() {
            if(lectorActivo) leerEnVozAlta('No tienes notificaciones nuevas.');
        });
    }

    const btnAccHeader = document.querySelector('.btn-accessibility-header');
    if (btnAccHeader) {
        btnAccHeader.addEventListener('click', function() {
            if(lectorActivo) leerEnVozAlta('Abriendo panel de accesibilidad.');
        });
    }

    document.querySelectorAll('.acc-opt-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const texto = this.querySelector('span')?.textContent || 'Opción';
            if(lectorActivo) leerEnVozAlta(`${texto} activado.`);
        });
    });

    const btnConfig = document.querySelector('.btn-open-config');
    if (btnConfig) {
        btnConfig.addEventListener('click', function() {
            if(lectorActivo) leerEnVozAlta('Abriendo configuración completa.');
        });
    }

});

// ========================================== */
// MENSAJES TEMPORALES                        */
// ========================================== */
setTimeout(function() {
    const mensajes = document.querySelectorAll('.mensaje');
    mensajes.forEach(function(mensaje) {
        mensaje.style.transition = 'opacity 0.5s ease';
        mensaje.style.opacity = '0';
        setTimeout(function() {
            mensaje.style.display = 'none';
        }, 500);
    });
}, 4000);