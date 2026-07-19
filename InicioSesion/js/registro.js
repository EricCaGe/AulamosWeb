// ========================================== //
// NAVEGACIÓN GLOBAL CON FLECHAS ← →          //
// ========================================== //
document.addEventListener("DOMContentLoaded", function() {
    
    function obtenerElementosInteractivos() {
        const elementos = document.querySelectorAll(
            'button, a[href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        return Array.from(elementos).filter(function(el) {
            return el.offsetParent !== null && 
                   !el.disabled && 
                   el.getAttribute('aria-hidden') !== 'true';
        });
    }

    document.addEventListener('keydown', function(event) {
        const activo = document.activeElement;
        const tagName = activo ? activo.tagName.toLowerCase() : '';
        const esCampoTexto = ['input', 'textarea', 'select'].includes(tagName);
        
        // SI ESTAMOS EN UN CAMPO DE TEXTO, NO HACER NADA
        if (esCampoTexto) {
            return;
        }

        if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
            event.preventDefault();
            
            const interactivos = obtenerElementosInteractivos();
            if (interactivos.length === 0) return;
            
            const indexActual = interactivos.indexOf(activo);
            let nuevoIndex;
            
            if (indexActual === -1) {
                nuevoIndex = 0;
            } else if (event.key === 'ArrowRight') {
                nuevoIndex = (indexActual + 1) % interactivos.length;
            } else {
                nuevoIndex = (indexActual - 1 + interactivos.length) % interactivos.length;
            }
            
            interactivos[nuevoIndex].focus();
        }
    });

    // ========================================== //
    // FUNCIONES ESPECÍFICAS DE CADA PÁGINA      //
    // ========================================== //
    
    // --- Mostrar/Ocultar Contraseña (Registro) ---
    const btnVerPassword = document.getElementById('btn-ver-password');
    const inputPassword = document.getElementById('registro-password');

    if (btnVerPassword && inputPassword) {
        function togglePasswordVisibility() {
            const isPassword = inputPassword.type === 'password';
            inputPassword.type = isPassword ? 'text' : 'password';
            btnVerPassword.classList.toggle('fa-eye');
            btnVerPassword.classList.toggle('fa-eye-slash');
        }

        btnVerPassword.addEventListener('click', togglePasswordVisibility);
        btnVerPassword.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                togglePasswordVisibility();
            }
        });
    }

    // --- Alternar Roles ---
    const btnAlumno = document.getElementById('btn-registro-alumno');
    const btnDocente = document.getElementById('btn-registro-docente');

    if (btnAlumno && btnDocente) {
        btnAlumno.addEventListener('click', function() {
            btnDocente.classList.remove('active');
            btnAlumno.classList.add('active');
        });

        btnDocente.addEventListener('click', function() {
            btnAlumno.classList.remove('active');
            btnDocente.classList.add('active');
        });
    }
});