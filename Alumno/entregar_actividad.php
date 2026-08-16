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
$id_actividad = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_actividad <= 0) {
    header('Location: actividades.php?error=Actividad no válida');
    exit;
}

// Obtener datos de la actividad
$query = "
    SELECT 
        a.id_actividad,
        a.titulo,
        a.descripcion,
        a.instrucciones,
        a.tipo,
        a.fecha_limite,
        a.puntaje_maximo,
        a.permite_entrega_archivo,
        m.nombre AS asignatura,
        c.nombre AS curso_nombre,
        ae.id_actividad_estudiante,
        ae.estado,
        ae.porcentaje_avance,
        e.id_entrega,
        e.texto_entrega,
        e.estado AS entrega_estado,
        e.calificacion,
        e.retroalimentacion,
        adj.id_adjunto,
        adj.nombre_archivo,
        adj.url_archivo
    FROM actividades a
    JOIN cursos c ON a.id_curso = c.id_curso
    JOIN materias m ON c.id_materia = m.id_materia
    LEFT JOIN actividad_estudiantes ae ON ae.id_actividad = a.id_actividad AND ae.id_alumno = ?
    LEFT JOIN entregas e ON e.id_actividad_estudiante = ae.id_actividad_estudiante
    LEFT JOIN adjuntos adj ON adj.entidad_tipo = 'Entrega' AND adj.entidad_id = e.id_entrega
    WHERE a.id_actividad = ?
";

$stmt = $conexion->prepare($query);
$stmt->bind_param("ii", $id_alumno, $id_actividad);
$stmt->execute();
$resultado = $stmt->get_result();
$actividad = $resultado->fetch_assoc();
$stmt->close();

if (!$actividad) {
    header('Location: actividades.php?error=Actividad no encontrada');
    exit;
}

// Obtener foto de perfil del alumno
$foto_perfil_alumno = $_SESSION['usuario']['foto_perfil'] ?? null;
$ruta_foto_alumno = !empty($foto_perfil_alumno) ? '../uploads/perfiles/' . $foto_perfil_alumno : 'https://placehold.co/40x40/ff7675/white?text=👩';
$nombre_alumno = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];

