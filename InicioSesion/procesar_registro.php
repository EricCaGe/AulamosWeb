<?php
// ========================================== */
// PROCESAR REGISTRO                         */
// ========================================== */

require_once '../Conexion/conexion.php';

// Cargar PHPMailer
require '../PHPMailer-master/Exception.php';
require '../PHPMailer-master/PHPMailer.php';
require '../PHPMailer-master/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Verificar que se envió el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro.php?error=sesion');
    exit;
}

// Obtener datos del formulario
$rol = $_POST['rol'] ?? 'Alumno';
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';
$nombre = trim($_POST['nombre'] ?? '');
$apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
$apellido_materno = trim($_POST['apellido_materno'] ?? '');

// Validar que los campos no estén vacíos
if (empty($correo) || empty($password) || empty($nombre) || empty($apellido_paterno)) {
    header('Location: registro.php?error=campos_vacios');
    exit;
}

// Validar que el rol sea válido
if (!in_array($rol, ['Alumno', 'Docente'])) {
    $rol = 'Alumno';
}

// Validar formato de correo
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header('Location: registro.php?error=correo_invalido');
    exit;
}

// ========================================== */
// VALIDACIÓN DE CONTRASEÑA (REQUISITOS)
// ========================================== */

$specialChars = '/[!@#$%^&*_\-]/';

if (strlen($password) < 8) {
    header('Location: registro.php?error=password_corta');
    exit;
}

if (!preg_match('/[A-Z]/', $password)) {
    header('Location: registro.php?error=password_mayuscula');
    exit;
}

if (!preg_match('/[a-z]/', $password)) {
    header('Location: registro.php?error=password_minuscula');
    exit;
}

if (!preg_match('/[0-9]/', $password)) {
    header('Location: registro.php?error=password_numero');
    exit;
}

if (!preg_match($specialChars, $password)) {
    header('Location: registro.php?error=password_especial');
    exit;
}

try {
    // ========================================== */
    // VERIFICAR SI EL CORREO YA EXISTE          */
    // ========================================== */
    $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->fetch_assoc()) {
        header('Location: registro.php?error=correo_existe');
        exit;
    }
    $stmt->close();
    
    // ========================================== */
    // INSERTAR USUARIO                          */
    // ========================================== */
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conexion->prepare("
        INSERT INTO usuarios (
            nombre, 
            apellido_paterno, 
            apellido_materno, 
            correo, 
            password_hash, 
            estado,
            verificado,
            fecha_registro
        ) VALUES (?, ?, ?, ?, ?, 'Activo', 0, NOW())
    ");
    $stmt->bind_param("sssss", $nombre, $apellido_paterno, $apellido_materno, $correo, $password_hash);
    $stmt->execute();
    $id_usuario = $conexion->insert_id;
    $stmt->close();
    
    // ========================================== */
    // ASIGNAR ROL                               */
    // ========================================== */
    
    if ($rol === 'Alumno') {
        $id_rol = 1;
    } else {
        $id_rol = 2;
    }
    
    $stmt = $conexion->prepare("INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (?, ?)");
    $stmt->bind_param("ii", $id_usuario, $id_rol);
    $stmt->execute();
    $stmt->close();
    
    // ========================================== */
    // GENERAR TOKEN DE VERIFICACIÓN             */
    // ========================================== */
    
    $token = bin2hex(random_bytes(32));
    
    $stmt = $conexion->prepare("UPDATE usuarios SET token_verificacion = ? WHERE id_usuario = ?");
    $stmt->bind_param("si", $token, $id_usuario);
    $stmt->execute();
    $stmt->close();
    
    // ========================================== */
    // ENVIAR CORREO DE VERIFICACIÓN             */
    // ========================================== */
    
    $mail = new PHPMailer(true);
    $correo_enviado = false;

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '230110496@itsoeh.edu.mx';
        $mail->Password   = 'sbob byyx xbvt nbec';  // ← Tu contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('230110496@itsoeh.edu.mx', 'AULAMOS');
        $mail->addAddress($correo, $nombre . ' ' . $apellido_paterno);
        $mail->addReplyTo('230110496@itsoeh.edu.mx', 'AULAMOS');

        $enlace_verificacion = "http://localhost/AulamosWeb/InicioSesion/verificar_correo.php?token=" . $token;

        $mail->isHTML(true);
        $mail->Subject = '✅ Verifica tu cuenta - AULAMOS';
        $mail->Body = "
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
                        <p>Verificación de cuenta</p>
                    </div>
                    <div class='content'>
                        <p>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
                        <p>Gracias por registrarte en <strong>AULAMOS</strong>. Para activar tu cuenta, haz clic en el siguiente enlace:</p>
                        <p style='text-align: center;'>
                            <a href='" . $enlace_verificacion . "' class='btn'>Verificar mi cuenta</a>
                        </p>
                        <p>O copia y pega este enlace en tu navegador:</p>
                        <p style='background: #eee; padding: 10px; word-break: break-all; font-size: 12px;'>
                            " . $enlace_verificacion . "
                        </p>
                        <p><strong>Este enlace expirará en 24 horas.</strong></p>
                        <p>Si no creaste una cuenta en AULAMOS, ignora este mensaje.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2024 AULAMOS - Todos los derechos reservados</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = "Verifica tu cuenta en: " . $enlace_verificacion;

        $mail->send();
        $correo_enviado = true;
        
    } catch (PHPMailerException $e) {
        error_log("Error al enviar correo de verificación: " . $mail->ErrorInfo);
        $correo_enviado = false;
    }
    
    // ========================================== */
    // REDIRIGIR AL LOGIN                        */
    // ========================================== */
    
    if ($correo_enviado) {
        header('Location: login.php?registro=exitoso&verificacion=enviada');
    } else {
        header('Location: login.php?registro=exitoso&verificacion=error');
    }
    exit;
    
} catch(Exception $e) {
    error_log("Error en registro: " . $e->getMessage());
    header('Location: registro.php?error=sesion');
    exit;
}
?>