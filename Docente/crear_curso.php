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
    <title>Crear Curso - Aulamos</title>
    
    <!-- 1. CSS Base (Diseño general y colores) -->
    <link rel="stylesheet" href="styles/docente.css">
    
    <!-- 2. CSS Específico (Diseño del formulario de esta página) -->
    <link rel="stylesheet" href="styles/curso.css">
    
    <!-- 3. Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="dashboard-container">
        
        <!-- =====================================
             BARRA LATERAL (RECICLADA)
        ====================================== -->
        <aside class="sidebar">
            <div class="logo-section">
                <img src="https://placehold.co/50x50/ffffff/3b71f3?text=🦉" alt="Búho Aulamos" class="logo-img">
                <div>
                    <h2>AULAMOS</h2>
                    <p>Aprendemos juntos</p>
                </div>
            </div>
            
            <nav class="menu">
                
                <a href="docente.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="crear_curso.php" class="menu-item active"><i class="fa-solid fa-medal"></i> Crear Curso</a>
                <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                
                <div class="menu-spacer"></div>
                
                <a href="#" class="menu-item"><i class="fa-solid fa-gear"></i> Configuración</a>
            </nav>
            
            <button class="btn-accessibility-main"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content">
            
            <!-- ENCABEZADO MODIFICADO -->
            <header class="content-header">
                <div class="welcome-text">
                    <h1>Crear curso</h1>
                    <p>Comparte material con tus estudiantes</p>
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

            <!-- =====================================
                 NUEVO RELLENO (FORMULARIO)
            ====================================== -->
            <div class="main-grid">
                
                <!-- COLUMNA IZQUIERDA -->
                <div class="left-column">
                    
                    <!-- 1. Tipo de curso -->
                    <section class="section-container">
                        <h3 class="section-title">Tipo de curso</h3>
                        <div class="course-types-grid">
                            <button class="type-card">
                                <div class="type-icon text-purple"><i class="fa-solid fa-play"></i></div>
                                <h4>Video</h4>
                                <p>Sube un video</p>
                            </button>
                            <button class="type-card">
                                <div class="type-icon text-red"><i class="fa-regular fa-file-pdf"></i></div>
                                <h4>PDF</h4>
                                <p>Sube un archivo PDF</p>
                            </button>
                            <button class="type-card">
                                <div class="type-icon text-blue"><i class="fa-regular fa-file-lines"></i></div>
                                <h4>Documento</h4>
                                <p>Sube un documento</p>
                            </button>
                        </div>
                    </section>

                    <!-- 2. Información del curso -->
                    <section class="section-container border-container">
                        <h3 class="section-title">Información el curso</h3>
                        
                        <form class="course-form">
                            <div class="form-group">
                                <label>Titulo del curso</label>
                                <input type="text" placeholder="Ej. La fotosintesis">
                            </div>

                            <div class="form-group">
                                <label>Descripción <span class="text-muted">(opcional)</span></label>
                                <input type="text" placeholder="Describe brevemente el contenido">
                            </div>

                            <div class="form-group">
                                <label>Selecionar materia</label>
                                <select>
                                    <option>Elige una materia</option>
                                    <option>Ciencias Naturales</option>
                                    <option>Matemáticas</option>
                                </select>
                            </div>

                            <div class="upload-area">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <p>Toca para selecionar o arrastrar tu archivo aquí</p>
                            </div>

                            <div class="form-actions-row">
                                <button type="button" class="btn-cancelar">Cancelar</button>
                                <button type="submit" class="btn-publicar">Publicar curso</button>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- =====================================
                     COLUMNA DERECHA (CALENDARIO RECICLADO)
                ====================================== -->
                <div class="right-column">
                    <aside class="calendar-widget border-container">
                        <div class="calendar-header">
                            <h3 class="section-title">Calendario</h3>
                            <a href="#" class="link-blue">Ver calendario <i class="fa-regular fa-calendar-days"></i></a>
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
                </div>
            </div>

            <!-- =====================================
                 BARRA ACCESIBILIDAD (RECICLADA)
            ====================================== -->
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

    <!-- EL MISMO JS QUE YA TIENES SIRVE AQUÍ -->
    <script src="jss/docente.js"></script>
</body>
</html>