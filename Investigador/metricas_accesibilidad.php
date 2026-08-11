<?php
session_start();
// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$titulo_pagina = 'Métricas de accesibilidad';
$descripcion_pagina = 'Consulta las herramientas y preferencias de accesibilidad utilizadas por los estudiantes durante las pruebas de AULAMOS.';

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

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM preferencias_accesibilidad WHERE alto_contraste = 1");
$stmt->execute();
$resultado = $stmt->get_result();
$usan_alto_contraste = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

$herramientas = [
    ['nombre' => 'Alto contraste', 'icono' => 'fa-solid fa-circle-half-stroke', 'condicion' => 'alto_contraste = 1'],
    ['nombre' => 'Tamaño de texto', 'icono' => 'fa-solid fa-text-height', 'condicion' => "tamano_texto != 'Normal'"],
    ['nombre' => 'Fuente para dislexia', 'icono' => 'fa-solid fa-book', 'condicion' => 'fuente_dislexia = 1'],
    ['nombre' => 'Lector de pantalla', 'icono' => 'fa-solid fa-volume-high', 'condicion' => 'lector_pantalla = 1'],
    ['nombre' => 'Subtítulos', 'icono' => 'fa-solid fa-closed-captioning', 'condicion' => 'subtitulos = 1'],
    ['nombre' => 'Navegación por teclado', 'icono' => 'fa-solid fa-keyboard', 'condicion' => 'navegacion_teclado = 1'],
    ['nombre' => 'Modo oscuro', 'icono' => 'fa-solid fa-moon', 'condicion' => 'modo_oscuro = 1'],
];

$herramientas_uso = [];
foreach ($herramientas as $h) {
    $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM preferencias_accesibilidad WHERE " . $h['condicion']);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $total = $resultado->fetch_assoc()['total'] ?? 0;
    $stmt->close();
    $porcentaje = $total_estudiantes > 0 ? round(($total / $total_estudiantes) * 100) : 0;
    $herramientas_uso[] = [
        'nombre' => $h['nombre'],
        'icono' => $h['icono'],
        'total' => $total,
        'porcentaje' => $porcentaje
    ];
}
usort($herramientas_uso, function($a, $b) {
    return $b['porcentaje'] - $a['porcentaje'];
});
$herramienta_principal = !empty($herramientas_uso) ? $herramientas_uso[0]['nombre'] : 'Sin datos';

