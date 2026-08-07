<?php

// ==========================================
// PROCESAR LOGIN
// ==========================================

session_start();

/*
|--------------------------------------------------------------------------
| BACKEND NODE DE AULAMOS
|--------------------------------------------------------------------------
|
| Esta debe ser la IP de la computadora donde ejecutas:
|
| node server.js
|
*/
const AULAMOS_API =
    'http://10.2.0.125:3000/api';

// ==========================================
// VALIDAR MÉTODO
// ==========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header(
        'Location: login.php?error=sesion'
    );
    exit;
}

// ==========================================
// OBTENER DATOS
// ==========================================

$rolSeleccionado =
    trim(
        $_POST['rol'] ??
        'Alumno'
    );

$correo =
    strtolower(
        trim(
            $_POST['correo'] ??
            ''
        )
    );

$password =
    $_POST['password'] ??
    '';

// ==========================================
// VALIDAR CAMPOS
// ==========================================

if (
    $correo === '' ||
    $password === ''
) {
    header(
        'Location: login.php?error=sesion'
    );
    exit;
}

// ==========================================
// VALIDAR ROL
// ==========================================

$rolesPermitidos = [
    'Alumno',
    'Docente',
    'Investigador',
    'Admin',
];

if (
    !in_array(
        $rolSeleccionado,
        $rolesPermitidos,
        true
    )
) {
    header(
        'Location: login.php?error=rol_invalido'
    );
    exit;
}

// ==========================================
// COMPROBAR CURL
// ==========================================

if (
    !function_exists(
        'curl_init'
    )
) {
    error_log(
        'PHP cURL no está habilitado.'
    );

    header(
        'Location: login.php?error=servidor'
    );
    exit;
}

try {

    // ======================================
    // DATOS PARA EL BACKEND NODE
    // ======================================

    $datosLogin = [
        'correo' =>
            $correo,

        'password' =>
            $password,
    ];

    // ======================================
    // PETICIÓN A NODE
    // ======================================

    $curl =
        curl_init();

    curl_setopt_array(
        $curl,
        [
            CURLOPT_URL =>
                AULAMOS_API .
                '/auth/login',

            CURLOPT_POST =>
                true,

            CURLOPT_POSTFIELDS =>
                json_encode(
                    $datosLogin
                ),

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_CONNECTTIMEOUT =>
                10,

            CURLOPT_TIMEOUT =>
                30,

            CURLOPT_HTTPHEADER =>
                [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
        ]
    );

    $respuesta =
        curl_exec(
            $curl
        );

    $errorCurl =
        curl_error(
            $curl
        );

    $codigoHttp =
        curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );

    curl_close(
        $curl
    );

    // ======================================
    // ERROR DE CONEXIÓN
    // ======================================

    if (
        $respuesta === false ||
        $errorCurl
    ) {

        error_log(
            'Error al conectar con Node: ' .
            $errorCurl
        );

        header(
            'Location: login.php?error=servidor'
        );

        exit;
    }

    // ======================================
    // CONVERTIR RESPUESTA
    // ======================================

    $datos =
        json_decode(
            $respuesta,
            true
        );

    if (
        !is_array(
            $datos
        )
    ) {

        error_log(
            'Respuesta inválida del backend Node.'
        );

        header(
            'Location: login.php?error=sesion'
        );

        exit;
    }

    // ======================================
    // CREDENCIALES INCORRECTAS
    // ======================================

    if (
        $codigoHttp === 401
    ) {

        header(
            'Location: login.php?error=credenciales'
        );

        exit;
    }

    // ======================================
    // CUENTA BLOQUEADA / INACTIVA
    // ======================================

    if (
        $codigoHttp === 403
    ) {

        $mensajeBackend =
            strtolower(
                $datos['mensaje'] ??
                ''
            );

        if (
            str_contains(
                $mensajeBackend,
                'bloqueada'
            )
        ) {

            header(
                'Location: login.php?error=bloqueado'
            );

        } else {

            header(
                'Location: login.php?error=inactivo'
            );
        }

        exit;
    }

    // ======================================
    // CUALQUIER OTRO ERROR
    // ======================================

    if (
        $codigoHttp < 200 ||
        $codigoHttp >= 300
    ) {

        error_log(
            'Error login API: ' .
            (
                $datos['mensaje'] ??
                'Error desconocido'
            )
        );

        header(
            'Location: login.php?error=sesion'
        );

        exit;
    }

    // ======================================
    // COMPROBAR RESPUESTA COMPLETA
    // ======================================

    if (
        empty(
            $datos['token']
        ) ||
        empty(
            $datos['usuario']
        )
    ) {

        error_log(
            'El backend no devolvió token o usuario.'
        );

        header(
            'Location: login.php?error=sesion'
        );

        exit;
    }

    $usuario =
        $datos['usuario'];

    // ======================================
    // VALIDAR ROL SELECCIONADO
    // ======================================

    if (
        !isset(
            $usuario['rol']
        ) ||
        $usuario['rol'] !==
            $rolSeleccionado
    ) {

        header(
            'Location: login.php?error=rol_incorrecto'
        );

        exit;
    }

    // ======================================
    // REGENERAR ID DE SESIÓN
    // ======================================

    session_regenerate_id(
        true
    );

    // ======================================
    // GUARDAR JWT
    // ======================================

    $_SESSION['token'] =
        $datos['token'];

    // También lo dejamos con este nombre
    // por compatibilidad futura.
    $_SESSION['jwt'] =
        $datos['token'];

    // ======================================
    // GUARDAR USUARIO
    // ======================================

    $_SESSION['usuario'] = [
        'id_usuario' =>
            $usuario['id_usuario'],

        'nombre' =>
            $usuario['nombre'],

        'apellido_paterno' =>
            $usuario['apellido_paterno'] ??
            '',

        'apellido_materno' =>
            $usuario['apellido_materno'] ??
            '',

        'correo' =>
            $usuario['correo'],

        'rol' =>
            $usuario['rol'],

        'token' =>
            $datos['token'],
    ];

    // ======================================
    // REDIRECCIONAR SEGÚN ROL
    // ======================================

    switch (
        $usuario['rol']
    ) {

        case 'Alumno':

            header(
                'Location: ../Alumno/alumno.php'
            );

            break;

        case 'Docente':

            header(
                'Location: ../Docente/docente_dashboard.php'
            );

            break;

        case 'Investigador':

            header(
                'Location: ../Investigador/investigador_dashboard.php'
            );

            break;

        case 'Admin':

            header(
                'Location: ../Administrador/admin_dashboard.php'
            );

            break;

        default:

            header(
                'Location: login.php?error=rol_invalido'
            );

            break;
    }

    exit;

} catch (
    Throwable $e
) {

    error_log(
        'Error en login web: ' .
        $e->getMessage()
    );

    header(
        'Location: login.php?error=sesion'
    );

    exit;
}