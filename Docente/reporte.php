<?php
session_start();

// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

// Regenerar ID de sesión por seguridad
session_regenerate_id(true);

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
$materias = $result_materias->fetch_all(MYSQLI_ASSOC);
$stmt_materias->close();

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
$periodos = $result_periodos->fetch_all(MYSQLI_ASSOC);
$stmt_periodos->close();

// =====================================================
// FUNCIÓN PARA CONSTRUIR FILTROS SQL
// =====================================================
function aplicarFiltros($sql, $materia_seleccionada, $periodo_seleccionado, $id_docente) {
    if ($materia_seleccionada !== 'todos') {
        $sql .= " AND a.id_curso IN (SELECT id_curso FROM cursos WHERE id_materia = $materia_seleccionada AND id_docente = $id_docente)";
    }
    if ($periodo_seleccionado !== 'todos') {
        $sql .= " AND a.id_periodo = $periodo_seleccionado";
    }
    return $sql;
}

// --- 3.1. Promedio general ---
$query_promedio = "
    SELECT AVG(e.calificacion) AS promedio_general
    FROM entregas e
    JOIN actividad_estudiantes ae ON e.id_actividad_estudiante = ae.id_actividad_estudiante
    JOIN actividades a ON ae.id_actividad = a.id_actividad
    JOIN cursos c ON a.id_curso = c.id_curso
    WHERE c.id_docente = ? AND e.calificacion IS NOT NULL
";
$query_promedio = aplicarFiltros($query_promedio, $materia_seleccionada, $periodo_seleccionado, $id_docente);
$stmt_promedio = $conexion->prepare($query_promedio);
$stmt_promedio->bind_param("i", $id_docente);
$stmt_promedio->execute();
$result_promedio = $stmt_promedio->get_result();
$promedio_general = $result_promedio->fetch_assoc()['promedio_general'] ?? 0;
$stmt_promedio->close();

// --- 3.2. Estudiantes aprobados (CORREGIDO) ---
$query_aprobados = "
    SELECT
        COUNT(DISTINCT ae.id_alumno) AS total_estudiantes,
        COUNT(DISTINCT CASE 
            WHEN EXISTS (
                SELECT 1 FROM entregas e2 
                WHERE e2.id_actividad_estudiante = ae.id_actividad_estudiante 
                AND e2.calificacion >= 6.0
            ) THEN ae.id_alumno 
        END) AS aprobados
    FROM actividad_estudiantes ae
    JOIN actividades a ON ae.id_actividad = a.id_actividad
    JOIN cursos c ON a.id_curso = c.id_curso
    WHERE c.id_docente = ? AND ae.estado = 'Calificada'
";
$query_aprobados = aplicarFiltros($query_aprobados, $materia_seleccionada, $periodo_seleccionado, $id_docente);
$stmt_aprobados = $conexion->prepare($query_aprobados);
$stmt_aprobados->bind_param("i", $id_docente);
$stmt_aprobados->execute();
$result_aprobados = $stmt_aprobados->get_result();
$datos_aprobados = $result_aprobados->fetch_assoc();
$total_estudiantes = $datos_aprobados['total_estudiantes'] ?? 0;
$aprobados = $datos_aprobados['aprobados'] ?? 0;
$porcentaje_aprobados = $total_estudiantes > 0 ? round(($aprobados / $total_estudiantes) * 100, 1) : 0;
$stmt_aprobados->close();

// --- 3.3. Actividades entregadas ---
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
$query_entregas = aplicarFiltros($query_entregas, $materia_seleccionada, $periodo_seleccionado, $id_docente);
$stmt_entregas = $conexion->prepare($query_entregas);
$stmt_entregas->bind_param("i", $id_docente);
$stmt_entregas->execute();
$result_entregas = $stmt_entregas->get_result();
$datos_entregas = $result_entregas->fetch_assoc();
$total_actividades = $datos_entregas['total_actividades'] ?? 0;
$entregadas = $datos_entregas['entregadas'] ?? 0;
$porcentaje_entregas = $total_actividades > 0 ? round(($entregadas / $total_actividades) * 100, 1) : 0;
$stmt_entregas->close();

