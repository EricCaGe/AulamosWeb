<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../../InicioSesion/login.php');
    exit;
}

require_once '../../Conexion/conexion.php';

$id_usuario = $_SESSION['usuario']['id_usuario'];

// Obtener datos del formulario
$tema = $_POST['tema'] ?? 'claro';
$idioma = $_POST['idioma'] ?? 'es';
$tamano_texto = $_POST['tamano_texto'] ?? 'normal';
$alto_contraste = isset($_POST['alto_contraste']) ? 1 : 0;

// Guardar en la tabla preferencias_accesibilidad
$stmt = $conexion->prepare("
    INSERT INTO preferencias_accesibilidad 
    (id_usuario, modo_oscuro, tamano_texto, alto_contraste, idioma) 
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
    modo_oscuro = VALUES(modo_oscuro),
    tamano_texto = VALUES(tamano_texto),
    alto_contraste = VALUES(alto_contraste),
    idioma = VALUES(idioma)
");

$modo_oscuro = ($tema === 'oscuro') ? 1 : 0;
$stmt->bind_param("issss", $id_usuario, $modo_oscuro, $tamano_texto, $alto_contraste, $idioma);
$stmt->execute();
$stmt->close();

header('Location: ../configuracion.php?mensaje=Configuración guardada correctamente&tipo=exito');
exit;
?>