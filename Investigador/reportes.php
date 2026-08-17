<?php
session_start();
// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$titulo_pagina = 'Reportes de investigación';
$descripcion_pagina = 'Consulta un resumen de las métricas recopiladas durante las pruebas de uso de la plataforma.';

// =====================================================
// CONSULTAS A LA BD
// =====================================================

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

$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total
    FROM preferencias_accesibilidad
    WHERE alto_contraste = 1 OR modo_oscuro = 1 OR lector_pantalla = 1 
       OR subtitulos = 1 OR tamano_texto != 'Normal' OR fuente_dislexia = 1
");
$stmt->execute();
$resultado = $stmt->get_result();
$usan_accesibilidad = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conexion->prepare("SELECT AVG(tiempo_realizacion) AS promedio FROM entregas WHERE tiempo_realizacion IS NOT NULL");
$stmt->execute();
$resultado = $stmt->get_result();
$promedio_segundos = round($resultado->fetch_assoc()['promedio'] ?? 0);
$stmt->close();
$promedio_minutos = floor($promedio_segundos / 60);
$promedio_segundos_resto = $promedio_segundos % 60;
$tiempo_promedio = $promedio_minutos . ' min ' . $promedio_segundos_resto . ' s';

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM eventos_investigacion WHERE tipo_evento = 'Error'");
$stmt->execute();
$resultado = $stmt->get_result();
$total_errores = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM mensajes_chatbot");
$stmt->execute();
$resultado = $stmt->get_result();
$total_chatbot = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conexion->prepare("SELECT AVG(porcentaje_avance) AS promedio FROM actividad_estudiantes");
$stmt->execute();
$resultado = $stmt->get_result();
$progreso_promedio = round($resultado->fetch_assoc()['promedio'] ?? 0, 1);
$stmt->close();

$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total,
           SUM(CASE WHEN alto_contraste = 1 THEN 1 ELSE 0 END) AS alto_contraste,
           SUM(CASE WHEN modo_oscuro = 1 THEN 1 ELSE 0 END) AS modo_oscuro,
           SUM(CASE WHEN lector_pantalla = 1 THEN 1 ELSE 0 END) AS lector_pantalla,
           SUM(CASE WHEN subtitulos = 1 THEN 1 ELSE 0 END) AS subtitulos,
           SUM(CASE WHEN tamano_texto != 'Normal' THEN 1 ELSE 0 END) AS tamano_texto
    FROM preferencias_accesibilidad
");
$stmt->execute();
$resultado = $stmt->get_result();
$accesibilidad_stats = $resultado->fetch_assoc();
$stmt->close();

$accesibilidad_data = [
    ['nombre' => 'Alto contraste', 'total' => $accesibilidad_stats['alto_contraste'] ?? 0],
    ['nombre' => 'Tamaño de texto', 'total' => $accesibilidad_stats['tamano_texto'] ?? 0],
    ['nombre' => 'Lector de pantalla', 'total' => $accesibilidad_stats['lector_pantalla'] ?? 0],
    ['nombre' => 'Subtítulos', 'total' => $accesibilidad_stats['subtitulos'] ?? 0],
];
$max_accesibilidad = !empty($accesibilidad_data) ? max(array_column($accesibilidad_data, 'total')) : 1;

