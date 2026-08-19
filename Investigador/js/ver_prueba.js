// =============================================
// FUNCIONES PARA MANEJAR PARTICIPANTES EN TIEMPO REAL
// =============================================

function toggleParticipante(checkbox, idUsuario) {
    const seleccionado = checkbox.checked;
    const btnExp = document.getElementById('btn_exp_' + idUsuario);
    const btnControl = document.getElementById('btn_control_' + idUsuario);
    const consentCheck = document.getElementById('consent_' + idUsuario);
    const estadoRegistro = document.getElementById('estado_' + idUsuario);
    const badgeRegistrado = document.getElementById('badge_' + idUsuario);
    const card = document.getElementById('card_' + idUsuario);
    const inputGrupo = document.getElementById('input_grupo_' + idUsuario);
    
    if (seleccionado) {
        btnExp.disabled = false;
        btnControl.disabled = false;
        btnExp.style.opacity = '1';
        btnControl.style.opacity = '1';
        consentCheck.disabled = false;
        if (card) card.classList.add('seleccionado');
        if (estadoRegistro) {
            estadoRegistro.className = 'estado-registro no-registrado';
            estadoRegistro.innerHTML = '<i class="fa-regular fa-circle"></i> Sin consentimiento';
        }
        if (badgeRegistrado) {
            badgeRegistrado.style.display = 'none';
        }
    } else {
        btnExp.disabled = true;
        btnControl.disabled = true;
        btnExp.style.opacity = '0.4';
        btnControl.style.opacity = '0.4';
        btnExp.classList.remove('activo');
        btnControl.classList.remove('activo');
        consentCheck.checked = false;
        consentCheck.disabled = true;
        if (inputGrupo) inputGrupo.value = 'Control';
        if (card) card.classList.remove('seleccionado');
        if (estadoRegistro) {
            estadoRegistro.className = 'estado-registro no-registrado';
            estadoRegistro.innerHTML = '<i class="fa-regular fa-circle"></i> Sin registrar';
        }
        if (badgeRegistrado) {
            badgeRegistrado.style.display = 'none';
        }
    }
}

function seleccionarGrupo(idUsuario, grupo) {
    const btnExp = document.getElementById('btn_exp_' + idUsuario);
    const btnControl = document.getElementById('btn_control_' + idUsuario);
    const inputGrupo = document.getElementById('input_grupo_' + idUsuario);
    const checkbox = document.getElementById('chk_' + idUsuario);
    
    if (!checkbox.checked) return;
    
    if (grupo === 'Experimental') {
        btnExp.classList.add('activo');
        btnControl.classList.remove('activo');
        if (inputGrupo) inputGrupo.value = 'Experimental';
    } else {
        btnControl.classList.add('activo');
        btnExp.classList.remove('activo');
        if (inputGrupo) inputGrupo.value = 'Control';
    }
}

function toggleConsentimiento(idUsuario, marcado) {
    const estadoRegistro = document.getElementById('estado_' + idUsuario);
    const badgeRegistrado = document.getElementById('badge_' + idUsuario);
    const checkbox = document.getElementById('chk_' + idUsuario);
    
    if (!checkbox.checked) {
        document.getElementById('consent_' + idUsuario).checked = false;
        return;
    }
    
    if (marcado) {
        if (estadoRegistro) {
            estadoRegistro.className = 'estado-registro registrado';
            estadoRegistro.innerHTML = '<i class="fa-solid fa-check-circle"></i> Consentimiento registrado';
        }
        if (badgeRegistrado) {
            badgeRegistrado.style.display = 'inline-flex';
            badgeRegistrado.innerHTML = '<i class="fa-solid fa-check-circle"></i> Consentimiento registrado';
        }
    } else {
        if (estadoRegistro) {
            estadoRegistro.className = 'estado-registro no-registrado';
            estadoRegistro.innerHTML = '<i class="fa-regular fa-circle"></i> Sin consentimiento';
        }
        if (badgeRegistrado) {
            badgeRegistrado.style.display = 'none';
        }
    }
}

// =============================================
// SELECCIONAR TODOS
// =============================================

document.addEventListener('DOMContentLoaded', function() {
    const btnSeleccionarTodos = document.getElementById('btnSeleccionarTodos');
    if (btnSeleccionarTodos) {
        btnSeleccionarTodos.addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.checkbox-participante');
            const todosSeleccionados = document.querySelectorAll('.checkbox-participante:checked').length === checkboxes.length;
            
            checkboxes.forEach(function(cb) {
                cb.checked = !todosSeleccionados;
                const event = new Event('change');
                cb.dispatchEvent(event);
                toggleParticipante(cb, parseInt(cb.dataset.id));
            });
            
            this.innerHTML = todosSeleccionados 
                ? '<i class="fa-solid fa-check-double"></i> Seleccionar todos'
                : '<i class="fa-regular fa-square"></i> Deseleccionar todos';
        });
    }
    
    // Inicializar estado de los participantes
    document.querySelectorAll('.checkbox-participante').forEach(function(cb) {
        const idUsuario = parseInt(cb.dataset.id);
        if (!cb.checked) {
            const btnExp = document.getElementById('btn_exp_' + idUsuario);
            const btnControl = document.getElementById('btn_control_' + idUsuario);
            const consentCheck = document.getElementById('consent_' + idUsuario);
            
            if (btnExp) { btnExp.disabled = true; btnExp.style.opacity = '0.4'; }
            if (btnControl) { btnControl.disabled = true; btnControl.style.opacity = '0.4'; }
            if (consentCheck) { consentCheck.disabled = true; }
        }
    });
});