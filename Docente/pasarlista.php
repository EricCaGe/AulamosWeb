<?php
// Iniciar sesión y verificar que el usuario sea docente
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

// Conexión a la base de datos (ajustar según tu configuración)
require_once '../Conexion/conexion.php';
$id_docente = $_SESSION['usuario']['id_usuario'];
$nombre_docente = $_SESSION['usuario']['nombre'] . ' ' . ($_SESSION['usuario']['apellido_paterno'] ?? '');

// Obtener cursos del docente
$consulta_cursos = "
    SELECT
        c.id_curso,
        c.nombre AS nombre_curso,
        m.nombre AS materia,
        g.nombre AS grupo
    FROM cursos c
    JOIN materias m ON c.id_materia = m.id_materia
    JOIN grupos g ON c.id_grupo = g.id_grupo
    WHERE c.id_docente = $id_docente
";
$resultado_cursos = mysqli_query($conexion, $consulta_cursos);
$cursos = mysqli_fetch_all($resultado_cursos, MYSQLI_ASSOC);

// Obtener ID del curso seleccionado (o el primero si no hay selección)
$id_curso_seleccionado = isset($_GET['id_curso']) ? (int)$_GET['id_curso'] : ($cursos ? $cursos[0]['id_curso'] : null);

// Obtener estudiantes del curso seleccionado
$estudiantes = [];
if ($id_curso_seleccionado) {
    $consulta_estudiantes = "
        SELECT
            u.id_usuario AS id_alumno,
            u.nombre,
            u.apellido_paterno,
            u.apellido_materno,
            CONCAT(u.nombre, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS nombre_completo,
            g.nombre AS grupo,
            c.nombre AS curso,
            m.nombre AS materia
        FROM inscripciones i
        JOIN usuarios u ON i.id_alumno = u.id_usuario
        JOIN cursos c ON i.id_curso = c.id_curso
        JOIN grupos g ON c.id_grupo = g.id_grupo
        JOIN materias m ON c.id_materia = m.id_materia
        WHERE i.id_curso = $id_curso_seleccionado AND i.estado = 'Activo'
        ORDER BY u.apellido_paterno, u.apellido_materno, u.nombre
    ";
    $resultado_estudiantes = mysqli_query($conexion, $consulta_estudiantes);
    $estudiantes = mysqli_fetch_all($resultado_estudiantes, MYSQLI_ASSOC);
}

// Obtener asistencia guardada (si existe en la sesión)
$asistencia_guardada = isset($_SESSION['asistencia']) ? $_SESSION['asistencia'] : [];
$asistencia_curso_actual = $asistencia_guardada[$id_curso_seleccionado] ?? [];

// Guardar asistencia (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_asistencia'])) {
    $id_curso = (int)$_POST['id_curso'];
    $asistencias = $_POST['asistencia'] ?? []; // Array de [id_alumno => estado]

    // Guardar en la sesión (simulando AsyncStorage)
    $_SESSION['asistencia'][$id_curso] = $asistencias;
    $mensaje_exito = "Lista de asistencia guardada correctamente.";
}

