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

    // ========================================== //
    // ALTERNAR ROLES + ACTUALIZAR CAMPO OCULTO  //
    // ========================================== //
    const btnAlumno = document.getElementById('btn-registro-alumno');
    const btnDocente = document.getElementById('btn-registro-docente');
    const rolInput = document.getElementById('rol-input');

    if (btnAlumno && btnDocente && rolInput) {
        // Asegurar que el rol inicial sea "alumno"
        rolInput.value = 'Alumno';

        btnAlumno.addEventListener('click', function() {
            btnDocente.classList.remove('active');
            btnAlumno.classList.add('active');
            rolInput.value = 'Alumno';
        });

        btnDocente.addEventListener('click', function() {
            btnAlumno.classList.remove('active');
            btnDocente.classList.add('active');
            rolInput.value = 'Docente';
        });
    }

    // ========================================== //
    // VALIDACIÓN DEL FORMULARIO                  //
    // ========================================== //
    const formulario = document.querySelector('.login-form');
    
    if (formulario) {
        formulario.addEventListener('submit', function(event) {
            
            // Obtener campos
            const nombre = document.getElementById('registro-nombre');
            const apellidoPaterno = document.getElementById('registro-apellido-paterno');
            const apellidoMaterno = document.getElementById('registro-apellido-materno');
            const email = document.getElementById('registro-email');
            const password = document.getElementById('registro-password');
            const rol = document.getElementById('rol-input');
            
            let errores = [];
            
            // Validar nombre
            if (!nombre || nombre.value.trim() === '') {
                errores.push('El nombre es obligatorio.');
                nombre.style.borderColor = '#dc2626';
            } else {
                nombre.style.borderColor = '';
            }
            
            // Validar apellido paterno
            if (!apellidoPaterno || apellidoPaterno.value.trim() === '') {
                errores.push('El apellido paterno es obligatorio.');
                apellidoPaterno.style.borderColor = '#dc2626';
            } else {
                apellidoPaterno.style.borderColor = '';
            }
            
            // Validar apellido materno
            if (!apellidoMaterno || apellidoMaterno.value.trim() === '') {
                errores.push('El apellido materno es obligatorio.');
                apellidoMaterno.style.borderColor = '#dc2626';
            } else {
                apellidoMaterno.style.borderColor = '';
            }
            
            // Validar email
            if (!email || email.value.trim() === '') {
                errores.push('El correo electrónico es obligatorio.');
                email.style.borderColor = '#dc2626';
            } else if (!email.value.includes('@') || !email.value.includes('.')) {
                errores.push('El correo electrónico no es válido.');
                email.style.borderColor = '#dc2626';
            } else {
                email.style.borderColor = '';
            }
            
            // Validar contraseña
            if (!password || password.value.trim() === '') {
                errores.push('La contraseña es obligatoria.');
                password.style.borderColor = '#dc2626';
            } else if (password.value.length < 8) {
                errores.push('La contraseña debe tener al menos 8 caracteres.');
                password.style.borderColor = '#dc2626';
            } else {
                password.style.borderColor = '';
            }
            
            // Si hay errores, cancelar envío
            if (errores.length > 0) {
                event.preventDefault();
                
                let mensajeError = '⚠️ Por favor corrige los siguientes errores:\n\n';
                errores.forEach(function(error) {
                    mensajeError += '• ' + error + '\n';
                });
                alert(mensajeError);
                
                return false;
            }
            
            // Si no hay errores, el formulario se envía normalmente
            return true;
        });
    }
});