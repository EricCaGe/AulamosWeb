<?php
session_start();

// Configurar CORS y tipo de respuesta
header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Alumno') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../Conexion/conexion.php';

$id_alumno = $_SESSION['usuario']['id_usuario'];
$accion = $_POST['accion'] ?? '';

// =============================================
// FUNCIÓN PARA CREAR CARPETA SI NO EXISTE
// =============================================
function asegurarCarpeta($ruta) {
    if (!is_dir($ruta)) {
        if (!mkdir($ruta, 0777, true)) {
            return false;
        }
    }
    // Verificar permisos de escritura
    if (!is_writable($ruta)) {
        chmod($ruta, 0777);
    }
    return is_writable($ruta);
}

// =============================================
// ACCIÓN: SUBIR ARCHIVO
// =============================================
if ($accion === 'subir_archivo') {
    $id_actividad = isset($_POST['id_actividad']) ? intval($_POST['id_actividad']) : 0;
    $texto_entrega = isset($_POST['texto_entrega']) ? trim($_POST['texto_entrega']) : '';
    
    if ($id_actividad <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de actividad inválido']);
        exit;
    }
    
    // Verificar que el archivo fue subido
    if (!isset($_FILES['archivo_entrega']) || $_FILES['archivo_entrega']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']);
        exit;
    }
    
    $archivo = $_FILES['archivo_entrega'];
    $nombre_original = $archivo['name'];
    $tamano = $archivo['size'];
    $temp = $archivo['tmp_name'];
    
    // Validar extensiones
    $extensiones_permitidas = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip', 'rar'];
    $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
    
    if (!in_array($extension, $extensiones_permitidas)) {
        echo json_encode(['success' => false, 'message' => 'Formato de archivo no permitido']);
        exit;
    }
    
    // Validar tamaño (10MB)
    if ($tamano > 10485760) {
        echo json_encode(['success' => false, 'message' => 'El archivo no debe superar los 10MB']);
        exit;
    }
    
    // Crear carpeta de destino
    $carpeta_destino = __DIR__ . '/../uploads/entregas/';
    if (!asegurarCarpeta($carpeta_destino)) {
        echo json_encode(['success' => false, 'message' => 'Error: No se puede escribir en la carpeta de destino']);
        exit;
    }
    
    // Generar nombre único
    $nombre_archivo = 'entrega_' . $id_actividad . '_' . $id_alumno . '_' . time() . '.' . $extension;
    $ruta_completa = $carpeta_destino . $nombre_archivo;
    
    // Mover archivo
    if (!move_uploaded_file($temp, $ruta_completa)) {
        echo json_encode(['success' => false, 'message' => 'Error al mover el archivo']);
        exit;
    }
    
    // Verificar que el archivo existe
    if (!file_exists($ruta_completa)) {
        echo json_encode(['success' => false, 'message' => 'El archivo no se guardó correctamente']);
        exit;
    }
    
    // =============================================
    // GUARDAR EN BASE DE DATOS
    // =============================================
    
    $conexion->begin_transaction();
    
    try {
        // Obtener o crear actividad_estudiante
        $query_ae = "SELECT id_actividad_estudiante FROM actividad_estudiantes WHERE id_actividad = ? AND id_alumno = ?";
        $stmt_ae = $conexion->prepare($query_ae);
        $stmt_ae->bind_param("ii", $id_actividad, $id_alumno);
        $stmt_ae->execute();
        $result_ae = $stmt_ae->get_result();
        $row_ae = $result_ae->fetch_assoc();
        $stmt_ae->close();
        
        if ($row_ae) {
            $id_actividad_estudiante = $row_ae['id_actividad_estudiante'];
            // Actualizar estado
            $update_ae = $conexion->prepare("UPDATE actividad_estudiantes SET estado = 'Completada', fecha_finalizacion = NOW(), porcentaje_avance = 100.00 WHERE id_actividad_estudiante = ?");
            $update_ae->bind_param("i", $id_actividad_estudiante);
            $update_ae->execute();
            $update_ae->close();
        } else {
            // Crear nuevo registro
            $insert_ae = $conexion->prepare("INSERT INTO actividad_estudiantes (id_actividad, id_alumno, estado, fecha_inicio, fecha_finalizacion, porcentaje_avance) VALUES (?, ?, 'Completada', NOW(), NOW(), 100.00)");
            $insert_ae->bind_param("ii", $id_actividad, $id_alumno);
            $insert_ae->execute();
            $id_actividad_estudiante = $insert_ae->insert_id;
            $insert_ae->close();
        }
        
        // Verificar si ya existe entrega
        $query_entrega = "SELECT id_entrega FROM entregas WHERE id_actividad_estudiante = ?";
        $stmt_entrega = $conexion->prepare($query_entrega);
        $stmt_entrega->bind_param("i", $id_actividad_estudiante);
        $stmt_entrega->execute();
        $result_entrega = $stmt_entrega->get_result();
        $row_entrega = $result_entrega->fetch_assoc();
        $stmt_entrega->close();
        
        if ($row_entrega) {
            $id_entrega = $row_entrega['id_entrega'];
            // Actualizar entrega
            $update_entrega = $conexion->prepare("UPDATE entregas SET texto_entrega = ?, fecha_entrega = NOW(), estado = 'Entregada' WHERE id_entrega = ?");
            $update_entrega->bind_param("si", $texto_entrega, $id_entrega);
            $update_entrega->execute();
            $update_entrega->close();
        } else {
            // Crear nueva entrega
            $insert_entrega = $conexion->prepare("INSERT INTO entregas (id_actividad_estudiante, texto_entrega, fecha_entrega, estado) VALUES (?, ?, NOW(), 'Entregada')");
            $insert_entrega->bind_param("is", $id_actividad_estudiante, $texto_entrega);
            $insert_entrega->execute();
            $id_entrega = $insert_entrega->insert_id;
            $insert_entrega->close();
        }
        
        // Verificar si ya existe adjunto
        $query_adjunto = "SELECT id_adjunto FROM adjuntos WHERE entidad_tipo = 'Entrega' AND entidad_id = ?";
        $stmt_adjunto = $conexion->prepare($query_adjunto);
        $stmt_adjunto->bind_param("i", $id_entrega);
        $stmt_adjunto->execute();
        $result_adjunto = $stmt_adjunto->get_result();
        $row_adjunto = $result_adjunto->fetch_assoc();
        $stmt_adjunto->close();
        
        $url_relativa = '../uploads/entregas/' . $nombre_archivo;
        $tipo_mime = $archivo['type'] ?: 'application/octet-stream';
        
        if ($row_adjunto) {
            // Actualizar adjunto
            $update_adjunto = $conexion->prepare("UPDATE adjuntos SET nombre_archivo = ?, tipo_archivo = ?, url_archivo = ?, tamano_bytes = ?, fecha_subida = NOW() WHERE id_adjunto = ?");
            $update_adjunto->bind_param("sssii", $nombre_archivo, $tipo_mime, $url_relativa, $tamano, $row_adjunto['id_adjunto']);
            $update_adjunto->execute();
            $update_adjunto->close();
        } else {
            // Insertar adjunto
            $insert_adjunto = $conexion->prepare("INSERT INTO adjuntos (entidad_tipo, entidad_id, nombre_archivo, tipo_archivo, url_archivo, tamano_bytes, id_usuario) VALUES ('Entrega', ?, ?, ?, ?, ?, ?)");
            $insert_adjunto->bind_param("issssi", $id_entrega, $nombre_archivo, $tipo_mime, $url_relativa, $tamano, $id_alumno);
            $insert_adjunto->execute();
            $insert_adjunto->close();
        }
        
        $conexion->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Archivo subido y entrega guardada correctamente',
            'data' => [
                'id_entrega' => $id_entrega,
                'archivo' => $nombre_archivo,
                'url' => $url_relativa
            ]
        ]);
        
    } catch (Exception $e) {
        $conexion->rollback();
        // Eliminar archivo si hubo error
        if (file_exists($ruta_completa)) {
            unlink($ruta_completa);
        }
        echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos: ' . $e->getMessage()]);
    }
    
    $conexion->close();
    exit;
}

