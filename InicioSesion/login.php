<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - AULAMOS</title>
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
        
        <!-- Columna Izquierda: Ilustración -->
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
                    <!-- BOTÓN REDONDO MORADO (SOLO VISTA, NO HACE NADA) -->
                    <button type="button" class="btn-accessibility-round" aria-label="Opciones de accesibilidad">
                        <i class="fa-solid fa-child-accessibility" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- MOSTRAR ERRORES -->
                <?php if (isset($_GET['error'])): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #dc2626;">
                        <?php 
                            $error = $_GET['error'];
                            if ($error === 'credenciales') {
                                echo '❌ Correo o contraseña incorrectos.';
                            } elseif ($error === 'inactivo') {
                                echo '⚠️ Tu cuenta está inactiva. Contacta al administrador.';
                            } elseif ($error === 'bloqueado') {
                                echo '⚠️ Tu cuenta está bloqueada. Contacta al administrador.';
                            } elseif ($error === 'sesion') {
                                echo '❌ Error al iniciar sesión. Intenta de nuevo.';
                            } else {
                                echo '❌ Error al iniciar sesión.';
                            }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['registro']) && $_GET['registro'] === 'exitoso'): ?>
                    <div style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #22c55e;">
                        ✅ ¡Cuenta creada exitosamente! Ahora inicia sesión.
                    </div>
                <?php endif; ?>

                <!-- Selector de Rol -->
                <div class="role-selector">
                    <button type="button" class="btn-role active" data-rol="Alumno" aria-label="Seleccionar rol Alumno">
                        <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Soy Alumno
                    </button>
                    <button type="button" class="btn-role" data-rol="Docente" aria-label="Seleccionar rol Docente">
                        <i class="fa-solid fa-user" aria-hidden="true"></i> Soy Docente
                    </button>
                </div>


                <!-- Formulario -->
                <form class="login-form" action="procesar_login.php" method="POST" novalidate>
                    <!-- Campo oculto para el rol -->
                <input type="hidden" name="rol" id="rol-input" value="Alumno">
                    <div class="input-group">
                        <label for="login-email">Correo electrónico</label>
                        <div class="input-wrapper">
                            <i class="fa-regular fa-envelope input-icon" aria-hidden="true"></i>
                            <input type="email" id="login-email" name="correo" placeholder="ejemplo@correo.com" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="login-password">Contraseña</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                            <input type="password" id="login-password" name="password" placeholder="••••••••••••••••" required>
                            <i class="fa-regular fa-eye toggle-password-icon" role="button" tabindex="0" aria-label="Mostrar contraseña"></i>
                        </div>
                    </div>

                    <div class="forgot-password-container">
                        <a href="recuperar.php" class="link-forgot">¿Olvidaste tu contraseña?</a>
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
    <!-- BLOQUE 3: SCRIPTS (JAVASCRIPT)             -->
    <!-- ========================================== -->
    <script src="js/login.js"></script>
    <script>
        // Actualizar campo oculto al seleccionar rol
        document.querySelectorAll('.btn-role').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.btn-role').forEach(function(b) {
                    b.classList.remove('active');
                });
                this.classList.add('active');
                document.getElementById('rol-input').value = this.getAttribute('data-rol');
            });
        });
    </script>

</body>
</html>