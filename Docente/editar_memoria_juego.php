<?php
session_start();

if (
    !isset($_SESSION['usuario']) ||
    ($_SESSION['usuario']['rol'] ?? '') !== 'Docente' ||
    empty($_SESSION['usuario']['id_usuario'])
) {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_docente = (int) $_SESSION['usuario']['id_usuario'];

$nombre_docente = trim(
    ($_SESSION['usuario']['nombre'] ?? '') . ' ' .
    ($_SESSION['usuario']['apellido_paterno'] ?? '')
);

$foto_perfil_docente =
    $_SESSION['usuario']['foto_perfil']
    ??
    null;

$ruta_foto_docente =
    !empty($foto_perfil_docente)
        ? '../uploads/perfiles/' . $foto_perfil_docente
        : 'https://placehold.co/40x40/ff7675/white?text=👨';

echo '<script>window.idUsuario = ' .
    json_encode($id_docente) .
    ';</script>';

$id_juego =
    isset($_GET['id_juego'])
        ? (int) $_GET['id_juego']
        : 0;

if ($id_juego <= 0) {
    header('Location: juegos_docente.php');
    exit;
}


// =====================================================
// OBTENER DATOS DEL JUEGO
// =====================================================

$queryJuego = "
    SELECT
        j.*,
        c.nombre AS curso,
        m.nombre AS materia,
        g.nombre AS grupo
    FROM conecta_juegos j
    INNER JOIN cursos c
        ON c.id_curso = j.id_curso
    INNER JOIN materias m
        ON m.id_materia = c.id_materia
    INNER JOIN grupos g
        ON g.id_grupo = c.id_grupo
    WHERE
        j.id_juego = ?
        AND j.id_docente = ?
    LIMIT 1
";

$stmtJuego =
    $conexion->prepare(
        $queryJuego
    );

$stmtJuego->bind_param(
    'ii',
    $id_juego,
    $id_docente
);

$stmtJuego->execute();

$juego =
    $stmtJuego
        ->get_result()
        ->fetch_assoc();

$stmtJuego->close();

if (!$juego) {
    header('Location: juegos_docente.php?error=juego_no_encontrado');
    exit;
}

if ($juego['modo'] !== 'Memoria') {
    header(
        'Location: detalle_juego_docente.php?id_juego=' .
        $id_juego .
        '&error=modo_incorrecto'
    );
    exit;
}

if ($juego['estado'] === 'Cerrado') {
    header(
        'Location: detalle_juego_docente.php?id_juego=' .
        $id_juego .
        '&error=juego_cerrado'
    );
    exit;
}


// =====================================================
// CARGAR PAREJAS
// =====================================================

function cargarParejas($conexion, $id_juego)
{
    $sql = "
        SELECT
            id_pareja,
            elemento_a_texto,
            elemento_a_imagen,
            elemento_a_audio,
            elemento_b_texto,
            elemento_b_imagen,
            elemento_b_audio,
            explicacion,
            categoria,
            orden,
            puntos
        FROM conecta_parejas
        WHERE id_juego = ?
        ORDER BY orden ASC, id_pareja ASC
    ";

    $stmt =
        $conexion->prepare(
            $sql
        );

    $stmt->bind_param(
        'i',
        $id_juego
    );

    $stmt->execute();

    $resultado =
        $stmt->get_result();

    $parejas =
        $resultado->fetch_all(
            MYSQLI_ASSOC
        );

    $stmt->close();

    return $parejas;
}


$parejas =
    cargarParejas(
        $conexion,
        $id_juego
    );


// =====================================================
// MENSAJES
// =====================================================

$mensaje = '';
$tipo_mensaje = '';

if (
    isset($_GET['success']) &&
    $_GET['success'] === '1'
) {
    $mensaje =
        'Juego creado. Ahora agrega las parejas de memoria.';
    $tipo_mensaje =
        'success';
}


// =====================================================
// PROCESAR FORMULARIOS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // =================================================
    // AGREGAR PAREJA
    // =================================================

    if (isset($_POST['agregar_pareja'])) {

        $tipo_a =
            $_POST['tipo_a']
            ??
            'Texto';

        $tipo_b =
            $_POST['tipo_b']
            ??
            'Texto';

        if (
            !in_array(
                $tipo_a,
                ['Texto', 'Imagen'],
                true
            )
        ) {
            $tipo_a =
                'Texto';
        }

        if (
            !in_array(
                $tipo_b,
                ['Texto', 'Imagen'],
                true
            )
        ) {
            $tipo_b =
                'Texto';
        }


        $texto_a =
            trim(
                $_POST['elemento_a_texto']
                ??
                ''
            );

        $texto_b =
            trim(
                $_POST['elemento_b_texto']
                ??
                ''
            );

        $imagen_a =
            trim(
                $_POST['elemento_a_imagen']
                ??
                ''
            );

        $imagen_b =
            trim(
                $_POST['elemento_b_imagen']
                ??
                ''
            );

        $explicacion =
            trim(
                $_POST['explicacion']
                ??
                ''
            );

        $puntos =
            isset($_POST['puntos'])
                ? (int) $_POST['puntos']
                : (int) (
                    $juego['puntos_por_acierto']
                    ??
                    50
                );


        // =============================================
        // VALIDACIONES
        // =============================================

        $errores = [];

        if (
            $tipo_a === 'Texto' &&
            $texto_a === ''
        ) {
            $errores[] =
                'Escribe el contenido de la tarjeta A.';
        }

        if (
            $tipo_a === 'Imagen' &&
            $imagen_a === ''
        ) {
            $errores[] =
                'Selecciona una imagen para la tarjeta A.';
        }

        if (
            $tipo_b === 'Texto' &&
            $texto_b === ''
        ) {
            $errores[] =
                'Escribe el contenido de la tarjeta B.';
        }

        if (
            $tipo_b === 'Imagen' &&
            $imagen_b === ''
        ) {
            $errores[] =
                'Selecciona una imagen para la tarjeta B.';
        }

        if ($puntos <= 0) {
            $errores[] =
                'Los puntos deben ser mayores que cero.';
        }


        // =============================================
        // INSERTAR
        // =============================================

        if (empty($errores)) {

            try {

                $elemento_a_texto =
                    $tipo_a === 'Texto'
                        ? $texto_a
                        : null;

                $elemento_a_imagen =
                    $tipo_a === 'Imagen'
                        ? $imagen_a
                        : null;

                $elemento_b_texto =
                    $tipo_b === 'Texto'
                        ? $texto_b
                        : null;

                $elemento_b_imagen =
                    $tipo_b === 'Imagen'
                        ? $imagen_b
                        : null;

                $categoria =
                    'Memoria';

                $orden =
                    count($parejas) + 1;


                $stmtInsert =
                    $conexion->prepare("
                        INSERT INTO conecta_parejas (
                            id_juego,
                            elemento_a_texto,
                            elemento_a_imagen,
                            elemento_b_texto,
                            elemento_b_imagen,
                            explicacion,
                            categoria,
                            orden,
                            puntos
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");


                $stmtInsert->bind_param(
                    'issssssii',
                    $id_juego,
                    $elemento_a_texto,
                    $elemento_a_imagen,
                    $elemento_b_texto,
                    $elemento_b_imagen,
                    $explicacion,
                    $categoria,
                    $orden,
                    $puntos
                );

                $stmtInsert->execute();

                $stmtInsert->close();


                $mensaje =
                    'Pareja de memoria agregada correctamente.';
                $tipo_mensaje =
                    'success';


                $parejas =
                    cargarParejas(
                        $conexion,
                        $id_juego
                    );

            } catch (Throwable $e) {

                $mensaje =
                    'No se pudo agregar la pareja: ' .
                    $e->getMessage();

                $tipo_mensaje =
                    'error';
            }

        } else {

            $mensaje =
                implode(
                    ' ',
                    $errores
                );

            $tipo_mensaje =
                'error';
        }
    }


    // =================================================
    // ELIMINAR PAREJA
    // =================================================

    elseif (isset($_POST['eliminar_pareja'])) {

        $id_pareja =
            isset($_POST['id_pareja'])
                ? (int) $_POST['id_pareja']
                : 0;

        if ($id_pareja > 0) {

            try {

                $stmtDelete =
                    $conexion->prepare("
                        DELETE FROM conecta_parejas
                        WHERE
                            id_pareja = ?
                            AND id_juego = ?
                    ");

                $stmtDelete->bind_param(
                    'ii',
                    $id_pareja,
                    $id_juego
                );

                $stmtDelete->execute();

                $stmtDelete->close();


                $mensaje =
                    'Pareja eliminada correctamente.';
                $tipo_mensaje =
                    'success';


                $parejas =
                    cargarParejas(
                        $conexion,
                        $id_juego
                    );

            } catch (Throwable $e) {

                $mensaje =
                    'No se pudo eliminar la pareja.';
                $tipo_mensaje =
                    'error';
            }
        }
    }


    // =================================================
    // PUBLICAR
    // =================================================

    elseif (
        isset($_POST['publicar']) &&
        $juego['estado'] === 'Borrador'
    ) {

        if (count($parejas) < 2) {

            $mensaje =
                'Debes agregar al menos 2 parejas antes de publicar.';
            $tipo_mensaje =
                'error';

        } else {

            try {

                $conexion->begin_transaction();


                $stmtPublicar =
                    $conexion->prepare("
                        UPDATE conecta_juegos
                        SET estado = 'Publicado'
                        WHERE
                            id_juego = ?
                            AND id_docente = ?
                    ");

                $stmtPublicar->bind_param(
                    'ii',
                    $id_juego,
                    $id_docente
                );

                $stmtPublicar->execute();

                $stmtPublicar->close();


                $stmtAlumnos =
                    $conexion->prepare("
                        SELECT id_alumno
                        FROM inscripciones
                        WHERE
                            id_curso = ?
                            AND estado = 'Activo'
                    ");

                $stmtAlumnos->bind_param(
                    'i',
                    $juego['id_curso']
                );

                $stmtAlumnos->execute();

                $resultadoAlumnos =
                    $stmtAlumnos->get_result();


                $asignados = 0;


                while (
                    $alumno =
                        $resultadoAlumnos->fetch_assoc()
                ) {

                    $id_alumno =
                        (int) $alumno['id_alumno'];


                    $stmtAsignar =
                        $conexion->prepare("
                            INSERT INTO conecta_asignaciones (
                                id_juego,
                                id_alumno,
                                estado
                            )
                            VALUES (?, ?, 'Pendiente')
                            ON DUPLICATE KEY UPDATE
                                estado = 'Pendiente'
                        ");

                    $stmtAsignar->bind_param(
                        'ii',
                        $id_juego,
                        $id_alumno
                    );

                    $stmtAsignar->execute();

                    $stmtAsignar->close();

                    $asignados++;
                }


                $stmtAlumnos->close();

                $conexion->commit();


                header(
                    'Location: detalle_juego_docente.php?id_juego=' .
                    $id_juego .
                    '&publicado=1&asignados=' .
                    $asignados
                );

                exit;

            } catch (Throwable $e) {

                $conexion->rollback();

                $mensaje =
                    'No se pudo publicar el juego: ' .
                    $e->getMessage();

                $tipo_mensaje =
                    'error';
            }
        }
    }
}


// =====================================================
// URL DE IMAGEN
// =====================================================

function getUrlImagen($ruta)
{
    if (empty($ruta)) {
        return null;
    }

    if (
        strpos($ruta, 'http://') === 0 ||
        strpos($ruta, 'https://') === 0
    ) {
        return $ruta;
    }

    return '../' .
        ltrim(
            $ruta,
            '/'
        );
}

?>
<!DOCTYPE html>
<html lang="es">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Editar Memoria - Docente</title>


    <link
        rel="stylesheet"
        href="styles/docente.css"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >


    <link
        rel="stylesheet"
        href="../Accesibilidad/accesibilidad.css"
    >


    <style>

        .memoria-container {
            padding: 20px 30px 40px;
            width: 100%;
        }


        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #3b71f3;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 600;
        }


        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 18px;
        }


        .juego-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }


        .juego-icono {
            width: 58px;
            height: 58px;
            border-radius: 17px;
            background: #eff6ff;
            color: #3b71f3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 27px;
            flex-shrink: 0;
        }


        .juego-info {
            flex: 1;
        }


        .juego-info h2 {
            margin: 0;
            color: #1e293b;
            font-size: 21px;
        }


        .juego-info p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 13px;
        }


        .contador {
            color: #3b71f3;
            font-weight: 800;
            font-size: 13px;
            margin-top: 7px;
        }


        .grid {
            display: grid;
            grid-template-columns:
                minmax(330px, 0.95fr)
                minmax(380px, 1.05fr);
            gap: 20px;
            align-items: start;
        }


        .seccion-titulo {
            margin: 0 0 6px;
            color: #1e293b;
            font-size: 18px;
        }


        .seccion-desc {
            margin: 0 0 18px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }


        .form-group {
            margin-bottom: 16px;
        }


        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #1e293b;
            font-size: 13px;
            font-weight: 700;
        }


        .required {
            color: #dc2626;
        }


        .input,
        .textarea {
            width: 100%;
            border: 1px solid #dbe3ed;
            border-radius: 11px;
            padding: 11px 13px;
            font-family: inherit;
            font-size: 14px;
            color: #1e293b;
            background: #ffffff;
        }


        .input:focus,
        .textarea:focus {
            outline: none;
            border-color: #3b71f3;
            box-shadow: 0 0 0 3px rgba(59, 113, 243, .08);
        }


        .textarea {
            min-height: 95px;
            resize: vertical;
        }


        .tarjetas-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }


        .tarjeta-form {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px;
        }


        .tarjeta-form h4 {
            margin: 0 0 12px;
            color: #1e293b;
        }


        .tipo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 7px;
            margin-bottom: 10px;
        }


        .tipo-opcion {
            min-height: 42px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            background: #ffffff;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }


        .tipo-opcion.active {
            border-color: #3b71f3;
            background: #eff6ff;
            color: #3b71f3;
        }


        .upload-box {
            min-height: 130px;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }


        .upload-box input[type="file"] {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 3;
        }


        .placeholder {
            color: #64748b;
            text-align: center;
            font-size: 12px;
            padding: 12px;
        }


        .placeholder i {
            display: block;
            color: #3b71f3;
            font-size: 28px;
            margin-bottom: 6px;
        }


        .preview {
            width: 100%;
            height: 130px;
            object-fit: cover;
            display: none;
        }


        .estado-upload {
            min-height: 18px;
            margin-top: 5px;
            color: #64748b;
            font-size: 11px;
        }


        .hidden {
            display: none !important;
        }


        .btn {
            border: none;
            border-radius: 11px;
            min-height: 48px;
            padding: 10px 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
        }


        .btn-primary {
            width: 100%;
            background: #3b71f3;
            color: #ffffff;
        }


        .btn-success {
            background: #22c55e;
            color: #ffffff;
        }


        .btn-success:disabled {
            opacity: .5;
            cursor: not-allowed;
        }


        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }


        .parejas-lista {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }


        .pareja-card {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 15px;
            padding: 14px;
        }


        .pareja-header {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 10px;
        }


        .numero {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: #3b71f3;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
        }


        .puntos-badge {
            margin-left: auto;
            background: #fef3c7;
            color: #92400e;
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 11px;
            font-weight: 800;
        }


        .btn-eliminar {
            margin-left: 5px;
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 9px;
            background: #fee2e2;
            color: #dc2626;
            cursor: pointer;
        }


        .pareja-contenido {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 10px;
            align-items: center;
        }


        .carta {
            min-height: 120px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            text-align: center;
            color: #1e293b;
            font-weight: 700;
        }


        .carta img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
        }


        .union {
            color: #3b71f3;
            font-size: 19px;
        }


        .explicacion {
            display: flex;
            gap: 7px;
            align-items: flex-start;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }


        .vacio {
            min-height: 220px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #94a3b8;
        }


        .vacio i {
            font-size: 44px;
            margin-bottom: 10px;
        }


        .publicar-card {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }


        .publicar-info {
            flex: 1;
            min-width: 260px;
        }


        .publicar-info h4 {
            margin: 0;
            color: #1e293b;
        }


        .publicar-info p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 13px;
        }


        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-weight: 600;
        }


        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }


        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }


        @media (max-width: 1000px) {

            .grid {
                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 768px) {

            .memoria-container {
                padding: 15px;
            }


            .tarjetas-form-grid {
                grid-template-columns: 1fr;
            }


            .pareja-contenido {
                grid-template-columns: 1fr;
            }


            .union {
                transform: rotate(90deg);
                justify-self: center;
            }


            .publicar-card {
                flex-direction: column;
                align-items: stretch;
            }


            .publicar-card .btn {
                width: 100%;
            }
        }

    </style>

</head>

<body>

<div class="dashboard-container">

    <aside class="sidebar">

        <div class="logo-section">

            <img
                src="../img/logo_g.png"
                alt="Logo Aulamos"
                class="logo-img"
            >

        </div>


        <nav class="menu">

            <a href="docente_dashboard.php" class="menu-item">
                <i class="fa-solid fa-house"></i>
                Dashboard
            </a>

            <a href="crear_recurso.php" class="menu-item">
                <i class="fa-solid fa-medal"></i>
                Crear Recurso
            </a>

            <a href="mis_recursos.php" class="menu-item">
                <i class="fa-solid fa-folder-open"></i>
                Mis Recursos
            </a>

            <a href="crear_actividad.php" class="menu-item">
                <i class="fa-solid fa-clipboard-check"></i>
                Crear Actividad
            </a>

            <a href="crear_evaluacion.php" class="menu-item">
                <i class="fa-solid fa-clipboard-list"></i>
                Crear Evaluación
            </a>

            <a href="crear_juego.php" class="menu-item active">
                <i class="fa-solid fa-gamepad"></i>
                Crear Juego
            </a>

            <a href="ver_estudiantes.php" class="menu-item">
                <i class="fa-solid fa-users"></i>
                Ver Estudiantes
            </a>

            <a href="reporte.php" class="menu-item">
                <i class="fa-solid fa-chart-column"></i>
                Reportes
            </a>

            <a href="pasarlista.php" class="menu-item">
                <i class="fa-solid fa-bars"></i>
                Pasar Lista
            </a>

            <a href="juegos_docente.php" class="menu-item">
                <i class="fa-solid fa-gamepad"></i>
                Conecta y Aprende
            </a>


            <div class="menu-spacer"></div>


            <button
                class="btn-accessibility-main"
                onclick="toggleBarraAccesibilidad()"
            >
                <i class="fa-solid fa-universal-access"></i>
                Accesibilidad
            </button>


            <a
                href="../InicioSesion/cerrar_sesion.php"
                class="menu-item btn-logout"
            >
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                Cerrar sesión
            </a>

        </nav>

    </aside>


    <main class="main-content">

        <header class="content-header">

            <div class="welcome-text">

                <h1>Memoria</h1>

                <p>
                    Crea las parejas de tarjetas que deberá encontrar el alumno
                </p>

            </div>


            <div class="header-actions">

                <button
                    type="button"
                    class="btn-assistant"
                    onclick="window.location.href='../Alumno/ChatbotDocente.php?rol=docente'"
                >
                    Asistente Virtual
                    <span class="robot-icon">🤖</span>
                </button>


                <div class="icon-bell-container">
                    <i class="fa-regular fa-bell"></i>
                </div>


                <a href="mi_perfil_d.php" class="user-profile">

                    <img
                        src="<?php echo htmlspecialchars($ruta_foto_docente); ?>"
                        alt="Avatar Docente"
                        class="avatar"
                    >

                    <div class="user-info">

                        <span class="user-name">
                            <?php echo htmlspecialchars($nombre_docente); ?>
                        </span>

                        <span class="user-role">
                            Docente
                        </span>

                    </div>

                </a>

            </div>

        </header>


        <div class="memoria-container">

            <a
                href="detalle_juego_docente.php?id_juego=<?php echo $id_juego; ?>"
                class="back-link"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Volver al detalle
            </a>


            <?php if ($mensaje): ?>

                <div class="alert alert-<?php echo htmlspecialchars($tipo_mensaje); ?>">

                    <?php echo htmlspecialchars($mensaje); ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 INFORMACIÓN DEL JUEGO
            ================================================= -->

            <div class="card">

                <div class="juego-header">

                    <div class="juego-icono">
                        <i class="fa-solid fa-table-cells-large"></i>
                    </div>


                    <div class="juego-info">

                        <h2>
                            <?php echo htmlspecialchars($juego['titulo']); ?>
                        </h2>


                        <p>

                            <?php echo htmlspecialchars($juego['materia']); ?>

                            ·

                            <?php echo htmlspecialchars($juego['curso']); ?>

                            ·

                            <?php echo htmlspecialchars($juego['grupo']); ?>

                        </p>


                        <div class="contador">

                            <?php echo count($parejas); ?>

                            pareja(s) registradas

                        </div>

                    </div>

                </div>

            </div>


            <div class="grid">

                <!-- =================================================
                     NUEVA PAREJA
                ================================================= -->

                <div class="card">

                    <h3 class="seccion-titulo">

                        <i class="fa-solid fa-plus"></i>

                        Nueva pareja de memoria

                    </h3>


                    <p class="seccion-desc">
                        Cada pareja puede combinar texto con texto,
                        texto con imagen o imagen con imagen.
                    </p>


                    <form
                        method="POST"
                        id="formMemoria"
                    >

                        <div class="tarjetas-form-grid">

                            <!-- =====================================
                                 TARJETA A
                            ====================================== -->

                            <div class="tarjeta-form">

                                <h4>Tarjeta A</h4>


                                <div class="tipo-grid">

                                    <label
                                        class="tipo-opcion active"
                                        id="tipoATexto"
                                        onclick="seleccionarTipo('A', 'Texto')"
                                    >
                                        <input
                                            type="radio"
                                            name="tipo_a"
                                            value="Texto"
                                            id="radioATexto"
                                            checked
                                            hidden
                                        >

                                        <i class="fa-solid fa-font"></i>
                                        Texto
                                    </label>


                                    <label
                                        class="tipo-opcion"
                                        id="tipoAImagen"
                                        onclick="seleccionarTipo('A', 'Imagen')"
                                    >
                                        <input
                                            type="radio"
                                            name="tipo_a"
                                            value="Imagen"
                                            id="radioAImagen"
                                            hidden
                                        >

                                        <i class="fa-regular fa-image"></i>
                                        Imagen
                                    </label>

                                </div>


                                <div id="textoAContenedor">

                                    <input
                                        type="text"
                                        class="input"
                                        id="textoA"
                                        name="elemento_a_texto"
                                        placeholder="Ej. Dog"
                                    >

                                </div>


                                <div
                                    id="imagenAContenedor"
                                    class="hidden"
                                >

                                    <div
                                        class="upload-box"
                                        id="uploadBoxA"
                                    >

                                        <div
                                            class="placeholder"
                                            id="placeholderA"
                                        >
                                            <i class="fa-regular fa-image"></i>
                                            Seleccionar imagen
                                        </div>


                                        <img
                                            id="previewA"
                                            class="preview"
                                            alt="Vista previa tarjeta A"
                                        >


                                        <input
                                            type="file"
                                            id="imagenA"
                                            accept="image/jpeg,image/png,image/gif,image/webp"
                                            onchange="subirImagen('A')"
                                        >

                                    </div>


                                    <div
                                        class="estado-upload"
                                        id="estadoA"
                                    ></div>


                                    <input
                                        type="hidden"
                                        id="rutaA"
                                        name="elemento_a_imagen"
                                        value=""
                                    >

                                </div>

                            </div>


                            <!-- =====================================
                                 TARJETA B
                            ====================================== -->

                            <div class="tarjeta-form">

                                <h4>Tarjeta B</h4>


                                <div class="tipo-grid">

                                    <label
                                        class="tipo-opcion active"
                                        id="tipoBTexto"
                                        onclick="seleccionarTipo('B', 'Texto')"
                                    >
                                        <input
                                            type="radio"
                                            name="tipo_b"
                                            value="Texto"
                                            id="radioBTexto"
                                            checked
                                            hidden
                                        >

                                        <i class="fa-solid fa-font"></i>
                                        Texto
                                    </label>


                                    <label
                                        class="tipo-opcion"
                                        id="tipoBImagen"
                                        onclick="seleccionarTipo('B', 'Imagen')"
                                    >
                                        <input
                                            type="radio"
                                            name="tipo_b"
                                            value="Imagen"
                                            id="radioBImagen"
                                            hidden
                                        >

                                        <i class="fa-regular fa-image"></i>
                                        Imagen
                                    </label>

                                </div>


                                <div id="textoBContenedor">

                                    <input
                                        type="text"
                                        class="input"
                                        id="textoB"
                                        name="elemento_b_texto"
                                        placeholder="Ej. Perro"
                                    >

                                </div>


                                <div
                                    id="imagenBContenedor"
                                    class="hidden"
                                >

                                    <div
                                        class="upload-box"
                                        id="uploadBoxB"
                                    >

                                        <div
                                            class="placeholder"
                                            id="placeholderB"
                                        >
                                            <i class="fa-regular fa-image"></i>
                                            Seleccionar imagen
                                        </div>


                                        <img
                                            id="previewB"
                                            class="preview"
                                            alt="Vista previa tarjeta B"
                                        >


                                        <input
                                            type="file"
                                            id="imagenB"
                                            accept="image/jpeg,image/png,image/gif,image/webp"
                                            onchange="subirImagen('B')"
                                        >

                                    </div>


                                    <div
                                        class="estado-upload"
                                        id="estadoB"
                                    ></div>


                                    <input
                                        type="hidden"
                                        id="rutaB"
                                        name="elemento_b_imagen"
                                        value=""
                                    >

                                </div>

                            </div>

                        </div>


                        <!-- EXPLICACIÓN -->

                        <div class="form-group" style="margin-top:16px;">

                            <label>
                                Retroalimentación
                            </label>

                            <textarea
                                name="explicacion"
                                class="textarea"
                                placeholder="Ej. Dog significa perro en español."
                            ></textarea>

                        </div>


                        <!-- PUNTOS -->

                        <div class="form-group">

                            <label>
                                Puntos por encontrar la pareja
                            </label>

                            <input
                                type="number"
                                name="puntos"
                                class="input"
                                min="1"
                                value="<?php
                                    echo (int) (
                                        $juego['puntos_por_acierto']
                                        ??
                                        50
                                    );
                                ?>"
                                required
                            >

                        </div>


                        <button
                            type="submit"
                            name="agregar_pareja"
                            class="btn btn-primary"
                        >
                            <i class="fa-solid fa-plus-circle"></i>
                            Agregar pareja
                        </button>

                    </form>

                </div>


                <!-- =================================================
                     PAREJAS REGISTRADAS
                ================================================= -->

                <div class="card">

                    <h3 class="seccion-titulo">

                        <i class="fa-solid fa-table-cells-large"></i>

                        Parejas registradas

                        <span
                            style="
                                color:#64748b;
                                font-size:13px;
                                font-weight:500;
                            "
                        >
                            (<?php echo count($parejas); ?>)
                        </span>

                    </h3>


                    <p class="seccion-desc">
                        Estas tarjetas se mezclarán cuando el alumno
                        inicie el juego.
                    </p>


                    <?php if (empty($parejas)): ?>

                        <div class="vacio">

                            <i class="fa-solid fa-table-cells-large"></i>

                            <strong>
                                Todavía no hay parejas
                            </strong>

                            <span
                                style="
                                    margin-top:5px;
                                    font-size:12px;
                                "
                            >
                                Agrega al menos 2 parejas para publicar.
                            </span>

                        </div>

                    <?php else: ?>

                        <div class="parejas-lista">

                            <?php foreach ($parejas as $indice => $pareja): ?>

                                <?php
                                    $imagenA =
                                        getUrlImagen(
                                            $pareja['elemento_a_imagen']
                                        );

                                    $imagenB =
                                        getUrlImagen(
                                            $pareja['elemento_b_imagen']
                                        );
                                ?>

                                <div class="pareja-card">

                                    <div class="pareja-header">

                                        <div class="numero">
                                            <?php echo $indice + 1; ?>
                                        </div>


                                        <strong>
                                            Pareja <?php echo $indice + 1; ?>
                                        </strong>


                                        <span class="puntos-badge">

                                            <i class="fa-solid fa-star"></i>

                                            <?php echo (int) $pareja['puntos']; ?>

                                            pts

                                        </span>


                                        <?php if ($juego['estado'] === 'Borrador'): ?>

                                            <form
                                                method="POST"
                                                style="margin:0;"
                                                onsubmit="return confirm('¿Eliminar esta pareja?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="id_pareja"
                                                    value="<?php echo (int) $pareja['id_pareja']; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="eliminar_pareja"
                                                    class="btn-eliminar"
                                                    title="Eliminar"
                                                >
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>


                                    <div class="pareja-contenido">

                                        <div class="carta">

                                            <?php if ($imagenA): ?>

                                                <img
                                                    src="<?php echo htmlspecialchars($imagenA); ?>"
                                                    alt="Tarjeta A"
                                                >

                                            <?php else: ?>

                                                <?php
                                                    echo htmlspecialchars(
                                                        $pareja['elemento_a_texto']
                                                        ??
                                                        'Sin contenido'
                                                    );
                                                ?>

                                            <?php endif; ?>

                                        </div>


                                        <div class="union">
                                            <i class="fa-solid fa-link"></i>
                                        </div>


                                        <div class="carta">

                                            <?php if ($imagenB): ?>

                                                <img
                                                    src="<?php echo htmlspecialchars($imagenB); ?>"
                                                    alt="Tarjeta B"
                                                >

                                            <?php else: ?>

                                                <?php
                                                    echo htmlspecialchars(
                                                        $pareja['elemento_b_texto']
                                                        ??
                                                        'Sin contenido'
                                                    );
                                                ?>

                                            <?php endif; ?>

                                        </div>

                                    </div>


                                    <?php if (!empty($pareja['explicacion'])): ?>

                                        <div class="explicacion">

                                            <i class="fa-regular fa-circle-info"></i>

                                            <span>
                                                <?php
                                                    echo htmlspecialchars(
                                                        $pareja['explicacion']
                                                    );
                                                ?>
                                            </span>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =================================================
                 PUBLICAR
            ================================================= -->

            <?php if ($juego['estado'] === 'Borrador'): ?>

                <div class="card publicar-card">

                    <div class="publicar-info">

                        <h4>
                            ¿Terminaste el juego de Memoria?
                        </h4>

                        <p>
                            Debes agregar al menos 2 parejas antes
                            de publicarlo para tus alumnos.
                        </p>

                    </div>


                    <form method="POST" style="margin:0;">

                        <button
                            type="submit"
                            name="publicar"
                            class="btn btn-success"
                            <?php
                                echo count($parejas) < 2
                                    ? 'disabled'
                                    : '';
                            ?>
                            onclick="return confirm('¿Publicar este juego de Memoria?');"
                        >
                            <i class="fa-solid fa-rocket"></i>
                            Publicar juego
                        </button>

                    </form>


                    <a
                        href="detalle_juego_docente.php?id_juego=<?php echo $id_juego; ?>"
                        class="btn btn-secondary"
                    >
                        <i class="fa-solid fa-eye"></i>
                        Ver detalle
                    </a>

                </div>

            <?php endif; ?>

        </div>


        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </main>

</div>


<button
    class="btn-accesibilidad-flotante"
    id="btnAccesibilidadFlotante"
    onclick="toggleBarraAccesibilidad()"
>
    <i class="fa-solid fa-universal-access"></i>
</button>


<script>

// =====================================================
// SELECCIONAR TIPO
// =====================================================

function seleccionarTipo(
    lado,
    tipo
) {

    const esTexto =
        tipo === 'Texto';


    document
        .getElementById(
            'radio' + lado + 'Texto'
        )
        .checked =
        esTexto;


    document
        .getElementById(
            'radio' + lado + 'Imagen'
        )
        .checked =
        !esTexto;


    document
        .getElementById(
            'tipo' + lado + 'Texto'
        )
        .classList
        .toggle(
            'active',
            esTexto
        );


    document
        .getElementById(
            'tipo' + lado + 'Imagen'
        )
        .classList
        .toggle(
            'active',
            !esTexto
        );


    document
        .getElementById(
            'texto' + lado + 'Contenedor'
        )
        .classList
        .toggle(
            'hidden',
            !esTexto
        );


    document
        .getElementById(
            'imagen' + lado + 'Contenedor'
        )
        .classList
        .toggle(
            'hidden',
            esTexto
        );
}


// =====================================================
// SUBIR IMAGEN
// =====================================================

async function subirImagen(lado) {

    const input =
        document.getElementById(
            'imagen' + lado
        );


    const archivo =
        input.files[0];


    if (!archivo) {
        return;
    }


    const tiposPermitidos = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];


    if (
        !tiposPermitidos.includes(
            archivo.type
        )
    ) {

        alert(
            'Solo se permiten imágenes JPG, PNG, GIF o WEBP.'
        );

        input.value =
            '';

        return;
    }


    if (
        archivo.size >
        5 * 1024 * 1024
    ) {

        alert(
            'La imagen no debe superar los 5 MB.'
        );

        input.value =
            '';

        return;
    }


    const estado =
        document.getElementById(
            'estado' + lado
        );


    estado.textContent =
        'Subiendo imagen...';


    const datos =
        new FormData();


    datos.append(
        'imagen',
        archivo
    );


    datos.append(
        'id_juego',
        <?php echo $id_juego; ?>
    );


    datos.append(
        'subir_imagen',
        '1'
    );


    try {

        const respuesta =
            await fetch(
                'subir_imagen_juego.php',
                {
                    method:
                        'POST',

                    body:
                        datos
                }
            );


        const resultado =
            await respuesta.json();


        if (
            !respuesta.ok ||
            !resultado.success
        ) {

            throw new Error(
                resultado.message
                ||
                'No se pudo subir la imagen.'
            );
        }


        document
            .getElementById(
                'ruta' + lado
            )
            .value =
            resultado.ruta;


        const preview =
            document.getElementById(
                'preview' + lado
            );


        preview.src =
            URL.createObjectURL(
                archivo
            );


        preview.style.display =
            'block';


        document
            .getElementById(
                'placeholder' + lado
            )
            .style.display =
            'none';


        estado.textContent =
            'Imagen lista para guardar.';


    } catch (error) {

        console.error(
            error
        );


        estado.textContent =
            'Error al subir la imagen.';


        alert(
            error.message
            ||
            'No se pudo subir la imagen.'
        );


        input.value =
            '';
    }
}


