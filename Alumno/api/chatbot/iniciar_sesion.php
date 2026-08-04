<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requerirMetodo('POST');

$idUsuario = obtenerIdUsuarioSesion();
$moduloOrigen = 'Web Alumno';

/*
|--------------------------------------------------------------------------
| Buscar una sesión abierta
|--------------------------------------------------------------------------
*/

$consulta = $bdChatbot->prepare(
    '
        SELECT id_sesion
        FROM sesiones_chatbot
        WHERE id_usuario = ?
          AND modulo_origen = ?
          AND fecha_fin IS NULL
        ORDER BY id_sesion DESC
        LIMIT 1
    '
);

$consulta->bind_param(
    'is',
    $idUsuario,
    $moduloOrigen
);

$consulta->execute();
$consulta->bind_result($idSesionExistente);

if ($consulta->fetch()) {
    $consulta->close();

    responderJson([
        'success' => true,
        'idSesion' => (int) $idSesionExistente,
        'nuevaSesion' => false,
    ]);
}

$consulta->close();

/*
|--------------------------------------------------------------------------
| Crear una nueva sesión
|--------------------------------------------------------------------------
*/

$insercion = $bdChatbot->prepare(
    '
        INSERT INTO sesiones_chatbot (
            id_usuario,
            modulo_origen
        )
        VALUES (?, ?)
    '
);

$insercion->bind_param(
    'is',
    $idUsuario,
    $moduloOrigen
);

$insercion->execute();

$idSesionNueva = $insercion->insert_id;

$insercion->close();

responderJson(
    [
        'success' => true,
        'idSesion' => (int) $idSesionNueva,
        'nuevaSesion' => true,
    ],
    201
);