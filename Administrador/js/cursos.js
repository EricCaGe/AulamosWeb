document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================== */
    // ELEMENTOS DEL MODAL                        */
    // ========================================== */
    const modal = document.getElementById('modalCurso');
    const modalTitulo = document.getElementById('modalTitulo');
    const modalAccion = document.getElementById('modalAccion');
    const modalId = document.getElementById('modalId');
    const modalNombre = document.getElementById('modalNombre');
    const modalDescripcion = document.getElementById('modalDescripcion');
    const modalContador = document.getElementById('modalContador');
    const formCurso = document.getElementById('formCurso');
    const btnCerrar = document.getElementById('modalCerrar');
    const btnCancelar = document.getElementById('modalCancelar');

    // ========================================== */
    // CONTADOR DE CARACTERES                     */
    // ========================================== */
    if (modalDescripcion && modalContador) {
        modalDescripcion.addEventListener('input', function() {
            modalContador.textContent = this.value.length;
        });
    }

    // ========================================== */
    // ABRIR MODAL PARA NUEVO CURSO              */
    // ========================================== */
    const btnNuevo = document.getElementById('btnNuevoCurso');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
            modalTitulo.textContent = 'Nuevo curso';
            modalAccion.value = 'guardar';
            modalId.value = '';
            modalNombre.value = '';
            modalDescripcion.value = '';
            modalContador.textContent = '0';
            
            // Seleccionar primer elemento de cada grupo de radios
            const primerCiclo = document.querySelector('input[name="id_ciclo"]');
            if (primerCiclo) primerCiclo.checked = true;
            
            const primerMateria = document.querySelector('input[name="id_materia"]');
            if (primerMateria) primerMateria.checked = true;
            
            const primerDocente = document.querySelector('input[name="id_docente"]');
            if (primerDocente) primerDocente.checked = true;
            
            // Limpiar selects
            document.getElementById('modalGrupo').value = '';
            
            document.querySelector('input[name="estado"][value="Activo"]').checked = true;
            
            modal.classList.add('active');
            modalNombre.focus();
        });
    }

    // ========================================== */
    // BOTÓN "CREAR PRIMER CURSO" (EMPTY STATE)  */
    // ========================================== */
    const btnNuevoEmpty = document.getElementById('btnNuevoCursoEmpty');
    if (btnNuevoEmpty) {
        btnNuevoEmpty.addEventListener('click', function() {
            document.getElementById('btnNuevoCurso').click();
        });
    }

    // ========================================== */
    // ABRIR MODAL PARA EDITAR CURSO             */
    // ========================================== */
    document.querySelectorAll('.btn-editar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            fetch('logica/procesar_cursos.php?accion=obtener&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error al cargar el curso');
                        return;
                    }
                    modalTitulo.textContent = 'Editar curso';
                    modalAccion.value = 'editar';
                    modalId.value = id;
                    modalNombre.value = data.nombre;
                    modalDescripcion.value = data.descripcion || '';
                    modalContador.textContent = data.descripcion ? data.descripcion.length : 0;

                    // Ciclo
                    document.querySelectorAll('input[name="id_ciclo"]').forEach(el => {
                        el.checked = parseInt(el.value) === parseInt(data.id_ciclo);
                    });
                    
                    // Grupo
                    document.getElementById('modalGrupo').value = data.id_grupo || '';
                    
                    // Materia
                    document.querySelectorAll('input[name="id_materia"]').forEach(el => {
                        el.checked = parseInt(el.value) === parseInt(data.id_materia);
                    });
                    
                    // Docente
                    document.querySelectorAll('input[name="id_docente"]').forEach(el => {
                        el.checked = parseInt(el.value) === parseInt(data.id_docente);
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
                    modalNombre.focus();
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
            if (confirm('⚠️ ¿Desactivar este curso?')) {
                window.location.href = 'logica/procesar_cursos.php?accion=deshabilitar&id=' + id;
            }
        });
    });

    document.querySelectorAll('.btn-eliminar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (confirm('⚠️ ¿Eliminar este curso? Esta acción no se puede deshacer.')) {
                window.location.href = 'logica/procesar_cursos.php?accion=eliminar&id=' + id;
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
    const buscar = document.getElementById('buscarCurso');
    const cursosGrid = document.getElementById('cursosGrid');
    const totalResultados = document.getElementById('totalResultados');

    if (buscar) {
        buscar.addEventListener('input', function() {
            const texto = this.value.toLowerCase();
            const cards = cursosGrid.querySelectorAll('.curso-card');
            let contador = 0;
            cards.forEach(function(card) {
                const nombre = card.querySelector('.curso-nombre')?.textContent.toLowerCase() || '';
                const materia = card.querySelectorAll('.detalle-valor')[0]?.textContent.toLowerCase() || '';
                const grupo = card.querySelectorAll('.detalle-valor')[1]?.textContent.toLowerCase() || '';
                const docente = card.querySelectorAll('.detalle-valor')[2]?.textContent.toLowerCase() || '';
                
                if (nombre.includes(texto) || materia.includes(texto) || grupo.includes(texto) || docente.includes(texto)) {
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