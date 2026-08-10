<?php
session_start();

// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

// Verificar que exista una sesión válida
if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id_usuario'])) {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_usuario = (int) $_SESSION['usuario']['id_usuario'];

// =============================================
// OBTENER DATOS DEL USUARIO
// =============================================
$stmt = $conexion->prepare("SELECT nombre, apellido_paterno FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
$nombre_completo = $usuario ? trim($usuario['nombre'] . ' ' . ($usuario['apellido_paterno'] ?? '')) : 'Estudiante';
$stmt->close();

// =============================================
// OBTENER ARTÍCULOS DEL CENTRO DE AYUDA
// =============================================
$articulosAyuda = [];
$sqlAyuda = "SELECT * FROM centro_ayuda WHERE activo = 1 ORDER BY fecha_publicacion DESC LIMIT 5";
$resultAyuda = $conexion->query($sqlAyuda);
if ($resultAyuda) {
    $articulosAyuda = $resultAyuda->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayuda Estudiante - Aulamos</title>
    
    <link rel="stylesheet" href="Style/Inicio.css">
    <link rel="stylesheet" href="Style/Ayuda.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- NUEVA ACCESIBILIDAD -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
</head>
<body>

<div class="dashboard-container">
    
    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logogeneral.png" alt="Logo AulamosWeb" class="logo">
        </div>
        
        <nav class="menu">
            <a href="alumno.php" class="menu-item"><i class="fa-solid fa-house"></i> Inicio</a>
            <a href="actividades.php" class="menu-item"><i class="fa-solid fa-cubes"></i> Mis actividades</a>
            <a href="biblioteca.php" class="menu-item"><i class="fa-solid fa-book-open"></i> Biblioteca digital</a>
            <a href="avances.php" class="menu-item"><i class="fa-solid fa-pen-to-square"></i> Mis avances</a>
            <a href="ayuda.php" class="menu-item active"><i class="fa-solid fa-circle-question"></i> Ayuda</a>
            <a href="accesibilidad.php" class="menu-item"><i class="fa-solid fa-gear"></i> Accesibilidad</a>
        </nav>
        
        <button class="btn-accessibility-main"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
        <div class="menu-spacer"></div>
        <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        
        <header class="content-header">
            <div class="welcome-text">
                <h1>Ayuda Estudiante</h1>
                <p>Encuentra respuestas y recursos para ti</p>
            </div>
            <div class="header-actions">
                <a href="Chatbot.php" class="btn-assistant" id="btnAsistente" aria-label="Abrir el asistente virtual AulaBot">
                    Asistente Virtual <span class="robot-icon" aria-hidden="true">🤖</span>
                </a>
                <div class="icon-bell"><i class="fa-regular fa-bell"></i></div>
                <img src="https://placehold.co/40x40/ff7675/white?text=👩" alt="Avatar" class="avatar">
            </div>
        </header>

        <!-- GUÍAS DE USO -->
        <section class="help-section">
            <h2 class="section-title"><i class="fa-solid fa-compass"></i> Guías de uso</h2>
            <p class="section-description">Aprende a usar Aulamos paso a paso</p>
            
            <div class="help-cards-grid">
                <div class="help-card">
                    <div class="help-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    <h3>Aprende a usar Aulamos</h3>
                    <p>Descubre todas las funcionalidades de la plataforma</p>
                    <button class="help-btn btn-primary">Ver guía</button>
                </div>
                
                <div class="help-card">
                    <div class="help-icon"><i class="fa-solid fa-headphones"></i></div>
                    <h3>Escuchar ayuda</h3>
                    <p>Contenido en audio para facilitar tu aprendizaje</p>
                    <button class="help-btn btn-secondary">Escuchar</button>
                </div>
                
                <div class="help-card">
                    <div class="help-icon"><i class="fa-solid fa-volume-high"></i></div>
                    <h3>Lee en voz alta</h3>
                    <p>Activa la lectura en voz alta de toda la información</p>
                    <button class="help-btn btn-secondary">Activar</button>
                </div>
            </div>
        </section>

        <!-- ARTÍCULOS DE AYUDA (desde BD) -->
        <?php if (!empty($articulosAyuda)): ?>
        <section class="help-section">
            <h2 class="section-title"><i class="fa-solid fa-book"></i> Artículos de ayuda</h2>
            <div class="help-articles-list">
                <?php foreach ($articulosAyuda as $articulo): ?>
                    <div class="help-article-item">
                        <div class="article-icon">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <div class="article-content">
                            <h3><?= htmlspecialchars($articulo['titulo']) ?></h3>
                            <p><?= htmlspecialchars(mb_substr($articulo['contenido'], 0, 120)) ?>...</p>
                            <span class="article-tag"><?= htmlspecialchars($articulo['tipo']) ?></span>
                        </div>
                        <button class="article-btn">Leer más</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- CONTACTAR SOPORTE -->
        <section class="help-section support-section">
            <div class="support-content">
                <div class="support-icon">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <div class="support-text">
                    <h2>Contactar soporte</h2>
                    <p>Estamos para ayudarte, habla con nuestro equipo de ayuda</p>
                </div>
                <button class="support-btn">Contactar ahora</button>
            </div>
        </section>

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
<script src="js/Inicio.js"></script>
<script src="js/Ayuda.js"></script>

<!-- NUEVA ACCESIBILIDAD -->
<script src="../Accesibilidad/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>