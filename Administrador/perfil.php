<?php
session_start();
// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$nombre_admin = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
$pagina_actual = basename($_SERVER['PHP_SELF']);

// Obtener datos del usuario
$id_usuario = $_SESSION['usuario']['id_usuario'];
$stmt = $conexion->prepare("SELECT nombre, apellido_paterno, apellido_materno, correo, foto_perfil FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
$stmt->close();

$foto_perfil = $usuario['foto_perfil'] ?? null;
$ruta_foto = !empty($foto_perfil) ? '../uploads/perfiles/' . $foto_perfil : 'https://placehold.co/120x120/3b71f3/white?text=👤';

// Procesar actualización de perfil
$mensaje = '';
$tipo = '';

// =============================================
// PROCESAR SUBIDA DE FOTO DE PERFIL
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && $_POST['accion'] === 'subir_foto') {
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $archivo = $_FILES['foto_perfil'];
            $nombre_original = $archivo['name'];
            $tamano = $archivo['size'];
            $temp = $archivo['tmp_name'];
            
            $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
            
            if (!in_array($extension, $extensiones_permitidas)) {
                $mensaje = 'Solo se permiten imágenes JPG, PNG, GIF o WEBP.';
                $tipo = 'error';
            } elseif ($tamano > 2097152) {
                $mensaje = 'La imagen no debe superar los 2MB.';
                $tipo = 'error';
            } else {
                $carpeta_destino = '../uploads/perfiles/';
                if (!is_dir($carpeta_destino)) {
                    mkdir($carpeta_destino, 0777, true);
                }
                
                $nombre_archivo = 'perfil_' . $id_usuario . '_' . time() . '.' . $extension;
                $ruta_completa = $carpeta_destino . $nombre_archivo;
                
                if (move_uploaded_file($temp, $ruta_completa)) {
                    // Eliminar foto anterior si existe
                    if ($foto_perfil && file_exists($carpeta_destino . $foto_perfil)) {
                        unlink($carpeta_destino . $foto_perfil);
                    }
                    
                    $stmt = $conexion->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?");
                    $stmt->bind_param("si", $nombre_archivo, $id_usuario);
                    if ($stmt->execute()) {
                        $_SESSION['usuario']['foto_perfil'] = $nombre_archivo;
                        $foto_perfil = $nombre_archivo;
                        $ruta_foto = '../uploads/perfiles/' . $nombre_archivo;
                        $mensaje = 'Foto de perfil actualizada correctamente.';
                        $tipo = 'exito';
                    } else {
                        $mensaje = 'Error al guardar la foto en la base de datos.';
                        $tipo = 'error';
                    }
                    $stmt->close();
                } else {
                    $mensaje = 'Error al subir la imagen.';
                    $tipo = 'error';
                }
            }
        } else {
            $mensaje = 'No se seleccionó ninguna imagen.';
            $tipo = 'error';
        }
    } else {
        // Procesar actualización de datos personales
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
        $apellido_materno = trim($_POST['apellido_materno'] ?? '');

        if (empty($nombre) || empty($apellido_paterno)) {
            $mensaje = 'Los campos nombre y apellido paterno son obligatorios.';
            $tipo = 'error';
        } else {
            $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, apellido_paterno = ?, apellido_materno = ? WHERE id_usuario = ?");
            $stmt->bind_param("sssi", $nombre, $apellido_paterno, $apellido_materno, $id_usuario);
            if ($stmt->execute()) {
                $_SESSION['usuario']['nombre'] = $nombre;
                $_SESSION['usuario']['apellido_paterno'] = $apellido_paterno;
                $_SESSION['usuario']['apellido_materno'] = $apellido_materno;
                $nombre_admin = $nombre . ' ' . $apellido_paterno;
                $mensaje = 'Perfil actualizado correctamente.';
                $tipo = 'exito';
                
                $usuario['nombre'] = $nombre;
                $usuario['apellido_paterno'] = $apellido_paterno;
                $usuario['apellido_materno'] = $apellido_materno;
            } else {
                $mensaje = 'Error al actualizar el perfil.';
                $tipo = 'error';
            }
            $stmt->close();
        }
    }
}

