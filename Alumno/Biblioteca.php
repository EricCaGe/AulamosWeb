<?php
session_start();

// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

// =============================================
// VERIFICAR SESIÓN DEL ALUMNO
// =============================================
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Alumno') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_usuario = (int) $_SESSION['usuario']['id_usuario'];

// =============================================
// FILTRO DE MATERIA
// =============================================
$filtroMateria = $_GET['materia'] ?? 'todas';

// =============================================
// MATERIAS DE LOS CURSOS DEL ALUMNO
// =============================================
$stmt = $conexion->prepare("
    SELECT DISTINCT m.campo_formativo
    FROM inscripciones i
    INNER JOIN cursos c ON c.id_curso = i.id_curso
    INNER JOIN materias m ON m.id_materia = c.id_materia
    WHERE i.id_alumno = ? AND i.estado = 'Activo' AND c.estado = 'Activo' AND m.estado = 'Activa'
    ORDER BY m.campo_formativo
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$materias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// =============================================
// RECURSOS DISPONIBLES PARA EL ALUMNO (CORREGIDO)
// =============================================
$sqlRecursos = "
    SELECT DISTINCT
        r.id_recurso,
        r.titulo,
        r.descripcion,
        r.tipo,
        r.url_recurso,
        r.url_subtitulos,
        r.accesible,
        r.subtitulos_disponibles,
        r.fecha_publicacion,
        m.nombre AS materia,
        m.campo_formativo,
        c.nombre AS curso,
        CONCAT_WS(' ', u.nombre, u.apellido_paterno) AS docente
    FROM recursos_educativos r
    LEFT JOIN materias m ON m.id_materia = r.id_materia
    LEFT JOIN cursos c ON c.id_curso = r.id_curso
    LEFT JOIN usuarios u ON u.id_usuario = r.id_docente
    WHERE r.estado = 'Activo'
      AND r.url_recurso IS NOT NULL
      AND r.url_recurso != ''
      AND (
            -- Recursos públicos: visibles para todos
            r.compartido_tipo = 'Publico'
            
            -- Recursos de curso: solo si el alumno está inscrito en ese curso
            OR (
                r.compartido_tipo = 'Curso'
                AND r.id_curso IS NOT NULL
                AND EXISTS (
                    SELECT 1
                    FROM inscripciones i
                    WHERE i.id_alumno = ?
                      AND i.id_curso = r.id_curso
                      AND i.estado = 'Activo'
                )
            )
            
            -- Recursos de grupo: solo si el alumno está en el grupo del curso
            OR (
                r.compartido_tipo = 'Grupo'
                AND r.id_curso IS NOT NULL
                AND EXISTS (
                    SELECT 1
                    FROM inscripciones i
                    INNER JOIN cursos c2 ON c2.id_curso = i.id_curso
                    INNER JOIN grupos g ON g.id_grupo = c2.id_grupo
                    WHERE i.id_alumno = ?
                      AND i.id_curso = r.id_curso
                      AND i.estado = 'Activo'
                )
            )
      )
";

$usarFiltroMateria = $filtroMateria !== 'todas' && $filtroMateria !== '';
if ($usarFiltroMateria) {
    $sqlRecursos .= " AND m.campo_formativo = ? ";
}
$sqlRecursos .= " ORDER BY r.fecha_publicacion DESC, r.id_recurso DESC ";

$stmt = $conexion->prepare($sqlRecursos);
if ($usarFiltroMateria) {
    $stmt->bind_param("iis", $id_usuario, $id_usuario, $filtroMateria);
} else {
    $stmt->bind_param("ii", $id_usuario, $id_usuario);
}
$stmt->execute();
$recursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// =============================================
// ICONOS POR TIPO DE RECURSO
// =============================================
$tiposMap = [
    'video' => ['icono' => 'fa-solid fa-video', 'label' => 'Video'],
    'pdf' => ['icono' => 'fa-solid fa-file-pdf', 'label' => 'PDF'],
    'imagen' => ['icono' => 'fa-solid fa-image', 'label' => 'Imagen'],
    'audio' => ['icono' => 'fa-solid fa-music', 'label' => 'Audio'],
    'enlace' => ['icono' => 'fa-solid fa-link', 'label' => 'Enlace'],
    'presentación' => ['icono' => 'fa-solid fa-presentation-screen', 'label' => 'Presentación'],
    'documento' => ['icono' => 'fa-solid fa-file-lines', 'label' => 'Documento']
];

$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca Digital - Aulamos</title>
    
    <link rel="stylesheet" href="Style/Inicio.css">
    <link rel="stylesheet" href="Style/Biblioteca.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- NUEVA ACCESIBILIDAD -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    
    <style>
        .filtros-materia {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 20px 0;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .filtros-materia a {
            padding: 8px 16px;
            border-radius: 20px;
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .filtros-materia a:hover {
            background: #e2e8f0;
        }
        .filtros-materia a.activo {
            background: #8b5cf6;
            color: white;
        }
        .grid-recursos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .recurso-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: none;
            text-align: left;
            cursor: pointer;
            width: 100%;
        }
        .recurso-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .recurso-card:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        .recurso-icono {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #f3e8ff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }
        .recurso-icono i {
            font-size: 24px;
            color: #8b5cf6;
        }
        .recurso-titulo {
            font-weight: 600;
            font-size: 16px;
            color: #1a1a2e;
            margin-bottom: 4px;
        }
        .recurso-materia {
            font-size: 13px;
            color: #8b5cf6;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .recurso-tipo {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
            margin-bottom: 8px;
        }
        .recurso-descripcion {
            font-size: 13px;
            color: #64748b;
            margin: 8px 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .recurso-detalle {
            font-size: 12px;
            color: #94a3b8;
            margin: 2px 0;
        }
        .recurso-detalle i {
            width: 14px;
            color: #8b5cf6;
        }
        .recurso-abrir {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #8b5cf6;
            font-weight: 500;
        }
        .recurso-abrir i {
            margin-right: 4px;
        }
        .sin-recursos {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }
        .sin-recursos i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 16px;
        }
        .sin-recursos h3 {
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        .sin-recursos p {
            color: #64748b;
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
            <a href="alumno.php" class="menu-item"><i class="fa-solid fa-house"></i> Inicio</a>
            <a href="actividades.php" class="menu-item"><i class="fa-solid fa-cubes"></i> Mis actividades</a>
            <a href="Biblioteca.php" class="menu-item active"><i class="fa-solid fa-book-open"></i> Biblioteca digital</a>
            <a href="avances.php" class="menu-item"><i class="fa-solid fa-pen-to-square"></i> Mis avances</a>
            <a href="ayuda.php" class="menu-item"><i class="fa-solid fa-circle-question"></i> Ayuda</a>
        </nav>
        <button class="btn-accessibility-main" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
        <div class="menu-spacer"></div>
        <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
    </aside>

    <!-- CONTENIDO -->
    <main class="main-content">

        <header class="content-header">
            <div class="welcome-text">
                <h1>Biblioteca Digital</h1>
                <p>Explora los recursos compartidos en tus cursos.</p>
            </div>
            <div class="header-actions">
                <button class="btn-assistant" id="btnAsistente" onclick="window.location.href='../Alumno/ChatbotAlumno.php?rol=alumno'">
                    Asistente Virtual <span class="robot-icon">🤖</span>
                </button>
                <div class="icon-bell"><i class="fa-regular fa-bell"></i></div>
            </div>
        </header>

        <!-- FILTROS -->
        <div class="filtros-materia">
            <a href="?materia=todas" class="<?php echo $filtroMateria === 'todas' ? 'activo' : ''; ?>">Todas</a>
            <?php foreach ($materias as $materia): 
                $campo = $materia['campo_formativo'];
            ?>
                <a href="?materia=<?php echo urlencode($campo); ?>" class="<?php echo $filtroMateria === $campo ? 'activo' : ''; ?>">
                    <?php echo htmlspecialchars($campo); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- RECURSOS -->
        <?php if (count($recursos) > 0): ?>
            <div class="grid-recursos" id="gridRecursos">
                <?php foreach ($recursos as $recurso): 
                    $tipo = strtolower($recurso['tipo']);
                    $icono = $tiposMap[$tipo]['icono'] ?? 'fa-solid fa-file';
                    $label = $tiposMap[$tipo]['label'] ?? ucfirst($tipo);
                    $tieneArchivo = !empty($recurso['url_recurso']);
                ?>
                    <button type="button" class="recurso-card js-abrir-recurso"
                        data-url="<?php echo htmlspecialchars($recurso['url_recurso'] ?? ''); ?>"
                        data-titulo="<?php echo htmlspecialchars($recurso['titulo']); ?>"
                        <?php echo !$tieneArchivo ? 'disabled' : ''; ?>
                    >
                        <div class="recurso-icono">
                            <i class="<?php echo htmlspecialchars($icono); ?>"></i>
                        </div>
                        <div class="recurso-titulo"><?php echo htmlspecialchars($recurso['titulo']); ?></div>
                        <?php if (!empty($recurso['materia'])): ?>
                            <div class="recurso-materia"><?php echo htmlspecialchars($recurso['materia']); ?></div>
                        <?php endif; ?>
                        <span class="recurso-tipo"><?php echo htmlspecialchars($label); ?></span>
                        <?php if (!empty($recurso['descripcion'])): ?>
                            <div class="recurso-descripcion"><?php echo htmlspecialchars($recurso['descripcion']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($recurso['curso'])): ?>
                            <div class="recurso-detalle"><i class="fa-solid fa-book"></i> <?php echo htmlspecialchars($recurso['curso']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($recurso['docente'])): ?>
                            <div class="recurso-detalle"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($recurso['docente']); ?></div>
                        <?php endif; ?>
                        <div class="recurso-abrir">
                            <?php if ($tieneArchivo): ?>
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir recurso
                            <?php else: ?>
                                Sin archivo disponible
                            <?php endif; ?>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="sin-recursos">
                <i class="fa-regular fa-folder-open"></i>
                <h3>No hay recursos disponibles</h3>
                <p>No se encontraron recursos para tus cursos o con el filtro seleccionado.</p>
            </div>
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
<script src="js/Inicio.js"></script>
<script src="js/Biblioteca.js"></script>

<!-- NUEVA ACCESIBILIDAD -->
<script src="../Accesibilidad/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>

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