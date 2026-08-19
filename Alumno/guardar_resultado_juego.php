<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Alumno') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../Conexion/conexion.php';

$id_alumno = $_SESSION['usuario']['id_usuario'];
$id_asignacion = isset($_POST['id_asignacion']) ? intval($_POST['id_asignacion']) : 0;
$id_juego = isset($_POST['id_juego']) ? intval($_POST['id_juego']) : 0;
$puntos = isset($_POST['puntos']) ? intval($_POST['puntos']) : 0;
$aciertos = isset($_POST['aciertos']) ? intval($_POST['aciertos']) : 0;
$errores = isset($_POST['errores']) ? intval($_POST['errores']) : 0;
$porcentaje = isset($_POST['porcentaje']) ? floatval($_POST['porcentaje']) : 0;
$tiempo = isset($_POST['tiempo']) ? intval($_POST['tiempo']) : 0;
$total_parejas = isset($_POST['total_parejas']) ? intval($_POST['total_parejas']) : 0;
$parejas_completadas = isset($_POST['parejas_completadas']) ? intval($_POST['parejas_completadas']) : 0;

if ($id_asignacion <= 0 || $id_juego <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

try {
    // Iniciar transacción
    $conexion->begin_transaction();
    
    // Obtener número de intento
    $query_intento = "SELECT COUNT(*) + 1 AS intento FROM conecta_intentos WHERE id_asignacion = ?";
    $stmt_intento = $conexion->prepare($query_intento);
    $stmt_intento->bind_param("i", $id_asignacion);
    $stmt_intento->execute();
    $result_intento = $stmt_intento->get_result();
    $row_intento = $result_intento->fetch_assoc();
    $numero_intento = $row_intento['intento'] ?? 1;
    $stmt_intento->close();
    
    // Insertar intento
    $insert = $conexion->prepare("
        INSERT INTO conecta_intentos (
            id_asignacion, numero_intento, puntuacion, parejas_correctas, 
            errores, tiempo_segundos, porcentaje
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->bind_param("iiiiiii", $id_asignacion, $numero_intento, $puntos, $parejas_completadas, $errores, $tiempo, $porcentaje);
    $insert->execute();
    $id_intento = $insert->insert_id;
    $insert->close();
    
    // Actualizar asignación
    $update = $conexion->prepare("
        UPDATE conecta_asignaciones 
        SET estado = 'Completado', fecha_finalizacion = NOW() 
        WHERE id_asignacion = ? AND id_alumno = ?
    ");
    $update->bind_param("ii", $id_asignacion, $id_alumno);
    $update->execute();
    $update->close();
    
    $conexion->commit();
    
    echo json_encode(['success' => true, 'message' => 'Resultado guardado correctamente', 'id_intento' => $id_intento]);
    
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conexion->close();
?>