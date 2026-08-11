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

$id_docente = $_SESSION['usuario']['id_usuario'];
$nombre_docente = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];

$mensaje = '';
$tipo_mensaje = '';

// Obtener la lista de materias para el selector de filtro
$consulta_materias = "SELECT id_materia, nombre FROM materias WHERE estado = 'Activa' ORDER BY nombre";
$stmt_materias = $conexion->prepare($consulta_materias);
$stmt_materias->execute();
$resultado_materias = $stmt_materias->get_result();
$materias = $resultado_materias->fetch_all(MYSQLI_ASSOC);
$stmt_materias->close();

// Obtener parámetros de filtro
$filtro_materia = isset($_GET['materia']) && $_GET['materia'] !== '' ? (int)$_GET['materia'] : null;
$filtro_tipo = isset($_GET['tipo']) && $_GET['tipo'] !== '' ? $_GET['tipo'] : null;

// Construir la consulta dinámica con parámetros seguros
$sql = "
    SELECT
        r.id_recurso,
        r.id_materia,
        r.titulo,
        r.descripcion,
        r.tipo,
        r.fecha_publicacion,
        r.estado,
        r.url_recurso,
        COALESCE(m.nombre, 'Sin materia') AS materia
    FROM recursos_educativos r
    LEFT JOIN materias m ON r.id_materia = m.id_materia
    WHERE r.id_docente = ?
";

$types = "i";
$params = [$id_docente];

if ($filtro_materia) {
    $sql .= " AND r.id_materia = ?";
    $types .= "i";
    $params[] = $filtro_materia;
}

if ($filtro_tipo) {
    $sql .= " AND r.tipo = ?";
    $types .= "s";
    $params[] = $filtro_tipo;
}

$sql .= " ORDER BY r.fecha_publicacion DESC";

// Ejecutar consulta de recursos
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    die("Error al preparar la consulta: " . $conexion->error);
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultado_recursos = $stmt->get_result();

if (!$resultado_recursos) {
    die("Error al ejecutar la consulta: " . $stmt->error);
}

