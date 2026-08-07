<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../login.php?error=no_autorizado');
    exit;
}

require_once '../../Conexion/conexion.php';

$id_usuario = $_SESSION['usuario']['id_usuario'];
$nombre = trim($_POST['nombre'] ?? '');
$apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
$apellido_materno = trim($_POST['apellido_materno'] ?? '');

if (empty($nombre) || empty($apellido_paterno)) {
    header('Location: ../perfil.php?mensaje=Nombre y apellido paterno son obligatorios&tipo=error');
    exit;
}

try {
    $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, apellido_paterno = ?, apellido_materno = ? WHERE id_usuario = ?");
    $stmt->bind_param("sssi", $nombre, $apellido_paterno, $apellido_materno, $id_usuario);
    $stmt->execute();
    $stmt->close();

    $_SESSION['usuario']['nombre'] = $nombre;
    $_SESSION['usuario']['apellido_paterno'] = $apellido_paterno;
    $_SESSION['usuario']['apellido_materno'] = $apellido_materno;

    header('Location: ../perfil.php?mensaje=Perfil actualizado correctamente&tipo=exito');
    exit;

} catch (Exception $e) {
    header('Location: ../perfil.php?mensaje=Error al actualizar: ' . $e->getMessage() . '&tipo=error');
    exit;
}
?>