document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================== */
    // ELEMENTOS DEL MODAL                        */
    // ========================================== */
    const modal = document.getElementById('modalGrupo');
    const modalTitulo = document.getElementById('modalTitulo');
    const modalAccion = document.getElementById('modalAccion');
    const modalId = document.getElementById('modalId');
    const modalNombre = document.getElementById('modalNombre');
    const modalCupo = document.getElementById('modalCupo');
    const formGrupo = document.getElementById('formGrupo');
    const btnCerrar = document.getElementById('modalCerrar');
    const btnCancelar = document.getElementById('modalCancelar');

    // ========================================== */
    // ABRIR MODAL PARA NUEVO GRUPO              */
    // ========================================== */
    const btnNuevo = document.getElementById('btnNuevoGrupo');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
            modalTitulo.textContent = 'Nuevo grupo';
            modalAccion.value = 'guardar';
            modalId.value = '';
            modalNombre.value = '';
            modalCupo.value = '30';
            document.querySelector('input[name="grado"][value="1°"]').checked = true;
            document.querySelector('input[name="turno"][value="Matutino"]').checked = true;
            document.querySelector('input[name="modalidad"][value="Presencial"]').checked = true;
            document.querySelector('input[name="estado"][value="Activo"]').checked = true;
            
            // Limpiar selects
            document.getElementById('modalCiclo').value = '';
            document.getElementById('modalDocente').value = '';
            
            modal.classList.add('active');
            modalNombre.focus();
        });
    }

    // ========================================== */
    // BOTÓN "CREAR PRIMER GRUPO" (EMPTY STATE)  */
    // ========================================== */
    const btnNuevoEmpty = document.getElementById('btnNuevoGrupoEmpty');
    if (btnNuevoEmpty) {
        btnNuevoEmpty.addEventListener('click', function() {
            document.getElementById('btnNuevoGrupo').click();
        });
    }

    // ========================================== */
    // ABRIR MODAL PARA EDITAR GRUPO             */
    // ========================================== */
    document.querySelectorAll('.btn-editar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            fetch('logica/procesar_grupos.php?accion=obtener&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error al cargar el grupo');
                        return;
                    }
                    modalTitulo.textContent = 'Editar grupo';
                    modalAccion.value = 'editar';
                    modalId.value = id;
                    modalNombre.value = data.nombre;
                    modalCupo.value = data.cupo_maximo || 30;

                    // ✅ Ciclo
                    document.getElementById('modalCiclo').value = data.id_ciclo || '';
                    
                    // ✅ Docente
                    document.getElementById('modalDocente').value = data.id_docente || '';

                    // Grado
                    document.querySelectorAll('input[name="grado"]').forEach(el => {
                        el.checked = el.value === data.grado;
                    });
                    // Turno
                    document.querySelectorAll('input[name="turno"]').forEach(el => {
                        el.checked = el.value === data.turno;
                    });
                    // Modalidad
                    document.querySelectorAll('input[name="modalidad"]').forEach(el => {
                        el.checked = el.value === data.modalidad;
                    });
                    // Estado
                    if (data.estado === 'Activo') {
                        document.querySelector('input[name="estado"][value="Activo"]').checked = true;
                    } else {
                        document.querySelector('input[name="estado"][value="Inactivo"]').checked = true;
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
            if (confirm('⚠️ ¿Desactivar este grupo?')) {
                window.location.href = 'logica/procesar_grupos.php?accion=deshabilitar&id=' + id;
            }
        });
    });

    document.querySelectorAll('.btn-eliminar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (confirm('⚠️ ¿Eliminar este grupo? Esta acción no se puede deshacer.')) {
                window.location.href = 'logica/procesar_grupos.php?accion=eliminar&id=' + id;
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
    // FILTROS                                    */
    // ========================================== */
    const filtros = document.querySelectorAll('.filtro-btn');
    const gruposGrid = document.getElementById('gruposGrid');
    const totalResultados = document.getElementById('totalResultados');

    filtros.forEach(function(btn) {
        btn.addEventListener('click', function() {
            filtros.forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            const filtro = this.getAttribute('data-filtro');
            filtrarGrupos(filtro);
        });
    });

    function filtrarGrupos(filtro) {
        const cards = gruposGrid.querySelectorAll('.grupo-card');
        let contador = 0;
        cards.forEach(function(card) {
            const estado = card.getAttribute('data-estado');
            if (filtro === 'todas' || estado === filtro) {
                card.style.display = 'block';
                contador++;
            } else {
                card.style.display = 'none';
            }
        });
        totalResultados.textContent = contador + ' resultados';
    }

    // ========================================== */
    // BÚSQUEDA                                   */
    // ========================================== */
    const buscar = document.getElementById('buscarGrupo');
    if (buscar) {
        buscar.addEventListener('input', function() {
            const texto = this.value.toLowerCase();
            const cards = gruposGrid.querySelectorAll('.grupo-card');
            let contador = 0;
            cards.forEach(function(card) {
                const nombre = card.querySelector('.grupo-nombre').textContent.toLowerCase();
                const turno = card.querySelector('.detalle-valor')?.textContent.toLowerCase() || '';
                if (nombre.includes(texto) || turno.includes(texto)) {
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