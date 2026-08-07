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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripciones - Administrador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="styles/inscripciones.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="ciclos_escolares.php" class="menu-item <?php echo ($pagina_actual == 'ciclos_escolares.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar"></i> Ciclos escolares
            </a>
            <a href="periodos.php" class="menu-item <?php echo ($pagina_actual == 'periodos.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock"></i> Periodos
            </a>
            <a href="materias.php" class="menu-item <?php echo ($pagina_actual == 'materias.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-book"></i> Materias
            </a>
            <a href="grupos.php" class="menu-item <?php echo ($pagina_actual == 'grupos.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-layer-group"></i> Grupos
            </a>
            <a href="cursos.php" class="menu-item <?php echo ($pagina_actual == 'cursos.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-cubes"></i> Cursos
            </a>
            <a href="inscripciones.php" class="menu-item <?php echo ($pagina_actual == 'inscripciones.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-pen-to-square"></i> Inscripciones
            </a>
            
            <a href="configuracion.php" class="menu-item <?php echo ($pagina_actual == 'configuracion.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gear"></i> Configuración
            </a>
        </nav>
        
        <button class="btn-accessibility-main">
            <i class="fa-solid fa-universal-access"></i> Accesibilidad
        </button>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        
        <!-- ENCABEZADO -->
        <header class="content-header">
            <div class="welcome-text">
                <h1>Inscripciones</h1>
                <p>Asigna estudiantes a sus cursos</p>
            </div>
            <div class="header-actions">
                <button class="btn-assistant" id="btn-asistente">
                    <i class="fa-solid fa-comment-dots"></i> Chatbot
                </button>
                <div class="icon-bell">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <button class="btn-accessibility-header">
                    <i class="fa-solid fa-universal-access"></i>
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
                    <span class="stat-label">Total</span>
                </div>
                <div class="stat-card stat-activa">
                    <span class="stat-number"><?php echo $inscripciones_activas; ?></span>
                    <span class="stat-label">Activas</span>
                </div>
                <button class="btn-nueva-inscripcion" id="btnNuevaInscripcion">
                    <i class="fa-solid fa-plus"></i> Nueva inscripción
                </button>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- BÚSQUEDA                                  -->
        <!-- ========================================== -->
        <section class="busqueda-inscripciones">
            <div class="busqueda-container">
                <i class="fa-solid fa-search"></i>
                <input type="text" placeholder="Buscar estudiante, curso, materia o grupo..." class="input-busqueda" id="buscarInscripcion">
            </div>
        </section>

        <!-- ========================================== -->
        <!-- LISTA DE INSCRIPCIONES                     -->
        <!-- ========================================== -->
        <section class="lista-inscripciones">
            <div class="inscripciones-header">
                <h3>Inscripciones registradas</h3>
                <span class="resultados" id="totalResultados"><?php echo count($inscripciones); ?> resultados</span>
            </div>

            <div class="inscripciones-grid" id="inscripcionesGrid">
                <?php if (empty($inscripciones)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <h4>No hay inscripciones registradas</h4>
                        <p>Comienza creando una nueva inscripción.</p>
                        <button class="btn-agregar-empty" id="btnNuevoInscripcionEmpty">
                            <i class="fa-solid fa-plus"></i> Crear primera inscripción
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
                                <span class="detalle-label">Correo:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($inscripcion['correo']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">Curso:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($inscripcion['curso']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">Materia:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($inscripcion['materia']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">Grupo:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($inscripcion['grupo']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">Ciclo:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($inscripcion['ciclo']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">Inscripción:</span>
                                <span class="detalle-valor"><?php echo date('d M Y, h:i a', strtotime($inscripcion['fecha'])); ?></span>
                            </div>
                        </div>

                        <div class="inscripcion-acciones">
                            <button class="btn-editar" data-id="<?php echo $inscripcion['id_inscripcion']; ?>">
                                <i class="fa-regular fa-pen-to-square"></i> Editar
                            </button>
                            <button class="btn-deshabilitar" data-id="<?php echo $inscripcion['id_inscripcion']; ?>">
                                <i class="fa-solid fa-eye-slash"></i> Desactivar
                            </button>
                            <button class="btn-eliminar" data-id="<?php echo $inscripcion['id_inscripcion']; ?>">
                                <i class="fa-regular fa-trash-can"></i> Eliminar
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
                    <h2 id="modalTitulo">Nueva inscripción</h2>
                    <button class="modal-cerrar" id="modalCerrar">&times;</button>
                </div>
                <form id="formInscripcion" method="POST" action="logica/procesar_inscripciones.php">
                    <input type="hidden" name="accion" id="modalAccion" value="guardar">
                    <input type="hidden" name="id" id="modalId" value="">

                    <!-- Estudiante -->
                    <div class="form-group">
                        <label>Estudiante <span class="text-danger">*</span></label>
                        <div class="radio-group radio-inline" id="estudianteOptions">
                            <?php if (empty($estudiantes)): ?>
                                <p style="color: #94a3b8; font-style: italic;">No hay estudiantes disponibles</p>
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
                        <label>Curso <span class="text-danger">*</span></label>
                        <div class="radio-group radio-inline" id="cursoOptions">
                            <?php if (empty($cursos)): ?>
                                <p style="color: #94a3b8; font-style: italic;">No hay cursos disponibles</p>
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
                        <label>Estado</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="estado" value="Activo" checked>
                                <i class="fa-solid fa-circle-check"></i> Activo
                            </label>
                            <label>
                                <input type="radio" name="estado" value="Inactivo">
                                <i class="fa-solid fa-circle-xmark"></i> Inactivo
                            </label>
                            <label>
                                <input type="radio" name="estado" value="Finalizado">
                                <i class="fa-solid fa-lock"></i> Finalizado
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-cancelar" id="modalCancelar">Cancelar</button>
                        <button type="submit" class="btn-guardar">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- BARRA DE ACCESIBILIDAD -->
        <footer class="accessibility-bar">
            <div class="acc-info">
                <i class="fa-solid fa-eye-low-vision acc-icon-main"></i>
                <div>
                    <strong>Accesibilidad siempre disponible</strong>
                    <p>Personaliza tu experiencia en cualquier momento.</p>
                </div>
            </div>
            <div class="acc-options">
                <button class="acc-opt-btn" id="btn-contrast">
                    <i class="fa-solid fa-eye"></i><span>Alto contraste</span>
                </button>
                <button class="acc-opt-btn" id="btn-darkmode">
                    <i class="fa-solid fa-moon"></i><span>Modo oscuro</span>
                </button>
                <button class="acc-opt-btn" id="btn-text-size">
                    <span class="font-icon">Aa</span><span>Texto grande</span>
                </button>
                <button class="acc-opt-btn">
                    <i class="fa-solid fa-volume-high"></i><span>Leer pantalla</span>
                </button>
                <button class="acc-opt-btn">
                    <i class="fa-solid fa-closed-captioning"></i><span>Subtítulos</span>
                </button>
                <button class="acc-opt-btn">
                    <i class="fa-solid fa-keyboard"></i><span>Navegación</span>
                </button>
            </div>
            <button class="btn-open-config">Abrir configuración</button>
        </footer>

    </main>
</div>

<script src="js/admin.js"></script>
<script src="js/inscripciones.js"></script>
<!-- LECTOR DE PANTALLA -->
<script src="js/lector.js"></script>
</body>
</html>