<?php
session_start();

// =============================================
// VERIFICAR SESIÓN DEL ALUMNO
// =============================================
if (
    !isset($_SESSION['usuario']) ||
    $_SESSION['usuario']['rol'] !== 'Alumno'
) {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_usuario = (int) $_SESSION['usuario']['id_usuario'];

// =============================================
// FILTRO DE MATERIA
// =============================================
$filtroMateria = $_GET['materia'] ?? 'todas';

// =============================================
// MATERIAS DE LOS CURSOS DEL ALUMNO
// =============================================
$stmt = $conexion->prepare("
    SELECT DISTINCT
        m.campo_formativo
    FROM inscripciones i
    INNER JOIN cursos c
        ON c.id_curso = i.id_curso
    INNER JOIN materias m
        ON m.id_materia = c.id_materia
    WHERE i.id_alumno = ?
      AND i.estado = 'Activo'
      AND c.estado = 'Activo'
      AND m.estado = 'Activa'
    ORDER BY m.campo_formativo
");

$stmt->bind_param("i", $id_usuario);
$stmt->execute();

$materias = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// =============================================
// RECURSOS DISPONIBLES PARA EL ALUMNO
//
// La lógica es equivalente a la utilizada por
// la Biblioteca móvil:
// - recursos públicos
// - recursos de cursos donde el alumno está
//   inscrito activamente
// =============================================

$sqlRecursos = "
    SELECT DISTINCT
        r.id_recurso,
        r.id_actividad,
        r.id_materia,
        r.id_curso,
        r.titulo,
        r.descripcion,
        r.tipo,
        r.url_recurso,
        r.url_subtitulos,
        r.accesible,
        r.subtitulos_disponibles,
        r.fecha_publicacion,

        m.nombre AS materia,
        m.campo_formativo,

        c.nombre AS curso,

        a.titulo AS actividad,

        CONCAT_WS(
            ' ',
            u.nombre,
            u.apellido_paterno
        ) AS docente

    FROM recursos_educativos r

    LEFT JOIN materias m
        ON m.id_materia = r.id_materia

    LEFT JOIN actividades a
        ON a.id_actividad = r.id_actividad

    LEFT JOIN cursos c
        ON c.id_curso = r.id_curso

    LEFT JOIN usuarios u
        ON u.id_usuario = r.id_docente

    WHERE r.estado = 'Activo'

      AND (
            r.compartido_tipo = 'Publico'

            OR EXISTS (
                SELECT 1
                FROM inscripciones i

                INNER JOIN cursos ci
                    ON ci.id_curso = i.id_curso

                WHERE i.id_alumno = ?
                  AND i.estado = 'Activo'
                  AND ci.estado = 'Activo'

                  AND (
                        (
                            r.id_actividad IS NOT NULL

                            AND EXISTS (
                                SELECT 1
                                FROM actividades ar

                                WHERE ar.id_actividad = r.id_actividad
                                  AND ar.id_curso = ci.id_curso
                            )
                        )

                        OR

                        (
                            r.id_actividad IS NULL
                            AND r.id_curso = ci.id_curso
                        )
                  )
            )
      )
";

$usarFiltroMateria =
    $filtroMateria !== 'todas' &&
    $filtroMateria !== '';

if ($usarFiltroMateria) {
    $sqlRecursos .= "
        AND m.campo_formativo = ?
    ";
}

$sqlRecursos .= "
    ORDER BY
        r.fecha_publicacion DESC,
        r.id_recurso DESC
";

$stmt = $conexion->prepare($sqlRecursos);

if ($usarFiltroMateria) {
    $stmt->bind_param(
        "is",
        $id_usuario,
        $filtroMateria
    );
} else {
    $stmt->bind_param(
        "i",
        $id_usuario
    );
}

$stmt->execute();

$recursos = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// =============================================
// ICONOS POR TIPO DE RECURSO
// =============================================

$tiposMap = [
    'video' => [
        'icono' => 'fa-solid fa-video',
        'label' => 'Video'
    ],

    'pdf' => [
        'icono' => 'fa-solid fa-file-pdf',
        'label' => 'PDF'
    ],

    'imagen' => [
        'icono' => 'fa-solid fa-image',
        'label' => 'Imagen'
    ],

    'audio' => [
        'icono' => 'fa-solid fa-music',
        'label' => 'Audio'
    ],

    'enlace' => [
        'icono' => 'fa-solid fa-link',
        'label' => 'Enlace'
    ],

    'presentación' => [
        'icono' => 'fa-solid fa-presentation-screen',
        'label' => 'Presentación'
    ],

    'documento' => [
        'icono' => 'fa-solid fa-file-lines',
        'label' => 'Documento'
    ]
];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Biblioteca Digital - Aulamos
    </title>

    <link
        rel="stylesheet"
        href="Style/Inicio.css"
    >

    <link
        rel="stylesheet"
        href="Style/Biblioteca.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <style>

        /* ACCESIBILIDAD - DISEÑO AULAMOS */
        .accessibility-bar{
            margin-top:34px;
            padding:0;
            background:transparent;
            border:0;
            display:flex;
            flex-direction:column;
            align-items:flex-start;
            gap:0;
        }
        .acc-info{
            display:flex;
            align-items:flex-start;
            gap:8px;
            margin-bottom:2px;
        }
        .acc-icon-main{
            color:#111827;
            font-size:20px;
            margin-top:2px;
        }
        .acc-info strong{
            display:block;
            color:#111827;
            font-size:16px;
            line-height:1.25;
            font-weight:800;
        }
        .acc-info p{
            margin:2px 0 0;
            color:#1e3a5f;
            font-size:15px;
            line-height:1.35;
        }
        .acc-options{
            display:flex;
            flex-wrap:wrap;
            gap:3px;
            margin-top:1px;
        }
        .acc-opt-btn,.btn-open-config{
            appearance:none;
            -webkit-appearance:none;
            border:1px solid #6b7280;
            background:#f8fafc;
            color:#111827;
            border-radius:2px;
            min-height:22px;
            padding:1px 6px;
            font-family:inherit;
            font-size:13px;
            line-height:18px;
            cursor:pointer;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:3px;
            box-shadow:none;
            transition:background .15s ease,border-color .15s ease;
        }
        .acc-opt-btn:hover,.btn-open-config:hover{
            background:#eef2f7;
            border-color:#334155;
        }
        .acc-opt-btn:focus-visible,.btn-open-config:focus-visible{
            outline:3px solid #2563eb;
            outline-offset:2px;
        }
        .acc-opt-btn i{font-size:12px}
        .btn-open-config{margin-top:3px;background:#f8fafc}
        .acc-opt-btn.activo{
            background:#eaf2ff;
            border-color:#2563eb;
            color:#1d4ed8;
        }
        body.modo-oscuro .acc-info strong{color:#f8fafc}
        body.modo-oscuro .acc-info p{color:#dbeafe}
        body.modo-oscuro .acc-icon-main{color:#f8fafc}
        body.modo-oscuro .acc-opt-btn,
        body.modo-oscuro .btn-open-config{
            background:#1f2937;
            border-color:#64748b;
            color:#f8fafc;
        }
        body.alto-contraste .acc-info strong,
        body.alto-contraste .acc-info p,
        body.alto-contraste .acc-icon-main{color:#ffff00!important}
        body.alto-contraste .acc-opt-btn,
        body.alto-contraste .btn-open-config{
            background:#000!important;
            color:#fff!important;
            border-color:#fff!important;
        }
        @media(max-width:700px){
            .accessibility-bar{margin-top:25px}
            .acc-options{gap:5px}
            .acc-opt-btn,.btn-open-config{font-size:12px}
        }

        .recurso-card {
            width: 100%;
            font: inherit;
        }

        button.recurso-card {
            cursor: pointer;
        }

        button.recurso-card:disabled {
            cursor: not-allowed;
            opacity: 0.65;
        }

        .recurso-descripcion {
            color: #64748b;
            font-size: 0.82rem;
            margin-top: 10px;
            line-height: 1.4;
        }

        .recurso-detalle {
            color: #64748b;
            font-size: 0.78rem;
            margin-top: 5px;
        }

        .recurso-abrir {
            margin-top: 14px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #2563eb;
        }

        body.modo-oscuro .recurso-descripcion,
        body.modo-oscuro .recurso-detalle {
            color: #cbd5e1;
        }

        body.alto-contraste .recurso-descripcion,
        body.alto-contraste .recurso-detalle,
        body.alto-contraste .recurso-abrir {
            color: #ffff00 !important;
        }
    </style>
</head>

<body>

<div class="dashboard-container">

    <!-- =========================================
         BARRA LATERAL
    ========================================== -->
    <aside class="sidebar">

        <div class="logo-section">
            <img
                src="../img/logogeneral.png"
                alt="Logo AulamosWeb"
                class="logo"
            >
        </div>

        <nav class="menu">

            <a
                href="alumno.php"
                class="menu-item"
            >
                <i class="fa-solid fa-house"></i>
                Inicio
            </a>

            <a
                href="actividades.php"
                class="menu-item"
            >
                <i class="fa-solid fa-cubes"></i>
                Mis actividades
            </a>
            
    <a href="juegos_alumno.php" class="menu-item"><i class="fa-solid fa-gamepad"></i> Conecta y Aprende</a>

            <a
                href="Biblioteca.php"
                class="menu-item active"
            >
                <i class="fa-solid fa-book-open"></i>
                Biblioteca digital
            </a>

            <a
                href="avances.php"
                class="menu-item"
            >
                <i class="fa-solid fa-pen-to-square"></i>
                Mis avances
            </a>

            <a
                href="ayuda.php"
                class="menu-item"
            >
                <i class="fa-solid fa-circle-question"></i>
                Ayuda
            </a>

            <a
                href="accesibilidad.php"
                class="menu-item"
            >
                <i class="fa-solid fa-gear"></i>
                Accesibilidad
            </a>

        </nav>

        <button class="btn-accessibility-main" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>

        <div class="menu-spacer"></div>

        <a
            href="../InicioSesion/cerrar_sesion.php"
            class="menu-item btn-logout"
        >
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            Cerrar sesión
        </a>

    </aside>

    <!-- =========================================
         CONTENIDO PRINCIPAL
    ========================================== -->
    <main class="main-content">

        <!-- CABECERA -->
        <header class="content-header">

            <div class="welcome-text">

                <h1>
                    Biblioteca Digital
                </h1>

                <p>
                    Explora los recursos compartidos
                    en tus cursos.
                </p>

            </div>

            <div class="header-actions">

                <button
                    class="btn-assistant"
                    id="btn-asistente"
                    type="button"
                    onclick="window.open('Chatbot.php', '_blank')"
                >
                    Asistente Virtual
                    <span class="robot-icon">🤖</span>
                </button>

                <div class="icon-bell">
                    <i class="fa-regular fa-bell"></i>
                </div>

            </div>

             <!-- NUEVA ACCESIBILIDAD -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">

        </header>

        <!-- =========================================
             FILTROS DE MATERIA
        ========================================== -->
        <div class="filtros-materia">

            <a
                href="?materia=todas"
                class="<?php
                    echo $filtroMateria === 'todas'
                        ? 'activo'
                        : '';
                ?>"
            >
                Todas
            </a>

            <?php foreach ($materias as $materia): ?>

                <?php
                    $campo =
                        $materia['campo_formativo'];
                ?>

                <a
                    href="?materia=<?php
                        echo urlencode($campo);
                    ?>"
                    class="<?php
                        echo $filtroMateria === $campo
                            ? 'activo'
                            : '';
                    ?>"
                >
                    <?php
                        echo htmlspecialchars(
                            $campo,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    ?>
                </a>

            <?php endforeach; ?>

        </div>

        <!-- =========================================
             RECURSOS
        ========================================== -->
        <?php if (count($recursos) > 0): ?>

            <div
                class="grid-recursos"
                id="gridRecursos"
            >

                <?php foreach ($recursos as $recurso): ?>

                    <?php
                        $tipo =
                            strtolower(
                                $recurso['tipo']
                            );

                        $icono =
                            $tiposMap[$tipo]['icono']
                            ?? 'fa-solid fa-file';

                        $label =
                            $tiposMap[$tipo]['label']
                            ?? ucfirst($tipo);

                        $tieneArchivo =
                            !empty(
                                $recurso['url_recurso']
                            );
                    ?>

                    <button
                        type="button"
                        class="recurso-card js-abrir-recurso"

                        data-url="<?php
                            echo htmlspecialchars(
                                $recurso['url_recurso'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>"

                        data-titulo="<?php
                            echo htmlspecialchars(
                                $recurso['titulo'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>"

                        <?php
                            echo !$tieneArchivo
                                ? 'disabled'
                                : '';
                        ?>
                    >

                        <div class="recurso-icono">

                            <i class="<?php
                                echo htmlspecialchars(
                                    $icono,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"></i>

                        </div>

                        <div class="recurso-titulo">

                            <?php
                                echo htmlspecialchars(
                                    $recurso['titulo'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>

                        </div>

                        <?php if (!empty($recurso['materia'])): ?>

                            <div class="recurso-materia">

                                <?php
                                    echo htmlspecialchars(
                                        $recurso['materia'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                ?>

                            </div>

                        <?php endif; ?>

                        <span class="recurso-tipo">

                            <?php
                                echo htmlspecialchars(
                                    $label,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>

                        </span>

                        <?php if (!empty($recurso['descripcion'])): ?>

                            <div class="recurso-descripcion">

                                <?php
                                    echo htmlspecialchars(
                                        $recurso['descripcion'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                ?>

                            </div>

                        <?php endif; ?>

                        <?php if (!empty($recurso['curso'])): ?>

                            <div class="recurso-detalle">

                                <i class="fa-solid fa-book"></i>

                                <?php
                                    echo htmlspecialchars(
                                        $recurso['curso'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                ?>

                            </div>

                        <?php endif; ?>

                        <?php if (!empty($recurso['docente'])): ?>

                            <div class="recurso-detalle">

                                <i class="fa-solid fa-user"></i>

                                <?php
                                    echo htmlspecialchars(
                                        $recurso['docente'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                ?>

                            </div>

                        <?php endif; ?>

                        <div class="recurso-abrir">

                            <?php if ($tieneArchivo): ?>

                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                Abrir recurso

                            <?php else: ?>

                                Sin archivo disponible

                            <?php endif; ?>

                        </div>

                    </button>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <p
                style="
                    text-align:center;
                    padding:35px 20px;
                    color:#64748b;
                "
            >
                No hay recursos disponibles
                para tus cursos.
            </p>

        <?php endif; ?>

       

    </main>

</div>

        <!-- ========================================== -->
        <!-- NUEVA BARRA DE ACCESIBILIDAD               -->
        <!-- ========================================== -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </main>
</div>

<!-- ========================================== -->
<!-- BOTÓN FLOTANTE DE ACCESIBILIDAD            -->
<!-- ========================================== -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>


<?php
include '../API/teclado_accesibilidad.php';
?>

<script src="js/navegacionTeclado.js"></script>
<script src="js/Accesibilidad.js"></script>
<script src="js/Biblioteca.js"></script>
<script src="../Administrador/js/lector.js"></script>
<script src="js/Inicio.js"></script>
<!-- NUEVA ACCESIBILIDAD -->
<script src="../Accesibilidad/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>