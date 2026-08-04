<?php
// Variables dinámicas
$usuario = "Profesora Ana"; 
$rol = "Docente";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Aulamos</title>
    
    <!-- CSS Base -->
    <link rel="stylesheet" href="styles/docente.css">
    <!-- CSS Específico para esta pantalla -->
    <link rel="stylesheet" href="styles/reportes.css">
    
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
                <a href="crear_evaluacio.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
                <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item active"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                <a href="mas.php" class="menu-item"><i class="fa-solid fa-bars"></i> Mas</a>
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
                    <h1>Reportes</h1>
                    <p>Analiza el progreso de tus clases</p>
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

            <!-- FILTROS SUPERIORES -->
            <div class="reports-filter-bar">
                <div class="filter-group">
                    <label>Seleccionar materia</label>
                    <select class="custom-select">
                        <option>Todas las materias</option>
                        <option>Matemáticas</option>
                        <option>Español</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Periodo</label>
                    <select class="custom-select">
                        <option>Por mes</option>
                        <option>Por semana</option>
                        <option>Por semestre</option>
                    </select>
                </div>
            </div>

            <!-- CUADRÍCULA PRINCIPAL DE REPORTES -->
            <div class="reports-main-grid">
                
                <!-- COLUMNA IZQUIERDA: RESUMEN GENERAL -->
                <div class="reports-left">
                    <h3 class="section-title">Resumen general</h3>
                    
                    <div class="stats-grid">
                        
                        <!-- Tarjeta 1: Promedio General -->
                        <div class="stat-card">
                            <p class="stat-title">Promedio general</p>
                            <div class="stat-value-box">
                                <span class="stat-big">8.6</span>
                                <span class="stat-small">/ 10</span>
                            </div>
                            <!-- Gráfica de línea simulada con SVG -->
                            <div class="mini-chart-container">
                                <svg viewBox="0 0 100 30" class="line-chart">
                                    <path d="M0,25 L20,20 L40,25 L60,10 L80,18 L100,5" fill="none" stroke="#3b71f3" stroke-width="2"/>
                                    <circle cx="20" cy="20" r="2" fill="#3b71f3"/>
                                    <circle cx="40" cy="25" r="2" fill="#3b71f3"/>
                                    <circle cx="60" cy="10" r="2" fill="#3b71f3"/>
                                    <circle cx="80" cy="18" r="2" fill="#3b71f3"/>
                                    <circle cx="100" cy="5" r="2" fill="#3b71f3"/>
                                    <!-- Sombra degradada -->
                                    <path d="M0,25 L20,20 L40,25 L60,10 L80,18 L100,5 L100,30 L0,30 Z" fill="rgba(59, 113, 243, 0.1)"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Tarjeta 2: Estudiantes Aprobados -->
                        <div class="stat-card center-content">
                            <p class="stat-title">Estudiantes aprobados</p>
                            <div class="donut-chart-container">
                                <div class="donut-chart">
                                    <div class="donut-inner">
                                        <span>92%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta 3: Actividades Entregadas -->
                        <div class="stat-card">
                            <p class="stat-title">Actividades entregadas</p>
                            <span class="stat-big mt-10 d-block">85%</span>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: 85%;"></div>
                            </div>
                        </div>

                        <!-- Tarjeta 4: Evaluaciones Realizadas -->
                        <div class="stat-card">
                            <p class="stat-title">Evaluaciones realizadas</p>
                            <span class="stat-big mt-10 d-block">3</span>
                            <p class="stat-small-text mt-5">este mes</p>
                        </div>

                    </div>
                </div>

                <!-- COLUMNA DERECHA: REPORTES DISPONIBLES -->
                <div class="reports-right">
                    <h3 class="section-title">Reportes disponibles</h3>
                    
                    <div class="reports-list">
                        
                        <!-- Opción 1 -->
                        <a href="#" class="report-list-item">
                            <div class="report-icon-box purple-box">
                                <i class="fa-solid fa-chart-simple"></i>
                            </div>
                            <span class="report-name">Rendimiento por actividad</span>
                            <i class="fa-solid fa-chevron-right report-arrow"></i>
                        </a>

                        <!-- Opción 2 -->
                        <a href="#" class="report-list-item">
                            <div class="report-icon-box orange-box">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                            <span class="report-name">Rendimiento por evaluación</span>
                            <i class="fa-solid fa-chevron-right report-arrow"></i>
                        </a>

                        <!-- Opción 3 -->
                        <a href="#" class="report-list-item">
                            <div class="report-icon-box green-box">
                                <i class="fa-solid fa-person-chalkboard"></i>
                            </div>
                            <span class="report-name">Asistencia y participación</span>
                            <i class="fa-solid fa-chevron-right report-arrow"></i>
                        </a>

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


    <!-- Archivos JS -->
    <script src="jss/docente_dashboard.js"></script>
</body>
</html>