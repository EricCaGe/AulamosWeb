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

// Obtener datos del juego y parejas
$query = "
    SELECT 
        j.id_juego,
        j.titulo,
        j.descripcion,
        j.tema,
        j.modo,
        j.modalidad,
        j.puntos_por_acierto,
        j.tiempo_limite_seg,
        j.intentos_maximos,
        j.mostrar_retroalimentacion,
        ja.id_asignacion,
        ja.estado AS asignacion_estado,
        ji.id_intento,
        ji.numero_intento,
        ji.puntuacion,
        ji.parejas_correctas,
        ji.errores,
        ji.movimientos,
        ji.porcentaje,
        ji.tiempo_segundos,
        c.nombre AS curso,
        m.nombre AS materia,
        (
            SELECT COUNT(*) FROM conecta_parejas WHERE id_juego = j.id_juego
        ) AS total_parejas
    FROM conecta_juegos j
    JOIN conecta_asignaciones ja ON j.id_juego = ja.id_juego
    JOIN cursos c ON j.id_curso = c.id_curso
    JOIN materias m ON c.id_materia = m.id_materia
    LEFT JOIN (
        SELECT 
            id_asignacion,
            id_intento,
            numero_intento,
            puntuacion,
            parejas_correctas,
            errores,
            movimientos,
            porcentaje,
            tiempo_segundos,
            ROW_NUMBER() OVER (PARTITION BY id_asignacion ORDER BY id_intento DESC) AS rn
        FROM conecta_intentos
    ) ji ON ja.id_asignacion = ji.id_asignacion AND ji.rn = 1
    WHERE j.id_juego = ? AND ja.id_asignacion = ? AND ja.id_alumno = ? AND j.estado = 'Publicado'
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

// Obtener parejas del juego (CON IMÁGENES)
$query_parejas = "
    SELECT 
        id_pareja,
        elemento_a_texto,
        elemento_a_imagen,
        elemento_a_audio,
        elemento_b_texto,
        elemento_b_imagen,
        elemento_b_audio,
        explicacion,
        categoria,
        puntos
    FROM conecta_parejas 
    WHERE id_juego = ? 
    ORDER BY orden ASC
";

$stmt_parejas = $conexion->prepare($query_parejas);
$stmt_parejas->bind_param("i", $id_juego);
$stmt_parejas->execute();
$result_parejas = $stmt_parejas->get_result();
$parejas = $result_parejas->fetch_all(MYSQLI_ASSOC);
$stmt_parejas->close();

$total_parejas = count($parejas);

// Verificar si el juego ya está completado
$ya_completado = $juego['asignacion_estado'] === 'Completado';

