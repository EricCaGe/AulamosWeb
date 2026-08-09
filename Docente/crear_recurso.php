<?php
session_start();

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

    $nombre =
        trim($_POST['nombre'] ?? '');

    $descripcion =
        trim($_POST['descripcion'] ?? '');

    $id_curso =
        (int) ($_POST['id_curso'] ?? 0);

    $id_recurso =
        (int) ($_POST['id_recurso'] ?? 0);

    $tipo_recurso =
        trim($_POST['tipo_curso'] ?? 'Documento');

    $estado =
        $_POST['estado'] ?? 'Activo';

    $tipos_permitidos = [
        'Video',
        'PDF',
        'Documento'
    ];

    $estados_permitidos = [
        'Activo',
        'Inactivo'
    ];

    if (
        empty($nombre) ||
        $id_curso <= 0 ||
        $id_recurso <= 0
    ) {

        $mensaje =
            'Completa el título, selecciona un curso y sube un archivo.';

        $tipo_mensaje = 'error';

    } elseif (
        !in_array(
            $tipo_recurso,
            $tipos_permitidos,
            true
        )
    ) {

        $mensaje =
            'El tipo de recurso seleccionado no es válido.';

        $tipo_mensaje = 'error';

    } elseif (
        !in_array(
            $estado,
            $estados_permitidos,
            true
        )
    ) {

        $mensaje =
            'El estado seleccionado no es válido.';

        $tipo_mensaje = 'error';

    } else {

        try {

            // ==========================================
            // COMPROBAR QUE EL CURSO SEA DEL DOCENTE
            // ==========================================

            $stmt = $conexion->prepare("
                SELECT
                    id_curso,
                    id_materia
                FROM cursos
                WHERE id_curso = ?
                  AND id_docente = ?
                  AND estado = 'Activo'
                LIMIT 1
            ");

            $stmt->bind_param(
                "ii",
                $id_curso,
                $id_docente
            );

            $stmt->execute();

            $curso_seleccionado =
                $stmt
                    ->get_result()
                    ->fetch_assoc();

            $stmt->close();

            if (!$curso_seleccionado) {

                $mensaje =
                    'El curso seleccionado no existe o no pertenece al docente.';

                $tipo_mensaje = 'error';

            } else {

                $id_materia =
                    (int) $curso_seleccionado['id_materia'];

                // ==========================================
                // ACTUALIZAR EL RECURSO YA SUBIDO
                // ==========================================

                $stmt = $conexion->prepare("
                    UPDATE recursos_educativos
                    SET
                        titulo = ?,
                        descripcion = ?,
                        tipo = ?,
                        id_materia = ?,
                        id_curso = ?,
                        id_actividad = NULL,
                        compartido_tipo = 'Curso',
                        estado = ?
                    WHERE id_recurso = ?
                      AND id_docente = ?
                ");

                $stmt->bind_param(
                    "sssiisii",
                    $nombre,
                    $descripcion,
                    $tipo_recurso,
                    $id_materia,
                    $id_curso,
                    $estado,
                    $id_recurso,
                    $id_docente
                );

                $stmt->execute();
                $stmt->close();

                $mensaje =
                    'Recurso "' .
                    htmlspecialchars(
                        $nombre,
                        ENT_QUOTES,
                        'UTF-8'
                    ) .
                    '" publicado correctamente.';

                $tipo_mensaje = 'exito';

                $_POST = [];
            }

        } catch (Exception $e) {

            $mensaje =
                'Error al publicar el recurso: ' .
                $e->getMessage();

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
    <title>Crear Recurso - Aulamos</title>
    
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="styles/curso.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluación</a>
                <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                <a href="pasarlista.php" class="menu-item"><i class="fa-solid fa-bars"></i> Pasar Lista</a>
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
                    <h1>Crear recurso</h1>
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
                        <h3 class="section-title">Tipo de recurso</h3>
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
</section>

                    <!-- 2. Información del recurso -->
                    <section class="section-container border-container">
                        <h3 class="section-title">Información del recurso</h3>
                        
                        <form class="course-form" method="POST" action="" enctype="multipart/form-data">

                            <input type="hidden" id="tipo_curso" name="tipo_curso" value="Documento">
                            <input type="hidden" id="id_recurso" name="id_recurso" value="">
                            <div class="form-group">
                                <label>Título del recurso <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" placeholder="Ej. La fotosíntesis" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Descripción <span class="text-muted">(opcional)</span></label>
                                <input type="text" name="descripcion" placeholder="Describe brevemente el contenido" value="<?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>
                                    Seleccionar curso
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="id_curso" required>
                                    <option value="">
                                        Elige un curso existente
                                    </option>

                                    <?php foreach ($cursos as $curso): ?>
                                        <option
                                            value="<?php echo (int) $curso['id_curso']; ?>"
                                            <?php
                                            echo (
                                                ($_POST['id_curso'] ?? '') ==
                                                $curso['id_curso']
                                            )
                                                ? 'selected'
                                                : '';
                                            ?>
                                        >
                                            <?php
                                            echo htmlspecialchars(
                                                $curso['nombre'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>

                                            ·

                                            <?php
                                            echo htmlspecialchars(
                                                $curso['materia'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>

                                            · Grupo

                                            <?php
                                            echo htmlspecialchars(
                                                $curso['grupo'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>

                                            ·

                                            <?php
                                            echo (int) $curso['alumnos_activos'];
                                            ?>

                                            alumno(s)
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <?php if (count($cursos) === 0): ?>
                                    <p style="color:#dc2626; font-size:13px; margin-top:6px;">
                                        No tienes cursos activos disponibles.
                                    </p>
                                <?php endif; ?>
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
                               <button type="submit" class="btn-publicar">Publicar recurso</button>
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
        let recursoSubido = false;

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

        // --- Función para subir archivo con AJAX (sin validar formulario) ---
        function subirArchivo(archivo) {
            const formData = new FormData();
            formData.append('archivo', archivo);

            const titulo = document.querySelector('input[name="nombre"]').value || archivo.name;
            const descripcion = document.querySelector('input[name="descripcion"]').value || '';
            const tipo_curso = document.getElementById('tipo_curso').value || 'Documento';

            formData.append('titulo', titulo);
            formData.append('descripcion', descripcion);
            formData.append('tipo_curso', tipo_curso);

            uploadText.textContent = '⏳ Subiendo archivo...';
            uploadText.style.color = '#f59e0b';

            fetch('subir_archivo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    recursoSubido = true;
                    uploadText.textContent = '✅ ' + data.nombre + ' (subido)';
                    uploadText.style.color = '#16a34a';
                    if (document.getElementById('id_recurso')) {
                        document.getElementById('id_recurso').value = data.id_recurso;
                    }
                    const mensaje = document.querySelector('.mensaje-container');
                    if (mensaje) {
                        mensaje.innerHTML = '<div class="mensaje exito" style="padding: 15px 20px; border-radius: 8px; font-weight: 500; background: #dcfce7; color: #166534; border-left: 4px solid #22c55e;">✅ Archivo "' + data.nombre + '" subido correctamente. Ahora completa el formulario y publica el curso.</div>';
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