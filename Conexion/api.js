const hostApi =
  window.location.hostname || 'localhost';

const API_URL =
  `http://${hostApi}:3000/api`;

async function peticionAPI(ruta, opciones = {}) {
  const token = localStorage.getItem('token');

  const headers = {
    ...(token
      ? {
          Authorization: `Bearer ${token}`,
        }
      : {}),
    ...(opciones.headers || {}),
  };

  /*
   * No se coloca Content-Type al enviar archivos,
   * porque el navegador debe configurarlo automáticamente.
   */
  if (
    !(opciones.body instanceof FormData) &&
    !headers['Content-Type']
  ) {
    headers['Content-Type'] = 'application/json';
  }

  try {
    const respuesta = await fetch(
      `${API_URL}${ruta}`,
      {
        ...opciones,
        headers,
      }
    );

    const texto = await respuesta.text();

    let datos = {};

    if (texto) {
      try {
        datos = JSON.parse(texto);
      } catch {
        datos = {
          mensaje: texto,
        };
      }
    }

    if (!respuesta.ok) {
      throw new Error(
        datos.mensaje ||
          `Error ${respuesta.status} al consultar la API`
      );
    }

    return datos;
  } catch (error) {
    if (error instanceof TypeError) {
      throw new Error(
        'No se pudo conectar con el servidor AULAMOS.'
      );
    }

    throw error;
  }
}