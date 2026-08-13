<?php
// ========================================== */
// OBTENER PREFERENCIAS DE UN ESTUDIANTE     */
// ========================================== */

session_start();

// Verificar sesión
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Incluir conexión
require_once '../../Conexion/conexion.php';

$id_usuario = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_usuario <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ID de usuario inválido']);
    exit;
}

try {
    $stmt = $conexion->prepare("
        SELECT 
            id_preferencia,
            id_usuario,
            alto_contraste,
            modo_oscuro,
            tamano_texto,
            fuente_dislexia,
            lector_pantalla,
            velocidad_lectura,
            subtitulos,
            idioma,
            animaciones,
            navegacion_teclado,
            fecha_actualizacion
        FROM preferencias_accesibilidad
        WHERE id_usuario = ?
    ");

    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . $conexion->error);
    }

    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $preferencias = $resultado->fetch_assoc();
    $stmt->close();

    header('Content-Type: application/json');

    if ($preferencias) {
        echo json_encode([
            'success' => true,
            'preferencias' => $preferencias
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No se encontraron preferencias para este estudiante'
        ]);
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conexion->close();
?>