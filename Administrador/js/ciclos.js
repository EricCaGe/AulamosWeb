document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================== */
    // ELEMENTOS DEL MODAL                        */
    // ========================================== */
    const modal = document.getElementById('modalCiclo');
    const modalTitulo = document.getElementById('modalTitulo');
    const modalAccion = document.getElementById('modalAccion');
    const modalId = document.getElementById('modalId');
    const modalNombre = document.getElementById('modalNombre');
    const modalInicio = document.getElementById('modalInicio');
    const modalFin = document.getElementById('modalFin');
    const formCiclo = document.getElementById('formCiclo');
    const btnCerrar = document.getElementById('modalCerrar');
    const btnCancelar = document.getElementById('modalCancelar');

    // ========================================== */
    // ABRIR MODAL PARA NUEVO CICLO              */
    // ========================================== */
    const btnNuevo = document.getElementById('btnNuevoCiclo');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
            modalTitulo.textContent = 'Nuevo ciclo';
            modalAccion.value = 'guardar';
            modalId.value = '';
            modalNombre.value = '';
            modalInicio.value = '';
            modalFin.value = '';
            document.querySelector('input[name="estado"][value="Activo"]').checked = true;
            modal.classList.add('active');
            modalNombre.focus();
        });
    }

    // ========================================== */
    // ABRIR MODAL PARA EDITAR CICLO             */
    // ========================================== */
    document.querySelectorAll('.btn-editar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            fetch('logica/procesar_ciclos.php?accion=obtener&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error al cargar el ciclo');
                        return;
                    }
                    modalTitulo.textContent = 'Editar ciclo';
                    modalAccion.value = 'editar';
                    modalId.value = id;
                    modalNombre.value = data.nombre;
                    modalInicio.value = data.fecha_inicio;
                    modalFin.value = data.fecha_fin;
                    if (data.estado === 'Activo') {
                        document.querySelector('input[name="estado"][value="Activo"]').checked = true;
                    } else if (data.estado === 'Inactivo') {
                        document.querySelector('input[name="estado"][value="Inactivo"]').checked = true;
                    } else {
                        document.querySelector('input[name="estado"][value="Cerrado"]').checked = true;
                    }
                    modal.classList.add('active');
                    modalNombre.focus();
                })
                .catch(error => {
                    alert('Error al cargar los datos');
                    console.error(error);
                });
        });
    });

    // ========================================== */
    // CERRAR CICLO                               */
    // ========================================== */
    document.querySelectorAll('.btn-cerrar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nombre = this.closest('.ciclo-card').querySelector('.ciclo-nombre').textContent;
            if (confirm('⚠️ ¿Estás seguro de cerrar el ciclo "' + nombre + '"? Esta acción no se puede deshacer.')) {
                window.location.href = 'logica/procesar_ciclos.php?accion=cerrar&id=' + id;
            }
        });
    });

    // ========================================== */
    // CERRAR MODAL                               */
    // ========================================== */
    function cerrarModal() {
        modal.classList.remove('active');
    }

    if (btnCerrar) {
        btnCerrar.addEventListener('click', cerrarModal);
    }

    if (btnCancelar) {
        btnCancelar.addEventListener('click', cerrarModal);
    }

    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            cerrarModal();
        }
    });
// Botón "Crear primer ciclo" (desde empty state)
const btnNuevoEmpty = document.getElementById('btnNuevoCicloEmpty');
if (btnNuevoEmpty) {
    btnNuevoEmpty.addEventListener('click', function() {
        document.getElementById('btnNuevoCiclo').click();
    });
}
});