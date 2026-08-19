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

// Obtener datos del docente
$id_docente = $_SESSION['usuario']['id_usuario'];
$nombre_docente = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];

// Consultar materias del docente
$query_materias = "
    SELECT DISTINCT m.id_materia, m.nombre
    FROM cursos c
    JOIN materias m ON c.id_materia = m.id_materia
    WHERE c.id_docente = ? AND c.estado = 'Activo'
    ORDER BY m.nombre
";
$stmt_materias = $conexion->prepare($query_materias);
$stmt_materias->bind_param("i", $id_docente);
$stmt_materias->execute();
$result_materias = $stmt_materias->get_result();

$materias = [];
while ($row = $result_materias->fetch_assoc()) {
    $materias[] = $row;
}

// Consultar cursos del docente
$query_cursos = "
    SELECT c.id_curso, c.nombre
    FROM cursos c
    WHERE c.id_docente = ? AND c.estado = 'Activo'
    ORDER BY c.nombre
";
$stmt_cursos = $conexion->prepare($query_cursos);
$stmt_cursos->bind_param("i", $id_docente);
$stmt_cursos->execute();
$result_cursos = $stmt_cursos->get_result();

$cursos = [];
while ($row = $result_cursos->fetch_assoc()) {
    $cursos[] = $row;
}

// Consultar periodos de evaluación
$query_periodos = "
    SELECT p.id_periodo, p.nombre
    FROM periodos_evaluacion p
    JOIN ciclos_escolares c ON p.id_ciclo = c.id_ciclo
    WHERE c.estado = 'Activo' AND p.estado = 'Activo'
    ORDER BY p.fecha_inicio
";
$stmt_periodos = $conexion->prepare($query_periodos);
$stmt_periodos->execute();
$result_periodos = $stmt_periodos->get_result();

$periodos = [];
while ($row = $result_periodos->fetch_assoc()) {
    $periodos[] = $row;
}

