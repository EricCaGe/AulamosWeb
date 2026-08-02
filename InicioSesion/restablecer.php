<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña - AULAMOS</title>
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

        <div class="nav-buttons">
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
                <h2 style="font-family: Georgia, serif; font-size: 32px; color: #111827; font-weight: bold; margin-bottom: 10px;">Restablecer contraseña</h2>
                <p style="font-size: 15px; color: #4b5563; line-height: 1.4;">Ingresa tu nueva contraseña y<br>confírmala para continuar.</p>
            </div>

            <div class="illustration-container">
                <img src="../img/login.png" alt="Restablecer contraseña" class="login-illustration">
            </div>
        </div>

        <!-- Columna Derecha -->
        <div class="login-right-side">
            <div class="login-card">
                
                <div class="card-header">
                    <div class="header-text">
                        <h2>Nueva contraseña</h2>
                        <p>Elige una contraseña segura</p>
                    </div>
                    <button type="button" class="btn-accessibility-round" aria-label="Opciones de accesibilidad">
                        <i class="fa-solid fa-child-accessibility" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- MOSTRAR ERRORES -->
                <?php if (isset($_GET['error'])): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #dc2626;">
                        <?php 
                            $error = $_GET['error'];
                            if ($error === 'token_invalido') {
                                echo '❌ El enlace no es válido o ya fue utilizado.';
                            } elseif ($error === 'token_expirado') {
                                echo '❌ El enlace ha expirado. Solicita uno nuevo.';
                            } elseif ($error === 'password_corta') {
                                echo '❌ La contraseña debe tener al menos 8 caracteres.';
                            } elseif ($error === 'no_coinciden') {
                                echo '❌ Las contraseñas no coinciden.';
                            } else {
                                echo '❌ Error al restablecer. Intenta de nuevo.';
                            }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['exito']) && $_GET['exito'] === 'restablecido'): ?>
                    <div style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #22c55e;">
                        ✅ ¡Contraseña restablecida exitosamente! Ahora inicia sesión.
                    </div>
                <?php endif; ?>

                <!-- FORMULARIO -->
                <form class="login-form" action="procesar_restablecer.php" method="POST" novalidate>
                    
                    <!-- Token oculto -->
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">

                    <div class="input-group">
                        <label for="nueva-password">Nueva contraseña</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                            <input type="password" id="nueva-password" name="password" placeholder="••••••••••••••••" required minlength="8">
                            <i class="fa-regular fa-eye toggle-password-icon" id="btn-ver-password" role="button" tabindex="0" aria-label="Mostrar contraseña"></i>
                        </div>
                        <p style="font-size: 12px; color: #6b7280; margin-top: 2px;">Mínimo 8 caracteres.</p>

                        <!-- ✅ VALIDACIONES DE CONTRASEÑA EN TIEMPO REAL -->
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

                    <div class="input-group">
                        <label for="confirmar-password">Confirmar contraseña</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                            <input type="password" id="confirmar-password" name="password_confirm" placeholder="••••••••••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit-login">Restablecer contraseña</button>
                </form>

                <div style="text-align: center; margin: 25px 0 15px 0;">
                    <a href="login.php" class="link-register" style="font-size: 15px;">
                        &larr; Volver al inicio de sesión
                    </a>
                </div>

            </div>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- SCRIPTS                                    -->
    <!-- ========================================== -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ========================================== //
            // MOSTRAR/OCULTAR CONTRASEÑA                //
            // ========================================== //
            const btnVer = document.getElementById('btn-ver-password');
            const inputPass = document.getElementById('nueva-password');

            if (btnVer && inputPass) {
                btnVer.addEventListener('click', function() {
                    const isPassword = inputPass.type === 'password';
                    inputPass.type = isPassword ? 'text' : 'password';
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }

            // ========================================== //
            // VALIDACIÓN DE CONTRASEÑA EN TIEMPO REAL    //
            // ========================================== //
            const passwordInput = document.getElementById('nueva-password');
            const reqLength = document.getElementById('req-length');
            const reqUppercase = document.getElementById('req-uppercase');
            const reqLowercase = document.getElementById('req-lowercase');
            const reqNumber = document.getElementById('req-number');
            const reqSpecial = document.getElementById('req-special');

            if (passwordInput) {
                function validarContraseña(contraseña) {
                    const requisitos = {
                        length: contraseña.length >= 8,
                        uppercase: /[A-Z]/.test(contraseña),
                        lowercase: /[a-z]/.test(contraseña),
                        number: /[0-9]/.test(contraseña),
                        special: /[_\-!@#$%^&*]/.test(contraseña)
                    };
                    return requisitos;
                }

                function actualizarRequisito(elemento, cumple) {
                    if (!elemento) return;
                    if (cumple) {
                        elemento.style.color = '#16a34a';
                        const icono = elemento.querySelector('i');
                        if (icono) icono.className = 'fa-regular fa-circle-check';
                    } else {
                        elemento.style.color = '#6b7280';
                        const icono = elemento.querySelector('i');
                        if (icono) icono.className = 'fa-regular fa-circle';
                    }
                }

                passwordInput.addEventListener('input', function() {
                    const contraseña = this.value;
                    const requisitos = validarContraseña(contraseña);

                    actualizarRequisito(reqLength, requisitos.length);
                    actualizarRequisito(reqUppercase, requisitos.uppercase);
                    actualizarRequisito(reqLowercase, requisitos.lowercase);
                    actualizarRequisito(reqNumber, requisitos.number);
                    actualizarRequisito(reqSpecial, requisitos.special);

                    const allValid = Object.values(requisitos).every(val => val === true);
                    if (contraseña.length === 0) {
                        this.style.borderColor = '';
                    } else if (allValid) {
                        this.style.borderColor = '#16a34a';
                    } else {
                        this.style.borderColor = '#dc2626';
                    }
                });
            }

            // ========================================== //
            // VALIDAR QUE LAS CONTRASEÑAS COINCIDAN     //
            // ========================================== //
            const form = document.querySelector('.login-form');
            const pass = document.getElementById('nueva-password');
            const confirm = document.getElementById('confirmar-password');

            if (form) {
                form.addEventListener('submit', function(event) {
                    let errores = [];

                    // Validar contraseña fuerte (misma validación del registro)
                    if (!pass || pass.value.trim() === '') {
                        errores.push('La contraseña es obligatoria.');
                        pass.style.borderColor = '#dc2626';
                    } else if (pass.value.length < 8) {
                        errores.push('La contraseña debe tener al menos 8 caracteres.');
                        pass.style.borderColor = '#dc2626';
                    } else if (!/[A-Z]/.test(pass.value)) {
                        errores.push('La contraseña debe tener al menos 1 mayúscula.');
                        pass.style.borderColor = '#dc2626';
                    } else if (!/[a-z]/.test(pass.value)) {
                        errores.push('La contraseña debe tener al menos 1 minúscula.');
                        pass.style.borderColor = '#dc2626';
                    } else if (!/[0-9]/.test(pass.value)) {
                        errores.push('La contraseña debe tener al menos 1 número.');
                        pass.style.borderColor = '#dc2626';
                    } else if (!/[_\-!@#$%^&*]/.test(pass.value)) {
                        errores.push('La contraseña debe tener al menos 1 carácter especial (-_!@#$%^&*).');
                        pass.style.borderColor = '#dc2626';
                    } else {
                        pass.style.borderColor = '';
                    }

                    if (!confirm || confirm.value.trim() === '') {
                        errores.push('Confirma tu contraseña.');
                        confirm.style.borderColor = '#dc2626';
                    } else if (pass.value !== confirm.value) {
                        errores.push('Las contraseñas no coinciden.');
                        confirm.style.borderColor = '#dc2626';
                    } else {
                        confirm.style.borderColor = '';
                    }

                    if (errores.length > 0) {
                        event.preventDefault();
                        alert('⚠️ Por favor corrige los siguientes errores:\n\n• ' + errores.join('\n• '));
                        return false;
                    }

                    console.log('✅ Formulario validado correctamente.');
                    return true;
                });
            }
        });
    </script>

</body>
</html>