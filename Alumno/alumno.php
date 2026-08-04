<?php
session_start();
// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header('Location: ../InicioSesion/login.php');
    exit;
}
require_once '../Conexion/conexion.php'; // Ajusta si es necesario

$id_usuario = $_SESSION['usuario']['id_usuario'];
// 1. Datos del usuario
$stmt = $conexion->prepare("SELECT nombre, apellido_paterno FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$usuario_data = $result->fetch_assoc();
$nombre_completo = $usuario_data['nombre'] . ' ' . $usuario_data['apellido_paterno'];

// 2. Continúa donde lo dejaste (última actividad en proceso)
$stmt = $conexion->prepare("
    SELECT a.titulo, m.nombre AS materia, ae.porcentaje_avance
    FROM actividad_estudiantes ae
    JOIN actividades a ON ae.id_actividad = a.id_actividad
    JOIN cursos c ON a.id_curso = c.id_curso
    JOIN materias m ON c.id_materia = m.id_materia
    WHERE ae.id_alumno = ? AND ae.estado = 'En_proceso'
    ORDER BY ae.ultimo_acceso DESC
    LIMIT 1
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$continua = $result->fetch_assoc();

if (!$continua) {
    // Si no hay en proceso, tomar la primera pendiente
    $stmt = $conexion->prepare("
        SELECT a.titulo, m.nombre AS materia, 0 AS porcentaje_avance
        FROM actividad_estudiantes ae
        JOIN actividades a ON ae.id_actividad = a.id_actividad
        JOIN cursos c ON a.id_curso = c.id_curso
        JOIN materias m ON c.id_materia = m.id_materia
        WHERE ae.id_alumno = ? AND ae.estado = 'Pendiente'
        ORDER BY a.fecha_limite ASC
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $continua = $result->fetch_assoc();
}

$tema_actual = $continua['titulo'] ?? 'Sin actividad en progreso';
$materia_actual = $continua['materia'] ?? '---';
$progreso_celula = $continua['porcentaje_avance'] ?? 0;

// 3. Actividad próxima
$stmt = $conexion->prepare("
    SELECT a.titulo, m.nombre AS materia, a.fecha_limite
    FROM actividad_estudiantes ae
    JOIN actividades a ON ae.id_actividad = a.id_actividad
    JOIN cursos c ON a.id_curso = c.id_curso
    JOIN materias m ON c.id_materia = m.id_materia
    WHERE ae.id_alumno = ? AND ae.estado = 'Pendiente' AND a.fecha_limite >= NOW()
    ORDER BY a.fecha_limite ASC
    LIMIT 1
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$proxima = $result->fetch_assoc();

$proxima_titulo = $proxima['titulo'] ?? 'No hay actividades pendientes';
$proxima_materia = $proxima['materia'] ?? '---';
$proxima_fecha = $proxima['fecha_limite'] ?? '';
$proxima_fecha_formateada = $proxima_fecha ? date('d M, Y', strtotime($proxima_fecha)) : 'Sin fecha';

// 4. Resumen de avances
// 4a. Actividades completadas (este mes)
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS completadas
    FROM actividad_estudiantes
    WHERE id_alumno = ? AND estado IN ('Completada', 'Calificada')
    AND MONTH(fecha_finalizacion) = MONTH(CURRENT_DATE())
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$actividades_completadas = $row['completadas'] ?? 0;

// 4b. Horas de aprendizaje
$stmt = $conexion->prepare("
    SELECT COALESCE(SUM(duracion_segundos), 0) / 3600 AS horas
    FROM eventos_investigacion
    WHERE id_usuario = ? AND MONTH(fecha_hora) = MONTH(CURRENT_DATE())
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$horas_aprendizaje = round($row['horas'] ?? 0, 1);

// 4c. Lecciones vistas (esta semana)
$stmt = $conexion->prepare("
    SELECT COUNT(DISTINCT id_actividad) AS lecciones
    FROM actividad_estudiantes
    WHERE id_alumno = ? AND estado IN ('Completada', 'Calificada')
    AND WEEK(fecha_finalizacion) = WEEK(CURRENT_DATE())
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$lecciones_vistas = $row['lecciones'] ?? 0;

// 4d. Racha de estudio
$stmt = $conexion->prepare("
    SELECT COUNT(DISTINCT DATE(fecha_hora)) AS dias_activos
    FROM eventos_investigacion
    WHERE id_usuario = ? AND DATE(fecha_hora) >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$racha_dias = $row['dias_activos'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aulamos - Inicio Estudiante</title>
    
    <link rel="stylesheet" href="Style/Inicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="dashboard-container">
    
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logogeneral.png" alt="Logo AulamosWeb" class="logo">
        </div>
        
        <nav class="menu">
            <a href="alumno.php" class="menu-item active"><i class="fa-solid fa-house"></i> Inicio</a>
            <a href="actividades.php" class="menu-item"><i class="fa-solid fa-cubes"></i> Mis actividades</a>
            <a href="biblioteca.php" class="menu-item"><i class="fa-solid fa-book-open"></i> Biblioteca digital</a>
            <a href="avances.php" class="menu-item"><i class="fa-solid fa-pen-to-square"></i> Mis avances</a>
            <a href="ayuda.php" class="menu-item"><i class="fa-solid fa-circle-question"></i> Ayuda</a>
            <a href="accesibilidad.php" class="menu-item"><i class="fa-solid fa-gear"></i> Accesibilidad</a>
        </nav>
        
        <button class="btn-accessibility-main"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>

        <div class="menu-spacer"></div>
    <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
    </aside>

    <main class="main-content">
        
        <header class="content-header">
    <div class="welcome-text">
        <h1>¡Hola, <?php echo htmlspecialchars($nombre_completo); ?>! 👋</h1>
        <p>Qué bueno verte hoy. Continúa aprendiendo a tu ritmo.</p>
    </div>
    <div class="header-actions">
        <button class="btn-assistant" id="btn-asistente" onclick="window.open('chatbot.php', '_blank')">
            Asistente Virtual <span class="robot-icon">🤖</span>
        </button>
        <div class="icon-bell"><i class="fa-regular fa-bell"></i></div>
        <img src="https://placehold.co/40x40/ff7675/white?text=👩" alt="Avatar Estudiante" class="avatar">
    </div>
</header>
        <section class="cards-grid">
            <div class="card card-purple">
                <h3>Continúa donde lo dejaste</h3>
                <div class="card-inner">
                    <h4><?php echo htmlspecialchars($tema_actual); ?></h4>
                    <p class="subtitle"><?php echo htmlspecialchars($materia_actual); ?></p>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?php echo $progreso_celula; ?>%;"></div>
                    </div>
                    <span class="progress-text"><?php echo $progreso_celula; ?>%</span>
                </div>
                <button class="btn-action btn-purple">Continuar</button>
            </div>

            <div class="card card-orange">
                <h3>Actividad próxima</h3>
                <div class="card-inner">
                    <h4><?php echo htmlspecialchars($proxima_titulo); ?></h4>
                    <p class="subtitle"><?php echo htmlspecialchars($proxima_materia); ?></p>
                    <p class="date-text"><i class="fa-regular fa-calendar"></i> Vence: <?php echo $proxima_fecha_formateada; ?></p>
                </div>
                <button class="btn-action btn-orange">Ver actividad</button>
            </div>
        </section>

        <section class="advances-section">
            <div class="section-title-container">
                <h3>Resumen de tus avances</h3>
                <a href="avances.php" class="view-details">Ver detalles ></a>
            </div>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <p class="stat-title">Actividades Completadas</p>
                    <div class="stat-value"><i class="fa-regular fa-circle-check icon-green"></i> <?php echo $actividades_completadas; ?></div>
                    <p class="stat-period">Este mes</p>
                </div>
                <div class="stat-box">
                    <p class="stat-title">Horas de aprendizaje</p>
                    <div class="stat-value"><i class="fa-regular fa-clock icon-blue"></i> <?php echo $horas_aprendizaje; ?> h</div>
                    <p class="stat-period">Este mes</p>
                </div>
                <div class="stat-box">
                    <p class="stat-title">Lecciones vistas</p>
                    <div class="stat-value"><i class="fa-regular fa-bookmark icon-purple-light"></i> <?php echo $lecciones_vistas; ?></div>
                    <p class="stat-period">Esta semana</p>
                </div>
                <div class="stat-box">
                    <p class="stat-title">Rachas de estudio</p>
                    <div class="stat-value"><i class="fa-solid fa-fire icon-orange-light"></i> <?php echo $racha_dias; ?></div>
                    <p class="stat-period">¡Sigue así!</p>
                </div>
            </div>
        </section>

        <footer class="accessibility-bar">
            <div class="acc-info">
                <i class="fa-solid fa-eye-low-vision acc-icon-main"></i>
                <div>
                    <strong>Accesibilidad siempre disponible</strong>
                    <p>Personaliza tu experiencia en cualquier momento.</p>
                </div>
            </div>
            <div class="acc-options">
                <button class="acc-opt-btn" id="btn-contrast"><i class="fa-solid fa-eye"></i><span>Alto contraste</span></button>
                <button class="acc-opt-btn" id="btn-darkmode"><i class="fa-solid fa-moon"></i><span>Modo oscuro</span></button>
                <button class="acc-opt-btn" id="btn-text-size"><i class="fa-solid fa-font"></i><span>Texto grande</span></button>
                <button class="acc-opt-btn"><i class="fa-solid fa-volume-high"></i><span>Leer pantalla</span></button>
                <button class="acc-opt-btn"><i class="fa-solid fa-closed-captioning"></i><span>Subtítulos</span></button>
                <button class="acc-opt-btn"><i class="fa-solid fa-keyboard"></i><span>Navegación</span></button>
            </div>
            <button class="btn-open-config">Abrir configuración</button>
        </footer>

    </main>
</div>

<script src="js/Inicio.js"></script>
</body>
</html>