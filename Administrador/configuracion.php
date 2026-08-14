<?php
session_start();
// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$nombre_admin = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
$pagina_actual = basename($_SERVER['PHP_SELF']);

$ciclo_activo = $conexion->query("SELECT nombre FROM ciclos_escolares WHERE estado = 'Activo' LIMIT 1")->fetch_assoc();
$ciclo_nombre = $ciclo_activo['nombre'] ?? 'No hay ciclo activo';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Administrador</title>
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="styles/configuracion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
</head>
<body>

<div class="dashboard-container">
    
    <aside class="sidebar">
        <button class="btn-assistant" id="btn-asistente" onclick="window.location.href='../Alumno/ChatbotAdmin.php'">
            <i class="fa-solid fa-comment-dots"></i> Chatbot
        </button>
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
            <a href="configuracion.php" class="menu-item <?php echo ($pagina_actual == 'configuracion.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gear"></i> Configuración
            </a>
        </nav>
        
        <button class="btn-accesibilidad-header" id="btnAccesibilidad" onclick="toggleBarraAccesibilidad()" style="width:100%; margin-top:20px; background:#5a189a; color:white; border:none; padding:12px; border-radius:10px; cursor:pointer; font-weight:bold;">
            <i class="fa-solid fa-universal-access"></i> Accesibilidad
        </button>
    </aside>

    <main class="main-content">
        
        <!-- ENCABEZADO CON FOTO DE PERFIL -->
<?php
// Obtener foto de perfil del administrador
$foto_perfil_admin = $_SESSION['usuario']['foto_perfil'] ?? null;
$ruta_foto_admin = !empty($foto_perfil_admin) ? '../uploads/perfiles/' . $foto_perfil_admin : 'https://placehold.co/40x40/3b71f3/white?text=👤';
?>
<header class="content-header">
    <div class="welcome-text">
        <h1>Panel Administrativo</h1>
        <h2>¡Hola, <span class="admin-name"><?php echo htmlspecialchars($nombre_admin); ?></span>! 👋</h2>
        <p>Bienvenido al panel de administración del sistema</p>
    </div>
    <div class="header-actions">
        <button class="btn-assistant" id="btn-asistente" onclick="window.location.href='../Alumno/ChatbotAdmin.php'">
            <i class="fa-solid fa-comment-dots"></i> Chatbot
        </button>

        <div class="icon-bell">
            <i class="fa-regular fa-bell"></i>
        </div>

        <a href="perfil.php" class="user-profile" style="text-decoration:none; cursor:pointer; display:flex; align-items:center; gap:10px;">
            <img src="<?php echo $ruta_foto_admin; ?>" alt="Avatar Admin" class="avatar">
            <span class="user-name"><?php echo htmlspecialchars($nombre_admin); ?></span>
            <i class="fa-solid fa-chevron-down drop-icon"></i>
        </a>
        <a href="../InicioSesion/cerrar_sesion.php" class="btn-logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
    </div>
</header>

        <div class="config-grid">
            
            <!-- ========================================== -->
            <!-- INFORMACIÓN GENERAL                        -->
            <!-- ========================================== -->
            <div class="config-card">
                <div class="config-header">
                    <i class="fa-solid fa-circle-info"></i>
                    <h3>Información general</h3>
                </div>
                <form>
                    <div class="form-group">
                        <label for="nombre_sistema">Nombre del sistema</label>
                        <input type="text" id="nombre_sistema" name="nombre_sistema" value="AULAMOS" disabled>
                        <p class="help-text">Nombre del sistema (solo lectura)</p>
                    </div>
                    <div class="form-group">
                        <label for="ciclo_actual">Ciclo escolar actual</label>
                        <input type="text" id="ciclo_actual" name="ciclo_actual" value="<?php echo htmlspecialchars($ciclo_nombre); ?>" disabled>
                        <p class="help-text">Ciclo escolar activo en el sistema</p>
                    </div>
                    <div class="form-group">
                        <label for="version">Versión</label>
                        <input type="text" id="version" name="version" value="1.0.0" disabled>
                    </div>
                </form>
            </div>

            <!-- ========================================== -->
            <!-- ACCESIBILIDAD - INFORMACIÓN               -->
            <!-- ========================================== -->
            <div class="config-card" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                <div class="config-header">
                    <i class="fa-solid fa-universal-access" style="color: #5a189a;"></i>
                    <h3 style="color: #1e293b;">Accesibilidad</h3>
                </div>
                <div style="padding: 10px 0; text-align: center;">
                    <i class="fa-solid fa-sliders" style="font-size: 28px; display: block; margin-bottom: 10px; color: #5a189a;"></i>
                    <p style="font-size: 14px; font-weight: 500; margin: 0; color: #475569;">
                        Las preferencias de accesibilidad (tamaño de texto, contraste, modo oscuro) 
                        se gestionan desde la <strong>barra de accesibilidad</strong> disponible en toda la plataforma.
                    </p>
                    <p style="font-size: 12px; color: #94a3b8; margin-top: 6px;">
                        Haz clic en el botón <strong>♿ Accesibilidad</strong> en el menú lateral o en el botón flotante 
                        en la esquina inferior derecha para personalizarlas.
                    </p>
                </div>
            </div>

        </div>

        <!-- BARRA DE ACCESIBILIDAD -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </main>
</div>

<!-- BOTÓN FLOTANTE -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<script src="js/admin.js"></script>
<script src="js/configuracion.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>