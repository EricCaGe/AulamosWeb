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

// Consultar materias del docente
$query_materias = "
    SELECT DISTINCT m.id_materia, m.nombre
    FROM cursos c
    JOIN materias m ON c.id_materia = m.id_materia
    WHERE c.id_docente = ? AND c.estado = 'Activo'
    ORDER BY m.nombre
";
$stmt_materias = $conexion->prepare($query_materias);
$stmt_materias->bind_param("i", $id_docente);
$stmt_materias->execute();
$result_materias = $stmt_materias->get_result();

$materias = [];
while ($row = $result_materias->fetch_assoc()) {
    $materias[] = $row;
}

// Consultar cursos del docente
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

// Consultar periodos de evaluación
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
$stmt_materias->close();
$stmt_cursos->close();
$stmt_periodos->close();
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Evaluación - Aulamos</title>

    <!-- CSS Base -->
    <link rel="stylesheet" href="styles/docente.css">
    <!-- CSS Específico para esta pantalla -->
    <link rel="stylesheet" href="styles/evaluacion.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="crear_evaluacion.php" class="menu-item active"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
                <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
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
                    <h1>Crear evaluación</h1>
                    <p>Diseña tus propias evaluaciones</p>
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

            <!-- MENSAJES DE ÉXITO O ERROR -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?>">
                    <?php echo $_SESSION['mensaje']; ?>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
                <?php unset($_SESSION['tipo_mensaje']); ?>
            <?php endif; ?>

            <!-- FORMULARIO DE EVALUACIÓN -->
            <div class="main-grid eval-layout">
                <!-- COLUMNA IZQUIERDA -->
                <div class="left-column">
                    <form class="eval-form" id="evalForm" method="POST" action="procesar_evaluacion.php">

                        <!-- Título de evaluación -->
                        <div class="form-group-clean">
                            <label for="titulo">Título de evaluación <span class="required-star">*</span></label>
                            <input type="text" class="clean-input" id="titulo" name="titulo" placeholder="Ej: Examen de Matemáticas" required>
                            <div class="error-message" id="error-titulo"></div>
                        </div>

                        <!-- Descripción -->
                        <div class="form-group-clean">
                            <label for="descripcion">Descripción <span class="required-star">*</span></label>
                            <textarea class="clean-input" id="descripcion" name="descripcion" placeholder="Describe el objetivo de la evaluación" rows="3" required></textarea>
                            <div class="error-message" id="error-descripcion"></div>
                        </div>

                        <!-- Seleccionar materia -->
                        <div class="form-group-clean">
                            <label for="id_materia">Seleccionar materia <span class="required-star">*</span></label>
                            <select class="clean-input" id="id_materia" name="id_materia" required>
                                <option value="" disabled selected>Seleccione una materia</option>
                                <?php foreach ($materias as $materia): ?>
                                    <option value="<?php echo $materia['id_materia']; ?>">
                                        <?php echo htmlspecialchars($materia['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message" id="error-materia"></div>
                        </div>

                        <!-- Seleccionar curso -->
                        <div class="form-group-clean">
                            <label for="id_curso">Seleccionar curso <span class="required-star">*</span></label>
                            <select class="clean-input" id="id_curso" name="id_curso" required>
                                <option value="" disabled selected>Seleccione un curso</option>
                                <?php foreach ($cursos as $curso): ?>
                                    <option value="<?php echo $curso['id_curso']; ?>">
                                        <?php echo htmlspecialchars($curso['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message" id="error-curso"></div>
                        </div>

                        <!-- SECCIÓN DE PREGUNTAS (movida aquí) -->
                        <div id="cuestionario-section" class="dynamic-section">
                            <h3>Preguntas del cuestionario</h3>
                            <div id="preguntas-container">
                                <!-- Pregunta inicial -->
                                <div class="pregunta-item">
                                    <div class="pregunta-header">
                                        <label>Pregunta 1 <span class="required-star">*</span></label>
                                        <button type="button" class="btn-remove-pregunta" onclick="eliminarPregunta(this)">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                    <textarea class="clean-input" name="preguntas[0][texto]" placeholder="Escribe la pregunta aquí" rows="2" required></textarea>
                                    <div class="pregunta-type-container">
                                        <label class="pregunta-radio">
                                            <input type="radio" name="preguntas[0][tipo]" value="OpcionMultiple" checked>
                                            <span class="custom-radio"></span>
                                            <span>Opción múltiple</span>
                                        </label>
                                        <label class="pregunta-radio">
                                            <input type="radio" name="preguntas[0][tipo]" value="Abierta">
                                            <span class="custom-radio"></span>
                                            <span>Pregunta abierta</span>
                                        </label>
                                        <label class="pregunta-radio">
                                            <input type="radio" name="preguntas[0][tipo]" value="VerdaderoFalso">
                                            <span class="custom-radio"></span>
                                            <span>Verdadero/Falso</span>
                                        </label>
                                    </div>
                                    <!-- Opciones para pregunta de opción múltiple o verdadero/falso -->
                                    <div class="opciones-container">
                                        <div class="opcion-item">
                                            <input type="text" class="clean-input" name="preguntas[0][opciones][]" placeholder="Opción 1" required>
                                            <label class="opcion-radio">
                                                <input type="radio" name="preguntas[0][respuesta_correcta]" value="0" checked>
                                                <span class="custom-radio small"></span>
                                            </label>
                                        </div>
                                        <div class="opcion-item">
                                            <input type="text" class="clean-input" name="preguntas[0][opciones][]" placeholder="Opción 2" required>
                                            <label class="opcion-radio">
                                                <input type="radio" name="preguntas[0][respuesta_correcta]" value="1">
                                                <span class="custom-radio small"></span>
                                            </label>
                                        </div>
                                        <div class="opcion-item">
                                            <input type="text" class="clean-input" name="preguntas[0][opciones][]" placeholder="Opción 3">
                                            <label class="opcion-radio">
                                                <input type="radio" name="preguntas[0][respuesta_correcta]" value="2">
                                                <span class="custom-radio small"></span>
                                            </label>
                                        </div>
                                        <div class="opcion-item">
                                            <input type="text" class="clean-input" name="preguntas[0][opciones][]" placeholder="Opción 4">
                                            <label class="opcion-radio">
                                                <input type="radio" name="preguntas[0][respuesta_correcta]" value="3">
                                                <span class="custom-radio small"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="pregunta-actions">
                                        <button type="button" class="btn-add-opcion" onclick="agregarOpcion(this)">
                                            <i class="fa-solid fa-plus"></i> Agregar opción
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn-add-pregunta" id="btnAddPregunta">
                                <i class="fa-solid fa-plus"></i> Agregar otra pregunta
                            </button>
                        </div>

                        <!-- Puntaje máximo -->
                        <div class="form-group-clean">
                            <label for="puntaje_maximo">Puntaje máximo <span class="required-star">*</span></label>
                            <input type="number" class="clean-input" id="puntaje_maximo" name="puntaje_maximo" value="100.00" min="0" max="1000" step="0.01" required>
                            <div class="error-message" id="error-puntaje"></div>
                        </div>

                        <!-- Fecha límite -->
                        <div class="form-group-clean">
                            <label for="fecha_limite">Fecha límite <span class="text-muted text-normal">(Opcional)</span></label>
                            <input type="datetime-local" class="clean-input" id="fecha_limite" name="fecha_limite">
                            <div class="error-message" id="error-fecha"></div>
                        </div>

                        <!-- Periodo de evaluación -->
                        <div class="form-group-clean">
                            <label for="id_periodo">Periodo de evaluación</label>
                            <select class="clean-input" id="id_periodo" name="id_periodo">
                                <option value="">Ninguno</option>
                                <?php foreach ($periodos as $periodo): ?>
                                    <option value="<?php echo $periodo['id_periodo']; ?>">
                                        <?php echo htmlspecialchars($periodo['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Campo oculto para el tipo de evaluación (fijo como Cuestionario) -->
                        <input type="hidden" id="hidden-tipo" name="tipo_evaluacion" value="Cuestionario">
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

                            <h2 id="month-year-title">AGOSTO 2026</h2>

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

                    <!-- BOTONES DE ACCIÓN -->
                    <div class="eval-buttons">
                        <button type="button" class="btn-outline-gray" id="btnCancelar">Cancelar</button>
                        <button type="submit" class="btn-outline-blue" id="btnCrearEvaluacion" form="evalForm" disabled>Crear evaluación</button>
                    </div>
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

    <!-- JavaScript para validaciones y funcionalidad -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos del formulario
            const form = document.getElementById('evalForm');
            const tituloInput = document.getElementById('titulo');
            const descripcionInput = document.getElementById('descripcion');
            const materiaSelect = document.getElementById('id_materia');
            const cursoSelect = document.getElementById('id_curso');
            const puntajeInput = document.getElementById('puntaje_maximo');
            const btnCrearEvaluacion = document.getElementById('btnCrearEvaluacion');
            const btnCancelar = document.getElementById('btnCancelar');

            // Campos de error
            const errorTitulo = document.getElementById('error-titulo');
            const errorDescripcion = document.getElementById('error-descripcion');
            const errorMateria = document.getElementById('error-materia');
            const errorCurso = document.getElementById('error-curso');
            const errorPuntaje = document.getElementById('error-puntaje');

            // Contador de preguntas
            let preguntaCount = 1;

            // Función para validar el formulario
            function validarFormulario() {
                let isValid = true;

                // Validar título
                if (tituloInput.value.trim() === '') {
                    errorTitulo.textContent = 'El título es obligatorio.';
                    isValid = false;
                } else {
                    errorTitulo.textContent = '';
                }

                // Validar descripción
                if (descripcionInput.value.trim() === '') {
                    errorDescripcion.textContent = 'La descripción es obligatoria.';
                    isValid = false;
                } else {
                    errorDescripcion.textContent = '';
                }

                // Validar materia
                if (materiaSelect.value === '') {
                    errorMateria.textContent = 'Debe seleccionar una materia.';
                    isValid = false;
                } else {
                    errorMateria.textContent = '';
                }

                // Validar curso
                if (cursoSelect.value === '') {
                    errorCurso.textContent = 'Debe seleccionar un curso.';
                    isValid = false;
                } else {
                    errorCurso.textContent = '';
                }

                // Validar puntaje máximo
                if (!puntajeInput.value || parseFloat(puntajeInput.value) <= 0) {
                    errorPuntaje.textContent = 'El puntaje máximo debe ser mayor a 0.';
                    isValid = false;
                } else {
                    errorPuntaje.textContent = '';
                }

                // Validar preguntas (solo si hay preguntas)
                const preguntas = document.querySelectorAll('.pregunta-item');
                if (preguntas.length > 0) {
                    preguntas.forEach((pregunta, index) => {
                        const textoPregunta = pregunta.querySelector('textarea[name^="preguntas"]');
                        if (textoPregunta && textoPregunta.value.trim() === '') {
                            isValid = false;
                        }

                        const tipoPregunta = pregunta.querySelector('input[name^="preguntas"][name$="[tipo]"]:checked');
                        if (!tipoPregunta) {
                            isValid = false;
                        }

                        if (tipoPregunta && (tipoPregunta.value === 'OpcionMultiple' || tipoPregunta.value === 'VerdaderoFalso')) {
                            const opciones = pregunta.querySelectorAll('input[name^="preguntas"][name$="[opciones][]"]');
                            let hasEmptyOption = false;
                            opciones.forEach(opcion => {
                                if (opcion.value.trim() === '') {
                                    hasEmptyOption = true;
                                }
                            });
                            if (hasEmptyOption) {
                                isValid = false;
                            }

                            const respuestaCorrecta = pregunta.querySelector('input[name^="preguntas"][name$="[respuesta_correcta]"]:checked');
                            if (!respuestaCorrecta) {
                                isValid = false;
                            }
                        }
                    });
                } else {
                    isValid = false;
                }

                // Habilitar/deshabilitar botón de crear evaluación
                btnCrearEvaluacion.disabled = !isValid;
                return isValid;
            }

            // Función para limpiar el formulario
            function limpiarFormulario() {
                if (confirm("¿Estás seguro de que deseas cancelar la creación de la evaluación? Todos los campos se borrarán.")) {
                    form.reset();
                    const preguntasContainer = document.getElementById('preguntas-container');
                    preguntasContainer.innerHTML = `
                        <div class="pregunta-item">
                            <div class="pregunta-header">
                                <label>Pregunta 1 <span class="required-star">*</span></label>
                                <button type="button" class="btn-remove-pregunta" onclick="eliminarPregunta(this)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                            <textarea class="clean-input" name="preguntas[0][texto]" placeholder="Escribe la pregunta aquí" rows="2" required></textarea>
                            <div class="pregunta-type-container">
                                <label class="pregunta-radio">
                                    <input type="radio" name="preguntas[0][tipo]" value="OpcionMultiple" checked>
                                    <span class="custom-radio"></span>
                                    <span>Opción múltiple</span>
                                </label>
                                <label class="pregunta-radio">
                                    <input type="radio" name="preguntas[0][tipo]" value="Abierta">
                                    <span class="custom-radio"></span>
                                    <span>Pregunta abierta</span>
                                </label>
                                <label class="pregunta-radio">
                                    <input type="radio" name="preguntas[0][tipo]" value="VerdaderoFalso">
                                    <span class="custom-radio"></span>
                                    <span>Verdadero/Falso</span>
                                </label>
                            </div>
                            <div class="opciones-container">
                                <div class="opcion-item">
                                    <input type="text" class="clean-input" name="preguntas[0][opciones][]" placeholder="Opción 1" required>
                                    <label class="opcion-radio">
                                        <input type="radio" name="preguntas[0][respuesta_correcta]" value="0" checked>
                                        <span class="custom-radio small"></span>
                                    </label>
                                </div>
                                <div class="opcion-item">
                                    <input type="text" class="clean-input" name="preguntas[0][opciones][]" placeholder="Opción 2" required>
                                    <label class="opcion-radio">
                                        <input type="radio" name="preguntas[0][respuesta_correcta]" value="1">
                                        <span class="custom-radio small"></span>
                                    </label>
                                </div>
                                <div class="opcion-item">
                                    <input type="text" class="clean-input" name="preguntas[0][opciones][]" placeholder="Opción 3">
                                    <label class="opcion-radio">
                                        <input type="radio" name="preguntas[0][respuesta_correcta]" value="2">
                                        <span class="custom-radio small"></span>
                                    </label>
                                </div>
                                <div class="opcion-item">
                                    <input type="text" class="clean-input" name="preguntas[0][opciones][]" placeholder="Opción 4">
                                    <label class="opcion-radio">
                                        <input type="radio" name="preguntas[0][respuesta_correcta]" value="3">
                                        <span class="custom-radio small"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="pregunta-actions">
                                <button type="button" class="btn-add-opcion" onclick="agregarOpcion(this)">
                                    <i class="fa-solid fa-plus"></i> Agregar opción
                                </button>
                            </div>
                        </div>
                    `;
                    preguntaCount = 1;
                    btnCrearEvaluacion.disabled = true;
                    errorTitulo.textContent = '';
                    errorDescripcion.textContent = '';
                    errorMateria.textContent = '';
                    errorCurso.textContent = '';
                    errorPuntaje.textContent = '';
                }
            }

            // Función para agregar una nueva pregunta
            function agregarPregunta() {
                const preguntasContainer = document.getElementById('preguntas-container');
                const nuevaPregunta = document.createElement('div');
                nuevaPregunta.className = 'pregunta-item';
                nuevaPregunta.innerHTML = `
                    <div class="pregunta-header">
                        <label>Pregunta ${preguntaCount + 1} <span class="required-star">*</span></label>
                        <button type="button" class="btn-remove-pregunta" onclick="eliminarPregunta(this)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                    <textarea class="clean-input" name="preguntas[${preguntaCount}][texto]" placeholder="Escribe la pregunta aquí" rows="2" required></textarea>
                    <div class="pregunta-type-container">
                        <label class="pregunta-radio">
                            <input type="radio" name="preguntas[${preguntaCount}][tipo]" value="OpcionMultiple" checked>
                            <span class="custom-radio"></span>
                            <span>Opción múltiple</span>
                        </label>
                        <label class="pregunta-radio">
                            <input type="radio" name="preguntas[${preguntaCount}][tipo]" value="Abierta">
                            <span class="custom-radio"></span>
                            <span>Pregunta abierta</span>
                        </label>
                        <label class="pregunta-radio">
                            <input type="radio" name="preguntas[${preguntaCount}][tipo]" value="VerdaderoFalso">
                            <span class="custom-radio"></span>
                            <span>Verdadero/Falso</span>
                        </label>
                    </div>
                    <div class="opciones-container">
                        <div class="opcion-item">
                            <input type="text" class="clean-input" name="preguntas[${preguntaCount}][opciones][]" placeholder="Opción 1" required>
                            <label class="opcion-radio">
                                <input type="radio" name="preguntas[${preguntaCount}][respuesta_correcta]" value="0" checked>
                                <span class="custom-radio small"></span>
                            </label>
                        </div>
                        <div class="opcion-item">
                            <input type="text" class="clean-input" name="preguntas[${preguntaCount}][opciones][]" placeholder="Opción 2" required>
                            <label class="opcion-radio">
                                <input type="radio" name="preguntas[${preguntaCount}][respuesta_correcta]" value="1">
                                <span class="custom-radio small"></span>
                            </label>
                        </div>
                        <div class="opcion-item">
                            <input type="text" class="clean-input" name="preguntas[${preguntaCount}][opciones][]" placeholder="Opción 3">
                            <label class="opcion-radio">
                                <input type="radio" name="preguntas[${preguntaCount}][respuesta_correcta]" value="2">
                                <span class="custom-radio small"></span>
                            </label>
                        </div>
                        <div class="opcion-item">
                            <input type="text" class="clean-input" name="preguntas[${preguntaCount}][opciones][]" placeholder="Opción 4">
                            <label class="opcion-radio">
                                <input type="radio" name="preguntas[${preguntaCount}][respuesta_correcta]" value="3">
                                <span class="custom-radio small"></span>
                            </label>
                        </div>
                    </div>
                    <div class="pregunta-actions">
                        <button type="button" class="btn-add-opcion" onclick="agregarOpcion(this)">
                            <i class="fa-solid fa-plus"></i> Agregar opción
                        </button>
                    </div>
                `;
                preguntasContainer.appendChild(nuevaPregunta);
                preguntaCount++;
                validarFormulario();
            }

            // Función para eliminar una pregunta
            function eliminarPregunta(button) {
                if (document.querySelectorAll('.pregunta-item').length > 1) {
                    button.closest('.pregunta-item').remove();
                    // Renumerar preguntas
                    const preguntas = document.querySelectorAll('.pregunta-item');
                    preguntas.forEach((pregunta, index) => {
                        pregunta.querySelector('label').textContent = `Pregunta ${index + 1}`;
                    });
                    preguntaCount = preguntas.length;
                    validarFormulario();
                } else {
                    alert('Debe haber al menos una pregunta.');
                }
            }

            // Función para agregar una opción a una pregunta
            function agregarOpcion(button) {
                const opcionesContainer = button.closest('.pregunta-item').querySelector('.opciones-container');
                const preguntaIndex = Array.from(button.closest('.pregunta-item').parentNode.querySelectorAll('.pregunta-item')).indexOf(button.closest('.pregunta-item'));
                const nuevaOpcionIndex = opcionesContainer.querySelectorAll('.opcion-item').length;

                const nuevaOpcion = document.createElement('div');
                nuevaOpcion.className = 'opcion-item';
                nuevaOpcion.innerHTML = `
                    <input type="text" class="clean-input" name="preguntas[${preguntaIndex}][opciones][]" placeholder="Opción ${nuevaOpcionIndex + 1}">
                    <label class="opcion-radio">
                        <input type="radio" name="preguntas[${preguntaIndex}][respuesta_correcta]" value="${nuevaOpcionIndex}">
                        <span class="custom-radio small"></span>
                    </label>
                `;
                opcionesContainer.appendChild(nuevaOpcion);
            }

            // Event listeners para validar en tiempo real
            tituloInput.addEventListener('input', validarFormulario);
            descripcionInput.addEventListener('input', validarFormulario);
            materiaSelect.addEventListener('change', validarFormulario);
            cursoSelect.addEventListener('change', validarFormulario);
            puntajeInput.addEventListener('input', validarFormulario);

            // Event listener para el botón Cancelar
            btnCancelar.addEventListener('click', limpiarFormulario);

            // Event listener para el botón "Agregar otra pregunta"
            document.getElementById('btnAddPregunta').addEventListener('click', agregarPregunta);

            // Event listener para el envío del formulario
            form.addEventListener('submit', function(e) {
                if (!validarFormulario()) {
                    e.preventDefault();
                    alert('Por favor, complete todos los campos obligatorios.');
                }
            });
        });
    </script>

    <!-- JavaScript para el calendario -->
    <script src="jss/docente_dashboard.js"></script>
</body>
</html>