document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================== */
    // ELEMENTOS DEL MODAL                        */
    // ========================================== */
    const modal = document.getElementById('modalPeriodo');
    const modalTitulo = document.getElementById('modalTitulo');
    const modalAccion = document.getElementById('modalAccion');
    const modalId = document.getElementById('modalId');
    const modalNombre = document.getElementById('modalNombre');
    const modalInicio = document.getElementById('modalInicio');
    const modalFin = document.getElementById('modalFin');
    const formPeriodo = document.getElementById('formPeriodo');
    const btnCerrar = document.getElementById('modalCerrar');
    const btnCancelar = document.getElementById('modalCancelar');

    // ========================================== */
    // ABRIR MODAL PARA NUEVO PERIODO            */
    // ========================================== */
    const btnNuevo = document.getElementById('btnNuevoPeriodo');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
            modalTitulo.textContent = 'Nuevo periodo';
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
    // BOTÓN "CREAR PRIMER PERIODO" (EMPTY STATE)*/
    // ========================================== */
    const btnNuevoEmpty = document.getElementById('btnNuevoPeriodoEmpty');
    if (btnNuevoEmpty) {
        btnNuevoEmpty.addEventListener('click', function() {
            document.getElementById('btnNuevoPeriodo').click();
        });
    }

    // ========================================== */
    // ABRIR MODAL PARA EDITAR PERIODO           */
    // ========================================== */
    document.querySelectorAll('.btn-editar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            fetch('logica/procesar_periodos.php?accion=obtener&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error al cargar el periodo');
                        return;
                    }
                    modalTitulo.textContent = 'Editar periodo';
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
    // CERRAR PERIODO                             */
    // ========================================== */
    document.querySelectorAll('.btn-cerrar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nombre = this.closest('.periodo-card').querySelector('.periodo-nombre').textContent;
            if (confirm('⚠️ ¿Estás seguro de cerrar el periodo "' + nombre + '"? Esta acción no se puede deshacer.')) {
                window.location.href = 'logica/procesar_periodos.php?accion=cerrar&id=' + id;
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

});