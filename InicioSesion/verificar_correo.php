<?php
// ========================================== */
// VERIFICAR CORREO ELECTRÓNICO
// ========================================== */

require_once '../Conexion/conexion.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die('❌ Token inválido o no proporcionado.');
}

// Buscar el token en la BD
$stmt = $conexion->prepare("SELECT id_usuario, correo FROM usuarios WHERE token_verificacion = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
$stmt->close();

if (!$usuario) {
    die('❌ Token inválido o expirado.');
}

// Actualizar usuario como verificado
$stmt = $conexion->prepare("UPDATE usuarios SET verificado = 1, token_verificacion = NULL WHERE id_usuario = ?");
$stmt->bind_param("i", $usuario['id_usuario']);
$stmt->execute();
$stmt->close();

// Mostrar mensaje de éxito
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta verificada - AULAMOS</title>
    <link rel="stylesheet" href="styles/login.css">
</head>
<body>
    <div style="display:flex; justify-content:center; align-items:center; min-height:100vh; background:#f3f4f6;">
        <div style="background:white; padding:40px; border-radius:20px; text-align:center; max-width:450px; box-shadow:0 4px 20px rgba(0,0,0,0.1);">
            <div style="font-size:60px; margin-bottom:20px;">✅</div>
            <h2 style="color:#1e293b;">¡Cuenta verificada!</h2>
            <p style="color:#64748b; margin:15px 0 25px;">
                Tu correo <strong><?php echo htmlspecialchars($usuario['correo']); ?></strong> ha sido verificado correctamente.
            </p>
            <p style="color:#64748b; margin-bottom:25px;">Ahora puedes iniciar sesión en AULAMOS.</p>
            <a href="login.php" style="display:inline-block; background:#3b71f3; color:white; padding:12px 32px; border-radius:10px; text-decoration:none; font-weight:600;">
                Ir a iniciar sesión
            </a>
        </div>
    </div>
</body>
</html>