// Si está completado, redirigir a ver resultados
if ($ya_completado) {
    header('Location: ver_resultado_juego.php?id_juego=' . $id_juego . '&id_asignacion=' . $id_asignacion);
    exit;
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

// Función para obtener URL de imagen (CORREGIDA)
function getUrlImagen($ruta) {
    if (empty($ruta)) return null;
    if (strpos($ruta, 'http://') === 0 || strpos($ruta, 'https://') === 0) {
        return $ruta;
    }
    return '../' . ltrim($ruta, '/');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 🔥 FORZAR RECARGA DE CSS - SIN CACHÉ -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Jugar - <?php echo htmlspecialchars($juego['titulo']); ?></title>
    
    <link rel="stylesheet" href="Style/Inicio.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Style/actividades.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css?v=<?php echo time(); ?>">
    
    <style>
        /* ==========================================
           ESTILOS ACTUALIZADOS PARA IMÁGENES
           ========================================== */
        
        .jugar-container {
            padding: 20px 30px;
            width: 100%;
            max-width: 100%;
            margin: 0;
        }
        
        .game-header {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .game-header .info h2 {
            margin: 0;
            font-size: 20px;
            color: #1e293b;
        }
        
        .game-header .info .tema {
            color: #64748b;
            font-size: 14px;
            margin-top: 4px;
        }
        
        .game-header .info .materia {
            font-size: 13px;
            color: #64748b;
        }
        
        .game-header .game-stats {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .game-header .game-stats .stat {
            text-align: center;
        }
        
        .game-header .game-stats .stat .numero {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .game-header .game-stats .stat .label {
            font-size: 10px;
            color: #94a3b8;
            text-transform: uppercase;
        }
        
        .game-header .game-stats .stat .numero.puntos {
            color: #f59e0b;
        }
        
        .game-container {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            min-height: 400px;
        }
        
        .game-container .game-title {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .game-container .game-title h3 {
            margin: 0;
            color: #1e293b;
            font-size: 18px;
        }
        
        .game-container .game-title p {
            color: #64748b;
            font-size: 14px;
            margin: 5px 0 0 0;
        }
        
        /* ==========================================
           ESTILOS PARA MODO RELACIONAR (CON IMÁGENES)
           ========================================== */
        .game-relacionar {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .game-relacionar .columna {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .game-relacionar .columna h4 {
            margin: 0 0 10px 0;
            color: #64748b;
            font-size: 14px;
            text-align: center;
        }
        
        .game-relacionar .item {
            padding: 12px 16px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
            font-weight: 500;
            color: #1e293b;
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .game-relacionar .item .item-imagen {
            max-width: 60px;
            max-height: 40px;
            border-radius: 6px;
            object-fit: cover;
        }
        
        .game-relacionar .item:hover:not(.seleccionado):not(.correcto):not(.incorrecto) {
            border-color: #94a3b8;
            background: #f1f5f9;
        }
        
        .game-relacionar .item.seleccionado {
            border-color: #3b71f3;
            background: #eff6ff;
        }
        
        .game-relacionar .item.correcto {
            border-color: #22c55e;
            background: #dcfce7;
            color: #166534;
        }
        
        .game-relacionar .item.correcto i {
            color: #22c55e;
        }
        
        .game-relacionar .item.incorrecto {
            border-color: #dc2626;
            background: #fee2e2;
            color: #991b1b;
        }
        
        .game-relacionar .item.incorrecto i {
            color: #dc2626;
        }
        
        .game-relacionar .item.deshabilitado {
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .game-relacionar .item .explicacion {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
            font-weight: 400;
        }
        
        /* ==========================================
           ESTILOS PARA MODO MEMORIA (CON IMÁGENES)
           ========================================== */
        .game-memoria {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .game-memoria .carta {
            aspect-ratio: 1;
            background: #3b71f3;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 24px;
            color: white;
            font-weight: 700;
            user-select: none;
            position: relative;
            overflow: hidden;
        }
        
        .game-memoria .carta .carta-imagen {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .game-memoria .carta .carta-icono {
            font-size: 40px;
        }
        
        .game-memoria .carta .carta-texto {
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            padding: 4px;
        }
        
        .game-memoria .carta:hover:not(.volteada):not(.encontrada) {
            transform: scale(1.05);
        }
        
        .game-memoria .carta.volteada {
            background: #f8fafc;
            color: #1e293b;
            border: 2px solid #3b71f3;
        }
        
        .game-memoria .carta.encontrada {
            background: #dcfce7;
            border: 2px solid #22c55e;
            color: #166534;
            cursor: default;
        }
        
        .game-memoria .carta.encontrada .carta-icono {
            color: #22c55e;
        }
        
        .game-memoria .carta .contenido {
            display: none;
            text-align: center;
            padding: 8px;
            width: 100%;
            height: 100%;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .game-memoria .carta.volteada .contenido,
        .game-memoria .carta.encontrada .contenido {
            display: flex;
        }
        
        /* ==========================================
           ESTILOS PARA MODO SECUENCIA (CON IMÁGENES)
           ========================================== */
        .game-secuencia {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .game-secuencia .item-secuencia {
            padding: 14px 20px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
            color: #1e293b;
            font-weight: 500;
        }
        
        .game-secuencia .item-secuencia .secuencia-imagen {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .game-secuencia .item-secuencia:hover:not(.correcto):not(.incorrecto) {
            border-color: #94a3b8;
            background: #f1f5f9;
        }
        
        .game-secuencia .item-secuencia .posicion {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            color: #64748b;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        
        .game-secuencia .item-secuencia.correcto {
            border-color: #22c55e;
            background: #dcfce7;
        }
        
        .game-secuencia .item-secuencia.correcto .posicion {
            background: #22c55e;
            color: white;
        }
        
        .game-secuencia .item-secuencia.incorrecto {
            border-color: #dc2626;
            background: #fee2e2;
        }
        
        .game-secuencia .item-secuencia.incorrecto .posicion {
            background: #dc2626;
            color: white;
        }
        
        .game-secuencia .item-secuencia.deshabilitado {
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        /* ==========================================
           ESTILOS PARA MODO CLASIFICAR (CON IMÁGENES)
           ========================================== */
        .game-clasificar {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .game-clasificar .categoria {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            border: 2px dashed #e2e8f0;
            min-height: 150px;
        }
        
        .game-clasificar .categoria h4 {
            margin: 0 0 10px 0;
            color: #64748b;
            text-align: center;
            font-size: 14px;
        }
        
        .game-clasificar .categoria .items {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .game-clasificar .categoria .item-clasificar {
            padding: 8px 12px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .game-clasificar .categoria .item-clasificar .clasificar-imagen {
            max-width: 40px;
            max-height: 30px;
            border-radius: 4px;
            object-fit: cover;
        }
        
        .game-clasificar .categoria .item-clasificar:hover {
            border-color: #3b71f3;
            background: #eff6ff;
        }
        
        .game-clasificar .categoria .item-clasificar.correcto {
            border-color: #22c55e;
            background: #dcfce7;
            color: #166534;
        }
        
        .game-clasificar .categoria .item-clasificar.incorrecto {
            border-color: #dc2626;
            background: #fee2e2;
            color: #991b1b;
        }
        
        .game-clasificar .categoria .item-clasificar.deshabilitado {
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        /* ==========================================
           BOTONES Y CONTROLES
           ========================================== */
        .game-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .game-controls .btn {
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .game-controls .btn-primary {
            background: #3b71f3;
            color: white;
        }
        
        .game-controls .btn-primary:hover:not(:disabled) {
            background: #2a5bd6;
        }
        
        .game-controls .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .game-controls .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .game-controls .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .game-controls .btn-success {
            background: #22c55e;
            color: white;
        }
        
        .game-controls .btn-success:hover {
            background: #16a34a;
        }
        
        .feedback-message {
            padding: 14px 18px;
            border-radius: 10px;
            margin: 12px 0;
            font-weight: 500;
            display: none;
            white-space: pre-line;
            line-height: 1.6;
        }
        
        .feedback-message.show {
            display: block;
        }
        
        .feedback-message.success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        
        .feedback-message.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        
        .feedback-message.info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b71f3;
        }
        
        /* Estilos para el spinner */
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3b71f3;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .jugar-container {
                padding: 15px;
            }
            
            .game-header {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            
            .game-header .game-stats {
                justify-content: center;
            }
            
            .game-relacionar {
                grid-template-columns: 1fr;
            }
            
            .game-memoria {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }
            
            .game-clasificar {
                grid-template-columns: 1fr;
            }
            
            .game-controls {
                flex-direction: column;
            }
            
            .game-controls .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .game-memoria {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Estilos generales del encabezado */
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
        
        /* Base */
        .carta .contenido .carta-texto {
            font-size: 12px;
            font-weight: 600;
        }
        
        .carta .contenido .carta-imagen {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
            <a href="juegos_alumno.php" class="menu-item"><i class="fa-solid fa-gamepad"></i> Conecta y Aprende</a>
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
                <h1>Jugar</h1>
                <p><?php echo htmlspecialchars($juego['titulo']); ?></p>
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

        <div class="jugar-container">
            
            <!-- HEADER DEL JUEGO -->
            <div class="game-header">
                <div class="info">
                    <h2><?php echo htmlspecialchars($juego['titulo']); ?></h2>
                    <?php if ($juego['tema']): ?>
                        <div class="tema"><?php echo htmlspecialchars($juego['tema']); ?></div>
                    <?php endif; ?>
                    <div class="materia">
                        <i class="fa-regular fa-bookmark"></i> <?php echo htmlspecialchars($juego['materia']); ?>
                    </div>
                </div>
                <div class="game-stats">
                    <div class="stat">
                        <div class="numero" id="puntosDisplay">0</div>
                        <div class="label">Puntos</div>
                    </div>
                    <div class="stat">
                        <div class="numero" id="aciertosDisplay">0</div>
                        <div class="label">Aciertos</div>
                    </div>
                    <div class="stat">
                        <div class="numero" id="erroresDisplay">0</div>
                        <div class="label">Errores</div>
                    </div>
                    <div class="stat">
                        <div class="numero" id="tiempoDisplay">00:00</div>
                        <div class="label">Tiempo</div>
                    </div>
                    <?php if ($juego['total_parejas']): ?>
                        <div class="stat">
                            <div class="numero" id="progresoDisplay">0/<?php echo $juego['total_parejas']; ?></div>
                            <div class="label">Progreso</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- FEEDBACK -->
            <div id="feedbackMessage" class="feedback-message"></div>
            
            <!-- CONTENEDOR DEL JUEGO -->
            <div class="game-container">
                <div class="game-title">
                    <h3>Modo: <?php echo $juego['modo']; ?></h3>
                    <p>Relaciona los elementos correctamente</p>
                </div>
                
                <div id="gameArea">
                    <!-- El contenido se genera dinámicamente según el modo -->
                </div>
            </div>
            
            <!-- CONTROLES -->
            <div class="game-controls">
                <a href="juegos_alumno.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Salir
                </a>
                <div>
                    <button id="btnReiniciar" class="btn btn-secondary" onclick="reiniciarJuego()">
                        <i class="fa-solid fa-rotate-right"></i> Reiniciar
                    </button>
                    <button id="btnTerminar" class="btn btn-success" onclick="terminarJuego()" style="display: none;">
                        <i class="fa-solid fa-flag-checkered"></i> Terminar juego
                    </button>
                </div>
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

<!-- SCRIPTS -->
<script>
// =============================================
// CONFIGURACIÓN DEL JUEGO
// =============================================

const juegoData = <?php echo json_encode($juego); ?>;
const parejasData = <?php echo json_encode($parejas); ?>;
const idJuego = <?php echo $id_juego; ?>;
const idAsignacion = <?php echo $id_asignacion; ?>;
const modo = '<?php echo $juego['modo']; ?>';
const puntosPorAcierto = <?php echo $juego['puntos_por_acierto']; ?>;
const totalParejas = <?php echo $total_parejas; ?>;
const intentosMaximos = <?php echo $juego['intentos_maximos'] ?? 0; ?>;
// 🔥 CORREGIDO: Obtener el número de intento actual de la base de datos
const intentoActual = <?php echo $juego['numero_intento'] ?? 0; ?>;

// Función para obtener URL de imagen (CORREGIDA)
function getUrlImagen(ruta) {
    if (!ruta) return null;
    if (ruta.startsWith('http://') || ruta.startsWith('https://')) {
        return ruta;
    }
    return '../' + ruta.replace(/^\//, '');
}

let puntos = 0;
let aciertos = 0;
let errores = 0;
let tiempoSegundos = 0;
let timerInterval = null;
let juegoTerminado = false;
let parejasCompletadas = 0;

// =============================================
// INICIALIZAR JUEGO
// =============================================

document.addEventListener('DOMContentLoaded', function() {
    // Verificar si hay un parámetro para nuevo intento
    const urlParams = new URLSearchParams(window.location.search);
    const nuevoIntento = urlParams.get('nuevo_intento');
    const reiniciar = urlParams.get('reiniciar');
    
    // 🔥 FORZAR RECARGA DE CSS
    document.querySelectorAll('link[rel="stylesheet"]').forEach(function(link) {
        const href = link.getAttribute('href');
        if (href && href.includes('?v=')) {
            // Ya tiene versión, no hacer nada
        } else if (href && !href.includes('?v=')) {
            link.setAttribute('href', href + '?v=' + Date.now());
        }
    });
    
    if (nuevoIntento || reiniciar) {
        console.log('🔄 Iniciando nuevo intento');
        mostrarFeedback('🔄 Nuevo intento iniciado', 'info');
    }
    
    iniciarJuego();
    iniciarTimer();
    
    // Actualizar información del intento
    actualizarInfoIntento();
});

// =============================================
// FUNCIÓN PARA ACTUALIZAR INFORMACIÓN DEL INTENTO
// =============================================

function actualizarInfoIntento() {
    fetch('obtener_info_intento.php?id_asignacion=' + idAsignacion)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('📊 Intento actual:', data.numero_intento);
                // Actualizar el título del juego con el número de intento
                const titleElement = document.querySelector('.game-title h3');
                if (titleElement && data.numero_intento) {
                    const modoTexto = titleElement.textContent.replace(/\(Intento \d+\)/, '').trim();
                    titleElement.textContent = modoTexto + ' (Intento ' + data.numero_intento + ')';
                }
            }
        })
        .catch(error => console.error('Error al obtener intento:', error));
}

function iniciarJuego() {
    const gameArea = document.getElementById('gameArea');

    switch (modo) {
        case 'Relacionar':
            gameArea.innerHTML = generarRelacionar();
            break;

        case 'Memoria':
            gameArea.innerHTML = generarMemoria();
            break;

        case 'Secuencia':
            gameArea.innerHTML = generarSecuencia();
            break;

        case 'Clasificar':
            gameArea.innerHTML = generarClasificar();
            break;

        default:
            gameArea.innerHTML =
                '<p style="text-align:center; color:#64748b;">Modo no disponible</p>';
    }

    actualizarEstadisticas();
}


// =============================================
// OBTENER EXPLICACIÓN DEL DOCENTE
// =============================================

function obtenerExplicacionPareja(idPareja) {
    const pareja = parejasData.find(
        p =>
            Number(p.id_pareja) ===
            Number(idPareja)
    );

    if (
        !pareja ||
        !pareja.explicacion
    ) {
        return '';
    }

    return String(
        pareja.explicacion
    ).trim();
}


// =============================================
// MOSTRAR RETROALIMENTACIÓN CORRECTA
// =============================================

function mostrarRetroalimentacionCorrecta(
    idPareja,
    mensajeCorrecto
) {
    const retroalimentacionActiva =
        Number(
            juegoData.mostrar_retroalimentacion
        ) === 1;

    const explicacion =
        obtenerExplicacionPareja(
            idPareja
        );

    if (
        retroalimentacionActiva &&
        explicacion
    ) {
        mostrarFeedback(
            mensajeCorrecto +
            '\n\n💡 Explicación del docente:\n' +
            explicacion,
            'success',
            6500
        );

        return;
    }

    mostrarFeedback(
        mensajeCorrecto,
        'success'
    );
}


// =============================================
// MODO RELACIONAR (CON IMÁGENES)
// =============================================

function generarRelacionar() {
    // Mezclar elementos A y B por separado
    const elementosA = parejasData.map(p => ({ ...p, lado: 'A' }));
    const elementosB = parejasData.map(p => ({ ...p, lado: 'B' }));
    
    // Mezclar
    elementosA.sort(() => Math.random() - 0.5);
    elementosB.sort(() => Math.random() - 0.5);
    
    let html = '<div class="game-relacionar">';
    
    // Columna A
    html += '<div class="columna"><h4>Elementos A</h4>';
    elementosA.forEach((item, index) => {
        const imagenUrl = getUrlImagen(item.elemento_a_imagen);
        html += `
            <div class="item" data-id="${item.id_pareja}" data-lado="A" data-index="${index}" onclick="seleccionarRelacionar(this, '${item.id_pareja}', 'A')">
                ${imagenUrl ? `<img src="${imagenUrl}" class="item-imagen" alt="Imagen">` : ''}
                ${item.elemento_a_texto || 'Elemento ' + (index + 1)}
            </div>
        `;
    });
    html += '</div>';
    
    // Columna B
    html += '<div class="columna"><h4>Elementos B</h4>';
    elementosB.forEach((item, index) => {
        const imagenUrl = getUrlImagen(item.elemento_b_imagen);
        html += `
            <div class="item" data-id="${item.id_pareja}" data-lado="B" data-index="${index}" onclick="seleccionarRelacionar(this, '${item.id_pareja}', 'B')">
                ${imagenUrl ? `<img src="${imagenUrl}" class="item-imagen" alt="Imagen">` : ''}
                ${item.elemento_b_texto || 'Elemento ' + (index + 1)}
            </div>
        `;
    });
    html += '</div>';
    
    html += '</div>';
    return html;
}

let seleccionA = null;
let seleccionB = null;

function seleccionarRelacionar(elemento, idPareja, lado) {
    if (juegoTerminado) return;
    if (elemento.classList.contains('correcto')) return;
    if (elemento.classList.contains('incorrecto')) return;
    
    if (lado === 'A') {
        if (seleccionA) {
            seleccionA.classList.remove('seleccionado');
        }
        seleccionA = elemento;
        elemento.classList.add('seleccionado');
        
        if (seleccionB) {
            verificarRelacionar();
        }
    } else {
        if (seleccionB) {
            seleccionB.classList.remove('seleccionado');
        }
        seleccionB = elemento;
        elemento.classList.add('seleccionado');
        
        if (seleccionA) {
            verificarRelacionar();
        }
    }
}

function verificarRelacionar() {
    const idA = seleccionA.dataset.id;
    const idB = seleccionB.dataset.id;
    
    const esCorrecto = idA === idB;
    
    if (esCorrecto) {
        seleccionA.classList.remove('seleccionado');
        seleccionB.classList.remove('seleccionado');
        seleccionA.classList.add('correcto');
        seleccionB.classList.add('correcto');
        seleccionA.classList.add('deshabilitado');
        seleccionB.classList.add('deshabilitado');
        
        if (!seleccionA.querySelector('i')) {
            seleccionA.innerHTML += ' <i class="fa-regular fa-check-circle"></i>';
        }
        if (!seleccionB.querySelector('i')) {
            seleccionB.innerHTML += ' <i class="fa-regular fa-check-circle"></i>';
        }
        
        aciertos++;
        puntos += puntosPorAcierto;
        parejasCompletadas++;
        
        mostrarRetroalimentacionCorrecta(
            idA,
            '✅ ¡Correcto! +' +
            puntosPorAcierto +
            ' puntos'
        );
    } else {
        seleccionA.classList.remove('seleccionado');
        seleccionB.classList.remove('seleccionado');
        seleccionA.classList.add('incorrecto');
        seleccionB.classList.add('incorrecto');
        
        errores++;
        
        mostrarFeedback('Incorrecto. Intenta de nuevo.', 'error');
        
        setTimeout(() => {
            seleccionA.classList.remove('incorrecto');
            seleccionB.classList.remove('incorrecto');
        }, 800);
    }
    
    seleccionA = null;
    seleccionB = null;
    
    actualizarEstadisticas();
    
    if (parejasCompletadas === totalParejas) {
        terminarJuego();
    }
}

// =============================================
// MODO MEMORIA (CON IMÁGENES)
// =============================================

let cartasVolteadas = [];
let cartasEncontradas = 0;
let movimientosMemoria = 0;

function generarMemoria() {
    // Crear pares de cartas
    let cartas = [];
    parejasData.forEach((p, index) => {
        const imagenUrlA = getUrlImagen(p.elemento_a_imagen);
        const imagenUrlB = getUrlImagen(p.elemento_b_imagen);
        cartas.push({ 
            id: p.id_pareja, 
            tipo: 'A', 
            texto: p.elemento_a_texto || 'A' + (index + 1),
            imagen: imagenUrlA,
            icono: 'fa-regular fa-file'
        });
        cartas.push({ 
            id: p.id_pareja, 
            tipo: 'B', 
            texto: p.elemento_b_texto || 'B' + (index + 1),
            imagen: imagenUrlB,
            icono: 'fa-regular fa-file-lines'
        });
    });
    
    // Mezclar
    cartas.sort(() => Math.random() - 0.5);
    
    let html = '<div class="game-memoria">';
    cartas.forEach((carta, index) => {
        html += `
            <div class="carta" data-id="${carta.id}" data-tipo="${carta.tipo}" data-index="${index}" onclick="voltearCarta(this)">
                <div class="contenido">
                    ${carta.imagen ? `<img src="${carta.imagen}" class="carta-imagen" alt="Imagen">` : `<span class="carta-texto">${carta.texto}</span>`}
                </div>
            </div>
        `;
    });
    html += '</div>';
    return html;
}

function voltearCarta(elemento) {
    if (juegoTerminado) return;
    if (elemento.classList.contains('volteada')) return;
    if (elemento.classList.contains('encontrada')) return;
    if (cartasVolteadas.length >= 2) return;
    
    elemento.classList.add('volteada');
    cartasVolteadas.push(elemento);
    movimientosMemoria++;
    
    if (cartasVolteadas.length === 2) {
        verificarMemoria();
    }
}

function verificarMemoria() {
    const carta1 = cartasVolteadas[0];
    const carta2 = cartasVolteadas[1];
    
    const esCorrecto = carta1.dataset.id === carta2.dataset.id && carta1.dataset.tipo !== carta2.dataset.tipo;
    
    if (esCorrecto) {
        carta1.classList.add('encontrada');
        carta2.classList.add('encontrada');
        carta1.classList.remove('volteada');
        carta2.classList.remove('volteada');
        
        aciertos++;
        puntos += puntosPorAcierto;
        parejasCompletadas++;
        cartasEncontradas += 2;
        
        mostrarRetroalimentacionCorrecta(
            carta1.dataset.id,
            '✅ ¡Pareja encontrada! +' +
            puntosPorAcierto +
            ' puntos'
        );
    } else {
        errores++;
        mostrarFeedback('No coinciden. Intenta de nuevo.', 'error');
        
        setTimeout(() => {
            carta1.classList.remove('volteada');
            carta2.classList.remove('volteada');
        }, 800);
    }
    
    cartasVolteadas = [];
    actualizarEstadisticas();
    
    if (cartasEncontradas === parejasData.length * 2) {
        terminarJuego();
    }
}

// =============================================
// MODO SECUENCIA (CON IMÁGENES)
// =============================================

function generarSecuencia() {
    let items = parejasData.map((p, index) => ({
        id: p.id_pareja,
        texto: p.elemento_a_texto || 'Elemento ' + (index + 1),
        imagen: getUrlImagen(p.elemento_a_imagen),
        ordenCorrecto: index
    }));
    
    items.sort(() => Math.random() - 0.5);
    
    let html = '<div class="game-secuencia">';
    items.forEach((item, index) => {
        html += `
            <div class="item-secuencia" data-id="${item.id}" data-orden="${index}" onclick="seleccionarSecuencia(this)">
                <span class="posicion">${index + 1}</span>
                ${item.imagen ? `<img src="${item.imagen}" class="secuencia-imagen" alt="Imagen">` : ''}
                <span>${item.texto}</span>
            </div>
        `;
    });
    html += '</div>';
    return html;
}

let ordenSeleccionado = 0;

function seleccionarSecuencia(elemento) {
    if (juegoTerminado) return;
    if (elemento.classList.contains('correcto')) return;
    if (elemento.classList.contains('incorrecto')) return;
    
    const items = document.querySelectorAll('.item-secuencia:not(.correcto)');
    const itemActual = Array.from(items).indexOf(elemento);
    const idActual = parseInt(elemento.dataset.id);
    
    const parejaCorrecta = parejasData.find(p => p.id_pareja === idActual);
    const ordenCorrecto = parejasData.indexOf(parejaCorrecta);
    
    if (ordenSeleccionado === ordenCorrecto) {
        elemento.classList.add('correcto');
        aciertos++;
        puntos += puntosPorAcierto;
        parejasCompletadas++;
        ordenSeleccionado++;
        
        mostrarRetroalimentacionCorrecta(
            idActual,
            '✅ ¡Correcto! +' +
            puntosPorAcierto +
            ' puntos'
        );
    } else {
        elemento.classList.add('incorrecto');
        errores++;
        
        mostrarFeedback('Orden incorrecto. El siguiente debería ser: ' + (ordenSeleccionado + 1), 'error');
        
        setTimeout(() => {
            elemento.classList.remove('incorrecto');
        }, 800);
    }
    
    actualizarEstadisticas();
    
    if (parejasCompletadas === totalParejas) {
        terminarJuego();
    }
}

// =============================================
// MODO CLASIFICAR (CON IMÁGENES)
// =============================================

function generarClasificar() {
    const categorias = [...new Set(parejasData.map(p => p.categoria || 'Sin categoría'))];
    
    let html = '<div class="game-clasificar">';
    
    categorias.forEach(categoria => {
        const items = parejasData.filter(p => (p.categoria || 'Sin categoría') === categoria);
        html += `
            <div class="categoria" data-categoria="${categoria}">
                <h4>${categoria}</h4>
                <div class="items">
                    ${items.map((item, index) => {
                        const imagenUrl = getUrlImagen(item.elemento_a_imagen);
                        return `
                            <div class="item-clasificar" data-id="${item.id_pareja}" onclick="seleccionarClasificar(this, '${categoria}')">
                                ${imagenUrl ? `<img src="${imagenUrl}" class="clasificar-imagen" alt="Imagen">` : ''}
                                ${item.elemento_a_texto || 'Elemento ' + (index + 1)}
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    return html;
}

function seleccionarClasificar(elemento, categoria) {
    if (juegoTerminado) return;
    if (elemento.classList.contains('correcto')) return;
    if (elemento.classList.contains('incorrecto')) return;
    
    const idActual = parseInt(elemento.dataset.id);
    const parejaActual = parejasData.find(p => p.id_pareja === idActual);
    const categoriaCorrecta = parejaActual.categoria || 'Sin categoría';
    
    if (categoria === categoriaCorrecta) {
        elemento.classList.add('correcto');
        aciertos++;
        puntos += puntosPorAcierto;
        parejasCompletadas++;
        
        mostrarRetroalimentacionCorrecta(
            idActual,
            '✅ ¡Correcto! +' +
            puntosPorAcierto +
            ' puntos'
        );
    } else {
        elemento.classList.add('incorrecto');
        errores++;
        
        mostrarFeedback('Incorrecto. Esta categoría es: ' + categoriaCorrecta, 'error');
        
        setTimeout(() => {
            elemento.classList.remove('incorrecto');
        }, 800);
    }
    
    actualizarEstadisticas();
    
    if (parejasCompletadas === totalParejas) {
        terminarJuego();
    }
}

// =============================================
// FUNCIONES AUXILIARES
// =============================================

function actualizarEstadisticas() {
    document.getElementById('puntosDisplay').textContent = puntos;
    document.getElementById('aciertosDisplay').textContent = aciertos;
    document.getElementById('erroresDisplay').textContent = errores;
    document.getElementById('progresoDisplay').textContent = parejasCompletadas + '/' + totalParejas;
}

function mostrarFeedback(
    mensaje,
    tipo,
    duracion = 3000
) {
    const feedback =
        document.getElementById(
            'feedbackMessage'
        );

    if (!feedback) {
        return;
    }

    feedback.textContent =
        mensaje;

    feedback.className =
        'feedback-message ' +
        tipo +
        ' show';

    clearTimeout(
        feedback._timeout
    );

    feedback._timeout =
        setTimeout(() => {
            feedback.classList.remove(
                'show'
            );
        }, duracion);
}


function iniciarTimer() {
    timerInterval = setInterval(() => {
        tiempoSegundos++;
        const minutos = String(Math.floor(tiempoSegundos / 60)).padStart(2, '0');
        const segundos = String(tiempoSegundos % 60).padStart(2, '0');
        document.getElementById('tiempoDisplay').textContent = minutos + ':' + segundos;
    }, 1000);
}

function detenerTimer() {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
}

// =============================================
// FUNCIÓN PARA VERIFICAR SI PUEDE JUGAR OTRA VEZ (CORREGIDA)
// =============================================

function puedeJugarOtraVez() {
    // Si no hay límite de intentos, siempre puede jugar
    if (intentosMaximos === 0 || intentosMaximos === null) return true;
    
    // 🔥 USAR EL NÚMERO DE INTENTO ACTUAL DE LA BD
    return intentoActual < intentosMaximos;
}

// =============================================
// FUNCIÓN PARA CREAR NUEVO INTENTO (NUEVA)
// =============================================

function crearNuevoIntento(callback) {
    const formData = new FormData();
    formData.append('id_asignacion', idAsignacion);
    formData.append('id_juego', idJuego);
    formData.append('accion', 'crear_intento');
    
    const feedback = document.getElementById('feedbackMessage');
    if (feedback) {
        feedback.textContent = '⏳ Creando nuevo intento...';
        feedback.className = 'feedback-message info show';
    }
    
    fetch('guardar_resultado_juego.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Nuevo intento creado:', data.numero_intento);
            if (feedback) {
                feedback.className = 'feedback-message';
                feedback.textContent = '✅ Nuevo intento creado';
                feedback.className = 'feedback-message success show';
                setTimeout(() => {
                    feedback.classList.remove('show');
                }, 2000);
            }
            if (callback) callback();
        } else {
            alert('❌ No se pudo crear un nuevo intento: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        alert('❌ Error de conexión al crear nuevo intento');
    });
}

// =============================================
// FUNCIÓN PARA GUARDAR RESULTADO (CORREGIDA)
// =============================================

function guardarResultado(porcentaje, callback) {
    const formData = new FormData();
    formData.append('id_asignacion', idAsignacion);
    formData.append('id_juego', idJuego);
    formData.append('puntos', puntos);
    formData.append('aciertos', aciertos);
    formData.append('errores', errores);
    formData.append('porcentaje', porcentaje);
    formData.append('tiempo', tiempoSegundos);
    formData.append('total_parejas', totalParejas);
    formData.append('parejas_completadas', parejasCompletadas);
    formData.append('accion', 'guardar_resultado');
    
    // Mostrar indicador de carga
    const feedback = document.getElementById('feedbackMessage');
    if (feedback) {
        feedback.textContent = '⏳ Guardando resultado...';
        feedback.className = 'feedback-message info show';
    }
    
    fetch('guardar_resultado_juego.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Resultado guardado:', data);
            if (feedback) {
                feedback.className = 'feedback-message';
            }
            if (callback) callback();
        } else {
            console.error('❌ Error al guardar resultado:', data.message);
            if (feedback) {
                feedback.textContent = '❌ Error al guardar: ' + data.message;
                feedback.className = 'feedback-message error show';
            }
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        if (feedback) {
            feedback.textContent = '❌ Error de conexión al guardar';
            feedback.className = 'feedback-message error show';
        }
    });
}

// =============================================
// FUNCIÓN REINICIAR CON NUEVO INTENTO (CORREGIDA)
// =============================================

function reiniciarConNuevoIntento() {
    if (!confirm('¿Seguro que quieres iniciar un nuevo intento? Se guardará tu progreso actual.')) return;
    
    const porcentaje = totalParejas > 0 ? Math.round((parejasCompletadas / totalParejas) * 100) : 0;
    
    // Mostrar loading
    document.getElementById('gameArea').innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div class="spinner"></div>
            <p style="color: #64748b; margin-top: 15px;">Creando nuevo intento...</p>
        </div>
    `;
    
    // Guardar resultado actual y luego reiniciar
    guardarResultado(porcentaje, function() {
        // 🔥 Crear nuevo intento en el servidor
        crearNuevoIntento(function() {
            // Después de crear el nuevo intento, recargar la página
            window.location.href = 'jugar_juego.php?id_juego=' + idJuego + '&id_asignacion=' + idAsignacion + '&nuevo_intento=1';
        });
    });
}

// =============================================
// FUNCIÓN REINICIAR JUEGO (CORREGIDA)
// =============================================

function reiniciarJuego() {
    if (!confirm('¿Seguro que quieres reiniciar el juego? Perderás todo tu progreso.')) return;
    
    // Si ya hay un intento guardado, recargar la página
    window.location.href = 'jugar_juego.php?id_juego=' + idJuego + '&id_asignacion=' + idAsignacion + '&reiniciar=1';
}

// =============================================
// FUNCIÓN TERMINAR JUEGO (CORREGIDA)
// =============================================

function terminarJuego() {
    if (juegoTerminado) return;
    if (parejasCompletadas < totalParejas) {
        if (!confirm('Aún no has completado todas las parejas. ¿Seguro que quieres terminar?')) return;
    }
    
    juegoTerminado = true;
    detenerTimer();
    
    const porcentaje = totalParejas > 0 ? Math.round((parejasCompletadas / totalParejas) * 100) : 0;
    
    // Guardar el resultado en el servidor
    guardarResultado(porcentaje, function() {
        // Después de guardar, mostrar el resultado
        mostrarResultadoFinal(porcentaje);
    });
}

// =============================================
// FUNCIÓN MOSTRAR RESULTADO FINAL (CORREGIDA)
// =============================================

function mostrarResultadoFinal(porcentaje) {
    const gameArea = document.getElementById('gameArea');
    const puedeReintentar = puedeJugarOtraVez();
    const intentosRestantes = intentosMaximos > 0 ? intentosMaximos - intentoActual : '∞';
    
    gameArea.innerHTML = `
        <div style="text-align: center; padding: 40px 20px;">
            <div style="font-size: 64px; margin-bottom: 20px;">
                ${porcentaje >= 80 ? '🏆' : porcentaje >= 50 ? '⭐' : '💪'}
            </div>
            <h3 style="margin: 0; color: #1e293b;">¡Juego terminado!</h3>
            <p style="color: #64748b; margin: 5px 0 20px 0;">
                Has completado ${parejasCompletadas} de ${totalParejas} parejas
                ${intentosMaximos > 0 ? ` · Intento ${intentoActual} de ${intentosMaximos}` : ''}
            </p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 15px; max-width: 500px; margin: 0 auto 20px auto;">
                <div style="background: #f8fafc; padding: 12px; border-radius: 10px;">
                    <div style="font-size: 24px; font-weight: 700; color: #3b71f3;">${puntos}</div>
                    <div style="font-size: 11px; color: #64748b;">Puntos</div>
                </div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 10px;">
                    <div style="font-size: 24px; font-weight: 700; color: #22c55e;">${aciertos}</div>
                    <div style="font-size: 11px; color: #64748b;">Aciertos</div>
                </div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 10px;">
                    <div style="font-size: 24px; font-weight: 700; color: #dc2626;">${errores}</div>
                    <div style="font-size: 11px; color: #64748b;">Errores</div>
                </div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 10px;">
                    <div style="font-size: 24px; font-weight: 700; color: #f59e0b;">${porcentaje}%</div>
                    <div style="font-size: 11px; color: #64748b;">Precisión</div>
                </div>
            </div>
            
            ${intentosMaximos > 0 ? `
                <div style="background: #f1f5f9; padding: 8px 16px; border-radius: 8px; display: inline-block; margin-bottom: 15px; font-size: 13px; color: #64748b;">
                    <i class="fa-regular fa-circle-info"></i> 
                    Intentos restantes: ${intentosRestantes === '∞' ? '∞' : intentosRestantes}
                </div>
            ` : ''}
            
            <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                ${puedeReintentar ? `
                    <button onclick="reiniciarConNuevoIntento()" class="btn btn-primary">
                        <i class="fa-solid fa-rotate-right"></i> Jugar de nuevo
                    </button>
                ` : `
                    <div style="background: #fef2f2; padding: 10px 20px; border-radius: 8px; color: #991b1b; font-size: 14px; border: 1px solid #fca5a5;">
                        <i class="fa-regular fa-circle-xmark"></i> Sin intentos disponibles
                    </div>
                `}
                <a href="juegos_alumno.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Volver a juegos
                </a>
            </div>
        </div>
    `;
    
    document.getElementById('btnTerminar').style.display = 'none';
    document.getElementById('btnReiniciar').style.display = 'none';
}

console.log('🎮 Página de juego con imágenes y gestión de intentos cargada correctamente');
console.log('📊 Intentos: ' + intentoActual + ' de ' + intentosMaximos);
</script>

<script src="js/Inicio.js"></script>
<script src="../Accesibilidad/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>