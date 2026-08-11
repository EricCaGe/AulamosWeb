<?php
session_start();

// =====================================================
// VERIFICAR SESIÓN DOCENTE
// =====================================================
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

require_once '../Conexion/conexion.php';

$id_docente = (int) $_SESSION['usuario']['id_usuario'];

$id_materia = isset($_GET['id_materia']) ? intval($_GET['id_materia']) : 0;
$id_grupo = isset($_GET['id_grupo']) ? intval($_GET['id_grupo']) : 0;
$id_ciclo = isset($_GET['id_ciclo']) ? intval($_GET['id_ciclo']) : 0;

// =====================================================
// VALIDAR PARÁMETROS
// =====================================================
if ($id_materia <= 0 || $id_grupo <= 0 || $id_ciclo <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
    exit;
}

// =====================================================
// BUSCAR CURSO (VERIFICANDO QUE PERTENEZCA AL DOCENTE)
// =====================================================
$stmt = $conexion->prepare("
    SELECT id_curso, nombre
    FROM cursos
    WHERE id_materia = ? 
      AND id_grupo = ? 
      AND id_ciclo = ?
      AND id_docente = ?
      AND estado = 'Activo'
    LIMIT 1
");

$stmt->bind_param("iiii", $id_materia, $id_grupo, $id_ciclo, $id_docente);
$stmt->execute();
$result = $stmt->get_result();
$curso = $result->fetch_assoc();
$stmt->close();

header('Content-Type: application/json');

if ($curso) {
    echo json_encode([
        'success' => true,
        'id_curso' => (int) $curso['id_curso'],
        'nombre' => $curso['nombre']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'id_curso' => null,
        'error' => 'No se encontró un curso activo para estos parámetros'
    ]);
}
?>