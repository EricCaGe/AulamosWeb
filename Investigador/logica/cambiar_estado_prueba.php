<?php
session_start();

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

require_once '../../Conexion/conexion.php';

$id_prueba = isset($_POST['id_prueba']) ? (int)$_POST['id_prueba'] : 0;
$nuevo_estado = $_POST['estado'] ?? '';

$estados_validos = ['Planeada', 'Activa', 'Finalizada'];

if ($id_prueba <= 0 || !in_array($nuevo_estado, $estados_validos)) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

try {
    // Si se activa, desactivar otras pruebas activas
    if ($nuevo_estado === 'Activa') {
        $stmt = $conexion->prepare("UPDATE pruebas_investigacion SET estado = 'Finalizada' WHERE estado = 'Activa'");
        $stmt->execute();
        $stmt->close();
    }

    // Actualizar la prueba
    $stmt = $conexion->prepare("UPDATE pruebas_investigacion SET estado = ? WHERE id_prueba = ?");
    $stmt->bind_param("si", $nuevo_estado, $id_prueba);
    
    if ($stmt->execute()) {
        // Obtener los datos actualizados
        $stmt2 = $conexion->prepare("
            SELECT 
                p.*,
                (SELECT COUNT(*) FROM participantes_prueba WHERE id_prueba = p.id_prueba) AS participantes,
                (SELECT COUNT(*) FROM participantes_prueba WHERE id_prueba = p.id_prueba AND consentimiento = 1) AS consentimientos
            FROM pruebas_investigacion p
            WHERE p.id_prueba = ?
        ");
        $stmt2->bind_param("i", $id_prueba);
        $stmt2->execute();
        $resultado = $stmt2->get_result();
        $prueba = $resultado->fetch_assoc();
        $stmt2->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'prueba' => $prueba
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar']);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conexion->close();
?>