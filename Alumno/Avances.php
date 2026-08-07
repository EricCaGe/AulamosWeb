<?php
session_start();
require_once '../Conexion/conexion.php';

// Si no hay sesión, usa un ID por defecto (para pruebas)
$id_usuario = $_SESSION['id_usuario'] ?? 1;

// =============================================
// 1. OBTENER MATERIAS DEL USUARIO (desde inscripciones)
// =============================================
$sqlMaterias = "
    SELECT DISTINCT m.campo_formativo as materia
    FROM inscripciones i
    JOIN cursos c ON i.id_curso = c.id_curso
    JOIN materias m ON c.id_materia = m.id_materia
    WHERE i.id_alumno = ? AND i.estado = 'Activo'
    ORDER BY m.campo_formativo
";
$stmt = $conexion->prepare($sqlMaterias);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultMaterias = $stmt->get_result();
$materias = [];
while ($row = $resultMaterias->fetch_assoc()) {
    $materias[] = $row['materia'];
}

// Si no tiene materias, mostrar un mensaje
if (empty($materias)) {
    $materias = ['Sin materias asignadas'];
}

// Materia actual (desde GET)
$materia_actual = isset($_GET['materia']) ? $_GET['materia'] : $materias[0];

// =============================================
// 2. ESTADÍSTICAS GENERALES (para todas las materias o la seleccionada)
// =============================================

// 2a. Racha de estudio (días consecutivos con actividad)
// Para simplificar, contamos días con actividad en los últimos 30 días
$sqlRacha = "
    SELECT COUNT(DISTINCT DATE(fecha_hora)) as dias_activos
    FROM eventos_investigacion
    WHERE id_usuario = ? 
        AND DATE(fecha_hora) >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
";
$stmt = $conexion->prepare($sqlRacha);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultRacha = $stmt->get_result();
$racha = $resultRacha->fetch_assoc()['dias_activos'] ?? 0;

// 2b. Actividades completadas (total)
$sqlCompletadas = "
    SELECT COUNT(*) as completadas
    FROM actividad_estudiantes
    WHERE id_alumno = ? AND estado IN ('Completada', 'Calificada')
";
$stmt = $conexion->prepare($sqlCompletadas);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultCompletadas = $stmt->get_result();
$completadas = $resultCompletadas->fetch_assoc()['completadas'] ?? 0;

// 2c. Lector activo (actividades vistas y pendientes)
// Vistas: actividades donde el usuario ha accedido (ultimo_acceso no nulo)
// Pendientes: total de actividades - vistas (simplificado)
$sqlVistas = "
    SELECT COUNT(DISTINCT id_actividad) as vistas
    FROM actividad_estudiantes
    WHERE id_alumno = ? AND ultimo_acceso IS NOT NULL
";
$stmt = $conexion->prepare($sqlVistas);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultVistas = $stmt->get_result();
$vistas = $resultVistas->fetch_assoc()['vistas'] ?? 0;

$sqlTotalActividades = "
    SELECT COUNT(*) as total
    FROM actividad_estudiantes
    WHERE id_alumno = ?
";
$stmt = $conexion->prepare($sqlTotalActividades);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultTotal = $stmt->get_result();
$totalActividades = $resultTotal->fetch_assoc()['total'] ?? 0;
$pendientes = $totalActividades - $vistas;

// 2d. Porcentaje general (completadas / total)
$porcentaje = $totalActividades > 0 ? round(($completadas / $totalActividades) * 100) : 0;
$mensaje = $porcentaje >= 70 ? '¡Sigue así, cada paso cuenta!' : '¡Cada esfuerzo te acerca a la meta!';

// =============================================
// 3. ESTADÍSTICAS POR MATERIA (si se selecciona una específica)
// =============================================
$statsMateria = [];
if ($materia_actual !== 'Sin materias asignadas' && $materia_actual !== $materias[0]) {
    // Obtener id_materia a partir del campo_formativo
    $sqlIdMateria = "SELECT id_materia FROM materias WHERE campo_formativo = ?";
    $stmt = $conexion->prepare($sqlIdMateria);
    $stmt->bind_param("s", $materia_actual);
    $stmt->execute();
    $resultId = $stmt->get_result();
    $idMateria = $resultId->fetch_assoc()['id_materia'] ?? null;

    if ($idMateria) {
        // Completadas en esta materia
        $sqlCompMateria = "
            SELECT COUNT(*) as comp
            FROM actividad_estudiantes ae
            JOIN actividades a ON ae.id_actividad = a.id_actividad
            JOIN cursos c ON a.id_curso = c.id_curso
            WHERE ae.id_alumno = ? AND c.id_materia = ? AND ae.estado IN ('Completada', 'Calificada')
        ";
        $stmt = $conexion->prepare($sqlCompMateria);
        $stmt->bind_param("ii", $id_usuario, $idMateria);
        $stmt->execute();
        $resultComp = $stmt->get_result();
        $compMateria = $resultComp->fetch_assoc()['comp'] ?? 0;

        // Total en esta materia
        $sqlTotalMateria = "
            SELECT COUNT(*) as total
            FROM actividad_estudiantes ae
            JOIN actividades a ON ae.id_actividad = a.id_actividad
            JOIN cursos c ON a.id_curso = c.id_curso
            WHERE ae.id_alumno = ? AND c.id_materia = ?
        ";
        $stmt = $conexion->prepare($sqlTotalMateria);
        $stmt->bind_param("ii", $id_usuario, $idMateria);
        $stmt->execute();
        $resultTotalMateria = $stmt->get_result();
        $totalMateria = $resultTotalMateria->fetch_assoc()['total'] ?? 0;

        $porcentajeMateria = $totalMateria > 0 ? round(($compMateria / $totalMateria) * 100) : 0;
        $statsMateria = [
            'completadas' => $compMateria,
            'total' => $totalMateria,
            'porcentaje' => $porcentajeMateria,
            'mensaje' => $porcentajeMateria >= 70 ? '¡Buen progreso!' : 'Sigue practicando'
        ];
    }
}

