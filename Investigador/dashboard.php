<?php
session_start();
// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$titulo_pagina = 'Panel de investigación';
$descripcion_pagina = 'Consulta las métricas registradas durante las pruebas de uso de la plataforma.';

// =====================================================
// CONSULTAS A LA BD
// =====================================================

// Total de estudiantes
$stmt = $conexion->prepare("
    SELECT COUNT(DISTINCT u.id_usuario) AS total
    FROM usuarios u
    INNER JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
    WHERE ur.id_rol = 1 AND u.estado = 'Activo'
");
$stmt->execute();
$resultado = $stmt->get_result();
$total_estudiantes = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total de accesos
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total
    FROM eventos_investigacion
    WHERE tipo_evento = 'InicioSesion'
");
$stmt->execute();
$resultado = $stmt->get_result();
$total_accesos = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total de errores
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total
    FROM eventos_investigacion
    WHERE tipo_evento = 'Error'
");
$stmt->execute();
$resultado = $stmt->get_result();
$total_errores = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total de interacciones chatbot
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total
    FROM mensajes_chatbot
");
$stmt->execute();
$resultado = $stmt->get_result();
$total_chatbot = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Módulos más visitados
$stmt = $conexion->prepare("
    SELECT modulo, COUNT(*) AS visitas
    FROM eventos_investigacion
    WHERE modulo IS NOT NULL
    GROUP BY modulo
    ORDER BY visitas DESC
    LIMIT 5
");
$stmt->execute();
$resultado = $stmt->get_result();
$modulos_visitados = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$max_visitas = !empty($modulos_visitados) ? max(array_column($modulos_visitados, 'visitas')) : 1;

// Actividad reciente
$stmt = $conexion->prepare("
    SELECT u.nombre, u.apellido_paterno, e.modulo, e.accion, e.fecha_hora
    FROM eventos_investigacion e
    INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
    ORDER BY e.fecha_hora DESC
    LIMIT 5
");
$stmt->execute();
$resultado = $stmt->get_result();
$actividad_reciente = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Progreso promedio
$stmt = $conexion->prepare("
    SELECT AVG(porcentaje_avance) AS promedio
    FROM actividad_estudiantes
");
$stmt->execute();
$resultado = $stmt->get_result();
$progreso_promedio = round($resultado->fetch_assoc()['promedio'] ?? 0, 1);
$stmt->close();

// Usan accesibilidad
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total
    FROM preferencias_accesibilidad
    WHERE alto_contraste = 1 
       OR modo_oscuro = 1 
       OR lector_pantalla = 1 
       OR subtitulos = 1 
       OR tamano_texto != 'Normal' 
       OR fuente_dislexia = 1
");
$stmt->execute();
$resultado = $stmt->get_result();
$usan_accesibilidad = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Investigador</title>
    
    <link rel="stylesheet" href="styles/dashboard.css">
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

        <!-- ===== TARJETAS DE RESUMEN ===== -->
        <section class="resumen-investigador">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e8f0fe;">
                        <i class="fa-solid fa-users" style="color: #3b71f3;"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $total_estudiantes; ?></span>
                        <span class="stat-label">Estudiantes</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e6f7e6;">
                        <i class="fa-solid fa-door-open" style="color: #2e7d32;"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $total_accesos; ?></span>
                        <span class="stat-label">Accesos</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fce8e6;">
                        <i class="fa-solid fa-triangle-exclamation" style="color: #d32f2f;"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $total_errores; ?></span>
                        <span class="stat-label">Errores</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #f3e8fd;">
                        <i class="fa-solid fa-robot" style="color: #7b1fa2;"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $total_chatbot; ?></span>
                        <span class="stat-label">Chatbot</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== MÓDULOS + PROGRESO ===== -->
        <section class="modulos-progreso">
            <div class="modulos-visitados">
                <h3><i class="fa-solid fa-chart-simple"></i> Módulos más visitados</h3>
                <div class="modulos-lista">
                    <?php if (empty($modulos_visitados)): ?>
                        <p style="color:#94a3b8;">No hay datos disponibles.</p>
                    <?php else: ?>
                        <?php foreach ($modulos_visitados as $modulo): 
                            $porcentaje = $max_visitas > 0 ? ($modulo['visitas'] / $max_visitas) * 100 : 0;
                        ?>
                        <div class="modulo-item">
                            <div class="modulo-header">
                                <span class="modulo-nombre"><?php echo htmlspecialchars($modulo['modulo'] ?: 'Sin módulo'); ?></span>
                                <span class="modulo-visitas"><?php echo $modulo['visitas']; ?></span>
                            </div>
                            <div class="modulo-barra">
                                <div class="modulo-barra-llena" style="width: <?php echo $porcentaje; ?>%;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="progreso-general">
                <h3><i class="fa-solid fa-chart-line"></i> Progreso general</h3>
                <div class="progreso-circular">
                    <div class="circulo-progreso" data-progreso="<?php echo $progreso_promedio; ?>">
                        <span class="circulo-valor"><?php echo $progreso_promedio; ?>%</span>
                        <span class="circulo-etiqueta">Promedio de avance</span>
                    </div>
                </div>
                <div class="progreso-detalles">
                    <div class="detalle-item">
                        <span class="detalle-label">Usan accesibilidad</span>
                        <span class="detalle-valor"><?php echo $usan_accesibilidad; ?> / <?php echo $total_estudiantes; ?></span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Errores registrados</span>
                        <span class="detalle-valor"><?php echo $total_errores; ?></span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Interacciones chatbot</span>
                        <span class="detalle-valor"><?php echo $total_chatbot; ?></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== ACTIVIDAD RECIENTE ===== -->
        <section class="actividad-reciente">
            <h3><i class="fa-regular fa-clock"></i> Actividad reciente</h3>
            <div class="actividad-lista">
                <?php if (empty($actividad_reciente)): ?>
                    <p style="color:#94a3b8; text-align:center; padding:20px;">No hay actividad reciente.</p>
                <?php else: ?>
                    <?php foreach ($actividad_reciente as $actividad): ?>
                    <div class="actividad-item">
                        <div class="actividad-icono">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="actividad-info">
                            <span class="actividad-usuario">
                                <?php echo htmlspecialchars($actividad['nombre'] . ' ' . $actividad['apellido_paterno']); ?>
                            </span>
                            <span class="actividad-accion">
                                <?php echo htmlspecialchars($actividad['accion']); ?>
                                <?php if ($actividad['modulo']): ?>
                                    en <strong><?php echo htmlspecialchars($actividad['modulo']); ?></strong>
                                <?php endif; ?>
                            </span>
                            <span class="actividad-fecha">
                                <?php echo date('d M Y, h:i a', strtotime($actividad['fecha_hora'])); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
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
<script src="js/dashboard.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>