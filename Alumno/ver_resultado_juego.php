<?php
session_start();

// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Alumno') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_alumno = $_SESSION['usuario']['id_usuario'];
$nombre_alumno = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];

// Obtener foto de perfil del alumno
$foto_perfil_alumno = $_SESSION['usuario']['foto_perfil'] ?? null;
$ruta_foto_alumno = !empty($foto_perfil_alumno) ? '../uploads/perfiles/' . $foto_perfil_alumno : 'https://placehold.co/40x40/3b71f3/white?text=👤';

$id_juego = isset($_GET['id_juego']) ? intval($_GET['id_juego']) : 0;
$id_asignacion = isset($_GET['id_asignacion']) ? intval($_GET['id_asignacion']) : 0;

if ($id_juego <= 0 || $id_asignacion <= 0) {
    header('Location: juegos_alumno.php');
    exit;
}

// Obtener datos del juego
$query = "
    SELECT 
        j.id_juego,
        j.titulo,
        j.descripcion,
        j.tema,
        j.modo,
        j.modalidad,
        j.puntos_por_acierto,
        j.mostrar_retroalimentacion,
        ja.id_asignacion,
        ja.estado AS asignacion_estado,
        ja.fecha_asignacion,
        ja.fecha_inicio,
        ja.fecha_finalizacion,
        c.nombre AS curso,
        m.nombre AS materia,
        (
            SELECT COUNT(*) FROM conecta_parejas WHERE id_juego = j.id_juego
        ) AS total_parejas
    FROM conecta_juegos j
    JOIN conecta_asignaciones ja ON j.id_juego = ja.id_juego
    JOIN cursos c ON j.id_curso = c.id_curso
    JOIN materias m ON c.id_materia = m.id_materia
    WHERE j.id_juego = ? AND ja.id_asignacion = ? AND ja.id_alumno = ?
";

$stmt = $conexion->prepare($query);
$stmt->bind_param("iii", $id_juego, $id_asignacion, $id_alumno);
$stmt->execute();
$resultado = $stmt->get_result();
$juego = $resultado->fetch_assoc();
$stmt->close();

if (!$juego) {
    header('Location: juegos_alumno.php?error=Juego no encontrado');
    exit;
}

// Obtener el último intento (consulta separada)
$query_intento = "
    SELECT 
        id_intento,
        numero_intento,
        puntuacion,
        parejas_correctas,
        errores,
        tiempo_segundos,
        porcentaje,
        fecha_inicio,
        fecha_fin
    FROM conecta_intentos 
    WHERE id_asignacion = ? 
    ORDER BY id_intento DESC 
    LIMIT 1
";
$stmt_intento = $conexion->prepare($query_intento);
$stmt_intento->bind_param("i", $id_asignacion);
$stmt_intento->execute();
$result_intento = $stmt_intento->get_result();
$intento = $result_intento->fetch_assoc();
$stmt_intento->close();

// Obtener historial de intentos
$query_historial = "
    SELECT 
        id_intento,
        numero_intento,
        puntuacion,
        parejas_correctas,
        errores,
        tiempo_segundos,
        porcentaje,
        fecha_inicio,
        fecha_fin
    FROM conecta_intentos 
    WHERE id_asignacion = ? 
    ORDER BY id_intento DESC
";
$stmt_historial = $conexion->prepare($query_historial);
$stmt_historial->bind_param("i", $id_asignacion);
$stmt_historial->execute();
$result_historial = $stmt_historial->get_result();
$historial = $result_historial->fetch_all(MYSQLI_ASSOC);
$stmt_historial->close();

// Obtener estadísticas de todos los intentos
$total_intentos = count($historial);
$mejor_puntuacion = 0;
$mejor_porcentaje = 0;

