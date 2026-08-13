<?php
session_start();

// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

// Verificar sesión y rol
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$titulo_pagina = 'Pruebas de investigación';
$descripcion_pagina = 'Crea y administra las pruebas utilizadas para analizar el uso de AULAMOS.';

// =====================================================
// OBTENER PRUEBAS
// =====================================================

$stmt = $conexion->prepare("
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM participantes_prueba WHERE id_prueba = p.id_prueba) AS participantes,
        (SELECT COUNT(*) FROM participantes_prueba WHERE id_prueba = p.id_prueba AND consentimiento = 1) AS consentimientos
    FROM pruebas_investigacion p
    ORDER BY p.fecha_inicio DESC
");
$stmt->execute();
$resultado = $stmt->get_result();
$pruebas = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// =====================================================
// PROCESAR FORMULARIOS
// =====================================================

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // =====================================================
    // CREAR PRUEBA
    // =====================================================
    if (isset($_POST['accion']) && $_POST['accion'] === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $hipotesis = trim($_POST['hipotesis'] ?? '');
        $objetivo = trim($_POST['objetivo'] ?? '');
        $version_wcag = trim($_POST['version_wcag'] ?? 'WCAG 2.1');
        $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
        $fecha_fin = trim($_POST['fecha_fin'] ?? '');
        $estado = 'Planeada';

        if (empty($nombre) || empty($hipotesis) || empty($fecha_inicio)) {
            $mensaje = 'El nombre, la hipótesis y la fecha de inicio son obligatorios.';
            $tipo_mensaje = 'error';
        } else {
            $stmt = $conexion->prepare("
                INSERT INTO pruebas_investigacion (
                    nombre, descripcion, hipotesis, objetivo, version_wcag,
                    fecha_inicio, fecha_fin, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssssssss", $nombre, $descripcion, $hipotesis, $objetivo, $version_wcag, $fecha_inicio, $fecha_fin, $estado);
            
            if ($stmt->execute()) {
                $mensaje = 'Prueba creada correctamente.';
                $tipo_mensaje = 'exito';
                // Recargar pruebas después de crear
                header('Location: pruebas_investigacion.php');
                exit;
            } else {
                $mensaje = 'Error al crear la prueba: ' . $stmt->error;
                $tipo_mensaje = 'error';
            }
            $stmt->close();
        }
    }
}

function colorEstado($estado) {
    switch ($estado) {
        case 'Activa': return '#15803D';
        case 'Finalizada': return '#475569';
        default: return '#D97706';
    }
}

function badgeEstado($estado) {
    switch ($estado) {
        case 'Activa': return 'badge-activa';
        case 'Finalizada': return 'badge-finalizada';
        default: return 'badge-planeada';
    }
}

function formatoFecha($fecha) {
    if (!$fecha) return 'Sin fecha';
    $timestamp = strtotime($fecha);
    return date('d/m/Y', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pruebas de investigación - Investigador</title>
    
    <link rel="stylesheet" href="styles/pruebas_investigacion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
</head>
<body>

<div class="dashboard-container">
    
    <!-- ===== SIDEBAR ===== -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <main class="main-content">
        
        <!-- ===== HEADER ===== -->
        <?php include 'includes/header.php'; ?>

        <!-- ===== MENSAJES ===== -->
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- ===== ENCABEZADO ===== -->
        <div class="pruebas-header">
            <div class="header-info">
                <span class="cantidad"><?php echo count($pruebas); ?> <?php echo count($pruebas) === 1 ? 'prueba registrada' : 'pruebas registradas'; ?></span>
                <p class="subtitulo">Administra los estudios realizados con los estudiantes.</p>
            </div>
            <button class="btn-nueva" id="btnNuevaPrueba">
                <i class="fa-solid fa-plus"></i> Nueva prueba
            </button>
        </div>

        <!-- ===== LISTA DE PRUEBAS ===== -->
        <section class="pruebas-list">
            <?php if (empty($pruebas)): ?>
                <div class="sin-datos">
                    <i class="fa-solid fa-flask"></i>
                    <p>No hay pruebas de investigación registradas.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pruebas as $prueba): ?>
                    <div class="prueba-card" data-id="<?php echo $prueba['id_prueba']; ?>">
                        <div class="prueba-header">
                            <div class="prueba-icono">
                                <i class="fa-solid fa-flask"></i>
                            </div>
                            <div class="prueba-info">
                                <h3><?php echo htmlspecialchars($prueba['nombre']); ?></h3>
                                <span class="badge <?php echo badgeEstado($prueba['estado']); ?>">
                                    <?php echo $prueba['estado']; ?>
                                </span>
                            </div>
                        </div>

                        <div class="prueba-datos">
                            <div class="dato-item">
                                <i class="fa-solid fa-universal-access"></i>
                                <span><?php echo htmlspecialchars($prueba['version_wcag']); ?></span>
                            </div>
                            <div class="dato-item">
                                <i class="fa-solid fa-calendar"></i>
                                <span><?php echo formatoFecha($prueba['fecha_inicio']); ?> - <?php echo formatoFecha($prueba['fecha_fin']); ?></span>
                            </div>
                            <div class="dato-item">
                                <i class="fa-solid fa-users"></i>
                                <span><?php echo $prueba['participantes'] ?? 0; ?> participantes</span>
                            </div>
                            <div class="dato-item">
                                <i class="fa-solid fa-check-circle"></i>
                                <span><?php echo $prueba['consentimientos'] ?? 0; ?> consentimientos</span>
                            </div>
                        </div>

                        <div class="prueba-detalle">
                            <div class="detalle-bloque">
                                <span class="detalle-label">Hipótesis</span>
                                <p><?php echo htmlspecialchars($prueba['hipotesis']); ?></p>
                            </div>
                            <?php if (!empty($prueba['objetivo'])): ?>
                                <div class="detalle-bloque">
                                    <span class="detalle-label">Objetivo</span>
                                    <p><?php echo htmlspecialchars($prueba['objetivo']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="prueba-acciones">
                            <?php if ($prueba['estado'] === 'Activa'): ?>
                                <button class="btn-cambiar-estado btn-finalizar js-cambiar-estado" 
                                        data-id="<?php echo $prueba['id_prueba']; ?>" 
                                        data-estado="Finalizada"
                                        data-nombre="<?php echo htmlspecialchars($prueba['nombre']); ?>">
                                    <i class="fa-solid fa-stop-circle"></i> Finalizar prueba
                                </button>
                            <?php else: ?>
                                <button class="btn-cambiar-estado btn-activar js-cambiar-estado" 
                                        data-id="<?php echo $prueba['id_prueba']; ?>" 
                                        data-estado="Activa"
                                        data-nombre="<?php echo htmlspecialchars($prueba['nombre']); ?>">
                                    <i class="fa-solid fa-play-circle"></i> Activar prueba
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- ===== BARRA DE ACCESIBILIDAD ===== -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </main>
</div>

<!-- ===== BOTÓN FLOTANTE ===== -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<!-- ========================================== -->
<!-- MODAL CREAR PRUEBA                        -->
<!-- ========================================== -->
<div id="modalPrueba" class="modal-overlay modal-hidden">
    <div class="modal-container">
        <div class="modal-header">
            <h2><i class="fa-solid fa-flask"></i> Nueva prueba</h2>
            <button class="modal-cerrar" id="modalCerrar">&times;</button>
        </div>
        <form method="POST" action="" class="modal-form">
            <input type="hidden" name="accion" value="crear">

            <div class="form-group">
                <label for="nombre">Nombre <span class="text-danger">*</span></label>
                <input type="text" id="nombre" name="nombre" placeholder="Ej. Evaluación de accesibilidad 2026" required>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="2" placeholder="Describe brevemente la prueba"></textarea>
            </div>

            <div class="form-group">
                <label for="hipotesis">Hipótesis <span class="text-danger">*</span></label>
                <textarea id="hipotesis" name="hipotesis" rows="2" placeholder="Escribe la hipótesis de investigación" required></textarea>
            </div>

            <div class="form-group">
                <label for="objetivo">Objetivo</label>
                <textarea id="objetivo" name="objetivo" rows="2" placeholder="Escribe el objetivo de la prueba"></textarea>
            </div>

            <div class="form-group">
                <label for="version_wcag">Versión WCAG</label>
                <input type="text" id="version_wcag" name="version_wcag" value="WCAG 2.1">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fecha_inicio">Fecha de inicio <span class="text-danger">*</span></label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" required>
                </div>
                <div class="form-group">
                    <label for="fecha_fin">Fecha de fin</label>
                    <input type="date" id="fecha_fin" name="fecha_fin">
                </div>
            </div>

            <div class="form-group estado-inicial">
                <label>Estado inicial</label>
                <div class="estado-badge-planeada">
                    <span class="punto-planeada"></span>
                    <span>Planeada</span>
                </div>
                <p class="ayuda-texto">Cuando la prueba esté lista, podrás activarla desde la lista.</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancelar" id="modalCancelar">Cancelar</button>
                <button type="submit" class="btn-guardar">Crear prueba</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== SCRIPTS ===== -->
<script src="js/pruebas_investigacion.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>

</body>
</html>