// ========================================== */
// PERFIL ADMIN - FUNCIONES                   */
// ========================================== */

document.addEventListener('DOMContentLoaded', function() {
    console.log('👤 Perfil de Administrador cargado');

    // Validación del formulario de cambio de contraseña
    const formPassword = document.querySelector('form[action=""] input[name="accion"][value="cambiar_password"]')?.closest('form');
    if (formPassword) {
        formPassword.addEventListener('submit', function(e) {
            const passwordNuevo = document.getElementById('password_nuevo').value;
            const passwordConfirmar = document.getElementById('password_confirmar').value;

            if (passwordNuevo.length < 6) {
                e.preventDefault();
                alert('La nueva contraseña debe tener al menos 6 caracteres.');
                return;
            }

            if (passwordNuevo !== passwordConfirmar) {
                e.preventDefault();
                alert('Las contraseñas no coinciden.');
                return;
            }
        });
    }

    // Validación del formulario de editar perfil
    const modalForm = document.querySelector('.modal-form');
    if (modalForm) {
        modalForm.addEventListener('submit', function(e) {
            const nombre = document.getElementById('edit_nombre').value.trim();
            const apellido = document.getElementById('edit_apellido_paterno').value.trim();
            const correo = document.getElementById('edit_correo').value.trim();

            if (!nombre || !apellido || !correo) {
                e.preventDefault();
                alert('Los campos nombre, apellido paterno y correo son obligatorios.');
                return;
            }

            if (!correo.includes('@')) {
                e.preventDefault();
                alert('Ingresa un correo electrónico válido.');
                return;
            }
        });
    }
});