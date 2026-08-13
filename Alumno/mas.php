<?php
session_start();

/* =========================================================
   VERIFICAR SESIÓN
   ========================================================= */

if (!isset($_SESSION['usuario'])) {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';


/* =========================================================
   DATOS DEL USUARIO QUE INICIÓ SESIÓN
   ========================================================= */

$id_usuario = $_SESSION['usuario']['id_usuario'];

$stmt = $conexion->prepare("
    SELECT 
        nombre,
        apellido_paterno,
        apellido_materno,
        correo
    FROM usuarios
    WHERE id_usuario = ?
");

$stmt->bind_param("i", $id_usuario);
$stmt->execute();

$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    die("No se encontraron los datos del usuario.");
}


/* =========================================================
   DATOS PARA MOSTRAR
   ========================================================= */

$nombre = $usuario['nombre'] ?? '';

$apellido_paterno = $usuario['apellido_paterno'] ?? '';

$apellido_materno = $usuario['apellido_materno'] ?? '';

$nombre_completo = trim(
    $nombre . ' ' .
    $apellido_paterno . ' ' .
    $apellido_materno
);

$correo = $usuario['correo'] ?? 'Correo no disponible';

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Aulamos - Más</title>


    <!-- =====================================================
         FONT AWESOME
         ===================================================== -->

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


    <!-- =====================================================
         CSS DE ESTA PÁGINA
         ===================================================== -->
    <link rel="stylesheet" href="Style/mas.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
</head>
<body>
<!-- =========================================================
     CONTENEDOR PRINCIPAL
     ========================================================= -->

<div class="mas-container">


    <!-- =====================================================
         ENCABEZADO
         ===================================================== -->

    <header class="mas-header">

        <div class="mas-header-left">

            <a href="alumno.php"
               class="btn-regresar"
               title="Regresar">

                <i class="fa-solid fa-arrow-left"></i>

            </a>


            <div class="mas-titulo">

                <h1>Más</h1>

                <p>
                    Opciones de tu cuenta de alumno
                </p>

            </div>

        </div>


        <a href="../InicioSesion/cerrar_sesion.php"
           class="btn-cerrar">

            <i class="fa-solid fa-right-from-bracket"></i>

            Cerrar sesión

        </a>

    </header>



    <!-- =====================================================
         INFORMACIÓN DEL USUARIO
         ===================================================== -->

    <section class="usuario-card">


        <!-- Avatar -->

        <div class="usuario-avatar">

            <i class="fa-regular fa-user"></i>

        </div>


        <!-- Información -->

        <div class="usuario-info">

            <h2>
                <?php
                echo htmlspecialchars($nombre_completo);
                ?>
            </h2>


            <p class="correo">

                <?php
                echo htmlspecialchars($correo);
                ?>

            </p>


            <span class="tipo-usuario">

                <i class="fa-solid fa-graduation-cap"></i>

                Alumno

            </span>

        </div>

    </section>



    <!-- =====================================================
         CUENTA Y HERRAMIENTAS
         ===================================================== -->

    <h2 class="seccion-titulo">

        Cuenta y herramientas

    </h2>



    <section class="opciones-card">


        <!-- =================================================
             PERFIL
             ================================================= -->

        <a href="perfil.php"
           class="opcion">

            <div class="opcion-icono">

                <i class="fa-regular fa-user"></i>

            </div>


            <div class="opcion-contenido">

                <h3>
                    Ver perfil
                </h3>

                <p>
                    Consulta tus datos personales y escolares
                </p>

            </div>


            <div class="opcion-flecha">

                <i class="fa-solid fa-chevron-right"></i>

            </div>

        </a>

<!-- =================================================
     ACTIVIDADES
     ================================================= -->

<a href="actividades.php"
   class="opcion">

    <div class="opcion-icono">

        <i class="fa-solid fa-list-check"></i>

    </div>

    <div class="opcion-contenido">

        <h3>
            Actividades
        </h3>

        <p>
            Consulta y realiza tus actividades escolares
        </p>

    </div>

    <div class="opcion-flecha">

        <i class="fa-solid fa-chevron-right"></i>

    </div>

</a>

        <!-- =================================================
             AVANCES
             ================================================= -->

        <a href="avances.php"
           class="opcion">

            <div class="opcion-icono">

                <i class="fa-solid fa-chart-column"></i>

            </div>


            <div class="opcion-contenido">

                <h3>
                    Mis avances
                </h3>

                <p>
                    Consulta tu progreso académico por materia
                </p>

            </div>


            <div class="opcion-flecha">

                <i class="fa-solid fa-chevron-right"></i>

            </div>

        </a>




        <!-- =================================================
             BIBLIOTECA
             ================================================= -->

        <a href="biblioteca.php"
           class="opcion">

            <div class="opcion-icono">

                <i class="fa-solid fa-book-open"></i>

            </div>


            <div class="opcion-contenido">

                <h3>
                    Biblioteca digital
                </h3>

                <p>
                    Consulta los recursos compartidos por tus docentes
                </p>

            </div>


            <div class="opcion-flecha">

                <i class="fa-solid fa-chevron-right"></i>

            </div>

        </a>



        <!-- =================================================
             NOTIFICACIONES
             ================================================= -->

        <a href="notificaciones.php"
           class="opcion">

            <div class="opcion-icono">

                <i class="fa-regular fa-bell"></i>

            </div>


            <div class="opcion-contenido">

                <h3>
                    Notificaciones
                </h3>

                <p>
                    Consulta avisos, actividades y recordatorios
                </p>

            </div>


            <div class="opcion-flecha">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </a>

        <!-- =================================================
             CHATBOT
             ================================================= -->
        <a href="chatbot.php"
           class="opcion">
            <div class="opcion-icono">
                <i class="fa-regular fa-comment-dots"></i>
            </div>
            <div class="opcion-contenido">
                <h3>
                    Chatbot Asistente
                </h3>
                <p>
                    Consulta al asistente virtual de AULAMOS
                </p>
            </div>
            <div class="opcion-flecha">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </a>

        <!-- =================================================
             ACCESIBILIDAD
             ================================================= -->
<button
    type="button"
    class="opcion opcion-accesibilidad"
    id="btnMasAccesibilidad"
    aria-label="Abrir configuración de accesibilidad">

    <div class="opcion-icono">

        <i class="fa-solid fa-universal-access"></i>

    </div>

    <div class="opcion-contenido">

        <h3>
            Configuración de accesibilidad
        </h3>

        <p>
            Personaliza contraste, texto y otras herramientas
        </p>

    </div>

    <div class="opcion-flecha">

        <i class="fa-solid fa-chevron-right"></i>

    </div>

</button>

</section>
</div>
<!-- =====================================================
     BARRA DE ACCESIBILIDAD
     ===================================================== -->

<?php include '../Accesibilidad/accesibilidad.php'; ?>


<!-- =====================================================
     BOTÓN FLOTANTE MORADO
     ===================================================== -->

<button
    class="btn-accesibilidad-flotante"
    id="btnAccesibilidadFlotante"
    type="button"
    onclick="toggleBarraAccesibilidad()">

    <i class="fa-solid fa-universal-access"></i>

</button>


<!-- =====================================================
     ID DEL USUARIO
     ===================================================== -->

<script>
    window.idUsuario = <?php echo (int)$id_usuario; ?>;
</script>


<!-- =====================================================
     JAVASCRIPT DE ACCESIBILIDAD
     ===================================================== -->

<script src="../Accesibilidad/accesibilidad.js"></script>


<!-- =====================================================
     ABRIR ACCESIBILIDAD DESDE "MÁS"
     ===================================================== -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const boton =
        document.getElementById('btnMasAccesibilidad');

    if (!boton) {
        return;
    }

    boton.addEventListener('click', function () {

        if (typeof toggleBarraAccesibilidad === 'function') {

            toggleBarraAccesibilidad();

        } else {

            console.error(
                'No se encontró toggleBarraAccesibilidad()'
            );

        }

    });

});

</script>
</body>
</html>