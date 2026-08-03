<?php
session_start();

// Verificar que el usuario sea Docente
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar que se envió un archivo
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió ningún archivo']);
    exit;
}

$archivo = $_FILES['archivo'];
$nombre_original = basename($archivo['name']);
$extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

// Tipos permitidos
$tipos_permitidos = ['pdf', 'mp4', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'png'];

if (!in_array($extension, $tipos_permitidos)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de archivo no permitido']);
    exit;
}

// Limitar tamaño a 50MB
if ($archivo['size'] > 50 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'El archivo excede el tamaño máximo (50MB)']);
    exit;
}

// Crear carpeta si no existe
$carpeta = '../uploads/cursos/';
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0777, true);
}

// Generar nombre único
$nombre_archivo = uniqid() . '.' . $extension;
$ruta_destino = $carpeta . $nombre_archivo;

// Mover archivo
if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
    // Guardar en recursos_educativos (sin id_materia por ahora)
    require_once '../Conexion/conexion.php';
    
    $id_docente = $_SESSION['usuario']['id_usuario'];
    $tipo_recurso = $_POST['tipo_curso'] ?? 'Documento';
    $titulo = $_POST['titulo'] ?? $nombre_original;
    $descripcion = $_POST['descripcion'] ?? '';
    $compartido_tipo = $_POST['compartido_tipo'] ?? 'Curso';
    
    $stmt = $conexion->prepare("
        INSERT INTO recursos_educativos (
            titulo, 
            descripcion, 
            tipo, 
            url_recurso, 
            id_docente, 
            compartido_tipo,
            estado
        ) VALUES (?, ?, ?, ?, ?, ?, 'Activo')
    ");
    $stmt->bind_param("ssssis", $titulo, $descripcion, $tipo_recurso, $ruta_destino, $id_docente, $compartido_tipo);
    
    if ($stmt->execute()) {
        $id_recurso = $conexion->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'Archivo subido correctamente',
            'nombre' => $nombre_original,
            'url' => $ruta_destino,
            'id_recurso' => $id_recurso
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar en la base de datos: ' . $stmt->error]);
    }
    $stmt->close();
    
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al mover el archivo']);
}
?>