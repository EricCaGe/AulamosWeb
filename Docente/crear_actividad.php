<?php
session_start();

// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

// Verificar que el usuario haya iniciado sesión y sea Docente
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

// Obtener datos del docente
$id_docente = $_SESSION['usuario']['id_usuario'];
$nombre_docente = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];

// Consultar los cursos del docente para el select
$query_cursos = "
    SELECT c.id_curso, c.nombre
    FROM cursos c
    WHERE c.id_docente = ? AND c.estado = 'Activo'
    ORDER BY c.nombre
";
$stmt_cursos = $conexion->prepare($query_cursos);
$stmt_cursos->bind_param("i", $id_docente);
$stmt_cursos->execute();
$result_cursos = $stmt_cursos->get_result();

$cursos = [];
while ($row = $result_cursos->fetch_assoc()) {
    $cursos[] = $row;
}

// Consultar los periodos de evaluación disponibles
$query_periodos = "
    SELECT p.id_periodo, p.nombre
    FROM periodos_evaluacion p
    JOIN ciclos_escolares c ON p.id_ciclo = c.id_ciclo
    WHERE c.estado = 'Activo' AND p.estado = 'Activo'
    ORDER BY p.fecha_inicio
";
$stmt_periodos = $conexion->prepare($query_periodos);
$stmt_periodos->execute();
$result_periodos = $stmt_periodos->get_result();

$periodos = [];
while ($row = $result_periodos->fetch_assoc()) {
    $periodos[] = $row;
}

// Cerrar conexiones
$stmt_cursos->close();
$stmt_periodos->close();
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Actividad</title>

    <!-- CSS Base -->
    <link rel="stylesheet" href="styles/docente.css">
    <!-- CSS Específico para esta pantalla -->
    <link rel="stylesheet" href="styles/actividad.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- NUEVA ACCESIBILIDAD -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
</head>
<body>

