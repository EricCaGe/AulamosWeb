<?php
// ========================================== */
// OBTENER PREFERENCIAS DE UN ESTUDIANTE     */
// ========================================== */

session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

require_once '../../Conexion/conexion.php';

$id_usuario = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_usuario <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de usuario inválido']);
    exit;
}

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

$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$preferencias = $resultado->fetch_assoc();
$stmt->close();

if ($preferencias) {
    echo json_encode(['success' => true, 'preferencias' => $preferencias]);
} else {
    echo json_encode(['success' => false, 'error' => 'No se encontraron preferencias']);
}
?>