<?php
// Iniciamos la sesión para poder identificarla y destruirla
session_start();

// Vaciamos las variables de sesión
session_unset();

// Destruimos la sesión completamente
session_destroy();

// Redirigimos al login (que está en esta misma carpeta)
header("Location: login.php");
exit();
?>