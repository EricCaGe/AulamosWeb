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

// Procesar formulario
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_pareja'])) {
        $elemento_a = trim($_POST['elemento_a'] ?? '');
        $elemento_b = trim($_POST['elemento_b'] ?? '');
        $explicacion = trim($_POST['explicacion'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $puntos = intval($_POST['puntos'] ?? 50);
        
        if (empty($elemento_a) || empty($elemento_b)) {
            $mensaje = 'Ambos elementos son obligatorios.';
            $tipo_mensaje = 'error';
        } else {
            try {
                $insert = $conexion->prepare("
                    INSERT INTO conecta_parejas (
                        id_juego, elemento_a_texto, elemento_b_texto, 
                        explicacion, categoria, orden, puntos
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $orden = count($parejas) + 1;
                $insert->bind_param("issssii", $id_juego, $elemento_a, $elemento_b, $explicacion, $categoria, $orden, $puntos);
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
        }
        
        .item-pareja .contenido .elementos .flecha {
            color: #3b71f3;
            font-size: 14px;
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
                <p>Agrega los elementos que relacionarán los estudiantes</p>
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
                                <i class="<?php echo getIconoModo($juego['modo']); ?>"></i> <?php echo $juego['modo']; ?>
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
                        <i class="fa-solid fa-plus"></i> Nueva pareja
                    </h4>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Elemento A <span class="required">*</span></label>
                            <input type="text" name="elemento_a" placeholder="Ej. Dog" required>
                        </div>
                        <div class="conector">
                            <i class="fa-solid fa-arrow-down"></i>
                        </div>
                        <div class="form-group">
                            <label>Elemento B <span class="required">*</span></label>
                            <input type="text" name="elemento_b" placeholder="Ej. Perro" required>
                        </div>
                        <div class="form-group">
                            <label>Explicación</label>
                            <textarea name="explicacion" placeholder="Ej. Dog significa perro en español."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Categoría</label>
                            <input type="text" name="categoria" placeholder="Ej. Animals">
                        </div>
                        <div class="form-group">
                            <label>Puntos</label>
                            <input type="number" name="puntos" value="50" min="1">
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
                                            <span><?php echo htmlspecialchars($pareja['elemento_a_texto'] ?? 'Multimedia'); ?></span>
                                            <span class="flecha"><i class="fa-solid fa-arrow-right"></i></span>
                                            <span><?php echo htmlspecialchars($pareja['elemento_b_texto'] ?? 'Multimedia'); ?></span>
                                        </div>
                                        <?php if ($pareja['categoria']): ?>
                                            <div class="extra">
                                                <i class="fa-regular fa-tag"></i> <?php echo htmlspecialchars($pareja['categoria']); ?>
                                                <span style="margin-left: 10px;">
                                                    <i class="fa-regular fa-star"></i> <?php echo $pareja['puntos']; ?> pts
                                                </span>
                                            </div>
                                        <?php endif; ?>
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

<script src="jss/docente_dashboard.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>

</body>
</html>