// --- 3.4. Evaluaciones realizadas ---
$query_evaluaciones = "
    SELECT COUNT(DISTINCT a.id_actividad) AS evaluaciones_realizadas
    FROM actividades a
    JOIN cursos c ON a.id_curso = c.id_curso
    WHERE c.id_docente = ? AND a.tipo = 'Evaluacion' AND a.estado = 'Publicada'
";
$query_evaluaciones = aplicarFiltros($query_evaluaciones, $materia_seleccionada, $periodo_seleccionado, $id_docente);
$stmt_evaluaciones = $conexion->prepare($query_evaluaciones);
$stmt_evaluaciones->bind_param("i", $id_docente);
$stmt_evaluaciones->execute();
$result_evaluaciones = $stmt_evaluaciones->get_result();
$evaluaciones_realizadas = $result_evaluaciones->fetch_assoc()['evaluaciones_realizadas'] ?? 0;
$stmt_evaluaciones->close();

// =====================================================
// 4. CONSULTAS PARA LOS REPORTES DETALLADOS
// =====================================================

// 4.1. Rendimiento por actividad (CORREGIDO)
$query_rendimiento_actividad = "
    SELECT
        a.titulo,
        AVG(e.calificacion) AS promedio,
        COUNT(DISTINCT ae.id_alumno) AS total_estudiantes,
        SUM(CASE WHEN e.calificacion >= 6.0 THEN 1 ELSE 0 END) AS aprobados
    FROM actividades a
    JOIN actividad_estudiantes ae ON a.id_actividad = ae.id_actividad
    JOIN cursos c ON a.id_curso = c.id_curso
    LEFT JOIN entregas e ON ae.id_actividad_estudiante = e.id_actividad_estudiante
    WHERE c.id_docente = ? AND a.estado = 'Publicada'
    GROUP BY a.id_actividad, a.titulo
";
$query_rendimiento_actividad = aplicarFiltros($query_rendimiento_actividad, $materia_seleccionada, $periodo_seleccionado, $id_docente);
$stmt = $conexion->prepare($query_rendimiento_actividad);
$stmt->bind_param("i", $id_docente);
$stmt->execute();
$rendimiento_actividad = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 4.2. Rendimiento por evaluación (CORREGIDO)
$query_rendimiento_evaluacion = "
    SELECT
        a.titulo,
        AVG(e.calificacion) AS promedio,
        COUNT(DISTINCT ae.id_alumno) AS total_estudiantes,
        SUM(CASE WHEN e.calificacion >= 6.0 THEN 1 ELSE 0 END) AS aprobados
    FROM actividades a
    JOIN actividad_estudiantes ae ON a.id_actividad = ae.id_actividad
    JOIN cursos c ON a.id_curso = c.id_curso
    LEFT JOIN entregas e ON ae.id_actividad_estudiante = e.id_actividad_estudiante
    WHERE c.id_docente = ? AND a.tipo = 'Evaluacion' AND a.estado = 'Publicada'
    GROUP BY a.id_actividad, a.titulo
";
$query_rendimiento_evaluacion = aplicarFiltros($query_rendimiento_evaluacion, $materia_seleccionada, $periodo_seleccionado, $id_docente);
$stmt = $conexion->prepare($query_rendimiento_evaluacion);
$stmt->bind_param("i", $id_docente);
$stmt->execute();
$rendimiento_evaluacion = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 4.3. Asistencia y participación (CORREGIDO)
$query_asistencia_participacion = "
    SELECT
        a.titulo,
        COUNT(DISTINCT ae.id_alumno) AS total_estudiantes,
        SUM(CASE WHEN ae.estado = 'Completada' OR ae.estado = 'Calificada' THEN 1 ELSE 0 END) AS participantes
    FROM actividades a
    JOIN actividad_estudiantes ae ON a.id_actividad = ae.id_actividad
    JOIN cursos c ON a.id_curso = c.id_curso
    WHERE c.id_docente = ? AND a.estado = 'Publicada'
    GROUP BY a.id_actividad, a.titulo
