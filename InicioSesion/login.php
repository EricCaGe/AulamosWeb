<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - AULAMOS</title>
    <!-- Vinculación ÚNICA a su propio CSS local -->
    <link rel="stylesheet" href="../styles/login.css">
    <!-- Iconos FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <!-- ========================================== -->
    <!-- BLOQUE 1: BARRA DE ACCESIBILIDAD PROPIA     -->
    <!-- ========================================== -->
    <!-- Ponemos un checkbox oculto para abrir/cerrar la barra con PURO CSS sin usar JavaScript -->
    <input type="checkbox" id="toggle-barra-acc" style="display: none;">
    
    <div id="barra-accesibilidad-login">
        <div class="barra-acc-interna">
            <div class="acc-mensaje">
                <div class="acc-icono-home"><i class="fa-solid fa-house-medical-flag"></i></div>
                <div class="acc-texto">
                    <h4>Accesibilidad siempre disponible</h4>
                    <p>Personaliza tu experiencia en cualquier momento.</p>
                </div>
            </div>
            <div class="acc-opciones-botones">
                <button class="acc-btn-opcion"><i class="fa-solid fa-eye"></i><span>Alto contraste</span></button>
                <button class="acc-btn-opcion"><i class="fa-solid fa-moon"></i><span>Modo oscuro</span></button>
                <button class="acc-btn-opcion"><i class="fa-solid fa-text-height"></i><span>Texto grande</span></button>
                <button class="acc-btn-opcion"><i class="fa-solid fa-volume-high"></i><span>Leer pantalla</span></button>
                <button class="acc-btn-opcion"><i class="fa-solid fa-closed-captioning"></i><span>Subtítulos</span></button>
                <button class="acc-btn-opcion"><i class="fa-solid fa-keyboard"></i><span>Navegación por teclado</span></button>
            </div>
            <div class="acc-config">
                <button class="btn-abrir-config">Abrir configuración</button>
            </div>
        </div>
    </div>
    <!-- ========================================== -->


    <!-- ========================================== -->
    <!-- BLOQUE 2: CONTENEDOR PRINCIPAL             -->
    <!-- ========================================== -->
    <div class="login-page-container">
        
        <!-- Columna Izquierda: Logo e Ilustración -->
        <div class="login-left-side">
            <div class="logo-back-home">
                <a href="../index.html">
                    <img src="../img/logogeneral.png" alt="Logo AULAMOS" class="logo-login-img">
                </a>
            </div>
            <div class="illustration-container">
                <img src="../img/login.png" alt="Estudiante en laptop" class="login-illustration">
            </div>
        </div>

        <!-- Columna Derecha: Tarjeta de Login -->
        <div class="login-right-side">
            <div class="login-card">
                
                <!-- Encabezado de Tarjeta -->
                <div class="card-header">
                    <div class="header-text">
                        <h2>Iniciar Sesión</h2>
                        <p>Ingresa tus datos para acceder a tu cuenta</p>
                    </div>
                    <!-- El botón ahora es un label amarrado al checkbox para abrir la barra -->
                    <label for="toggle-barra-acc" class="btn-accessibility-round">
                        <i class="fa-solid fa-child-accessibility"></i>
                    </label>
                </div>

                <!-- Selector de Rol (Pura Vista) -->
                <div class="role-selector">
                    <button type="button" class="btn-role active">
                        <i class="fa-solid fa-graduation-cap"></i> Soy Alumno
                    </button>
                    <button type="button" class="btn-role">
                        <i class="fa-solid fa-user"></i> Soy Docente
                    </button>
                </div>

                <!-- Formulario -->
                <form class="login-form" onsubmit="return false;">
                    <div class="input-group">
                        <label>Correo electrónico</label>
                        <div class="input-wrapper">
                            <i class="fa-regular fa-envelope input-icon"></i>
                            <input type="email" placeholder="ejemplo@correo.com">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Contraseña</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" placeholder="••••••••••••••••">
                            <i class="fa-regular fa-eye toggle-password-icon"></i>
                        </div>
                    </div>

                    <div class="forgot-password-container">
                        <a href="#" class="link-forgot">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="btn-submit-login">Iniciar Sesión</button>
                </form>

                <!-- Divisor -->
                <div class="form-divider">
                    <span class="divider-circle">o</span>
                </div>

                <!-- Enlace a Registro -->
                <div class="register-redirect">
                    <span>¿No tienes cuenta?</span>
                    <a href="registro.php" class="link-register">Crear cuenta</a>
                </div>

            </div>
        </div>

    </div>
    <!-- ========================================== -->

</body>
</html>