<?php
// Datos de ejemplo para las actividades (pueden venir de BD)
$actividades = [
    [
        'id' => 1,
        'titulo' => 'Lectura: El medio ambiente',
        'asignatura' => 'Ciencias Naturales',
        'vencimiento' => '23 may, 2024',
        'estado' => 'pendiente'
    ],
    [
        'id' => 2,
        'titulo' => 'Cuestionario: Ecosistema',
        'asignatura' => 'Ciencias Naturales',
        'vencimiento' => '23 may, 2024',
        'estado' => 'pendiente'
    ],
    [
        'id' => 3,
        'titulo' => 'Mapa conceptual: La célula',
        'asignatura' => 'Ciencias Naturales',
        'vencimiento' => '23 mar, 2024',
        'estado' => 'pendiente'
    ],
    // Ejemplos extra para mostrar otros estados
    [
        'id' => 4,
        'titulo' => 'Práctica: Fotosíntesis',
        'asignatura' => 'Ciencias Naturales',
        'vencimiento' => '15 abr, 2024',
        'estado' => 'proceso'
    ],
    [
        'id' => 5,
        'titulo' => 'Examen: Biología celular',
        'asignatura' => 'Ciencias Naturales',
        'vencimiento' => '10 mar, 2024',
        'estado' => 'completada'
    ]
];

// Función para filtrar
function filtrarActividades($estado) {
    global $actividades;
    if ($estado === 'todas') return $actividades;
    return array_filter($actividades, function($a) use ($estado) {
        return $a['estado'] === $estado;
    });
}

$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';
$actividadesFiltradas = filtrarActividades($filtro);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis actividades - Aulamos</title>
    
    <!-- Estilos base (tu Inicio.css) -->
    <link rel="stylesheet" href="Style/Inicio.css">
    <!-- Estilos específicos para actividades -->
    <link rel="stylesheet" href="Style/actividades.css">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="dashboard-container">
        
        <!-- BARRA LATERAL (idéntica a la de alumno.php) -->
        <aside class="sidebar">
            <div class="logo-section">
                <img src="https://placehold.co/50x50/3b71f3/white?text=🦉" alt="Búho Aulamos" class="logo-img">
                <div>
                    <h2>AULAMOS</h2>
                    <p>Aprendemos juntos</p>
                </div>
            </div>
            
            <nav class="menu">
                 
                <a href="alumno.php" class="menu-item"><i class="fa-solid fa-cubes"></i> Inicio</a>
                <a href="actividades.php" class="menu-item active"><i class="fa-solid fa-cubes"></i> Mis actividades</a>
                <a href="biblioteca.php" class="menu-item"><i class="fa-solid fa-book-open"></i> Biblioteca digital</a>
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
                <button data-filtro="todas" class="activo">Todas</button>
                <button data-filtro="pendiente">Pendientes</button>
                <button data-filtro="proceso">En Proceso</button>
                <button data-filtro="completada">Completadas</button>
            </div>

            <!-- LISTA DE ACTIVIDADES -->
            <div class="lista-actividades" id="listaActividades">
                <?php if (count($actividadesFiltradas) > 0): ?>
                    <?php foreach ($actividadesFiltradas as $act): ?>
                        <div class="card-actividad" data-estado="<?= $act['estado'] ?>">
                            <div class="card-info">
                                <div class="card-titulo"><?= htmlspecialchars($act['titulo']) ?></div>
                                <div class="card-asignatura"><?= htmlspecialchars($act['asignatura']) ?></div>
                                <div class="card-fecha">Vence: <?= htmlspecialchars($act['vencimiento']) ?></div>
                            </div>
                            <div class="card-acciones">
                                <span class="estado-badge <?= $act['estado'] ?>">
                                    <?php
                                        $estados = ['pendiente' => 'Pendiente', 'proceso' => 'En Proceso', 'completada' => 'Completada'];
                                        echo $estados[$act['estado']];
                                    ?>
                                </span>
                                <?php if ($act['estado'] !== 'completada'): ?>
                                    <button class="btn-ext" data-id="<?= $act['id'] ?>">Solicitar extensión</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; padding:20px; color:#64748b;">No hay actividades en este estado.</p>
                <?php endif; ?>
            </div>

            <!-- ACCESIBILIDAD (igual que en alumno.php) -->
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

    <!-- JavaScript base (Inicio.js) y específico -->
    <script src="js/Inicio.js"></script>
    <script src="js/actividades.js"></script>
</body>
</html>