$recursos = $resultado_recursos->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Recursos - Aulamos</title>
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- NUEVA ACCESIBILIDAD -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    
    <style>
        /* Estilos adicionales para ver_recursos.php - SOLO BASE */
        .filters-container {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
        }
        .filter-group select {
            padding: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }
        .resources-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .resource-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            display: flex;
            gap: 15px;
        }
        .resource-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .resource-icon.pdf { background: #ef4444; }
        .resource-icon.video { background: #3b82f6; }
        .resource-icon.documento { background: #10b981; }
        .resource-icon.imagen { background: #8b5cf6; }
        .resource-icon.enlace { background: #f59e0b; }
        .resource-info {
            flex: 1;
        }
        .resource-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 5px;
        }
        .resource-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 12px;
            color: #64748b;
        }
        .resource-meta span {
            display: flex;
            align-items: center;
            gap: 3px;
        }
        .resource-actions {
            display: flex;
            gap: 8px;
        }
        .btn-icon {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-ver {
            background: #eff6ff;
            color: #3b71f3;
        }
        .btn-descargar {
            background: #dcfce7;
            color: #166534;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .btn-aplicar-filtros {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-aplicar-filtros:hover {
            background-color: #1d4ed8;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logo_g.png" alt="Logo Aulamos" class="logo-img">
        </div>
        <nav class="menu">
            <a href="docente_dashboard.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="crear_recurso.php" class="menu-item"><i class="fa-solid fa-medal"></i> Crear Recurso</a>
            <a href="ver_recursos.php" class="menu-item active"><i class="fa-solid fa-folder-open"></i> Ver Recursos</a>
            <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
            <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluación</a>
            <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
            <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
            <a href="pasarlista.php" class="menu-item"><i class="fa-solid fa-bars"></i> Pasar Lista</a>
            <div class="menu-spacer"></div>
            <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>

    <!-- Contenido Principal -->
    <main class="main-content">
        
        <header class="content-header">
            <div class="welcome-text">
                <h1>Ver Recursos</h1>
                <p>Administra los recursos que has creado</p>
            </div>
            <div class="header-actions">
                <button class="btn-assistant" id="btn-asistente">Asistente Virtual <span class="robot-icon">🤖</span></button>
                <div class="icon-bell-container">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <div class="user-profile">
                    <img src="https://placehold.co/40x40/ff7675/white?text=👨" alt="Avatar Docente" class="avatar">
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($nombre_docente) ?></span>
                        <span class="user-role">Docente</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Filtros -->
        <div class="filters-container">
            <h3 class="section-title">Filtros</h3>
            <form method="get" action="ver_recursos.php" class="filters-grid">
                <div class="filter-group">
                    <label>Materia</label>
                    <select name="materia">
                        <option value="">Todas</option>
                        <?php foreach ($materias as $materia): ?>
                            <option value="<?= $materia['id_materia'] ?>" <?= ($filtro_materia == $materia['id_materia']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($materia['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Tipo</label>
                    <select name="tipo">
                        <option value="">Todos</option>
                        <option value="PDF" <?= ($filtro_tipo == 'PDF') ? 'selected' : '' ?>>PDF</option>
                        <option value="Video" <?= ($filtro_tipo == 'Video') ? 'selected' : '' ?>>Video</option>
                        <option value="Documento" <?= ($filtro_tipo == 'Documento') ? 'selected' : '' ?>>Documento</option>
                        <option value="Imagen" <?= ($filtro_tipo == 'Imagen') ? 'selected' : '' ?>>Imagen</option>
                        <option value="Enlace" <?= ($filtro_tipo == 'Enlace') ? 'selected' : '' ?>>Enlace</option>
                    </select>
                </div>
                <div class="filter-group" style="align-self: flex-end;">
                    <button type="submit" class="btn-aplicar-filtros" style="width: 100%;">Aplicar Filtros</button>
                </div>
            </form>
        </div>

        <!-- Lista de Recursos -->
        <div class="section-container">
            <h3 class="section-title">Tus Recursos (<?= count($recursos) ?>)</h3>
            <?php if (empty($recursos)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-folder-open"></i>
                    <p>No hay recursos creados aún.</p>
                    <a href="crear_recurso.php" class="btn-aplicar-filtros" style="margin-top: 15px; display: inline-block; text-decoration: none;">Crear Recurso</a>
                </div>
            <?php else: ?>
                <div class="resources-list">
                    <?php foreach ($recursos as $recurso): ?>
                        <div class="resource-card">
                            <div class="resource-icon
                                <?php
                                $tipo = strtolower($recurso['tipo']);
                                echo $tipo === 'pdf' ? 'pdf' :
                                     ($tipo === 'video' ? 'video' :
                                     ($tipo === 'documento' ? 'documento' :
                                     ($tipo === 'imagen' ? 'imagen' : 'enlace')));
                                ?>">
                                <i class="fa-solid
                                    <?php
                                    if ($tipo === 'pdf') echo 'fa-file-pdf';
                                    else if ($tipo === 'video') echo 'fa-play';
                                    else if ($tipo === 'documento') echo 'fa-file-lines';
                                    else if ($tipo === 'imagen') echo 'fa-image';
                                    else echo 'fa-link';
                                    ?>">
                                </i>
                            </div>
                            <div class="resource-info">
                                <div class="resource-title"><?= htmlspecialchars($recurso['titulo']) ?></div>
                                <div class="resource-meta">
                                    <span><i class="fa-solid fa-book"></i> <?= htmlspecialchars($recurso['materia']) ?></span>
                                    <span><i class="fa-solid fa-calendar"></i> <?= date('d/m/Y', strtotime($recurso['fecha_publicacion'])) ?></span>
                                    <span><i class="fa-solid fa-circle" style="font-size: 8px; color: <?= $recurso['estado'] === 'Activo' ? '#16a34a' : '#dc2626' ?>;"></i> <?= htmlspecialchars($recurso['estado']) ?></span>
                                </div>
                                <p style="font-size: 13px; color: #64748b; margin-top: 8px;">
                                    <?= htmlspecialchars($recurso['descripcion'] ?? 'Sin descripción') ?>
                                </p>
                            </div>
                            <div class="resource-actions">
                                <?php if (!empty($recurso['url_recurso'])): ?>
                                    <button class="btn-icon btn-descargar" title="Descargar" onclick="descargarRecurso(<?= $recurso['id_recurso'] ?>)">
                                        <i class="fa-solid fa-download"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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

<script>
    function descargarRecurso(idRecurso) {
        window.location.href = `descargar_recurso.php?id_recurso=${idRecurso}`;
    }
</script>

</body>
</html>