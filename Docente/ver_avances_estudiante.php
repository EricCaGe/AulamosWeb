<?php
session_start();

// Verificar que el usuario sea docente
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_docente = $_SESSION['usuario']['id_usuario'];
$nombre_docente = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];

// Obtener ID del estudiante desde GET
$id_estudiante = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_estudiante === 0) {
    header('Location: ver_estudiantes.php');
    exit;
}

// Obtener datos del estudiante
$query_estudiante = "
    SELECT 
        u.id_usuario,
        u.nombre,
        u.apellido_paterno,
        u.apellido_materno,
        u.correo
    FROM usuarios u
    WHERE u.id_usuario = ? AND u.estado = 'Activo'
";

$stmt_estudiante = $conexion->prepare($query_estudiante);
$stmt_estudiante->bind_param("i", $id_estudiante);
$stmt_estudiante->execute();
$result_estudiante = $stmt_estudiante->get_result();
$estudiante = $result_estudiante->fetch_assoc();

if (!$estudiante) {
    header('Location: ver_estudiantes.php');
    exit;
}

$nombre_completo = $estudiante['nombre'] . ' ' . $estudiante['apellido_paterno'] . ' ' . $estudiante['apellido_materno'];

// Obtener cursos del estudiante con el docente actual
$query_cursos = "
    SELECT DISTINCT
        c.id_curso,
        c.nombre AS nombre_curso,
        m.nombre AS materia,
        g.nombre AS grupo
    FROM inscripciones i
    JOIN cursos c ON i.id_curso = c.id_curso
    JOIN materias m ON c.id_materia = m.id_materia
    JOIN grupos g ON c.id_grupo = g.id_grupo
    WHERE i.id_alumno = ? 
    AND c.id_docente = ?
    AND i.estado = 'Activo'
    AND c.estado = 'Activo'
";

$stmt_cursos = $conexion->prepare($query_cursos);
$stmt_cursos->bind_param("ii", $id_estudiante, $id_docente);
$stmt_cursos->execute();
$result_cursos = $stmt_cursos->get_result();
$cursos = $result_cursos->fetch_all(MYSQLI_ASSOC);

// Obtener el curso seleccionado (por defecto el primero)
$id_curso_seleccionado = isset($_GET['id_curso']) ? (int)$_GET['id_curso'] : ($cursos ? $cursos[0]['id_curso'] : null);

// Obtener todas las actividades del estudiante en el curso seleccionado
$actividades_estudiante = [];
$promedio_general = 0;
$total_calificaciones = 0;

if ($id_curso_seleccionado) {
    $query_actividades = "
        SELECT 
            a.id_actividad,
            a.titulo,
            a.descripcion,
            a.tipo,
            a.puntaje_maximo,
            a.fecha_publicacion,
            a.fecha_limite,
            ae.id_actividad_estudiante,
            ae.estado,
            ae.porcentaje_avance,
            ae.fecha_inicio,
            ae.fecha_finalizacion,
            e.calificacion,
            e.retroalimentacion,
            e.fecha_entrega,
            CASE 
                WHEN ae.estado = 'Pendiente' AND a.fecha_limite < NOW() THEN 'Atrasada'
                ELSE ae.estado
            END AS estado_mostrar
        FROM actividades a
        JOIN actividad_estudiantes ae ON a.id_actividad = ae.id_actividad
        LEFT JOIN entregas e ON ae.id_actividad_estudiante = e.id_actividad_estudiante
        WHERE a.id_curso = ? 
        AND ae.id_alumno = ?
        AND a.estado != 'Borrador'
        ORDER BY a.fecha_limite DESC
    ";

    $stmt_actividades = $conexion->prepare($query_actividades);
    $stmt_actividades->bind_param("ii", $id_curso_seleccionado, $id_estudiante);
    $stmt_actividades->execute();
    $result_actividades = $stmt_actividades->get_result();
    $actividades_estudiante = $result_actividades->fetch_all(MYSQLI_ASSOC);

    // Calcular promedio del estudiante en este curso
    $suma_calificaciones = 0;
    $total_calificaciones = 0;
    
    foreach ($actividades_estudiante as $actividad) {
        if ($actividad['calificacion'] !== null) {
            // Normalizar calificación a porcentaje
            $puntaje_maximo = $actividad['puntaje_maximo'] > 0 ? $actividad['puntaje_maximo'] : 100;
            $calificacion_porcentaje = ($actividad['calificacion'] / $puntaje_maximo) * 100;
            
            $suma_calificaciones += $calificacion_porcentaje;
            $total_calificaciones++;
        }
    }
    
    $promedio_general = $total_calificaciones > 0 ? round($suma_calificaciones / $total_calificaciones, 1) : 0;
}

