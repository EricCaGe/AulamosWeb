<?php
session_start();

// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

// Verificar que el usuario sea docente
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

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

// Obtener ID del curso seleccionado
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

// Variables para el modal de confirmación
$mostrar_modal = false;
$resumen_asistencia = ['presentes' => 0, 'faltas' => 0, 'retardos' => 0];
$nombre_curso_modal = '';

// Guardar asistencia (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_asistencia'])) {
    $id_curso = (int)$_POST['id_curso'];
    $asistencias = $_POST['asistencia'] ?? [];
    $_SESSION['asistencia'][$id_curso] = $asistencias;
    
    // Calcular resumen
    foreach ($asistencias as $estado) {
        if ($estado === 'Presente') $resumen_asistencia['presentes']++;
        elseif ($estado === 'Falta') $resumen_asistencia['faltas']++;
        elseif ($estado === 'Retardo') $resumen_asistencia['retardos']++;
    }
    
    // Obtener nombre del curso para el modal
    foreach ($cursos as $curso) {
        if ($curso['id_curso'] == $id_curso) {
            $nombre_curso_modal = $curso['nombre_curso'] . ' - ' . $curso['materia'] . ' - ' . $curso['grupo'];
            break;
        }
    }
    
    $mostrar_modal = true;
}

// Configurar zona horaria
date_default_timezone_set('America/Mexico_City');

// Traductores de días y meses en español
$dias_semana = [
    'Sunday' => 'Domingo',
    'Monday' => 'Lunes',
    'Tuesday' => 'Martes',
    'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves',
    'Friday' => 'Viernes',
    'Saturday' => 'Sábado'
];

$meses_año = [
    'January' => 'Enero',
    'February' => 'Febrero',
    'March' => 'Marzo',
    'April' => 'Abril',
    'May' => 'Mayo',
    'June' => 'Junio',
    'July' => 'Julio',
    'August' => 'Agosto',
    'September' => 'Septiembre',
    'October' => 'Octubre',
    'November' => 'Noviembre',
    'December' => 'Diciembre'
];

