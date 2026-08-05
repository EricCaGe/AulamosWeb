<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$nombre_admin = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
$pagina_actual = basename($_SERVER['PHP_SELF']);

// Obtener ciclo activo
$ciclo_activo = $conexion->query("SELECT nombre FROM ciclos_escolares WHERE estado = 'Activo' LIMIT 1")->fetch_assoc();
$ciclo_nombre = $ciclo_activo['nombre'] ?? 'No hay ciclo activo';

// Cargar preferencias del usuario
$preferencias = $conexion->query("
    SELECT modo_oscuro, tamano_texto, alto_contraste, idioma 
    FROM preferencias_accesibilidad 
    WHERE id_usuario = " . $_SESSION['usuario']['id_usuario']
)->fetch_assoc();

$tema_actual = $preferencias['modo_oscuro'] ? 'oscuro' : 'claro';
$idioma_actual = $preferencias['idioma'] ?? 'es';
$tamano_actual = $preferencias['tamano_texto'] ?? 'normal';
$contraste_actual = $preferencias['alto_contraste'] ?? 0;

$mensaje = $_GET['mensaje'] ?? '';
$tipo = $_GET['tipo'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $idioma_actual === 'es' ? 'es' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Administrador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="styles/configuracion.css">
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
                <h1>Configuración</h1>
                <p>Administra la configuración general de la plataforma</p>
            </div>
            <div class="header-actions">
                <button class="btn-assistant" id="btn-asistente">
                    <i class="fa-solid fa-comment-dots"></i> Chatbot
                </button>
                <div class="icon-bell">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <!-- ========================================== -->
                <!-- BOTÓN DE IDIOMA                           -->
                <!-- ========================================== -->
                <button class="btn-idioma" id="btnIdioma" title="Cambiar idioma">
                    <i class="fa-solid fa-language"></i>
                    <span id="idiomaTexto"><?php echo $idioma_actual === 'es' ? 'ES' : 'EN'; ?></span>
                </button>
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

        <!-- MENSAJES -->
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo; ?>" style="padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; <?php echo ($tipo === 'exito') ? 'background: #dcfce7; color: #166534; border-left: 4px solid #22c55e;' : 'background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626;'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- CONFIGURACIÓN                              -->
        <!-- ========================================== -->
        <div class="config-grid">
            
            <!-- ========================================== -->
            <!-- INFORMACIÓN GENERAL                       -->
            <!-- ========================================== -->
            <div class="config-card">
                <div class="config-header">
                    <i class="fa-solid fa-circle-info"></i>
                    <h3>Información general</h3>
                </div>
                <form method="POST" action="logica/procesar_configuracion.php">
                    <div class="form-group">
                        <label for="nombre_sistema">Nombre del sistema</label>
                        <input type="text" id="nombre_sistema" name="nombre_sistema" value="AULAMOS" disabled>
                        <p class="help-text">El nombre del sistema no se puede modificar desde aquí.</p>
                    </div>

                    <div class="form-group">
                        <label for="ciclo_actual">Ciclo escolar actual</label>
                        <input type="text" id="ciclo_actual" name="ciclo_actual" value="<?php echo htmlspecialchars($ciclo_nombre); ?>" disabled>
                        <p class="help-text">El ciclo activo se gestiona desde "Ciclos escolares".</p>
                    </div>

                    <div class="form-group">
                        <label for="version">Versión de la plataforma</label>
                        <input type="text" id="version" name="version" value="1.0.0" disabled>
                    </div>
                </form>
            </div>

            <!-- ========================================== -->
            <!-- PREFERENCIAS DE LA PLATAFORMA             -->
            <!-- ========================================== -->
            <div class="config-card">
                <div class="config-header">
                    <i class="fa-solid fa-sliders"></i>
                    <h3>Preferencias de la plataforma</h3>
                </div>
                <form method="POST" action="logica/procesar_configuracion.php" id="formConfiguracion">
                    <div class="form-group">
                        <label>Tema por defecto</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="tema" value="claro" <?php echo ($tema_actual === 'claro') ? 'checked' : ''; ?>>
                                <i class="fa-solid fa-sun"></i> Claro
                            </label>
                            <label>
                                <input type="radio" name="tema" value="oscuro" <?php echo ($tema_actual === 'oscuro') ? 'checked' : ''; ?>>
                                <i class="fa-solid fa-moon"></i> Oscuro
                            </label>
                            <label>
                                <input type="radio" name="tema" value="sistema" <?php echo ($tema_actual === 'sistema') ? 'checked' : ''; ?>>
                                <i class="fa-solid fa-desktop"></i> Sistema
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Idioma</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="idioma" value="es" <?php echo ($idioma_actual === 'es') ? 'checked' : ''; ?>>
                                <i class="fa-solid fa-language"></i> Español
                            </label>
                            <label>
                                <input type="radio" name="idioma" value="en" <?php echo ($idioma_actual === 'en') ? 'checked' : ''; ?>>
                                <i class="fa-solid fa-language"></i> Inglés
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tamaño de texto</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="tamano_texto" value="pequeño" <?php echo ($tamano_actual === 'pequeño') ? 'checked' : ''; ?>>
                                <span style="font-size: 12px;">A</span> Pequeño
                            </label>
                            <label>
                                <input type="radio" name="tamano_texto" value="normal" <?php echo ($tamano_actual === 'normal') ? 'checked' : ''; ?>>
                                <span style="font-size: 16px;">A</span> Normal
                            </label>
                            <label>
                                <input type="radio" name="tamano_texto" value="grande" <?php echo ($tamano_actual === 'grande') ? 'checked' : ''; ?>>
                                <span style="font-size: 20px;">A</span> Grande
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Alto contraste</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="alto_contraste" value="0" <?php echo ($contraste_actual == 0) ? 'checked' : ''; ?>>
                                <i class="fa-solid fa-eye"></i> Desactivado
                            </label>
                            <label>
                                <input type="radio" name="alto_contraste" value="1" <?php echo ($contraste_actual == 1) ? 'checked' : ''; ?>>
                                <i class="fa-solid fa-eye-low-vision"></i> Activado
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-guardar">Guardar cambios</button>
                    </div>
                </form>
            </div>

        </div>

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
<script src="js/configuracion.js"></script>
<!-- LECTOR DE PANTALLA -->
<script src="js/lector.js"></script>
</body>
</html>