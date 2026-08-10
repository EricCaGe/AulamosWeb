<?php
session_start();
// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

// Verificar que el usuario haya iniciado sesión y sea Admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';
require_once 'includes/preferencias.php';

$nombre_admin = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
$pagina_actual = basename($_SERVER['PHP_SELF']);

// ========================================== */
// CONSULTAS A LA BD                          */
// ========================================== */

// 1. Ciclos activos (Planeación)
$resultado = $conexion->query("SELECT COUNT(*) AS total FROM ciclos_escolares WHERE estado = 'Activo'");
$row = $resultado->fetch_assoc();
$ciclos_activos = $row['total'] ?? 0;

// 2. Materias activas (Académico)
$resultado = $conexion->query("SELECT COUNT(*) AS total FROM materias WHERE estado = 'Activa'");
$row = $resultado->fetch_assoc();
$materias_activas = $row['total'] ?? 0;

// 3. Estudiantes inscritos (Estudiantes)
$resultado = $conexion->query("SELECT COUNT(DISTINCT id_alumno) AS total FROM inscripciones WHERE estado = 'Activo'");
$row = $resultado->fetch_assoc();
$estudiantes_inscritos = $row['total'] ?? 0;

// 4. Cursos activos (Módulos)
$resultado = $conexion->query("SELECT COUNT(*) AS total FROM cursos WHERE estado = 'Activo'");
$row = $resultado->fetch_assoc();
$cursos_activos = $row['total'] ?? 0;

