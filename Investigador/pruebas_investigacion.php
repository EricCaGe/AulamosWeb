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
// DATOS DEL USUARIO
// =====================================================

$idUsuario =
    (int) $_SESSION['usuario']['id_usuario'];


// =====================================================
// CONEXIÓN
// =====================================================

require_once '../Conexion/conexion.php';


$titulo_pagina =
    'Pruebas de investigación';


$descripcion_pagina =
    'Selecciona una prueba para gestionar sus participantes.';


// =====================================================
// MENSAJES
// =====================================================

$mensajeExito =
    '';


$mensajeError =
    '';


// =====================================================
// CREAR NUEVA PRUEBA
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['accion']) &&
    $_POST['accion'] === 'crear_prueba'
) {

    // =================================================
    // RECIBIR DATOS
    // =================================================

    $nombre =
        trim(
            $_POST['nombre'] ?? ''
        );


    $descripcion =
        trim(
            $_POST['descripcion'] ?? ''
        );


    $hipotesis =
        trim(
            $_POST['hipotesis'] ?? ''
        );


    $objetivo =
        trim(
            $_POST['objetivo'] ?? ''
        );


    $versionWcag =
        trim(
            $_POST['version_wcag'] ?? 'WCAG 2.1'
        );


    $fechaInicio =
        trim(
            $_POST['fecha_inicio'] ?? ''
        );


    $fechaFin =
        trim(
            $_POST['fecha_fin'] ?? ''
        );


    // =================================================
    // PRINCIPIOS PERMITIDOS
    // =================================================

    $principiosPermitidos = [
        'Perceptible',
        'Operable',
        'Comprensible',
        'Robusto'
    ];


    // =================================================
    // VALIDACIONES
    // =================================================

    if (
        empty($nombre) ||
        !in_array(
            $nombre,
            $principiosPermitidos,
            true
        )
    ) {

        $mensajeError =
            'Selecciona un principio WCAG 2.1 válido.';

    } elseif (
        empty($hipotesis)
    ) {

        $mensajeError =
            'La hipótesis es obligatoria.';

    } elseif (
        empty($fechaInicio)
    ) {

        $mensajeError =
            'La fecha de inicio es obligatoria.';

    } elseif (
        !empty($fechaFin) &&
        $fechaFin < $fechaInicio
    ) {

        $mensajeError =
            'La fecha de fin no puede ser anterior a la fecha de inicio.';

    } else {

        try {

            // =============================================
            // FECHA FIN NULL
            // =============================================

            $fechaFinBD =
                !empty($fechaFin)
                    ? $fechaFin
                    : null;


            // =============================================
            // INSERTAR PRUEBA
            // =============================================

            $stmtCrear =
                $conexion->prepare("
                    INSERT INTO pruebas_investigacion
                    (
                        nombre,
                        descripcion,
                        hipotesis,
                        objetivo,
                        version_wcag,
                        fecha_inicio,
                        fecha_fin,
                        estado
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'Planeada'
                    )
                ");


            if (
                !$stmtCrear
            ) {

                throw new Exception(
                    'No se pudo preparar la consulta.'
                );
            }


            $stmtCrear->bind_param(
                'sssssss',
                $nombre,
                $descripcion,
                $hipotesis,
                $objetivo,
                $versionWcag,
                $fechaInicio,
                $fechaFinBD
            );


            if (
                !$stmtCrear->execute()
            ) {

                throw new Exception(
                    'No se pudo guardar la prueba.'
                );
            }


            $stmtCrear->close();


            // =============================================
            // REDIRECCIÓN PARA EVITAR REENVÍO DEL FORMULARIO
            // =============================================

            header(
                'Location: pruebas_investigacion.php?creada=1'
            );

            exit;

        } catch (
            Throwable $error
        ) {

            $mensajeError =
                'No se pudo crear la prueba: ' .
                $error->getMessage();
        }
    }
}


// =====================================================
// MENSAJE DE CREACIÓN
// =====================================================

if (
    isset($_GET['creada']) &&
    $_GET['creada'] === '1'
) {

    $mensajeExito =
        'La prueba de investigación se creó correctamente.';
}


// =====================================================
// OBTENER PRUEBAS
// =====================================================

$stmt =
    $conexion->prepare("
        SELECT

            p.*,

            (
                SELECT COUNT(*)

                FROM participantes_prueba pp

                WHERE pp.id_prueba = p.id_prueba

            ) AS participantes,

            (
                SELECT COUNT(*)

                FROM participantes_prueba pp2

                WHERE pp2.id_prueba = p.id_prueba

                AND pp2.consentimiento = 1

            ) AS consentimientos

        FROM pruebas_investigacion p

        ORDER BY p.fecha_inicio DESC
    ");


$stmt->execute();


$resultado =
    $stmt->get_result();


$pruebas =
    $resultado->fetch_all(
        MYSQLI_ASSOC
    );


$stmt->close();


// =====================================================
// FUNCIONES
// =====================================================

function badgeEstado(
    $estado
) {

    switch (
        $estado
    ) {

        case 'Activa':

            return 'badge-activa';


        case 'Finalizada':

            return 'badge-finalizada';


        default:

            return 'badge-planeada';
    }
}


function formatoFecha(
    $fecha
) {

    if (
        !$fecha
    ) {

        return 'Sin fecha';
    }


    $timestamp =
        strtotime(
            $fecha
        );


    return date(
        'd/m/Y',
        $timestamp
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


    <title>
        Pruebas de investigación - Investigador
    </title>


    <link
        rel="stylesheet"
        href="styles/pruebas_investigacion.css"
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


    <!-- =================================================
         ESTILOS DEL MODAL Y SELECTOR WCAG
    ================================================= -->

    <style>

        /* ==============================================
           ENCABEZADO
        ============================================== */

        .pruebas-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 22px;
        }


        .header-info {
            flex: 1;
        }


        .btn-nueva-prueba {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 8px;

            min-height: 44px;

            padding:
                10px 18px;

            border: none;
            border-radius: 11px;

            background: #2D5BFF;

            color: #FFFFFF;

            font-size: 14px;
            font-weight: 700;

            cursor: pointer;

            transition:
                background 0.2s,
                transform 0.2s;
        }


        .btn-nueva-prueba:hover {
            background: #2449D8;

            transform:
                translateY(-1px);
        }


        /* ==============================================
           MENSAJES
        ============================================== */

        .mensaje-prueba {
            display: flex;
            align-items: center;

            gap: 10px;

            padding: 13px 15px;

            margin-bottom: 18px;

            border-radius: 12px;

            font-size: 14px;
            font-weight: 600;
        }


        .mensaje-exito {
            background: #DCFCE7;

            border: 1px solid #86EFAC;

            color: #166534;
        }


        .mensaje-error {
            background: #FEE2E2;

            border: 1px solid #FCA5A5;

            color: #991B1B;
        }


        /* ==============================================
           MODAL
        ============================================== */

        .modal-prueba {
            position: fixed;

            inset: 0;

            z-index: 9999;

            display: none;

            align-items: center;
            justify-content: center;

            padding: 20px;

            background:
                rgba(
                    15,
                    23,
                    42,
                    0.55
                );
        }


        .modal-prueba.activo {
            display: flex;
        }


        .modal-contenido {
            width: 100%;

            max-width: 650px;

            max-height: 92vh;

            overflow-y: auto;

            background: #FFFFFF;

            border-radius: 20px;

            box-shadow:
                0 24px 60px
                rgba(
                    15,
                    23,
                    42,
                    0.25
                );
        }


        .modal-header {
            display: flex;

            justify-content: space-between;
            align-items: flex-start;

            gap: 15px;

            padding: 20px;

            border-bottom:
                1px solid #E2E8F0;
        }


        .modal-header h2 {
            margin: 0;

            color: #172033;

            font-size: 22px;
        }


        .modal-header p {
            margin:
                5px 0 0;

            color: #64748B;

            font-size: 13px;

            line-height: 19px;
        }


        .btn-cerrar-modal {
            width: 40px;
            height: 40px;

            flex-shrink: 0;

            display: flex;

            align-items: center;
            justify-content: center;

            border: none;

            border-radius: 10px;

            background: #F1F5F9;

            color: #475569;

            font-size: 18px;

            cursor: pointer;
        }


        .form-prueba {
            padding: 20px;
        }


        /* ==============================================
           CAMPOS
        ============================================== */

        .form-group {
            margin-bottom: 17px;
        }


        .form-group label {
            display: block;

            margin-bottom: 7px;

            color: #334155;

            font-size: 13px;
            font-weight: 700;
        }


        .form-control {
            width: 100%;

            min-height: 48px;

            box-sizing: border-box;

            padding:
                10px 13px;

            border:
                1px solid #CBD5E1;

            border-radius: 11px;

            background: #FFFFFF;

            color: #172033;

            font-size: 14px;

            outline: none;

            transition:
                border-color 0.2s,
                box-shadow 0.2s;
        }


        .form-control:focus {
            border-color: #2D5BFF;

            box-shadow:
                0 0 0 3px
                rgba(
                    45,
                    91,
                    255,
                    0.14
                );
        }


        textarea.form-control {
            min-height: 95px;

            resize: vertical;
        }


        /* ==============================================
           SELECT WCAG
        ============================================== */

        .select-principio-contenedor {
            position: relative;
        }


        .select-principio {
            appearance: none;
            -webkit-appearance: none;

            cursor: pointer;

            padding-right: 44px;

            font-weight: 700;
        }


        .icono-select {
            position: absolute;

            right: 15px;
            top: 50%;

            transform:
                translateY(-50%);

            color: #64748B;

            pointer-events: none;
        }


        .descripcion-principio {
            display: none;

            margin-top: 9px;

            padding: 12px 14px;

            background: #EEF2FF;

            border:
                1px solid #C7D2FE;

            border-radius: 11px;

            color: #475569;

            font-size: 12px;

            line-height: 18px;
        }


        .descripcion-principio.activo {
            display: block;
        }


        .descripcion-principio strong {
            display: block;

            margin-bottom: 3px;

            color: #2D5BFF;

            font-size: 13px;
        }


        .ayuda-campo {
            display: block;

            margin-top: 6px;

            color: #64748B;

            font-size: 11px;

            line-height: 16px;
        }


        /* ==============================================
           ESTADO
        ============================================== */

        .estado-planeada-modal {
            display: inline-flex;

            align-items: center;

            gap: 7px;

            min-height: 38px;

            padding:
                0 13px;

            background: #FFFBEB;

            border:
                1px solid #FCD34D;

            border-radius: 999px;

            color: #B45309;

            font-size: 13px;
            font-weight: 700;
        }


        .estado-planeada-modal::before {
            content: '';

            width: 8px;
            height: 8px;

            border-radius: 50%;

            background: #D97706;
        }


        /* ==============================================
           FECHAS
        ============================================== */

        .fechas-grid {
            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 12px;
        }


        /* ==============================================
           BOTONES MODAL
        ============================================== */

        .modal-acciones {
            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 20px;

            padding-top: 18px;

            border-top:
                1px solid #E2E8F0;
        }


        .btn-cancelar {
            min-height: 46px;

            padding:
                0 18px;

            border:
                1px solid #CBD5E1;

            border-radius: 11px;

            background: #FFFFFF;

            color: #475569;

            font-weight: 700;

            cursor: pointer;
        }


        .btn-guardar-prueba {
            min-height: 46px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 7px;

            padding:
                0 20px;

            border: none;

            border-radius: 11px;

            background: #2D5BFF;

            color: #FFFFFF;

            font-weight: 700;

            cursor: pointer;
        }


        .btn-guardar-prueba:hover {
            background: #2449D8;
        }


        /* ==============================================
           ACCIONES DE TARJETAS
        ============================================== */

        .prueba-acciones {
            display: flex;

            flex-wrap: wrap;

            align-items: center;

            gap: 10px;

            margin-top: 16px;
        }


        .form-estado-prueba {
            margin: 0;
        }


        .btn-ver-participantes {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 8px;

            min-height: 42px;

            box-sizing: border-box;

            padding:
                10px 20px;

            background: #F1F5F9;

            border:
                1px solid #E2E8F0;

            border-radius: 10px;

            color: #475569;

            text-decoration: none;

            font-weight: 600;

            font-size: 14px;

            transition:
                background 0.2s;
        }


        .btn-ver-participantes:hover {
            background: #E2E8F0;
        }


        /* ==============================================
           RESPONSIVE
        ============================================== */

        @media (
            max-width: 700px
        ) {

            .pruebas-header {
                flex-direction: column;

                align-items: stretch;
            }


            .btn-nueva-prueba {
                width: 100%;
            }


            .fechas-grid {
                grid-template-columns: 1fr;
            }


            .modal-prueba {
                align-items: flex-end;

                padding: 0;
            }


            .modal-contenido {
                max-height: 94vh;

                border-radius:
                    20px 20px 0 0;
            }


            .modal-acciones {
                flex-direction: column-reverse;
            }


            .btn-cancelar,
            .btn-guardar-prueba {
                width: 100%;
            }


            .prueba-acciones {
                flex-direction: column;

                align-items: stretch;
            }


            .form-estado-prueba,
            .btn-cambiar-estado,
            .btn-ver-participantes {
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
             MENSAJES
        ================================================= -->

        <?php if (!empty($mensajeExito)): ?>

            <div
                class="mensaje-prueba mensaje-exito"
                role="status"
            >

                <i class="fa-solid fa-circle-check"></i>


                <span>

                    <?php
                        echo htmlspecialchars(
                            $mensajeExito
                        );
                    ?>

                </span>

            </div>

        <?php endif; ?>


        <?php if (!empty($mensajeError)): ?>

            <div
                class="mensaje-prueba mensaje-error"
                role="alert"
            >

                <i class="fa-solid fa-circle-exclamation"></i>


                <span>

                    <?php
                        echo htmlspecialchars(
                            $mensajeError
                        );
                    ?>

                </span>

            </div>

        <?php endif; ?>


        <!-- =================================================
             ENCABEZADO
        ================================================= -->

        <div class="pruebas-header">


            <div class="header-info">

                <span class="cantidad">

                    <?php echo count($pruebas); ?>

                    <?php

                    echo count($pruebas) === 1
                        ? 'prueba disponible'
                        : 'pruebas disponibles';

                    ?>

                </span>


                <p class="subtitulo">

                    Selecciona una prueba para gestionar
                    sus participantes.

                </p>

            </div>


            <!-- =============================================
                 NUEVA PRUEBA
            ============================================== -->

            <button
                type="button"
                class="btn-nueva-prueba"
                id="btnNuevaPrueba"
            >

                <i class="fa-solid fa-plus"></i>

                Nueva prueba

            </button>

        </div>


        <!-- =================================================
             LISTA DE PRUEBAS
        ================================================= -->

        <section class="pruebas-list">


            <?php if (empty($pruebas)): ?>


                <div class="sin-datos">

                    <i class="fa-solid fa-flask"></i>


                    <p>

                        No hay pruebas de investigación
                        registradas.

                    </p>

                </div>


            <?php else: ?>


                <?php foreach ($pruebas as $prueba): ?>


                    <div
                        class="prueba-card"
                        data-id="<?php echo (int) $prueba['id_prueba']; ?>"
                    >


                        <!-- =================================
                             CABECERA
                        ================================== -->

                        <div class="prueba-header">


                            <div class="prueba-icono">

                                <i class="fa-solid fa-flask"></i>

                            </div>


                            <div class="prueba-info">


                                <h3>

                                    <?php

                                    echo htmlspecialchars(
                                        $prueba['nombre']
                                    );

                                    ?>

                                </h3>


                                <span
                                    class="badge <?php echo badgeEstado($prueba['estado']); ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $prueba['estado']
                                    );

                                    ?>

                                </span>


                            </div>

                        </div>


                        <!-- =================================
                             DATOS
                        ================================== -->

                        <div class="prueba-datos">


                            <div class="dato-item">

                                <i class="fa-solid fa-universal-access"></i>


                                <span>

                                    <?php

                                    echo htmlspecialchars(
                                        $prueba['version_wcag']
                                        ??
                                        'WCAG 2.1'
                                    );

                                    ?>

                                </span>

                            </div>


                            <div class="dato-item">

                                <i class="fa-solid fa-calendar"></i>


                                <span>

                                    <?php

                                    echo formatoFecha(
                                        $prueba['fecha_inicio']
                                    );

                                    ?>

                                    -

                                    <?php

                                    echo formatoFecha(
                                        $prueba['fecha_fin']
                                    );

                                    ?>

                                </span>

                            </div>


                            <div class="dato-item">

                                <i class="fa-solid fa-users"></i>


                                <span>

                                    <?php

                                    echo (int) (
                                        $prueba['participantes']
                                        ??
                                        0
                                    );

                                    ?>

                                    participantes

                                </span>

                            </div>


                            <div class="dato-item">

                                <i class="fa-solid fa-check-circle"></i>


                                <span>

                                    <?php

                                    echo (int) (
                                        $prueba['consentimientos']
                                        ??
                                        0
                                    );

                                    ?>

                                    consentimientos

                                </span>

                            </div>

                        </div>


                        <!-- =================================
                             DETALLE
                        ================================== -->

                        <div class="prueba-detalle">


                            <div class="detalle-bloque">

                                <span class="detalle-label">

                                    Hipótesis

                                </span>


                                <p>

                                    <?php

                                    echo htmlspecialchars(
                                        $prueba['hipotesis']
                                        ??
                                        ''
                                    );

                                    ?>

                                </p>

                            </div>


                            <?php
                            if (
                                !empty(
                                    $prueba['objetivo']
                                )
                            ):
                            ?>


                                <div class="detalle-bloque">

                                    <span class="detalle-label">

                                        Objetivo

                                    </span>


                                    <p>

                                        <?php

                                        echo htmlspecialchars(
                                            $prueba['objetivo']
                                        );

                                        ?>

                                    </p>

                                </div>


                            <?php endif; ?>


                        </div>


                        <!-- =================================
                             ACCIONES
                        ================================== -->

                        <div class="prueba-acciones">


                            <?php
                            if (
                                $prueba['estado'] ===
                                'Activa'
                            ):
                            ?>


                                <form
                                    method="POST"
                                    action="logica/cambiar_estado_prueba.php"
                                    class="form-estado-prueba"
                                >

                                    <input
                                        type="hidden"
                                        name="id_prueba"
                                        value="<?php echo (int) $prueba['id_prueba']; ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="estado"
                                        value="Finalizada"
                                    >


                                    <button
                                        type="submit"
                                        class="btn-cambiar-estado btn-finalizar"
                                        onclick="return confirm('¿Finalizar esta prueba?')"
                                    >

                                        <i class="fa-solid fa-stop-circle"></i>

                                        Finalizar prueba

                                    </button>

                                </form>


                            <?php else: ?>


                                <form
                                    method="POST"
                                    action="logica/cambiar_estado_prueba.php"
                                    class="form-estado-prueba"
                                >

                                    <input
                                        type="hidden"
                                        name="id_prueba"
                                        value="<?php echo (int) $prueba['id_prueba']; ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="estado"
                                        value="Activa"
                                    >


                                    <button
                                        type="submit"
                                        class="btn-cambiar-estado btn-activar"
                                        onclick="return confirm('¿Activar esta prueba?')"
                                    >

                                        <i class="fa-solid fa-play-circle"></i>

                                        Activar prueba

                                    </button>

                                </form>


                            <?php endif; ?>


                            <!-- =============================
                                 VER PARTICIPANTES
                            ============================== -->

                            <a
                                href="ver_prueba.php?id=<?php echo (int) $prueba['id_prueba']; ?>"
                                class="btn-ver-participantes"
                            >

                                <i class="fa-solid fa-users"></i>

                                Ver participantes

                            </a>


                        </div>

                    </div>


                <?php endforeach; ?>


            <?php endif; ?>


        </section>


        <!-- =================================================
             ACCESIBILIDAD
        ================================================= -->

        <?php
            include '../Accesibilidad/accesibilidad.php';
        ?>


    </main>

</div>


<!-- =====================================================
     MODAL NUEVA PRUEBA
===================================================== -->

<div
    class="modal-prueba"
    id="modalNuevaPrueba"
    role="dialog"
    aria-modal="true"
    aria-labelledby="tituloModalPrueba"
>


    <div class="modal-contenido">


        <!-- =================================================
             CABECERA MODAL
        ================================================= -->

        <div class="modal-header">


            <div>

                <h2 id="tituloModalPrueba">

                    Nueva prueba

                </h2>


                <p>

                    Selecciona el principio WCAG 2.1 que
                    deseas evaluar y define la información
                    de la prueba.

                </p>

            </div>


            <button
                type="button"
                class="btn-cerrar-modal"
                id="btnCerrarModal"
                aria-label="Cerrar formulario"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>


        </div>


        <!-- =================================================
             FORMULARIO
        ================================================= -->

        <form
            method="POST"
            action=""
            class="form-prueba"
            id="formNuevaPrueba"
        >


            <input
                type="hidden"
                name="accion"
                value="crear_prueba"
            >


            <!-- =============================================
                 NOMBRE = PRINCIPIO WCAG
            ============================================== -->

            <div class="form-group">


                <label for="nombre">

                    Nombre de la prueba *

                </label>


                <div class="select-principio-contenedor">


                    <select
                        id="nombre"
                        name="nombre"
                        class="form-control select-principio"
                        required
                    >

                        <option value="">

                            Selecciona un principio WCAG 2.1

                        </option>


                        <option value="Perceptible">

                            Perceptible

                        </option>


                        <option value="Operable">

                            Operable

                        </option>


                        <option value="Comprensible">

                            Comprensible

                        </option>


                        <option value="Robusto">

                            Robusto

                        </option>

                    </select>


                    <i
                        class="fa-solid fa-chevron-down icono-select"
                        aria-hidden="true"
                    ></i>


                </div>


                <!-- =========================================
                     DESCRIPCIÓN DEL PRINCIPIO
                ========================================== -->

                <div
                    id="descripcionPrincipio"
                    class="descripcion-principio"
                ></div>


                <span class="ayuda-campo">

                    El principio seleccionado se guardará
                    como nombre de la prueba.

                </span>


            </div>


            <!-- =============================================
                 DESCRIPCIÓN
            ============================================== -->

            <div class="form-group">


                <label for="descripcion">

                    Descripción

                </label>


                <textarea
                    id="descripcion"
                    name="descripcion"
                    class="form-control"
                    placeholder="Describe brevemente la prueba"
                ></textarea>


            </div>


            <!-- =============================================
                 HIPÓTESIS
            ============================================== -->

            <div class="form-group">


                <label for="hipotesis">

                    Hipótesis *

                </label>


                <textarea
                    id="hipotesis"
                    name="hipotesis"
                    class="form-control"
                    placeholder="Escribe la hipótesis de investigación"
                    required
                ></textarea>


            </div>


            <!-- =============================================
                 OBJETIVO
            ============================================== -->

            <div class="form-group">


                <label for="objetivo">

                    Objetivo

                </label>


                <textarea
                    id="objetivo"
                    name="objetivo"
                    class="form-control"
                    placeholder="Escribe el objetivo de la prueba"
                ></textarea>


            </div>


            <!-- =============================================
                 VERSIÓN WCAG
            ============================================== -->

            <div class="form-group">


                <label for="version_wcag">

                    Versión WCAG

                </label>


                <input
                    type="text"
                    id="version_wcag"
                    name="version_wcag"
                    class="form-control"
                    value="WCAG 2.1"
                    readonly
                >


            </div>


            <!-- =============================================
                 ESTADO
            ============================================== -->

            <div class="form-group">


                <label>

                    Estado inicial

                </label>


                <div class="estado-planeada-modal">

                    Planeada

                </div>


                <span class="ayuda-campo">

                    Después podrás activar la prueba desde
                    la lista principal.

                </span>


            </div>


            <!-- =============================================
                 FECHAS CON CALENDARIO
            ============================================== -->

            <div class="fechas-grid">


                <!-- FECHA INICIO -->

                <div class="form-group">


                    <label for="fecha_inicio">

                        Fecha de inicio *

                    </label>


                    <input
                        type="date"
                        id="fecha_inicio"
                        name="fecha_inicio"
                        class="form-control"
                        required
                    >


                </div>


                <!-- FECHA FIN -->

                <div class="form-group">


                    <label for="fecha_fin">

                        Fecha de fin

                    </label>


                    <input
                        type="date"
                        id="fecha_fin"
                        name="fecha_fin"
                        class="form-control"
                    >


                </div>


            </div>


            <!-- =============================================
                 ACCIONES
            ============================================== -->

            <div class="modal-acciones">


                <button
                    type="button"
                    class="btn-cancelar"
                    id="btnCancelarModal"
                >

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="btn-guardar-prueba"
                >

                    <i class="fa-solid fa-floppy-disk"></i>

                    Crear prueba

                </button>


            </div>


        </form>

    </div>

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

<script src="js/pruebas_investigacion.js"></script>

<script src="../Accesibilidad/accesibilidad.js"></script>

<script src="../Accesibilidad/navegacionTeclado.js"></script>


<!-- =====================================================
     SCRIPT NUEVA PRUEBA
===================================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        // =============================================
        // ELEMENTOS
        // =============================================

        const modal =
            document.getElementById(
                'modalNuevaPrueba'
            );


        const btnAbrir =
            document.getElementById(
                'btnNuevaPrueba'
            );


        const btnCerrar =
            document.getElementById(
                'btnCerrarModal'
            );


        const btnCancelar =
            document.getElementById(
                'btnCancelarModal'
            );


        const selectPrincipio =
            document.getElementById(
                'nombre'
            );


        const descripcionPrincipio =
            document.getElementById(
                'descripcionPrincipio'
            );


        const fechaInicio =
            document.getElementById(
                'fecha_inicio'
            );


        const fechaFin =
            document.getElementById(
                'fecha_fin'
            );


        const formulario =
            document.getElementById(
                'formNuevaPrueba'
            );


        // =============================================
        // DESCRIPCIONES WCAG 2.1
        // =============================================

        const principios = {

            Perceptible:
                'La información y los componentes de la interfaz deben presentarse de forma que puedan ser percibidos por los usuarios.',


            Operable:
                'Los componentes de la interfaz y la navegación deben poder ser utilizados por los usuarios.',


            Comprensible:
                'La información y el funcionamiento de la interfaz deben ser comprensibles.',


            Robusto:
                'El contenido debe ser compatible con diferentes tecnologías, incluidas las tecnologías de asistencia.'
        };


        // =============================================
        // ABRIR MODAL
        // =============================================

        function abrirModal() {

            modal.classList.add(
                'activo'
            );


            document.body.style.overflow =
                'hidden';


            setTimeout(
                function () {

                    selectPrincipio.focus();

                },
                100
            );
        }


        // =============================================
        // CERRAR MODAL
        // =============================================

        function cerrarModal() {

            modal.classList.remove(
                'activo'
            );


            document.body.style.overflow =
                '';
        }


        // =============================================
        // EVENTOS MODAL
        // =============================================

        btnAbrir.addEventListener(
            'click',
            abrirModal
        );


        btnCerrar.addEventListener(
            'click',
            cerrarModal
        );


        btnCancelar.addEventListener(
            'click',
            cerrarModal
        );


        modal.addEventListener(
            'click',
            function (
                evento
            ) {

                if (
                    evento.target ===
                    modal
                ) {

                    cerrarModal();
                }
            }
        );


        // =============================================
        // TECLA ESC
        // =============================================

        document.addEventListener(
            'keydown',
            function (
                evento
            ) {

                if (
                    evento.key ===
                    'Escape'
                    &&
                    modal.classList.contains(
                        'activo'
                    )
                ) {

                    cerrarModal();
                }
            }
        );


        // =============================================
        // PRINCIPIO WCAG
        // =============================================

        selectPrincipio.addEventListener(
            'change',
            function () {

                const valor =
                    this.value;


                if (
                    valor
                    &&
                    principios[
                        valor
                    ]
                ) {

                    descripcionPrincipio.innerHTML =
                        '<strong>' +
                        valor +
                        '</strong>' +
                        principios[
                            valor
                        ];


                    descripcionPrincipio
                        .classList
                        .add(
                            'activo'
                        );

                } else {

                    descripcionPrincipio.innerHTML =
                        '';


                    descripcionPrincipio
                        .classList
                        .remove(
                            'activo'
                        );
                }
            }
        );


        // =============================================
        // FECHA INICIO
        // =============================================

        fechaInicio.addEventListener(
            'change',
            function () {

                fechaFin.min =
                    fechaInicio.value;


                if (
                    fechaFin.value
                    &&
                    fechaFin.value <
                    fechaInicio.value
                ) {

                    fechaFin.value =
                        '';
                }
            }
        );


        // =============================================
        // VALIDAR FECHA FIN
        // =============================================

        fechaFin.addEventListener(
            'change',
            function () {

                if (
                    fechaInicio.value
                    &&
                    fechaFin.value
                    &&
                    fechaFin.value <
                    fechaInicio.value
                ) {

                    alert(
                        'La fecha de fin no puede ser anterior a la fecha de inicio.'
                    );


                    fechaFin.value =
                        '';
                }
            }
        );


        // =============================================
        // VALIDAR FORMULARIO
        // =============================================

        formulario.addEventListener(
            'submit',
            function (
                evento
            ) {

                if (
                    !selectPrincipio.value
                ) {

                    evento.preventDefault();


                    alert(
                        'Selecciona un principio WCAG 2.1.'
                    );


                    selectPrincipio.focus();


                    return;
                }


                if (
                    fechaFin.value
                    &&
                    fechaInicio.value
                    &&
                    fechaFin.value <
                    fechaInicio.value
                ) {

                    evento.preventDefault();


                    alert(
                        'La fecha de fin no puede ser anterior a la fecha de inicio.'
                    );
                }
            }
        );

    }
);

</script>


</body>

</html>