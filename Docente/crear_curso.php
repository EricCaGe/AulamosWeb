<?php
session_start();

// Verificar que el usuario haya iniciado sesión y sea Docente
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_docente = $_SESSION['usuario']['id_usuario'];
$nombre_docente = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];

$mensaje = '';
$tipo_mensaje = '';

// ========================================== */
// OBTENER MATERIAS PARA EL SELECT           */
// ========================================== */
$stmt = $conexion->prepare("SELECT id_materia, nombre FROM materias WHERE estado = 'Activa' ORDER BY nombre");
$stmt->execute();
$materias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ========================================== */
// OBTENER GRUPOS PARA EL SELECT             */
// ========================================== */
$stmt = $conexion->prepare("SELECT id_grupo, nombre FROM grupos WHERE estado = 'Activo' ORDER BY nombre");
$stmt->execute();
$grupos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ========================================== */
// OBTENER CICLOS ESCOLARES                  */
// ========================================== */
$stmt = $conexion->prepare("SELECT id_ciclo, nombre FROM ciclos_escolares WHERE estado = 'Activo' ORDER BY fecha_inicio DESC");
$stmt->execute();
$ciclos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ========================================== */
// PROCESAR FORMULARIO                        */
// ========================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $id_materia = $_POST['id_materia'] ?? 0;
    $id_grupo = $_POST['id_grupo'] ?? 0;
    $id_ciclo = $_POST['id_ciclo'] ?? 0;
    $estado = $_POST['estado'] ?? 'Activo';

    // Validar campos obligatorios
    if (empty($nombre) || empty($id_materia) || empty($id_grupo) || empty($id_ciclo)) {
        $mensaje = '❌ Todos los campos obligatorios deben estar llenos.';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Insertar curso
            $stmt = $conexion->prepare("
                INSERT INTO cursos (
                    nombre, 
                    descripcion, 
                    id_materia, 
                    id_grupo, 
                    id_docente, 
                    id_ciclo, 
                    estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssiiiss", $nombre, $descripcion, $id_materia, $id_grupo, $id_docente, $id_ciclo, $estado);
            $stmt->execute();
            $id_curso = $conexion->insert_id;
            $stmt->close();

            $mensaje = '✅ Curso "' . htmlspecialchars($nombre) . '" creado exitosamente.';
            $tipo_mensaje = 'exito';

            // ========================================== */
            // SUBIR ARCHIVO                             */
            // ========================================== */
            if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                $archivo = $_FILES['archivo'];
                $nombre_original = basename($archivo['name']);
                $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
                
                // Tipos permitidos
                $tipos_permitidos = ['pdf', 'mp4', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'png'];
                
                if (in_array($extension, $tipos_permitidos)) {
                    // Generar nombre único
                    $nombre_archivo = uniqid() . '.' . $extension;
                    $ruta_destino = '../uploads/cursos/' . $nombre_archivo;
                    
                    // Crear carpeta si no existe
                    if (!is_dir('../uploads/cursos/')) {
                        mkdir('../uploads/cursos/', 0777, true);
                    }
                    
                    // Mover archivo
                    if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                        // Guardar en recursos_educativos
                        $tipo_recurso = $_POST['tipo_curso'] ?? 'Documento';
                        $titulo_recurso = $nombre;
                        
                        $stmt = $conexion->prepare("
                            INSERT INTO recursos_educativos (
                                titulo, 
                                descripcion, 
                                tipo, 
                                url_recurso, 
                                id_materia, 
                                id_docente, 
                                compartido_tipo,
                                estado
                            ) VALUES (?, ?, ?, ?, ?, ?, 'Curso', 'Activo')
                        ");
                        $stmt->bind_param("ssssii", $titulo_recurso, $descripcion, $tipo_recurso, $ruta_destino, $id_materia, $id_docente);
                        $stmt->execute();
                        $stmt->close();
                        
                        $mensaje .= ' 📎 Archivo subido correctamente.';
                    }
                }
            }
            
            // Limpiar campos después de guardar
            $_POST = array();
            
        } catch (Exception $e) {
            $mensaje = '❌ Error al crear el curso: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Curso - Aulamos</title>
    
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="styles/curso.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="dashboard-container">
        
        <!-- BARRA LATERAL -->
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../img/logogeneral.png" alt="Logo Aulamos" class="logo-img">
                <div>
                    <h2>AULAMOS</h2>
                    <p>Aprendemos juntos</p>
                </div>
            </div>
            <nav class="menu">
                <a href="docente_dashboard.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="crear_curso.php" class="menu-item active"><i class="fa-solid fa-medal"></i> Crear Curso</a>
                <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluación</a>
                <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                <a href="mas.php" class="menu-item"><i class="fa-solid fa-bars"></i> Más</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-gear"></i> Configuración</a>
                <a href="#" class="menu-item"><i class="fa-solid fa-universal-access"></i> Accesibilidad</a>
                
                <div class="menu-spacer"></div>
                <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
            </nav>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content">
            
            <!-- ENCABEZADO -->
            <header class="content-header">
                <div class="welcome-text">
                    <h1>Crear curso</h1>
                    <p>Comparte material con tus estudiantes</p>
                </div>
                <div class="header-actions">
                    <button class="btn-assistant" id="btn-asistente">Asistente Virtual <span class="robot-icon">🤖</span></button>
                    <div class="icon-bell-container">
                        <i class="fa-regular fa-bell"></i>
                    </div>
                    <div class="user-profile">
                        <img src="https://placehold.co/40x40/ff7675/white?text=👨" alt="Avatar Docente" class="avatar">
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($nombre_docente); ?></span>
                            <span class="user-role">Docente</span>
                        </div>
                        <i class="fa-solid fa-chevron-down drop-icon"></i>
                    </div>
                </div>
            </header>

            <!-- ========================================== -->
            <!-- MENSAJES DE ÉXITO O ERROR                  -->
            <!-- ========================================== -->
            <?php if ($mensaje): ?>
                <div class="mensaje-container" style="margin-bottom: 20px;">
                    <div class="mensaje <?php echo $tipo_mensaje; ?>" style="padding: 15px 20px; border-radius: 8px; font-weight: 500;">
                        <?php echo $mensaje; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="main-grid">
                
                <!-- COLUMNA IZQUIERDA -->
                <div class="left-column">
                    
                    <!-- 1. Tipo de curso -->
                    <section class="section-container">
                        <h3 class="section-title">Tipo de curso</h3>
                        <div class="course-types-grid">
                            <button type="button" class="type-card" onclick="document.getElementById('tipo_curso').value='Video'">
                                <div class="type-icon text-purple"><i class="fa-solid fa-play"></i></div>
                                <h4>Video</h4>
                                <p>Sube un video</p>
                            </button>
                            <button type="button" class="type-card" onclick="document.getElementById('tipo_curso').value='PDF'">
                                <div class="type-icon text-red"><i class="fa-regular fa-file-pdf"></i></div>
                                <h4>PDF</h4>
                                <p>Sube un archivo PDF</p>
                            </button>
                            <button type="button" class="type-card" onclick="document.getElementById('tipo_curso').value='Documento'">
                                <div class="type-icon text-blue"><i class="fa-regular fa-file-lines"></i></div>
                                <h4>Documento</h4>
                                <p>Sube un documento</p>
                            </button>
                        </div>
                        <input type="hidden" id="tipo_curso" name="tipo_curso" value="Documento">
                        <input type="hidden" id="id_recurso" name="id_recurso" value="">
                    </section>

                    <!-- 2. Información del curso -->
                    <section class="section-container border-container">
                        <h3 class="section-title">Información del curso</h3>
                        
                        <form class="course-form" method="POST" action="" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Título del curso <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" placeholder="Ej. La fotosíntesis" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Descripción <span class="text-muted">(opcional)</span></label>
                                <input type="text" name="descripcion" placeholder="Describe brevemente el contenido" value="<?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label>Seleccionar materia <span class="text-danger">*</span></label>
                                <select name="id_materia" required>
                                    <option value="">Elige una materia</option>
                                    <?php foreach ($materias as $materia): ?>
                                        <option value="<?php echo $materia['id_materia']; ?>" <?php echo (($_POST['id_materia'] ?? '') == $materia['id_materia']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($materia['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Seleccionar grupo <span class="text-danger">*</span></label>
                                <select name="id_grupo" required>
                                    <option value="">Elige un grupo</option>
                                    <?php foreach ($grupos as $grupo): ?>
                                        <option value="<?php echo $grupo['id_grupo']; ?>" <?php echo (($_POST['id_grupo'] ?? '') == $grupo['id_grupo']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($grupo['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Ciclo escolar <span class="text-danger">*</span></label>
                                <select name="id_ciclo" required>
                                    <option value="">Elige un ciclo</option>
                                    <?php foreach ($ciclos as $ciclo): ?>
                                        <option value="<?php echo $ciclo['id_ciclo']; ?>" <?php echo (($_POST['id_ciclo'] ?? '') == $ciclo['id_ciclo']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ciclo['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Estado</label>
                                <select name="estado">
                                    <option value="Activo" <?php echo (($_POST['estado'] ?? '') == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                                    <option value="Inactivo" <?php echo (($_POST['estado'] ?? '') == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            </div>

                            <div class="upload-area" onclick="document.getElementById('archivo').click();" style="cursor: pointer;">
    <i class="fa-solid fa-cloud-arrow-up"></i>
    <p>Toca para seleccionar o arrastrar tu archivo aquí</p>
    <input type="file" name="archivo" id="archivo" style="display: none;" accept=".pdf,.mp4,.doc,.docx,.ppt,.pptx,.txt,.jpg,.png">
    <p style="font-size: 12px; color: #6b7280; margin-top: 5px;">Formatos permitidos: PDF, MP4, DOC, DOCX, PPT, PPTX, TXT, JPG, PNG</p>
</div>

                            <div class="form-actions-row">
                                <a href="docente_dashboard.php" class="btn-cancelar">Cancelar</a>
                                <button type="submit" class="btn-publicar">Publicar curso</button>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- COLUMNA DERECHA -->
                <div class="right-column">
                    <aside class="calendar-widget border-container">
                        <div class="calendar-header">
                            <h3 class="section-title">Calendario</h3>
                            <a href="#" class="link-blue">Ver calendario <i class="fa-regular fa-calendar-days"></i></a>
                        </div>
                        <div class="calendar-month">
                            <span class="month-title">Mayo 2024</span>
                            <div class="calendar-nav">
                                <i class="fa-solid fa-chevron-left"></i>
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                        </div>
                        <div class="calendar-grid">
                            <div class="cal-day-header">LUN</div><div class="cal-day-header">MAR</div><div class="cal-day-header">MIE</div><div class="cal-day-header">JUE</div><div class="cal-day-header">VIE</div><div class="cal-day-header">SAB</div><div class="cal-day-header">DOM</div>
                            <div class="cal-day disabled">29</div><div class="cal-day disabled">30</div><div class="cal-day dot">1</div><div class="cal-day">2</div><div class="cal-day">3</div><div class="cal-day">4</div><div class="cal-day">5</div>
                            <div class="cal-day">6</div><div class="cal-day">7</div><div class="cal-day">8</div><div class="cal-day dot">9</div><div class="cal-day">10</div><div class="cal-day">11</div><div class="cal-day">12</div>
                            <div class="cal-day">13</div><div class="cal-day">14</div><div class="cal-day">15</div><div class="cal-day dot">16</div><div class="cal-day">17</div><div class="cal-day">18</div><div class="cal-day">19</div>
                            <div class="cal-day active">20</div><div class="cal-day dot">21</div><div class="cal-day">22</div><div class="cal-day">23</div><div class="cal-day double-dot">24</div><div class="cal-day">25</div><div class="cal-day">26</div>
                            <div class="cal-day">27</div><div class="cal-day">28</div><div class="cal-day">29</div><div class="cal-day">30</div><div class="cal-day">31</div><div class="cal-day disabled">1</div><div class="cal-day disabled">2</div>
                        </div>
                    </aside>
                </div>
            </div>

            <!-- BARRA ACCESIBILIDAD -->
            <footer class="accessibility-bar">
                <div class="acc-info">
                    <div class="acc-icon-box">
                        <i class="fa-solid fa-universal-access acc-icon-main"></i>
                    </div>
                    <div>
                        <strong>Accesibilidad siempre disponible</strong>
                        <p>Personaliza tu experiencia en cualquier momento.</p>
                    </div>
                </div>
                <div class="acc-options">
                    <button class="acc-opt-btn" id="btn-contrast"><i class="fa-solid fa-eye"></i><span>Alto contraste</span></button>
                    <button class="acc-opt-btn" id="btn-darkmode"><i class="fa-solid fa-moon"></i><span>Modo oscuro</span></button>
                    <button class="acc-opt-btn" id="btn-text-size"><span class="font-icon">Aa</span><span>Texto grande</span></button>
                    <button class="acc-opt-btn"><i class="fa-solid fa-volume-high"></i><span>Leer pantalla</span></button>
                    <button class="acc-opt-btn"><i class="fa-solid fa-closed-captioning"></i><span>Subtítulos</span></button>
                    <button class="acc-opt-btn"><i class="fa-solid fa-keyboard"></i><span>Navegación<br>por teclado</span></button>
                </div>
                <button class="btn-open-config">Abrir configuración</button>
            </footer>

        </main>
    </div>

    <script src="jss/docente_dashboard.js"></script>
    <!-- ✅ AGREGAR AQUÍ EL CÓDIGO DE SUBIDA DE ARCHIVOS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.querySelector('.upload-area');
    const fileInput = document.getElementById('archivo');
    const uploadText = uploadArea.querySelector('p');
    let archivoSeleccionado = null;

    // --- Seleccionar archivo con clic ---
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            archivoSeleccionado = this.files[0];
            uploadText.textContent = '📎 ' + archivoSeleccionado.name;
            uploadText.style.color = '#16a34a';
            uploadText.style.fontWeight = '500';
            subirArchivo(archivoSeleccionado);
        } else {
            uploadText.textContent = 'Toca para seleccionar o arrastrar tu archivo aquí';
            uploadText.style.color = '';
            uploadText.style.fontWeight = '';
        }
    });

    // --- Arrastrar archivo ---
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#5a189a';
        this.style.background = '#f3e8ff';
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '';
        this.style.background = '';
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '';
        this.style.background = '';

        if (e.dataTransfer.files.length > 0) {
            archivoSeleccionado = e.dataTransfer.files[0];
            fileInput.files = e.dataTransfer.files;
            uploadText.textContent = '📎 ' + archivoSeleccionado.name;
            uploadText.style.color = '#16a34a';
            uploadText.style.fontWeight = '500';
            subirArchivo(archivoSeleccionado);
        }
    });

    // --- Función para subir archivo con AJAX ---
    function subirArchivo(archivo) {
        const formData = new FormData();
        formData.append('archivo', archivo);

        const titulo = document.querySelector('input[name="nombre"]').value || archivo.name;
        const descripcion = document.querySelector('input[name="descripcion"]').value || '';
        const id_materia = document.querySelector('select[name="id_materia"]').value || '';
        const tipo_curso = document.getElementById('tipo_curso').value || 'Documento';

        formData.append('titulo', titulo);
        formData.append('descripcion', descripcion);
        formData.append('id_materia', id_materia);
        formData.append('tipo_curso', tipo_curso);

        uploadText.textContent = '⏳ Subiendo...';
        uploadText.style.color = '#f59e0b';

        fetch('subir_archivo.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                uploadText.textContent = '✅ ' + data.nombre + ' (subido)';
                uploadText.style.color = '#16a34a';
                if (document.getElementById('id_recurso')) {
                    document.getElementById('id_recurso').value = data.id_recurso;
                }
            } else {
                uploadText.textContent = '❌ Error: ' + data.error;
                uploadText.style.color = '#dc2626';
            }
        })
        .catch(error => {
            uploadText.textContent = '❌ Error al subir el archivo';
            uploadText.style.color = '#dc2626';
            console.error('Error:', error);
        });
    }
});
</script>
</body>
</html>
