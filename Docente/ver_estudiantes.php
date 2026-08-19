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
        u.correo,
        g.nombre AS grupo_nombre,
        GROUP_CONCAT(DISTINCT m.nombre SEPARATOR ', ') AS materias
    FROM inscripciones i
    JOIN cursos c ON i.id_curso = c.id_curso
    JOIN grupos g ON c.id_grupo = g.id_grupo
    JOIN usuarios u ON i.id_alumno = u.id_usuario
    JOIN materias m ON c.id_materia = m.id_materia
    WHERE c.id_docente = ? AND i.estado = 'Activo'
    GROUP BY u.id_usuario, u.nombre, u.apellido_paterno, u.apellido_materno, u.correo, g.nombre
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

// Calcular promedio y estadísticas para cada estudiante
foreach ($estudiantes as &$estudiante) {
    $id_alumno = $estudiante['id_usuario'];
    
    // Obtener calificaciones del estudiante en cursos del docente
    $query_calificaciones = "
        SELECT 
            e.calificacion,
            a.puntaje_maximo,
            c.id_curso
        FROM entregas e
        JOIN actividad_estudiantes ae ON e.id_actividad_estudiante = ae.id_actividad_estudiante
        JOIN actividades a ON ae.id_actividad = a.id_actividad
        JOIN cursos c ON a.id_curso = c.id_curso
        WHERE ae.id_alumno = ? 
        AND c.id_docente = ?
        AND e.calificacion IS NOT NULL
    ";
    
    $stmt_calif = $conexion->prepare($query_calificaciones);
    $stmt_calif->bind_param("ii", $id_alumno, $id_docente);
    $stmt_calif->execute();
    $result_calif = $stmt_calif->get_result();
    
    $suma_porcentajes = 0;
    $total_calif = 0;
    
    while ($row = $result_calif->fetch_assoc()) {
        $puntaje_maximo = $row['puntaje_maximo'] > 0 ? $row['puntaje_maximo'] : 100;
        $porcentaje = ($row['calificacion'] / $puntaje_maximo) * 100;
        $suma_porcentajes += $porcentaje;
        $total_calif++;
    }
    
    $estudiante['promedio'] = $total_calif > 0 ? round($suma_porcentajes / $total_calif, 1) : 0;
    $estudiante['total_calificaciones'] = $total_calif;
    
    $stmt_calif->close();
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
    
    <style>
        /* Estilos adicionales para la lista de estudiantes */
        .student-card {
            transition: all 0.2s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .student-card:hover {
            background: #f8fafc;
            border-color: #3b71f3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 113, 243, 0.1);
        }

        .student-card a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            text-decoration: none;
            color: inherit;
        }

        .student-info-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar-icon {
            font-size: 32px;
            color: #3b71f3;
            background: #eff6ff;
            border-radius: 50%;
            padding: 4px;
        }

        .student-details h4 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }

        .student-details p {
            margin: 2px 0 0 0;
            font-size: 12px;
            color: #64748b;
        }

        .student-stats {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .student-stats .stat-item {
            text-align: center;
            padding: 0 10px;
        }

        .student-stats .stat-number {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }

        .student-stats .stat-label {
            font-size: 10px;
            color: #64748b;
        }

        .student-stats .stat-number.promedio {
            color: #3b71f3;
        }

        .chevron-icon {
            color: #cbd5e1;
            font-size: 14px;
        }

        .no-students-message {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .no-students-message i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 15px;
        }

        .no-students-message p {
            color: #64748b;
            margin: 0;
        }

        .students-header-tools {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .search-bar-container {
            display: flex;
            align-items: center;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0 12px;
            flex: 1;
            min-width: 200px;
            max-width: 400px;
        }

        .search-bar-container .search-icon {
            color: #94a3b8;
            margin-right: 10px;
        }

        .student-search-input {
            border: none;
            padding: 10px 0;
            width: 100%;
            outline: none;
            font-size: 14px;
            background: transparent;
        }

        .group-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 6px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: white;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            transition: all 0.2s;
        }

        .tab-btn:hover {
            background: #f1f5f9;
        }

        .tab-btn.active {
            background: #3b71f3;
            color: white;
            border-color: #3b71f3;
        }

        .mt-20 {
            margin-top: 20px;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 24px;
        }

        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            .right-column {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .students-header-tools {
                flex-direction: column;
                align-items: stretch;
            }
            .search-bar-container {
                max-width: 100%;
            }
            .group-tabs {
                justify-content: center;
            }
            .student-stats {
                display: none;
            }
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
            <a href="ver_estudiantes.php" class="menu-item active"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
            <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
            <a href="pasarlista.php" class="menu-item"><i class="fa-solid fa-bars"></i> Pasar Lista</a>
            <a href="juegos_docente.php" class="menu-item"><i class="fa-solid fa-gamepad"></i> Conecta y Aprende</a>
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
                <h1>Ver estudiantes</h1>
                <p>Gestiona tu lista de estudiantes y consulta sus avances</p>
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

        <!-- BARRA DE BÚSQUEDA Y PESTAÑAS (TABS) -->
        <div class="students-header-tools">
            <div class="search-bar-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" class="student-search-input" placeholder="Buscar estudiante por nombre...">
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
                                <a href="ver_avances_estudiante.php?id=<?= $estudiante['id_usuario'] ?>">
                                    <div class="student-info-left">
                                        <i class="fa-solid fa-circle-user avatar-icon"></i>
                                        <div class="student-details">
                                            <h4><?php echo htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido_paterno'] . ' ' . $estudiante['apellido_materno']); ?></h4>
                                            <p>
                                                <i class="fa-regular fa-envelope" style="font-size: 10px;"></i> 
                                                <?php echo htmlspecialchars($estudiante['correo']); ?>
                                                &nbsp;·&nbsp;
                                                <i class="fa-regular fa-folder"></i> 
                                                <?php echo htmlspecialchars($estudiante['grupo_nombre']); ?>
                                                <?php if (!empty($estudiante['materias'])): ?>
                                                    &nbsp;·&nbsp;
                                                    <i class="fa-regular fa-bookmark"></i>
                                                    <?php echo htmlspecialchars($estudiante['materias']); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="student-stats">
                                        <div class="stat-item">
                                            <div class="stat-number promedio"><?= $estudiante['promedio'] ?>%</div>
                                            <div class="stat-label">Promedio</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-number"><?= $estudiante['total_calificaciones'] ?></div>
                                            <div class="stat-label">Calif.</div>
                                        </div>
                                        <i class="fa-solid fa-chevron-right chevron-icon"></i>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Calendario -->
            <aside class="right-column">
                <div class="border-container">
                    <div class="calendar-container">
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
                    </div>
                </div>
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
<script src="jss/docente_dashboard.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Seleccionamos los elementos
    const tabs = document.querySelectorAll('.tab-btn');
    const students = document.querySelectorAll('.student-card');
    const searchInput = document.querySelector('.student-search-input');

    // Función para filtrar estudiantes por pestaña y búsqueda
    function filterStudents() {
        const activeTab = document.querySelector('.tab-btn.active');
        const groupFilter = activeTab ? activeTab.getAttribute('data-filter') : 'todos';
        const searchTerm = searchInput.value.toLowerCase().trim();

        let visibleStudents = 0;

        students.forEach(student => {
            const studentGroup = student.getAttribute('data-group');
            const studentName = student.querySelector('h4')?.textContent.toLowerCase() || '';

            const matchesGroup = (groupFilter === 'todos' || groupFilter === studentGroup);
            const matchesSearch = studentName.includes(searchTerm);

            if (matchesGroup && matchesSearch) {
                student.style.display = 'flex';
                visibleStudents++;
            } else {
                student.style.display = 'none';
            }
        });

        // Mostrar mensaje si no hay estudiantes visibles
        const noStudentsMessage = document.querySelector('.no-students-message');
        if (noStudentsMessage) {
            noStudentsMessage.style.display = (visibleStudents === 0) ? 'block' : 'none';
        }
    }

    // Evento para los botones de grupos
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            filterStudents();
        });
    });

    // Evento para la barra de búsqueda con debounce
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(filterStudents, 300);
        });
    }

    // Filtrar al cargar la página
    filterStudents();
});
</script>

</body>
</html>