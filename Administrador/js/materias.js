document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================== */
    // ELEMENTOS DEL MODAL                        */
    // ========================================== */
    const modal = document.getElementById('modalMateria');
    const modalTitulo = document.getElementById('modalTitulo');
    const modalAccion = document.getElementById('modalAccion');
    const modalId = document.getElementById('modalId');
    const modalNombre = document.getElementById('modalNombre');
    const modalCampo = document.getElementById('modalCampo');
    const modalDescripcion = document.getElementById('modalDescripcion');
    const modalContador = document.getElementById('modalContador');
    const formMateria = document.getElementById('formMateria');
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
    // ABRIR MODAL PARA NUEVA MATERIA            */
    // ========================================== */
    const btnNueva = document.getElementById('btnNuevaMateria');
    if (btnNueva) {
        btnNueva.addEventListener('click', function() {
            modalTitulo.textContent = 'Nueva materia';
            modalAccion.value = 'guardar';
            modalId.value = '';
            modalNombre.value = '';
            modalCampo.value = '';
            modalDescripcion.value = '';
            modalContador.textContent = '0';
            document.querySelector('input[name="estado"][value="Activa"]').checked = true;
            modal.classList.add('active');
            modalNombre.focus();
        });
    }

    // ========================================== */
    // ✅ BOTÓN "CREAR PRIMERA MATERIA" (EMPTY)  */
    // ========================================== */
    const btnNuevaEmpty = document.getElementById('btnNuevaMateriaEmpty');
    if (btnNuevaEmpty) {
        btnNuevaEmpty.addEventListener('click', function() {
            // Disparar clic en el botón principal
            const btnPrincipal = document.getElementById('btnNuevaMateria');
            if (btnPrincipal) {
                btnPrincipal.click();
            }
        });
    }

    // ========================================== */
    // ABRIR MODAL PARA EDITAR MATERIA           */
    // ========================================== */
    document.querySelectorAll('.btn-editar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            fetch('logica/procesar_materias.php?accion=obtener&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error al cargar la materia');
                        return;
                    }
                    modalTitulo.textContent = 'Editar materia';
                    modalAccion.value = 'editar';
                    modalId.value = id;
                    modalNombre.value = data.nombre;
                    modalCampo.value = data.campo_formativo;
                    modalDescripcion.value = data.descripcion || '';
                    modalContador.textContent = data.descripcion ? data.descripcion.length : 0;
                    if (data.estado === 'Activa') {
                        document.querySelector('input[name="estado"][value="Activa"]').checked = true;
                    } else {
                        document.querySelector('input[name="estado"][value="Inactiva"]').checked = true;
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
    const materiasGrid = document.getElementById('materiasGrid');
    const totalResultados = document.getElementById('totalResultados');

    filtros.forEach(function(btn) {
        btn.addEventListener('click', function() {
            filtros.forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            const filtro = this.getAttribute('data-filtro');
            filtrarMaterias(filtro);
        });
    });

    function filtrarMaterias(filtro) {
        const cards = materiasGrid.querySelectorAll('.materia-card');
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
    const buscar = document.getElementById('buscarMateria');
    if (buscar) {
        buscar.addEventListener('input', function() {
            const texto = this.value.toLowerCase();
            const cards = materiasGrid.querySelectorAll('.materia-card');
            let contador = 0;
            cards.forEach(function(card) {
                const nombre = card.querySelector('.materia-nombre').textContent.toLowerCase();
                if (nombre.includes(texto)) {
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
    // DESHABILITAR Y ELIMINAR                    */
    // ========================================== */
    document.querySelectorAll('.btn-deshabilitar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (confirm('⚠️ ¿Deshabilitar esta materia?')) {
                window.location.href = 'logica/procesar_materias.php?accion=deshabilitar&id=' + id;
            }
        });
    });

    document.querySelectorAll('.btn-eliminar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (confirm('⚠️ ¿Eliminar esta materia? Esta acción no se puede deshacer.')) {
                window.location.href = 'logica/procesar_materias.php?accion=eliminar&id=' + id;
            }
        });
    });
});