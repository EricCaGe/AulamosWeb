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

// Total de grupos
$resultado = $conexion->query("SELECT COUNT(*) AS total FROM grupos");
$total_grupos = $resultado->fetch_assoc()['total'] ?? 0;

// Grupos activos
$resultado = $conexion->query("SELECT COUNT(*) AS total FROM grupos WHERE estado = 'Activo'");
$grupos_activos = $resultado->fetch_assoc()['total'] ?? 0;

// Grupos inactivos
$resultado = $conexion->query("SELECT COUNT(*) AS total FROM grupos WHERE estado = 'Inactivo'");
$grupos_inactivos = $resultado->fetch_assoc()['total'] ?? 0;

// Lista de todos los grupos
$grupos = $conexion->query("
    SELECT 
        g.*, 
        c.nombre AS ciclo_nombre,
        CONCAT(u.nombre, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS docente_nombre,
        (SELECT COUNT(*) FROM cursos WHERE id_grupo = g.id_grupo) AS total_cursos
    FROM grupos g
    LEFT JOIN ciclos_escolares c ON g.id_ciclo = c.id_ciclo
    LEFT JOIN usuarios u ON g.id_docente = u.id_usuario
    ORDER BY g.nombre
")->fetch_all(MYSQLI_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
$tipo = $_GET['tipo'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $idioma_actual === 'es' ? 'es' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('grupos'); ?> - Administrador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="styles/grupos.css">
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
                <h1><?php echo __('grupos'); ?></h1>
                <p><?php echo __('administra_grupos'); ?></p>
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
        <!-- RESUMEN DE GRUPOS                          -->
        <!-- ========================================== -->
        <section class="resumen-grupos">
            <div class="stats-row">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $total_grupos; ?></span>
                    <span class="stat-label"><?php echo __('total'); ?></span>
                </div>
                <div class="stat-card stat-activa">
                    <span class="stat-number"><?php echo $grupos_activos; ?></span>
                    <span class="stat-label"><?php echo __('activos'); ?></span>
                </div>
                <div class="stat-card stat-inactiva">
                    <span class="stat-number"><?php echo $grupos_inactivos; ?></span>
                    <span class="stat-label"><?php echo __('inactivos'); ?></span>
                </div>
                <button class="btn-nuevo-grupo" id="btnNuevoGrupo">
                    <i class="fa-solid fa-plus"></i> <?php echo __('nuevo_grupo'); ?>
                </button>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- BÚSQUEDA Y FILTROS                         -->
        <!-- ========================================== -->
        <section class="filtros-grupos">
            <div class="busqueda-container">
                <i class="fa-solid fa-search"></i>
                <input type="text" placeholder="<?php echo __('buscar_grupo'); ?>" class="input-busqueda" id="buscarGrupo">
            </div>
            <div class="filtros-botones">
                <button class="filtro-btn active" data-filtro="todas"><?php echo __('todos'); ?></button>
                <button class="filtro-btn" data-filtro="Activo"><?php echo __('activo'); ?></button>
                <button class="filtro-btn" data-filtro="Inactivo"><?php echo __('inactivo'); ?></button>
                <button class="filtro-btn" data-filtro="Finalizado"><?php echo __('finalizado'); ?></button>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- LISTA DE GRUPOS                            -->
        <!-- ========================================== -->
        <section class="lista-grupos">
            <div class="grupos-header">
                <h3><?php echo __('grupos_registrados'); ?></h3>
                <span class="resultados" id="totalResultados"><?php echo count($grupos); ?> <?php echo __('resultados'); ?></span>
            </div>

            <div class="grupos-grid" id="gruposGrid">
                <?php if (empty($grupos)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-layer-group"></i>
                        <h4><?php echo __('sin_grupos'); ?></h4>
                        <p><?php echo __('crear_primer_grupo'); ?></p>
                        <button class="btn-agregar-empty" id="btnNuevoGrupoEmpty">
                            <i class="fa-solid fa-plus"></i> <?php echo __('crear_grupo'); ?>
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($grupos as $grupo): ?>
                    <div class="grupo-card" data-estado="<?php echo $grupo['estado']; ?>">
                        <div class="grupo-header">
                            <h4 class="grupo-nombre">
                                <?php 
                                $grado = $grupo['grado'] ?? '';
                                $nombre = htmlspecialchars($grupo['nombre']);
                                
                                if (!empty($grado)) {
                                    if (preg_match('/^\d+$/', trim($grado))) {
                                        $grado = $grado . '°';
                                    }
                                    echo htmlspecialchars($grado) . ' ' . $nombre;
                                } else {
                                    echo $nombre;
                                }
                                ?>
                            </h4>
                            <span class="badge <?php 
                                echo ($grupo['estado'] === 'Activo') ? 'badge-activo' : 
                                    (($grupo['estado'] === 'Finalizado') ? 'badge-cerrado' : 'badge-inactivo'); 
                            ?>">
                                <?php echo $grupo['estado']; ?>
                            </span>
                        </div>

                        <div class="grupo-detalles">
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('ciclo'); ?>:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($grupo['ciclo_nombre'] ?? 'Sin ciclo'); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('docente'); ?>:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($grupo['docente_nombre'] ?? 'Sin docente'); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('turno'); ?>:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($grupo['turno']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('modalidad'); ?>:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($grupo['modalidad']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('cupo'); ?>:</span>
                                <span class="detalle-valor"><?php echo $grupo['cupo_maximo']; ?> <?php echo __('estudiantes'); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label"><?php echo __('cursos_relacionados'); ?>:</span>
                                <span class="detalle-valor"><?php echo $grupo['total_cursos'] ?? 0; ?></span>
                            </div>
                        </div>

                        <div class="grupo-acciones">
                            <button class="btn-editar" data-id="<?php echo $grupo['id_grupo']; ?>">
                                <i class="fa-regular fa-pen-to-square"></i> <?php echo __('editar'); ?>
                            </button>
                            <?php if ($grupo['estado'] !== 'Finalizado'): ?>
                            <button class="btn-deshabilitar" data-id="<?php echo $grupo['id_grupo']; ?>">
                                <i class="fa-solid fa-eye-slash"></i> <?php echo __('deshabilitar'); ?>
                            </button>
                            <?php endif; ?>
                            <button class="btn-eliminar" data-id="<?php echo $grupo['id_grupo']; ?>">
                                <i class="fa-regular fa-trash-can"></i> <?php echo __('eliminar'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MODAL PARA NUEVO / EDITAR GRUPO           -->
        <!-- ========================================== -->
        <div class="modal-overlay" id="modalGrupo">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitulo"><?php echo __('nuevo_grupo'); ?></h2>
                    <button class="modal-cerrar" id="modalCerrar">&times;</button>
                </div>
                <form id="formGrupo" method="POST" action="logica/procesar_grupos.php">
                    <input type="hidden" name="accion" id="modalAccion" value="guardar">
                    <input type="hidden" name="id" id="modalId" value="">

                    <div class="form-group">
                        <label for="modalCiclo"><?php echo __('ciclo_escolar'); ?> <span class="text-danger">*</span></label>
                        <select id="modalCiclo" name="id_ciclo" required>
                            <option value="">-- <?php echo __('seleccionar_ciclo'); ?> --</option>
                            <?php
                            $ciclos = $conexion->query("SELECT id_ciclo, nombre FROM ciclos_escolares WHERE estado = 'Activo' ORDER BY fecha_inicio DESC");
                            if ($ciclos && $ciclos->num_rows > 0) {
                                while ($ciclo = $ciclos->fetch_assoc()):
                            ?>
                            <option value="<?php echo $ciclo['id_ciclo']; ?>">
                                <?php echo htmlspecialchars($ciclo['nombre']); ?>
                            </option>
                            <?php
                                endwhile;
                            } else {
                                echo '<option value="" disabled style="color: #94a3b8; font-style: italic;">No hay ciclos disponibles</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="modalDocente"><?php echo __('docente_a_cargo'); ?> <span class="text-danger">*</span></label>
                        <select id="modalDocente" name="id_docente" required>
                            <option value="">-- <?php echo __('seleccionar_docente'); ?> --</option>
                            <?php
                            $docentes = $conexion->query("
                                SELECT u.id_usuario, u.nombre, u.apellido_paterno, u.apellido_materno 
                                FROM usuarios u
                                INNER JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
                                WHERE ur.id_rol = 2 AND u.estado = 'Activo'
                                ORDER BY u.nombre
                            ");
                            if ($docentes && $docentes->num_rows > 0) {
                                while ($docente = $docentes->fetch_assoc()):
                            ?>
                            <option value="<?php echo $docente['id_usuario']; ?>">
                                <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido_paterno'] . ' ' . $docente['apellido_materno']); ?>
                            </option>
                            <?php
                                endwhile;
                            } else {
                                echo '<option value="" disabled style="color: #94a3b8; font-style: italic;">No hay docentes disponibles</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="modalNombre"><?php echo __('nombre_grupo'); ?> <span class="text-danger">*</span></label>
                        <input type="text" id="modalNombre" name="nombre" placeholder="Ej. A" required>
                    </div>

                    <div class="form-group">
                        <label><?php echo __('grado_escolar'); ?></label>
                        <div class="radio-group radio-inline">
                            <label>
                                <input type="radio" name="grado" value="1°" checked> 1°
                            </label>
                            <label>
                                <input type="radio" name="grado" value="2°"> 2°
                            </label>
                            <label>
                                <input type="radio" name="grado" value="3°"> 3°
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?php echo __('turno'); ?></label>
                        <div class="radio-group radio-inline">
                            <label>
                                <input type="radio" name="turno" value="Matutino" checked> <?php echo __('matutino'); ?>
                            </label>
                            <label>
                                <input type="radio" name="turno" value="Vespertino"> <?php echo __('vespertino'); ?>
                            </label>
                            <label>
                                <input type="radio" name="turno" value="Mixto"> <?php echo __('mixto'); ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?php echo __('modalidad'); ?></label>
                        <div class="radio-group radio-inline">
                            <label>
                                <input type="radio" name="modalidad" value="Presencial" checked> <?php echo __('presencial'); ?>
                            </label>
                            <label>
                                <input type="radio" name="modalidad" value="Hibrida"> <?php echo __('hibrida'); ?>
                            </label>
                            <label>
                                <input type="radio" name="modalidad" value="Virtual"> <?php echo __('virtual'); ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="modalCupo"><?php echo __('cupo_maximo'); ?></label>
                        <input type="number" id="modalCupo" name="cupo_maximo" placeholder="30" value="30" min="1">
                    </div>

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
<script src="js/grupos.js"></script>

<!-- ✅ NUEVA ACCESIBILIDAD JS -->
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>