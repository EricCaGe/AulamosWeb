<?php
session_start();
// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$titulo_pagina = 'Tiempos de actividades';
$descripcion_pagina = 'Consulta cuánto tiempo tarda cada estudiante en completar una actividad durante las pruebas de uso.';

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

// Tiempo promedio general
if ($id_prueba_activa) {
    $stmt = $conexion->prepare("
        SELECT AVG(e.tiempo_realizacion) AS promedio 
        FROM entregas e
        INNER JOIN actividad_estudiantes ae ON e.id_actividad_estudiante = ae.id_actividad_estudiante
        INNER JOIN participantes_prueba pp ON ae.id_alumno = pp.id_usuario
        WHERE e.tiempo_realizacion IS NOT NULL AND pp.id_prueba = ?
    ");
    $stmt->bind_param("i", $id_prueba_activa);
} else {
    $stmt = $conexion->prepare("SELECT AVG(tiempo_realizacion) AS promedio FROM entregas WHERE tiempo_realizacion IS NOT NULL");
}
$stmt->execute();
$resultado = $stmt->get_result();
$promedio_segundos = round($resultado->fetch_assoc()['promedio'] ?? 0);
$stmt->close();
$promedio_minutos = floor($promedio_segundos / 60);
$promedio_segundos_resto = $promedio_segundos % 60;
$tiempo_promedio_general = $promedio_minutos . ' min ' . $promedio_segundos_resto . ' s';

// Actividad más rápida y más lenta
if ($id_prueba_activa) {
    $stmt = $conexion->prepare("
        SELECT a.titulo, AVG(e.tiempo_realizacion) AS promedio
        FROM entregas e
        INNER JOIN actividad_estudiantes ae ON e.id_actividad_estudiante = ae.id_actividad_estudiante
        INNER JOIN actividades a ON ae.id_actividad = a.id_actividad
        INNER JOIN participantes_prueba pp ON ae.id_alumno = pp.id_usuario
        WHERE e.tiempo_realizacion IS NOT NULL AND pp.id_prueba = ?
        GROUP BY a.id_actividad
    ");
    $stmt->bind_param("i", $id_prueba_activa);
} else {
    $stmt = $conexion->prepare("
        SELECT a.titulo, AVG(e.tiempo_realizacion) AS promedio
        FROM entregas e
        INNER JOIN actividad_estudiantes ae ON e.id_actividad_estudiante = ae.id_actividad_estudiante
        INNER JOIN actividades a ON ae.id_actividad = a.id_actividad
        WHERE e.tiempo_realizacion IS NOT NULL
        GROUP BY a.id_actividad
    ");
}
$stmt->execute();
$resultado = $stmt->get_result();
$actividades_tiempos = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$actividad_mas_rapida = 'Sin datos';
$actividad_mas_lenta = 'Sin datos';
$tiempo_rapido = PHP_INT_MAX;
$tiempo_lento = 0;

foreach ($actividades_tiempos as $act) {
    if ($act['promedio'] < $tiempo_rapido && $act['promedio'] > 0) {
        $tiempo_rapido = $act['promedio'];
        $actividad_mas_rapida = $act['titulo'];
    }
    if ($act['promedio'] > $tiempo_lento) {
        $tiempo_lento = $act['promedio'];
        $actividad_mas_lenta = $act['titulo'];
    }
}

// Promedio por actividad
if ($id_prueba_activa) {
    $stmt = $conexion->prepare("
        SELECT a.titulo, COUNT(DISTINCT ae.id_alumno) AS estudiantes, AVG(e.tiempo_realizacion) AS promedio
        FROM entregas e
        INNER JOIN actividad_estudiantes ae ON e.id_actividad_estudiante = ae.id_actividad_estudiante
        INNER JOIN actividades a ON ae.id_actividad = a.id_actividad
        INNER JOIN participantes_prueba pp ON ae.id_alumno = pp.id_usuario
        WHERE e.tiempo_realizacion IS NOT NULL AND pp.id_prueba = ?
        GROUP BY a.id_actividad
        ORDER BY promedio ASC
        LIMIT 5
    ");
    $stmt->bind_param("i", $id_prueba_activa);
} else {
    $stmt = $conexion->prepare("
        SELECT a.titulo, COUNT(DISTINCT ae.id_alumno) AS estudiantes, AVG(e.tiempo_realizacion) AS promedio
        FROM entregas e
        INNER JOIN actividad_estudiantes ae ON e.id_actividad_estudiante = ae.id_actividad_estudiante
        INNER JOIN actividades a ON ae.id_actividad = a.id_actividad
        WHERE e.tiempo_realizacion IS NOT NULL
        GROUP BY a.id_actividad
        ORDER BY promedio ASC
        LIMIT 5
    ");
}
$stmt->execute();
$resultado = $stmt->get_result();
$resumen_actividades = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Registros de tiempo por estudiante
if ($id_prueba_activa) {
    $stmt = $conexion->prepare("
        SELECT u.nombre, u.apellido_paterno, a.titulo AS actividad, e.fecha_entrega, e.tiempo_realizacion
        FROM entregas e
        INNER JOIN actividad_estudiantes ae ON e.id_actividad_estudiante = ae.id_actividad_estudiante
        INNER JOIN usuarios u ON ae.id_alumno = u.id_usuario
        INNER JOIN actividades a ON ae.id_actividad = a.id_actividad
        INNER JOIN participantes_prueba pp ON ae.id_alumno = pp.id_usuario
        WHERE e.tiempo_realizacion IS NOT NULL AND pp.id_prueba = ?
        ORDER BY e.fecha_entrega DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $id_prueba_activa);
} else {
    $stmt = $conexion->prepare("
        SELECT u.nombre, u.apellido_paterno, a.titulo AS actividad, e.fecha_entrega, e.tiempo_realizacion
        FROM entregas e
        INNER JOIN actividad_estudiantes ae ON e.id_actividad_estudiante = ae.id_actividad_estudiante
        INNER JOIN usuarios u ON ae.id_alumno = u.id_usuario
        INNER JOIN actividades a ON ae.id_actividad = a.id_actividad
        WHERE e.tiempo_realizacion IS NOT NULL
        ORDER BY e.fecha_entrega DESC
        LIMIT 5
    ");
}
$stmt->execute();
$resultado = $stmt->get_result();
$registros_tiempo = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function formatearTiempo($segundos) {
    if (!$segundos) return '0 min';
    $min = floor($segundos / 60);
    $seg = $segundos % 60;
    return $min . ' min ' . $seg . ' s';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiempos de actividades - Investigador</title>
    <link rel="stylesheet" href="styles/tiempos_actividades.css">
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

        <section class="tiempo-promedio">
            <div class="tarjeta-promedio">
                <div class="icono-promedio"><i class="fa-solid fa-clock"></i></div>
                <div class="promedio-contenido">
                    <span class="promedio-etiqueta">Tiempo promedio general</span>
                    <span class="promedio-valor"><?php echo $tiempo_promedio_general; ?></span>
                </div>
            </div>
        </section>

        <section class="destacados-tiempos">
            <div class="grid-destacados">
                <div class="tarjeta-destacada">
                    <i class="fa-solid fa-bolt" style="color:#2e7d32;"></i>
                    <span class="destacado-etiqueta">Menor tiempo</span>
                    <span class="destacado-valor"><?php echo htmlspecialchars($actividad_mas_rapida); ?></span>
                </div>
                <div class="tarjeta-destacada">
                    <i class="fa-solid fa-hourglass" style="color:#5a189a;"></i>
                    <span class="destacado-etiqueta">Mayor tiempo</span>
                    <span class="destacado-valor"><?php echo htmlspecialchars($actividad_mas_lenta); ?></span>
                </div>
            </div>
        </section>

        <section class="promedio-actividades">
            <h3><i class="fa-solid fa-chart-simple"></i> Promedio por actividad</h3>
            <div class="tarjeta-actividades">
                <?php if (empty($resumen_actividades)): ?>
                    <p style="color:#94a3b8; text-align:center; padding:20px;">No hay datos disponibles.</p>
                <?php else: ?>
                    <?php foreach ($resumen_actividades as $actividad): 
                        $promedio = formatearTiempo($actividad['promedio']);
                    ?>
                    <div class="actividad-resumen">
                        <div class="actividad-resumen-info">
                            <i class="fa-solid fa-file-lines" style="color:#5a189a;"></i>
                            <div>
                                <span class="actividad-resumen-nombre"><?php echo htmlspecialchars($actividad['titulo']); ?></span>
                                <span class="actividad-resumen-estudiantes"><?php echo $actividad['estudiantes']; ?> estudiantes</span>
                            </div>
                        </div>
                        <div class="actividad-resumen-tiempo">
                            <span class="actividad-resumen-promedio"><?php echo $promedio; ?></span>
                            <span class="actividad-resumen-label">promedio</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="registros-estudiantes">
            <h3><i class="fa-regular fa-clock"></i> Registros por estudiante</h3>
            <?php if (empty($registros_tiempo)): ?>
                <p style="color:#94a3b8; text-align:center; padding:20px;">No hay registros disponibles.</p>
            <?php else: ?>
                <?php foreach ($registros_tiempo as $registro): 
                    $tiempo = formatearTiempo($registro['tiempo_realizacion']);
                    $nombre_completo = $registro['nombre'] . ' ' . $registro['apellido_paterno'];
                ?>
                <div class="tarjeta-registro">
                    <div class="registro-encabezado">
                        <div class="registro-usuario">
                            <i class="fa-solid fa-user" style="color:#5a189a;"></i>
                            <div>
                                <span class="registro-nombre"><?php echo htmlspecialchars($nombre_completo); ?></span>
                                <span class="registro-actividad"><?php echo htmlspecialchars($registro['actividad']); ?></span>
                            </div>
                        </div>
                        <div class="registro-badge">
                            <i class="fa-solid fa-clock"></i>
                            <span><?php echo $tiempo; ?></span>
                        </div>
                    </div>
                    <div class="registro-fecha">
                        <i class="fa-regular fa-calendar"></i>
                        <span><?php echo date('d M Y, h:i a', strtotime($registro['fecha_entrega'])); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="info-registrada">
            <h3><i class="fa-solid fa-list-check"></i> Información registrada</h3>
            <div class="info-grid">
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Fecha y hora de inicio</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Fecha y hora de finalización</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Tiempo total empleado</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Actividad realizada</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Estudiante correspondiente</span></div>
            </div>
        </section>

        <div class="aviso-investigador"><i class="fa-solid fa-circle-info"></i><p>Los tiempos mostrados son calculados automáticamente desde el inicio hasta la finalización de cada actividad.</p></div>
        <?php include '../Accesibilidad/accesibilidad.php'; ?>
    </main>
</div>
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i></button>
<script src="js/tiempos_actividades.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
</body>
</html>