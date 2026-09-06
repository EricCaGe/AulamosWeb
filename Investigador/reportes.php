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
// ID DEL INVESTIGADOR
// =====================================================

$idUsuario =
    (int) $_SESSION['usuario']['id_usuario'];


// =====================================================
// CONEXIÓN
// =====================================================

require_once '../Conexion/conexion.php';


$titulo_pagina =
    'Reportes de investigación';


$descripcion_pagina =
    'Consulta y exporta los resultados completos de la prueba de investigación.';


// =====================================================
// FUNCIONES AUXILIARES
// =====================================================

function porcentaje(
    $cantidad,
    $total
) {

    $cantidad =
        (float) $cantidad;

    $total =
        (float) $total;


    if (
        $total <= 0
    ) {

        return 0;
    }


    return round(
        (
            $cantidad /
            $total
        ) * 100,
        2
    );
}


function formatearTiempo(
    $segundos
) {

    $segundos =
        (int) round(
            (float) $segundos
        );


    if (
        $segundos <= 0
    ) {

        return '0 min 0 s';
    }


    $horas =
        floor(
            $segundos /
            3600
        );


    $minutos =
        floor(
            (
                $segundos %
                3600
            ) /
            60
        );


    $segundosRestantes =
        $segundos %
        60;


    if (
        $horas > 0
    ) {

        return
            $horas .
            ' h ' .
            $minutos .
            ' min ' .
            $segundosRestantes .
            ' s';
    }


    return
        $minutos .
        ' min ' .
        $segundosRestantes .
        ' s';
}


function formatearFecha(
    $fecha
) {

    if (
        empty($fecha)
    ) {

        return 'Sin fecha';
    }


    $timestamp =
        strtotime(
            $fecha
        );


    if (
        !$timestamp
    ) {

        return 'Sin fecha';
    }


    return date(
        'd/m/Y',
        $timestamp
    );
}


function tablaExiste(
    $conexion,
    $tabla
) {

    $tabla =
        $conexion->real_escape_string(
            $tabla
        );


    $resultado =
        $conexion->query(
            "SHOW TABLES LIKE '$tabla'"
        );


    return
        $resultado &&
        $resultado->num_rows > 0;
}


// =====================================================
// PRUEBA ACTIVA
// =====================================================

$prueba_activa =
    null;


