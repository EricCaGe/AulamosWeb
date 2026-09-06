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

$resultadoJuego =
    $stmtJuego->get_result();

$juego =
    $resultadoJuego->fetch_assoc();

$stmtJuego->close();

if (!$juego) {
    header('Location: juegos_docente.php?error=juego_no_encontrado');
    exit;
}

if ($juego['modo'] !== 'Secuencia') {
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
// CARGAR PASOS
// =====================================================

function cargarPasos($conexion, $id_juego)
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

    $pasos =
        $resultado->fetch_all(
            MYSQLI_ASSOC
        );

    $stmt->close();

    return $pasos;
}


$pasos =
    cargarPasos(
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
        'Juego creado. Ahora agrega los pasos de la secuencia.';
    $tipo_mensaje =
        'success';
}


// =====================================================
// PROCESAR FORMULARIOS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // =================================================
    // AGREGAR PASO
    // =================================================

    if (isset($_POST['agregar_paso'])) {

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

        $explicacion =
            trim(
                $_POST['explicacion']
                ??
                ''
            );

        $orden =
            isset($_POST['orden'])
                ? (int) $_POST['orden']
                : 0;

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
                'Escribe el contenido del paso.';
        }

        if (
            $tipo_elemento === 'Imagen' &&
            $imagen === ''
        ) {
            $errores[] =
                'Selecciona una imagen para el paso.';
        }

        if ($orden <= 0) {
            $errores[] =
                'El orden debe ser mayor que cero.';
        }

        if ($puntos <= 0) {
            $errores[] =
                'Los puntos deben ser mayores que cero.';
        }


        // =============================================
        // VALIDAR ORDEN REPETIDO
        // =============================================

        if (empty($errores)) {

            $stmtOrden =
                $conexion->prepare("
                    SELECT COUNT(*) AS total
                    FROM conecta_parejas
                    WHERE id_juego = ?
                    AND orden = ?
                ");

            $stmtOrden->bind_param(
                'ii',
                $id_juego,
                $orden
            );

            $stmtOrden->execute();

            $ordenExiste =
                (int) (
                    $stmtOrden
                        ->get_result()
                        ->fetch_assoc()['total']
                    ??
                    0
                );

            $stmtOrden->close();

            if ($ordenExiste > 0) {
                $errores[] =
                    'Ya existe un paso con ese número de orden.';
            }
        }


        // =============================================
        // INSERTAR
        //
        // Secuencia utiliza conecta_parejas para compartir
        // la misma estructura que el resto de juegos.
        // El contenido del paso se guarda en elemento A.
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

                $elemento_b_texto =
                    null;

                $elemento_b_imagen =
                    null;

                $categoria =
                    'Secuencia';


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
                    'Paso agregado correctamente.';
                $tipo_mensaje =
                    'success';


                $pasos =
                    cargarPasos(
                        $conexion,
                        $id_juego
                    );

            } catch (Throwable $e) {

                $mensaje =
                    'No se pudo agregar el paso: ' .
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
    // ELIMINAR PASO
    // =================================================

    elseif (isset($_POST['eliminar_paso'])) {

        $id_pareja =
            isset($_POST['id_pareja'])
                ? (int) $_POST['id_pareja']
                : 0;

        if ($id_pareja > 0) {

            try {

                $stmtEliminar =
                    $conexion->prepare("
                        DELETE FROM conecta_parejas
                        WHERE
                            id_pareja = ?
                            AND id_juego = ?
                    ");

                $stmtEliminar->bind_param(
                    'ii',
                    $id_pareja,
                    $id_juego
                );

                $stmtEliminar->execute();

                $stmtEliminar->close();


                $mensaje =
                    'Paso eliminado correctamente.';
                $tipo_mensaje =
                    'success';


                $pasos =
                    cargarPasos(
                        $conexion,
                        $id_juego
                    );

            } catch (Throwable $e) {

                $mensaje =
                    'No se pudo eliminar el paso.';
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

        if (count($pasos) < 2) {

            $mensaje =
                'Debes agregar al menos 2 pasos antes de publicar.';
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
                // ASIGNAR A LOS ALUMNOS DEL CURSO
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
// AUXILIARES
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


$siguienteOrden =
    count($pasos) > 0
        ? (
            max(
                array_map(
                    fn($paso) =>
                        (int) $paso['orden'],
                    $pasos
                )
            ) + 1
        )
        : 1;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Editar Secuencia - Docente</title>

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

        .secuencia-container {
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
                minmax(300px, 0.9fr)
                minmax(360px, 1.1fr);
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
            margin-bottom: 15px;
        }


        .tipo-opcion {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #475569;
            font-weight: 700;
            cursor: pointer;
            background: #ffffff;
            transition: .2s;
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
            font-size: 36px;
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
            margin-top: 7px;
            font-size: 12px;
            color: #64748b;
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


        .btn-primary:hover {
            background: #2a5bd6;
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


        .pasos-lista {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }


        .paso-card {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 15px;
            padding: 14px;
        }


        .paso-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 11px;
        }


        .numero {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #3b71f3;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            flex-shrink: 0;
        }


        .orden-badge {
            background: #eff6ff;
            color: #3b71f3;
            border-radius: 999px;
            padding: 5px 9px;
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


        .paso-contenido {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            color: #1e293b;
            font-weight: 700;
            text-align: center;
        }


        .paso-imagen {
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


        .btn-eliminar {
            margin-left: 7px;
            background: #fee2e2;
            color: #dc2626;
            border: none;
            border-radius: 9px;
            width: 35px;
            height: 35px;
            cursor: pointer;
        }


        .vacio {
            min-height: 220px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            text-align: center;
        }


        .vacio i {
            font-size: 45px;
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


        .hidden {
            display: none !important;
        }


        @media (max-width: 900px) {

            .grid {
                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 768px) {

            .secuencia-container {
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

    <!-- =================================================
         SIDEBAR
    ================================================= -->

    <aside class="sidebar">

        <div class="logo-section">
            <img
                src="../img/logo_g.png"
                alt="Logo Aulamos"
                class="logo-img"
            >
        </div>


        <nav class="menu">

            <a
                href="docente_dashboard.php"
                class="menu-item"
            >
                <i class="fa-solid fa-house"></i>
                Dashboard
            </a>


            <a
                href="crear_recurso.php"
                class="menu-item"
            >
                <i class="fa-solid fa-medal"></i>
                Crear Recurso
            </a>


            <a
                href="mis_recursos.php"
                class="menu-item"
            >
                <i class="fa-solid fa-folder-open"></i>
                Mis Recursos
            </a>


            <a
                href="crear_actividad.php"
                class="menu-item"
            >
                <i class="fa-solid fa-clipboard-check"></i>
                Crear Actividad
            </a>


            <a
                href="crear_evaluacion.php"
                class="menu-item"
            >
                <i class="fa-solid fa-clipboard-list"></i>
                Crear Evaluación
            </a>


            <a
                href="crear_juego.php"
                class="menu-item active"
            >
                <i class="fa-solid fa-gamepad"></i>
                Crear Juego
            </a>


            <a
                href="ver_estudiantes.php"
                class="menu-item"
            >
                <i class="fa-solid fa-users"></i>
                Ver Estudiantes
            </a>


            <a
                href="reporte.php"
                class="menu-item"
            >
                <i class="fa-solid fa-chart-column"></i>
                Reportes
            </a>


            <a
                href="pasarlista.php"
                class="menu-item"
            >
                <i class="fa-solid fa-bars"></i>
                Pasar Lista
            </a>


            <a
                href="juegos_docente.php"
                class="menu-item"
            >
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


    <!-- =================================================
         CONTENIDO
    ================================================= -->

    <main class="main-content">

        <header class="content-header">

            <div class="welcome-text">

                <h1>Secuencia</h1>

                <p>
                    Define el orden correcto de los pasos
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


                <a
                    href="mi_perfil_d.php"
                    class="user-profile"
                >

                    <img
                        src="<?php echo htmlspecialchars($ruta_foto_docente); ?>"
                        alt="Avatar Docente"
                        class="avatar"
                    >

                    <div class="user-info">

                        <span class="user-name">
                            <?php
                                echo htmlspecialchars(
                                    $nombre_docente
                                );
                            ?>
                        </span>

                        <span class="user-role">
                            Docente
                        </span>

                    </div>

                </a>

            </div>

        </header>


        <div class="secuencia-container">

            <a
                href="detalle_juego_docente.php?id_juego=<?php echo $id_juego; ?>"
                class="back-link"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Volver al detalle
            </a>


            <?php if ($mensaje): ?>

                <div
                    class="alert alert-<?php echo htmlspecialchars($tipo_mensaje); ?>"
                >
                    <?php
                        echo htmlspecialchars(
                            $mensaje
                        );
                    ?>
                </div>

            <?php endif; ?>


            <!-- =================================================
                 INFORMACIÓN DEL JUEGO
            ================================================= -->

            <div class="card">

                <div class="juego-header">

                    <div class="juego-icono">

                        <i class="fa-solid fa-list-ol"></i>

                    </div>


                    <div class="juego-info">

                        <h2>
                            <?php
                                echo htmlspecialchars(
                                    $juego['titulo']
                                );
                            ?>
                        </h2>


                        <p>
                            <?php
                                echo htmlspecialchars(
                                    $juego['materia']
                                );
                            ?>

                            ·

                            <?php
                                echo htmlspecialchars(
                                    $juego['curso']
                                );
                            ?>

                            ·

                            <?php
                                echo htmlspecialchars(
                                    $juego['grupo']
                                );
                            ?>
                        </p>


                        <div class="contador">

                            <?php
                                echo count(
                                    $pasos
                                );
                            ?>

                            paso(s) registrados

                        </div>

                    </div>

                </div>

            </div>


            <div class="grid">

                <!-- =================================================
                     NUEVO PASO
                ================================================= -->

                <div class="card">

                    <h3 class="seccion-titulo">
                        <i class="fa-solid fa-plus"></i>
                        Nuevo paso
                    </h3>


                    <p class="seccion-desc">
                        Agrega cada elemento siguiendo el orden
                        correcto que deberá reconstruir el alumno.
                    </p>


                    <form
                        method="POST"
                        id="formPaso"
                    >

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
                                Contenido del paso
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                class="input"
                                name="elemento_texto"
                                id="elementoTexto"
                                placeholder="Ej. Mezclar los ingredientes"
                            >

                        </div>


                        <!-- IMAGEN -->

                        <div
                            class="form-group hidden"
                            id="contenedorImagen"
                        >

                            <label>
                                Imagen del paso
                                <span class="required">*</span>
                            </label>


                            <div
                                class="upload-box"
                                id="uploadBox"
                            >

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
                                    id="imagenPaso"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    onchange="subirImagenPaso()"
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


                        <!-- ORDEN -->

                        <div class="form-group">

                            <label>
                                Orden correcto
                                <span class="required">*</span>
                            </label>

                            <input
                                type="number"
                                class="input"
                                name="orden"
                                id="orden"
                                min="1"
                                value="<?php echo (int) $siguienteOrden; ?>"
                                required
                            >

                        </div>


                        <!-- EXPLICACIÓN -->

                        <div class="form-group">

                            <label>
                                Explicación
                            </label>

                            <textarea
                                class="textarea"
                                name="explicacion"
                                placeholder="Explica qué ocurre en este paso."
                            ></textarea>

                        </div>


                        <!-- PUNTOS -->

                        <div class="form-group">

                            <label>
                                Puntos
                            </label>

                            <input
                                type="number"
                                class="input"
                                name="puntos"
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
                            name="agregar_paso"
                            class="btn btn-primary"
                        >
                            <i class="fa-solid fa-plus-circle"></i>
                            Agregar paso
                        </button>

                    </form>

                </div>


                <!-- =================================================
                     PASOS REGISTRADOS
                ================================================= -->

                <div class="card">

                    <h3 class="seccion-titulo">

                        <i class="fa-solid fa-list-ol"></i>

                        Pasos registrados

                        <span
                            style="
                                color:#64748b;
                                font-size:13px;
                                font-weight:500;
                            "
                        >
                            (<?php echo count($pasos); ?>)
                        </span>

                    </h3>


                    <p class="seccion-desc">
                        El número azul representa el orden correcto.
                    </p>


                    <?php if (empty($pasos)): ?>

                        <div class="vacio">

                            <i class="fa-solid fa-list-ol"></i>

                            <strong>
                                Todavía no hay pasos
                            </strong>

                            <span
                                style="
                                    margin-top:5px;
                                    font-size:12px;
                                "
                            >
                                Agrega al menos 2 pasos para publicar.
                            </span>

                        </div>

                    <?php else: ?>

                        <div class="pasos-lista">

                            <?php foreach ($pasos as $indice => $paso): ?>

                                <?php
                                    $imagenPaso =
                                        getUrlImagen(
                                            $paso['elemento_a_imagen']
                                        );
                                ?>

                                <div class="paso-card">

                                    <div class="paso-header">

                                        <div class="numero">
                                            <?php
                                                echo (int) $paso['orden'];
                                            ?>
                                        </div>


                                        <span class="orden-badge">

                                            Orden
                                            <?php
                                                echo (int) $paso['orden'];
                                            ?>

                                        </span>


                                        <span class="puntos-badge">

                                            <i class="fa-solid fa-star"></i>

                                            <?php
                                                echo (int) $paso['puntos'];
                                            ?>

                                            pts

                                        </span>


                                        <?php if ($juego['estado'] === 'Borrador'): ?>

                                            <form
                                                method="POST"
                                                style="margin:0;"
                                                onsubmit="return confirm('¿Eliminar este paso?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="id_pareja"
                                                    value="<?php echo (int) $paso['id_pareja']; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="eliminar_paso"
                                                    class="btn-eliminar"
                                                    title="Eliminar paso"
                                                >
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>


                                    <div class="paso-contenido">

                                        <?php if ($imagenPaso): ?>

                                            <img
                                                src="<?php echo htmlspecialchars($imagenPaso); ?>"
                                                class="paso-imagen"
                                                alt="Imagen del paso <?php echo (int) $paso['orden']; ?>"
                                            >

                                        <?php else: ?>

                                            <?php
                                                echo htmlspecialchars(
                                                    $paso['elemento_a_texto']
                                                    ??
                                                    'Sin contenido'
                                                );
                                            ?>

                                        <?php endif; ?>

                                    </div>


                                    <?php if (!empty($paso['explicacion'])): ?>

                                        <div class="explicacion">

                                            <i class="fa-regular fa-circle-info"></i>

                                            <span>
                                                <?php
                                                    echo htmlspecialchars(
                                                        $paso['explicacion']
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
                            ¿Terminaste la secuencia?
                        </h4>

                        <p>
                            Para publicar debes agregar al menos
                            2 pasos. Después se asignará a los
                            alumnos activos del curso.
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
                                echo count($pasos) < 2
                                    ? 'disabled'
                                    : '';
                            ?>
                            onclick="return confirm('¿Publicar este juego de Secuencia?');"
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


        <?php
            include '../Accesibilidad/accesibilidad.php';
        ?>

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
// SUBIR IMAGEN
// =====================================================

async function subirImagenPaso() {

    const input =
        document.getElementById(
            'imagenPaso'
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
        'formPaso'
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


            const orden =
                Number(
                    document
                        .getElementById(
                            'orden'
                        )
                        .value
                );


            if (
                tipo === 'Texto' &&
                !texto
            ) {

                evento.preventDefault();

                alert(
                    'Escribe el contenido del paso.'
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


            if (
                !Number.isInteger(
                    orden
                ) ||
                orden <= 0
            ) {

                evento.preventDefault();

                alert(
                    'El orden debe ser un número entero mayor que cero.'
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
