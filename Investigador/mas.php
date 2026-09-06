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
// DATOS DE LA PÁGINA
// =====================================================

$titulo_pagina =
    'Más';

$descripcion_pagina =
    'Consulta otras opciones del módulo de investigación.';

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
        Más - Investigador
    </title>


    <!-- =================================================
         ESTILOS
    ================================================= -->

    <link
        rel="stylesheet"
        href="styles/mas.css"
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
         ID DEL USUARIO PARA ACCESIBILIDAD
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
             OPCIONES
        ================================================= -->

        <section class="opciones-mas">


            <!-- =============================================
                 MI PERFIL
            ============================================== -->

            <div
                class="opcion-item"
                onclick="window.location.href='perfil_investigador.php'"
                role="button"
                tabindex="0"
                data-url="perfil_investigador.php"
            >

                <div class="opcion-icono">

                    <i class="fa-solid fa-user"></i>

                </div>


                <div class="opcion-contenido">

                    <span class="opcion-titulo">
                        Mi perfil
                    </span>

                    <span class="opcion-descripcion">
                        Consulta la información de tu cuenta
                    </span>

                </div>


                <i class="fa-solid fa-chevron-right"></i>

            </div>


            <!-- =============================================
                 PRUEBAS DE INVESTIGACIÓN
            ============================================== -->

            <div
                class="opcion-item"
                onclick="window.location.href='pruebas_investigacion.php'"
                role="button"
                tabindex="0"
                data-url="pruebas_investigacion.php"
            >

                <div class="opcion-icono">

                    <i class="fa-solid fa-flask"></i>

                </div>


                <div class="opcion-contenido">

                    <span class="opcion-titulo">
                        Pruebas de investigación
                    </span>

                    <span class="opcion-descripcion">
                        Consulta y administra las pruebas realizadas
                        en la plataforma
                    </span>

                </div>


                <i class="fa-solid fa-chevron-right"></i>

            </div>


            <!-- =============================================
                 PARTICIPANTES
            ============================================== -->

            <div
                class="opcion-item"
                onclick="window.location.href='pruebas_investigacion.php'"
                role="button"
                tabindex="0"
                data-url="pruebas_investigacion.php"
            >

                <div class="opcion-icono">

                    <i class="fa-solid fa-users"></i>

                </div>


                <div class="opcion-contenido">

                    <span class="opcion-titulo">
                        Participantes
                    </span>

                    <span class="opcion-descripcion">
                        Selecciona una prueba para consultar
                        sus estudiantes participantes
                    </span>

                </div>


                <i class="fa-solid fa-chevron-right"></i>

            </div>


            <!-- =============================================
                 CONFIGURACIÓN
            ============================================== -->

            <div
                class="opcion-item"
                onclick="toggleBarraAccesibilidad()"
                role="button"
                tabindex="0"
                data-accion="accesibilidad"
            >

                <div class="opcion-icono">

                    <i class="fa-solid fa-gear"></i>

                </div>


                <div class="opcion-contenido">

                    <span class="opcion-titulo">
                        Configuración de accesibilidad
                    </span>

                    <span class="opcion-descripcion">
                        Configura tus preferencias de accesibilidad
                    </span>

                </div>


                <i class="fa-solid fa-chevron-right"></i>

            </div>


            <!-- =============================================
                 AYUDA
            ============================================== -->
<!-- =============================================
            <div
                class="opcion-item"
                onclick="alert('La sección de ayuda todavía no está disponible.')"
                role="button"
                tabindex="0"
                data-accion="ayuda"
            >

                <div class="opcion-icono">

                    <i class="fa-solid fa-circle-question"></i>

                </div>


                <div class="opcion-contenido">

                    <span class="opcion-titulo">
                        Ayuda
                    </span>

                    <span class="opcion-descripcion">
                        Consulta información de ayuda
                    </span>

                </div>


                <i class="fa-solid fa-chevron-right"></i>

            </div>


        </section>
         ============================================== -->


        <!-- =================================================
             CERRAR SESIÓN
        ================================================= -->

        <section class="cerrar-sesion">


            <div
                class="cerrar-item"
                onclick="
                    if (
                        confirm('¿Deseas cerrar tu sesión?')
                    ) {
                        window.location.href='../InicioSesion/cerrar_sesion.php';
                    }
                "
                role="button"
                tabindex="0"
                data-accion="cerrar-sesion"
            >

                <div class="cerrar-icono">

                    <i class="fa-solid fa-right-from-bracket"></i>

                </div>


                <span class="cerrar-texto">
                    Cerrar sesión
                </span>


                <i class="fa-solid fa-chevron-right"></i>

            </div>


        </section>


        <!-- =================================================
             BARRA DE ACCESIBILIDAD
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

<script src="js/mas.js"></script>

<script src="../Accesibilidad/accesibilidad.js"></script>

<script src="../Accesibilidad/navegacionTeclado.js"></script>


<!-- =====================================================
     NAVEGACIÓN CON TECLADO
===================================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        // =============================================
        // ELEMENTOS NAVEGABLES
        // =============================================

        const elementos =
            document.querySelectorAll(
                '.opcion-item[data-url]'
            );


        elementos.forEach(
            function (
                elemento
            ) {

                elemento.addEventListener(
                    'keydown',
                    function (
                        evento
                    ) {

                        if (
                            evento.key === 'Enter' ||
                            evento.key === ' '
                        ) {

                            evento.preventDefault();


                            const url =
                                elemento.getAttribute(
                                    'data-url'
                                );


                            if (
                                url
                            ) {

                                window.location.href =
                                    url;
                            }
                        }
                    }
                );
            }
        );


        // =============================================
        // CONFIGURACIÓN ACCESIBILIDAD CON TECLADO
        // =============================================

        const configuracion =
            document.querySelector(
                '[data-accion="accesibilidad"]'
            );


        if (
            configuracion
        ) {

            configuracion.addEventListener(
                'keydown',
                function (
                    evento
                ) {

                    if (
                        evento.key === 'Enter' ||
                        evento.key === ' '
                    ) {

                        evento.preventDefault();


                        if (
                            typeof toggleBarraAccesibilidad ===
                            'function'
                        ) {

                            toggleBarraAccesibilidad();
                        }
                    }
                }
            );
        }


        // =============================================
        // AYUDA CON TECLADO
        // =============================================

        const ayuda =
            document.querySelector(
                '[data-accion="ayuda"]'
            );


        if (
            ayuda
        ) {

            ayuda.addEventListener(
                'keydown',
                function (
                    evento
                ) {

                    if (
                        evento.key === 'Enter' ||
                        evento.key === ' '
                    ) {

                        evento.preventDefault();


                        alert(
                            'La sección de ayuda todavía no está disponible.'
                        );
                    }
                }
            );
        }


        // =============================================
        // CERRAR SESIÓN CON TECLADO
        // =============================================

        const cerrarSesion =
            document.querySelector(
                '[data-accion="cerrar-sesion"]'
            );


        if (
            cerrarSesion
        ) {

            cerrarSesion.addEventListener(
                'keydown',
                function (
                    evento
                ) {

                    if (
                        evento.key === 'Enter' ||
                        evento.key === ' '
                    ) {

                        evento.preventDefault();


                        if (
                            confirm(
                                '¿Deseas cerrar tu sesión?'
                            )
                        ) {

                            window.location.href =
                                '../InicioSesion/cerrar_sesion.php';
                        }
                    }
                }
            );
        }

    }
);

</script>


</body>

</html>