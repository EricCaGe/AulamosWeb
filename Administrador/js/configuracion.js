document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================== */
    // APLICAR PREFERENCIAS AL GUARDAR           */
    // ========================================== */
    const form = document.querySelector('.config-card form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('logica/procesar_configuracion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                mostrarMensaje('✅ Configuración guardada correctamente.');
                
                // Recargar página para aplicar cambios
                setTimeout(() => {
                    location.reload();
                }, 1000);
            })
            .catch(error => {
                mostrarMensaje('❌ Error al guardar la configuración.', true);
            });
        });
    }

    function mostrarMensaje(texto, esError = false) {
        const mensaje = document.querySelector('.mensaje');
        if (mensaje) {
            mensaje.textContent = texto;
            mensaje.style.background = esError ? '#fee2e2' : '#dcfce7';
            mensaje.style.color = esError ? '#991b1b' : '#166534';
            mensaje.style.borderLeft = esError ? '4px solid #dc2626' : '4px solid #22c55e';
            mensaje.style.display = 'block';
            
            setTimeout(() => {
                mensaje.style.opacity = '0';
                setTimeout(() => {
                    mensaje.style.display = 'none';
                    mensaje.style.opacity = '1';
                }, 500);
            }, 3000);
        }
    }

});