<?php
session_start();

// Verificar que el usuario sea docente
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_docente = $_SESSION['usuario']['id_usuario'];
$nombre_docente = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];

// Obtener parámetros GET
$id_actividad = isset($_GET['id_actividad']) ? (int)$_GET['id_actividad'] : 0;
$id_estudiante = isset($_GET['id_estudiante']) ? (int)$_GET['id_estudiante'] : 0;
$id_curso = isset($_GET['id_curso']) ? (int)$_GET['id_curso'] : 0;

if ($id_actividad === 0 || $id_estudiante === 0) {
    header('Location: ver_estudiantes.php');
    exit;
}

// Obtener datos de la actividad y la entrega
$query = "
    SELECT 
        a.id_actividad,
        a.titulo,
        a.descripcion,
        a.tipo,
        a.puntaje_maximo,
        a.fecha_limite,
        m.nombre AS materia,
        c.nombre AS curso_nombre,
        ae.id_actividad_estudiante,
        ae.estado,
        e.id_entrega,
        e.texto_entrega,
        e.fecha_entrega,
        e.calificacion,
        e.retroalimentacion,
        e.estado AS entrega_estado,
        adj.id_adjunto,
        adj.nombre_archivo,
        adj.url_archivo,
        adj.tamano_bytes,
        u.nombre AS estudiante_nombre,
        u.apellido_paterno AS estudiante_apellido,
        u.apellido_materno AS estudiante_apellido_materno,
        u.correo AS estudiante_correo,
        u.foto_perfil AS estudiante_foto
    FROM actividades a
    JOIN cursos c ON a.id_curso = c.id_curso
    JOIN materias m ON c.id_materia = m.id_materia
    JOIN actividad_estudiantes ae ON ae.id_actividad = a.id_actividad
    JOIN usuarios u ON ae.id_alumno = u.id_usuario
    LEFT JOIN entregas e ON ae.id_actividad_estudiante = e.id_actividad_estudiante
    LEFT JOIN adjuntos adj ON adj.entidad_tipo = 'Entrega' AND adj.entidad_id = e.id_entrega
    WHERE a.id_actividad = ? 
    AND ae.id_alumno = ?
";

$stmt = $conexion->prepare($query);
$stmt->bind_param("ii", $id_actividad, $id_estudiante);
$stmt->execute();
$resultado = $stmt->get_result();
$datos = $resultado->fetch_assoc();
$stmt->close();

if (!$datos) {
    header('Location: ver_estudiantes.php');
    exit;
}

