<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

require_once '../../Conexion/conexion.php';

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM pruebas_investigacion");
$stmt->execute();
$resultado = $stmt->get_result();
$total = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM pruebas_investigacion WHERE estado = 'Activa'");
$stmt->execute();
$resultado = $stmt->get_result();
$activas = $resultado->fetch_assoc()['total'] ?? 0;
$stmt->close();

echo json_encode(['success' => true, 'total' => $total, 'activas' => $activas]);
$conexion->close();
?>