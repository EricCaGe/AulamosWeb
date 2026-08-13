// ========================================== */
// CATÁLOGO DE ESTÁNDARES WCAG - INVESTIGADOR */
// ========================================== */

document.addEventListener('DOMContentLoaded', function() {
    console.log('📚 Catálogo de estándares WCAG - Investigador');

    // ========================================== */
    // FILTROS
    // ========================================== */
    
    const filtroNivel = document.getElementById('filtroNivel');
    const filtroBusqueda = document.getElementById('filtroBusqueda');
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    const tarjetas = document.querySelectorAll('.estandar-card');

    function aplicarFiltros() {
        const nivelSeleccionado = filtroNivel.value;
        const busqueda = filtroBusqueda.value.toLowerCase().trim();

        tarjetas.forEach(function(tarjeta) {
            let mostrar = true;

            // Filtrar por nivel
            if (nivelSeleccionado !== 'todos') {
                const nivelTarjeta = tarjeta.dataset.nivel || 'sin-nivel';
                if (nivelTarjeta !== nivelSeleccionado) {
                    mostrar = false;
                }
            }

            // Filtrar por búsqueda
            if (mostrar && busqueda !== '') {
                const texto = tarjeta.textContent.toLowerCase();
                if (!texto.includes(busqueda)) {
                    mostrar = false;
                }
            }

            tarjeta.classList.toggle('oculto', !mostrar);
        });

        // Mostrar mensaje si no hay resultados
        mostrarSinResultados();
    }

    function mostrarSinResultados() {
        const visibles = document.querySelectorAll('.estandar-card:not(.oculto)');
        const sinDatos = document.querySelector('.sin-datos');

        // Eliminar mensaje anterior si existe
        const mensajeExistente = document.querySelector('.sin-resultados-mensaje');
        if (mensajeExistente) {
            mensajeExistente.remove();
        }

        if (visibles.length === 0) {
            const mensaje = document.createElement('div');
            mensaje.className = 'sin-resultados-mensaje';
            mensaje.style.cssText = `
                text-align: center;
                padding: 40px 20px;
                color: #94a3b8;
                background: #ffffff;
                border: 1px solid #e8edf2;
                border-radius: 12px;
                margin-top: 16px;
            `;
            mensaje.innerHTML = `
                <i class="fa-solid fa-search" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 12px;"></i>
                <p style="font-size: 16px; font-weight: 500; color: #1a1a2e;">No se encontraron resultados</p>
                <p style="font-size: 14px; margin-top: 4px;">Intenta con otros filtros o palabras de búsqueda.</p>
            `;
            const lista = document.getElementById('catalogoLista');
            lista.appendChild(mensaje);
        }
    }

    // Event listeners
    if (filtroNivel) {
        filtroNivel.addEventListener('change', aplicarFiltros);
    }

    if (filtroBusqueda) {
        filtroBusqueda.addEventListener('input', aplicarFiltros);
    }

    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function() {
            if (filtroNivel) filtroNivel.value = 'todos';
            if (filtroBusqueda) filtroBusqueda.value = '';
            aplicarFiltros();
        });
    }

    // ========================================== */
    // CONTADOR DE FUNCIONALIDADES POR ESTÁNDAR
    // ========================================== */
    
    // Ya se muestra en el PHP, pero podemos agregar un tooltip o información adicional

    // ========================================== */
    // EXPANDIR / CONTRAER DESCRIPCIÓN (opcional)
    // ========================================== */
    
    // Si las descripciones son muy largas, se pueden truncar
    const descripciones = document.querySelectorAll('.estandar-descripcion');
    descripciones.forEach(function(desc) {
        const texto = desc.textContent.trim();
        if (texto.length > 200) {
            // Opcional: agregar botón "Ver más"
            // Por ahora solo mostramos el texto completo
        }
    });

    console.log('✅ Catálogo WCAG inicializado correctamente');
});