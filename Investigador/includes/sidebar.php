<?php
// ========================================== */
// SIDEBAR - INVESTIGADOR                     */
// ========================================== */

$pagina_actual = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div class="logo-section">
        <img src="../img/logogeneral.png" alt="Logo Aulamos" class="logo">
    </div>
    
    <nav class="menu">
        <a href="dashboard.php" class="menu-item <?php echo ($pagina_actual == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>
        <a href="metricas_uso.php" class="menu-item <?php echo ($pagina_actual == 'metricas_uso.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-bar"></i> Uso de la plataforma
        </a>
        <a href="tiempos_actividades.php" class="menu-item <?php echo ($pagina_actual == 'tiempos_actividades.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-clock"></i> Tiempos de actividades
        </a>
        <a href="errores_navegacion.php" class="menu-item <?php echo ($pagina_actual == 'errores_navegacion.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-triangle-exclamation"></i> Errores de navegación
        </a>
        <a href="metricas_chatbot.php" class="menu-item <?php echo ($pagina_actual == 'metricas_chatbot.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-robot"></i> Uso del chatbot
        </a>
        <a href="progreso_academico.php" class="menu-item <?php echo ($pagina_actual == 'progreso_academico.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-line"></i> Progreso académico
        </a>
        <a href="metricas_accesibilidad.php" class="menu-item <?php echo ($pagina_actual == 'metricas_accesibilidad.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-universal-access"></i> Accesibilidad
        </a>
        <a href="reportes.php" class="menu-item <?php echo ($pagina_actual == 'reportes.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-alt"></i> Reportes
        </a>
        <a href="mas.php" class="menu-item <?php echo ($pagina_actual == 'mas.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-ellipsis-h"></i> Más
        </a>
    </nav>
    
    <button class="btn-accesibilidad-header" id="btnAccesibilidad" onclick="toggleBarraAccesibilidad()">
        <i class="fa-solid fa-universal-access"></i> Accesibilidad
    </button>
</aside>