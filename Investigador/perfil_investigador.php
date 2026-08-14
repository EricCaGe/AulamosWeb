<?php
session_start();

// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

// Verificar que el usuario esté logueado y sea Investigador
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Investigador') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_usuario = $_SESSION['usuario']['id_usuario'];
$nombre_completo = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'] . ' ' . $_SESSION['usuario']['apellido_materno'];
$correo = $_SESSION['usuario']['correo'];
$rol = 'Investigador';

// Obtener datos adicionales del usuario
$stmt = $conexion->prepare("SELECT fecha_registro, ultimo_acceso, foto_perfil FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario_data = $resultado->fetch_assoc();
$stmt->close();

$fecha_registro = $usuario_data['fecha_registro'] ?? date('d/m/Y');
$ultimo_acceso = $usuario_data['ultimo_acceso'] ?? 'Nunca';
$foto_perfil = $usuario_data['foto_perfil'] ?? null;

// Procesar actualización de perfil
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'editar_perfil':
                $nombre = trim($_POST['nombre'] ?? '');
                $apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
                $apellido_materno = trim($_POST['apellido_materno'] ?? '');
                $correo_nuevo = trim($_POST['correo'] ?? '');

                if (empty($nombre) || empty($apellido_paterno) || empty($correo_nuevo)) {
                    $mensaje = 'Los campos nombre, apellido paterno y correo son obligatorios.';
                    $tipo_mensaje = 'error';
                } elseif (!filter_var($correo_nuevo, FILTER_VALIDATE_EMAIL)) {
                    $mensaje = 'El correo electrónico no es válido.';
                    $tipo_mensaje = 'error';
                } else {
                    // Validación: Verificar que el correo no esté en uso por otro usuario
                    $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? AND id_usuario != ?");
                    $stmt->bind_param("si", $correo_nuevo, $id_usuario);
                    $stmt->execute();
                    $resultado = $stmt->get_result();
                    if ($resultado->num_rows > 0) {
                        $mensaje = 'El correo ya está registrado por otro usuario.';
                        $tipo_mensaje = 'error';
                    } else {
                        $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, apellido_paterno = ?, apellido_materno = ?, correo = ? WHERE id_usuario = ?");
                        $stmt->bind_param("ssssi", $nombre, $apellido_paterno, $apellido_materno, $correo_nuevo, $id_usuario);
                        if ($stmt->execute()) {
                            $_SESSION['usuario']['nombre'] = $nombre;
                            $_SESSION['usuario']['apellido_paterno'] = $apellido_paterno;
                            $_SESSION['usuario']['apellido_materno'] = $apellido_materno;
                            $_SESSION['usuario']['correo'] = $correo_nuevo;
                            $nombre_completo = $nombre . ' ' . $apellido_paterno . ' ' . $apellido_materno;
                            $correo = $correo_nuevo;
                            $mensaje = 'Perfil actualizado correctamente.';
                            $tipo_mensaje = 'exito';
                        } else {
                            $mensaje = 'Error al actualizar el perfil.';
                            $tipo_mensaje = 'error';
                        }
                        $stmt->close();
                    }
                }
                break;

            case 'cambiar_password':
                $password_actual = $_POST['password_actual'] ?? '';
                $password_nuevo = $_POST['password_nuevo'] ?? '';
                $password_confirmar = $_POST['password_confirmar'] ?? '';

                if (empty($password_actual) || empty($password_nuevo) || empty($password_confirmar)) {
                    $mensaje = 'Todos los campos de contraseña son obligatorios.';
                    $tipo_mensaje = 'error';
                } elseif ($password_nuevo !== $password_confirmar) {
                    $mensaje = 'Las contraseñas nuevas no coinciden.';
                    $tipo_mensaje = 'error';
                } elseif (strlen($password_nuevo) < 6) {
                    $mensaje = 'La nueva contraseña debe tener al menos 6 caracteres.';
                    $tipo_mensaje = 'error';
                } else {
                    $stmt = $conexion->prepare("SELECT password_hash FROM usuarios WHERE id_usuario = ?");
                    $stmt->bind_param("i", $id_usuario);
                    $stmt->execute();
                    $resultado = $stmt->get_result();
                    $usuario = $resultado->fetch_assoc();
                    $stmt->close();

                    if (!password_verify($password_actual, $usuario['password_hash'])) {
                        $mensaje = 'La contraseña actual es incorrecta.';
                        $tipo_mensaje = 'error';
                    } elseif (password_verify($password_nuevo, $usuario['password_hash'])) {
                        $mensaje = 'La nueva contraseña debe ser diferente a la actual.';
                        $tipo_mensaje = 'error';
                    } else {
                        $nuevo_hash = password_hash($password_nuevo, PASSWORD_DEFAULT);
                        $stmt = $conexion->prepare("UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?");
                        $stmt->bind_param("si", $nuevo_hash, $id_usuario);
                        if ($stmt->execute()) {
                            $mensaje = 'Contraseña actualizada correctamente.';
                            $tipo_mensaje = 'exito';
                        } else {
                            $mensaje = 'Error al actualizar la contraseña.';
                            $tipo_mensaje = 'error';
                        }
                        $stmt->close();
                    }
                }
                break;

            // =============================================
            // NUEVO: SUBIR FOTO DE PERFIL
            // =============================================
            case 'subir_foto':
                if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                    $archivo = $_FILES['foto_perfil'];
                    $nombre_original = $archivo['name'];
                    $tipo = $archivo['type'];
                    $tamano = $archivo['size'];
                    $temp = $archivo['tmp_name'];
                    
                    // Validar tipo de archivo
                    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
                    
                    if (!in_array($extension, $extensiones_permitidas)) {
                        $mensaje = 'Solo se permiten imágenes JPG, PNG, GIF o WEBP.';
                        $tipo_mensaje = 'error';
                    } elseif ($tamano > 2097152) { // 2MB
                        $mensaje = 'La imagen no debe superar los 2MB.';
                        $tipo_mensaje = 'error';
                    } else {
                        // Crear carpeta si no existe
                        $carpeta_destino = '../uploads/perfiles/';
                        if (!is_dir($carpeta_destino)) {
                            mkdir($carpeta_destino, 0777, true);
                        }
                        
                        // Generar nombre único
                        $nombre_archivo = 'perfil_' . $id_usuario . '_' . time() . '.' . $extension;
                        $ruta_completa = $carpeta_destino . $nombre_archivo;
                        
                        // Mover archivo
                        if (move_uploaded_file($temp, $ruta_completa)) {
                            // Eliminar foto anterior si existe
                            if ($foto_perfil && file_exists($carpeta_destino . $foto_perfil)) {
                                unlink($carpeta_destino . $foto_perfil);
                            }
                            
                            // Actualizar base de datos
                            $stmt = $conexion->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?");
                            $stmt->bind_param("si", $nombre_archivo, $id_usuario);
                            if ($stmt->execute()) {
                                // Actualizar sesión
                                $_SESSION['usuario']['foto_perfil'] = $nombre_archivo;
                                $foto_perfil = $nombre_archivo;
                                
                                $mensaje = 'Foto de perfil actualizada correctamente.';
                                $tipo_mensaje = 'exito';
                            } else {
                                $mensaje = 'Error al guardar la foto en la base de datos.';
                                $tipo_mensaje = 'error';
                            }
                            $stmt->close();
                        } else {
                            $mensaje = 'Error al subir la imagen.';
                            $tipo_mensaje = 'error';
                        }
                    }
                } else {
                    $mensaje = 'No se seleccionó ninguna imagen.';
                    $tipo_mensaje = 'error';
                }
                break;
        }
    }
}

