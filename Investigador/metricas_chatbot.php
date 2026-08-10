<?php
session_start();
// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';


if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$titulo_pagina = 'Uso del chatbot';
$descripcion_pagina = 'Consulta las interacciones realizadas por los estudiantes con el chatbot educativo durante las pruebas de uso.';

// =====================================================
// CONSULTAS A LA BD
// =====================================================

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM mensajes_chatbot");
$stmt->execute();
$resultado = $stmt->get_result();
$total_interacciones = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conexion->prepare("SELECT COUNT(DISTINCT s.id_usuario) AS total FROM sesiones_chatbot s");
$stmt->execute();
$resultado = $stmt->get_result();
$estudiantes_usuarios = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conexion->prepare("SELECT AVG(TIMESTAMPDIFF(SECOND, fecha_inicio, fecha_fin)) AS promedio FROM sesiones_chatbot WHERE fecha_fin IS NOT NULL");
$stmt->execute();
$resultado = $stmt->get_result();
$promedio_segundos = round($resultado->fetch_assoc()['promedio'] ?? 0);
$stmt->close();
$promedio_minutos = floor($promedio_segundos / 60);
$promedio_segundos_resto = $promedio_segundos % 60;
$promedio_duracion = $promedio_minutos . ' min ' . $promedio_segundos_resto . ' s';

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM mensajes_chatbot WHERE DATE(fecha_mensaje) = CURDATE()");
$stmt->execute();
$resultado = $stmt->get_result();
$preguntas_hoy = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conexion->prepare("SELECT tipo_consulta, COUNT(*) AS total FROM mensajes_chatbot GROUP BY tipo_consulta ORDER BY total DESC LIMIT 5");
$stmt->execute();
$resultado = $stmt->get_result();
$tipos_consulta = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$max_consultas = !empty($tipos_consulta) ? max(array_column($tipos_consulta, 'total')) : 1;

