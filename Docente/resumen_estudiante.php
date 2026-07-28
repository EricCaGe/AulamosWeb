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
    <title>Resumen del Estudiante - Aulamos</title>
    
    <!-- CSS Base -->
    <link rel="stylesheet" href="styles/docente.css">
    <!-- CSS Específico para esta pantalla -->
    <link rel="stylesheet" href="styles/resumen.css">
    
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
                <a href="docente_dashboard.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="crear_curso.php" class="menu-item"><i class="fa-solid fa-medal"></i> Crear Curso</a>
                <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="crear_evaluacio.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
                <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                <a href="mas.php" class="menu-item active"><i class="fa-solid fa-bars"></i> Mas</a>
                
                <div class="menu-spacer"></div>
                
                <a href="#" class="menu-item"><i class="fa-solid fa-gear"></i> Configuración</a>
            </nav>
            
            <button class="btn-accessibility-main"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
            
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content">
            
            <!-- ENCABEZADO SUPERIOR CON FLECHA DE REGRESO -->
            <header class="content-header header-with-back">
                <div class="welcome-text">
                    <h1>
                        <a href="mas.php" class="back-arrow"><i class="fa-solid fa-arrow-left"></i></a> 
                        Resumen del estudiante
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

            <!-- CONTENEDOR PRINCIPAL A DOS COLUMNAS -->
            <div class="resumen-layout">
                
                <!-- COLUMNA IZQUIERDA: Tarjetas de resumen -->
                <div class="resumen-left">
                    <h3 class="section-title">Resumen de actividades</h3>
                    <div class="cards-grid">
                        
                        <div class="activity-card">
                            <span class="card-title">Total de actividades</span>
                            <div class="card-value">12</div>
                            <span class="card-subtext gray">Asignadas</span>
                        </div>
                        
                        <div class="activity-card">
                            <span class="card-title">Completadas</span>
                            <div class="card-value">9</div>
                            <span class="card-subtext green">75%</span>
                        </div>
                        
                        <div class="activity-card">
                            <span class="card-title">Pendientes</span>
                            <div class="card-value">2</div>
                            <span class="card-subtext yellow">16.1%</span>
                        </div>
                        
                        <div class="activity-card">
                            <span class="card-title">Atrasadas</span>
                            <div class="card-value">1</div>
                            <span class="card-subtext red">8.3%</span>
                        </div>

                    </div>
                </div>

                <!-- COLUMNA DERECHA: Proceso y Botón -->
                <div class="resumen-right">
                    <div class="progress-card">
                        <h3 class="progress-title">Proceso General</h3>
                        
                        <!-- Gráfico Circular -->
                        <div class="circular-progress">
                            <div class="inner-circle">75%</div>
                        </div>
                        
                        <h4>Buen Trabajo 🥳</h4>
                        <p>Ana a completado el 75% de sus actividades asignadas</p>
                    </div>
                    
                    <button class="btn-ver-detalles">Ver detalles de actividades</button>
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