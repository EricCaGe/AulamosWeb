function guardarSesion(token, usuario) {
  localStorage.setItem('token', token);

  localStorage.setItem(
    'usuario',
    JSON.stringify(usuario)
  );
}

function obtenerToken() {
  return localStorage.getItem('token');
}

function obtenerUsuario() {
  const usuarioGuardado =
    localStorage.getItem('usuario');

  if (!usuarioGuardado) {
    return null;
  }

  try {
    return JSON.parse(usuarioGuardado);
  } catch {
    return null;
  }
}

function cerrarSesion() {
  localStorage.removeItem('token');
  localStorage.removeItem('usuario');

  window.location.href =
    '../InicioSesion/login.php';
}

function protegerPagina(rolPermitido = null) {
  const token = obtenerToken();
  const usuario = obtenerUsuario();

  if (!token || !usuario) {
    window.location.href =
      '../InicioSesion/login.php';

    return false;
  }

  const rolUsuario = String(
    usuario.rol ||
      usuario.nombre_rol ||
      ''
  ).toLowerCase();

  if (
    rolPermitido &&
    rolUsuario !== rolPermitido.toLowerCase()
  ) {
    alert(
      'No tienes permiso para acceder a esta página.'
    );

    cerrarSesion();
    return false;
  }

  return true;
}