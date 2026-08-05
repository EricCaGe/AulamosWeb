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

// ========================================== */
// 1. CONTAR CLASES ACTIVAS DEL DOCENTE      */
// ========================================== */
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total 
    FROM cursos 
    WHERE id_docente = ? AND estado = 'Activo'
");
$stmt->bind_param("i", $id_docente);
$stmt->execute();
$resultado = $stmt->get_result();
$row = $resultado->fetch_assoc();
$clases_activas = $row['total'] ?? 0;
$stmt->close();

// ========================================== */
// 2. CONTAR ACTIVIDADES PENDIENTES          */
// (Actividades creadas por el docente que no han sido calificadas)
// ========================================== */
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total 
    FROM actividades a
    INNER JOIN inscripciones ce ON a.id_curso = ce.id_curso
    INNER JOIN actividad_estudiantes ae ON a.id_actividad = ae.id_actividad AND ce.id_alumno = ae.id_alumno
    WHERE a.id_docente = ? AND ae.estado IN ('Pendiente', 'En_proceso', 'Entregada')
");
$stmt->bind_param("i", $id_docente);
$stmt->execute();
$resultado = $stmt->get_result();
$row = $resultado->fetch_assoc();
$actividades_pendientes = $row['total'] ?? 0;
$stmt->close();

// ========================================== */
// 3. CONTAR EVALUACIONES PENDIENTES         */
// ========================================== */
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total 
    FROM entregas e
    INNER JOIN actividad_estudiantes ae ON e.id_actividad_estudiante = ae.id_actividad_estudiante
    INNER JOIN actividades a ON ae.id_actividad = a.id_actividad
    WHERE a.id_docente = ? AND e.estado = 'Entregada'
");
$stmt->bind_param("i", $id_docente);
$stmt->execute();
$resultado = $stmt->get_result();
$row = $resultado->fetch_assoc();
$evaluaciones_pendientes = $row['total'] ?? 0;
$stmt->close();

