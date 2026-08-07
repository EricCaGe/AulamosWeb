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

    // ========================================== */
    // MODAL DE PERFIL DEL ADMIN                  */
    // ========================================== */
    const modalPerfil = document.getElementById('modalPerfil');
    const btnPerfil = document.querySelector('.btn-perfil');
    const cerrarPerfil = document.getElementById('cerrarPerfil');
    const cancelarPerfil = document.getElementById('cancelarPerfil');
    const fotoInput = document.getElementById('perfilFotoInput');
    const avatarImg = document.getElementById('perfilAvatar');

    // Abrir modal al hacer clic en el perfil
    if (btnPerfil && modalPerfil) {
        btnPerfil.addEventListener('click', function(e) {
            modalPerfil.classList.add('active');
            modalPerfil.style.display = 'flex';
            if(lectorActivo) leerEnVozAlta('Abriendo perfil de usuario');
        });
    }

    // Cerrar modal
    function cerrarModalPerfil() {
        if (modalPerfil) {
            modalPerfil.classList.remove('active');
            modalPerfil.style.display = 'none';
        }
    }

    if (cerrarPerfil) {
        cerrarPerfil.addEventListener('click', function() {
            cerrarModalPerfil();
            if(lectorActivo) leerEnVozAlta('Cerrando perfil');
        });
    }

    if (cancelarPerfil) {
        cancelarPerfil.addEventListener('click', function() {
            cerrarModalPerfil();
            if(lectorActivo) leerEnVozAlta('Cerrando perfil');
        });
    }

    // Cerrar al hacer clic fuera
    if (modalPerfil) {
        modalPerfil.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalPerfil();
                if(lectorActivo) leerEnVozAlta('Cerrando perfil');
            }
        });
    }

    // Cerrar con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalPerfil && modalPerfil.classList.contains('active')) {
            cerrarModalPerfil();
            if(lectorActivo) leerEnVozAlta('Cerrando perfil');
        }
    });

    // Subir foto de perfil (previsualización)
    if (fotoInput && avatarImg) {
        fotoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarImg.src = e.target.result;
                    if(lectorActivo) leerEnVozAlta('Foto de perfil actualizada');
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Enviar formulario de perfil con AJAX
    const formPerfil = document.getElementById('formPerfil');
    if (formPerfil) {
        formPerfil.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('logica/procesar_perfil.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if(lectorActivo) leerEnVozAlta('Perfil actualizado correctamente');
                    
                    // Actualizar nombre en el header
                    const nombreUsuario = document.querySelector('.user-name');
                    if (nombreUsuario && data.nombre_completo) {
                        nombreUsuario.textContent = data.nombre_completo;
                    }
                    // Actualizar el nombre en el saludo
                    const adminName = document.querySelector('.admin-name');
                    if (adminName && data.nombre_completo) {
                        adminName.textContent = data.nombre_completo;
                    }
                    
                    // Mostrar mensaje y cerrar modal
                    mostrarMensaje('✅ Perfil actualizado correctamente.');
                    setTimeout(() => {
                        cerrarModalPerfil();
                        location.reload();
                    }, 1500);
                } else {
                    mostrarMensaje('❌ ' + data.mensaje, true);
                }
            })
            .catch(error => {
                mostrarMensaje('❌ Error al guardar los cambios.', true);
                console.error(error);
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