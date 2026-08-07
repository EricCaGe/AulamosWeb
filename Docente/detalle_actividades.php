<?php
$usuario = "Profesora Ana"; 
$rol = "Docente";
$nombre_alumno = "Ana López";
$grupo_alumno = "1° A";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Actividades - Aulamos</title>
    
    <!-- CSS Base -->
    <link rel="stylesheet" href="styles/docente.css">
    <!-- CSS Específico para esta pantalla -->
    <link rel="stylesheet" href="styles/detalle.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="dashboard-container">
        
        <!-- BARRA LATERAL -->
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../img/logo_g.png" alt="Búho Aulamos" class="logo-img">
                
            </div>
            
            <nav class="menu">
                <a href="docente_dashboard.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="crear_recurso.php" class="menu-item"><i class="fa-solid fa-medal"></i> Crear Recurso</a>
                <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
                <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                
                <!-- "Mas" Activo -->
                <a href="pasarlista.php" class="menu-item"><i class="fa-solid fa-bars"></i> Pasar Lista</a>
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
                        <a href="resumen_estudiante.php" class="back-arrow"><i class="fa-solid fa-arrow-left"></i></a> 
                        Detalle de actividades
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

            <!-- PERFIL DEL ESTUDIANTE -->
            <div class="student-profile-header">
                <i class="fa-regular fa-circle-user large-avatar"></i>
                <div class="student-details">
                    <h2><?php echo $nombre_alumno; ?></h2>
                    <span class="badge-grade"><?php echo $grupo_alumno; ?></span>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="filters-container">
                <button class="filter-btn active" data-filter="todos">Todos (12)</button>
                <button class="filter-btn" data-filter="completada">Completadas (9)</button>
                <button class="filter-btn" data-filter="pendiente">Pendientes (2)</button>
                <button class="filter-btn" data-filter="atrasada">Atrasadas (1)</button>
            </div>

            <!-- LISTA DE ACTIVIDADES -->
            <div class="activities-list">
                
                <!-- Mapa conceptual -->
                <a href="actividad_Mapa_conceptual.php" class="activity-row" style="text-decoration: none; color: inherit;" data-status="completada">
                    <div class="activity-left">
                        <div class="icon-box purple"><i class="fa-solid fa-file-lines"></i></div>
                        <div class="activity-text">
                            <h4>Mapa conceptual</h4>
                            <span>Ciencias Naturales</span>
                        </div>
                    </div>
                    <div class="activity-center">Entrega: 12/05/2026</div>
                    <div class="activity-right">
                        <span class="status-badge completada">Completada</span>
                    </div>
                </a>

                <!-- Ejercicio de matemáticas -->
                <a href="actividad_Ejercicio_de_matematicas.php" class="activity-row" style="text-decoration: none; color: inherit;" data-status="completada">
                    <div class="activity-left">
                        <div class="icon-box red"><i class="fa-solid fa-file-pdf"></i></div>
                        <div class="activity-text">
                            <h4>Ejercicio de matemáticas</h4>
                            <span>Matemáticas</span>
                        </div>
                    </div>
                    <div class="activity-center">Entrega: 10/05/2026</div>
                    <div class="activity-right">
                        <span class="status-badge completada">Completada</span>
                    </div>
                </a>

                <!-- Lectura y resumen -->
                <a href="actividad_Lectura_y_resumen.php" class="activity-row" style="text-decoration: none; color: inherit;" data-status="completada">
                    <div class="activity-left">
                        <div class="icon-box blue"><i class="fa-solid fa-file-lines"></i></div>
                        <div class="activity-text">
                            <h4>Lectura y resumen</h4>
                            <span>Lengua y Literatura</span>
                        </div>
                    </div>
                    <div class="activity-center">Entrega: 08/05/2026</div>
                    <div class="activity-right">
                        <span class="status-badge completada">Completada</span>
                    </div>
                </a>

                <!-- Examen de historia -->
                <a href="actividad_Examen_de_historia.php" class="activity-row" style="text-decoration: none; color: inherit;" data-status="pendiente">
                    <div class="activity-left">
                        <div class="icon-box green"><i class="fa-solid fa-clipboard-check"></i></div>
                        <div class="activity-text">
                            <h4>Examen de historia</h4>
                            <span>Historia</span>
                        </div>
                    </div>
                    <div class="activity-center">Entrega: 15/05/2026</div>
                    <div class="activity-right">
                        <span class="status-badge pendiente">Pendiente</span>
                    </div>
                </a>

                <!-- Presentación del tema -->
                <a href="actividad_Presentacion_del_tema.php" class="activity-row" style="text-decoration: none; color: inherit;" data-status="pendiente">
                    <div class="activity-left">
                        <div class="icon-box light-purple"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div class="activity-text">
                            <h4>Presentación del tema</h4>
                            <span>Tecnología</span>
                        </div>
                    </div>
                    <div class="activity-center">Entrega: 05/05/2026</div>
                    <div class="activity-right">
                        <span class="status-badge pendiente">Pendiente</span>
                    </div>
                </a>

                <!-- Investigación: Ecosistemas -->
                <a href="actividad_Investigacion_Ecosistemas.php" class="activity-row" style="text-decoration: none; color: inherit;" data-status="atrasada">
                    <div class="activity-left">
                        <div class="icon-box light-purple"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div class="activity-text">
                            <h4>investigación: Ecosistemas</h4>
                            <span>Ciencias Naturales</span>
                        </div>
                    </div>
                    <div class="activity-center">Entrega: 12/05/2026</div>
                    <div class="activity-right">
                        <span class="status-badge atrasada">Atrasada</span>
                    </div>
                </a>

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

    <!-- SCRIPT PARA FILTROS INTERACTIVOS -->
    <script src="jss/detalle_actividades.js"></script>
    <script src="jss/docente_dashboard.js"></script>
</body>
</html>