// ========================================== */
// 4. CONTAR ESTUDIANTES TOTAL               */
// ========================================== */
$stmt = $conexion->prepare("
    SELECT COUNT(DISTINCT ce.id_alumno) AS total 
    FROM inscripciones ce
    INNER JOIN cursos c ON ce.id_curso = c.id_curso
    WHERE c.id_docente = ?
");
$stmt->bind_param("i", $id_docente);
$stmt->execute();
$resultado = $stmt->get_result();
$row = $resultado->fetch_assoc();
$estudiantes_total = $row['total'] ?? 0;
$stmt->close();

// ========================================== */
// 5. CONTENIDO RECIENTE (últimas 5 actividades creadas)
// ========================================== */
$stmt = $conexion->prepare("
    SELECT 
        a.titulo,
        a.tipo,
        a.fecha_publicacion,
        a.estado
    FROM actividades a
    WHERE a.id_docente = ?
    ORDER BY a.fecha_publicacion DESC
    LIMIT 5
");
$stmt->bind_param("i", $id_docente);
$stmt->execute();
$resultado = $stmt->get_result();
$contenido_reciente = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ========================================== */
// 6. PRÓXIMAS ACTIVIDADES (con fecha límite próxima)
// ========================================== */
$stmt = $conexion->prepare("
    SELECT 
        a.titulo,
        a.fecha_limite,
        a.tipo
    FROM actividades a
    WHERE a.id_docente = ? AND a.fecha_limite >= NOW() AND a.estado != 'Cerrada'
    ORDER BY a.fecha_limite ASC
    LIMIT 3
");
$stmt->bind_param("i", $id_docente);
$stmt->execute();
$resultado = $stmt->get_result();
$proximas_actividades = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ========================================== */
// 7. OBTENER ÍCONO SEGÚN TIPO DE ACTIVIDAD  */
// ========================================== */
function getIconoActividad($tipo) {
    switch ($tipo) {
        case 'Video': return 'fa-solid fa-play';
        case 'PDF': return 'fa-regular fa-file-pdf';
        case 'Documento': return 'fa-regular fa-file-lines';
        case 'Actividad': return 'fa-solid fa-clipboard-check';
        case 'Evaluacion': return 'fa-solid fa-clipboard-list';
        default: return 'fa-regular fa-file';
    }
}

function getColorActividad($tipo) {
    switch ($tipo) {
        case 'Video': return 'bg-blue-icon';
        case 'PDF': return 'bg-red-icon';
        case 'Documento': return 'bg-blue-doc-icon';
        case 'Actividad': return 'bg-green-icon';
        case 'Evaluacion': return 'bg-purple-icon';
        default: return 'bg-gray-icon';
    }
}

function getBadgeEstado($estado) {
    switch ($estado) {
        case 'Publicada': return 'badge-publicado';
        case 'Publicado': return 'badge-publicado';
        case 'Pendiente': return 'badge-pendiente';
        default: return 'badge-borrador';
    }
}

// Función para formatear fecha
function formatearFecha($fecha) {
    if (!$fecha) return 'Sin fecha';
    $dias = round((time() - strtotime($fecha)) / 86400);
    if ($dias == 0) return 'Publicado hoy';
    if ($dias == 1) return 'Publicado ayer';
    if ($dias < 7) return "Hace $dias días";
    return date('d M, Y', strtotime($fecha));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aulamos - Dashboard Docente</title>
    
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="dashboard-container">
        
        <!-- BARRA LATERAL -->
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../img/logo_g.png" alt="Logo Aulamos" class="logo-img">
                
            </div>
            
            <nav class="menu">
                <a href="docente_dashboard.php" class="menu-item active"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="crear_recurso.php" class="menu-item"><i class="fa-solid fa-medal"></i> Crear Recurso</a>
                <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluación</a>
                <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                <a href="mas.php" class="menu-item"><i class="fa-solid fa-bars"></i> Más</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-gear"></i> Configuración</a>
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
                    <h1>Dashboard Docente</h1>
                    <h2>¡Hola Prof. <?php echo htmlspecialchars($nombre_docente); ?>! 👋</h2>
                    <p>Bienvenido a tu espacio docente.</p>
                </div>
                <div class="header-actions">
                    <button
    type="button"
    class="btn-assistant"
    id="btn-asistente"
    onclick="window.location.href='../Alumno/ChatbotDocente.php?rol=docente'"
>
    Asistente Virtual
    <span class="robot-icon">🤖</span>
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
                        <i class="fa-solid fa-chevron-down drop-icon"></i>
                    </div>
                </div>
            </header>

            <!-- GRID PRINCIPAL (2 COLUMNAS) -->
            <div class="main-grid">
                
                <!-- COLUMNA IZQUIERDA (Ancha) -->
                <div class="left-column">
                    
                    <!-- Resumen del día -->
                    <section class="section-container">
                        <h3 class="section-title">Resumen del día</h3>
                        <div class="stats-grid">
                            <div class="stat-box bg-purple-light">
                                <div class="stat-icon-top"><i class="fa-solid fa-chalkboard-user"></i></div>
                                <div class="stat-content">
                                    <p class="stat-label">Clases activas</p>
                                    <h4 class="stat-number"><?php echo $clases_activas; ?></h4>
                                    <p class="stat-sub">Hoy</p>
                                </div>
                            </div>
                            <div class="stat-box bg-green-light">
                                <div class="stat-icon-top text-green"><i class="fa-regular fa-square-check"></i></div>
                                <div class="stat-content">
                                    <p class="stat-label">Actividades pendientes</p>
                                    <h4 class="stat-number"><?php echo $actividades_pendientes; ?></h4>
                                    <p class="stat-sub">Por revisar</p>
                                </div>
                            </div>
                            <div class="stat-box bg-yellow-light">
                                <div class="stat-icon-top text-yellow"><i class="fa-regular fa-file-lines"></i></div>
                                <div class="stat-content">
                                    <p class="stat-label">Evaluaciones pendientes</p>
                                    <h4 class="stat-number"><?php echo $evaluaciones_pendientes; ?></h4>
                                    <p class="stat-sub">Por calificar</p>
                                </div>
                            </div>
                            <div class="stat-box bg-blue-light">
                                <div class="stat-icon-top text-blue"><i class="fa-solid fa-user-group"></i></div>
                                <div class="stat-content">
                                    <p class="stat-label">Estudiantes en total</p>
                                    <h4 class="stat-number"><?php echo $estudiantes_total; ?></h4>
                                    <p class="stat-sub">En la plataforma</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Accesos rápidos -->
                    <section class="section-container">
                        <h3 class="section-title">Accesos rápidos</h3>
                        <div class="quick-access-grid">
                            <a href="crear_curso.php" class="quick-btn bg-purple-solid">
                                <i class="fa-solid fa-arrow-up-from-bracket"></i>
                                <span>Crear curso</span>
                            </a>
                            <a href="crear_actividad.php" class="quick-btn bg-green-solid">
                                <i class="fa-solid fa-clipboard-check"></i>
                                <span>Crear actividad</span>
                            </a>
                            <a href="crear_evaluacion.php" class="quick-btn bg-yellow-solid text-dark-yellow">
                                <i class="fa-solid fa-clipboard-list"></i>
                                <span>Crear evaluación</span>
                            </a>
                            <a href="ver_estudiantes.php" class="quick-btn bg-blue-solid">
                                <i class="fa-solid fa-users"></i>
                                <span>Ver estudiantes</span>
                            </a>
                            <a href="reporte.php" class="quick-btn bg-gray-solid">
                                <i class="fa-solid fa-chart-column"></i>
                                <span>Reportes</span>
                            </a>
                        </div>
                    </section>

                    <!-- Contenido reciente -->
                    <section class="section-container border-container">
                        <div class="section-header">
                            <h3 class="section-title">Contenido reciente</h3>
                            <a href="#" class="link-blue">Ver todo</a>
                        </div>
                        
                        <div class="content-list">
                            <?php if (empty($contenido_reciente)): ?>
                                <p class="text-center text-muted">No hay contenido reciente.</p>
                            <?php else: ?>
                                <?php foreach ($contenido_reciente as $item): ?>
                                <div class="list-item">
                                    <div class="item-main">
                                        <div class="icon-box <?php echo getColorActividad($item['tipo']); ?>">
                                            <i class="<?php echo getIconoActividad($item['tipo']); ?>"></i>
                                        </div>
                                        <div>
                                            <h4 class="item-title"><?php echo htmlspecialchars($item['titulo']); ?></h4>
                                            <p class="item-desc"><?php echo htmlspecialchars($item['tipo']); ?> • <?php echo formatearFecha($item['fecha_publicacion']); ?></p>
                                        </div>
                                    </div>
                                    <div class="item-actions">
                                        <span class="badge <?php echo getBadgeEstado($item['estado']); ?>"><?php echo htmlspecialchars($item['estado']); ?></span>
                                        <i class="fa-solid fa-ellipsis-vertical menu-dots"></i>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-center mt-15">
                            <a href="#" class="link-blue view-all-link">Ver todo mi contenido</a>
                        </div>
                    </section>

                </div>

                <!-- COLUMNA DERECHA (Estrecha) -->
                <div class="right-column">
                    
                    <!-- Calendario -->
                     <!-- Calendario -->
<aside class="calendar-container">
    <!-- Cabecera y Navegación -->
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

                    <!-- Próximas actividades -->
                    <aside class="upcoming-activities border-container">
                        <h3 class="section-title">Próximas actividades</h3>
                        
                        <div class="activity-list">
                            <?php if (empty($proximas_actividades)): ?>
                                <p class="text-center text-muted">No hay actividades próximas.</p>
                            <?php else: ?>
                                <?php foreach ($proximas_actividades as $item): ?>
                                <div class="activity-item">
                                    <div class="act-icon text-green"><i class="fa-solid fa-clipboard-check"></i></div>
                                    <div class="act-details">
                                        <h5><?php echo htmlspecialchars($item['titulo']); ?></h5>
                                        <p><?php echo date('d M, Y • h:i A', strtotime($item['fecha_limite'])); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="text-center mt-15">
                            <a href="#" class="link-blue view-all-link">Ver todas mis actividades</a>
                        </div>
                    </aside>

                </div>
            </div>

            <!-- BARRA DE ACCESIBILIDAD INFERIOR -->
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

    <script src="jss/docente_dashboard.js"></script>
</body>
</html>