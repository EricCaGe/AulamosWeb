<?php
session_start();

// Verificar que el usuario haya iniciado sesión y sea Docente
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_docente = $_SESSION['usuario']['id_usuario'];
$nombre_docente = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];

// --- Filtrado por materia y periodo ---
$materia_seleccionada = isset($_GET['materia']) ? $_GET['materia'] : 'todos';
$periodo_seleccionado = isset($_GET['periodo']) ? $_GET['periodo'] : 'todos';

// --- 1. Consultar materias del docente para el filtro ---
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

// --- 2. Consultar periodos de evaluación para el filtro ---
$query_periodos = "
    SELECT id_periodo, nombre
    FROM periodos_evaluacion
    WHERE id_ciclo IN (
        SELECT DISTINCT id_ciclo
        FROM cursos
        WHERE id_docente = ?
    )
    ORDER BY fecha_inicio
";
$stmt_periodos = $conexion->prepare($query_periodos);
$stmt_periodos->bind_param("i", $id_docente);
$stmt_periodos->execute();
$result_periodos = $stmt_periodos->get_result();

$periodos = [];
while ($row = $result_periodos->fetch_assoc()) {
    $periodos[] = $row;
}

// --- 3. Consultar datos para el resumen general ---
// --- 3.1. Promedio general de calificaciones ---
$query_promedio = "
    SELECT AVG(e.calificacion) AS promedio_general
    FROM entregas e
    JOIN actividad_estudiantes ae ON e.id_actividad_estudiante = ae.id_actividad_estudiante
    JOIN actividades a ON ae.id_actividad = a.id_actividad
    JOIN cursos c ON a.id_curso = c.id_curso
    WHERE c.id_docente = ? AND e.calificacion IS NOT NULL
";
if ($materia_seleccionada !== 'todos') {
    $query_promedio .= " AND a.id_curso IN (SELECT id_curso FROM cursos WHERE id_materia = $materia_seleccionada AND id_docente = $id_docente)";
}
if ($periodo_seleccionado !== 'todos') {
    $query_promedio .= " AND a.id_periodo = $periodo_seleccionado";
}

$stmt_promedio = $conexion->prepare($query_promedio);
$stmt_promedio->bind_param("i", $id_docente);
$stmt_promedio->execute();
$result_promedio = $stmt_promedio->get_result();
$promedio_general = $result_promedio->fetch_assoc()['promedio_general'] ?? 0;

// --- 3.2. Porcentaje de estudiantes aprobados ---
$query_aprobados = "
    SELECT
        COUNT(DISTINCT ae.id_alumno) AS total_estudiantes,
        SUM(CASE WHEN e.calificacion >= 6.0 THEN 1 ELSE 0 END) AS aprobados
    FROM actividad_estudiantes ae
    JOIN actividades a ON ae.id_actividad = a.id_actividad
    JOIN cursos c ON a.id_curso = c.id_curso
    LEFT JOIN entregas e ON ae.id_actividad_estudiante = e.id_actividad_estudiante
    WHERE c.id_docente = ? AND ae.estado = 'Calificada'
";
if ($materia_seleccionada !== 'todos') {
    $query_aprobados .= " AND a.id_curso IN (SELECT id_curso FROM cursos WHERE id_materia = $materia_seleccionada AND id_docente = $id_docente)";
}
if ($periodo_seleccionado !== 'todos') {
    $query_aprobados .= " AND a.id_periodo = $periodo_seleccionado";
}

$stmt_aprobados = $conexion->prepare($query_aprobados);
$stmt_aprobados->bind_param("i", $id_docente);
$stmt_aprobados->execute();
$result_aprobados = $stmt_aprobados->get_result();
$datos_aprobados = $result_aprobados->fetch_assoc();
$total_estudiantes = $datos_aprobados['total_estudiantes'] ?? 0;
$aprobados = $datos_aprobados['aprobados'] ?? 0;
$porcentaje_aprobados = $total_estudiantes > 0 ? round(($aprobados / $total_estudiantes) * 100, 1) : 0;

// --- 3.3. Porcentaje de actividades entregadas ---
$query_entregas = "
    SELECT
        COUNT(DISTINCT ae.id_actividad_estudiante) AS total_actividades,
        SUM(CASE WHEN e.estado = 'Entregada' OR e.estado = 'Calificada' THEN 1 ELSE 0 END) AS entregadas
    FROM actividad_estudiantes ae
    JOIN actividades a ON ae.id_actividad = a.id_actividad
    JOIN cursos c ON a.id_curso = c.id_curso
    LEFT JOIN entregas e ON ae.id_actividad_estudiante = e.id_actividad_estudiante
    WHERE c.id_docente = ?