$timestamp = time();
$dia_nombre = $dias_semana[date('l', $timestamp)];
$dia_num = date('d', $timestamp);
$mes_nombre = $meses_año[date('F', $timestamp)];
$anio = date('Y', $timestamp);
$fecha_formateada = "{$dia_nombre}, {$dia_num} de {$mes_nombre} de {$anio}";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasar Lista</title>
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- NUEVA ACCESIBILIDAD -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    
    <style>
        /* Estilos adicionales para pasarlista.php - SOLO BASE */
        .student-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border: 1px solid #e2e8f0;
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
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b71f3;
        }
        .student-name {
            font-weight: 600;
            color: #1e293b;
        }
        .student-group {
            font-size: 12px;
            color: #64748b;
        }
        .attendance-buttons {
            display: flex;
            gap: 6px;
        }
        .attendance-btn {
            padding: 6px 12px;
            border: 1px solid #e2e8f0;
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
            background: #dcfce7;
            border-color: #22c55e;
            color: #166534;
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
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            margin: 20px 0;
        }
        .summary-number {
            font-size: 24px;
            font-weight: 800;
            color: #1e293b;
        }
        .summary-label {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        .course-selector {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 20px;
            cursor: pointer;
        }
        .course-name {
            font-weight: 600;
            color: #1e293b;
        }
        .course-details {
            font-size: 12px;
            color: #64748b;
        }
        .save-btn {
            background: #3b71f3;
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
            background: #eff6ff;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .date-display strong {
            color: #3b71f3;
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
            color: #1e293b;
        }
        .close-modal {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #64748b;
        }
        .course-option {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
        }
        .course-option:hover {
            background: #f1f5f9;
        }
        .course-option.selected {
            background: #eff6ff;
        }

        /* Estilos para el modal de confirmación */
        .modal-confirmacion {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .modal-confirmacion.active {
            display: flex;
        }
        .modal-confirmacion .modal-content {
            background: white;
            border-radius: 16px;
            padding: 30px;
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        .modal-confirmacion .modal-icon {
            font-size: 48px;
            color: #22c55e;
            margin-bottom: 15px;
        }
        .modal-confirmacion .modal-titulo {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        .modal-confirmacion .modal-subtitulo {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 20px;
        }
        .modal-confirmacion .modal-resumen {
            display: flex;
            justify-content: space-around;
            padding: 15px 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        .modal-confirmacion .resumen-item {
            text-align: center;
        }
        .modal-confirmacion .resumen-numero {
            font-size: 28px;
            font-weight: 800;
            color: #1e293b;
        }
        .modal-confirmacion .resumen-etiqueta {
            font-size: 12px;
            color: #64748b;
        }
        .modal-confirmacion .resumen-item.presente .resumen-numero {
            color: #22c55e;
        }
        .modal-confirmacion .resumen-item.falta .resumen-numero {
            color: #dc2626;
        }
        .modal-confirmacion .resumen-item.retardo .resumen-numero {
            color: #d97706;
        }
        .modal-confirmacion .btn-aceptar {
            background: #3b71f3;
            color: white;
            border: none;
            padding: 10px 40px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .modal-confirmacion .btn-aceptar:hover {
            background: #2563eb;
        }
    </style>
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
                <a href="mis_recursos.php" class="menu-item"><i class="fa-solid fa-folder-open"></i> Mis Recursos</a>
                <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
                <a href="crear_juego.php" class="menu-item"><i class="fa-solid fa-gamepad"></i> Crear Juego</a>
                <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                <a href="pasarlista.php" class="menu-item active"><i class="fa-solid fa-bars"></i> Pasar Lista</a>
                <a href="juegos_docente.php" class="menu-item"><i class="fa-solid fa-gamepad"></i> Conecta y Aprende</a>
                <div class="menu-spacer"></div>
            <button class="btn-accessibility-main" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
            <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="main-content">
            
            <!-- ENCABEZADO CON FOTO DE PERFIL -->
            <?php
            // Obtener foto de perfil del docente
            $foto_perfil_docente = $_SESSION['usuario']['foto_perfil'] ?? null;
            $ruta_foto_docente = !empty($foto_perfil_docente) ? '../uploads/perfiles/' . $foto_perfil_docente : 'https://placehold.co/40x40/ff7675/white?text=👨';
            ?>
            <div class="content-header">
                <div class="welcome-text">
                    <h1>Pasar Lista</h1>
                    <p>Registra la asistencia de tus estudiantes</p>
                </div>
                <div class="header-actions">
                    <button type="button" class="btn-assistant" id="btn-asistente" onclick="window.location.href='../Alumno/ChatbotDocente.php?rol=docente'">
                        Asistente Virtual <span class="robot-icon">🤖</span>
                    </button>
                    <div class="icon-bell-container">
                        <i class="fa-regular fa-bell"></i>
                    </div>
                    <a href="mi_perfil_d.php" class="user-profile" style="text-decoration:none; cursor:pointer; display:flex; align-items:center; gap:10px; padding:5px 12px 5px 5px; border-radius:50px; background:#f1f5f9; transition:background 0.2s;">
                        <img src="<?php echo $ruta_foto_docente; ?>" alt="Avatar Docente" class="avatar" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid white;">
                        <div class="user-info" style="display:flex; flex-direction:column; line-height:1.2;">
                            <span class="user-name" style="font-weight:600; font-size:14px; color:#1e293b;"><?php echo htmlspecialchars($nombre_docente); ?></span>
                            <span class="user-role" style="font-size:11px; color:#64748b;">Docente</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- CONTENIDO PRINCIPAL -->
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
                            <p style="text-align: center; color: #64748b; padding: 20px;">
                                No hay estudiantes en este curso.
                            </p>
                        <?php else: ?>
                            <form method="post" action="pasarlista.php" id="form-asistencia">
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
                                        <i class="fas fa-check-circle" style="color: #22c55e; font-size: 20px;"></i>
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

                                <button type="submit" name="guardar_asistencia" class="save-btn" id="btn-guardar">
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
                        <aside class="calendar-container">
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
                            <div class="calendar-weekdays">
                                <div class="weekday">Do</div>
                                <div class="weekday">Lu</div>
                                <div class="weekday">Ma</div>
                                <div class="weekday">Mi</div>
                                <div class="weekday">Ju</div>
                                <div class="weekday">Vi</div>
                                <div class="weekday">Sá</div>
                            </div>
                            <div id="calendar-days" class="calendar-days-grid"></div>
                        </aside>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- NUEVA BARRA DE ACCESIBILIDAD               -->
            <!-- ========================================== -->
            <?php include '../Accesibilidad/accesibilidad.php'; ?>

        </div>
    </div>

    <!-- ========================================== -->
    <!-- BOTÓN FLOTANTE DE ACCESIBILIDAD            -->
    <!-- ========================================== -->
    <button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
        <i class="fa-solid fa-universal-access"></i>
    </button>

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
                        <div style="font-size: 12px; color: #64748b;">
                            <?= htmlspecialchars($curso['materia'] . ' · ' . $curso['grupo']) ?>
                        </div>
                    </div>
                    <?php if ($curso['id_curso'] == $id_curso_seleccionado): ?>
                        <i class="fas fa-check-circle" style="color: #3b71f3;"></i>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal de confirmación de guardado -->
    <?php if ($mostrar_modal): ?>
    <div class="modal-confirmacion active" id="modal-confirmacion">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="modal-titulo">¡Lista guardada!</h3>
            <p class="modal-subtitulo">
                La lista de <strong><?= htmlspecialchars($nombre_curso_modal) ?></strong> se guardó en este dispositivo.
            </p>
            <div class="modal-resumen">
                <div class="resumen-item presente">
                    <div class="resumen-numero"><?= $resumen_asistencia['presentes'] ?></div>
                    <div class="resumen-etiqueta">Presentes</div>
                </div>
                <div class="resumen-item falta">
                    <div class="resumen-numero"><?= $resumen_asistencia['faltas'] ?></div>
                    <div class="resumen-etiqueta">Faltas</div>
                </div>
                <div class="resumen-item retardo">
                    <div class="resumen-numero"><?= $resumen_asistencia['retardos'] ?></div>
                    <div class="resumen-etiqueta">Retardos</div>
                </div>
            </div>
            <button class="btn-aceptar" onclick="cerrarModalConfirmacion()">Aceptar</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- SCRIPTS                                    -->
    <!-- ========================================== -->
    <script src="jss/docente_dashboard.js"></script>

    <!-- NUEVA ACCESIBILIDAD -->
    <script src="../Accesibilidad/accesibilidad.js"></script>
    <script src="../Accesibilidad/navegacionTeclado.js"></script>

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

        // Función para cerrar el modal de confirmación
        function cerrarModalConfirmacion() {
            document.getElementById('modal-confirmacion').classList.remove('active');
        }

        // Marcar todos como presentes
        function marcarTodosPresentes() {
            const radios = document.querySelectorAll('input[type="radio"][value="Presente"]');
            radios.forEach(radio => {
                radio.checked = true;
                const label = radio.closest('label');
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
                    const studentContainer = this.closest('.student-list-item');
                    studentContainer.querySelectorAll('.attendance-btn').forEach(b => {
                        b.classList.remove('active', 'falta', 'retardo');
                    });
                    this.classList.add('active');
                    if (radio.value === 'Falta') this.classList.add('falta');
                    if (radio.value === 'Retardo') this.classList.add('retardo');
                    updateSummary();
                }
            });
        });

        // Inicializar resumen
        updateSummary();

        <?php if (isset($mensaje_exito)): ?>
            alert("<?= addslashes($mensaje_exito) ?>");
        <?php endif; ?>
    </script>
</body>
</html>