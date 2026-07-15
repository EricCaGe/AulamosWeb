document.addEventListener('DOMContentLoaded', function() {
    
    const rolButtons = document.querySelectorAll('.btn-rol');
    rolButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            rolButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    const accessibilityBtn = document.querySelector('.btn-accessibility');
    if (accessibilityBtn) {
        accessibilityBtn.addEventListener('click', function() {
            console.log('Accesibilidad (diseño)');
        });
    }
    
});