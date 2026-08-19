<?php
session_start();
// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$titulo_pagina = 'Métricas de uso';
$descripcion_pagina = 'Consulta cómo utilizan los estudiantes la plataforma durante las pruebas de uso.';

// =====================================================
// DETECTAR PRUEBA ACTIVA
// =====================================================

$stmt = $conexion->prepare("SELECT id_prueba, nombre FROM pruebas_investigacion WHERE estado = 'Activa' LIMIT 1");
$stmt->execute();
$resultado = $stmt->get_result();
$prueba_activa = $resultado->fetch_assoc();
$stmt->close();

$id_prueba_activa = $prueba_activa['id_prueba'] ?? null;
$prueba_activa_nombre = $prueba_activa['nombre'] ?? 'Ninguna';

// =====================================================
// CONSULTAS A LA BD (con filtro por prueba activa)
// =====================================================

// Total de accesos
if ($id_prueba_activa) {
    $stmt = $conexion->prepare("
        SELECT COUNT(*) AS total 
        FROM eventos_investigacion e
        INNER JOIN participantes_prueba pp ON e.id_usuario = pp.id_usuario
        WHERE e.tipo_evento = 'InicioSesion' AND pp.id_prueba = ?
    ");
    $stmt->bind_param("i", $id_prueba_activa);
} else {
    $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM eventos_investigacion WHERE tipo_evento = 'InicioSesion'");
}
$stmt->execute();
$resultado = $stmt->get_result();
$total_accesos = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total de estudiantes
if ($id_prueba_activa) {
    $stmt = $conexion->prepare("
        SELECT COUNT(DISTINCT u.id_usuario) AS total
        FROM usuarios u
        INNER JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
        INNER JOIN participantes_prueba pp ON u.id_usuario = pp.id_usuario
        WHERE ur.id_rol = 1 AND u.estado = 'Activo' AND pp.id_prueba = ?
    ");
    $stmt->bind_param("i", $id_prueba_activa);
} else {
    $stmt = $conexion->prepare("
        SELECT COUNT(DISTINCT u.id_usuario) AS total
        FROM usuarios u
        INNER JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
        WHERE ur.id_rol = 1 AND u.estado = 'Activo'
    ");
}
$stmt->execute();
$resultado = $stmt->get_result();
$total_estudiantes = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Promedio de accesos por estudiante
$promedio_accesos = $total_estudiantes > 0 ? round($total_accesos / $total_estudiantes, 1) : 0;

// Módulos más visitados
if ($id_prueba_activa) {
    $stmt = $conexion->prepare("
        SELECT e.modulo, COUNT(*) AS visitas
        FROM eventos_investigacion e
        INNER JOIN participantes_prueba pp ON e.id_usuario = pp.id_usuario
        WHERE e.modulo IS NOT NULL AND pp.id_prueba = ?
        GROUP BY e.modulo
        ORDER BY visitas DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $id_prueba_activa);
} else {
    $stmt = $conexion->prepare("
        SELECT modulo, COUNT(*) AS visitas
        FROM eventos_investigacion
        WHERE modulo IS NOT NULL
        GROUP BY modulo
        ORDER BY visitas DESC
        LIMIT 5
    ");
}
$stmt->execute();
$resultado = $stmt->get_result();
$modulos_visitados = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$max_visitas = !empty($modulos_visitados) ? max(array_column($modulos_visitados, 'visitas')) : 1;
$modulo_mas_visitado = !empty($modulos_visitados) ? $modulos_visitados[0]['modulo'] : 'Sin datos';

// Actividad reciente
if ($id_prueba_activa) {
    $stmt = $conexion->prepare("
        SELECT u.nombre, u.apellido_paterno, e.modulo, e.accion, e.fecha_hora
        FROM eventos_investigacion e
        INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
        INNER JOIN participantes_prueba pp ON e.id_usuario = pp.id_usuario
        WHERE pp.id_prueba = ?
        ORDER BY e.fecha_hora DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $id_prueba_activa);
} else {
    $stmt = $conexion->prepare("
        SELECT u.nombre, u.apellido_paterno, e.modulo, e.accion, e.fecha_hora
        FROM eventos_investigacion e
        INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
        ORDER BY e.fecha_hora DESC
        LIMIT 5
    ");
}
$stmt->execute();
$resultado = $stmt->get_result();
$actividad_reciente = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uso de la plataforma - Investigador</title>
    <link rel="stylesheet" href="styles/metricas_uso.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
</head>
<body>
<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <!-- ===== AVISO DE PRUEBA ACTIVA ===== -->
        <?php if ($id_prueba_activa): ?>
            <div style="background: #f3e8fd; border: 1px solid #7C3AED; border-radius: 12px; padding: 12px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                <i class="fa-solid fa-flask" style="color: #7C3AED; font-size: 18px;"></i>
                <span style="color: #5a189a; font-weight: 600;">
                    Prueba activa: <strong><?php echo htmlspecialchars($prueba_activa_nombre); ?></strong>
                    <span style="font-weight: 400; color: #7C3AED;">— Los datos mostrados corresponden SOLO a los participantes de esta prueba.</span>
                </span>
            </div>
        <?php else: ?>
            <div style="background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                <i class="fa-solid fa-circle-info" style="color: #64748b; font-size: 18px;"></i>
                <span style="color: #475569; font-weight: 500;">
                    No hay prueba activa. Los datos muestran <strong>todos los estudiantes</strong> del sistema.
                </span>
            </div>
        <?php endif; ?>

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

        <section class="resumen-investigador">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e8f0fe;"><i class="fa-solid fa-door-open" style="color:#3b71f3;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $total_accesos; ?></span>
                        <span class="stat-label">Accesos</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e6f7e6;"><i class="fa-solid fa-users" style="color:#2e7d32;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $total_estudiantes; ?></span>
                        <span class="stat-label">Estudiantes <?php echo $id_prueba_activa ? 'seleccionados' : 'activos'; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#f3e8fd;"><i class="fa-solid fa-repeat" style="color:#7b1fa2;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $promedio_accesos; ?></span>
                        <span class="stat-label">Promedio de accesos</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fff3e0;"><i class="fa-solid fa-star" style="color:#e65100;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo !empty($modulos_visitados) ? $modulos_visitados[0]['visitas'] : 0; ?></span>
                        <span class="stat-label">Mayor frecuencia</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="modulos-detalle">
            <div class="modulos-header">
                <h3><i class="fa-solid fa-chart-simple"></i> Módulos más visitados</h3>
                <span class="modulos-sub">Número de visitas</span>
            </div>
            <div class="modulos-lista-detalle">
                <?php if (empty($modulos_visitados)): ?>
                    <p style="color:#94a3b8; text-align:center; padding:20px;">No hay datos disponibles.</p>
                <?php else: ?>
                    <?php foreach ($modulos_visitados as $modulo): 
                        $porcentaje = $max_visitas > 0 ? ($modulo['visitas'] / $max_visitas) * 100 : 0;
                    ?>
                    <div class="modulo-detalle-item">
                        <div class="modulo-detalle-info">
                            <span class="modulo-detalle-nombre"><?php echo htmlspecialchars($modulo['modulo'] ?: 'Sin módulo'); ?></span>
                            <span class="modulo-detalle-visitas"><?php echo $modulo['visitas']; ?></span>
                        </div>
                        <div class="modulo-detalle-barra">
                            <div class="modulo-detalle-llena" style="width: <?php echo $porcentaje; ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="modulo-destacado">
                <i class="fa-solid fa-arrow-trend-up"></i>
                <span>El módulo con mayor frecuencia de uso es <strong><?php echo htmlspecialchars($modulo_mas_visitado); ?></strong>.</span>
            </div>
        </section>

        <section class="actividad-reciente">
            <h3><i class="fa-regular fa-clock"></i> Actividad reciente</h3>
            <div class="actividad-lista">
                <?php if (empty($actividad_reciente)): ?>
                    <p style="color:#94a3b8; text-align:center; padding:20px;">No hay actividad reciente.</p>
                <?php else: ?>
                    <?php foreach ($actividad_reciente as $actividad): ?>
                    <div class="actividad-item">
                        <div class="actividad-icono"><i class="fa-solid fa-user"></i></div>
                        <div class="actividad-info">
                            <span class="actividad-usuario"><?php echo htmlspecialchars($actividad['nombre'] . ' ' . $actividad['apellido_paterno']); ?></span>
                            <span class="actividad-accion"><?php echo htmlspecialchars($actividad['accion']); ?><?php if ($actividad['modulo']): ?> en <strong><?php echo htmlspecialchars($actividad['modulo']); ?></strong><?php endif; ?></span>
                            <span class="actividad-fecha"><?php echo date('d M Y, h:i a', strtotime($actividad['fecha_hora'])); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="info-registrada">
            <h3><i class="fa-solid fa-list-check"></i> Información registrada</h3>
            <div class="info-grid">
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Módulos visitados</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Número de accesos</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Frecuencia de uso</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Fecha y hora de acceso</span></div>
            </div>
        </section>

        <div class="aviso-investigador"><i class="fa-solid fa-circle-info"></i><p>Los datos mostrados en esta pantalla son reales registrados por AULAMOS.</p></div>
        <?php include '../Accesibilidad/accesibilidad.php'; ?>
    </main>
</div>
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i></button>
<script src="js/metricas_uso.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
</body>
</html>