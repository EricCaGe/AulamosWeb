// ==========================================
// PERFIL ALUMNO - FUNCIONES
// ==========================================

document.addEventListener('DOMContentLoaded', function () {

    console.log('👤 Perfil de Alumno cargado');


    // ==========================================
    // VALIDACIÓN CAMBIO DE CONTRASEÑA
    // ==========================================

    const formPassword = document
        .querySelector(
            'form[action=""] input[name="accion"][value="cambiar_password"]'
        )
        ?.closest('form');

    if (formPassword) {

        formPassword.addEventListener('submit', function (e) {

            const passwordNuevo =
                document.getElementById('password_nuevo').value;

            const passwordConfirmar =
                document.getElementById('password_confirmar').value;


            if (passwordNuevo.length < 6) {

                e.preventDefault();

                alert(
                    'La nueva contraseña debe tener al menos 6 caracteres.'
                );

                return;
            }


            if (passwordNuevo !== passwordConfirmar) {

                e.preventDefault();

                alert(
                    'Las contraseñas nuevas no coinciden.'
                );

                return;
            }

        });
    }


    // ==========================================
    // VALIDACIÓN EDITAR PERFIL
    // ==========================================

    const modalForm =
        document.querySelector('.modal-form');

    if (modalForm) {

        modalForm.addEventListener('submit', function (e) {

            const nombre =
                document.getElementById('edit_nombre')
                ?.value.trim();

            const apellido =
                document.getElementById('edit_apellido_paterno')
                ?.value.trim();

            const correo =
                document.getElementById('edit_correo')
                ?.value.trim();


            if (!nombre || !apellido || !correo) {

                e.preventDefault();

                alert(
                    'Los campos nombre, apellido paterno y correo son obligatorios.'
                );

                return;
            }


            // Validación básica del correo

            const correoValido =
                /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


            if (!correoValido.test(correo)) {

                e.preventDefault();

                alert(
                    'Ingresa un correo electrónico válido.'
                );

                return;
            }

        });
    }


    // ==========================================
    // CERRAR MODAL CON ESC
    // ==========================================

    document.addEventListener('keydown', function (e) {

        if (e.key === 'Escape') {

            const modal =
                document.getElementById('modalEditar');

            if (modal) {

                modal.classList.add('modal-hidden');

                document.body.style.overflow = 'auto';

            }
        }

    });


    // ==========================================
    // ACTUALIZAR TEXTO DE NOTIFICACIONES
    // ==========================================

    const notificaciones =
        document.querySelector(
            '.toggle-group .switch input'
        );

    if (notificaciones) {

        notificaciones.addEventListener(
            'change',
            function () {

                const grupo =
                    this.closest('.toggle-group');

                if (!grupo) return;

                const label =
                    grupo.querySelector('.toggle-label');

                if (label) {

                    label.textContent =
                        this.checked
                            ? 'Activadas'
                            : 'Desactivadas';

                }

            }
        );
    }

})
// ==========================================
// FOTO DE PERFIL
// ==========================================

const inputFotoPerfil =
    document.getElementById('inputFotoPerfil');

const formFotoPerfil =
    document.getElementById('formFotoPerfil');

if (inputFotoPerfil && formFotoPerfil) {

    inputFotoPerfil.addEventListener('change', function () {

        if (
            this.files &&
            this.files.length > 0
        ) {

            const archivo = this.files[0];

            const tiposPermitidos = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp'
            ];

            // Validar tipo
            if (!tiposPermitidos.includes(archivo.type)) {

                alert(
                    'Solo se permiten imágenes JPG, PNG, GIF o WEBP.'
                );

                this.value = '';

                return;
            }

            // Validar tamaño
            if (archivo.size > 2097152) {

                alert(
                    'La imagen no debe superar los 2 MB.'
                );

                this.value = '';

                return;
            }

            // Confirmar
            const confirmar = confirm(
                '¿Deseas actualizar tu foto de perfil?'
            );

            if (confirmar) {

                formFotoPerfil.submit();

            } else {

                this.value = '';

            }
        }
    });
}

