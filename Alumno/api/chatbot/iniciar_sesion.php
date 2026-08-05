<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requerirMetodo('POST');

$idUsuario = obtenerIdUsuarioSesion();
$entrada = obtenerEntradaJson();

$rol = strtolower(
    trim((string) ($entrada['rol'] ?? 'alumno'))
);

if (!in_array($rol, ['alumno', 'docente'], true)) {
    $rol = 'alumno';
}

$moduloOrigen =
    $rol === 'docente'
        ? 'Web Docente'
        : 'Web Alumno';

/*
|--------------------------------------------------------------------------
| Buscar una sesión abierta del módulo correcto
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
        'moduloOrigen' => $moduloOrigen,
        'rol' => $rol,
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