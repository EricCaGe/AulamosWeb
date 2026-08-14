<?php
session_start();
// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../InicioSesion/login.php');
    exit;
}
require_once 'includes/preferencias.php';
$nombre_admin = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Administrador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
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
            <a href="perfil.php" class="menu-item <?php echo ($pagina_actual == 'perfil.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user"></i> Mi perfil
            </a>
        </nav>
        
        <button class="btn-accesibilidad-header" id="btnAccesibilidad" onclick="toggleBarraAccesibilidad()" style="width:100%; margin-top:20px; background:#5a189a; color:white; border:none; padding:12px; border-radius:10px; cursor:pointer; font-weight:bold;">
    <i class="fa-solid fa-universal-access"></i> Accesibilidad
</button>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        
        <!-- ENCABEZADO -->
        <header class="content-header">
            <div class="welcome-text">
                <h1><i class="fa-solid fa-user"></i> Mi Perfil</h1>
                <p>Administra tu información personal</p>
            </div>
            <div class="header-actions">
                <!-- ✅ BOTÓN CHATBOT -->
<button class="btn-assistant" id="btn-asistente" onclick="window.location.href='../Alumno/ChatbotAdmin.php'">
    <i class="fa-solid fa-comment-dots"></i> <?php echo __('chatbot'); ?>
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

        <?php
        $mensaje = $_GET['mensaje'] ?? '';
        $tipo = $_GET['tipo'] ?? '';
        if ($mensaje):
        ?>
            <div style="padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; <?php echo ($tipo === 'exito') ? 'background: #dcfce7; color: #166534; border-left: 4px solid #22c55e;' : 'background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626;'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- FORMULARIO DE PERFIL -->
        <section class="panel-academico">
            <h3 class="section-title">Información personal</h3>
            
            <form method="POST" action="logica/procesar_perfil.php">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Nombre(s)</label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($_SESSION['usuario']['nombre'] ?? ''); ?>" style="width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:10px; font-size:14px;" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido Paterno</label>
                        <input type="text" name="apellido_paterno" value="<?php echo htmlspecialchars($_SESSION['usuario']['apellido_paterno'] ?? ''); ?>" style="width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:10px; font-size:14px;" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido Materno</label>
                        <input type="text" name="apellido_materno" value="<?php echo htmlspecialchars($_SESSION['usuario']['apellido_materno'] ?? ''); ?>" style="width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:10px; font-size:14px;">
                    </div>
                    <div class="form-group">
                        <label>Correo electrónico</label>
                        <input type="email" value="<?php echo htmlspecialchars($_SESSION['usuario']['correo'] ?? ''); ?>" style="width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:10px; font-size:14px; background:#f1f5f9; color:#94a3b8;" readonly>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Rol</label>
                        <input type="text" value="<?php echo htmlspecialchars($_SESSION['usuario']['rol'] ?? 'Admin'); ?>" style="width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:10px; font-size:14px; background:#f1f5f9; color:#94a3b8;" readonly>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                    <a href="admin_dashboard.php" style="background: #f1f5f9; border: none; padding: 12px 28px; border-radius: 10px; font-weight: 600; font-size: 15px; color: #475569; cursor: pointer; text-decoration: none;">Cancelar</a>
                    <button type="submit" style="background: #3b71f3; color: white; border: none; padding: 12px 32px; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer;">Guardar cambios</button>
                </div>
            </form>
        </section>
 <!-- ✅ NUEVA BARRA DE ACCESIBILIDAD -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </main>
</div>

<!-- ✅ BOTÓN FLOTANTE DE ACCESIBILIDAD -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<script src="js/admin.js"></script>
<script src="js/lector.js"></script>

<!-- ✅ NUEVA ACCESIBILIDAD JS -->
<script src="../Accesibilidad/accesibilidad.js"></script>
</body>
</html>