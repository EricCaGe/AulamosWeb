<?php
// Solo usamos la sesión que ya está iniciada en cada archivo PHP
$nombre_investigador = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
?>

<header class="content-header">
    <div class="welcome-text">
        <h1><?php echo $titulo_pagina ?? 'Panel de investigación'; ?></h1>
        <p><?php echo $descripcion_pagina ?? 'Consulta las métricas registradas durante las pruebas de uso de la plataforma.'; ?></p>
    </div>
    <div class="header-actions">
        <div class="icon-bell">
            <i class="fa-regular fa-bell"></i>
        </div>
        <a href="perfil.php" class="user-profile">
            <img src="https://placehold.co/40x40/3b71f3/white?text=👤" alt="Avatar" class="avatar">
            <span class="user-name"><?php echo htmlspecialchars($nombre_investigador); ?></span>
            <i class="fa-solid fa-chevron-down drop-icon"></i>
        </a>
        <a href="../InicioSesion/cerrar_sesion.php" class="btn-logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
    </div>
</header>