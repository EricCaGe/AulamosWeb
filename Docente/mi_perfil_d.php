<?php
session_start();
// Pasar el ID del usuario al JavaScript para accesibilidad por usuario
echo '<script>window.idUsuario = ' . $_SESSION['usuario']['id_usuario'] . ';</script>';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$nombre_docente = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido_paterno'];
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
$ruta_foto = !empty($foto_perfil) ? '../uploads/perfiles/' . $foto_perfil : 'https://placehold.co/120x120/3b71f3/white?text=👨‍🏫';

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
                $nombre_docente = $nombre . ' ' . $apellido_paterno;
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
$ruta_foto_header = !empty($foto_perfil_actual) ? '../uploads/perfiles/' . $foto_perfil_actual : 'https://placehold.co/40x40/3b71f3/white?text=👨‍🏫';
$ruta_foto_grande = !empty($foto_perfil_actual) ? '../uploads/perfiles/' . $foto_perfil_actual : 'https://placehold.co/120x120/3b71f3/white?text=👨‍🏫';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Docente</title>
    
    <link rel="stylesheet" href="styles/docente.css">
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
        .estadisticas-docente {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .estadistica-card {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .estadistica-card .numero {
            font-size: 24px;
            font-weight: 700;
            color: #3b71f3;
        }
        .estadistica-card .etiqueta {
            font-size: 13px;
            color: #64748b;
            margin-top: 5px;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../img/logo_g.png" alt="Búho Aulamos" class="logo-img">
        </div>
        
        <nav class="menu">
                <a href="docente_dashboard.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="crear_recurso.php" class="menu-item"><i class="fa-solid fa-medal"></i> Crear Recurso</a>
                <a href="crear_actividad.php" class="menu-item"><i class="fa-solid fa-clipboard-check"></i> Crear Actividad</a>
                <a href="crear_evaluacion.php" class="menu-item"><i class="fa-solid fa-clipboard-list"></i> Crear Evaluacion</a>
                <a href="ver_estudiantes.php" class="menu-item"><i class="fa-solid fa-users"></i> Ver Estudiantes</a>
                <a href="reporte.php" class="menu-item"><i class="fa-solid fa-chart-column"></i> Reportes</a>
                <a href="pasarlista.php" class="menu-item"><i class="fa-solid fa-bars"></i> Pasar Lista</a>
                <a href="mi_perfil_d.php" class="menu-item active"><i class="fa-solid fa-user"></i> Mi Perfil</a>
                <div class="menu-spacer"></div>
            <button class="btn-accessibility-main" onclick="toggleBarraAccesibilidad()"><i class="fa-solid fa-universal-access"></i> Accesibilidad</button>
            <a href="../InicioSesion/cerrar_sesion.php" class="menu-item btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        
        <!-- ========================================== -->
        <!-- ENCABEZADO CON MENÚ ACTUALIZADO            -->
        <!-- ========================================== -->
        <header class="content-header">
            <div class="welcome-text">
                <h1><i class="fa-solid fa-user"></i> Mi Perfil</h1>
                <p>Gestiona tu información como docente</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-assistant" id="btn-asistente" onclick="window.location.href='../Alumno/ChatbotDocente.php?rol=docente'">
                    Asistente Virtual <span class="robot-icon">🤖</span>
                </button>
                <div class="icon-bell-container">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <div class="user-profile">
                    <img src="<?php echo $ruta_foto_header; ?>" alt="Avatar Docente" class="avatar">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($nombre_docente); ?></span>
                        <span class="user-role">Docente</span>
                    </div>
                </div>
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
                    <h3><?php echo htmlspecialchars($nombre_docente); ?></h3>
                    <p><?php echo htmlspecialchars($usuario['correo']); ?></p>
                    <p class="rol"><i class="fa-solid fa-chalkboard-user"></i> Docente</p>
                    <p style="font-size: 13px; color: #94a3b8; margin-top: 8px;">
                        <i class="fa-regular fa-clock"></i> Último acceso: <?php echo date('d/m/Y H:i'); ?>
                    </p>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- ESTADÍSTICAS DEL DOCENTE                   -->
        <!-- ========================================== -->
        <section class="panel-academico">
            <h3 class="section-title"><i class="fa-solid fa-chart-simple"></i> Estadísticas de actividad</h3>
            
            <?php
            // Obtener estadísticas del docente
            $stats_query = "SELECT 
                (SELECT COUNT(*) FROM cursos WHERE id_docente = ?) as total_cursos,
                (SELECT COUNT(*) FROM actividades WHERE id_docente = ?) as total_actividades,
                (SELECT COUNT(*) FROM grupos WHERE id_docente = ?) as total_grupos,
                (SELECT COUNT(*) FROM inscripciones i 
                 JOIN cursos c ON i.id_curso = c.id_curso 
                 WHERE c.id_docente = ?) as total_alumnos";
            
            $stats_stmt = $conexion->prepare($stats_query);
            $stats_stmt->bind_param("iiii", $id_usuario, $id_usuario, $id_usuario, $id_usuario);
            $stats_stmt->execute();
            $stats_result = $stats_stmt->get_result();
            $estadisticas = $stats_result->fetch_assoc();
            $stats_stmt->close();
            ?>
            
            <div class="estadisticas-docente">
                <div class="estadistica-card">
                    <div class="numero"><?php echo $estadisticas['total_cursos'] ?? 0; ?></div>
                    <div class="etiqueta"><i class="fa-solid fa-cubes"></i> Cursos</div>
                </div>
                <div class="estadistica-card">
                    <div class="numero"><?php echo $estadisticas['total_actividades'] ?? 0; ?></div>
                    <div class="etiqueta"><i class="fa-solid fa-tasks"></i> Actividades</div>
                </div>
                <div class="estadistica-card">
                    <div class="numero"><?php echo $estadisticas['total_grupos'] ?? 0; ?></div>
                    <div class="etiqueta"><i class="fa-solid fa-layer-group"></i> Grupos</div>
                </div>
                <div class="estadistica-card">
                    <div class="numero"><?php echo $estadisticas['total_alumnos'] ?? 0; ?></div>
                    <div class="etiqueta"><i class="fa-solid fa-user-graduate"></i> Alumnos</div>
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
                        <input type="text" value="Docente" disabled>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                    <a href="docente_dashboard.php" style="background: #f1f5f9; border: none; padding: 12px 28px; border-radius: 10px; font-weight: 600; font-size: 15px; color: #475569; cursor: pointer; text-decoration: none;">Cancelar</a>
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

console.log('👨‍🏫 Perfil de Docente cargado correctamente');
</script>

<script src="js/docente.js"></script>
<script src="js/lector.js"></script>
<script src="../Accesibilidad/accesibilidad.js"></script>
</body>
</html>