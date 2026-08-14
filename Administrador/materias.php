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

$resultado = $conexion->query("SELECT COUNT(*) AS total FROM materias");
$row = $resultado->fetch_assoc();
$total_materias = $row['total'] ?? 0;

$resultado = $conexion->query("SELECT COUNT(*) AS total FROM materias WHERE estado = 'Activa'");
$row = $resultado->fetch_assoc();
$materias_activas = $row['total'] ?? 0;

$resultado = $conexion->query("SELECT COUNT(*) AS total FROM materias WHERE estado = 'Inactiva'");
$row = $resultado->fetch_assoc();
$materias_inactivas = $row['total'] ?? 0;

$materias = $conexion->query("
    SELECT id_materia, nombre, campo_formativo, descripcion, estado 
    FROM materias 
    ORDER BY nombre
")->fetch_all(MYSQLI_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
$tipo = $_GET['tipo'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $idioma_actual === 'es' ? 'es' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('materias'); ?> - Administrador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="styles/materias.css">
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
                <h1><?php echo __('materias'); ?></h1>
                <p><?php echo __('administra_materias'); ?></p>
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
        <!-- RESUMEN DE MATERIAS                        -->
        <!-- ========================================== -->
        <section class="resumen-materias">
            <div class="stats-row">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $total_materias; ?></span>
                    <span class="stat-label"><?php echo __('total'); ?></span>
                </div>
                <div class="stat-card stat-activa">
                    <span class="stat-number"><?php echo $materias_activas; ?></span>
                    <span class="stat-label"><?php echo __('activas'); ?></span>
                </div>
                <div class="stat-card stat-inactiva">
                    <span class="stat-number"><?php echo $materias_inactivas; ?></span>
                    <span class="stat-label"><?php echo __('inactivas'); ?></span>
                </div>
                <button class="btn-nueva-materia" id="btnNuevaMateria">
                    <i class="fa-solid fa-plus"></i> <?php echo __('nueva_materia'); ?>
                </button>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- BÚSQUEDA Y FILTROS                         -->
        <!-- ========================================== -->
        <section class="filtros-materias">
            <div class="busqueda-container">
                <i class="fa-solid fa-search"></i>
                <input type="text" placeholder="<?php echo __('buscar_materia'); ?>" class="input-busqueda" id="buscarMateria">
            </div>
            <div class="filtros-botones">
                <button class="filtro-btn active" data-filtro="todas"><?php echo __('todas'); ?></button>
                <button class="filtro-btn" data-filtro="Activa"><?php echo __('activa'); ?></button>
                <button class="filtro-btn" data-filtro="Inactiva"><?php echo __('inactiva'); ?></button>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- CATÁLOGO DE MATERIAS                       -->
        <!-- ========================================== -->
        <section class="catalogo-materias">
            <div class="catalogo-header">
                <h3><?php echo __('catalogo'); ?></h3>
                <span class="resultados" id="totalResultados"><?php echo count($materias); ?> <?php echo __('resultados'); ?></span>
            </div>

            <div class="materias-grid" id="materiasGrid">
                <?php foreach ($materias as $materia): ?>
                <div class="materia-card" data-estado="<?php echo $materia['estado']; ?>">
                    <div class="materia-header">
                        <div>
                            <h4 class="materia-nombre"><?php echo htmlspecialchars($materia['nombre']); ?></h4>
                            <span class="materia-campo"><?php echo htmlspecialchars($materia['campo_formativo']); ?></span>
                        </div>
                        <span class="badge <?php echo ($materia['estado'] === 'Activa') ? 'badge-activo' : 'badge-inactivo'; ?>">
                            <?php echo $materia['estado']; ?>
                        </span>
                    </div>
                    <p class="materia-descripcion"><?php echo htmlspecialchars($materia['descripcion'] ?? 'Sin descripción'); ?></p>
                    <div class="materia-acciones">
                        <button class="btn-editar" data-id="<?php echo $materia['id_materia']; ?>">
                            <i class="fa-regular fa-pen-to-square"></i> <?php echo __('editar'); ?>
                        </button>
                        <button class="btn-deshabilitar" data-id="<?php echo $materia['id_materia']; ?>">
                            <i class="fa-solid fa-eye-slash"></i> <?php echo __('deshabilitar'); ?>
                        </button>
                        <button class="btn-eliminar" data-id="<?php echo $materia['id_materia']; ?>">
                            <i class="fa-regular fa-trash-can"></i> <?php echo __('eliminar'); ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MODAL PARA NUEVA / EDITAR MATERIA         -->
        <!-- ========================================== -->
        <div class="modal-overlay" id="modalMateria">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitulo"><?php echo __('nueva_materia'); ?></h2>
                    <button class="modal-cerrar" id="modalCerrar">&times;</button>
                </div>
                <form id="formMateria" method="POST" action="logica/procesar_materias.php">
                    <input type="hidden" name="accion" id="modalAccion" value="guardar">
                    <input type="hidden" name="id" id="modalId" value="">

                    <div class="form-group">
                        <label for="modalNombre"><?php echo __('nombre_materia'); ?> <span class="text-danger">*</span></label>
                        <input type="text" id="modalNombre" name="nombre" placeholder="<?php echo __('ej_materia'); ?>" required>
                    </div>

                    <div class="form-group">
    <label for="modalCampo"><?php echo __('campo_formativo'); ?> <span class="text-danger">*</span></label>
    <select id="modalCampo" name="campo_formativo" required>
        <option value=""><?php echo __('seleccionar_campo'); ?></option>
        <option value="Lenguajes">Lenguajes</option>
        <option value="Saberes y Pensamiento Científico">Saberes y Pensamiento Científico</option>
        <option value="Naturaleza y Sociedades">Naturaleza y Sociedades</option>
        <option value="De lo Humano y lo Comunitario">De lo Humano y lo Comunitario</option>
    </select>
</div>

                    <div class="form-group">
                        <label for="modalDescripcion"><?php echo __('descripcion'); ?></label>
                        <textarea id="modalDescripcion" name="descripcion" rows="3" placeholder="<?php echo __('descripcion_materia'); ?>" maxlength="1000"></textarea>
                        <p class="contador-caracteres"><span id="modalContador">0</span>/1000</p>
                    </div>

                    <div class="form-group">
                        <label><?php echo __('estado'); ?></label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="estado" value="Activa" checked>
                                <i class="fa-solid fa-circle-check"></i> <?php echo __('activa'); ?>
                            </label>
                            <label>
                                <input type="radio" name="estado" value="Inactiva">
                                <i class="fa-solid fa-circle-xmark"></i> <?php echo __('inactiva'); ?>
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
<script src="js/materias.js"></script>

<!-- ✅ NUEVA ACCESIBILIDAD JS -->
<script src="../Accesibilidad/accesibilidad.js"></script>

</body>
</html>