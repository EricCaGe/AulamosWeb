<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/bootstrap.php';

requerirMetodo('POST');

$idUsuario =
    obtenerIdUsuarioSesion();

$rol =
    obtenerRolUsuarioSesion();

$entrada =
    obtenerEntradaJson();

$mensaje = trim(
    (string) (
        $entrada['mensaje'] ??
        ''
    )
);

if ($mensaje === '') {
    responderJson(
        [
            'success' => false,
            'message' =>
                'Escribe una pregunta para AulaBot.',
        ],
        422
    );
}

if (mb_strlen($mensaje) > 1000) {
    responderJson(
        [
            'success' => false,
            'message' =>
                'La pregunta no puede superar los 1000 caracteres.',
        ],
        422
    );
}

if (!function_exists('curl_init')) {
    responderJson(
        [
            'success' => false,
            'message' =>
                'La extensión cURL de PHP no está habilitada.',
        ],
        500
    );
}

/*
|--------------------------------------------------------------------------
| PUENTE WEB -> CORE NODE
|--------------------------------------------------------------------------
|
| Web ya no genera una respuesta con un cerebro independiente.
|
| PHP:
| - valida la sesión Web;
| - obtiene el usuario verdadero;
| - obtiene el rol verdadero;
| - envía esos datos al Core Node por localhost.
|
| Node:
| - obtiene contexto;
| - obtiene memoria;
| - consulta Gemini;
| - guarda la interacción;
| - devuelve la respuesta.
|
*/

$urlNode =
    'http://127.0.0.1:3000' .
    '/api/chatbot/web/mensaje';

$cuerpo = json_encode(
    [
        'idUsuario' =>
            $idUsuario,

        'rol' =>
            $rol,

        'mensaje' =>
            $mensaje,
    ],
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);

if ($cuerpo === false) {
    responderJson(
        [
            'success' => false,
            'message' =>
                'No se pudo preparar la solicitud de AulaBot.',
        ],
        500
    );
}

$curl =
    curl_init($urlNode);

curl_setopt_array(
    $curl,
    [
        CURLOPT_POST =>
            true,

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_CONNECTTIMEOUT =>
            5,

        CURLOPT_TIMEOUT =>
            125,

        CURLOPT_HTTPHEADER =>
            [
                'Content-Type: application/json',
                'Accept: application/json',
            ],

        CURLOPT_POSTFIELDS =>
            $cuerpo,
    ]
);

$respuestaCruda =
    curl_exec($curl);

if ($respuestaCruda === false) {
    $detalle =
        curl_error($curl);

    curl_close($curl);

    error_log(
        'AulaBot Web no pudo conectar con Node: ' .
        $detalle
    );

    responderJson(
        [
            'success' => false,
            'message' =>
                'AulaBot no está disponible en este momento. Verifica que el servidor Node.js esté encendido.',
        ],
        503
    );
}

$codigoHttp =
    (int) curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );

curl_close($curl);

$datos =
    json_decode(
        $respuestaCruda,
        true
    );

if (!is_array($datos)) {
    responderJson(
        [
            'success' => false,
            'message' =>
                'El servidor central de AulaBot devolvió una respuesta inválida.',
        ],
        502
    );
}

if (
    $codigoHttp < 200 ||
    $codigoHttp >= 300
) {
    $mensajeError =
        trim(
            (string) (
                $datos['mensaje'] ??
                $datos['message'] ??
                'AulaBot no pudo completar la solicitud.'
            )
        );

    responderJson(
        [
            'success' => false,
            'message' =>
                $mensajeError,
        ],
        (
            $codigoHttp >= 400 &&
            $codigoHttp <= 599
        )
            ? $codigoHttp
            : 502
    );
}

$respuestaTexto =
    trim(
        (string) (
            $datos['respuesta'] ??
            ''
        )
    );

if ($respuestaTexto === '') {
    responderJson(
        [
            'success' => false,
            'message' =>
                'AulaBot no devolvió una respuesta válida.',
        ],
        502
    );
}

responderJson([
    'success' => true,

    'respuesta' =>
        $respuestaTexto,

    'tipoConsulta' =>
        $datos['tipoConsulta']
        ?? 'General',

    'origenConocimiento' =>
        $datos['origenConocimiento']
        ?? 'IA Generativa',

    'tiempoRespuestaMs' =>
        $datos['tiempoRespuestaMs']
        ?? null,

    'idSesion' =>
        $datos['idSesion']
        ?? null,

    'idMensaje' =>
        $datos['idMensaje']
        ?? null,

    'rol' =>
        $rol,

    'acciones' =>
        is_array(
            $datos['acciones'] ??
            null
        )
            ? $datos['acciones']
            : [],
]);