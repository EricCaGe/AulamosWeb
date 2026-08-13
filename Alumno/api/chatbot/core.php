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

/*
 * El navegador jamás decide
 * idUsuario ni rol.
 */
unset(
    $entrada['idUsuario'],
    $entrada['rol']
);

$entrada['idUsuario'] =
    $idUsuario;

$entrada['rol'] =
    $rol;

$cuerpo = json_encode(
    $entrada,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);

if ($cuerpo === false) {
    responderJson(
        [
            'success' => false,
            'message' =>
                'No se pudo preparar la solicitud.',
        ],
        500
    );
}

$curl = curl_init(
    'http://127.0.0.1:3000/api/chatbot/web/accion'
);

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

$respuesta =
    curl_exec($curl);

if ($respuesta === false) {
    $detalle =
        curl_error($curl);

    curl_close($curl);

    error_log(
        'AulaBot core.php: ' .
        $detalle
    );

    responderJson(
        [
            'success' => false,
            'message' =>
                'No fue posible conectar con el servidor central de AulaBot.',
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
        $respuesta,
        true
    );

if (!is_array($datos)) {
    responderJson(
        [
            'success' => false,
            'message' =>
                'AulaBot devolvió una respuesta inválida.',
        ],
        502
    );
}

responderJson(
    $datos,
    (
        $codigoHttp >= 100 &&
        $codigoHttp <= 599
    )
        ? $codigoHttp
        : 500
);