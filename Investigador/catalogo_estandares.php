<?php
session_start();

// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

// Verificar sesión y rol
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$titulo_pagina = 'Catálogo de estándares WCAG';
$descripcion_pagina = 'Consulta el catálogo completo de estándares de accesibilidad WCAG y sus funcionalidades relacionadas.';

// =====================================================
// OBTENER CATÁLOGO DE ESTÁNDARES
// =====================================================

$stmt = $conexion->prepare("
    SELECT 
        ce.*,
        (SELECT COUNT(*) FROM funcionalidades_estandares WHERE id_estandar = ce.id_estandar) AS total_funcionalidades
    FROM catalogo_estandares ce
    ORDER BY ce.norma, ce.criterio
");
$stmt->execute();
$resultado = $stmt->get_result();
$catalogo_estandares = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function colorNivel($nivel) {
    switch ($nivel) {
        case 'AAA': return '#7C3AED';
        case 'AA': return '#2563EB';
        case 'A': return '#15803D';
        default: return '#64748B';
    }
}

function badgeEstado($estado) {
    switch ($estado) {
        case 'Activa': return 'badge-verde';
        case 'Finalizada': return 'badge-gris';
        default: return 'badge-amarillo';
    }
}

function iconoNorma($norma) {
    switch ($norma) {
        case 'WCAG': return 'fa-solid fa-universal-access';
        case 'ISO 9241': return 'fa-solid fa-clipboard-check';
        case 'EN 301549': return 'fa-solid fa-european-union';
        default: return 'fa-solid fa-book';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo WCAG - Investigador</title>
    
    <link rel="stylesheet" href="styles/catalogo_estandares.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
</head>
<body>

<div class="dashboard-container">
    
    <!-- ===== SIDEBAR ===== -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <main class="main-content">
        
        <!-- ===== HEADER ===== -->
        <?php include 'includes/header.php'; ?>

        <!-- ===== ENCABEZADO ===== -->
        <div class="catalogo-header">
            <div class="header-info">
                <h1><i class="fa-solid fa-book"></i> Catálogo de estándares WCAG</h1>
                <p>Consulta el catálogo completo de estándares de accesibilidad WCAG y sus funcionalidades relacionadas.</p>
            </div>
            <div class="header-stats">
                <span class="stat-badge">
                    <i class="fa-solid fa-list"></i> <?php echo count($catalogo_estandares); ?> estándares
                </span>
            </div>
        </div>

        <!-- ===== ESTADÍSTICAS RÁPIDAS ===== -->
        <div class="stats-rapidas">
            <div class="stat-item">
                <span class="stat-number" style="color:#7C3AED;"><?php 
                    $aaa = array_filter($catalogo_estandares, function($e) { return $e['nivel'] === 'AAA'; });
                    echo count($aaa);
                ?></span>
                <span class="stat-label">Nivel AAA</span>
            </div>
            <div class="stat-item">
                <span class="stat-number" style="color:#2563EB;"><?php 
                    $aa = array_filter($catalogo_estandares, function($e) { return $e['nivel'] === 'AA'; });
                    echo count($aa);
                ?></span>
                <span class="stat-label">Nivel AA</span>
            </div>
            <div class="stat-item">
                <span class="stat-number" style="color:#15803D;"><?php 
                    $a = array_filter($catalogo_estandares, function($e) { return $e['nivel'] === 'A'; });
                    echo count($a);
                ?></span>
                <span class="stat-label">Nivel A</span>
            </div>
            <div class="stat-item">
                <span class="stat-number" style="color:#64748B;"><?php 
                    $sin_nivel = array_filter($catalogo_estandares, function($e) { return empty($e['nivel']); });
                    echo count($sin_nivel);
                ?></span>
                <span class="stat-label">Sin nivel</span>
            </div>
        </div>

        <!-- ===== FILTROS ===== -->
        <div class="filtros-catalogo">
            <div class="filtro-grupo">
                <label for="filtroNivel">Nivel:</label>
                <select id="filtroNivel" class="filtro-select">
                    <option value="todos">Todos</option>
                    <option value="AAA">AAA</option>
                    <option value="AA">AA</option>
                    <option value="A">A</option>
                    <option value="sin-nivel">Sin nivel</option>
                </select>
            </div>
            <div class="filtro-grupo">
                <label for="filtroBusqueda">Buscar:</label>
                <input type="text" id="filtroBusqueda" class="filtro-input" placeholder="Nombre, criterio o descripción...">
            </div>
            <button class="btn-limpiar-filtros" id="btnLimpiarFiltros">
                <i class="fa-solid fa-rotate-left"></i> Limpiar
            </button>
        </div>

        <!-- ===== CATÁLOGO DE ESTÁNDARES ===== -->
        <section class="catalogo-lista" id="catalogoLista">
            <?php if (empty($catalogo_estandares)): ?>
                <div class="sin-datos">
                    <i class="fa-solid fa-book-open"></i>
                    <p>No hay estándares registrados en el catálogo.</p>
                </div>
            <?php else: ?>
                <?php foreach ($catalogo_estandares as $estandar): ?>
                    <div class="estandar-card" data-nivel="<?php echo $estandar['nivel'] ?? 'sin-nivel'; ?>" data-norma="<?php echo $estandar['norma']; ?>" data-criterio="<?php echo $estandar['criterio']; ?>">
                        <div class="estandar-header">
                            <div class="estandar-titulo">
                                <div class="estandar-icono">
                                    <i class="<?php echo iconoNorma($estandar['norma']); ?>"></i>
                                </div>
                                <div>
                                    <div class="estandar-codigo">
                                        <?php echo htmlspecialchars($estandar['norma']); ?> 
                                        <?php echo htmlspecialchars($estandar['criterio']); ?>
                                        <?php if ($estandar['nivel']): ?>
                                            <span class="nivel-badge" style="background: <?php echo colorNivel($estandar['nivel']); ?>20; color: <?php echo colorNivel($estandar['nivel']); ?>; border: 1px solid <?php echo colorNivel($estandar['nivel']); ?>;">
                                                <?php echo $estandar['nivel']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="estandar-nombre">
                                        <?php echo htmlspecialchars($estandar['nombre']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="estandar-funcionalidades-count">
                                <i class="fa-regular fa-circle-check"></i>
                                <span><?php echo $estandar['total_funcionalidades'] ?? 0; ?> funcionalidades</span>
                            </div>
                        </div>
                        
                        <div class="estandar-descripcion">
                            <?php echo htmlspecialchars($estandar['descripcion']); ?>
                        </div>
                        
                        <?php if ($estandar['principio']): ?>
                            <div class="estandar-principio">
                                <i class="fa-regular fa-lightbulb"></i>
                                <span>Principio: <?php echo htmlspecialchars($estandar['principio']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="estandar-footer">
                            <span><i class="fa-regular fa-calendar"></i> <?php echo date('d/m/Y', strtotime($estandar['fecha_registro'])); ?></span>
                            <?php if ($estandar['referencia_oficial']): ?>
                                <span>
                                    <i class="fa-regular fa-link"></i> 
                                    <a href="<?php echo htmlspecialchars($estandar['referencia_oficial']); ?>" target="_blank" rel="noopener noreferrer">
                                        Referencia oficial
                                    </a>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- ===== BARRA DE ACCESIBILIDAD ===== -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </main>
</div>

<!-- ===== BOTÓN FLOTANTE ===== -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<!-- ===== SCRIPTS ===== -->
<script src="js/catalogo_estandares.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>