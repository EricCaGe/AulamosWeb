<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requerirMetodo('GET');

$idUsuario = obtenerIdUsuarioSesion();
$rol = obtenerRolUsuarioSesion();
$moduloOrigen = obtenerModuloOrigenPorRol($rol);

$idSesion = filter_input(
    INPUT_GET,
    'id_sesion',
    FILTER_VALIDATE_INT
);

if (!$idSesion || $idSesion <= 0) {
    responderJson(
        [
            'success' => false,
            'message' => 'El ID de la sesión no es válido.',
        ],
        422
    );
}

/*
|--------------------------------------------------------------------------
| Verificar que la sesión pertenece al usuario
|--------------------------------------------------------------------------
*/

$verificacion = $bdChatbot->prepare(
    '
        SELECT id_sesion
        FROM sesiones_chatbot
        WHERE id_sesion = ?
          AND id_usuario = ?
          AND modulo_origen = ?
        LIMIT 1
    '
);

$verificacion->bind_param(
    'iis',
    $idSesion,
    $idUsuario,
    $moduloOrigen
);

$verificacion->execute();
$verificacion->store_result();

if ($verificacion->num_rows === 0) {
    $verificacion->close();

    responderJson(
        [
            'success' => false,
            'message' => 'No tienes acceso a esta conversación.',
        ],
        403
    );
}

$verificacion->close();

/*
|--------------------------------------------------------------------------
| Obtener las interacciones
|--------------------------------------------------------------------------
*/

$consulta = $bdChatbot->prepare(
    '
        SELECT
            id_mensaje,
            pregunta,
            respuesta,
            modelo_ia,
            tiempo_respuesta_ms,
            fecha_mensaje
        FROM mensajes_chatbot
        WHERE id_sesion = ?
        ORDER BY fecha_mensaje ASC, id_mensaje ASC
        LIMIT 100
    '
);

$consulta->bind_param(
    'i',
    $idSesion
);

$consulta->execute();

$resultado = $consulta->get_result();
$interacciones = [];

while ($fila = $resultado->fetch_assoc()) {
    $interacciones[] = [
        'idMensaje' => (int) $fila['id_mensaje'],
        'pregunta' => $fila['pregunta'],
        'respuesta' => $fila['respuesta'],
        'modeloIa' => $fila['modelo_ia'],
        'tiempoRespuestaMs' =>
            $fila['tiempo_respuesta_ms'] !== null
                ? (int) $fila['tiempo_respuesta_ms']
                : null,
        'fechaMensaje' => $fila['fecha_mensaje'],
    ];
}

$consulta->close();

responderJson([
    'success' => true,
    'idSesion' => (int) $idSesion,
    'interacciones' => $interacciones,
]);