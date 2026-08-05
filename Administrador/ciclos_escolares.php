<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$nombre_admin = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
$pagina_actual = basename($_SERVER['PHP_SELF']);

// ========================================== */
// CONSULTAS A LA BD                          */
// ========================================== */

// Ciclo actual (el primero activo)
$resultado = $conexion->query("SELECT * FROM ciclos_escolares WHERE estado = 'Activo' LIMIT 1");
$ciclo_actual = $resultado->fetch_assoc();

// Total de ciclos
$resultado = $conexion->query("SELECT COUNT(*) AS total FROM ciclos_escolares");
$total_ciclos = $resultado->fetch_assoc()['total'] ?? 0;

// Lista de todos los ciclos
$ciclos = $conexion->query("
    SELECT 
        c.*,
        (SELECT COUNT(*) FROM periodos_evaluacion WHERE id_ciclo = c.id_ciclo) AS total_periodos,
        (SELECT COUNT(*) FROM grupos WHERE id_ciclo = c.id_ciclo) AS total_grupos,
        (SELECT COUNT(*) FROM cursos WHERE id_ciclo = c.id_ciclo) AS total_cursos
    FROM ciclos_escolares c
    ORDER BY c.fecha_inicio DESC
")->fetch_all(MYSQLI_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
$tipo = $_GET['tipo'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ciclos Escolares - Administrador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="styles/ciclos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

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
                <h1>Ciclos escolares</h1>
                <p>Administración académica</p>
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
                <div class="user-profile">
                    <img src="https://placehold.co/40x40/3b71f3/white?text=👤" alt="Avatar Admin" class="avatar">
                    <span class="user-name"><?php echo htmlspecialchars($nombre_admin); ?></span>
                    <i class="fa-solid fa-chevron-down drop-icon"></i>
                </div>
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
        <!-- RESUMEN DEL CICLO ACTUAL                  -->
        <!-- ========================================== -->
        <section class="resumen-ciclo">
            <div class="resumen-card">
                <div class="resumen-icon">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div class="resumen-info">
                    <p class="resumen-label">Ciclo actual</p>
                    <h3 class="resumen-titulo"><?php echo $ciclo_actual['nombre'] ?? 'Sin ciclo activo'; ?></h3>
                    <p class="resumen-sub">
                        <?php echo $total_ciclos; ?> ciclo<?php echo ($total_ciclos != 1) ? 's' : ''; ?> registrado<?php echo ($total_ciclos != 1) ? 's' : ''; ?>
                    </p>
                </div>
                <button class="btn-agregar" id="btnNuevoCiclo">
                    <i class="fa-solid fa-plus"></i> Nuevo ciclo
                </button>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- LISTA DE CICLOS REGISTRADOS               -->
        <!-- ========================================== -->
        <section class="lista-ciclos">
            <div class="section-header">
                <h3 class="section-title">Ciclos registrados</h3>
                <p class="section-sub"><?php echo count($ciclos); ?> ciclo<?php echo (count($ciclos) != 1) ? 's' : ''; ?> encontrado<?php echo (count($ciclos) != 1) ? 's' : ''; ?></p>
            </div>

            <div class="ciclos-grid">
                <?php if (empty($ciclos)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-calendar-circle-plus"></i>
                        <h4>No hay ciclos registrados</h4>
                        <p>Comienza creando un nuevo ciclo escolar.</p>
                        <button class="btn-agregar-empty" id="btnNuevoCicloEmpty">
                            <i class="fa-solid fa-plus"></i> Crear primer ciclo
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($ciclos as $ciclo): ?>
                    <div class="ciclo-card" data-id="<?php echo $ciclo['id_ciclo']; ?>">
                        <div class="ciclo-header">
                            <div>
                                <h4 class="ciclo-nombre"><?php echo htmlspecialchars($ciclo['nombre']); ?></h4>
                                <p class="ciclo-id">Identificador: <?php echo $ciclo['id_ciclo']; ?></p>
                            </div>
                            <span class="badge <?php 
                                echo ($ciclo['estado'] === 'Activo') ? 'badge-activo' : 
                                    (($ciclo['estado'] === 'Cerrado') ? 'badge-cerrado' : 'badge-inactivo'); 
                            ?>">
                                <?php echo $ciclo['estado']; ?>
                            </span>
                        </div>

                        <div class="ciclo-fechas">
                            <div class="fecha-item">
                                <i class="fa-regular fa-calendar"></i>
                                <span>Inicio: <strong><?php echo date('d M Y', strtotime($ciclo['fecha_inicio'])); ?></strong></span>
                            </div>
                            <div class="fecha-item">
                                <i class="fa-regular fa-calendar"></i>
                                <span>Finalización: <strong><?php echo date('d M Y', strtotime($ciclo['fecha_fin'])); ?></strong></span>
                            </div>
                        </div>

                        <div class="ciclo-estadisticas">
                            <div class="stat-item">
                                <span class="stat-numero"><?php echo $ciclo['total_periodos'] ?? 0; ?></span>
                                <span class="stat-etiqueta">Periodos</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-numero"><?php echo $ciclo['total_grupos'] ?? 0; ?></span>
                                <span class="stat-etiqueta">Grupos</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-numero"><?php echo $ciclo['total_cursos'] ?? 0; ?></span>
                                <span class="stat-etiqueta">Cursos</span>
                            </div>
                        </div>

                        <div class="ciclo-acciones">
                            <button class="btn-editar" data-id="<?php echo $ciclo['id_ciclo']; ?>">
                                <i class="fa-regular fa-pen-to-square"></i> Editar
                            </button>
                            <?php if ($ciclo['estado'] !== 'Cerrado'): ?>
                            <button class="btn-cerrar" data-id="<?php echo $ciclo['id_ciclo']; ?>">
                                <i class="fa-solid fa-lock"></i> Cerrar ciclo
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MODAL PARA NUEVO / EDITAR CICLO (FLOTANTE)-->
        <!-- ========================================== -->
        <div class="modal-overlay" id="modalCiclo">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitulo">Nuevo ciclo</h2>
                    <button class="modal-cerrar" id="modalCerrar">&times;</button>
                </div>
                <form id="formCiclo" method="POST" action="logica/procesar_ciclos.php">
                    <input type="hidden" name="accion" id="modalAccion" value="guardar">
                    <input type="hidden" name="id" id="modalId" value="">

                    <div class="form-group">
                        <label for="modalNombre">Nombre del ciclo <span class="text-danger">*</span></label>
                        <input type="text" id="modalNombre" name="nombre" placeholder="Ej. Ciclo escolar 2026-2027" required>
                    </div>

                    <div class="form-group">
                        <label for="modalInicio">Fecha de inicio <span class="text-danger">*</span></label>
                        <input type="date" id="modalInicio" name="fecha_inicio" required>
                    </div>

                    <div class="form-group">
                        <label for="modalFin">Fecha de finalización <span class="text-danger">*</span></label>
                        <input type="date" id="modalFin" name="fecha_fin" required>
                    </div>

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
                                <input type="radio" name="estado" value="Cerrado">
                                <i class="fa-solid fa-lock"></i> Cerrado
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

        <!-- ========================================== -->
        <!-- BARRA DE ACCESIBILIDAD                     -->
        <!-- ========================================== -->
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
<script src="js/ciclos.js"></script>
<!-- LECTOR DE PANTALLA -->
<script src="js/lector.js"></script>
</body>
</html>