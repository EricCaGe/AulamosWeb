<?php
session_start();
require_once '../Conexion/conexion.php';

if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$id_usuario = $_SESSION['usuario']['id_usuario'];
$campo = $_POST['campo'] ?? '';
$valor = $_POST['valor'] ?? '';

// ✅ CAMPOS PERMITIDOS (agregamos contraste_fondo y contraste_color)
$campos_permitidos = [
    'alto_contraste',
    'modo_oscuro',
    'tamano_texto',
    'lector_pantalla',
    'subtitulos',
    'navegacion_teclado',
    'contraste_fondo',
    'contraste_color'
];

if (!in_array($campo, $campos_permitidos)) {
    echo json_encode(['success' => false, 'error' => 'Campo no permitido']);
    exit;
}

// Convertir valor (para booleanos)
if ($valor === 'true' || $valor === '1') {
    $valor = 1;
} elseif ($valor === 'false' || $valor === '0') {
    $valor = 0;
}
// Si es string (color o fondo), se guarda como está

$stmt = $conexion->prepare("SELECT id_preferencia FROM preferencias_accesibilidad WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt = $conexion->prepare("UPDATE preferencias_accesibilidad SET $campo = ? WHERE id_usuario = ?");
    
    if ($valor === 1 || $valor === 0) {
        $stmt->bind_param("ii", $valor, $id_usuario);
    } else {
        $stmt->bind_param("si", $valor, $id_usuario);
    }
} else {
    $stmt = $conexion->prepare("INSERT INTO preferencias_accesibilidad (id_usuario, $campo) VALUES (?, ?)");
    
    if ($valor === 1 || $valor === 0) {
        $stmt->bind_param("ii", $id_usuario, $valor);
    } else {
        $stmt->bind_param("is", $id_usuario, $valor);
    }
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conexion->close();
?>