$stmt = $conexion->prepare("
    SELECT DATE(fecha_mensaje) AS fecha, COUNT(*) AS total
    FROM mensajes_chatbot
    WHERE fecha_mensaje >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(fecha_mensaje)
    ORDER BY fecha ASC
");
$stmt->execute();
$resultado = $stmt->get_result();
$interacciones_semana = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$dias_semana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$interacciones_por_dia = array_fill(0, 7, 0);

foreach ($interacciones_semana as $row) {
    $dia_num = date('N', strtotime($row['fecha'])) - 1;
    $interacciones_por_dia[$dia_num] = $row['total'];
}
$max_dia = !empty($interacciones_por_dia) ? max($interacciones_por_dia) : 1;

$stmt = $conexion->prepare("
    SELECT u.nombre, u.apellido_paterno, m.pregunta, m.respuesta, m.tipo_consulta, m.fecha_mensaje,
           TIMESTAMPDIFF(SECOND, s.fecha_inicio, s.fecha_fin) AS duracion
    FROM mensajes_chatbot m
    INNER JOIN sesiones_chatbot s ON m.id_sesion = s.id_sesion
    INNER JOIN usuarios u ON s.id_usuario = u.id_usuario
    ORDER BY m.fecha_mensaje DESC
    LIMIT 5
");
$stmt->execute();
$resultado = $stmt->get_result();
$interacciones_recientes = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uso del chatbot - Investigador</title>
    <link rel="stylesheet" href="../styles/admin.css">
    <link rel="stylesheet" href="styles/metricas_chatbot.css">
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
        <section class="resumen-chatbot">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#f3e8fd;"><i class="fa-solid fa-comments" style="color:#7b1fa2;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $total_interacciones; ?></span>
                        <span class="stat-label">Interacciones</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e8f0fe;"><i class="fa-solid fa-users" style="color:#3b71f3;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $estudiantes_usuarios; ?></span>
                        <span class="stat-label">Estudiantes</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e6f7e6;"><i class="fa-solid fa-clock" style="color:#2e7d32;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $promedio_duracion; ?></span>
                        <span class="stat-label">Duración promedio</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fff3e0;"><i class="fa-solid fa-calendar-day" style="color:#e65100;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $preguntas_hoy; ?></span>
                        <span class="stat-label">Preguntas hoy</span>
                    </div>
                </div>
            </div>
        </section>
        <section class="interacciones-semana">
            <h3><i class="fa-solid fa-chart-bar"></i> Interacciones durante la semana</h3>
            <div class="tarjeta-grafica">
                <?php for ($i = 0; $i < 7; $i++): 
                    $porcentaje = $max_dia > 0 ? round(($interacciones_por_dia[$i] / $max_dia) * 100) : 0;
                ?>
                <div class="barra-dia">
                    <span class="dia-label"><?php echo $dias_semana[$i]; ?></span>
                    <div class="barra-dia-fondo">
                        <div class="barra-dia-llena" style="height: <?php echo max($porcentaje, 5); ?>%; background: <?php echo $porcentaje > 0 ? '#7b1fa2' : '#e8edf2'; ?>;"></div>
                    </div>
                    <span class="dia-cantidad"><?php echo $interacciones_por_dia[$i]; ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </section>
        <section class="tipos-consulta">
            <h3><i class="fa-solid fa-chart-pie"></i> Tipos de consulta</h3>
            <div class="tarjeta-tipos">
                <?php if (empty($tipos_consulta)): ?>
                    <p style="color:#94a3b8; text-align:center; padding:20px;">No hay datos disponibles.</p>
                <?php else: ?>
                    <?php foreach ($tipos_consulta as $tipo): 
                        $porcentaje = $max_consultas > 0 ? round(($tipo['total'] / $max_consultas) * 100) : 0;
                    ?>
                    <div class="tipo-consulta">
                        <div class="tipo-consulta-encabezado">
                            <span class="tipo-consulta-nombre"><?php echo htmlspecialchars($tipo['tipo_consulta']); ?></span>
                            <span class="tipo-consulta-cantidad"><?php echo $tipo['total']; ?></span>
                        </div>
                        <div class="barra-fondo">
                            <div class="barra-llena" style="width: <?php echo $porcentaje; ?>%; background:#7b1fa2;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        <section class="interacciones-recientes">
            <h3><i class="fa-regular fa-clock"></i> Interacciones recientes</h3>
            <?php if (empty($interacciones_recientes)): ?>
                <p style="color:#94a3b8; text-align:center; padding:20px;">No hay interacciones recientes.</p>
            <?php else: ?>
                <?php foreach ($interacciones_recientes as $interaccion): 
                    $nombre_completo = $interaccion['nombre'] . ' ' . $interaccion['apellido_paterno'];
                    $duracion = $interaccion['duracion'] ? floor($interaccion['duracion'] / 60) . ' min ' . ($interaccion['duracion'] % 60) . ' s' : 'N/A';
                ?>
                <div class="tarjeta-interaccion">
                    <div class="interaccion-encabezado">
                        <div class="interaccion-usuario">
                            <i class="fa-solid fa-user" style="color:#7b1fa2;"></i>
                            <div>
                                <span class="interaccion-nombre"><?php echo htmlspecialchars($nombre_completo); ?></span>
                                <span class="interaccion-tipo"><?php echo htmlspecialchars($interaccion['tipo_consulta']); ?></span>
                            </div>
                        </div>
                        <div class="interaccion-fecha">
                            <span><?php echo date('d M Y', strtotime($interaccion['fecha_mensaje'])); ?></span>
                            <span><?php echo date('h:i a', strtotime($interaccion['fecha_mensaje'])); ?></span>
                        </div>
                    </div>
                    <div class="interaccion-mensajes">
                        <div class="mensaje-pregunta">
                            <i class="fa-solid fa-question-circle" style="color:#7b1fa2;"></i>
                            <p><?php echo htmlspecialchars($interaccion['pregunta']); ?></p>
                        </div>
                        <div class="mensaje-respuesta">
                            <i class="fa-solid fa-comment-dots" style="color:#2e7d32;"></i>
                            <p><?php echo htmlspecialchars($interaccion['respuesta']); ?></p>
                        </div>
                    </div>
                    <div class="interaccion-duracion">
                        <i class="fa-solid fa-clock" style="color:#94a3b8;"></i>
                        <span>Duración aproximada: <strong><?php echo $duracion; ?></strong></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <section class="info-registrada">
            <h3><i class="fa-solid fa-list-check"></i> Información registrada</h3>
            <div class="info-grid">
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Estudiante que realizó la consulta</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Fecha y hora de la interacción</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Pregunta realizada</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Respuesta proporcionada</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Duración aproximada</span></div>
            </div>
        </section>
        <div class="aviso-investigador"><i class="fa-solid fa-circle-info"></i><p>Las conversaciones mostradas son registradas automáticamente por el chatbot de AULAMOS.</p></div>
        <?php include '../Accesibilidad/accesibilidad.php'; ?>
    </main>
</div>
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i></button>
<script src="js/metricas_chatbot.js"></script>
<script src="../Accesibilidad/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
</body>
</html>