<div class="dashboard-container">

    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logo_g.png" alt="Búho Aulamos" class="logo-img">
        </div>

        <nav class="menu">
            <a href="docente_dashboard.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="crear_recurso.php" class="menu-item"><i class="fa-solid fa-medal"></i> Crear Recurso</a>
            <a href="crear_actividad.php" class="menu-item active"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
            <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
            <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
            <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
            <a href="pasarlista.php" class="menu-item"><i class="fa-solid fa-bars"></i> Pasar Lista</a>

            <div class="menu-spacer"></div>
            <button class="btn-accessibility-main" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
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
                <button type="button" class="btn-assistant" id="btn-asistente" onclick="window.location.href='../Alumno/ChatbotDocente.php?rol=docente'">
                    Asistente Virtual <span class="robot-icon">🤖</span>
                </button>
                <div class="icon-bell-container">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <div class="user-profile">
                    <img src="https://placehold.co/40x40/ff7675/white?text=👨" alt="Avatar Docente" class="avatar">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($nombre_docente); ?></span>
                        <span class="user-role">Docente</span>
                    </div>
                </div>
            </div>
            </header>

            <!-- MENSAJES DE ÉXITO O ERROR -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?>">
                    <?php echo $_SESSION['mensaje']; ?>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
                <?php unset($_SESSION['tipo_mensaje']); ?>
            <?php endif; ?>

            <!-- FORMULARIO DE ACTIVIDAD -->
            <div class="main-grid activity-layout">
                <!-- COLUMNA IZQUIERDA -->
                <div class="left-column">
                    <form class="activity-form" action="procesar_actividad.php" method="POST">
                        <div class="form-group-clean">
                            <label for="titulo">Título <span class="required-field">*</span></label>
                            <input type="text" id="titulo" name="titulo" class="clean-input" placeholder="Ej: Tarea de matemáticas" required>
                        </div>

                        <div class="form-group-clean">
                            <label for="descripcion">Descripción <span class="required-field">*</span></label>
                            <textarea id="descripcion" name="descripcion" class="clean-textarea" rows="3" placeholder="Describe brevemente la actividad" required></textarea>
                        </div>

                        <div class="form-group-clean">
                            <label for="instrucciones">Instrucciones para los estudiantes <span class="required-field">*</span></label>
                            <div class="rich-text-editor">
                                <div class="editor-toolbar">
                                    <button type="button" onclick="formatText('bold')" title="Negrita"><i class="fa-solid fa-bold"></i></button>
                                    <button type="button" onclick="formatText('italic')" title="Cursiva"><i class="fa-solid fa-italic"></i></button>
                                    <button type="button" onclick="formatText('underline')" title="Subrayado"><i class="fa-solid fa-underline"></i></button>
                                </div>
                                <textarea id="instrucciones" name="instrucciones" class="clean-textarea no-top-border" rows="4" placeholder="Escribe las instrucciones aquí" required></textarea>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group-clean">
                                <label for="tipo">Tipo de actividad <span class="required-field">*</span></label>
                                <select id="tipo" name="tipo" class="clean-input" required>
                                    <option value="">Selecciona un tipo</option>
                                    <option value="Tarea">Tarea</option>
                                    <option value="Ejercicio">Ejercicio</option>
                                    <option value="Lectura">Lectura</option>
                                    <option value="Proyecto">Proyecto</option>
                                </select>
                            </div>

                            <div class="form-group-clean">
                                <label for="puntaje_maximo">Puntaje máximo <span class="required-field">*</span></label>
                                <input type="number" id="puntaje_maximo" name="puntaje_maximo" class="clean-input" value="100.00" min="0" max="1000" step="0.01" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group-clean">
                                <label for="id_curso">Curso <span class="required-field">*</span></label>
                                <select id="id_curso" name="id_curso" class="clean-input" required>
                                    <option value="">Selecciona un curso</option>
                                    <?php foreach ($cursos as $curso): ?>
                                        <option value="<?php echo $curso['id_curso']; ?>">
                                            <?php echo htmlspecialchars($curso['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group-clean">
                                <label for="id_periodo">Periodo de evaluación</label>
                                <select id="id_periodo" name="id_periodo" class="clean-input">
                                    <option value="">Ninguno</option>
                                    <?php foreach ($periodos as $periodo): ?>
                                        <option value="<?php echo $periodo['id_periodo']; ?>">
                                            <?php echo htmlspecialchars($periodo['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-toggle-row">
                            <label for="permite_entrega_archivo">Permitir la entrega de archivos</label>
                            <label class="switch">
                                <input type="checkbox" id="permite_entrega_archivo" name="permite_entrega_archivo" checked value="1">
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <div class="form-group-clean mt-20">
                            <label for="fecha_limite">Fecha límite <span class="required-field">*</span></label>
                            <input type="datetime-local" id="fecha_limite" name="fecha_limite" class="clean-input" required>
                        </div>

                        <!-- Botones -->
                        <div class="button-group">
                            <button type="button" class="btn-outline-gray" onclick="limpiarFormulario()">
                                <i class="fa-solid fa-times"></i> Cancelar
                            </button>
                            <button type="submit" class="btn-outline-blue">
                                <i class="fa-solid fa-plus"></i> Crear Actividad
                            </button>
                        </div>
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
        </header>

        <!-- MENSAJES DE ÉXITO O ERROR -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?>">
                <?php echo $_SESSION['mensaje']; ?>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
            <?php unset($_SESSION['tipo_mensaje']); ?>
        <?php endif; ?>

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

<!-- ========================================== -->
<!-- SCRIPTS                                    -->
<!-- ========================================== -->
<script src="jss/docente_dashboard.js"></script>

<!-- NUEVA ACCESIBILIDAD -->
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>

<!-- JavaScript para el editor de texto y limpiar formulario -->
<script>
    // Función para formatear texto
    function formatText(command) {
        const textarea = document.getElementById('instrucciones');
        textarea.focus();
        document.execCommand(command, false, null);
    }

    // Función para limpiar el formulario
    function limpiarFormulario() {
        if (confirm("¿Estás seguro de que deseas cancelar la creación de la actividad? Todos los campos se borrarán.")) {
            document.querySelector(".activity-form").reset();
        }
    }

    // Validación del formulario
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
                    titulo.focus();
                    return false;
                }

                // Validar descripción
                const descripcion = document.getElementById("descripcion");
                if (!descripcion.value.trim()) {
                    isValid = false;
                    alert("La descripción es obligatoria.");
                    descripcion.focus();
                    return false;
                }

                // Validar instrucciones
                const instrucciones = document.getElementById("instrucciones");
                if (!instrucciones.value.trim()) {
                    isValid = false;
                    alert("Las instrucciones para los estudiantes son obligatorias.");
                    instrucciones.focus();
                    return false;
                }

                // Validar tipo
                const tipo = document.getElementById("tipo");
                if (!tipo.value) {
                    isValid = false;
                    alert("El tipo de actividad es obligatorio.");
                    tipo.focus();
                    return false;
                }

                // Validar curso
                const curso = document.getElementById("id_curso");
                if (!curso.value) {
                    isValid = false;
                    alert("El curso es obligatorio.");
                    curso.focus();
                    return false;
                }

                // Validar puntaje máximo
                const puntaje = document.getElementById("puntaje_maximo");
                if (!puntaje.value || parseFloat(puntaje.value) <= 0) {
                    isValid = false;
                    alert("El puntaje máximo debe ser mayor a 0.");
                    puntaje.focus();
                    return false;
                }

                // Validar fecha límite
                const fechaLimite = document.getElementById("fecha_limite");
                if (!fechaLimite.value) {
                    isValid = false;
                    alert("La fecha límite es obligatoria.");
                    fechaLimite.focus();
                    return false;
                }

                // Validar que la fecha límite sea en el futuro
                const fechaLimiteDate = new Date(fechaLimite.value);
                const ahora = new Date();
                if (fechaLimiteDate <= ahora) {
                    isValid = false;
                    alert("La fecha límite debe ser en el futuro.");
                    fechaLimite.focus();
                    return false;
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });
        }
    });
</script>

</body>
</html>