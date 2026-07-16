 <?php
// Datos de ejemplo para la biblioteca (pueden venir de BD)
$recursos = [
    [
        'id' => 1,
        'titulo' => 'El sistema solar',
        'materia' => 'Saberes y Pensamiento Científico',
        'tipo' => 'video',
        'icono' => 'fa-solid fa-video'
    ],
    [
        'id' => 2,
        'titulo' => 'Fracciones',
        'materia' => 'Saberes y Pensamiento Científico',
        'tipo' => 'pdf',
        'icono' => 'fa-solid fa-file-pdf'
    ],
    [
        'id' => 3,
        'titulo' => 'Ética, Naturaleza y Sociedades',
        'materia' => 'Ética, Naturaleza y Sociedades',
        'tipo' => 'video',
        'icono' => 'fa-solid fa-video'
    ],
    [
        'id' => 4,
        'titulo' => 'Ortografía',
        'materia' => 'Lenguajes',
        'tipo' => 'documento',
        'icono' => 'fa-solid fa-file-lines'
    ],
    [
        'id' => 5,
        'titulo' => 'De lo Humano y lo Comunitario',
        'materia' => 'De lo Humano y lo Comunitario',
        'tipo' => 'documento',
        'icono' => 'fa-solid fa-file-lines'
    ]
];

// Obtener materias únicas para los filtros
$materias = array_unique(array_column($recursos, 'materia'));
sort($materias);

// Filtro por materia (desde GET)
$filtroMateria = isset($_GET['materia']) ? $_GET['materia'] : 'todas';
if ($filtroMateria !== 'todas') {
    $recursosFiltrados = array_filter($recursos, function($r) use ($filtroMateria) {
        return $r['materia'] === $filtroMateria;
    });
} else {
    $recursosFiltrados = $recursos;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca Digital - Aulamos</title>
    
    <!-- CSS base (el mismo que usas en Inicio) -->
    <link rel="stylesheet" href="Style/Inicio.css">
    <!-- CSS específico para biblioteca -->
    <link rel="stylesheet" href="Style/Biblioteca.css">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="dashboard-container">
    
    <!-- BARRA LATERAL (idéntica) -->
    <aside class="sidebar">
        <div class="logo-section">
            <img src="https://placehold.co/50x50/3b71f3/white?text=🦉" alt="Búho Aulamos" class="logo-img">
            <div>
                <h2>AULAMOS</h2>
                <p>Aprendemos juntos</p>
            </div>
        </div>
        
        <nav class="menu">
            <a href="alumno.php" class="menu-item"><i class="fa-solid fa-house"></i> Inicio</a>
            <a href="actividades.php" class="menu-item"><i class="fa-solid fa-cubes"></i> Mis actividades</a>
            <a href="biblioteca.php" class="menu-item active"><i class="fa-solid fa-book-open"></i> Biblioteca digital</a>
           <a href="avances.php" class="menu-item"><i class="fa-solid fa-pen-to-square"></i> Mis avances</a>
            <a href="#" class="menu-item"><i class="fa-solid fa-circle-question"></i> Ayuda</a>
            <a href="#" class="menu-item"><i class="fa-solid fa-gear"></i> Configuración accesible</a>
        </nav>
        
        <button class="btn-accessibility-main"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
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
        <?php if (count($recursosFiltrados) > 0): ?>
            <div class="grid-recursos" id="gridRecursos">
                <?php foreach ($recursosFiltrados as $recurso): ?>
                    <div class="recurso-card" data-materia="<?= htmlspecialchars($recurso['materia']) ?>">
                        <div class="recurso-icono"><i class="<?= $recurso['icono'] ?>"></i></div>
                        <div class="recurso-titulo"><?= htmlspecialchars($recurso['titulo']) ?></div>
                        <div class="recurso-materia"><?= htmlspecialchars($recurso['materia']) ?></div>
                        <span class="recurso-tipo">
                            <?php 
                                $tipos = ['video' => 'Video', 'pdf' => 'PDF', 'documento' => 'Documento'];
                                echo $tipos[$recurso['tipo']] ?? $recurso['tipo'];
                            ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align:center; padding:20px; color:#64748b;">No hay recursos disponibles para esta materia.</p>
        <?php endif; ?>

        <!-- ACCESIBILIDAD (igual que en Inicio) -->
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

<!-- JS base (Inicio.js) y específico para biblioteca -->
<script src="js/Inicio.js"></script>
<script src="js/Biblioteca.js"></script>
</body>
</html>