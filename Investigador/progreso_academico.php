<?php
session_start();
// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$titulo_pagina = 'Progreso académico';
$descripcion_pagina = 'Consulta el avance de los estudiantes en actividades, evaluaciones y recursos durante el periodo de prueba.';

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

// Progreso promedio
if ($id_prueba_activa) {
    $stmt = $conexion->prepare("
        SELECT AVG(ae.porcentaje_avance) AS promedio 
        FROM actividad_estudiantes ae
        INNER JOIN participantes_prueba pp ON ae.id_alumno = pp.id_usuario
        WHERE pp.id_prueba = ?
    ");
    $stmt->bind_param("i", $id_prueba_activa);
} else {
    $stmt = $conexion->prepare("SELECT AVG(porcentaje_avance) AS promedio FROM actividad_estudiantes");
}
$stmt->execute();
$resultado = $stmt->get_result();
$progreso_promedio = round($resultado->fetch_assoc()['promedio'] ?? 0, 1);
$stmt->close();

// Actividades completadas
if ($id_prueba_activa) {
    $stmt = $conexion->prepare("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN ae.estado IN ('Completada', 'Calificada') THEN 1 ELSE 0 END) AS completadas
        FROM actividad_estudiantes ae
        INNER JOIN participantes_prueba pp ON ae.id_alumno = pp.id_usuario
        WHERE pp.id_prueba = ?
    ");
    $stmt->bind_param("i", $id_prueba_activa);
} else {
    $stmt = $conexion->prepare("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN estado IN ('Completada', 'Calificada') THEN 1 ELSE 0 END) AS completadas
        FROM actividad_estudiantes
    ");
}
$stmt->execute();
$resultado = $stmt->get_result();
$act_data = $resultado->fetch_assoc();
$stmt->close();
$actividades_completadas = $act_data['completadas'] ?? 0;
$total_actividades = $act_data['total'] ?? 1;

// Evaluaciones realizadas
if ($id_prueba_activa) {
    $stmt = $conexion->prepare("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN e.estado = 'Calificada' THEN 1 ELSE 0 END) AS realizadas
        FROM entregas e
        INNER JOIN actividad_estudiantes ae ON e.id_actividad_estudiante = ae.id_actividad_estudiante
        INNER JOIN participantes_prueba pp ON ae.id_alumno = pp.id_usuario
        WHERE pp.id_prueba = ?
    ");
    $stmt->bind_param("i", $id_prueba_activa);
} else {
    $stmt = $conexion->prepare("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN estado = 'Calificada' THEN 1 ELSE 0 END) AS realizadas
        FROM entregas
    ");
}
$stmt->execute();
$resultado = $stmt->get_result();
$eval_data = $resultado->fetch_assoc();
$stmt->close();
$evaluaciones_realizadas = $eval_data['realizadas'] ?? 0;
$total_evaluaciones = $eval_data['total'] ?? 1;

// Progreso por estudiante (top 5)
if ($id_prueba_activa) {
    $stmt = $conexion->prepare("
        SELECT u.id_usuario, u.nombre, u.apellido_paterno,
               AVG(ae.porcentaje_avance) AS porcentaje,
               SUM(CASE WHEN ae.estado IN ('Completada', 'Calificada') THEN 1 ELSE 0 END) AS completadas,
               COUNT(ae.id_actividad_estudiante) AS total_actividades,
               SUM(CASE WHEN e.estado = 'Calificada' THEN 1 ELSE 0 END) AS evaluaciones_realizadas,
               COUNT(DISTINCT e.id_entrega) AS total_evaluaciones
        FROM usuarios u
        INNER JOIN actividad_estudiantes ae ON u.id_usuario = ae.id_alumno
        LEFT JOIN entregas e ON ae.id_actividad_estudiante = e.id_actividad_estudiante
        INNER JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
        INNER JOIN participantes_prueba pp ON u.id_usuario = pp.id_usuario
        WHERE ur.id_rol = 1 AND u.estado = 'Activo' AND pp.id_prueba = ?
        GROUP BY u.id_usuario
        ORDER BY porcentaje DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $id_prueba_activa);
} else {
    $stmt = $conexion->prepare("
        SELECT u.id_usuario, u.nombre, u.apellido_paterno,
               AVG(ae.porcentaje_avance) AS porcentaje,
               SUM(CASE WHEN ae.estado IN ('Completada', 'Calificada') THEN 1 ELSE 0 END) AS completadas,
               COUNT(ae.id_actividad_estudiante) AS total_actividades,
               SUM(CASE WHEN e.estado = 'Calificada' THEN 1 ELSE 0 END) AS evaluaciones_realizadas,
               COUNT(DISTINCT e.id_entrega) AS total_evaluaciones
        FROM usuarios u
        INNER JOIN actividad_estudiantes ae ON u.id_usuario = ae.id_alumno
        LEFT JOIN entregas e ON ae.id_actividad_estudiante = e.id_actividad_estudiante
        INNER JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
        WHERE ur.id_rol = 1 AND u.estado = 'Activo'
        GROUP BY u.id_usuario
        ORDER BY porcentaje DESC
        LIMIT 5
    ");
}
$stmt->execute();
$resultado = $stmt->get_result();
$estudiantes = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Historial de progreso
if ($id_prueba_activa) {
    $stmt = $conexion->prepare("
        SELECT DATE(ae.fecha_inicio) AS fecha, AVG(ae.porcentaje_avance) AS promedio
        FROM actividad_estudiantes ae
        INNER JOIN participantes_prueba pp ON ae.id_alumno = pp.id_usuario
        WHERE ae.fecha_inicio IS NOT NULL AND pp.id_prueba = ?
        GROUP BY DATE(ae.fecha_inicio)
        ORDER BY fecha DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $id_prueba_activa);
} else {
    $stmt = $conexion->prepare("
        SELECT DATE(fecha_inicio) AS fecha, AVG(porcentaje_avance) AS promedio
        FROM actividad_estudiantes
        WHERE fecha_inicio IS NOT NULL
        GROUP BY DATE(fecha_inicio)
        ORDER BY fecha DESC
        LIMIT 5
    ");
}
$stmt->execute();
$resultado = $stmt->get_result();
$historial = array_reverse($resultado->fetch_all(MYSQLI_ASSOC));
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progreso académico - Investigador</title>
    <link rel="stylesheet" href="styles/progreso_academico.css">
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

        <section class="progreso-general">
            <div class="tarjeta-progreso-general">
                <div class="progreso-encabezado">
                    <div class="icono-principal"><i class="fa-solid fa-chart-line"></i></div>
                    <div class="progreso-texto">
                        <span class="progreso-etiqueta">Progreso promedio</span>
                        <span class="progreso-valor"><?php echo $progreso_promedio; ?>%</span>
                    </div>
                </div>
                <div class="barra-fondo-grande">
                    <div class="barra-grande" style="width: <?php echo $progreso_promedio; ?>%;"></div>
                </div>
            </div>
        </section>

        <section class="resumen-progreso">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e6f7e6;"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $actividades_completadas; ?>/<?php echo $total_actividades; ?></span>
                        <span class="stat-label">Actividades completadas</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e8f0fe;"><i class="fa-solid fa-clipboard-check" style="color:#3b71f3;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $evaluaciones_realizadas; ?>/<?php echo $total_evaluaciones; ?></span>
                        <span class="stat-label">Evaluaciones realizadas</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="evolucion-progreso">
            <h3><i class="fa-solid fa-chart-simple"></i> Evolución del progreso</h3>
            <div class="tarjeta-historial">
                <?php if (empty($historial)): ?>
                    <p style="color:#94a3b8; text-align:center; padding:20px;">No hay datos disponibles.</p>
                <?php else: ?>
                    <?php foreach ($historial as $registro): 
                        $fecha = date('d M', strtotime($registro['fecha']));
                        $porcentaje = round($registro['promedio']);
                    ?>
                    <div class="historial-item">
                        <div class="historial-icono"><i class="fa-solid fa-calendar-day"></i></div>
                        <span class="historial-fecha"><?php echo $fecha; ?></span>
                        <div class="historial-barra-fondo">
                            <div class="historial-barra" style="width: <?php echo $porcentaje; ?>%;"></div>
                        </div>
                        <span class="historial-porcentaje"><?php echo $porcentaje; ?>%</span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="progreso-estudiantes">
            <h3><i class="fa-solid fa-user-graduate"></i> Progreso por estudiante</h3>
            <?php if (empty($estudiantes)): ?>
                <p style="color:#94a3b8; text-align:center; padding:20px;">No hay datos disponibles.</p>
            <?php else: ?>
                <?php foreach ($estudiantes as $estudiante): 
                    $nombre_completo = $estudiante['nombre'] . ' ' . $estudiante['apellido_paterno'];
                    $porcentaje = round($estudiante['porcentaje'] ?? 0);
                    $completadas = $estudiante['completadas'] ?? 0;
                    $total_act = $estudiante['total_actividades'] ?? 1;
                ?>
                <div class="tarjeta-estudiante">
                    <div class="estudiante-encabezado">
                        <div class="estudiante-avatar"><i class="fa-solid fa-user"></i></div>
                        <div class="estudiante-info">
                            <span class="estudiante-nombre"><?php echo htmlspecialchars($nombre_completo); ?></span>
                            <span class="estudiante-fecha">Actualizado: <?php echo date('d M Y'); ?></span>
                        </div>
                        <span class="estudiante-porcentaje"><?php echo $porcentaje; ?>%</span>
                    </div>
                    <div class="barra-estudiante-fondo">
                        <div class="barra-estudiante" style="width: <?php echo $porcentaje; ?>%;"></div>
                    </div>
                    <div class="estudiante-datos">
                        <div class="dato-estudiante">
                            <i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i>
                            <span class="dato-valor"><?php echo $completadas; ?>/<?php echo $total_act; ?></span>
                            <span class="dato-etiqueta">Actividades</span>
                        </div>
                        <div class="dato-estudiante">
                            <i class="fa-solid fa-clipboard" style="color:#3b71f3;"></i>
                            <span class="dato-valor"><?php echo $estudiante['evaluaciones_realizadas'] ?? 0; ?>/<?php echo $estudiante['total_evaluaciones'] ?? 1; ?></span>
                            <span class="dato-etiqueta">Evaluaciones</span>
                        </div>
                        <div class="dato-estudiante">
                            <i class="fa-solid fa-book" style="color:#7b1fa2;"></i>
                            <span class="dato-valor">0</span>
                            <span class="dato-etiqueta">Recursos</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="info-registrada">
            <h3><i class="fa-solid fa-list-check"></i> Información registrada</h3>
            <div class="info-grid">
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Porcentaje de avance</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Actividades completadas</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Evaluaciones realizadas</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Recursos utilizados</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Fecha de cada registro</span></div>
            </div>
        </section>

        <div class="aviso-investigador"><i class="fa-solid fa-circle-info"></i><p>Los avances mostrados son obtenidos automáticamente a partir del desempeño académico registrado en AULAMOS.</p></div>
        <?php include '../Accesibilidad/accesibilidad.php'; ?>
    </main>
</div>
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i></button>
<script src="js/progreso_academico.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
</body>
</html>