// =====================================================
// VALIDACIÓN
// =====================================================

document
    .getElementById(
        'formMemoria'
    )
    .addEventListener(
        'submit',
        function (evento) {

            const tipoA =
                document
                    .querySelector(
                        'input[name="tipo_a"]:checked'
                    )
                    ?.value
                ||
                'Texto';


            const tipoB =
                document
                    .querySelector(
                        'input[name="tipo_b"]:checked'
                    )
                    ?.value
                ||
                'Texto';


            const textoA =
                document
                    .getElementById(
                        'textoA'
                    )
                    .value
                    .trim();


            const textoB =
                document
                    .getElementById(
                        'textoB'
                    )
                    .value
                    .trim();


            const imagenA =
                document
                    .getElementById(
                        'rutaA'
                    )
                    .value
                    .trim();


            const imagenB =
                document
                    .getElementById(
                        'rutaB'
                    )
                    .value
                    .trim();


            if (
                tipoA === 'Texto' &&
                !textoA
            ) {

                evento.preventDefault();

                alert(
                    'Escribe el contenido de la tarjeta A.'
                );

                return;
            }


            if (
                tipoA === 'Imagen' &&
                !imagenA
            ) {

                evento.preventDefault();

                alert(
                    'Selecciona una imagen para la tarjeta A.'
                );

                return;
            }


            if (
                tipoB === 'Texto' &&
                !textoB
            ) {

                evento.preventDefault();

                alert(
                    'Escribe el contenido de la tarjeta B.'
                );

                return;
            }


            if (
                tipoB === 'Imagen' &&
                !imagenB
            ) {

                evento.preventDefault();

                alert(
                    'Selecciona una imagen para la tarjeta B.'
                );
            }
        }
    );

</script>


<script src="jss/docente_dashboard.js"></script>

<script src="../Accesibilidad/accesibilidad.js"></script>

<script src="../Accesibilidad/navegacionTeclado.js"></script>

</body>
</html>
