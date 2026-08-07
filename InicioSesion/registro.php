<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea tu cuenta - AULAMOS</title>
    <link rel="stylesheet" href="styles/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/accesibilidad.css">
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
        <div class="nav-buttons">
            <!-- BOTÓN CHATBOT -->
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
                    <button type="button" class="btn-accessibility-round" id="btnAccesibilidad" aria-label="Opciones de accesibilidad">
                        <i class="fa-solid fa-universal-access" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- MOSTRAR ERRORES -->
                <?php if (isset($_GET['error'])): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #dc2626;">
                        <?php 
                            $error = $_GET['error'];
                            if ($error === 'campos_vacios') {
                                echo '❌ Todos los campos son obligatorios.';
                            } elseif ($error === 'correo_existe') {
                                echo '❌ Este correo ya está registrado.';
                            } elseif ($error === 'correo_invalido') {
                                echo '❌ El correo no es válido.';
                            } elseif ($error === 'password_corta') {
                                echo '❌ La contraseña debe tener al menos 8 caracteres.';
                            } elseif ($error === 'rol_invalido') {
                                echo '❌ Rol no válido.';
                            } else {
                                echo '❌ Error al registrar. Intenta de nuevo.';
                            }
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Selector de Rol -->
                <div class="role-selector">
                    <button type="button" class="btn-role active" id="btn-registro-alumno" data-rol="Alumno" aria-label="Seleccionar rol alumno">
                        <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Soy Alumno
                    </button>
                    <button type="button" class="btn-role" id="btn-registro-docente" data-rol="Docente" aria-label="Seleccionar rol docente">
                        <i class="fa-solid fa-user" aria-hidden="true"></i> Soy Docente
                    </button>
                </div>

                <!-- FORMULARIO -->
                <form class="login-form" action="procesar_registro.php" method="POST" novalidate>
                    <input type="hidden" name="rol" id="rol-input" value="Alumno">
                    
                    <div class="input-group">
                        <label for="registro-nombre">Nombre(s)</label>
                        <div class="input-wrapper">
                            <i class="fa-regular fa-user input-icon" aria-hidden="true"></i>
                            <input type="text" id="registro-nombre" name="nombre" placeholder="Tu nombre" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="registro-apellido-paterno">Apellido Paterno</label>
                        <div class="input-wrapper">
                            <i class="fa-regular fa-user input-icon" aria-hidden="true"></i>
                            <input type="text" id="registro-apellido-paterno" name="apellido_paterno" placeholder="Tu apellido paterno" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="registro-apellido-materno">Apellido Materno</label>
                        <div class="input-wrapper">
                            <i class="fa-regular fa-user input-icon" aria-hidden="true"></i>
                            <input type="text" id="registro-apellido-materno" name="apellido_materno" placeholder="Tu apellido materno" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="registro-email">Correo electrónico</label>
                        <div class="input-wrapper">
                            <i class="fa-regular fa-envelope input-icon" aria-hidden="true"></i>
                            <input type="email" id="registro-email" name="correo" placeholder="ejemplo@correo.com" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="registro-password">Contraseña</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                            <input type="password" id="registro-password" name="password" placeholder="••••••••••••••••" required>
                            <i class="fa-regular fa-eye toggle-password-icon" id="btn-ver-password" role="button" tabindex="0" aria-label="Mostrar contraseña"></i>
                        </div>
                        <p style="font-size: 12px; color: #6b7280; margin-top: 2px;">La contraseña debe tener al menos 8 caracteres.</p>

                        <div class="password-requirements" id="passwordRequirements">
                            <p style="font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #4b5563;">
                                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> 
                                Tu contraseña debe cumplir con:
                            </p>
                            <ul style="list-style: none; padding: 0; margin: 0; font-size: 13px;">
                                <li id="req-length" style="padding: 4px 0; color: #6b7280; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-regular fa-circle" aria-hidden="true"></i>
                                    <span>Mínimo 8 caracteres</span>
                                </li>
                                <li id="req-uppercase" style="padding: 4px 0; color: #6b7280; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-regular fa-circle" aria-hidden="true"></i>
                                    <span>Al menos 1 mayúscula</span>
                                </li>
                                <li id="req-lowercase" style="padding: 4px 0; color: #6b7280; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-regular fa-circle" aria-hidden="true"></i>
                                    <span>Al menos 1 minúscula</span>
                                </li>
                                <li id="req-number" style="padding: 4px 0; color: #6b7280; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-regular fa-circle" aria-hidden="true"></i>
                                    <span>Al menos 1 número</span>
                                </li>
                                <li id="req-special" style="padding: 4px 0; color: #6b7280; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-regular fa-circle" aria-hidden="true"></i>
                                    <span>Al menos 1 carácter especial (-_!@#$%^&*)</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit-login">Crear Cuenta</button>
                </form>

                <div class="form-divider">
                    <span class="divider-circle">o</span>
                </div>

                <div class="register-redirect">
                    <span>¿Ya tienes una cuenta?</span>
                    <a href="login.php" class="link-register">Iniciar Sesión</a>
                </div>

            </div>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- PANEL DE ACCESIBILIDAD FLOTANTE            -->
    <!-- ========================================== -->
    <div class="panel-accesibilidad" id="panelAccesibilidad">
        <div class="panel-header">
            <h3><i class="fa-solid fa-universal-access"></i> Accesibilidad</h3>
            <button class="panel-cerrar" id="cerrarPanel">&times;</button>
        </div>
        <div class="panel-body">
            <!-- ✅ BOTÓN PERSONALIZAR ALTO CONTRASTE -->
            <button class="btn-accesibilidad-opcion" id="btnPersonalizarContraste">
                <i class="fa-solid fa-palette"></i> Personalizar alto contraste
                <span class="badge-nuevo">Nuevo</span>
            </button>
            <button class="btn-accesibilidad-opcion" id="btnModoOscuro">
                <i class="fa-solid fa-moon"></i> Modo oscuro
            </button>
            <button class="btn-accesibilidad-opcion" id="btnAltoContraste">
                <i class="fa-solid fa-eye"></i> Alto contraste
            </button>
            <button class="btn-accesibilidad-opcion" id="btnTextoGrande">
                <i class="fa-solid fa-text-height"></i> Texto grande
            </button>
            <button class="btn-accesibilidad-opcion" id="btnLectorPantalla">
                <i class="fa-solid fa-volume-high"></i> Lector de pantalla
            </button>
            <button class="btn-accesibilidad-opcion restaurar" id="btnRestablecer">
                <i class="fa-solid fa-rotate"></i> Restablecer todas las opciones
            </button>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL PERSONALIZAR ALTO CONTRASTE          -->
    <!-- ========================================== -->
    <div class="modal-overlay" id="modalContraste">
        <div class="modal-content modal-contraste">
            <div class="modal-header">
                <h3><i class="fa-solid fa-palette"></i> Personalizar alto contraste</h3>
                <button class="modal-cerrar" id="cerrarModalContraste">&times;</button>
            </div>
            <div class="modal-body">
                <p class="modal-desc">Elige el fondo y el color de acento para el modo alto contraste.</p>
                
                <div class="opcion-grupo">
                    <label class="opcion-label">Fondo</label>
                    <div class="opciones-botones">
                        <button class="btn-opt fondo-blanco" data-fondo="blanco">
                            <span class="preview" style="background:#ffffff; border:2px solid #333;"></span> Blanco
                        </button>
                        <button class="btn-opt fondo-negro" data-fondo="negro">
                            <span class="preview" style="background:#000000; border:2px solid #333;"></span> Negro
                        </button>
                    </div>
                </div>
                
                <div class="opcion-grupo">
                    <label class="opcion-label">Color de acento</label>
                    <div class="opciones-botones">
                        <button class="btn-opt acento-azul" data-color="azul">
                            <span class="preview" style="background:#3b82f6;"></span> Azul
                        </button>
                        <button class="btn-opt acento-amarillo" data-color="amarillo">
                            <span class="preview" style="background:#eab308;"></span> Amarillo
                        </button>
                        <button class="btn-opt acento-verde" data-color="verde">
                            <span class="preview" style="background:#22c55e;"></span> Verde
                        </button>
                        <button class="btn-opt acento-rojo" data-color="rojo">
                            <span class="preview" style="background:#ef4444;"></span> Rojo
                        </button>
                        <button class="btn-opt acento-naranja" data-color="naranja">
                            <span class="preview" style="background:#f97316;"></span> Naranja
                        </button>
                        <button class="btn-opt acento-morado" data-color="morado">
                            <span class="preview" style="background:#8b5cf6;"></span> Morado
                        </button>
                    </div>
                </div>
                
                <div class="vista-previa" id="vistaPrevia">
                    <p class="vista-titulo">Vista previa</p>
                    <div class="vista-ejemplo">
                        <span class="vista-texto">Texto de ejemplo</span>
                        <button class="vista-boton">Botón</button>
                        <span class="vista-badge">Activo</span>
                    </div>
                </div>
                
                <div class="modal-acciones">
                    <button class="btn-cancelar-modal" id="cancelarContraste">Cancelar</button>
                    <button class="btn-aplicar-modal" id="aplicarContraste">Aplicar cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- SCRIPTS                                    -->
    <!-- ========================================== -->
    <script src="js/registro.js"></script>
    <script src="js/accesibilidad.js"></script>
    <script src="js/lector.js"></script>
</body>
</html>