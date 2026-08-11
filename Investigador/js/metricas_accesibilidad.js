// ========================================== */
// MÉTRICAS ACCESIBILIDAD - INVESTIGADOR      */
// ========================================== */

document.addEventListener('DOMContentLoaded', function() {
    console.log('♿ Métricas de accesibilidad - Investigador');
    
    // ========================================== */
    // DATOS DE ESTÁNDARES POR PREFERENCIA        */
    // ========================================== */
    
    const estandaresPorPreferencia = {
        'alto_contraste': {
            estandar: 'WCAG 2.1',
            nivel: 'AA',
            funcionalidad: 'Alto contraste'
        },
        'modo_oscuro': {
            estandar: 'WCAG 2.1',
            nivel: 'AA',
            funcionalidad: 'Modo oscuro'
        },
        'tamano_texto': {
            estandar: 'WCAG 2.1',
            nivel: 'AA',
            funcionalidad: 'Tamaño de texto ajustable'
        },
        'fuente_dislexia': {
            estandar: 'WCAG 2.1',
            nivel: 'A',
            funcionalidad: 'Fuente para dislexia'
        },
        'lector_pantalla': {
            estandar: 'WCAG 2.1',
            nivel: 'A',
            funcionalidad: 'Lector de pantalla'
        },
        'velocidad_lectura': {
            estandar: 'WCAG 2.1',
            nivel: 'A',
            funcionalidad: 'Velocidad de lectura ajustable'
        },
        'subtitulos': {
            estandar: 'WCAG 2.1',
            nivel: 'AA',
            funcionalidad: 'Subtítulos'
        },
        'navegacion_teclado': {
            estandar: 'WCAG 2.1',
            nivel: 'A',
            funcionalidad: 'Navegación por teclado'
        }
    };

    // ========================================== */
    // FUNCIONES DEL MODAL                        */
    // ========================================== */

    function abrirModalEstandares(idUsuario, nombre, fecha) {
        const modal = document.getElementById('modalEstandares');
        if (!modal) return;
        
        document.getElementById('modalNombreEstudiante').textContent = nombre;
        document.getElementById('modalFechaEstudiante').textContent = 'Actualizado: ' + fecha;
        document.getElementById('modalTituloEstudiante').textContent = 'Estándares de accesibilidad - ' + nombre;
        
        modal.classList.remove('modal-estandares-hidden');
        document.body.style.overflow = 'hidden';
        
        // Obtener preferencias del estudiante
        fetch(`../Investigador/logica/obtener_preferencias_estudiante.php?id=${idUsuario}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderizarTabla(data.preferencias);
                } else {
                    document.getElementById('tablaEstandaresBody').innerHTML = `
                        <tr><td colspan="4" class="sin-preferencias">No se pudieron cargar las preferencias</td></tr>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('tablaEstandaresBody').innerHTML = `
                    <tr><td colspan="4" class="sin-preferencias">Error al cargar los datos</td></tr>
                `;
            });
    }

    function cerrarModalEstandares() {
        const modal = document.getElementById('modalEstandares');
        if (!modal) return;
        modal.classList.add('modal-estandares-hidden');
        document.body.style.overflow = 'auto';
    }

    function renderizarTabla(preferencias) {
        const tbody = document.getElementById('tablaEstandaresBody');
        if (!tbody) return;
        
        let html = '';
        let tienePreferencias = false;

        for (const [campo, valor] of Object.entries(preferencias)) {
            if (campo === 'id_usuario' || campo === 'id_preferencia' || campo === 'fecha_actualizacion') continue;
            
            const estandar = estandaresPorPreferencia[campo];
            if (!estandar) continue;

            let activo = false;
            if (campo === 'tamano_texto') {
                activo = (valor !== 'Normal' && valor !== null);
            } else if (campo === 'velocidad_lectura') {
                activo = (parseFloat(valor) !== 1.0);
            } else {
                activo = (valor == 1 || valor === '1' || valor === true);
            }

            tienePreferencias = true;

            html += `
                <tr>
                    <td><strong>${estandar.estandar}</strong></td>
                    <td><span style="background:#e8f0fe; padding:2px 10px; border-radius:12px; font-size:12px; font-weight:600;">${estandar.nivel}</span></td>
                    <td>${estandar.funcionalidad}</td>
                    <td>
                        <span class="estado-cumple ${activo ? 'cumple' : 'no-cumple'}">
                            ${activo ? '✅ Cumple' : '❌ No cumple'}
                        </span>
                    </td>
                </tr>
            `;
        }

        if (!tienePreferencias) {
            html = `
                <tr>
                    <td colspan="4" class="sin-preferencias">No se encontraron preferencias registradas para este estudiante</td>
                </tr>
            `;
        }

        tbody.innerHTML = html;
    }

    // ========================================== */
    // EVENTOS - BOTONES "Ver estándares"         */
    // ========================================== */

    document.querySelectorAll('.btn-estandares').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const id = this.dataset.id;
            const nombre = this.dataset.nombre;
            const fecha = this.dataset.fecha;
            abrirModalEstandares(id, nombre, fecha);
        });
    });

    // Cerrar modal con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalEstandares();
        }
    });

    // Cerrar modal al hacer clic fuera
    const modal = document.getElementById('modalEstandares');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalEstandares();
            }
        });
    }

    // Exponer funciones globalmente para que funcionen con onclick en el HTML
    window.abrirModalEstandares = abrirModalEstandares;
    window.cerrarModalEstandares = cerrarModalEstandares;
});