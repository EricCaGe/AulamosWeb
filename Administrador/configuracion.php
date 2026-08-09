<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';
require_once 'includes/preferencias.php';

$nombre_admin = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
$pagina_actual = basename($_SERVER['PHP_SELF']);

$ciclo_activo = $conexion->query("SELECT nombre FROM ciclos_escolares WHERE estado = 'Activo' LIMIT 1")->fetch_assoc();
$ciclo_nombre = $ciclo_activo['nombre'] ?? 'No hay ciclo activo';

$tema_actual = ($modo_oscuro == 1) ? 'oscuro' : 'claro';
$tamano_actual = strtolower($tamano_texto ?? 'normal');
$contraste_actual = $alto_contraste ?? 0;

// Cargar colores personalizados de alto contraste
$fondo_contraste = $_SESSION['contraste_fondo'] ?? 'negro';
$color_contraste = $_SESSION['contraste_color'] ?? 'azul';
?>
<!DOCTYPE html>
<html lang="<?php echo $idioma_actual === 'es' ? 'es' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('configuracion'); ?> - Administrador</title>
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="styles/configuracion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- ✅ NUEVA ACCESIBILIDAD - RUTA CORRECTA -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
</head>
<body class="<?php echo $clases_body; ?>">

<div class="dashboard-container">
    
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
        
        <!-- ✅ BOTÓN ACCESIBILIDAD EN SIDEBAR -->
        <button class="btn-accesibilidad-header" id="btnAccesibilidad" onclick="toggleBarraAccesibilidad()" style="width:100%; margin-top:20px; background:#5a189a; color:white; border:none; padding:12px; border-radius:10px; cursor:pointer; font-weight:bold;">
            <i class="fa-solid fa-universal-access"></i> Accesibilidad
        </button>
    </aside>

    <main class="main-content">
        
        <header class="content-header">
            <div class="welcome-text">
                <h1><?php echo __('configuracion'); ?></h1>
                <p><?php echo __('administra_config'); ?></p>
            </div>
            <div class="header-actions">
                <div class="icon-bell">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <button class="btn-idioma" id="btnIdioma" title="Cambiar idioma">
                    <i class="fa-solid fa-language"></i>
                    <span id="idiomaTexto"><?php echo $idioma_actual === 'es' ? 'ES' : 'EN'; ?></span>
                </button>
                <a href="perfil.php" class="user-profile" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <img src="https://placehold.co/40x40/3b71f3/white?text=👤" alt="Avatar Admin" class="avatar">
                    <span class="user-name"><?php echo htmlspecialchars($nombre_admin); ?></span>
                    <i class="fa-solid fa-chevron-down drop-icon"></i>
                </a>
                <a href="../InicioSesion/cerrar_sesion.php" class="btn-logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <div class="config-grid">
            
            <div class="config-card">
                <div class="config-header">
                    <i class="fa-solid fa-circle-info"></i>
                    <h3><?php echo __('informacion_general'); ?></h3>
                </div>
                <form>
                    <div class="form-group">
                        <label for="nombre_sistema"><?php echo __('nombre_sistema'); ?></label>
                        <input type="text" id="nombre_sistema" name="nombre_sistema" value="AULAMOS" disabled>
                        <p class="help-text">Nombre del sistema (solo lectura)</p>
                    </div>
                    <div class="form-group">
                        <label for="ciclo_actual"><?php echo __('ciclo_actual'); ?></label>
                        <input type="text" id="ciclo_actual" name="ciclo_actual" value="<?php echo htmlspecialchars($ciclo_nombre); ?>" disabled>
                        <p class="help-text">Ciclo escolar activo en el sistema</p>
                    </div>
                    <div class="form-group">
                        <label for="version"><?php echo __('version'); ?></label>
                        <input type="text" id="version" name="version" value="1.0.0" disabled>
                    </div>
                </form>
            </div>

            <div class="config-card">
                <div class="config-header">
                    <i class="fa-solid fa-sliders"></i>
                    <h3><?php echo __('preferencias'); ?></h3>
                </div>
                <form method="POST" action="logica/procesar_configuracion.php" id="formConfiguracion">
                    
                    <!-- IDIOMA -->
                    <div class="form-group">
                        <label><?php echo __('idioma'); ?></label>
                        <div class="radio-group">
                            <label for="idioma_es">
                                <input type="radio" name="idioma" id="idioma_es" value="es" <?php echo ($idioma_actual === 'es') ? 'checked' : ''; ?>>
                                <i class="fa-solid fa-language"></i> <?php echo __('español'); ?>
                            </label>
                            <label for="idioma_en">
                                <input type="radio" name="idioma" id="idioma_en" value="en" <?php echo ($idioma_actual === 'en') ? 'checked' : ''; ?>>
                                <i class="fa-solid fa-language"></i> <?php echo __('ingles'); ?>
                            </label>
                        </div>
                    </div>

                    <!-- TAMAÑO DE TEXTO -->
                    <div class="form-group">
                        <label><?php echo __('tamano_texto'); ?></label>
                        <div class="radio-group">
                            <label for="tamano_pequeno">
                                <input type="radio" name="tamano_texto" id="tamano_pequeno" value="pequeño" <?php echo ($tamano_actual === 'pequeño') ? 'checked' : ''; ?>>
                                <span style="font-size: 12px;">A</span> <?php echo __('pequeño'); ?>
                            </label>
                            <label for="tamano_normal">
                                <input type="radio" name="tamano_texto" id="tamano_normal" value="normal" <?php echo ($tamano_actual === 'normal') ? 'checked' : ''; ?>>
                                <span style="font-size: 16px;">A</span> <?php echo __('normal'); ?>
                            </label>
                            <label for="tamano_grande">
                                <input type="radio" name="tamano_texto" id="tamano_grande" value="grande" <?php echo ($tamano_actual === 'grande') ? 'checked' : ''; ?>>
                                <span style="font-size: 20px;">A</span> <?php echo __('grande'); ?>
                            </label>
                            <label for="tamano_muy_grande">
            <input type="radio" name="tamano_texto" id="tamano_muy_grande" value="muy_grande" <?php echo ($tamano_actual === 'muy_grande') ? 'checked' : ''; ?>>
            <span style="font-size: 24px;">A</span> <?php echo __('muy_grande'); ?>
        </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-guardar"><?php echo __('guardar'); ?></button>
                    </div>
                </form>
            </div>

        </div>

        <!-- ✅ NUEVA BARRA DE ACCESIBILIDAD -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </main>
</div>

<!-- ========================================== -->
<!-- INYECTAR PREFERENCIAS AL JAVASCRIPT        -->
<!-- ========================================== -->
<script>
    window.preferenciasServidor = {
        modo_oscuro: <?php echo $modo_oscuro ?? 0; ?>,
        alto_contraste: <?php echo $alto_contraste ?? 0; ?>,
        contraste_fondo: '<?php echo $contraste_fondo ?? "negro"; ?>',
        contraste_color: '<?php echo $contraste_color ?? "azul"; ?>',
        tamano_texto: '<?php echo $tamano_texto ?? "normal"; ?>'
    };
    console.log('✅ Preferencias del servidor cargadas:', window.preferenciasServidor);
</script>

<!-- ✅ BOTÓN FLOTANTE -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<script src="js/admin.js"></script>
<script src="js/configuracion.js"></script>

<!-- ✅ NUEVA ACCESIBILIDAD JS - RUTA CORRECTA -->
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>