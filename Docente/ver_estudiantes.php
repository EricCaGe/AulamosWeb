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
$nombre_docente = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];

// Consultar los cursos del docente
$query_cursos = "
    SELECT c.id_curso, g.nombre AS grupo_nombre
    FROM cursos c
    JOIN grupos g ON c.id_grupo = g.id_grupo
    WHERE c.id_docente = ? AND c.estado = 'Activo'
";
$stmt_cursos = $conexion->prepare($query_cursos);
$stmt_cursos->bind_param("i", $id_docente);
$stmt_cursos->execute();
$result_cursos = $stmt_cursos->get_result();

$cursos = [];
while ($row = $result_cursos->fetch_assoc()) {
    $cursos[] = $row;
}

// Obtener los grupos únicos para los botones de filtro
$grupos_unicos = array_unique(array_column($cursos, 'grupo_nombre'));
sort($grupos_unicos);

// Consultar los estudiantes inscritos en los cursos del docente
$query_estudiantes = "
    SELECT
        u.id_usuario,
        u.nombre,
        u.apellido_paterno,
        u.apellido_materno,
        g.nombre AS grupo_nombre
    FROM inscripciones i
    JOIN cursos c ON i.id_curso = c.id_curso
    JOIN grupos g ON c.id_grupo = g.id_grupo
    JOIN usuarios u ON i.id_alumno = u.id_usuario
    WHERE c.id_docente = ? AND i.estado = 'Activo'
    ORDER BY g.nombre, u.apellido_paterno, u.nombre
";
$stmt_estudiantes = $conexion->prepare($query_estudiantes);
$stmt_estudiantes->bind_param("i", $id_docente);
$stmt_estudiantes->execute();
$result_estudiantes = $stmt_estudiantes->get_result();

$estudiantes = [];
while ($row = $result_estudiantes->fetch_assoc()) {
    $estudiantes[] = $row;
}

// Cerrar conexiones
$stmt_cursos->close();
$stmt_estudiantes->close();
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Estudiantes</title>

    <!-- CSS Base -->
    <link rel="stylesheet" href="styles/docente.css">
    <!-- CSS Específico para esta pantalla -->
    <link rel="stylesheet" href="styles/estudiantes.css">

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
            <a href="ver_estudiantes.php" class="menu-item active"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
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
                <h1>Ver estudiantes</h1>
                <p>Gestiona tu lista de estudiantes</p>
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

        <!-- BARRA DE BÚSQUEDA Y PESTAÑAS (TABS) -->
        <div class="students-header-tools">
            <div class="search-bar-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" class="student-search-input" placeholder="Buscar estudiante">
            </div>

            <div class="group-tabs" id="filter-tabs">
                <button class="tab-btn active" data-filter="todos">Todos</button>
                <?php foreach ($grupos_unicos as $grupo): ?>
                    <button class="tab-btn" data-filter="<?php echo htmlspecialchars($grupo); ?>">
                        <?php echo htmlspecialchars($grupo); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- LISTA DE ESTUDIANTES -->
        <div class="main-grid mt-20">
            <div class="left-column">
                <div class="students-list-container" id="students-list">
                    <?php if (empty($estudiantes)): ?>
                        <div class="no-students-message">
                            <i class="fa-solid fa-users-slash"></i>
                            <p>No hay estudiantes inscritos en tus cursos.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($estudiantes as $estudiante): ?>
                            <div class="student-card" data-group="<?php echo htmlspecialchars($estudiante['grupo_nombre']); ?>">
                                <div class="student-info-left">
                                    <i class="fa-solid fa-circle-user avatar-icon"></i>
                                    <div class="student-details">
                                        <h4><?php echo htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido_paterno'] . ' ' . $estudiante['apellido_materno']); ?></h4>
                                        <p><?php echo htmlspecialchars($estudiante['grupo_nombre']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
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

        <!-- NUEVA BARRA DE ACCESIBILIDAD -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </main>
</div>

<!-- BOTÓN FLOTANTE DE ACCESIBILIDAD -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<!-- SCRIPTS -->
<script src="jss/ver_estudiantes_ocultos.js?v=2"></script>
<script src="jss/docente_dashboard.js"></script>

<!-- NUEVA ACCESIBILIDAD -->
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>

</body>
</html>