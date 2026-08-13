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

// =====================================================
// TOKEN CSRF
// =====================================================
if (empty($_SESSION['csrf_mis_recursos'])) {
    $_SESSION['csrf_mis_recursos'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_mis_recursos'];

$mensaje = '';
$tipo_mensaje = '';

// =====================================================
// CAMBIAR ESTADO DEL RECURSO
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';
    $id_recurso = (int) ($_POST['id_recurso'] ?? 0);
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';

    $estados_permitidos = ['Activo', 'Inactivo', 'Archivado'];

    if (!hash_equals($csrf, $token)) {
        $mensaje = 'La solicitud no es válida. Recarga la página e inténtalo nuevamente.';
        $tipo_mensaje = 'error';
    } elseif ($id_recurso <= 0 || !in_array($nuevo_estado, $estados_permitidos, true)) {
        $mensaje = 'Los datos enviados no son válidos.';
        $tipo_mensaje = 'error';
    } else {
        $stmt = $conexion->prepare("
            UPDATE recursos_educativos
            SET estado = ?
            WHERE id_recurso = ?
              AND id_docente = ?
        ");
        $stmt->bind_param("sii", $nuevo_estado, $id_recurso, $id_docente);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $mensaje = '✅ El estado del recurso se actualizó correctamente.';
            $tipo_mensaje = 'exito';
        } else {
            $mensaje = 'No fue necesario realizar cambios o el recurso no pertenece al docente.';
            $tipo_mensaje = 'info';
        }
        $stmt->close();
    }
}

// =====================================================
// FILTRO DE ESTADO
// =====================================================
$filtroEstado = $_GET['estado'] ?? 'Todos';
$estadosFiltro = ['Todos', 'Activo', 'Inactivo', 'Archivado'];

if (!in_array($filtroEstado, $estadosFiltro, true)) {
    $filtroEstado = 'Todos';
}

// =====================================================
// CONSULTAR RECURSOS DEL DOCENTE
// =====================================================
$sql = "
    SELECT
        r.id_recurso,
        r.titulo,
        r.descripcion,
        r.tipo,
        r.url_recurso,
        r.accesible,
        r.compartido_tipo,
        r.estado,
        r.fecha_publicacion,
        m.nombre AS materia,
        c.nombre AS curso
    FROM recursos_educativos r
    LEFT JOIN materias m ON m.id_materia = r.id_materia
    LEFT JOIN cursos c ON c.id_curso = r.id_curso
    WHERE r.id_docente = ?
";

if ($filtroEstado !== 'Todos') {
    $sql .= " AND r.estado = ? ";
}

$sql .= "
    ORDER BY
        CASE r.estado
            WHEN 'Activo' THEN 1
            WHEN 'Inactivo' THEN 2
            WHEN 'Archivado' THEN 3
            ELSE 4
        END,
        r.fecha_publicacion DESC,
        r.id_recurso DESC
";

$stmt = $conexion->prepare($sql);
if ($filtroEstado !== 'Todos') {
    $stmt->bind_param("is", $id_docente, $filtroEstado);
} else {
    $stmt->bind_param("i", $id_docente);
}
$stmt->execute();
$recursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// =====================================================
// CONTADORES
// =====================================================
$stmt = $conexion->prepare("
    SELECT
        SUM(estado = 'Activo') AS activos,
        SUM(estado = 'Inactivo') AS inactivos,
        SUM(estado = 'Archivado') AS archivados,
        COUNT(*) AS total
    FROM recursos_educativos
    WHERE id_docente = ?
");
$stmt->bind_param("i", $id_docente);
$stmt->execute();
$conteos = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total = (int) ($conteos['total'] ?? 0);
$activos = (int) ($conteos['activos'] ?? 0);
$inactivos = (int) ($conteos['inactivos'] ?? 0);
$archivados = (int) ($conteos['archivados'] ?? 0);

function iconoTipo($tipo)
{
    switch (strtolower($tipo ?? '')) {
        case 'pdf':
            return 'fa-solid fa-file-pdf';
        case 'video':
            return 'fa-solid fa-video';
        case 'audio':
            return 'fa-solid fa-volume-high';
        case 'imagen':
            return 'fa-solid fa-image';
        case 'enlace':
            return 'fa-solid fa-link';
        case 'presentación':
            return 'fa-solid fa-display';
        default:
            return 'fa-solid fa-file-lines';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Recursos</title>
    
    <link rel="stylesheet" href="styles/docente.css">
    <link rel="stylesheet" href="styles/recursos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- NUEVA ACCESIBILIDAD -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    
    <style>
        .mensaje {
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .mensaje-exito {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        .mensaje-error {
            background: #fecaca;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .mensaje-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        .btn-recurso.btn-abrir {
            background: #8b5cf6;
            color: white;
        }
        .btn-recurso.btn-abrir:hover {
            background: #7c3aed;
        }
        .btn-recurso.btn-activar {
            background: #22c55e;
            color: white;
        }
        .btn-recurso.btn-activar:hover {
            background: #16a34a;
        }
        .btn-recurso.btn-inactivar {
            background: #f59e0b;
            color: white;
        }
        .btn-recurso.btn-inactivar:hover {
            background: #d97706;
        }
        .btn-recurso.btn-archivar {
            background: #6b7280;
            color: white;
        }
        .btn-recurso.btn-archivar:hover {
            background: #4b5563;
        }
        .recurso-acciones form {
            display: inline;
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logo_g.png" alt="Logo Aulamos" class="logo-img">
        </div>
        <nav class="menu">
            <a href="docente_dashboard.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="crear_recurso.php" class="menu-item"><i class="fa-solid fa-circle-plus"></i> Crear Recurso</a>
            <a href="mis_recursos.php" class="menu-item active"><i class="fa-solid fa-folder-open"></i> Mis Recursos</a>
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

    <!-- CONTENIDO -->
    <main class="main-content recursos-main">

        <header class="recursos-header">
            <div>
                <h1>Mis Recursos</h1>
                <p>Administra los recursos que has publicado desde Aulamos Web o la aplicación móvil.</p>
            </div>
            <a href="crear_recurso.php" class="btn-crear-recurso">
                <i class="fa-solid fa-plus"></i> Crear recurso
            </a>
        </header>

        <?php if ($mensaje !== ''): ?>
            <div class="mensaje mensaje-<?php echo htmlspecialchars($tipo_mensaje); ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- RESUMEN -->
        <section class="resumen-recursos">
            <div class="resumen-card">
                <span>Total</span>
                <strong><?php echo $total; ?></strong>
            </div>
            <div class="resumen-card">
                <span>Activos</span>
                <strong><?php echo $activos; ?></strong>
            </div>
            <div class="resumen-card">
                <span>Inactivos</span>
                <strong><?php echo $inactivos; ?></strong>
            </div>
            <div class="resumen-card">
                <span>Archivados</span>
                <strong><?php echo $archivados; ?></strong>
            </div>
        </section>

        <!-- FILTROS -->
        <section class="filtros-recursos">
            <?php $filtros = ['Todos', 'Activo', 'Inactivo', 'Archivado']; ?>
            <?php foreach ($filtros as $filtro): ?>
                <a href="?estado=<?php echo urlencode($filtro); ?>" class="filtro-recurso <?php echo $filtroEstado === $filtro ? 'activo' : ''; ?>">
                    <?php echo htmlspecialchars($filtro); ?>
                </a>
            <?php endforeach; ?>
        </section>

        <!-- GRID -->
        <?php if (count($recursos) > 0): ?>
            <section class="grid-mis-recursos">
                <?php foreach ($recursos as $recurso): ?>
                    <article class="mis-recurso-card">
                        <div class="recurso-card-header">
                            <div class="recurso-icon">
                                <i class="<?php echo iconoTipo($recurso['tipo']); ?>"></i>
                            </div>
                            <span class="estado-badge estado-<?php echo strtolower($recurso['estado']); ?>">
                                <?php echo htmlspecialchars($recurso['estado']); ?>
                            </span>
                        </div>
                        <h3><?php echo htmlspecialchars($recurso['titulo']); ?></h3>
                        <div class="recurso-meta">
                            <span><i class="fa-solid fa-file"></i> <?php echo htmlspecialchars($recurso['tipo']); ?></span>
                            <?php if (!empty($recurso['materia'])): ?>
                                <span><i class="fa-solid fa-book"></i> <?php echo htmlspecialchars($recurso['materia']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($recurso['curso'])): ?>
                                <span><i class="fa-solid fa-graduation-cap"></i> <?php echo htmlspecialchars($recurso['curso']); ?></span>
                            <?php endif; ?>
                            <span><i class="fa-solid fa-share-nodes"></i> <?php echo htmlspecialchars($recurso['compartido_tipo']); ?></span>
                        </div>
                        <?php if (!empty($recurso['descripcion'])): ?>
                            <p class="recurso-descripcion"><?php echo htmlspecialchars($recurso['descripcion']); ?></p>
                        <?php endif; ?>
                        <div class="recurso-fecha">Publicado: <?php echo date('d/m/Y H:i', strtotime($recurso['fecha_publicacion'])); ?></div>
                        <div class="recurso-acciones">
                            <?php if (!empty($recurso['url_recurso'])): ?>
                                <button type="button" class="btn-recurso btn-abrir js-abrir-recurso" data-url="<?php echo htmlspecialchars($recurso['url_recurso']); ?>">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir
                                </button>
                            <?php endif; ?>
                            <?php if ($recurso['estado'] !== 'Activo'): ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                    <input type="hidden" name="id_recurso" value="<?php echo (int) $recurso['id_recurso']; ?>">
                                    <input type="hidden" name="nuevo_estado" value="Activo">
                                    <button type="submit" class="btn-recurso btn-activar"><i class="fa-solid fa-circle-check"></i> Activar</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($recurso['estado'] === 'Activo'): ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                    <input type="hidden" name="id_recurso" value="<?php echo (int) $recurso['id_recurso']; ?>">
                                    <input type="hidden" name="nuevo_estado" value="Inactivo">
                                    <button type="submit" class="btn-recurso btn-inactivar"><i class="fa-solid fa-pause"></i> Inactivar</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($recurso['estado'] !== 'Archivado'): ?>
                                <form method="POST" onsubmit="return confirm('¿Archivar este recurso?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                    <input type="hidden" name="id_recurso" value="<?php echo (int) $recurso['id_recurso']; ?>">
                                    <input type="hidden" name="nuevo_estado" value="Archivado">
                                    <button type="submit" class="btn-recurso btn-archivar"><i class="fa-solid fa-box-archive"></i> Archivar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php else: ?>
            <section class="sin-recursos">
                <i class="fa-regular fa-folder-open"></i>
                <h3>No hay recursos</h3>
                <p>No encontramos recursos con el filtro seleccionado.</p>
            </section>
        <?php endif; ?>

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
(function () {
    'use strict';

    function construirUrl(ruta) {
        if (!ruta) return null;
        let limpia = String(ruta).trim().replace(/\\/g, '/');
        if (/^https?:\/\//i.test(limpia)) return limpia;
        if (limpia.startsWith('/')) {
            return window.location.origin + limpia;
        }
        return window.location.origin + '/' + limpia.replace(/^\.\.\//, '');
    }

    document.querySelectorAll('.js-abrir-recurso').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const url = construirUrl(this.dataset.url);
            if (!url) {
                alert('No se puede abrir el recurso: URL no disponible.');
                return;
            }
            window.open(url, '_blank', 'noopener,noreferrer');
        });
    });

})();
</script>

</body>
</html>