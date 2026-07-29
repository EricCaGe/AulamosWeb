<?php
// ========================================== */
// PROCESAR RECUPERAR CONTRASEÑA             */
// ========================================== */

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
    // ENVIAR CORREO (SIMULADO)                   */
    // ========================================== */
    
    // En desarrollo, mostramos el enlace en pantalla
    // En producción, aquí iría el código de envío de correo con PHPMailer
    
    $mensaje = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #5a189a; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .btn { 
                display: inline-block; 
                padding: 12px 30px; 
                background: #5a189a; 
                color: white; 
                text-decoration: none; 
                border-radius: 8px;
                margin: 20px 0;
            }
            .footer { text-align: center; padding: 10px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>AULAMOS</h1>
                <p>Recuperación de contraseña</p>
            </div>
            <div class='content'>
                <p>Hola <strong>" . htmlspecialchars($usuario['nombre']) . "</strong>,</p>
                <p>Hemos recibido una solicitud para restablecer tu contraseña.</p>
                <p>Haz clic en el siguiente enlace para crear una nueva contraseña:</p>
                <p style='text-align: center;'>
                    <a href='" . $enlace . "' class='btn'>Restablecer contraseña</a>
                </p>
                <p>O copia y pega este enlace en tu navegador:</p>
                <p style='background: #eee; padding: 10px; word-break: break-all; font-size: 12px;'>
                    " . $enlace . "
                </p>
                <p><strong>Este enlace expirará en 1 hora.</strong></p>
                <p>Si no solicitaste este cambio, ignora este mensaje.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2024 AULAMOS - Todos los derechos reservados</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // ========================================== */
    // GUARDAR ENLACE EN SESIÓN PARA MOSTRARLO   */
    // ========================================== */
    
    // Iniciar sesión para guardar el enlace temporalmente
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Guardar el enlace para mostrarlo en una página de confirmación
    $_SESSION['enlace_recuperacion'] = $enlace;
    $_SESSION['correo_recuperacion'] = $correo;
    
    // ========================================== */
    // REDIRIGIR A PÁGINA DE CONFIRMACIÓN        */
    // ========================================== */
    
    header('Location: recuperar_confirmacion.php');
    exit;
    
} catch(Exception $e) {
    // Error de base de datos
    error_log("Error en recuperar: " . $e->getMessage());
    header('Location: recuperar.php?error=sesion');
    exit;
}
?>