";
if ($materia_seleccionada !== 'todos') {
    $query_entregas .= " AND a.id_curso IN (SELECT id_curso FROM cursos WHERE id_materia = $materia_seleccionada AND id_docente = $id_docente)";
}
if ($periodo_seleccionado !== 'todos') {
    $query_entregas .= " AND a.id_periodo = $periodo_seleccionado";
}

$stmt_entregas = $conexion->prepare($query_entregas);
$stmt_entregas->bind_param("i", $id_docente);
$stmt_entregas->execute();
$result_entregas = $stmt_entregas->get_result();
$datos_entregas = $result_entregas->fetch_assoc();
$total_actividades = $datos_entregas['total_actividades'] ?? 0;
$entregadas = $datos_entregas['entregadas'] ?? 0;
$porcentaje_entregas = $total_actividades > 0 ? round(($entregadas / $total_actividades) * 100, 1) : 0;

// --- 3.4. Número de evaluaciones realizadas ---
$query_evaluaciones = "
    SELECT COUNT(DISTINCT a.id_actividad) AS evaluaciones_realizadas
    FROM actividades a
    JOIN cursos c ON a.id_curso = c.id_curso
    WHERE c.id_docente = ? AND a.tipo = 'Evaluacion' AND a.estado = 'Publicada'
";
if ($materia_seleccionada !== 'todos') {
    $query_evaluaciones .= " AND a.id_curso IN (SELECT id_curso FROM cursos WHERE id_materia = $materia_seleccionada AND id_docente = $id_docente)";
}
if ($periodo_seleccionado !== 'todos') {
    $query_evaluaciones .= " AND a.id_periodo = $periodo_seleccionado";
}

$stmt_evaluaciones = $conexion->prepare($query_evaluaciones);
$stmt_evaluaciones->bind_param("i", $id_docente);
$stmt_evaluaciones->execute();
$result_evaluaciones = $stmt_evaluaciones->get_result();
$evaluaciones_realizadas = $result_evaluaciones->fetch_assoc()['evaluaciones_realizadas'] ?? 0;

// Cerrar conexiones
$stmt_materias->close();
$stmt_periodos->close();
$stmt_promedio->close();
$stmt_aprobados->close();
$stmt_entregas->close();
$stmt_evaluaciones->close();
$conexion->close();

// --- Generar datos para el gráfico de línea (SVG) ---
$puntos_svg = [];
if ($promedio_general > 0) {
    // Generar puntos aleatorios alrededor del promedio
    $puntos_svg = [
        ['x' => 0, 'y' => 30 - ($promedio_general * 3)],
        ['x' => 20, 'y' => 30 - (($promedio_general + 0.5) * 3)],
        ['x' => 40, 'y' => 30 - (($promedio_general - 0.3) * 3)],
        ['x' => 60, 'y' => 30 - (($promedio_general + 0.8) * 3)],
        ['x' => 80, 'y' => 30 - (($promedio_general - 0.2) * 3)],
        ['x' => 100, 'y' => 30 - (($promedio_general + 0.1) * 3)]
    ];
} else {
    // Si no hay datos, línea plana en 0
    $puntos_svg = [
        ['x' => 0, 'y' => 30],
        ['x' => 20, 'y' => 30],
        ['x' => 40, 'y' => 30],
        ['x' => 60, 'y' => 30],
        ['x' => 80, 'y' => 30],
        ['x' => 100, 'y' => 30]
    ];
}

// Generar el path para el SVG
$path_d = "M";
foreach ($puntos_svg as $i => $punto) {
    $path_d .= $punto['x'] . "," . $punto['y'];
    if ($i < count($puntos_svg) - 1) {
        $path_d .= " L";
    }
}

