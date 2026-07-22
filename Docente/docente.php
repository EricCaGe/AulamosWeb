<?php
// Simulación de datos dinámicos del docente
$usuario = "Profesora Ana"; 
$rol = "Docente";
$clases_activas = 6;
$actividades_pendientes = 12;
$evaluaciones_pendientes = 3;
$estudiantes_total = 128;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aulamos - Dashboard Docente</title>
    
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="dashboard-container">
        
        <!-- BARRA LATERAL -->
        <aside class="sidebar">
            <div class="logo-section">
                <img src="https://placehold.co/50x50/ffffff/3b71f3?text=🦉" alt="Búho Aulamos" class="logo-img">
                <div>
                    <h2>AULAMOS</h2>
                    <p>Aprendemos juntos</p>
                </div>
            </div>
            
            <nav class="menu">
                <a href="#" class="menu-item active"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-medal"></i> Crear Curso</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                
                <div class="menu-spacer"></div>
                
                <a href="#" class="menu-item"><i class="fa-solid fa-gear"></i> Configuración</a>
            </nav>
            
            <button class="btn-accessibility-main"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content">
            
            <!-- ENCABEZADO -->
            <header class="content-header">
                <div class="welcome-text">
                    <h1>Dashboard Docente</h1>
                    <h2>¡Hola <?php echo $usuario; ?>! 👋</h2>
                    <p>Bienvenida a tu espacio docente.</p>
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

            <!-- GRID PRINCIPAL (2 COLUMNAS) -->
            <div class="main-grid">
                
                <!-- COLUMNA IZQUIERDA (Ancha) -->
                <div class="left-column">
                    
                    <!-- Resumen del día -->
                    <section class="section-container">
                        <h3 class="section-title">Resumen del día</h3>
                        <div class="stats-grid">
                            <div class="stat-box bg-purple-light">
                                <div class="stat-icon-top"><i class="fa-solid fa-chalkboard-user"></i></div>
                                <div class="stat-content">
                                    <p class="stat-label">Clases activas</p>
                                    <h4 class="stat-number"><?php echo $clases_activas; ?></h4>
                                    <p class="stat-sub">Hoy</p>
                                </div>
                            </div>
                            <div class="stat-box bg-green-light">
                                <div class="stat-icon-top text-green"><i class="fa-regular fa-square-check"></i></div>
                                <div class="stat-content">
                                    <p class="stat-label">Actividades pendientes</p>
                                    <h4 class="stat-number"><?php echo $actividades_pendientes; ?></h4>
                                    <p class="stat-sub">Por revisar</p>
                                </div>
                            </div>
                            <div class="stat-box bg-yellow-light">
                                <div class="stat-icon-top text-yellow"><i class="fa-regular fa-file-lines"></i></div>
                                <div class="stat-content">
                                    <p class="stat-label">Evaluaciones pendientes</p>
                                    <h4 class="stat-number"><?php echo $evaluaciones_pendientes; ?></h4>
                                    <p class="stat-sub">Por calificar</p>
                                </div>
                            </div>
                            <div class="stat-box bg-blue-light">
                                <div class="stat-icon-top text-blue"><i class="fa-solid fa-user-group"></i></div>
                                <div class="stat-content">
                                    <p class="stat-label">Estudiantes en total</p>
                                    <h4 class="stat-number"><?php echo $estudiantes_total; ?></h4>
                                    <p class="stat-sub">En la plataforma</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Accesos rápidos -->
                    <section class="section-container">
                        <h3 class="section-title">Accesos rápidos</h3>
                        <div class="quick-access-grid">
                            <button class="quick-btn bg-purple-solid">
                                <i class="fa-solid fa-arrow-up-from-bracket"></i>
                                <span>Crear curso</span>
                            </button>
                            <button class="quick-btn bg-green-solid">
                                <i class="fa-solid fa-clipboard-check"></i>
                                <span>Crear actividad</span>
                            </button>
                            <button class="quick-btn bg-yellow-solid text-dark-yellow">
                                <i class="fa-solid fa-clipboard-list"></i>
                                <span>Crear evaluación</span>
                            </button>
                            <button class="quick-btn bg-blue-solid">
                                <i class="fa-solid fa-users"></i>
                                <span>Ver estudiantes</span>
                            </button>
                            <button class="quick-btn bg-gray-solid">
                                <i class="fa-solid fa-chart-column"></i>
                                <span>Reportes</span>
                            </button>
                        </div>
                    </section>

                    <!-- Contenido reciente -->
                    <section class="section-container border-container">
                        <div class="section-header">
                            <h3 class="section-title">Contenido recien</h3>
                            <a href="#" class="link-blue">Ver todo</a>
                        </div>
                        
                        <div class="content-list">
                            <div class="list-item">
                                <div class="item-main">
                                    <div class="icon-box bg-blue-icon"><i class="fa-solid fa-play"></i></div>
                                    <div>
                                        <h4 class="item-title">Ecosistema acuaticos</h4>
                                        <p class="item-desc">Video • Publicado hoy</p>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <span class="badge badge-publicado">Publicado</span>
                                    <i class="fa-solid fa-ellipsis-vertical menu-dots"></i>
                                </div>
                            </div>

                            <div class="list-item">
                                <div class="item-main">
                                    <div class="icon-box bg-red-icon"><i class="fa-regular fa-file-pdf"></i></div>
                                    <div>
                                        <h4 class="item-title">Guía de lectura: La célula</h4>
                                        <p class="item-desc">PDF • Publicado ayer</p>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <span class="badge badge-publicado">Publicado</span>
                                    <i class="fa-solid fa-ellipsis-vertical menu-dots"></i>
                                </div>
                            </div>

                            <div class="list-item">
                                <div class="item-main">
                                    <div class="icon-box bg-blue-doc-icon"><i class="fa-regular fa-file-lines"></i></div>
                                    <div>
                                        <h4 class="item-title">Instrucciones proyecto final</h4>
                                        <p class="item-desc">Documento • Hace 2 días</p>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <span class="badge badge-publicado">Publicado</span>
                                    <i class="fa-solid fa-ellipsis-vertical menu-dots"></i>
                                </div>
                            </div>

                            <div class="list-item">
                                <div class="item-main">
                                    <div class="icon-box bg-green-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                                    <div>
                                        <h4 class="item-title">Actividad: Lectura comprensiva</h4>
                                        <p class="item-desc">Actividad • Hace 2 días</p>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <span class="badge badge-pendiente">Pendiente</span>
                                    <i class="fa-solid fa-ellipsis-vertical menu-dots"></i>
                                </div>
                            </div>

                            <div class="list-item">
                                <div class="item-main">
                                    <div class="icon-box bg-purple-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                                    <div>
                                        <h4 class="item-title">Evaluación: Ecosistemas</h4>
                                        <p class="item-desc">Evaluacion • Hace 5 días</p>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <span class="badge badge-pendiente">Pendiente</span>
                                    <i class="fa-solid fa-ellipsis-vertical menu-dots"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-15">
                            <a href="#" class="link-blue view-all-link">Ver todo mi contenido</a>
                        </div>
                    </section>

                </div>

                <!-- COLUMNA DERECHA (Estrecha) -->
                <div class="right-column">
                    
                    <!-- Calendario -->
                    <aside class="calendar-widget border-container">
                        <div class="calendar-header">
                            <h3 class="section-title">Calendario</h3>
                            <a href="#" class="link-blue"><i class="fa-regular fa-calendar-days"></i> Ver calendario</a>
                        </div>
                        <div class="calendar-month">
                            <span class="month-title">Mayo 2024</span>
                            <div class="calendar-nav">
                                <i class="fa-solid fa-chevron-left"></i>
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                        </div>
                        <div class="calendar-grid">
                            <div class="cal-day-header">LUN</div><div class="cal-day-header">MAR</div><div class="cal-day-header">MIE</div><div class="cal-day-header">JUE</div><div class="cal-day-header">VIE</div><div class="cal-day-header">SAB</div><div class="cal-day-header">DOM</div>
                            <div class="cal-day disabled">29</div><div class="cal-day disabled">30</div><div class="cal-day dot">1</div><div class="cal-day">2</div><div class="cal-day">3</div><div class="cal-day">4</div><div class="cal-day">5</div>
                            <div class="cal-day">6</div><div class="cal-day">7</div><div class="cal-day">8</div><div class="cal-day dot">9</div><div class="cal-day">10</div><div class="cal-day">11</div><div class="cal-day">12</div>
                            <div class="cal-day">13</div><div class="cal-day">14</div><div class="cal-day">15</div><div class="cal-day dot">16</div><div class="cal-day">17</div><div class="cal-day">18</div><div class="cal-day">19</div>
                            <div class="cal-day active">20</div><div class="cal-day dot">21</div><div class="cal-day">22</div><div class="cal-day">23</div><div class="cal-day double-dot">24</div><div class="cal-day">25</div><div class="cal-day">26</div>
                            <div class="cal-day">27</div><div class="cal-day">28</div><div class="cal-day">29</div><div class="cal-day">30</div><div class="cal-day">31</div><div class="cal-day disabled">1</div><div class="cal-day disabled">2</div>
                        </div>
                    </aside>

                    <!-- Próximas actividades -->
                    <aside class="upcoming-activities border-container">
                        <h3 class="section-title">Próximas actividades</h3>
                        
                        <div class="activity-list">
                            <div class="activity-item">
                                <div class="act-icon text-green"><i class="fa-solid fa-clipboard-check"></i></div>
                                <div class="act-details">
                                    <h5>Revisión de actividades</h5>
                                    <p>21 de mayo, 2026 • 10:00 AM</p>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="act-icon text-yellow"><i class="fa-solid fa-clipboard-list"></i></div>
                                <div class="act-details">
                                    <h5>Evaluación: Ecosistemas</h5>
                                    <p>25 de mayo, 2026 • 08:00 AM</p>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="act-icon text-blue"><i class="fa-regular fa-file-lines"></i></div>
                                <div class="act-details">
                                    <h5>Entrega proyecto final</h5>
                                    <p>29 de mayo, 2026 • 11:59 PM</p>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-15">
                            <a href="#" class="link-blue view-all-link">Ver todas mis actividades</a>
                        </div>
                    </aside>

                </div>
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

    <script src="jss/docente.js"></script>
</body>
</html>