<?php

session_start();


// =====================================================
// VALIDAR SESIÓN
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

$id_usuario_sesion =
    (int) (
        $_SESSION['usuario']['id_usuario']
        ?? 0
    );

echo '
<script>
    window.idUsuario = '
    . $id_usuario_sesion
    . ';
</script>
';


require_once '../Conexion/conexion.php';


$titulo_pagina =
    'Tiempos de actividades';

$descripcion_pagina =
    'Consulta cuánto tiempo tarda cada estudiante en completar actividades y juegos durante las pruebas de uso.';


// =====================================================
// DETECTAR PRUEBA ACTIVA
// =====================================================

$stmt =
    $conexion->prepare(
        "
            SELECT
                id_prueba,
                nombre,
                fecha_inicio,
                fecha_fin

            FROM pruebas_investigacion

            WHERE estado = 'Activa'

            ORDER BY
                fecha_inicio DESC,
                id_prueba DESC

            LIMIT 1
        "
    );


$stmt->execute();


$resultado =
    $stmt->get_result();


$prueba_activa =
    $resultado->fetch_assoc();


$stmt->close();


$id_prueba_activa =
    $prueba_activa['id_prueba']
    ?? null;


$prueba_activa_nombre =
    $prueba_activa['nombre']
    ?? 'Ninguna';


// =====================================================
// FUNCIÓN PARA FORMATEAR TIEMPO
// =====================================================

function formatearTiempo(
    $segundos
) {

    $segundos =
        max(
            0,
            (int) round(
                (float) $segundos
            )
        );


    if (
        $segundos === 0
    ) {

        return '0 s';
    }


    $horas =
        floor(
            $segundos / 3600
        );


    $minutos =
        floor(
            (
                $segundos % 3600
            )
            / 60
        );


    $segundosRestantes =
        $segundos % 60;


    $partes = [];


    if (
        $horas > 0
    ) {

        $partes[] =
            $horas . ' h';
    }


    if (
        $minutos > 0
    ) {

        $partes[] =
            $minutos . ' min';
    }


    if (
        $segundosRestantes > 0
        ||
        empty($partes)
    ) {

        $partes[] =
            $segundosRestantes . ' s';
    }


    return implode(
        ' ',
        $partes
    );
}


// =====================================================
// =====================================================
// ACTIVIDADES
// =====================================================
// =====================================================


// =====================================================
// TIEMPO PROMEDIO GENERAL
// =====================================================

if (
    $id_prueba_activa
) {

    $stmt =
        $conexion->prepare(
            "
                SELECT
                    AVG(
                        e.tiempo_realizacion
                    ) AS promedio

                FROM entregas e

                INNER JOIN actividad_estudiantes ae
                    ON e.id_actividad_estudiante =
                       ae.id_actividad_estudiante

                INNER JOIN participantes_prueba pp
                    ON ae.id_alumno =
                       pp.id_usuario

                WHERE
                    e.tiempo_realizacion
                    IS NOT NULL

                    AND pp.id_prueba = ?

                    AND pp.consentimiento =
                        TRUE
            "
        );


    $stmt->bind_param(
        "i",
        $id_prueba_activa
    );

} else {

    $stmt =
        $conexion->prepare(
            "
                SELECT
                    AVG(
                        tiempo_realizacion
                    ) AS promedio

                FROM entregas

                WHERE
                    tiempo_realizacion
                    IS NOT NULL
            "
        );
}


$stmt->execute();


$resultado =
    $stmt->get_result();


$fila_promedio =
    $resultado->fetch_assoc();


$promedio_segundos =
    round(
        $fila_promedio['promedio']
        ?? 0
    );


$stmt->close();


$tiempo_promedio_general =
    formatearTiempo(
        $promedio_segundos
    );


// =====================================================
// ACTIVIDAD MÁS RÁPIDA Y MÁS LENTA
// =====================================================

if (
    $id_prueba_activa
) {

    $stmt =
        $conexion->prepare(
            "
                SELECT
                    a.id_actividad,

                    a.titulo,

                    AVG(
                        e.tiempo_realizacion
                    ) AS promedio

                FROM entregas e

                INNER JOIN actividad_estudiantes ae
                    ON e.id_actividad_estudiante =
                       ae.id_actividad_estudiante

                INNER JOIN actividades a
                    ON ae.id_actividad =
                       a.id_actividad

                INNER JOIN participantes_prueba pp
                    ON ae.id_alumno =
                       pp.id_usuario

                WHERE
                    e.tiempo_realizacion
                    IS NOT NULL

                    AND pp.id_prueba = ?

                    AND pp.consentimiento =
                        TRUE

                GROUP BY
                    a.id_actividad,
                    a.titulo
            "
        );


    $stmt->bind_param(
        "i",
        $id_prueba_activa
    );

} else {

    $stmt =
        $conexion->prepare(
            "
                SELECT
                    a.id_actividad,

                    a.titulo,

                    AVG(
                        e.tiempo_realizacion
                    ) AS promedio

                FROM entregas e

                INNER JOIN actividad_estudiantes ae
                    ON e.id_actividad_estudiante =
                       ae.id_actividad_estudiante

                INNER JOIN actividades a
                    ON ae.id_actividad =
                       a.id_actividad

                WHERE
                    e.tiempo_realizacion
                    IS NOT NULL

                GROUP BY
                    a.id_actividad,
                    a.titulo
            "
        );
}


