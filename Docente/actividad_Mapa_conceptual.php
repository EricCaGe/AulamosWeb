<?php
session_start();

// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

$usuario = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
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
    
    <!-- NUEVA ACCESIBILIDAD -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
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
            <a href="pasarlista.php" class="menu-item"><i class="fa-solid fa-bars"></i> Pasar Lista</a>
            <a href="#" class="menu-item"><i class="fa-solid fa-universal-access"></i> Accesibilidad</a>
            
            <div class="menu-spacer"></div>
            <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
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
                    <div class="user-profile">
                        <img src="https://placehold.co/40x40/ff7675/white?text=👩" alt="Avatar Docente" class="avatar">
                        <div class="user-info">
                            <span class="user-name"><?php echo $usuario; ?>!</span>
                            <span class="user-role"><?php echo $rol; ?></span>
                        </div>
                        
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

        <!-- ========================================== -->
        <!-- NUEVA BARRA DE ACCESIBILIDAD               -->
        <!-- ========================================== -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </main>
</div>

<!-- ========================================== -->
<!-- BOTÓN FLOTANTE DE ACCESIBILIDAD            -->
<!-- ========================================== -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<!-- ========================================== -->
<!-- SCRIPTS                                    -->
<!-- ========================================== -->
<script src="jss/docente_dashboard.js"></script>

<!-- NUEVA ACCESIBILIDAD -->
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>

</body>
</html>