foreach ($historial as $h) {
    if ($h['puntuacion'] > $mejor_puntuacion) {
        $mejor_puntuacion = $h['puntuacion'];
    }
    if ($h['porcentaje'] > $mejor_porcentaje) {
        $mejor_porcentaje = $h['porcentaje'];
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

function getColorPorcentaje($porcentaje) {
    if ($porcentaje >= 80) return '#22c55e';
    if ($porcentaje >= 60) return '#f59e0b';
    return '#dc2626';
}

function getEmojiPorcentaje($porcentaje) {
    if ($porcentaje >= 90) return '🏆';
    if ($porcentaje >= 75) return '⭐';
    if ($porcentaje >= 60) return '🌟';
    if ($porcentaje >= 40) return '💪';
    return '📚';
}

function getMensajePorcentaje($porcentaje) {
    if ($porcentaje >= 90) return '¡Excelente! Eres un experto.';
    if ($porcentaje >= 75) return '¡Muy bien! Sigue así.';
    if ($porcentaje >= 60) return 'Buen trabajo. Puedes mejorar.';
    if ($porcentaje >= 40) return 'Sigue practicando.';
    return 'No te rindas, inténtalo de nuevo.';
}

function formatearTiempo($segundos) {
    if (!$segundos) return '--:--';
    $minutos = floor($segundos / 60);
    $segundos_restantes = $segundos % 60;
    return sprintf('%02d:%02d', $minutos, $segundos_restantes);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado - <?php echo htmlspecialchars($juego['titulo']); ?></title>
    
    <link rel="stylesheet" href="Style/Inicio.css">
    <link rel="stylesheet" href="Style/actividades.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    
    <style>
        .resultado-container {
            padding: 20px 30px;
            width: 100%;
            max-width: 100%;
            margin: 0;
        }
        
        .resultado-header {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        
        .resultado-header .game-info {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .resultado-header .game-info .icono-grande {
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
        
        .resultado-header .game-info .titulo {
            flex: 1;
        }
        
        .resultado-header .game-info .titulo h2 {
            margin: 0 0 5px 0;
            font-size: 22px;
            color: #1e293b;
        }
        
        .resultado-header .game-info .titulo .tema {
            color: #64748b;
            font-size: 14px;
        }
        
        .resultado-header .game-info .titulo .materia {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }
        
        .resultado-header .game-info .titulo .materia i {
            color: #3b71f3;
        }
        
        .badge-completado {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            background: #dcfce7;
            color: #166534;
        }
        
        /* Resumen principal */
        .resumen-principal {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .resumen-principal .stat-card {
            background: #f8fafc;
            padding: 15px 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .resumen-principal .stat-card .numero {
            font-size: 32px;
            font-weight: 800;
            color: #1e293b;
        }
        
        .resumen-principal .stat-card .numero.puntos {
            color: #f59e0b;
        }
        
        .resumen-principal .stat-card .numero.verde {
            color: #22c55e;
        }
        
        .resumen-principal .stat-card .numero.rojo {
            color: #dc2626;
        }
        
        .resumen-principal .stat-card .numero.azul {
            color: #3b71f3;
        }
        
        .resumen-principal .stat-card .label {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        
        /* Tarjeta de resultado general */
        .resultado-general {
            display: flex;
            align-items: center;
            gap: 30px;
            background: #f8fafc;
            padding: 24px;
            border-radius: 16px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }
        
        .resultado-general .circulo-progreso {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
        }
        
        .resultado-general .circulo-progreso .porcentaje-texto {
            font-size: 32px;
            font-weight: 800;
            color: #1e293b;
            z-index: 1;
        }
        
        .resultado-general .circulo-progreso .porcentaje-texto .simbolo {
            font-size: 18px;
            color: #64748b;
        }
        
        .resultado-general .resultado-mensaje {
            flex: 1;
        }
        
        .resultado-general .resultado-mensaje h3 {
            margin: 0 0 8px 0;
            font-size: 22px;
            color: #1e293b;
        }
        
        .resultado-general .resultado-mensaje p {
            margin: 0;
            color: #64748b;
            font-size: 15px;
        }
        
        .resultado-general .resultado-mensaje .fecha {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 8px;
        }
        
        .resultado-general .resultado-emoji {
            font-size: 64px;
        }
        
        /* Historial de intentos */
        .historial-container {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        
        .historial-container .historial-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .historial-container .historial-header h3 {
            margin: 0;
            font-size: 18px;
            color: #1e293b;
        }
        
        .historial-container .historial-header h3 i {
            color: #3b71f3;
        }
        
        .tabla-historial {
            width: 100%;
            border-collapse: collapse;
        }
        
        .tabla-historial th {
            background: #f8fafc;
            padding: 10px 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .tabla-historial td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
            color: #1e293b;
        }
        
        .tabla-historial tr:hover td {
            background: #f8fafc;
        }
        
        .tabla-historial .badge-intento {
            background: #f1f5f9;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
        }
        
        .tabla-historial .badge-intento.mejor {
            background: #dcfce7;
            color: #166534;
        }
        
        .tabla-historial .badge-intento.mejor i {
            color: #f59e0b;
        }
        
        .tabla-historial .barra-progreso-mini {
            width: 100%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 4px;
        }
        
        .tabla-historial .barra-progreso-mini .fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.5s ease;
        }
        
        /* Acciones */
        .acciones-resultado {
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
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
        }
        
        /* Estilos del encabezado y menú */
        .user-profile {
            text-decoration: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 12px 5px 5px;
            border-radius: 50px;
            background: #f1f5f9;
            transition: background 0.2s;
        }
        
        .user-profile:hover {
            background: #e2e8f0;
        }
        
        .user-profile .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: #1e293b;
        }
        
        .user-role {
            font-size: 11px;
            color: #64748b;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .btn-assistant {
            background: #3b71f3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        
        .btn-assistant:hover {
            background: #2a5bd6;
        }
        
        .robot-icon {
            font-size: 18px;
        }
        
        .icon-bell {
            font-size: 20px;
            color: #64748b;
            cursor: pointer;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
            width: 100%;
        }
        
        .welcome-text h1 {
            margin: 0;
            font-size: 22px;
        }
        
        .welcome-text p {
            margin: 5px 0 0 0;
            color: #64748b;
        }
        
        .menu-spacer {
            flex: 1;
            height: 20px;
        }
        
        .btn-accessibility-main {
            width: 100%;
            background: #5a189a;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            margin: 10px 0;
            text-align: center;
        }
        
        .btn-accessibility-main:hover {
            background: #7b2cbf;
        }
        
        .menu-item.btn-logout {
            color: #dc2626 !important;
        }
        
        .menu-item.btn-logout:hover {
            background: #fee2e2 !important;
        }
        
        .main-content {
            padding: 0 !important;
            width: 100%;
            max-width: 100%;
        }
        
        .dashboard-container {
            width: 100%;
            max-width: 100%;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .resultado-container {
                padding: 15px;
            }
            
            .resultado-header .game-info {
                flex-direction: column;
                align-items: stretch;
            }
            
            .resultado-general {
                flex-direction: column;
                text-align: center;
            }
            
            .resultado-general .circulo-progreso {
                width: 100px;
                height: 100px;
            }
            
            .resultado-general .circulo-progreso .porcentaje-texto {
                font-size: 26px;
            }
            
            .resultado-general .resultado-emoji {
                font-size: 48px;
            }
            
            .resumen-principal {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .tabla-historial {
                font-size: 12px;
            }
            
            .tabla-historial th,
            .tabla-historial td {
                padding: 6px 8px;
            }
            
            .acciones-resultado {
                flex-direction: column;
            }
            
            .acciones-resultado .btn {
                width: 100%;
                justify-content: center;
            }
            
            .content-header {
                padding: 15px;
            }
            
            .header-actions {
                flex-wrap: wrap;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logogeneral.png" alt="Logo AulamosWeb" class="logo">
        </div>
        
        <nav class="menu">
            <a href="alumno.php" class="menu-item"><i class="fa-solid fa-house"></i> Inicio</a>
            <a href="actividades.php" class="menu-item"><i class="fa-solid fa-cubes"></i> Mis actividades</a>
            <a href="juegos_alumno.php" class="menu-item active"><i class="fa-solid fa-gamepad"></i> Conecta y Aprende</a>
            <a href="biblioteca.php" class="menu-item"><i class="fa-solid fa-book-open"></i> Biblioteca digital</a>
            <a href="avances.php" class="menu-item"><i class="fa-solid fa-pen-to-square"></i> Mis avances</a>
            <a href="ayuda.php" class="menu-item"><i class="fa-solid fa-circle-question"></i> Ayuda</a>
            <a href="mas.php" class="menu-item"> <i class="fa-solid fa-bars"></i> Más</a>
        </nav>
        
        <button class="btn-accessibility-main" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
        <div class="menu-spacer"></div>
        <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        
        <header class="content-header">
            <div class="welcome-text">
                <h1>Resultado del juego</h1>
                <p>Revisa tu desempeño en <?php echo htmlspecialchars($juego['titulo']); ?></p>
            </div>
            <div class="header-actions">
                <button class="btn-assistant" id="btn-asistente" onclick="window.open('chatbot.php', '_blank')">
                    Asistente Virtual <span class="robot-icon">🤖</span>
                </button>
                <div class="icon-bell">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <a href="perfil_alumno.php" class="user-profile">
                    <img src="<?php echo $ruta_foto_alumno; ?>" alt="Avatar Alumno" class="avatar">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($nombre_alumno); ?></span>
                        <span class="user-role">Alumno</span>
                    </div>
                </a>
            </div>
        </header>

        <div class="resultado-container">
            
            <!-- HEADER DEL RESULTADO -->
            <div class="resultado-header">
                <div class="game-info">
                    <div class="icono-grande">
                        <i class="<?php echo getIconoModo($juego['modo']); ?>"></i>
                    </div>
                    <div class="titulo">
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <h2><?php echo htmlspecialchars($juego['titulo']); ?></h2>
                            <span class="badge-completado">
                                <i class="fa-regular fa-check-circle"></i> Completado
                            </span>
                        </div>
                        <?php if ($juego['tema']): ?>
                            <div class="tema"><?php echo htmlspecialchars($juego['tema']); ?></div>
                        <?php endif; ?>
                        <div class="materia">
                            <i class="fa-regular fa-bookmark"></i> <?php echo htmlspecialchars($juego['materia']); ?>
                            <span style="margin-left: 12px;">
                                <i class="fa-regular fa-users"></i> <?php echo $juego['modalidad']; ?>
                            </span>
                            <span style="margin-left: 12px;">
                                <i class="<?php echo getIconoModo($juego['modo']); ?>"></i> <?php echo $juego['modo']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- RESULTADO GENERAL -->
            <?php if ($intento): ?>
                <?php 
                $porcentaje = $intento['porcentaje'] ?? 0;
                $color = getColorPorcentaje($porcentaje);
                $emoji = getEmojiPorcentaje($porcentaje);
                $mensaje = getMensajePorcentaje($porcentaje);
                ?>
                <div class="resultado-general">
                    <div class="circulo-progreso" style="background: radial-gradient(circle, rgba(<?php echo hexdec(substr($color, 1, 2)); ?>, <?php echo hexdec(substr($color, 3, 2)); ?>, <?php echo hexdec(substr($color, 5, 2)); ?>, 0.1) 60%, transparent 70%);">
                        <div class="porcentaje-texto">
                            <?php echo round($porcentaje); ?><span class="simbolo">%</span>
                        </div>
                    </div>
                    <div class="resultado-mensaje">
                        <h3><?php echo $emoji . ' ' . $mensaje; ?></h3>
                        <p>
                            Completaste <?php echo $intento['parejas_correctas']; ?> de <?php echo $juego['total_parejas']; ?> parejas correctamente.
                        </p>
                        <div class="fecha">
                            <i class="fa-regular fa-calendar"></i> 
                            Finalizado el <?php echo date('d M, Y \a \l\a\s H:i', strtotime($juego['fecha_finalizacion'] ?? 'now')); ?>
                        </div>
                    </div>
                    <div class="resultado-emoji">
                        <?php echo $emoji; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="resultado-general" style="justify-content: center;">
                    <div style="text-align: center; padding: 20px;">
                        <div style="font-size: 48px; margin-bottom: 10px;">📋</div>
                        <h3 style="margin: 0; color: #1e293b;">Aún no has jugado este juego</h3>
                        <p style="color: #64748b;">Completa el juego para ver tus resultados aquí.</p>
                        <a href="jugar_juego.php?id_juego=<?php echo $id_juego; ?>&id_asignacion=<?php echo $id_asignacion; ?>" class="btn btn-primary" style="margin-top: 15px;">
                            <i class="fa-solid fa-play"></i> Jugar ahora
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- RESUMEN PRINCIPAL -->
            <?php if ($intento): ?>
                <div class="resumen-principal">
                    <div class="stat-card">
                        <div class="numero puntos"><?php echo $intento['puntuacion']; ?></div>
                        <div class="label"><i class="fa-regular fa-star" style="color: #f59e0b;"></i> Puntos</div>
                    </div>
                    <div class="stat-card">
                        <div class="numero verde"><?php echo $intento['parejas_correctas']; ?></div>
                        <div class="label"><i class="fa-regular fa-check-circle" style="color: #22c55e;"></i> Aciertos</div>
                    </div>
                    <div class="stat-card">
                        <div class="numero rojo"><?php echo $intento['errores']; ?></div>
                        <div class="label"><i class="fa-regular fa-times-circle" style="color: #dc2626;"></i> Errores</div>
                    </div>
                    <div class="stat-card">
                        <div class="numero azul"><?php echo formatearTiempo($intento['tiempo_segundos']); ?></div>
                        <div class="label"><i class="fa-regular fa-clock" style="color: #3b71f3;"></i> Tiempo</div>
                    </div>
                    <div class="stat-card">
                        <div class="numero">#<?php echo $intento['numero_intento']; ?></div>
                        <div class="label"><i class="fa-regular fa-repeat"></i> Intento</div>
                    </div>
                    <div class="stat-card">
                        <div class="numero" style="color: <?php echo getColorPorcentaje($intento['porcentaje']); ?>;">
                            <?php echo round($intento['porcentaje']); ?>%
                        </div>
                        <div class="label"><i class="fa-regular fa-percent"></i> Precisión</div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- ESTADÍSTICAS GENERALES -->
            <?php if ($total_intentos > 0): ?>
                <div class="historial-container">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0;">
                        <div style="text-align: center;">
                            <div style="font-size: 20px; font-weight: 700; color: #1e293b;"><?php echo $total_intentos; ?></div>
                            <div style="font-size: 12px; color: #64748b;">Intentos totales</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 20px; font-weight: 700; color: #f59e0b;"><?php echo $mejor_puntuacion; ?></div>
                            <div style="font-size: 12px; color: #64748b;">Mejor puntuación</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 20px; font-weight: 700; color: #3b71f3;"><?php echo round($mejor_porcentaje); ?>%</div>
                            <div style="font-size: 12px; color: #64748b;">Mejor precisión</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 20px; font-weight: 700; color: #22c55e;">
                                <?php 
                                $mejor_tiempo = null;
                                foreach ($historial as $h) {
                                    if ($mejor_tiempo === null || $h['tiempo_segundos'] < $mejor_tiempo) {
                                        $mejor_tiempo = $h['tiempo_segundos'];
                                    }
                                }
                                echo formatearTiempo($mejor_tiempo);
                                ?>
                            </div>
                            <div style="font-size: 12px; color: #64748b;">Mejor tiempo</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- HISTORIAL DE INTENTOS -->
            <?php if (!empty($historial)): ?>
                <div class="historial-container">
                    <div class="historial-header">
                        <h3><i class="fa-regular fa-clock"></i> Historial de intentos</h3>
                        <span style="font-size: 13px; color: #64748b;"><?php echo $total_intentos; ?> intentos</span>
                    </div>
                    
                    <?php if (count($historial) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="tabla-historial">
                                <thead>
                                    <tr>
                                        <th>Intento</th>
                                        <th>Puntos</th>
                                        <th>Aciertos</th>
                                        <th>Errores</th>
                                        <th>Precisión</th>
                                        <th>Tiempo</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial as $item): 
                                        $es_mejor = ($item['puntuacion'] == $mejor_puntuacion && $mejor_puntuacion > 0);
                                        $porcentaje_item = round($item['porcentaje']);
                                        $color_item = getColorPorcentaje($porcentaje_item);
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="badge-intento <?php echo $es_mejor ? 'mejor' : ''; ?>">
                                                    #<?php echo $item['numero_intento']; ?>
                                                    <?php if ($es_mejor && $total_intentos > 1): ?>
                                                        <i class="fa-regular fa-trophy"></i>
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td><strong><?php echo $item['puntuacion']; ?></strong></td>
                                            <td><?php echo $item['parejas_correctas']; ?></td>
                                            <td><?php echo $item['errores']; ?></td>
                                            <td>
                                                <span style="color: <?php echo $color_item; ?>; font-weight: 700;">
                                                    <?php echo $porcentaje_item; ?>%
                                                </span>
                                                <div class="barra-progreso-mini">
                                                    <div class="fill" style="width: <?php echo $porcentaje_item; ?>%; background: <?php echo $color_item; ?>;"></div>
                                                </div>
                                            </td>
                                            <td><?php echo formatearTiempo($item['tiempo_segundos']); ?></td>
                                            <td style="font-size: 12px; color: #64748b;">
                                                <?php echo date('d M, H:i', strtotime($item['fecha_inicio'] ?? 'now')); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #64748b; padding: 20px 0;">No hay intentos registrados aún.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- ACCIONES -->
            <div class="acciones-resultado">
                <a href="juegos_alumno.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Volver a juegos
                </a>
                <?php if ($juego['asignacion_estado'] === 'Completado'): ?>
                    <a href="jugar_juego.php?id_juego=<?php echo $id_juego; ?>&id_asignacion=<?php echo $id_asignacion; ?>" class="btn btn-warning">
                        <i class="fa-solid fa-rotate-right"></i> Jugar de nuevo
                    </a>
                <?php endif; ?>
                <a href="juegos_alumno.php" class="btn btn-primary">
                    <i class="fa-solid fa-gamepad"></i> Ver más juegos
                </a>
            </div>
            
        </div>
        
        <!-- BARRA DE ACCESIBILIDAD -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>
        
    </main>
</div>

<!-- BOTÓN FLOTANTE DE ACCESIBILIDAD -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<script src="js/Inicio.js"></script>
<script src="../Accesibilidad/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>