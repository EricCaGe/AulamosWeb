document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================== */
    // ELEMENTOS                                  */
    // ========================================== */
    const form = document.querySelector('.config-card form');
    const btnIdioma = document.getElementById('btnIdioma');
    const idiomaTexto = document.getElementById('idiomaTexto');
    
    // ========================================== */
    // FUNCIÓN PARA MOSTRAR MENSAJES              */
    // ========================================== */
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

    // ========================================== */
    // GUARDAR CONFIGURACIÓN                      */
    // ========================================== */
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
                
                setTimeout(() => {
                    location.reload();
                }, 1000);
            })
            .catch(error => {
                mostrarMensaje('❌ Error al guardar la configuración.', true);
            });
        });
    }

    // ========================================== */
    // CAMBIO DE IDIOMA                           */
    // ========================================== */
    if (btnIdioma && idiomaTexto) {
        // Cargar idioma actual desde localStorage
        const idiomaActual = localStorage.getItem('idioma') || 'es';
        idiomaTexto.textContent = idiomaActual === 'es' ? 'ES' : 'EN';
        
        btnIdioma.addEventListener('click', function() {
            const idiomaActual = localStorage.getItem('idioma') || 'es';
            const nuevoIdioma = idiomaActual === 'es' ? 'en' : 'es';
            
            // Guardar en localStorage
            localStorage.setItem('idioma', nuevoIdioma);
            idiomaTexto.textContent = nuevoIdioma === 'es' ? 'ES' : 'EN';
            
            // Cambiar el radio en el formulario
            if (form) {
                const radios = form.querySelectorAll('input[name="idioma"]');
                radios.forEach(radio => {
                    radio.checked = radio.value === nuevoIdioma;
                });
                
                // Guardar automáticamente
                const formData = new FormData(form);
                fetch('logica/procesar_configuracion.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => {
                    mostrarMensaje('✅ Idioma cambiado a ' + (nuevoIdioma === 'es' ? 'Español' : 'Inglés'));
                    setTimeout(() => {
                        location.reload();
                    }, 800);
                })
                .catch(error => {
                    mostrarMensaje('❌ Error al cambiar idioma.', true);
                });
            }
        });
    }

});