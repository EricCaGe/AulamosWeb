(function() {
    'use strict';

    const idUsuario = 1; // TODO: Reemplazar con el ID real de sesión

    // =============================================
    // 1. ASISTENTE VIRTUAL
    // =============================================
    const btnAsistente = document.getElementById('btnAsistente');
    if (btnAsistente) {
        btnAsistente.addEventListener('click', function() {
            alert('🧠 Asistente Virtual: Estoy aquí para ayudarte a personalizar tu experiencia de accesibilidad. ¿Qué prefieres ajustar?');
        });
    }

    // =============================================
    // 2. TOGGLES (botones de activación/desactivación)
    // =============================================
    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const pref = this.dataset.pref;
            const currentValue = parseInt(this.dataset.value);
            const newValue = currentValue === 1 ? 0 : 1;
            
            // Actualizar visualmente
            this.classList.toggle('active');
            this.dataset.value = newValue;
            this.querySelector('.toggle-label').textContent = newValue === 1 ? 'Sí' : 'No';
            
            // Guardar en BD mediante AJAX
            guardarPreferencia(pref, newValue);
            
            // Aplicar cambio visual en la página (si aplica)
            aplicarPreferenciaVisual(pref, newValue);
        });
    });

    // =============================================
    // 3. SELECTS (tamaño de texto)
    // =============================================
    document.querySelectorAll('.select-pref').forEach(select => {
        select.addEventListener('change', function() {
            const pref = this.dataset.pref;
            const value = this.value;
            
            // Guardar en BD
            guardarPreferencia(pref, value);
            
            // Aplicar cambio visual
            aplicarPreferenciaVisual(pref, value);
        });
    });

    // =============================================
    // 4. RESTABLECER CONFIGURACIÓN
    // =============================================
    document.getElementById('btnReset').addEventListener('click', function() {
        if (confirm('¿Estás seguro de que deseas restablecer todas las preferencias de accesibilidad a los valores predeterminados?')) {
            // Valores predeterminados
            const defaults = {
                'alto_contraste': 0,
                'modo_oscuro': 0,
                'tamano_texto': 'Normal',
                'lector_pantalla': 0,
                'subtitulos': 0,
                'navegacion_teclado': 0
            };
            
            // Actualizar cada preferencia
            Object.keys(defaults).forEach(pref => {
                const value = defaults[pref];
                guardarPreferencia(pref, value);
                
                // Actualizar elementos visuales
                const toggle = document.querySelector(`.toggle-btn[data-pref="${pref}"]`);
                if (toggle) {
                    const newValue = value === 1 ? 1 : 0;
                    toggle.classList.toggle('active', newValue === 1);
                    toggle.dataset.value = newValue;
                    toggle.querySelector('.toggle-label').textContent = newValue === 1 ? 'Sí' : 'No';
                    aplicarPreferenciaVisual(pref, newValue);
                }
                
                const select = document.querySelector(`.select-pref[data-pref="${pref}"]`);
                if (select) {
                    select.value = value;
                    aplicarPreferenciaVisual(pref, value);
                }
            });
            
            alert('✅ Configuración restablecida a valores predeterminados.');
        }
    });

    // =============================================
    // 5. FUNCIONES AUXILIARES
    // =============================================
    
    // Guardar preferencia en BD mediante AJAX
    function guardarPreferencia(pref, value) {
        fetch('../ajax/guardar_preferencia.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id_usuario=${idUsuario}&pref=${pref}&value=${encodeURIComponent(value)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`✅ Preferencia "${pref}" guardada:`, value);
            } else {
                console.error('❌ Error al guardar:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ Error de conexión:', error);
        });
    }
    
    // Aplicar cambio visual en la página (para que se vea en tiempo real)
    function aplicarPreferenciaVisual(pref, value) {
        const body = document.body;
        
        switch(pref) {
            case 'alto_contraste':
                body.classList.toggle('alto-contraste', value == 1);
                break;
            case 'modo_oscuro':
                body.classList.toggle('modo-oscuro', value == 1);
                break;
            case 'tamano_texto':
                // Remover clases anteriores
                body.classList.remove('texto-pequeno', 'texto-normal', 'texto-grande', 'texto-muy-grande');
                // Agregar nueva clase según el valor
                const claseMap = {
                    'Pequeño': 'texto-pequeno',
                    'Normal': 'texto-normal',
                    'Grande': 'texto-grande',
                    'Muy Grande': 'texto-muy-grande'
                };
                if (claseMap[value]) {
                    body.classList.add(claseMap[value]);
                }
                break;
            case 'lector_pantalla':
                // Simulación: mostrar alerta o activar lector
                if (value == 1) {
                    console.log('🔊 Lector de pantalla activado');
                } else {
                    console.log('🔇 Lector de pantalla desactivado');
                }
                break;
            case 'subtitulos':
                // Simulación
                if (value == 1) {
                    console.log('📝 Subtítulos activados');
                } else {
                    console.log('📝 Subtítulos desactivados');
                }
                break;
            case 'navegacion_teclado':
                // Simulación
                if (value == 1) {
                    console.log('⌨️ Navegación por teclado activada');
                } else {
                    console.log('⌨️ Navegación por teclado desactivada');
                }
                break;
        }
    }
    
    // =============================================
    // 6. CARGAR PREFERENCIAS AL INICIAR (aplicar visualmente)
    // =============================================
    function cargarPreferenciasIniciales() {
        // Obtener todos los toggles y selects
        document.querySelectorAll('.toggle-btn').forEach(btn => {
            const pref = btn.dataset.pref;
            const value = parseInt(btn.dataset.value);
            aplicarPreferenciaVisual(pref, value);
        });
        
        document.querySelectorAll('.select-pref').forEach(select => {
            const pref = select.dataset.pref;
            const value = select.value;
            aplicarPreferenciaVisual(pref, value);
        });
    }
    
    cargarPreferenciasIniciales();
    
    // =============================================
    // 7. NOTA: Los botones de accesibilidad en el footer
    // ya están manejados por Inicio.js, por lo que no
    // los duplicamos aquí.
    // =============================================

})();