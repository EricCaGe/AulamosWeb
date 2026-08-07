<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../../../Conexion/conexion.php';

/*
|--------------------------------------------------------------------------
| Detectar la variable de conexión
|--------------------------------------------------------------------------
| Permite trabajar aunque conexion.php utilice:
| $conexion, $conn, $mysqli o $db.
*/

$bdChatbot = null;

$posiblesConexiones = [
    'conexion',
    'conn',
    'mysqli',
    'db',
];

foreach ($posiblesConexiones as $nombreVariable) {
    if (
        isset($$nombreVariable) &&
        $$nombreVariable instanceof mysqli
    ) {
        $bdChatbot = $$nombreVariable;
        break;
    }
}

if (!$bdChatbot instanceof mysqli) {
    responderJson(
        [
            'success' => false,
            'message' => 'No se encontró una conexión válida con MySQL.',
        ],
        500
    );
}

$bdChatbot->set_charset('utf8mb4');

/**
 * Envía una respuesta JSON y termina la ejecución.
 */
function responderJson(
    array $datos,
    int $codigoEstado = 200
): never {
    http_response_code($codigoEstado);

    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

/**
 * Obtiene el ID del usuario autenticado.
 */
function obtenerIdUsuarioSesion(): int
{
    $idUsuario =
        $_SESSION['usuario']['id_usuario'] ??
        $_SESSION['id_usuario'] ??
        0;

    $idUsuario = filter_var(
        $idUsuario,
        FILTER_VALIDATE_INT
    );

    if (!$idUsuario || $idUsuario <= 0) {
        responderJson(
            [
                'success' => false,
                'message' => 'La sesión del usuario no es válida.',
            ],
            401
        );
    }

    return (int) $idUsuario;
}

/**
 * Lee el cuerpo JSON enviado por JavaScript.
 */
function obtenerEntradaJson(): array
{
    $contenido = file_get_contents('php://input');

    if (!$contenido) {
        return [];
    }

    $datos = json_decode($contenido, true);

    if (!is_array($datos)) {
        responderJson(
            [
                'success' => false,
                'message' => 'Los datos enviados no son JSON válido.',
            ],
            400
        );
    }

    return $datos;
}

/**
 * Verifica el método HTTP utilizado.
 */
function requerirMetodo(string $metodo): void
{
    $metodoActual =
        strtoupper($_SERVER['REQUEST_METHOD'] ?? '');

    if ($metodoActual !== strtoupper($metodo)) {
        responderJson(
            [
                'success' => false,
                'message' => 'Método HTTP no permitido.',
            ],
            405
        );
    }
}

/**
 * Obtiene el rol verdadero del usuario desde la sesion PHP.
 * El navegador no controla los permisos de AulaBot.
 */
function obtenerRolUsuarioSesion(): string
{
    $rolSesion = strtolower(
        trim(
            (string) (
                $_SESSION['usuario']['rol'] ??
                ''
            )
        )
    );

    $rolesPermitidos = [
        'alumno',
        'docente',
        'admin',
        'investigador',
    ];

    if (!in_array($rolSesion, $rolesPermitidos, true)) {
        responderJson(
            [
                'success' => false,
                'message' => 'El rol de la sesion no es valido para AulaBot.',
            ],
            403
        );
    }

    return $rolSesion;
}

/**
 * Obtiene el modulo correspondiente al rol autenticado.
 */
function obtenerModuloOrigenPorRol(string $rol): string
{
    return match ($rol) {
        'alumno' => 'Web Alumno',
        'docente' => 'Web Docente',
        'admin' => 'Web Admin',
        'investigador' => 'Web Investigador',
        default => 'Web Desconocido',
    };
}
