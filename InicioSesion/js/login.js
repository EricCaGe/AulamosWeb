// ========================================== //
// NAVEGACIÓN GLOBAL CON FLECHAS ← →          //
// ========================================== //
document.addEventListener("DOMContentLoaded", function() {
    
    // ========================================== //
    // 1. FUNCIÓN PARA OBTENER ELEMENTOS INTERACTIVOS
    // ========================================== //
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

    // ========================================== //
    // 2. NAVEGACIÓN GLOBAL CON FLECHAS ← →
    // ========================================== //
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
    // 3. FUNCIONES ESPECÍFICAS DE CADA PÁGINA   //
    // ========================================== //
    
    // --- Mostrar/Ocultar Contraseña ---
    const togglePassword = document.querySelector('.toggle-password-icon');
    const passwordInput = document.getElementById('login-password');

    if (togglePassword && passwordInput) {
        function togglePasswordVisibility() {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            togglePassword.classList.toggle('fa-eye');
            togglePassword.classList.toggle('fa-eye-slash');
        }

        togglePassword.addEventListener('click', togglePasswordVisibility);
        togglePassword.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                togglePasswordVisibility();
            }
        });
    }

    // ========================================== //
    // SELECTOR DE ROL + ACTUALIZAR CAMPO OCULTO //
    // ========================================== //
    const roleButtons = document.querySelectorAll('.btn-role');
    const rolInput = document.getElementById('rol-input');
    
    if (roleButtons.length > 0 && rolInput) {
        // Asegurar que el rol inicial sea "alumno"
        rolInput.value = 'Alumno';

        roleButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                // Quitar active de todos
                roleButtons.forEach(function(btn) {
                    btn.classList.remove('active');
                });
                // Activar el seleccionado
                this.classList.add('active');
                // Actualizar campo oculto
                const rol = this.getAttribute('data-rol');
                rolInput.value = rol;
                console.log('Rol seleccionado:', rol);
            });
        });
    }

    // ========================================== //
    // VALIDACIÓN DEL FORMULARIO                  //
    // ========================================== //
    const formulario = document.querySelector('.login-form');
    
    if (formulario) {
        formulario.addEventListener('submit', function(event) {
            
            // Obtener campos
            const email = document.getElementById('login-email');
            const password = document.getElementById('login-password');
            const rol = document.getElementById('rol-input');
            
            let errores = [];
            
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
            } else {
                password.style.borderColor = '';
            }
            
            
            // Validar rol
           // Validar rol
if (!rol || rol.value === '' || rol.value === null) {
    errores.push('Debes seleccionar un rol (Alumno, Docente, Investigador o Administrador).');
} else {
    console.log('Rol a enviar:', rol.value);
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
            console.log('✅ Formulario validado correctamente. Enviando...');
            return true;
        });
    }
});
