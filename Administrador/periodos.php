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

// ✅ SOLO CICLOS ACTIVOS
$ciclos = $conexion->query("SELECT id_ciclo, nombre, fecha_inicio, fecha_fin FROM ciclos_escolares WHERE estado = 'Activo' ORDER BY fecha_inicio DESC")->fetch_all(MYSQLI_ASSOC);

// Ciclo seleccionado (por defecto el primero)
$ciclo_seleccionado = isset($_GET['id_ciclo']) ? intval($_GET['id_ciclo']) : ($ciclos[0]['id_ciclo'] ?? 0);

// Datos del ciclo seleccionado
$ciclo_actual = null;
foreach ($ciclos as $c) {
    if ($c['id_ciclo'] == $ciclo_seleccionado) {
        $ciclo_actual = $c;
        break;
    }
}

// Periodos del ciclo seleccionado
$periodos = [];
if ($ciclo_seleccionado > 0) {
    $periodos = $conexion->query("
        SELECT * FROM periodos_evaluacion 
        WHERE id_ciclo = $ciclo_seleccionado 
        ORDER BY fecha_inicio ASC
    ")->fetch_all(MYSQLI_ASSOC);
}

$mensaje = $_GET['mensaje'] ?? '';
$tipo = $_GET['tipo'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $idioma_actual === 'es' ? 'es' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('periodos'); ?> - Administrador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="styles/periodos.css">
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
                <h1><?php echo __('periodos'); ?></h1>
                <p><?php echo __('administra_periodos'); ?></p>
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
        <!-- SELECTOR DE CICLO ESCOLAR                  -->
        <!-- ========================================== -->
        <section class="selector-ciclo">
            <div class="ciclo-selector-card">
                <label class="selector-label"><?php echo __('ciclo_escolar'); ?></label>
                <select class="selector-select" id="selectorCiclo" onchange="window.location.href='periodos.php?id_ciclo='+this.value">
                    <?php foreach ($ciclos as $ciclo): ?>
                        <option value="<?php echo $ciclo['id_ciclo']; ?>" <?php echo ($ciclo['id_ciclo'] == $ciclo_seleccionado) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ciclo['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($ciclo_actual): ?>
                <div class="ciclo-fechas-info">
                    <span class="fecha-info">
                        <i class="fa-regular fa-calendar"></i>
                        <?php echo date('d/m/Y', strtotime($ciclo_actual['fecha_inicio'])); ?> – <?php echo date('d/m/Y', strtotime($ciclo_actual['fecha_fin'])); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- LISTA DE PERIODOS                          -->
        <!-- ========================================== -->
        <section class="lista-periodos">
            <div class="periodos-header">
                <div>
                    <h3><?php echo __('periodos_registrados'); ?></h3>
                    <p class="periodos-sub"><?php echo count($periodos); ?> <?php echo __('periodos_encontrados'); ?></p>
                </div>
                <button class="btn-nuevo-periodo" id="btnNuevoPeriodo">
                    <i class="fa-solid fa-plus"></i> <?php echo __('nuevo_periodo'); ?>
                </button>
            </div>

            <div class="periodos-grid">
                <?php if (empty($periodos)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-clock"></i>
                        <h4><?php echo __('sin_periodos'); ?></h4>
                        <p><?php echo __('crear_primer_periodo'); ?></p>
                        <button class="btn-agregar-empty" id="btnNuevoPeriodoEmpty">
                            <i class="fa-solid fa-plus"></i> <?php echo __('crear_periodo'); ?>
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($periodos as $periodo): ?>
                    <div class="periodo-card" data-id="<?php echo $periodo['id_periodo']; ?>">
                        <div class="periodo-header">
                            <div>
                                <h4 class="periodo-nombre"><?php echo htmlspecialchars($periodo['nombre']); ?></h4>
                                <p class="periodo-ciclo"><?php echo htmlspecialchars($ciclo_actual['nombre'] ?? 'Sin ciclo'); ?></p>
                            </div>
                            <span class="badge <?php 
                                echo ($periodo['estado'] === 'Activo') ? 'badge-activo' : 
                                    (($periodo['estado'] === 'Cerrado') ? 'badge-cerrado' : 'badge-inactivo'); 
                            ?>">
                                <?php echo $periodo['estado']; ?>
                            </span>
                        </div>

                        <div class="periodo-fechas">
                            <div class="fecha-item">
                                <span class="fecha-label"><?php echo __('inicio'); ?></span>
                                <span class="fecha-valor"><?php echo date('d/m/Y', strtotime($periodo['fecha_inicio'])); ?></span>
                            </div>
                            <div class="fecha-item">
                                <span class="fecha-label"><?php echo __('fin'); ?></span>
                                <span class="fecha-valor"><?php echo date('d/m/Y', strtotime($periodo['fecha_fin'])); ?></span>
                            </div>
                        </div>

                        <div class="periodo-acciones">
                            <button class="btn-editar" data-id="<?php echo $periodo['id_periodo']; ?>">
                                <i class="fa-regular fa-pen-to-square"></i> <?php echo __('editar'); ?>
                            </button>
                            <?php if ($periodo['estado'] !== 'Cerrado'): ?>
                            <button class="btn-cerrar" data-id="<?php echo $periodo['id_periodo']; ?>">
                                <i class="fa-solid fa-lock"></i> <?php echo __('cerrar'); ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MODAL PARA NUEVO / EDITAR PERIODO         -->
        <!-- ========================================== -->
        <div class="modal-overlay" id="modalPeriodo">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitulo"><?php echo __('nuevo_periodo'); ?></h2>
                    <button class="modal-cerrar" id="modalCerrar">&times;</button>
                </div>
                <form id="formPeriodo" method="POST" action="logica/procesar_periodos.php">
                    <input type="hidden" name="accion" id="modalAccion" value="guardar">
                    <input type="hidden" name="id" id="modalId" value="">
                    <input type="hidden" name="id_ciclo" id="modalCicloId" value="<?php echo $ciclo_seleccionado; ?>">

                    <div class="form-group">
                        <label><?php echo __('ciclo_escolar'); ?></label>
                        <p class="ciclo-info">
                            <strong><?php echo htmlspecialchars($ciclo_actual['nombre'] ?? 'Sin ciclo'); ?></strong>
                        </p>
                        <?php if ($ciclo_actual): ?>
                        <p class="fechas-permitidas">
                            <i class="fa-regular fa-calendar"></i>
                            <?php echo __('fechas_permitidas'); ?>: <?php echo date('d/m/Y', strtotime($ciclo_actual['fecha_inicio'])); ?> – <?php echo date('d/m/Y', strtotime($ciclo_actual['fecha_fin'])); ?>
                        </p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="modalNombre"><?php echo __('nombre_periodo'); ?> <span class="text-danger">*</span></label>
                        <input type="text" id="modalNombre" name="nombre" placeholder="<?php echo __('ej_periodo'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="modalInicio"><?php echo __('fecha_inicio'); ?> <span class="text-danger">*</span></label>
                        <input type="date" id="modalInicio" name="fecha_inicio" required>
                    </div>

                    <div class="form-group">
                        <label for="modalFin"><?php echo __('fecha_fin'); ?> <span class="text-danger">*</span></label>
                        <input type="date" id="modalFin" name="fecha_fin" required>
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
                            <label>
                                <input type="radio" name="estado" value="Cerrado">
                                <i class="fa-solid fa-lock"></i> <?php echo __('cerrado'); ?>
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
<script src="js/periodos.js"></script>

<!-- ✅ NUEVA ACCESIBILIDAD JS -->
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>