$stmt = $conexion->prepare("
    SELECT u.id_usuario, u.nombre, u.apellido_paterno,
           p.alto_contraste, p.modo_oscuro, p.tamano_texto, p.fuente_dislexia,
           p.lector_pantalla, p.velocidad_lectura, p.subtitulos,
           p.idioma, p.animaciones, p.navegacion_teclado, p.fecha_actualizacion
    FROM preferencias_accesibilidad p
    INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
    ORDER BY p.fecha_actualizacion DESC
    LIMIT 5
");
$stmt->execute();
$resultado = $stmt->get_result();
$preferencias_estudiantes = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Métricas de accesibilidad - Investigador</title>
    <link rel="stylesheet" href="../styles/admin.css">
    <link rel="stylesheet" href="styles/metricas_accesibilidad.css">
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

        <section class="resumen-accesibilidad">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e8f0fe;"><i class="fa-solid fa-users" style="color:#3b71f3;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $total_estudiantes; ?></span>
                        <span class="stat-label">Estudiantes analizados</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#f3e8fd;"><i class="fa-solid fa-universal-access" style="color:#7b1fa2;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $usan_accesibilidad; ?></span>
                        <span class="stat-label">Usan accesibilidad</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fff3e0;"><i class="fa-solid fa-circle-half-stroke" style="color:#e65100;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $usan_alto_contraste; ?></span>
                        <span class="stat-label">Usan alto contraste</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e6f7e6;"><i class="fa-solid fa-star" style="color:#2e7d32;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo !empty($herramientas_uso) ? $herramientas_uso[0]['porcentaje'] . '%' : '0%'; ?></span>
                        <span class="stat-label">Mayor uso</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="destacado-accesibilidad">
            <i class="fa-solid fa-universal-access"></i>
            <div>
                <span class="destacado-etiqueta">Herramienta más utilizada</span>
                <span class="destacado-valor"><?php echo htmlspecialchars($herramienta_principal); ?></span>
            </div>
        </div>

        <section class="funciones-utilizadas">
            <h3><i class="fa-solid fa-chart-bar"></i> Funciones más utilizadas</h3>
            <div class="tarjeta-herramientas">
                <?php if (empty($herramientas_uso)): ?>
                    <p style="color:#94a3b8; text-align:center; padding:20px;">No hay datos disponibles.</p>
                <?php else: ?>
                    <?php foreach ($herramientas_uso as $herramienta): ?>
                    <div class="herramienta-item">
                        <div class="herramienta-info">
                            <div class="herramienta-icono"><i class="<?php echo $herramienta['icono']; ?>"></i></div>
                            <div>
                                <span class="herramienta-nombre"><?php echo htmlspecialchars($herramienta['nombre']); ?></span>
                                <span class="herramienta-estudiantes"><?php echo $herramienta['total']; ?> estudiantes</span>
                            </div>
                        </div>
                        <span class="herramienta-porcentaje"><?php echo $herramienta['porcentaje']; ?>%</span>
                    </div>
                    <div class="barra-fondo">
                        <div class="barra-llena" style="width: <?php echo $herramienta['porcentaje']; ?>%;"></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="preferencias-estudiantes">
            <h3><i class="fa-solid fa-user-gear"></i> Preferencias por estudiante</h3>
            <?php if (empty($preferencias_estudiantes)): ?>
                <p style="color:#94a3b8; text-align:center; padding:20px;">No hay datos disponibles.</p>
            <?php else: ?>
                <?php foreach ($preferencias_estudiantes as $estudiante): 
                    $nombre_completo = $estudiante['nombre'] . ' ' . $estudiante['apellido_paterno'];
                    $tamano_texto = $estudiante['tamano_texto'] ?? 'Normal';
                ?>
                <div class="tarjeta-estudiante">
                    <div class="estudiante-encabezado">
                        <div class="estudiante-avatar"><i class="fa-solid fa-user"></i></div>
                        <div class="estudiante-info">
                            <span class="estudiante-nombre"><?php echo htmlspecialchars($nombre_completo); ?></span>
                            <span class="estudiante-id">Usuario #<?php echo $estudiante['id_usuario']; ?></span>
                        </div>
                        <i class="fa-solid fa-universal-access" style="color:#7b1fa2; font-size:20px;"></i>
                    </div>
                    <div class="preferencias-grid">
                        <div class="preferencia-item <?php echo $estudiante['alto_contraste'] ? 'activa' : 'inactiva'; ?>">
                            <i class="fa-solid fa-circle-half-stroke"></i>
                            <span>Alto contraste</span>
                            <span class="preferencia-estado"><?php echo $estudiante['alto_contraste'] ? 'Activo' : 'Desactivado'; ?></span>
                        </div>
                        <div class="preferencia-item <?php echo $estudiante['modo_oscuro'] ? 'activa' : 'inactiva'; ?>">
                            <i class="fa-solid fa-moon"></i>
                            <span>Modo oscuro</span>
                            <span class="preferencia-estado"><?php echo $estudiante['modo_oscuro'] ? 'Activo' : 'Desactivado'; ?></span>
                        </div>
                        <div class="preferencia-item <?php echo $estudiante['tamano_texto'] != 'Normal' ? 'activa' : 'inactiva'; ?>">
                            <i class="fa-solid fa-text-height"></i>
                            <span>Tamaño de texto</span>
                            <span class="preferencia-estado"><?php echo htmlspecialchars($tamano_texto); ?></span>
                        </div>
                        <div class="preferencia-item <?php echo $estudiante['fuente_dislexia'] ? 'activa' : 'inactiva'; ?>">
                            <i class="fa-solid fa-book"></i>
                            <span>Fuente para dislexia</span>
                            <span class="preferencia-estado"><?php echo $estudiante['fuente_dislexia'] ? 'Activo' : 'Desactivado'; ?></span>
                        </div>
                        <div class="preferencia-item <?php echo $estudiante['lector_pantalla'] ? 'activa' : 'inactiva'; ?>">
                            <i class="fa-solid fa-volume-high"></i>
                            <span>Lector de pantalla</span>
                            <span class="preferencia-estado"><?php echo $estudiante['lector_pantalla'] ? 'Activo' : 'Desactivado'; ?></span>
                        </div>
                        <div class="preferencia-item <?php echo $estudiante['velocidad_lectura'] != 1.0 ? 'activa' : 'inactiva'; ?>">
                            <i class="fa-solid fa-speedometer"></i>
                            <span>Velocidad de lectura</span>
                            <span class="preferencia-estado"><?php echo number_format($estudiante['velocidad_lectura'] ?? 1.0, 1); ?>x</span>
                        </div>
                        <div class="preferencia-item <?php echo $estudiante['subtitulos'] ? 'activa' : 'inactiva'; ?>">
                            <i class="fa-solid fa-closed-captioning"></i>
                            <span>Subtítulos</span>
                            <span class="preferencia-estado"><?php echo $estudiante['subtitulos'] ? 'Activo' : 'Desactivado'; ?></span>
                        </div>
                        <div class="preferencia-item <?php echo $estudiante['navegacion_teclado'] ? 'activa' : 'inactiva'; ?>">
                            <i class="fa-solid fa-keyboard"></i>
                            <span>Navegación por teclado</span>
                            <span class="preferencia-estado"><?php echo $estudiante['navegacion_teclado'] ? 'Activo' : 'Desactivado'; ?></span>
                        </div>
                    </div>
                    <div class="estudiante-footer">
                        <div class="estudiante-fecha">
                            <i class="fa-regular fa-clock"></i>
                            <span>Última actualización: <?php echo date('d M Y, h:i a', strtotime($estudiante['fecha_actualizacion'])); ?></span>
                        </div>
                        <button class="btn-estandares" 
                                data-id="<?php echo $estudiante['id_usuario']; ?>" 
                                data-nombre="<?php echo htmlspecialchars($nombre_completo); ?>" 
                                data-fecha="<?php echo date('d M Y, h:i a', strtotime($estudiante['fecha_actualizacion'])); ?>">
                            <i class="fa-solid fa-clipboard-list"></i> Ver estándares
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="info-registrada">
            <h3><i class="fa-solid fa-list-check"></i> Información registrada</h3>
            <div class="info-grid">
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Alto contraste</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Modo oscuro</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Tamaño de texto</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Fuente para dislexia</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Lector de pantalla</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Velocidad de lectura</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Subtítulos</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Idioma</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Animaciones</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Navegación por teclado</span></div>
                <div class="info-item"><i class="fa-solid fa-check-circle" style="color:#2e7d32;"></i><span>Fecha de actualización</span></div>
            </div>
        </section>

        <div class="aviso-investigador">
            <i class="fa-solid fa-circle-info"></i>
            <p>Los datos mostrados son las preferencias de accesibilidad almacenadas en la plataforma.</p>
        </div>

        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </main>
</div>

<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<!-- ========================================== -->
<!-- MODAL DE ESTÁNDARES                       -->
<!-- ========================================== -->
<div id="modalEstandares" class="modal-estandares-overlay modal-estandares-hidden">
    <div class="modal-estandares-container">
        <div class="modal-estandares-header">
            <h3><i class="fa-solid fa-clipboard-list"></i> <span id="modalTituloEstudiante">Estándares de accesibilidad</span></h3>
            <button class="btn-cerrar-modal" onclick="cerrarModalEstandares()">&times;</button>
        </div>
        
        <div id="modalEstudianteInfo" class="modal-estudiante-info">
            <i class="fa-solid fa-user"></i>
            <div>
                <span class="nombre" id="modalNombreEstudiante">Estudiante</span>
                <span style="font-size:12px; color:#64748b; display:block;" id="modalFechaEstudiante">Actualizado: --</span>
            </div>
        </div>

        <div id="modalTablaContainer">
            <table class="tabla-estandares" id="tablaEstandares">
                <thead>
                    <tr>
                        <th>Estándar</th>
                        <th>Nivel</th>
                        <th>Funcionalidad</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody id="tablaEstandaresBody">
                    <tr>
                        <td colspan="4" class="sin-preferencias">Cargando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- SCRIPTS                                    -->
<!-- ========================================== -->
<script src="js/metricas_accesibilidad.js"></script>
<script src="../Accesibilidad/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>