// Si no hay estadísticas por materia, usar las generales
if (empty($statsMateria)) {
    $statsMateria = [
        'completadas' => $completadas,
        'total' => $totalActividades,
        'porcentaje' => $porcentaje,
        'mensaje' => $mensaje
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis avances - Aulamos</title>
    
    <link rel="stylesheet" href="Style/Inicio.css">
    <link rel="stylesheet" href="Style/Avances.css">
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
            <a href="alumno.php" class="menu-item"><i class="fa-solid fa-house"></i> Inicio</a>
            <a href="actividades.php" class="menu-item"><i class="fa-solid fa-cubes"></i> Mis actividades</a>
            <a href="biblioteca.php" class="menu-item"><i class="fa-solid fa-book-open"></i> Biblioteca digital</a>
            <a href="avances.php" class="menu-item active"><i class="fa-solid fa-pen-to-square"></i> Mis avances</a>
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
                <h1>Mis avances</h1>
                <p>Revisa tu progreso en cada materia</p>
            </div>
            <div class="header-actions">
                <button class="btn-assistant" id="btnAsistente">Asistente Virtual <span class="robot-icon">🤖</span></button>
                <div class="icon-bell"><i class="fa-regular fa-bell"></i></div>
                <img src="https://placehold.co/40x40/ff7675/white?text=👩" alt="Avatar" class="avatar">
            </div>
        </header>

        <!-- MENÚ DE MATERIAS (tabs) -->
        <div class="materias-tabs">
            <?php foreach ($materias as $materia): ?>
                <a href="?materia=<?= urlencode($materia) ?>" 
                   class="<?= $materia_actual === $materia ? 'activo' : '' ?>">
                    <?= htmlspecialchars($materia) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- TARJETAS DE ESTADÍSTICAS -->
        <div class="stats-grid-avances">
            <div class="stat-card racha">
                <div class="stat-icon"><i class="fa-solid fa-fire"></i></div>
                <div class="stat-number"><?= $racha ?></div>
                <div class="stat-label">Racha de <?= $racha ?> días</div>
                <div class="stat-sub">Excelente</div>
            </div>
            <div class="stat-card completadas">
                <div class="stat-icon"><i class="fa-regular fa-circle-check"></i></div>
                <div class="stat-number"><?= $statsMateria['completadas'] ?></div>
                <div class="stat-label">Actividades</div>
                <div class="stat-sub">Completadas</div>
            </div>
            <div class="stat-card lector">
                <div class="stat-icon"><i class="fa-regular fa-bookmark"></i></div>
                <div class="stat-number"><?= $vistas ?> - <?= $pendientes ?></div>
                <div class="stat-label">Lector activo</div>
                <div class="stat-sub"><?= $vistas ?> vistas, <?= $pendientes ?> pendientes</div>
            </div>
        </div>

        <!-- RESUMEN GENERAL (porcentaje) -->
        <div class="resumen-general">
            <div class="porcentaje-circular">
                <svg viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="54" fill="none" stroke="#e2e8f0" stroke-width="12" />
                    <circle cx="60" cy="60" r="54" fill="none" stroke="#3b82f6" stroke-width="12" 
                            stroke-dasharray="<?= 2 * pi() * 54 * ($statsMateria['porcentaje'] / 100) ?> <?= 2 * pi() * 54 * (1 - $statsMateria['porcentaje'] / 100) ?>" 
                            stroke-linecap="round" transform="rotate(-90 60 60)" />
                </svg>
                <div class="porcentaje-texto">
                    <span class="numero"><?= $statsMateria['porcentaje'] ?>%</span>
                    <span class="mensaje"><?= $statsMateria['mensaje'] ?></span>
                </div>
            </div>
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
               <button class="acc-opt-btn"><i class="fa-solid fa-volume-high"></i><span>Leer pantalla</span></button>
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
<script src="js/Inicio.js"></script>
<script src="js/Avances.js"></script>
</body>
</html>