// =============================================
// ACCIÓN: GUARDAR SOLO TEXTO
// =============================================
if ($accion === 'guardar_texto') {
    $id_actividad = isset($_POST['id_actividad']) ? intval($_POST['id_actividad']) : 0;
    $texto_entrega = isset($_POST['texto_entrega']) ? trim($_POST['texto_entrega']) : '';
    
    if ($id_actividad <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de actividad inválido']);
        exit;
    }
    
    if (empty($texto_entrega)) {
        echo json_encode(['success' => false, 'message' => 'Debes escribir un comentario']);
        exit;
    }
    
    try {
        // Obtener o crear actividad_estudiante
        $query_ae = "SELECT id_actividad_estudiante FROM actividad_estudiantes WHERE id_actividad = ? AND id_alumno = ?";
        $stmt_ae = $conexion->prepare($query_ae);
        $stmt_ae->bind_param("ii", $id_actividad, $id_alumno);
        $stmt_ae->execute();
        $result_ae = $stmt_ae->get_result();
        $row_ae = $result_ae->fetch_assoc();
        $stmt_ae->close();
        
        if ($row_ae) {
            $id_actividad_estudiante = $row_ae['id_actividad_estudiante'];
            $update_ae = $conexion->prepare("UPDATE actividad_estudiantes SET estado = 'En_proceso', ultimo_acceso = NOW() WHERE id_actividad_estudiante = ?");
            $update_ae->bind_param("i", $id_actividad_estudiante);
            $update_ae->execute();
            $update_ae->close();
        } else {
            $insert_ae = $conexion->prepare("INSERT INTO actividad_estudiantes (id_actividad, id_alumno, estado, fecha_inicio) VALUES (?, ?, 'En_proceso', NOW())");
            $insert_ae->bind_param("ii", $id_actividad, $id_alumno);
            $insert_ae->execute();
            $id_actividad_estudiante = $insert_ae->insert_id;
            $insert_ae->close();
        }
        
        // Verificar si ya existe entrega
        $query_entrega = "SELECT id_entrega FROM entregas WHERE id_actividad_estudiante = ?";
        $stmt_entrega = $conexion->prepare($query_entrega);
        $stmt_entrega->bind_param("i", $id_actividad_estudiante);
        $stmt_entrega->execute();
        $result_entrega = $stmt_entrega->get_result();
        $row_entrega = $result_entrega->fetch_assoc();
        $stmt_entrega->close();
        
        if ($row_entrega) {
            $update_entrega = $conexion->prepare("UPDATE entregas SET texto_entrega = ?, fecha_entrega = NOW(), estado = 'Entregada' WHERE id_entrega = ?");
            $update_entrega->bind_param("si", $texto_entrega, $row_entrega['id_entrega']);
            $update_entrega->execute();
            $update_entrega->close();
        } else {
            $insert_entrega = $conexion->prepare("INSERT INTO entregas (id_actividad_estudiante, texto_entrega, fecha_entrega, estado) VALUES (?, ?, NOW(), 'Entregada')");
            $insert_entrega->bind_param("is", $id_actividad_estudiante, $texto_entrega);
            $insert_entrega->execute();
            $insert_entrega->close();
        }
        
        echo json_encode(['success' => true, 'message' => 'Comentario guardado correctamente']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
    }
    
    $conexion->close();
    exit;
}

// =============================================
// ACCIÓN: ELIMINAR ARCHIVO
// =============================================
if ($accion === 'eliminar_archivo') {
    $id_entrega = isset($_POST['id_entrega']) ? intval($_POST['id_entrega']) : 0;
    $id_actividad = isset($_POST['id_actividad']) ? intval($_POST['id_actividad']) : 0;
    
    if ($id_entrega <= 0 || $id_actividad <= 0) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }
    
    try {
        // Obtener información del adjunto
        $query_adjunto = "SELECT id_adjunto, url_archivo, nombre_archivo FROM adjuntos WHERE entidad_tipo = 'Entrega' AND entidad_id = ?";
        $stmt_adjunto = $conexion->prepare($query_adjunto);
        $stmt_adjunto->bind_param("i", $id_entrega);
        $stmt_adjunto->execute();
        $result_adjunto = $stmt_adjunto->get_result();
        $row_adjunto = $result_adjunto->fetch_assoc();
        $stmt_adjunto->close();
        
        if ($row_adjunto) {
            // Eliminar archivo físico
            $ruta_fisica = __DIR__ . '/../' . $row_adjunto['url_archivo'];
            if (file_exists($ruta_fisica)) {
                unlink($ruta_fisica);
            }
            
            // Eliminar registro de adjunto
            $delete_adjunto = $conexion->prepare("DELETE FROM adjuntos WHERE id_adjunto = ?");
            $delete_adjunto->bind_param("i", $row_adjunto['id_adjunto']);
            $delete_adjunto->execute();
            $delete_adjunto->close();
            
            // Actualizar entrega a solo texto
            $update_entrega = $conexion->prepare("UPDATE entregas SET estado = 'Entregada' WHERE id_entrega = ?");
            $update_entrega->bind_param("i", $id_entrega);
            $update_entrega->execute();
            $update_entrega->close();
            
            echo json_encode(['success' => true, 'message' => 'Archivo eliminado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el archivo']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()]);
    }
    
    $conexion->close();
    exit;
}

// Si no se reconoce la acción
echo json_encode(['success' => false, 'message' => 'Acción no válida']);
?>