// Generar el path para el área de sombra
$path_area = "M0,30 L" . implode(" L", array_map(fn($p) => $p['x'] . "," . $p['y'], $puntos_svg)) . " L100,30 L0,30 Z";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Aulamos</title>

    <!-- CSS Base -->
    <link rel="stylesheet" href="styles/docente.css">
    <!-- CSS Específico para esta pantalla -->
    <link rel="stylesheet" href="styles/reportes.css">

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
                <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
                <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item active"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                
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
                    <h1>Reportes</h1>
                    <p>Analiza el progreso de tus clases</p>
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

            <!-- FILTROS SUPERIORES Y CALENDARIO EN LA MISMA FILA -->
                <div class="filters-and-calendar-grid">
                    <!-- FILTROS SUPERIORES -->
                    <div class="reports-filter-bar">
                        <form method="GET" action="reporte.php">
                            <div class="filter-group">
                                <label>Seleccionar materia</label>
                                <select class="custom-select" name="materia">
                                    <option value="todos">Todas las materias</option>
                                    <?php foreach ($materias as $materia): ?>
                                        <option value="<?php echo $materia['id_materia']; ?>"
                                            <?php echo ($materia_seleccionada == $materia['id_materia']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($materia['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Periodo</label>
                                <select class="custom-select" name="periodo">
                                    <option value="todos">Todos los periodos</option>
                                    <?php foreach ($periodos as $periodo): ?>
                                        <option value="<?php echo $periodo['id_periodo']; ?>"
                                            <?php echo ($periodo_seleccionado == $periodo['id_periodo']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($periodo['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-button-group">
                                <button type="submit" class="btn-apply-filters">
                                    <i class="fa-solid fa-magnifying-glass"></i> Aplicar filtros
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- CALENDARIO (ALINEADO A LA DERECHA DE LOS FILTROS) -->
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
</div>

            <!-- RESUMEN GENERAL Y REPORTES DISPONIBLES -->
            <div class="reports-main-grid">
                <!-- RESUMEN GENERAL -->
                <div class="reports-left">
                    <h3 class="section-title">Resumen general</h3>
                    <div class="stats-grid">
                        <!-- Tarjeta 1: Promedio General -->
                        <div class="stat-card highlight-card">
                            <div class="stat-card-header">
                                <i class="fa-solid fa-chart-line stat-icon"></i>
                                <p class="stat-title">Promedio general</p>
                            </div>
                            <div class="stat-value-box">
                                <span class="stat-big"><?php echo number_format($promedio_general, 1); ?></span>
                                <span class="stat-small">/ 10</span>
                            </div>
                            <!-- Gráfica de línea dinámica con SVG -->
                            <div class="mini-chart-container">
                                <svg viewBox="0 0 100 30" class="line-chart">
                                    <path d="<?php echo $path_d; ?>" fill="none" stroke="#3b71f3" stroke-width="2"/>
                                    <?php foreach ($puntos_svg as $punto): ?>
                                        <circle cx="<?php echo $punto['x']; ?>" cy="<?php echo $punto['y']; ?>" r="2" fill="#3b71f3"/>
                                    <?php endforeach; ?>
                                    <!-- Sombra degradada -->
                                    <path d="<?php echo $path_area; ?>" fill="rgba(59, 113, 243, 0.1)"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Tarjeta 2: Estudiantes Aprobados -->
                        <div class="stat-card highlight-card">
                            <div class="stat-card-header">
                                <i class="fa-solid fa-user-check stat-icon"></i>
                                <p class="stat-title">Estudiantes aprobados</p>
                            </div>
                            <div class="donut-chart-container">
                                <div class="donut-chart" style="--percent: <?php echo $porcentaje_aprobados; ?>%;">
                                    <div class="donut-inner">
                                        <span><?php echo $porcentaje_aprobados; ?>%</span>
                                    </div>
                                </div>
                            </div>
                            <p class="stat-small-text mt-10">
                                <?php echo $aprobados; ?> de <?php echo $total_estudiantes; ?> estudiantes
                            </p>
                        </div>

                        <!-- Tarjeta 3: Actividades Entregadas -->
                        <div class="stat-card highlight-card">
                            <div class="stat-card-header">
                                <i class="fa-solid fa-clipboard-check stat-icon"></i>
                                <p class="stat-title">Actividades entregadas</p>
                            </div>
                            <span class="stat-big mt-10 d-block"><?php echo $porcentaje_entregas; ?>%</span>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: <?php echo $porcentaje_entregas; ?>%;"></div>
                            </div>
                            <p class="stat-small-text mt-5">
                                <?php echo $entregadas; ?> de <?php echo $total_actividades; ?> actividades
                            </p>
                        </div>

                        <!-- Tarjeta 4: Evaluaciones Realizadas -->
                        <div class="stat-card highlight-card">
                            <div class="stat-card-header">
                                <i class="fa-solid fa-file-alt stat-icon"></i>
                                <p class="stat-title">Evaluaciones realizadas</p>
                            </div>
                            <span class="stat-big mt-10 d-block"><?php echo $evaluaciones_realizadas; ?></span>
                            <p class="stat-small-text mt-5">este periodo</p>
                        </div>
                    </div>
                </div>

                <!-- REPORTES DISPONIBLES -->
                <div class="reports-right">
                    <h3 class="section-title">Reportes disponibles</h3>
                    <div class="reports-list">
                        <!-- Opción 1: Rendimiento por actividad -->
                        <a href="#rendimiento-actividad" class="report-list-item" onclick="mostrarReporte('rendimiento-actividad')">
                            <div class="report-icon-box purple-box">
                                <i class="fa-solid fa-chart-simple"></i>
                            </div>
                            <span class="report-name">Rendimiento por actividad</span>
                            <i class="fa-solid fa-chevron-right report-arrow"></i>
                        </a>

                        <!-- Opción 2: Rendimiento por evaluación -->
                        <a href="#rendimiento-evaluacion" class="report-list-item" onclick="mostrarReporte('rendimiento-evaluacion')">
                            <div class="report-icon-box orange-box">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                            <span class="report-name">Rendimiento por evaluación</span>
                            <i class="fa-solid fa-chevron-right report-arrow"></i>
                        </a>

                        <!-- Opción 3: Asistencia y participación -->
                        <a href="#asistencia-participacion" class="report-list-item" onclick="mostrarReporte('asistencia-participacion')">
                            <div class="report-icon-box green-box">
                                <i class="fa-solid fa-person-chalkboard"></i>
                            </div>
                            <span class="report-name">Asistencia y participación</span>
                            <i class="fa-solid fa-chevron-right report-arrow"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE DETALLES DE REPORTES (OCULTA POR DEFECTO) -->
            <div id="detalles-reportes" class="reports-details-section" style="display: none;">
                <!-- Detalles de Rendimiento por Actividad -->
                <div id="rendimiento-actividad" class="report-detail">
                    <h3>Rendimiento por Actividad</h3>
                    <div class="table-container">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Actividad</th>
                                    <th>Promedio</th>
                                    <th>Estudiantes</th>
                                    <th>Aprobados</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rendimiento_actividad)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center;">No hay datos disponibles.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rendimiento_actividad as $actividad): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($actividad['titulo']); ?></td>
                                            <td><?php echo number_format($actividad['promedio'], 1); ?></td>
                                            <td><?php echo $actividad['total_estudiantes']; ?></td>
                                            <td><?php echo $actividad['aprobados']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Detalles de Rendimiento por Evaluación -->
                <div id="rendimiento-evaluacion" class="report-detail" style="display: none;">
                    <h3>Rendimiento por Evaluación</h3>
                    <div class="table-container">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Evaluación</th>
                                    <th>Promedio</th>
                                    <th>Estudiantes</th>
                                    <th>Aprobados</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rendimiento_evaluacion)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center;">No hay datos disponibles.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rendimiento_evaluacion as $evaluacion): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($evaluacion['titulo']); ?></td>
                                            <td><?php echo number_format($evaluacion['promedio'], 1); ?></td>
                                            <td><?php echo $evaluacion['total_estudiantes']; ?></td>
                                            <td><?php echo $evaluacion['aprobados']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Detalles de Asistencia y Participación -->
                <div id="asistencia-participacion" class="report-detail" style="display: none;">
                    <h3>Asistencia y Participación</h3>
                    <div class="table-container">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Actividad</th>
                                    <th>Estudiantes</th>
                                    <th>Participantes</th>
                                    <th>% Participación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($asistencia_participacion)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center;">No hay datos disponibles.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($asistencia_participacion as $asistencia): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($asistencia['titulo']); ?></td>
                                            <td><?php echo $asistencia['total_estudiantes']; ?></td>
                                            <td><?php echo $asistencia['participantes']; ?></td>
                                            <td>
                                                <?php
                                                $porcentaje_participacion = $asistencia['total_estudiantes'] > 0 ?
                                                    round(($asistencia['participantes'] / $asistencia['total_estudiantes']) * 100, 1) : 0;
                                                echo $porcentaje_participacion . '%';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- BARRA ACCESIBILIDAD -->
            <footer class="accessibility-bar" style="margin-top: 30px;">
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

    <!-- Archivos JS -->
    <script src="jss/docente_dashboard.js"></script>
    <script>
        function mostrarReporte(reporteId) {
            // Ocultar todos los detalles de reportes
            const detalles = document.querySelectorAll('.report-detail');
            detalles.forEach(detalle => {
                detalle.style.display = 'none';
            });

            // Mostrar el reporte seleccionado
            const reporteSeleccionado = document.getElementById(reporteId);
            if (reporteSeleccionado) {
                reporteSeleccionado.style.display = 'block';
                document.getElementById('detalles-reportes').style.display = 'block';
            }
        }
    </script>
</body>
</html>