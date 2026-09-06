<?php
session_start();

// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['rol'] ?? '') !== 'Docente' || empty($_SESSION['usuario']['id_usuario'])) {
    header('Location: ../InicioSesion/login.php');
    exit;
}

echo '<script>window.idUsuario = ' . json_encode((int)$_SESSION['usuario']['id_usuario']) . ';</script>';

require_once '../Conexion/conexion.php';

$id_docente = $_SESSION['usuario']['id_usuario'];
$nombre_docente = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];

// Obtener foto de perfil del docente
$foto_perfil_docente = $_SESSION['usuario']['foto_perfil'] ?? null;
$ruta_foto_docente = !empty($foto_perfil_docente) ? '../uploads/perfiles/' . $foto_perfil_docente : 'https://placehold.co/40x40/ff7675/white?text=👨';

// Obtener cursos del docente
$query_cursos = "
    SELECT c.id_curso, c.nombre, m.nombre AS materia, g.nombre AS grupo
    FROM cursos c
    JOIN materias m ON c.id_materia = m.id_materia
    JOIN grupos g ON c.id_grupo = g.id_grupo
    WHERE c.id_docente = ? AND c.estado = 'Activo'
";

$stmt_cursos = $conexion->prepare($query_cursos);
$stmt_cursos->bind_param("i", $id_docente);
$stmt_cursos->execute();
$result_cursos = $stmt_cursos->get_result();
$cursos = $result_cursos->fetch_all(MYSQLI_ASSOC);
$stmt_cursos->close();

