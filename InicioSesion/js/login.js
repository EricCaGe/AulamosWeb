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
  // 7. FUNCIONES DE API Y SESIÓN
  // =====================================================
  function peticionAPI(endpoint, options) {
    const url = `http://localhost:3000/api${endpoint}`;
    return fetch(url, options)
        .then(response => response.json())
        .then(data => {
            if (data.success === false) {
                throw new Error(data.message || 'Error en la petición');
            }
            return data;
        });
  }

  function guardarSesion(token, usuario) {
    if (token) {
        localStorage.setItem('token', token);
    }
    localStorage.setItem('usuario', JSON.stringify(usuario));
  }

  // =====================================================
  // 8. COMPARAR ROLES (CORREGIDO)
  // =====================================================
  function rolesEquivalentes(rolSeleccionado, rolReal) {
    // Normalizar ambos roles (minúsculas y sin espacios)
    const seleccionado = rolSeleccionado?.toLowerCase().trim() || '';
    const real = rolReal?.toLowerCase().trim() || '';

    // Mapeo de roles equivalentes
    const equivalencias = {
        'alumno': ['alumno', 'estudiante'],
        'docente': ['docente', 'profesor', 'maestro'],
        'investigador': ['investigador', 'investigadora'],
        'admin': ['admin', 'administrador'],
        'administrador': ['admin', 'administrador'],
    };

    // Si el rol seleccionado está en el mapeo, verificar si el rol real está en la lista
    for (const [clave, valores] of Object.entries(equivalencias)) {
        if (valores.includes(seleccionado)) {
            return valores.includes(real);
        }
    }

    // Si no está en el mapeo, comparar directamente
    return seleccionado === real;
  }

  // =====================================================
  // 9. REDIRECCIÓN (CORREGIDO)
  // =====================================================
  function redirigirSegunRol(rol) {
    const rolLower = rol?.toLowerCase().trim() || '';

    // Admin
    if (rolLower === 'administrador' || rolLower === 'admin') {
        window.location.href = '../Administrador/admin_dashboard.php';
        return;
    }

    // Docente
    if (rolLower === 'docente' || rolLower === 'profesor' || rolLower === 'maestro') {
        window.location.href = '../Docente/docente_dashboard.php';
        return;
    }

    // Alumno
    if (rolLower === 'alumno' || rolLower === 'estudiante') {
        window.location.href = '../Alumno/alumno.php';
        return;
    }

    // Investigador
    if (rolLower === 'investigador' || rolLower === 'investigadora') {
        window.location.href = '../Investigador/investigador_dashboard.php';
        return;
    }

    throw new Error('El usuario no tiene un rol reconocido.');
  }

  // =====================================================
  // 10. ENVIAR FORMULARIO
  // =====================================================
  formulario.addEventListener(
    'submit',
    async (evento) => {
      evento.preventDefault();

      const errores = validarFormulario();

      if (errores.length > 0) {
        mostrarMensaje(
          errores.join(' '),
          true
        );

        return;
      }

      mostrarMensaje('Iniciando sesión...');

      const botonSubmit =
        formulario.querySelector(
          'button[type="submit"], input[type="submit"]'
        );

      if (botonSubmit) {
        botonSubmit.disabled = true;
      }

      try {
        const datos = await peticionAPI(
          '/auth/login',
          {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              correo: correoInput.value.trim(),
              password: passwordInput.value,
            }),
          }
        );

        if (!datos.usuario) {
          throw new Error(
            'La respuesta del servidor no contiene una sesión válida.'
          );
        }

        const rolReal = String(
          datos.usuario.rol ||
          datos.usuario.nombre_rol ||
          datos.rol ||
          ''
        );

        const rolSeleccionado = String(
          rolInput?.value || ''
        );

        if (
          rolSeleccionado &&
          !rolesEquivalentes(
            rolSeleccionado,
            rolReal
          )
        ) {
          throw new Error(
            `La cuenta ingresada no pertenece al rol ${rolSeleccionado}.`
          );
        }

        guardarSesion(
          datos.token || 'sesion_activa',
          datos.usuario
        );

        mostrarMensaje(
          'Inicio de sesión correcto.'
        );

        redirigirSegunRol(rolReal);
      } catch (error) {
        mostrarMensaje(
          error.message ||
            'No se pudo iniciar sesión.',
          true
        );
      } finally {
        if (botonSubmit) {
          botonSubmit.disabled = false;
        }
      }
    }
  );
});