// Actualizar la variable de sesión de foto para el header
$foto_perfil_actual = $_SESSION['usuario']['foto_perfil'] ?? $foto_perfil;

// Páginas del investigador
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Investigador</title>

    <link rel="stylesheet" href="styles/perfil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
</head>
<body>

<div class="dashboard-container">

    <!-- ===== SIDEBAR INVESTIGADOR ===== -->
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logogeneral.png" alt="Logo Aulamos" class="logo">
        </div>

        <nav class="menu">
            <a href="dashboard.php" class="menu-item <?php echo ($pagina_actual == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="metricas_uso.php" class="menu-item <?php echo ($pagina_actual == 'metricas_uso.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-bar"></i> Uso de la plataforma
            </a>
            <a href="tiempos_actividades.php" class="menu-item <?php echo ($pagina_actual == 'tiempos_actividades.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock"></i> Tiempos de actividades
            </a>
            <a href="errores_navegacion.php" class="menu-item <?php echo ($pagina_actual == 'errores_navegacion.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-triangle-exclamation"></i> Errores de navegación
            </a>
            <a href="metricas_chatbot.php" class="menu-item <?php echo ($pagina_actual == 'metricas_chatbot.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-robot"></i> Uso del chatbot
            </a>
            <a href="progreso_academico.php" class="menu-item <?php echo ($pagina_actual == 'progreso_academico.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-line"></i> Progreso académico
            </a>
            <a href="metricas_accesibilidad.php" class="menu-item <?php echo ($pagina_actual == 'metricas_accesibilidad.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-universal-access"></i> Accesibilidad
            </a>
            <a href="reportes.php" class="menu-item <?php echo ($pagina_actual == 'reportes.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-file-alt"></i> Reportes
            </a>
            <a href="mas.php" class="menu-item <?php echo ($pagina_actual == 'mas.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-ellipsis-h"></i> Más
            </a>
            <a href="perfil_investigador.php" class="menu-item <?php echo ($pagina_actual == 'perfil_investigador.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user"></i> Mi Perfil
            </a>
        </nav>

        <button class="btn-accesibilidad-header" id="btnAccesibilidad" onclick="toggleBarraAccesibilidad()">
            <i class="fa-solid fa-universal-access"></i> Accesibilidad
        </button>
    </aside>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <main class="main-content">

        <!-- ===== HEADER ===== -->
        <header class="content-header">
            <div class="welcome-text">
                <h1>Mi Perfil</h1>
                <p>Administra tu información personal y preferencias</p>
            </div>
            <div class="header-actions">
                <div class="icon-bell">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <a href="perfil_investigador.php" class="user-profile" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <?php if (!empty($foto_perfil_actual)): ?>
                        <img src="../uploads/perfiles/<?php echo htmlspecialchars($foto_perfil_actual); ?>" alt="Avatar" class="avatar">
                    <?php else: ?>
                        <img src="https://placehold.co/40x40/3b71f3/white?text=👤" alt="Avatar" class="avatar">
                    <?php endif; ?>
                    <span class="user-name"><?php echo htmlspecialchars($nombre_completo); ?></span>
                    <i class="fa-solid fa-chevron-down drop-icon"></i>
                </a>
                <a href="../InicioSesion/cerrar_sesion.php" class="btn-logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <!-- ===== MENSAJES ===== -->
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- ===== PERFIL ===== -->
        <div class="perfil-container">

            <!-- ===== SECCIÓN: DATOS PERSONALES ===== -->
            <section class="perfil-seccion datos-personales">
                <div class="perfil-avatar">
                    <div class="avatar-grande">
                        <?php if (!empty($foto_perfil_actual)): ?>
                            <img src="../uploads/perfiles/<?php echo htmlspecialchars($foto_perfil_actual); ?>" alt="Foto de perfil">
                        <?php else: ?>
                            <img src="https://placehold.co/120x120/3b71f3/white?text=👤" alt="Avatar por defecto">
                        <?php endif; ?>
                    </div>
                    
                    <!-- FORMULARIO SUBIR FOTO -->
                    <form method="POST" enctype="multipart/form-data" id="formFotoPerfil">
                        <input type="hidden" name="accion" value="subir_foto">
                        <label for="inputFotoPerfil" class="btn-cambiar-foto" style="cursor:pointer;">
                            <i class="fa-solid fa-camera"></i> Cambiar foto
                        </label>
                        <input type="file" name="foto_perfil" id="inputFotoPerfil" accept="image/*" style="display:none;">
                    </form>
                </div>

                <div class="perfil-info">
                    <div class="info-grupo">
                        <span class="info-label">Nombre completo</span>
                        <span class="info-valor"><?php echo htmlspecialchars($nombre_completo); ?></span>
                    </div>
                    <div class="info-grupo">
                        <span class="info-label">Correo electrónico</span>
                        <span class="info-valor"><?php echo htmlspecialchars($correo); ?></span>
                    </div>
                    <div class="info-grupo">
                        <span class="info-label">Rol</span>
                        <span class="info-valor"><span class="badge-rol"><?php echo $rol; ?></span></span>
                    </div>
                    <div class="info-grupo">
                        <span class="info-label">Fecha de registro</span>
                        <span class="info-valor"><?php echo date('d/m/Y', strtotime($fecha_registro)); ?></span>
                    </div>
                    <div class="info-grupo">
                        <span class="info-label">Último acceso</span>
                        <span class="info-valor"><?php echo $ultimo_acceso !== 'Nunca' ? date('d/m/Y h:i a', strtotime($ultimo_acceso)) : 'Nunca'; ?></span>
                    </div>
                </div>
            </section>

            <!-- ===== SECCIÓN: CONFIGURACIÓN ===== -->
            <section class="perfil-seccion configuracion">
                <h2><i class="fa-solid fa-gear"></i> Configuración</h2>

                <div class="config-grid">

                    <!-- Cambiar contraseña -->
                    <div class="config-card">
                        <h3><i class="fa-solid fa-lock"></i> Cambiar contraseña</h3>
                        <form method="POST" action="" class="config-form" id="formPassword">
                            <input type="hidden" name="accion" value="cambiar_password">
                            <div class="form-group">
                                <label for="password_actual">Contraseña actual</label>
                                <input type="password" id="password_actual" name="password_actual" placeholder="Ingresa tu contraseña actual" required>
                            </div>
                            <div class="form-group">
                                <label for="password_nuevo">Nueva contraseña</label>
                                <input type="password" id="password_nuevo" name="password_nuevo" placeholder="Ingresa tu nueva contraseña" required>
                            </div>
                            <div class="form-group">
                                <label for="password_confirmar">Confirmar nueva contraseña</label>
                                <input type="password" id="password_confirmar" name="password_confirmar" placeholder="Confirma tu nueva contraseña" required>
                            </div>
                            <button type="submit" class="btn-guardar">Actualizar contraseña</button>
                        </form>
                    </div>

                    <!-- Mostrar datos del usuario (reemplaza preferencias) -->
                    <div class="config-card">
                        <h3><i class="fa-solid fa-user"></i> Datos del usuario</h3>
                        <div class="config-form">
                            <div class="form-group">
                                <label>ID de usuario</label>
                                <input type="text" value="<?php echo $id_usuario; ?>" disabled style="background:#f1f5f9; color:#64748b; cursor:not-allowed;">
                            </div>
                            <div class="form-group">
                                <label>Rol</label>
                                <input type="text" value="<?php echo $rol; ?>" disabled style="background:#f1f5f9; color:#64748b; cursor:not-allowed;">
                            </div>
                            <div class="form-group">
                                <label>Estado</label>
                                <input type="text" value="Activo" disabled style="background:#f1f5f9; color:#64748b; cursor:not-allowed;">
                            </div>
                            <div class="form-group">
                                <label>Fecha de registro</label>
                                <input type="text" value="<?php echo date('d/m/Y H:i', strtotime($fecha_registro)); ?>" disabled style="background:#f1f5f9; color:#64748b; cursor:not-allowed;">
                            </div>
                            <div class="form-group">
                                <label>Último acceso</label>
                                <input type="text" value="<?php echo $ultimo_acceso !== 'Nunca' ? date('d/m/Y H:i', strtotime($ultimo_acceso)) : 'Nunca'; ?>" disabled style="background:#f1f5f9; color:#64748b; cursor:not-allowed;">
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- ===== SECCIÓN: ACCIONES ===== -->
            <section class="perfil-seccion acciones">
                <h2><i class="fa-solid fa-ellipsis-h"></i> Acciones</h2>
                <div class="acciones-grid">
                    <button class="btn-accion btn-editar" id="btnEditarPerfil">
                        <i class="fa-solid fa-pen"></i> Editar perfil
                    </button>
                    <a href="../InicioSesion/cerrar_sesion.php" class="btn-accion btn-cerrar-sesion">
                        <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
                    </a>
                </div>
            </section>

        </div>

        <!-- ===== BARRA DE ACCESIBILIDAD ===== -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </main>
</div>

<!-- ===== BOTÓN FLOTANTE ===== -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<!-- ===== MODAL EDITAR PERFIL ===== -->
<div id="modalEditar" class="modal-overlay modal-hidden">
    <div class="modal-container">
        <div class="modal-header">
            <h2><i class="fa-solid fa-pen"></i> Editar perfil</h2>
            <button class="modal-cerrar" id="modalCerrar">&times;</button>
        </div>
        <form method="POST" action="" class="modal-form" id="formEditarPerfil">
            <input type="hidden" name="accion" value="editar_perfil">
            <div class="form-group">
                <label for="edit_nombre">Nombre <span class="text-danger">*</span></label>
                <input type="text" id="edit_nombre" name="nombre" value="<?php echo htmlspecialchars($_SESSION['usuario']['nombre']); ?>" required>
            </div>
            <div class="form-group">
                <label for="edit_apellido_paterno">Apellido paterno <span class="text-danger">*</span></label>
                <input type="text" id="edit_apellido_paterno" name="apellido_paterno" value="<?php echo htmlspecialchars($_SESSION['usuario']['apellido_paterno']); ?>" required>
            </div>
            <div class="form-group">
                <label for="edit_apellido_materno">Apellido materno</label>
                <input type="text" id="edit_apellido_materno" name="apellido_materno" value="<?php echo htmlspecialchars($_SESSION['usuario']['apellido_materno'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="edit_correo">Correo electrónico <span class="text-danger">*</span></label>
                <input type="email" id="edit_correo" name="correo" value="<?php echo htmlspecialchars($_SESSION['usuario']['correo']); ?>" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar" id="modalCancelar">Cancelar</button>
                <button type="submit" class="btn-guardar">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== SCRIPTS ===== -->
<script src="../js/admin.js"></script>
<script src="js/perfil_investigador.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
<script src="../Accesibilidad/navegacionTeclado.js"></script>

<script>
    // =============================================
    // SUBIR FOTO - AUTO SUBMIT AL SELECCIONAR
    // =============================================
    const inputFoto = document.getElementById('inputFotoPerfil');
    const formFoto = document.getElementById('formFotoPerfil');

    if (inputFoto) {
        inputFoto.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                // Validar tamaño y tipo antes de enviar
                const archivo = this.files[0];
                const extensionesPermitidas = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                const tamanoMaximo = 2097152; // 2MB

                if (!extensionesPermitidas.includes(archivo.type)) {
                    alert('⚠️ Solo se permiten imágenes JPG, PNG, GIF o WEBP.');
                    this.value = '';
                    return;
                }

                if (archivo.size > tamanoMaximo) {
                    alert('⚠️ La imagen no debe superar los 2MB.');
                    this.value = '';
                    return;
                }

                // Enviar formulario automáticamente
                if (confirm('¿Deseas actualizar tu foto de perfil?')) {
                    formFoto.submit();
                } else {
                    this.value = '';
                }
            }
        });
    }

    // =============================================
    // MODAL - ABRIR Y CERRAR
    // =============================================
    const modalEditar = document.getElementById('modalEditar');
    const btnEditarPerfil = document.getElementById('btnEditarPerfil');
    const modalCerrar = document.getElementById('modalCerrar');
    const modalCancelar = document.getElementById('modalCancelar');

    function abrirModalEditar() {
        modalEditar.classList.remove('modal-hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModalEditar() {
        modalEditar.classList.add('modal-hidden');
        document.body.style.overflow = 'auto';
    }

    btnEditarPerfil.addEventListener('click', abrirModalEditar);
    modalCerrar.addEventListener('click', cerrarModalEditar);
    modalCancelar.addEventListener('click', cerrarModalEditar);

    modalEditar.addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModalEditar();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalEditar();
        }
    });

    // =============================================
    // VALIDACIÓN FORMULARIO CONTRASEÑA
    // =============================================
    const formPassword = document.getElementById('formPassword');
    if (formPassword) {
        formPassword.addEventListener('submit', function(e) {
            const passwordActual = document.getElementById('password_actual').value;
            const passwordNuevo = document.getElementById('password_nuevo').value;
            const passwordConfirmar = document.getElementById('password_confirmar').value;

            if (passwordNuevo.length < 6) {
                e.preventDefault();
                alert('La nueva contraseña debe tener al menos 6 caracteres.');
                return;
            }

            if (passwordNuevo !== passwordConfirmar) {
                e.preventDefault();
                alert('Las contraseñas no coinciden.');
                return;
            }

            if (passwordActual === passwordNuevo) {
                e.preventDefault();
                alert('La nueva contraseña debe ser diferente a la actual.');
                return;
            }
        });
    }

    // =============================================
    // VALIDACIÓN FORMULARIO EDITAR PERFIL
    // =============================================
    const formEditarPerfil = document.getElementById('formEditarPerfil');
    if (formEditarPerfil) {
        formEditarPerfil.addEventListener('submit', function(e) {
            const nombre = document.getElementById('edit_nombre').value.trim();
            const apellido = document.getElementById('edit_apellido_paterno').value.trim();
            const correo = document.getElementById('edit_correo').value.trim();

            if (!nombre || !apellido || !correo) {
                e.preventDefault();
                alert('Los campos nombre, apellido paterno y correo son obligatorios.');
                return;
            }

            if (!correo.includes('@')) {
                e.preventDefault();
                alert('Ingresa un correo electrónico válido.');
                return;
            }
        });
    }

    console.log('👤 Perfil de Investigador cargado correctamente');
</script>

</body>
</html>