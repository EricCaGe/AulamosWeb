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

if (!$idSesion || $idSesion <= 0) {
    responderJson(
        [
            'success' => false,
            'message' => 'El ID de la sesión no es válido.',
        ],
        422
    );
}

$actualizacion = $bdChatbot->prepare(
    '
        UPDATE sesiones_chatbot
        SET fecha_fin = CURRENT_TIMESTAMP
        WHERE id_sesion = ?
          AND id_usuario = ?
          AND modulo_origen = ?
          AND fecha_fin IS NULL
    '
);

$actualizacion->bind_param(
    'iis',
    $idSesion,
    $idUsuario,
    $moduloOrigen
);

$actualizacion->execute();

$filasActualizadas = $actualizacion->affected_rows;

$actualizacion->close();

responderJson([
    'success' => true,
    'sesionCerrada' => $filasActualizadas > 0,
]);