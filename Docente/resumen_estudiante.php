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

$id_docente = $_SESSION['usuario']['id_usuario'];
$usuario = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
$rol = "Docente";

// =============================================
// OBTENER DATOS DEL ESTUDIANTE DESDE GET
// =============================================
$id_alumno = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_alumno <= 0) {
    // Si no hay ID, redirigir a la lista de estudiantes
    header('Location: mas.php');
    exit;
}

// Obtener datos del estudiante
$stmt = $conexion->prepare("
    SELECT 
        u.id_usuario,
        u.nombre,
        u.apellido_paterno,
        u.apellido_materno,
        g.nombre AS grupo
    FROM usuarios u
    LEFT JOIN inscripciones i ON u.id_usuario = i.id_alumno
    LEFT JOIN cursos c ON i.id_curso = c.id_curso
    LEFT JOIN grupos g ON c.id_grupo = g.id_grupo
    WHERE u.id_usuario = ?
    LIMIT 1
");
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$result = $stmt->get_result();
$estudiante = $result->fetch_assoc();
$stmt->close();

if (!$estudiante) {
    header('Location: mas.php');
    exit;
}

$nombre_alumno = $estudiante['nombre'] . ' ' . $estudiante['apellido_paterno'] . ' ' . ($estudiante['apellido_materno'] ?? '');
$grupo_alumno = $estudiante['grupo'] ?? 'Sin grupo';

// =============================================
// OBTENER ESTADÍSTICAS DEL ESTUDIANTE
// =============================================

// Total de actividades
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total
    FROM actividad_estudiantes
    WHERE id_alumno = ?
");
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$result = $stmt->get_result();
$total_actividades = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Completadas
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS completadas
    FROM actividad_estudiantes
    WHERE id_alumno = ? AND estado IN ('Completada', 'Calificada')
");
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$result = $stmt->get_result();
$completadas = $result->fetch_assoc()['completadas'] ?? 0;
$stmt->close();

// Pendientes
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS pendientes
    FROM actividad_estudiantes
    WHERE id_alumno = ? AND estado IN ('Pendiente', 'En_proceso')
");
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$result = $stmt->get_result();
$pendientes = $result->fetch_assoc()['pendientes'] ?? 0;
$stmt->close();

// Atrasadas
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS atrasadas
    FROM actividad_estudiantes ae
    JOIN actividades a ON ae.id_actividad = a.id_actividad
    WHERE ae.id_alumno = ? AND ae.estado = 'Pendiente' AND a.fecha_limite < NOW()
");
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$result = $stmt->get_result();
$atrasadas = $result->fetch_assoc()['atrasadas'] ?? 0;
$stmt->close();

// Porcentaje de completadas
$porcentaje = $total_actividades > 0 ? round(($completadas / $total_actividades) * 100) : 0;

// Calcular porcentaje de pendientes y atrasadas
$porcentaje_pendientes = $total_actividades > 0 ? round(($pendientes / $total_actividades) * 100, 1) : 0;
$porcentaje_atrasadas = $total_actividades > 0 ? round(($atrasadas / $total_actividades) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen del Estudiante - Aulamos</title>
    
    <!-- CSS Base -->
    <link rel="stylesheet" href="styles/docente.css">
    <!-- CSS Específico para esta pantalla -->
    <link rel="stylesheet" href="styles/resumen.css">
    
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
            <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
            <a href="pasarlista.php" class="menu-item"><i class="fa-solid fa-bars"></i> Pasar Lista</a>
            <a href="#" class="menu-item"><i class="fa-solid fa-universal-access"></i> Accesibilidad</a>
            
            <div class="menu-spacer"></div>
            <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        
        <!-- ENCABEZADO SUPERIOR CON FLECHA DE REGRESO -->
        <header class="content-header header-with-back">
            <div class="welcome-text">
                <h1>
                    <a href="mas.php" class="back-arrow"><i class="fa-solid fa-arrow-left"></i></a> 
                    Resumen del estudiante
                </h1>
            </div>
            
            <div class="header-actions">
                <button class="btn-assistant" id="btn-asistente">Asistente Virtual <span class="robot-icon">🤖</span></button>
                <div class="icon-bell-container">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <div class="user-profile">
                    <img src="https://placehold.co/40x40/ff7675/white?text=👩" alt="Avatar Docente" class="avatar">
                    <div class="user-info">
                        <span class="user-name"><?php echo $usuario; ?>!</span>
                        <span class="user-role"><?php echo $rol; ?></span>
                    </div>
                    <i class="fa-solid fa-chevron-down drop-icon"></i>
                </div>
            </div>
        </header>

        <!-- PERFIL DEL ESTUDIANTE -->
        <div class="student-profile-header">
            <i class="fa-regular fa-circle-user large-avatar"></i>
            <div class="student-details">
                <h2><?php echo htmlspecialchars($nombre_alumno); ?></h2>
                <span class="badge-grade"><?php echo htmlspecialchars($grupo_alumno); ?></span>
            </div>
        </div>

        <!-- CONTENEDOR PRINCIPAL A DOS COLUMNAS -->
        <div class="resumen-layout">
            
            <!-- COLUMNA IZQUIERDA: Tarjetas de resumen -->
            <div class="resumen-left">
                <h3 class="section-title">Resumen de actividades</h3>
                <div class="cards-grid">
                    
                    <div class="activity-card">
                        <span class="card-title">Total de actividades</span>
                        <div class="card-value"><?php echo $total_actividades; ?></div>
                        <span class="card-subtext gray">Asignadas</span>
                    </div>
                    
                    <div class="activity-card">
                        <span class="card-title">Completadas</span>
                        <div class="card-value"><?php echo $completadas; ?></div>
                        <span class="card-subtext green"><?php echo $porcentaje; ?>%</span>
                    </div>
                    
                    <div class="activity-card">
                        <span class="card-title">Pendientes</span>
                        <div class="card-value"><?php echo $pendientes; ?></div>
                        <span class="card-subtext yellow"><?php echo $porcentaje_pendientes; ?>%</span>
                    </div>
                    
                    <div class="activity-card">
                        <span class="card-title">Atrasadas</span>
                        <div class="card-value"><?php echo $atrasadas; ?></div>
                        <span class="card-subtext red"><?php echo $porcentaje_atrasadas; ?>%</span>
                    </div>

                </div>
            </div>

            <!-- COLUMNA DERECHA: Proceso y Botón -->
            <div class="resumen-right">
                <div class="progress-card">
                    <h3 class="progress-title">Proceso General</h3>
                    
                    <!-- Gráfico Circular -->
                    <div class="circular-progress">
                        <div class="inner-circle"><?php echo $porcentaje; ?>%</div>
                    </div>
                    
                    <h4><?php echo $porcentaje >= 70 ? 'Buen Trabajo 🥳' : '¡Sigue esforzándote! 💪'; ?></h4>
                    <p><?php echo htmlspecialchars($nombre_alumno); ?> ha completado el <?php echo $porcentaje; ?>% de sus actividades asignadas</p>
                </div>
                
                <a href="detalle_actividades.php?id=<?php echo $id_alumno; ?>" class="btn-ver-detalles" style="text-align: center; text-decoration: none; display: block;">
                    Ver detalles de actividades
                </a>  
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

</body>
</html>