// =====================================================
// EXPORTAR A CSV
// =====================================================
if (isset($_GET['exportar']) && $_GET['exportar'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_investigacion_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['Métrica', 'Valor', 'Descripción', 'Fecha']);
    
    $data = [
        ['Estudiantes con accesibilidad', $usan_accesibilidad . ' de ' . $total_estudiantes, 'Estudiantes que usan funciones de accesibilidad', date('Y-m-d')],
        ['Tiempo promedio', $tiempo_promedio, 'Tiempo promedio en actividades', date('Y-m-d')],
        ['Errores registrados', $total_errores, 'Total de errores de navegación', date('Y-m-d')],
        ['Interacciones chatbot', $total_chatbot, 'Total de mensajes con el chatbot', date('Y-m-d')],
        ['Progreso académico', $progreso_promedio . '%', 'Porcentaje promedio de avance', date('Y-m-d')],
    ];
    
    foreach ($accesibilidad_data as $item) {
        $data[] = ['Accesibilidad: ' . $item['nombre'], $item['total'] . ' estudiantes', 'Estudiantes con ' . $item['nombre'] . ' activado', date('Y-m-d')];
    }
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// =====================================================
// EXPORTAR A EXCEL (HTML)
// =====================================================
if (isset($_GET['exportar']) && $_GET['exportar'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_investigacion_' . date('Y-m-d') . '.xls"');
    
    echo '<html><head><meta charset="utf-8"></head><body>';
    echo '<h2>Reporte de Investigación - AULAMOS</h2>';
    echo '<p>Fecha: ' . date('d/m/Y H:i') . '</p>';
    echo '<table border="1" cellpadding="5">';
    echo '<tr style="background:#3b71f3; color:white;"><th>Métrica</th><th>Valor</th><th>Descripción</th><th>Fecha</th></tr>';
    
    $rows = [
        ['Estudiantes con accesibilidad', $usan_accesibilidad . ' de ' . $total_estudiantes, 'Estudiantes que usan funciones de accesibilidad', date('Y-m-d')],
        ['Tiempo promedio', $tiempo_promedio, 'Tiempo promedio en actividades', date('Y-m-d')],
        ['Errores registrados', $total_errores, 'Total de errores de navegación', date('Y-m-d')],
        ['Interacciones chatbot', $total_chatbot, 'Total de mensajes con el chatbot', date('Y-m-d')],
        ['Progreso académico', $progreso_promedio . '%', 'Porcentaje promedio de avance', date('Y-m-d')],
    ];
    
    foreach ($accesibilidad_data as $item) {
        $rows[] = ['Accesibilidad: ' . $item['nombre'], $item['total'] . ' estudiantes', 'Estudiantes con ' . $item['nombre'] . ' activado', date('Y-m-d')];
    }
    
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de investigación - Investigador</title>
    <link rel="stylesheet" href="styles/reportes.css">
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
                    <span class="periodo-etiqueta">Periodo del reporte</span>
                    <span class="periodo-valor">01 Ago - 08 Ago 2026</span>
                </div>
            </div>
            <button class="btn-periodo"><i class="fa-solid fa-chevron-down"></i></button>
        </div>
        <section class="resumen-general">
            <h3><i class="fa-solid fa-chart-simple"></i> Resumen general</h3>
            <div class="reporte-item">
                <div class="reporte-icono" style="background:#f3e8fd;"><i class="fa-solid fa-universal-access" style="color:#7b1fa2;"></i></div>
                <div class="reporte-contenido">
                    <span class="reporte-titulo">Accesibilidad</span>
                    <span class="reporte-valor"><?php echo $usan_accesibilidad; ?> de <?php echo $total_estudiantes; ?></span>
                    <span class="reporte-descripcion">Estudiantes utilizaron alguna función de accesibilidad.</span>
                </div>
            </div>
            <div class="reporte-item">
                <div class="reporte-icono" style="background:#e8f0fe;"><i class="fa-solid fa-clock" style="color:#3b71f3;"></i></div>
                <div class="reporte-contenido">
                    <span class="reporte-titulo">Tiempo promedio</span>
                    <span class="reporte-valor"><?php echo $tiempo_promedio; ?></span>
                    <span class="reporte-descripcion">Tiempo promedio empleado para completar actividades.</span>
                </div>
            </div>
            <div class="reporte-item">
                <div class="reporte-icono" style="background:#fce8e6;"><i class="fa-solid fa-triangle-exclamation" style="color:#d32f2f;"></i></div>
                <div class="reporte-contenido">
                    <span class="reporte-titulo">Errores registrados</span>
                    <span class="reporte-valor"><?php echo $total_errores; ?></span>
                    <span class="reporte-descripcion">Errores y dificultades de navegación detectados.</span>
                </div>
            </div>
            <div class="reporte-item">
                <div class="reporte-icono" style="background:#e6f7e6;"><i class="fa-solid fa-robot" style="color:#2e7d32;"></i></div>
                <div class="reporte-contenido">
                    <span class="reporte-titulo">Uso del chatbot</span>
                    <span class="reporte-valor"><?php echo $total_chatbot; ?></span>
                    <span class="reporte-descripcion">Interacciones realizadas con el chatbot educativo.</span>
                </div>
            </div>
            <div class="reporte-item">
                <div class="reporte-icono" style="background:#fff3e0;"><i class="fa-solid fa-chart-line" style="color:#e65100;"></i></div>
                <div class="reporte-contenido">
                    <span class="reporte-titulo">Progreso académico</span>
                    <span class="reporte-valor"><?php echo $progreso_promedio; ?>%</span>
                    <span class="reporte-descripcion">Porcentaje promedio de avance de los estudiantes.</span>
                </div>
            </div>
        </section>
        <section class="estadisticas-accesibilidad">
            <h3><i class="fa-solid fa-universal-access"></i> Estadísticas de accesibilidad</h3>
            <div class="tarjeta-estadisticas">
                <?php foreach ($accesibilidad_data as $item): 
                    $porcentaje = $max_accesibilidad > 0 ? round(($item['total'] / $max_accesibilidad) * 100) : 0;
                ?>
                <div class="estadistica-item">
                    <div class="estadistica-encabezado">
                        <span class="estadistica-titulo"><?php echo $item['nombre']; ?></span>
                        <span class="estadistica-texto"><?php echo $item['total']; ?> estudiantes</span>
                    </div>
                    <div class="barra-fondo">
                        <div class="barra-llena" style="width: <?php echo $porcentaje; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="metricas-incluidas">
            <h3><i class="fa-solid fa-list-check"></i> Métricas incluidas</h3>
            <div class="tarjeta-incluidas">
                <div class="incluida-item">
                    <div class="incluida-icono"><i class="fa-solid fa-universal-access"></i></div>
                    <span class="incluida-texto">Estadísticas de accesibilidad</span>
                    <i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i>
                </div>
                <div class="incluida-item">
                    <div class="incluida-icono"><i class="fa-solid fa-clock"></i></div>
                    <span class="incluida-texto">Tiempos de realización</span>
                    <i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i>
                </div>
                <div class="incluida-item">
                    <div class="incluida-icono"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <span class="incluida-texto">Errores de navegación</span>
                    <i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i>
                </div>
                <div class="incluida-item">
                    <div class="incluida-icono"><i class="fa-solid fa-robot"></i></div>
                    <span class="incluida-texto">Uso del chatbot</span>
                    <i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i>
                </div>
                <div class="incluida-item">
                    <div class="incluida-icono"><i class="fa-solid fa-chart-line"></i></div>
                    <span class="incluida-texto">Progreso académico</span>
                    <i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i>
                </div>
            </div>
        </section>
        <section class="exportar-reporte">
            <h3><i class="fa-solid fa-download"></i> Exportar reporte</h3>
            <p class="exportar-descripcion">Selecciona el formato en el que deseas obtener la información recopilada.</p>
            <div class="boton-exportar" onclick="window.location.href='?exportar=csv'">
                <div class="exportar-icono"><i class="fa-solid fa-file-csv"></i></div>
                <div class="exportar-contenido">
                    <span class="exportar-titulo">Exportar CSV</span>
                    <span class="exportar-desc">Archivo compatible con hojas de cálculo</span>
                </div>
                <i class="fa-solid fa-download"></i>
            </div>
            <div class="boton-exportar principal" onclick="window.location.href='?exportar=excel'">
                <div class="exportar-icono"><i class="fa-solid fa-file-excel"></i></div>
                <div class="exportar-contenido">
                    <span class="exportar-titulo">Exportar Excel</span>
                    <span class="exportar-desc">Reporte organizado en formato de hoja de cálculo</span>
                </div>
                <i class="fa-solid fa-download"></i>
            </div>
        </section>
        <div class="aviso-investigador"><i class="fa-solid fa-circle-info"></i><p>Los resultados mostrados son datos reales registrados por AULAMOS.</p></div>
        <?php include '../Accesibilidad/accesibilidad.php'; ?>
    </main>
</div>
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i></button>
<script src="js/reportes.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
</body>
</html>