$resultado =
    $conexion->query("
        SELECT
            id_prueba,
            nombre,
            descripcion,
            hipotesis,
            objetivo,
            version_wcag,
            fecha_inicio,
            fecha_fin,
            estado

        FROM pruebas_investigacion

        WHERE estado = 'Activa'

        ORDER BY
            fecha_inicio DESC

        LIMIT 1
    ");


if (
    $resultado
) {

    $prueba_activa =
        $resultado->fetch_assoc();
}


// =====================================================
// DATOS DE LA PRUEBA
// =====================================================

$id_prueba_activa =
    $prueba_activa
        ? (int) $prueba_activa['id_prueba']
        : null;


$prueba_activa_nombre =
    $prueba_activa['nombre']
    ??
    'Sin prueba activa';


$version_wcag =
    $prueba_activa['version_wcag']
    ??
    'Sin especificar';


$estado_prueba =
    $prueba_activa['estado']
    ??
    'Sin estado';


$hipotesis_prueba =
    $prueba_activa['hipotesis']
    ??
    'Sin hipótesis registrada';


$objetivo_prueba =
    $prueba_activa['objetivo']
    ??
    'Sin objetivo registrado';


$descripcion_prueba =
    $prueba_activa['descripcion']
    ??
    'Sin descripción registrada';


$fecha_inicio_prueba =
    $prueba_activa['fecha_inicio']
    ??
    null;


$fecha_fin_prueba =
    $prueba_activa['fecha_fin']
    ??
    null;


// =====================================================
// PERIODO
// =====================================================

if (
    $fecha_inicio_prueba
) {

    $periodo_reporte =
        formatearFecha(
            $fecha_inicio_prueba
        )
        .
        ' - '
        .
        (
            $fecha_fin_prueba
                ? formatearFecha(
                    $fecha_fin_prueba
                )
                : 'En curso'
        );

} else {

    $periodo_reporte =
        'Sin periodo definido';
}


// =====================================================
// SUBCONSULTA DE PARTICIPANTES
//
// IMPORTANTE:
// Si no existe prueba activa, NO tomamos todos los
// estudiantes del sistema.
// =====================================================

if (
    $id_prueba_activa
) {

    $subParticipantes =
        "
        SELECT id_usuario
        FROM participantes_prueba
        WHERE id_prueba =
        $id_prueba_activa
        ";

} else {

    $subParticipantes =
        "
        SELECT id_usuario
        FROM participantes_prueba
        WHERE 1 = 0
        ";
}


// =====================================================
// FILTROS DE FECHA
// =====================================================

$fechaInicioSQL =
    $fecha_inicio_prueba
        ? $conexion->real_escape_string(
            $fecha_inicio_prueba
        )
        : null;


$fechaFinSQL =
    $fecha_fin_prueba
        ? $conexion->real_escape_string(
            $fecha_fin_prueba
        )
        : date(
            'Y-m-d'
        );


$filtroFechaEntrega =
    '';


$filtroFechaChatbot =
    '';


if (
    $fechaInicioSQL
) {

    $filtroFechaEntrega =
        "
        AND DATE(e.fecha_entrega)
        BETWEEN
        '$fechaInicioSQL'
        AND
        '$fechaFinSQL'
        ";


    $filtroFechaChatbot =
        "
        AND DATE(m.fecha_mensaje)
        BETWEEN
        '$fechaInicioSQL'
        AND
        '$fechaFinSQL'
        ";
}


// =====================================================
// =====================================================
// 1. PARTICIPANTES DE LA PRUEBA
// =====================================================
// =====================================================

$total_estudiantes =
    0;


$total_consentimientos =
    0;


if (
    $id_prueba_activa
) {

    $resultado =
        $conexion->query("
            SELECT
                COUNT(
                    DISTINCT pp.id_usuario
                ) AS total,

                COUNT(
                    DISTINCT
                    CASE
                        WHEN pp.consentimiento = 1
                        THEN pp.id_usuario
                    END
                ) AS consentimientos

            FROM participantes_prueba pp

            INNER JOIN usuarios u
                ON u.id_usuario =
                   pp.id_usuario

            WHERE
                pp.id_prueba =
                $id_prueba_activa

                AND u.estado =
                'Activo'
        ");


    if (
        $resultado
    ) {

        $fila =
            $resultado->fetch_assoc();


        $total_estudiantes =
            (int) (
                $fila['total']
                ??
                0
            );


        $total_consentimientos =
            (int) (
                $fila['consentimientos']
                ??
                0
            );
    }
}


$porcentaje_consentimiento =
    porcentaje(
        $total_consentimientos,
        $total_estudiantes
    );


// =====================================================
// =====================================================
// 2. USO DE LA PLATAFORMA
// =====================================================
// =====================================================

$total_accesos =
    0;


$usuarios_con_actividad =
    0;


$total_eventos_plataforma =
    0;


$promedio_accesos =
    0;


$modulos_visitados =
    [];


if (
    $id_prueba_activa
) {

    // =================================================
    // ACCESOS
    // =================================================

    $resultado =
        $conexion->query("
            SELECT
                COUNT(*) AS total

            FROM eventos_investigacion

            WHERE
                id_prueba =
                $id_prueba_activa

                AND tipo_evento =
                'InicioSesion'
        ");


    if (
        $resultado
    ) {

        $total_accesos =
            (int) (
                $resultado
                    ->fetch_assoc()['total']
                ??
                0
            );
    }


    // =================================================
    // PARTICIPANTES CON ACTIVIDAD
    // =================================================

    $resultado =
        $conexion->query("
            SELECT
                COUNT(
                    DISTINCT id_usuario
                ) AS total

            FROM eventos_investigacion

            WHERE
                id_prueba =
                $id_prueba_activa
        ");


    if (
        $resultado
    ) {

        $usuarios_con_actividad =
            (int) (
                $resultado
                    ->fetch_assoc()['total']
                ??
                0
            );
    }


    // =================================================
    // TOTAL DE EVENTOS
    // =================================================

    $resultado =
        $conexion->query("
            SELECT
                COUNT(*) AS total

            FROM eventos_investigacion

            WHERE
                id_prueba =
                $id_prueba_activa
        ");


    if (
        $resultado
    ) {

        $total_eventos_plataforma =
            (int) (
                $resultado
                    ->fetch_assoc()['total']
                ??
                0
            );
    }


    // =================================================
    // MÓDULOS
    // =================================================

    $resultado =
        $conexion->query("
            SELECT
                modulo,

                COUNT(*) AS visitas,

                COUNT(
                    DISTINCT id_usuario
                ) AS usuarios

            FROM eventos_investigacion

            WHERE
                id_prueba =
                $id_prueba_activa

                AND modulo IS NOT NULL

                AND modulo <> ''

            GROUP BY modulo

            ORDER BY visitas DESC
        ");


    if (
        $resultado
    ) {

        $modulos_visitados =
            $resultado->fetch_all(
                MYSQLI_ASSOC
            );
    }
}


$promedio_accesos =
    $total_estudiantes > 0
        ? round(
            $total_accesos /
            $total_estudiantes,
            2
        )
        : 0;


$porcentaje_uso_plataforma =
    porcentaje(
        $usuarios_con_actividad,
        $total_estudiantes
    );


$total_visitas_modulos =
    0;


foreach (
    $modulos_visitados
    as $modulo
) {

    $total_visitas_modulos +=
        (int) $modulo['visitas'];
}


// =====================================================
// =====================================================
// 3. TIEMPOS DE ACTIVIDADES
// =====================================================
// =====================================================

$promedio_segundos =
    0;


$tiempo_promedio =
    '0 min 0 s';


$actividades_tiempos =
    [];


if (
    $id_prueba_activa &&
    $total_estudiantes > 0
) {

    // =================================================
    // PROMEDIO GENERAL
    // =================================================

    $sql =
        "
        SELECT
            AVG(
                e.tiempo_realizacion
            ) AS promedio

        FROM entregas e

        INNER JOIN actividad_estudiantes ae
            ON e.id_actividad_estudiante =
               ae.id_actividad_estudiante

        WHERE
            ae.id_alumno IN (
                $subParticipantes
            )

            AND e.tiempo_realizacion
                IS NOT NULL

            $filtroFechaEntrega
        ";


    $resultado =
        $conexion->query(
            $sql
        );


    if (
        $resultado
    ) {

        $promedio_segundos =
            (int) round(
                (float) (
                    $resultado
                        ->fetch_assoc()['promedio']
                    ??
                    0
                )
            );
    }


    // =================================================
    // PROMEDIO POR ACTIVIDAD
    // =================================================

    $sql =
        "
        SELECT
            a.titulo,

            COUNT(
                DISTINCT ae.id_alumno
            ) AS estudiantes,

            COUNT(*) AS entregas,

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
            ae.id_alumno IN (
                $subParticipantes
            )

            AND e.tiempo_realizacion
                IS NOT NULL

            $filtroFechaEntrega

        GROUP BY
            a.id_actividad,
            a.titulo

        ORDER BY
            promedio ASC
        ";


    $resultado =
        $conexion->query(
            $sql
        );


    if (
        $resultado
    ) {

        $actividades_tiempos =
            $resultado->fetch_all(
                MYSQLI_ASSOC
            );
    }
}


$tiempo_promedio =
    formatearTiempo(
        $promedio_segundos
    );


// =====================================================
// =====================================================
// 4. JUEGOS / GAMIFICACIÓN
// =====================================================
// =====================================================

$tieneJuegos =
    tablaExiste(
        $conexion,
        'conecta_intentos'
    )
    &&
    tablaExiste(
        $conexion,
        'conecta_asignaciones'
    )
    &&
    tablaExiste(
        $conexion,
        'conecta_juegos'
    );


$total_intentos_juegos =
    0;


$estudiantes_juegos =
    0;


$tiempo_promedio_juegos_seg =
    0;


$porcentaje_promedio_juegos =
    0;


$total_aciertos_juegos =
    0;


$total_errores_juegos =
    0;


$juegos_detalle =
    [];


if (
    $tieneJuegos &&
    $id_prueba_activa &&
    $total_estudiantes > 0
) {

    $resultado =
        $conexion->query("
            SELECT
                COUNT(
                    ci.id_intento
                ) AS intentos,

                COUNT(
                    DISTINCT ca.id_alumno
                ) AS estudiantes,

                AVG(
                    ci.tiempo_segundos
                ) AS tiempo_promedio,

                AVG(
                    ci.porcentaje
                ) AS porcentaje_promedio,

                SUM(
                    ci.parejas_correctas
                ) AS aciertos,

                SUM(
                    ci.errores
                ) AS errores

            FROM conecta_intentos ci

            INNER JOIN conecta_asignaciones ca
                ON ca.id_asignacion =
                   ci.id_asignacion

            WHERE
                ca.id_alumno IN (
                    $subParticipantes
                )
        ");


    if (
        $resultado
    ) {

        $fila =
            $resultado->fetch_assoc();


        $total_intentos_juegos =
            (int) (
                $fila['intentos']
                ??
                0
            );


        $estudiantes_juegos =
            (int) (
                $fila['estudiantes']
                ??
                0
            );


        $tiempo_promedio_juegos_seg =
            (int) round(
                (float) (
                    $fila['tiempo_promedio']
                    ??
                    0
                )
            );


        $porcentaje_promedio_juegos =
            round(
                (float) (
                    $fila['porcentaje_promedio']
                    ??
                    0
                ),
                2
            );


        $total_aciertos_juegos =
            (int) (
                $fila['aciertos']
                ??
                0
            );


        $total_errores_juegos =
            (int) (
                $fila['errores']
                ??
                0
            );
    }


    // =================================================
    // DETALLE POR JUEGO
    // =================================================

    $resultado =
        $conexion->query("
            SELECT
                cj.titulo,
                cj.modo,

                COUNT(
                    ci.id_intento
                ) AS intentos,

                COUNT(
                    DISTINCT ca.id_alumno
                ) AS estudiantes,

                AVG(
                    ci.tiempo_segundos
                ) AS tiempo_promedio,

                AVG(
                    ci.porcentaje
                ) AS porcentaje_promedio,

                SUM(
                    ci.parejas_correctas
                ) AS aciertos,

                SUM(
                    ci.errores
                ) AS errores

            FROM conecta_intentos ci

            INNER JOIN conecta_asignaciones ca
                ON ca.id_asignacion =
                   ci.id_asignacion

            INNER JOIN conecta_juegos cj
                ON cj.id_juego =
                   ca.id_juego

            WHERE
                ca.id_alumno IN (
                    $subParticipantes
                )

            GROUP BY
                cj.id_juego,
                cj.titulo,
                cj.modo

            ORDER BY
                cj.titulo ASC
        ");


    if (
        $resultado
    ) {

        $juegos_detalle =
            $resultado->fetch_all(
                MYSQLI_ASSOC
            );
    }
}


$porcentaje_uso_juegos =
    porcentaje(
        $estudiantes_juegos,
        $total_estudiantes
    );


// =====================================================
// =====================================================
// 5. ERRORES DE NAVEGACIÓN
// =====================================================
// =====================================================

$total_errores =
    0;


$estudiantes_con_errores =
    0;


$accesos_fallidos =
    0;


$errores_navegacion =
    0;


$tipos_error =
    [];


if (
    $id_prueba_activa
) {

    // =================================================
    // TOTAL
    // =================================================

    $resultado =
        $conexion->query("
            SELECT
                COUNT(*) AS total

            FROM eventos_investigacion

            WHERE
                id_prueba =
                $id_prueba_activa

                AND tipo_evento =
                'Error'
        ");


    if (
        $resultado
    ) {

        $total_errores =
            (int) (
                $resultado
                    ->fetch_assoc()['total']
                ??
                0
            );
    }


    // =================================================
    // ESTUDIANTES AFECTADOS
    // =================================================

    $resultado =
        $conexion->query("
            SELECT
                COUNT(
                    DISTINCT id_usuario
                ) AS total

            FROM eventos_investigacion

            WHERE
                id_prueba =
                $id_prueba_activa

                AND tipo_evento =
                'Error'
        ");


    if (
        $resultado
    ) {

        $estudiantes_con_errores =
            (int) (
                $resultado
                    ->fetch_assoc()['total']
                ??
                0
            );
    }


    // =================================================
    // ACCESOS FALLIDOS
    // =================================================

    $resultado =
        $conexion->query("
            SELECT
                COUNT(*) AS total

            FROM eventos_investigacion

            WHERE
                id_prueba =
                $id_prueba_activa

                AND tipo_evento =
                'Error'

                AND accion LIKE
                '%acceso%'
        ");


    if (
        $resultado
    ) {

        $accesos_fallidos =
            (int) (
                $resultado
                    ->fetch_assoc()['total']
                ??
                0
            );
    }


    // =================================================
    // ERRORES NAVEGACIÓN
    // =================================================

    $resultado =
        $conexion->query("
            SELECT
                COUNT(*) AS total

            FROM eventos_investigacion

            WHERE
                id_prueba =
                $id_prueba_activa

                AND tipo_evento =
                'Error'

                AND accion LIKE
                '%navegacion%'
        ");


    if (
        $resultado
    ) {

        $errores_navegacion =
            (int) (
                $resultado
                    ->fetch_assoc()['total']
                ??
                0
            );
    }


    // =================================================
    // TIPOS DE ERROR
    // =================================================

    $resultado =
        $conexion->query("
            SELECT
                accion,

                COUNT(*) AS total

            FROM eventos_investigacion

            WHERE
                id_prueba =
                $id_prueba_activa

                AND tipo_evento =
                'Error'

            GROUP BY accion

            ORDER BY total DESC
        ");


    if (
        $resultado
    ) {

        $tipos_error =
            $resultado->fetch_all(
                MYSQLI_ASSOC
            );
    }
}


$porcentaje_usuarios_error =
    porcentaje(
        $estudiantes_con_errores,
        $total_estudiantes
    );


// =====================================================
// =====================================================
// 6. CHATBOT
// =====================================================
// =====================================================

$total_chatbot =
    0;


$usuarios_chatbot =
    0;


$total_sesiones_chatbot =
    0;


$duracion_chatbot_seg =
    0;


$tipos_consulta =
    [];


if (
    $id_prueba_activa &&
    $total_estudiantes > 0
) {

    // =================================================
    // MENSAJES Y USUARIOS
    // =================================================

    $sql =
        "
        SELECT
            COUNT(
                m.id_mensaje
            ) AS mensajes,

            COUNT(
                DISTINCT s.id_usuario
            ) AS usuarios,

            COUNT(
                DISTINCT s.id_sesion
            ) AS sesiones

        FROM mensajes_chatbot m

        INNER JOIN sesiones_chatbot s
            ON m.id_sesion =
               s.id_sesion

        WHERE
            s.id_usuario IN (
                $subParticipantes
            )

            $filtroFechaChatbot
        ";


    $resultado =
        $conexion->query(
            $sql
        );


    if (
        $resultado
    ) {

        $fila =
            $resultado->fetch_assoc();


        $total_chatbot =
            (int) (
                $fila['mensajes']
                ??
                0
            );


        $usuarios_chatbot =
            (int) (
                $fila['usuarios']
                ??
                0
            );


        $total_sesiones_chatbot =
            (int) (
                $fila['sesiones']
                ??
                0
            );
    }


    // =================================================
    // DURACIÓN PROMEDIO
    // =================================================

    $resultado =
        $conexion->query("
            SELECT
                AVG(
                    TIMESTAMPDIFF(
                        SECOND,
                        fecha_inicio,
                        fecha_fin
                    )
                ) AS promedio

            FROM sesiones_chatbot

            WHERE
                id_usuario IN (
                    $subParticipantes
                )

                AND fecha_fin IS NOT NULL

                " .
                (
                    $fechaInicioSQL
                        ?
                        "
                        AND DATE(fecha_inicio)
                        BETWEEN
                        '$fechaInicioSQL'
                        AND
                        '$fechaFinSQL'
                        "
                        :
                        ''
                )
        );


    if (
        $resultado
    ) {

        $duracion_chatbot_seg =
            (int) round(
                (float) (
                    $resultado
                        ->fetch_assoc()['promedio']
                    ??
                    0
                )
            );
    }


    // =================================================
    // TIPOS DE CONSULTA
    // =================================================

    $sql =
        "
        SELECT
            m.tipo_consulta,

            COUNT(*) AS total

        FROM mensajes_chatbot m

        INNER JOIN sesiones_chatbot s
            ON m.id_sesion =
               s.id_sesion

        WHERE
            s.id_usuario IN (
                $subParticipantes
            )

            $filtroFechaChatbot

        GROUP BY
            m.tipo_consulta

        ORDER BY
            total DESC
        ";


    $resultado =
        $conexion->query(
            $sql
        );


    if (
        $resultado
    ) {

        $tipos_consulta =
            $resultado->fetch_all(
                MYSQLI_ASSOC
            );
    }
}


$porcentaje_uso_chatbot =
    porcentaje(
        $usuarios_chatbot,
        $total_estudiantes
    );


$promedio_mensajes_chatbot =
    $usuarios_chatbot > 0
        ? round(
            $total_chatbot /
            $usuarios_chatbot,
            2
        )
        : 0;


// =====================================================
// =====================================================
// 7. PROGRESO ACADÉMICO
// =====================================================
// =====================================================

$progreso_promedio =
    0;


$actividades_completadas =
    0;


$total_actividades =
    0;


$evaluaciones_realizadas =
    0;


$total_evaluaciones =
    0;


$progreso_estudiantes =
    [];


if (
    $id_prueba_activa &&
    $total_estudiantes > 0
) {

    // =================================================
    // PROMEDIO
    // =================================================

    $resultado =
        $conexion->query("
            SELECT
                AVG(
                    porcentaje_avance
                ) AS promedio

            FROM actividad_estudiantes

            WHERE
                id_alumno IN (
                    $subParticipantes
                )
        ");


    if (
        $resultado
    ) {

        $progreso_promedio =
            round(
                (float) (
                    $resultado
                        ->fetch_assoc()['promedio']
                    ??
                    0
                ),
                2
            );
    }


    // =================================================
    // ACTIVIDADES COMPLETADAS
    // =================================================

    $resultado =
        $conexion->query("
            SELECT
                COUNT(*) AS total,

                SUM(
                    CASE
                        WHEN estado IN (
                            'Completada',
                            'Calificada'
                        )
                        THEN 1
                        ELSE 0
                    END
                ) AS completadas

            FROM actividad_estudiantes

            WHERE
                id_alumno IN (
                    $subParticipantes
                )
        ");


    if (
        $resultado
    ) {

        $fila =
            $resultado->fetch_assoc();


        $total_actividades =
            (int) (
                $fila['total']
                ??
                0
            );


        $actividades_completadas =
            (int) (
                $fila['completadas']
                ??
                0
            );
    }


    // =================================================
    // EVALUACIONES / ENTREGAS CALIFICADAS
    // =================================================

    $sql =
        "
        SELECT
            COUNT(*) AS total,

            SUM(
                CASE
                    WHEN e.estado =
                    'Calificada'
                    THEN 1
                    ELSE 0
                END
            ) AS realizadas

        FROM entregas e

        INNER JOIN actividad_estudiantes ae
            ON e.id_actividad_estudiante =
               ae.id_actividad_estudiante

        WHERE
            ae.id_alumno IN (
                $subParticipantes
            )

            $filtroFechaEntrega
        ";


    $resultado =
        $conexion->query(
            $sql
        );


    if (
        $resultado
    ) {

        $fila =
            $resultado->fetch_assoc();


        $total_evaluaciones =
            (int) (
                $fila['total']
                ??
                0
            );


        $evaluaciones_realizadas =
            (int) (
                $fila['realizadas']
                ??
                0
            );
    }


    // =================================================
    // PROGRESO POR ESTUDIANTE
    // =================================================

    $resultado =
        $conexion->query("
            SELECT
                u.id_usuario,

                CONCAT_WS(
                    ' ',
                    u.nombre,
                    u.apellido_paterno
                ) AS alumno,

                AVG(
                    ae.porcentaje_avance
                ) AS progreso,

                COUNT(*) AS actividades,

                SUM(
                    CASE
                        WHEN ae.estado IN (
                            'Completada',
                            'Calificada'
                        )
                        THEN 1
                        ELSE 0
                    END
                ) AS completadas

            FROM actividad_estudiantes ae

            INNER JOIN usuarios u
                ON u.id_usuario =
                   ae.id_alumno

            WHERE
                ae.id_alumno IN (
                    $subParticipantes
                )

            GROUP BY
                u.id_usuario,
                u.nombre,
                u.apellido_paterno

            ORDER BY
                progreso DESC
        ");


    if (
        $resultado
    ) {

        $progreso_estudiantes =
            $resultado->fetch_all(
                MYSQLI_ASSOC
            );
    }
}


$porcentaje_actividades =
    porcentaje(
        $actividades_completadas,
        $total_actividades
    );


$porcentaje_evaluaciones =
    porcentaje(
        $evaluaciones_realizadas,
        $total_evaluaciones
    );


// =====================================================
// =====================================================
// 8. ACCESIBILIDAD
// =====================================================
// =====================================================

$usan_accesibilidad =
    0;


$accesibilidad_stats =
    [];


$accesibilidad_data =
    [];


if (
    $id_prueba_activa &&
    $total_estudiantes > 0
) {

    // =================================================
    // USAN ALGUNA FUNCIÓN
    // =================================================

    $resultado =
        $conexion->query("
            SELECT
                COUNT(
                    DISTINCT id_usuario
                ) AS total

            FROM preferencias_accesibilidad

            WHERE
                id_usuario IN (
                    $subParticipantes
                )

                AND (
                    alto_contraste = 1

                    OR modo_oscuro = 1

                    OR lector_pantalla = 1

                    OR subtitulos = 1

                    OR tamano_texto <>
                    'Normal'

                    OR fuente_dislexia = 1

                    OR navegacion_teclado = 1
                )
        ");


    if (
        $resultado
    ) {

        $usan_accesibilidad =
            (int) (
                $resultado
                    ->fetch_assoc()['total']
                ??
                0
            );
    }


    // =================================================
    // DETALLE
    // =================================================

    $resultado =
        $conexion->query("
            SELECT

                SUM(
                    CASE
                        WHEN alto_contraste = 1
                        THEN 1
                        ELSE 0
                    END
                ) AS alto_contraste,

                SUM(
                    CASE
                        WHEN modo_oscuro = 1
                        THEN 1
                        ELSE 0
                    END
                ) AS modo_oscuro,

                SUM(
                    CASE
                        WHEN tamano_texto <>
                        'Normal'
                        THEN 1
                        ELSE 0
                    END
                ) AS tamano_texto,

                SUM(
                    CASE
                        WHEN fuente_dislexia = 1
                        THEN 1
                        ELSE 0
                    END
                ) AS fuente_dislexia,

                SUM(
                    CASE
                        WHEN lector_pantalla = 1
                        THEN 1
                        ELSE 0
                    END
                ) AS lector_pantalla,

                SUM(
                    CASE
                        WHEN subtitulos = 1
                        THEN 1
                        ELSE 0
                    END
                ) AS subtitulos,

                SUM(
                    CASE
                        WHEN navegacion_teclado = 1
                        THEN 1
                        ELSE 0
                    END
                ) AS navegacion_teclado

            FROM preferencias_accesibilidad

            WHERE
                id_usuario IN (
                    $subParticipantes
                )
        ");


    if (
        $resultado
    ) {

        $accesibilidad_stats =
            $resultado->fetch_assoc()
            ??
            [];
    }
}


$accesibilidad_data = [

    [
        'nombre' =>
            'Alto contraste',

        'total' =>
            (int) (
                $accesibilidad_stats['alto_contraste']
                ??
                0
            ),
    ],

    [
        'nombre' =>
            'Modo oscuro',

        'total' =>
            (int) (
                $accesibilidad_stats['modo_oscuro']
                ??
                0
            ),
    ],

    [
        'nombre' =>
            'Tamaño de texto',

        'total' =>
            (int) (
                $accesibilidad_stats['tamano_texto']
                ??
                0
            ),
    ],

    [
        'nombre' =>
            'Fuente para dislexia',

        'total' =>
            (int) (
                $accesibilidad_stats['fuente_dislexia']
                ??
                0
            ),
    ],

    [
        'nombre' =>
            'Lector de pantalla',

        'total' =>
            (int) (
                $accesibilidad_stats['lector_pantalla']
                ??
                0
            ),
    ],

    [
        'nombre' =>
            'Subtítulos',

        'total' =>
            (int) (
                $accesibilidad_stats['subtitulos']
                ??
                0
            ),
    ],

    [
        'nombre' =>
            'Navegación por teclado',

        'total' =>
            (int) (
                $accesibilidad_stats['navegacion_teclado']
                ??
                0
            ),
    ],
];


foreach (
    $accesibilidad_data
    as &$item
) {

    $item['porcentaje'] =
        porcentaje(
            $item['total'],
            $total_estudiantes
        );
}

unset(
    $item
);


$porcentaje_accesibilidad =
    porcentaje(
        $usan_accesibilidad,
        $total_estudiantes
    );

// =====================================================
// =====================================================
// 9. ESTÁNDARES UTILIZADOS
//
// MISMA LÓGICA QUE LA APP MÓVIL:
// 1. Detectar qué funciones de accesibilidad utilizan
//    los participantes de la prueba.
// 2. Buscar esas funciones exactamente en
//    funcionalidades_estandares.
// 3. Obtener los criterios asociados desde
//    catalogo_estandares.
// =====================================================
// =====================================================

$estandares =
    [];


$funciones_utilizadas =
    [];


$tieneEstandares =
    tablaExiste(
        $conexion,
        'catalogo_estandares'
    )
    &&
    tablaExiste(
        $conexion,
        'funcionalidades_estandares'
    )
    &&
    tablaExiste(
        $conexion,
        'preferencias_accesibilidad'
    );


// =====================================================
// FUNCIONES UTILIZADAS POR LOS PARTICIPANTES
//
// Se usan exactamente los mismos nombres que maneja
// el módulo móvil del investigador.
// =====================================================

if (
    (int) (
        $accesibilidad_stats['alto_contraste']
        ??
        0
    ) > 0
) {

    $funciones_utilizadas[] =
        'Alto contraste';
}


if (
    (int) (
        $accesibilidad_stats['tamano_texto']
        ??
        0
    ) > 0
) {

    $funciones_utilizadas[] =
        'Tamaño de texto';
}


if (
    (int) (
        $accesibilidad_stats['lector_pantalla']
        ??
        0
    ) > 0
) {

    $funciones_utilizadas[] =
        'Compatibilidad con lector de pantalla';
}


if (
    (int) (
        $accesibilidad_stats['subtitulos']
        ??
        0
    ) > 0
) {

    $funciones_utilizadas[] =
        'Subtítulos';
}


if (
    (int) (
        $accesibilidad_stats['navegacion_teclado']
        ??
        0
    ) > 0
) {

    $funciones_utilizadas[] =
        'Navegación por teclado';
}


// =====================================================
// CONSULTAR CRITERIOS RELACIONADOS
// =====================================================

if (
    $tieneEstandares
    &&
    $id_prueba_activa
    &&
    !empty(
        $funciones_utilizadas
    )
) {

    $placeholders =
        implode(
            ',',
            array_fill(
                0,
                count(
                    $funciones_utilizadas
                ),
                '?'
            )
        );


    $sqlEstandares =
        "
        SELECT DISTINCT

            ce.id_estandar,

            ce.norma,

            ce.criterio,

            ce.nombre,

            ce.descripcion,

            ce.principio,

            ce.nivel,

            ce.referencia_oficial,

            fe.modulo,

            fe.funcionalidad,

            fe.descripcion
                AS descripcion_funcionalidad


        FROM funcionalidades_estandares fe


        INNER JOIN catalogo_estandares ce

            ON ce.id_estandar =
               fe.id_estandar


        WHERE

            fe.implementado = 1

            AND fe.funcionalidad
                IN (
                    $placeholders
                )


        ORDER BY

            ce.norma,

            ce.criterio,

            fe.modulo,

            fe.funcionalidad
        ";


    $stmtEstandares =
        $conexion->prepare(
            $sqlEstandares
        );


    if (
        $stmtEstandares
    ) {

        $tipos =
            str_repeat(
                's',
                count(
                    $funciones_utilizadas
                )
            );


        $stmtEstandares->bind_param(
            $tipos,
            ...$funciones_utilizadas
        );


        $stmtEstandares->execute();


        $resultadoEstandares =
            $stmtEstandares->get_result();


        if (
            $resultadoEstandares
        ) {

            $estandares =
                $resultadoEstandares->fetch_all(
                    MYSQLI_ASSOC
                );
        }


        $stmtEstandares->close();

    } else {

        error_log(
            'Error al preparar estándares utilizados: ' .
            $conexion->error
        );
    }
}


// =====================================================
// =====================================================
// 10. RESUMEN DE PORCENTAJES
// =====================================================
// =====================================================

$resumen_porcentajes = [

    [
        'nombre' =>
            'Participación en plataforma',

        'valor' =>
            $porcentaje_uso_plataforma,
    ],

    [
        'nombre' =>
            'Uso de accesibilidad',

        'valor' =>
            $porcentaje_accesibilidad,
    ],

    [
        'nombre' =>
            'Uso del chatbot',

        'valor' =>
            $porcentaje_uso_chatbot,
    ],

    [
        'nombre' =>
            'Progreso académico',

        'valor' =>
            $progreso_promedio,
    ],

    [
        'nombre' =>
            'Actividades completadas',

        'valor' =>
            $porcentaje_actividades,
    ],

    [
        'nombre' =>
            'Evaluaciones realizadas',

        'valor' =>
            $porcentaje_evaluaciones,
    ],

    [
        'nombre' =>
            'Participantes con errores',

        'valor' =>
            $porcentaje_usuarios_error,
    ],

    [
        'nombre' =>
            'Consentimiento',

        'valor' =>
            $porcentaje_consentimiento,
    ],
];


if (
    $tieneJuegos
) {

    $resumen_porcentajes[] = [

        'nombre' =>
            'Uso de juegos',

        'valor' =>
            $porcentaje_uso_juegos,
    ];
}


// =====================================================
// =====================================================
// EXPORTAR CSV
// =====================================================
// =====================================================

if (
    isset(
        $_GET['exportar']
    )
    &&
    $_GET['exportar'] ===
    'csv'
) {

    header(
        'Content-Type: text/csv; charset=utf-8'
    );


    header(
        'Content-Disposition: attachment; filename="reporte_investigacion_' .
        date(
            'Y-m-d'
        ) .
        '.csv"'
    );


    echo "\xEF\xBB\xBF";


    $output =
        fopen(
            'php://output',
            'w'
        );


    // =================================================
    // DATOS PRUEBA
    // =================================================

    fputcsv(
        $output,
        [
            'REPORTE DE INVESTIGACIÓN - AULAMOS'
        ]
    );


    fputcsv(
        $output,
        []
    );


    fputcsv(
        $output,
        [
            'DATOS DE LA PRUEBA'
        ]
    );


    $datosPruebaCSV = [

        [
            'Nombre',
            $prueba_activa_nombre,
        ],

        [
            'Versión WCAG',
            $version_wcag,
        ],

        [
            'Estado',
            $estado_prueba,
        ],

        [
            'Periodo',
            $periodo_reporte,
        ],

        [
            'Participantes',
            $total_estudiantes,
        ],

        [
            'Consentimientos',
            $total_consentimientos,
        ],

        [
            'Porcentaje consentimiento',
            $porcentaje_consentimiento . '%',
        ],

        [
            'Descripción',
            $descripcion_prueba,
        ],

        [
            'Hipótesis',
            $hipotesis_prueba,
        ],

        [
            'Objetivo',
            $objetivo_prueba,
        ],
    ];


    foreach (
        $datosPruebaCSV
        as $fila
    ) {

        fputcsv(
            $output,
            $fila
        );
    }


    // =================================================
    // RESUMEN PORCENTAJES
    // =================================================

    fputcsv(
        $output,
        []
    );


    fputcsv(
        $output,
        [
            'RESUMEN GENERAL'
        ]
    );


    fputcsv(
        $output,
        [
            'Métrica',
            'Porcentaje'
        ]
    );


    foreach (
        $resumen_porcentajes
        as $item
    ) {

        fputcsv(
            $output,
            [
                $item['nombre'],
                number_format(
                    $item['valor'],
                    2
                ) . '%',
            ]
        );
    }


    // =================================================
    // USO PLATAFORMA
    // =================================================

    fputcsv(
        $output,
        []
    );


    fputcsv(
        $output,
        [
            'USO DE LA PLATAFORMA'
        ]
    );


    fputcsv(
        $output,
        [
            'Accesos totales',
            $total_accesos
        ]
    );


    fputcsv(
        $output,
        [
            'Promedio de accesos por participante',
            $promedio_accesos
        ]
    );


    fputcsv(
        $output,
        [
            'Participantes con actividad',
            $usuarios_con_actividad .
            ' de ' .
            $total_estudiantes
        ]
    );


    fputcsv(
        $output,
        [
            'Participación',
            number_format(
                $porcentaje_uso_plataforma,
                2
            ) . '%'
        ]
    );


    fputcsv(
        $output,
        []
    );


    fputcsv(
        $output,
        [
            'Módulo',
            'Visitas',
            'Usuarios',
            '% de visitas',
            '% de participantes'
        ]
    );


    foreach (
        $modulos_visitados
        as $modulo
    ) {

        $porcentajeVisitas =
            porcentaje(
                $modulo['visitas'],
                $total_visitas_modulos
            );


        $porcentajeUsuarios =
            porcentaje(
                $modulo['usuarios'],
                $total_estudiantes
            );


        fputcsv(
            $output,
            [
                $modulo['modulo'],
                $modulo['visitas'],
                $modulo['usuarios'],
                $porcentajeVisitas . '%',
                $porcentajeUsuarios . '%',
            ]
        );
    }


    // =================================================
    // TIEMPOS
    // =================================================

    fputcsv(
        $output,
        []
    );


    fputcsv(
        $output,
        [
            'TIEMPOS DE ACTIVIDADES'
        ]
    );


    fputcsv(
        $output,
        [
            'Tiempo promedio general',
            $tiempo_promedio
        ]
    );


    fputcsv(
        $output,
        []
    );


    fputcsv(
        $output,
        [
            'Actividad',
            'Estudiantes',
            'Entregas',
            'Tiempo promedio'
        ]
    );


    foreach (
        $actividades_tiempos
        as $actividad
    ) {

        fputcsv(
            $output,
            [
                $actividad['titulo'],
                $actividad['estudiantes'],
                $actividad['entregas'],
                formatearTiempo(
                    $actividad['promedio']
                ),
            ]
        );
    }


    // =================================================
    // JUEGOS
    // =================================================

    if (
        $tieneJuegos
    ) {

        fputcsv(
            $output,
            []
        );


        fputcsv(
            $output,
            [
                'JUEGOS / GAMIFICACIÓN'
            ]
        );


        fputcsv(
            $output,
            [
                'Participantes que jugaron',
                $estudiantes_juegos .
                ' de ' .
                $total_estudiantes
            ]
        );


        fputcsv(
            $output,
            [
                'Porcentaje de uso',
                $porcentaje_uso_juegos . '%'
            ]
        );


        fputcsv(
            $output,
            [
                'Intentos',
                $total_intentos_juegos
            ]
        );


        fputcsv(
            $output,
            [
                'Tiempo promedio',
                formatearTiempo(
                    $tiempo_promedio_juegos_seg
                )
            ]
        );


        fputcsv(
            $output,
            [
                'Resultado promedio',
                $porcentaje_promedio_juegos . '%'
            ]
        );


        fputcsv(
            $output,
            [
                'Aciertos',
                $total_aciertos_juegos
            ]
        );


        fputcsv(
            $output,
            [
                'Errores',
                $total_errores_juegos
            ]
        );


        fputcsv(
            $output,
            []
        );


        fputcsv(
            $output,
            [
                'Juego',
                'Modo',
                'Participantes',
                'Intentos',
                'Tiempo promedio',
                'Resultado promedio',
                'Aciertos',
                'Errores'
            ]
        );


        foreach (
            $juegos_detalle
            as $juego
        ) {

            fputcsv(
                $output,
                [
                    $juego['titulo'],
                    $juego['modo'],
                    $juego['estudiantes'],
                    $juego['intentos'],
                    formatearTiempo(
                        $juego['tiempo_promedio']
                    ),
                    number_format(
                        $juego['porcentaje_promedio'],
                        2
                    ) . '%',
                    $juego['aciertos'],
                    $juego['errores'],
                ]
            );
        }
    }


    // =================================================
    // ERRORES
    // =================================================

    fputcsv(
        $output,
        []
    );


    fputcsv(
        $output,
        [
            'ERRORES DE NAVEGACIÓN'
        ]
    );


    fputcsv(
        $output,
        [
            'Errores totales',
            $total_errores
        ]
    );


    fputcsv(
        $output,
        [
            'Participantes afectados',
            $estudiantes_con_errores .
            ' de ' .
            $total_estudiantes
        ]
    );


    fputcsv(
        $output,
        [
            'Porcentaje afectados',
            $porcentaje_usuarios_error . '%'
        ]
    );


    fputcsv(
        $output,
        [
            'Accesos fallidos',
            $accesos_fallidos
        ]
    );


    fputcsv(
        $output,
        [
            'Errores de navegación',
            $errores_navegacion
        ]
    );


    fputcsv(
        $output,
        []
    );


    fputcsv(
        $output,
        [
            'Tipo de error',
            'Cantidad',
            '% del total'
        ]
    );


    foreach (
        $tipos_error
        as $error
    ) {

        fputcsv(
            $output,
            [
                $error['accion'],
                $error['total'],
                porcentaje(
                    $error['total'],
                    $total_errores
                ) . '%',
            ]
        );
    }


    // =================================================
    // CHATBOT
    // =================================================

    fputcsv(
        $output,
        []
    );


    fputcsv(
        $output,
        [
            'USO DEL CHATBOT'
        ]
    );


    fputcsv(
        $output,
        [
            'Usuarios',
            $usuarios_chatbot .
            ' de ' .
            $total_estudiantes
        ]
    );


    fputcsv(
        $output,
        [
            'Porcentaje de uso',
            $porcentaje_uso_chatbot . '%'
        ]
    );


    fputcsv(
        $output,
        [
            'Sesiones',
            $total_sesiones_chatbot
        ]
    );


    fputcsv(
        $output,
        [
            'Mensajes',
            $total_chatbot
        ]
    );


    fputcsv(
        $output,
        [
            'Promedio mensajes por usuario',
            $promedio_mensajes_chatbot
        ]
    );


    fputcsv(
        $output,
        [
            'Duración promedio',
            formatearTiempo(
                $duracion_chatbot_seg
            )
        ]
    );


    fputcsv(
        $output,
        []
    );


    fputcsv(
        $output,
        [
            'Tipo de consulta',
            'Cantidad',
            'Porcentaje'
        ]
    );


    foreach (
        $tipos_consulta
        as $consulta
    ) {

        fputcsv(
            $output,
            [
                $consulta['tipo_consulta'],
                $consulta['total'],
                porcentaje(
                    $consulta['total'],
                    $total_chatbot
                ) . '%',
            ]
        );
    }


    // =================================================
    // PROGRESO
    // =================================================

    fputcsv(
        $output,
        []
    );


    fputcsv(
        $output,
        [
            'PROGRESO ACADÉMICO'
        ]
    );


    fputcsv(
        $output,
        [
            'Progreso promedio',
            $progreso_promedio . '%'
        ]
    );


    fputcsv(
        $output,
        [
            'Actividades completadas',
            $actividades_completadas .
            ' de ' .
            $total_actividades,
            $porcentaje_actividades . '%'
        ]
    );


    fputcsv(
        $output,
        [
            'Evaluaciones realizadas',
            $evaluaciones_realizadas .
            ' de ' .
            $total_evaluaciones,
            $porcentaje_evaluaciones . '%'
        ]
    );


    fputcsv(
        $output,
        []
    );


    fputcsv(
        $output,
        [
            'Alumno',
            'Progreso',
            'Actividades completadas',
            'Total actividades'
        ]
    );


    foreach (
        $progreso_estudiantes
        as $estudiante
    ) {

        fputcsv(
            $output,
            [
                $estudiante['alumno'],
                number_format(
                    $estudiante['progreso'],
                    2
                ) . '%',
                $estudiante['completadas'],
                $estudiante['actividades'],
            ]
        );
    }


    // =================================================
    // ACCESIBILIDAD
    // =================================================

    fputcsv(
        $output,
        []
    );


    fputcsv(
        $output,
        [
            'ACCESIBILIDAD'
        ]
    );


    fputcsv(
        $output,
        [
            'Uso de alguna función',
            $usan_accesibilidad .
            ' de ' .
            $total_estudiantes,
            $porcentaje_accesibilidad . '%'
        ]
    );


    fputcsv(
        $output,
        []
    );


    fputcsv(
        $output,
        [
            'Función',
            'Estudiantes',
            'Total participantes',
            'Porcentaje'
        ]
    );


    foreach (
        $accesibilidad_data
        as $item
    ) {

        fputcsv(
            $output,
            [
                $item['nombre'],
                $item['total'],
                $total_estudiantes,
                $item['porcentaje'] . '%',
            ]
        );
    }


    // =================================================
    // ESTÁNDARES
    // =================================================

    fputcsv(
        $output,
        []
    );


    fputcsv(
        $output,
        [
            'ESTÁNDARES UTILIZADOS'
        ]
    );


    fputcsv(
        $output,
        [
            'Norma',
            'Criterio',
            'Nombre',
            'Principio',
            'Nivel',
            'Módulo',
            'Funcionalidad utilizada',
            'Descripción',
            'Referencia'
        ]
    );


    if (
        empty(
            $estandares
        )
    ) {

        fputcsv(
            $output,
            [
                'Sin estándares relacionados con las funciones utilizadas por los participantes.'
            ]
        );

    } else {

        foreach (
            $estandares
            as $estandar
        ) {

            fputcsv(
                $output,
                [
                    $estandar['norma']
                    ??
                    '',

                    $estandar['criterio']
                    ??
                    '',

                    $estandar['nombre']
                    ??
                    '',

                    $estandar['principio']
                    ??
                    '',

                    $estandar['nivel']
                    ??
                    '',

                    $estandar['modulo']
                    ??
                    '',

                    $estandar['funcionalidad']
                    ??
                    '',

                    $estandar['descripcion']
                    ??
                    '',

                    $estandar['referencia_oficial']
                    ??
                    '',
                ]
            );
        }
    }


    fclose(
        $output
    );


    exit;
}


// =====================================================
// =====================================================
// EXPORTAR EXCEL
// =====================================================
// =====================================================

if (
    isset(
        $_GET['exportar']
    )
    &&
    $_GET['exportar'] ===
    'excel'
) {

    header(
        'Content-Type: application/vnd.ms-excel; charset=utf-8'
    );


    header(
        'Content-Disposition: attachment; filename="reporte_investigacion_' .
        date(
            'Y-m-d'
        ) .
        '.xls"'
    );


    echo '
    <html>

    <head>

        <meta charset="utf-8">

        <style>

            body {
                font-family:
                    Arial,
                    sans-serif;
            }

            h1 {
                color:
                    #172033;
            }

            h2 {
                margin-top:
                    25px;

                color:
                    #5a189a;
            }

            table {
                border-collapse:
                    collapse;

                width:
                    100%;

                margin-bottom:
                    20px;
            }

            th {
                background:
                    #3b71f3;

                color:
                    #ffffff;

                padding:
                    7px;
            }

            td {
                padding:
                    7px;

                vertical-align:
                    top;
            }

            .seccion-morada th {
                background:
                    #7C3AED;
            }

            .seccion-verde th {
                background:
                    #2e7d32;
            }

            .seccion-roja th {
                background:
                    #c62828;
            }

            .titulo-dato {
                font-weight:
                    bold;

                background:
                    #f8fafc;
            }

        </style>

    </head>

    <body>
    ';


    // =================================================
    // TÍTULO
    // =================================================

    echo '
    <h1>
        Reporte de Investigación - AULAMOS
    </h1>

    <p>
        <strong>
            Fecha de generación:
        </strong>
        ' .
        date(
            'd/m/Y H:i'
        ) .
        '
    </p>
    ';


    // =================================================
    // DATOS PRUEBA
    // =================================================

    echo '
    <h2>
        1. Datos de la prueba
    </h2>

    <table border="1">

        <tr class="seccion-morada">
            <th>Dato</th>
            <th>Información</th>
        </tr>
    ';


    $datosPrueba = [

        [
            'Nombre / principio',
            $prueba_activa_nombre,
        ],

        [
            'Versión WCAG',
            $version_wcag,
        ],

        [
            'Estado',
            $estado_prueba,
        ],

        [
            'Periodo',
            $periodo_reporte,
        ],

        [
            'Participantes',
            $total_estudiantes,
        ],

        [
            'Consentimientos',
            $total_consentimientos,
        ],

        [
            'Porcentaje de consentimiento',
            number_format(
                $porcentaje_consentimiento,
                2
            ) . '%',
        ],

        [
            'Descripción',
            $descripcion_prueba,
        ],

        [
            'Hipótesis',
            $hipotesis_prueba,
        ],

        [
            'Objetivo',
            $objetivo_prueba,
        ],
    ];


    foreach (
        $datosPrueba
        as $dato
    ) {

        echo '
        <tr>

            <td class="titulo-dato">' .
            htmlspecialchars(
                (string) $dato[0]
            ) .
            '</td>

            <td>' .
            htmlspecialchars(
                (string) $dato[1]
            ) .
            '</td>

        </tr>
        ';
    }


    echo '
    </table>
    ';


    // =================================================
    // RESUMEN GENERAL
    // =================================================

    echo '
    <h2>
        2. Resumen general
    </h2>

    <table border="1">

        <tr>
            <th>Métrica</th>
            <th>Resultado</th>
        </tr>
    ';


    foreach (
        $resumen_porcentajes
        as $item
    ) {

        echo '
        <tr>

            <td>' .
            htmlspecialchars(
                $item['nombre']
            ) .
            '</td>

            <td>' .
            number_format(
                $item['valor'],
                2
            ) .
            '%</td>

        </tr>
        ';
    }


    echo '
    </table>
    ';


    // =================================================
    // USO PLATAFORMA
    // =================================================

    echo '
    <h2>
        3. Uso de la plataforma
    </h2>

    <table border="1">

        <tr>
            <th>Métrica</th>
            <th>Valor</th>
        </tr>

        <tr>
            <td>Accesos totales</td>
            <td>' .
            $total_accesos .
            '</td>
        </tr>

        <tr>
            <td>Promedio accesos por participante</td>
            <td>' .
            $promedio_accesos .
            '</td>
        </tr>

        <tr>
            <td>Participantes con actividad</td>
            <td>' .
            $usuarios_con_actividad .
            ' de ' .
            $total_estudiantes .
            '</td>
        </tr>

        <tr>
            <td>Porcentaje participación</td>
            <td>' .
            number_format(
                $porcentaje_uso_plataforma,
                2
            ) .
            '%</td>
        </tr>

        <tr>
            <td>Eventos registrados</td>
            <td>' .
            $total_eventos_plataforma .
            '</td>
        </tr>

    </table>


    <table border="1">

        <tr>
            <th>Módulo</th>
            <th>Visitas</th>
            <th>Usuarios</th>
            <th>% visitas</th>
            <th>% participantes</th>
        </tr>
    ';


    foreach (
        $modulos_visitados
        as $modulo
    ) {

        echo '
        <tr>

            <td>' .
            htmlspecialchars(
                $modulo['modulo']
            ) .
            '</td>

            <td>' .
            (int) $modulo['visitas'] .
            '</td>

            <td>' .
            (int) $modulo['usuarios'] .
            '</td>

            <td>' .
            number_format(
                porcentaje(
                    $modulo['visitas'],
                    $total_visitas_modulos
                ),
                2
            ) .
            '%</td>

            <td>' .
            number_format(
                porcentaje(
                    $modulo['usuarios'],
                    $total_estudiantes
                ),
                2
            ) .
            '%</td>

        </tr>
        ';
    }


    echo '
    </table>
    ';


    // =================================================
    // TIEMPOS
    // =================================================

    echo '
    <h2>
        4. Tiempos de actividades
    </h2>

    <p>
        <strong>
            Tiempo promedio general:
        </strong>
        ' .
        htmlspecialchars(
            $tiempo_promedio
        ) .
        '
    </p>

    <table border="1">

        <tr>
            <th>Actividad</th>
            <th>Estudiantes</th>
            <th>Entregas</th>
            <th>Tiempo promedio</th>
        </tr>
    ';


    foreach (
        $actividades_tiempos
        as $actividad
    ) {

        echo '
        <tr>

            <td>' .
            htmlspecialchars(
                $actividad['titulo']
            ) .
            '</td>

            <td>' .
            (int) $actividad['estudiantes'] .
            '</td>

            <td>' .
            (int) $actividad['entregas'] .
            '</td>

            <td>' .
            htmlspecialchars(
                formatearTiempo(
                    $actividad['promedio']
                )
            ) .
            '</td>

        </tr>
        ';
    }


    echo '
    </table>
    ';


    // =================================================
    // JUEGOS
    // =================================================

    if (
        $tieneJuegos
    ) {

        echo '
        <h2>
            5. Juegos y gamificación
        </h2>

        <table border="1">

            <tr class="seccion-verde">
                <th>Métrica</th>
                <th>Valor</th>
            </tr>

            <tr>
                <td>Participantes que jugaron</td>
                <td>' .
                $estudiantes_juegos .
                ' de ' .
                $total_estudiantes .
                '</td>
            </tr>

            <tr>
                <td>Porcentaje de uso</td>
                <td>' .
                number_format(
                    $porcentaje_uso_juegos,
                    2
                ) .
                '%</td>
            </tr>

            <tr>
                <td>Intentos</td>
                <td>' .
                $total_intentos_juegos .
                '</td>
            </tr>

            <tr>
                <td>Tiempo promedio</td>
                <td>' .
                formatearTiempo(
                    $tiempo_promedio_juegos_seg
                ) .
                '</td>
            </tr>

            <tr>
                <td>Resultado promedio</td>
                <td>' .
                number_format(
                    $porcentaje_promedio_juegos,
                    2
                ) .
                '%</td>
            </tr>

            <tr>
                <td>Aciertos</td>
                <td>' .
                $total_aciertos_juegos .
                '</td>
            </tr>

            <tr>
                <td>Errores</td>
                <td>' .
                $total_errores_juegos .
                '</td>
            </tr>

        </table>


        <table border="1">

            <tr class="seccion-verde">
                <th>Juego</th>
                <th>Modo</th>
                <th>Participantes</th>
                <th>Intentos</th>
                <th>Tiempo promedio</th>
                <th>Resultado promedio</th>
                <th>Aciertos</th>
                <th>Errores</th>
            </tr>
        ';


        foreach (
            $juegos_detalle
            as $juego
        ) {

            echo '
            <tr>

                <td>' .
                htmlspecialchars(
                    $juego['titulo']
                ) .
                '</td>

                <td>' .
                htmlspecialchars(
                    $juego['modo']
                ) .
                '</td>

                <td>' .
                (int) $juego['estudiantes'] .
                '</td>

                <td>' .
                (int) $juego['intentos'] .
                '</td>

                <td>' .
                formatearTiempo(
                    $juego['tiempo_promedio']
                ) .
                '</td>

                <td>' .
                number_format(
                    $juego['porcentaje_promedio'],
                    2
                ) .
                '%</td>

                <td>' .
                (int) $juego['aciertos'] .
                '</td>

                <td>' .
                (int) $juego['errores'] .
                '</td>

            </tr>
            ';
        }


        echo '
        </table>
        ';
    }


    // =================================================
    // ERRORES
    // =================================================

    echo '
    <h2>
        6. Errores de navegación
    </h2>

    <table
        border="1"
        class="seccion-roja"
    >

        <tr>
            <th>Métrica</th>
            <th>Resultado</th>
        </tr>

        <tr>
            <td>Errores totales</td>
            <td>' .
            $total_errores .
            '</td>
        </tr>

        <tr>
            <td>Participantes afectados</td>
            <td>' .
            $estudiantes_con_errores .
            ' de ' .
            $total_estudiantes .
            '</td>
        </tr>

        <tr>
            <td>Porcentaje afectados</td>
            <td>' .
            number_format(
                $porcentaje_usuarios_error,
                2
            ) .
            '%</td>
        </tr>

        <tr>
            <td>Accesos fallidos</td>
            <td>' .
            $accesos_fallidos .
            '</td>
        </tr>

        <tr>
            <td>Errores de navegación</td>
            <td>' .
            $errores_navegacion .
            '</td>
        </tr>

    </table>


    <table border="1">

        <tr class="seccion-roja">
            <th>Tipo de error</th>
            <th>Cantidad</th>
            <th>Porcentaje</th>
        </tr>
    ';


    foreach (
        $tipos_error
        as $error
    ) {

        echo '
        <tr>

            <td>' .
            htmlspecialchars(
                $error['accion']
            ) .
            '</td>

            <td>' .
            (int) $error['total'] .
            '</td>

            <td>' .
            number_format(
                porcentaje(
                    $error['total'],
                    $total_errores
                ),
                2
            ) .
            '%</td>

        </tr>
        ';
    }


    echo '
    </table>
    ';


    // =================================================
    // CHATBOT
    // =================================================

    echo '
    <h2>
        7. Uso del chatbot
    </h2>

    <table border="1">

        <tr>
            <th>Métrica</th>
            <th>Resultado</th>
        </tr>

        <tr>
            <td>Usuarios del chatbot</td>
            <td>' .
            $usuarios_chatbot .
            ' de ' .
            $total_estudiantes .
            '</td>
        </tr>

        <tr>
            <td>Porcentaje de uso</td>
            <td>' .
            number_format(
                $porcentaje_uso_chatbot,
                2
            ) .
            '%</td>
        </tr>

        <tr>
            <td>Sesiones</td>
            <td>' .
            $total_sesiones_chatbot .
            '</td>
        </tr>

        <tr>
            <td>Mensajes</td>
            <td>' .
            $total_chatbot .
            '</td>
        </tr>

        <tr>
            <td>Promedio mensajes por usuario</td>
            <td>' .
            $promedio_mensajes_chatbot .
            '</td>
        </tr>

        <tr>
            <td>Duración promedio</td>
            <td>' .
            formatearTiempo(
                $duracion_chatbot_seg
            ) .
            '</td>
        </tr>

    </table>


    <table border="1">

        <tr>
            <th>Tipo de consulta</th>
            <th>Cantidad</th>
            <th>Porcentaje</th>
        </tr>
    ';


    foreach (
        $tipos_consulta
        as $consulta
    ) {

        echo '
        <tr>

            <td>' .
            htmlspecialchars(
                $consulta['tipo_consulta']
            ) .
            '</td>

            <td>' .
            (int) $consulta['total'] .
            '</td>

            <td>' .
            number_format(
                porcentaje(
                    $consulta['total'],
                    $total_chatbot
                ),
                2
            ) .
            '%</td>

        </tr>
        ';
    }


    echo '
    </table>
    ';


    // =================================================
    // PROGRESO
    // =================================================

    echo '
    <h2>
        8. Progreso académico
    </h2>

    <table border="1">

        <tr>
            <th>Métrica</th>
            <th>Resultado</th>
        </tr>

        <tr>
            <td>Progreso promedio</td>
            <td>' .
            number_format(
                $progreso_promedio,
                2
            ) .
            '%</td>
        </tr>

        <tr>
            <td>Actividades completadas</td>
            <td>' .
            $actividades_completadas .
            ' de ' .
            $total_actividades .
            ' (' .
            number_format(
                $porcentaje_actividades,
                2
            ) .
            '%)</td>
        </tr>

        <tr>
            <td>Evaluaciones realizadas</td>
            <td>' .
            $evaluaciones_realizadas .
            ' de ' .
            $total_evaluaciones .
            ' (' .
            number_format(
                $porcentaje_evaluaciones,
                2
            ) .
            '%)</td>
        </tr>

    </table>


    <table border="1">

        <tr>
            <th>Alumno</th>
            <th>Progreso</th>
            <th>Completadas</th>
            <th>Total actividades</th>
        </tr>
    ';


    foreach (
        $progreso_estudiantes
        as $estudiante
    ) {

        echo '
        <tr>

            <td>' .
            htmlspecialchars(
                $estudiante['alumno']
            ) .
            '</td>

            <td>' .
            number_format(
                $estudiante['progreso'],
                2
            ) .
            '%</td>

            <td>' .
            (int) $estudiante['completadas'] .
            '</td>

            <td>' .
            (int) $estudiante['actividades'] .
            '</td>

        </tr>
        ';
    }


    echo '
    </table>
    ';


    // =================================================
    // ACCESIBILIDAD
    // =================================================

    echo '
    <h2>
        9. Accesibilidad
    </h2>

    <p>
        <strong>
            Participantes que utilizan alguna función:
        </strong>
        ' .
        $usan_accesibilidad .
        ' de ' .
        $total_estudiantes .
        '
        (' .
        number_format(
            $porcentaje_accesibilidad,
            2
        ) .
        '%)
    </p>


    <table border="1">

        <tr class="seccion-morada">
            <th>Función</th>
            <th>Estudiantes</th>
            <th>Total participantes</th>
            <th>Porcentaje</th>
        </tr>
    ';


    foreach (
        $accesibilidad_data
        as $item
    ) {

        echo '
        <tr>

            <td>' .
            htmlspecialchars(
                $item['nombre']
            ) .
            '</td>

            <td>' .
            (int) $item['total'] .
            '</td>

            <td>' .
            $total_estudiantes .
            '</td>

            <td>' .
            number_format(
                $item['porcentaje'],
                2
            ) .
            '%</td>

        </tr>
        ';
    }


    echo '
    </table>
    ';


    // =================================================
    // ESTÁNDARES
    // =================================================

    echo '
    <h2>
        10. Estándares utilizados
    </h2>

    <p>
        Los criterios mostrados corresponden a las
        funciones de accesibilidad utilizadas por los
        participantes de la prueba.
    </p>


    <table border="1">

        <tr class="seccion-morada">
            <th>Norma</th>
            <th>Criterio</th>
            <th>Nombre</th>
            <th>Principio</th>
            <th>Nivel</th>
            <th>Módulo</th>
            <th>Funcionalidad utilizada</th>
            <th>Descripción</th>
            <th>Referencia</th>
        </tr>
    ';


    if (
        empty(
            $estandares
        )
    ) {

        echo '
        <tr>
            <td colspan="9">
                Los participantes todavía no han utilizado
                funciones asociadas a criterios de accesibilidad.
            </td>
        </tr>
        ';

    } else {

        foreach (
            $estandares
            as $estandar
        ) {

            echo '
            <tr>

                <td>' .
                htmlspecialchars(
                    $estandar['norma']
                    ??
                    ''
                ) .
                '</td>

                <td>' .
                htmlspecialchars(
                    $estandar['criterio']
                    ??
                    ''
                ) .
                '</td>

                <td>' .
                htmlspecialchars(
                    $estandar['nombre']
                    ??
                    ''
                ) .
                '</td>

                <td>' .
                htmlspecialchars(
                    $estandar['principio']
                    ??
                    ''
                ) .
                '</td>

                <td>' .
                htmlspecialchars(
                    $estandar['nivel']
                    ??
                    ''
                ) .
                '</td>

                <td>' .
                htmlspecialchars(
                    $estandar['modulo']
                    ??
                    ''
                ) .
                '</td>

                <td>' .
                htmlspecialchars(
                    $estandar['funcionalidad']
                    ??
                    ''
                ) .
                '</td>

                <td>' .
                htmlspecialchars(
                    $estandar['descripcion']
                    ??
                    ''
                ) .
                '</td>

                <td>' .
                htmlspecialchars(
                    $estandar['referencia_oficial']
                    ??
                    ''
                ) .
                '</td>

            </tr>
            ';
        }
    }


    echo '
    </table>


    <p>
        <strong>Nota:</strong>
        Los porcentajes se calcularon utilizando
        como base los participantes registrados
        en la prueba activa.
    </p>


    </body>
    </html>
    ';


    exit;
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

    <title>
        Reportes de investigación - Investigador
    </title>


    <link
        rel="stylesheet"
        href="styles/reportes.css"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >


    <link
        rel="stylesheet"
        href="../Accesibilidad/accesibilidad.css"
    >


    <script>

        window.idUsuario =
            <?php echo $idUsuario; ?>;

    </script>


    <style>

        .reporte-seccion-detalle {
            background:
                #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius:
                16px;

            padding:
                20px;

            margin-bottom:
                20px;
        }


        .reporte-seccion-detalle h3 {
            margin:
                0 0 16px;

            color:
                #172033;

            display:
                flex;

            align-items:
                center;

            gap:
                8px;
        }


        .tabla-reporte-contenedor {
            width:
                100%;

            overflow-x:
                auto;
        }


        .tabla-reporte {
            width:
                100%;

            border-collapse:
                collapse;

            min-width:
                650px;
        }


        .tabla-reporte th {
            background:
                #f1f5f9;

            color:
                #334155;

            text-align:
                left;

            padding:
                11px;

            font-size:
                12px;

            border-bottom:
                1px solid #cbd5e1;
        }


        .tabla-reporte td {
            padding:
                11px;

            font-size:
                12px;

            color:
                #475569;

            border-bottom:
                1px solid #e2e8f0;
        }


        .porcentaje-positivo {
            font-weight:
                800;

            color:
                #2e7d32;
        }


        .porcentaje-alerta {
            font-weight:
                800;

            color:
                #c62828;
        }


        .datos-prueba-grid {
            display:
                grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap:
                12px;
        }


        .dato-prueba {
            padding:
                13px;

            background:
                #f8fafc;

            border:
                1px solid #e2e8f0;

            border-radius:
                11px;
        }


        .dato-prueba-label {
            display:
                block;

            color:
                #64748b;

            font-size:
                11px;

            margin-bottom:
                4px;
        }


        .dato-prueba-valor {
            display:
                block;

            color:
                #172033;

            font-size:
                14px;

            font-weight:
                700;
        }


        .resumen-porcentajes-grid {
            display:
                grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(
                        180px,
                        1fr
                    )
                );

            gap:
                12px;
        }


        .porcentaje-card {
            background:
                #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius:
                14px;

            padding:
                15px;
        }


        .porcentaje-card span {
            display:
                block;
        }


        .porcentaje-card .nombre {
            color:
                #64748b;

            font-size:
                11px;
        }


        .porcentaje-card .valor {
            margin-top:
                5px;

            color:
                #3b71f3;

            font-size:
                22px;

            font-weight:
                900;
        }


        @media (
            max-width: 700px
        ) {

            .datos-prueba-grid {
                grid-template-columns:
                    1fr;
            }

        }

    </style>

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


        <!-- =================================================
             PRUEBA ACTIVA
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

                    Prueba analizada:

                    <strong>

                        <?php
                            echo htmlspecialchars(
                                $prueba_activa_nombre
                            );
                        ?>

                    </strong>


                    — <?php echo htmlspecialchars($version_wcag); ?>

                </span>

            </div>


        <?php else: ?>


            <div
                style="
                    background:#fef3c7;
                    border:1px solid #f59e0b;
                    border-radius:12px;
                    padding:14px 20px;
                    margin-bottom:20px;
                "
            >

                <strong>
                    No existe una prueba activa.
                </strong>

                Activa una prueba para generar un reporte
                de investigación asociado a sus participantes.

            </div>


        <?php endif; ?>


        <!-- =================================================
             PERIODO
        ================================================= -->

        <div class="periodo-selector">

            <div class="periodo-info">

                <i class="fa-solid fa-calendar"></i>

                <div>

                    <span class="periodo-etiqueta">
                        Periodo del reporte
                    </span>

                    <span class="periodo-valor">

                        <?php
                            echo htmlspecialchars(
                                $periodo_reporte
                            );
                        ?>

                    </span>

                </div>

            </div>

        </div>


        <!-- =================================================
             DATOS DE LA PRUEBA
        ================================================= -->

        <section class="reporte-seccion-detalle">

            <h3>

                <i class="fa-solid fa-flask"></i>

                Datos de la prueba

            </h3>


            <div class="datos-prueba-grid">


                <div class="dato-prueba">

                    <span class="dato-prueba-label">
                        Nombre / principio
                    </span>

                    <span class="dato-prueba-valor">
                        <?php echo htmlspecialchars($prueba_activa_nombre); ?>
                    </span>

                </div>


                <div class="dato-prueba">

                    <span class="dato-prueba-label">
                        Versión WCAG
                    </span>

                    <span class="dato-prueba-valor">
                        <?php echo htmlspecialchars($version_wcag); ?>
                    </span>

                </div>


                <div class="dato-prueba">

                    <span class="dato-prueba-label">
                        Estado
                    </span>

                    <span class="dato-prueba-valor">
                        <?php echo htmlspecialchars($estado_prueba); ?>
                    </span>

                </div>


                <div class="dato-prueba">

                    <span class="dato-prueba-label">
                        Participantes
                    </span>

                    <span class="dato-prueba-valor">
                        <?php echo $total_estudiantes; ?>
                    </span>

                </div>


                <div class="dato-prueba">

                    <span class="dato-prueba-label">
                        Consentimientos
                    </span>

                    <span class="dato-prueba-valor">

                        <?php echo $total_consentimientos; ?>
                        de
                        <?php echo $total_estudiantes; ?>

                        (
                        <?php
                            echo number_format(
                                $porcentaje_consentimiento,
                                2
                            );
                        ?>%
                        )

                    </span>

                </div>


                <div class="dato-prueba">

                    <span class="dato-prueba-label">
                        Periodo
                    </span>

                    <span class="dato-prueba-valor">
                        <?php echo htmlspecialchars($periodo_reporte); ?>
                    </span>

                </div>


            </div>


            <div
                style="
                    margin-top:15px;
                    padding:14px;
                    background:#f8fafc;
                    border-radius:12px;
                "
            >

                <strong>
                    Hipótesis
                </strong>

                <p>
                    <?php echo htmlspecialchars($hipotesis_prueba); ?>
                </p>


                <strong>
                    Objetivo
                </strong>

                <p>
                    <?php echo htmlspecialchars($objetivo_prueba); ?>
                </p>

            </div>

        </section>


        <!-- =================================================
             RESUMEN EN PORCENTAJES
        ================================================= -->

        <section class="reporte-seccion-detalle">

            <h3>

                <i class="fa-solid fa-chart-pie"></i>

                Resumen general de resultados

            </h3>


            <div class="resumen-porcentajes-grid">

                <?php foreach ($resumen_porcentajes as $item): ?>

                    <div class="porcentaje-card">

                        <span class="nombre">
                            <?php echo htmlspecialchars($item['nombre']); ?>
                        </span>

                        <span class="valor">
                            <?php echo number_format($item['valor'], 2); ?>%
                        </span>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>


        <!-- =================================================
             USO DE PLATAFORMA
        ================================================= -->

        <section class="reporte-seccion-detalle">

            <h3>

                <i class="fa-solid fa-chart-simple"></i>

                Uso de la plataforma

            </h3>


            <p>

                <strong>
                    <?php echo $usuarios_con_actividad; ?>
                    de
                    <?php echo $total_estudiantes; ?>
                </strong>

                participantes registraron actividad.

                Participación:

                <strong>
                    <?php echo number_format($porcentaje_uso_plataforma, 2); ?>%
                </strong>

            </p>


            <p>
                Accesos:
                <strong><?php echo $total_accesos; ?></strong>

                · Promedio:
                <strong><?php echo $promedio_accesos; ?></strong>

                · Eventos:
                <strong><?php echo $total_eventos_plataforma; ?></strong>
            </p>


            <div class="tabla-reporte-contenedor">

                <table class="tabla-reporte">

                    <thead>

                        <tr>

                            <th>Módulo</th>

                            <th>Visitas</th>

                            <th>Usuarios</th>

                            <th>% visitas</th>

                            <th>% participantes</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($modulos_visitados as $modulo): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($modulo['modulo']); ?>
                            </td>

                            <td>
                                <?php echo (int) $modulo['visitas']; ?>
                            </td>

                            <td>
                                <?php echo (int) $modulo['usuarios']; ?>
                            </td>

                            <td>
                                <?php
                                    echo number_format(
                                        porcentaje(
                                            $modulo['visitas'],
                                            $total_visitas_modulos
                                        ),
                                        2
                                    );
                                ?>%
                            </td>

                            <td>
                                <?php
                                    echo number_format(
                                        porcentaje(
                                            $modulo['usuarios'],
                                            $total_estudiantes
                                        ),
                                        2
                                    );
                                ?>%
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- =================================================
             TIEMPOS
        ================================================= -->

        <section class="reporte-seccion-detalle">

            <h3>

                <i class="fa-solid fa-clock"></i>

                Tiempos de actividades

            </h3>


            <p>

                Tiempo promedio general:

                <strong>
                    <?php echo htmlspecialchars($tiempo_promedio); ?>
                </strong>

            </p>


            <div class="tabla-reporte-contenedor">

                <table class="tabla-reporte">

                    <thead>

                        <tr>

                            <th>Actividad</th>

                            <th>Estudiantes</th>

                            <th>Entregas</th>

                            <th>Tiempo promedio</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($actividades_tiempos as $actividad): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($actividad['titulo']); ?>
                            </td>

                            <td>
                                <?php echo (int) $actividad['estudiantes']; ?>
                            </td>

                            <td>
                                <?php echo (int) $actividad['entregas']; ?>
                            </td>

                            <td>
                                <?php echo formatearTiempo($actividad['promedio']); ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- =================================================
             JUEGOS
        ================================================= -->

        <?php if ($tieneJuegos): ?>

        <section class="reporte-seccion-detalle">

            <h3>

                <i class="fa-solid fa-gamepad"></i>

                Juegos y gamificación

            </h3>


            <p>

                Participantes:

                <strong>
                    <?php echo $estudiantes_juegos; ?>
                    de
                    <?php echo $total_estudiantes; ?>
                    (<?php echo number_format($porcentaje_uso_juegos, 2); ?>%)
                </strong>

            </p>


            <p>

                Intentos:
                <strong><?php echo $total_intentos_juegos; ?></strong>

                · Tiempo promedio:
                <strong><?php echo formatearTiempo($tiempo_promedio_juegos_seg); ?></strong>

                · Resultado:
                <strong><?php echo number_format($porcentaje_promedio_juegos, 2); ?>%</strong>

            </p>


            <div class="tabla-reporte-contenedor">

                <table class="tabla-reporte">

                    <thead>

                        <tr>

                            <th>Juego</th>

                            <th>Modo</th>

                            <th>Participantes</th>

                            <th>Intentos</th>

                            <th>Tiempo</th>

                            <th>Resultado</th>

                            <th>Aciertos</th>

                            <th>Errores</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($juegos_detalle as $juego): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($juego['titulo']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($juego['modo']); ?>
                            </td>

                            <td>
                                <?php echo (int) $juego['estudiantes']; ?>
                            </td>

                            <td>
                                <?php echo (int) $juego['intentos']; ?>
                            </td>

                            <td>
                                <?php echo formatearTiempo($juego['tiempo_promedio']); ?>
                            </td>

                            <td>
                                <?php echo number_format($juego['porcentaje_promedio'], 2); ?>%
                            </td>

                            <td>
                                <?php echo (int) $juego['aciertos']; ?>
                            </td>

                            <td>
                                <?php echo (int) $juego['errores']; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>

        <?php endif; ?>


        <!-- =================================================
             ERRORES
        ================================================= -->

        <section class="reporte-seccion-detalle">

            <h3>

                <i class="fa-solid fa-triangle-exclamation"></i>

                Errores de navegación

            </h3>


            <p>

                Errores totales:

                <strong>
                    <?php echo $total_errores; ?>
                </strong>

                · Participantes afectados:

                <strong>
                    <?php echo $estudiantes_con_errores; ?>
                    de
                    <?php echo $total_estudiantes; ?>

                    (
                    <?php echo number_format($porcentaje_usuarios_error, 2); ?>%
                    )
                </strong>

            </p>


            <div class="tabla-reporte-contenedor">

                <table class="tabla-reporte">

                    <thead>

                        <tr>

                            <th>Tipo de error</th>

                            <th>Cantidad</th>

                            <th>Porcentaje</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($tipos_error as $error): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($error['accion']); ?>
                            </td>

                            <td>
                                <?php echo (int) $error['total']; ?>
                            </td>

                            <td class="porcentaje-alerta">

                                <?php
                                    echo number_format(
                                        porcentaje(
                                            $error['total'],
                                            $total_errores
                                        ),
                                        2
                                    );
                                ?>%

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- =================================================
             CHATBOT
        ================================================= -->

        <section class="reporte-seccion-detalle">

            <h3>

                <i class="fa-solid fa-robot"></i>

                Uso del chatbot

            </h3>


            <p>

                Usuarios:

                <strong>
                    <?php echo $usuarios_chatbot; ?>
                    de
                    <?php echo $total_estudiantes; ?>

                    (
                    <?php echo number_format($porcentaje_uso_chatbot, 2); ?>%
                    )
                </strong>

            </p>


            <p>

                Sesiones:
                <strong><?php echo $total_sesiones_chatbot; ?></strong>

                · Mensajes:
                <strong><?php echo $total_chatbot; ?></strong>

                · Promedio:
                <strong><?php echo $promedio_mensajes_chatbot; ?></strong>

                · Duración:
                <strong><?php echo formatearTiempo($duracion_chatbot_seg); ?></strong>

            </p>


            <div class="tabla-reporte-contenedor">

                <table class="tabla-reporte">

                    <thead>

                        <tr>

                            <th>Tipo de consulta</th>

                            <th>Cantidad</th>

                            <th>Porcentaje</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($tipos_consulta as $consulta): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($consulta['tipo_consulta']); ?>
                            </td>

                            <td>
                                <?php echo (int) $consulta['total']; ?>
                            </td>

                            <td>
                                <?php
                                    echo number_format(
                                        porcentaje(
                                            $consulta['total'],
                                            $total_chatbot
                                        ),
                                        2
                                    );
                                ?>%
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- =================================================
             PROGRESO
        ================================================= -->

        <section class="reporte-seccion-detalle">

            <h3>

                <i class="fa-solid fa-chart-line"></i>

                Progreso académico

            </h3>


            <p>

                Progreso promedio:

                <strong>
                    <?php echo number_format($progreso_promedio, 2); ?>%
                </strong>

            </p>


            <p>

                Actividades:

                <strong>
                    <?php echo $actividades_completadas; ?>
                    /
                    <?php echo $total_actividades; ?>

                    (
                    <?php echo number_format($porcentaje_actividades, 2); ?>%
                    )
                </strong>

                · Evaluaciones:

                <strong>
                    <?php echo $evaluaciones_realizadas; ?>
                    /
                    <?php echo $total_evaluaciones; ?>

                    (
                    <?php echo number_format($porcentaje_evaluaciones, 2); ?>%
                    )
                </strong>

            </p>


            <div class="tabla-reporte-contenedor">

                <table class="tabla-reporte">

                    <thead>

                        <tr>

                            <th>Alumno</th>

                            <th>Progreso</th>

                            <th>Completadas</th>

                            <th>Total actividades</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($progreso_estudiantes as $estudiante): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($estudiante['alumno']); ?>
                            </td>

                            <td class="porcentaje-positivo">
                                <?php echo number_format($estudiante['progreso'], 2); ?>%
                            </td>

                            <td>
                                <?php echo (int) $estudiante['completadas']; ?>
                            </td>

                            <td>
                                <?php echo (int) $estudiante['actividades']; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- =================================================
             ACCESIBILIDAD
        ================================================= -->

        <section class="estadisticas-accesibilidad">

            <h3>

                <i class="fa-solid fa-universal-access"></i>

                Estadísticas de accesibilidad

            </h3>


            <p>

                <strong>
                    <?php echo $usan_accesibilidad; ?>
                    de
                    <?php echo $total_estudiantes; ?>
                </strong>

                participantes utilizan al menos una función.

                <strong>
                    <?php echo number_format($porcentaje_accesibilidad, 2); ?>%
                </strong>

            </p>


            <div class="tarjeta-estadisticas">

                <?php foreach ($accesibilidad_data as $item): ?>

                    <div class="estadistica-item">

                        <div class="estadistica-encabezado">

                            <span class="estadistica-titulo">
                                <?php echo htmlspecialchars($item['nombre']); ?>
                            </span>

                            <span class="estadistica-texto">

                                <?php echo $item['total']; ?>
                                de
                                <?php echo $total_estudiantes; ?>

                                (
                                <?php echo number_format($item['porcentaje'], 2); ?>%
                                )

                            </span>

                        </div>


                        <div class="barra-fondo">

                            <div
                                class="barra-llena"
                                style="
                                    width:
                                    <?php echo min(100, $item['porcentaje']); ?>%;
                                "
                            ></div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>

<!-- =================================================
     ESTÁNDARES REALMENTE UTILIZADOS
================================================= -->

<section class="reporte-seccion-detalle">

    <h3>

        <i class="fa-solid fa-shield-halved"></i>

        Estándares utilizados

    </h3>


    <p>

        Estos criterios corresponden a las funciones de
        accesibilidad realmente utilizadas por los participantes de:

        <strong>
            <?php
                echo htmlspecialchars(
                    $prueba_activa_nombre
                );
            ?>
        </strong>

    </p>


    <div class="tabla-reporte-contenedor">

        <table class="tabla-reporte">

            <thead>

                <tr>

                    <th>Norma</th>

                    <th>Criterio</th>

                    <th>Nombre</th>

                    <th>Principio</th>

                    <th>Nivel</th>

                    <th>Módulo</th>

                    <th>Funcionalidad utilizada</th>

                    <th>Descripción</th>

                </tr>

            </thead>


            <tbody>

            <?php if (empty($estandares)): ?>

                <tr>

                    <td colspan="8">

                        Los participantes todavía no han utilizado
                        funciones asociadas a criterios de accesibilidad.

                    </td>

                </tr>

            <?php else: ?>


                <?php foreach ($estandares as $estandar): ?>

                    <tr>

                        <td>

                            <?php
                                echo htmlspecialchars(
                                    $estandar['norma']
                                );
                            ?>

                        </td>


                        <td>

                            <strong>

                                <?php
                                    echo htmlspecialchars(
                                        $estandar['criterio']
                                    );
                                ?>

                            </strong>

                        </td>


                        <td>

                            <?php
                                echo htmlspecialchars(
                                    $estandar['nombre']
                                );
                            ?>

                        </td>


                        <td>

                            <?php
                                echo htmlspecialchars(
                                    $estandar['principio']
                                    ??
                                    'Sin especificar'
                                );
                            ?>

                        </td>


                        <td>

                            <?php
                                echo htmlspecialchars(
                                    $estandar['nivel']
                                    ??
                                    'Sin especificar'
                                );
                            ?>

                        </td>


                        <td>

                            <?php
                                echo htmlspecialchars(
                                    $estandar['modulo']
                                    ??
                                    'Sin módulo'
                                );
                            ?>

                        </td>


                        <td>

                            <?php
                                echo htmlspecialchars(
                                    $estandar['funcionalidad']
                                    ??
                                    'Sin funcionalidad'
                                );
                            ?>

                        </td>


                        <td>

                            <?php
                                echo htmlspecialchars(
                                    $estandar['descripcion']
                                    ??
                                    ''
                                );
                            ?>

                        </td>

                    </tr>

                <?php endforeach; ?>


            <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>
        <!-- =================================================
             EXPORTAR
        ================================================= -->

        <section class="exportar-reporte">

            <h3>

                <i class="fa-solid fa-download"></i>

                Exportar reporte completo

            </h3>


            <p class="exportar-descripcion">

                El archivo incluirá los datos de la prueba,
                porcentajes, uso de plataforma, tiempos,
                juegos, errores, chatbot, progreso,
                accesibilidad y estándares.

            </p>


            <div
                class="boton-exportar"
                onclick="window.location.href='?exportar=csv'"
            >

                <div class="exportar-icono">

                    <i class="fa-solid fa-file-csv"></i>

                </div>


                <div class="exportar-contenido">

                    <span class="exportar-titulo">
                        Exportar CSV
                    </span>

                    <span class="exportar-desc">
                        Reporte detallado compatible con hojas de cálculo
                    </span>

                </div>


                <i class="fa-solid fa-download"></i>

            </div>


            <div
                class="boton-exportar principal"
                onclick="window.location.href='?exportar=excel'"
            >

                <div class="exportar-icono">

                    <i class="fa-solid fa-file-excel"></i>

                </div>


                <div class="exportar-contenido">

                    <span class="exportar-titulo">
                        Exportar Excel completo
                    </span>

                    <span class="exportar-desc">
                        Reporte organizado por secciones de investigación
                    </span>

                </div>


                <i class="fa-solid fa-download"></i>

            </div>

        </section>


        <!-- =================================================
             AVISO
        ================================================= -->

        <div class="aviso-investigador">

            <i class="fa-solid fa-circle-info"></i>

            <p>

                Los resultados corresponden únicamente a los
                participantes de la prueba

                <strong>
                    <?php echo htmlspecialchars($prueba_activa_nombre); ?>
                </strong>

                durante el periodo

                <strong>
                    <?php echo htmlspecialchars($periodo_reporte); ?>
                </strong>.

            </p>

        </div>


        <?php
            include '../Accesibilidad/accesibilidad.php';
        ?>


    </main>

</div>


<!-- =====================================================
     BOTÓN ACCESIBILIDAD
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

<script src="js/reportes.js"></script>

<script src="../Accesibilidad/accesibilidad.js"></script>

<script src="../Accesibilidad/navegacionTeclado.js"></script>


</body>

</html>