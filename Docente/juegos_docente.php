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

// Consultar juegos del docente
$query = "
    SELECT 
        j.id_juego,
        j.titulo,
        j.tema,
        j.modo,
        j.modalidad,
        j.estado,
        j.fecha_creacion,
        c.nombre AS curso,
        m.nombre AS materia,
        (SELECT COUNT(*) FROM conecta_parejas WHERE id_juego = j.id_juego) AS total_parejas
    FROM conecta_juegos j
    JOIN cursos c ON j.id_curso = c.id_curso
    JOIN materias m ON c.id_materia = m.id_materia
    WHERE j.id_docente = ?
    ORDER BY j.fecha_creacion DESC
";

$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $id_docente);
$stmt->execute();
$resultado = $stmt->get_result();
$juegos = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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

$total_juegos = count($juegos);
$publicados = 0;
$borradores = 0;
foreach ($juegos as $juego) {
    if ($juego['estado'] === 'Publicado') $publicados++;
    elseif ($juego['estado'] === 'Borrador') $borradores++;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Juegos - Docente</title>
    
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .header-juegos p {
            margin: 5px 0 0 0;
            color: #64748b;
        }
        
        .btn-crear-juego {
            background: #3b71f3;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
            text-decoration: none;
        }
        
        .btn-crear-juego:hover {
            background: #2a5bd6;
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
            cursor: pointer;
        }
        
        .card-juego:hover {
            border-color: #3b71f3;
            box-shadow: 0 4px 12px rgba(59, 113, 243, 0.1);
            transform: translateY(-2px);
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
        
        .badge-secondary {
            background: #e5e7eb;
            color: #374151;
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
        
        .card-juego-footer .ver-detalles {
            color: #3b71f3;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .card-juego-footer .ver-detalles i {
            font-size: 14px;
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
            margin: 0 0 20px 0;
        }
        
        /* Main content ocupa todo */
        .main-content {
            padding: 0 !important;
            width: 100%;
            max-width: 100%;
        }
        
        .dashboard-container {
            width: 100%;
            max-width: 100%;
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
        
        .icon-bell-container {
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
        
        @media (max-width: 768px) {
            .header-juegos {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn-crear-juego {
                justify-content: center;
            }
            
            .stats-grid-juegos {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .card-juego-header {
                flex-wrap: wrap;
            }
            
            .juegos-container {
                padding: 15px;
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
                <h1>Conecta y Aprende</h1>
                <p>Gamificación educativa para tus estudiantes</p>
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

        <div class="juegos-container">
            
            <!-- HEADER -->
            <div class="header-juegos">
                <div>
                    <h1><i class="fa-solid fa-gamepad" style="color: #3b71f3;"></i> Mis juegos</h1>
                    <p>Crea y administra actividades de Conecta y Aprende</p>
                </div>
                <a href="crear_juego.php" class="btn-crear-juego">
                    <i class="fa-solid fa-plus"></i> Crear juego
                </a>
            </div>
            
            <!-- ESTADÍSTICAS -->
            <div class="stats-grid-juegos">
                <div class="stat-card-juego">
                    <div class="numero azul"><?php echo $total_juegos; ?></div>
                    <div class="etiqueta">Total juegos</div>
                </div>
                <div class="stat-card-juego">
                    <div class="numero verde"><?php echo $publicados; ?></div>
                    <div class="etiqueta"><i class="fa-regular fa-circle-check" style="color: #22c55e;"></i> Publicados</div>
                </div>
                <div class="stat-card-juego">
                    <div class="numero naranja"><?php echo $borradores; ?></div>
                    <div class="etiqueta"><i class="fa-solid fa-pen" style="color: #f59e0b;"></i> Borradores</div>
                </div>
            </div>
            
            <!-- LISTA DE JUEGOS -->
            <?php if (empty($juegos)): ?>
                <div class="empty-state-juegos">
                    <span class="icono">🎮</span>
                    <h3>Todavía no tienes juegos</h3>
                    <p>Crea tu primer juego de Conecta y Aprende para tus estudiantes.</p>
                    <a href="crear_juego.php" class="btn-crear-juego" style="display: inline-flex;">
                        <i class="fa-solid fa-plus"></i> Crear juego
                    </a>
                </div>
            <?php else: ?>
                <div class="list-juegos">
                    <?php foreach ($juegos as $juego): ?>
                        <div class="card-juego" onclick="window.location.href='detalle_juego_docente.php?id_juego=<?php echo $juego['id_juego']; ?>'">
                            <div class="card-juego-header">
                                <div class="card-juego-icono">
                                    <i class="<?php echo getIconoModo($juego['modo']); ?>"></i>
                                </div>
                                <div class="card-juego-titulo">
                                    <h3><?php echo htmlspecialchars($juego['titulo']); ?></h3>
                                    <?php if ($juego['tema']): ?>
                                        <div class="tema"><?php echo htmlspecialchars($juego['tema']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <span class="badge-estado <?php echo getColorEstado($juego['estado']); ?>">
                                    <i class="<?php echo getIconoEstado($juego['estado']); ?>"></i>
                                    <?php echo $juego['estado']; ?>
                                </span>
                            </div>
                            
                            <div class="card-juego-info">
                                <span class="info-item">
                                    <i class="fa-regular fa-bookmark"></i>
                                    <strong><?php echo htmlspecialchars($juego['materia']); ?></strong>
                                </span>
                                <span class="info-item">
                                    <i class="fa-regular fa-building"></i>
                                    <?php echo htmlspecialchars($juego['curso']); ?>
                                </span>
                                <span class="info-item">
                                    <i class="<?php echo getIconoModo($juego['modo']); ?>"></i>
                                    <?php echo $juego['modo']; ?>
                                </span>
                                <span class="info-item">
                                    <i class="fa-regular fa-user"></i>
                                    <?php echo $juego['modalidad']; ?>
                                </span>
                                <span class="info-item">
                                    <i class="fa-regular fa-grip"></i>
                                    <?php echo $juego['total_parejas']; ?> parejas
                                </span>
                            </div>
                            
                            <div class="card-juego-footer">
                                <span class="ver-detalles">
                                    Ver detalles <i class="fa-solid fa-chevron-right"></i>
                                </span>
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

<script src="jss/docente_dashboard.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>

</body>
</html>