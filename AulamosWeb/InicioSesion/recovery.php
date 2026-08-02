<?php
// ========================================== */
// RECOVERY - ENVÍO DE CORREO CON PHPMailer  */
// ========================================== */

require_once '../Conexion/conexion.php';

// Cargar PHPMailer manualmente (sin Composer)
require '../PHPMailer-master/Exception.php';
require '../PHPMailer-master/PHPMailer.php';
require '../PHPMailer-master/SMTP.php';

// Cargar PHPMailer manualmente
require '../PHPMailer-master/Exception.php';
require '../PHPMailer-master/PHPMailer.php';
require '../PHPMailer-master/SMTP.php';

// ========================================== */
// FUNCIÓN PARA ENVIAR CORREO                */
// ========================================== */

function enviarCorreoRecuperacion($correo_destino, $nombre, $enlace) {
    $mail = new PHPMailer(true);

    try {
        // ========================================== */
        // CONFIGURACIÓN DEL SERVIDOR SMTP           */
        // ========================================== */
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '260110496@itsoeh.edu.mx';        // 🔴 CAMBIA ESTO
        $mail->Password   = 'sbob byyx xbvt nbec';         // 🔴 CAMBIA ESTO
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // ========================================== */
        // REMITENTE Y DESTINATARIO                   */
        // ========================================== */
        
        $mail->setFrom('260110496@itsoeh.edu.mx', 'AULAMOS'); // 🔴 CAMBIA ESTO
        $mail->addAddress($correo_destino, $nombre);
        $mail->addReplyTo('260110496@itsoeh.edu.mx', 'AULAMOS'); // 🔴 CAMBIA ESTO

        // ========================================== */
        // CONTENIDO DEL CORREO                      */
        // ========================================== */
        
        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de contraseña - AULAMOS';
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
                        <p>Recuperación de contraseña</p>
                    </div>
                    <div class='content'>
                        <p>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
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
        $mail->AltBody = "Restablece tu contraseña en: " . $enlace;

        // ========================================== */
        // ENVIAR CORREO                             */
        // ========================================== */
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
        return false;
    }
}

// ========================================== */
// SI SE LLAMA DIRECTAMENTE (PRUEBA)         */
// ========================================== */

// Si quieres probar el envío de correo manualmente:
/*
$enlace_prueba = "http://localhost/AulamosWeb/InicioSesion/restablecer.php?token=abc123";
$resultado = enviarCorreoRecuperacion('tucorreo@example.com', 'Usuario Prueba', $enlace_prueba);

if ($resultado) {
    echo "✅ Correo enviado correctamente.";
} else {
    echo "❌ Error al enviar correo.";
}
*/
?>