$stmt->execute();


$resultado =
    $stmt->get_result();


$actividades_tiempos =
    $resultado->fetch_all(
        MYSQLI_ASSOC
    );


$stmt->close();


$actividad_mas_rapida =
    'Sin datos';


$actividad_mas_lenta =
    'Sin datos';


$tiempo_rapido =
    PHP_INT_MAX;


$tiempo_lento =
    0;


foreach (
    $actividades_tiempos
    as $act
) {

    $promedio =
        (float) (
            $act['promedio']
            ?? 0
        );


    if (
        $promedio > 0
        &&
        $promedio < $tiempo_rapido
    ) {

        $tiempo_rapido =
            $promedio;


        $actividad_mas_rapida =
            $act['titulo'];
    }


    if (
        $promedio >
        $tiempo_lento
    ) {

        $tiempo_lento =
            $promedio;


        $actividad_mas_lenta =
            $act['titulo'];
    }
}


// =====================================================
// PROMEDIO POR ACTIVIDAD
// =====================================================

if (
    $id_prueba_activa
) {

    $stmt =
        $conexion->prepare(
            "
                SELECT
                    a.id_actividad,

                    a.titulo,

                    COUNT(
                        DISTINCT ae.id_alumno
                    ) AS estudiantes,

                    AVG(
                        e.tiempo_realizacion
                    ) AS promedio

                FROM entregas e

                INNER JOIN actividad_estudiantes ae
                    ON e.id_actividad_estudiante =
                       ae.id_actividad_estudiante

                INNER JOIN actividades a
                    ON ae.id_actividad =
                       a.id_actividad

                INNER JOIN participantes_prueba pp
                    ON ae.id_alumno =
                       pp.id_usuario

                WHERE
                    e.tiempo_realizacion
                    IS NOT NULL

                    AND pp.id_prueba = ?

                    AND pp.consentimiento =
                        TRUE

                GROUP BY
                    a.id_actividad,
                    a.titulo

                ORDER BY
                    promedio ASC

                LIMIT 5
            "
        );


    $stmt->bind_param(
        "i",
        $id_prueba_activa
    );

} else {

    $stmt =
        $conexion->prepare(
            "
                SELECT
                    a.id_actividad,

                    a.titulo,

                    COUNT(
                        DISTINCT ae.id_alumno
                    ) AS estudiantes,

                    AVG(
                        e.tiempo_realizacion
                    ) AS promedio

                FROM entregas e

                INNER JOIN actividad_estudiantes ae
                    ON e.id_actividad_estudiante =
                       ae.id_actividad_estudiante

                INNER JOIN actividades a
                    ON ae.id_actividad =
                       a.id_actividad

                WHERE
                    e.tiempo_realizacion
                    IS NOT NULL

                GROUP BY
                    a.id_actividad,
                    a.titulo

                ORDER BY
                    promedio ASC

                LIMIT 5
            "
        );
}


$stmt->execute();


$resultado =
    $stmt->get_result();


$resumen_actividades =
    $resultado->fetch_all(
        MYSQLI_ASSOC
    );


$stmt->close();


// =====================================================
// REGISTROS DE ACTIVIDADES POR ESTUDIANTE
// =====================================================

if (
    $id_prueba_activa
) {

    $stmt =
        $conexion->prepare(
            "
                SELECT
                    u.nombre,

                    u.apellido_paterno,

                    u.apellido_materno,

                    a.titulo
                        AS actividad,

                    e.fecha_entrega,

                    e.tiempo_realizacion

                FROM entregas e

                INNER JOIN actividad_estudiantes ae
                    ON e.id_actividad_estudiante =
                       ae.id_actividad_estudiante

                INNER JOIN usuarios u
                    ON ae.id_alumno =
                       u.id_usuario

                INNER JOIN actividades a
                    ON ae.id_actividad =
                       a.id_actividad

                INNER JOIN participantes_prueba pp
                    ON ae.id_alumno =
                       pp.id_usuario

                WHERE
                    e.tiempo_realizacion
                    IS NOT NULL

                    AND pp.id_prueba = ?

                    AND pp.consentimiento =
                        TRUE

                ORDER BY
                    e.fecha_entrega DESC

                LIMIT 10
            "
        );


    $stmt->bind_param(
        "i",
        $id_prueba_activa
    );

} else {

    $stmt =
        $conexion->prepare(
            "
                SELECT
                    u.nombre,

                    u.apellido_paterno,

                    u.apellido_materno,

                    a.titulo
                        AS actividad,

                    e.fecha_entrega,

                    e.tiempo_realizacion

                FROM entregas e

                INNER JOIN actividad_estudiantes ae
                    ON e.id_actividad_estudiante =
                       ae.id_actividad_estudiante

                INNER JOIN usuarios u
                    ON ae.id_alumno =
                       u.id_usuario

                INNER JOIN actividades a
                    ON ae.id_actividad =
                       a.id_actividad

                WHERE
                    e.tiempo_realizacion
                    IS NOT NULL

                ORDER BY
                    e.fecha_entrega DESC

                LIMIT 10
            "
        );
}


