<?php
session_start();

if (
    !isset($_SESSION['usuario']) ||
    ($_SESSION['usuario']['rol'] ?? '') !== 'Docente' ||
    empty($_SESSION['usuario']['id_usuario'])
) {
    header('Location: ../InicioSesion/login.php');
    exit;
}

// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . json_encode((int)$_SESSION['usuario']['id_usuario']) . ';</script>';

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
    SELECT j.*, c.nombre AS curso, m.nombre AS materia 
    FROM conecta_juegos j
    JOIN cursos c ON j.id_curso = c.id_curso
    JOIN materias m ON c.id_materia = m.id_materia
    WHERE j.id_juego = ? AND j.id_docente = ?
";

$stmt = $conexion->prepare($query);
$stmt->bind_param("ii", $id_juego, $id_docente);
$stmt->execute();
$resultado = $stmt->get_result();
$juego = $resultado->fetch_assoc();
$stmt->close();

if (!$juego || $juego['estado'] === 'Cerrado') {
    header('Location: juegos_docente.php');
    exit;
}

// Obtener parejas existentes
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

// =============================================
// FUNCIÓN PARA SUBIR IMAGEN VÍA AJAX
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subir_imagen'])) {
    $archivo = $_FILES['imagen'] ?? null;
    
    if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No se recibió ninguna imagen']);
        exit;
    }
    
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($extension, $tipos_permitidos, true)) {
        echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
        exit;
    }
    
    if ($archivo['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'La imagen no debe superar los 5MB']);
        exit;
    }
    
    $carpeta_destino = __DIR__ . '/../uploads/juegos/';
    if (!is_dir($carpeta_destino)) {
        mkdir($carpeta_destino, 0777, true);
    }
    
    $nombre_archivo = 'juego_' . $id_juego . '_' . $id_docente . '_' . time() . '.' . $extension;
    $ruta_completa = $carpeta_destino . $nombre_archivo;
    $ruta_publica = '../uploads/juegos/' . $nombre_archivo;
    
    if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        echo json_encode([
            'success' => true, 
            'ruta' => $ruta_publica,
            'nombre' => $nombre_archivo
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al mover la imagen']);
    }
    exit;
}

// Procesar formulario
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_pareja'])) {
        // Elemento A: SOLO TEXTO
        $elemento_a = trim($_POST['elemento_a'] ?? '');

        // Elemento B: SOLO IMAGEN
        $elemento_b_imagen = trim($_POST['elemento_b_imagen'] ?? '');

        // Mantener descripción / explicación
        $explicacion = trim($_POST['explicacion'] ?? '');

        // La categoría corresponde al tema seleccionado al crear el juego.
        $categoria = trim($juego['tema'] ?? '');

        // Los puntos deben respetar la configuración original del juego.
        $puntos = (int)($juego['puntos_por_acierto'] ?? 50);

        // Estos campos no se usan en esta pantalla
        $elemento_a_imagen = null;
        $elemento_b = null;
        
        if ($elemento_a === '') {
            $mensaje = 'El elemento A es obligatorio y debe ser texto.';
            $tipo_mensaje = 'error';
        } elseif ($elemento_b_imagen === '') {
            $mensaje = 'El elemento B es obligatorio y debe contener una imagen.';
            $tipo_mensaje = 'error';
        } else {
            try {
                $insert = $conexion->prepare("
                    INSERT INTO conecta_parejas (
                        id_juego, elemento_a_texto, elemento_a_imagen, 
                        elemento_b_texto, elemento_b_imagen,
                        explicacion, categoria, orden, puntos
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $orden = count($parejas) + 1;
                $insert->bind_param(
                    "issssssii",
                    $id_juego,
                    $elemento_a,
                    $elemento_a_imagen,
                    $elemento_b,
                    $elemento_b_imagen,
                    $explicacion,
                    $categoria,
                    $orden,
                    $puntos
                );
                $insert->execute();
                $insert->close();
                
                $mensaje = 'Pareja agregada correctamente.';
                $tipo_mensaje = 'success';
                
                // Recargar parejas
                $stmt_parejas = $conexion->prepare($query_parejas);
                $stmt_parejas->bind_param("i", $id_juego);
                $stmt_parejas->execute();
                $result_parejas = $stmt_parejas->get_result();
                $parejas = $result_parejas->fetch_all(MYSQLI_ASSOC);
                $stmt_parejas->close();
                
            } catch (Exception $e) {
                $mensaje = 'Error al agregar: ' . $e->getMessage();
                $tipo_mensaje = 'error';
            }
        }
    } elseif (isset($_POST['eliminar_pareja'])) {
        $id_pareja = intval($_POST['id_pareja']);
        try {
            $delete = $conexion->prepare("DELETE FROM conecta_parejas WHERE id_pareja = ? AND id_juego = ?");
            $delete->bind_param("ii", $id_pareja, $id_juego);
            $delete->execute();
            $delete->close();
            
            $mensaje = 'Pareja eliminada.';
            $tipo_mensaje = 'success';
            
            // Recargar parejas
            $stmt_parejas = $conexion->prepare($query_parejas);
            $stmt_parejas->bind_param("i", $id_juego);
            $stmt_parejas->execute();
            $result_parejas = $stmt_parejas->get_result();
            $parejas = $result_parejas->fetch_all(MYSQLI_ASSOC);
            $stmt_parejas->close();
            
        } catch (Exception $e) {
            $mensaje = 'Error al eliminar: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    } elseif (isset($_POST['publicar'])) {
        if (count($parejas) < 2) {
            $mensaje = 'Debes agregar al menos 2 parejas antes de publicar.';
            $tipo_mensaje = 'error';
        } else {
            try {
                $update = $conexion->prepare("UPDATE conecta_juegos SET estado = 'Publicado' WHERE id_juego = ?");
                $update->bind_param("i", $id_juego);
                $update->execute();
                $update->close();
                
                // Asignar a alumnos
                $query_alumnos = "SELECT id_alumno FROM inscripciones WHERE id_curso = ? AND estado = 'Activo'";
                $stmt_alumnos = $conexion->prepare($query_alumnos);
                $stmt_alumnos->bind_param("i", $juego['id_curso']);
                $stmt_alumnos->execute();
                $result_alumnos = $stmt_alumnos->get_result();
                
                while ($alumno = $result_alumnos->fetch_assoc()) {
                    $insert = $conexion->prepare("
                        INSERT INTO conecta_asignaciones (id_juego, id_alumno, estado) 
                        VALUES (?, ?, 'Pendiente')
                        ON DUPLICATE KEY UPDATE estado = 'Pendiente'
                    ");
                    $insert->bind_param("ii", $id_juego, $alumno['id_alumno']);
                    $insert->execute();
                    $insert->close();
                }
                $stmt_alumnos->close();
                
                header('Location: detalle_juego_docente.php?id_juego=' . $id_juego . '&publicado=1');
                exit;
                
            } catch (Exception $e) {
                $mensaje = 'Error al publicar: ' . $e->getMessage();
                $tipo_mensaje = 'error';
            }
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Parejas - Docente</title>
    
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    
    <style>
        .editar-container {
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
        
        .card-editar {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        
        .card-editar .header-juego {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .card-editar .header-juego .icono {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #3b71f3;
        }
        
        .card-editar .header-juego h3 {
            margin: 0;
            font-size: 18px;
            color: #1e293b;
        }
        
        .card-editar .header-juego .sub {
            color: #64748b;
            font-size: 13px;
        }
        
        .grid-parejas {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-pareja {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        
        .form-pareja .form-group {
            margin-bottom: 15px;
        }
        
        .form-pareja label {
            font-weight: 600;
            font-size: 13px;
            color: #1e293b;
            display: block;
            margin-bottom: 4px;
        }
        
        .form-pareja label .required {
            color: #dc2626;
        }
        
        .form-pareja input,
        .form-pareja textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        
        .form-pareja input:focus,
        .form-pareja textarea:focus {
            border-color: #3b71f3;
            outline: none;
        }
        
        .form-pareja textarea {
            resize: vertical;
            min-height: 60px;
        }
        
        .form-pareja .conector {
            text-align: center;
            padding: 5px 0;
            color: #3b71f3;
            font-size: 20px;
        }
        
        .form-pareja .btn-agregar {
            width: 100%;
            padding: 10px;
            background: #3b71f3;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .form-pareja .btn-agregar:hover {
            background: #2a5bd6;
        }
        
        /* Estilos para subida de imágenes */
        .image-upload-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 5px;
        }
        
        .upload-box {
            flex: 1;
            min-height: 80px;
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            background: #f8fafc;
        }
        
        .upload-box:hover {
            border-color: #3b71f3;
            background: #eff6ff;
        }
        
        .upload-box .preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        .upload-box .placeholder {
            color: #94a3b8;
            font-size: 12px;
            text-align: center;
            padding: 10px;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        
        .upload-box .placeholder i {
            font-size: 24px;
            display: block;
        }
        
        .upload-box input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }
        
        .upload-box .btn-remove-image {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(220, 38, 38, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            z-index: 3;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            transition: background 0.2s;
        }
        
        .upload-box .btn-remove-image:hover {
            background: #dc2626;
        }
        
        .upload-box .nombre-archivo {
            position: absolute;
            bottom: 4px;
            left: 4px;
            right: 4px;
            background: rgba(0,0,0,0.6);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            z-index: 3;
            text-align: center;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .list-parejas {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }
        
        .item-pareja {
            background: #f8fafc;
            border-radius: 10px;
            padding: 14px 16px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .item-pareja .numero {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #3b71f3;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            flex-shrink: 0;
        }
        
        .item-pareja .contenido {
            flex: 1;
        }
        
        .item-pareja .contenido .elementos {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #1e293b;
            flex-wrap: wrap;
        }
        
        .item-pareja .contenido .elementos .flecha {
            color: #3b71f3;
            font-size: 14px;
        }
        
        .item-pareja .contenido .elementos .imagen-miniatura {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
        }
        
        .item-pareja .contenido .extra {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        
        .item-pareja .btn-eliminar {
            background: none;
            border: none;
            color: #dc2626;
            cursor: pointer;
            font-size: 16px;
            padding: 5px 8px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        
        .item-pareja .btn-eliminar:hover {
            background: #fee2e2;
        }
        
        .acciones-publicar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn {
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
            padding: 30px 20px;
            color: #94a3b8;
        }
        
        .sin-parejas i {
            font-size: 36px;
            display: block;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .editar-container {
                padding: 15px;
            }
            
            .grid-parejas {
                grid-template-columns: 1fr;
            }
            
            .item-pareja {
                flex-wrap: wrap;
            }
            
            .acciones-publicar {
                flex-direction: column;
            }
            
            .acciones-publicar .btn {
                width: 100%;
                justify-content: center;
            }
            
            .image-upload-container {
                flex-direction: column;
            }
            
            .upload-box {
                min-height: 60px;
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
            <a href="juegos_docente.php" class="menu-item"><i class="fa-solid fa-gamepad"></i> Conecta y Aprende</a>
            
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
                <h1>Editar parejas</h1>
                <p>Relaciona texto con imagen y conserva su descripción</p>
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

        <div class="editar-container">
            
            <a href="detalle_juego_docente.php?id_juego=<?php echo $id_juego; ?>" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Volver al detalle
            </a>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>
            
            <!-- Información del juego -->
            <div class="card-editar">
                <div class="header-juego">
                    <div class="icono">
                        <i class="<?php echo getIconoModo($juego['modo']); ?>"></i>
                    </div>
                    <div>
                        <h3><?php echo htmlspecialchars($juego['titulo']); ?></h3>
                        <div class="sub">
                            <?php echo htmlspecialchars($juego['materia']); ?> · <?php echo htmlspecialchars($juego['curso']); ?>
                            <span style="margin-left: 12px;">
                                <i class="<?php echo getIconoModo($juego['modo']); ?>"></i> <?php echo $juego['modo'] === 'Memoria' ? 'Memorama' : htmlspecialchars($juego['modo']); ?>
                            </span>
                            <span style="margin-left: 12px;">
                                <i class="fa-regular fa-grip"></i> <?php echo count($parejas); ?> parejas
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formulario y lista -->
            <div class="grid-parejas">
                
                <!-- Formulario para agregar -->
                <div class="form-pareja">
                    <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b;">
                        <i class="fa-solid fa-plus"></i> Nueva pareja Texto → Imagen
                    </h4>
                    <form method="POST" action="" id="formPareja">
                        <!-- ELEMENTO A: SOLO TEXTO -->
                        <div class="form-group">
                            <label>
                                Elemento A - Texto
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="elemento_a"
                                id="elementoA"
                                placeholder="Ej. Dog"
                                required
                            >

                            <small style="color:#64748b; font-size:11px;">
                                En el elemento A solamente se permite texto.
                            </small>
                        </div>

                        <div class="conector">
                            <i class="fa-solid fa-arrow-down"></i>
                        </div>

                        <!-- ELEMENTO B: SOLO IMAGEN -->
                        <div class="form-group">
                            <label>
                                Elemento B - Imagen
                                <span class="required">*</span>
                            </label>

                            <div class="image-upload-container">
                                <div class="upload-box" id="uploadBoxB">

                                    <span class="placeholder" id="placeholderB">
                                        <i class="fa-regular fa-image"></i>
                                        Subir imagen
                                    </span>

                                    <input
                                        type="file"
                                        name="imagen_b"
                                        id="imagenB"
                                        accept="image/jpeg,image/png,image/gif,image/webp"
                                        onchange="previsualizarImagenB()"
                                    >

                                    <button
                                        type="button"
                                        class="btn-remove-image"
                                        id="removeB"
                                        onclick="eliminarImagenB()"
                                    >
                                        ×
                                    </button>
                                </div>
                            </div>

                            <input
                                type="hidden"
                                name="elemento_b_imagen"
                                id="elementoBImagen"
                                value=""
                            >

                            <small style="color:#64748b; font-size:11px;">
                                En el elemento B solamente se permite una imagen.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Descripción / Explicación</label>
                            <textarea
                                name="explicacion"
                                placeholder="Ej. Dog significa perro en español."
                            ></textarea>
                            <small style="color:#64748b; font-size:11px;">
                                Esta descripción se conserva como retroalimentación para el alumno.
                            </small>
                        </div>
                        <div class="form-group">
                            <label>Categoría / Tema</label>

                            <select
                                name="categoria_visual"
                                id="categoriaVisual"
                                disabled
                                style="
                                    width:100%;
                                    padding:8px 12px;
                                    border:1px solid #e2e8f0;
                                    border-radius:8px;
                                    font-size:14px;
                                    background:#f8fafc;
                                    color:#1e293b;
                                "
                            >
                                <option selected>
                                    <?php echo htmlspecialchars(
                                        !empty($juego['tema'])
                                            ? $juego['tema']
                                            : 'Sin tema'
                                    ); ?>
                                </option>
                            </select>

                            <small style="color:#64748b; font-size:11px;">
                                Esta categoría corresponde al tema seleccionado al crear el juego.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Puntos por acierto</label>

                            <input
                                type="number"
                                value="<?php echo (int)($juego['puntos_por_acierto'] ?? 50); ?>"
                                readonly
                                style="background:#f8fafc; cursor:not-allowed;"
                            >

                            <small style="color:#64748b; font-size:11px;">
                                Este valor se toma de la configuración del juego y no puede modificarse aquí.
                            </small>
                        </div>
                        <button type="submit" name="agregar_pareja" class="btn-agregar">
                            <i class="fa-solid fa-plus"></i> Agregar pareja
                        </button>
                    </form>
                </div>
                
                <!-- Lista de parejas -->
                <div>
                    <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b;">
                        <i class="fa-regular fa-list"></i> Parejas registradas
                        <span style="font-size: 13px; color: #64748b; font-weight: 400;">(<?php echo count($parejas); ?>)</span>
                    </h4>
                    
                    <?php if (empty($parejas)): ?>
                        <div class="sin-parejas">
                            <i class="fa-regular fa-grip"></i>
                            <p>Todavía no hay parejas</p>
                            <p style="font-size: 12px;">Agrega al menos 2 parejas para publicar.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-parejas">
                            <?php foreach ($parejas as $index => $pareja): ?>
                                <div class="item-pareja">
                                    <span class="numero"><?php echo $index + 1; ?></span>
                                    <div class="contenido">
                                        <div class="elementos">
                                            <!-- Elemento A: texto -->
                                            <span>
                                                <?php
                                                    echo htmlspecialchars(
                                                        $pareja['elemento_a_texto']
                                                        ??
                                                        'Sin texto'
                                                    );
                                                ?>
                                            </span>

                                            <span class="flecha">
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </span>

                                            <!-- Elemento B: imagen -->
                                            <?php if (!empty($pareja['elemento_b_imagen'])): ?>
                                                <img
                                                    src="<?php echo htmlspecialchars($pareja['elemento_b_imagen']); ?>"
                                                    class="imagen-miniatura"
                                                    alt="Imagen B"
                                                >
                                            <?php else: ?>
                                                <span style="color:#dc2626;">
                                                    Sin imagen
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($pareja['explicacion'])): ?>
                                            <div class="extra">
                                                <i class="fa-regular fa-comment-dots"></i>
                                                <?php echo htmlspecialchars($pareja['explicacion']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="extra">
                                            <i class="fa-regular fa-tag"></i>
                                            <?php echo htmlspecialchars(
                                                !empty($pareja['categoria'])
                                                    ? $pareja['categoria']
                                                    : ($juego['tema'] ?? 'Sin tema')
                                            ); ?>

                                            <span style="margin-left: 10px;">
                                                <i class="fa-regular fa-star"></i>
                                                <?php echo (int)$pareja['puntos']; ?> pts
                                            </span>
                                        </div>
                                    </div>
                                    <form method="POST" style="margin: 0;" onsubmit="return confirm('¿Eliminar esta pareja?');">
                                        <input type="hidden" name="id_pareja" value="<?php echo $pareja['id_pareja']; ?>">
                                        <button type="submit" name="eliminar_pareja" class="btn-eliminar" title="Eliminar">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Acciones -->
                    <div class="acciones-publicar">
                        <div style="flex: 1; font-size: 13px; color: #64748b;">
                            <i class="fa-regular fa-circle-info"></i>
                            Se necesitan al menos <strong>2 parejas</strong> para publicar.
                            <?php if (count($parejas) >= 2): ?>
                                <span style="color: #22c55e;">
                                    <i class="fa-regular fa-check-circle"></i> ¡Listo para publicar!
                                </span>
                            <?php endif; ?>
                        </div>
                        <form method="POST" style="margin: 0;">
                            <button type="submit" name="publicar" class="btn btn-success" <?php echo count($parejas) < 2 ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-rocket"></i> Publicar juego
                            </button>
                        </form>
                        <a href="detalle_juego_docente.php?id_juego=<?php echo $id_juego; ?>" class="btn btn-secondary">
                            <i class="fa-solid fa-times"></i> Cancelar
                        </a>
                    </div>
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
// SUBIR UNA SOLA IMAGEN PARA EL ELEMENTO B
// =============================================

function previsualizarImagenB() {
    const input =
        document.getElementById('imagenB');

    const file =
        input.files[0];

    if (!file) {
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        alert('⚠️ La imagen no debe superar los 5MB.');
        input.value = '';
        return;
    }

    const tiposPermitidos = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    if (!tiposPermitidos.includes(file.type)) {
        alert('⚠️ Solo se permiten imágenes JPG, PNG, GIF o WEBP.');
        input.value = '';
        return;
    }

    const uploadBox =
        document.getElementById('uploadBoxB');

    const placeholder =
        document.getElementById('placeholderB');

    const removeBtn =
        document.getElementById('removeB');

    const hiddenInput =
        document.getElementById('elementoBImagen');

    const previewAnterior =
        uploadBox.querySelector('img.preview');

    if (previewAnterior) {
        previewAnterior.remove();
    }

    const reader =
        new FileReader();

    reader.onload = function (e) {
        const img =
            document.createElement('img');

        img.src =
            e.target.result;

        img.className =
            'preview';

        uploadBox.appendChild(img);

        placeholder.style.display =
            'none';

        removeBtn.style.display =
            'flex';
    };

    reader.readAsDataURL(file);

    const formData =
        new FormData();

    formData.append('imagen', file);
    formData.append(
        'id_juego',
        <?php echo $id_juego; ?>
    );
    formData.append(
        'subir_imagen',
        '1'
    );

    uploadBox.style.borderColor =
        '#f59e0b';

    fetch(
        'subir_imagen_juego.php',
        {
            method: 'POST',
            body: formData
        }
    )
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(
                    data.message ||
                    'No se pudo subir la imagen.'
                );
            }

            hiddenInput.value =
                data.ruta;

            uploadBox.style.borderColor =
                '#22c55e';

            const anterior =
                uploadBox.querySelector(
                    '.nombre-archivo'
                );

            if (anterior) {
                anterior.remove();
            }

            const nombreArchivo =
                document.createElement('div');

            nombreArchivo.className =
                'nombre-archivo';

            nombreArchivo.textContent =
                file.name;

            uploadBox.appendChild(
                nombreArchivo
            );
        })
        .catch(error => {
            console.error(error);

            alert(
                '❌ Error al subir la imagen: ' +
                error.message
            );

            eliminarImagenB();
        });
}


// =============================================
// ELIMINAR IMAGEN DEL ELEMENTO B
// =============================================

function eliminarImagenB() {
    const uploadBox =
        document.getElementById('uploadBoxB');

    const placeholder =
        document.getElementById('placeholderB');

    const removeBtn =
        document.getElementById('removeB');

    const hiddenInput =
        document.getElementById('elementoBImagen');

    const input =
        document.getElementById('imagenB');

    const preview =
        uploadBox.querySelector('img.preview');

    if (preview) {
        preview.remove();
    }

    const nombreArchivo =
        uploadBox.querySelector(
            '.nombre-archivo'
        );

    if (nombreArchivo) {
        nombreArchivo.remove();
    }

    hiddenInput.value = '';
    input.value = '';

    removeBtn.style.display =
        'none';

    placeholder.style.display =
        'flex';

    placeholder.innerHTML =
        '<i class="fa-regular fa-image"></i> Subir imagen';

    uploadBox.style.borderColor =
        '#e2e8f0';
}


// =============================================
// VALIDACIÓN
// =============================================

document
    .getElementById('formPareja')
    .addEventListener(
        'submit',
        function (e) {
            const elementoA =
                document
                    .getElementById('elementoA')
                    .value
                    .trim();

            const imagenB =
                document
                    .getElementById('elementoBImagen')
                    .value
                    .trim();

            if (!elementoA) {
                e.preventDefault();

                alert(
                    '⚠️ El Elemento A debe contener texto.'
                );

                return;
            }

            if (!imagenB) {
                e.preventDefault();

                alert(
                    '⚠️ Debes subir una imagen para el Elemento B.'
                );
            }
        }
    );

</script>

<script src="jss/docente_dashboard.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>

</body>
</html>