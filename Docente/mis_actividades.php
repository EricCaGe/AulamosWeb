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

echo '<script>window.idUsuario = ' . json_encode((int)$_SESSION['usuario']['id_usuario']) . ';</script>';

require_once '../Conexion/conexion.php';

$id_docente = (int)$_SESSION['usuario']['id_usuario'];
$nombre_docente = trim(
    ($_SESSION['usuario']['nombre'] ?? '') . ' ' .
    ($_SESSION['usuario']['apellido_paterno'] ?? '')
);

$foto_perfil_docente = $_SESSION['usuario']['foto_perfil'] ?? null;
$ruta_foto_docente = !empty($foto_perfil_docente)
    ? '../uploads/perfiles/' . $foto_perfil_docente
    : 'https://placehold.co/40x40/ff7675/white?text=👨';

$buscar = trim($_GET['buscar'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$id_curso = isset($_GET['id_curso']) ? (int)$_GET['id_curso'] : 0;

$cursos = [];
$stmt = $conexion->prepare("
    SELECT c.id_curso, c.nombre, m.nombre AS materia, g.nombre AS grupo
    FROM cursos c
    INNER JOIN materias m ON m.id_materia = c.id_materia
    INNER JOIN grupos g ON g.id_grupo = c.id_grupo
    WHERE c.id_docente = ?
    ORDER BY m.nombre, c.nombre
");
$stmt->bind_param('i', $id_docente);
$stmt->execute();
$cursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function badgeClase($estado) {
    $e = strtolower((string)$estado);
    if ($e === 'publicada') return 'badge-publicada';
    if ($e === 'cerrada') return 'badge-cerrada';
    if ($e === 'archivada') return 'badge-archivada';
    return 'badge-borrador';
}

function fechaBonita($fecha) {
    if (!$fecha) return 'Sin fecha';
    return date('d/m/Y h:i A', strtotime($fecha));
}

$sql = "
    SELECT
        a.id_actividad,
        a.titulo,
        a.descripcion,
        a.tipo,
        a.fecha_publicacion,
        a.fecha_limite,
        a.puntaje_maximo,
        a.estado,
        c.id_curso,
        c.nombre AS curso,
        m.nombre AS materia,
        g.nombre AS grupo,
        COUNT(DISTINCT ae.id_alumno) AS alumnos_asignados,
        SUM(CASE WHEN ae.estado IN ('Entregada','Completada','completada') THEN 1 ELSE 0 END) AS completadas,
        SUM(CASE WHEN ae.estado IN ('Calificada','calificada') THEN 1 ELSE 0 END) AS calificadas,
        COUNT(DISTINCT e.id_entrega) AS entregas_reales,
        GROUP_CONCAT(
            DISTINCT CASE
                WHEN e.id_entrega IS NOT NULL THEN CONCAT(
                    ae.id_alumno, '|||',
                    COALESCE(u.nombre,''), ' ', COALESCE(u.apellido_paterno,''), ' ', COALESCE(u.apellido_materno,''), '|||',
                    e.id_entrega, '|||',
                    COALESCE(e.estado,'Entregada')
                )
            END
            SEPARATOR '###'
        ) AS lista_entregas
    FROM actividades a
    INNER JOIN cursos c ON c.id_curso = a.id_curso
    INNER JOIN materias m ON m.id_materia = c.id_materia
    INNER JOIN grupos g ON g.id_grupo = c.id_grupo
    LEFT JOIN actividad_estudiantes ae ON ae.id_actividad = a.id_actividad
    LEFT JOIN entregas e ON e.id_actividad_estudiante = ae.id_actividad_estudiante
    LEFT JOIN usuarios u ON u.id_usuario = ae.id_alumno
    WHERE a.id_docente = ?
      AND LOWER(a.tipo) <> 'evaluacion'
";

$tipos = 'i';
$params = [$id_docente];

if ($buscar !== '') {
    $sql .= " AND (a.titulo LIKE ? OR a.descripcion LIKE ? OR m.nombre LIKE ? OR c.nombre LIKE ?)";
    $like = '%' . $buscar . '%';
    $tipos .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}

if ($estado !== '') {
    $sql .= " AND LOWER(a.estado) = LOWER(?)";
    $tipos .= 's';
    $params[] = $estado;
}

if ($id_curso > 0) {
    $sql .= " AND a.id_curso = ?";
    $tipos .= 'i';
    $params[] = $id_curso;
}

$sql .= "
    GROUP BY
        a.id_actividad, a.titulo, a.descripcion, a.tipo, a.fecha_publicacion,
        a.fecha_limite, a.puntaje_maximo, a.estado,
        c.id_curso, c.nombre, m.nombre, g.nombre
    ORDER BY a.fecha_publicacion DESC
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param($tipos, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total = count($items);
$publicadas = count(array_filter($items, fn($x) => strtolower($x['estado']) === 'publicada'));
$borradores = count(array_filter($items, fn($x) => strtolower($x['estado']) === 'borrador'));
$vencidas = count(array_filter($items, fn($x) => !empty($x['fecha_limite']) && strtotime($x['fecha_limite']) < time()));

$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis Actividades - Docente</title>
<link rel="stylesheet" href="styles/docente.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">

<style>
.page-wrap{padding:24px 30px}
.page-tools{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:20px}
.search-box{flex:1;min-width:240px;max-width:520px;position:relative}
.search-box i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#64748b}
.search-box input,.filter-select{width:100%;border:1px solid #dbe3ef;border-radius:12px;padding:12px 14px;background:#fff;font-size:14px}
.search-box input{padding-left:40px}
.filters{display:flex;gap:10px;flex-wrap:wrap}
.filter-select{width:auto;min-width:160px}
.summary-grid{display:grid;grid-template-columns:repeat(4,minmax(130px,1fr));gap:14px;margin-bottom:22px}
.summary-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:16px}
.summary-card .n{font-size:26px;font-weight:800;color:#0f172a}
.summary-card .l{font-size:12px;color:#64748b;margin-top:4px}
.cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.item-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:18px;box-shadow:0 4px 14px rgba(15,23,42,.04)}
.item-top{display:flex;justify-content:space-between;gap:14px;align-items:flex-start}
.item-title{margin:0;font-size:18px;color:#0f172a}
.meta{margin:5px 0 0;color:#64748b;font-size:13px}
.badge{display:inline-flex;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:800}
.badge-publicada{background:#dcfce7;color:#166534}
.badge-borrador{background:#f1f5f9;color:#475569}
.badge-cerrada{background:#fee2e2;color:#991b1b}
.badge-archivada{background:#ede9fe;color:#5b21b6}
.item-desc{color:#475569;font-size:13px;line-height:1.55;margin:14px 0;min-height:40px}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:12px 0 16px}
.info{background:#f8fafc;border-radius:10px;padding:10px;font-size:12px;color:#475569}
.info strong{display:block;color:#0f172a;margin-bottom:3px}
.actions{display:flex;gap:8px;flex-wrap:wrap;border-top:1px solid #eef2f7;padding-top:14px}
.btn-mini{display:inline-flex;align-items:center;gap:7px;text-decoration:none;padding:9px 12px;border-radius:10px;font-size:12px;font-weight:700;border:1px solid #dbe3ef;color:#334155;background:#fff}
.btn-mini.primary{background:#2D5BFF;color:#fff;border-color:#2D5BFF}
.empty{grid-column:1/-1;background:#fff;border:1px dashed #cbd5e1;border-radius:18px;padding:44px;text-align:center;color:#64748b}

.empty i{font-size:38px;margin-bottom:12px}
.entregas-panel{margin-top:14px;padding-top:14px;border-top:1px solid #eef2f7}
.entregas-panel-title{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}
.entregas-panel-title strong{font-size:13px;color:#0f172a}
.entregas-count{font-size:11px;font-weight:800;background:#eff6ff;color:#2563eb;padding:5px 9px;border-radius:999px}
.entrega-row{display:flex;align-items:center;justify-content:space-between;gap:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:11px;padding:10px 12px;margin-top:8px}
.entrega-alumno{min-width:0}
.entrega-alumno strong{display:block;color:#0f172a;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.entrega-alumno span{display:block;color:#64748b;font-size:11px;margin-top:2px}
.sin-entregas{font-size:12px;color:#64748b;background:#f8fafc;border-radius:10px;padding:10px 12px}

@media(max-width:900px){.cards{grid-template-columns:1fr}.summary-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:560px){.page-wrap{padding:16px}.summary-grid{grid-template-columns:1fr 1fr}.info-grid{grid-template-columns:1fr}}
</style>

</head>
<body>
<div class="dashboard-container">
<aside class="sidebar">
<div class="logo-section"><img src="../img/logo_g.png" alt="Logo Aulamos" class="logo-img"></div>

        <nav class="menu">
            <a href="docente_dashboard.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="crear_recurso.php" class="menu-item"><i class="fa-solid fa-medal"></i> Crear Recurso</a>
            <a href="mis_recursos.php" class="menu-item"><i class="fa-solid fa-folder-open"></i> Mis Recursos</a>
            <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
            <a href="mis_actividades.php" class="menu-item active"><i class="fa-solid fa-list-check"></i> Mis Actividades</a>
            <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluación</a>
            <a href="mis_evaluaciones.php" class="menu-item "><i class="fa-solid fa-file-circle-check"></i> Mis Evaluaciones</a>
            <a href="crear_juego.php" class="menu-item"><i class="fa-solid fa-gamepad"></i> Crear Juego</a>
            <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
            <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
            <a href="pasarlista.php" class="menu-item"><i class="fa-solid fa-bars"></i> Pasar Lista</a>
            <a href="juegos_docente.php" class="menu-item"><i class="fa-solid fa-gamepad"></i> Conecta y Aprende</a>
            <div class="menu-spacer"></div>
            <button class="btn-accessibility-main" onclick="toggleBarraAccesibilidad()">
                <i class="fa-solid fa-universal-access"></i> Accesibilidad
            </button>
            <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión
            </a>
        </nav>

</aside>
<main class="main-content">
<header class="content-header">
<div class="welcome-text">
<h1>Mis actividades</h1>
<p>Consulta las actividades que has creado para tus cursos.</p>
</div>
<div class="header-actions">
<a href="crear_actividad.php" class="btn-assistant" style="text-decoration:none;"><i class="fa-solid fa-plus"></i> Nueva actividad</a>
<a href="mi_perfil_d.php" class="user-profile" style="text-decoration:none;display:flex;align-items:center;gap:10px;">
<img src="<?php echo htmlspecialchars($ruta_foto_docente); ?>" alt="Avatar" class="avatar">
<span class="user-name"><?php echo htmlspecialchars($nombre_docente); ?></span>
</a>
</div>
</header>

<div class="page-wrap">
<div class="summary-grid">
<div class="summary-card"><div class="n"><?php echo $total; ?></div><div class="l">Total de actividades</div></div>
<div class="summary-card"><div class="n"><?php echo $publicadas; ?></div><div class="l">Publicadas</div></div>
<div class="summary-card"><div class="n"><?php echo $borradores; ?></div><div class="l">Borradores</div></div>
<div class="summary-card"><div class="n"><?php echo $vencidas; ?></div><div class="l">Con fecha vencida</div></div>
</div>

<form class="page-tools" method="GET">
<div class="search-box"><i class="fa-solid fa-search"></i><input type="text" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>" placeholder="Buscar por título, materia o curso..."></div>
<div class="filters">
<select name="id_curso" class="filter-select" onchange="this.form.submit()">
<option value="0">Todos los cursos</option>
<?php foreach ($cursos as $curso): ?>
<option value="<?php echo (int)$curso['id_curso']; ?>" <?php echo $id_curso === (int)$curso['id_curso'] ? 'selected' : ''; ?>>
<?php echo htmlspecialchars($curso['materia'].' · '.$curso['nombre'].' · '.$curso['grupo']); ?>
</option>
<?php endforeach; ?>
</select>
<select name="estado" class="filter-select" onchange="this.form.submit()">
<option value="">Todos los estados</option>
<?php foreach (['Publicada','Borrador','Cerrada','Archivada'] as $op): ?>
<option value="<?php echo $op; ?>" <?php echo strcasecmp($estado,$op)===0 ? 'selected' : ''; ?>><?php echo $op; ?></option>
<?php endforeach; ?>
</select>
<button class="btn-mini primary" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
</div>
</form>

<div class="cards">
<?php if (empty($items)): ?>
<div class="empty"><i class="fa-regular fa-clipboard"></i><h3>No hay actividades</h3><p>No se encontraron actividades con los filtros seleccionados.</p><a class="btn-mini primary" href="crear_actividad.php">Crear actividad</a></div>
<?php else: foreach ($items as $item): ?>
<div class="item-card">
<div class="item-top">
<div><h3 class="item-title"><?php echo htmlspecialchars($item['titulo']); ?></h3><p class="meta"><?php echo htmlspecialchars($item['materia'].' · '.$item['curso'].' · '.$item['grupo']); ?></p></div>
<span class="badge <?php echo badgeClase($item['estado']); ?>"><?php echo htmlspecialchars($item['estado']); ?></span>
</div>
<p class="item-desc"><?php echo htmlspecialchars($item['descripcion'] ?: 'Sin descripción.'); ?></p>
<div class="info-grid">
<div class="info"><strong>Tipo</strong><?php echo htmlspecialchars($item['tipo']); ?></div>
<div class="info"><strong>Puntaje</strong><?php echo number_format((float)$item['puntaje_maximo'],2); ?></div>
<div class="info"><strong>Fecha límite</strong><?php echo fechaBonita($item['fecha_limite']); ?></div>
<div class="info"><strong>Estudiantes</strong><?php echo (int)$item['alumnos_asignados']; ?> asignados</div>
</div>
<div class="actions">
<a class="btn-mini primary" href="detalle_actividades.php?id_actividad=<?php echo (int)$item['id_actividad']; ?>"><i class="fa-regular fa-eye"></i> Ver</a>
</div>

<div class="entregas-panel">
    <div class="entregas-panel-title">
        <strong><i class="fa-solid fa-check-double"></i> Entregas</strong>
        <span class="entregas-count"><?php echo (int)$item['entregas_reales']; ?> recibidas</span>
    </div>

    <?php
    $entregasActividad = [];
    if (!empty($item['lista_entregas'])) {
        foreach (explode('###', $item['lista_entregas']) as $registroEntrega) {
            $partes = explode('|||', $registroEntrega);
            if (count($partes) >= 4) {
                $entregasActividad[] = [
                    'id_estudiante' => (int)$partes[0],
                    'nombre' => trim($partes[1]),
                    'id_entrega' => (int)$partes[2],
                    'estado' => trim($partes[3]),
                ];
            }
        }
    }
    ?>

    <?php if (empty($entregasActividad)): ?>
        <div class="sin-entregas">
            <i class="fa-regular fa-clock"></i>
            Aún no hay entregas para esta actividad.
        </div>
    <?php else: ?>
        <?php foreach ($entregasActividad as $entregaActividad): ?>
            <div class="entrega-row">
                <div class="entrega-alumno">
                    <strong><?php echo htmlspecialchars($entregaActividad['nombre'] ?: 'Alumno'); ?></strong>
                    <span><?php echo htmlspecialchars($entregaActividad['estado']); ?></span>
                </div>
                <a
                    class="btn-mini <?php echo strcasecmp($entregaActividad['estado'], 'Calificada') === 0 ? '' : 'primary'; ?>"
                    href="calificar_entrega.php?id_actividad=<?php echo (int)$item['id_actividad']; ?>&id_estudiante=<?php echo (int)$entregaActividad['id_estudiante']; ?>&id_curso=<?php echo (int)$item['id_curso']; ?>"
                >
                    <i class="fa-solid <?php echo strcasecmp($entregaActividad['estado'], 'Calificada') === 0 ? 'fa-pen-to-square' : 'fa-star'; ?>"></i>
                    <?php echo strcasecmp($entregaActividad['estado'], 'Calificada') === 0 ? 'Ver / editar' : 'Calificar'; ?>
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</div>
<?php endforeach; endif; ?>
</div>
</div>

<?php include '../Accesibilidad/accesibilidad.php'; ?>
</main>
</div>
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i></button>
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>
</body>
</html>