// Obtener fecha actual
$fecha_hoy = date('Y-m-d');
$fecha_formateada = date('l, d \d\e F \d\e Y', strtotime($fecha_hoy));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasar Lista - Aulamos</title>
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos adicionales para pasarlista.php */
        .student-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: white;
            margin-bottom: 10px;
        }
        .student-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--blue-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-blue);
        }
        .student-name {
            font-weight: 600;
            color: var(--text-dark);
        }
        .student-group {
            font-size: 12px;
            color: var(--text-muted);
        }
        .attendance-buttons {
            display: flex;
            gap: 6px;
        }
        .attendance-btn {
            padding: 6px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .attendance-btn.active {
            background: var(--green-light);
            border-color: var(--text-green);
            color: var(--text-green);
        }
        .attendance-btn.falta.active {
            background: #fee2e2;
            border-color: #dc2626;
            color: #dc2626;
        }
        .attendance-btn.retardo.active {
            background: #fef3c7;
            border-color: #d97706;
            color: #d97706;
        }
        .summary-container {
            display: flex;
            justify-content: space-around;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 15px;
            margin: 20px 0;
        }
        .summary-item {
            text-align: center;
        }
        .summary-number {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-dark);
        }
        .summary-label {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .course-selector {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 20px;
            cursor: pointer;
        }
        .course-info {
            display: flex;
            flex-direction: column;
        }
        .course-name {
            font-weight: 600;
            color: var(--text-dark);
        }
        .course-details {
            font-size: 12px;
            color: var(--text-muted);
        }
        .save-btn {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
            width: 100%;
        }
        .save-btn:hover {
            background: #2563eb;
        }
        .date-display {
            background: var(--blue-light);
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .date-display strong {
            color: var(--primary-blue);
            font-weight: 600;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .modal-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
        }
        .close-modal {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-muted);
        }
        .course-option {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
        }
        .course-option:hover {
            background: #f1f5f9;
        }
        .course-option.selected {
            background: var(--blue-light);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="logo-section">
                <img src="../img/logo_g.png" alt="Búho Aulamos" class="logo-img">
                
            </div>
            <div class="menu">
                <a href="docente_dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Crear Recurso</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-tasks"></i>
                    <span>Crear Actividad</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Crear Evaluación</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Ver Estudiantes</span>
                </a>
                <a href="pasarlista.php" class="menu-item active">
                    <i class="fas fa-list"></i>
                    <span>Pasar Lista</span>
                </a>
                <div class="menu-spacer"></div>
                <button class="btn-accessibility-main">
                    <i class="fas fa-universal-access"></i>
                    <span>Accesibilidad</span>
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="content-header">
                <div class="welcome-text">
                    <h1>Pasar Lista</h1>
                    <p>Registra la asistencia de tus estudiantes</p>
                </div>
                <div class="header-actions">
                    <button class="btn-assistant" id="btn-asistente">
                        <div class="robot-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <span>Asistente Virtual</span>
                    </button>
                    <div class="icon-bell-container">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="user-profile">
                        <img src="https://via.placeholder.com/45x45" alt="Avatar" class="avatar">
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($nombre_docente) ?></span>
                            <span class="user-role">Docente</span>
                        </div>
                        <i class="fas fa-chevron-down drop-icon"></i>
                    </div>
                </div>
            </div>

            <!-- Contenido Principal -->
            <div class="main-grid">
                <div class="left-column">
                    <!-- Fecha -->
                    <div class="date-display">
                        <i class="fas fa-calendar-alt"></i>
                        <strong><?= $fecha_formateada ?></strong>
                    </div>

                    <!-- Selector de Curso -->
                    <div class="course-selector" onclick="openCourseModal()">
                        <div class="course-info">
                            <?php if ($cursos): ?>
                                <?php
                                $curso_actual = null;
                                foreach ($cursos as $curso) {
                                    if ($curso['id_curso'] == $id_curso_seleccionado) {
                                        $curso_actual = $curso;
                                        break;
                                    }
                                }
                                if (!$curso_actual && $cursos) {
                                    $curso_actual = $cursos[0];
                                    $id_curso_seleccionado = $curso_actual['id_curso'];
                                }
                                ?>
                                <span class="course-name">
                                    <?= htmlspecialchars($curso_actual['nombre_curso'] ?? 'Selecciona un curso') ?>
                                </span>
                                <span class="course-details">
                                    <?= htmlspecialchars(($curso_actual['materia'] ?? '') . ' · ' . ($curso_actual['grupo'] ?? '')) ?>
                                </span>
                            <?php else: ?>
                                <span class="course-name">No hay cursos disponibles</span>
                            <?php endif; ?>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>

                    <!-- Lista de Estudiantes -->
                    <div class="section-container">
                        <div class="section-header">
                            <h3 class="section-title">Estudiantes</h3>
                            <span><?= count($estudiantes) ?> estudiantes registrados</span>
                            <button class="btn-accessibility-main" onclick="marcarTodosPresentes()" style="padding: 6px 12px; font-size: 12px;">
                                <i class="fas fa-check-circle"></i>
                                <span>Todos presentes</span>
                            </button>
                        </div>

                        <?php if (empty($estudiantes)): ?>
                            <p style="text-align: center; color: var(--text-muted); padding: 20px;">
                                No hay estudiantes en este curso.
                            </p>
                        <?php else: ?>
                            <form method="post" action="pasarlista.php">
                                <input type="hidden" name="id_curso" value="<?= $id_curso_seleccionado ?>">

                                <?php foreach ($estudiantes as $estudiante): ?>
                                    <div class="student-list-item">
                                        <div class="student-info">
                                            <div class="student-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <div class="student-name">
                                                    <?= htmlspecialchars($estudiante['nombre_completo']) ?>
                                                </div>
                                                <div class="student-group">
                                                    <?= htmlspecialchars($estudiante['grupo']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="attendance-buttons">
                                            <?php
                                            $estado_actual = $asistencia_curso_actual[$estudiante['id_alumno']] ?? null;
                                            ?>
                                            <label class="attendance-btn <?php echo ($estado_actual === 'Presente') ? 'active' : ''; ?>">
                                                <input type="radio" name="asistencia[<?= $estudiante['id_alumno'] ?>]"
                                                       value="Presente" <?= ($estado_actual === 'Presente') ? 'checked' : '' ?>>
                                                <i class="fas fa-check-circle"></i>
                                                <span>Presente</span>
                                            </label>
                                            <label class="attendance-btn falta <?php echo ($estado_actual === 'Falta') ? 'active' : ''; ?>">
                                                <input type="radio" name="asistencia[<?= $estudiante['id_alumno'] ?>]"
                                                       value="Falta" <?= ($estado_actual === 'Falta') ? 'checked' : '' ?>>
                                                <i class="fas fa-times-circle"></i>
                                                <span>Falta</span>
                                            </label>
                                            <label class="attendance-btn retardo <?php echo ($estado_actual === 'Retardo') ? 'active' : ''; ?>">
                                                <input type="radio" name="asistencia[<?= $estudiante['id_alumno'] ?>]"
                                                       value="Retardo" <?= ($estado_actual === 'Retardo') ? 'checked' : '' ?>>
                                                <i class="fas fa-clock"></i>
                                                <span>Retardo</span>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Resumen -->
                                <div class="summary-container">
                                    <div class="summary-item">
                                        <i class="fas fa-check-circle" style="color: var(--text-green); font-size: 20px;"></i>
                                        <div class="summary-number" id="presentes-count">0</div>
                                        <div class="summary-label">Presentes</div>
                                    </div>
                                    <div class="summary-item">
                                        <i class="fas fa-times-circle" style="color: #dc2626; font-size: 20px;"></i>
                                        <div class="summary-number" id="faltas-count">0</div>
                                        <div class="summary-label">Faltas</div>
                                    </div>
                                    <div class="summary-item">
                                        <i class="fas fa-clock" style="color: #d97706; font-size: 20px;"></i>
                                        <div class="summary-number" id="retardos-count">0</div>
                                        <div class="summary-label">Retardos</div>
                                    </div>
                                </div>

                                <button type="submit" name="guardar_asistencia" class="save-btn">
                                    <i class="fas fa-save"></i>
                                    <span>Guardar lista</span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Calendario (Right Column) -->
                <div class="right-column">
                    <div class="border-container">
                        <div class="calendar-container">
                            <div class="calendar-header">
                                <div class="nav-left">
                                    <button class="nav-btn" id="prev-year">«</button>
                                    <button class="nav-btn" id="prev-month">‹</button>
                                </div>
                                <h3 id="month-year-title">AGOSTO 2026</h3>
                                <div class="nav-right">
                                    <button class="nav-btn" id="next-month">›</button>
                                    <button class="nav-btn" id="next-year">»</button>
                                </div>
                            </div>
                            <div class="calendar-weekdays">
                                <div class="weekday">Do</div>
                                <div class="weekday">Lu</div>
                                <div class="weekday">Ma</div>
                                <div class="weekday">Mi</div>
                                <div class="weekday">Ju</div>
                                <div class="weekday">Vi</div>
                                <div class="weekday">Sá</div>
                            </div>
                            <div class="calendar-days-grid" id="calendar-days">
                                <!-- Días se generan con JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barra de Accesibilidad -->
            <div class="accessibility-bar">
                <div class="acc-info">
                    <div class="acc-icon-box">
                        <i class="fas fa-universal-access acc-icon-main"></i>
                    </div>
                    <div>
                        <strong>Accesibilidad siempre disponible</strong>
                        <p>Personaliza tu experiencia en cualquier momento.</p>
                    </div>
                </div>
                <div class="acc-options">
                    <button class="acc-opt-btn" id="btn-contrast">
                        <i class="fas fa-adjust"></i>
                        <span class="font-icon">Aa</span>
                        <span>Alto contraste</span>
                    </button>
                    <button class="acc-opt-btn" id="btn-darkmode">
                        <i class="fas fa-moon"></i>
                        <span>Modo oscuro</span>
                    </button>
                    <button class="acc-opt-btn" id="btn-text-size">
                        <i class="fas fa-text-height"></i>
                        <span>Texto grande</span>
                    </button>
                    <button class="acc-opt-btn">
                        <i class="fas fa-volume-up"></i>
                        <span>Leer pantalla</span>
                    </button>
                    <button class="acc-opt-btn">
                        <i class="fas fa-closed-captioning"></i>
                        <span>Subtítulos</span>
                    </button>
                    <button class="acc-opt-btn">
                        <i class="fas fa-keyboard"></i>
                        <span>Navegación por teclado</span>
                    </button>
                </div>
                <button class="btn-open-config">
                    <i class="fas fa-cog"></i>
                    <span>Abrir configuración</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal para seleccionar curso -->
    <div class="modal" id="course-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Seleccionar curso</h3>
                <button class="close-modal" onclick="closeCourseModal()">&times;</button>
            </div>
            <?php foreach ($cursos as $curso): ?>
                <div class="course-option <?= ($curso['id_curso'] == $id_curso_seleccionado) ? 'selected' : '' ?>"
                     onclick="selectCourse(<?= $curso['id_curso'] ?>)">
                    <div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($curso['nombre_curso']) ?></div>
                        <div style="font-size: 12px; color: var(--text-muted);">
                            <?= htmlspecialchars($curso['materia'] . ' · ' . $curso['grupo']) ?>
                        </div>
                    </div>
                    <?php if ($curso['id_curso'] == $id_curso_seleccionado): ?>
                        <i class="fas fa-check-circle" style="color: var(--primary-blue);"></i>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="docente_dashboard.js"></script>
    <script>
        // Funciones para el modal de cursos
        function openCourseModal() {
            document.getElementById('course-modal').classList.add('active');
        }

        function closeCourseModal() {
            document.getElementById('course-modal').classList.remove('active');
        }

        function selectCourse(idCurso) {
            window.location.href = `pasarlista.php?id_curso=${idCurso}`;
        }

        // Marcar todos como presentes
        function marcarTodosPresentes() {
            const radios = document.querySelectorAll('input[type="radio"][value="Presente"]');
            radios.forEach(radio => {
                radio.checked = true;
                const label = radio.closest('label');
                // Desmarcar otros botones del mismo estudiante
                const studentContainer = radio.closest('.student-list-item');
                studentContainer.querySelectorAll('.attendance-btn').forEach(btn => {
                    btn.classList.remove('active', 'falta', 'retardo');
                });
                label.classList.add('active');
            });
            updateSummary();
        }

        // Actualizar resumen de asistencia
        function updateSummary() {
            let presentes = 0, faltas = 0, retardos = 0;
            document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
                if (radio.value === 'Presente') presentes++;
                else if (radio.value === 'Falta') faltas++;
                else if (radio.value === 'Retardo') retardos++;
            });

            document.getElementById('presentes-count').textContent = presentes;
            document.getElementById('faltas-count').textContent = faltas;
            document.getElementById('retardos-count').textContent = retardos;
        }

        // Event listeners para los botones de asistencia
        document.querySelectorAll('.attendance-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    // Desmarcar otros botones del mismo estudiante
                    const studentContainer = this.closest('.student-list-item');
                    studentContainer.querySelectorAll('.attendance-btn').forEach(b => {
                        b.classList.remove('active', 'falta', 'retardo');
                    });
                    // Marcar el botón clickeado
                    this.classList.add('active');
                    if (radio.value === 'Falta') this.classList.add('falta');
                    if (radio.value === 'Retardo') this.classList.add('retardo');
                    updateSummary();
                }
            });
        });

        // Inicializar resumen
        updateSummary();

        // Mensaje de éxito (PHP)
        <?php if (isset($mensaje_exito)): ?>
            alert("<?= addslashes($mensaje_exito) ?>");
        <?php endif; ?>
    </script>
    <script src="jss/docente_dashboard.js"></script>
</body>
</html>