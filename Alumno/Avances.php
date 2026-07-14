<?php
// Datos de ejemplo para el progreso (pueden venir de BD)
$materias = [
    'Resumen general',
    'Ciencias naturales',
    'Matemáticas',
    'Lenguas',
    'Ciencias sociales',
    'Arte'
];

// Datos de estadísticas (cambian según la materia seleccionada)
$stats = [
    'racha' => 5,
    'completadas' => 10,
    'lector' => ['vistas' => 10, 'pendientes' => 4],
    'porcentaje' => 72,
    'mensaje' => 'Sigue así, cada paso cuenta'
];

// Filtro por materia (desde GET)
$materia_actual = isset($_GET['materia']) ? $_GET['materia'] : 'Resumen general';
// (Simulación: podrías cargar datos distintos según la materia)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis avances - Aulamos</title>
    
    <!-- CSS base (Inicio.css) -->
    <link rel="stylesheet" href="Style/Inicio.css">
    <!-- CSS específico para avances -->
    <link rel="stylesheet" href="Style/Avances.css">
    
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
            <a href="biblioteca.php" class="menu-item"><i class="fa-solid fa-book-open"></i> Biblioteca digital</a>
            <a href="avances.php" class="menu-item active"><i class="fa-solid fa-pen-to-square"></i> Mis avances</a>
            <a href="#" class="menu-item"><i class="fa-solid fa-circle-question"></i> Ayuda</a>
            <a href="#" class="menu-item"><i class="fa-solid fa-gear"></i> Configuración accesible</a>
        </nav>
        
        <button class="btn-accessibility-main"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
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
                <div class="stat-number"><?= $stats['racha'] ?></div>
                <div class="stat-label">Racha de <?= $stats['racha'] ?> días</div>
                <div class="stat-sub">Excelente</div>
            </div>
            <div class="stat-card completadas">
                <div class="stat-icon"><i class="fa-regular fa-circle-check"></i></div>
                <div class="stat-number"><?= $stats['completadas'] ?></div>
                <div class="stat-label">Actividades</div>
                <div class="stat-sub">Completadas</div>
            </div>
            <div class="stat-card lector">
                <div class="stat-icon"><i class="fa-regular fa-bookmark"></i></div>
                <div class="stat-number"><?= $stats['lector']['vistas'] ?> - <?= $stats['lector']['pendientes'] ?></div>
                <div class="stat-label">Lector activo</div>
                <div class="stat-sub"><?= $stats['lector']['vistas'] ?> vistas, <?= $stats['lector']['pendientes'] ?> pendientes</div>
            </div>
        </div>

        <!-- RESUMEN GENERAL (porcentaje) -->
        <div class="resumen-general">
            <div class="porcentaje-circular">
                <svg viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="54" fill="none" stroke="#e2e8f0" stroke-width="12" />
                    <circle cx="60" cy="60" r="54" fill="none" stroke="#3b82f6" stroke-width="12" 
                            stroke-dasharray="<?= 2 * pi() * 54 * ($stats['porcentaje'] / 100) ?> <?= 2 * pi() * 54 * (1 - $stats['porcentaje'] / 100) ?>" 
                            stroke-linecap="round" transform="rotate(-90 60 60)" />
                </svg>
                <div class="porcentaje-texto">
                    <span class="numero"><?= $stats['porcentaje'] ?>%</span>
                    <span class="mensaje"><?= $stats['mensaje'] ?></span>
                </div>
            </div>
        </div>

        <!-- ACCESIBILIDAD (igual que en las otras páginas) -->
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

<!-- JS base (Inicio.js) y específico para avances -->
<script src="js/Inicio.js"></script>
<script src="js/Avances.js"></script>
</body>
</html>