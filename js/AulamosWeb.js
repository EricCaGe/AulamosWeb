// ==========================================
// AulamosWeb.js - JS DE LA PÁGINA DE INICIO
// ==========================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // BOTÓN DE ACCESIBILIDAD
    // ==========================================
    const accessibilityBtn = document.querySelector('.btn-accessibility');
    
    if (accessibilityBtn) {
        accessibilityBtn.addEventListener('click', function() {
            document.body.classList.toggle('modo-accesible');
            console.log('Modo accesibilidad: ' + 
                (document.body.classList.contains('modo-accesible') ? 'ACTIVADO' : 'DESACTIVADO'));
        });
    }
    
    // ==========================================
    // BOTÓN INICIAR SESIÓN
    // ==========================================
    const loginBtn = document.querySelector('.btn-login');
    if (loginBtn) {
        loginBtn.addEventListener('click', function() {
            console.log('Redirigir a login');
            // window.location.href = 'login.html';
        });
    }
    
    // ==========================================
    // BOTÓN CREAR CUENTA
    // ==========================================
    const registerBtn = document.querySelector('.btn-register');
    if (registerBtn) {
        registerBtn.addEventListener('click', function() {
            console.log('Redirigir a registro');
            // window.location.href = 'registro.html';
        });
    }
    
});