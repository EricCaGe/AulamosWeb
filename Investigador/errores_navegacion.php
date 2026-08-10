<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$titulo_pagina = 'Errores de navegación';
$descripcion_pagina = 'Consulta los errores de navegación, accesos fallidos y acciones que dificultaron el uso de la plataforma.';

// =====================================================
// CONSULTAS A LA BD
// =====================================================

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM eventos_investigacion WHERE tipo_evento = 'Error'");
$stmt->execute();
$resultado = $stmt->get_result();
$total_errores = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conexion->prepare("SELECT COUNT(DISTINCT id_usuario) AS total FROM eventos_investigacion WHERE tipo_evento = 'Error'");
$stmt->execute();
$resultado = $stmt->get_result();
$estudiantes_con_errores = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM eventos_investigacion WHERE tipo_evento = 'Error' AND accion LIKE '%acceso%'");
$stmt->execute();
$resultado = $stmt->get_result();
$accesos_fallidos = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM eventos_investigacion WHERE tipo_evento = 'Error' AND accion LIKE '%navegacion%'");
$stmt->execute();
$resultado = $stmt->get_result();
$errores_navegacion = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conexion->prepare("SELECT accion, COUNT(*) AS total FROM eventos_investigacion WHERE tipo_evento = 'Error' GROUP BY accion ORDER BY total DESC LIMIT 5");
$stmt->execute();
$resultado = $stmt->get_result();
$tipos_error = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$max_error = !empty($tipos_error) ? $tipos_error[0]['total'] : 1;

$stmt = $conexion->prepare("
    SELECT u.nombre, u.apellido_paterno, e.accion, e.modulo, e.pantalla, e.descripcion, e.fecha_hora
    FROM eventos_investigacion e
    INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
    WHERE e.tipo_evento = 'Error'
    ORDER BY e.fecha_hora DESC
    LIMIT 5
");
$stmt->execute();
$resultado = $stmt->get_result();
$errores_recientes = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Errores de navegación - Investigador</title>
    <link rel="stylesheet" href="../styles/admin.css">
    <link rel="stylesheet" href="styles/errores_navegacion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
</head>
<body>
<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="periodo-selector">
            <div class="periodo-info">
                <i class="fa-solid fa-calendar"></i>
                <div>
                    <span class="periodo-etiqueta">Periodo analizado</span>
                    <span class="periodo-valor">01 Ago - 08 Ago 2026</span>
                </div>
            </div>
            <button class="btn-periodo"><i class="fa-solid fa-chevron-down"></i></button>
        </div>
        <section class="resumen-errores">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fce8e6;"><i class="fa-solid fa-triangle-exclamation" style="color:#d32f2f;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $total_errores; ?></span>
                        <span class="stat-label">Errores registrados</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e8f0fe;"><i class="fa-solid fa-users" style="color:#3b71f3;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $estudiantes_con_errores; ?></span>
                        <span class="stat-label">Estudiantes</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fce8e6;"><i class="fa-solid fa-circle-xmark" style="color:#d32f2f;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $accesos_fallidos; ?></span>
                        <span class="stat-label">Accesos fallidos</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fff3e0;"><i class="fa-solid fa-compass" style="color:#e65100;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $errores_navegacion; ?></span>
                        <span class="stat-label">De navegación</span>
                    </div>
                </div>
            </div>
        </section>
        <section class="distribucion-errores">
            <h3><i class="fa-solid fa-chart-pie"></i> Tipos de error</h3>
            <div class="tarjeta-distribucion">
                <?php if (empty($tipos_error)): ?>
                    <p style="color:#94a3b8; text-align:center; padding:20px;">No hay datos disponibles.</p>
                <?php else: ?>
                    <?php foreach ($tipos_error as $tipo): 
                        $porcentaje = $max_error > 0 ? round(($tipo['total'] / $max_error) * 100) : 0;
                    ?>
                    <div class="tipo-error">
                        <div class="tipo-error-encabezado">
                            <span class="tipo-error-nombre"><?php echo htmlspecialchars($tipo['accion']); ?></span>
                            <span class="tipo-error-cantidad"><?php echo $tipo['total']; ?></span>
                        </div>
                        <div class="barra-fondo">
                            <div class="barra-llena" style="width: <?php echo $porcentaje; ?>%; background:#dc3545;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        <section class="errores-recientes">
            <h3><i class="fa-regular fa-clock"></i> Errores recientes</h3>
            <?php if (empty($errores_recientes)): ?>
                <p style="color:#94a3b8; text-align:center; padding:20px;">No hay errores recientes.</p>
            <?php else: ?>
                <?php foreach ($errores_recientes as $error): 
                    $nombre_completo = $error['nombre'] . ' ' . $error['apellido_paterno'];
                ?>
                <div class="tarjeta-error">
                    <div class="error-encabezado">
                        <div class="error-usuario">
                            <i class="fa-solid fa-user" style="color:#dc3545;"></i>
                            <div>
                                <span class="error-tipo"><?php echo htmlspecialchars($error['accion']); ?></span>
                                <span class="error-estudiante"><?php echo htmlspecialchars($nombre_completo); ?></span>
                            </div>
                        </div>
                        <div class="error-fecha">
                            <span><?php echo date('d M Y', strtotime($error['fecha_hora'])); ?></span>
                            <span><?php echo date('h:i a', strtotime($error['fecha_hora'])); ?></span>
                        </div>
                    </div>
                    <div class="error-detalle">
                        <div class="error-detalle-item">
                            <i class="fa-solid fa-display"></i>
                            <span class="error-detalle-label">Pantalla:</span>
                            <span class="error-detalle-valor"><?php echo htmlspecialchars($error['pantalla'] ?: 'Sin especificar'); ?></span>
                        </div>
                        <div class="error-detalle-item">
                            <i class="fa-solid fa-folder"></i>
                            <span class="error-detalle-label">Módulo:</span>
                            <span class="error-detalle-valor"><?php echo htmlspecialchars($error['modulo'] ?: 'Sin especificar'); ?></span>
                        </div>
                    </div>
                    <div class="error-descripcion">
                        <i class="fa-solid fa-quote-left" style="color:#94a3b8;"></i>
                        <p><?php echo htmlspecialchars($error['descripcion'] ?: 'Sin descripción disponible.'); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <section class="info-registrada">
            <h3><i class="fa-solid fa-list-check"></i> Información registrada</h3>
            <div class="info-grid">
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Tipo de error</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Pantalla donde ocurrió</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Fecha y hora</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Estudiante que presentó el error</span></div>
            </div>
        </section>
        <div class="aviso-investigador"><i class="fa-solid fa-circle-info"></i><p>Los errores mostrados son registrados automáticamente durante el uso de la plataforma.</p></div>
        <?php include '../Accesibilidad/accesibilidad.php'; ?>
    </main>
</div>
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i></button>
<script src="js/errores_navegacion.js"></script>
<script src="../Accesibilidad/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
</body>
</html>