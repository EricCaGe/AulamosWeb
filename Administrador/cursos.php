<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';
require_once 'includes/preferencias.php';

$nombre_admin = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
$pagina_actual = basename($_SERVER['PHP_SELF']);

// ========================================== */
// CONSULTAS A LA BD                          */
// ========================================== */

// Total de cursos
$resultado = $conexion->query("SELECT COUNT(*) AS total FROM cursos");
$total_cursos = $resultado->fetch_assoc()['total'] ?? 0;

// Cursos activos
$resultado = $conexion->query("SELECT COUNT(*) AS total FROM cursos WHERE estado = 'Activo'");
$cursos_activos = $resultado->fetch_assoc()['total'] ?? 0;

// Lista de todos los cursos con sus relaciones
$cursos = $conexion->query("
    SELECT 
        c.id_curso,
        c.nombre,
        c.descripcion,
        c.estado,
        m.nombre AS materia,
        g.nombre AS grupo,
        CONCAT(u.nombre, ' ', u.apellido_paterno) AS docente,
        ce.nombre AS ciclo
    FROM cursos c
    INNER JOIN materias m ON c.id_materia = m.id_materia
    INNER JOIN grupos g ON c.id_grupo = g.id_grupo
    INNER JOIN usuarios u ON c.id_docente = u.id_usuario
    INNER JOIN ciclos_escolares ce ON c.id_ciclo = ce.id_ciclo
    ORDER BY c.nombre
")->fetch_all(MYSQLI_ASSOC);

// Obtener datos para el modal
// Ciclos activos
$ciclos = $conexion->query("SELECT id_ciclo, nombre FROM ciclos_escolares WHERE estado = 'Activo' ORDER BY fecha_inicio DESC")->fetch_all(MYSQLI_ASSOC);

// Grupos activos
$grupos = $conexion->query("SELECT id_grupo, nombre FROM grupos WHERE estado = 'Activo' ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Materias activas
$materias = $conexion->query("SELECT id_materia, nombre FROM materias WHERE estado = 'Activa' ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Docentes (con rol Docente)
$docentes = $conexion->query("
    SELECT u.id_usuario, CONCAT(u.nombre, ' ', u.apellido_paterno) AS nombre_completo
    FROM usuarios u
    INNER JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
    WHERE ur.id_rol = 2 AND u.estado = 'Activo'
    ORDER BY u.nombre
")->fetch_all(MYSQLI_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
$tipo = $_GET['tipo'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $idioma_actual === 'es' ? 'es' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('cursos'); ?> - Administrador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="styles/cursos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- ✅ NUEVA ACCESIBILIDAD -->
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
</head>
<body class="<?php echo $clases_body; ?>">

<div class="dashboard-container">
    
    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logogeneral.png" alt="Logo Aulamos" class="logo">
        </div>
        
        <nav class="menu">
            <a href="admin_dashboard.php" class="menu-item <?php echo ($pagina_actual == 'admin_dashboard.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-house"></i> <?php echo __('dashboard'); ?>
            </a>
            <a href="ciclos_escolares.php" class="menu-item <?php echo ($pagina_actual == 'ciclos_escolares.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar"></i> <?php echo __('ciclos'); ?>
            </a>
            <a href="periodos.php" class="menu-item <?php echo ($pagina_actual == 'periodos.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock"></i> <?php echo __('periodos'); ?>
            </a>
            <a href="materias.php" class="menu-item <?php echo ($pagina_actual == 'materias.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-book"></i> <?php echo __('materias'); ?>
            </a>
            <a href="grupos.php" class="menu-item <?php echo ($pagina_actual == 'grupos.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-layer-group"></i> <?php echo __('grupos'); ?>
            </a>
            <a href="cursos.php" class="menu-item <?php echo ($pagina_actual == 'cursos.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-cubes"></i> <?php echo __('cursos'); ?>
            </a>
            <a href="inscripciones.php" class="menu-item <?php echo ($pagina_actual == 'inscripciones.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-pen-to-square"></i> <?php echo __('inscripciones'); ?>
            </a>
            <a href="configuracion.php" class="menu-item <?php echo ($pagina_actual == 'configuracion.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gear"></i> <?php echo __('configuracion'); ?>
            </a>
        </nav>
        
        <!-- ✅ BOTÓN ACCESIBILIDAD NUEVO -->
        <button class="btn-accesibilidad-header" id="btnAccesibilidad" onclick="toggleBarraAccesibilidad()" style="width:100%; margin-top:20px; background:#5a189a; color:white; border:none; padding:12px; border-radius:10px; cursor:pointer; font-weight:bold;">
            <i class="fa-solid fa-universal-access"></i> <?php echo __('accesibilidad'); ?>
        </button>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        
        <!-- ENCABEZADO -->
        <header class="content-header">
            <div class="welcome-text">
                <h1><?php echo __('cursos'); ?></h1>
                <p><?php echo __('administra_cursos'); ?></p>
            </div>
            <div class="header-actions">
                <div class="icon-bell">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <button class="btn-idioma" id="btnIdioma" title="Cambiar idioma">
                    <i class="fa-solid fa-language"></i>
                    <span id="idiomaTexto"><?php echo $idioma_actual === 'es' ? 'ES' : 'EN'; ?></span>
                </button>
                <a href="perfil.php" class="user-profile" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <img src="https://placehold.co/40x40/3b71f3/white?text=👤" alt="Avatar Admin" class="avatar">
                    <span class="user-name"><?php echo htmlspecialchars($nombre_admin); ?></span>
                    <i class="fa-solid fa-chevron-down drop-icon"></i>
                </a>
                <a href="../InicioSesion/cerrar_sesion.php" class="btn-logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <!-- MENSAJES -->
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo; ?>" style="padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; <?php echo ($tipo === 'exito') ? 'background: #dcfce7; color: #166534; border-left: 4px solid #22c55e;' : 'background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626;'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- RESUMEN DE CURSOS                          -->
        <!-- ========================================== -->
        <section class="resumen-cursos">
            <div class="stats-row">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $total_cursos; ?></span>
                    <span class="stat-label"><?php echo __('total'); ?></span>
                </div>
                <div class="stat-card stat-activa">
                    <span class="stat-number"><?php echo $cursos_activos; ?></span>
                    <span class="stat-label"><?php echo __('activos'); ?></span>
                </div>
                <button class="btn-nuevo-curso" id="btnNuevoCurso">
                    <i class="fa-solid fa-plus"></i> <?php echo __('nuevo_curso'); ?>
                </button>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- BÚSQUEDA                                  -->
        <!-- ========================================== -->
        <section class="busqueda-cursos">
            <div class="busqueda-container">
                <i class="fa-solid fa-search"></i>
                <input type="text" placeholder="<?php echo __('buscar_curso'); ?>" class="input-busqueda" id="buscarCurso">
            </div>
        </section>

        <!-- ========================================== -->
        <!-- LISTA DE CURSOS                            -->
        <!-- ========================================== -->
        <section class="lista-cursos">
            <div class="cursos-header">
                <h3><?php echo __('cursos_registrados'); ?></h3>
                <span class="resultados" id="totalResultados"><?php echo count($cursos); ?> <?php echo __('resultados'); ?></span>
            </div>

            <div class="cursos-grid" id="cursosGrid">
                <?php if (empty($cursos)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-cubes"></i>
                        <h4><?php echo __('sin_cursos'); ?></h4>
                        <p><?php echo __('crear_primer_curso'); ?></p>
                        <button class="btn-agregar-empty" id="btnNuevoCursoEmpty">
                            <i class="fa-solid fa-plus"></i> <?php echo __('crear_curso'); ?>
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($cursos as $curso): ?>
                    <div class="curso-card" data-estado="<?php echo $curso['estado']; ?>">
                        <div class="curso-header">
                            <div>
                                <h4 class="curso-nombre"><?php echo htmlspecialchars($curso['nombre']); ?></h4>
                                <span class="badge <?php echo ($curso['estado'] === 'Activo') ? 'badge-activo' : 'badge-inactivo'; ?>">
                                    <?php echo $curso['estado']; ?>
                                </span>
                            </div>
                        </div>

                        <p class="curso-descripcion"><?php echo htmlspecialchars($curso['descripcion'] ?? 'Sin descripción'); ?></p>

                        <div class="curso-detalles">
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('materia'); ?>:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($curso['materia']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('grupo'); ?>:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($curso['grupo']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('docente'); ?>:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($curso['docente']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('ciclo'); ?>:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($curso['ciclo']); ?></span>
                            </div>
                        </div>

                        <div class="curso-acciones">
                            <button class="btn-editar" data-id="<?php echo $curso['id_curso']; ?>">
                                <i class="fa-regular fa-pen-to-square"></i> <?php echo __('editar'); ?>
                            </button>
                            <?php if ($curso['estado'] !== 'Finalizado'): ?>
                            <button class="btn-deshabilitar" data-id="<?php echo $curso['id_curso']; ?>">
                                <i class="fa-solid fa-eye-slash"></i> <?php echo __('deshabilitar'); ?>
                            </button>
                            <?php endif; ?>
                            <button class="btn-eliminar" data-id="<?php echo $curso['id_curso']; ?>">
                                <i class="fa-regular fa-trash-can"></i> <?php echo __('eliminar'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MODAL PARA NUEVO / EDITAR CURSO           -->
        <!-- ========================================== -->
        <div class="modal-overlay" id="modalCurso">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitulo"><?php echo __('nuevo_curso'); ?></h2>
                    <button class="modal-cerrar" id="modalCerrar">&times;</button>
                </div>
                <form id="formCurso" method="POST" action="logica/procesar_cursos.php">
                    <input type="hidden" name="accion" id="modalAccion" value="guardar">
                    <input type="hidden" name="id" id="modalId" value="">

                    <!-- Ciclo escolar -->
                    <div class="form-group">
                        <label><?php echo __('ciclo_escolar'); ?> <span class="text-danger">*</span></label>
                        <div class="radio-group radio-inline" id="cicloOptions">
                            <?php if (empty($ciclos)): ?>
                                <p style="color: #94a3b8; font-style: italic;"><?php echo __('sin_ciclos'); ?></p>
                            <?php else: ?>
                                <?php foreach ($ciclos as $index => $ciclo): ?>
                                <label>
                                    <input type="radio" name="id_ciclo" value="<?php echo $ciclo['id_ciclo']; ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($ciclo['nombre']); ?>
                                </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Grupo -->
                    <div class="form-group">
                        <label for="modalGrupo"><?php echo __('grupo'); ?> <span class="text-danger">*</span></label>
                        <select id="modalGrupo" name="id_grupo" required>
                            <option value="">-- <?php echo __('seleccionar_grupo'); ?> --</option>
                            <?php foreach ($grupos as $grupo): ?>
                            <option value="<?php echo $grupo['id_grupo']; ?>">
                                <?php echo htmlspecialchars($grupo['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Materia -->
                    <div class="form-group">
                        <label><?php echo __('materia'); ?> <span class="text-danger">*</span></label>
                        <div class="radio-group radio-inline" id="materiaOptions">
                            <?php if (empty($materias)): ?>
                                <p style="color: #94a3b8; font-style: italic;"><?php echo __('sin_materias'); ?></p>
                            <?php else: ?>
                                <?php foreach ($materias as $index => $materia): ?>
                                <label>
                                    <input type="radio" name="id_materia" value="<?php echo $materia['id_materia']; ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($materia['nombre']); ?>
                                </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Docente -->
                    <div class="form-group">
                        <label><?php echo __('docente'); ?> <span class="text-danger">*</span></label>
                        <div class="radio-group radio-inline" id="docenteOptions">
                            <?php if (empty($docentes)): ?>
                                <p style="color: #94a3b8; font-style: italic;"><?php echo __('sin_docentes'); ?></p>
                            <?php else: ?>
                                <?php foreach ($docentes as $index => $docente): ?>
                                <label>
                                    <input type="radio" name="id_docente" value="<?php echo $docente['id_usuario']; ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($docente['nombre_completo']); ?>
                                </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Nombre del curso -->
                    <div class="form-group">
                        <label for="modalNombre"><?php echo __('nombre_curso'); ?> <span class="text-danger">*</span></label>
                        <input type="text" id="modalNombre" name="nombre" placeholder="<?php echo __('ej_curso'); ?>" required>
                    </div>

                    <!-- Descripción -->
                    <div class="form-group">
                        <label for="modalDescripcion"><?php echo __('descripcion'); ?></label>
                        <textarea id="modalDescripcion" name="descripcion" rows="3" placeholder="<?php echo __('descripcion_curso'); ?>"></textarea>
                        <p class="contador-caracteres"><span id="modalContador">0</span>/1000</p>
                    </div>

                    <!-- Estado -->
                    <div class="form-group">
                        <label><?php echo __('estado'); ?></label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="estado" value="Activo" checked>
                                <i class="fa-solid fa-circle-check"></i> <?php echo __('activo'); ?>
                            </label>
                            <label>
                                <input type="radio" name="estado" value="Inactivo">
                                <i class="fa-solid fa-circle-xmark"></i> <?php echo __('inactivo'); ?>
                            </label>
                            <label>
                                <input type="radio" name="estado" value="Finalizado">
                                <i class="fa-solid fa-lock"></i> <?php echo __('finalizado'); ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-cancelar" id="modalCancelar"><?php echo __('cancelar'); ?></button>
                        <button type="submit" class="btn-guardar"><?php echo __('guardar'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ✅ NUEVA BARRA DE ACCESIBILIDAD (ELIMINADA LA VIEJA) -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </main>
</div>

<!-- ✅ BOTÓN FLOTANTE -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<script src="js/admin.js"></script>
<script src="js/cursos.js"></script>

<!-- ✅ NUEVA ACCESIBILIDAD JS -->
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>