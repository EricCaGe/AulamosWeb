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

// Obtener juegos asignados al alumno
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
        j.estado,
        ja.id_asignacion,
        ja.estado AS asignacion_estado,
        ja.fecha_inicio,
        ja.fecha_finalizacion,
        ji.id_intento,
        ji.puntuacion,
        ji.parejas_correctas,
        ji.porcentaje,
        ji.numero_intento,
        ji.tiempo_segundos,
        c.nombre AS curso,
        m.nombre AS materia
    FROM conecta_asignaciones ja
    JOIN conecta_juegos j ON ja.id_juego = j.id_juego
    JOIN cursos c ON j.id_curso = c.id_curso
    JOIN materias m ON c.id_materia = m.id_materia
    LEFT JOIN (
        SELECT 
            id_asignacion,
            id_intento,
            puntuacion,
            parejas_correctas,
            porcentaje,
            numero_intento,
            tiempo_segundos,
            ROW_NUMBER() OVER (PARTITION BY id_asignacion ORDER BY id_intento DESC) AS rn
        FROM conecta_intentos
    ) ji ON ja.id_asignacion = ji.id_asignacion AND ji.rn = 1
    WHERE ja.id_alumno = ? AND j.estado = 'Publicado'
    ORDER BY ja.fecha_asignacion DESC
";

$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$resultado = $stmt->get_result();
$juegos = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Contar juegos por estado
$total_juegos = count($juegos);
$pendientes = 0;
$en_proceso = 0;
$completados = 0;

foreach ($juegos as $juego) {
    if ($juego['asignacion_estado'] === 'Pendiente') $pendientes++;
    elseif ($juego['asignacion_estado'] === 'En_proceso') $en_proceso++;
    elseif ($juego['asignacion_estado'] === 'Completado') $completados++;
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
        case 'Completado': return 'badge-success';
        case 'En_proceso': return 'badge-warning';
        default: return 'badge-pendiente';
    }
}

function getColorAsignacion($estado) {
    switch ($estado) {
        case 'Completado': return '#22c55e';
        case 'En_proceso': return '#f59e0b';
        default: return '#94a3b8';
    }
}

function getBadgeAsignacion($estado) {
    switch ($estado) {
        case 'Completado': return '<i class="fa-regular fa-check-circle"></i> Completado';
        case 'En_proceso': return '<i class="fa-regular fa-clock"></i> En proceso';
        default: return '<i class="fa-regular fa-hourglass"></i> Pendiente';
    }
}

