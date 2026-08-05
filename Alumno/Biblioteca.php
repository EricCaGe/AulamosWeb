<?php
session_start();
require_once '../Conexion/conexion.php';

// Si no hay sesión, usa un ID por defecto (para pruebas)
$id_usuario = $_SESSION['id_usuario'] ?? 1;

// =============================================
// 1. OBTENER MATERIAS (CAMPOS FORMATIVOS) PARA FILTROS
// =============================================
$sqlMaterias = "SELECT DISTINCT campo_formativo FROM materias WHERE estado = 'Activa' ORDER BY campo_formativo";
$resultMaterias = $conexion->query($sqlMaterias);
$materias = [];
while ($row = $resultMaterias->fetch_assoc()) {
    $materias[] = $row['campo_formativo'];
}

// =============================================
// 2. OBTENER RECURSOS EDUCATIVOS
// =============================================
$filtroMateria = isset($_GET['materia']) ? $_GET['materia'] : 'todas';

$sqlRecursos = "
    SELECT 
        r.id_recurso,
        r.titulo,
        m.nombre AS materia,
        r.tipo
    FROM recursos_educativos r
    JOIN materias m ON r.id_materia = m.id_materia
    WHERE r.estado = 'Activo' AND r.accesible = 1
";
$params = [];
if ($filtroMateria !== 'todas') {
    $sqlRecursos .= " AND m.campo_formativo = ?";
    $params[] = $filtroMateria;
}
$sqlRecursos .= " ORDER BY r.titulo ASC";

$stmt = $conexion->prepare($sqlRecursos);
if ($filtroMateria !== 'todas') {
    $stmt->bind_param("s", $filtroMateria);
}
$stmt->execute();
$resultRecursos = $stmt->get_result();
$recursos = $resultRecursos->fetch_all(MYSQLI_ASSOC);

// Mapeo de tipos a iconos y etiquetas
$tiposMap = [
    'video' => ['icono' => 'fa-solid fa-video', 'label' => 'Video'],
    'pdf' => ['icono' => 'fa-solid fa-file-pdf', 'label' => 'PDF'],
    'imagen' => ['icono' => 'fa-solid fa-image', 'label' => 'Imagen'],
    'audio' => ['icono' => 'fa-solid fa-music', 'label' => 'Audio'],
    'enlace' => ['icono' => 'fa-solid fa-link', 'label' => 'Enlace'],
    'presentación' => ['icono' => 'fa-solid fa-presentation-screen', 'label' => 'Presentación'],
    'documento' => ['icono' => 'fa-solid fa-file-lines', 'label' => 'Documento']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca Digital - Aulamos</title>
    
    <link rel="stylesheet" href="Style/Inicio.css">
    <link rel="stylesheet" href="Style/Biblioteca.css">
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
            <a href="biblioteca.php" class="menu-item active"><i class="fa-solid fa-book-open"></i> Biblioteca digital</a>
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
                <h1>Biblioteca Digital</h1>
                <p>Explora y accede a todos tus recursos educativos</p>
            </div>
            <div class="header-actions">
                <button class="btn-assistant" id="btnAsistente">Asistente Virtual <span class="robot-icon">🤖</span></button>
                <div class="icon-bell"><i class="fa-regular fa-bell"></i></div>
                <img src="https://placehold.co/40x40/ff7675/white?text=👩" alt="Avatar" class="avatar">
            </div>
        </header>

        <!-- FILTROS POR MATERIA -->
        <div class="filtros-materia">
            <a href="?materia=todas" class="<?= $filtroMateria === 'todas' ? 'activo' : '' ?>">Todas</a>
            <?php foreach ($materias as $materia): ?>
                <a href="?materia=<?= urlencode($materia) ?>" class="<?= $filtroMateria === $materia ? 'activo' : '' ?>">
                    <?= htmlspecialchars($materia) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- LISTA DE RECURSOS -->
        <?php if (count($recursos) > 0): ?>
            <div class="grid-recursos" id="gridRecursos">
                <?php foreach ($recursos as $recurso): ?>
                    <?php 
                        $tipo = strtolower($recurso['tipo']);
                        $icono = $tiposMap[$tipo]['icono'] ?? 'fa-solid fa-file';
                        $label = $tiposMap[$tipo]['label'] ?? ucfirst($tipo);
                    ?>
                    <div class="recurso-card" data-materia="<?= htmlspecialchars($recurso['materia']) ?>">
                        <div class="recurso-icono"><i class="<?= $icono ?>"></i></div>
                        <div class="recurso-titulo"><?= htmlspecialchars($recurso['titulo']) ?></div>
                        <div class="recurso-materia"><?= htmlspecialchars($recurso['materia']) ?></div>
                        <span class="recurso-tipo"><?= htmlspecialchars($label) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align:center; padding:20px; color:#64748b;">No hay recursos disponibles para esta materia.</p>
        <?php endif; ?>

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

<script src="js/Inicio.js"></script>
<script src="js/Biblioteca.js"></script>
<script src="../Administrador/js/lector.js"></script>
</body>
</html>