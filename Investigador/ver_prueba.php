<?php
session_start();
// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_prueba = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_prueba <= 0) {
    header('Location: pruebas_investigacion.php');
    exit;
}

$titulo_pagina = 'Detalle de prueba';
$descripcion_pagina = 'Gestiona los participantes de la prueba de investigación.';

// =====================================================
// OBTENER DATOS DE LA PRUEBA
// =====================================================

$stmt = $conexion->prepare("SELECT * FROM pruebas_investigacion WHERE id_prueba = ?");
$stmt->bind_param("i", $id_prueba);
$stmt->execute();
$resultado = $stmt->get_result();
$prueba = $resultado->fetch_assoc();
$stmt->close();

if (!$prueba) {
    header('Location: pruebas_investigacion.php');
    exit;
}

// =====================================================
// OBTENER PARTICIPANTES
// =====================================================

$participantes = $conexion->query("
    SELECT 
        pp.id_participante,
        u.id_usuario,
        u.nombre,
        u.apellido_paterno,
        u.apellido_materno,
        u.correo,
        u.estado AS cuenta_estado,
        pp.grupo_experimental,
        pp.consentimiento,
        pp.fecha_registro
    FROM participantes_prueba pp
    INNER JOIN usuarios u ON pp.id_usuario = u.id_usuario
    WHERE pp.id_prueba = $id_prueba
    ORDER BY u.nombre
")->fetch_all(MYSQLI_ASSOC);

// =====================================================
// OBTENER TODOS LOS ESTUDIANTES DISPONIBLES
// =====================================================

$todos_estudiantes = $conexion->query("
    SELECT u.id_usuario, 
           CONCAT(u.nombre, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS nombre_completo,
           u.correo,
           u.estado,
           (SELECT COUNT(*) FROM participantes_prueba WHERE id_usuario = u.id_usuario AND id_prueba = $id_prueba) AS ya_participa
    FROM usuarios u
    INNER JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
    WHERE ur.id_rol = 1 AND u.estado = 'Activo'
    ORDER BY u.nombre
")->fetch_all(MYSQLI_ASSOC);

// =====================================================
// ESTADÍSTICAS
// =====================================================

$total_participantes = count($participantes);
$total_consentimientos = 0;
foreach ($participantes as $p) {
    if ($p['consentimiento'] == 1) $total_consentimientos++;
}

// =====================================================
// PROCESAR ACTUALIZACIÓN DE PARTICIPANTES
// =====================================================

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_participantes') {
    
    $participantes_data = $_POST['participantes'] ?? [];
    
    // Primero, eliminar todos los participantes actuales de esta prueba
    $conexion->query("DELETE FROM participantes_prueba WHERE id_prueba = $id_prueba");
    
    // Insertar los nuevos participantes
    $insertados = 0;
    foreach ($participantes_data as $id_usuario => $data) {
        // Solo insertar si el checkbox de selección está marcado
        if (!isset($data['seleccionado']) || $data['seleccionado'] != 1) {
            continue;
        }
        
        $grupo = $data['grupo'] ?? 'Control';
        $consentimiento = isset($data['consentimiento']) ? 1 : 0;
        
        $stmt = $conexion->prepare("
            INSERT INTO participantes_prueba (id_prueba, id_usuario, grupo_experimental, consentimiento) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iisi", $id_prueba, $id_usuario, $grupo, $consentimiento);
        
        if ($stmt->execute()) {
            $insertados++;
        }
        $stmt->close();
    }
    
    $mensaje = "$insertados participantes actualizados correctamente.";
    $tipo_mensaje = 'exito';
    
    // Recargar datos
    header("Location: ver_prueba.php?id=$id_prueba&mensaje=" . urlencode($mensaje) . "&tipo=exito");
    exit;
}

// =====================================================
// FUNCIONES
// =====================================================

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
    <title>Detalle de prueba - Investigador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="styles/ver_prueba.css">
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
        <?php 
        $mensaje = $_GET['mensaje'] ?? $mensaje ?? '';
        $tipo_mensaje = $_GET['tipo'] ?? $tipo_mensaje ?? '';
        if ($mensaje): 
        ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- ===== DETALLE DE LA PRUEBA ===== -->
        <div class="prueba-detalle-header">
            <div class="info">
                <h2><?php echo htmlspecialchars($prueba['nombre']); ?></h2>
                <p><i class="fa-regular fa-calendar"></i> <?php echo formatoFecha($prueba['fecha_inicio']); ?> - <?php echo formatoFecha($prueba['fecha_fin']); ?> &nbsp;|&nbsp; <i class="fa-solid fa-universal-access"></i> <?php echo htmlspecialchars($prueba['version_wcag']); ?></p>
            </div>
            <div>
                <span class="badge <?php echo badgeEstado($prueba['estado']); ?>">
                    <?php echo $prueba['estado']; ?>
                </span>
                <a href="pruebas_investigacion.php" class="btn-volver">
                    <i class="fa-solid fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <!-- ===== DASHBOARD DE ESTADÍSTICAS (3 tarjetas) ===== -->
        <div class="stats-dashboard">
            <div class="stat-card-dash azul">
                <span class="stat-number"><?php echo $total_participantes; ?></span>
                <span class="stat-label">Seleccionados</span>
            </div>
            <div class="stat-card-dash naranja">
                <span class="stat-number"><?php echo count($todos_estudiantes); ?></span>
                <span class="stat-label">Disponibles</span>
            </div>
            <div class="stat-card-dash verde">
                <span class="stat-number"><?php echo $total_consentimientos; ?></span>
                <span class="stat-label">Consentimientos</span>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- FORMULARIO DE PARTICIPANTES               -->
        <!-- ========================================== -->
        <form method="POST" action="" id="formParticipantes">
            <input type="hidden" name="accion" value="guardar_participantes">
            
            <div class="participantes-container">
                <div class="participantes-header">
                    <div>
                        <h3><i class="fa-solid fa-users"></i> Participantes</h3>
                        <span class="badge-count"><?php echo $total_participantes; ?> estudiantes seleccionados de <?php echo count($todos_estudiantes); ?></span>
                    </div>
                    <button type="button" class="btn-seleccionar-todos" id="btnSeleccionarTodos">
                        <i class="fa-solid fa-check-double"></i> Seleccionar todos
                    </button>
                </div>

                <?php if (empty($todos_estudiantes)): ?>
                    <div class="empty-participantes">
                        <i class="fa-solid fa-user-slash"></i>
                        <p>No hay estudiantes disponibles para esta prueba.</p>
                    </div>
                <?php else: ?>
                    <div class="participantes-grid">
                        <?php foreach ($todos_estudiantes as $estudiante): 
                            $ya_participa = $estudiante['ya_participa'] > 0;
                            $participante_data = null;
                            foreach ($participantes as $p) {
                                if ($p['id_usuario'] == $estudiante['id_usuario']) {
                                    $participante_data = $p;
                                    break;
                                }
                            }
                            $grupo_actual = $participante_data['grupo_experimental'] ?? 'Control';
                            $consentimiento_actual = $participante_data['consentimiento'] ?? 0;
                        ?>
                            <div class="participante-card <?php echo $ya_participa ? 'seleccionado' : ''; ?>" id="card_<?php echo $estudiante['id_usuario']; ?>">
                                
                                <!-- Checkbox de selección -->
                                <div class="card-checkbox">
                                    <input type="checkbox" 
       name="participantes[<?php echo $estudiante['id_usuario']; ?>][seleccionado]" 
       value="1" <?php echo $ya_participa ? 'checked' : ''; ?> 
       class="checkbox-participante" 
       id="chk_<?php echo $estudiante['id_usuario']; ?>"
       data-id="<?php echo $estudiante['id_usuario']; ?>"
       onchange="toggleParticipante(this, <?php echo $estudiante['id_usuario']; ?>)">
                                </div>

                                <!-- Información del estudiante -->
                                <div class="card-info">
                                    <div class="card-nombre"><?php echo htmlspecialchars($estudiante['nombre_completo']); ?></div>
                                    <div class="card-correo"><?php echo htmlspecialchars($estudiante['correo']); ?></div>
                                    <div class="card-estado">
                                        <span class="estado-cuenta <?php echo $estudiante['estado'] === 'Activo' ? '' : 'inactivo'; ?>">
                                            <i class="fa-solid fa-circle" style="font-size: 8px;"></i> 
                                            Cuenta <?php echo $estudiante['estado']; ?>
                                        </span>
                                        <span class="badge-registrado" id="badge_<?php echo $estudiante['id_usuario']; ?>" 
      style="<?php echo $consentimiento_actual == 1 ? '' : 'display:none;'; ?>">
    <i class="fa-solid fa-check-circle"></i> Consentimiento registrado
</span>
                                    </div>
                                </div>

                                <!-- Grupo de investigación -->
                                <div class="card-grupo">
                                    <span class="grupo-label">GRUPO DE INVESTIGACIÓN</span>
                                    <div class="grupo-botones">
                                        <button type="button" 
        class="btn-grupo <?php echo $grupo_actual === 'Experimental' ? 'activo' : ''; ?>"
        onclick="seleccionarGrupo(<?php echo $estudiante['id_usuario']; ?>, 'Experimental')"
        id="btn_exp_<?php echo $estudiante['id_usuario']; ?>">
    <i class="fa-solid fa-flask"></i> Experimental
</button>
<button type="button" 
        class="btn-grupo <?php echo $grupo_actual === 'Control' ? 'activo' : ''; ?>"
        onclick="seleccionarGrupo(<?php echo $estudiante['id_usuario']; ?>, 'Control')"
        id="btn_control_<?php echo $estudiante['id_usuario']; ?>">
    <i class="fa-solid fa-users"></i> Control
</button>
                                    </div>
                                    <input type="hidden" 
                                           name="participantes[<?php echo $estudiante['id_usuario']; ?>][grupo]" 
                                           value="<?php echo $grupo_actual; ?>"
                                           class="input-grupo" id="input_grupo_<?php echo $estudiante['id_usuario']; ?>">
                                </div>

                                <!-- Consentimiento -->
                                <div class="card-consentimiento">
                                    <label class="consentimiento-check">
                                        <input type="checkbox" 
       name="participantes[<?php echo $estudiante['id_usuario']; ?>][consentimiento]" 
       value="1" <?php echo $consentimiento_actual == 1 ? 'checked' : ''; ?>
       id="consent_<?php echo $estudiante['id_usuario']; ?>"
       onchange="toggleConsentimiento(<?php echo $estudiante['id_usuario']; ?>, this.checked)">
                                        <span>Consentimiento</span>
                                    </label>
                                    <span class="estado-registro <?php echo $consentimiento_actual == 1 ? 'registrado' : 'no-registrado'; ?>" 
                                          id="estado_<?php echo $estudiante['id_usuario']; ?>">
                                        <?php if ($consentimiento_actual == 1): ?>
                                            <i class="fa-solid fa-check-circle"></i> Consentimiento registrado
                                        <?php else: ?>
                                            <i class="fa-regular fa-circle"></i> Sin consentimiento
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <a href="pruebas_investigacion.php" class="btn-cancelar">Cancelar</a>
                    <button type="submit" class="btn-guardar-participantes">
                        <i class="fa-solid fa-save"></i> Guardar participantes
                    </button>
                </div>
            </div>
        </form>

        <!-- ===== BARRA DE ACCESIBILIDAD ===== -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </main>
</div>

<!-- ===== BOTÓN FLOTANTE ===== -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<!-- ===== SCRIPTS ===== -->
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>
<script src="js/ver_prueba.js"></script>

</body>
</html>