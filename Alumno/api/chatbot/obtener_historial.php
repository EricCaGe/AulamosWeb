<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requerirMetodo('GET');

$idUsuario = obtenerIdUsuarioSesion();

obtenerRolUsuarioSesion();

$idSesion = filter_input(
    INPUT_GET,
    'id_sesion',
    FILTER_VALIDATE_INT
);

if (!$idSesion || $idSesion <= 0) {
    responderJson(
        [
            'success' => false,
            'message' => 'La conversación no es válida.',
        ],
        422
    );
}

$verificacion = $bdChatbot->prepare(
    '
        SELECT
            modulo_origen,
            fecha_fin

        FROM sesiones_chatbot

        WHERE id_sesion = ?
          AND id_usuario = ?

        LIMIT 1
    '
);

$verificacion->bind_param(
    'ii',
    $idSesion,
    $idUsuario
);

$verificacion->execute();

$verificacion->bind_result(
    $moduloOrigen,
    $fechaFin
);

if (!$verificacion->fetch()) {
    $verificacion->close();

    responderJson(
        [
            'success' => false,
            'message' =>
                'No tienes acceso a esta conversación.',
        ],
        403
    );
}

$verificacion->close();

$consulta = $bdChatbot->prepare(
    '
        SELECT
            id_mensaje,
            pregunta,
            respuesta,
            tipo_consulta,
            modelo_ia,
            origen_conocimiento,
            tipo_respuesta,
            nivel_respuesta,
            utilidad_usuario,
            tiempo_respuesta_ms,
            fecha_mensaje

        FROM mensajes_chatbot

        WHERE id_sesion = ?

        ORDER BY
            fecha_mensaje ASC,
            id_mensaje ASC

        LIMIT 100
    '
);

$consulta->bind_param(
    'i',
    $idSesion
);

$consulta->execute();

$resultado =
    $consulta->get_result();

$interacciones = [];

while (
    $fila =
        $resultado->fetch_assoc()
) {
    $interacciones[] = [
        'idMensaje' =>
            (int) $fila['id_mensaje'],

        'pregunta' =>
            (string) $fila['pregunta'],

        'respuesta' =>
            (string) $fila['respuesta'],

        'tipoConsulta' =>
            $fila['tipo_consulta'],

        'modeloIa' =>
            $fila['modelo_ia'],

        'origenConocimiento' =>
            $fila['origen_conocimiento'],

        'tipoRespuesta' =>
            $fila['tipo_respuesta'],

        'nivelRespuesta' =>
            $fila['nivel_respuesta'],

        'utilidadUsuario' =>
            $fila['utilidad_usuario'],

        'tiempoRespuestaMs' =>
            $fila['tiempo_respuesta_ms'] !== null
                ? (int) $fila['tiempo_respuesta_ms']
                : null,

        'fechaMensaje' =>
            $fila['fecha_mensaje'],
    ];
}

$consulta->close();

responderJson([
    'success' => true,

    'idSesion' =>
        (int) $idSesion,

    'moduloOrigen' =>
        (string) $moduloOrigen,

    'activa' =>
        $fechaFin === null,

    'interacciones' =>
        $interacciones,
]);