<?php
session_start();

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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aulamos - Administrador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="<?php echo $clases_body; ?>">

<div class="dashboard-container">
    
    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logogeneral.png" alt="Logo Aulamos" class="logo">
        </div>
        
        <nav class="menu">
            <a href="admin_dashboard.php" class="menu-item active">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="ciclos_escolares.php" class="menu-item">
                <i class="fa-solid fa-calendar"></i> Ciclos escolares
            </a>
            <a href="periodos.php" class="menu-item">
                <i class="fa-solid fa-clock"></i> Periodos
            </a>
            <a href="materias.php" class="menu-item">
                <i class="fa-solid fa-book"></i> Materias
            </a>
            <a href="grupos.php" class="menu-item">
                <i class="fa-solid fa-layer-group"></i> Grupos
            </a>
            <a href="cursos.php" class="menu-item">
                <i class="fa-solid fa-cubes"></i> Cursos
            </a>
            <a href="inscripciones.php" class="menu-item">
                <i class="fa-solid fa-pen-to-square"></i> Inscripciones
            </a>
           
            <a href="configuracion.php" class="menu-item">
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
                <h1>Panel Administrativo</h1>
                <h2>¡Hola, <span class="admin-name"><?php echo htmlspecialchars($nombre_admin); ?></span>! 👋</h2>
                <p>Bienvenido a tu espacio administrativo.</p>
            </div>
            <div class="header-actions">
<<<<<<< HEAD
                <button class="btn-assistant" id="btn-asistente" onclick="window.location.href='../Alumno/ChatbotAdmin.php'">
=======
                <!-- <button class="btn-assistant" id="btn-asistente">
>>>>>>> 713a1ae (Correcciones de accesibilidad en Administrador)
                    <i class="fa-solid fa-comment-dots"></i> Chatbot
                </button> -->
                <div class="icon-bell">
                    <i class="fa-regular fa-bell"></i>
                </div>
               <!--  <button class="btn-accessibility-header">
                    <i class="fa-solid fa-universal-access"></i>
                </button>-->
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
            <h3 class="section-title">Panel Académico</h3>
            <div class="stats-grid">
                <div class="stat-box bg-blue">
                    <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="stat-content">
                        <p class="stat-label">Planeación</p>
                        <h4 class="stat-number"><?php echo $ciclos_activos; ?></h4>
                        <p class="stat-sub">Ciclos activos</p>
                    </div>
                </div>
                <div class="stat-box bg-purple">
                    <div class="stat-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    <div class="stat-content">
                        <p class="stat-label">Académico</p>
                        <h4 class="stat-number"><?php echo $materias_activas; ?></h4>
                        <p class="stat-sub">Materias</p>
                    </div>
                </div>
                <div class="stat-box bg-green">
                    <div class="stat-icon"><i class="fa-solid fa-user-group"></i></div>
                    <div class="stat-content">
                        <p class="stat-label">Estudiantes</p>
                        <h4 class="stat-number"><?php echo $estudiantes_inscritos; ?></h4>
                        <p class="stat-sub">Inscritos</p>
                    </div>
                </div>
                <div class="stat-box bg-orange">
                    <div class="stat-icon"><i class="fa-solid fa-cubes"></i></div>
                    <div class="stat-content">
                        <p class="stat-label">Módulos</p>
                        <h4 class="stat-number"><?php echo $cursos_activos; ?></h4>
                        <p class="stat-sub">Cursos activos</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ACCESOS RÁPIDOS -->
        <section class="accesos-rapidos">
            <h3 class="section-title">Accesos rápidos</h3>
            <div class="quick-access-grid">
                <a href="ciclos_escolares.php" class="quick-btn bg-blue-light">
                    <i class="fa-solid fa-calendar"></i>
                    <span>Ciclos escolares</span>
                </a>
                <a href="periodos.php" class="quick-btn bg-purple-light">
                    <i class="fa-solid fa-clock"></i>
                    <span>Periodos</span>
                </a>
                <a href="materias.php" class="quick-btn bg-green-light">
                    <i class="fa-solid fa-book"></i>
                    <span>Materias</span>
                </a>
                <a href="grupos.php" class="quick-btn bg-orange-light">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Grupos</span>
                </a>
                <a href="cursos.php" class="quick-btn bg-pink-light">
                    <i class="fa-solid fa-cubes"></i>
                    <span>Cursos</span>
                </a>
                <a href="inscripciones.php" class="quick-btn bg-cyan-light">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Inscripciones</span>
                </a>
            </div>
        </section>

        <!-- GESTIÓN ACADÉMICA -->
        <section class="gestion-academica">
            <div class="gestion-card">
                <div class="gestion-content">
                    <h3>Gestión académica</h3>
                    <p>Mantiene actualizada la información escolar</p>
                    <a href="configuracion.php" class="btn-configuracion">
                        <i class="fa-solid fa-gear"></i> Configuración
                    </a>
                </div>
                <div class="gestion-nota">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Revisa la configuración del ciclo escolar</span>
                </div>
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
<script src="js/lector.js"></script>
</body>
</html>