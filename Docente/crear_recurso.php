<?php
session_start();

// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

// Verificar que el usuario haya iniciado sesión y sea Docente
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_docente = (int) $_SESSION['usuario']['id_usuario'];

$nombre_docente =
    $_SESSION['usuario']['nombre'] . ' ' .
    $_SESSION['usuario']['apellido_paterno'];

$mensaje = '';
$tipo_mensaje = '';

// ==========================================
// OBTENER CURSOS EXISTENTES DEL DOCENTE
// ==========================================

$stmt = $conexion->prepare("
    SELECT
        c.id_curso,
        c.nombre,
        c.id_materia,
        m.nombre AS materia,
        g.nombre AS grupo,
        ce.nombre AS ciclo,
        COUNT(DISTINCT i.id_alumno) AS alumnos_activos
    FROM cursos c
    INNER JOIN materias m
        ON m.id_materia = c.id_materia
    INNER JOIN grupos g
        ON g.id_grupo = c.id_grupo
    INNER JOIN ciclos_escolares ce
        ON ce.id_ciclo = c.id_ciclo
    LEFT JOIN inscripciones i
        ON i.id_curso = c.id_curso
        AND i.estado = 'Activo'
    WHERE c.id_docente = ?
      AND c.estado = 'Activo'
    GROUP BY
        c.id_curso,
        c.nombre,
        c.id_materia,
        m.nombre,
        g.nombre,
        ce.nombre
    ORDER BY c.nombre ASC
");

$stmt->bind_param("i", $id_docente);
$stmt->execute();

$cursos = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// ==========================================
// PROCESAR PUBLICACIÓN DEL RECURSO
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $id_curso = (int) ($_POST['id_curso'] ?? 0);
    $tipo_recurso = trim($_POST['tipo_curso'] ?? 'Documento');
    $estado = $_POST['estado'] ?? 'Activo';
    $compartido_tipo = $_POST['compartido_tipo'] ?? 'Curso';

    $tipos_permitidos = ['Video', 'PDF', 'Documento'];
    $estados_permitidos = ['Activo', 'Inactivo'];
    $compartidos_permitidos = ['Publico', 'Curso', 'Grupo'];

    // Validaciones
    $errores = [];

    if (empty($titulo)) {
        $errores[] = "El título es obligatorio.";
    }
    if ($id_curso <= 0) {
        $errores[] = "Debes seleccionar un curso.";
    }
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        $errores[] = "Debes seleccionar un archivo válido.";
    }
    if (!in_array($tipo_recurso, $tipos_permitidos, true)) {
        $errores[] = "El tipo de recurso seleccionado no es válido.";
    }
    if (!in_array($estado, $estados_permitidos, true)) {
        $errores[] = "El estado seleccionado no es válido.";
    }
    if (!in_array($compartido_tipo, $compartidos_permitidos, true)) {
        $errores[] = "El tipo de compartición no es válido.";
    }

    if (!empty($errores)) {
        $_SESSION['mensaje'] = implode(" ", $errores);
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: crear_recurso.php');
        exit;
    }

    try {
        // ==========================================
        // VERIFICAR QUE EL CURSO SEA DEL DOCENTE
        // ==========================================

        $stmt = $conexion->prepare("
            SELECT id_curso, id_materia
            FROM cursos
            WHERE id_curso = ?
              AND id_docente = ?
              AND estado = 'Activo'
            LIMIT 1
        ");

        $stmt->bind_param("ii", $id_curso, $id_docente);
        $stmt->execute();
        $curso_seleccionado = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$curso_seleccionado) {
            $_SESSION['mensaje'] = 'El curso seleccionado no existe o no pertenece al docente.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: crear_recurso.php');
            exit;
        }

        // ==========================================
        // PROCESAR ARCHIVO
        // ==========================================

        $archivo = $_FILES['archivo'];
        $nombre_original = basename($archivo['name']);
        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
        // AULAMOS: detectar tipo real por extension
        switch ($extension) {
            case 'mp4':
                $tipo_recurso = 'Video';
                break;
            case 'pdf':
                $tipo_recurso = 'PDF';
                break;
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'webp':
                $tipo_recurso = 'Imagen';
                break;
            case 'ppt':
            case 'pptx':
                $tipo_recurso = 'Presentación';
                break;
            default:
                $tipo_recurso = 'Documento';
                break;
        }

        // Tipos de archivo permitidos
        $tipos_archivo_permitidos = ['pdf', 'mp4', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'png', 'jpeg', 'gif', 'webp'];

        if (!in_array($extension, $tipos_archivo_permitidos, true)) {
            $_SESSION['mensaje'] = "Tipo de archivo no permitido. Extensiones permitidas: " . implode(', ', $tipos_archivo_permitidos);
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: crear_recurso.php');
            exit;
        }

        // Limitar tamaño a 50 MB
        if ($archivo['size'] > 50 * 1024 * 1024) {
            $_SESSION['mensaje'] = "El archivo excede el tamaño máximo (50MB).";
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: crear_recurso.php');
            exit;
        }

        // Crear carpeta si no existe
        $carpeta_fisica = __DIR__ . '/../uploads/recursos/';
        if (!is_dir($carpeta_fisica)) {
            if (!mkdir($carpeta_fisica, 0777, true)) {
                throw new Exception('No se pudo crear la carpeta de recursos.');
            }
        }

        // Guardar archivo
        $nombre_archivo = uniqid() . '.' . $extension;
        $ruta_fisica = $carpeta_fisica . $nombre_archivo;
        $ruta_publica = '/uploads/recursos/' . $nombre_archivo;

        if (!move_uploaded_file($archivo['tmp_name'], $ruta_fisica)) {
            throw new Exception('Error al guardar el archivo. Verifica los permisos de la carpeta.');
        }

        // ==========================================
        // GUARDAR EN BASE DE DATOS
        // ==========================================

        $id_materia = (int) $curso_seleccionado['id_materia'];

        $stmt = $conexion->prepare("
            INSERT INTO recursos_educativos (
                titulo,
                descripcion,
                tipo,
                url_recurso,
                id_docente,
                id_materia,
                id_curso,
                compartido_tipo,
                estado,
                fecha_publicacion
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "ssssiiiss",
            $titulo,
            $descripcion,
            $tipo_recurso,
            $ruta_publica,
            $id_docente,
            $id_materia,
            $id_curso,
            $compartido_tipo,
            $estado
        );

        if (!$stmt->execute()) {
            // Eliminar archivo si falla la BD
            if (file_exists($ruta_fisica)) {
                unlink($ruta_fisica);
            }
            throw new Exception('Error al guardar en la base de datos: ' . $stmt->error);
        }

        $stmt->close();

        $_SESSION['mensaje'] = '✅ Recurso "' . htmlspecialchars($titulo) . '" publicado correctamente.';
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: mis_recursos.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['mensaje'] = '❌ Error al publicar el recurso: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: crear_recurso.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Recurso</title>
    
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="styles/curso.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- NUEVA ACCESIBILIDAD -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    
    <style>
        /* Estilos para mensajes */
        .mensaje {
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .mensaje.exito {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        .mensaje.error {
            background: #fecaca;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .mensaje.info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        .upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            transition: all 0.3s;
            background: #f8fafc;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #8b5cf6;
            background: #f5f3ff;
        }
        .upload-area i {
            font-size: 40px;
            color: #8b5cf6;
            display: block;
            margin-bottom: 10px;
        }
        .upload-area .upload-text {
            margin: 0;
            color: #475569;
        }
        .upload-area .upload-text.archivo-seleccionado {
            color: #16a34a;
            font-weight: 500;
        }
        .upload-area .upload-hint {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 8px;
        }
        .form-actions-row {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .btn-cancelar {
            padding: 10px 24px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-cancelar:hover {
            background: #e2e8f0;
        }
        .btn-publicar {
            padding: 10px 24px;
            background: #8b5cf6;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-publicar:hover {
            background: #7c3aed;
            transform: translateY(-1px);
        }
        .btn-publicar:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }
        .form-group select:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
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
                <a href="crear_recurso.php" class="menu-item active"><i class="fa-solid fa-medal"></i> Crear Recurso</a>
                <a href="mis_recursos.php" class="menu-item"><i class="fa-solid fa-folder-open"></i> Mis Recursos</a>
                <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluación</a>
                <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                <a href="pasarlista.php" class="menu-item"><i class="fa-solid fa-bars"></i> Pasar Lista</a>
                
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
                    <h1>Crear recurso</h1>
                    <p>Comparte material con tus estudiantes</p>
                </div>
                <div class="header-actions">
                    <button type="button" class="btn-assistant" id="btn-asistente" onclick="window.location.href='../Alumno/ChatbotDocente.php?rol=docente'">
                        Asistente Virtual <span class="robot-icon">🤖</span>
                    </button>
                    <div class="icon-bell-container">
                        <i class="fa-regular fa-bell"></i>
                    </div>
                    <div class="user-profile">
                        <img src="https://placehold.co/40x40/ff7675/white?text=👨" alt="Avatar Docente" class="avatar">
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($nombre_docente); ?></span>
                            <span class="user-role">Docente</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- ========================================== -->
            <!-- MENSAJES DE ÉXITO O ERROR                  -->
            <!-- ========================================== -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="mensaje <?php echo $_SESSION['tipo_mensaje'] ?? 'info'; ?>">
                    <i class="fa-solid <?php 
                        $tipo = $_SESSION['tipo_mensaje'] ?? 'info';
                        if ($tipo === 'success' || $tipo === 'exito') echo 'fa-check-circle';
                        elseif ($tipo === 'error') echo 'fa-exclamation-circle';
                        else echo 'fa-info-circle';
                    ?>"></i>
                    <?php echo $_SESSION['mensaje']; ?>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
                <?php unset($_SESSION['tipo_mensaje']); ?>
            <?php endif; ?>

            <div class="main-grid">
                
                <!-- COLUMNA IZQUIERDA -->
                <div class="left-column">
                    
                    <!-- 1. Tipo de curso -->
                    <section class="section-container">
                        <h3 class="section-title">Tipo de recurso</h3>
                        <div class="course-types-grid">
                            <button type="button" class="type-card" data-tipo="Video">
                                <div class="type-icon text-purple"><i class="fa-solid fa-play"></i></div>
                                <h4>Video</h4>
                                <p>Sube un video</p>
                            </button>
                            <button type="button" class="type-card" data-tipo="PDF">
                                <div class="type-icon text-red"><i class="fa-regular fa-file-pdf"></i></div>
                                <h4>PDF</h4>
                                <p>Sube un archivo PDF</p>
                            </button>
                            <button type="button" class="type-card" data-tipo="Documento">
                                <div class="type-icon text-blue"><i class="fa-regular fa-file-lines"></i></div>
                                <h4>Documento</h4>
                                <p>Sube un documento</p>
                            </button>
                        </div>
                    </section>

                    <!-- 2. Información del recurso -->
                    <section class="section-container border-container">
                        <h3 class="section-title">Información del recurso</h3>
                        
                        <form class="course-form" method="POST" action="" enctype="multipart/form-data">

                            <input type="hidden" id="tipo_curso" name="tipo_curso" value="Documento">
                            
                            <div class="form-group">
                                <label>Título del recurso <span class="text-danger">*</span></label>
                                <input type="text" name="titulo" placeholder="Ej. La fotosíntesis" value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Descripción <span class="text-muted">(opcional)</span></label>
                                <input type="text" name="descripcion" placeholder="Describe brevemente el contenido" value="<?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Seleccionar curso <span class="text-danger">*</span></label>
                                <select name="id_curso" required>
                                    <option value="">Elige un curso existente</option>
                                    <?php foreach ($cursos as $curso): ?>
                                        <option value="<?php echo (int) $curso['id_curso']; ?>" <?php echo (($_POST['id_curso'] ?? '') == $curso['id_curso']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($curso['nombre'] . ' · ' . $curso['materia'] . ' · Grupo ' . $curso['grupo'] . ' · ' . (int) $curso['alumnos_activos'] . ' alumno(s)'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (count($cursos) === 0): ?>
                                    <p style="color:#dc2626; font-size:13px; margin-top:6px;">No tienes cursos activos disponibles.</p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>Compartir con</label>
                                <select name="compartido_tipo">
                                    <option value="Publico" <?php echo (($_POST['compartido_tipo'] ?? '') == 'Publico') ? 'selected' : ''; ?>>Público (todos los alumnos)</option>
                                    <option value="Curso" <?php echo (($_POST['compartido_tipo'] ?? '') == 'Curso') ? 'selected' : ''; ?>>Solo alumnos del curso seleccionado</option>
                                    <option value="Grupo" <?php echo (($_POST['compartido_tipo'] ?? '') == 'Grupo') ? 'selected' : ''; ?>>Solo alumnos del grupo</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Estado</label>
                                <select name="estado">
                                    <option value="Activo" <?php echo (($_POST['estado'] ?? '') == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                                    <option value="Inactivo" <?php echo (($_POST['estado'] ?? '') == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            </div>

                            <div class="upload-area" id="uploadArea">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <p class="upload-text" id="uploadText">Toca para seleccionar o arrastra tu archivo aquí</p>
                                <input type="file" name="archivo" id="archivo" style="display: none;" accept=".pdf,.mp4,.doc,.docx,.ppt,.pptx,.txt,.jpg,.png,.jpeg,.gif,.webp" required>
                                <p class="upload-hint">Formatos permitidos: PDF, MP4, DOC, DOCX, PPT, PPTX, TXT, JPG, PNG, GIF, WEBP</p>
                            </div>

                            <div class="form-actions-row">
                                <a href="docente_dashboard.php" class="btn-cancelar">Cancelar</a>
                                <button type="submit" class="btn-publicar" id="btnPublicar">Publicar recurso</button>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- COLUMNA DERECHA -->
                <div class="right-column">
                    <!-- Calendario -->
                    <aside class="calendar-container">
                        <!-- Cabecera y Navegación -->
                        <div class="calendar-header">
                            <div class="nav-left">
                                <button id="prev-year" class="nav-btn" title="Año anterior">&laquo;</button>
                                <button id="prev-month" class="nav-btn" title="Mes anterior">&lsaquo;</button>
                            </div>
                            
                            <h2 id="month-year-title">MES AÑO</h2>
                            
                            <div class="nav-right">
                                <button id="next-month" class="nav-btn" title="Mes siguiente">&rsaquo;</button>
                                <button id="next-year" class="nav-btn" title="Año siguiente">&raquo;</button>
                            </div>
                        </div>

                        <!-- Días de la semana -->
                        <div class="calendar-weekdays">
                            <div class="weekday">Do</div>
                            <div class="weekday">Lu</div>
                            <div class="weekday">Ma</div>
                            <div class="weekday">Mi</div>
                            <div class="weekday">Ju</div>
                            <div class="weekday">Vi</div>
                            <div class="weekday">Sá</div>
                        </div>

                        <!-- Contenedor dinámico de los días -->
                        <div id="calendar-days" class="calendar-days-grid">
                            <!-- JavaScript inyectará los días aquí -->
                        </div>
                    </aside>
                </div>
            </div>
            <!-- ========================================== -->
            <!-- NUEVA BARRA DE ACCESIBILIDAD               -->
            <!-- ========================================== -->
            <?php include '../Accesibilidad/accesibilidad.php'; ?>
        </main>
    </div>

    <!-- ========================================== -->
    <!-- BOTÓN FLOTANTE DE ACCESIBILIDAD            -->
    <!-- ========================================== -->
    <button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
        <i class="fa-solid fa-universal-access"></i>
    </button>

    <!-- NUEVA ACCESIBILIDAD -->
    <script src="../Accesibilidad/accesibilidad.js"></script>
    <script src="../Accesibilidad/navegacionTeclado.js"></script>

    <script src="jss/docente_dashboard.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // ==========================================
        // SELECCIONAR TIPO DE RECURSO
        // ==========================================
        const typeCards = document.querySelectorAll('.type-card');
        const tipoCursoInput = document.getElementById('tipo_curso');
        
        typeCards.forEach(card => {
            card.addEventListener('click', function() {
                typeCards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                const tipo = this.dataset.tipo;
                tipoCursoInput.value = tipo;
            });
        });
        
        // Documento por defecto. El servidor valida el tipo real.
        const tarjetaDocumento =
            document.querySelector('.type-card[data-tipo="Documento"]');

        if (tarjetaDocumento) {
            typeCards.forEach(c => c.classList.remove('selected'));
            tarjetaDocumento.classList.add('selected');
            tipoCursoInput.value = 'Documento';
        }

        // ==========================================
        // MANEJO DE ARCHIVOS
        // ==========================================
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('archivo');
        const uploadText = document.getElementById('uploadText');
        const btnPublicar = document.getElementById('btnPublicar');

        function detectarTipoPorArchivo(archivo) {
            if (!archivo) return;

            const nombre = String(archivo.name || '').toLowerCase();
            const extension = nombre.includes('.')
                ? nombre.split('.').pop()
                : '';

            let tipo = 'Documento';

            if (extension === 'mp4') {
                tipo = 'Video';
            } else if (extension === 'pdf') {
                tipo = 'PDF';
            }

            tipoCursoInput.value = tipo;
            typeCards.forEach(c => c.classList.remove('selected'));

            const tarjeta =
                document.querySelector(
                    '.type-card[data-tipo="' + tipo + '"]'
                );

            if (tarjeta) {
                tarjeta.classList.add('selected');
            }
        }

        // Click en el área de upload
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });

        // Seleccionar archivo
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const archivo = this.files[0];
                uploadText.textContent = '📎 ' + archivo.name + ' (' + (archivo.size / 1024 / 1024).toFixed(2) + ' MB)';
                uploadText.className = 'upload-text archivo-seleccionado';
                detectarTipoPorArchivo(archivo);
            } else {
                uploadText.textContent = 'Toca para seleccionar o arrastra tu archivo aquí';
                uploadText.className = 'upload-text';
            }
        });

        // Arrastrar archivo
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#8b5cf6';
            this.style.background = '#f5f3ff';
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#cbd5e1';
            this.style.background = '#f8fafc';
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#cbd5e1';
            this.style.background = '#f8fafc';

            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                const archivo = e.dataTransfer.files[0];
                uploadText.textContent = '📎 ' + archivo.name + ' (' + (archivo.size / 1024 / 1024).toFixed(2) + ' MB)';
                uploadText.className = 'upload-text archivo-seleccionado';
                detectarTipoPorArchivo(archivo);
            }
        });

        // ==========================================
        // PREVENIR ENVÍO DOBLE
        // ==========================================
        document.querySelector('form').addEventListener('submit', function() {
            btnPublicar.disabled = true;
            btnPublicar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Publicando...';
        });
    });
    </script>
</body>
</html>