";
$query_asistencia_participacion = aplicarFiltros($query_asistencia_participacion, $materia_seleccionada, $periodo_seleccionado, $id_docente);
$stmt = $conexion->prepare($query_asistencia_participacion);
$stmt->bind_param("i", $id_docente);
$stmt->execute();
$asistencia_participacion = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- Generar datos para el gráfico de línea (SVG) ---
$puntos_svg = [];
if ($promedio_general > 0) {
    $puntos_svg = [
        ['x' => 0, 'y' => 30 - ($promedio_general * 3)],
        ['x' => 20, 'y' => 30 - (($promedio_general + 0.5) * 3)],
        ['x' => 40, 'y' => 30 - (($promedio_general - 0.3) * 3)],
        ['x' => 60, 'y' => 30 - (($promedio_general + 0.8) * 3)],
        ['x' => 80, 'y' => 30 - (($promedio_general - 0.2) * 3)],
        ['x' => 100, 'y' => 30 - (($promedio_general + 0.1) * 3)]
    ];
} else {
    $puntos_svg = [
        ['x' => 0, 'y' => 30],
        ['x' => 20, 'y' => 30],
        ['x' => 40, 'y' => 30],
        ['x' => 60, 'y' => 30],
        ['x' => 80, 'y' => 30],
        ['x' => 100, 'y' => 30]
    ];
}

$path_d = "M";
foreach ($puntos_svg as $i => $punto) {
    $path_d .= $punto['x'] . "," . $punto['y'];
    if ($i < count($puntos_svg) - 1) {
        $path_d .= " L";
    }
}

