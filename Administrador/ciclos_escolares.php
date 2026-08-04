<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

$nombre_admin = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
$pagina_actual = basename($_SERVER['PHP_SELF']);

// Datos de ejemplo (después se conectarán a la BD)
$ciclo_actual = 'Ciclo escolar 2026-2027';
$total_ciclos = 1;

$ciclos = [
    [
        'id' => 1,
        'nombre' => 'Ciclo escolar 2026-2027',
        'estado' => 'Activo',
        'inicio' => '05 oct 2026',
        'fin' => '05 oct 2027',
        'periodos' => 1,
        'grupos' => 1,
        'cursos' => 2
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ciclos Escolares - Administrador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="dashboard-container">
    
    <!-- BARRA LATERAL (SIDEBAR) -->
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
                <h1>Ciclos escolares</h1>
                <p>Administración académica</p>
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
        <!-- RESUMEN DEL CICLO ACTUAL                  -->
        <!-- ========================================== -->
        <section class="resumen-ciclo">
            <div class="resumen-card">
                <div class="resumen-icon">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div class="resumen-info">
                    <p class="resumen-label">Ciclo actual</p>
                    <h3 class="resumen-titulo"><?php echo $ciclo_actual; ?></h3>
                    <p class="resumen-sub"><?php echo $total_ciclos; ?> ciclo registrado</p>
                </div>
                <button class="btn-agregar">
                    <i class="fa-solid fa-plus"></i> Nuevo ciclo
                </button>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- LISTA DE CICLOS REGISTRADOS               -->
        <!-- ========================================== -->
        <section class="lista-ciclos">
            <div class="section-header">
                <h3 class="section-title">Ciclos registrados</h3>
                <p class="section-sub">Desliza hacia abajo para actualizar.</p>
            </div>

            <div class="ciclos-grid">
                <?php foreach ($ciclos as $ciclo): ?>
                <div class="ciclo-card">
                    <div class="ciclo-header">
                        <div>
                            <h4 class="ciclo-nombre"><?php echo $ciclo['nombre']; ?></h4>
                            <p class="ciclo-id">Identificador: <?php echo $ciclo['id']; ?></p>
                        </div>
                        <span class="badge <?php echo ($ciclo['estado'] === 'Activo') ? 'badge-activo' : 'badge-inactivo'; ?>">
                            <?php echo $ciclo['estado']; ?>
                        </span>
                    </div>

                    <div class="ciclo-fechas">
                        <div class="fecha-item">
                            <i class="fa-regular fa-calendar"></i>
                            <span>Inicio: <strong><?php echo $ciclo['inicio']; ?></strong></span>
                        </div>
                        <div class="fecha-item">
                            <i class="fa-regular fa-calendar"></i>
                            <span>Finalización: <strong><?php echo $ciclo['fin']; ?></strong></span>
                        </div>
                    </div>

                    <div class="ciclo-estadisticas">
                        <div class="stat-item">
                            <span class="stat-numero"><?php echo $ciclo['periodos']; ?></span>
                            <span class="stat-etiqueta">Periodos</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-numero"><?php echo $ciclo['grupos']; ?></span>
                            <span class="stat-etiqueta">Grupos</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-numero"><?php echo $ciclo['cursos']; ?></span>
                            <span class="stat-etiqueta">Cursos</span>
                        </div>
                    </div>

                    <div class="ciclo-acciones">
                        <button class="btn-editar">
                            <i class="fa-regular fa-pen-to-square"></i> Editar
                        </button>
                        <button class="btn-cerrar">
                            <i class="fa-solid fa-lock"></i> Cerrar ciclo
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- BARRA DE ACCESIBILIDAD INFERIOR -->
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