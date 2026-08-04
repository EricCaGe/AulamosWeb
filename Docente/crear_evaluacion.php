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
    <title>Crear Evaluación - Aulamos</title>

    <!-- CSS Base -->
    <link rel="stylesheet" href="styles/docente.css">
    <!-- CSS Específico para esta pantalla -->
    <link rel="stylesheet" href="styles/evaluacion.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="dashboard-container">

        <!-- BARRA LATERAL (RECICLADA) -->
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
                <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="crear_evaluacion.php" class="menu-item active"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
                <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                <a href="mas.php" class="menu-item"><i class="fa-solid fa-bars"></i> Mas</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-universal-access"></i> Accesibilidad</a>

                <div class="menu-spacer"></div>
                <a href="login.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
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
                            <label for="materia">Seleccionar materia <span class="required-star">*</span></label>
                            <select class="clean-input" id="materia" name="materia" required>
                                <option value="" disabled selected>Seleccione una materia</option>
                                <?php foreach ($materias as $materia): ?>
                                    <option value="<?php echo $materia['id_materia']; ?>">
                                        <?php echo htmlspecialchars($materia['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message" id="error-materia"></div>
                        </div>

                        <!-- Tipo de evaluación -->
                        <div class="form-group-clean mt-20">
                            <label>Tipo de evaluación <span class="required-star">*</span></label>
                            <div class="eval-type-container">
                                <label class="eval-radio-card">
                                    <input type="radio" name="tipo_evaluacion" value="Cuestionario" checked>
                                    <span class="custom-radio"></span>
                                    <span class="radio-label">Cuestionario</span>
                                </label>
                                <label class="eval-radio-card">
                                    <input type="radio" name="tipo_evaluacion" value="Tarea">
                                    <span class="custom-radio"></span>
                                    <span class="radio-label">Tarea</span>
                                </label>
                                <label class="eval-radio-card">
                                    <input type="radio" name="tipo_evaluacion" value="Examen">
                                    <span class="custom-radio"></span>
                                    <span class="radio-label">Examen</span>
                                </label>
                            </div>
                            <div class="error-message" id="error-tipo"></div>
                        </div>

                        <!-- Fecha límite -->
                        <div class="form-group-clean">
                            <label for="fecha_limite">Fecha límite <span class="text-muted text-normal">(Opcional)</span></label>
                            <div class="date-input-container">
                                <input type="date" class="clean-input" id="fecha_limite" name="fecha_limite">
                                <i class="fa-regular fa-calendar-days"></i>
                            </div>
                            <div class="error-message" id="error-fecha"></div>
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
                        <button type="button" class="btn-outline-blue" id="btnCancelar">Cancelar</button>
                        <button type="submit" class="btn-light-blue" id="btnCrearEvaluacion" form="evalForm" disabled>Crear evaluación</button>
                    </div>
                </div>
            </div>

            <!-- BARRA ACCESIBILIDAD (RECICLADA) -->
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
            const materiaSelect = document.getElementById('materia');
            const tipoEvaluacionRadios = document.querySelectorAll('input[name="tipo_evaluacion"]');
            const fechaLimiteInput = document.getElementById('fecha_limite');
            const btnCrearEvaluacion = document.getElementById('btnCrearEvaluacion');
            const btnCancelar = document.getElementById('btnCancelar');

            // Campos de error
            const errorTitulo = document.getElementById('error-titulo');
            const errorDescripcion = document.getElementById('error-descripcion');
            const errorMateria = document.getElementById('error-materia');
            const errorTipo = document.getElementById('error-tipo');
            const errorFecha = document.getElementById('error-fecha');

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

                // Validar tipo de evaluación
                const tipoSeleccionado = document.querySelector('input[name="tipo_evaluacion"]:checked');
                if (!tipoSeleccionado) {
                    errorTipo.textContent = 'Debe seleccionar un tipo de evaluación.';
                    isValid = false;
                } else {
                    errorTipo.textContent = '';
                }

                // Validar fecha límite (opcional, pero si se ingresa debe ser válida)
                if (fechaLimiteInput.value && !validarFecha(fechaLimiteInput.value)) {
                    errorFecha.textContent = 'Formato de fecha no válido. Use YYYY-MM-DD.';
                    isValid = false;
                } else {
                    errorFecha.textContent = '';
                }

                // Habilitar/deshabilitar botón de crear evaluación
                btnCrearEvaluacion.disabled = !isValid;
            }

            // Función para validar formato de fecha (YYYY-MM-DD)
            function validarFecha(fecha) {
                const regex = /^\d{4}-\d{2}-\d{2}$/;
                if (!regex.test(fecha)) return false;

                const date = new Date(fecha);
                return !isNaN(date.getTime());
            }

            // Event listeners para validar en tiempo real
            tituloInput.addEventListener('input', validarFormulario);
            descripcionInput.addEventListener('input', validarFormulario);
            materiaSelect.addEventListener('change', validarFormulario);

            // Event listeners para los radios de tipo de evaluación
            tipoEvaluacionRadios.forEach(radio => {
                radio.addEventListener('change', validarFormulario);
            });

            // Event listener para la fecha límite
            fechaLimiteInput.addEventListener('change', validarFormulario);

            // Event listener para el botón Cancelar
            btnCancelar.addEventListener('click', function() {
                form.reset();
                btnCrearEvaluacion.disabled = true;
                // Limpiar mensajes de error
                errorTitulo.textContent = '';
                errorDescripcion.textContent = '';
                errorMateria.textContent = '';
                errorTipo.textContent = '';
                errorFecha.textContent = '';
            });

            // Validar al cargar la página (por si hay valores pre-seleccionados)
            validarFormulario();
        });
    </script>
    <script src="jss/docente_dashboard.js"></script>
</body>
</html>