$stmt->execute();


$resultado =
    $stmt->get_result();


$registros_tiempo =
    $resultado->fetch_all(
        MYSQLI_ASSOC
    );


$stmt->close();


// =====================================================
// =====================================================
// JUEGOS - CONECTA Y APRENDE
// =====================================================
// =====================================================


// =====================================================
// VALORES POR DEFECTO
// =====================================================

$tiempo_promedio_juegos =
    '0 s';


$total_intentos_juegos =
    0;


$juegos_completados =
    0;


$total_aciertos_juegos =
    0;


$total_errores_juegos =
    0;


$registros_juegos =
    [];


// =====================================================
// RESUMEN GENERAL DE JUEGOS
// =====================================================

if (
    $id_prueba_activa
) {

    $stmt =
        $conexion->prepare(
            "
                SELECT
                    COUNT(
                        ci.id_intento
                    ) AS total_intentos,


                    COALESCE(
                        ROUND(
                            AVG(
                                CASE

                                    WHEN
                                        ci.fecha_fin
                                        IS NOT NULL

                                    THEN
                                        ci.tiempo_segundos

                                    ELSE NULL

                                END
                            )
                        ),
                        0
                    ) AS tiempo_promedio_seg,


                    COUNT(
                        DISTINCT CASE

                            WHEN
                                ci.fecha_fin
                                IS NOT NULL

                            THEN
                                ca.id_asignacion

                        END
                    ) AS juegos_completados,


                    COALESCE(
                        SUM(
                            ci.parejas_correctas
                        ),
                        0
                    ) AS total_aciertos,


                    COALESCE(
                        SUM(
                            ci.errores
                        ),
                        0
                    ) AS total_errores


                FROM conecta_intentos ci


                INNER JOIN conecta_asignaciones ca
                    ON ca.id_asignacion =
                       ci.id_asignacion


                INNER JOIN participantes_prueba pp
                    ON pp.id_usuario =
                       ca.id_alumno

                    AND pp.id_prueba = ?

                    AND pp.consentimiento =
                        TRUE
            "
        );


    $stmt->bind_param(
        "i",
        $id_prueba_activa
    );

} else {

    $stmt =
        $conexion->prepare(
            "
                SELECT
                    COUNT(
                        ci.id_intento
                    ) AS total_intentos,


                    COALESCE(
                        ROUND(
                            AVG(
                                CASE

                                    WHEN
                                        ci.fecha_fin
                                        IS NOT NULL

                                    THEN
                                        ci.tiempo_segundos

                                    ELSE NULL

                                END
                            )
                        ),
                        0
                    ) AS tiempo_promedio_seg,


                    COUNT(
                        DISTINCT CASE

                            WHEN
                                ci.fecha_fin
                                IS NOT NULL

                            THEN
                                ca.id_asignacion

                        END
                    ) AS juegos_completados,


                    COALESCE(
                        SUM(
                            ci.parejas_correctas
                        ),
                        0
                    ) AS total_aciertos,


                    COALESCE(
                        SUM(
                            ci.errores
                        ),
                        0
                    ) AS total_errores


                FROM conecta_intentos ci


                INNER JOIN conecta_asignaciones ca
                    ON ca.id_asignacion =
                       ci.id_asignacion
            "
        );
}


$stmt->execute();


$resultado =
    $stmt->get_result();


$resumen_juegos =
    $resultado->fetch_assoc()
    ?? [];


$stmt->close();


$total_intentos_juegos =
    (int) (
        $resumen_juegos['total_intentos']
        ?? 0
    );


$juegos_completados =
    (int) (
        $resumen_juegos['juegos_completados']
        ?? 0
    );


$total_aciertos_juegos =
    (int) (
        $resumen_juegos['total_aciertos']
        ?? 0
    );


$total_errores_juegos =
    (int) (
        $resumen_juegos['total_errores']
        ?? 0
    );


