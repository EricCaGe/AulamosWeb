<?php
// ========================================== */
// PROCESAR RECUPERAR CONTRASEÑA             */
// ========================================== */

// Activar errores para depurar
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../Conexion/conexion.php';

// Verificar que se envió el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: recuperar.php?error=sesion');
    exit;
}

// Obtener datos del formulario
$correo = trim($_POST['correo'] ?? '');

// Validar que el correo no esté vacío
if (empty($correo)) {
    header('Location: recuperar.php?error=correo_invalido');
    exit;
}

// Validar formato de correo
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header('Location: recuperar.php?error=correo_invalido');
    exit;
}

try {
    // ========================================== */
    // VERIFICAR SI EL CORREO EXISTE              */
    // ========================================== */
    $stmt = $conexion->prepare("SELECT id_usuario, nombre FROM usuarios WHERE correo = ? AND estado = 'Activo'");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();
    $stmt->close();
    
    if (!$usuario) {
        header('Location: recuperar.php?error=correo_no_existe');
        exit;
    }
    
    // ========================================== */
    // GENERAR TOKEN DE RECUPERACIÓN              */
    // ========================================== */
    
    // Generar token único
    $token = bin2hex(random_bytes(32));
    $token_hash = password_hash($token, PASSWORD_DEFAULT);
    
    // Fecha de expiración (1 hora)
    $expira_en = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Guardar token en la base de datos
    $stmt = $conexion->prepare("
        INSERT INTO tokens_recuperacion (id_usuario, token_hash, expira_en, usado, fecha_creacion) 
        VALUES (?, ?, ?, 0, NOW())
    ");
    $stmt->bind_param("iss", $usuario['id_usuario'], $token_hash, $expira_en);
    $stmt->execute();
    $stmt->close();
    
    // ========================================== */
    // CONSTRUIR ENLACE DE RECUPERACIÓN           */
    // ========================================== */
    
    // Obtener la URL base del sitio
    $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $ruta_base = '/AulamosWeb/InicioSesion/';
    $enlace = $protocolo . $host . $ruta_base . 'restablecer.php?token=' . $token;
    
    // ========================================== */
    // ENVIAR CORREO CON PHPMailer               */
    // ========================================== */
    
    // Incluir recovery.php y enviar el correo
    require_once 'recovery.php';
    $enviado = enviarCorreoRecuperacion($correo, $usuario['nombre'], $enlace);
    
    // ========================================== */
    // GUARDAR ENLACE EN SESIÓN (por si falla)   */
    // ========================================== */
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['enlace_recuperacion'] = $enlace;
    $_SESSION['correo_recuperacion'] = $correo;
    
    // ========================================== */
    // REDIRIGIR SEGÚN RESULTADO                 */
    // ========================================== */
    
    if ($enviado) {
        header('Location: recuperar_confirmacion.php?exito=correo_enviado');
    } else {
        // Si no se envió, mostramos el enlace para pruebas
        header('Location: recuperar_confirmacion.php?exito=correo_no_enviado');
    }
    exit;
    
} catch(Exception $e) {
    // Error de base de datos
    error_log("Error en recuperar: " . $e->getMessage());
    header('Location: recuperar.php?error=sesion');
    exit;
}
?>