$path_area = "M0,30 L" . implode(" L", array_map(fn($p) => $p['x'] . "," . $p['y'], $puntos_svg)) . " L100,30 L0,30 Z";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes</title>

    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="styles/reportes.css">
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
            <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
            <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
            <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
            <a href="reporte.php" class="menu-item active"><i class="fa-solid fa-chart-column"></i> Reportes</a>
            <a href="pasarlista.php" class="menu-item"><i class="fa-solid fa-bars"></i> Pasar Lista</a>
            <div class="menu-spacer"></div>
            <button class="btn-accessibility-main" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
            <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">

        <!-- ENCABEZADO CON FOTO DE PERFIL -->
        <?php
        // Obtener foto de perfil del docente
        $foto_perfil_docente = $_SESSION['usuario']['foto_perfil'] ?? null;
        $ruta_foto_docente = !empty($foto_perfil_docente) ? '../uploads/perfiles/' . $foto_perfil_docente : 'https://placehold.co/40x40/ff7675/white?text=👨';
        ?>
        <header class="content-header">
            <div class="welcome-text">
                <h1>Reportes</h1>
                <p>Analiza el progreso de tus clases</p>
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
        </header>

        <!-- FILTROS SUPERIORES Y CALENDARIO -->
        <div class="filters-and-calendar-grid">
            <div class="reports-filter-bar">
                <form method="GET" action="reporte.php">
                    <div class="filter-group">
                        <label>Seleccionar materia</label>
                        <select class="custom-select" name="materia">
                            <option value="todos">Todas las materias</option>
                            <?php foreach ($materias as $materia): ?>
                                <option value="<?php echo $materia['id_materia']; ?>" <?php echo ($materia_seleccionada == $materia['id_materia']) ? 'selected' : ''; ?>>
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
                                <option value="<?php echo $periodo['id_periodo']; ?>" <?php echo ($periodo_seleccionado == $periodo['id_periodo']) ? 'selected' : ''; ?>>
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

            <!-- Calendario -->
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

        <!-- RESUMEN GENERAL Y REPORTES DISPONIBLES -->
        <div class="reports-main-grid">
            <div class="reports-left">
                <h3 class="section-title">Resumen general</h3>
                <div class="stats-grid">
                    <div class="stat-card highlight-card">
                        <div class="stat-card-header">
                            <i class="fa-solid fa-chart-line stat-icon"></i>
                            <p class="stat-title">Promedio general</p>
                        </div>
                        <div class="stat-value-box">
                            <span class="stat-big"><?php echo number_format($promedio_general, 1); ?></span>
                            <span class="stat-small">/ 10</span>
                        </div>
                        <div class="mini-chart-container">
                            <svg viewBox="0 0 100 30" class="line-chart">
                                <path d="<?php echo $path_d; ?>" fill="none" stroke="#3b71f3" stroke-width="2"/>
                                <?php foreach ($puntos_svg as $punto): ?>
                                    <circle cx="<?php echo $punto['x']; ?>" cy="<?php echo $punto['y']; ?>" r="2" fill="#3b71f3"/>
                                <?php endforeach; ?>
                                <path d="<?php echo $path_area; ?>" fill="rgba(59, 113, 243, 0.1)"/>
                            </svg>
                        </div>
                    </div>

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

            <div class="reports-right">
                <h3 class="section-title">Reportes disponibles</h3>
                <div class="reports-list">
                    <a href="#rendimiento-actividad" class="report-list-item" onclick="mostrarReporte('rendimiento-actividad')">
                        <div class="report-icon-box purple-box">
                            <i class="fa-solid fa-chart-simple"></i>
                        </div>
                        <span class="report-name">Rendimiento por actividad</span>
                        <i class="fa-solid fa-chevron-right report-arrow"></i>
                    </a>
                    <a href="#rendimiento-evaluacion" class="report-list-item" onclick="mostrarReporte('rendimiento-evaluacion')">
                        <div class="report-icon-box orange-box">
                            <i class="fa-regular fa-file-lines"></i>
                        </div>
                        <span class="report-name">Rendimiento por evaluación</span>
                        <i class="fa-solid fa-chevron-right report-arrow"></i>
                    </a>
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

        <!-- SECCIÓN DE DETALLES DE REPORTES -->
        <div id="detalles-reportes" class="reports-details-section" style="display: none;">
            <div id="rendimiento-actividad" class="report-detail">
                <h3>Rendimiento por Actividad</h3>
                <div class="table-container">
                    <table class="report-table">
                        <thead>
                            <tr><th>Actividad</th><th>Promedio</th><th>Estudiantes</th><th>Aprobados</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rendimiento_actividad)): ?>
                                <tr><td colspan="4" style="text-align: center;">No hay datos disponibles.</td></tr>
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

            <div id="rendimiento-evaluacion" class="report-detail" style="display: none;">
                <h3>Rendimiento por Evaluación</h3>
                <div class="table-container">
                    <table class="report-table">
                        <thead>
                            <tr><th>Evaluación</th><th>Promedio</th><th>Estudiantes</th><th>Aprobados</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rendimiento_evaluacion)): ?>
                                <tr><td colspan="4" style="text-align: center;">No hay datos disponibles.</td></tr>
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

            <div id="asistencia-participacion" class="report-detail" style="display: none;">
                <h3>Asistencia y Participación</h3>
                <div class="table-container">
                    <table class="report-table">
                        <thead>
                            <tr><th>Actividad</th><th>Estudiantes</th><th>Participantes</th><th>% Participación</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($asistencia_participacion)): ?>
                                <tr><td colspan="4" style="text-align: center;">No hay datos disponibles.</td></tr>
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

<script>
    function mostrarReporte(reporteId) {
        const detalles = document.querySelectorAll('.report-detail');
        detalles.forEach(detalle => {
            detalle.style.display = 'none';
        });
        const reporteSeleccionado = document.getElementById(reporteId);
        if (reporteSeleccionado) {
            reporteSeleccionado.style.display = 'block';
            document.getElementById('detalles-reportes').style.display = 'block';
        }
    }
</script>

</body>
</html>