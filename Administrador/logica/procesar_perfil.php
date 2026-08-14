<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Admin') {
    header('Location: ../login.php?error=no_autorizado');
    exit;
}

require_once '../Conexion/conexion.php';

// =============================================
// PROCESAR SUBIDA DE FOTO DE PERFIL
// =============================================
if (isset($_POST['accion']) && $_POST['accion'] === 'subir_foto') {
    $id_usuario = $_SESSION['usuario']['id_usuario'];
    
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['foto_perfil'];
        $nombre_original = $archivo['name'];
        $tamano = $archivo['size'];
        $temp = $archivo['tmp_name'];
        
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $extensiones_permitidas)) {
            header('Location: ../perfil.php?mensaje=Solo se permiten imágenes JPG, PNG, GIF o WEBP&tipo=error');
            exit;
        }
        
        if ($tamano > 2097152) {
            header('Location: ../perfil.php?mensaje=La imagen no debe superar los 2MB&tipo=error');
            exit;
        }
        
        // Obtener foto actual para eliminarla
        $stmt = $conexion->prepare("SELECT foto_perfil FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();
        $foto_anterior = $usuario['foto_perfil'] ?? null;
        $stmt->close();
        
        $carpeta_destino = '../uploads/perfiles/';
        if (!is_dir($carpeta_destino)) {
            mkdir($carpeta_destino, 0777, true);
        }
        
        $nombre_archivo = 'perfil_' . $id_usuario . '_' . time() . '.' . $extension;
        $ruta_completa = $carpeta_destino . $nombre_archivo;
        
        if (move_uploaded_file($temp, $ruta_completa)) {
            // Eliminar foto anterior
            if ($foto_anterior && file_exists($carpeta_destino . $foto_anterior)) {
                unlink($carpeta_destino . $foto_anterior);
            }
            
            $stmt = $conexion->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?");
            $stmt->bind_param("si", $nombre_archivo, $id_usuario);
            if ($stmt->execute()) {
                $_SESSION['usuario']['foto_perfil'] = $nombre_archivo;
                header('Location: ../perfil.php?mensaje=Foto de perfil actualizada correctamente&tipo=exito');
                exit;
            } else {
                header('Location: ../perfil.php?mensaje=Error al guardar la foto&tipo=error');
                exit;
            }
            $stmt->close();
        } else {
            header('Location: ../perfil.php?mensaje=Error al subir la imagen&tipo=error');
            exit;
        }
    } else {
        header('Location: ../perfil.php?mensaje=No se seleccionó ninguna imagen&tipo=error');
        exit;
    }
}

// =============================================
// PROCESAR ACTUALIZACIÓN DE DATOS PERSONALES
// =============================================
$id_usuario = $_SESSION['usuario']['id_usuario'];
$nombre = trim($_POST['nombre'] ?? '');
$apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
$apellido_materno = trim($_POST['apellido_materno'] ?? '');

if (empty($nombre) || empty($apellido_paterno)) {
    header('Location: ../perfil.php?mensaje=Nombre y apellido paterno son obligatorios&tipo=error');
    exit;
}

try {
    $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, apellido_paterno = ?, apellido_materno = ? WHERE id_usuario = ?");
    $stmt->bind_param("sssi", $nombre, $apellido_paterno, $apellido_materno, $id_usuario);
    $stmt->execute();
    $stmt->close();

    $_SESSION['usuario']['nombre'] = $nombre;
    $_SESSION['usuario']['apellido_paterno'] = $apellido_paterno;
    $_SESSION['usuario']['apellido_materno'] = $apellido_materno;

    header('Location: ../perfil.php?mensaje=Perfil actualizado correctamente&tipo=exito');
    exit;

} catch (Exception $e) {
    header('Location: ../perfil.php?mensaje=Error al actualizar: ' . $e->getMessage() . '&tipo=error');
    exit;
}
?>