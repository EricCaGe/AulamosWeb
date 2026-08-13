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
$stmt = $conexion->prepare("SELECT fecha_registro, ultimo_acceso FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario_data = $resultado->fetch_assoc();
$stmt->close();

$fecha_registro = $usuario_data['fecha_registro'] ?? date('d/m/Y');
$ultimo_acceso = $usuario_data['ultimo_acceso'] ?? 'Nunca';

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

                    if (password_verify($password_actual, $usuario['password_hash'])) {
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
                    } else {
                        $mensaje = 'La contraseña actual es incorrecta.';
                        $tipo_mensaje = 'error';
                    }
                }
                break;

            case 'guardar_preferencias':
                $idioma = $_POST['idioma'] ?? 'es';
                $notificaciones = isset($_POST['notificaciones']) ? 1 : 0;
                $tema = $_POST['tema'] ?? 'claro';

                $_SESSION['preferencias'] = [
                    'idioma' => $idioma,
                    'notificaciones' => $notificaciones,
                    'tema' => $tema
                ];

                $mensaje = 'Preferencias guardadas correctamente.';
                $tipo_mensaje = 'exito';
                break;
        }
    }
}

$idioma_actual = $_SESSION['preferencias']['idioma'] ?? 'es';
$notificaciones_actual = $_SESSION['preferencias']['notificaciones'] ?? 1;
$tema_actual = $_SESSION['preferencias']['tema'] ?? 'claro';

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
                    <img src="https://placehold.co/40x40/3b71f3/white?text=👤" alt="Avatar" class="avatar">
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
                        <img src="https://placehold.co/120x120/3b71f3/white?text=👤" alt="Avatar">
                    </div>
                    <button class="btn-cambiar-foto">
                        <i class="fa-solid fa-camera"></i> Cambiar foto
                    </button>
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
                        <form method="POST" action="" class="config-form">
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

                    <!-- Preferencias -->
                    <div class="config-card">
                        <h3><i class="fa-solid fa-sliders"></i> Preferencias</h3>
                        <form method="POST" action="" class="config-form">
                            <input type="hidden" name="accion" value="guardar_preferencias">
                            <div class="form-group">
                                <label for="idioma">Idioma</label>
                                <select id="idioma" name="idioma" class="clean-select">
                                    <option value="es" <?php echo $idioma_actual === 'es' ? 'selected' : ''; ?>>Español</option>
                                    <option value="en" <?php echo $idioma_actual === 'en' ? 'selected' : ''; ?>>English</option>
                                </select>
                            </div>
                            <div class="form-group toggle-group">
                                <label for="notificaciones">Notificaciones</label>
                                <label class="switch">
                                    <input type="checkbox" id="notificaciones" name="notificaciones" <?php echo $notificaciones_actual ? 'checked' : ''; ?>>
                                    <span class="slider round"></span>
                                </label>
                                <span class="toggle-label"><?php echo $notificaciones_actual ? 'Activadas' : 'Desactivadas'; ?></span>
                            </div>
                            <div class="form-group">
                                <label for="tema">Tema</label>
                                <select id="tema" name="tema" class="clean-select">
                                    <option value="claro" <?php echo $tema_actual === 'claro' ? 'selected' : ''; ?>>Claro</option>
                                    <option value="oscuro" <?php echo $tema_actual === 'oscuro' ? 'selected' : ''; ?>>Oscuro</option>
                                    <option value="sistema" <?php echo $tema_actual === 'sistema' ? 'selected' : ''; ?>>Sistema</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-guardar">Guardar preferencias</button>
                        </form>
                    </div>

                </div>
            </section>

            <!-- ===== SECCIÓN: ACCIONES ===== -->
            <section class="perfil-seccion acciones">
                <h2><i class="fa-solid fa-ellipsis-h"></i> Acciones</h2>
                <div class="acciones-grid">
                    <button class="btn-accion btn-editar" onclick="abrirModalEditar()">
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
            <button class="modal-cerrar" onclick="cerrarModalEditar()">&times;</button>
        </div>
        <form method="POST" action="" class="modal-form">
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
                <button type="button" class="btn-cancelar" onclick="cerrarModalEditar()">Cancelar</button>
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
    function abrirModalEditar() {
        document.getElementById('modalEditar').classList.remove('modal-hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModalEditar() {
        document.getElementById('modalEditar').classList.add('modal-hidden');
        document.body.style.overflow = 'auto';
    }

    document.getElementById('modalEditar').addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModalEditar();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalEditar();
        }
    });

    document.querySelector('.toggle-group .switch input').addEventListener('change', function() {
        const label = this.closest('.toggle-group').querySelector('.toggle-label');
        label.textContent = this.checked ? 'Activadas' : 'Desactivadas';
    });
</script>

</body>
</html>