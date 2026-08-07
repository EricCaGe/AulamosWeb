<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requerirMetodo('POST');

$idUsuario = obtenerIdUsuarioSesion();
$rol = obtenerRolUsuarioSesion();
$moduloOrigen = obtenerModuloOrigenPorRol($rol);

$datos = obtenerEntradaJson();

$idSesion = filter_var(
    $datos['idSesion'] ?? null,
    FILTER_VALIDATE_INT
);

$pregunta = trim(
    (string) ($datos['pregunta'] ?? '')
);

$respuesta = trim(
    (string) ($datos['respuesta'] ?? '')
);

$modeloIa = trim(
    (string) ($datos['modeloIa'] ?? 'Gemini')
);

$tiempoRespuesta = filter_var(
    $datos['tiempoRespuestaMs'] ?? 0,
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

if ($pregunta === '') {
    responderJson(
        [
            'success' => false,
            'message' => 'La pregunta está vacía.',
        ],
        422
    );
}

if ($respuesta === '') {
    responderJson(
        [
            'success' => false,
            'message' => 'La respuesta está vacía.',
        ],
        422
    );
}

if (mb_strlen($pregunta) > 5000) {
    responderJson(
        [
            'success' => false,
            'message' => 'La pregunta es demasiado extensa.',
        ],
        422
    );
}

if (mb_strlen($respuesta) > 50000) {
    responderJson(
        [
            'success' => false,
            'message' => 'La respuesta es demasiado extensa.',
        ],
        422
    );
}

$modeloIa = mb_substr(
    $modeloIa !== '' ? $modeloIa : 'Gemini',
    0,
    100
);

$tiempoRespuesta = max(
    0,
    min((int) $tiempoRespuesta, 300000)
);

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
          AND fecha_fin IS NULL
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
            'message' => 'La sesión de AulaBot no existe o ya fue cerrada.',
        ],
        403
    );
}

$verificacion->close();

/*
|--------------------------------------------------------------------------
| Guardar la pregunta y la respuesta
|--------------------------------------------------------------------------
*/

$insercion = $bdChatbot->prepare(
    "
        INSERT INTO mensajes_chatbot (
            id_sesion,
            pregunta,
            respuesta,
            tipo_consulta,
            modelo_ia,
            origen_conocimiento,
            tipo_respuesta,
            nivel_respuesta,
            tiempo_respuesta_ms
        )
        VALUES (
            ?,
            ?,
            ?,
            'General',
            ?,
            'IA Generativa',
            'Texto',
            'Intermedio',
            ?
        )
    "
);

$insercion->bind_param(
    'isssi',
    $idSesion,
    $pregunta,
    $respuesta,
    $modeloIa,
    $tiempoRespuesta
);

$insercion->execute();

$idMensaje = $insercion->insert_id;

$insercion->close();

responderJson(
    [
        'success' => true,
        'idMensaje' => (int) $idMensaje,
        'message' => 'Interacción guardada correctamente.',
    ],
    201
);