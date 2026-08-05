document.addEventListener('DOMContentLoaded', () => {
  // =====================================================
  // 1. ELEMENTOS DEL INICIO DE SESIÓN
  // =====================================================
  const formulario =
    document.getElementById('formLogin') ||
    document.querySelector('.login-form');

  const correoInput =
    document.getElementById('login-email') ||
    document.getElementById('correo');

  const passwordInput =
    document.getElementById('login-password') ||
    document.getElementById('password');

  const mensaje =
    document.getElementById('mensaje');

  const rolInput =
    document.getElementById('rol-input');

  if (!formulario) {
    console.error('No se encontró el formulario de inicio de sesión.');
    return;
  }

  if (!correoInput || !passwordInput) {
    console.error(
      'No se encontraron los campos de correo o contraseña.'
    );
    return;
  }

  // =====================================================
  // 2. MOSTRAR MENSAJES
  // =====================================================
  function mostrarMensaje(texto, esError = false) {
    if (mensaje) {
      mensaje.textContent = texto;
      mensaje.style.color = esError
        ? '#dc2626'
        : '#2D5BFF';
    } else if (esError) {
      alert(texto);
    }
  }

  // =====================================================
  // 3. NAVEGACIÓN CON FLECHAS
  // =====================================================
  function obtenerElementosInteractivos() {
    const elementos = document.querySelectorAll(
      'button, a[href], input, select, textarea, ' +
      '[tabindex]:not([tabindex="-1"])'
    );

    return Array.from(elementos).filter((elemento) => {
      return (
        elemento.offsetParent !== null &&
        !elemento.disabled &&
        elemento.getAttribute('aria-hidden') !== 'true'
      );
    });
  }

  document.addEventListener('keydown', (evento) => {
    const elementoActivo = document.activeElement;

    const etiqueta = elementoActivo
      ? elementoActivo.tagName.toLowerCase()
      : '';

    const esCampoDeFormulario = [
      'input',
      'textarea',
      'select',
    ].includes(etiqueta);

    if (esCampoDeFormulario) {
      return;
    }

    if (
      evento.key !== 'ArrowRight' &&
      evento.key !== 'ArrowLeft'
    ) {
      return;
    }

    evento.preventDefault();

    const elementos = obtenerElementosInteractivos();

    if (elementos.length === 0) {
      return;
    }

    const indiceActual =
      elementos.indexOf(elementoActivo);

    let nuevoIndice;

    if (indiceActual === -1) {
      nuevoIndice = 0;
    } else if (evento.key === 'ArrowRight') {
      nuevoIndice =
        (indiceActual + 1) % elementos.length;
    } else {
      nuevoIndice =
        (indiceActual - 1 + elementos.length) %
        elementos.length;
    }

    elementos[nuevoIndice].focus();
  });

  // =====================================================
  // 4. MOSTRAR U OCULTAR CONTRASEÑA
  // =====================================================
  const botonMostrarPassword =
    document.querySelector('.toggle-password-icon');

  if (botonMostrarPassword) {
    function cambiarVisibilidadPassword() {
      const estaOculta =
        passwordInput.type === 'password';

      passwordInput.type =
        estaOculta ? 'text' : 'password';

      botonMostrarPassword.classList.toggle(
        'fa-eye',
        !estaOculta
      );

      botonMostrarPassword.classList.toggle(
        'fa-eye-slash',
        estaOculta
      );

      botonMostrarPassword.setAttribute(
        'aria-label',
        estaOculta
          ? 'Ocultar contraseña'
          : 'Mostrar contraseña'
      );
    }

    botonMostrarPassword.addEventListener(
      'click',
      cambiarVisibilidadPassword
    );

    botonMostrarPassword.addEventListener(
      'keydown',
      (evento) => {
        if (
          evento.key === 'Enter' ||
          evento.key === ' '
        ) {
          evento.preventDefault();
          cambiarVisibilidadPassword();
        }
      }
    );
  }

  // =====================================================
  // 5. SELECTOR DE ROL
  // =====================================================
  const botonesRol =
    document.querySelectorAll('.btn-role');

  botonesRol.forEach((boton) => {
    boton.addEventListener('click', () => {
      botonesRol.forEach((otroBoton) => {
        otroBoton.classList.remove('active');
        otroBoton.setAttribute(
          'aria-pressed',
          'false'
        );
      });

      boton.classList.add('active');
      boton.setAttribute('aria-pressed', 'true');

      if (rolInput) {
        rolInput.value =
          boton.getAttribute('data-rol') || '';
      }
    });
  });

  // =====================================================
  // 6. VALIDAR FORMULARIO
  // =====================================================
  function validarFormulario() {
    const errores = [];

    const correo =
      correoInput.value.trim();

    const password =
      passwordInput.value;

    const expresionCorreo =
      /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    correoInput.style.borderColor = '';
    passwordInput.style.borderColor = '';

    if (!correo) {
      errores.push(
        'El correo electrónico es obligatorio.'
      );

      correoInput.style.borderColor = '#dc2626';
    } else if (!expresionCorreo.test(correo)) {
      errores.push(
        'El correo electrónico no es válido.'
      );

      correoInput.style.borderColor = '#dc2626';
    }

    if (!password.trim()) {
      errores.push(
        'La contraseña es obligatoria.'
      );

      passwordInput.style.borderColor = '#dc2626';
    }

    if (rolInput && !rolInput.value) {
      errores.push(
        'Debes seleccionar un rol.'
      );
    }

    return errores;
  }

  // =====================================================
  // 7. ENVIAR FORMULARIO (SIN FETCH, SOLO PHP)
  // =====================================================
  formulario.addEventListener(
    'submit',
    (evento) => {
      const errores = validarFormulario();

      if (errores.length > 0) {
        evento.preventDefault();
        mostrarMensaje(
          errores.join(' '),
          true
        );
        return;
      }

      // Si no hay errores, el formulario se envía normalmente a procesar_login.php
      mostrarMensaje('Iniciando sesión...');
      // El formulario se envía solo
    }
  );

});