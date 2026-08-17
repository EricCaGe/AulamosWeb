<?php
session_start();

/* =========================================================
   VERIFICAR SESIÓN
   ========================================================= */

if (!isset($_SESSION['usuario'])) {
    header('Location: ../InicioSesion/login.php');
    exit;
}

/* =========================================================
   VERIFICAR QUE SEA ALUMNO
   ========================================================= */

if (
    isset($_SESSION['usuario']['rol']) &&
    $_SESSION['usuario']['rol'] !== 'Alumno'
) {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';


/* =========================================================
   ID DEL USUARIO
   ========================================================= */

$id_usuario = $_SESSION['usuario']['id_usuario'];


/* =========================================================
   OBTENER DATOS DEL USUARIO
   ========================================================= */

$stmt = $conexion->prepare("
    SELECT
        nombre,
        apellido_paterno,
        apellido_materno,
        correo,
        fecha_registro,
        ultimo_acceso,
        foto_perfil
    FROM usuarios
    WHERE id_usuario = ?
");

$stmt->bind_param("i", $id_usuario);
$stmt->execute();

$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

$stmt->close();


if (!$usuario) {
    die("No se encontraron los datos del usuario.");
}


/* =========================================================
   DATOS DEL ALUMNO
   ========================================================= */

$nombre = $usuario['nombre'] ?? '';

$apellido_paterno = $usuario['apellido_paterno'] ?? '';

$apellido_materno = $usuario['apellido_materno'] ?? '';

$correo = $usuario['correo'] ?? 'Correo no disponible';


$nombre_completo = trim(
    $nombre . ' ' .
    $apellido_paterno . ' ' .
    $apellido_materno
);


$fecha_registro = $usuario['fecha_registro'] ?? null;

$ultimo_acceso = $usuario['ultimo_acceso'] ?? null;

$foto_perfil = $usuario['foto_perfil'] ?? null;


/* =========================================================
   MENSAJES
   ========================================================= */

$mensaje = '';
$tipo_mensaje = '';


/* =========================================================
   PROCESAR FORMULARIOS
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =====================================================
       EDITAR PERFIL
       ===================================================== */

    if (isset($_POST['accion']) && $_POST['accion'] === 'editar_perfil') {

        $nombre_nuevo = trim($_POST['nombre'] ?? '');

        $apellido_paterno_nuevo =
            trim($_POST['apellido_paterno'] ?? '');

        $apellido_materno_nuevo =
            trim($_POST['apellido_materno'] ?? '');

        $correo_nuevo =
            trim($_POST['correo'] ?? '');


        /* VALIDACIONES */

        if (
            empty($nombre_nuevo) ||
            empty($apellido_paterno_nuevo) ||
            empty($correo_nuevo)
        ) {

            $mensaje =
                'Los campos nombre, apellido paterno y correo son obligatorios.';

            $tipo_mensaje = 'error';

        } elseif (!filter_var($correo_nuevo, FILTER_VALIDATE_EMAIL)) {

            $mensaje =
                'Ingresa un correo electrónico válido.';

            $tipo_mensaje = 'error';

        } else {

            $stmt = $conexion->prepare("
                UPDATE usuarios
                SET
                    nombre = ?,
                    apellido_paterno = ?,
                    apellido_materno = ?,
                    correo = ?
                WHERE id_usuario = ?
            ");

            $stmt->bind_param(
                "ssssi",
                $nombre_nuevo,
                $apellido_paterno_nuevo,
                $apellido_materno_nuevo,
                $correo_nuevo,
                $id_usuario
            );


            if ($stmt->execute()) {

                /* ACTUALIZAR SESIÓN */

                $_SESSION['usuario']['nombre'] =
                    $nombre_nuevo;

                $_SESSION['usuario']['apellido_paterno'] =
                    $apellido_paterno_nuevo;

                $_SESSION['usuario']['apellido_materno'] =
                    $apellido_materno_nuevo;

                $_SESSION['usuario']['correo'] =
                    $correo_nuevo;


                /* ACTUALIZAR VARIABLES */

                $nombre =
                    $nombre_nuevo;

                $apellido_paterno =
                    $apellido_paterno_nuevo;

                $apellido_materno =
                    $apellido_materno_nuevo;

                $correo =
                    $correo_nuevo;


                $nombre_completo = trim(
                    $nombre . ' ' .
                    $apellido_paterno . ' ' .
                    $apellido_materno
                );


                $mensaje =
                    'Tu perfil se actualizó correctamente.';

                $tipo_mensaje =
                    'exito';

            } else {

                $mensaje =
                    'Ocurrió un error al actualizar tu perfil.';

                $tipo_mensaje =
                    'error';
            }

            $stmt->close();
        }
    }


    /* =====================================================
       CAMBIAR CONTRASEÑA
       ===================================================== */

    if (
        isset($_POST['accion']) &&
        $_POST['accion'] === 'cambiar_password'
    ) {

        $password_actual =
            $_POST['password_actual'] ?? '';

        $password_nuevo =
            $_POST['password_nuevo'] ?? '';

        $password_confirmar =
            $_POST['password_confirmar'] ?? '';


        if (
            empty($password_actual) ||
            empty($password_nuevo) ||
            empty($password_confirmar)
        ) {

            $mensaje =
                'Todos los campos de contraseña son obligatorios.';

            $tipo_mensaje =
                'error';

        } elseif ($password_nuevo !== $password_confirmar) {

            $mensaje =
                'Las nuevas contraseñas no coinciden.';

            $tipo_mensaje =
                'error';

        } elseif (strlen($password_nuevo) < 6) {

            $mensaje =
                'La nueva contraseña debe tener al menos 6 caracteres.';

            $tipo_mensaje =
                'error';

        } else {

            /* OBTENER CONTRASEÑA ACTUAL */

            $stmt = $conexion->prepare("
                SELECT password_hash
                FROM usuarios
                WHERE id_usuario = ?
            ");

            $stmt->bind_param(
                "i",
                $id_usuario
            );

            $stmt->execute();

            $resultado =
                $stmt->get_result();

            $datos_password =
                $resultado->fetch_assoc();

            $stmt->close();


            /* COMPROBAR CONTRASEÑA */

            if (
                $datos_password &&
                password_verify(
                    $password_actual,
                    $datos_password['password_hash']
                )
            ) {

                $nuevo_hash =
                    password_hash(
                        $password_nuevo,
                        PASSWORD_DEFAULT
                    );


                $stmt = $conexion->prepare("
                    UPDATE usuarios
                    SET password_hash = ?
                    WHERE id_usuario = ?
                ");

                $stmt->bind_param(
                    "si",
                    $nuevo_hash,
                    $id_usuario
                );


                if ($stmt->execute()) {

                    $mensaje =
                        'Tu contraseña se actualizó correctamente.';

                    $tipo_mensaje =
                        'exito';

                } else {

                    $mensaje =
                        'No fue posible actualizar la contraseña.';

                    $tipo_mensaje =
                        'error';
                }

                $stmt->close();

            } else {

                $mensaje =
                    'La contraseña actual es incorrecta.';

                $tipo_mensaje =
                    'error';
            }
        }
    }
}

/* =====================================================
   SUBIR FOTO DE PERFIL
   ===================================================== */

if (
    isset($_POST['accion']) &&
    $_POST['accion'] === 'subir_foto'
) {

    if (
        isset($_FILES['foto_perfil']) &&
        $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK
    ) {

        $archivo = $_FILES['foto_perfil'];

        $nombre_original = $archivo['name'];
        $tamano = $archivo['size'];
        $temporal = $archivo['tmp_name'];

        // Extensiones permitidas
        $extensiones_permitidas = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp'
        ];

        $extension = strtolower(
            pathinfo(
                $nombre_original,
                PATHINFO_EXTENSION
            )
        );

        // Validar extensión
        if (!in_array($extension, $extensiones_permitidas)) {

            $mensaje =
                'Solo se permiten imágenes JPG, PNG, GIF o WEBP.';

            $tipo_mensaje = 'error';

        // Validar tamaño máximo: 2 MB
        } elseif ($tamano > 2097152) {

            $mensaje =
                'La imagen no debe superar los 2 MB.';

            $tipo_mensaje = 'error';

        } else {

            // Carpeta donde se guardarán las imágenes
            $carpeta_destino = '../uploads/perfiles/';

            // Crear carpeta si no existe
            if (!is_dir($carpeta_destino)) {

                mkdir(
                    $carpeta_destino,
                    0777,
                    true
                );
            }

            // Nombre único
            $nombre_archivo =
                'perfil_' .
                $id_usuario .
                '_' .
                time() .
                '.' .
                $extension;

            $ruta_completa =
                $carpeta_destino .
                $nombre_archivo;

            // Mover imagen
            if (
                move_uploaded_file(
                    $temporal,
                    $ruta_completa
                )
            ) {

                // Eliminar foto anterior
                if (
                    !empty($foto_perfil) &&
                    file_exists(
                        $carpeta_destino .
                        $foto_perfil
                    )
                ) {

                    unlink(
                        $carpeta_destino .
                        $foto_perfil
                    );
                }

                // Guardar nombre de imagen en BD
                $stmt = $conexion->prepare("
                    UPDATE usuarios
                    SET foto_perfil = ?
                    WHERE id_usuario = ?
                ");

                $stmt->bind_param(
                    "si",
                    $nombre_archivo,
                    $id_usuario
                );

                if ($stmt->execute()) {

                    // Actualizar sesión
                    $_SESSION['usuario']['foto_perfil'] =
                        $nombre_archivo;

                    // Actualizar variable
                    $foto_perfil =
                        $nombre_archivo;

                    $mensaje =
                        'Foto de perfil actualizada correctamente.';

                    $tipo_mensaje =
                        'exito';

                } else {

                    $mensaje =
                        'Error al guardar la foto en la base de datos.';

                    $tipo_mensaje =
                        'error';
                }

                $stmt->close();

            } else {

                $mensaje =
                    'Error al subir la imagen.';

                $tipo_mensaje =
                    'error';
            }
        }

    } else {

        $mensaje =
            'No se seleccionó ninguna imagen.';

        $tipo_mensaje =
            'error';
    }
}

/* =========================================================
   DATOS PARA MOSTRAR
   ========================================================= */

$fecha_mostrar = 'No disponible';

if (!empty($fecha_registro)) {

    $fecha_mostrar =
        date(
            'd/m/Y',
            strtotime($fecha_registro)
        );
}


$acceso_mostrar = 'No disponible';

if (!empty($ultimo_acceso)) {

    $acceso_mostrar =
        date(
            'd/m/Y h:i a',
            strtotime($ultimo_acceso)
        );
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Aulamos - Mi Perfil</title>


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >


    <!-- CSS DEL PERFIL -->

    <link
        rel="stylesheet"
        href="Style/perfil.css"
    >

</head>


<body>


<div class="perfil-container">


    <!-- =====================================================
         ENCABEZADO
         ===================================================== -->

    <header class="perfil-header">

        <div class="perfil-header-left">

            <a
                href="mas.php"
                class="btn-regresar"
                title="Regresar"
            >

                <i class="fa-solid fa-arrow-left"></i>

            </a>


            <div>

                <h1>Mi perfil</h1>

                <p>
                    Consulta y administra tu información personal
                </p>

            </div>

        </div>


        <a
            href="../InicioSesion/cerrar_sesion.php"
            class="btn-cerrar"
        >

            <i class="fa-solid fa-right-from-bracket"></i>

            Cerrar sesión

        </a>

    </header>



    <!-- =====================================================
         MENSAJE
         ===================================================== -->

    <?php if (!empty($mensaje)): ?>

        <div class="mensaje <?php echo $tipo_mensaje; ?>">

            <?php echo htmlspecialchars($mensaje); ?>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         TARJETA PRINCIPAL
         ===================================================== -->

   <section class="perfil-card">

    <!-- AVATAR -->
    <div class="perfil-avatar">

        <div class="avatar">

            <?php if (!empty($foto_perfil)): ?>

                <img
                    src="../uploads/perfiles/<?php echo htmlspecialchars($foto_perfil); ?>"
                    alt="Foto de perfil"
                    class="foto-perfil"
                >

            <?php else: ?>

                <i class="fa-regular fa-user"></i>

            <?php endif; ?>

        </div>

        <!-- FORMULARIO PARA SUBIR FOTO -->

        <form
            method="POST"
            enctype="multipart/form-data"
            id="formFotoPerfil"
        >

            <input
                type="hidden"
                name="accion"
                value="subir_foto"
            >

            <label
                for="inputFotoPerfil"
                class="btn-cambiar-foto"
            >

                <i class="fa-solid fa-camera"></i>

                Cambiar foto

            </label>

            <input
                type="file"
                name="foto_perfil"
                id="inputFotoPerfil"
                accept="image/jpeg,image/png,image/gif,image/webp"
                style="display:none;"
            >

        </form>

    </div>

    <!-- INFORMACIÓN -->

    <div class="perfil-datos">

        <h2>
            <?php
            echo htmlspecialchars($nombre_completo);
            ?>
        </h2>

        <p class="correo">
            <?php
            echo htmlspecialchars($correo);
            ?>
        </p>

        <span class="rol">

            <i class="fa-solid fa-graduation-cap"></i>

            Alumno

        </span>

    </div>

</section>
    <!-- =====================================================
         INFORMACIÓN PERSONAL
         ===================================================== -->
    <section class="seccion">
        <h2>
            <i class="fa-regular fa-user"></i>
            Información personal
        </h2>
        <div class="informacion-grid">
            <div class="dato">
                <span>Nombre</span>
                <strong>
                    <?php
                    echo htmlspecialchars($nombre);
                    ?>
                </strong>
            </div>
            <div class="dato">
                <span>Apellido paterno</span>
                <strong>
                    <?php
                    echo htmlspecialchars($apellido_paterno);
                    ?>
                </strong>
            </div>
            <div class="dato">
                <span>Apellido materno</span>
                <strong>
                    <?php
                    echo !empty($apellido_materno)
                        ? htmlspecialchars($apellido_materno)
                        : 'No registrado';

                    ?>

                </strong>

            </div>


            <div class="dato">

                <span>Correo electrónico</span>

                <strong>
                    <?php
                    echo htmlspecialchars($correo);
                    ?>
                </strong>

            </div>


            <div class="dato">

                <span>Tipo de usuario</span>

                <strong class="texto-rol">

                    <i class="fa-solid fa-graduation-cap"></i>

                    Alumno

                </strong>

            </div>


            <div class="dato">

                <span>Fecha de registro</span>

                <strong>
                    <?php
                    echo htmlspecialchars($fecha_mostrar);
                    ?>
                </strong>

            </div>


            <div class="dato">

                <span>Último acceso</span>

                <strong>
                    <?php
                    echo htmlspecialchars($acceso_mostrar);
                    ?>
                </strong>

            </div>

        </div>

    </section>



    <!-- =====================================================
         SEGURIDAD
         ===================================================== -->

    <section class="seccion">

        <h2>

            <i class="fa-solid fa-lock"></i>

            Seguridad

        </h2>


        <div class="seguridad-card">

            <div class="seguridad-titulo">

                <i class="fa-solid fa-key"></i>

                <div>

                    <h3>
                        Cambiar contraseña
                    </h3>

                    <p>
                        Actualiza la contraseña de tu cuenta
                    </p>

                </div>

            </div>


            <form
                method="POST"
                action=""
                class="formulario"
            >

                <input
                    type="hidden"
                    name="accion"
                    value="cambiar_password"
                >


                <div class="campo">

                    <label for="password_actual">

                        Contraseña actual

                    </label>

                    <input
                        type="password"
                        id="password_actual"
                        name="password_actual"
                        placeholder="Ingresa tu contraseña actual"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="password_nuevo">

                        Nueva contraseña

                    </label>

                    <input
                        type="password"
                        id="password_nuevo"
                        name="password_nuevo"
                        placeholder="Mínimo 6 caracteres"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="password_confirmar">

                        Confirmar nueva contraseña

                    </label>

                    <input
                        type="password"
                        id="password_confirmar"
                        name="password_confirmar"
                        placeholder="Confirma tu nueva contraseña"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn-guardar"
                >

                    <i class="fa-solid fa-check"></i>

                    Actualizar contraseña

                </button>

            </form>

        </div>

    </section>



    <!-- =====================================================
         EDITAR PERFIL
         ===================================================== -->

    <section class="seccion">

        <h2>

            <i class="fa-solid fa-pen"></i>

            Editar información

        </h2>


        <form
            method="POST"
            action=""
            class="formulario formulario-editar"
        >

            <input
                type="hidden"
                name="accion"
                value="editar_perfil"
            >


            <div class="formulario-grid">


                <div class="campo">

                    <label for="nombre">

                        Nombre

                    </label>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        value="<?php echo htmlspecialchars($nombre); ?>"
                        required
                    >
                </div>
                <div class="campo">
                    <label for="apellido_paterno">
                        Apellido paterno
                    </label>
                    <input
                        type="text"
                        id="apellido_paterno"
                        name="apellido_paterno"
                        value="<?php echo htmlspecialchars($apellido_paterno); ?>"
                        required
                    >
                </div>
                <div class="campo">
                    <label for="apellido_materno">
                        Apellido materno
                    </label>
                    <input
                        type="text"
                        id="apellido_materno"
                        name="apellido_materno"
                       value="<?php echo htmlspecialchars($apellido_materno); ?>"
                    >
                </div>
                <div class="campo">
                    <label for="correo">
                        Correo electrónico
                    </label>
                    <input
                        type="email"
                        id="correo"
                        name="correo"
                        value="<?php echo htmlspecialchars($correo); ?>"
                        required
                    >
                </div>
            </div>
            <button
                type="submit"
                class="btn-guardar"
            >
                <i class="fa-solid fa-floppy-disk"></i>
                Guardar cambios
            </button>
        </form>
    </section>
    <!-- =====================================================
         ACCIONES
         ===================================================== -->
    <section class="acciones">
        <a
            href="mas.php"
           class="btn-secundario"
        >
            <i class="fa-solid fa-arrow-left"></i>
            Volver a Más
        </a>
        <a
            href="../InicioSesion/cerrar_sesion.php"
            class="btn-salir"
        >
            <i class="fa-solid fa-right-from-bracket"></i>
            Cerrar sesión
        </a>
    </section>
<script src="js/perfil.js"></script>
</div>
</body>
</html>