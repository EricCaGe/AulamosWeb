document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================== */
    // ELEMENTOS DEL MODAL                        */
    // ========================================== */
    const modal = document.getElementById('modalInscripcion');
    const modalTitulo = document.getElementById('modalTitulo');
    const modalAccion = document.getElementById('modalAccion');
    const modalId = document.getElementById('modalId');
    const formInscripcion = document.getElementById('formInscripcion');
    const btnCerrar = document.getElementById('modalCerrar');
    const btnCancelar = document.getElementById('modalCancelar');

    // ========================================== */
    // ABRIR MODAL PARA NUEVA INSCRIPCIÓN        */
    // ========================================== */
    const btnNuevo = document.getElementById('btnNuevaInscripcion');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
            modalTitulo.textContent = 'Nueva inscripción';
            modalAccion.value = 'guardar';
            modalId.value = '';
            
            // Seleccionar primer estudiante y curso
            const primerEstudiante = document.querySelector('input[name="id_alumno"]');
            if (primerEstudiante) primerEstudiante.checked = true;
            
            const primerCurso = document.querySelector('input[name="id_curso"]');
            if (primerCurso) primerCurso.checked = true;
            
            document.querySelector('input[name="estado"][value="Activo"]').checked = true;
            
            modal.classList.add('active');
        });
    }

    // ========================================== */
    // BOTÓN "CREAR PRIMERA INSCRIPCIÓN"         */
    // ========================================== */
    const btnNuevoEmpty = document.getElementById('btnNuevoInscripcionEmpty');
    if (btnNuevoEmpty) {
        btnNuevoEmpty.addEventListener('click', function() {
            document.getElementById('btnNuevaInscripcion').click();
        });
    }

    // ========================================== */
    // ABRIR MODAL PARA EDITAR INSCRIPCIÓN       */
    // ========================================== */
    document.querySelectorAll('.btn-editar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            fetch('logica/procesar_inscripciones.php?accion=obtener&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error al cargar la inscripción');
                        return;
                    }
                    modalTitulo.textContent = 'Editar inscripción';
                    modalAccion.value = 'editar';
                    modalId.value = id;

                    // Estudiante
                    document.querySelectorAll('input[name="id_alumno"]').forEach(el => {
                        el.checked = parseInt(el.value) === parseInt(data.id_alumno);
                    });
                    
                    // Curso
                    document.querySelectorAll('input[name="id_curso"]').forEach(el => {
                        el.checked = parseInt(el.value) === parseInt(data.id_curso);
                    });
                    
                    // Estado
                    if (data.estado === 'Activo') {
                        document.querySelector('input[name="estado"][value="Activo"]').checked = true;
                    } else if (data.estado === 'Inactivo') {
                        document.querySelector('input[name="estado"][value="Inactivo"]').checked = true;
                    } else {
                        document.querySelector('input[name="estado"][value="Finalizado"]').checked = true;
                    }

                    modal.classList.add('active');
                })
                .catch(error => {
                    alert('Error al cargar los datos');
                    console.error(error);
                });
        });
    });

    // ========================================== */
    // DESHABILITAR Y ELIMINAR                   */
    // ========================================== */
    document.querySelectorAll('.btn-deshabilitar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (confirm('⚠️ ¿Desactivar esta inscripción?')) {
                window.location.href = 'logica/procesar_inscripciones.php?accion=deshabilitar&id=' + id;
            }
        });
    });

    document.querySelectorAll('.btn-eliminar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (confirm('⚠️ ¿Eliminar esta inscripción? Esta acción no se puede deshacer.')) {
                window.location.href = 'logica/procesar_inscripciones.php?accion=eliminar&id=' + id;
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

    // ========================================== */
    // BÚSQUEDA                                   */
    // ========================================== */
    const buscar = document.getElementById('buscarInscripcion');
    const grid = document.getElementById('inscripcionesGrid');
    const totalResultados = document.getElementById('totalResultados');

    if (buscar) {
        buscar.addEventListener('input', function() {
            const texto = this.value.toLowerCase();
            const cards = grid.querySelectorAll('.inscripcion-card');
            let contador = 0;
            cards.forEach(function(card) {
                const estudiante = card.querySelector('.inscripcion-estudiante')?.textContent.toLowerCase() || '';
                const detalles = card.querySelectorAll('.detalle-valor');
                let coincide = estudiante.includes(texto);
                detalles.forEach(function(detalle) {
                    if (detalle.textContent.toLowerCase().includes(texto)) coincide = true;
                });
                if (coincide) {
                    card.style.display = 'block';
                    contador++;
                } else {
                    card.style.display = 'none';
                }
            });
            totalResultados.textContent = contador + ' resultados';
        });
    }

});