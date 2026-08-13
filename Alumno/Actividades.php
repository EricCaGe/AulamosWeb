<?php
session_start();

// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

require_once '../Conexion/conexion.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../InicioSesion/login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id_usuario'];

// Consulta para obtener las actividades del alumno (INCLUYENDO EVALUACIONES)
$sql = "
    SELECT 
        a.id_actividad,
        a.titulo,
        a.descripcion,
        m.nombre AS asignatura,
        a.fecha_limite AS vencimiento,
        a.tipo,
        a.configuracion_evaluacion,
        a.puntaje_maximo,
        ae.id_actividad_estudiante,
        ae.estado,
        ae.porcentaje_avance,
        CASE 
            WHEN ae.estado = 'Pendiente' AND a.fecha_limite < NOW() THEN 'atrasada'
            ELSE LOWER(ae.estado)
        END AS estado_mostrar,
        (SELECT COUNT(*) FROM entregas e 
         WHERE e.id_actividad_estudiante = ae.id_actividad_estudiante) AS tiene_entrega
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
$stmt->close();

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

// Función para obtener el badge de estado
function getEstadoBadgeClass($estado) {
    switch ($estado) {
        case 'pendiente': return 'estado-pendiente';
        case 'atrasada': return 'estado-atrasada';
        case 'en_proceso': return 'estado-proceso';
        case 'completada': return 'estado-completada';
        case 'calificada': return 'estado-calificada';
        default: return '';
    }
}

$conexion->close();
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
    
    <!-- NUEVA ACCESIBILIDAD -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
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
            <a href="mas.php" class="menu-item"> <i class="fa-solid fa-bars"></i> Más</a>
        </nav>
        <button class="btn-accessibility-main" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
        <div class="menu-spacer"></div>
        <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        
        <header class="content-header">
            <div class="welcome-text">
                <h1>Mis actividades</h1>
                <p>Aquí están tus tareas, evaluaciones y actividades asignadas</p>
            </div>
            <div class="header-actions">
                <button class="btn-assistant" id="btnAsistente" onclick="window.location.href='../Alumno/ChatbotAlumno.php?rol=alumno'">
                    Asistente Virtual <span class="robot-icon">🤖</span>
                </button>
                <div class="icon-bell"><i class="fa-regular fa-bell"></i></div>
                <img src="https://placehold.co/40x40/ff7675/white?text=👩" alt="Avatar" class="avatar">
            </div>
        </header>

        <!-- ========================================== -->
        <!-- MENSAJES DE ÉXITO O ERROR                  -->
        <!-- ========================================== -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensaje'] ?? 'success'; ?>">
                <i class="fa-solid <?php 
                    $tipo = $_SESSION['tipo_mensaje'] ?? 'success';
                    if ($tipo === 'success') echo 'fa-check-circle';
                    elseif ($tipo === 'error') echo 'fa-exclamation-circle';
                    else echo 'fa-info-circle';
                ?>"></i>
                <?php echo $_SESSION['mensaje']; ?>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
            <?php unset($_SESSION['tipo_mensaje']); ?>
        <?php endif; ?>

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
                            <div class="card-titulo">
                                <?= htmlspecialchars($act['titulo']) ?>
                                <?php if ($act['tipo'] === 'Evaluacion'): ?>
                                    <span class="badge-evaluacion">
                                        <i class="fa-regular fa-file-lines"></i> Evaluación
                                    </span>
                                <?php else: ?>
                                    <span class="badge-tarea">
                                        <i class="fa-regular fa-clipboard"></i> <?= $act['tipo'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-asignatura">
                                <i class="fa-regular fa-bookmark"></i>
                                <?= htmlspecialchars($act['asignatura']) ?>
                            </div>
                            <div class="card-fecha">
                                <i class="fa-regular fa-calendar"></i>
                                Vence: <?= htmlspecialchars(date('d M, Y H:i', strtotime($act['vencimiento']))) ?>
                                <?php if ($act['tipo'] === 'Evaluacion'): ?>
                                    <span style="color: #4f7cff; font-size: 12px; margin-left: 8px;">
                                        <i class="fa-regular fa-star"></i> <?= $act['puntaje_maximo'] ?> pts
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($act['tipo'] !== 'Evaluacion' && $act['porcentaje_avance'] > 0): ?>
                                <div class="porcentaje-bar">
                                    <div class="fill <?= $act['porcentaje_avance'] == 100 ? 'completado' : '' ?>" 
                                         style="width: <?= $act['porcentaje_avance'] ?>%"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-acciones">
                            <span class="estado-badge <?= getEstadoBadgeClass($act['estado_mostrar']) ?>">
                                <?= $estados_texto[$act['estado_mostrar']] ?? $act['estado_mostrar'] ?>
                            </span>
                            <div class="card-actions-group">
                                <?php if ($act['tipo'] === 'Evaluacion'): ?>
                                    <?php if ($act['estado_mostrar'] === 'calificada' || $act['estado_mostrar'] === 'completada'): ?>
                                        <a href="ver_resultado_evaluacion.php?id=<?= $act['id_actividad'] ?>" 
                                           class="btn-ver-resultado">
                                            <i class="fa-solid fa-chart-simple"></i> Ver resultado
                                        </a>
                                    <?php elseif ($act['estado_mostrar'] === 'pendiente' || $act['estado_mostrar'] === 'en_proceso'): ?>
                                        <a href="realizar_evaluacion.php?id=<?= $act['id_actividad'] ?>" 
                                           class="btn-realizar">
                                            <i class="fa-solid fa-pencil"></i> Realizar evaluación
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (!in_array($act['estado_mostrar'], ['completada', 'calificada'])): ?>
                                        <button class="btn-ext" data-id="<?= $act['id_actividad'] ?>">
                                            <i class="fa-regular fa-clock"></i> Solicitar extensión
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icono">📭</div>
                    <h3>No hay actividades en este estado</h3>
                    <p>Cuando tengas actividades asignadas, aparecerán aquí.</p>
                </div>
            <?php endif; ?>
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
<script src="js/actividades.js"></script>
<script src="js/Inicio.js"></script>

<!-- NUEVA ACCESIBILIDAD -->
<script src="../Accesibilidad/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtros
    const filtros = document.querySelectorAll('.filtros button');
    filtros.forEach(btn => {
        btn.addEventListener('click', function() {
            const filtro = this.dataset.filtro;
            window.location.href = `actividades.php?filtro=${filtro}`;
        });
    });

    // Botones de solicitud de extensión
    document.querySelectorAll('.btn-ext').forEach(btn => {
        btn.addEventListener('click', function() {
            const idActividad = this.dataset.id;
            if (confirm('¿Deseas solicitar una extensión de plazo para esta actividad?')) {
                alert('Solicitud de extensión enviada al docente.');
            }
        });
    });
});
</script>

</body>
</html>