<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enlace enviado - AULAMOS</title>
    <link rel="stylesheet" href="styles/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <header class="main-header">
        <div class="logo-container">
            <a href="../index.html">
                <img src="../img/logogeneral.png" alt="Logo AULAMOS" class="logo-img">
            </a>
        </div>
    </header>

    <div class="login-page-container">
        <div class="login-left-side">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="font-family: Georgia, serif; font-size: 32px; color: #111827; font-weight: bold; margin-bottom: 10px;">¡Enlace enviado!</h2>
            </div>
            <div class="illustration-container">
                <img src="../img/login.png" alt="Correo enviado" class="login-illustration">
            </div>
        </div>

        <div class="login-right-side">
            <div class="login-card">
                
                <div class="card-header">
                    <div class="header-text">
                        <h2>Revisa tu correo</h2>
                        <p>Te hemos enviado un enlace para restablecer tu contraseña</p>
                    </div>
                </div>

                <?php
                session_start();
                $correo = $_SESSION['correo_recuperacion'] ?? '';
                $enlace = $_SESSION['enlace_recuperacion'] ?? '';
                ?>

                <div style="background: #dcfce7; color: #166534; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #22c55e;">
                    <p style="margin: 0 0 10px 0;">
                        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                        <strong>Correo:</strong> <?php echo htmlspecialchars($correo); ?>
                    </p>
                    <p style="margin: 0; font-size: 14px;">
                        El enlace expirará en <strong>1 hora</strong>.
                    </p>
                </div>

                <!-- ENLACE PARA PRUEBA (SOLO DESARROLLO) -->
                <?php if ($enlace): ?>
                    <div style="background: #fef3c7; color: #92400e; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #f59e0b;">
                        <p style="margin: 0 0 10px 0;">
                            <i class="fa-solid fa-link" aria-hidden="true"></i>
                            <strong>Enlace de prueba (desarrollo):</strong>
                        </p>
                        <a href="<?php echo $enlace; ?>" target="_blank" style="word-break: break-all; color: #5a189a;">
                            <?php echo $enlace; ?>
                        </a>
                    </div>
                <?php endif; ?>

                <div style="text-align: center;">
                    <a href="login.php" class="btn-submit-login" style="display: inline-block; text-decoration: none; text-align: center;">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Ir al inicio de sesión
                    </a>
                </div>

                <div class="info-spam-box" style="margin-top: 20px;">
                    <div class="info-icon-container">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    </div>
                    <div class="info-text-container">
                        Revisa tu carpeta de spam si no recibes el correo en unos minutos
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>