$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entregar Actividad - Aulamos</title>
    
    <link rel="stylesheet" href="Style/Inicio.css">
    <link rel="stylesheet" href="Style/actividades.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    
    <style>
        /* Estilos principales */
        .entrega-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .card-actividad-detalle {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .card-actividad-detalle h2 {
            margin: 0 0 10px 0;
            color: #1e293b;
        }
        .card-actividad-detalle .asignatura {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .card-actividad-detalle .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
        }
        .card-actividad-detalle .info-item {
            display: flex;
            flex-direction: column;
        }
        .card-actividad-detalle .info-item .label {
            font-size: 12px;
            color: #94a3b8;
            text-transform: uppercase;
            font-weight: 600;
        }
        .card-actividad-detalle .info-item .value {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }
        .card-actividad-detalle .descripcion {
            margin: 15px 0;
            color: #475569;
        }
        .card-actividad-detalle .instrucciones {
            background: #f1f5f9;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid #3b71f3;
        }
        .form-entrega {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .form-entrega .form-group {
            margin-bottom: 20px;
        }
        .form-entrega label {
            font-weight: 600;
            font-size: 14px;
            color: #1e293b;
            display: block;
            margin-bottom: 5px;
        }
        .form-entrega textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 80px;
            transition: border-color 0.2s;
        }
        .form-entrega textarea:focus {
            border-color: #3b71f3;
            outline: none;
        }
        .file-drop-zone {
            border: 2px dashed #e2e8f0;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        .file-drop-zone:hover {
            border-color: #3b71f3;
            background: #f8fafc;
        }
        .file-drop-zone.dragover {
            border-color: #3b71f3;
            background: #eff6ff;
        }
        .file-drop-zone .icono {
            font-size: 48px;
            color: #94a3b8;
            display: block;
            margin-bottom: 10px;
        }
        .file-drop-zone .icono.uploading {
            color: #3b71f3;
            animation: pulse 1.5s infinite;
        }
        .file-drop-zone .icono.success {
            color: #22c55e;
        }
        .file-drop-zone .icono.error {
            color: #dc2626;
        }
        .file-drop-zone p {
            color: #64748b;
            margin: 0;
        }
        .file-drop-zone .formato {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 5px;
        }
        .file-drop-zone input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .progress-container {
            display: none;
            margin-top: 15px;
        }
        .progress-container.active {
            display: block;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-bar .fill {
            height: 100%;
            background: #3b71f3;
            border-radius: 4px;
            transition: width 0.3s ease;
            width: 0%;
        }
        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #64748b;
            margin-top: 5px;
        }
        .progress-text .porcentaje {
            font-weight: 600;
            color: #1e293b;
        }
        .archivo-adjunto {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
        }
        .archivo-adjunto i {
            font-size: 24px;
            color: #3b71f3;
        }
        .archivo-adjunto .info {
            flex: 1;
        }
        .archivo-adjunto .info .nombre {
            font-weight: 600;
            font-size: 14px;
        }
        .archivo-adjunto .info .tamano {
            font-size: 12px;
            color: #94a3b8;
        }
        .archivo-adjunto .btn-eliminar {
            background: none;
            border: none;
            color: #dc2626;
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
        }
        .btn-acciones {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
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
        .btn-danger {
            background: #dc2626;
            color: white;
        }
        .btn-danger:hover {
            background: #b91c1c;
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
        .badge-entregado {
            background: #dcfce7;
            color: #166534;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-calificado {
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .calificacion-final {
            font-size: 28px;
            font-weight: 700;
            color: #3b71f3;
            text-align: center;
            padding: 20px;
        }
        .retroalimentacion {
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
            border-left: 4px solid #3b71f3;
        }
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #e2e8f0;
            border-radius: 50%;
            border-top-color: #3b71f3;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .mensaje-exito {
            background: #dcfce7;
            color: #166534;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #22c55e;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mensaje-exito i {
            font-size: 20px;
        }
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
            padding: 20px 0;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
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
            <a href="alumno.php" class="menu-item"><i class="fa-solid fa-cubes"></i> Inicio</a>
            <a href="actividades.php" class="menu-item"><i class="fa-solid fa-cubes"></i> Mis actividades</a>
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
                <h1>Entregar actividad</h1>
                <p>Sube tu trabajo para ser evaluado</p>
            </div>
            <div class="header-actions">
                <button class="btn-assistant" id="btn-asistente" onclick="window.open('Chatbot.php?rol=alumno', '_blank')">
                    Asistente Virtual <span class="robot-icon">🤖</span>
                </button>
                <div class="icon-bell"><i class="fa-regular fa-bell"></i></div>
                <a href="perfil_alumno.php" class="user-profile">
                    <img src="<?php echo $ruta_foto_alumno; ?>" alt="Avatar Alumno" class="avatar">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($nombre_alumno); ?></span>
                        <span class="user-role">Alumno</span>
                    </div>
                </a>
            </div>
        </header>

        <div class="entrega-container">
            
            <!-- ALERTA DE MENSAJE -->
            <div id="alertMessage" class="alert"></div>

            <!-- DETALLES DE LA ACTIVIDAD -->
            <div class="card-actividad-detalle">
                <h2><?php echo htmlspecialchars($actividad['titulo']); ?></h2>
                <div class="asignatura">
                    <i class="fa-regular fa-bookmark"></i> 
                    <?php echo htmlspecialchars($actividad['asignatura']); ?> - 
                    <?php echo htmlspecialchars($actividad['curso_nombre']); ?>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">Fecha límite</span>
                        <span class="value"><?php echo date('d M, Y H:i', strtotime($actividad['fecha_limite'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Puntaje máximo</span>
                        <span class="value"><?php echo $actividad['puntaje_maximo']; ?> pts</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Tipo</span>
                        <span class="value"><?php echo $actividad['tipo']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Estado</span>
                        <span class="value">
                            <?php if ($actividad['entrega_estado'] === 'Calificada'): ?>
                                <span class="badge-calificado">Calificada</span>
                            <?php elseif ($actividad['entrega_estado'] === 'Entregada' || $actividad['estado'] === 'Completada'): ?>
                                <span class="badge-entregado">Entregada</span>
                            <?php else: ?>
                                <span style="color: #f39c12; font-weight: 600;">Pendiente</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                
                <div class="descripcion">
                    <strong>Descripción:</strong>
                    <p><?php echo nl2br(htmlspecialchars($actividad['descripcion'])); ?></p>
                </div>
                
                <?php if ($actividad['instrucciones']): ?>
                    <div class="instrucciones">
                        <strong><i class="fa-regular fa-list-check"></i> Instrucciones:</strong>
                        <p><?php echo nl2br(htmlspecialchars($actividad['instrucciones'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- FORMULARIO DE ENTREGA -->
            <?php if ($actividad['entrega_estado'] !== 'Calificada'): ?>
                
                <form class="form-entrega" id="formEntrega" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_actividad" value="<?php echo $id_actividad; ?>">
                    
                    <div class="form-group">
                        <label for="texto_entrega">Comentario para el docente (opcional)</label>
                        <textarea id="texto_entrega" name="texto_entrega" placeholder="Escribe un comentario sobre tu entrega..."><?php echo htmlspecialchars($actividad['texto_entrega'] ?? ''); ?></textarea>
                    </div>
                    
                    <?php if ($actividad['permite_entrega_archivo']): ?>
                        <div class="form-group">
                            <label>Adjuntar archivo</label>
                            <div class="file-drop-zone" id="dropZone">
                                <span class="icono" id="dropIcon"><i class="fa-regular fa-file-arrow-up"></i></span>
                                <p id="dropText">Arrastra o haz clic para subir tu archivo</p>
                                <div class="formato">PDF, Word, Excel, Imagen, ZIP · Máximo 10 MB</div>
                                <input type="file" name="archivo_entrega" id="archivoInput" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.rar">
                            </div>
                            
                            <!-- Barra de progreso -->
                            <div class="progress-container" id="progressContainer">
                                <div class="progress-bar">
                                    <div class="fill" id="progressFill"></div>
                                </div>
                                <div class="progress-text">
                                    <span id="progressStatus">Subiendo archivo...</span>
                                    <span class="porcentaje" id="progressPercent">0%</span>
                                </div>
                            </div>
                            
                            <!-- Archivo adjunto actual -->
                            <?php if ($actividad['id_adjunto']): ?>
                                <div class="archivo-adjunto" id="archivoActual">
                                    <i class="fa-regular fa-file-pdf"></i>
                                    <div class="info">
                                        <div class="nombre"><?php echo htmlspecialchars($actividad['nombre_archivo']); ?></div>
                                        <div class="tamano">Archivo adjunto actual</div>
                                    </div>
                                    <button type="button" class="btn-eliminar" onclick="eliminarArchivo(<?php echo $actividad['id_entrega']; ?>, <?php echo $id_actividad; ?>)">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="btn-acciones">
                        <a href="actividades.php" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                        <button type="submit" id="btnEntregar" class="btn btn-primary">
                            <i class="fa-regular fa-paper-plane"></i> Entregar
                        </button>
                    </div>
                </form>
                
            <?php else: ?>
                
                <!-- ENTREGA CALIFICADA -->
                <div class="mensaje-exito">
                    <i class="fa-regular fa-check-circle"></i>
                    <div>
                        <strong>¡Actividad calificada!</strong>
                        <p style="margin: 0; color: #166534;">Tu trabajo ha sido revisado por el docente.</p>
                    </div>
                </div>
                
                <div class="form-entrega">
                    <div class="calificacion-final">
                        <?php echo $actividad['calificacion']; ?> / <?php echo $actividad['puntaje_maximo']; ?>
                        <div style="font-size: 14px; font-weight: 400; color: #64748b;">Calificación obtenida</div>
                    </div>
                    
                    <?php if ($actividad['retroalimentacion']): ?>
                        <div class="retroalimentacion">
                            <strong><i class="fa-regular fa-comment"></i> Retroalimentación del docente:</strong>
                            <p><?php echo nl2br(htmlspecialchars($actividad['retroalimentacion'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($actividad['id_adjunto']): ?>
                        <div style="margin-top: 15px;">
                            <a href="<?php echo $actividad['url_archivo']; ?>" target="_blank" class="btn btn-primary" style="text-decoration: none;">
                                <i class="fa-regular fa-eye"></i> Ver archivo entregado
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="btn-acciones" style="justify-content: center;">
                        <a href="actividades.php" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
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

<!-- SCRIPTS -->
<script>
// =============================================
// CONFIGURACIÓN DE LA SUBIDA CON AJAX
// =============================================

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formEntrega');
    const fileInput = document.getElementById('archivoInput');
    const dropZone = document.getElementById('dropZone');
    const progressContainer = document.getElementById('progressContainer');
    const progressFill = document.getElementById('progressFill');
    const progressStatus = document.getElementById('progressStatus');
    const progressPercent = document.getElementById('progressPercent');
    const btnEntregar = document.getElementById('btnEntregar');
    const alertMessage = document.getElementById('alertMessage');
    const dropIcon = document.getElementById('dropIcon');
    const dropText = document.getElementById('dropText');
    const textoEntrega = document.getElementById('texto_entrega');
    
    let archivoSeleccionado = null;
    let estaSubiendo = false;

    // =============================================
    // EVENTOS DE ARRASTRE Y SOLTURA
    // =============================================
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
        });
    });

    dropZone.addEventListener('drop', function(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelect(files[0]);
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFileSelect(this.files[0]);
        }
    });

    // =============================================
    // MANEJAR SELECCIÓN DE ARCHIVO
    // =============================================
    
    function handleFileSelect(file) {
        // Validar tamaño
        if (file.size > 10485760) { // 10MB
            mostrarAlerta('El archivo no debe superar los 10MB.', 'error');
            fileInput.value = '';
            return;
        }

        // Validar extensión
        const extensionesPermitidas = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip', 'rar'];
        const extension = file.name.split('.').pop().toLowerCase();
        
        if (!extensionesPermitidas.includes(extension)) {
            mostrarAlerta('Formato de archivo no permitido. Formatos permitidos: PDF, Word, Excel, Imagen, ZIP.', 'error');
            fileInput.value = '';
            return;
        }

        archivoSeleccionado = file;
        dropIcon.innerHTML = '<i class="fa-regular fa-file-circle-check"></i>';
        dropIcon.className = 'icono success';
        dropText.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
        dropText.style.color = '#22c55e';
        dropText.style.fontWeight = '600';
        
        mostrarAlerta('Archivo seleccionado correctamente. Haz clic en "Entregar" para subirlo.', 'info');
    }

    // =============================================
    // SUBIR ARCHIVO CON AJAX
    // =============================================
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (estaSubiendo) return;
        
        const texto = textoEntrega.value.trim();
        const tieneArchivo = archivoSeleccionado !== null;
        const tieneArchivoActual = <?php echo $actividad['id_adjunto'] ? 1 : 0; ?>;
        
        // Validar que tenga al menos comentario o archivo
        if (!texto && !tieneArchivo && !tieneArchivoActual) {
            mostrarAlerta('Debes escribir un comentario o adjuntar un archivo.', 'error');
            return;
        }
        
        // Si tiene archivo seleccionado, subirlo primero
        if (tieneArchivo) {
            subirArchivoConAJAX();
        } else {
            // Solo guardar comentario
            guardarEntrega(texto);
        }
    });

    function subirArchivoConAJAX() {
        const formData = new FormData();
        formData.append('archivo_entrega', archivoSeleccionado);
        formData.append('id_actividad', '<?php echo $id_actividad; ?>');
        formData.append('texto_entrega', textoEntrega.value.trim());
        formData.append('accion', 'subir_archivo');
        
        estaSubiendo = true;
        btnEntregar.disabled = true;
        btnEntregar.innerHTML = '<span class="spinner"></span> Subiendo...';
        
        // Mostrar barra de progreso
        progressContainer.classList.add('active');
        progressFill.style.width = '0%';
        progressPercent.textContent = '0%';
        progressStatus.textContent = 'Iniciando subida...';
        
        dropIcon.innerHTML = '<i class="fa-regular fa-circle-notch fa-spin"></i>';
        dropIcon.className = 'icono uploading';
        dropText.textContent = 'Subiendo archivo...';
        
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressFill.style.width = percent + '%';
                progressPercent.textContent = percent + '%';
                
                if (percent < 30) {
                    progressStatus.textContent = 'Subiendo archivo...';
                } else if (percent < 60) {
                    progressStatus.textContent = 'Procesando archivo...';
                } else if (percent < 90) {
                    progressStatus.textContent = 'Casi listo...';
                } else {
                    progressStatus.textContent = 'Finalizando...';
                }
            }
        });
        
        xhr.onload = function() {
            estaSubiendo = false;
            btnEntregar.disabled = false;
            btnEntregar.innerHTML = '<i class="fa-regular fa-paper-plane"></i> Entregar';
            
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        progressFill.style.width = '100%';
                        progressPercent.textContent = '100%';
                        progressStatus.textContent = '¡Completado!';
                        
                        dropIcon.innerHTML = '<i class="fa-regular fa-check-circle"></i>';
                        dropIcon.className = 'icono success';
                        dropText.textContent = 'Archivo subido correctamente';
                        dropText.style.color = '#22c55e';
                        
                        mostrarAlerta(response.message || '¡Archivo subido correctamente!', 'success');
                        
                        // Recargar la página después de 2 segundos
                        setTimeout(function() {
                            window.location.href = 'entregar_actividad.php?id=<?php echo $id_actividad; ?>&success=1';
                        }, 2000);
                    } else {
                        mostrarAlerta(response.message || 'Error al subir el archivo.', 'error');
                        dropIcon.innerHTML = '<i class="fa-regular fa-circle-xmark"></i>';
                        dropIcon.className = 'icono error';
                        dropText.textContent = 'Error al subir archivo';
                        dropText.style.color = '#dc2626';
                    }
                } catch (e) {
                    mostrarAlerta('Error al procesar la respuesta del servidor.', 'error');
                }
            } else {
                mostrarAlerta('Error de conexión con el servidor. Intenta nuevamente.', 'error');
            }
            
            progressContainer.classList.remove('active');
            archivoSeleccionado = null;
        };
        
        xhr.onerror = function() {
            estaSubiendo = false;
            btnEntregar.disabled = false;
            btnEntregar.innerHTML = '<i class="fa-regular fa-paper-plane"></i> Entregar';
            progressContainer.classList.remove('active');
            mostrarAlerta('Error de red. Verifica tu conexión.', 'error');
        };
        
        xhr.open('POST', 'upload_ajax.php', true);
        xhr.send(formData);
    }

    function guardarEntrega(texto) {
        const formData = new FormData();
        formData.append('id_actividad', '<?php echo $id_actividad; ?>');
        formData.append('texto_entrega', texto);
        formData.append('accion', 'guardar_texto');
        
        btnEntregar.disabled = true;
        btnEntregar.innerHTML = '<span class="spinner"></span> Guardando...';
        
        fetch('upload_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btnEntregar.disabled = false;
            btnEntregar.innerHTML = '<i class="fa-regular fa-paper-plane"></i> Entregar';
            
            if (data.success) {
                mostrarAlerta(data.message || '¡Entrega guardada correctamente!', 'success');
                setTimeout(function() {
                    window.location.href = 'entregar_actividad.php?id=<?php echo $id_actividad; ?>&success=1';
                }, 2000);
            } else {
                mostrarAlerta(data.message || 'Error al guardar la entrega.', 'error');
            }
        })
        .catch(error => {
            btnEntregar.disabled = false;
            btnEntregar.innerHTML = '<i class="fa-regular fa-paper-plane"></i> Entregar';
            mostrarAlerta('Error de conexión. Intenta nuevamente.', 'error');
        });
    }

    // =============================================
    // ELIMINAR ARCHIVO
    // =============================================
    
    window.eliminarArchivo = function(idEntrega, idActividad) {
        if (!confirm('¿Estás seguro de que deseas eliminar este archivo?')) return;
        
        fetch('upload_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'accion=eliminar_archivo&id_entrega=' + idEntrega + '&id_actividad=' + idActividad
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const archivoActual = document.getElementById('archivoActual');
                if (archivoActual) archivoActual.remove();
                mostrarAlerta('Archivo eliminado correctamente.', 'success');
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            } else {
                mostrarAlerta(data.message || 'Error al eliminar el archivo.', 'error');
            }
        })
        .catch(error => {
            mostrarAlerta('Error de conexión.', 'error');
        });
    };

    // =============================================
    // FUNCIONES DE UTILIDAD
    // =============================================
    
    function mostrarAlerta(mensaje, tipo) {
        alertMessage.textContent = mensaje;
        alertMessage.className = 'alert alert-' + tipo + ' show';
        
        // Auto ocultar después de 5 segundos
        clearTimeout(window.alertTimeout);
        window.alertTimeout = setTimeout(function() {
            alertMessage.classList.remove('show');
        }, 5000);
    }

    // Verificar si hay mensaje de éxito en la URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        mostrarAlerta('¡Entrega realizada con éxito!', 'success');
    }
});

console.log('📤 Página de entrega con AJAX cargada correctamente');
</script>

<script src="js/actividades.js"></script>
<script src="js/Inicio.js"></script>
<script src="../Accesibilidad/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>