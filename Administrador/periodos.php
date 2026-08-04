<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

$nombre_admin = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
$pagina_actual = basename($_SERVER['PHP_SELF']);

// Datos de ejemplo (después se conectarán a la BD)
$ciclos = [
    ['id' => 1, 'nombre' => 'Ciclo escolar 2026-2027', 'inicio' => '05/10/2026', 'fin' => '05/10/2027']
];

$ciclo_seleccionado = 1; // Por defecto el primero
$periodos = [
    [
        'id' => 1,
        'nombre' => 'Primer periodo',
        'ciclo' => 'Ciclo escolar 2026-2027',
        'inicio' => '05/10/2026',
        'fin' => '15/01/2027',
        'estado' => 'Activo'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Periodos de evaluación - Administrador</title>
    
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
                <h1>Periodos de evaluación</h1>
                <p>Administra los periodos de cada ciclo escolar.</p>
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
        <!-- SELECTOR DE CICLO ESCOLAR                  -->
        <!-- ========================================== -->
        <section class="selector-ciclo">
            <div class="ciclo-selector-card">
                <label class="selector-label">Ciclo escolar</label>
                <select class="selector-select">
                    <?php foreach ($ciclos as $ciclo): ?>
                        <option value="<?php echo $ciclo['id']; ?>" <?php echo ($ciclo['id'] == $ciclo_seleccionado) ? 'selected' : ''; ?>>
                            <?php echo $ciclo['nombre']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="ciclo-fechas-info">
                    <span class="fecha-info">
                        <i class="fa-regular fa-calendar"></i>
                        <?php echo $ciclos[0]['inicio']; ?> – <?php echo $ciclos[0]['fin']; ?>
                    </span>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- LISTA DE PERIODOS                          -->
        <!-- ========================================== -->
        <section class="lista-periodos">
            <div class="periodos-header">
                <h3>Periodos registrados</h3>
                <span class="resultados"><?php echo count($periodos); ?> periodo encontrado</span>
                <button class="btn-nuevo-periodo">
                    <i class="fa-solid fa-plus"></i> Nuevo
                </button>
            </div>

            <div class="periodos-grid">
                <?php foreach ($periodos as $periodo): ?>
                <div class="periodo-card">
                    <div class="periodo-header">
                        <div>
                            <h4 class="periodo-nombre"><?php echo $periodo['nombre']; ?></h4>
                            <p class="periodo-ciclo"><?php echo $periodo['ciclo']; ?></p>
                        </div>
                        <span class="badge <?php echo ($periodo['estado'] === 'Activo') ? 'badge-activo' : 'badge-inactivo'; ?>">
                            <?php echo $periodo['estado']; ?>
                        </span>
                    </div>

                    <div class="periodo-fechas">
                        <div class="fecha-item">
                            <span class="fecha-label">Inicio</span>
                            <span class="fecha-valor"><?php echo $periodo['inicio']; ?></span>
                        </div>
                        <div class="fecha-item">
                            <span class="fecha-label">Fin</span>
                            <span class="fecha-valor"><?php echo $periodo['fin']; ?></span>
                        </div>
                    </div>

                    <div class="periodo-acciones">
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