function getTextoEstado($estado) {
    switch ($estado) {
        case 'Completado': return 'Completado';
        case 'En_proceso': return 'En proceso';
        default: return 'Pendiente';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conecta y Aprende - Alumno</title>
    
    <link rel="stylesheet" href="Style/Inicio.css">
    <link rel="stylesheet" href="Style/actividades.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- NUEVA ACCESIBILIDAD -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    
    <style>
        /* Estilos específicos para juegos */
        .juegos-container {
            padding: 20px 30px;
            width: 100%;
            max-width: 100%;
            margin: 0;
        }
        
        .header-juegos {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .header-juegos h1 {
            margin: 0;
            font-size: 24px;
            color: #1e293b;
        }
        
        .header-juegos h1 i {
            color: #3b71f3;
        }
        
        .header-juegos p {
            margin: 5px 0 0 0;
            color: #64748b;
        }
        
        .stats-grid-juegos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card-juego {
            background: white;
            padding: 18px 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        
        .stat-card-juego .numero {
            font-size: 28px;
            font-weight: 800;
            color: #1e293b;
        }
        
        .stat-card-juego .numero.azul {
            color: #3b71f3;
        }
        
        .stat-card-juego .numero.verde {
            color: #22c55e;
        }
        
        .stat-card-juego .numero.naranja {
            color: #f59e0b;
        }
        
        .stat-card-juego .numero.gris {
            color: #94a3b8;
        }
        
        .stat-card-juego .etiqueta {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        
        .list-juegos {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }
        
        .card-juego {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 20px 24px;
            transition: all 0.2s;
        }
        
        .card-juego:hover {
            border-color: #3b71f3;
            box-shadow: 0 4px 12px rgba(59, 113, 243, 0.1);
        }
        
        .card-juego-header {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .card-juego-icono {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #3b71f3;
            flex-shrink: 0;
        }
        
        .card-juego-titulo {
            flex: 1;
        }
        
        .card-juego-titulo h3 {
            margin: 0;
            font-size: 17px;
            color: #1e293b;
            font-weight: 700;
        }
        
        .card-juego-titulo .tema {
            font-size: 13px;
            color: #64748b;
            margin-top: 2px;
        }
        
        .card-juego-titulo .materia {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }
        
        .card-juego-titulo .materia i {
            color: #3b71f3;
            margin-right: 4px;
        }
        
        .badge-estado {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
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
        
        .badge-pendiente {
            background: #f1f5f9;
            color: #64748b;
        }
        
        .card-juego-info {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 20px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
        }
        
        .card-juego-info .info-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #64748b;
        }
        
        .card-juego-info .info-item i {
            color: #3b71f3;
            width: 16px;
        }
        
        .card-juego-info .info-item strong {
            color: #1e293b;
        }
        
        .card-juego-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            gap: 12px;
        }
        
        .card-juego-footer .btn-jugar {
            background: #3b71f3;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        
        .card-juego-footer .btn-jugar:hover {
            background: #2a5bd6;
        }
        
        .card-juego-footer .btn-jugar.completado {
            background: #22c55e;
        }
        
        .card-juego-footer .btn-jugar.completado:hover {
            background: #16a34a;
        }
        
        .card-juego-footer .btn-jugar.ver {
            background: #f1f5f9;
            color: #475569;
        }
        
        .card-juego-footer .btn-jugar.ver:hover {
            background: #e2e8f0;
        }
        
        .card-juego-footer .puntuacion {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .card-juego-footer .puntuacion i {
            color: #f59e0b;
        }
        
        .empty-state-juegos {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }
        
        .empty-state-juegos .icono {
            font-size: 64px;
            color: #cbd5e1;
            display: block;
            margin-bottom: 15px;
        }
        
        .empty-state-juegos h3 {
            color: #1e293b;
            margin: 0 0 8px 0;
        }
        
        .empty-state-juegos p {
            color: #64748b;
            margin: 0;
        }
        
        /* Estilos del encabezado y menú (heredados de Inicio.css) */
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
        
        @media (max-width: 768px) {
            .juegos-container {
                padding: 15px;
            }
            
            .header-juegos {
                flex-direction: column;
                align-items: stretch;
            }
            
            .stats-grid-juegos {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .card-juego-header {
                flex-wrap: wrap;
            }
            
            .content-header {
                padding: 15px;
            }
            
            .header-actions {
                flex-wrap: wrap;
                justify-content: flex-end;
            }
            
            .card-juego-footer {
                flex-direction: column;
                align-items: stretch;
            }
            
            .card-juego-footer .btn-jugar {
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
                <h1><i class="fa-solid fa-gamepad" style="color: #3b71f3;"></i> Conecta y Aprende</h1>
                <p>Juegos educativos asignados por tus docentes</p>
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

        <div class="juegos-container">
            
            <!-- HEADER -->
            <div class="header-juegos">
                <div>
                    <h1>Mis juegos</h1>
                    <p>Juega y aprende con actividades interactivas</p>
                </div>
            </div>
            
            <!-- ESTADÍSTICAS -->
            <div class="stats-grid-juegos">
                <div class="stat-card-juego">
                    <div class="numero azul"><?php echo $total_juegos; ?></div>
                    <div class="etiqueta">Total juegos</div>
                </div>
                <div class="stat-card-juego">
                    <div class="numero gris"><?php echo $pendientes; ?></div>
                    <div class="etiqueta"><i class="fa-regular fa-hourglass" style="color: #94a3b8;"></i> Pendientes</div>
                </div>
                <div class="stat-card-juego">
                    <div class="numero naranja"><?php echo $en_proceso; ?></div>
                    <div class="etiqueta"><i class="fa-regular fa-clock" style="color: #f59e0b;"></i> En proceso</div>
                </div>
                <div class="stat-card-juego">
                    <div class="numero verde"><?php echo $completados; ?></div>
                    <div class="etiqueta"><i class="fa-regular fa-check-circle" style="color: #22c55e;"></i> Completados</div>
                </div>
            </div>
            
            <!-- LISTA DE JUEGOS -->
            <?php if (empty($juegos)): ?>
                <div class="empty-state-juegos">
                    <span class="icono">🎮</span>
                    <h3>No tienes juegos asignados</h3>
                    <p>Tus docentes te asignarán juegos educativos para aprender jugando.</p>
                </div>
            <?php else: ?>
                <div class="list-juegos">
                    <?php foreach ($juegos as $juego): ?>
                        <div class="card-juego">
                            <div class="card-juego-header">
                                <div class="card-juego-icono">
                                    <i class="<?php echo getIconoModo($juego['modo']); ?>"></i>
                                </div>
                                <div class="card-juego-titulo">
                                    <h3><?php echo htmlspecialchars($juego['titulo']); ?></h3>
                                    <?php if ($juego['tema']): ?>
                                        <div class="tema"><?php echo htmlspecialchars($juego['tema']); ?></div>
                                    <?php endif; ?>
                                    <div class="materia">
                                        <i class="fa-regular fa-bookmark"></i> <?php echo htmlspecialchars($juego['materia']); ?>
                                    </div>
                                </div>
                                <span class="badge-estado <?php echo getColorEstado($juego['asignacion_estado']); ?>">
                                    <?php echo getBadgeAsignacion($juego['asignacion_estado']); ?>
                                </span>
                            </div>
                            
                            <div class="card-juego-info">
                                <span class="info-item">
                                    <i class="<?php echo getIconoModo($juego['modo']); ?>"></i>
                                    <strong><?php echo $juego['modo']; ?></strong>
                                </span>
                                <span class="info-item">
                                    <i class="fa-regular fa-user"></i>
                                    <?php echo $juego['modalidad']; ?>
                                </span>
                                <span class="info-item">
                                    <i class="fa-regular fa-star"></i>
                                    <?php echo $juego['puntos_por_acierto']; ?> pts por acierto
                                </span>
                                <?php if ($juego['asignacion_estado'] === 'Completado' && $juego['puntuacion'] !== null): ?>
                                    <span class="info-item">
                                        <i class="fa-regular fa-trophy" style="color: #f59e0b;"></i>
                                        <strong style="color: #f59e0b;"><?php echo $juego['puntuacion']; ?> pts</strong>
                                    </span>
                                    <span class="info-item">
                                        <i class="fa-regular fa-percent"></i>
                                        <strong><?php echo $juego['porcentaje']; ?>%</strong>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-juego-footer">
                                <?php if ($juego['asignacion_estado'] === 'Completado'): ?>
                                    <span class="puntuacion">
                                        <i class="fa-regular fa-trophy"></i> <?php echo $juego['puntuacion'] ?? 0; ?> pts
                                    </span>
                                    <a href="ver_resultado_juego.php?id_juego=<?php echo $juego['id_juego']; ?>&id_asignacion=<?php echo $juego['id_asignacion']; ?>" class="btn-jugar ver">
                                        <i class="fa-regular fa-eye"></i> Ver resultado
                                    </a>
                                <?php elseif ($juego['asignacion_estado'] === 'En_proceso'): ?>
                                    <span class="puntuacion">
                                        <i class="fa-regular fa-clock"></i> En progreso
                                    </span>
                                    <a href="jugar_juego.php?id_juego=<?php echo $juego['id_juego']; ?>&id_asignacion=<?php echo $juego['id_asignacion']; ?>" class="btn-jugar">
                                        <i class="fa-solid fa-play"></i> Continuar jugando
                                    </a>
                                <?php else: ?>
                                    <a href="jugar_juego.php?id_juego=<?php echo $juego['id_juego']; ?>&id_asignacion=<?php echo $juego['id_asignacion']; ?>" class="btn-jugar">
                                        <i class="fa-solid fa-play"></i> Jugar ahora
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
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

<script src="js/Inicio.js"></script>
<script src="../Accesibilidad/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>