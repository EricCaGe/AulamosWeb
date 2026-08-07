<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../../InicioSesion/login.php');
    exit;
}

// ========================================== */
// OBTENER DATOS DEL FORMULARIO              */
// ========================================== */

$idioma = $_POST['idioma'] ?? 'es';
$tamano_texto = $_POST['tamano_texto'] ?? 'normal';

// ✅ NUEVAS: Preferencias de contraste personalizado
$contraste_fondo = $_POST['contraste_fondo'] ?? 'negro';
$contraste_color = $_POST['contraste_color'] ?? 'azul';

// Mantener modo oscuro desde la sesión (no se modifica aquí)
$modo_oscuro = $_SESSION['preferencias']['modo_oscuro'] ?? 0;

// Mantener alto contraste desde la sesión (no se modifica aquí)
$alto_contraste = $_SESSION['preferencias']['alto_contraste'] ?? 0;

// ========================================== */
// GUARDAR EN SESIÓN                         */
// ========================================== */

$_SESSION['preferencias'] = [
    'modo_oscuro' => $modo_oscuro,
    'tamano_texto' => $tamano_texto,
    'alto_contraste' => $alto_contraste,
    'idioma' => $idioma
];

// ✅ NUEVAS: Guardar colores de contraste en sesión
$_SESSION['contraste_fondo'] = $contraste_fondo;
$_SESSION['contraste_color'] = $contraste_color;

// ========================================== */
// REDIRIGIR                                 */
// ========================================== */

$referer = $_SERVER['HTTP_REFERER'] ?? '../configuracion.php';
if (empty($referer) || strpos($referer, 'procesar_configuracion.php') !== false) {
    $referer = '../configuracion.php';
}

header('Location: ' . $referer);
exit;
?>