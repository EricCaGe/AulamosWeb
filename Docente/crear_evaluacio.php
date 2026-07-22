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
    <title>Crear Evaluación - Aulamos</title>
    
    <!-- CSS Base -->
    <link rel="stylesheet" href="styles/docente.css">
    <!-- CSS Específico para esta pantalla -->
    <link rel="stylesheet" href="styles/evaluacion.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="dashboard-container">
        
        <!-- BARRA LATERAL (RECICLADA) -->
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
                <a href="crear_curso.php" class="menu-item"><i class="fa-solid fa-medal"></i> Crear Curso</a>
                <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="crear_evaluacio.php" class="menu-item active"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                
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
                    <h1>Crear evaluación</h1>
                    <p>Diseña tus propias evaluaciones</p>
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

            <!-- RELLENO: FORMULARIO DE EVALUACIÓN -->
            <div class="main-grid eval-layout">
                
                <!-- COLUMNA IZQUIERDA -->
                <div class="left-column">
                    <form class="eval-form">
                        
                        <div class="form-group-clean">
                            <label>Titulo de evaluacion</label>
                            <input type="text" class="clean-input">
                        </div>

                        <div class="form-group-clean">
                            <label>Descripcion</label>
                            <!-- Usamos input en lugar de textarea para que se vea como en tu diseño -->
                            <input type="text" class="clean-input" style="height: 60px;">
                        </div>
                        
                        <div class="form-group-clean">
                            <label>Seleccionar materia</label>
                            <input type="text" class="clean-input">
                        </div>

                        <div class="form-group-clean mt-20">
                            <label>Tipo de evaluación</label>
                            <div class="eval-type-container">
                                <label class="eval-radio-card">
                                    <input type="radio" name="tipo_evaluacion" checked>
                                    <span class="custom-radio"></span>
                                    <span class="radio-label">Questionario</span>
                                </label>
                                <label class="eval-radio-card">
                                    <input type="radio" name="tipo_evaluacion">
                                    <span class="custom-radio"></span>
                                    <span class="radio-label">Tarea</span>
                                </label>
                                <label class="eval-radio-card">
                                    <input type="radio" name="tipo_evaluacion">
                                    <span class="custom-radio"></span>
                                    <span class="radio-label">Examen</span>
                                </label>
                            </div>
                        </div>

                    </form>
                </div>

                <!-- COLUMNA DERECHA -->
                <div class="right-column">
                    
                    <div class="form-group-clean">
                        <label>Fecha limite <span class="text-muted text-normal">Opcional</span></label>
                        <div class="date-input-container">
                            <input type="text" class="clean-input" placeholder="--/--/--">
                            <i class="fa-regular fa-calendar-days"></i>
                        </div>
                    </div>
                    
                    <!-- CALENDARIO RECICLADO -->
                    <aside class="calendar-widget border-container mt-10">
                        <div class="calendar-month">
                            <span class="month-title" style="font-size: 14px;">Mayo 2024</span>
                            <div class="calendar-nav">
                                <i class="fa-solid fa-chevron-left" style="font-size: 12px;"></i>
                                <i class="fa-solid fa-chevron-right" style="font-size: 12px;"></i>
                            </div>
                        </div>
                        <div class="calendar-grid small-cal">
                            <div class="cal-day-header">LUN</div><div class="cal-day-header">MAR</div><div class="cal-day-header">MIE</div><div class="cal-day-header">JUE</div><div class="cal-day-header">VIE</div><div class="cal-day-header">SAB</div><div class="cal-day-header">DOM</div>
                            <div class="cal-day disabled">29</div><div class="cal-day disabled">30</div><div class="cal-day dot">1</div><div class="cal-day">2</div><div class="cal-day">3</div><div class="cal-day">4</div><div class="cal-day">5</div>
                            <div class="cal-day">6</div><div class="cal-day">7</div><div class="cal-day">8</div><div class="cal-day dot">9</div><div class="cal-day">10</div><div class="cal-day">11</div><div class="cal-day">12</div>
                            <div class="cal-day">13</div><div class="cal-day">14</div><div class="cal-day">15</div><div class="cal-day dot">16</div><div class="cal-day">17</div><div class="cal-day">18</div><div class="cal-day">19</div>
                            <div class="cal-day active">20</div><div class="cal-day dot">21</div><div class="cal-day">22</div><div class="cal-day">23</div><div class="cal-day double-dot">24</div><div class="cal-day">25</div><div class="cal-day">26</div>
                            <div class="cal-day">27</div><div class="cal-day">28</div><div class="cal-day">29</div><div class="cal-day">30</div><div class="cal-day">31</div><div class="cal-day disabled">1</div><div class="cal-day disabled">2</div>
                        </div>
                    </aside>

                    <!-- BOTONES DE ACCIÓN -->
                    <div class="eval-buttons">
                        <button type="button" class="btn-outline-blue">Cancelar</button>
                        <button type="button" class="btn-light-blue">Crear evaluacion</button>
                    </div>
                </div>
            </div>

            <!-- BARRA ACCESIBILIDAD (RECICLADA) -->
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

    <!-- TU JS -->
    <script src="jss/docente.js"></script>
</body>
</html>