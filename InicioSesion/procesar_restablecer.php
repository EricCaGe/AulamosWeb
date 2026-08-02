<?php
// ========================================== */
// PROCESAR RESTABLECER CONTRASEÑA           */
// ========================================== */

require_once '../Conexion/conexion.php';

// Verificar que se envió el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: restablecer.php?error=sesion');
    exit;
}

// Obtener datos
$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// Validar token
if (empty($token)) {
    header('Location: restablecer.php?error=token_invalido');
    exit;
}

// Validar contraseñas
if (strlen($password) < 8) {
    header('Location: restablecer.php?error=password_corta');
    exit;
}

if ($password !== $password_confirm) {
    header('Location: restablecer.php?error=no_coinciden');
    exit;
}

try {
    // Buscar token en la BD
    $stmt = $conexion->prepare("
        SELECT id_usuario, token_hash, expira_en, usado
        FROM tokens_recuperacion
        WHERE usado = 0 AND expira_en > NOW()
        ORDER BY fecha_creacion DESC
        LIMIT 1
    ");
    $stmt->execute();
    $resultado = $stmt->get_result();
    $token_data = $resultado->fetch_assoc();
    $stmt->close();

    if (!$token_data) {
        header('Location: restablecer.php?error=token_invalido');
        exit;
    }

    // Verificar si el token coincide (en texto plano vs hash)
    if (!password_verify($token, $token_data['token_hash'])) {
        header('Location: restablecer.php?error=token_invalido');
        exit;
    }

    // Verificar si expiró (ya lo hicimos en la consulta, pero por seguridad)
    if (strtotime($token_data['expira_en']) < time()) {
        header('Location: restablecer.php?error=token_expirado');
        exit;
    }

    // ========================================== */
    // ACTUALIZAR CONTRASEÑA                     */
    // ========================================== */

    $id_usuario = $token_data['id_usuario'];
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conexion->prepare("UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?");
    $stmt->bind_param("si", $password_hash, $id_usuario);
    $stmt->execute();
    $stmt->close();

    // Marcar token como usado
    $stmt = $conexion->prepare("UPDATE tokens_recuperacion SET usado = 1 WHERE id_usuario = ? AND usado = 0");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->close();

    // ========================================== */
    // REDIRIGIR AL LOGIN                        */
    // ========================================== */

    header('Location: login.php?restablecido=exitoso');
    exit;

} catch(Exception $e) {
    error_log("Error en restablecer: " . $e->getMessage());
    header('Location: restablecer.php?error=sesion');
    exit;
}
?>