// Procesar formulario
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_juego'])) {
    // Obtener valores
    $id_curso = isset($_POST['id_curso']) ? intval($_POST['id_curso']) : 0;
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $tema = trim($_POST['tema'] ?? '');
    $modo = isset($_POST['modo']) ? $_POST['modo'] : 'Relacionar';
    $modalidad = 'Individual';
    $tiempo_limite = !empty($_POST['tiempo_limite']) ? intval($_POST['tiempo_limite']) : null;
    $puntos_por_acierto = intval($_POST['puntos_por_acierto'] ?? 50);
    $intentos_maximos = !empty($_POST['intentos_maximos']) ? intval($_POST['intentos_maximos']) : null;
    $mostrar_retroalimentacion = isset($_POST['mostrar_retroalimentacion']) ? 1 : 0;
    
    // ==========================================
    // VALIDACIÓN ESTRICTA - COINCIDIR CON ENUM
    // ==========================================
    
    $modos_permitidos = ['Relacionar', 'Memoria', 'Clasificar', 'Secuencia'];
    $modalidades_permitidas = ['Individual'];
    
    if (!in_array($modo, $modos_permitidos, true)) {
        $modo = 'Relacionar';
    }
    
    if (!in_array($modalidad, $modalidades_permitidas, true)) {
        $modalidad = 'Individual';
    }
    
    $titulo = !empty($titulo) ? $titulo : 'Juego sin título';
    $descripcion = !empty($descripcion) ? $descripcion : '';
    $tema = !empty($tema) ? $tema : '';
    
    $tiempo_limite = (!empty($tiempo_limite) && $tiempo_limite > 0) ? $tiempo_limite : null;
    $puntos_por_acierto = ($puntos_por_acierto > 0) ? $puntos_por_acierto : 50;
    $intentos_maximos = (!empty($intentos_maximos) && $intentos_maximos > 0) ? $intentos_maximos : null;
    $mostrar_retroalimentacion = isset($_POST['mostrar_retroalimentacion']) ? 1 : 0;
    
    // ==========================================
    // VALIDACIONES DE ERRORES
    // ==========================================
    
    $errores = [];
    
    if ($id_curso <= 0) {
        $errores[] = 'Selecciona un curso.';
    }
    
    if (empty($titulo)) {
        $errores[] = 'El título del juego es obligatorio.';
    }
    
    if (strlen($titulo) > 150) {
        $errores[] = 'El título no puede superar los 150 caracteres.';
    }

    if (empty($tema)) {
        $errores[] = 'Selecciona un tema.';
    }
    
    if ($puntos_por_acierto <= 0) {
        $errores[] = 'Los puntos por acierto deben ser mayores que cero.';
    }
    
    // ==========================================
    // INSERTAR EN BASE DE DATOS
    // ==========================================
    
    if (empty($errores)) {
        try {
            $query_insert = "
                INSERT INTO conecta_juegos (
                    id_curso, id_docente, titulo, descripcion, tema, 
                    modo, modalidad, tiempo_limite_seg, puntos_por_acierto, 
                    intentos_maximos, mostrar_retroalimentacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $stmt_insert = $conexion->prepare($query_insert);
            
            if (!$stmt_insert) {
                throw new Exception('Error en la preparación: ' . $conexion->error);
            }
            
            $stmt_insert->bind_param(
                "iisssssiiii",
                $id_curso,
                $id_docente,
                $titulo,
                $descripcion,
                $tema,
                $modo,
                $modalidad,
                $tiempo_limite,
                $puntos_por_acierto,
                $intentos_maximos,
                $mostrar_retroalimentacion
            );
            
            if ($stmt_insert->execute()) {
                $id_juego = $stmt_insert->insert_id;
                $stmt_insert->close();
                
                // =====================================================
                // REDIRECCIONAR A LA PANTALLA DEL MODO
                // =====================================================

                $pantallas_modo = [
                    'Relacionar' => 'editar_parejas_juego.php',
                    'Memoria' => 'editar_memoria_juego.php',
                    'Clasificar' => 'editar_clasificar_juego.php',
                    'Secuencia' => 'editar_secuencia_juego.php',
                ];

                $pantalla_destino =
                    $pantallas_modo[$modo]
                    ?? 'editar_parejas_juego.php';

                header(
                    'Location: ' .
                    $pantalla_destino .
                    '?id_juego=' .
                    $id_juego .
                    '&success=1'
                );

                exit;
            } else {
                $mensaje = 'Error al crear el juego: ' . $stmt_insert->error;
                $tipo_mensaje = 'error';
                $stmt_insert->close();
            }
            
        } catch (Exception $e) {
            $mensaje = 'Error: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = implode(' ', $errores);
        $tipo_mensaje = 'error';
    }
}

$conexion->close();

$modos = ['Relacionar', 'Memoria', 'Clasificar', 'Secuencia'];
$modalidades = ['Individual'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Juego - Docente</title>
    
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    
    <style>
        .crear-container {
            padding: 20px 30px;
            width: 100%;
            max-width: 100%;
            margin: 0;
        }
        
        .form-juego {
            background: white;
            border-radius: 16px;
            padding: 30px;
            border: 1px solid #e2e8f0;
            max-width: 800px;
        }
        
        .form-juego .form-group {
            margin-bottom: 20px;
        }
        
        .form-juego label {
            font-weight: 600;
            font-size: 14px;
            color: #1e293b;
            display: block;
            margin-bottom: 5px;
        }
        
        .form-juego label .required {
            color: #dc2626;
        }
        
        .form-juego input,
        .form-juego select,
        .form-juego textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        
        .form-juego input:focus,
        .form-juego select:focus,
        .form-juego textarea:focus {
            border-color: #3b71f3;
            outline: none;
        }
        
        .form-juego textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-juego .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-juego .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
        }
        
        .form-juego .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .form-juego .btn-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 28px;
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
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
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
        
        .modalidad-fija {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .modalidad-fija i {
            font-size: 22px;
            color: #3b71f3;
        }

        .modalidad-fija div {
            display: flex;
            flex-direction: column;
        }

        .modalidad-fija strong {
            color: #1e293b;
            font-size: 14px;
        }

        .modalidad-fija span {
            color: #64748b;
            font-size: 12px;
            margin-top: 2px;
        }

        .modo-opciones {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .modo-destino {
            display: flex; gap: 10px; align-items: flex-start; padding: 13px 15px;
            margin-top: 14px; background: #eff6ff; color: #1e40af;
            border: 1px solid #bfdbfe; border-radius: 10px; font-size: 13px; line-height: 1.5;
        }
        .modo-destino i { color: #3b71f3; margin-top: 2px; }
        .modo-opcion {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }
        
        .modo-opcion:hover {
            border-color: #94a3b8;
        }
        
        .modo-opcion.active {
            border-color: #3b71f3;
            background: #eff6ff;
        }
        
        .modo-opcion .icono {
            font-size: 24px;
            color: #3b71f3;
        }
        
        .modo-opcion .nombre {
            font-weight: 600;
            font-size: 14px;
            color: #1e293b;
            margin-top: 4px;
        }
        
        .modo-opcion .desc {
            font-size: 11px;
            color: #64748b;
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
            .crear-container {
                padding: 15px;
            }
            
            .form-juego {
                padding: 20px 15px;
            }
            
            .form-juego .row {
                grid-template-columns: 1fr;
            }
            
            .modalidad-fija {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .modalidad-fija i {
            font-size: 22px;
            color: #3b71f3;
        }

        .modalidad-fija div {
            display: flex;
            flex-direction: column;
        }

        .modalidad-fija strong {
            color: #1e293b;
            font-size: 14px;
        }

        .modalidad-fija span {
            color: #64748b;
            font-size: 12px;
            margin-top: 2px;
        }

        .modo-opciones {
                grid-template-columns: 1fr;
            }
            
            .btn-actions {
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
            <img src="../img/logo_g.png" alt="Logo Aulamos" class="logo-img">
        </div>
        
        <nav class="menu">
            <a href="docente_dashboard.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="crear_recurso.php" class="menu-item"><i class="fa-solid fa-medal"></i> Crear Recurso</a>
            <a href="mis_recursos.php" class="menu-item"><i class="fa-solid fa-folder-open"></i> Mis Recursos</a>
            <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
            <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluación</a>
            <a href="crear_juego.php" class="menu-item active"><i class="fa-solid fa-gamepad"></i> Crear Juego</a>
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
                <h1>Crear juego</h1>
                <p>Configura Conecta y Aprende</p>
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

        <div class="crear-container">
            
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-juego">
                <form method="POST" action="">
                    <!-- Información general -->
                    <div class="form-group">
                        <label>Curso <span class="required">*</span></label>
                        <select name="id_curso" id="id_curso" required>
                            <option value="">Selecciona un curso</option>
                            <?php foreach ($cursos as $curso): ?>
                                <option
                                    value="<?php echo $curso['id_curso']; ?>"
                                    data-materia="<?php echo htmlspecialchars($curso['materia'], ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo ((string)($_POST['id_curso'] ?? '') === (string)$curso['id_curso']) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($curso['nombre'] . ' - ' . $curso['materia'] . ' (' . $curso['grupo'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Título del juego <span class="required">*</span></label>
                        <input type="text" name="titulo" placeholder="Ej. Vocabulary Animals" maxlength="150" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Tema <span class="required">*</span></label>
                        <select name="tema" id="tema" required disabled>
                            <option value="">Primero selecciona un curso</option>
                        </select>
                        <small id="ayudaTema" style="display:block; margin-top:6px; color:#64748b; font-size:12px;">
                            Los temas se filtrarán automáticamente según la materia del curso seleccionado.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="descripcion" placeholder="Describe brevemente la dinámica del juego"></textarea>
                    </div>
                    
                    <!-- Modo de juego -->
                    <div class="form-group">
                        <label>Modo de juego <span class="required">*</span></label>
                        <div class="modo-opciones">
                            <?php foreach ($modos as $modo): ?>
                                <?php 
                                $iconos = [
                                    'Relacionar' => 'fa-solid fa-arrow-right-arrow-left',
                                    'Memoria' => 'fa-solid fa-grid-2',
                                    'Clasificar' => 'fa-solid fa-layer-group',
                                    'Secuencia' => 'fa-solid fa-list-ol'
                                ];
                                $descripciones = [
                                    'Relacionar' => 'Relaciona conceptos, palabras, imágenes o definiciones.',
                                    'Memoria' => 'Encuentra las parejas del memorama.',
                                    'Clasificar' => 'Organiza elementos dentro de una categoría.',
                                    'Secuencia' => 'Ordena elementos siguiendo una secuencia correcta.'
                                ];
                                ?>
                                <div class="modo-opcion <?php echo $modo === 'Relacionar' ? 'active' : ''; ?>" onclick="seleccionarModo(this)">
                                    <input type="radio" name="modo" value="<?php echo $modo; ?>" <?php echo $modo === 'Relacionar' ? 'checked' : ''; ?> style="display: none;">
                                    <div class="icono"><i class="<?php echo $iconos[$modo]; ?>"></i></div>
                                    <div class="nombre"><?php echo $modo === 'Memoria' ? 'Memorama' : $modo; ?></div>
                                    <div class="desc"><?php echo $descripciones[$modo]; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Modalidad fija -->
                    <div class="form-group">
                        <label>Modalidad</label>
                        <div class="modalidad-fija">
                            <i class="fa-solid fa-user"></i>
                            <div>
                                <strong>Individual</strong>
                                <span>El juego será realizado individualmente por cada alumno.</span>
                            </div>
                        </div>
                        <input type="hidden" name="modalidad" value="Individual">
                    </div>
                    
                    <!-- Configuración -->
                    <div class="row">
                        <div class="form-group">
                            <label>Tiempo límite (segundos)</label>
                            <input type="number" name="tiempo_limite" placeholder="120" min="1">
                        </div>
                        <div class="form-group">
                            <label>Puntos por acierto <span class="required">*</span></label>
                            <input type="number" name="puntos_por_acierto" value="50" min="1" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="form-group">
                            <label>Intentos máximos</label>
                            <input type="number" name="intentos_maximos" placeholder="3" min="1">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="checkbox-group">
                                <input type="checkbox" name="mostrar_retroalimentacion" checked>
                                <span>Mostrar retroalimentación a los estudiantes</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones -->
                    <div class="btn-actions">
                        <a href="juegos_docente.php" class="btn btn-secondary">
                            <i class="fa-solid fa-times"></i> Cancelar
                        </a>
                        <button type="submit" name="crear_juego" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> <span id="textoBtnCrear">Crear y configurar Relacionar</span>
                        </button>
                    </div>
                    <div class="modo-destino" id="modoDestino">
                        <i class="fa-solid fa-circle-info"></i>
                        <span id="textoModoDestino">Después de crear el juego podrás configurar el contenido correspondiente al modo seleccionado.</span>
                    </div>
                </form>
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

<script>

const temasPorMateria = {
    ingles: [
        'Información personal',
        'Pronunciación',
        'Números',
        'Adjetivos',
        'Verbos',
        'Preposiciones',
        'Familia',
        'Clima',
        'Transporte',
        'Deportes',
        'Sinónimos y antónimos'
    ],
    educacionsocioemocional: [
        'Cultura de paz',
        'Empatía',
        'Respeto',
        'Inclusión',
        'Resolución de conflictos',
        'Fortalezas de carácter',
        'Redes de apoyo',
        'Amistad',
        'Gestión de emociones',
        'Proyecto de vida'
    ],
    espanol: [
        'Comprensión lectora',
        'Análisis de textos',
        'Argumentación',
        'Pensamiento crítico'
    ],
    fisica: [
        'Universo',
        'Galaxias',
        'Sistema Solar',
        'Estrellas',
        'Agujeros negros',
        'Nebulosas',
        'Cometas',
        'Satélites naturales'
    ],
    matematicas: [
        'Ángulos',
        'Polígonos',
        'Área',
        'Perímetro',
        'Círculo y circunferencia',
        'Figuras compuestas',
        'Probabilidad',
        'Azar',
        'Espacio muestral',
        'Frecuencias',
        'Ecuaciones de primer grado',
        'Ecuaciones de segundo grado',
        'Sistemas de ecuaciones'
    ],
    danza: [
        'Expresión corporal',
        'Emociones',
        'Coreografía'
    ],
    musica: [
        'Ritmo',
        'Piano',
        'Melodía'
    ],
    ajedrez: [
        'Tácticas de ajedrez',
        'Coronación del peón',
        'Notación algebraica'
    ]
};

function normalizarMateria(texto) {
    return (texto || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]/g, '');
}

function obtenerTemasMateria(materia) {
    const clave = normalizarMateria(materia);

    if (temasPorMateria[clave]) {
        return temasPorMateria[clave];
    }

    // Compatibilidad con nombres de materia que incluyan texto adicional.
    const coincidencia = Object.keys(temasPorMateria).find(k =>
        clave.includes(k) || k.includes(clave)
    );

    return coincidencia ? temasPorMateria[coincidencia] : [];
}

function actualizarTemasPorCurso() {
    const selectCurso = document.getElementById('id_curso');
    const selectTema = document.getElementById('tema');
    const ayudaTema = document.getElementById('ayudaTema');
    const opcionCurso = selectCurso.options[selectCurso.selectedIndex];
    const materia = opcionCurso ? (opcionCurso.dataset.materia || '') : '';
    const temas = obtenerTemasMateria(materia);
    const temaAnterior = selectTema.dataset.temaAnterior || '';

    selectTema.innerHTML = '';

    if (!selectCurso.value) {
        selectTema.disabled = true;
        selectTema.innerHTML = '<option value="">Primero selecciona un curso</option>';
        ayudaTema.textContent = 'Los temas se filtrarán automáticamente según la materia del curso seleccionado.';
        return;
    }

    selectTema.disabled = false;

    const opcionInicial = document.createElement('option');
    opcionInicial.value = '';
    opcionInicial.textContent = temas.length ? 'Selecciona un tema' : 'No hay temas predefinidos para esta materia';
    selectTema.appendChild(opcionInicial);

    temas.forEach(tema => {
        const option = document.createElement('option');
        option.value = tema;
        option.textContent = tema;
        if (tema === temaAnterior) option.selected = true;
        selectTema.appendChild(option);
    });

    ayudaTema.textContent = temas.length
        ? `Mostrando temas de ${materia}.`
        : `No hay temas configurados para ${materia}.`;
}

const configuracionModos = {
    Relacionar: { boton: 'Crear y configurar Relacionar', texto: 'Después podrás agregar las parejas que relacionarán los estudiantes.' },
    Memoria: { boton: 'Crear y configurar Memorama', texto: 'Después podrás agregar las parejas de tarjetas que formarán el memorama.' },
    Clasificar: { boton: 'Crear y configurar Clasificar', texto: 'Después podrás agregar elementos y asignarlos a sus categorías correctas.' },
    Secuencia: { boton: 'Crear y configurar Secuencia', texto: 'Después podrás agregar los pasos y definir el orden correcto de la secuencia.' }
};

function actualizarTextoModo() {
    const radio = document.querySelector('input[name="modo"]:checked');
    const modo = radio ? radio.value : 'Relacionar';
    const cfg = configuracionModos[modo] || configuracionModos.Relacionar;
    document.getElementById('textoBtnCrear').textContent = cfg.boton;
    document.getElementById('textoModoDestino').textContent = cfg.texto;
}

function seleccionarModo(elemento) {
    document.querySelectorAll('.modo-opcion').forEach(el => el.classList.remove('active'));
    elemento.classList.add('active');
    const radio = elemento.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
    actualizarTextoModo();
}

document.addEventListener('DOMContentLoaded', () => {
    const selectCurso = document.getElementById('id_curso');
    const selectTema = document.getElementById('tema');

    selectTema.dataset.temaAnterior = <?php echo json_encode($_POST['tema'] ?? ''); ?>;

    actualizarTextoModo();
    actualizarTemasPorCurso();

    selectCurso.addEventListener('change', () => {
        selectTema.dataset.temaAnterior = '';
        actualizarTemasPorCurso();
    });
});
</script>

<script src="jss/docente_dashboard.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>

</body>
</html>