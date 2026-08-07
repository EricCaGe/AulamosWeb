// ========================================== */
// ACCESIBILIDAD - PANEL FLOTANTE             */
// ========================================== */

// ========================================== */
// CARGAR PREFERENCIAS DEL SERVIDOR          */
// ========================================== */

function cargarPreferenciasServidor() {
    if (!window.preferenciasServidor) {
        console.log('⚠️ No hay preferencias del servidor');
        return;
    }
    
    const data = window.preferenciasServidor;
    console.log('🔍 Cargando preferencias del servidor:', data);
    
    const body = document.body;
    
    // Alto contraste con colores personalizados
    if (data.alto_contraste == 1) {
        body.classList.add('alto-contraste');
        body.classList.add('fondo-' + data.contraste_fondo);
        body.classList.add('color-' + data.contraste_color);
        localStorage.setItem('alto-contraste', 'true');
        localStorage.setItem('contraste_fondo', data.contraste_fondo);
        localStorage.setItem('contraste_color', data.contraste_color);
        console.log('✅ Alto contraste aplicado:', data.contraste_fondo, data.contraste_color);
    }
    
    // Modo oscuro
    if (data.modo_oscuro == 1) {
        body.classList.add('modo-oscuro');
        localStorage.setItem('modo-oscuro', 'true');
        console.log('✅ Modo oscuro aplicado');
    }
    
    // Texto grande
    if (data.tamano_texto === 'grande') {
        body.classList.add('texto-grande');
        localStorage.setItem('texto-grande', 'true');
        console.log('✅ Texto grande aplicado');
    }
}

// ========================================== */
// CARGAR PREFERENCIAS LOCALES                */
// ========================================== */

function cargarPreferenciasLocales() {
    const body = document.body;
    
    // Solo cargar si no hay preferencias del servidor
    if (window.preferenciasServidor) return;
    
    if (localStorage.getItem('modo-oscuro') === 'true') {
        body.classList.add('modo-oscuro');
    }
    if (localStorage.getItem('alto-contraste') === 'true') {
        body.classList.add('alto-contraste');
        const fondo = localStorage.getItem('contraste_fondo') || 'negro';
        const color = localStorage.getItem('contraste_color') || 'azul';
        body.classList.add('fondo-' + fondo);
        body.classList.add('color-' + color);
    }
    if (localStorage.getItem('texto-grande') === 'true') {
        body.classList.add('texto-grande');
    }
    if (localStorage.getItem('lector_pantalla') === 'true') {
        const btnLector = document.getElementById('btnLectorPantalla');
        if (btnLector) {
            btnLector.classList.add('active');
            const icon = btnLector.querySelector('i');
            if (icon) {
                icon.className = 'fa-solid fa-volume-high';
            }
        }
    }
}

