<?php
session_start();

// Verificar sesión
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../../Conexion/conexion.php';

$id_prueba = isset($_POST['id_prueba']) ? (int)$_POST['id_prueba'] : 0;
$nuevo_estado = $_POST['estado'] ?? '';

$estados_validos = ['Planeada', 'Activa', 'Finalizada'];

if ($id_prueba <= 0 || !in_array($nuevo_estado, $estados_validos)) {
    header('Location: ../pruebas_investigacion.php?mensaje=Datos inválidos&tipo=error');
    exit;
}

try {
    // Si se activa, desactivar otras pruebas activas
    if ($nuevo_estado === 'Activa') {
        $conexion->query("UPDATE pruebas_investigacion SET estado = 'Finalizada' WHERE estado = 'Activa'");
    }

    // Actualizar la prueba
    $stmt = $conexion->prepare("UPDATE pruebas_investigacion SET estado = ? WHERE id_prueba = ?");
    $stmt->bind_param("si", $nuevo_estado, $id_prueba);
    $stmt->execute();
    $stmt->close();

    header('Location: ../pruebas_investigacion.php?mensaje=Estado actualizado correctamente&tipo=exito');
    exit;

} catch (Exception $e) {
    header('Location: ../pruebas_investigacion.php?mensaje=Error: ' . $e->getMessage() . '&tipo=error');
    exit;
}
?>