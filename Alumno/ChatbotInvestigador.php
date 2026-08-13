<?php

declare(strict_types=1);

session_start();

if (
    !isset($_SESSION['usuario']) ||
    !isset($_SESSION['usuario']['id_usuario']) ||
    (
        $_SESSION['usuario']['rol'] ?? ''
    ) !== 'Investigador'
) {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_usuario =
    (int) $_SESSION['usuario']['id_usuario'];

$nombre =
    $_SESSION['usuario']['nombre']
    ?? 'Investigador';

$apellido_paterno =
    $_SESSION['usuario']['apellido_paterno']
    ?? '';

$stmt = $conexion->prepare(
    '
        SELECT
            nombre,
            apellido_paterno
        FROM usuarios
        WHERE id_usuario = ?
        LIMIT 1
    '
);

if ($stmt) {

    $stmt->bind_param(
        'i',
        $id_usuario
    );

    $stmt->execute();

    $resultado =
        $stmt->get_result();

    $usuario =
        $resultado->fetch_assoc();

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

$nombre_completo =
    trim(
        $nombre .
        ' ' .
        $apellido_paterno
    );

$configuracionChatbot =
    json_encode(
        [
            'endpoint' =>
                'api/chatbot/responder.php',

            'rol' =>
                'investigador',

            'moduloOrigen' =>
                'Web Investigador',

            'urlRegreso' =>
                '../Investigador/dashboard.php',

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
        AulaBot Investigador - AulaMos
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

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="logo-section">

            <img
                src="../img/logogeneral.png"
                alt="Logo de AulaMos"
                class="logo"
            >

        </div>

        <nav
            class="menu"
            aria-label="Menú principal del investigador"
        >

            <a
                href="../Investigador/dashboard.php"
                class="menu-item"
            >
                <i
                    class="fa-solid fa-house"
                    aria-hidden="true"
                ></i>

                Dashboard
            </a>

            <a
                href="../Investigador/metricas_uso.php"
                class="menu-item"
            >
                <i
                    class="fa-solid fa-chart-bar"
                    aria-hidden="true"
                ></i>

                Uso de la plataforma
            </a>

            <a
                href="../Investigador/tiempos_actividades.php"
                class="menu-item"
            >
                <i
                    class="fa-solid fa-clock"
                    aria-hidden="true"
                ></i>

                Tiempos de actividades
            </a>

            <a
                href="../Investigador/errores_navegacion.php"
                class="menu-item"
            >
                <i
                    class="fa-solid fa-triangle-exclamation"
                    aria-hidden="true"
                ></i>

                Errores de navegación
            </a>

            <a
                href="../Investigador/metricas_chatbot.php"
                class="menu-item"
            >
                <i
                    class="fa-solid fa-chart-column"
                    aria-hidden="true"
                ></i>

                Uso del chatbot
            </a>

            <a
                href="ChatbotInvestigador.php"
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
                href="../Investigador/progreso_academico.php"
                class="menu-item"
            >
                <i
                    class="fa-solid fa-chart-line"
                    aria-hidden="true"
                ></i>

                Progreso académico
            </a>

            <a
                href="../Investigador/metricas_accesibilidad.php"
                class="menu-item"
            >
                <i
                    class="fa-solid fa-universal-access"
                    aria-hidden="true"
                ></i>

                Accesibilidad
            </a>

            <a
                href="../Investigador/reportes.php"
                class="menu-item"
            >
                <i
                    class="fa-solid fa-file-alt"
                    aria-hidden="true"
                ></i>

                Reportes
            </a>

            <a
                href="../Investigador/mas.php"
                class="menu-item"
            >
                <i
                    class="fa-solid fa-ellipsis-h"
                    aria-hidden="true"
                ></i>

                Más
            </a>

        </nav>

    </aside>


    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">

        <header
            class="content-header chatbot-page-header"
        >

            <div class="welcome-text">

                <h1>

                    <i
                        class="fa-solid fa-robot"
                        aria-hidden="true"
                    ></i>

                    AulaBot

                </h1>

                <p>
                    Asistente para consultar métricas e información
                    de investigación de AulaMos.
                </p>

            </div>

            <div class="header-actions">

                <a
                    href="../Investigador/dashboard.php"
                    class="header-icon-button"
                    aria-label="Regresar al panel del investigador"
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
                    class="student-avatar"
                    title="<?= htmlspecialchars(
                        $nombre_completo,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    aria-label="Investigador <?= htmlspecialchars(
                        $nombre_completo,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                    <i
                        class="fa-solid fa-magnifying-glass-chart"
                        aria-hidden="true"
                    ></i>

                </div>

            </div>

        </header>


        <!-- CHATBOT -->
        <section
            class="chatbot-container"
            aria-labelledby="tituloConversacion"
        >

            <div class="chatbot-card">

                <!-- ENCABEZADO -->
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

                            Métricas de investigación de AulaMos

                        </p>

                    </div>

                    <button
                        type="button"
                        class="read-response-button"
                        id="btnLeerRespuesta"
                        aria-label="Leer última respuesta"
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

                        <button
                            type="button"
                            class="quick-question"
                            data-question="¿Cuántos alumnos activos hay actualmente?"
                        >
                            Alumnos activos
                        </button>

                        <button
                            type="button"
                            class="quick-question"
                            data-question="¿Cuántos mensajes se han registrado en AulaBot?"
                        >
                            Uso de AulaBot
                        </button>

                        <button
                            type="button"
                            class="quick-question"
                            data-question="¿Cuántos errores están registrados en la plataforma?"
                        >
                            Errores registrados
                        </button>

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

                    <article
                        class="message message-bot"
                    >

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

                                Soy AulaBot.

                                Puedo ayudarte a consultar
                                métricas agregadas de investigación
                                registradas en AulaMos.

                            </p>

                            <time>Ahora</time>

                        </div>

                    </article>

                </div>


                <!-- ESTADO -->
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
                            placeholder="Pregunta sobre las métricas de AulaMos..."
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

                            <span id="cantidadCaracteres">
                                0
                            </span>/1000

                        </small>

                    </div>

                </form>

            </div>


            <!-- INFORMACIÓN -->
            <aside class="chatbot-information-card">

                <i
                    class="fa-solid fa-shield-halved"
                    aria-hidden="true"
                ></i>

                <div>

                    <h2>
                        Consulta de investigación segura
                    </h2>

                    <p>
                        AulaBot utiliza métricas agregadas
                        registradas en AulaMos y evita mostrar
                        información personal, contraseñas,
                        claves API o credenciales.
                    </p>

                </div>

            </aside>

        </section>


        <!-- ACCESIBILIDAD -->
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
                        Personaliza tu experiencia
                        en cualquier momento.
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

                    <span>
                        Alto contraste
                    </span>

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

                    <span>
                        Modo oscuro
                    </span>

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

                    <span>
                        Texto grande
                    </span>

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

                    <span>
                        Leer pantalla
                    </span>

                </button>

            </div>

        </footer>

    </main>

</div>


<script>
    window.AULAMOS_CHATBOT_CONFIG =
        <?= $configuracionChatbot ?: '{}' ?>;
</script>

<script src="js/Inicio.js"></script>
<script src="js/Chatbot.js"></script>
<script src="js/ChatbotHistorial.js"></script>
<script src="js/ChatbotFeedback.js"></script>

</body>
</html>