$tiempo_promedio_juegos =
    formatearTiempo(
        $resumen_juegos['tiempo_promedio_seg']
        ?? 0
    );


// =====================================================
// JUEGOS POR ESTUDIANTE
// =====================================================

if (
    $id_prueba_activa
) {

    $stmt =
        $conexion->prepare(
            "
                SELECT
                    ci.id_intento,

                    ci.numero_intento,

                    ci.puntuacion,

                    ci.parejas_correctas,

                    ci.errores,

                    ci.movimientos,

                    ci.tiempo_segundos,

                    ci.porcentaje,

                    ci.fecha_inicio,

                    ci.fecha_fin,


                    ca.id_asignacion,

                    ca.estado
                        AS estado_asignacion,


                    cj.id_juego,

                    cj.titulo
                        AS juego,

                    cj.tema,

                    cj.modo,


                    u.id_usuario,

                    u.nombre,

                    u.apellido_paterno,

                    u.apellido_materno


                FROM conecta_intentos ci


                INNER JOIN conecta_asignaciones ca
                    ON ca.id_asignacion =
                       ci.id_asignacion


                INNER JOIN conecta_juegos cj
                    ON cj.id_juego =
                       ca.id_juego


                INNER JOIN usuarios u
                    ON u.id_usuario =
                       ca.id_alumno


                INNER JOIN participantes_prueba pp
                    ON pp.id_usuario =
                       ca.id_alumno

                    AND pp.id_prueba = ?

                    AND pp.consentimiento =
                        TRUE


                ORDER BY
                    COALESCE(
                        ci.fecha_fin,
                        ci.fecha_inicio
                    ) DESC,

                    ci.id_intento DESC


                LIMIT 50
            "
        );


    $stmt->bind_param(
        "i",
        $id_prueba_activa
    );

} else {

    $stmt =
        $conexion->prepare(
            "
                SELECT
                    ci.id_intento,

                    ci.numero_intento,

                    ci.puntuacion,

                    ci.parejas_correctas,

                    ci.errores,

                    ci.movimientos,

                    ci.tiempo_segundos,

                    ci.porcentaje,

                    ci.fecha_inicio,

                    ci.fecha_fin,


                    ca.id_asignacion,

                    ca.estado
                        AS estado_asignacion,


                    cj.id_juego,

                    cj.titulo
                        AS juego,

                    cj.tema,

                    cj.modo,


                    u.id_usuario,

                    u.nombre,

                    u.apellido_paterno,

                    u.apellido_materno


                FROM conecta_intentos ci


                INNER JOIN conecta_asignaciones ca
                    ON ca.id_asignacion =
                       ci.id_asignacion


                INNER JOIN conecta_juegos cj
                    ON cj.id_juego =
                       ca.id_juego


                INNER JOIN usuarios u
                    ON u.id_usuario =
                       ca.id_alumno


                ORDER BY
                    COALESCE(
                        ci.fecha_fin,
                        ci.fecha_inicio
                    ) DESC,

                    ci.id_intento DESC


                LIMIT 50
            "
        );
}


$stmt->execute();


$resultado =
    $stmt->get_result();


$registros_juegos =
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
        Tiempos de actividades - Investigador
    </title>


    <link
        rel="stylesheet"
        href="styles/tiempos_actividades.css"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >


    <link
        rel="stylesheet"
        href="../Accesibilidad/accesibilidad.css"
    >

</head>


<body>

