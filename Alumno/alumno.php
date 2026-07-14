<?php
// Simulación de datos dinámicos del estudiante (pueden venir de una Base de Datos)
$usuario = "Jazie"; 
$materia_actual = "Ciencias Naturales";
$tema_actual = "La célula y sus partes";
$progreso_celula = 60;

$actividades_completadas = 15;
$horas_aprendizaje = 12;
$lecciones_vistas = 8;
$racha_dias = 5;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aulamos - Inicio Estudiante</title>
    
    <link rel="stylesheet" href="Style/Inicio.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="dashboard-container">
        
        <aside class="sidebar">
            <div class="logo-section">
                <img src="https://placehold.co/50x50/3b71f3/white?text=🦉" alt="Búho Aulamos" class="logo-img">
                <div>
                    <h2>AULAMOS</h2>
                    <p>Aprendemos juntos</p>
                </div>
            </div>
            
            <nav class="menu">
                <a href="#" class="menu-item active"><i class="fa-solid fa-house"></i> Inicio</a>
              <a href="actividades.php" class="menu-item"><i class="fa-solid fa-cubes"></i> Mis actividades</a>
                <a href="biblioteca.php" class="menu-item"><i class="fa-solid fa-book-open"></i> Biblioteca digital</a>
                <a href="avances.php" class="menu-item"><i class="fa-solid fa-pen-to-square"></i> Mis avances</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-circle-question"></i> Ayuda</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-gear"></i> Configuración accesible</a>
            </nav>
            
            <button class="btn-accessibility-main"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
        </aside>

        <main class="main-content">
            
            <header class="content-header">
                <div class="welcome-text">
                    <h1>¡Hola, <?php echo $usuario; ?>! 👋</h1>
                    <p>Qué bueno verte hoy. Continúa aprendiendo a tu ritmo.</p>
                </div>
                <div class="header-actions">
                    <button class="btn-assistant" id="btn-asistente">Asistente Virtual <span class="robot-icon">🤖</span></button>
                    <div class="icon-bell"><i class="fa-regular fa-bell"></i></div>
                    <img src="https://placehold.co/40x40/ff7675/white?text=👩" alt="Avatar Estudiante" class="avatar">
                </div>
            </header>

            <section class="cards-grid">
                <div class="card card-purple">
                    <h3>Continúa donde lo dejaste</h3>
                    <div class="card-inner">
                        <h4><?php echo $tema_actual; ?></h4>
                        <p class="subtitle"><?php echo $materia_actual; ?></p>
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?php echo $progreso_celula; ?>%;"></div>
                        </div>
                        <span class="progress-text"><?php echo $progreso_celula; ?>%</span>
                    </div>
                    <button class="btn-action btn-purple">Continuar</button>
                </div>

                <div class="card card-orange">
                    <h3>Actividad próxima</h3>
                    <div class="card-inner">
                        <h4>Cuestionario: Ecosistemas</h4>
                        <p class="subtitle">Ciencias Naturales</p>
                        <p class="date-text"><i class="fa-regular fa-calendar"></i> Vence: 24 jul. 2026</p>
                    </div>
                    <button class="btn-action btn-orange">Ver actividad</button>
                </div>
            </section>

            <section class="advances-section">
                <div class="section-title-container">
                    <h3>Resumen de tus avances</h3>
                    <a href="#" class="view-details">Ver detalles ></a>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-box">
                        <p class="stat-title">Actividades Completadas</p>
                        <div class="stat-value"><i class="fa-regular fa-circle-check icon-green"></i> <?php echo $actividades_completadas; ?></div>
                        <p class="stat-period">Este mes</p>
                    </div>
                    <div class="stat-box">
                        <p class="stat-title">Horas de aprendizaje</p>
                        <div class="stat-value"><i class="fa-regular fa-clock icon-blue"></i> <?php echo $horas_aprendizaje; ?> h</div>
                        <p class="stat-period">Este mes</p>
                    </div>
                    <div class="stat-box">
                        <p class="stat-title">Lecciones vistas</p>
                        <div class="stat-value"><i class="fa-regular fa-bookmark icon-purple-light"></i> <?php echo $lecciones_vistas; ?></div>
                        <p class="stat-period">Esta semana</p>
                    </div>
                    <div class="stat-box">
                        <p class="stat-title">Rachas de estudio</p>
                        <div class="stat-value"><i class="fa-solid fa-fire icon-orange-light"></i> <?php echo $racha_dias; ?></div>
                        <p class="stat-period">¡Sigue así!</p>
                    </div>
                </div>
            </section>

            <footer class="accessibility-bar">
                <div class="acc-info">
                    <i class="fa-solid fa-eye-low-vision acc-icon-main"></i>
                    <div>
                        <strong>Accesibilidad siempre disponible</strong>
                        <p>Personaliza tu experiencia en cualquier momento.</p>
                    </div>
                </div>
                <div class="acc-options">
                    <button class="acc-opt-btn" id="btn-contrast"><i class="fa-solid fa-eye"></i><span>Alto contraste</span></button>
                    <button class="acc-opt-btn" id="btn-darkmode"><i class="fa-solid fa-moon"></i><span>Modo oscuro</span></button>
                    <button class="acc-opt-btn" id="btn-text-size"><i class="fa-solid fa-font"></i><span>Texto grande</span></button>
                    <button class="acc-opt-btn"><i class="fa-solid fa-volume-high"></i><span>Leer pantalla</span></button>
                    <button class="acc-opt-btn"><i class="fa-solid fa-closed-captioning"></i><span>Subtítulos</span></button>
                    <button class="acc-opt-btn"><i class="fa-solid fa-keyboard"></i><span>Navegación</span></button>
                </div>
                <button class="btn-open-config">Abrir configuración</button>
            </footer>

        </main>
    </div>

    <script src="js/Inicio.js"></script>
</body>
</html>