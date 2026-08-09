<?php
require_once '../Conexion/conexion.php';

header('Content-Type: application/json');

$id_materia = isset($_GET['id_materia']) ? intval($_GET['id_materia']) : 0;
$id_grupo = isset($_GET['id_grupo']) ? intval($_GET['id_grupo']) : 0;
$id_ciclo = isset($_GET['id_ciclo']) ? intval($_GET['id_ciclo']) : 0;

if ($id_materia && $id_grupo && $id_ciclo) {
    $stmt = $conexion->prepare("
        SELECT id_curso
        FROM cursos
        WHERE id_materia = ? AND id_grupo = ? AND id_ciclo = ?
        LIMIT 1
    ");
    $stmt->bind_param("iii", $id_materia, $id_grupo, $id_ciclo);
    $stmt->execute();
    $result = $stmt->get_result();
    $curso = $result->fetch_assoc();

    if ($curso) {
        echo json_encode(['id_curso' => $curso['id_curso']]);
    } else {
        echo json_encode(['id_curso' => null]);
    }
} else {
    echo json_encode(['id_curso' => null]);
}
?>