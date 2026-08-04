<?php
// Iniciar sesión
session_start();

// Verificar que el usuario haya iniciado sesión y sea Docente
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

// Obtener datos del docente
$id_docente = $_SESSION['usuario']['id_usuario'];
$nombre_docente = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Actividad - Aulamos</title>

    <!-- CSS Base -->
    <link rel="stylesheet" href="styles/docente.css">
    <!-- CSS Específico para esta pantalla -->
    <link rel="stylesheet" href="styles/actividad.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="dashboard-container">

        <!-- BARRA LATERAL -->
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../img/logo_g.png" alt="Búho Aulamos" class="logo-img">
                <div>
                    <h2>AULAMOS</h2>
                    <p>Aprendemos juntos</p>
                </div>
            </div>

            <nav class="menu">
                <a href="docente_dashboard.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="crear_curso.php" class="menu-item"><i class="fa-solid fa-medal"></i> Crear Curso</a>
                <a href="crear_actividad.php" class="menu-item active"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
                <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                <a href="mas.php" class="menu-item"><i class="fa-solid fa-bars"></i> Mas</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-universal-access"></i> Accesibilidad</a>

                <div class="menu-spacer"></div>
                <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
            </nav>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content">

            <!-- ENCABEZADO -->
            <header class="content-header">
                <div class="welcome-text">
                    <h1>Crear actividad</h1>
                    <p>Diseña las nuevas actividades</p>
                </div>
                <div class="header-actions">
                    <button class="btn-assistant" id="btn-asistente">Asistente Virtual <span class="robot-icon">🤖</span></button>
                    <div class="icon-bell-container">
                        <i class="fa-regular fa-bell"></i>
                    </div>
                    <div class="user-profile">
                        <img src="https://placehold.co/40x40/ff7675/white?text=👩" alt="Avatar Docente" class="avatar">
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($nombre_docente); ?></span>
                            <span class="user-role">Docente</span>
                        </div>
                        <i class="fa-solid fa-chevron-down drop-icon"></i>
                    </div>
                </div>
            </header>

            <!-- FORMULARIO DE ACTIVIDAD -->
            <div class="main-grid activity-layout">
                <!-- COLUMNA IZQUIERDA -->
                <div class="left-column">
                    <form class="activity-form" action="procesar_actividad.php" method="POST">
                        <?php
                        if (isset($_SESSION['errores'])) {
                            foreach ($_SESSION['errores'] as $error) {
                                echo "<div class='error-message'>$error</div>";
                            }
                            unset($_SESSION['errores']);
                        }
                        ?>

                        <div class="form-group-clean">
                            <label for="titulo">Título</label>
                            <input type="text" id="titulo" name="titulo" class="clean-input" required>
                        </div>

                        <div class="form-group-clean">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" class="clean-textarea" rows="3" required></textarea>
                        </div>

                        <div class="form-group-clean">
                            <label for="instrucciones">Instrucciones para los estudiantes</label>
                            <div class="rich-text-editor">
                                <div class="editor-toolbar">
                                    <button type="button" style="font-weight: bold;">B</button>
                                    <button type="button" style="font-style: italic;">I</button>
                                    <button type="button" style="text-decoration: underline;">U</button>
                                </div>
                                <textarea id="instrucciones" name="instrucciones" class="clean-textarea no-top-border" rows="4" placeholder="Escribe las instrucciones aquí" required></textarea>
                            </div>
                        </div>

                        <div class="form-toggle-row">
                            <label for="permite_entrega_archivo">Permitir la entrega de archivos</label>
                            <label class="switch">
                                <input type="checkbox" id="permite_entrega_archivo" name="permite_entrega_archivo" checked value="1">
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <div class="form-group-clean">
                            <label for="grupo">Grupo</label>
                            <select id="grupo" name="grupo" class="clean-input" required>
                                <option value="">Selecciona un grupo</option>
                                <?php
                                foreach ($grupos as $grupo) {
                                    echo "<option value='" . $grupo['id_grupo'] . "'>" . htmlspecialchars($grupo['nombre']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group-clean mt-20">
                            <label for="fecha_limite">Fecha límite</label>
                            <div class="date-input-container">
                                <input type="datetime-local" id="fecha_limite" name="fecha_limite" class="clean-input" required>
                                <i class="fa-regular fa-calendar-days"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">Crear Actividad</button>
                    </form>
                </div>

                <!-- COLUMNA DERECHA -->
                <div class="right-column">
                    <!-- Calendario -->
                    <aside class="calendar-container">
                        <!-- Cabecera y Navegación -->
                        <div class="calendar-header">
                            <div class="nav-left">
                                <button id="prev-year" class="nav-btn" title="Año anterior">&laquo;</button>
                                <button id="prev-month" class="nav-btn" title="Mes anterior">&lsaquo;</button>
                            </div>

                            <h2 id="month-year-title">MES AÑO</h2>

                            <div class="nav-right">
                                <button id="next-month" class="nav-btn" title="Mes siguiente">&rsaquo;</button>
                                <button id="next-year" class="nav-btn" title="Año siguiente">&raquo;</button>
                            </div>
                        </div>

                        <!-- Días de la semana -->
                        <div class="calendar-weekdays">
                            <div class="weekday">Do</div>
                            <div class="weekday">Lu</div>
                            <div class="weekday">Ma</div>
                            <div class="weekday">Mi</div>
                            <div class="weekday">Ju</div>
                            <div class="weekday">Vi</div>
                            <div class="weekday">Sá</div>
                        </div>

                        <!-- Contenedor dinámico de los días -->
                        <div id="calendar-days" class="calendar-days-grid">
                            <!-- JavaScript inyectará los días aquí -->
                        </div>
                    </aside>
                </div>
            </div>

            <!-- BARRA ACCESIBILIDAD -->
            <footer class="accessibility-bar">
                <div class="acc-info">
                    <div class="acc-icon-box">
                        <i class="fa-solid fa-universal-access acc-icon-main"></i>
                    </div>
                    <div>
                        <strong>Accesibilidad siempre disponible</strong>
                        <p>Personaliza tu experiencia en cualquier momento.</p>
                    </div>
                </div>
                <div class="acc-options">
                    <button class="acc-opt-btn" id="btn-contrast"><i class="fa-solid fa-eye"></i><span>Alto contraste</span></button>
                    <button class="acc-opt-btn" id="btn-darkmode"><i class="fa-solid fa-moon"></i><span>Modo oscuro</span></button>
                    <button class="acc-opt-btn" id="btn-text-size"><span class="font-icon">Aa</span><span>Texto grande</span></button>
                    <button class="acc-opt-btn"><i class="fa-solid fa-volume-high"></i><span>Leer pantalla</span></button>
                    <button class="acc-opt-btn"><i class="fa-solid fa-closed-captioning"></i><span>Subtítulos</span></button>
                    <button class="acc-opt-btn"><i class="fa-solid fa-keyboard"></i><span>Navegación<br>por teclado</span></button>
                </div>
                <button class="btn-open-config">Abrir configuración</button>
            </footer>
        </main>
    </div>

    <!-- JavaScript para validación del formulario -->
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const form = document.querySelector(".activity-form");

        if (form) {
            form.addEventListener("submit", (e) => {
                let isValid = true;

                // Validar título
                const titulo = document.getElementById("titulo");
                if (!titulo.value.trim()) {
                    isValid = false;
                    alert("El título es obligatorio.");
                }

                // Validar descripción
                const descripcion = document.getElementById("descripcion");
                if (!descripcion.value.trim()) {
                    isValid = false;
                    alert("La descripción es obligatoria.");
                }

                // Validar instrucciones
                const instrucciones = document.getElementById("instrucciones");
                if (!instrucciones.value.trim()) {
                    isValid = false;
                    alert("Las instrucciones para los estudiantes son obligatorias.");
                }

                // Validar grupo
                const grupo = document.getElementById("grupo");
                if (!grupo.value) {
                    isValid = false;
                    alert("El grupo es obligatorio.");
                }

                // Validar fecha límite
                const fechaLimite = document.getElementById("fecha_limite");
                if (!fechaLimite.value) {
                    isValid = false;
                    alert("La fecha límite es obligatoria.");
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });
        }
    });
    </script>

    <!-- JavaScript para el calendario -->
    <script src="jss/docente_dashboard.js"></script>
</body>
</html>