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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursos - Administrador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="styles/cursos.css">
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
                <h1>Cursos</h1>
                <p>Administra las asignaciones académicas</p>
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
        <!-- RESUMEN DE CURSOS                          -->
        <!-- ========================================== -->
        <section class="resumen-cursos">
            <div class="stats-row">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $total_cursos; ?></span>
                    <span class="stat-label">Total</span>
                </div>
                <div class="stat-card stat-activa">
                    <span class="stat-number"><?php echo $cursos_activos; ?></span>
                    <span class="stat-label">Activos</span>
                </div>
                <button class="btn-nuevo-curso" id="btnNuevoCurso">
                    <i class="fa-solid fa-plus"></i> Nuevo curso
                </button>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- BÚSQUEDA                                  -->
        <!-- ========================================== -->
        <section class="busqueda-cursos">
            <div class="busqueda-container">
                <i class="fa-solid fa-search"></i>
                <input type="text" placeholder="Buscar curso, materia, grupo o docente..." class="input-busqueda" id="buscarCurso">
            </div>
        </section>

        <!-- ========================================== -->
        <!-- LISTA DE CURSOS                            -->
        <!-- ========================================== -->
        <section class="lista-cursos">
            <div class="cursos-header">
                <h3>Cursos registrados</h3>
                <span class="resultados" id="totalResultados"><?php echo count($cursos); ?> resultados</span>
            </div>

            <div class="cursos-grid" id="cursosGrid">
                <?php if (empty($cursos)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-cubes"></i>
                        <h4>No hay cursos registrados</h4>
                        <p>Comienza creando un nuevo curso.</p>
                        <button class="btn-agregar-empty" id="btnNuevoCursoEmpty">
                            <i class="fa-solid fa-plus"></i> Crear primer curso
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
                                <span class="detalle-label">Materia:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($curso['materia']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">Grupo:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($curso['grupo']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">Docente:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($curso['docente']); ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">Ciclo:</span>
                                <span class="detalle-valor"><?php echo htmlspecialchars($curso['ciclo']); ?></span>
                            </div>
                        </div>

                        <div class="curso-acciones">
                            <button class="btn-editar" data-id="<?php echo $curso['id_curso']; ?>">
                                <i class="fa-regular fa-pen-to-square"></i> Editar
                            </button>
                            <?php if ($curso['estado'] !== 'Finalizado'): ?>
                            <button class="btn-deshabilitar" data-id="<?php echo $curso['id_curso']; ?>">
                                <i class="fa-solid fa-eye-slash"></i> Desactivar
                            </button>
                            <?php endif; ?>
                            <button class="btn-eliminar" data-id="<?php echo $curso['id_curso']; ?>">
                                <i class="fa-regular fa-trash-can"></i> Eliminar
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
                    <h2 id="modalTitulo">Nuevo curso</h2>
                    <button class="modal-cerrar" id="modalCerrar">&times;</button>
                </div>
                <form id="formCurso" method="POST" action="logica/procesar_cursos.php">
                    <input type="hidden" name="accion" id="modalAccion" value="guardar">
                    <input type="hidden" name="id" id="modalId" value="">

                    <!-- Ciclo escolar -->
                    <div class="form-group">
                        <label>Ciclo escolar <span class="text-danger">*</span></label>
                        <div class="radio-group radio-inline" id="cicloOptions">
                            <?php if (empty($ciclos)): ?>
                                <p style="color: #94a3b8; font-style: italic;">No hay ciclos activos disponibles</p>
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
                        <label for="modalGrupo">Grupo <span class="text-danger">*</span></label>
                        <select id="modalGrupo" name="id_grupo" required>
                            <option value="">-- Selecciona un grupo --</option>
                            <?php foreach ($grupos as $grupo): ?>
                            <option value="<?php echo $grupo['id_grupo']; ?>">
                                <?php echo htmlspecialchars($grupo['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Materia -->
                    <div class="form-group">
                        <label>Materia <span class="text-danger">*</span></label>
                        <div class="radio-group radio-inline" id="materiaOptions">
                            <?php if (empty($materias)): ?>
                                <p style="color: #94a3b8; font-style: italic;">No hay materias activas disponibles</p>
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
                        <label>Docente <span class="text-danger">*</span></label>
                        <div class="radio-group radio-inline" id="docenteOptions">
                            <?php if (empty($docentes)): ?>
                                <p style="color: #94a3b8; font-style: italic;">No hay docentes disponibles</p>
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
                        <label for="modalNombre">Nombre del curso <span class="text-danger">*</span></label>
                        <input type="text" id="modalNombre" name="nombre" placeholder="Ej. Inglés Básico" required>
                    </div>

                    <!-- Descripción -->
                    <div class="form-group">
                        <label for="modalDescripcion">Descripción</label>
                        <textarea id="modalDescripcion" name="descripcion" rows="3" placeholder="Descripción del curso"></textarea>
                        <p class="contador-caracteres"><span id="modalContador">0</span>/1000</p>
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
<script src="js/cursos.js"></script>
<!-- LECTOR DE PANTALLA -->
<script src="js/lector.js"></script>
</body>
</html>