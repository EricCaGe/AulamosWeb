<?php
session_start();
// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

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

// Total de inscripciones
$resultado = $conexion->query("SELECT COUNT(*) AS total FROM inscripciones");
$total_inscripciones = $resultado->fetch_assoc()['total'] ?? 0;

// Inscripciones activas
$resultado = $conexion->query("SELECT COUNT(*) AS total FROM inscripciones WHERE estado = 'Activo'");
$inscripciones_activas = $resultado->fetch_assoc()['total'] ?? 0;

// Lista de inscripciones con detalles
$inscripciones = $conexion->query("
    SELECT 
        i.id_inscripcion,
        CONCAT(u.nombre, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS estudiante,
        u.correo,
        c.nombre AS curso,
        m.nombre AS materia,
        g.nombre AS grupo,
        ce.nombre AS ciclo,
        i.fecha_inscripcion AS fecha,
        i.estado
    FROM inscripciones i
    INNER JOIN usuarios u ON i.id_alumno = u.id_usuario
    INNER JOIN cursos c ON i.id_curso = c.id_curso
    INNER JOIN materias m ON c.id_materia = m.id_materia
    INNER JOIN grupos g ON c.id_grupo = g.id_grupo
    INNER JOIN ciclos_escolares ce ON c.id_ciclo = ce.id_ciclo
    ORDER BY i.fecha_inscripcion DESC
")->fetch_all(MYSQLI_ASSOC);

// ========================================== */
// DATOS PARA EL MODAL                        */
// ========================================== */

// Estudiantes (usuarios con rol Alumno)
$estudiantes = $conexion->query("
    SELECT u.id_usuario, CONCAT(u.nombre, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS nombre_completo
    FROM usuarios u
    INNER JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
    WHERE ur.id_rol = 1 AND u.estado = 'Activo'
    ORDER BY u.nombre
")->fetch_all(MYSQLI_ASSOC);

// Cursos activos
$cursos = $conexion->query("
    SELECT c.id_curso, 
           CONCAT(c.nombre, ' · ', m.nombre, ' · ', g.nombre, ' · ', ce.nombre) AS info_completa
    FROM cursos c
    INNER JOIN materias m ON c.id_materia = m.id_materia
    INNER JOIN grupos g ON c.id_grupo = g.id_grupo
    INNER JOIN ciclos_escolares ce ON c.id_ciclo = ce.id_ciclo
    WHERE c.estado = 'Activo'
    ORDER BY c.nombre
")->fetch_all(MYSQLI_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
$tipo = $_GET['tipo'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $idioma_actual === 'es' ? 'es' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('inscripciones'); ?> - Administrador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="styles/inscripciones.css">
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
                <h1><?php echo __('inscripciones'); ?></h1>
                <p><?php echo __('administra_inscripciones'); ?></p>
            </div>
            <div class="header-actions">
                <!-- ✅ BOTÓN CHATBOT -->
<button class="btn-assistant" id="btn-asistente" onclick="window.location.href='../Alumno/ChatbotAdmin.php'">
    <i class="fa-solid fa-comment-dots"></i> <?php echo __('chatbot'); ?>
</button>
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
        <!-- RESUMEN DE INSCRIPCIONES                   -->
        <!-- ========================================== -->
        <section class="resumen-inscripciones">
            <div class="stats-row">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $total_inscripciones; ?></span>
                    <span class="stat-label"><?php echo __('total'); ?></span>
                </div>
                <div class="stat-card stat-activa">
                    <span class="stat-number"><?php echo $inscripciones_activas; ?></span>
                    <span class="stat-label"><?php echo __('activas'); ?></span>
                </div>
                <button class="btn-nueva-inscripcion" id="btnNuevaInscripcion">
                    <i class="fa-solid fa-plus"></i> <?php echo __('nueva_inscripcion'); ?>
                </button>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- BÚSQUEDA                                  -->
        <!-- ========================================== -->
        <section class="busqueda-inscripciones">
            <div class="busqueda-container">
                <i class="fa-solid fa-search"></i>
                <input type="text" placeholder="<?php echo __('buscar_inscripcion'); ?>" class="input-busqueda" id="buscarInscripcion">
            </div>
        </section>

        <!-- ========================================== -->
        <!-- LISTA DE INSCRIPCIONES                     -->
        <!-- ========================================== -->
        <section class="lista-inscripciones">
            <div class="inscripciones-header">
                <h3><?php echo __('inscripciones_registradas'); ?></h3>
                <span class="resultados" id="totalResultados"><?php echo count($inscripciones); ?> <?php echo __('resultados'); ?></span>
            </div>

            <div class="inscripciones-grid" id="inscripcionesGrid">
                <?php if (empty($inscripciones)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <h4><?php echo __('sin_inscripciones'); ?></h4>
                        <p><?php echo __('crear_primer_inscripcion'); ?></p>
                        <button class="btn-agregar-empty" id="btnNuevoInscripcionEmpty">
                            <i class="fa-solid fa-plus"></i> <?php echo __('crear_inscripcion'); ?>
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($inscripciones as $inscripcion): ?>
                    <div class="inscripcion-card" data-estado="<?php echo $inscripcion['estado']; ?>">
                        <div class="inscripcion-header">
                            <div>
                                <h4 class="inscripcion-estudiante"><?php echo htmlspecialchars($inscripcion['estudiante']); ?></h4>
                                <span class="badge <?php echo ($inscripcion['estado'] === 'Activo') ? 'badge-activo' : 'badge-inactivo'; ?>">
                                    <?php echo $inscripcion['estado']; ?>
                                </span>
                            </div>
                        </div>

                        <div class="inscripcion-detalles">
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('correo'); ?>:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($inscripcion['correo']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('curso'); ?>:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($inscripcion['curso']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('materia'); ?>:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($inscripcion['materia']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('grupo'); ?>:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($inscripcion['grupo']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('ciclo'); ?>:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($inscripcion['ciclo']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('fecha_inscripcion'); ?>:</span>
                                <span class="detalle-valor"><?php echo date('d M Y, h:i a', strtotime($inscripcion['fecha'])); ?></span>
                            </div>
                        </div>

                        <div class="inscripcion-acciones">
                            <button class="btn-editar" data-id="<?php echo $inscripcion['id_inscripcion']; ?>">
                                <i class="fa-regular fa-pen-to-square"></i> <?php echo __('editar'); ?>
                            </button>
                            <button class="btn-deshabilitar" data-id="<?php echo $inscripcion['id_inscripcion']; ?>">
                                <i class="fa-solid fa-eye-slash"></i> <?php echo __('deshabilitar'); ?>
                            </button>
                            <button class="btn-eliminar" data-id="<?php echo $inscripcion['id_inscripcion']; ?>">
                                <i class="fa-regular fa-trash-can"></i> <?php echo __('eliminar'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MODAL PARA NUEVA / EDITAR INSCRIPCIÓN     -->
        <!-- ========================================== -->
        <div class="modal-overlay" id="modalInscripcion">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitulo"><?php echo __('nueva_inscripcion'); ?></h2>
                    <button class="modal-cerrar" id="modalCerrar">&times;</button>
                </div>
                <form id="formInscripcion" method="POST" action="logica/procesar_inscripciones.php">
                    <input type="hidden" name="accion" id="modalAccion" value="guardar">
                    <input type="hidden" name="id" id="modalId" value="">

                    <!-- Estudiante -->
                    <div class="form-group">
                        <label><?php echo __('estudiante'); ?> <span class="text-danger">*</span></label>
                        <div class="radio-group radio-inline" id="estudianteOptions">
                            <?php if (empty($estudiantes)): ?>
                                <p style="color: #94a3b8; font-style: italic;"><?php echo __('sin_estudiantes'); ?></p>
                            <?php else: ?>
                                <?php foreach ($estudiantes as $index => $estudiante): ?>
                                <label>
                                    <input type="radio" name="id_alumno" value="<?php echo $estudiante['id_usuario']; ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($estudiante['nombre_completo']); ?>
                                </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Curso -->
                    <div class="form-group">
                        <label><?php echo __('curso'); ?> <span class="text-danger">*</span></label>
                        <div class="radio-group radio-inline" id="cursoOptions">
                            <?php if (empty($cursos)): ?>
                                <p style="color: #94a3b8; font-style: italic;"><?php echo __('sin_cursos'); ?></p>
                            <?php else: ?>
                                <?php foreach ($cursos as $index => $curso): ?>
                                <label>
                                    <input type="radio" name="id_curso" value="<?php echo $curso['id_curso']; ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($curso['info_completa']); ?>
                                </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
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
<script src="js/inscripciones.js"></script>

<!-- ✅ NUEVA ACCESIBILIDAD JS -->
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>