// 5. Últimos 5 usuarios registrados
$usuarios_recientes = $conexion->query("
    SELECT nombre, apellido_paterno, correo, fecha_registro 
    FROM usuarios 
    ORDER BY fecha_registro DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?php echo $idioma_actual === 'es' ? 'es' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('dashboard'); ?> - Administrador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- ✅ NUEVA ACCESIBILIDAD -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
</head>
<body class="<?php echo $clases_body; ?>">

<div class="dashboard-container">
    
    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logogeneral.png" alt="Logo Aulamos" class="logo">
        </div>
        
        <nav class="menu">
            <a href="admin_dashboard.php" class="menu-item <?php echo ($pagina_actual == 'admin_dashboard.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-house"></i> <?php echo __('dashboard'); ?>
            </a>
            <a href="ciclos_escolares.php" class="menu-item <?php echo ($pagina_actual == 'ciclos_escolares.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar"></i> <?php echo __('ciclos'); ?>
            </a>
            <a href="periodos.php" class="menu-item <?php echo ($pagina_actual == 'periodos.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock"></i> <?php echo __('periodos'); ?>
            </a>
            <a href="materias.php" class="menu-item <?php echo ($pagina_actual == 'materias.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-book"></i> <?php echo __('materias'); ?>
            </a>
            <a href="grupos.php" class="menu-item <?php echo ($pagina_actual == 'grupos.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-layer-group"></i> <?php echo __('grupos'); ?>
            </a>
            <a href="cursos.php" class="menu-item <?php echo ($pagina_actual == 'cursos.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-cubes"></i> <?php echo __('cursos'); ?>
            </a>
            <a href="inscripciones.php" class="menu-item <?php echo ($pagina_actual == 'inscripciones.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-pen-to-square"></i> <?php echo __('inscripciones'); ?>
            </a>
           
            <a href="configuracion.php" class="menu-item <?php echo ($pagina_actual == 'configuracion.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gear"></i> <?php echo __('configuracion'); ?>
            </a>
        </nav>
        
        <!-- ✅ BOTÓN ACCESIBILIDAD NUEVO -->
        <button class="btn-accesibilidad-header" id="btnAccesibilidad" onclick="toggleBarraAccesibilidad()" style="width:100%; margin-top:20px; background:#5a189a; color:white; border:none; padding:12px; border-radius:10px; cursor:pointer; font-weight:bold;">
            <i class="fa-solid fa-universal-access"></i> <?php echo __('accesibilidad'); ?>
        </button>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        
        <!-- ENCABEZADO -->
        <header class="content-header">
            <div class="welcome-text">
                <h1><?php echo __('panel_administrativo'); ?></h1>
                <h2>¡Hola, <span class="admin-name"><?php echo htmlspecialchars($nombre_admin); ?></span>! 👋</h2>
                <p><?php echo __('bienvenido_admin'); ?></p>
            </div>
            <div class="header-actions">
                <!-- ✅ BOTÓN CHATBOT -->
                <button class="btn-assistant" id="btn-asistente" onclick="window.location.href='../Alumno/ChatbotAdmin.php'">
                    <i class="fa-solid fa-comment-dots"></i> <?php echo __('chatbot'); ?>
                </button>

                <div class="icon-bell">
                    <i class="fa-regular fa-bell"></i>
                </div>

                <a href="perfil.php" class="user-profile" style="text-decoration:none; cursor:pointer; display:flex; align-items:center; gap:10px;">
                    <img src="https://placehold.co/40x40/3b71f3/white?text=👤" alt="Avatar Admin" class="avatar">
                    <span class="user-name"><?php echo htmlspecialchars($nombre_admin); ?></span>
                    <i class="fa-solid fa-chevron-down drop-icon"></i>
                </a>
                <a href="../InicioSesion/cerrar_sesion.php" class="btn-logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <!-- PANEL ACADÉMICO (DASHBOARD) -->
        <section class="panel-academico">
            <h3 class="section-title"><?php echo __('panel_academico'); ?></h3>
            <div class="stats-grid">
                <div class="stat-box bg-blue">
                    <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="stat-content">
                        <p class="stat-label"><?php echo __('planeacion'); ?></p>
                        <h4 class="stat-number"><?php echo $ciclos_activos; ?></h4>
                        <p class="stat-sub"><?php echo __('ciclos_activos'); ?></p>
                    </div>
                </div>
                <div class="stat-box bg-purple">
                    <div class="stat-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    <div class="stat-content">
                        <p class="stat-label"><?php echo __('academico'); ?></p>
                        <h4 class="stat-number"><?php echo $materias_activas; ?></h4>
                        <p class="stat-sub"><?php echo __('materias'); ?></p>
                    </div>
                </div>
                <div class="stat-box bg-green">
                    <div class="stat-icon"><i class="fa-solid fa-user-group"></i></div>
                    <div class="stat-content">
                        <p class="stat-label"><?php echo __('estudiantes'); ?></p>
                        <h4 class="stat-number"><?php echo $estudiantes_inscritos; ?></h4>
                        <p class="stat-sub"><?php echo __('inscritos'); ?></p>
                    </div>
                </div>
                <div class="stat-box bg-orange">
                    <div class="stat-icon"><i class="fa-solid fa-cubes"></i></div>
                    <div class="stat-content">
                        <p class="stat-label"><?php echo __('modulos'); ?></p>
                        <h4 class="stat-number"><?php echo $cursos_activos; ?></h4>
                        <p class="stat-sub"><?php echo __('cursos_activos'); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ACCESOS RÁPIDOS -->
        <section class="accesos-rapidos">
            <h3 class="section-title"><?php echo __('accesos_rapidos'); ?></h3>
            <div class="quick-access-grid">
                <a href="ciclos_escolares.php" class="quick-btn bg-blue-light">
                    <i class="fa-solid fa-calendar"></i>
                    <span><?php echo __('ciclos'); ?></span>
                </a>
                <a href="periodos.php" class="quick-btn bg-purple-light">
                    <i class="fa-solid fa-clock"></i>
                    <span><?php echo __('periodos'); ?></span>
                </a>
                <a href="materias.php" class="quick-btn bg-green-light">
                    <i class="fa-solid fa-book"></i>
                    <span><?php echo __('materias'); ?></span>
                </a>
                <a href="grupos.php" class="quick-btn bg-orange-light">
                    <i class="fa-solid fa-layer-group"></i>
                    <span><?php echo __('grupos'); ?></span>
                </a>
                <a href="cursos.php" class="quick-btn bg-pink-light">
                    <i class="fa-solid fa-cubes"></i>
                    <span><?php echo __('cursos'); ?></span>
                </a>
                <a href="inscripciones.php" class="quick-btn bg-cyan-light">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span><?php echo __('inscripciones'); ?></span>
                </a>
            </div>
        </section>

        <!-- GESTIÓN ACADÉMICA -->
        <section class="gestion-academica">
            <div class="gestion-card">
                <div class="gestion-content">
                    <h3><?php echo __('gestion_academica'); ?></h3>
                    <p><?php echo __('gestion_descripcion'); ?></p>
                    <a href="configuracion.php" class="btn-configuracion">
                        <i class="fa-solid fa-gear"></i> <?php echo __('configuracion'); ?>
                    </a>
                </div>
                <div class="gestion-nota">
                    <i class="fa-solid fa-circle-info"></i>
                    <span><?php echo __('gestion_nota'); ?></span>
                </div>
            </div>
        </section>

        <!-- ✅ NUEVA BARRA DE ACCESIBILIDAD (ELIMINADA LA VIEJA) -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </main>
</div>

<!-- ✅ BOTÓN FLOTANTE -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<script src="js/admin.js"></script>

<!-- ✅ NUEVA ACCESIBILIDAD JS -->
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>