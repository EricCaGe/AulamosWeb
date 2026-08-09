<?php
session_start();
require_once '../Conexion/conexion.php'; // Asegúrate de que la ruta sea correcta

// Si no hay sesión, redirige o usa un ID por defecto (para pruebas)
// Si no hay sesión, redirige o usa un ID por defecto (para pruebas)
$id_usuario = $_SESSION['usuario']['id_usuario'] ?? 1; // Usamos la estructura correcta de la sesión

// Consulta para obtener las actividades del alumno
$sql = "
    SELECT 
        a.id_actividad,
        a.titulo,
        m.nombre AS asignatura,
        a.fecha_limite AS vencimiento,
        ae.estado,
        -- Calcular estado real (Atrasada si está Pendiente y fecha límite pasó)
        CASE 
            WHEN ae.estado = 'Pendiente' AND a.fecha_limite < NOW() THEN 'atrasada'
            ELSE LOWER(ae.estado)
        END AS estado_mostrar
    FROM actividad_estudiantes ae
    JOIN actividades a ON ae.id_actividad = a.id_actividad
    JOIN cursos c ON a.id_curso = c.id_curso
    JOIN materias m ON c.id_materia = m.id_materia
    WHERE ae.id_alumno = ?
    ORDER BY a.fecha_limite ASC
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$actividades = $resultado->fetch_all(MYSQLI_ASSOC);

// Filtro desde GET
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';
$actividadesFiltradas = array_filter($actividades, function($act) use ($filtro) {
    if ($filtro === 'todas') return true;
    if ($filtro === 'pendiente') return in_array($act['estado_mostrar'], ['pendiente', 'atrasada']);
    if ($filtro === 'proceso') return $act['estado_mostrar'] === 'en_proceso';
    if ($filtro === 'completada') return in_array($act['estado_mostrar'], ['completada', 'calificada']);
    return true;
});

// Mapeo de estados a texto legible
$estados_texto = [
    'pendiente'   => 'Pendiente',
    'atrasada'    => 'Atrasada',
    'en_proceso'  => 'En Proceso',
    'completada'  => 'Completada',
    'calificada'  => 'Calificada'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis actividades - Aulamos</title>
    
    <!-- Estilos base y específicos -->
    <link rel="stylesheet" href="Style/Inicio.css">
    <link rel="stylesheet" href="Style/actividades.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="dashboard-container">
    
    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <div class="logo-section">
           <img src="../img/logogeneral.png" alt="Logo AulamosWeb" class="logo">
        </div>
        <nav class="menu">
            <a href="alumno.php" class="menu-item"><i class="fa-solid fa-cubes"></i> Inicio</a>
            <a href="actividades.php" class="menu-item active"><i class="fa-solid fa-cubes"></i> Mis actividades</a>
            <a href="biblioteca.php" class="menu-item"><i class="fa-solid fa-book-open"></i> Biblioteca digital</a>
            <a href="avances.php" class="menu-item"><i class="fa-solid fa-pen-to-square"></i> Mis avances</a>
            <a href="ayuda.php" class="menu-item"><i class="fa-solid fa-circle-question"></i> Ayuda</a>
            <a href="accesibilidad.php" class="menu-item"><i class="fa-solid fa-gear"></i> Accesibilidad</a>
        </nav>
        <button class="btn-accessibility-main"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
        <div class="menu-spacer"></div>
    <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        
        <header class="content-header">
            <div class="welcome-text">
                <h1>Mis actividades</h1>
                <p>Aquí están tus tareas y actividades asignadas</p>
            </div>
            <div class="header-actions">
                <button class="btn-assistant" id="btnAsistente">Asistente Virtual <span class="robot-icon">🤖</span></button>
                <div class="icon-bell"><i class="fa-regular fa-bell"></i></div>
                <img src="https://placehold.co/40x40/ff7675/white?text=👩" alt="Avatar" class="avatar">
            </div>
        </header>

        <!-- FILTROS -->
        <div class="filtros" id="filtros">
            <button data-filtro="todas" class="<?= $filtro === 'todas' ? 'activo' : '' ?>">Todas</button>
            <button data-filtro="pendiente" class="<?= $filtro === 'pendiente' ? 'activo' : '' ?>">Pendientes</button>
            <button data-filtro="proceso" class="<?= $filtro === 'proceso' ? 'activo' : '' ?>">En Proceso</button>
            <button data-filtro="completada" class="<?= $filtro === 'completada' ? 'activo' : '' ?>">Completadas</button>
        </div>

        <!-- LISTA DE ACTIVIDADES -->
        <div class="lista-actividades" id="listaActividades">
            <?php if (count($actividadesFiltradas) > 0): ?>
                <?php foreach ($actividadesFiltradas as $act): ?>
                    <div class="card-actividad" data-estado="<?= $act['estado_mostrar'] ?>">
                        <div class="card-info">
                            <div class="card-titulo"><?= htmlspecialchars($act['titulo']) ?></div>
                            <div class="card-asignatura"><?= htmlspecialchars($act['asignatura']) ?></div>
                            <div class="card-fecha">Vence: <?= htmlspecialchars(date('d M, Y', strtotime($act['vencimiento']))) ?></div>
                        </div>
                        <div class="card-acciones">
                            <span class="estado-badge <?= $act['estado_mostrar'] ?>">
                                <?= $estados_texto[$act['estado_mostrar']] ?? $act['estado_mostrar'] ?>
                            </span>
                            <?php if (!in_array($act['estado_mostrar'], ['completada', 'calificada'])): ?>
                                <button class="btn-ext" data-id="<?= $act['id_actividad'] ?>">Solicitar extensión</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; padding:20px; color:#64748b;">No hay actividades en este estado.</p>
            <?php endif; ?>
        </div>

        <!-- ACCESIBILIDAD -->
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
                <button class="acc-opt-btn" id="btn-leer"><i class="fa-solid fa-volume-high"></i><span>Leer pantalla</span></button>
                <button class="acc-opt-btn" id="btn-subtitulos"><i class="fa-solid fa-closed-captioning"></i><span>Subtítulos</span></button>
                <button class="acc-opt-btn" id="btn-navegacion"><i class="fa-solid fa-keyboard"></i><span>Navegación</span></button>
            </div>
            <button class="btn-open-config" id="btn-config">Abrir configuración</button>
        </footer>

    </main>
</div>

<?php include '../API/teclado_accesibilidad.php'; ?>
<script src="js/navegacionTeclado.js"></script>
<script src="js/Accesibilidad.js"></script>
<script src="../Administrador/js/lector.js"></script>
<script src="js/actividades.js"></script>
<script src="js/Inicio.js"></script>

</body>
</html>