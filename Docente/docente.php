<?php
// Enrutador básico de vistas
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

$allowed_pages = [
    'dashboard' => 'dashboard.php',
    'crear_curso' => 'crear_curso.php',
    'crear_actividad' => 'crear_actividad.php',
    'crear_evaluacion' => 'crear_evaluacio.php',
    'ver_estudiantes' => 'ver_estudiantes.php',
    'reporte' => 'reporte.php'
];

$content_file = isset($allowed_pages[$page]) ? $allowed_pages[$page] : 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aulamos - Panel Docente</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="styles/docente-layout.css">
</head>
<body>

    <div class="layout-grid">
        
        <aside class="layout-sidebar" aria-label="Menú de navegación">
            <div class="sidebar-top">
                <div class="brand-container">
                    <img src="../img/logo-buho.png" alt="Logotipo Aulamos" class="brand-logo">
                    <div class="brand-meta">
                        <h2>AULAMOS</h2>
                        <span>Aprendemos juntos</span>
                    </div>
                </div>

                <nav class="sidebar-nav">
                    <a href="docente.php?page=dashboard" class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-house"></i> Dashboard
                    </a>
                    <a href="docente.php?page=crear_curso" class="nav-link <?php echo $page === 'crear_curso' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-graduation-cap"></i> Crear Curso
                    </a>
                    <a href="docente.php?page=crear_actividad" class="nav-link <?php echo $page === 'crear_actividad' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-file-signature"></i> Crear Actividad
                    </a>
                    <a href="docente.php?page=crear_evaluacion" class="nav-link <?php echo $page === 'crear_evaluacion' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-book-open"></i> Crear Evaluación
                    </a>
                    <a href="docente.php?page=ver_estudiantes" class="nav-link <?php echo $page === 'ver_estudiantes' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-user-group"></i> Ver Estudiantes
                    </a>
                    <a href="docente.php?page=reporte" class="nav-link <?php echo $page === 'reporte' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-chart-line"></i> Reportes
                    </a>
                </nav>
            </div>

            <div class="sidebar-bottom">
                <a href="#configuracion" class="nav-link">
                    <i class="fa-solid fa-gear"></i> Configuración
                </a>
               
            </div>
        </aside>

        <div class="workspace-container">
            
            <header class="workspace-header">
                <div class="header-welcome">
                    <h1>Dashboard Docente</h1>
                    <p>¡Hola Profesora Ana! 👋 <span>Bienvenida a tu espacio docente.</span></p>
                </div>
                
                <div class="header-widgets">
                    <button class="btn-assistant" id="btnOpenAssistant">
                        <span>Asistente Virtual</span>
                        <img src="../img/asistente-bot.png" alt="Icono Asistente" class="assistant-icon">
                    </button>
                    
                    <button class="btn-notification" aria-label="Notificaciones">
                        <i class="fa-regular fa-bell"></i>
                    </button>
                    
                    <div class="profile-widget">
                        <img src="../img/profesora-ana.png" alt="Avatar" class="profile-avatar">
                        <div class="profile-meta">
                            <span class="profile-name">Profesora Ana!</span>
                            <span class="profile-role">Docente <i class="fa-solid fa-chevron-down"></i></span>
                        </div>
                    </div>
                </div>
            </header>

            <main class="workspace-content">
                <?php 
                    if (file_exists($content_file)) {
                        include $content_file;
                    } else {
                        echo "<div class='error-view'><h2>Próximamente</h2><p>Esta sección está en desarrollo.</p></div>";
                    }
                ?>
            </main>

            <footer class="accessibility-bar">
                <div class="acc-info">
                    <i class="fa-solid fa-universal-access"></i>
                    <div>
                        <strong>Accesibilidad siempre disponible</strong>
                        <span>Personaliza tu experiencia en cualquier momento.</span>
                    </div>
                </div>
                <div class="acc-controls">
                    <button class="acc-option-btn"><i class="fa-regular fa-eye"></i> Alto contraste</button>
                    <button class="acc-option-btn"><i class="fa-regular fa-moon"></i> Modo oscuro</button>
                    
                    <div class="acc-slider-container">
                        <i class="fa-solid fa-text-height"></i>
                        <span>Letra:</span>
                        <input type="range" id="fontSizeSlider" min="13" max="21" value="16" step="1" aria-label="Ajustar tamaño de letra">
                        <span id="fontSizeVal">16px</span>
                    </div>

                    <button class="acc-option-btn"><i class="fa-solid fa-volume-high"></i> Leer pantalla</button>
                    <button class="acc-option-btn"><i class="fa-regular fa-closed-captioning"></i> Subtítulos</button>
                    <button class="acc-option-btn"><i class="fa-solid fa-keyboard"></i> Navegación por teclado</button>
                    
                </div>
            </footer>

        </div>
    </div>

    <div class="chatbot-container" id="chatbot">
        <div class="chatbot-header">
            <div class="chatbot-title">
                <img src="../img/asistente-bot.png" alt="Bot Icon" style="height:24px;">
                <span>Asistente Aulamos</span>
            </div>
            <button class="btn-close-chat" id="btnCloseChat" style="background:none; border:none; color:white; font-size:1.2rem; cursor:pointer;">&times;</button>
        </div>
        <div class="chatbot-body">
            <div class="msg bot">¡Hola! Soy tu asistente virtual. ¿En qué te puedo ayudar hoy?</div>
        </div>
        <div class="chatbot-footer">
            <input type="text" placeholder="Escribe tu mensaje aquí...">
            <button class="btn-send"><i class="fa-regular fa-paper-plane"></i></button>
        </div>
    </div>

    <script src="js/docente.js" defer></script>
</body>
</html>