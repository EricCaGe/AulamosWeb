<?php
$usuario = "Profesora Ana"; 
$rol = "Docente";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Más - Aulamos</title>
    
    <!-- CSS Base -->
    <link rel="stylesheet" href="styles/docente.css">
    <!-- CSS Específico para esta pantalla -->
    <link rel="stylesheet" href="styles/mas.css">
    
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
                <a href="mas.php" class="menu-item active"><i class="fa-solid fa-bars"></i> Mas</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-universal-access"></i> Accesibilidad</a>
                
                <div class="menu-spacer"></div>
                <a href="login.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
            </nav>
            
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content">
            
            <!-- ENCABEZADO -->
            <header class="content-header">
                <div class="welcome-text">
                    <h1>Estudiantes</h1>
                    <p>Gestionar tu lista de estudiantes</p>
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

            <!-- CUADRÍCULA PRINCIPAL (Lista izquierda, Calendario derecha) -->
            <div class="mas-layout">
                
                <!-- COLUMNA IZQUIERDA: LISTA Y FILTROS -->
                <div class="mas-left-col">
                    
                    <!-- Buscador -->
                    <div class="search-bar-container">
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        <input type="text" class="search-input" placeholder="Buscar estudiantes ...">
                    </div>

                    <!-- Píldoras de filtro -->
<div class="filter-pills">
    <button class="pill active" data-filter="Todos">Todos (10)</button>
    <button class="pill" data-filter="1° A">1° A</button>
    <button class="pill" data-filter="1° B">1° B</button>
    <button class="pill" data-filter="2° A">2° A</button>
    <button class="pill" data-filter="2° B">2° B</button>
    <button class="pill" data-filter="3° A">3° A</button>
    <button class="pill" data-filter="3° B">3° B</button>
</div>

                    <!-- Lista de Estudiantes (Cada uno es un enlace a resumen_estudiante.php) -->
                    <div class="student-list">
                        
                        <!-- Ana López -->
                        <a href="resumen_estudiante.php" class="student-row" data-group="1° A">
                            <div class="student-info-left">
                                <i class="fa-regular fa-circle-user avatar-icon"></i>
                                <div>
                                    <h4>Ana López</h4>
                                    <span class="student-group">1° A</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right arrow-icon"></i>
                        </a>

                        <!-- Carlos Martínez -->
                        <a href="resumen_estudiante.php" class="student-row" data-group="1° A">
                            <div class="student-info-left">
                                <i class="fa-regular fa-circle-user avatar-icon"></i>
                                <div>
                                    <h4>Carlos Martínez</h4>
                                    <span class="student-group">1° A</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right arrow-icon"></i>
                        </a>

                        <!-- José Ramírez -->
                        <a href="resumen_estudiante.php" class="student-row" data-group="1° B">
                            <div class="student-info-left">
                                <i class="fa-regular fa-circle-user avatar-icon"></i>
                                <div>
                                    <h4>José Ramírez</h4>
                                    <span class="student-group">1° B</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right arrow-icon"></i>
                        </a>

                        <!-- Valentina Ruiz -->
                        <a href="resumen_estudiante.php" class="student-row" data-group="1° B">
                            <div class="student-info-left">
                                <i class="fa-regular fa-circle-user avatar-icon"></i>
                                <div>
                                    <h4>Valentina Ruiz</h4>
                                    <span class="student-group">1° B</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right arrow-icon"></i>
                        </a>

                        <!-- Mateo Castro -->
                        <a href="resumen_estudiante.php" class="student-row" data-group="2° A">
                            <div class="student-info-left">
                                <i class="fa-regular fa-circle-user avatar-icon"></i>
                                <div>
                                    <h4>Mateo Castro</h4>
                                    <span class="student-group">2° A</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right arrow-icon"></i>
                        </a>

                        <!-- Sofía Gómez -->
                        <a href="resumen_estudiante.php" class="student-row" data-group="2° A">
                            <div class="student-info-left">
                                <i class="fa-regular fa-circle-user avatar-icon"></i>
                                <div>
                                    <h4>Sofía Gómez</h4>
                                    <span class="student-group">2° A</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right arrow-icon"></i>
                        </a>

                        <!-- Eduardo Sanchez -->
                        <a href="resumen_estudiante.php" class="student-row" data-group="2° B">
                            <div class="student-info-left">
                                <i class="fa-regular fa-circle-user avatar-icon"></i>
                                <div>
                                    <h4>Eduardo Sanchez</h4>
                                    <span class="student-group">2° B</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right arrow-icon"></i>
                        </a>

                        <!-- Luis Fernando -->
                        <a href="resumen_estudiante.php" class="student-row" data-group="2° B">
                            <div class="student-info-left">
                                <i class="fa-regular fa-circle-user avatar-icon"></i>
                                <div>
                                    <h4>Luis Fernando</h4>
                                    <span class="student-group">2° B</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right arrow-icon"></i>
                        </a>

                        <!-- Camila Torres -->
                        <a href="resumen_estudiante.php" class="student-row" data-group="3° A">
                            <div class="student-info-left">
                                <i class="fa-regular fa-circle-user avatar-icon"></i>
                                <div>
                                    <h4>Camila Torres</h4>
                                    <span class="student-group">3° A</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right arrow-icon"></i>
                        </a>

                        <!-- Diego Herrera -->
                        <a href="resumen_estudiante.php" class="student-row" data-group="3° B">
                            <div class="student-info-left">
                                <i class="fa-regular fa-circle-user avatar-icon"></i>
                                <div>
                                    <h4>Diego Herrera</h4>
                                    <span class="student-group">3° B</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right arrow-icon"></i>
                        </a>

                    </div>
                </div>

                <!-- Calendario -->
                 <div class="right-column">
<aside class="calendar-container">
    <!-- Cabecera y Navegación -->
    <div class="calendar-header">
        <div class="nav-left">
            <button id="prev-year" class="nav-btn" title="Año anterior">&laquo;</button>
            <button id="prev-month" class="nav-btn" title="Mes anterior">&lsaquo;</button>
        </div>
        
        <h2 id="month-year-title">MES AÑO</h2>
        
        <div class="nav-right">
            <button id="next-month" class="nav-btn" title="Mes siguiente">&rsaquo;</button>
            <button id="next-year" class="nav-btn" title="Año siguiente">&raquo;</button>
        </div>
    </div>

    <!-- Días de la semana -->
    <div class="calendar-weekdays">
        <div class="weekday">Do</div>
        <div class="weekday">Lu</div>
        <div class="weekday">Ma</div>
        <div class="weekday">Mi</div>
        <div class="weekday">Ju</div>
        <div class="weekday">Vi</div>
        <div class="weekday">Sá</div>
    </div>

    <!-- Contenedor dinámico de los días -->
    <div id="calendar-days" class="calendar-days-grid">
        <!-- JavaScript inyectará los días aquí -->
    </div>
</aside>

            </div>
         <!-- BARRA DE ACCESIBILIDAD INFERIOR -->
            <footer class="accessibility-bar">
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
    <script src="jss/mas.js"></script>
</body>
</html>