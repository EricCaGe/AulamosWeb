// ========================================== */
// PRUEBAS DE INVESTIGACIÓN - INVESTIGADOR    */
// ========================================== */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🧪 Pruebas de investigación - Investigador');

    // ========================================== */
    // MODAL - ABRIR / CERRAR
    // ========================================== */
    const modal = document.getElementById('modalPrueba');
    const btnAbrir = document.getElementById('btnNuevaPrueba');
    const btnCerrar = document.getElementById('modalCerrar');
    const btnCancelar = document.getElementById('modalCancelar');

    function abrirModal() {
        modal.classList.remove('modal-hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal() {
        modal.classList.add('modal-hidden');
        document.body.style.overflow = 'auto';
        const form = modal.querySelector('form');
        if (form) form.reset();
    }

    if (btnAbrir) {
        btnAbrir.addEventListener('click', abrirModal);
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
        if (e.key === 'Escape') {
            cerrarModal();
        }
    });

    // ========================================== */
    // VALIDACIÓN DEL FORMULARIO
    // ========================================== */
    const form = modal.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const hipotesis = document.getElementById('hipotesis').value.trim();
            const fechaInicio = document.getElementById('fecha_inicio').value;

            if (!nombre) {
                e.preventDefault();
                alert('El nombre de la prueba es obligatorio.');
                document.getElementById('nombre').focus();
                return;
            }

            if (!hipotesis) {
                e.preventDefault();
                alert('La hipótesis es obligatoria.');
                document.getElementById('hipotesis').focus();
                return;
            }

            if (!fechaInicio) {
                e.preventDefault();
                alert('La fecha de inicio es obligatoria.');
                document.getElementById('fecha_inicio').focus();
                return;
            }
        });
    }

    // ========================================== */
    // CAMBIAR ESTADO CON AJAX (DELEGACIÓN DE EVENTOS)
    // ========================================== */
    document.querySelector('.pruebas-list').addEventListener('click', function(e) {
        // Buscar si el clic fue en un botón .js-cambiar-estado o dentro de él
        const btn = e.target.closest('.js-cambiar-estado');
        if (!btn) return;

        e.preventDefault();

        const idPrueba = btn.dataset.id;
        const nuevoEstado = btn.dataset.estado;
        const textoOriginal = btn.innerHTML;

        // Deshabilitar botón mientras procesa
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';

        // Enviar petición AJAX
        fetch('logica/cambiar_estado_prueba.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id_prueba=${idPrueba}&estado=${nuevoEstado}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                actualizarTarjeta(data.prueba);
                mostrarMensaje(data.message, 'exito');
            } else {
                alert('Error: ' + (data.error || 'No se pudo cambiar el estado'));
                btn.disabled = false;
                btn.innerHTML = textoOriginal;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ocurrió un error al cambiar el estado.');
            btn.disabled = false;
            btn.innerHTML = textoOriginal;
        });
    });

    // ========================================== */
    // ACTUALIZAR TARJETA (sin recargar)
    // ========================================== */
    function actualizarTarjeta(prueba) {
        const card = document.querySelector(`.prueba-card[data-id="${prueba.id_prueba}"]`);
        if (!card) return;

        // Actualizar badge (estado)
        const badge = card.querySelector('.badge');
        badge.textContent = prueba.estado;
        badge.className = 'badge ' + getBadgeClass(prueba.estado);

        // Actualizar botones (sin perder el evento porque usamos delegación)
        const acciones = card.querySelector('.prueba-acciones');
        if (prueba.estado === 'Activa') {
            acciones.innerHTML = `
                <button class="btn-cambiar-estado btn-finalizar js-cambiar-estado" 
                        data-id="${prueba.id_prueba}" 
                        data-estado="Finalizada"
                        data-nombre="${prueba.nombre}">
                    <i class="fa-solid fa-stop-circle"></i> Finalizar prueba
                </button>
            `;
        } else {
            acciones.innerHTML = `
                <button class="btn-cambiar-estado btn-activar js-cambiar-estado" 
                        data-id="${prueba.id_prueba}" 
                        data-estado="Activa"
                        data-nombre="${prueba.nombre}">
                    <i class="fa-solid fa-play-circle"></i> Activar prueba
                </button>
            `;
        }

        // Actualizar datos
        const items = card.querySelectorAll('.dato-item');
        if (items.length >= 4) {
            items[2].innerHTML = `<i class="fa-solid fa-users"></i> <span>${prueba.participantes || 0} participantes</span>`;
            items[3].innerHTML = `<i class="fa-solid fa-check-circle"></i> <span>${prueba.consentimientos || 0} consentimientos</span>`;
        }

        // Actualizar contadores
        actualizarContadores();
    }

    // ========================================== */
    // OBTENER CLASE DE BADGE
    // ========================================== */
    function getBadgeClass(estado) {
        switch (estado) {
            case 'Activa': return 'badge-activa';
            case 'Finalizada': return 'badge-finalizada';
            default: return 'badge-planeada';
        }
    }

    // ========================================== */
    // MOSTRAR MENSAJE FLOTANTE
    // ========================================== */
    function mostrarMensaje(texto, tipo) {
        const mensajesAnteriores = document.querySelectorAll('.mensaje-flotante');
        mensajesAnteriores.forEach(el => el.remove());

        const div = document.createElement('div');
        div.className = `mensaje-flotante ${tipo}`;
        div.textContent = texto;
        div.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 14px 24px;
            border-radius: 10px;
            font-weight: 500;
            z-index: 99999;
            animation: slideIn 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            ${tipo === 'exito' 
                ? 'background: #dcfce7; color: #166534; border-left: 4px solid #22c55e;' 
                : 'background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626;'}
        `;

        document.body.appendChild(div);

        setTimeout(() => {
            div.style.opacity = '0';
            div.style.transition = 'opacity 0.3s';
            setTimeout(() => div.remove(), 300);
        }, 3000);
    }

    // ========================================== */
    // ACTUALIZAR CONTADORES EN EL HEADER
    // ========================================== */
    function actualizarContadores() {
        fetch('logica/obtener_contadores_pruebas.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cards = document.querySelectorAll('.stat-card');
                    cards.forEach(card => {
                        const icon = card.querySelector('.fa-flask');
                        if (icon) {
                            const numberSpan = card.querySelector('.stat-number');
                            if (numberSpan) {
                                numberSpan.textContent = data.activas + ' / ' + data.total;
                            }
                        }
                    });
                }
            })
            .catch(error => console.error('Error al actualizar contadores:', error));
    }
});