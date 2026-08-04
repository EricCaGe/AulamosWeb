<?php
$usuario = "Profesora Ana"; 
$rol = "Docente";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actividad Individual - Aulamos</title>
    
    <!-- CSS Base -->
    <link rel="stylesheet" href="styles/docente.css">
    <!-- CSS Específico para esta pantalla -->
    <link rel="stylesheet" href="styles/actividad_Mapa_conceptual.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="dashboard-container">
        
        <!-- BARRA LATERAL -->
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../img/logo_g.png" alt="Búho Aulamos" class="logo-img">
                <div>
                    <h2>AULAMOS</h2>
                    <p>Aprendemos juntos</p>
                </div>
            </div>
            
            <nav class="menu">
                <a href="docente_dashboard.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="crear_curso.php" class="menu-item"><i class="fa-solid fa-medal"></i> Crear Curso</a>
                <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
                <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                
                <!-- "Mas" Activo -->
                <a href="mas.php" class="menu-item active"><i class="fa-solid fa-bars"></i> Mas</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-universal-access"></i> Accesibilidad</a>
                
                <div class="menu-spacer"></div>
                <a href="login.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
            </nav>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content">
            
            <!-- ENCABEZADO SUPERIOR CON FLECHA DE REGRESO -->
            <header class="content-header header-with-back">
                <div class="welcome-text">
                    <h1>
                        <a href="detalle_actividades.php" class="back-arrow"><i class="fa-solid fa-arrow-left"></i></a> 
                        Mapa conceptual
                    </h1>
                </div>
                <div class="header-actions">
                    <button class="btn-assistant" id="btn-asistente">Asistente Virtual <span class="robot-icon">🤖</span></button>
                    <div class="icon-bell-container">
                        <i class="fa-regular fa-bell"></i>
                    </div>
                    <div class="user-profile">
                        <img src="https://placehold.co/40x40/ff7675/white?text=👩" alt="Avatar Docente" class="avatar">
                        <div class="user-info">
                            <span class="user-name"><?php echo $usuario; ?>!</span>
                            <span class="user-role"><?php echo $rol; ?></span>
                        </div>
                        <i class="fa-solid fa-chevron-down drop-icon"></i>
                    </div>
                </div>
            </header>

            <!-- CONTENIDO DE LA ACTIVIDAD -->
            <div class="actividad-layout">
                
                <!-- COLUMNA IZQUIERDA: Detalles y Entrega -->
                <div class="actividad-left">
                    
                    <div class="materia-info">
                        <div class="icon-materia">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <div class="text-materia">
                            <h4>Ciencias Naturales</h4>
                            <span>Entrega: 12/05/2026</span>
                        </div>
                    </div>

                    <div class="estado-badge-container">
                        <span class="status-badge completada">Completada</span>
                    </div>

                    <div class="seccion-bloque">
                        <h3>Descripción</h3>
                        <p>Realiza un mapa conceptual sobre los ecosistemas acuaticos y sus caracteristicas principales</p>
                    </div>

                    <div class="seccion-bloque">
                        <h3>Entrega del estudiante</h3>
                        
                        <div class="archivo-entrega-card">
                            <div class="archivo-izq">
                                <i class="fa-solid fa-file-pdf icon-pdf-dark"></i>
                                <div class="archivo-textos">
                                    <strong>Mapa_conceptual_Ana.pdf</strong>
                                    <span>Entregado el 10/05/2026 14:30</span>
                                </div>
                            </div>
                            <button class="btn-descargar-archivo">
                                <i class="fa-solid fa-circle-down"></i>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- COLUMNA DERECHA: Calificación y Comentarios -->
                <div class="actividad-right">
                    
                    <div class="seccion-bloque">
                        <h3>Calificación</h3>
                        <div class="calificacion-box">
                            9.5/10
                        </div>
                    </div>

                    <div class="seccion-bloque">
                        <h3>Comentarios</h3>
                        <div class="comentario-burbuja">
                            Excelente trabajo, Ana. Muy bien organizado y completo 👏
                        </div>
                    </div>

                </div>

            </div>

            <!-- BARRA ACCESIBILIDAD -->
            <footer class="accessibility-bar" style="margin-top: 30px;">
                <div class="acc-info">
                    <div class="acc-icon-box">
                        <i class="fa-solid fa-universal-access acc-icon-main"></i>
                    </div>
                    <div>
                        <strong>Accesibilidad siempre disponible</strong>
                        <p>Personaliza tu experiencia en cualquier momento.</p>
                    </div>
                </div>
                <div class="acc-options">
                    <button class="acc-opt-btn" id="btn-contrast"><i class="fa-solid fa-eye"></i><span>Alto contraste</span></button>
                    <button class="acc-opt-btn" id="btn-darkmode"><i class="fa-solid fa-moon"></i><span>Modo oscuro</span></button>
                    <button class="acc-opt-btn" id="btn-text-size"><span class="font-icon">Aa</span><span>Texto grande</span></button>
                    <button class="acc-opt-btn"><i class="fa-solid fa-volume-high"></i><span>Leer pantalla</span></button>
                    <button class="acc-opt-btn"><i class="fa-solid fa-closed-captioning"></i><span>Subtítulos</span></button>
                    <button class="acc-opt-btn"><i class="fa-solid fa-keyboard"></i><span>Navegación<br>por teclado</span></button>
                </div>
                <button class="btn-open-config">Abrir configuración</button>
            </footer>

        </main>
    </div>
    <script src="jss/docente_dashboard.js"></script>
</body>
</html>