// Obtener estadísticas de asistencia del estudiante en el curso seleccionado
$asistencia_stats = ['presentes' => 0, 'faltas' => 0, 'retardos' => 0];
if ($id_curso_seleccionado) {
    // Obtener asistencia de la sesión (simulada, ya que no hay tabla de asistencia)
    // En una implementación real, aquí se consultaría la tabla de asistencias
    $asistencia_guardada = isset($_SESSION['asistencia'][$id_curso_seleccionado]) ? 
        $_SESSION['asistencia'][$id_curso_seleccionado] : [];
    
    if (isset($asistencia_guardada[$id_estudiante])) {
        $estado_asistencia = $asistencia_guardada[$id_estudiante];
        if ($estado_asistencia === 'Presente') $asistencia_stats['presentes']++;
        elseif ($estado_asistencia === 'Falta') $asistencia_stats['faltas']++;
        elseif ($estado_asistencia === 'Retardo') $asistencia_stats['retardos']++;
    }
}

// Cerrar conexiones
$stmt_estudiante->close();
$stmt_cursos->close();
if (isset($stmt_actividades)) $stmt_actividades->close();
$conexion->close();

// Mapeo de estados a texto legible
$estados_texto = [
    'Pendiente' => 'Pendiente',
    'Atrasada' => 'Atrasada',
    'En_proceso' => 'En Proceso',
    'Completada' => 'Completada',
    'Calificada' => 'Calificada'
];

