<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - AULAMOS</title>
    <link rel="stylesheet" href="styles/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <!-- ========================================== -->
    <!-- BLOQUE 1: ENCABEZADO SUPERIOR GLOBAL       -->
    <!-- ========================================== -->
    <header class="main-header">
        <div class="logo-container">
            <a href="../index.html">
                <img src="../img/logogeneral.png" alt="Logo AULAMOS" class="logo-img">
            </a>
        </div>

        <!-- Botones a la derecha -->
        <div class="nav-buttons">
            <!-- BOTÓN CHATBOT (SOLO VISTA, NO HACE NADA) -->
            <button type="button" class="btn btn-chatbot" aria-label="Abrir chatbot">
                <i class="fa-solid fa-comment-dots" aria-hidden="true"></i> Chatbot
            </button>
        </div>
    </header>

    <!-- ========================================== -->
    <!-- BLOQUE 2: CONTENEDOR PRINCIPAL             -->
    <!-- ========================================== -->
    <div class="login-page-container">
        
        <!-- Columna Izquierda -->
        <div class="login-left-side">
            <!-- Textos de recuperación -->
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="font-family: Georgia, serif; font-size: 32px; color: #111827; font-weight: bold; margin-bottom: 10px;">Recupera tu cuenta</h2>
                <p style="font-size: 15px; color: #4b5563; line-height: 1.4;">Ingresa tu correo electrónico y te enviaremos<br>un enlace para restablecer tu contraseña.</p>
            </div>

            <div class="illustration-container">
                <img src="../img/login.png" alt="Estudiantes usando laptop" class="login-illustration">
            </div>
        </div>

        <!-- Columna Derecha: Tarjeta de Recuperación -->
        <div class="login-right-side">
            <div class="login-card">
                
                <!-- Encabezado de Tarjeta -->
                <div class="card-header">
                    <div class="header-text">
                        <h2>Recuperar contraseña</h2>
                        <p>Ingresa el correo electrónico asociado a tu cuenta</p>
                    </div>
                    <!-- BOTÓN REDONDO MORADO (SOLO VISTA, NO HACE NADA) -->
                    <button type="button" class="btn-accessibility-round" aria-label="Opciones de accesibilidad">
                        <i class="fa-solid fa-child-accessibility" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- Formulario -->
                <form class="login-form" onsubmit="return false;" novalidate>
                    <div class="input-group">
                        <label for="recuperar-email">Correo electrónico</label>
                        <div class="input-wrapper">
                            <i class="fa-regular fa-envelope input-icon" aria-hidden="true"></i>
                            <input type="email" id="recuperar-email" placeholder="ejemplo@correo.com" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit-login">Enviar enlace de recuperación</button>
                </form>

                <!-- Enlace de regreso al Login -->
                <div style="text-align: center; margin: 25px 0 15px 0;">
                    <a href="login.php" class="link-register" style="font-size: 15px;">
                        &larr; Volver al inicio de sesión
                    </a>
                </div>

                <!-- Caja informativa -->
                <div class="info-spam-box">
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

    <!-- ========================================== -->
    <!-- BLOQUE 3: SCRIPTS (JAVASCRIPT)             -->
    <!-- ========================================== -->
    <script src="js/recuperar.js"></script>

</body>
</html>