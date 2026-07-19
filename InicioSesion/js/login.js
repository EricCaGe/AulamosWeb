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
        // ========================================== //
        // VERIFICAR SI EL FOCO ESTÁ EN UN INPUT
        // ========================================== //
        const activo = document.activeElement;
        const tagName = activo ? activo.tagName.toLowerCase() : '';
        const esCampoTexto = ['input', 'textarea', 'select'].includes(tagName);
        
        // ========================================== //
        // SI ESTAMOS EN UN CAMPO DE TEXTO, NO HACER NADA
        // Dejar que el input maneje las teclas normalmente
        // ========================================== //
        if (esCampoTexto) {
            return; // ✅ Las flechas mueven el cursor, las letras escriben
        }

        // ========================================== //
        // SOLO PROCESAMOS FLECHAS SI NO ESTAMOS EN UN INPUT
        // ========================================== //
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
    // (Mostrar/ocultar contraseña, roles, etc.) //
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

    // --- Selector de Rol ---
    const roleButtons = document.querySelectorAll('.btn-role');
    roleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            roleButtons.forEach(function(btn) {
                btn.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
});