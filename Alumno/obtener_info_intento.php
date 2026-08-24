<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Alumno') {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once '../Conexion/conexion.php';

$id_asignacion = isset($_GET['id_asignacion']) ? intval($_GET['id_asignacion']) : 0;

if ($id_asignacion <= 0) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

// Obtener el número de intento actual y estadísticas
$query = "
    SELECT 
        numero_intento,
        puntuacion,
        parejas_correctas,
        porcentaje,
        id_intento
    FROM conecta_intentos 
    WHERE id_asignacion = ? 
    ORDER BY id_intento DESC 
    LIMIT 1
";

$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $id_asignacion);
$stmt->execute();
$result = $stmt->get_result();
$intento = $result->fetch_assoc();
$stmt->close();

// También obtener el total de intentos permitidos
$query_max = "
    SELECT intentos_maximos 
    FROM conecta_juegos j
    JOIN conecta_asignaciones a ON j.id_juego = a.id_juego
    WHERE a.id_asignacion = ?
";

$stmt_max = $conexion->prepare($query_max);
$stmt_max->bind_param("i", $id_asignacion);
$stmt_max->execute();
$result_max = $stmt_max->get_result();
$juego = $result_max->fetch_assoc();
$stmt_max->close();

$conexion->close();

echo json_encode([
    'success' => true,
    'numero_intento' => $intento['numero_intento'] ?? 0,
    'puntuacion' => $intento['puntuacion'] ?? 0,
    'parejas_correctas' => $intento['parejas_correctas'] ?? 0,
    'porcentaje' => $intento['porcentaje'] ?? 0,
    'id_intento' => $intento['id_intento'] ?? 0,
    'intentos_maximos' => $juego['intentos_maximos'] ?? 0
]);
?>