// Cerrar conexiones
$stmt_materias->close();
$stmt_cursos->close();
$stmt_periodos->close();
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Evaluación - Aulamos</title>
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="styles/evaluacion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- NUEVA ACCESIBILIDAD -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    
    <style>
        /* Estilos para los botones de tipo de respuesta */
        .tipo-respuesta-container {
            display: flex;
            gap: 10px;
            margin: 10px 0;
            flex-wrap: wrap;
        }
        .tipo-respuesta-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        .tipo-respuesta-btn.selected {
            border-color: #4f7cff;
            background: #f0f4ff;
        }
        .tipo-respuesta-btn input {
            display: none;
        }
        .tipo-respuesta-btn i {
            font-size: 16px;
        }
        .tipo-respuesta-btn span {
            font-size: 14px;
            font-weight: 500;
        }

        /* Estilos para la sección de configuración */
        .config-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        .config-section h3 {
            margin-top: 0;
            color: #333;
            font-size: 18px;
        }
        .config-section p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        /* Estilos para puntos y obligatoria */
        .pregunta-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            flex-wrap: wrap;
            gap: 10px;
        }
        .pregunta-meta .meta-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .pregunta-meta label {
            font-size: 14px;
            font-weight: 500;
        }
        .pregunta-meta input[type="number"] {
            width: 60px;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-align: center;
        }
        .obligatoria-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .obligatoria-toggle .switch {
            width: 40px;
            height: 20px;
            background: #ccc;
            border-radius: 20px;
            position: relative;
            cursor: pointer;
            transition: background 0.3s;
        }
        .obligatoria-toggle .switch.active {
            background: #4f7cff;
        }
        .obligatoria-toggle .switch::after {
            content: '';
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            position: absolute;
            top: 2px;
            left: 2px;
            transition: left 0.3s;
        }
        .obligatoria-toggle .switch.active::after {
            left: 22px;
        }
        .obligatoria-toggle input[type="checkbox"] {
            display: none;
        }

        /* Estilo para opciones de respuesta */
        .opcion-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }
        .opcion-radio {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .custom-radio {
            width: 20px;
            height: 20px;
            border: 2px solid #d9d9d9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        .opcion-radio input:checked + .custom-radio {
            border-color: #4f7cff;
            background: #f0f4ff;
        }
        .opcion-radio input:checked + .custom-radio::after {
            content: '✓';
            color: #4f7cff;
            font-size: 12px;
        }
        .opcion-item input[type="text"] {
            flex: 1;
            padding: 10px;
            border: 1px solid #d9d9d9;
            border-radius: 8px;
        }

        /* Estilo para el contenedor de opciones */
        .opciones-container {
            margin-top: 15px;
        }

        /* Estilo para el textarea de respuesta abierta */
        .respuesta-abierta-container {
            margin-top: 15px;
        }
        .respuesta-abierta-container textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            min-height: 80px;
            resize: vertical;
        }

        /* Estilo para el botón de agregar opción */
        .btn-add-opcion {
            background: none;
            border: 1px dashed #4f7cff;
            color: #4f7cff;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }
        .btn-add-opcion i {
            margin-right: 5px;
        }

        /* Estilo para el botón de eliminar pregunta */
        .btn-remove-pregunta {
            background: none;
            border: none;
            color: #ff4d4f;
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            transition: all 0.2s;
        }
        .btn-remove-pregunta:hover {
            background: #ffebee;
            color: #ff0000;
        }

        /* Estilo para el botón de agregar pregunta */
        .btn-add-pregunta {
            width: 100%;
            padding: 12px;
            border: 2px dashed #4f7cff;
            background: white;
            color: #4f7cff;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 20px;
        }
        .btn-add-pregunta:hover {
            background: #f0f4ff;
        }

        /* Estilo para el botón de crear evaluación */
        .btn-outline-blue:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* NUEVO: Contenedor de dos columnas */
        .two-column-container {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }
        .left-column {
            flex: 2;
            min-width: 0;
        }
        .right-column {
            flex: 1;
            min-width: 300px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Estilos para el calendario */
        .calendar-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .calendar-header h2 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        .nav-btn {
            background: none;
            border: none;
            font-size: 18px;
            color: #666;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .nav-btn:hover {
            background: #f0f0f0;
        }
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            margin-bottom: 8px;
        }
        .weekday {
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            padding: 5px 0;
        }
        .calendar-days-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }
        .calendar-day {
            text-align: center;
            padding: 8px 0;
            border-radius: 6px;
            font-size: 14px;
            color: #333;
            cursor: pointer;
            transition: all 0.2s;
        }
        .calendar-day:hover {
            background: #f0f4ff;
        }
        .calendar-day.empty {
            cursor: default;
            color: #ccc;
        }
        .calendar-day.today {
            background: #4f7cff;
            color: white;
            font-weight: 600;
        }
        .calendar-day.selected {
            background: #e8edff;
            border: 2px solid #4f7cff;
        }
        .calendar-day.has-event {
            position: relative;
        }
        .calendar-day.has-event::after {
            content: '';
            width: 6px;
            height: 6px;
            background: #4f7cff;
            border-radius: 50%;
            position: absolute;
            bottom: 4px;
            left: 50%;
            transform: translateX(-50%);
        }

        /* Estilos para los botones de acción */
        .eval-buttons {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }
        .eval-buttons button {
            flex: 1;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        .btn-outline-gray {
            background: #f5f5f5;
            color: #666;
            border: 1px solid #ddd;
        }
        .btn-outline-gray:hover {
            background: #eee;
        }
        .btn-outline-blue {
            background: #4f7cff;
            color: white;
        }
        .btn-outline-blue:hover:not(:disabled) {
            background: #3a6beb;
        }
        .btn-outline-blue:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Mensajes de alerta */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
        }

        .form-group-clean {
            margin-bottom: 20px;
        }
        .form-group-clean label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }
        .required-star {
            color: #e74c3c;
        }
        .clean-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .clean-input:focus {
            outline: none;
            border-color: #4f7cff;
            box-shadow: 0 0 0 3px rgba(79, 124, 255, 0.1);
        }
        .clean-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            font-size: 14px;
            resize: vertical;
            transition: border-color 0.3s;
        }
        .clean-textarea:focus {
            outline: none;
            border-color: #4f7cff;
            box-shadow: 0 0 0 3px rgba(79, 124, 255, 0.1);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .two-column-container {
                flex-direction: column;
            }
            .right-column {
                min-width: unset;
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
            <a href="mis_recursos.php" class="menu-item"><i class="fa-solid fa-folder-open"></i> Mis Recursos</a>
            <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
            <a href="crear_evaluacion.php" class="menu-item active"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
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
        <!-- ENCABEZADO CON FOTO DE PERFIL -->
        <?php
        // Obtener foto de perfil del docente
        $foto_perfil_docente = $_SESSION['usuario']['foto_perfil'] ?? null;
        $ruta_foto_docente = !empty($foto_perfil_docente) ? '../uploads/perfiles/' . $foto_perfil_docente : 'https://placehold.co/40x40/ff7675/white?text=👨';
        ?>
        <header class="content-header">
            <div class="welcome-text">
                <h1>Crear evaluación 👋</h1>
                <p>Diseña tus propias evaluaciones</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-assistant" id="btn-asistente" onclick="window.location.href='../Alumno/ChatbotDocente.php?rol=docente'">
                    Asistente Virtual <span class="robot-icon">🤖</span>
                </button>
                <div class="icon-bell-container">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <a href="mi_perfil_d.php" class="user-profile" style="text-decoration:none; cursor:pointer; display:flex; align-items:center; gap:10px; padding:5px 12px 5px 5px; border-radius:50px; background:#f1f5f9; transition:background 0.2s;">
                    <img src="<?php echo $ruta_foto_docente; ?>" alt="Avatar Docente" class="avatar" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid white;">
                    <div class="user-info" style="display:flex; flex-direction:column; line-height:1.2;">
                        <span class="user-name" style="font-weight:600; font-size:14px; color:#1e293b;"><?php echo htmlspecialchars($nombre_docente); ?></span>
                        <span class="user-role" style="font-size:11px; color:#64748b;">Docente</span>
                    </div>
                </a>
            </div>
        </header>

        <!-- ========================================== -->
        <!-- MENSAJES DE ÉXITO O ERROR                  -->
        <!-- ========================================== -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensaje'] ?? 'success'; ?>">
                <i class="fa-solid <?php 
                    $tipo = $_SESSION['tipo_mensaje'] ?? 'success';
                    if ($tipo === 'success') echo 'fa-check-circle';
                    elseif ($tipo === 'error') echo 'fa-exclamation-circle';
                    else echo 'fa-info-circle';
                ?>"></i>
                <?php echo $_SESSION['mensaje']; ?>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
            <?php unset($_SESSION['tipo_mensaje']); ?>
        <?php endif; ?>

        <!-- CONTENEDOR DE DOS COLUMNAS -->
        <div class="two-column-container">
            <!-- COLUMNA IZQUIERDA: FORMULARIO -->
            <div class="left-column">
                <form id="evalForm" action="guardar_evaluacion.php" method="POST">
                    <!-- Título -->
                    <div class="form-group-clean">
                        <label for="titulo">Título <span class="required-star">*</span></label>
                        <input type="text" class="clean-input" id="titulo" name="titulo" placeholder="Escribe el título de la evaluación" required>
                        <div class="error-message" id="error-titulo"></div>
                    </div>

                    <!-- Descripción -->
                    <div class="form-group-clean">
                        <label for="descripcion">Descripción <span class="required-star">*</span></label>
                        <textarea class="clean-textarea" id="descripcion" name="descripcion" placeholder="Describe el objetivo de la evaluación" rows="3" required></textarea>
                        <div class="error-message" id="error-descripcion"></div>
                    </div>

                    <!-- Seleccionar materia -->
                    <div class="form-group-clean">
                        <label for="id_materia">Seleccionar materia <span class="required-star">*</span></label>
                        <select class="clean-input" id="id_materia" name="id_materia" required>
                            <option value="" disabled selected>Seleccione una materia</option>
                            <?php foreach ($materias as $materia): ?>
                                <option value="<?php echo $materia['id_materia']; ?>">
                                    <?php echo htmlspecialchars($materia['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error-message" id="error-materia"></div>
                    </div>

                    <!-- Seleccionar curso -->
                    <div class="form-group-clean">
                        <label for="id_curso">Seleccionar curso <span class="required-star">*</span></label>
                        <select class="clean-input" id="id_curso" name="id_curso" required>
                            <option value="" disabled selected>Seleccione un curso</option>
                            <?php foreach ($cursos as $curso): ?>
                                <option value="<?php echo $curso['id_curso']; ?>">
                                    <?php echo htmlspecialchars($curso['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error-message" id="error-curso"></div>
                    </div>

                    <!-- Periodo de evaluación -->
                    <div class="form-group-clean">
                        <label for="id_periodo">Periodo de evaluación</label>
                        <select class="clean-input" id="id_periodo" name="id_periodo">
                            <option value="">Ninguno</option>
                            <?php foreach ($periodos as $periodo): ?>
                                <option value="<?php echo $periodo['id_periodo']; ?>">
                                    <?php echo htmlspecialchars($periodo['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- SECCIÓN DE PREGUNTAS -->
                    <div id="cuestionario-section" class="dynamic-section">
                        <h3>Preguntas</h3>
                        <p style="color: #666; font-size: 14px; margin-top: 5px;">Crea preguntas y publicarlas</p>

                        <div id="preguntas-container">
                            <!-- Pregunta inicial -->
                            <div class="pregunta-item" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <label style="font-weight: 600; font-size: 16px;">Pregunta 1</label>
                                    <button type="button" class="btn-remove-pregunta">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                                <textarea class="clean-textarea" name="preguntas[0][texto]" placeholder="Escribe la pregunta" rows="2" required></textarea>

                                <div style="margin-top: 15px;">
                                    <label style="display: block; font-weight: 500; margin-bottom: 8px;">Tipo de respuesta</label>
                                    <div class="tipo-respuesta-container">
                                        <label class="tipo-respuesta-btn selected" data-tipo="OpcionMultiple">
                                            <input type="radio" name="preguntas[0][tipo]" value="OpcionMultiple" checked>
                                            <i class="fa-solid fa-circle"></i>
                                            <span>Opción</span>
                                        </label>
                                        <label class="tipo-respuesta-btn" data-tipo="VerdaderoFalso">
                                            <input type="radio" name="preguntas[0][tipo]" value="VerdaderoFalso">
                                            <i class="fa-solid fa-check-circle"></i>
                                            <span>V/F</span>
                                        </label>
                                        <label class="tipo-respuesta-btn" data-tipo="Abierta">
                                            <input type="radio" name="preguntas[0][tipo]" value="Abierta">
                                            <i class="fa-solid fa-align-left"></i>
                                            <span>Abierta</span>
                                        </label>
                                    </div>
                                </div>

                                <div style="margin-top: 15px; color: #666; font-size: 14px;">Toca el círculo para marcar la respuesta correcta</div>

                                <!-- Opciones para pregunta de opción múltiple o verdadero/falso -->
                                <div class="opciones-container" data-pregunta-index="0">
                                    <div class="opcion-item">
                                        <label class="opcion-radio">
                                            <input type="radio" name="preguntas[0][respuesta_correcta]" value="0" checked>
                                            <span class="custom-radio"></span>
                                        </label>
                                        <input type="text" class="clean-input" name="preguntas[0][opciones][]" placeholder="Opción 1" required>
                                    </div>
                                    <div class="opcion-item">
                                        <label class="opcion-radio">
                                            <input type="radio" name="preguntas[0][respuesta_correcta]" value="1">
                                            <span class="custom-radio"></span>
                                        </label>
                                        <input type="text" class="clean-input" name="preguntas[0][opciones][]" placeholder="Opción 2" required>
                                    </div>
                                </div>
                                <button type="button" class="btn-add-opcion">
                                    <i class="fa-solid fa-plus"></i> Agregar opción
                                </button>

                                <!-- Campo para respuesta abierta (oculto por defecto) -->
                                <div class="respuesta-abierta-container" style="display: none;">
                                    <textarea class="clean-textarea" name="preguntas[0][respuesta_modelo]" placeholder="Escribe aquí la respuesta modelo o indicaciones para el estudiante (opcional)" rows="2"></textarea>
                                </div>

                                <div class="pregunta-meta">
                                    <div class="meta-group">
                                        <label>Puntos</label>
                                        <input type="number" name="preguntas[0][puntaje]" value="1" min="0" step="0.01" required>
                                    </div>
                                    <div class="meta-group obligatoria-toggle">
                                        <label>Obligatoria</label>
                                        <label class="switch">
                                            <input type="checkbox" name="preguntas[0][obligatoria]" checked value="1">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn-add-pregunta" id="btnAddPregunta">
                            <i class="fa-solid fa-plus"></i> Agregar pregunta
                        </button>
                    </div>

                    <!-- SECCIÓN DE CONFIGURACIÓN -->
                    <div class="config-section">
                        <h3>Configuración</h3>
                        <p>Fecha, duración e intentos</p>

                        <div class="form-group-clean">
                            <label for="fecha_limite">Fecha y hora límite <span class="required-star">*</span></label>
                            <input type="datetime-local" class="clean-input" id="fecha_limite" name="fecha_limite" required>
                        </div>

                        <div class="form-group-clean">
                            <label for="duracion_minutos">Duración (minutos)</label>
                            <input type="number" class="clean-input" id="duracion_minutos" name="duracion_minutos" value="30" min="1">
                        </div>

                        <div class="form-group-clean">
                            <label for="intentos_permitidos">Intentos permitidos</label>
                            <input type="number" class="clean-input" id="intentos_permitidos" name="intentos_permitidos" value="1" min="1">
                        </div>
                    </div>

                    <!-- Puntaje máximo -->
                    <div class="form-group-clean">
                        <label for="puntaje_maximo">Puntaje máximo <span class="required-star">*</span></label>
                        <input type="number" class="clean-input" id="puntaje_maximo" name="puntaje_maximo" value="100.00" min="0" max="1000" step="0.01" required>
                        <div class="error-message" id="error-puntaje"></div>
                    </div>

                    <!-- Campo oculto para el tipo de evaluación (fijo como Cuestionario) -->
                    <input type="hidden" id="hidden-tipo" name="tipo_evaluacion" value="Cuestionario">
                </form>
            </div>

            <!-- COLUMNA DERECHA: CALENDARIO + BOTONES -->
            <div class="right-column">
                <!-- Calendario -->
                <aside class="calendar-container">
                    <div class="calendar-header">
                        <div class="nav-left">
                            <button id="prev-year" class="nav-btn" title="Año anterior">&laquo;</button>
                            <button id="prev-month" class="nav-btn" title="Mes anterior">&lsaquo;</button>
                        </div>
                        <h2 id="month-year-title">AGOSTO 2026</h2>
                        <div class="nav-right">
                            <button id="next-month" class="nav-btn" title="Mes siguiente">&rsaquo;</button>
                            <button id="next-year" class="nav-btn" title="Año siguiente">&raquo;</button>
                        </div>
                    </div>
                    <div class="calendar-weekdays">
                        <div class="weekday">Do</div>
                        <div class="weekday">Lu</div>
                        <div class="weekday">Ma</div>
                        <div class="weekday">Mi</div>
                        <div class="weekday">Ju</div>
                        <div class="weekday">Vi</div>
                        <div class="weekday">Sá</div>
                    </div>
                    <div id="calendar-days" class="calendar-days-grid"></div>
                </aside>

                <!-- BOTONES DE ACCIÓN -->
                <div class="eval-buttons">
                    <button type="button" class="btn-outline-gray" id="btnCancelar">Cancelar</button>
                    <button type="submit" class="btn-outline-blue" id="btnCrearEvaluacion" form="evalForm" disabled>Crear evaluación</button>
                </div>
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

<!-- ========================================== -->
<!-- SCRIPTS                                    -->
<!-- ========================================== -->
<script src="jss/docente_dashboard.js"></script>

<!-- NUEVA ACCESIBILIDAD -->
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>

<!-- JavaScript para validaciones y funcionalidad -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elementos del formulario
        const form = document.getElementById('evalForm');
        const btnCrearEvaluacion = document.getElementById('btnCrearEvaluacion');
        const btnCancelar = document.getElementById('btnCancelar');
        const preguntasContainer = document.getElementById('preguntas-container');

        // Contador de preguntas
        let preguntaCount = 1;

        // Inicializar los botones de tipo de respuesta, switches y botones de agregar opción para todas las preguntas existentes
        document.querySelectorAll('.pregunta-item').forEach((preguntaItem, index) => {
            // Inicializar botones de tipo de respuesta
            const tipoRespuestaBtns = preguntaItem.querySelectorAll('.tipo-respuesta-btn');
            tipoRespuestaBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tipo = this.getAttribute('data-tipo');

                    // Actualizar el radio button seleccionado
                    preguntaItem.querySelectorAll('.tipo-respuesta-btn').forEach(b => b.classList.remove('selected'));
                    this.classList.add('selected');

                    // Actualizar el input hidden del tipo
                    const tipoInput = preguntaItem.querySelector('input[name^="preguntas"][name$="[tipo]"]');
                    if (tipoInput) tipoInput.value = tipo;

                    // Actualizar las opciones según el tipo
                    actualizarOpcionesSegunTipo(preguntaItem, index, tipo);
                });
            });

            // Inicializar switches de obligatoria
            const obligatoriaCheckbox = preguntaItem.querySelector('input[name^="preguntas"][name$="[obligatoria]"]');
            const obligatoriaSwitch = preguntaItem.querySelector('.switch');
            if (obligatoriaCheckbox && obligatoriaSwitch) {
                obligatoriaSwitch.classList.toggle('active', obligatoriaCheckbox.checked);
                obligatoriaCheckbox.addEventListener('change', function() {
                    obligatoriaSwitch.classList.toggle('active', this.checked);
                    validarFormulario();
                });
            }

            // Inicializar botón de agregar opción
            const btnAddOpcion = preguntaItem.querySelector('.btn-add-opcion');
            if (btnAddOpcion) {
                btnAddOpcion.addEventListener('click', function() {
                    agregarOpcion(this);
                });
            }
        });

        // Event listener para eliminar preguntas (usando event delegation)
        preguntasContainer.addEventListener('click', function(e) {
            if (e.target.closest('.btn-remove-pregunta')) {
                const button = e.target.closest('.btn-remove-pregunta');
                const preguntaItem = button.closest('.pregunta-item');
                const preguntas = document.querySelectorAll('.pregunta-item');

                if (preguntas.length > 1) {
                    // Eliminar la pregunta
                    preguntaItem.remove();

                    // Renumerar las preguntas restantes y actualizar los nombres de los inputs
                    document.querySelectorAll('.pregunta-item').forEach((pregunta, newIndex) => {
                        // Actualizar el texto de la pregunta
                        pregunta.querySelector('label').textContent = `Pregunta ${newIndex + 1}`;

                        // Actualizar todos los nombres de los inputs dentro de esta pregunta
                        const inputs = pregunta.querySelectorAll('[name^="preguntas"]');
                        inputs.forEach(input => {
                            const oldName = input.getAttribute('name');
                            // Reemplazar el índice antiguo con el nuevo
                            const newName = oldName.replace(/preguntas\[(\d+)\]/g, `preguntas[${newIndex}]`);
                            input.setAttribute('name', newName);
                        });
                    });

                    // Actualizar el contador
                    preguntaCount = document.querySelectorAll('.pregunta-item').length;

                    // Validar el formulario
                    validarFormulario();
                } else {
                    alert('Debe haber al menos una pregunta.');
                }
            }
        });

        // Función para actualizar las opciones según el tipo de pregunta
        function actualizarOpcionesSegunTipo(preguntaItem, preguntaIndex, tipo) {
            const opcionesContainer = preguntaItem.querySelector('.opciones-container');
            const btnAddOpcion = preguntaItem.querySelector('.btn-add-opcion');
            const respuestaAbiertaContainer = preguntaItem.querySelector('.respuesta-abierta-container');

            // Limpiar el contenedor de opciones
            opcionesContainer.innerHTML = '';

            // Ocultar o mostrar el contenedor de respuesta abierta
            if (respuestaAbiertaContainer) {
                respuestaAbiertaContainer.style.display = (tipo === 'Abierta') ? 'block' : 'none';
            }

            // Agregar opciones según el tipo
            if (tipo === 'VerdaderoFalso') {
                // Opciones para V/F
                opcionesContainer.innerHTML = `
                    <div class="opcion-item">
                        <label class="opcion-radio">
                            <input type="radio" name="preguntas[${preguntaIndex}][respuesta_correcta]" value="0" checked>
                            <span class="custom-radio"></span>
                        </label>
                        <input type="text" class="clean-input" name="preguntas[${preguntaIndex}][opciones][]" value="Verdadero" required>
                    </div>
                    <div class="opcion-item">
                        <label class="opcion-radio">
                            <input type="radio" name="preguntas[${preguntaIndex}][respuesta_correcta]" value="1">
                            <span class="custom-radio"></span>
                        </label>
                        <input type="text" class="clean-input" name="preguntas[${preguntaIndex}][opciones][]" value="Falso" required>
                    </div>
                `;
                // Ocultar el botón de agregar opción para V/F
                if (btnAddOpcion) btnAddOpcion.style.display = 'none';
            } else if (tipo === 'OpcionMultiple') {
                // Opciones para Opción Múltiple (2 por defecto)
                opcionesContainer.innerHTML = `
                    <div class="opcion-item">
                        <label class="opcion-radio">
                            <input type="radio" name="preguntas[${preguntaIndex}][respuesta_correcta]" value="0" checked>
                            <span class="custom-radio"></span>
                        </label>
                        <input type="text" class="clean-input" name="preguntas[${preguntaIndex}][opciones][]" placeholder="Opción 1" required>
                    </div>
                    <div class="opcion-item">
                        <label class="opcion-radio">
                            <input type="radio" name="preguntas[${preguntaIndex}][respuesta_correcta]" value="1">
                            <span class="custom-radio"></span>
                        </label>
                        <input type="text" class="clean-input" name="preguntas[${preguntaIndex}][opciones][]" placeholder="Opción 2" required>
                    </div>
                `;
                // Mostrar el botón de agregar opción para Opción Múltiple
                if (btnAddOpcion) btnAddOpcion.style.display = 'block';
            } else if (tipo === 'Abierta') {
                // No mostrar opciones para preguntas abiertas
                opcionesContainer.innerHTML = '<p style="color: #666; font-size: 14px;">Los estudiantes escribirán su respuesta.</p>';
                // Ocultar el botón de agregar opción para Abierta
                if (btnAddOpcion) btnAddOpcion.style.display = 'none';
            }

            // Validar el formulario después de cambiar el tipo
            validarFormulario();
        }

        // Función para validar el formulario
        function validarFormulario() {
            let isValid = true;

            // Validar campos básicos
            const titulo = document.getElementById('titulo').value.trim();
            const descripcion = document.getElementById('descripcion').value.trim();
            const materia = document.getElementById('id_materia').value;
            const curso = document.getElementById('id_curso').value;
            const puntajeMaximo = document.getElementById('puntaje_maximo').value;
            const fechaLimite = document.getElementById('fecha_limite').value;

            if (!titulo) isValid = false;
            if (!descripcion) isValid = false;
            if (!materia) isValid = false;
            if (!curso) isValid = false;
            if (!puntajeMaximo || parseFloat(puntajeMaximo) <= 0) isValid = false;
            if (!fechaLimite) isValid = false;

            // Validar preguntas
            const preguntas = document.querySelectorAll('.pregunta-item');
            if (preguntas.length === 0) {
                isValid = false;
            } else {
                preguntas.forEach((pregunta, index) => {
                    // Validar texto de la pregunta
                    const textoPregunta = pregunta.querySelector('textarea[name^="preguntas"]');
                    if (!textoPregunta || textoPregunta.value.trim() === '') {
                        isValid = false;
                        return;
                    }

                    // Validar tipo de pregunta
                    const tipoPregunta = pregunta.querySelector('input[name^="preguntas"][name$="[tipo]"]:checked');
                    if (!tipoPregunta) {
                        isValid = false;
                        return;
                    }

                    const tipo = tipoPregunta.value;

                    // Validar opciones según el tipo
                    if (tipo === 'OpcionMultiple' || tipo === 'VerdaderoFalso') {
                        const opciones = pregunta.querySelectorAll('input[name^="preguntas"][name$="[opciones][]"]');
                        if (opciones.length === 0) {
                            isValid = false;
                            return;
                        }

                        let hasEmptyOption = false;
                        opciones.forEach(opcion => {
                            if (opcion.value.trim() === '') {
                                hasEmptyOption = true;
                            }
                        });
                        if (hasEmptyOption) {
                            isValid = false;
                            return;
                        }

                        // Validar respuesta correcta
                        const respuestaCorrecta = pregunta.querySelector('input[name^="preguntas"][name$="[respuesta_correcta]"]:checked');
                        if (!respuestaCorrecta) {
                            isValid = false;
                            return;
                        }
                    }

                    // Validar puntos
                    const puntosInput = pregunta.querySelector('input[name^="preguntas"][name$="[puntaje]"]');
                    if (!puntosInput || parseFloat(puntosInput.value) <= 0) {
                        isValid = false;
                        return;
                    }
                });
            }

            // Habilitar/deshabilitar botón de crear evaluación
            btnCrearEvaluacion.disabled = !isValid;
            return isValid;
        }

        // Función para limpiar el formulario
        function limpiarFormulario() {
            if (confirm("¿Estás seguro de que deseas cancelar la creación de la evaluación? Todos los campos se borrarán.")) {
                form.reset();
                // Restablecer a una pregunta inicial
                preguntasContainer.innerHTML = `
                    <div class="pregunta-item" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <label style="font-weight: 600; font-size: 16px;">Pregunta 1</label>
                            <button type="button" class="btn-remove-pregunta">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                        <textarea class="clean-textarea" name="preguntas[0][texto]" placeholder="Escribe la pregunta" rows="2" required></textarea>

                        <div style="margin-top: 15px;">
                            <label style="display: block; font-weight: 500; margin-bottom: 8px;">Tipo de respuesta</label>
                            <div class="tipo-respuesta-container">
                                <label class="tipo-respuesta-btn selected" data-tipo="OpcionMultiple">
                                    <input type="radio" name="preguntas[0][tipo]" value="OpcionMultiple" checked>
                                    <i class="fa-solid fa-circle"></i>
                                    <span>Opción</span>
                                </label>
                                <label class="tipo-respuesta-btn" data-tipo="VerdaderoFalso">
                                    <input type="radio" name="preguntas[0][tipo]" value="VerdaderoFalso">
                                    <i class="fa-solid fa-check-circle"></i>
                                    <span>V/F</span>
                                </label>
                                <label class="tipo-respuesta-btn" data-tipo="Abierta">
                                    <input type="radio" name="preguntas[0][tipo]" value="Abierta">
                                    <i class="fa-solid fa-align-left"></i>
                                    <span>Abierta</span>
                                </label>
                            </div>
                        </div>

                        <div style="margin-top: 15px; color: #666; font-size: 14px;">Toca el círculo para marcar la respuesta correcta</div>

                        <div class="opciones-container" data-pregunta-index="0">
                            <div class="opcion-item">
                                <label class="opcion-radio">
                                    <input type="radio" name="preguntas[0][respuesta_correcta]" value="0" checked>
                                    <span class="custom-radio"></span>
                                </label>
                                <input type="text" class="clean-input" name="preguntas[0][opciones][]" placeholder="Opción 1" required>
                            </div>
                            <div class="opcion-item">
                                <label class="opcion-radio">
                                    <input type="radio" name="preguntas[0][respuesta_correcta]" value="1">
                                    <span class="custom-radio"></span>
                                </label>
                                <input type="text" class="clean-input" name="preguntas[0][opciones][]" placeholder="Opción 2" required>
                            </div>
                        </div>
                        <button type="button" class="btn-add-opcion">
                            <i class="fa-solid fa-plus"></i> Agregar opción
                        </button>

                        <div class="respuesta-abierta-container" style="display: none;">
                            <textarea class="clean-textarea" name="preguntas[0][respuesta_modelo]" placeholder="Escribe aquí la respuesta modelo o indicaciones para el estudiante (opcional)" rows="2"></textarea>
                        </div>

                        <div class="pregunta-meta">
                            <div class="meta-group">
                                <label>Puntos</label>
                                <input type="number" name="preguntas[0][puntaje]" value="1" min="0" step="0.01" required>
                            </div>
                            <div class="meta-group obligatoria-toggle">
                                <label>Obligatoria</label>
                                <label class="switch">
                                    <input type="checkbox" name="preguntas[0][obligatoria]" checked value="1">
                                </label>
                            </div>
                        </div>
                    </div>
                `;
                preguntaCount = 1;
                btnCrearEvaluacion.disabled = true;

                // Re-inicializar event listeners para la nueva pregunta
                const nuevaPregunta = preguntasContainer.querySelector('.pregunta-item');

                // Inicializar botones de tipo de respuesta
                const tipoRespuestaBtns = nuevaPregunta.querySelectorAll('.tipo-respuesta-btn');
                tipoRespuestaBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const tipo = this.getAttribute('data-tipo');
                        const preguntaIndex = 0;

                        nuevaPregunta.querySelectorAll('.tipo-respuesta-btn').forEach(b => b.classList.remove('selected'));
                        this.classList.add('selected');

                        const tipoInput = nuevaPregunta.querySelector('input[name^="preguntas"][name$="[tipo]"]');
                        if (tipoInput) tipoInput.value = tipo;

                        actualizarOpcionesSegunTipo(nuevaPregunta, preguntaIndex, tipo);
                    });
                });

                // Inicializar switch de obligatoria
                const obligatoriaCheckbox = nuevaPregunta.querySelector('input[name^="preguntas"][name$="[obligatoria]"]');
                const obligatoriaSwitch = nuevaPregunta.querySelector('.switch');
                if (obligatoriaCheckbox && obligatoriaSwitch) {
                    obligatoriaSwitch.classList.toggle('active', obligatoriaCheckbox.checked);
                    obligatoriaCheckbox.addEventListener('change', function() {
                        obligatoriaSwitch.classList.toggle('active', this.checked);
                        validarFormulario();
                    });
                }

                // Inicializar botón de agregar opción
                const btnAddOpcion = nuevaPregunta.querySelector('.btn-add-opcion');
                if (btnAddOpcion) {
                    btnAddOpcion.addEventListener('click', function() {
                        agregarOpcion(this);
                    });
                }
            }
        }

        // Función para agregar una nueva pregunta
        function agregarPregunta() {
            const nuevaPregunta = document.createElement('div');
            nuevaPregunta.className = 'pregunta-item';
            nuevaPregunta.style = "background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);";
            nuevaPregunta.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <label style="font-weight: 600; font-size: 16px;">Pregunta ${preguntaCount + 1}</label>
                    <button type="button" class="btn-remove-pregunta">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <textarea class="clean-textarea" name="preguntas[${preguntaCount}][texto]" placeholder="Escribe la pregunta" rows="2" required></textarea>

                <div style="margin-top: 15px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px;">Tipo de respuesta</label>
                    <div class="tipo-respuesta-container">
                        <label class="tipo-respuesta-btn selected" data-tipo="OpcionMultiple">
                            <input type="radio" name="preguntas[${preguntaCount}][tipo]" value="OpcionMultiple" checked>
                            <i class="fa-solid fa-circle"></i>
                            <span>Opción</span>
                        </label>
                        <label class="tipo-respuesta-btn" data-tipo="VerdaderoFalso">
                            <input type="radio" name="preguntas[${preguntaCount}][tipo]" value="VerdaderoFalso">
                            <i class="fa-solid fa-check-circle"></i>
                            <span>V/F</span>
                        </label>
                        <label class="tipo-respuesta-btn" data-tipo="Abierta">
                            <input type="radio" name="preguntas[${preguntaCount}][tipo]" value="Abierta">
                            <i class="fa-solid fa-align-left"></i>
                            <span>Abierta</span>
                        </label>
                    </div>
                </div>

                <div style="margin-top: 15px; color: #666; font-size: 14px;">Toca el círculo para marcar la respuesta correcta</div>

                <div class="opciones-container" data-pregunta-index="${preguntaCount}">
                    <div class="opcion-item">
                        <label class="opcion-radio">
                            <input type="radio" name="preguntas[${preguntaCount}][respuesta_correcta]" value="0" checked>
                            <span class="custom-radio"></span>
                        </label>
                        <input type="text" class="clean-input" name="preguntas[${preguntaCount}][opciones][]" placeholder="Opción 1" required>
                    </div>
                    <div class="opcion-item">
                        <label class="opcion-radio">
                            <input type="radio" name="preguntas[${preguntaCount}][respuesta_correcta]" value="1">
                            <span class="custom-radio"></span>
                        </label>
                        <input type="text" class="clean-input" name="preguntas[${preguntaCount}][opciones][]" placeholder="Opción 2" required>
                    </div>
                </div>
                <button type="button" class="btn-add-opcion">
                    <i class="fa-solid fa-plus"></i> Agregar opción
                </button>

                <div class="respuesta-abierta-container" style="display: none;">
                    <textarea class="clean-textarea" name="preguntas[${preguntaCount}][respuesta_modelo]" placeholder="Escribe aquí la respuesta modelo o indicaciones para el estudiante (opcional)" rows="2"></textarea>
                </div>

                <div class="pregunta-meta">
                    <div class="meta-group">
                        <label>Puntos</label>
                        <input type="number" name="preguntas[${preguntaCount}][puntaje]" value="1" min="0" step="0.01" required>
                    </div>
                    <div class="meta-group obligatoria-toggle">
                        <label>Obligatoria</label>
                        <label class="switch">
                            <input type="checkbox" name="preguntas[${preguntaCount}][obligatoria]" checked value="1">
                        </label>
                    </div>
                </div>
            `;
            preguntasContainer.appendChild(nuevaPregunta);
            preguntaCount++;

            // Agregar event listeners a los botones de tipo de respuesta de la nueva pregunta
            const tipoRespuestaBtns = nuevaPregunta.querySelectorAll('.tipo-respuesta-btn');
            tipoRespuestaBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const preguntaIndex = Array.from(document.querySelectorAll('.pregunta-item')).indexOf(nuevaPregunta);
                    const tipo = this.getAttribute('data-tipo');

                    nuevaPregunta.querySelectorAll('.tipo-respuesta-btn').forEach(b => b.classList.remove('selected'));
                    this.classList.add('selected');

                    const tipoInput = nuevaPregunta.querySelector('input[name^="preguntas"][name$="[tipo]"]');
                    if (tipoInput) tipoInput.value = tipo;

                    actualizarOpcionesSegunTipo(nuevaPregunta, preguntaIndex, tipo);
                });
            });

            // Agregar event listener al switch de obligatoria
            const obligatoriaCheckbox = nuevaPregunta.querySelector('input[name^="preguntas"][name$="[obligatoria]"]');
            const obligatoriaSwitch = nuevaPregunta.querySelector('.switch');
            if (obligatoriaCheckbox && obligatoriaSwitch) {
                obligatoriaSwitch.classList.toggle('active', obligatoriaCheckbox.checked);
                obligatoriaCheckbox.addEventListener('change', function() {
                    obligatoriaSwitch.classList.toggle('active', this.checked);
                    validarFormulario();
                });
            }

            // Agregar event listener al botón de agregar opción
            const btnAddOpcion = nuevaPregunta.querySelector('.btn-add-opcion');
            if (btnAddOpcion) {
                btnAddOpcion.addEventListener('click', function() {
                    agregarOpcion(this);
                });
            }

            validarFormulario();
        }

        // Función para agregar una opción a una pregunta
        function agregarOpcion(button) {
            const preguntaItem = button.closest('.pregunta-item');
            const preguntaIndex = Array.from(document.querySelectorAll('.pregunta-item')).indexOf(preguntaItem);
            const opcionesContainer = preguntaItem.querySelector('.opciones-container');
            const opcionCount = opcionesContainer.querySelectorAll('.opcion-item').length;

            const nuevaOpcion = document.createElement('div');
            nuevaOpcion.className = 'opcion-item';
            nuevaOpcion.innerHTML = `
                <label class="opcion-radio">
                    <input type="radio" name="preguntas[${preguntaIndex}][respuesta_correcta]" value="${opcionCount}">
                    <span class="custom-radio"></span>
                </label>
                <input type="text" class="clean-input" name="preguntas[${preguntaIndex}][opciones][]" placeholder="Opción ${opcionCount + 1}" required>
            `;
            opcionesContainer.appendChild(nuevaOpcion);
            validarFormulario();
        }

        // Event listeners para validar en tiempo real
        document.getElementById('titulo').addEventListener('input', validarFormulario);
        document.getElementById('descripcion').addEventListener('input', validarFormulario);
        document.getElementById('id_materia').addEventListener('change', validarFormulario);
        document.getElementById('id_curso').addEventListener('change', validarFormulario);
        document.getElementById('puntaje_maximo').addEventListener('input', validarFormulario);
        document.getElementById('fecha_limite').addEventListener('change', validarFormulario);

        // Event listener para el botón Cancelar
        btnCancelar.addEventListener('click', limpiarFormulario);

        // Event listener para el botón "Agregar otra pregunta"
        document.getElementById('btnAddPregunta').addEventListener('click', agregarPregunta);

        // Event listener para el envío del formulario
        form.addEventListener('submit', function(e) {
            if (!validarFormulario()) {
                e.preventDefault();
                alert('Por favor, complete todos los campos obligatorios.');
            }
        });

        // Validar al cargar la página
        validarFormulario();
    });
</script>

</body>
</html>