function getEstadoBadgeClass($estado) {
    switch ($estado) {
        case 'Pendiente': return 'estado-pendiente';
        case 'Atrasada': return 'estado-atrasada';
        case 'En_proceso': return 'estado-proceso';
        case 'Completada': return 'estado-completada';
        case 'Calificada': return 'estado-calificada';
        default: return '';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avances del Estudiante - Aulamos</title>
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    
    <style>
        /* Estilos para la vista de avances */
        .student-profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        .student-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #3b71f3;
        }
        .student-info-detail {
            flex: 1;
        }
        .student-info-detail h2 {
            margin: 0 0 5px 0;
            color: #1e293b;
        }
        .student-info-detail p {
            margin: 0;
            color: #64748b;
        }
        .back-btn {
            background: #f1f5f9;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            color: #1e293b;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .back-btn:hover {
            background: #e2e8f0;
        }
        .course-selector-avances {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .course-btn {
            padding: 8px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        .course-btn.active {
            background: #3b71f3;
            color: white;
            border-color: #3b71f3;
        }
        .course-btn:hover:not(.active) {
            background: #f1f5f9;
        }
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .stat-number {
            font-size: 28px;
            font-weight: 800;
            color: #1e293b;
        }
        .stat-number.promedio {
            color: #3b71f3;
        }
        .stat-number.presente {
            color: #22c55e;
        }
        .stat-number.falta {
            color: #dc2626;
        }
        .stat-number.retardo {
            color: #d97706;
        }
        .stat-label {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        .activity-list {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .activity-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
            padding: 12px 16px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            font-size: 12px;
            color: #64748b;
        }
        .activity-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            align-items: center;
        }
        .activity-row:last-child {
            border-bottom: none;
        }
        .activity-row .titulo {
            font-weight: 500;
            color: #1e293b;
        }
        .activity-row .tipo-badge {
            font-size: 10px;
            padding: 2px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        .tipo-badge.tarea {
            background: #ff9f43;
            color: white;
        }
        .tipo-badge.evaluacion {
            background: #4f7cff;
            color: white;
        }
        .tipo-badge.proyecto {
            background: #9b59b6;
            color: white;
        }
        .activity-row .estado-badge {
            font-size: 10px;
            padding: 2px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        .estado-pendiente { background: #f39c12; color: white; }
        .estado-atrasada { background: #e74c3c; color: white; }
        .estado-proceso { background: #3498db; color: white; }
        .estado-completada { background: #2ecc71; color: white; }
        .estado-calificada { background: #9b59b6; color: white; }
        .activity-row .calificacion {
            font-weight: 600;
        }
        .activity-row .calificacion.aprobado {
            color: #22c55e;
        }
        .activity-row .calificacion.reprobado {
            color: #dc2626;
        }
        .activity-row .calificacion.pendiente {
            color: #f39c12;
        }
        .progress-bar-mini {
            width: 100%;
            height: 4px;
            background: #ecf0f1;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 4px;
        }
        .progress-bar-mini .fill {
            height: 100%;
            background: #4f7cff;
            border-radius: 4px;
            transition: width 0.3s;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #cbd5e1;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #3b71f3;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
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
                <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
                <a href="ver_estudiantes.php" class="menu-item active"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                <a href="pasarlista.php" class="menu-item"><i class="fa-solid fa-bars"></i> Pasar Lista</a>
                <div class="menu-spacer"></div>
                <button class="btn-accessibility-main" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
                <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
            </nav>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="main-content">
            
            <!-- ENCABEZADO -->
            <div class="content-header">
                <div class="welcome-text">
                    <h1>Avances del Estudiante</h1>
                    <p>Visualiza el progreso académico del estudiante</p>
                </div>
                <div class="header-actions">
                    <button type="button" class="btn-assistant" onclick="window.location.href='../Alumno/ChatbotDocente.php?rol=docente'">
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
            </div>

            <!-- CONTENIDO -->
            <div class="main-grid">
                <div class="left-column">
                    
                    <!-- Botón de regreso -->
                    <a href="ver_estudiantes.php" class="back-link">
                        <i class="fa-solid fa-arrow-left"></i> Volver a estudiantes
                    </a>

                    <!-- Perfil del estudiante -->
                    <div class="student-profile-header">
                        <div class="student-avatar-large">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="student-info-detail">
                            <h2><?php echo htmlspecialchars($nombre_completo); ?></h2>
                            <p><i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($estudiante['correo']); ?></p>
                        </div>
                    </div>

                    <!-- Selector de cursos -->
                    <?php if (!empty($cursos)): ?>
                    <div class="course-selector-avances">
                        <?php foreach ($cursos as $curso): ?>
                            <a href="ver_avances_estudiante.php?id=<?= $id_estudiante ?>&id_curso=<?= $curso['id_curso'] ?>" 
                               class="course-btn <?= ($curso['id_curso'] == $id_curso_seleccionado) ? 'active' : '' ?>">
                                <?= htmlspecialchars($curso['materia']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Estadísticas de resumen -->
                    <div class="summary-stats">
                        <div class="stat-card">
                            <div class="stat-number promedio"><?= $promedio_general ?>%</div>
                            <div class="stat-label">Promedio General</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number presente"><?= $asistencia_stats['presentes'] ?></div>
                            <div class="stat-label">Presentes</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number falta"><?= $asistencia_stats['faltas'] ?></div>
                            <div class="stat-label">Faltas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number retardo"><?= $asistencia_stats['retardos'] ?></div>
                            <div class="stat-label">Retardos</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= count($actividades_estudiante) ?></div>
                            <div class="stat-label">Total Actividades</div>
                        </div>
                    </div>

                    <!-- Lista de actividades del estudiante -->
                    <div class="activity-list">
                        <div class="activity-header">
                            <div>Actividad</div>
                            <div>Tipo</div>
                            <div>Estado</div>
                            <div>Calificación</div>
                            <div>Avance</div>
                        </div>

                        <?php if (!empty($actividades_estudiante)): ?>
                            <?php foreach ($actividades_estudiante as $actividad): ?>
                                <?php
                                $estado_mostrar = $actividad['estado_mostrar'];
                                $calificacion = $actividad['calificacion'];
                                $puntaje_maximo = $actividad['puntaje_maximo'] > 0 ? $actividad['puntaje_maximo'] : 100;
                                
                                // Determinar clase de calificación
                                $calif_clase = 'pendiente';
                                if ($calificacion !== null) {
                                    $porcentaje = ($calificacion / $puntaje_maximo) * 100;
                                    $calif_clase = $porcentaje >= 60 ? 'aprobado' : 'reprobado';
                                }
                                
                                $avance = $actividad['porcentaje_avance'] ?? 0;
                                ?>
                                <div class="activity-row">
                                    <div class="titulo"><?= htmlspecialchars($actividad['titulo']) ?></div>
                                    <div>
                                        <span class="tipo-badge <?= strtolower($actividad['tipo']) ?>">
                                            <?= htmlspecialchars($actividad['tipo']) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="estado-badge <?= getEstadoBadgeClass($estado_mostrar) ?>">
                                            <?= $estados_texto[$estado_mostrar] ?? $estado_mostrar ?>
                                        </span>
                                    </div>
                                    <div>
                                        <?php if ($calificacion !== null): ?>
                                            <span class="calificacion <?= $calif_clase ?>">
                                                <?= number_format($calificacion, 1) ?> / <?= number_format($puntaje_maximo, 1) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="calificacion pendiente">--</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span><?= $avance ?>%</span>
                                        <div class="progress-bar-mini">
                                            <div class="fill" style="width: <?= $avance ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fa-regular fa-clipboard"></i>
                                <p>No hay actividades registradas para este estudiante en este curso.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- Calendario (derecha) -->
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

            <!-- BARRA DE ACCESIBILIDAD -->
            <?php include '../Accesibilidad/accesibilidad.php'; ?>

        </div>
    </div>

    <!-- BOTÓN FLOTANTE DE ACCESIBILIDAD -->
    <button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
        <i class="fa-solid fa-universal-access"></i>
    </button>

    <!-- SCRIPTS -->
    <script src="jss/docente_dashboard.js"></script>
    <script src="../Accesibilidad/accesibilidad.js"></script>
    <script src="../Accesibilidad/navegacionTeclado.js"></script>
</body>
</html>