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

// ==========================================
// RECARGAR DATOS DEL USUARIO PARA ACTUALIZAR FOTO
// ==========================================
function recargarDatosUsuario($conexion) {
    if (isset($_SESSION['usuario']['id_usuario'])) {
        $id_usuario = $_SESSION['usuario']['id_usuario'];
        
        $stmt = $conexion->prepare("SELECT nombre, apellido_paterno, foto_perfil FROM usuarios WHERE id_usuario = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $row = $resultado->fetch_assoc();
            if ($row) {
                $_SESSION['usuario']['nombre'] = $row['nombre'];
                $_SESSION['usuario']['apellido_paterno'] = $row['apellido_paterno'];
                $_SESSION['usuario']['foto_perfil'] = $row['foto_perfil'];
            }
            $stmt->close();
        }
    }
}

// Recargar datos del usuario
recargarDatosUsuario($conexion);

$id_docente = $_SESSION['usuario']['id_usuario'];
$nombre_docente = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];

// Obtener foto de perfil del docente
$foto_perfil_docente = $_SESSION['usuario']['foto_perfil'] ?? null;
$ruta_foto_docente = !empty($foto_perfil_docente) ? '../uploads/perfiles/' . $foto_perfil_docente : 'https://placehold.co/40x40/ff7675/white?text=👨';

// ========================================== */
// 1. CONTAR CLASES ACTIVAS DEL DOCENTE      */
// ========================================== */
$clases_activas = 0;
$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM cursos WHERE id_docente = ? AND estado = 'Activo'");
if ($stmt) {
    $stmt->bind_param("i", $id_docente);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $row = $resultado->fetch_assoc();
    $clases_activas = $row['total'] ?? 0;
    $stmt->close();
}

// ========================================== */
// 2. CONTAR ACTIVIDADES PENDIENTES          */
// ========================================== */
$actividades_pendientes = 0;
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total 
    FROM actividades a
    WHERE a.id_docente = ? AND a.estado = 'Publicada'
");
if ($stmt) {
    $stmt->bind_param("i", $id_docente);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $row = $resultado->fetch_assoc();
    $actividades_pendientes = $row['total'] ?? 0;
    $stmt->close();
}

// ========================================== */
// 3. CONTAR EVALUACIONES PENDIENTES         */
// ========================================== */
$evaluaciones_pendientes = 0;
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total 
    FROM actividades a
    WHERE a.id_docente = ? AND a.tipo = 'Evaluacion' AND a.estado = 'Publicada'
");
if ($stmt) {
    $stmt->bind_param("i", $id_docente);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $row = $resultado->fetch_assoc();
    $evaluaciones_pendientes = $row['total'] ?? 0;
    $stmt->close();
}

// ========================================== */
// 4. CONTAR ESTUDIANTES TOTAL               */
// ========================================== */
$estudiantes_total = 0;
$stmt = $conexion->prepare("
    SELECT COUNT(DISTINCT i.id_alumno) AS total 
    FROM inscripciones i
    INNER JOIN cursos c ON i.id_curso = c.id_curso
    WHERE c.id_docente = ?
");
if ($stmt) {
    $stmt->bind_param("i", $id_docente);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $row = $resultado->fetch_assoc();
    $estudiantes_total = $row['total'] ?? 0;
    $stmt->close();
}

// ========================================== */
// 5. CONTENIDO RECIENTE                     */
// ========================================== */
$contenido_reciente = [];
$stmt = $conexion->prepare("
    SELECT 
        titulo,
        tipo,
        fecha_publicacion,
        estado
    FROM actividades
    WHERE id_docente = ?
    ORDER BY fecha_publicacion DESC
    LIMIT 5
");
if ($stmt) {
    $stmt->bind_param("i", $id_docente);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $contenido_reciente = $resultado->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// ========================================== */
// 6. PRÓXIMAS ACTIVIDADES                   */
// ========================================== */
$proximas_actividades = [];
$stmt = $conexion->prepare("
    SELECT 
        titulo,
        fecha_limite,
        tipo
    FROM actividades
    WHERE id_docente = ? AND fecha_limite >= NOW() AND estado != 'Cerrada'
    ORDER BY fecha_limite ASC
    LIMIT 3
");
if ($stmt) {
    $stmt->bind_param("i", $id_docente);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $proximas_actividades = $resultado->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// ========================================== */
// FUNCIONES AUXILIARES                      */
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

function formatearFecha($fecha) {
    if (!$fecha) return 'Sin fecha';
    $dias = round((time() - strtotime($fecha)) / 86400);
    if ($dias == 0) return 'Publicado hoy';
    if ($dias == 1) return 'Publicado ayer';
    if ($dias < 7) return "Hace $dias días";
    return date('d M, Y', strtotime($fecha));
}

$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- NUEVA ACCESIBILIDAD -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
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
            <a href="mis_recursos.php" class="menu-item"><i class="fa-solid fa-folder-open"></i> Mis Recursos</a>
            <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
            <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluación</a>
            <a href="crear_juego.php" class="menu-item"><i class="fa-solid fa-gamepad"></i> Crear Juego</a>
            <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
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
        <header class="content-header">
            <div class="welcome-text">
                <h1>¡Hola Prof. <?php echo htmlspecialchars($nombre_docente); ?>! 👋</h1>
                <p>Bienvenido a tu espacio docente.</p>
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
                        <a href="crear_recurso.php" class="quick-btn bg-purple-solid">
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

<!-- NUEVA ACCESIBILIDAD -->
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>

<!-- ========================================== -->
<!-- SCRIPTS                                    -->
<!-- ========================================== -->
<script src="jss/docente_dashboard.js"></script>
</body>
</html>