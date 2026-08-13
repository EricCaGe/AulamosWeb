<?php
session_start();

if (
    !isset($_SESSION['usuario']) ||
    !isset($_SESSION['usuario']['id_usuario'])
) {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_usuario = (int) $_SESSION['usuario']['id_usuario'];

/*
|--------------------------------------------------------------------------
| Configuración según el tipo de usuario
|--------------------------------------------------------------------------
*/

$rolChatbot =
    ($_GET['rol'] ?? '') === 'docente'
        ? 'docente'
        : 'alumno';

$esDocente = $rolChatbot === 'docente';

$moduloOrigen =
    $esDocente
        ? 'Web Docente'
        : 'Web Alumno';

$urlRegreso =
    $esDocente
        ? '../Docente/docente_dashboard.php'
        : 'alumno.php';

$nombrePredeterminado =
    $esDocente
        ? 'Docente'
        : 'Estudiante';

$nombre =
    $_SESSION['usuario']['nombre']
    ?? $nombrePredeterminado;

$apellido_paterno =
    $_SESSION['usuario']['apellido_paterno']
    ?? '';

/*
|--------------------------------------------------------------------------
| Recuperar información actualizada del usuario
|--------------------------------------------------------------------------
*/

$stmt = $conexion->prepare(
    "SELECT nombre, apellido_paterno
     FROM usuarios
     WHERE id_usuario = ?
     LIMIT 1"
);

if ($stmt) {
    $stmt->bind_param(
        'i',
        $id_usuario
    );

    $stmt->execute();

    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();

    if ($usuario) {
        $nombre =
            $usuario['nombre']
            ?? $nombre;

        $apellido_paterno =
            $usuario['apellido_paterno']
            ?? $apellido_paterno;
    }

    $stmt->close();
}

$nombre_completo = trim(
    $nombre . ' ' . $apellido_paterno
);

/*
|--------------------------------------------------------------------------
| Configuración que utilizará Chatbot.js
|--------------------------------------------------------------------------
*/

$configuracionChatbot = json_encode(
    [
        /*
         * La web utiliza PHP directamente.
         * No depende del servidor Node en el puerto 3000.
         */
        'endpoint' =>
            'api/chatbot/responder.php',

        'rol' =>
            $rolChatbot,

        'moduloOrigen' =>
            $moduloOrigen,

        'urlRegreso' =>
            $urlRegreso,

        'nombre' =>
            $nombre,

        'idUsuario' =>
            $id_usuario,
    ],
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
);
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
        AulaBot <?= $esDocente ? 'Docente' : 'Alumno' ?> - AulaMos
    </title>

    <link
        rel="stylesheet"
        href="Style/Inicio.css"
    >

    <link
        rel="stylesheet"
        href="Style/Chatbot.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >
</head>

<body>
<div class="dashboard-container">

    <!-- =========================================================
         BARRA LATERAL
    ========================================================== -->
    <aside class="sidebar">

        <div class="logo-section">
            <img
                src="../img/logogeneral.png"
                alt="Logo de AulaMos"
                class="logo"
            >
        </div>

        <?php if ($esDocente): ?>

            <!-- MENÚ DEL DOCENTE -->
            <nav
                class="menu"
                aria-label="Menú principal del docente"
            >
                <a
                    href="../Docente/docente_dashboard.php"
                    class="menu-item"
                >
                    <i
                        class="fa-solid fa-house"
                        aria-hidden="true"
                    ></i>
                    Dashboard
                </a>

                <a
                    href="../Docente/crear_recurso.php"
                    class="menu-item"
                >
                    <i
                        class="fa-solid fa-medal"
                        aria-hidden="true"
                    ></i>
                    Crear curso
                </a>

                <a
                    href="../Docente/crear_actividad.php"
                    class="menu-item"
                >
                    <i
                        class="fa-solid fa-clipboard-check"
                        aria-hidden="true"
                    ></i>
                    Crear actividad
                </a>

                <a
                    href="../Docente/crear_evaluacion.php"
                    class="menu-item"
                >
                    <i
                        class="fa-solid fa-clipboard-list"
                        aria-hidden="true"
                    ></i>
                    Crear evaluación
                </a>

                <a
                    href="../Docente/ver_estudiantes.php"
                    class="menu-item"
                >
                    <i
                        class="fa-solid fa-users"
                        aria-hidden="true"
                    ></i>
                    Ver estudiantes
                </a>

                <a
                    href="../Docente/reporte.php"
                    class="menu-item"
                >
                    <i
                        class="fa-solid fa-chart-column"
                        aria-hidden="true"
                    ></i>
                    Reportes
                </a>

                <a
                    href="Chatbot.php?rol=docente"
                    class="menu-item active"
                    aria-current="page"
                >
                    <i
                        class="fa-solid fa-robot"
                        aria-hidden="true"
                    ></i>
                    AulaBot
                </a>

                <a
                    href="../Docente/pasarlista.php"
                    class="menu-item"
                >
                    <i
                        class="fa-solid fa-bars"
                        aria-hidden="true"
                    ></i>
                    Pasar lista
                </a>
            </nav>

        <?php else: ?>

            <!-- MENÚ DEL ALUMNO -->
            <nav
                class="menu"
                aria-label="Menú principal del alumno"
            >
                <a
                    href="alumno.php"
                    class="menu-item"
                >
                    <i
                        class="fa-solid fa-house"
                        aria-hidden="true"
                    ></i>
                    Inicio
                </a>

                <a
                    href="Actividades.php"
                    class="menu-item"
                >
                    <i
                        class="fa-solid fa-cubes"
                        aria-hidden="true"
                    ></i>
                    Mis actividades
                </a>

                <a
                    href="Biblioteca.php"
                    class="menu-item"
                >
                    <i
                        class="fa-solid fa-book-open"
                        aria-hidden="true"
                    ></i>
                    Biblioteca digital
                </a>

                <a
                    href="Avances.php"
                    class="menu-item"
                >
                    <i
                        class="fa-solid fa-pen-to-square"
                        aria-hidden="true"
                    ></i>
                    Mis avances
                </a>

                <a
                    href="Chatbot.php"
                    class="menu-item active"
                    aria-current="page"
                >
                    <i
                        class="fa-solid fa-robot"
                        aria-hidden="true"
                    ></i>
                    AulaBot
                </a>

                <a
                    href="Ayuda.php"
                    class="menu-item"
                >
                    <i
                        class="fa-solid fa-circle-question"
                        aria-hidden="true"
                    ></i>
                    Ayuda
                </a>

                <a
                    href="accesibilidad.php"
                    class="menu-item"
                >
                    <i
                        class="fa-solid fa-universal-access"
                        aria-hidden="true"
                    ></i>
                    Accesibilidad
                </a>
            </nav>

            <a
                href="accesibilidad.php"
                class="btn-accessibility-main"
                aria-label="Abrir configuración de accesibilidad"
            >
                <i
                    class="fa-solid fa-universal-access"
                    aria-hidden="true"
                ></i>
                Accesibilidad
            </a>

        <?php endif; ?>

    </aside>

    <!-- =========================================================
         CONTENIDO PRINCIPAL
    ========================================================== -->
    <main class="main-content">

        <header class="content-header chatbot-page-header">

            <div class="welcome-text">
                <h1>
                    <i
                        class="fa-solid fa-robot"
                        aria-hidden="true"
                    ></i>

                    AulaBot
                </h1>

                <p>
                    <?php if ($esDocente): ?>
                        Tu asistente para crear actividades,
                        evaluaciones, cursos y utilizar AulaMos.
                    <?php else: ?>
                        Tu asistente educativo para resolver dudas
                        y aprender paso a paso.
                    <?php endif; ?>
                </p>
            </div>

            <div class="header-actions">

                <a
                    href="<?= htmlspecialchars(
                        $urlRegreso,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    class="header-icon-button"
                    aria-label="Regresar al panel"
                    title="Regresar al panel"
                >
                    <i
                        class="fa-solid fa-arrow-left"
                        aria-hidden="true"
                    ></i>
                </a>

                <button
                    type="button"
                    class="header-icon-button"
                    id="btnLimpiarChat"
                    aria-label="Limpiar conversación"
                    title="Limpiar conversación"
                >
                    <i
                        class="fa-solid fa-trash"
                        aria-hidden="true"
                    ></i>
                </button>

                <div
                    class="icon-bell"
                    role="button"
                    tabindex="0"
                    aria-label="Notificaciones"
                    title="Notificaciones"
                >
                    <i
                        class="fa-regular fa-bell"
                        aria-hidden="true"
                    ></i>
                </div>

                <div
                    class="student-avatar"
                    title="<?= htmlspecialchars(
                        $nombre_completo,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    aria-label="Usuario <?= htmlspecialchars(
                        $nombre_completo,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <i
                        class="fa-solid fa-user"
                        aria-hidden="true"
                    ></i>
                </div>

            </div>
        </header>

        <section
            class="chatbot-container"
            aria-labelledby="tituloConversacion"
        >
            <div class="chatbot-card">

                <!-- ENCABEZADO DEL CHAT -->
                <div class="chatbot-header">

                    <div
                        class="bot-avatar"
                        aria-hidden="true"
                    >
                        <i class="fa-solid fa-robot"></i>
                    </div>

                    <div class="bot-information">
                        <h2 id="tituloConversacion">
                            Conversación con AulaBot
                        </h2>

                        <p>
                            <span
                                class="status-indicator"
                                aria-hidden="true"
                            ></span>

                            Disponible para ayudarte
                        </p>
                    </div>

                    <button
                        type="button"
                        class="read-response-button"
                        id="btnLeerRespuesta"
                        aria-label="Leer en voz alta la última respuesta"
                        title="Leer última respuesta"
                    >
                        <i
                            class="fa-solid fa-volume-high"
                            aria-hidden="true"
                        ></i>
                    </button>

                </div>

                <!-- PREGUNTAS RÁPIDAS -->
                <div
                    class="quick-questions"
                    aria-label="Preguntas sugeridas"
                >
                    <p>Prueba preguntando:</p>

                    <div class="quick-question-list">

                        <?php if ($esDocente): ?>

                            <button
                                type="button"
                                class="quick-question"
                                data-question="¿Cómo creo una actividad para mis estudiantes?"
                            >
                                ¿Cómo creo una actividad?
                            </button>

                            <button
                                type="button"
                                class="quick-question"
                                data-question="¿Cómo creo una evaluación en AulaMos?"
                            >
                                ¿Cómo creo una evaluación?
                            </button>

                            <button
                                type="button"
                                class="quick-question"
                                data-question="¿Cómo puedo consultar el avance de mis estudiantes?"
                            >
                                Consultar avances
                            </button>

                        <?php else: ?>

                            <button
                                type="button"
                                class="quick-question"
                                data-question="Explícame qué es la fotosíntesis"
                            >
                                ¿Qué es la fotosíntesis?
                            </button>

                            <button
                                type="button"
                                class="quick-question"
                                data-question="Ayúdame a entender las fracciones"
                            >
                                Ayúdame con fracciones
                            </button>

                            <button
                                type="button"
                                class="quick-question"
                                data-question="Explícame el ciclo del agua paso a paso"
                            >
                                Explícame el ciclo del agua
                            </button>

                        <?php endif; ?>

                    </div>
                </div>

                <!-- MENSAJES -->
                <div
                    class="chat-messages"
                    id="chatMessages"
                    role="log"
                    aria-live="polite"
                    aria-relevant="additions text"
                    aria-label="Mensajes de la conversación"
                    tabindex="0"
                >
                    <article class="message message-bot">

                        <div
                            class="message-avatar"
                            aria-hidden="true"
                        >
                            <i class="fa-solid fa-robot"></i>
                        </div>

                        <div class="message-content">
                            <p>
                                Hola,

                                <strong>
                                    <?= htmlspecialchars(
                                        $nombre,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>.

                                <?php if ($esDocente): ?>

                                    Soy AulaBot. Puedo ayudarte a crear
                                    cursos, actividades y evaluaciones,
                                    consultar estudiantes, revisar
                                    reportes y utilizar AulaMos.

                                <?php else: ?>

                                    Soy AulaBot. Puedo explicarte temas,
                                    darte ejemplos y ayudarte a estudiar
                                    paso a paso.

                                <?php endif; ?>
                            </p>

                            <time>Ahora</time>
                        </div>

                    </article>
                </div>

                <!-- ESTADO DEL CHAT -->
                <div
                    class="chat-status"
                    id="chatStatus"
                    role="status"
                    aria-live="polite"
                ></div>

                <!-- FORMULARIO -->
                <form
                    class="chat-form"
                    id="chatForm"
                >
                    <label
                        for="mensajeChat"
                        class="sr-only"
                    >
                        Escribe tu pregunta para AulaBot
                    </label>

                    <div class="message-input-container">

                        <textarea
                            id="mensajeChat"
                            name="mensaje"
                            rows="1"
                            maxlength="1000"
                            placeholder="Escribe una pregunta..."
                            aria-describedby="mensajeAyuda contadorMensaje"
                            required
                        ></textarea>

                        <button
                            type="submit"
                            class="send-message-button"
                            id="btnEnviarMensaje"
                            aria-label="Enviar mensaje"
                        >
                            <i
                                class="fa-solid fa-paper-plane"
                                aria-hidden="true"
                            ></i>

                            <span>Enviar</span>
                        </button>

                    </div>

                    <div class="input-information">

                        <small id="mensajeAyuda">
                            Presiona Enter para enviar y
                            Shift + Enter para agregar una línea.
                        </small>

                        <small id="contadorMensaje">
                            <span id="cantidadCaracteres">0</span>/1000
                        </small>

                    </div>
                </form>

            </div>

            <!-- INFORMACIÓN LATERAL -->
            <aside class="chatbot-information-card">

                <i
                    class="fa-solid fa-shield-heart"
                    aria-hidden="true"
                ></i>

                <div>
                    <h2>
                        <?= $esDocente
                            ? 'Apoyo para tu trabajo docente'
                            : 'Aprende con seguridad'
                        ?>
                    </h2>

                    <p>
                        <?php if ($esDocente): ?>

                            AulaBot puede orientarte para utilizar
                            las funciones de AulaMos y preparar
                            materiales educativos, pero debes revisar
                            la información antes de compartirla con
                            tus estudiantes.

                        <?php else: ?>

                            AulaBot puede ayudarte a comprender temas
                            y practicar, pero también debes revisar
                            las indicaciones de tu docente.

                        <?php endif; ?>
                    </p>
                </div>

            </aside>
        </section>

        <!-- =====================================================
             ACCESIBILIDAD
        ====================================================== -->
        <footer class="accessibility-bar">

            <div class="acc-info">

                <i
                    class="fa-solid fa-eye-low-vision acc-icon-main"
                    aria-hidden="true"
                ></i>

                <div>
                    <strong>
                        Accesibilidad siempre disponible
                    </strong>

                    <p>
                        Personaliza tu experiencia en cualquier momento.
                    </p>
                </div>

            </div>

            <div class="acc-options">

                <button
                    type="button"
                    class="acc-opt-btn"
                    id="btn-contrast"
                >
                    <i
                        class="fa-solid fa-eye"
                        aria-hidden="true"
                    ></i>

                    <span>Alto contraste</span>
                </button>

                <button
                    type="button"
                    class="acc-opt-btn"
                    id="btn-darkmode"
                >
                    <i
                        class="fa-solid fa-moon"
                        aria-hidden="true"
                    ></i>

                    <span>Modo oscuro</span>
                </button>

                <button
                    type="button"
                    class="acc-opt-btn"
                    id="btn-text-size"
                >
                    <i
                        class="fa-solid fa-font"
                        aria-hidden="true"
                    ></i>

                    <span>Texto grande</span>
                </button>

                <button
                    type="button"
                    class="acc-opt-btn"
                    id="btn-leer"
                >
                    <i
                        class="fa-solid fa-volume-high"
                        aria-hidden="true"
                    ></i>

                    <span>Leer pantalla</span>
                </button>

            </div>

            <a
                href="accesibilidad.php"
                class="btn-open-config"
                id="btn-config"
            >
                Abrir configuración
            </a>

        </footer>

    </main>
</div>

<!-- Configuración disponible para Chatbot.js -->
<script>
    window.AULAMOS_CHATBOT_CONFIG =
        <?= $configuracionChatbot ?: '{}' ?>;
</script>

<!-- Se cargan una sola vez -->
<script src="js/Inicio.js"></script>
<script src="js/Chatbot.js"></script>
<script src="js/ChatbotHistorial.js"></script>
<script src="js/ChatbotFeedback.js"></script>

</body>
</html>