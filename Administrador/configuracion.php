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
        <button class="btn-accessibility-main">
            <i class="fa-solid fa-universal-access"></i> <?php echo __('accesibilidad'); ?>
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
                            <label>
                                <input type="radio" name="idioma" value="es" <?php echo ($idioma_actual === 'es') ? 'checked' : ''; ?>>
                                <i class="fa-solid fa-language"></i> <?php echo __('español'); ?>
                            </label>
                            <label>
                                <input type="radio" name="idioma" value="en" <?php echo ($idioma_actual === 'en') ? 'checked' : ''; ?>>
                                <i class="fa-solid fa-language"></i> <?php echo __('ingles'); ?>
                            </label>
                        </div>
                    </div>

                    <!-- TAMAÑO DE TEXTO -->
                    <div class="form-group">
                        <label><?php echo __('tamano_texto'); ?></label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="tamano_texto" value="pequeño" <?php echo ($tamano_actual === 'pequeño') ? 'checked' : ''; ?>>
                                <span style="font-size: 12px;">A</span> <?php echo __('pequeño'); ?>
                            </label>
                            <label>
                                <input type="radio" name="tamano_texto" value="normal" <?php echo ($tamano_actual === 'normal') ? 'checked' : ''; ?>>
                                <span style="font-size: 16px;">A</span> <?php echo __('normal'); ?>
                            </label>
                            <label>
                                <input type="radio" name="tamano_texto" value="grande" <?php echo ($tamano_actual === 'grande') ? 'checked' : ''; ?>>
                                <span style="font-size: 20px;">A</span> <?php echo __('grande'); ?>
                            </label>
                        </div>
                    </div>

                    <!-- PERSONALIZAR ALTO CONTRASTE 
                    <div class="form-group">
                        <label>🎨 <?php echo __('personalizar_contraste'); ?></label>
                        <p style="font-size: 13px; color: #6b7280; margin-bottom: 12px;">Elige el fondo y el color de acento para el modo alto contraste.</p>
                        
                        <!-- Fondo 
                        <div style="margin-bottom: 12px;">
                            <span style="font-weight: 500; font-size: 14px; display: block; margin-bottom: 6px;">Fondo</span>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="contraste_fondo" value="blanco" <?php echo ($fondo_contraste === 'blanco') ? 'checked' : ''; ?>>
                                    <span class="color-preview" style="background:#ffffff; border:2px solid #333;"></span> Blanco
                                </label>
                                <label>
                                    <input type="radio" name="contraste_fondo" value="negro" <?php echo ($fondo_contraste === 'negro') ? 'checked' : ''; ?>>
                                    <span class="color-preview" style="background:#000000; border:2px solid #333;"></span> Negro
                                </label>
                            </div>
                        </div>

                        <!-- Color de acento 
                        <div>
                            <span style="font-weight: 500; font-size: 14px; display: block; margin-bottom: 6px;">Color de acento</span>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="contraste_color" value="azul" <?php echo ($color_contraste === 'azul') ? 'checked' : ''; ?>>
                                    <span class="color-preview" style="background:#1d4ed8;"></span> Azul
                                </label>
                                <label>
                                    <input type="radio" name="contraste_color" value="amarillo" <?php echo ($color_contraste === 'amarillo') ? 'checked' : ''; ?>>
                                    <span class="color-preview" style="background:#ca8a04;"></span> Amarillo
                                </label>
                                <label>
                                    <input type="radio" name="contraste_color" value="verde" <?php echo ($color_contraste === 'verde') ? 'checked' : ''; ?>>
                                    <span class="color-preview" style="background:#15803d;"></span> Verde
                                </label>
                                <label>
                                    <input type="radio" name="contraste_color" value="rojo" <?php echo ($color_contraste === 'rojo') ? 'checked' : ''; ?>>
                                    <span class="color-preview" style="background:#b91c1c;"></span> Rojo
                                </label>
                                <label>
                                    <input type="radio" name="contraste_color" value="naranja" <?php echo ($color_contraste === 'naranja') ? 'checked' : ''; ?>>
                                    <span class="color-preview" style="background:#ea580c;"></span> Naranja
                                </label>
                                <label>
                                    <input type="radio" name="contraste_color" value="morado" <?php echo ($color_contraste === 'morado') ? 'checked' : ''; ?>>
                                    <span class="color-preview" style="background:#7c3aed;"></span> Morado
                                </label>
                            </div>
                        </div>
                    </div> -->

                    <div class="form-actions">
                        <button type="submit" class="btn-guardar"><?php echo __('guardar'); ?></button>
                    </div>
                </form>
            </div>

        </div>

        <footer class="accessibility-bar">
            <div class="acc-info">
                <i class="fa-solid fa-eye-low-vision acc-icon-main"></i>
                <div>
                    <strong><?php echo __('accesibilidad'); ?></strong>
                    <p>Personaliza tu experiencia en cualquier momento</p>
                </div>
            </div>
            <div class="acc-options">
                <button class="acc-opt-btn" id="btn-contrast">
                    <i class="fa-solid fa-eye"></i><span><?php echo __('alto_contraste'); ?></span>
                </button>
                <button class="acc-opt-btn" id="btn-darkmode">
                    <i class="fa-solid fa-moon"></i><span><?php echo __('oscuro'); ?></span>
                </button>
                <button class="acc-opt-btn" id="btn-text-size">
                    <span class="font-icon">Aa</span><span><?php echo __('grande'); ?></span>
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
            <button class="btn-open-config"><?php echo __('configuracion'); ?></button>
        </footer>

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

<script src="js/admin.js"></script>
<script src="js/configuracion.js"></script>
<script src="js/lector.js"></script>
</body>
</html>