<div class="dashboard-container">

    <?php
        include 'includes/sidebar.php';
    ?>


    <main class="main-content">

        <?php
            include 'includes/header.php';
        ?>


        <!-- ==========================================
             PRUEBA ACTIVA
        =========================================== -->

        <?php if ($id_prueba_activa): ?>

            <div
                style="
                    background: #f3e8fd;
                    border: 1px solid #7C3AED;
                    border-radius: 12px;
                    padding: 12px 20px;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                "
            >

                <i
                    class="fa-solid fa-flask"
                    style="
                        color: #7C3AED;
                        font-size: 18px;
                    "
                ></i>


                <span
                    style="
                        color: #5a189a;
                        font-weight: 600;
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
                            font-weight: 400;
                            color: #7C3AED;
                        "
                    >

                        — Los datos mostrados corresponden SOLO
                        a los participantes de esta prueba.

                    </span>

                </span>

            </div>


        <?php else: ?>

            <div
                style="
                    background: #f1f5f9;
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    padding: 12px 20px;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                "
            >

                <i
                    class="fa-solid fa-circle-info"
                    style="
                        color: #64748b;
                        font-size: 18px;
                    "
                ></i>


                <span
                    style="
                        color: #475569;
                        font-weight: 500;
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


        <!-- ==========================================
             PERIODO
        =========================================== -->

        <div class="periodo-selector">

            <div class="periodo-info">

                <i class="fa-solid fa-calendar"></i>


                <div>

                    <span class="periodo-etiqueta">
                        Periodo analizado
                    </span>


                    <span class="periodo-valor">

                        <?php if ($id_prueba_activa): ?>

                            <?php
                                echo date(
                                    'd M Y',
                                    strtotime(
                                        $prueba_activa['fecha_inicio']
                                    )
                                );
                            ?>

                            -

                            <?php

                                if (
                                    !empty(
                                        $prueba_activa['fecha_fin']
                                    )
                                ) {

                                    echo date(
                                        'd M Y',
                                        strtotime(
                                            $prueba_activa['fecha_fin']
                                        )
                                    );

                                } else {

                                    echo 'Actualidad';
                                }

                            ?>

                        <?php else: ?>

                            Todos los registros

                        <?php endif; ?>

                    </span>

                </div>

            </div>


            <button
                class="btn-periodo"
                type="button"
                aria-label="Periodo analizado"
            >

                <i class="fa-solid fa-calendar-days"></i>

            </button>

        </div>


        <!-- ==========================================
             ACTIVIDADES
        =========================================== -->

        <section class="tiempo-promedio">

            <div class="tarjeta-promedio">

                <div class="icono-promedio">

                    <i class="fa-solid fa-clock"></i>

                </div>


                <div class="promedio-contenido">

                    <span class="promedio-etiqueta">
                        Tiempo promedio general de actividades
                    </span>


                    <span class="promedio-valor">

                        <?php
                            echo htmlspecialchars(
                                $tiempo_promedio_general
                            );
                        ?>

                    </span>

                </div>

            </div>

        </section>


        <!-- ==========================================
             ACTIVIDAD RÁPIDA / LENTA
        =========================================== -->

        <section class="destacados-tiempos">

            <div class="grid-destacados">

                <div class="tarjeta-destacada">

                    <i class="fa-solid fa-bolt"></i>


                    <span class="destacado-etiqueta">
                        Menor tiempo
                    </span>


                    <span class="destacado-valor">

                        <?php
                            echo htmlspecialchars(
                                $actividad_mas_rapida
                            );
                        ?>

                    </span>

                </div>


                <div class="tarjeta-destacada">

                    <i class="fa-solid fa-hourglass"></i>


                    <span class="destacado-etiqueta">
                        Mayor tiempo
                    </span>


                    <span class="destacado-valor">

                        <?php
                            echo htmlspecialchars(
                                $actividad_mas_lenta
                            );
                        ?>

                    </span>

                </div>

            </div>

        </section>


        <!-- ==========================================
             PROMEDIO POR ACTIVIDAD
        =========================================== -->

        <section class="promedio-actividades">

            <h3>

                <i class="fa-solid fa-chart-simple"></i>

                Promedio por actividad

            </h3>


            <div class="tarjeta-actividades">

                <?php if (
                    empty(
                        $resumen_actividades
                    )
                ): ?>

                    <p
                        style="
                            color: #94a3b8;
                            text-align: center;
                            padding: 20px;
                        "
                    >

                        No hay datos disponibles.

                    </p>


                <?php else: ?>


                    <?php foreach (
                        $resumen_actividades
                        as $actividad
                    ): ?>


                        <?php

                            $promedio =
                                formatearTiempo(
                                    $actividad['promedio']
                                );

                        ?>


                        <div class="actividad-resumen">

                            <div class="actividad-resumen-info">

                                <i class="fa-solid fa-file-lines"></i>


                                <div>

                                    <span class="actividad-resumen-nombre">

                                        <?php
                                            echo htmlspecialchars(
                                                $actividad['titulo']
                                            );
                                        ?>

                                    </span>


                                    <span class="actividad-resumen-estudiantes">

                                        <?php
                                            echo (int)
                                                $actividad['estudiantes'];
                                        ?>

                                        estudiantes

                                    </span>

                                </div>

                            </div>


                            <div class="actividad-resumen-tiempo">

                                <span class="actividad-resumen-promedio">

                                    <?php
                                        echo htmlspecialchars(
                                            $promedio
                                        );
                                    ?>

                                </span>


                                <span class="actividad-resumen-label">
                                    promedio
                                </span>

                            </div>

                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>

            </div>

        </section>


        <!-- ==========================================
             ACTIVIDADES POR ESTUDIANTE
        =========================================== -->

        <section class="registros-estudiantes">

            <h3>

                <i class="fa-regular fa-clock"></i>

                Registros de actividades por estudiante

            </h3>


            <?php if (
                empty(
                    $registros_tiempo
                )
            ): ?>

                <p
                    style="
                        color: #94a3b8;
                        text-align: center;
                        padding: 20px;
                    "
                >

                    No hay registros disponibles.

                </p>


            <?php else: ?>


                <?php foreach (
                    $registros_tiempo
                    as $registro
                ): ?>


                    <?php

                        $tiempo =
                            formatearTiempo(
                                $registro['tiempo_realizacion']
                            );


                        $nombre_completo =
                            trim(
                                (
                                    $registro['nombre']
                                    ?? ''
                                )
                                . ' '
                                .
                                (
                                    $registro['apellido_paterno']
                                    ?? ''
                                )
                                . ' '
                                .
                                (
                                    $registro['apellido_materno']
                                    ?? ''
                                )
                            );

                    ?>


                    <div class="tarjeta-registro">

                        <div class="registro-encabezado">

                            <div class="registro-usuario">

                                <i class="fa-solid fa-user"></i>


                                <div>

                                    <span class="registro-nombre">

                                        <?php
                                            echo htmlspecialchars(
                                                $nombre_completo
                                            );
                                        ?>

                                    </span>


                                    <span class="registro-actividad">

                                        <?php
                                            echo htmlspecialchars(
                                                $registro['actividad']
                                            );
                                        ?>

                                    </span>

                                </div>

                            </div>


                            <div class="registro-badge">

                                <i class="fa-solid fa-clock"></i>


                                <span>

                                    <?php
                                        echo htmlspecialchars(
                                            $tiempo
                                        );
                                    ?>

                                </span>

                            </div>

                        </div>


                        <div class="registro-fecha">

                            <i class="fa-regular fa-calendar"></i>


                            <span>

                                <?php

                                    if (
                                        !empty(
                                            $registro['fecha_entrega']
                                        )
                                    ) {

                                        echo date(
                                            'd M Y, h:i a',
                                            strtotime(
                                                $registro['fecha_entrega']
                                            )
                                        );

                                    } else {

                                        echo 'Fecha no registrada';
                                    }

                                ?>

                            </span>

                        </div>

                    </div>


                <?php endforeach; ?>


            <?php endif; ?>

        </section>


        <!-- ==========================================
             ==========================================
             JUEGOS
             ==========================================
        =========================================== -->


        <!-- ==========================================
             RESUMEN DE JUEGOS
        =========================================== -->

        <section class="juegos-resumen">

            <div class="juegos-titulo">

                <div>

                    <span class="juegos-etiqueta">
                        GAMIFICACIÓN
                    </span>


                    <h3>

                        <i class="fa-solid fa-gamepad"></i>

                        Resumen de juegos

                    </h3>

                </div>


                <div class="juegos-icono-principal">

                    <i class="fa-solid fa-puzzle-piece"></i>

                </div>

            </div>


            <div class="juegos-metricas-grid">


                <!-- TIEMPO PROMEDIO -->

                <div class="juego-metrica">

                    <div class="juego-metrica-icono azul">

                        <i class="fa-solid fa-stopwatch"></i>

                    </div>


                    <span class="juego-metrica-etiqueta">
                        Tiempo promedio
                    </span>


                    <strong class="juego-metrica-valor">

                        <?php
                            echo htmlspecialchars(
                                $tiempo_promedio_juegos
                            );
                        ?>

                    </strong>

                </div>


                <!-- JUEGOS COMPLETADOS -->

                <div class="juego-metrica">

                    <div class="juego-metrica-icono morado">

                        <i class="fa-solid fa-gamepad"></i>

                    </div>


                    <span class="juego-metrica-etiqueta">
                        Juegos completados
                    </span>


                    <strong class="juego-metrica-valor">

                        <?php
                            echo $juegos_completados;
                        ?>

                    </strong>

                </div>


                <!-- ACIERTOS -->

                <div class="juego-metrica">

                    <div class="juego-metrica-icono verde">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>


                    <span class="juego-metrica-etiqueta">
                        Aciertos
                    </span>


                    <strong class="juego-metrica-valor">

                        <?php
                            echo $total_aciertos_juegos;
                        ?>

                    </strong>

                </div>


                <!-- ERRORES -->

                <div class="juego-metrica">

                    <div class="juego-metrica-icono rojo">

                        <i class="fa-solid fa-circle-xmark"></i>

                    </div>


                    <span class="juego-metrica-etiqueta">
                        Errores
                    </span>


                    <strong class="juego-metrica-valor">

                        <?php
                            echo $total_errores_juegos;
                        ?>

                    </strong>

                </div>

            </div>


            <!-- TOTAL DE INTENTOS -->

            <div class="juegos-intentos">

                <div class="juegos-intentos-icono">

                    <i class="fa-solid fa-rotate"></i>

                </div>


                <div>

                    <span>
                        Total de intentos registrados
                    </span>


                    <strong>

                        <?php
                            echo $total_intentos_juegos;
                        ?>

                    </strong>

                </div>

            </div>

        </section>


        <!-- ==========================================
             JUEGOS POR ESTUDIANTE
        =========================================== -->

        <section class="juegos-estudiantes">

            <h3>

                <i class="fa-solid fa-users"></i>

                Juegos por estudiante

            </h3>


            <?php if (
                empty(
                    $registros_juegos
                )
            ): ?>

                <div class="juegos-sin-datos">

                    <i class="fa-solid fa-gamepad"></i>


                    <p>
                        Aún no existen juegos realizados por los
                        participantes de esta prueba.
                    </p>

                </div>


            <?php else: ?>


                <?php foreach (
                    $registros_juegos
                    as $juego
                ): ?>


                    <?php

                        $nombre_jugador =
                            trim(
                                (
                                    $juego['nombre']
                                    ?? ''
                                )
                                . ' '
                                .
                                (
                                    $juego['apellido_paterno']
                                    ?? ''
                                )
                                . ' '
                                .
                                (
                                    $juego['apellido_materno']
                                    ?? ''
                                )
                            );


                        $tiempo_juego =
                            formatearTiempo(
                                $juego['tiempo_segundos']
                                ?? 0
                            );


                        $completado =
                            !empty(
                                $juego['fecha_fin']
                            );


                        $estado_juego =
                            $completado
                                ? 'Completado'
                                : 'En progreso';


                        $porcentaje =
                            round(
                                (float) (
                                    $juego['porcentaje']
                                    ?? 0
                                )
                            );


                        $puntuacion =
                            (int) (
                                $juego['puntuacion']
                                ?? 0
                            );


                        $aciertos =
                            (int) (
                                $juego['parejas_correctas']
                                ?? 0
                            );


                        $errores =
                            (int) (
                                $juego['errores']
                                ?? 0
                            );


                        $movimientos =
                            (int) (
                                $juego['movimientos']
                                ?? 0
                            );


                        $numero_intento =
                            (int) (
                                $juego['numero_intento']
                                ?? 1
                            );

                    ?>


                    <article class="tarjeta-juego-estudiante">


                        <!-- CABECERA -->

                        <div class="juego-estudiante-cabecera">

                            <div class="juego-estudiante-usuario">

                                <div class="juego-avatar">

                                    <i class="fa-solid fa-user"></i>

                                </div>


                                <div>

                                    <span class="juego-estudiante-nombre">

                                        <?php
                                            echo htmlspecialchars(
                                                $nombre_jugador
                                            );
                                        ?>

                                    </span>


                                    <span class="juego-estudiante-juego">

                                        <i class="fa-solid fa-gamepad"></i>

                                        <?php
                                            echo htmlspecialchars(
                                                $juego['juego']
                                                ?? 'Juego'
                                            );
                                        ?>

                                    </span>


                                    <span class="juego-estudiante-tema">

                                        Tema:

                                        <?php
                                            echo htmlspecialchars(
                                                $juego['tema']
                                                ?? 'Sin tema'
                                            );
                                        ?>

                                        · Intento

                                        <?php
                                            echo $numero_intento;
                                        ?>

                                    </span>

                                </div>

                            </div>


                            <span
                                class="
                                    estado-juego
                                    <?php
                                        echo $completado
                                            ? 'completado'
                                            : 'progreso';
                                    ?>
                                "
                            >

                                <i
                                    class="
                                        fa-solid
                                        <?php
                                            echo $completado
                                                ? 'fa-circle-check'
                                                : 'fa-circle-play';
                                        ?>
                                    "
                                ></i>


                                <?php
                                    echo $estado_juego;
                                ?>

                            </span>

                        </div>


                        <!-- MÉTRICAS -->

                        <div class="juego-detalles-grid">


                            <!-- TIEMPO -->

                            <div class="juego-detalle">

                                <i class="fa-regular fa-clock tiempo"></i>

                                <span>
                                    Tiempo
                                </span>

                                <strong>

                                    <?php
                                        echo htmlspecialchars(
                                            $tiempo_juego
                                        );
                                    ?>

                                </strong>

                            </div>


                            <!-- ACIERTOS -->

                            <div class="juego-detalle">

                                <i class="fa-solid fa-check acierto"></i>

                                <span>
                                    Aciertos
                                </span>

                                <strong>

                                    <?php
                                        echo $aciertos;
                                    ?>

                                </strong>

                            </div>


                            <!-- ERRORES -->

                            <div class="juego-detalle">

                                <i class="fa-solid fa-xmark error"></i>

                                <span>
                                    Errores
                                </span>

                                <strong>

                                    <?php
                                        echo $errores;
                                    ?>

                                </strong>

                            </div>


                            <!-- MOVIMIENTOS -->

                            <div class="juego-detalle">

                                <i class="fa-solid fa-arrow-right-arrow-left movimiento"></i>

                                <span>
                                    Movimientos
                                </span>

                                <strong>

                                    <?php
                                        echo $movimientos;
                                    ?>

                                </strong>

                            </div>

                        </div>


                        <!-- RESULTADO -->

                        <div class="juego-resultado">


                            <div>

                                <i class="fa-solid fa-star"></i>

                                <span>
                                    Puntuación
                                </span>

                                <strong>

                                    <?php
                                        echo $puntuacion;
                                    ?>

                                </strong>

                            </div>


                            <div>

                                <i class="fa-solid fa-chart-pie"></i>

                                <span>
                                    Resultado
                                </span>

                                <strong>

                                    <?php
                                        echo $porcentaje;
                                    ?>%

                                </strong>

                            </div>


                            <div>

                                <i class="fa-solid fa-layer-group"></i>

                                <span>
                                    Modo
                                </span>

                                <strong>

                                    <?php
                                        echo htmlspecialchars(
                                            $juego['modo']
                                            ?? 'Sin definir'
                                        );
                                    ?>

                                </strong>

                            </div>

                        </div>


                        <!-- FECHAS -->

                        <div class="juego-fechas">


                            <div>

                                <i class="fa-solid fa-play"></i>

                                <span>

                                    <strong>
                                        Inicio:
                                    </strong>

                                    <?php

                                        if (
                                            !empty(
                                                $juego['fecha_inicio']
                                            )
                                        ) {

                                            echo date(
                                                'd/m/Y H:i',
                                                strtotime(
                                                    $juego['fecha_inicio']
                                                )
                                            );

                                        } else {

                                            echo 'Sin registrar';
                                        }

                                    ?>

                                </span>

                            </div>


                            <div>

                                <i class="fa-solid fa-flag-checkered"></i>

                                <span>

                                    <strong>
                                        Finalización:
                                    </strong>

                                    <?php

                                        if (
                                            !empty(
                                                $juego['fecha_fin']
                                            )
                                        ) {

                                            echo date(
                                                'd/m/Y H:i',
                                                strtotime(
                                                    $juego['fecha_fin']
                                                )
                                            );

                                        } else {

                                            echo 'Sin registrar';
                                        }

                                    ?>

                                </span>

                            </div>

                        </div>

                    </article>


                <?php endforeach; ?>


            <?php endif; ?>

        </section>


        <!-- ==========================================
             INFORMACIÓN REGISTRADA
        =========================================== -->

        <section class="info-registrada">

            <h3>

                <i class="fa-solid fa-list-check"></i>

                Información registrada

            </h3>


            <div class="info-grid">

                <div class="info-item">

                    <i class="fa-solid fa-check-circle"></i>

                    <span>
                        Fecha y hora de inicio
                    </span>

                </div>


                <div class="info-item">

                    <i class="fa-solid fa-check-circle"></i>

                    <span>
                        Fecha y hora de finalización
                    </span>

                </div>


                <div class="info-item">

                    <i class="fa-solid fa-check-circle"></i>

                    <span>
                        Tiempo total empleado
                    </span>

                </div>


                <div class="info-item">

                    <i class="fa-solid fa-check-circle"></i>

                    <span>
                        Actividad o juego realizado
                    </span>

                </div>


                <div class="info-item">

                    <i class="fa-solid fa-check-circle"></i>

                    <span>
                        Estudiante correspondiente
                    </span>

                </div>


                <div class="info-item">

                    <i class="fa-solid fa-check-circle"></i>

                    <span>
                        Aciertos y errores en juegos
                    </span>

                </div>


                <div class="info-item">

                    <i class="fa-solid fa-check-circle"></i>

                    <span>
                        Puntuación y porcentaje
                    </span>

                </div>


                <div class="info-item">

                    <i class="fa-solid fa-check-circle"></i>

                    <span>
                        Número de intento
                    </span>

                </div>

            </div>

        </section>


        <!-- ==========================================
             AVISO
        =========================================== -->

        <div class="aviso-investigador">

            <i class="fa-solid fa-circle-info"></i>


            <p>

                Los tiempos de actividades y juegos se calculan
                automáticamente con los registros generados por
                cada estudiante. En los juegos también se muestran
                los aciertos, errores, movimientos, puntuación y
                porcentaje obtenido.

            </p>

        </div>


        <?php
            include '../Accesibilidad/accesibilidad.php';
        ?>

    </main>

</div>


<!-- ==========================================
     BOTÓN ACCESIBILIDAD
=========================================== -->

<button
    class="btn-accesibilidad-flotante"
    id="btnAccesibilidadFlotante"
    onclick="toggleBarraAccesibilidad()"
    aria-label="Abrir opciones de accesibilidad"
>

    <i class="fa-solid fa-universal-access"></i>

</button>


<script src="js/tiempos_actividades.js"></script>

<script src="../Accesibilidad/accesibilidad.js"></script>


</body>

</html>