// Procesar el formulario de calificación
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calificar'])) {
    $calificacion = isset($_POST['calificacion']) ? floatval($_POST['calificacion']) : null;
    $retroalimentacion = isset($_POST['retroalimentacion']) ? trim($_POST['retroalimentacion']) : '';
    $id_entrega = isset($_POST['id_entrega']) ? intval($_POST['id_entrega']) : 0;
    
    if ($calificacion === null || $calificacion < 0 || $calificacion > 100) {
        $mensaje = 'La calificación debe ser un número entre 0 y 100.';
        $tipo_mensaje = 'error';
    } else {
        // Validar que no exceda el puntaje máximo
        $puntaje_maximo = $datos['puntaje_maximo'] > 0 ? $datos['puntaje_maximo'] : 100;
        if ($calificacion > $puntaje_maximo) {
            $mensaje = 'La calificación no puede superar el puntaje máximo de ' . $puntaje_maximo . '.';
            $tipo_mensaje = 'error';
        } else {
            try {
                // Iniciar transacción
                $conexion->begin_transaction();
                
                // Actualizar la entrega
                $update_entrega = $conexion->prepare("
                    UPDATE entregas 
                    SET calificacion = ?, 
                        retroalimentacion = ?, 
                        estado = 'Calificada',
                        calificado_por = ?,
                        calificado_en = NOW()
                    WHERE id_entrega = ?
                ");
                $update_entrega->bind_param("dsii", $calificacion, $retroalimentacion, $id_docente, $id_entrega);
                $update_entrega->execute();
                $update_entrega->close();
                
                // Actualizar actividad_estudiantes
                $update_ae = $conexion->prepare("
                    UPDATE actividad_estudiantes 
                    SET estado = 'Calificada',
                        fecha_finalizacion = NOW(),
                        porcentaje_avance = 100.00
                    WHERE id_actividad_estudiante = ?
                ");
                $update_ae->bind_param("i", $datos['id_actividad_estudiante']);
                $update_ae->execute();
                $update_ae->close();
                
                $conexion->commit();
                
                $mensaje = '¡Calificación guardada correctamente!';
                $tipo_mensaje = 'success';
                
                // Recargar datos actualizados
                $query_updated = "
                    SELECT 
                        e.calificacion,
                        e.retroalimentacion,
                        e.estado AS entrega_estado
                    FROM entregas e
                    WHERE e.id_entrega = ?
                ";
                $stmt_updated = $conexion->prepare($query_updated);
                $stmt_updated->bind_param("i", $id_entrega);
                $stmt_updated->execute();
                $result_updated = $stmt_updated->get_result();
                $updated_data = $result_updated->fetch_assoc();
                $stmt_updated->close();
                
                // Actualizar los datos mostrados
                $datos['calificacion'] = $updated_data['calificacion'];
                $datos['retroalimentacion'] = $updated_data['retroalimentacion'];
                $datos['entrega_estado'] = $updated_data['entrega_estado'];
                
            } catch (Exception $e) {
                $conexion->rollback();
                $mensaje = 'Error al guardar la calificación: ' . $e->getMessage();
                $tipo_mensaje = 'error';
            }
        }
    }
}

// Función para formatear tamaño de archivo
function formatearTamañoArchivo($bytes) {
    if ($bytes === null) return '';
    $unidades = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($unidades) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 1) . ' ' . $unidades[$i];
}

$conexion->close();

// Obtener foto de perfil del docente
$foto_perfil_docente = $_SESSION['usuario']['foto_perfil'] ?? null;
$ruta_foto_docente = !empty($foto_perfil_docente) ? '../uploads/perfiles/' . $foto_perfil_docente : 'https://placehold.co/40x40/ff7675/white?text=👨';

