// ============================================
// ACCESIBILIDAD - AULAMOS
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const body = document.body;
    
    // ============================================
    // 1. CREAR ELEMENTOS DEL PANEL
    // ============================================
    
    // Botón toggle (flotante)
    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'accesibilidad-toggle';
    toggleBtn.innerHTML = '♿';
    toggleBtn.setAttribute('aria-label', 'Abrir panel de accesibilidad');
    document.body.appendChild(toggleBtn);
    
    // Indicador de accesibilidad activa
    const indicador = document.createElement('div');
    indicador.className = 'accesibilidad-indicador';
    indicador.textContent = '♿ Accesibilidad activa';
    document.body.appendChild(indicador);
    
    // Panel de accesibilidad
    const panel = document.createElement('div');
    panel.className = 'accesibilidad-panel';
    panel.innerHTML = `
        <button class="cerrar-panel" aria-label="Cerrar panel">✕</button>
        <h3>♿ Accesibilidad</h3>
        
        <!-- Tamaño de texto -->
        <div class="accesibilidad-grupo">
            <label>Tamaño de texto</label>
            <div class="tamano-opciones">
                <button data-tamano="normal" class="activo">Normal</button>
                <button data-tamano="grande">Grande</button>
                <button data-tamano="muy-grande">Muy grande</button>
            </div>
        </div>
        
        <!-- Contraste -->
        <div class="accesibilidad-grupo">
            <label>Contraste</label>
            <div class="contraste-opciones">
                <button data-contraste="normal" class="activo">Normal</button>
                <button data-contraste="alto">Alto</button>
            </div>
        </div>
        
        <!-- Modo oscuro -->
        <div class="accesibilidad-grupo">
            <div class="accesibilidad-opcion">
                <span>🌙 Modo oscuro</span>
                <label class="switch">
                    <input type="checkbox" id="modo-oscuro">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        
        <!-- Fuente dislexia -->
        <div class="accesibilidad-grupo">
            <div class="accesibilidad-opcion">
                <span>📖 Fuente para dislexia</span>
                <label class="switch">
                    <input type="checkbox" id="fuente-dislexia">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        
        <!-- Subtítulos -->
        <div class="accesibilidad-grupo">
            <div class="accesibilidad-opcion">
                <span>🔊 Subtítulos</span>
                <label class="switch">
                    <input type="checkbox" id="subtitulos">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        
        <!-- Lector de pantalla -->
        <div class="accesibilidad-grupo">
            <div class="accesibilidad-opcion">
                <span>🔊 Lector de pantalla</span>
                <label class="switch">
                    <input type="checkbox" id="lector-pantalla">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        
        <!-- Navegación por teclado -->
        <div class="accesibilidad-grupo">
            <div class="accesibilidad-opcion">
                <span>⌨️ Navegación por teclado</span>
                <label class="switch">
                    <input type="checkbox" id="navegacion-teclado">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        
        <button id="reset-accesibilidad" style="width:100%;margin-top:10px;padding:8px;background:#e74c3c;color:white;border:none;border-radius:6px;cursor:pointer;">
            🔄 Restablecer todo
        </button>
    `;
    document.body.appendChild(panel);
    
    // ============================================
    // 2. FUNCIONES DE ACCESIBILIDAD
    // ============================================
    
    // Guardar preferencias en localStorage
    function guardarPreferencias() {
        const prefs = {
            tamano: document.querySelector('.tamano-opciones button.activo')?.dataset.tamano || 'normal',
            contraste: document.querySelector('.contraste-opciones button.activo')?.dataset.contraste || 'normal',
            modoOscuro: document.getElementById('modo-oscuro').checked,
            fuenteDislexia: document.getElementById('fuente-dislexia').checked,
            subtitulos: document.getElementById('subtitulos').checked,
            lectorPantalla: document.getElementById('lector-pantalla').checked,
            navegacionTeclado: document.getElementById('navegacion-teclado').checked
        };
        localStorage.setItem('accesibilidad_prefs', JSON.stringify(prefs));
    }
    
    // Cargar preferencias guardadas
    function cargarPreferencias() {
        const saved = localStorage.getItem('accesibilidad_prefs');
        if (!saved) return;
        
        try {
            const prefs = JSON.parse(saved);
            
            // Tamaño de texto
            document.querySelectorAll('.tamano-opciones button').forEach(btn => {
                btn.classList.remove('activo');
                if (btn.dataset.tamano === prefs.tamano) {
                    btn.classList.add('activo');
                }
            });
            aplicarTamano(prefs.tamano);
            
            // Contraste
            document.querySelectorAll('.contraste-opciones button').forEach(btn => {
                btn.classList.remove('activo');
                if (btn.dataset.contraste === prefs.contraste) {
                    btn.classList.add('activo');
                }
            });
            aplicarContraste(prefs.contraste);
            
            // Modo oscuro
            document.getElementById('modo-oscuro').checked = prefs.modoOscuro;
            aplicarModoOscuro(prefs.modoOscuro);
            
            // Fuente dislexia
            document.getElementById('fuente-dislexia').checked = prefs.fuenteDislexia;
            aplicarFuenteDislexia(prefs.fuenteDislexia);
            
            // Subtítulos
            document.getElementById('subtitulos').checked = prefs.subtitulos;
            aplicarSubtitulos(prefs.subtitulos);
            
            // Lector de pantalla
            document.getElementById('lector-pantalla').checked = prefs.lectorPantalla;
            aplicarLectorPantalla(prefs.lectorPantalla);
            
            // Navegación por teclado
            document.getElementById('navegacion-teclado').checked = prefs.navegacionTeclado;
            aplicarNavegacionTeclado(prefs.navegacionTeclado);
            
            actualizarIndicador();
        } catch(e) {
            console.error('Error cargando preferencias:', e);
        }
    }
    
    // --- Aplicar tamaño de texto ---
    function aplicarTamano(tamano) {
        body.classList.remove('texto-grande', 'texto-muy-grande');
        if (tamano === 'grande') body.classList.add('texto-grande');
        if (tamano === 'muy-grande') body.classList.add('texto-muy-grande');
        guardarPreferencias();
    }
    
    // --- Aplicar contraste ---
    function aplicarContraste(contraste) {
        body.classList.remove('alto-contraste');
        if (contraste === 'alto') body.classList.add('alto-contraste');
        guardarPreferencias();
    }
    
    // --- Aplicar modo oscuro ---
    function aplicarModoOscuro(activado) {
        body.classList.toggle('modo-oscuro', activado);
        guardarPreferencias();
    }
    
    // --- Aplicar fuente dislexia ---
    function aplicarFuenteDislexia(activado) {
        body.classList.toggle('fuente-dislexia', activado);
        guardarPreferencias();
    }
    
    // --- Aplicar subtítulos ---
    function aplicarSubtitulos(activado) {
        body.classList.toggle('subtitulos', activado);
        guardarPreferencias();
    }
    
    // --- Aplicar lector de pantalla ---
    function aplicarLectorPantalla(activado) {
        // Añadir atributo ARIA para lectores de pantalla
        if (activado) {
            body.setAttribute('aria-live', 'polite');
            // Notificar cambio
            const anuncio = document.createElement('div');
            anuncio.setAttribute('aria-live', 'assertive');
            anuncio.className = 'sr-only';
            anuncio.textContent = 'Modo lector de pantalla activado';
            document.body.appendChild(anuncio);
            setTimeout(() => anuncio.remove(), 3000);
        } else {
            body.removeAttribute('aria-live');
        }
        guardarPreferencias();
    }
    
    // --- Aplicar navegación por teclado ---
    function aplicarNavegacionTeclado(activado) {
        if (activado) {
            // Añadir indicadores visuales de foco
            const style = document.createElement('style');
            style.id = 'keyboard-focus-style';
            style.textContent = `
                *:focus-visible {
                    outline: 4px solid #ff6b35 !important;
                    outline-offset: 2px !important;
                    box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.3) !important;
                }
            `;
            document.head.appendChild(style);
        } else {
            const style = document.getElementById('keyboard-focus-style');
            if (style) style.remove();
        }
        guardarPreferencias();
    }
    
    // --- Actualizar indicador ---
    function actualizarIndicador() {
        const tieneAccesibilidad = 
            body.classList.contains('modo-oscuro') ||
            body.classList.contains('alto-contraste') ||
            body.classList.contains('texto-grande') ||
            body.classList.contains('texto-muy-grande') ||
            body.classList.contains('fuente-dislexia') ||
            body.classList.contains('subtitulos') ||
            document.getElementById('modo-oscuro').checked ||
            document.getElementById('lector-pantalla').checked;
        
        document.querySelector('.accesibilidad-indicador').classList.toggle('active', tieneAccesibilidad);
    }
    
    // --- Restablecer todo ---
    function restablecerTodo() {
        // Resetear clases
        body.classList.remove('modo-oscuro', 'alto-contraste', 'texto-grande', 'texto-muy-grande', 'fuente-dislexia', 'subtitulos');
        body.removeAttribute('aria-live');
        
        // Resetear inputs
        document.getElementById('modo-oscuro').checked = false;
        document.getElementById('fuente-dislexia').checked = false;
        document.getElementById('subtitulos').checked = false;
        document.getElementById('lector-pantalla').checked = false;
        document.getElementById('navegacion-teclado').checked = false;
        
        // Resetear botones activos
        document.querySelectorAll('.tamano-opciones button').forEach(btn => {
            btn.classList.remove('activo');
            if (btn.dataset.tamano === 'normal') btn.classList.add('activo');
        });
        document.querySelectorAll('.contraste-opciones button').forEach(btn => {
            btn.classList.remove('activo');
            if (btn.dataset.contraste === 'normal') btn.classList.add('activo');
        });
        
        // Eliminar estilo de teclado
        const style = document.getElementById('keyboard-focus-style');
        if (style) style.remove();
        
        // Guardar y actualizar
        guardarPreferencias();
        actualizarIndicador();
    }
    
    // ============================================
    // 3. EVENTOS
    // ============================================
    
    // Toggle panel
    toggleBtn.addEventListener('click', function() {
        panel.classList.toggle('active');
        // Cerrar al hacer clic fuera
        if (panel.classList.contains('active')) {
            setTimeout(() => {
                document.addEventListener('click', cerrarPanelOutside);
            }, 10);
        }
    });
    
    function cerrarPanelOutside(e) {
        if (!panel.contains(e.target) && e.target !== toggleBtn) {
            panel.classList.remove('active');
            document.removeEventListener('click', cerrarPanelOutside);
        }
    }
    
    // Cerrar panel con botón X
    panel.querySelector('.cerrar-panel').addEventListener('click', function() {
        panel.classList.remove('active');
    });
    
    // Tamaño de texto
    document.querySelectorAll('.tamano-opciones button').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tamano-opciones button').forEach(b => b.classList.remove('activo'));
            this.classList.add('activo');
            aplicarTamano(this.dataset.tamano);
            actualizarIndicador();
        });
    });
    
    // Contraste
    document.querySelectorAll('.contraste-opciones button').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.contraste-opciones button').forEach(b => b.classList.remove('activo'));
            this.classList.add('activo');
            aplicarContraste(this.dataset.contraste);
            actualizarIndicador();
        });
    });
    
    // Modo oscuro
    document.getElementById('modo-oscuro').addEventListener('change', function() {
        aplicarModoOscuro(this.checked);
        actualizarIndicador();
    });
    
    // Fuente dislexia
    document.getElementById('fuente-dislexia').addEventListener('change', function() {
        aplicarFuenteDislexia(this.checked);
        actualizarIndicador();
    });
    
    // Subtítulos
    document.getElementById('subtitulos').addEventListener('change', function() {
        aplicarSubtitulos(this.checked);
        actualizarIndicador();
    });
    
    // Lector de pantalla
    document.getElementById('lector-pantalla').addEventListener('change', function() {
        aplicarLectorPantalla(this.checked);
        actualizarIndicador();
    });
    
    // Navegación por teclado
    document.getElementById('navegacion-teclado').addEventListener('change', function() {
        aplicarNavegacionTeclado(this.checked);
        actualizarIndicador();
    });
    
    // Restablecer
    document.getElementById('reset-accesibilidad').addEventListener('click', function() {
        restablecerTodo();
    });
    
    // ============================================
    // 4. INICIALIZAR
    // ============================================
    
    cargarPreferencias();
    
    // Cerrar panel con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && panel.classList.contains('active')) {
            panel.classList.remove('active');
        }
    });
});