// ========================================== */
// DOCUMENT READY                            */
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
            // Si el alto contraste está activo, lo quitamos todo
            if (body.classList.contains('alto-contraste')) {
                body.classList.remove('alto-contraste');
                body.classList.remove('fondo-blanco', 'fondo-negro');
                ['azul', 'amarillo', 'verde', 'rojo', 'naranja', 'morado'].forEach(c => {
                    body.classList.remove('color-' + c);
                });
                this.classList.remove('active');
                localStorage.setItem('alto-contraste', 'false');
            } else {
                // Si NO está activo, lo activamos con los colores personalizados
                body.classList.add('alto-contraste');
                this.classList.add('active');
                localStorage.setItem('alto-contraste', 'true');
                
                // ✅ USAR COLORES DE LA CONFIGURACIÓN (desde SESSION)
                // Primero intentar desde el servidor, si no, desde localStorage
                let fondo = window.preferenciasServidor?.contraste_fondo || localStorage.getItem('contraste_fondo') || 'negro';
                let color = window.preferenciasServidor?.contraste_color || localStorage.getItem('contraste_color') || 'azul';
                
                body.classList.remove('fondo-blanco', 'fondo-negro');
                body.classList.add('fondo-' + fondo);
                
                ['azul', 'amarillo', 'verde', 'rojo', 'naranja', 'morado'].forEach(c => {
                    body.classList.remove('color-' + c);
                });
                body.classList.add('color-' + color);
                
                // Guardar en localStorage también
                localStorage.setItem('contraste_fondo', fondo);
                localStorage.setItem('contraste_color', color);
            }
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
            body.classList.remove('fondo-blanco', 'fondo-negro');
            ['azul', 'amarillo', 'verde', 'rojo', 'naranja', 'morado'].forEach(c => {
                body.classList.remove('color-' + c);
            });
            document.querySelectorAll('.btn-accesibilidad-opcion').forEach(btn => {
                btn.classList.remove('active');
            });
            ['modo-oscuro', 'alto-contraste', 'texto-grande', 'lector_pantalla', 'contraste_fondo', 'contraste_color'].forEach(key => {
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

    // ========================================== */
    // 4. MODAL PERSONALIZAR ALTO CONTRASTE      */
    // ========================================== */

    console.log('🔍 Inicializando modal de contraste...');

    const modalContraste = document.getElementById('modalContraste');
    const btnPersonalizar = document.getElementById('btnPersonalizarContraste');
    const cerrarModalBtn = document.getElementById('cerrarModalContraste');
    const cancelarModalBtn = document.getElementById('cancelarContraste');
    const aplicarModalBtn = document.getElementById('aplicarContraste');

    // Variables para guardar selecciones
    let seleccionFondo = localStorage.getItem('contraste_fondo') || 'negro';
    let seleccionColor = localStorage.getItem('contraste_color') || 'azul';

    // Función para abrir modal
    function abrirModalContraste() {
        console.log('🟣 Abriendo modal...');
        if (!modalContraste) {
            console.error('❌ No existe el modal #modalContraste');
            alert('Error: No se encontró el modal. Revisa el HTML.');
            return;
        }
        // Cargar selecciones actuales
        seleccionFondo = localStorage.getItem('contraste_fondo') || 'negro';
        seleccionColor = localStorage.getItem('contraste_color') || 'azul';
        
        // Marcar botones activos
        document.querySelectorAll('.btn-opt[data-fondo]').forEach(b => {
            b.classList.toggle('active', b.dataset.fondo === seleccionFondo);
        });
        document.querySelectorAll('.btn-opt[data-color]').forEach(b => {
            b.classList.toggle('active', b.dataset.color === seleccionColor);
        });
        
        actualizarVistaPrevia(seleccionFondo, seleccionColor);
        modalContraste.classList.add('active');
        console.log('✅ Modal abierto');
    }

    // Función para cerrar modal
    function cerrarModalContraste() {
        console.log('🔴 Cerrando modal...');
        if (modalContraste) {
            modalContraste.classList.remove('active');
        }
    }

    // Evento del botón personalizar
    if (btnPersonalizar) {
        btnPersonalizar.addEventListener('click', function(e) {
            console.log('🖱️ Click en Personalizar alto contraste');
            e.preventDefault();
            e.stopPropagation();
            abrirModalContraste();
        });
    }

    // Eventos para cerrar
    if (cerrarModalBtn) {
        cerrarModalBtn.addEventListener('click', cerrarModalContraste);
    }

    if (cancelarModalBtn) {
        cancelarModalBtn.addEventListener('click', cerrarModalContraste);
    }

    // Cerrar al hacer clic fuera del modal
    if (modalContraste) {
        modalContraste.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalContraste();
            }
        });
    }

    // Cerrar con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalContraste && modalContraste.classList.contains('active')) {
            cerrarModalContraste();
        }
    });

    // ========================================== */
    // 5. SELECCIONAR OPCIONES                    */
    // ========================================== */

    // Seleccionar fondo
    document.querySelectorAll('.btn-opt[data-fondo]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.btn-opt[data-fondo]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            seleccionFondo = this.dataset.fondo;
            actualizarVistaPrevia(seleccionFondo, seleccionColor);
        });
    });

    // Seleccionar color
    document.querySelectorAll('.btn-opt[data-color]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.btn-opt[data-color]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            seleccionColor = this.dataset.color;
            actualizarVistaPrevia(seleccionFondo, seleccionColor);
        });
    });

    // Vista previa
    function actualizarVistaPrevia(fondo, color) {
        const vista = document.getElementById('vistaPrevia');
        if (!vista) return;
        
        const colores = {
            'azul': '#3b82f6',
            'amarillo': '#eab308',
            'verde': '#22c55e',
            'rojo': '#ef4444',
            'naranja': '#f97316',
            'morado': '#8b5cf6'
        };
        
        const colorHex = colores[color] || '#5a189a';
        const fondoHex = fondo === 'blanco' ? '#ffffff' : '#000000';
        const textoHex = fondo === 'blanco' ? '#000000' : '#ffffff';
        
        vista.style.background = fondoHex;
        vista.style.borderColor = textoHex;
        vista.style.color = textoHex;
        
        const texto = vista.querySelector('.vista-texto');
        if (texto) texto.style.color = textoHex;
        
        const boton = vista.querySelector('.vista-boton');
        if (boton) {
            boton.style.background = colorHex;
            boton.style.color = fondo === 'blanco' ? '#000000' : '#ffffff';
        }
        
        const badge = vista.querySelector('.vista-badge');
        if (badge) {
            badge.style.background = colorHex;
            badge.style.color = fondo === 'blanco' ? '#000000' : '#ffffff';
        }
    }

    // Aplicar cambios
    if (aplicarModalBtn) {
        aplicarModalBtn.addEventListener('click', function() {
            console.log('🟢 Aplicando cambios...');
            
            localStorage.setItem('contraste_fondo', seleccionFondo);
            localStorage.setItem('contraste_color', seleccionColor);
            
            body.classList.remove('fondo-blanco', 'fondo-negro');
            body.classList.add('fondo-' + seleccionFondo);
            
            ['azul', 'amarillo', 'verde', 'rojo', 'naranja', 'morado'].forEach(c => {
                body.classList.remove('color-' + c);
            });
            body.classList.add('color-' + seleccionColor);
            
            if (!body.classList.contains('alto-contraste')) {
                body.classList.add('alto-contraste');
                if (btnAltoContraste) btnAltoContraste.classList.add('active');
                localStorage.setItem('alto-contraste', 'true');
            }
            
            cerrarModalContraste();
            
            // Feedback
            const mensaje = document.createElement('div');
            mensaje.style.cssText = `
                position: fixed;
                bottom: 120px;
                left: 50%;
                transform: translateX(-50%);
                background: #22c55e;
                color: white;
                padding: 12px 24px;
                border-radius: 12px;
                font-weight: 600;
                z-index: 999999;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                animation: fadeInUp 0.3s ease;
            `;
            mensaje.textContent = '✅ Alto contraste personalizado aplicado';
            document.body.appendChild(mensaje);
            
            setTimeout(() => {
                mensaje.style.opacity = '0';
                mensaje.style.transition = 'opacity 0.5s';
                setTimeout(() => mensaje.remove(), 500);
            }, 3000);
        });
    }

    // ========================================== */
    // 6. INICIALIZAR                            */
    // ========================================== */
    
    // Primero cargar preferencias del servidor
    cargarPreferenciasServidor();
    
    // Luego cargar preferencias locales (si no hay del servidor)
    cargarPreferenciasLocales();
    
    // Inicializar vista previa
    setTimeout(() => {
        actualizarVistaPrevia(seleccionFondo, seleccionColor);
    }, 200);

    console.log('✅ Accesibilidad cargada correctamente');
});