// Foto del estudiante
$foto_estudiante = !empty($datos['estudiante_foto']) ? '../uploads/perfiles/' . $datos['estudiante_foto'] : 'https://placehold.co/80x80/3b71f3/white?text=' . substr($datos['estudiante_nombre'], 0, 1);
$nombre_estudiante = $datos['estudiante_nombre'] . ' ' . $datos['estudiante_apellido'] . ' ' . $datos['estudiante_apellido_materno'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificar Entrega - Aulamos</title>
    
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    
    <style>
        /* ==========================================
           ESTILOS MODIFICADOS PARA OCUPAR TODO EL ESPACIO
           ========================================== */
        
        .calificar-container {
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
        
        .card-info {
            background: white;
            border-radius: 12px;
            padding: 25px 30px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            width: 100%;
        }
        
        .card-info .header-info {
            display: flex;
            align-items: center;
            gap: 25px;
            flex-wrap: wrap;
        }
        
        .card-info .header-info .avatar-estudiante {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3b71f3;
        }
        
        .card-info .header-info .datos-estudiante h2 {
            margin: 0 0 5px 0;
            color: #1e293b;
        }
        
        .card-info .header-info .datos-estudiante p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }
        
        .card-info .header-info .datos-estudiante .badge-materia {
            display: inline-block;
            background: #eff6ff;
            color: #3b71f3;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .grid-info {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 20px;
            padding-top: 20px;
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
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .card-archivo {
            background: #f8fafc;
            border-radius: 10px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
            border: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }
        
        .card-archivo .icono {
            font-size: 32px;
            color: #3b71f3;
        }
        
        .card-archivo .info {
            flex: 1;
        }
        
        .card-archivo .info .nombre {
            font-weight: 600;
            font-size: 14px;
            color: #1e293b;
        }
        
        .card-archivo .info .tamano {
            font-size: 12px;
            color: #64748b;
        }
        
        .card-archivo .btn-descargar {
            background: #3b71f3;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        
        .card-archivo .btn-descargar:hover {
            background: #2a5bd6;
        }
        
        .card-entrega-texto {
            background: #f8fafc;
            border-radius: 10px;
            padding: 15px 20px;
            margin-top: 15px;
            border: 1px solid #e2e8f0;
        }
        
        .card-entrega-texto .label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }
        
        .card-entrega-texto .texto {
            margin-top: 5px;
            color: #1e293b;
            line-height: 1.6;
        }
        
        .form-calificar {
            background: white;
            border-radius: 12px;
            padding: 25px 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            width: 100%;
        }
        
        .form-calificar .form-group {
            margin-bottom: 20px;
        }
        
        .form-calificar label {
            font-weight: 600;
            font-size: 14px;
            color: #1e293b;
            display: block;
            margin-bottom: 5px;
        }
        
        .form-calificar .input-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .form-calificar input[type="number"] {
            width: 150px;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 20px;
            font-weight: 700;
            text-align: center;
            transition: border-color 0.2s;
            -moz-appearance: textfield;
        }
        
        .form-calificar input[type="number"]::-webkit-outer-spin-button,
        .form-calificar input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .form-calificar input[type="number"]:focus {
            border-color: #3b71f3;
            outline: none;
        }
        
        .form-calificar .input-group .max-label {
            font-size: 16px;
            color: #64748b;
            font-weight: 600;
        }
        
        .form-calificar textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
            transition: border-color 0.2s;
        }
        
        .form-calificar textarea:focus {
            border-color: #3b71f3;
            outline: none;
        }
        
        .form-calificar .range-display {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #94a3b8;
            margin-top: 4px;
            padding: 0 10px;
        }
        
        .form-calificar .btn-acciones {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 12px 32px;
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
            display: none;
        }
        
        .alert.show {
            display: block;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b71f3;
        }
        
        .badge-calificado {
            background: #dcfce7;
            color: #166534;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .calificacion-mostrada {
            font-size: 32px;
            font-weight: 700;
            color: #3b71f3;
            text-align: center;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
        }
        
        .retroalimentacion-mostrada {
            background: #f8fafc;
            padding: 15px 20px;
            border-radius: 10px;
            border-left: 4px solid #3b71f3;
            margin-top: 10px;
        }
        
        .ya-calificado-msg {
            background: #dcfce7;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .ya-calificado-msg i {
            font-size: 48px;
            color: #22c55e;
            display: block;
            margin-bottom: 10px;
        }
        
        .ya-calificado-msg h3 {
            color: #166534;
            margin: 0 0 5px 0;
        }
        
        .ya-calificado-msg p {
            color: #15803d;
            margin: 0;
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
        
        /* Estilos del encabezado */
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
        
        /* Responsive */
        @media (max-width: 1024px) {
            .grid-info {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .calificar-container {
                padding: 15px;
            }
            
            .card-info {
                padding: 20px 15px;
            }
            
            .form-calificar {
                padding: 20px 15px;
            }
            
            .grid-info {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .card-info .header-info {
                flex-direction: column;
                text-align: center;
            }
            
            .form-calificar .input-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .form-calificar input[type="number"] {
                width: 100%;
            }
            
            .form-calificar .btn-acciones {
                flex-direction: column;
            }
            
            .btn {
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
            <img src="../img/logo_g.png" alt="Búho Aulamos" class="logo-img">
        </div>
        <nav class="menu">
            <a href="docente_dashboard.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="crear_recurso.php" class="menu-item"><i class="fa-solid fa-medal"></i> Crear Recurso</a>
            <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
            <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
            <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
            <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
            <a href="pasarlista.php" class="menu-item"><i class="fa-solid fa-bars"></i> Pasar Lista</a>
            <div class="menu-spacer"></div>
            <button class="btn-accessibility-main" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
            <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="main-content">
        
        <!-- ENCABEZADO -->
        <?php
        // Recargar foto de perfil del docente
        $foto_perfil_docente = $_SESSION['usuario']['foto_perfil'] ?? null;
        $ruta_foto_docente = !empty($foto_perfil_docente) ? '../uploads/perfiles/' . $foto_perfil_docente : 'https://placehold.co/40x40/ff7675/white?text=👨';
        ?>
        <div class="content-header">
            <div class="welcome-text">
                <h1>Calificar Entrega</h1>
                <p>Revisa y califica el trabajo del estudiante</p>
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
        </div>

        <div class="calificar-container">
            
            <!-- Mensajes -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> show">
                    <i class="fa-solid <?php echo $tipo_mensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <!-- Botón de regreso -->
            <a href="ver_avances_estudiante.php?id=<?php echo $id_estudiante; ?>&id_curso=<?php echo $id_curso; ?>" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Volver a avances del estudiante
            </a>

            <!-- Información de la actividad y estudiante -->
            <div class="card-info">
                <div class="header-info">
                    <img src="<?php echo $foto_estudiante; ?>" alt="Avatar" class="avatar-estudiante">
                    <div class="datos-estudiante">
                        <h2><?php echo htmlspecialchars($nombre_estudiante); ?></h2>
                        <p><i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($datos['estudiante_correo']); ?></p>
                        <span class="badge-materia">
                            <i class="fa-regular fa-bookmark"></i> <?php echo htmlspecialchars($datos['materia']); ?> - <?php echo htmlspecialchars($datos['curso_nombre']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="grid-info">
                    <div class="item">
                        <span class="label">Actividad</span>
                        <span class="value"><?php echo htmlspecialchars($datos['titulo']); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Tipo</span>
                        <span class="value"><?php echo htmlspecialchars($datos['tipo']); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Puntaje máximo</span>
                        <span class="value"><?php echo $datos['puntaje_maximo']; ?> pts</span>
                    </div>
                    <div class="item">
                        <span class="label">Fecha límite</span>
                        <span class="value"><?php echo date('d M, Y H:i', strtotime($datos['fecha_limite'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Archivo adjunto -->
            <?php if (!empty($datos['id_adjunto'])): ?>
                <div class="card-info">
                    <h3 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b;">
                        <i class="fa-regular fa-file"></i> Archivo entregado
                    </h3>
                    <div class="card-archivo">
                        <div class="icono">
                            <i class="fa-regular fa-file-pdf"></i>
                        </div>
                        <div class="info">
                            <div class="nombre"><?php echo htmlspecialchars($datos['nombre_archivo']); ?></div>
                            <div class="tamano"><?php echo formatearTamañoArchivo($datos['tamano_bytes']); ?></div>
                        </div>
                        <a href="<?php echo $datos['url_archivo']; ?>" target="_blank" class="btn-descargar">
                            <i class="fa-regular fa-eye"></i> Ver archivo
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card-info">
                    <div style="text-align: center; padding: 20px; color: #94a3b8;">
                        <i class="fa-regular fa-file" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                        <p>El estudiante no adjuntó ningún archivo.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Texto de entrega -->
            <?php if (!empty($datos['texto_entrega'])): ?>
                <div class="card-info">
                    <h3 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b;">
                        <i class="fa-regular fa-comment"></i> Comentario del estudiante
                    </h3>
                    <div class="card-entrega-texto">
                        <div class="texto"><?php echo nl2br(htmlspecialchars($datos['texto_entrega'])); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Formulario de calificación -->
            <?php if ($datos['entrega_estado'] !== 'Calificada'): ?>
                
                <div class="form-calificar">
                    <h3 style="margin: 0 0 20px 0; font-size: 18px; color: #1e293b;">
                        <i class="fa-regular fa-star"></i> Calificar entrega
                    </h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="id_entrega" value="<?php echo $datos['id_entrega']; ?>">
                        <input type="hidden" name="id_actividad_estudiante" value="<?php echo $datos['id_actividad_estudiante']; ?>">
                        
                        <div class="form-group">
                            <label for="calificacion">Calificación <span style="color: #dc2626;">*</span></label>
                            <div class="input-group">
                                <input type="number" 
                                       id="calificacion" 
                                       name="calificacion" 
                                       min="0" 
                                       max="<?php echo $datos['puntaje_maximo']; ?>" 
                                       step="0.5"
                                       value="<?php echo isset($_POST['calificacion']) ? htmlspecialchars($_POST['calificacion']) : ''; ?>"
                                       required>
                                <span class="max-label">/ <?php echo $datos['puntaje_maximo']; ?> puntos</span>
                            </div>
                            <div class="range-display">
                                <span>0</span>
                                <span>Puntaje máximo: <?php echo $datos['puntaje_maximo']; ?></span>
                            </div>
                            <small style="color: #64748b; font-size: 12px;">Ingresa un valor entre 0 y <?php echo $datos['puntaje_maximo']; ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="retroalimentacion">Retroalimentación</label>
                            <textarea id="retroalimentacion" 
                                      name="retroalimentacion" 
                                      placeholder="Escribe aquí tu retroalimentación para el estudiante..."><?php echo isset($_POST['retroalimentacion']) ? htmlspecialchars($_POST['retroalimentacion']) : ''; ?></textarea>
                        </div>
                        
                        <div class="btn-acciones">
                            <a href="ver_avances_estudiante.php?id=<?php echo $id_estudiante; ?>&id_curso=<?php echo $id_curso; ?>" class="btn btn-secondary">
                                <i class="fa-solid fa-times"></i> Cancelar
                            </a>
                            <button type="submit" name="calificar" class="btn btn-primary">
                                <i class="fa-regular fa-check-circle"></i> Guardar calificación
                            </button>
                        </div>
                    </form>
                </div>
                
            <?php else: ?>
                
                <!-- Ya calificado -->
                <div class="ya-calificado-msg">
                    <i class="fa-regular fa-check-circle"></i>
                    <h3>¡Esta entrega ya ha sido calificada!</h3>
                    <p>La calificación fue registrada el <?php echo date('d M, Y H:i', strtotime($datos['calificado_en'] ?? 'now')); ?></p>
                </div>
                
                <div class="card-info">
                    <h3 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b;">
                        <i class="fa-regular fa-star"></i> Calificación asignada
                    </h3>
                    <div class="calificacion-mostrada">
                        <?php echo number_format($datos['calificacion'], 1); ?> / <?php echo $datos['puntaje_maximo']; ?> puntos
                    </div>
                    
                    <?php if (!empty($datos['retroalimentacion'])): ?>
                        <div class="retroalimentacion-mostrada">
                            <strong style="display: block; margin-bottom: 5px; color: #1e293b;">
                                <i class="fa-regular fa-comment"></i> Retroalimentación:
                            </strong>
                            <p style="margin: 0; color: #475569;"><?php echo nl2br(htmlspecialchars($datos['retroalimentacion'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="ver_avances_estudiante.php?id=<?php echo $id_estudiante; ?>&id_curso=<?php echo $id_curso; ?>" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                        <?php if (!empty($datos['id_adjunto'])): ?>
                            <a href="<?php echo $datos['url_archivo']; ?>" target="_blank" class="btn btn-primary">
                                <i class="fa-regular fa-eye"></i> Ver archivo
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php endif; ?>

        </div>

        <!-- BARRA DE ACCESIBILIDAD -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </div>
</div>

<!-- BOTÓN FLOTANTE DE ACCESIBILIDAD -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<!-- SCRIPTS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validar que la calificación no exceda el puntaje máximo
    const inputCalificacion = document.getElementById('calificacion');
    const maxPuntaje = <?php echo $datos['puntaje_maximo']; ?>;
    
    if (inputCalificacion) {
        inputCalificacion.addEventListener('change', function() {
            let valor = parseFloat(this.value);
            if (valor > maxPuntaje) {
                alert('La calificación no puede superar el puntaje máximo de ' + maxPuntaje);
                this.value = maxPuntaje;
            }
            if (valor < 0) {
                this.value = 0;
            }
        });
        
        inputCalificacion.addEventListener('input', function() {
            let valor = parseFloat(this.value);
            if (valor > maxPuntaje) {
                this.style.borderColor = '#dc2626';
            } else {
                this.style.borderColor = '#e2e8f0';
            }
        });
    }
    
    console.log('📝 Página de calificación cargada correctamente');
});
</script>

<script src="jss/docente_dashboard.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>

</body>
</html>