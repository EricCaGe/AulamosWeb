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
// OBTENER JUEGO
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

if ($juego['modo'] !== 'Clasificar') {
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
// CARGAR ELEMENTOS
// =====================================================

function cargarElementos($conexion, $id_juego)
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

    $elementos =
        $resultado->fetch_all(
            MYSQLI_ASSOC
        );

    $stmt->close();

    return $elementos;
}


$elementos =
    cargarElementos(
        $conexion,
        $id_juego
    );


// =====================================================
// CATEGORÍAS EXISTENTES
// =====================================================

function obtenerCategorias($elementos)
{
    $categorias = [];

    foreach ($elementos as $elemento) {

        $categoria =
            trim(
                (string) (
                    $elemento['categoria']
                    ??
                    ''
                )
            );

        if (
            $categoria !== '' &&
            !in_array(
                $categoria,
                $categorias,
                true
            )
        ) {
            $categorias[] =
                $categoria;
        }
    }

    return $categorias;
}


$categorias =
    obtenerCategorias(
        $elementos
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
        'Juego creado. Ahora agrega los elementos y sus categorías.';
    $tipo_mensaje =
        'success';
}


// =====================================================
// PROCESAR FORMULARIOS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // =================================================
    // AGREGAR ELEMENTO
    // =================================================

    if (isset($_POST['agregar_elemento'])) {

        $tipo_elemento =
            $_POST['tipo_elemento']
            ??
            'Texto';

        if (
            !in_array(
                $tipo_elemento,
                ['Texto', 'Imagen'],
                true
            )
        ) {
            $tipo_elemento =
                'Texto';
        }


        $texto =
            trim(
                $_POST['elemento_texto']
                ??
                ''
            );

        $imagen =
            trim(
                $_POST['elemento_imagen']
                ??
                ''
            );

        $categoria =
            trim(
                $_POST['categoria']
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
            $tipo_elemento === 'Texto' &&
            $texto === ''
        ) {
            $errores[] =
                'Escribe el elemento que deberá clasificar el alumno.';
        }

        if (
            $tipo_elemento === 'Imagen' &&
            $imagen === ''
        ) {
            $errores[] =
                'Selecciona una imagen para el elemento.';
        }

        if ($categoria === '') {
            $errores[] =
                'Escribe la categoría correcta.';
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
                    $tipo_elemento === 'Texto'
                        ? $texto
                        : null;

                $elemento_a_imagen =
                    $tipo_elemento === 'Imagen'
                        ? $imagen
                        : null;

                // Se conserva también la categoría en elemento B
                // porque así trabaja la lógica móvil actual.
                $elemento_b_texto =
                    $categoria;

                $elemento_b_imagen =
                    null;

                $orden =
                    count($elementos) + 1;


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
                    'Elemento agregado correctamente.';
                $tipo_mensaje =
                    'success';


                $elementos =
                    cargarElementos(
                        $conexion,
                        $id_juego
                    );

                $categorias =
                    obtenerCategorias(
                        $elementos
                    );

            } catch (Throwable $e) {

                $mensaje =
                    'No se pudo agregar el elemento: ' .
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
    // ELIMINAR ELEMENTO
    // =================================================

    elseif (isset($_POST['eliminar_elemento'])) {

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
                    'Elemento eliminado correctamente.';
                $tipo_mensaje =
                    'success';


                $elementos =
                    cargarElementos(
                        $conexion,
                        $id_juego
                    );

                $categorias =
                    obtenerCategorias(
                        $elementos
                    );

            } catch (Throwable $e) {

                $mensaje =
                    'No se pudo eliminar el elemento.';
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

        if (count($elementos) < 2) {

            $mensaje =
                'Debes agregar al menos 2 elementos antes de publicar.';
            $tipo_mensaje =
                'error';

        } elseif (count($categorias) < 2) {

            $mensaje =
                'Debes utilizar al menos 2 categorías diferentes.';
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


                // =====================================
                // ASIGNAR A ALUMNOS ACTIVOS
                // =====================================

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

                $asignados =
                    0;


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

    <title>Editar Clasificar - Docente</title>


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

        .clasificar-container {
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
                minmax(330px, .9fr)
                minmax(390px, 1.1fr);
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


        .tipo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 12px;
        }


        .tipo-opcion {
            min-height: 48px;
            border: 2px solid #e2e8f0;
            border-radius: 11px;
            background: #ffffff;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }


        .tipo-opcion.active {
            border-color: #3b71f3;
            background: #eff6ff;
            color: #3b71f3;
        }


        .upload-box {
            min-height: 170px;
            border: 2px dashed #cbd5e1;
            border-radius: 14px;
            background: #f8fafc;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }


        .upload-box input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 3;
        }


        .upload-placeholder {
            text-align: center;
            color: #64748b;
            font-size: 13px;
            padding: 18px;
        }


        .upload-placeholder i {
            display: block;
            font-size: 34px;
            color: #3b71f3;
            margin-bottom: 8px;
        }


        .preview {
            width: 100%;
            height: 170px;
            object-fit: cover;
            display: none;
        }


        .estado-upload {
            margin-top: 6px;
            min-height: 18px;
            color: #64748b;
            font-size: 11px;
        }


        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 8px;
        }


        .chip {
            border: none;
            border-radius: 999px;
            padding: 7px 10px;
            background: #eff6ff;
            color: #3b71f3;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
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


        .elementos-lista {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }


        .elemento-card {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 15px;
            padding: 14px;
        }


        .elemento-header {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 11px;
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


        .categoria-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 999px;
            background: #eff6ff;
            color: #3b71f3;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 800;
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


        .elemento-contenido {
            min-height: 115px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            text-align: center;
            color: #1e293b;
            font-weight: 800;
        }


        .elemento-contenido img {
            width: 100%;
            max-height: 190px;
            object-fit: cover;
            border-radius: 10px;
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


        @media (max-width: 950px) {

            .grid {
                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 768px) {

            .clasificar-container {
                padding: 15px;
            }


            .tipo-grid {
                grid-template-columns: 1fr;
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

                <h1>Clasificar</h1>

                <p>
                    Agrega elementos y asígnalos a categorías
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


        <div class="clasificar-container">

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
                 DATOS DEL JUEGO
            ================================================= -->

            <div class="card">

                <div class="juego-header">

                    <div class="juego-icono">
                        <i class="fa-solid fa-layer-group"></i>
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

                            <?php echo count($elementos); ?>

                            elemento(s) ·

                            <?php echo count($categorias); ?>

                            categoría(s)

                        </div>

                    </div>

                </div>

            </div>


            <div class="grid">

                <!-- =================================================
                     NUEVO ELEMENTO
                ================================================= -->

                <div class="card">

                    <h3 class="seccion-titulo">

                        <i class="fa-solid fa-plus"></i>

                        Nuevo elemento

                    </h3>


                    <p class="seccion-desc">
                        El alumno verá este elemento y deberá
                        elegir la categoría correcta.
                    </p>


                    <form
                        method="POST"
                        id="formClasificar"
                    >

                        <!-- TIPO -->

                        <div class="form-group">

                            <label>
                                Tipo de elemento
                                <span class="required">*</span>
                            </label>


                            <div class="tipo-grid">

                                <label
                                    class="tipo-opcion active"
                                    id="opcionTexto"
                                    onclick="seleccionarTipo('Texto')"
                                >

                                    <input
                                        type="radio"
                                        name="tipo_elemento"
                                        value="Texto"
                                        id="radioTexto"
                                        checked
                                        hidden
                                    >

                                    <i class="fa-solid fa-font"></i>
                                    Texto

                                </label>


                                <label
                                    class="tipo-opcion"
                                    id="opcionImagen"
                                    onclick="seleccionarTipo('Imagen')"
                                >

                                    <input
                                        type="radio"
                                        name="tipo_elemento"
                                        value="Imagen"
                                        id="radioImagen"
                                        hidden
                                    >

                                    <i class="fa-regular fa-image"></i>
                                    Imagen

                                </label>

                            </div>

                        </div>


                        <!-- TEXTO -->

                        <div
                            class="form-group"
                            id="contenedorTexto"
                        >

                            <label>
                                Elemento
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                class="input"
                                name="elemento_texto"
                                id="elementoTexto"
                                placeholder="Ej. Delfín"
                            >

                        </div>


                        <!-- IMAGEN -->

                        <div
                            class="form-group hidden"
                            id="contenedorImagen"
                        >

                            <label>
                                Imagen
                                <span class="required">*</span>
                            </label>


                            <div class="upload-box">

                                <div
                                    class="upload-placeholder"
                                    id="uploadPlaceholder"
                                >

                                    <i class="fa-regular fa-image"></i>

                                    <strong>
                                        Seleccionar imagen
                                    </strong>

                                    <div>
                                        JPG, PNG, GIF o WEBP
                                    </div>

                                </div>


                                <img
                                    id="previewImagen"
                                    class="preview"
                                    alt="Vista previa"
                                >


                                <input
                                    type="file"
                                    id="imagenElemento"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    onchange="subirImagenElemento()"
                                >

                            </div>


                            <div
                                class="estado-upload"
                                id="estadoUpload"
                            ></div>


                            <input
                                type="hidden"
                                name="elemento_imagen"
                                id="elementoImagen"
                                value=""
                            >

                        </div>


                        <!-- CATEGORÍA -->

                        <div class="form-group">

                            <label>
                                Categoría correcta
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                class="input"
                                name="categoria"
                                id="categoria"
                                placeholder="Ej. Acuáticos"
                                required
                            >


                            <?php if (!empty($categorias)): ?>

                                <div class="chips">

                                    <?php foreach ($categorias as $categoriaExistente): ?>

                                        <button
                                            type="button"
                                            class="chip"
                                            onclick="usarCategoria(<?php echo json_encode($categoriaExistente); ?>)"
                                        >
                                            <i class="fa-regular fa-folder"></i>

                                            <?php
                                                echo htmlspecialchars(
                                                    $categoriaExistente
                                                );
                                            ?>
                                        </button>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- RETROALIMENTACIÓN -->

                        <div class="form-group">

                            <label>
                                Retroalimentación
                            </label>

                            <textarea
                                class="textarea"
                                name="explicacion"
                                placeholder="Ej. El delfín pertenece a la categoría de animales acuáticos."
                            ></textarea>

                        </div>


                        <!-- PUNTOS -->

                        <div class="form-group">

                            <label>
                                Puntos
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
                            name="agregar_elemento"
                            class="btn btn-primary"
                        >
                            <i class="fa-solid fa-plus-circle"></i>
                            Agregar elemento
                        </button>

                    </form>

                </div>


                <!-- =================================================
                     ELEMENTOS REGISTRADOS
                ================================================= -->

                <div class="card">

                    <h3 class="seccion-titulo">

                        <i class="fa-solid fa-layer-group"></i>

                        Elementos registrados

                        <span
                            style="
                                color:#64748b;
                                font-size:13px;
                                font-weight:500;
                            "
                        >
                            (<?php echo count($elementos); ?>)
                        </span>

                    </h3>


                    <p class="seccion-desc">
                        Para publicar utiliza al menos dos categorías diferentes.
                    </p>


                    <?php if (empty($elementos)): ?>

                        <div class="vacio">

                            <i class="fa-solid fa-layer-group"></i>

                            <strong>
                                Todavía no hay elementos
                            </strong>

                            <span
                                style="
                                    margin-top:5px;
                                    font-size:12px;
                                "
                            >
                                Agrega elementos y asígnalos a categorías.
                            </span>

                        </div>

                    <?php else: ?>

                        <div class="elementos-lista">

                            <?php foreach ($elementos as $indice => $elemento): ?>

                                <?php
                                    $imagenElemento =
                                        getUrlImagen(
                                            $elemento['elemento_a_imagen']
                                        );
                                ?>

                                <div class="elemento-card">

                                    <div class="elemento-header">

                                        <div class="numero">
                                            <?php echo $indice + 1; ?>
                                        </div>


                                        <span class="categoria-badge">

                                            <i class="fa-regular fa-folder"></i>

                                            <?php
                                                echo htmlspecialchars(
                                                    $elemento['categoria']
                                                    ??
                                                    'Sin categoría'
                                                );
                                            ?>

                                        </span>


                                        <span class="puntos-badge">

                                            <i class="fa-solid fa-star"></i>

                                            <?php echo (int) $elemento['puntos']; ?>

                                            pts

                                        </span>


                                        <?php if ($juego['estado'] === 'Borrador'): ?>

                                            <form
                                                method="POST"
                                                style="margin:0;"
                                                onsubmit="return confirm('¿Eliminar este elemento?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="id_pareja"
                                                    value="<?php echo (int) $elemento['id_pareja']; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="eliminar_elemento"
                                                    class="btn-eliminar"
                                                    title="Eliminar"
                                                >
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>


                                    <div class="elemento-contenido">

                                        <?php if ($imagenElemento): ?>

                                            <img
                                                src="<?php echo htmlspecialchars($imagenElemento); ?>"
                                                alt="Elemento <?php echo $indice + 1; ?>"
                                            >

                                        <?php else: ?>

                                            <?php
                                                echo htmlspecialchars(
                                                    $elemento['elemento_a_texto']
                                                    ??
                                                    'Sin contenido'
                                                );
                                            ?>

                                        <?php endif; ?>

                                    </div>


                                    <?php if (!empty($elemento['explicacion'])): ?>

                                        <div class="explicacion">

                                            <i class="fa-regular fa-circle-info"></i>

                                            <span>
                                                <?php
                                                    echo htmlspecialchars(
                                                        $elemento['explicacion']
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
                            ¿Terminaste el juego de Clasificar?
                        </h4>

                        <p>
                            Necesitas al menos 2 elementos y
                            2 categorías diferentes para publicar.
                        </p>

                    </div>


                    <form
                        method="POST"
                        style="margin:0;"
                    >

                        <button
                            type="submit"
                            name="publicar"
                            class="btn btn-success"
                            <?php
                                echo (
                                    count($elementos) < 2 ||
                                    count($categorias) < 2
                                )
                                    ? 'disabled'
                                    : '';
                            ?>
                            onclick="return confirm('¿Publicar este juego de Clasificar?');"
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
// TIPO DE ELEMENTO
// =====================================================

function seleccionarTipo(tipo) {

    const esTexto =
        tipo === 'Texto';


    document
        .getElementById(
            'radioTexto'
        )
        .checked =
        esTexto;


    document
        .getElementById(
            'radioImagen'
        )
        .checked =
        !esTexto;


    document
        .getElementById(
            'opcionTexto'
        )
        .classList
        .toggle(
            'active',
            esTexto
        );


    document
        .getElementById(
            'opcionImagen'
        )
        .classList
        .toggle(
            'active',
            !esTexto
        );


    document
        .getElementById(
            'contenedorTexto'
        )
        .classList
        .toggle(
            'hidden',
            !esTexto
        );


    document
        .getElementById(
            'contenedorImagen'
        )
        .classList
        .toggle(
            'hidden',
            esTexto
        );
}


// =====================================================
// USAR CATEGORÍA EXISTENTE
// =====================================================

function usarCategoria(categoria) {

    document
        .getElementById(
            'categoria'
        )
        .value =
        categoria;
}


// =====================================================
// SUBIR IMAGEN
// =====================================================

async function subirImagenElemento() {

    const input =
        document.getElementById(
            'imagenElemento'
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
            'estadoUpload'
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
                'elementoImagen'
            )
            .value =
            resultado.ruta;


        const preview =
            document.getElementById(
                'previewImagen'
            );


        preview.src =
            URL.createObjectURL(
                archivo
            );


        preview.style.display =
            'block';


        document
            .getElementById(
                'uploadPlaceholder'
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
        'formClasificar'
    )
    .addEventListener(
        'submit',
        function (evento) {

            const tipo =
                document
                    .querySelector(
                        'input[name="tipo_elemento"]:checked'
                    )
                    ?.value
                ||
                'Texto';


            const texto =
                document
                    .getElementById(
                        'elementoTexto'
                    )
                    .value
                    .trim();


            const imagen =
                document
                    .getElementById(
                        'elementoImagen'
                    )
                    .value
                    .trim();


            const categoria =
                document
                    .getElementById(
                        'categoria'
                    )
                    .value
                    .trim();


            if (
                tipo === 'Texto' &&
                !texto
            ) {

                evento.preventDefault();

                alert(
                    'Escribe el elemento que deberá clasificar el alumno.'
                );

                return;
            }


            if (
                tipo === 'Imagen' &&
                !imagen
            ) {

                evento.preventDefault();

                alert(
                    'Selecciona y espera a que termine de subir la imagen.'
                );

                return;
            }


            if (!categoria) {

                evento.preventDefault();

                alert(
                    'Escribe la categoría correcta.'
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
