<?php

session_start();


// =====================================================
// VERIFICAR SESIÓN Y ROL
// =====================================================

if (
    !isset($_SESSION['usuario']) ||
    $_SESSION['usuario']['rol'] !== 'Investigador'
) {

    header(
        'Location: ../InicioSesion/login.php'
    );

    exit;
}


// =====================================================
// ID DEL USUARIO PARA ACCESIBILIDAD
// =====================================================

$idUsuario =
    (int) $_SESSION['usuario']['id_usuario'];


// =====================================================
// CONEXIÓN
// =====================================================

require_once '../Conexion/conexion.php';


$titulo_pagina =
    'Métricas de uso';


$descripcion_pagina =
    'Consulta cómo utilizan los estudiantes la plataforma durante las pruebas de uso.';


// =====================================================
// DETECTAR PRUEBA ACTIVA
// =====================================================

$stmt =
    $conexion->prepare("
        SELECT
            id_prueba,
            nombre,
            fecha_inicio,
            fecha_fin
        FROM pruebas_investigacion
        WHERE estado = 'Activa'
        ORDER BY fecha_inicio DESC
        LIMIT 1
    ");


$stmt->execute();


$resultado =
    $stmt->get_result();


$prueba_activa =
    $resultado->fetch_assoc();


$stmt->close();


// =====================================================
// DATOS DE LA PRUEBA ACTIVA
// =====================================================

$id_prueba_activa =
    $prueba_activa['id_prueba']
    ?? null;


$prueba_activa_nombre =
    $prueba_activa['nombre']
    ?? 'Ninguna';


$fecha_inicio_prueba =
    $prueba_activa['fecha_inicio']
    ?? null;


$fecha_fin_prueba =
    $prueba_activa['fecha_fin']
    ?? null;


// =====================================================
// FORMATEAR FECHA DEL PERIODO
// =====================================================

function formatearFechaPeriodo(
    $fecha
) {

    if (
        empty($fecha)
    ) {

        return null;
    }


    $meses = [

        1 => 'Ene',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Abr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ago',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dic',

    ];


    $timestamp =
        strtotime(
            $fecha
        );


    if (
        !$timestamp
    ) {

        return null;
    }


    $dia =
        date(
            'd',
            $timestamp
        );


    $numeroMes =
        (int) date(
            'n',
            $timestamp
        );


    $anio =
        date(
            'Y',
            $timestamp
        );


    return
        $dia .
        ' ' .
        $meses[$numeroMes] .
        ' ' .
        $anio;
}


// =====================================================
// CONSTRUIR PERIODO DE LA PRUEBA
// =====================================================

if (
    $fecha_inicio_prueba
) {

    $fechaInicioFormateada =
        formatearFechaPeriodo(
            $fecha_inicio_prueba
        );


    if (
        $fecha_fin_prueba
    ) {

        $fechaFinFormateada =
            formatearFechaPeriodo(
                $fecha_fin_prueba
            );


        $periodo_prueba =
            $fechaInicioFormateada .
            ' - ' .
            $fechaFinFormateada;

    } else {

        $periodo_prueba =
            $fechaInicioFormateada .
            ' - En curso';
    }

} else {

    if (
        $id_prueba_activa
    ) {

        $periodo_prueba =
            'Sin fechas definidas';

    } else {

        $periodo_prueba =
            'No hay una prueba activa';
    }
}


// =====================================================
// CONSULTAS A LA BD
// FILTRADAS POR PRUEBA ACTIVA
// =====================================================


// =====================================================
// TOTAL DE ACCESOS
// =====================================================

if (
    $id_prueba_activa
) {

    $stmt =
        $conexion->prepare("
            SELECT
                COUNT(*) AS total

            FROM eventos_investigacion e

            INNER JOIN participantes_prueba pp
                ON e.id_usuario = pp.id_usuario

            WHERE
                e.tipo_evento = 'InicioSesion'
                AND pp.id_prueba = ?
        ");


    $stmt->bind_param(
        "i",
        $id_prueba_activa
    );

} else {

    $stmt =
        $conexion->prepare("
            SELECT
                COUNT(*) AS total

            FROM eventos_investigacion

            WHERE tipo_evento = 'InicioSesion'
        ");
}


$stmt->execute();


$resultado =
    $stmt->get_result();


$fila =
    $resultado->fetch_assoc();


$total_accesos =
    (int) (
        $fila['total']
        ?? 0
    );


$stmt->close();


// =====================================================
// TOTAL DE ESTUDIANTES
// =====================================================

if (
    $id_prueba_activa
) {

    $stmt =
        $conexion->prepare("
            SELECT
                COUNT(
                    DISTINCT u.id_usuario
                ) AS total

            FROM usuarios u

            INNER JOIN usuario_roles ur
                ON u.id_usuario = ur.id_usuario

            INNER JOIN participantes_prueba pp
                ON u.id_usuario = pp.id_usuario

            WHERE
                ur.id_rol = 1
                AND u.estado = 'Activo'
                AND pp.id_prueba = ?
        ");


    $stmt->bind_param(
        "i",
        $id_prueba_activa
    );

} else {

    $stmt =
        $conexion->prepare("
            SELECT
                COUNT(
                    DISTINCT u.id_usuario
                ) AS total

            FROM usuarios u

            INNER JOIN usuario_roles ur
                ON u.id_usuario = ur.id_usuario

            WHERE
                ur.id_rol = 1
                AND u.estado = 'Activo'
        ");
}


$stmt->execute();


$resultado =
    $stmt->get_result();


$fila =
    $resultado->fetch_assoc();


$total_estudiantes =
    (int) (
        $fila['total']
        ?? 0
    );


$stmt->close();


// =====================================================
// PROMEDIO DE ACCESOS POR ESTUDIANTE
// =====================================================

$promedio_accesos =
    $total_estudiantes > 0
        ? round(
            $total_accesos /
            $total_estudiantes,
            1
        )
        : 0;


// =====================================================
// MÓDULOS MÁS VISITADOS
// =====================================================

if (
    $id_prueba_activa
) {

    $stmt =
        $conexion->prepare("
            SELECT
                e.modulo,
                COUNT(*) AS visitas

            FROM eventos_investigacion e

            INNER JOIN participantes_prueba pp
                ON e.id_usuario = pp.id_usuario

            WHERE
                e.modulo IS NOT NULL
                AND e.modulo <> ''
                AND pp.id_prueba = ?

            GROUP BY e.modulo

            ORDER BY visitas DESC

            LIMIT 5
        ");


    $stmt->bind_param(
        "i",
        $id_prueba_activa
    );

} else {

    $stmt =
        $conexion->prepare("
            SELECT
                modulo,
                COUNT(*) AS visitas

            FROM eventos_investigacion

            WHERE
                modulo IS NOT NULL
                AND modulo <> ''

            GROUP BY modulo

            ORDER BY visitas DESC

            LIMIT 5
        ");
}


$stmt->execute();


$resultado =
    $stmt->get_result();


$modulos_visitados =
    $resultado->fetch_all(
        MYSQLI_ASSOC
    );


$stmt->close();


// =====================================================
// MAYOR NÚMERO DE VISITAS
// =====================================================

$max_visitas =
    !empty(
        $modulos_visitados
    )
        ? max(
            array_column(
                $modulos_visitados,
                'visitas'
            )
        )
        : 1;


// =====================================================
// MÓDULO MÁS VISITADO
// =====================================================

$modulo_mas_visitado =
    !empty(
        $modulos_visitados
    )
        ? (
            $modulos_visitados[0]['modulo']
            ?? 'Sin datos'
        )
        : 'Sin datos';


// =====================================================
// ACTIVIDAD RECIENTE
// =====================================================

if (
    $id_prueba_activa
) {

    $stmt =
        $conexion->prepare("
            SELECT
                u.nombre,
                u.apellido_paterno,
                e.modulo,
                e.accion,
                e.fecha_hora

            FROM eventos_investigacion e

            INNER JOIN usuarios u
                ON e.id_usuario = u.id_usuario

            INNER JOIN participantes_prueba pp
                ON e.id_usuario = pp.id_usuario

            WHERE
                pp.id_prueba = ?

            ORDER BY
                e.fecha_hora DESC

            LIMIT 5
        ");


    $stmt->bind_param(
        "i",
        $id_prueba_activa
    );

} else {

    $stmt =
        $conexion->prepare("
            SELECT
                u.nombre,
                u.apellido_paterno,
                e.modulo,
                e.accion,
                e.fecha_hora

            FROM eventos_investigacion e

            INNER JOIN usuarios u
                ON e.id_usuario = u.id_usuario

            ORDER BY
                e.fecha_hora DESC

            LIMIT 5
        ");
}


$stmt->execute();


$resultado =
    $stmt->get_result();


$actividad_reciente =
    $resultado->fetch_all(
        MYSQLI_ASSOC
    );


$stmt->close();

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
        Uso de la plataforma - Investigador
    </title>


    <link
        rel="stylesheet"
        href="styles/metricas_uso.css"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >


    <link
        rel="stylesheet"
        href="../Accesibilidad/accesibilidad.css"
    >


    <!-- =================================================
         USUARIO PARA ACCESIBILIDAD
    ================================================= -->

    <script>

        window.idUsuario =
            <?php echo $idUsuario; ?>;

    </script>

</head>


<body>


<div class="dashboard-container">


    <!-- =================================================
         SIDEBAR
    ================================================= -->

    <?php
        include 'includes/sidebar.php';
    ?>


    <!-- =================================================
         CONTENIDO PRINCIPAL
    ================================================= -->

    <main class="main-content">


        <!-- =================================================
             HEADER
        ================================================= -->

        <?php
            include 'includes/header.php';
        ?>


        <!-- =================================================
             AVISO DE PRUEBA ACTIVA
        ================================================= -->

        <?php if ($id_prueba_activa): ?>


            <div
                style="
                    background:#f3e8fd;
                    border:1px solid #7C3AED;
                    border-radius:12px;
                    padding:12px 20px;
                    margin-bottom:20px;
                    display:flex;
                    align-items:center;
                    gap:12px;
                "
            >

                <i
                    class="fa-solid fa-flask"
                    style="
                        color:#7C3AED;
                        font-size:18px;
                    "
                ></i>


                <span
                    style="
                        color:#5a189a;
                        font-weight:600;
                    "
                >

                    Prueba activa:

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $prueba_activa_nombre
                        );

                        ?>

                    </strong>


                    <span
                        style="
                            font-weight:400;
                            color:#7C3AED;
                        "
                    >

                        — Los datos mostrados corresponden
                        SOLO a los participantes de esta prueba.

                    </span>

                </span>

            </div>


        <?php else: ?>


            <div
                style="
                    background:#f1f5f9;
                    border:1px solid #e2e8f0;
                    border-radius:12px;
                    padding:12px 20px;
                    margin-bottom:20px;
                    display:flex;
                    align-items:center;
                    gap:12px;
                "
            >

                <i
                    class="fa-solid fa-circle-info"
                    style="
                        color:#64748b;
                        font-size:18px;
                    "
                ></i>


                <span
                    style="
                        color:#475569;
                        font-weight:500;
                    "
                >

                    No hay prueba activa.

                    Los datos muestran

                    <strong>
                        todos los estudiantes
                    </strong>

                    del sistema.

                </span>

            </div>


        <?php endif; ?>


        <!-- =================================================
             PERIODO ANALIZADO
        ================================================= -->

        <div class="periodo-selector">


            <div class="periodo-info">


                <i class="fa-solid fa-calendar"></i>


                <div>


                    <span class="periodo-etiqueta">

                        Periodo analizado

                    </span>


                    <span class="periodo-valor">

                        <?php

                        echo htmlspecialchars(
                            $periodo_prueba
                        );

                        ?>

                    </span>


                    <?php if ($id_prueba_activa): ?>

                        <span
                            style="
                                display:block;
                                margin-top:3px;
                                color:#94a3b8;
                                font-size:11px;
                            "
                        >

                            <?php

                            echo htmlspecialchars(
                                $prueba_activa_nombre
                            );

                            ?>

                        </span>

                    <?php endif; ?>


                </div>


            </div>


            <button
                class="btn-periodo"
                type="button"
                aria-label="Periodo correspondiente a la prueba activa"
            >

                <i class="fa-solid fa-chevron-down"></i>

            </button>


        </div>


        <!-- =================================================
             RESUMEN
        ================================================= -->

        <section class="resumen-investigador">


            <div class="stats-row">


                <!-- =========================================
                     ACCESOS
                ========================================== -->

                <div class="stat-card">


                    <div
                        class="stat-icon"
                        style="background:#e8f0fe;"
                    >

                        <i
                            class="fa-solid fa-door-open"
                            style="color:#3b71f3;"
                        ></i>

                    </div>


                    <div class="stat-info">


                        <span class="stat-number">

                            <?php echo $total_accesos; ?>

                        </span>


                        <span class="stat-label">

                            Accesos

                        </span>


                    </div>

                </div>


                <!-- =========================================
                     ESTUDIANTES
                ========================================== -->

                <div class="stat-card">


                    <div
                        class="stat-icon"
                        style="background:#e6f7e6;"
                    >

                        <i
                            class="fa-solid fa-users"
                            style="color:#2e7d32;"
                        ></i>

                    </div>


                    <div class="stat-info">


                        <span class="stat-number">

                            <?php echo $total_estudiantes; ?>

                        </span>


                        <span class="stat-label">

                            Estudiantes

                            <?php

                            echo $id_prueba_activa
                                ? 'seleccionados'
                                : 'activos';

                            ?>

                        </span>


                    </div>

                </div>


                <!-- =========================================
                     PROMEDIO
                ========================================== -->

                <div class="stat-card">


                    <div
                        class="stat-icon"
                        style="background:#f3e8fd;"
                    >

                        <i
                            class="fa-solid fa-repeat"
                            style="color:#7b1fa2;"
                        ></i>

                    </div>


                    <div class="stat-info">


                        <span class="stat-number">

                            <?php echo $promedio_accesos; ?>

                        </span>


                        <span class="stat-label">

                            Promedio de accesos

                        </span>


                    </div>

                </div>


                <!-- =========================================
                     MAYOR FRECUENCIA
                ========================================== -->

                <div class="stat-card">


                    <div
                        class="stat-icon"
                        style="background:#fff3e0;"
                    >

                        <i
                            class="fa-solid fa-star"
                            style="color:#e65100;"
                        ></i>

                    </div>


                    <div class="stat-info">


                        <span class="stat-number">

                            <?php

                            echo !empty(
                                $modulos_visitados
                            )
                                ? (int) $modulos_visitados[0]['visitas']
                                : 0;

                            ?>

                        </span>


                        <span class="stat-label">

                            Mayor frecuencia

                        </span>


                    </div>

                </div>


            </div>

        </section>


        <!-- =================================================
             MÓDULOS MÁS VISITADOS
        ================================================= -->

        <section class="modulos-detalle">


            <div class="modulos-header">


                <h3>

                    <i class="fa-solid fa-chart-simple"></i>

                    Módulos más visitados

                </h3>


                <span class="modulos-sub">

                    Número de visitas

                </span>


            </div>


            <div class="modulos-lista-detalle">


                <?php if (empty($modulos_visitados)): ?>


                    <p
                        style="
                            color:#94a3b8;
                            text-align:center;
                            padding:20px;
                        "
                    >

                        No hay datos disponibles.

                    </p>


                <?php else: ?>


                    <?php foreach ($modulos_visitados as $modulo): ?>


                        <?php

                        $visitas =
                            (int) (
                                $modulo['visitas']
                                ?? 0
                            );


                        $porcentaje =
                            $max_visitas > 0
                                ? (
                                    $visitas /
                                    $max_visitas
                                ) * 100
                                : 0;

                        ?>


                        <div class="modulo-detalle-item">


                            <div class="modulo-detalle-info">


                                <span class="modulo-detalle-nombre">

                                    <?php

                                    echo htmlspecialchars(
                                        $modulo['modulo']
                                        ?: 'Sin módulo'
                                    );

                                    ?>

                                </span>


                                <span class="modulo-detalle-visitas">

                                    <?php echo $visitas; ?>

                                </span>


                            </div>


                            <div class="modulo-detalle-barra">


                                <div
                                    class="modulo-detalle-llena"
                                    style="
                                        width:
                                        <?php
                                            echo min(
                                                100,
                                                $porcentaje
                                            );
                                        ?>%;
                                    "
                                ></div>


                            </div>


                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </div>


            <div class="modulo-destacado">


                <i class="fa-solid fa-arrow-trend-up"></i>


                <span>

                    El módulo con mayor frecuencia de uso es

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $modulo_mas_visitado
                        );

                        ?>

                    </strong>.

                </span>


            </div>


        </section>


        <!-- =================================================
             ACTIVIDAD RECIENTE
        ================================================= -->

        <section class="actividad-reciente">


            <h3>

                <i class="fa-regular fa-clock"></i>

                Actividad reciente

            </h3>


            <div class="actividad-lista">


                <?php if (empty($actividad_reciente)): ?>


                    <p
                        style="
                            color:#94a3b8;
                            text-align:center;
                            padding:20px;
                        "
                    >

                        No hay actividad reciente.

                    </p>


                <?php else: ?>


                    <?php foreach ($actividad_reciente as $actividad): ?>


                        <div class="actividad-item">


                            <div class="actividad-icono">

                                <i class="fa-solid fa-user"></i>

                            </div>


                            <div class="actividad-info">


                                <span class="actividad-usuario">

                                    <?php

                                    echo htmlspecialchars(
                                        trim(
                                            (
                                                $actividad['nombre']
                                                ?? ''
                                            )
                                            .
                                            ' '
                                            .
                                            (
                                                $actividad['apellido_paterno']
                                                ?? ''
                                            )
                                        )
                                    );

                                    ?>

                                </span>


                                <span class="actividad-accion">

                                    <?php

                                    echo htmlspecialchars(
                                        $actividad['accion']
                                        ?? ''
                                    );

                                    ?>


                                    <?php if (!empty($actividad['modulo'])): ?>

                                        en

                                        <strong>

                                            <?php

                                            echo htmlspecialchars(
                                                $actividad['modulo']
                                            );

                                            ?>

                                        </strong>

                                    <?php endif; ?>


                                </span>


                                <span class="actividad-fecha">

                                    <?php

                                    if (
                                        !empty(
                                            $actividad['fecha_hora']
                                        )
                                    ) {

                                        echo date(
                                            'd/m/Y, h:i a',
                                            strtotime(
                                                $actividad['fecha_hora']
                                            )
                                        );

                                    } else {

                                        echo 'Sin fecha';
                                    }

                                    ?>

                                </span>


                            </div>


                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </div>


        </section>


        <!-- =================================================
             INFORMACIÓN REGISTRADA
        ================================================= -->

        <section class="info-registrada">


            <h3>

                <i class="fa-solid fa-list-check"></i>

                Información registrada

            </h3>


            <div class="info-grid">


                <div class="info-item">

                    <i
                        class="fa-solid fa-check-circle"
                        style="color:#2e7d32;"
                    ></i>

                    <span>
                        Módulos visitados
                    </span>

                </div>


                <div class="info-item">

                    <i
                        class="fa-solid fa-check-circle"
                        style="color:#2e7d32;"
                    ></i>

                    <span>
                        Número de accesos
                    </span>

                </div>


                <div class="info-item">

                    <i
                        class="fa-solid fa-check-circle"
                        style="color:#2e7d32;"
                    ></i>

                    <span>
                        Frecuencia de uso
                    </span>

                </div>


                <div class="info-item">

                    <i
                        class="fa-solid fa-check-circle"
                        style="color:#2e7d32;"
                    ></i>

                    <span>
                        Fecha y hora de acceso
                    </span>

                </div>


            </div>


        </section>


        <!-- =================================================
             AVISO
        ================================================= -->

        <div class="aviso-investigador">


            <i class="fa-solid fa-circle-info"></i>


            <p>

                Los datos mostrados en esta pantalla son
                datos reales registrados por AULAMOS

                <?php if ($id_prueba_activa): ?>

                    durante la prueba

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $prueba_activa_nombre
                        );

                        ?>

                    </strong>

                    en el periodo

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $periodo_prueba
                        );

                        ?>

                    </strong>.

                <?php else: ?>

                    .

                <?php endif; ?>


            </p>


        </div>


        <!-- =================================================
             ACCESIBILIDAD
        ================================================= -->

        <?php
            include '../Accesibilidad/accesibilidad.php';
        ?>


    </main>


</div>


<!-- =====================================================
     BOTÓN FLOTANTE DE ACCESIBILIDAD
===================================================== -->

<button
    class="btn-accesibilidad-flotante"
    id="btnAccesibilidadFlotante"
    onclick="toggleBarraAccesibilidad()"
    aria-label="Abrir opciones de accesibilidad"
>

    <i class="fa-solid fa-universal-access"></i>

</button>


<!-- =====================================================
     SCRIPTS
===================================================== -->

<script src="js/metricas_uso.js"></script>

<script src="../Accesibilidad/accesibilidad.js"></script>

<script src="../Accesibilidad/navegacionTeclado.js"></script>


</body>

</html>