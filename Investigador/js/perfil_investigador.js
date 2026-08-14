// =============================================
// PERFIL INVESTIGADOR - FUNCIONES
// =============================================

document.addEventListener('DOMContentLoaded', function() {

    // =============================================
    // SUBIR FOTO - AUTO SUBMIT AL SELECCIONAR
    // =============================================
    const inputFoto = document.getElementById('inputFotoPerfil');
    const formFoto = document.getElementById('formFotoPerfil');

    if (inputFoto && formFoto) {
        inputFoto.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                const archivo = this.files[0];
                const extensionesPermitidas = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                const tamanoMaximo = 2097152;

                if (!extensionesPermitidas.includes(archivo.type)) {
                    alert('⚠️ Solo se permiten imágenes JPG, PNG, GIF o WEBP.');
                    this.value = '';
                    return;
                }

                if (archivo.size > tamanoMaximo) {
                    alert('⚠️ La imagen no debe superar los 2MB.');
                    this.value = '';
                    return;
                }

                if (confirm('¿Deseas actualizar tu foto de perfil?')) {
                    // Mostrar un pequeño indicador de carga
                    const btnCambiar = document.querySelector('.btn-cambiar-foto');
                    if (btnCambiar) {
                        btnCambiar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Subiendo...';
                        btnCambiar.disabled = true;
                    }
                    formFoto.submit();
                } else {
                    this.value = '';
                }
            }
        });
    }

    // =============================================
    // MODAL - ABRIR Y CERRAR
    // =============================================
    const modalEditar = document.getElementById('modalEditar');
    const btnEditarPerfil = document.getElementById('btnEditarPerfil');
    const modalCerrar = document.getElementById('modalCerrar');
    const modalCancelar = document.getElementById('modalCancelar');

    function abrirModalEditar() {
        if (modalEditar) {
            modalEditar.classList.remove('modal-hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function cerrarModalEditar() {
        if (modalEditar) {
            modalEditar.classList.add('modal-hidden');
            document.body.style.overflow = 'auto';
        }
    }

    if (btnEditarPerfil) {
        btnEditarPerfil.addEventListener('click', abrirModalEditar);
    }

    if (modalCerrar) {
        modalCerrar.addEventListener('click', cerrarModalEditar);
    }

    if (modalCancelar) {
        modalCancelar.addEventListener('click', cerrarModalEditar);
    }

    if (modalEditar) {
        modalEditar.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalEditar();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalEditar();
        }
    });

    // =============================================
    // VALIDACIÓN FORMULARIO CONTRASEÑA
    // =============================================
    const formPassword = document.getElementById('formPassword');
    if (formPassword) {
        formPassword.addEventListener('submit', function(e) {
            const passwordActual = document.getElementById('password_actual').value;
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

            if (passwordActual === passwordNuevo) {
                e.preventDefault();
                alert('La nueva contraseña debe ser diferente a la actual.');
                return;
            }
        });
    }

    // =============================================
    // VALIDACIÓN FORMULARIO EDITAR PERFIL
    // =============================================
    const formEditarPerfil = document.getElementById('formEditarPerfil');
    if (formEditarPerfil) {
        formEditarPerfil.addEventListener('submit', function(e) {
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

    console.log('👤 Perfil de Investigador cargado correctamente');
});