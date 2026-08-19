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

$titulo_pagina = 'Pruebas de investigación';
$descripcion_pagina = 'Selecciona una prueba para gestionar sus participantes.';

// =====================================================
// OBTENER PRUEBAS
// =====================================================

$stmt = $conexion->prepare("
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM participantes_prueba WHERE id_prueba = p.id_prueba) AS participantes,
        (SELECT COUNT(*) FROM participantes_prueba WHERE id_prueba = p.id_prueba AND consentimiento = 1) AS consentimientos
    FROM pruebas_investigacion p
    ORDER BY p.fecha_inicio DESC
");
$stmt->execute();
$resultado = $stmt->get_result();
$pruebas = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function badgeEstado($estado) {
    switch ($estado) {
        case 'Activa': return 'badge-activa';
        case 'Finalizada': return 'badge-finalizada';
        default: return 'badge-planeada';
    }
}

function formatoFecha($fecha) {
    if (!$fecha) return 'Sin fecha';
    $timestamp = strtotime($fecha);
    return date('d/m/Y', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pruebas de investigación - Investigador</title>
    
    <link rel="stylesheet" href="styles/pruebas_investigacion.css">
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
        <div class="pruebas-header">
            <div class="header-info">
                <span class="cantidad"><?php echo count($pruebas); ?> <?php echo count($pruebas) === 1 ? 'prueba disponible' : 'pruebas disponibles'; ?></span>
                <p class="subtitulo">Selecciona una prueba para gestionar sus participantes.</p>
            </div>
        </div>

        <!-- ===== LISTA DE PRUEBAS ===== -->
        <section class="pruebas-list">
            <?php if (empty($pruebas)): ?>
                <div class="sin-datos">
                    <i class="fa-solid fa-flask"></i>
                    <p>No hay pruebas de investigación registradas.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pruebas as $prueba): ?>
                    <div class="prueba-card" data-id="<?php echo $prueba['id_prueba']; ?>">
                        <div class="prueba-header">
                            <div class="prueba-icono">
                                <i class="fa-solid fa-flask"></i>
                            </div>
                            <div class="prueba-info">
                                <h3><?php echo htmlspecialchars($prueba['nombre']); ?></h3>
                                <span class="badge <?php echo badgeEstado($prueba['estado']); ?>">
                                    <?php echo $prueba['estado']; ?>
                                </span>
                            </div>
                        </div>

                        <div class="prueba-datos">
                            <div class="dato-item">
                                <i class="fa-solid fa-universal-access"></i>
                                <span><?php echo htmlspecialchars($prueba['version_wcag']); ?></span>
                            </div>
                            <div class="dato-item">
                                <i class="fa-solid fa-calendar"></i>
                                <span><?php echo formatoFecha($prueba['fecha_inicio']); ?> - <?php echo formatoFecha($prueba['fecha_fin']); ?></span>
                            </div>
                            <div class="dato-item">
                                <i class="fa-solid fa-users"></i>
                                <span><?php echo $prueba['participantes'] ?? 0; ?> participantes</span>
                            </div>
                            <div class="dato-item">
                                <i class="fa-solid fa-check-circle"></i>
                                <span><?php echo $prueba['consentimientos'] ?? 0; ?> consentimientos</span>
                            </div>
                        </div>

                        <div class="prueba-detalle">
                            <div class="detalle-bloque">
                                <span class="detalle-label">Hipótesis</span>
                                <p><?php echo htmlspecialchars($prueba['hipotesis']); ?></p>
                            </div>
                            <?php if (!empty($prueba['objetivo'])): ?>
                                <div class="detalle-bloque">
                                    <span class="detalle-label">Objetivo</span>
                                    <p><?php echo htmlspecialchars($prueba['objetivo']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                       <div class="prueba-acciones">
    <?php if ($prueba['estado'] === 'Activa'): ?>
        <form method="POST" action="logica/cambiar_estado_prueba.php" style="display:inline;">
            <input type="hidden" name="id_prueba" value="<?php echo $prueba['id_prueba']; ?>">
            <input type="hidden" name="estado" value="Finalizada">
            <button type="submit" class="btn-cambiar-estado btn-finalizar" onclick="return confirm('¿Finalizar esta prueba?')">
                <i class="fa-solid fa-stop-circle"></i> Finalizar prueba
            </button>
        </form>
    <?php else: ?>
        <form method="POST" action="logica/cambiar_estado_prueba.php" style="display:inline;">
            <input type="hidden" name="id_prueba" value="<?php echo $prueba['id_prueba']; ?>">
            <input type="hidden" name="estado" value="Activa">
            <button type="submit" class="btn-cambiar-estado btn-activar" onclick="return confirm('¿Activar esta prueba?')">
                <i class="fa-solid fa-play-circle"></i> Activar prueba
            </button>
        </form>
    <?php endif; ?>
</div>
                            
                            <!-- BOTÓN VER PARTICIPANTES -->
                            <a href="ver_prueba.php?id=<?php echo $prueba['id_prueba']; ?>" class="btn-ver-participantes" style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px; background:#f1f5f9; border-radius:10px; color:#475569; text-decoration:none; font-weight:600; font-size:14px; margin-top:8px; transition:background 0.2s;">
                                <i class="fa-solid fa-users"></i> Ver participantes
                            </a>
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
<script src="js/pruebas_investigacion.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>

</body>
</html>