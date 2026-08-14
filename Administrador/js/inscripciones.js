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
            
            // Deseleccionar todos los checkboxes de estudiantes
            document.querySelectorAll('.checkbox-estudiante-simple').forEach(el => {
                el.checked = false;
            });
            
            // Seleccionar primer curso
            const primerCurso = document.querySelector('input[name="id_curso"]');
            if (primerCurso) primerCurso.checked = true;
            
            document.querySelector('input[name="estado"][value="Activo"]').checked = true;
            
            // Resetear contador
            actualizarContadorSimple();
            
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

                    // ✅ Estudiante - marcar el checkbox correspondiente
                    document.querySelectorAll('.checkbox-estudiante-simple').forEach(el => {
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

                    // Actualizar contador
                    actualizarContadorSimple();

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

    // ========================================== */
    // ✅ CHECKBOXES EN MODAL SIMPLE              */
    // ========================================== */
    
    const checkboxesSimple = document.querySelectorAll('.checkbox-estudiante-simple');
    const contadorSimple = document.getElementById('contadorSeleccionadosSimple');
    const btnSeleccionarTodosSimple = document.getElementById('btnSeleccionarTodosSimple');

    function actualizarContadorSimple() {
        const seleccionados = document.querySelectorAll('.checkbox-estudiante-simple:checked');
        const total = document.querySelectorAll('.checkbox-estudiante-simple').length;
        if (contadorSimple) {
            if (total === 0) {
                contadorSimple.innerHTML = '<i class="fa-regular fa-circle"></i> 0 estudiantes seleccionados';
            } else {
                contadorSimple.innerHTML = `<i class="fa-regular fa-circle-check"></i> ${seleccionados.length} de ${total} estudiantes seleccionados`;
            }
        }
    }

    if (btnSeleccionarTodosSimple) {
        btnSeleccionarTodosSimple.addEventListener('click', function() {
            const todos = document.querySelectorAll('.checkbox-estudiante-simple');
            const todosSeleccionados = document.querySelectorAll('.checkbox-estudiante-simple:checked').length === todos.length;
            
            todos.forEach(cb => {
                cb.checked = !todosSeleccionados;
            });
            actualizarContadorSimple();
            
            if (todosSeleccionados) {
                btnSeleccionarTodosSimple.innerHTML = '<i class="fa-solid fa-check-double"></i> Seleccionar todos';
            } else {
                btnSeleccionarTodosSimple.innerHTML = '<i class="fa-regular fa-square"></i> Deseleccionar todos';
            }
        });
    }

    // Actualizar contador al cambiar checkbox simple
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('checkbox-estudiante-simple')) {
            actualizarContadorSimple();
        }
    });

    // ========================================== */
    // ✅ NUEVO: INSCRIPCIÓN MASIVA              */
    // ========================================== */

    // Elementos del modal masivo
    const modalMasiva = document.getElementById('modalInscripcionMasiva');
    const btnMasiva = document.getElementById('btnInscripcionMasiva');
    const btnCerrarMasiva = document.getElementById('modalCerrarMasiva');
    const btnCancelarMasiva = document.getElementById('modalCancelarMasiva');
    const btnSeleccionarTodos = document.getElementById('btnSeleccionarTodos');
    const contadorSeleccionados = document.getElementById('contadorSeleccionados');
    const formMasiva = document.getElementById('formInscripcionMasiva');

    // ========================================== */
    // ABRIR MODAL MASIVO                         */
    // ========================================== */
    if (btnMasiva) {
        btnMasiva.addEventListener('click', function() {
            if (modalMasiva) {
                modalMasiva.classList.add('active');
                document.body.style.overflow = 'hidden';
                actualizarContadorMasiva();
            }
        });
    }

    // ========================================== */
    // CERRAR MODAL MASIVO                        */
    // ========================================== */
    function cerrarModalMasiva() {
        if (modalMasiva) {
            modalMasiva.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    }

    if (btnCerrarMasiva) {
        btnCerrarMasiva.addEventListener('click', cerrarModalMasiva);
    }

    if (btnCancelarMasiva) {
        btnCancelarMasiva.addEventListener('click', cerrarModalMasiva);
    }

    if (modalMasiva) {
        modalMasiva.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalMasiva();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalMasiva && modalMasiva.classList.contains('active')) {
            cerrarModalMasiva();
        }
    });

    // ========================================== */
    // SELECCIONAR TODOS / CONTADOR MASIVO       */
    // ========================================== */
    function actualizarContadorMasiva() {
        const checkboxes = document.querySelectorAll('.checkbox-estudiante');
        const seleccionados = document.querySelectorAll('.checkbox-estudiante:checked');
        const total = checkboxes.length;
        
        if (contadorSeleccionados) {
            if (total === 0) {
                contadorSeleccionados.innerHTML = '<i class="fa-regular fa-circle"></i> 0 estudiantes seleccionados';
            } else {
                contadorSeleccionados.innerHTML = `<i class="fa-regular fa-circle-check"></i> ${seleccionados.length} de ${total} estudiantes seleccionados`;
            }
        }
    }

    if (btnSeleccionarTodos) {
        btnSeleccionarTodos.addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.checkbox-estudiante');
            const todosSeleccionados = document.querySelectorAll('.checkbox-estudiante:checked').length === checkboxes.length;
            
            checkboxes.forEach(cb => {
                cb.checked = !todosSeleccionados;
            });
            actualizarContadorMasiva();
            
            if (todosSeleccionados) {
                btnSeleccionarTodos.innerHTML = '<i class="fa-solid fa-check-double"></i> Seleccionar todos';
            } else {
                btnSeleccionarTodos.innerHTML = '<i class="fa-regular fa-square"></i> Deseleccionar todos';
            }
        });
    }

    // Actualizar contador al cambiar checkbox masivo
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('checkbox-estudiante')) {
            actualizarContadorMasiva();
        }
    });

    // ========================================== */
    // VALIDACIÓN FORMULARIO MASIVO              */
    // ========================================== */
    if (formMasiva) {
        formMasiva.addEventListener('submit', function(e) {
            const curso = document.getElementById('selectCursoMasivo');
            const seleccionados = document.querySelectorAll('.checkbox-estudiante:checked');
            
            if (!curso || !curso.value) {
                e.preventDefault();
                alert('⚠️ Selecciona un curso');
                return;
            }
            
            if (seleccionados.length === 0) {
                e.preventDefault();
                alert('⚠️ Selecciona al menos un estudiante');
                return;
            }
            
            if (!confirm(`¿Estás seguro de inscribir ${seleccionados.length} estudiantes en este curso?`)) {
                e.preventDefault();
            }
        });
    }

});