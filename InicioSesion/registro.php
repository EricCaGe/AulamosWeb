<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea tu cuenta - AULAMOS</title>
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
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="font-family: Georgia, serif; font-size: 32px; color: #111827; font-weight: bold; margin-bottom: 10px;">Crea tu cuenta</h2>
                <p style="font-size: 15px; color: #4b5563; line-height: 1.4;">Únete a Aulamos y comienza tu<br>experiencia de aprendizaje</p>
            </div>

            <div class="illustration-container">
                <img src="../img/login.png" alt="Estudiantes usando laptop" class="login-illustration">
            </div>
        </div>

        <!-- Columna Derecha: Tarjeta de Registro -->
        <div class="login-right-side">
            <div class="login-card">
                
                <!-- Encabezado de Tarjeta -->
                <div class="card-header">
                    <div class="header-text">
                        <h2>Crea tu cuenta</h2>
                        <p>Selecciona tu rol para continuar</p>
                    </div>
                    <!-- BOTÓN REDONDO MORADO (SOLO VISTA, NO HACE NADA) -->
                    <button type="button" class="btn-accessibility-round" aria-label="Opciones de accesibilidad">
                        <i class="fa-solid fa-child-accessibility" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- Selector de Rol -->
                <div class="role-selector">
                    <button type="button" class="btn-role active" id="btn-registro-alumno" aria-label="Seleccionar rol alumno">
                        <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Soy Alumno
                    </button>
                    <button type="button" class="btn-role" id="btn-registro-docente" aria-label="Seleccionar rol docente">
                        <i class="fa-solid fa-user" aria-hidden="true"></i> Soy Docente
                    </button>
                </div>

                <!-- Formulario -->
                <form class="login-form" onsubmit="return false;" novalidate>
                    <div class="input-group">
                        <label for="registro-email">Correo electrónico</label>
                        <div class="input-wrapper">
                            <i class="fa-regular fa-envelope input-icon" aria-hidden="true"></i>
                            <input type="email" id="registro-email" placeholder="ejemplo@correo.com" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="registro-password">Contraseña</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                            <input type="password" id="registro-password" placeholder="••••••••••••••••" required>
                            <i class="fa-regular fa-eye toggle-password-icon" id="btn-ver-password" role="button" tabindex="0" aria-label="Mostrar contraseña"></i>
                        </div>
                        <p style="font-size: 12px; color: #6b7280; margin-top: 2px;">La contraseña debe tener al menos 8 caracteres.</p>
                    </div>

                    <button type="submit" class="btn-submit-login">Crear Cuenta</button>
                </form>

                <!-- Divisor -->
                <div class="form-divider">
                    <span class="divider-circle">o</span>
                </div>

                <!-- Enlace a Iniciar Sesión -->
                <div class="register-redirect">
                    <span>¿Ya tienes una cuenta?</span>
                    <a href="login.php" class="link-register">Iniciar Sesión</a>
                </div>

            </div>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- BLOQUE 3: SCRIPTS (JAVASCRIPT)             -->
    <!-- ========================================== -->
    <script src="js/registro.js"></script>

</body>
</html>