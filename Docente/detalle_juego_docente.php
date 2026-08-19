<?php
session_start();

// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_docente = $_SESSION['usuario']['id_usuario'];
$nombre_docente = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];

// Obtener foto de perfil del docente
$foto_perfil_docente = $_SESSION['usuario']['foto_perfil'] ?? null;
$ruta_foto_docente = !empty($foto_perfil_docente) ? '../uploads/perfiles/' . $foto_perfil_docente : 'https://placehold.co/40x40/ff7675/white?text=👨';

$id_juego = isset($_GET['id_juego']) ? intval($_GET['id_juego']) : 0;

if ($id_juego <= 0) {
    header('Location: juegos_docente.php');
    exit;
}

// Obtener datos del juego
$query = "
    SELECT 
        j.*,
        c.nombre AS curso,
        m.nombre AS materia,
        g.nombre AS grupo
    FROM conecta_juegos j
    JOIN cursos c ON j.id_curso = c.id_curso
    JOIN materias m ON c.id_materia = m.id_materia
    JOIN grupos g ON c.id_grupo = g.id_grupo
    WHERE j.id_juego = ? AND j.id_docente = ?
";

$stmt = $conexion->prepare($query);
$stmt->bind_param("ii", $id_juego, $id_docente);
$stmt->execute();
$resultado = $stmt->get_result();
$juego = $resultado->fetch_assoc();
$stmt->close();

if (!$juego) {
    header('Location: juegos_docente.php');
    exit;
}

// Obtener parejas del juego
$query_parejas = "
    SELECT * FROM conecta_parejas 
    WHERE id_juego = ? 
    ORDER BY orden ASC
";

$stmt_parejas = $conexion->prepare($query_parejas);
$stmt_parejas->bind_param("i", $id_juego);
$stmt_parejas->execute();
$result_parejas = $stmt_parejas->get_result();
$parejas = $result_parejas->fetch_all(MYSQLI_ASSOC);
$stmt_parejas->close();

// Obtener estadísticas de asignaciones
$query_stats = "
    SELECT 
        COUNT(*) AS total_asignados,
        SUM(CASE WHEN estado = 'Completado' THEN 1 ELSE 0 END) AS completados,
        SUM(CASE WHEN estado = 'En_proceso' THEN 1 ELSE 0 END) AS en_proceso
    FROM conecta_asignaciones
    WHERE id_juego = ?
";

$stmt_stats = $conexion->prepare($query_stats);
$stmt_stats->bind_param("i", $id_juego);
$stmt_stats->execute();
$result_stats = $stmt_stats->get_result();
$stats = $result_stats->fetch_assoc();
$stmt_stats->close();

// Procesar publicación
$mensaje = '';
$tipo_mensaje = '';

