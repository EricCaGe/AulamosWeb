<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../../InicioSesion/login.php');
    exit;
}

$tema = $_POST['tema'] ?? 'claro';
$idioma = $_POST['idioma'] ?? 'es';
$tamano_texto = $_POST['tamano_texto'] ?? 'normal';
$alto_contraste = isset($_POST['alto_contraste']) ? (int)$_POST['alto_contraste'] : 0;

$modo_oscuro = ($tema === 'oscuro') ? 1 : 0;

$_SESSION['preferencias'] = [
    'modo_oscuro' => $modo_oscuro,
    'tamano_texto' => $tamano_texto,
    'alto_contraste' => $alto_contraste,
    'idioma' => $idioma
];

$referer = $_SERVER['HTTP_REFERER'] ?? '../configuracion.php';
if (empty($referer) || strpos($referer, 'procesar_configuracion.php') !== false) {
    $referer = '../configuracion.php';
}

header('Location: ' . $referer);
exit;
?>