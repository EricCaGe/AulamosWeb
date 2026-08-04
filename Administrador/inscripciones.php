<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

$nombre_admin = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
$pagina_actual = basename($_SERVER['PHP_SELF']);

// Datos de ejemplo (después se conectarán a la BD)
$total_inscripciones = 4;
$activas = 4;

$inscripciones = [
    [
        'id' => 1,
        'estudiante' => 'Alumno Prueba Aulamos',
        'correo' => 'alumno.prueba@gmail.com',
        'curso' => 'Curso de prueba',
        'materia' => 'Matemáticas',
        'grupo' => 'Primero - 1° A',
        'ciclo' => 'Ciclo escolar 2026-2027',
        'fecha' => '30 jul 2026, 8:36 p.m.',
        'estado' => 'Activo'
    ],
    [
        'id' => 2,
        'estudiante' => 'Grecia Contreras Martínez',
        'correo' => 'grecia@gmail.com',
        'curso' => 'Inglés',
        'materia' => 'Inglés',
        'grupo' => 'Primero - 1° A',
        'ciclo' => 'Ciclo escolar 2026-2027',
        'fecha' => '30 jul 2026, 3:11 p.m.',
        'estado' => 'Activo'
    ],
    [
        'id' => 3,
        'estudiante' => 'Héctor Juárez López',
        'correo' => 'hector@gmail.com',
        'curso' => 'Inglés',
        'materia' => 'Inglés',
        'grupo' => 'Primero - 1° A',
        'ciclo' => 'Ciclo escolar 2026-2027',
        'fecha' => '30 jul 2026, 3:11 p.m.',
        'estado' => 'Activo'
    ],
    [
        'id' => 4,
        'estudiante' => 'Gamarcon',
        'correo' => 'gamarcon@cetis26.edu.mx',
        'curso' => 'Inglés',
        'materia' => 'Inglés',
        'grupo' => 'Primero - 1° A',
        'ciclo' => 'Ciclo escolar 2026-2027',
        'fecha' => '30 jul 2026, 3:11 p.m.',
        'estado' => 'Activo'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripciones - Administrador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="dashboard-container">
    
    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logogeneral.png" alt="Logo Aulamos" class="logo">
        </div>
        
        <nav class="menu">
            <a href="admin_dashboard.php" class="menu-item <?php echo ($pagina_actual == 'admin_dashboard.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="ciclos_escolares.php" class="menu-item <?php echo ($pagina_actual == 'ciclos_escolares.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar"></i> Ciclos escolares
            </a>
            <a href="periodos.php" class="menu-item <?php echo ($pagina_actual == 'periodos.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock"></i> Periodos
            </a>
            <a href="materias.php" class="menu-item <?php echo ($pagina_actual == 'materias.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-book"></i> Materias
            </a>
            <a href="grupos.php" class="menu-item <?php echo ($pagina_actual == 'grupos.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-layer-group"></i> Grupos
            </a>
            <a href="cursos.php" class="menu-item <?php echo ($pagina_actual == 'cursos.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-cubes"></i> Cursos
            </a>
            <a href="inscripciones.php" class="menu-item <?php echo ($pagina_actual == 'inscripciones.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-pen-to-square"></i> Inscripciones
            </a>
            <a href="usuarios.php" class="menu-item <?php echo ($pagina_actual == 'usuarios.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i> Usuarios
            </a>
            <a href="reportes.php" class="menu-item <?php echo ($pagina_actual == 'reportes.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-bar"></i> Reportes
            </a>
            <a href="configuracion.php" class="menu-item <?php echo ($pagina_actual == 'configuracion.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gear"></i> Configuración
            </a>
        </nav>
        
        <button class="btn-accessibility-main">
            <i class="fa-solid fa-universal-access"></i> Accesibilidad
        </button>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        
        <!-- ENCABEZADO -->
        <header class="content-header">
            <div class="welcome-text">
                <h1>Inscripciones</h1>
                <p>Asigna estudiantes a sus cursos</p>
            </div>
            <div class="header-actions">
                <button class="btn-assistant" id="btn-asistente">
                    <i class="fa-solid fa-comment-dots"></i> Chatbot
                </button>
                <div class="icon-bell">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <button class="btn-accessibility-header">
                    <i class="fa-solid fa-universal-access"></i>
                </button>
                <div class="user-profile">
                    <img src="https://placehold.co/40x40/3b71f3/white?text=👤" alt="Avatar Admin" class="avatar">
                    <span class="user-name"><?php echo htmlspecialchars($nombre_admin); ?></span>
                    <i class="fa-solid fa-chevron-down drop-icon"></i>
                </div>
                <a href="../InicioSesion/cerrar_sesion.php" class="btn-logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <!-- ========================================== -->
        <!-- RESUMEN DE INSCRIPCIONES                   -->
        <!-- ========================================== -->
        <section class="resumen-inscripciones">
            <div class="stats-row">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $total_inscripciones; ?></span>
                    <span class="stat-label">Total</span>
                </div>
                <div class="stat-card stat-activa">
                    <span class="stat-number"><?php echo $activas; ?></span>
                    <span class="stat-label">Activas</span>
                </div>
                <button class="btn-nueva-inscripcion">
                    <i class="fa-solid fa-plus"></i> Nueva
                </button>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- BÚSQUEDA                                  -->
        <!-- ========================================== -->
        <section class="busqueda-inscripciones">
            <div class="busqueda-container">
                <i class="fa-solid fa-search"></i>
                <input type="text" placeholder="Buscar estudiante, curso, materia o grupo..." class="input-busqueda">
            </div>
        </section>

        <!-- ========================================== -->
        <!-- LISTA DE INSCRIPCIONES                     -->
        <!-- ========================================== -->
        <section class="lista-inscripciones">
            <div class="inscripciones-grid">
                <?php foreach ($inscripciones as $inscripcion): ?>
                <div class="inscripcion-card">
                    <div class="inscripcion-header">
                        <div>
                            <h4 class="inscripcion-estudiante"><?php echo $inscripcion['estudiante']; ?></h4>
                            <span class="badge <?php echo ($inscripcion['estado'] === 'Activo') ? 'badge-activo' : 'badge-inactivo'; ?>">
                                <?php echo $inscripcion['estado']; ?>
                            </span>
                        </div>
                    </div>

                    <div class="inscripcion-detalles">
                        <div class="detalle-item">
                            <span class="detalle-label">Correo:</span>
                            <span class="detalle-valor"><?php echo $inscripcion['correo']; ?></span>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Curso:</span>
                            <span class="detalle-valor"><?php echo $inscripcion['curso']; ?></span>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Materia:</span>
                            <span class="detalle-valor"><?php echo $inscripcion['materia']; ?></span>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Grupo:</span>
                            <span class="detalle-valor"><?php echo $inscripcion['grupo']; ?></span>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Ciclo:</span>
                            <span class="detalle-valor"><?php echo $inscripcion['ciclo']; ?></span>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Inscripción:</span>
                            <span class="detalle-valor"><?php echo $inscripcion['fecha']; ?></span>
                        </div>
                    </div>

                    <div class="inscripcion-acciones">
                        <button class="btn-editar"><i class="fa-regular fa-pen-to-square"></i> Editar</button>
                        <button class="btn-deshabilitar"><i class="fa-solid fa-eye-slash"></i> Desactivar</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- BARRA DE ACCESIBILIDAD -->
        <footer class="accessibility-bar">
            <div class="acc-info">
                <i class="fa-solid fa-eye-low-vision acc-icon-main"></i>
                <div>
                    <strong>Accesibilidad siempre disponible</strong>
                    <p>Personaliza tu experiencia en cualquier momento.</p>
                </div>
            </div>
            <div class="acc-options">
                <button class="acc-opt-btn" id="btn-contrast">
                    <i class="fa-solid fa-eye"></i><span>Alto contraste</span>
                </button>
                <button class="acc-opt-btn" id="btn-darkmode">
                    <i class="fa-solid fa-moon"></i><span>Modo oscuro</span>
                </button>
                <button class="acc-opt-btn" id="btn-text-size">
                    <span class="font-icon">Aa</span><span>Texto grande</span>
                </button>
                <button class="acc-opt-btn">
                    <i class="fa-solid fa-volume-high"></i><span>Leer pantalla</span>
                </button>
                <button class="acc-opt-btn">
                    <i class="fa-solid fa-closed-captioning"></i><span>Subtítulos</span>
                </button>
                <button class="acc-opt-btn">
                    <i class="fa-solid fa-keyboard"></i><span>Navegación</span>
                </button>
            </div>
            <button class="btn-open-config">Abrir configuración</button>
        </footer>

    </main>
</div>

<script src="js/admin.js"></script>
</body>
</html>