if (isset($_POST['publicar']) && $juego['estado'] === 'Borrador') {
    if (count($parejas) < 2) {
        $mensaje = 'Debes agregar al menos 2 parejas antes de publicar.';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Actualizar estado
            $update = $conexion->prepare("UPDATE conecta_juegos SET estado = 'Publicado' WHERE id_juego = ?");
            $update->bind_param("i", $id_juego);
            $update->execute();
            $update->close();
            
            // Asignar a alumnos del curso
            $query_alumnos = "
                SELECT id_alumno FROM inscripciones 
                WHERE id_curso = ? AND estado = 'Activo'
            ";
            $stmt_alumnos = $conexion->prepare($query_alumnos);
            $stmt_alumnos->bind_param("i", $juego['id_curso']);
            $stmt_alumnos->execute();
            $result_alumnos = $stmt_alumnos->get_result();
            
            $asignados = 0;
            while ($alumno = $result_alumnos->fetch_assoc()) {
                $insert = $conexion->prepare("
                    INSERT INTO conecta_asignaciones (id_juego, id_alumno, estado) 
                    VALUES (?, ?, 'Pendiente')
                    ON DUPLICATE KEY UPDATE estado = 'Pendiente'
                ");
                $insert->bind_param("ii", $id_juego, $alumno['id_alumno']);
                $insert->execute();
                $asignados++;
                $insert->close();
            }
            $stmt_alumnos->close();
            
            $mensaje = "Juego publicado correctamente. Asignado a $asignados alumno(s).";
            $tipo_mensaje = 'success';
            
            // Recargar datos
            $juego['estado'] = 'Publicado';
            
        } catch (Exception $e) {
            $mensaje = 'Error al publicar: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

$conexion->close();

function getIconoModo($modo) {
    switch ($modo) {
        case 'Memoria': return 'fa-solid fa-grid-2';
        case 'Relacionar': return 'fa-solid fa-arrow-right-arrow-left';
        case 'Clasificar': return 'fa-solid fa-layer-group';
        case 'Secuencia': return 'fa-solid fa-list-ol';
        default: return 'fa-solid fa-gamepad';
    }
}

function getColorEstado($estado) {
    switch ($estado) {
        case 'Publicado': return 'badge-success';
        case 'Cerrado': return 'badge-secondary';
        default: return 'badge-warning';
    }
}

function getIconoEstado($estado) {
    switch ($estado) {
        case 'Publicado': return 'fa-regular fa-circle-check';
        case 'Cerrado': return 'fa-solid fa-lock';
        default: return 'fa-solid fa-pen';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Juego - Docente</title>
    
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    
    <style>
        .detalle-container {
            padding: 20px 30px;
            width: 100%;
            max-width: 100%;
            margin: 0;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #3b71f3;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .card-detalle {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        
        .card-detalle .header-info {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .card-detalle .header-info .icono-grande {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #3b71f3;
            flex-shrink: 0;
        }
        
        .card-detalle .header-info .info-titulo {
            flex: 1;
        }
        
        .card-detalle .header-info .info-titulo h2 {
            margin: 0 0 5px 0;
            font-size: 22px;
            color: #1e293b;
        }
        
        .card-detalle .header-info .info-titulo .tema {
            color: #64748b;
            font-size: 14px;
        }
        
        .card-detalle .header-info .info-titulo .descripcion {
            color: #475569;
            margin-top: 8px;
            font-size: 14px;
        }
        
        .grid-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #e2e8f0;
        }
        
        .grid-info .item {
            display: flex;
            flex-direction: column;
        }
        
        .grid-info .item .label {
            font-size: 11px;
            color: #94a3b8;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .grid-info .item .value {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-top: 2px;
        }
        
        .grid-info .item .value i {
            color: #3b71f3;
            margin-right: 4px;
        }
        
        .badge-estado {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-secondary {
            background: #e5e7eb;
            color: #374151;
        }
        
        .list-parejas {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .card-pareja {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px 20px;
            border: 1px solid #e2e8f0;
        }
        
        .card-pareja .pareja-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }
        
        .card-pareja .pareja-header .numero {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: #3b71f3;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }
        
        .card-pareja .pareja-header .categoria {
            background: #eff6ff;
            padding: 2px 12px;
            border-radius: 12px;
            font-size: 12px;
            color: #3b71f3;
            font-weight: 600;
        }
        
        .card-pareja .pareja-header .puntos {
            margin-left: auto;
            background: #fef3c7;
            padding: 2px 12px;
            border-radius: 12px;
            font-size: 12px;
            color: #92400e;
            font-weight: 600;
        }
        
        .card-pareja .elementos {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .card-pareja .elementos .elemento {
            flex: 1;
            background: white;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #e2e8f0;
            font-weight: 600;
            color: #1e293b;
        }
        
        .card-pareja .elementos .conector {
            color: #3b71f3;
            font-size: 20px;
        }
        
        .card-pareja .explicacion {
            margin-top: 10px;
            font-size: 13px;
            color: #64748b;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
        }
        
        .card-pareja .explicacion i {
            color: #3b71f3;
            margin-right: 6px;
        }
        
        .acciones-juego {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #3b71f3;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2a5bd6;
        }
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .btn-success {
            background: #22c55e;
            color: white;
        }
        
        .btn-success:hover {
            background: #16a34a;
        }
        
        .btn-success:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        
        .sin-parejas {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }
        
        .sin-parejas i {
            font-size: 48px;
            display: block;
            margin-bottom: 15px;
        }
        
        .sin-parejas h4 {
            color: #1e293b;
            margin: 0 0 5px 0;
        }
        
        .stats-asignaciones {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 12px;
            margin-top: 15px;
        }
        
        .stats-asignaciones .stat {
            text-align: center;
            padding: 10px;
            background: #f8fafc;
            border-radius: 10px;
        }
        
        .stats-asignaciones .stat .num {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .stats-asignaciones .stat .label {
            font-size: 11px;
            color: #64748b;
        }
        
        @media (max-width: 768px) {
            .detalle-container {
                padding: 15px;
            }
            
            .card-detalle {
                padding: 18px 15px;
            }
            
            .card-detalle .header-info {
                flex-direction: column;
                align-items: stretch;
            }
            
            .card-pareja .elementos {
                flex-direction: column;
            }
            
            .card-pareja .elementos .conector {
                transform: rotate(90deg);
            }
            
            .acciones-juego {
                flex-direction: column;
            }
            
            .acciones-juego .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logo_g.png" alt="Logo Aulamos" class="logo-img">
        </div>
        
        <nav class="menu">
            <a href="docente_dashboard.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="crear_recurso.php" class="menu-item"><i class="fa-solid fa-medal"></i> Crear Recurso</a>
            <a href="mis_recursos.php" class="menu-item"><i class="fa-solid fa-folder-open"></i> Mis Recursos</a>
            <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
            <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluación</a>
            <a href="crear_juego.php" class="menu-item"><i class="fa-solid fa-gamepad"></i> Crear Juego</a>
            <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
            <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
            <a href="pasarlista.php" class="menu-item"><i class="fa-solid fa-bars"></i> Pasar Lista</a>
            <a href="juegos_docente.php" class="menu-item active"><i class="fa-solid fa-gamepad"></i> Conecta y Aprende</a>
            
            <div class="menu-spacer"></div>
            <button class="btn-accessibility-main" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
            <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        
        <!-- ENCABEZADO -->
        <header class="content-header">
            <div class="welcome-text">
                <h1>Detalle del juego</h1>
                <p>Consulta la configuración y las parejas registradas</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-assistant" onclick="window.location.href='../Alumno/ChatbotDocente.php?rol=docente'">
                    Asistente Virtual <span class="robot-icon">🤖</span>
                </button>
                <div class="icon-bell-container">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <a href="mi_perfil_d.php" class="user-profile">
                    <img src="<?php echo $ruta_foto_docente; ?>" alt="Avatar Docente" class="avatar">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($nombre_docente); ?></span>
                        <span class="user-role">Docente</span>
                    </div>
                </a>
            </div>
        </header>

        <div class="detalle-container">
            
            <a href="juegos_docente.php" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Volver a mis juegos
            </a>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>
            
            <!-- Información del juego -->
            <div class="card-detalle">
                <div class="header-info">
                    <div class="icono-grande">
                        <i class="<?php echo getIconoModo($juego['modo']); ?>"></i>
                    </div>
                    <div class="info-titulo">
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <h2><?php echo htmlspecialchars($juego['titulo']); ?></h2>
                            <span class="badge-estado <?php echo getColorEstado($juego['estado']); ?>">
                                <i class="<?php echo getIconoEstado($juego['estado']); ?>"></i>
                                <?php echo $juego['estado']; ?>
                            </span>
                        </div>
                        <?php if ($juego['tema']): ?>
                            <div class="tema"><i class="fa-regular fa-tag"></i> <?php echo htmlspecialchars($juego['tema']); ?></div>
                        <?php endif; ?>
                        <?php if ($juego['descripcion']): ?>
                            <div class="descripcion"><?php echo nl2br(htmlspecialchars($juego['descripcion'])); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="grid-info">
                    <div class="item">
                        <span class="label">Curso</span>
                        <span class="value"><?php echo htmlspecialchars($juego['curso']); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Materia</span>
                        <span class="value"><?php echo htmlspecialchars($juego['materia']); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Grupo</span>
                        <span class="value"><?php echo htmlspecialchars($juego['grupo']); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Modo</span>
                        <span class="value"><i class="<?php echo getIconoModo($juego['modo']); ?>"></i> <?php echo $juego['modo']; ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Modalidad</span>
                        <span class="value"><i class="fa-regular fa-user"></i> <?php echo $juego['modalidad']; ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Puntos por acierto</span>
                        <span class="value"><i class="fa-regular fa-star"></i> <?php echo $juego['puntos_por_acierto']; ?></span>
                    </div>
                    <?php if ($juego['tiempo_limite_seg']): ?>
                        <div class="item">
                            <span class="label">Tiempo límite</span>
                            <span class="value"><i class="fa-regular fa-clock"></i> <?php echo $juego['tiempo_limite_seg']; ?>s</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($juego['intentos_maximos']): ?>
                        <div class="item">
                            <span class="label">Intentos máximos</span>
                            <span class="value"><i class="fa-regular fa-repeat"></i> <?php echo $juego['intentos_maximos']; ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="item">
                        <span class="label">Retroalimentación</span>
                        <span class="value"><?php echo $juego['mostrar_retroalimentacion'] ? '<i class="fa-regular fa-check-circle" style="color: #22c55e;"></i> Activada' : '<i class="fa-regular fa-circle" style="color: #94a3b8;"></i> Desactivada'; ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Parejas</span>
                        <span class="value"><i class="fa-regular fa-grip"></i> <?php echo count($parejas); ?></span>
                    </div>
                </div>
                
                <?php if ($juego['estado'] === 'Publicado'): ?>
                    <div class="stats-asignaciones">
                        <div class="stat">
                            <div class="num"><?php echo $stats['total_asignados'] ?? 0; ?></div>
                            <div class="label">Asignados</div>
                        </div>
                        <div class="stat">
                            <div class="num"><?php echo $stats['completados'] ?? 0; ?></div>
                            <div class="label">Completados</div>
                        </div>
                        <div class="stat">
                            <div class="num"><?php echo $stats['en_proceso'] ?? 0; ?></div>
                            <div class="label">En proceso</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Parejas -->
            <div class="card-detalle">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                    <h3 style="margin: 0; font-size: 18px; color: #1e293b;">
                        <i class="fa-regular fa-grip"></i> Parejas registradas
                        <span style="font-size: 14px; color: #64748b; font-weight: 400;">(<?php echo count($parejas); ?>)</span>
                    </h3>
                    <?php if ($juego['estado'] === 'Borrador'): ?>
                        <a href="editar_parejas_juego.php?id_juego=<?php echo $id_juego; ?>" class="btn btn-secondary" style="padding: 8px 16px; font-size: 13px;">
                            <i class="fa-solid fa-pen"></i> Editar parejas
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($parejas)): ?>
                    <div class="sin-parejas">
                        <i class="fa-regular fa-grip"></i>
                        <h4>Todavía no hay parejas</h4>
                        <p>Agrega al menos 2 parejas para poder publicar el juego.</p>
                        <?php if ($juego['estado'] === 'Borrador'): ?>
                            <a href="editar_parejas_juego.php?id_juego=<?php echo $id_juego; ?>" class="btn btn-primary" style="margin-top: 10px;">
                                <i class="fa-solid fa-plus"></i> Agregar parejas
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="list-parejas">
                        <?php foreach ($parejas as $index => $pareja): ?>
                            <div class="card-pareja">
                                <div class="pareja-header">
                                    <span class="numero"><?php echo $index + 1; ?></span>
                                    <?php if ($pareja['categoria']): ?>
                                        <span class="categoria"><?php echo htmlspecialchars($pareja['categoria']); ?></span>
                                    <?php endif; ?>
                                    <span class="puntos"><i class="fa-regular fa-star"></i> <?php echo $pareja['puntos']; ?> pts</span>
                                </div>
                                <div class="elementos">
                                    <div class="elemento">
                                        <?php echo htmlspecialchars($pareja['elemento_a_texto'] ?? 'Multimedia'); ?>
                                    </div>
                                    <div class="conector"><i class="fa-solid fa-arrow-right"></i></div>
                                    <div class="elemento">
                                        <?php echo htmlspecialchars($pareja['elemento_b_texto'] ?? 'Multimedia'); ?>
                                    </div>
                                </div>
                                <?php if ($pareja['explicacion']): ?>
                                    <div class="explicacion">
                                        <i class="fa-regular fa-circle-info"></i>
                                        <?php echo htmlspecialchars($pareja['explicacion']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Acciones -->
            <?php if ($juego['estado'] === 'Borrador'): ?>
                <div class="card-detalle">
                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <div style="flex: 1;">
                            <h4 style="margin: 0; color: #1e293b;">¿Terminaste el juego?</h4>
                            <p style="margin: 5px 0 0 0; color: #64748b; font-size: 14px;">
                                Al publicarlo se asignará automáticamente a los alumnos activos del curso.
                            </p>
                        </div>
                        <form method="POST" style="margin: 0;">
                            <button type="submit" name="publicar" class="btn btn-success" <?php echo count($parejas) < 2 ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-rocket"></i> Publicar juego
                            </button>
                        </form>
                    </div>
                    <?php if (count($parejas) < 2): ?>
                        <p style="margin: 10px 0 0 0; color: #dc2626; font-size: 13px;">
                            <i class="fa-regular fa-circle-exclamation"></i> Se necesitan al menos 2 parejas para publicar.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($juego['estado'] === 'Publicado'): ?>
                <div class="card-detalle" style="background: #dcfce7; border-color: #86efac;">
                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <i class="fa-regular fa-circle-check" style="font-size: 28px; color: #166534;"></i>
                        <div style="flex: 1;">
                            <h4 style="margin: 0; color: #166534;">Juego publicado</h4>
                            <p style="margin: 5px 0 0 0; color: #15803d; font-size: 14px;">
                                Los alumnos asignados ya pueden verlo desde su cuenta.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
        
        <!-- BARRA DE ACCESIBILIDAD -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>
        
    </main>
</div>

<!-- BOTÓN FLOTANTE DE ACCESIBILIDAD -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<script src="jss/docente_dashboard.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>

</body>
</html>