// Actualizar ruta de foto después de posible cambio
$foto_perfil_actual = $_SESSION['usuario']['foto_perfil'] ?? $foto_perfil;
$ruta_foto_header = !empty($foto_perfil_actual) ? '../uploads/perfiles/' . $foto_perfil_actual : 'https://placehold.co/40x40/3b71f3/white?text=👤';
$ruta_foto_grande = !empty($foto_perfil_actual) ? '../uploads/perfiles/' . $foto_perfil_actual : 'https://placehold.co/120x120/3b71f3/white?text=👤';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Administrador</title>
    
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="../Accesibilidad/accesibilidad.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .perfil-avatar-container {
            display: flex;
            align-items: center;
            gap: 30px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .avatar-grande {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #3b71f3;
        }
        .btn-cambiar-foto {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #e8f0fe;
            color: #3b71f3;
            border: 1px solid #3b71f3;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
        }
        .btn-cambiar-foto:hover {
            background: #3b71f3;
            color: white;
        }
        .perfil-info h3 {
            margin: 0 0 5px 0;
            font-size: 20px;
        }
        .perfil-info p {
            margin: 0;
            color: #64748b;
        }
        .perfil-info .rol {
            font-size: 14px;
            color: #3b71f3;
            font-weight: 600;
        }
        .form-group label {
            font-weight: 600;
            font-size: 14px;
            color: #1e293b;
            margin-bottom: 4px;
            display: block;
        }
        .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            border-color: #3b71f3;
            outline: none;
        }
        .form-group input:disabled {
            background: #f1f5f9;
            color: #94a3b8;
        }
        .text-danger {
            color: #dc2626;
        }
        .mensaje-exito {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        .mensaje-error {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logogeneral.png" alt="Logo Aulamos" class="logo">
        </div>
        
        <nav class="menu">
            <a href="admin_dashboard.php" class="menu-item <?php echo ($pagina_actual == 'admin_dashboard.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="ciclos_escolares.php" class="menu-item <?php echo ($pagina_actual == 'ciclos_escolares.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar"></i> Ciclos escolares
            </a>
            <a href="periodos.php" class="menu-item <?php echo ($pagina_actual == 'periodos.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock"></i> Periodos
            </a>
            <a href="materias.php" class="menu-item <?php echo ($pagina_actual == 'materias.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-book"></i> Materias
            </a>
            <a href="grupos.php" class="menu-item <?php echo ($pagina_actual == 'grupos.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-layer-group"></i> Grupos
            </a>
            <a href="cursos.php" class="menu-item <?php echo ($pagina_actual == 'cursos.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-cubes"></i> Cursos
            </a>
            <a href="inscripciones.php" class="menu-item <?php echo ($pagina_actual == 'inscripciones.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-pen-to-square"></i> Inscripciones
            </a>
            <a href="configuracion.php" class="menu-item <?php echo ($pagina_actual == 'configuracion.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gear"></i> Configuración
            </a>
            <a href="perfil.php" class="menu-item <?php echo ($pagina_actual == 'perfil.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user"></i> Mi perfil
            </a>
        </nav>
        
        <button class="btn-accesibilidad-header" id="btnAccesibilidad" onclick="toggleBarraAccesibilidad()" style="width:100%; margin-top:20px; background:#5a189a; color:white; border:none; padding:12px; border-radius:10px; cursor:pointer; font-weight:bold;">
            <i class="fa-solid fa-universal-access"></i> Accesibilidad
        </button>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        
        <!-- ENCABEZADO -->
        <header class="content-header">
            <div class="welcome-text">
                <h1><i class="fa-solid fa-user"></i> Mi Perfil</h1>
                <p>Administra tu información personal</p>
            </div>
            <div class="header-actions">
                <button class="btn-assistant" id="btn-asistente" onclick="window.location.href='../Alumno/ChatbotAdmin.php'">
                    <i class="fa-solid fa-comment-dots"></i> Chatbot
                </button>
                <div class="icon-bell">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <a href="perfil.php" class="user-profile" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <img src="<?php echo $ruta_foto_header; ?>" alt="Avatar Admin" class="avatar">
                    <span class="user-name"><?php echo htmlspecialchars($nombre_admin); ?></span>
                    <i class="fa-solid fa-chevron-down drop-icon"></i>
                </a>
                <a href="../InicioSesion/cerrar_sesion.php" class="btn-logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <!-- MENSAJES -->
        <?php if ($mensaje): ?>
            <div class="<?php echo ($tipo === 'exito') ? 'mensaje-exito' : 'mensaje-error'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- FOTO DE PERFIL Y DATOS                     -->
        <!-- ========================================== -->
        <section class="panel-academico">
            <div class="perfil-avatar-container">
                <div style="position: relative; text-align: center;">
                    <img src="<?php echo $ruta_foto_grande; ?>" alt="Foto de perfil" class="avatar-grande">
                    
                    <!-- FORMULARIO SUBIR FOTO -->
                    <form method="POST" enctype="multipart/form-data" id="formFotoPerfil" style="margin-top: 12px;">
                        <input type="hidden" name="accion" value="subir_foto">
                        <label for="inputFotoPerfil" class="btn-cambiar-foto" style="cursor:pointer;">
                            <i class="fa-solid fa-camera"></i> Cambiar foto
                        </label>
                        <input type="file" name="foto_perfil" id="inputFotoPerfil" accept="image/*" style="display:none;">
                    </form>
                </div>
                <div class="perfil-info">
                    <h3><?php echo htmlspecialchars($nombre_admin); ?></h3>
                    <p><?php echo htmlspecialchars($usuario['correo']); ?></p>
                    <p class="rol"><i class="fa-solid fa-shield-halved"></i> Administrador</p>
                    <p style="font-size: 13px; color: #94a3b8; margin-top: 8px;">
                        <i class="fa-regular fa-clock"></i> Último acceso: <?php echo date('d/m/Y H:i'); ?>
                    </p>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- FORMULARIO DE PERFIL                       -->
        <!-- ========================================== -->
        <section class="panel-academico">
            <h3 class="section-title"><i class="fa-solid fa-pen"></i> Información personal</h3>
            
            <form method="POST" action="" id="formEditarPerfil">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Nombre(s) <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido Paterno <span class="text-danger">*</span></label>
                        <input type="text" name="apellido_paterno" value="<?php echo htmlspecialchars($usuario['apellido_paterno'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido Materno</label>
                        <input type="text" name="apellido_materno" value="<?php echo htmlspecialchars($usuario['apellido_materno'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Correo electrónico</label>
                        <input type="email" value="<?php echo htmlspecialchars($usuario['correo'] ?? ''); ?>" disabled>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Rol</label>
                        <input type="text" value="Administrador" disabled>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                    <a href="admin_dashboard.php" style="background: #f1f5f9; border: none; padding: 12px 28px; border-radius: 10px; font-weight: 600; font-size: 15px; color: #475569; cursor: pointer; text-decoration: none;">Cancelar</a>
                    <button type="submit" style="background: #3b71f3; color: white; border: none; padding: 12px 32px; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer;">Guardar cambios</button>
                </div>
            </form>
        </section>

        <!-- BARRA DE ACCESIBILIDAD -->
        <?php include '../Accesibilidad/accesibilidad.php'; ?>

    </main>
</div>

<!-- BOTÓN FLOTANTE DE ACCESIBILIDAD -->
<button class="btn-accesibilidad-flotante" id="btnAccesibilidadFlotante" onclick="toggleBarraAccesibilidad()">
    <i class="fa-solid fa-universal-access"></i>
</button>

<script>
// =============================================
// SUBIR FOTO - AUTO SUBMIT AL SELECCIONAR
// =============================================
const inputFoto = document.getElementById('inputFotoPerfil');
const formFoto = document.getElementById('formFotoPerfil');

if (inputFoto) {
    inputFoto.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            const archivo = this.files[0];
            const extensionesPermitidas = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            const tamanoMaximo = 2097152;

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

            if (confirm('¿Deseas actualizar tu foto de perfil?')) {
                formFoto.submit();
            } else {
                this.value = '';
            }
        }
    });
}

// =============================================
// VALIDACIÓN FORMULARIO EDITAR PERFIL
// =============================================
const formEditarPerfil = document.getElementById('formEditarPerfil');
if (formEditarPerfil) {
    formEditarPerfil.addEventListener('submit', function(e) {
        const nombre = document.querySelector('input[name="nombre"]').value.trim();
        const apellido = document.querySelector('input[name="apellido_paterno"]').value.trim();

        if (!nombre || !apellido) {
            e.preventDefault();
            alert('Los campos nombre y apellido paterno son obligatorios.');
        }
    });
}

console.log('👤 Perfil de Administrador cargado correctamente');
</script>

<script src="js/admin.js"></script>
<script src="js/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
</body>
</html>