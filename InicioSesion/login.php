<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - AULAMOS</title>
    <!-- Vinculación ÚNICA a tu CSS según tu estructura -->
    <link rel="stylesheet" href="../styles/login.css">
    <!-- Iconos FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <!-- ========================================== -->
    <!-- BLOQUE 1: ENCABEZADO SUPERIOR GLOBAL       -->
    <!-- ========================================== -->
    <header class="main-header">
        <!-- Logo a la izquierda -->
        <div class="logo-container">
            <a href="../index.html">
                <img src="../img/logogeneral.png" alt="Logo AULAMOS" class="logo-img">
            </a>
        </div>

        <!-- Botón de Accesibilidad idéntico al index a la derecha -->
        <div class="nav-buttons">
            <label for="toggle-barra-acc" class="btn btn-accessibility">
                <i class="fa-solid fa-universal-access"></i> Accesibilidad
            </label>
        </div>
    </header>

    <!-- ========================================== -->
    <!-- BLOQUE 2: LLAMADA AL MÓDULO DE ACCESIBILIDAD -->
    <!-- ========================================== -->
    <?php include '../Accesibilidad/accesibilidad.php'; ?>
    <!-- ========================================== -->


    <!-- ========================================== -->
    <!-- BLOQUE 3: CONTENEDOR PRINCIPAL             -->
    <!-- ========================================== -->
    <div class="login-page-container">
        
        <!-- Columna Izquierda: Solo Ilustración (El logo ya subió al header) -->
        <div class="login-left-side">
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
                    <!-- El monito morado también puede activar la barra -->
                    <label for="toggle-barra-acc" class="btn-accessibility-round">
                        <i class="fa-solid fa-child-accessibility"></i>
                    </label>
                </div>

                <!-- Selector de Rol -->
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
<script src="../js/login.js"></script>
</body>
</html>