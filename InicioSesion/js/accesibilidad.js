// ========================================== */
// ACCESIBILIDAD - PANEL FLOTANTE             */
// ========================================== */

document.addEventListener('DOMContentLoaded', function() {
    
    const body = document.body;
    const panel = document.getElementById('panelAccesibilidad');
    const btnAbrir = document.querySelector('.btn-accessibility-round');
    const btnCerrar = document.getElementById('cerrarPanel');
    const btnModoOscuro = document.getElementById('btnModoOscuro');
    const btnAltoContraste = document.getElementById('btnAltoContraste');
    const btnTextoGrande = document.getElementById('btnTextoGrande');
    const btnLectorPantalla = document.getElementById('btnLectorPantalla');
    const btnRestablecer = document.getElementById('btnRestablecer');

    // ========================================== */
    // 1. TOGGLE PANEL                           */
    // ========================================== */
    function togglePanel() {
        if (panel) {
            panel.classList.toggle('active');
        }
    }

    function cerrarPanel() {
        if (panel) {
            panel.classList.remove('active');
        }
    }

    if (btnAbrir) {
        btnAbrir.addEventListener('click', function(e) {
            e.stopPropagation();
            togglePanel();
        });
    }

    if (btnCerrar) {
        btnCerrar.addEventListener('click', cerrarPanel);
    }

    document.addEventListener('click', function(e) {
        if (panel && panel.classList.contains('active')) {
            if (!panel.contains(e.target) && !btnAbrir.contains(e.target)) {
                cerrarPanel();
            }
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && panel && panel.classList.contains('active')) {
            cerrarPanel();
        }
    });

    // ========================================== */
    // 2. FUNCIONES DE ACCESIBILIDAD             */
    // ========================================== */

    function toggleClase(elemento, clase, boton) {
        elemento.classList.toggle(clase);
        const activo = elemento.classList.contains(clase);
        localStorage.setItem(clase, activo ? 'true' : 'false');
        if (boton) {
            boton.classList.toggle('active');
        }
        return activo;
    }

    function cargarPreferencias() {
        const preferencias = ['modo-oscuro', 'alto-contraste', 'texto-grande'];
        const botones = {
            'modo-oscuro': btnModoOscuro,
            'alto-contraste': btnAltoContraste,
            'texto-grande': btnTextoGrande
        };
        
        preferencias.forEach(clase => {
            if (localStorage.getItem(clase) === 'true') {
                body.classList.add(clase);
                if (botones[clase]) {
                    botones[clase].classList.add('active');
                }
            }
        });
    }

    // ========================================== */
    // 3. EVENTOS DE LOS BOTONES                 */
    // ========================================== */

    if (btnModoOscuro) {
        btnModoOscuro.addEventListener('click', function() {
            toggleClase(body, 'modo-oscuro', this);
        });
    }

    if (btnAltoContraste) {
        btnAltoContraste.addEventListener('click', function() {
            toggleClase(body, 'alto-contraste', this);
        });
    }

    if (btnTextoGrande) {
        btnTextoGrande.addEventListener('click', function() {
            toggleClase(body, 'texto-grande', this);
        });
    }

    if (btnLectorPantalla) {
        btnLectorPantalla.addEventListener('click', function() {
            if (typeof lector !== 'undefined' && lector) {
                const estado = lector.toggle();
                this.classList.toggle('active');
                const icon = this.querySelector('i');
                if (icon) {
                    icon.className = estado ? 'fa-solid fa-volume-high' : 'fa-solid fa-volume-xmark';
                }
            } else {
                alert('🔊 Lector de pantalla activado (simulación)');
                this.classList.toggle('active');
            }
        });
        
        if (localStorage.getItem('lector_pantalla') === 'true') {
            btnLectorPantalla.classList.add('active');
            const icon = btnLectorPantalla.querySelector('i');
            if (icon) {
                icon.className = 'fa-solid fa-volume-high';
            }
        }
    }

    if (btnRestablecer) {
        btnRestablecer.addEventListener('click', function() {
            body.classList.remove('modo-oscuro', 'alto-contraste', 'texto-grande');
            document.querySelectorAll('.btn-accesibilidad-opcion').forEach(btn => {
                btn.classList.remove('active');
            });
            ['modo-oscuro', 'alto-contraste', 'texto-grande', 'lector_pantalla'].forEach(key => {
                localStorage.removeItem(key);
            });
            if (typeof lector !== 'undefined' && lector && lector.activado) {
                lector.desactivar();
                if (btnLectorPantalla) {
                    btnLectorPantalla.classList.remove('active');
                    const icon = btnLectorPantalla.querySelector('i');
                    if (icon) {
                        icon.className = 'fa-solid fa-volume-high';
                    }
                }
            }
            cerrarPanel();
            alert('✅ Todas las preferencias de accesibilidad han sido restablecidas.');
        });
    }

    cargarPreferencias();

    window.togglePanel = togglePanel;
    window.cerrarPanel = cerrarPanel;

});