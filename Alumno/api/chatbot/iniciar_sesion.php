<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requerirMetodo('POST');

$idUsuario = obtenerIdUsuarioSesion();
$rol = obtenerRolUsuarioSesion();

/*
|--------------------------------------------------------------------------
| Buscar la conversación abierta del usuario
|--------------------------------------------------------------------------
| La conversación pertenece al usuario, no al dispositivo.
| Por eso puede haber sido iniciada desde Web o desde Móvil.
*/

$consulta = $bdChatbot->prepare(
    '
        SELECT
            id_sesion,
            modulo_origen
        FROM sesiones_chatbot
        WHERE id_usuario = ?
          AND fecha_fin IS NULL
        ORDER BY id_sesion DESC
        LIMIT 1
    '
);

$consulta->bind_param(
    'i',
    $idUsuario
);

$consulta->execute();

$consulta->bind_result(
    $idSesionExistente,
    $moduloOrigenExistente
);

if ($consulta->fetch()) {
    $consulta->close();

    responderJson([
        'success' => true,
        'idSesion' => (int) $idSesionExistente,
        'nuevaSesion' => false,
        'moduloOrigen' => (string) $moduloOrigenExistente,
        'rol' => $rol,
    ]);
}

$consulta->close();

/*
|--------------------------------------------------------------------------
| Crear conversación nueva
|--------------------------------------------------------------------------
| Solo se crea si el usuario no tiene ninguna conversación abierta.
*/

$moduloOrigen = obtenerModuloOrigenPorRol($rol);

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

$idSesionNueva = (int) $insercion->insert_id;

$insercion->close();

responderJson(
    [
        'success' => true,
        'idSesion' => $idSesionNueva,
        'nuevaSesion' => true,
        'moduloOrigen' => $moduloOrigen,
        'rol' => $rol,
    ],
    201
);