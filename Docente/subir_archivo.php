<?php
session_start();

// Regenerar ID de sesión por seguridad
session_regenerate_id(true);

header('Content-Type: application/json; charset=utf-8');

// Verificar que el usuario sea Docente
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$id_docente = $_SESSION['usuario']['id_usuario'];

// Verificar que se recibió un archivo
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $error_msg = 'No se recibió ningún archivo. Error: ';
    switch ($_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $error_msg .= 'El archivo excede el tamaño máximo permitido.';
            break;
        case UPLOAD_ERR_PARTIAL:
            $error_msg .= 'El archivo se subió parcialmente.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $error_msg .= 'No se seleccionó ningún archivo.';
            break;
        default:
            $error_msg .= 'Error desconocido.';
    }
    echo json_encode(['success' => false, 'error' => $error_msg]);
    exit;
}

$archivo = $_FILES['archivo'];
$nombre_original = basename($archivo['name']);
$extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

// =====================================================
// VALIDAR TIPO DE ARCHIVO
// =====================================================
$tipos_permitidos = [
    'pdf', 'mp4', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'png', 'jpeg', 'gif', 'webp'
];

if (!in_array($extension, $tipos_permitidos, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido. Extensiones permitidas: ' . implode(', ', $tipos_permitidos)]);
    exit;
}

// Limitar tamaño a 50 MB
if ($archivo['size'] > 50 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'El archivo excede el tamaño máximo (50MB)'
    ]);
    exit;
}

// =====================================================
// VALIDAR DATOS DEL FORMULARIO
// =====================================================
// AULAMOS: detectar tipo real por extension
switch ($extension) {
    case 'mp4':
        $tipo_recurso = 'Video';
        break;
    case 'pdf':
        $tipo_recurso = 'PDF';
        break;
    case 'jpg':
    case 'jpeg':
    case 'png':
    case 'gif':
    case 'webp':
        $tipo_recurso = 'Imagen';
        break;
    case 'ppt':
    case 'pptx':
        $tipo_recurso = 'Presentación';
        break;
    default:
        $tipo_recurso = 'Documento';
        break;
}

$titulo = trim($_POST['titulo'] ?? $nombre_original);
$descripcion = trim($_POST['descripcion'] ?? '');

$tipos_recurso_permitidos = [
    'Video',
    'PDF',
    'Documento',
    'Imagen',
    'Presentación'
];
if (!in_array($tipo_recurso, $tipos_recurso_permitidos, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tipo de recurso no válido']);
    exit;
}

// =====================================================
// CREAR CARPETA SI NO EXISTE
// =====================================================
$carpeta_fisica = __DIR__ . '/../uploads/recursos/';

if (!is_dir($carpeta_fisica)) {
    if (!mkdir($carpeta_fisica, 0777, true)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'No se pudo crear la carpeta de recursos'
        ]);
        exit;
    }
}

// =====================================================
// GUARDAR ARCHIVO
// =====================================================
$nombre_archivo = uniqid() . '.' . $extension;
$ruta_fisica = $carpeta_fisica . $nombre_archivo;
$ruta_publica = '/uploads/recursos/' . $nombre_archivo;

if (!move_uploaded_file($archivo['tmp_name'], $ruta_fisica)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al mover el archivo. Verifica los permisos de la carpeta.'
    ]);
    exit;
}

// =====================================================
// GUARDAR EN BASE DE DATOS
// =====================================================
require_once '../Conexion/conexion.php';

$stmt = $conexion->prepare("
    INSERT INTO recursos_educativos (
        titulo,
        descripcion,
        tipo,
        url_recurso,
        id_docente,
        compartido_tipo,
        estado,
        fecha_publicacion
    )
    VALUES (?, ?, ?, ?, ?, 'Publico', 'Activo', NOW())
");

$stmt->bind_param(
    "ssssi",
    $titulo,
    $descripcion,
    $tipo_recurso,
    $ruta_publica,
    $id_docente
);

if ($stmt->execute()) {
    $id_recurso = $conexion->insert_id;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Archivo subido correctamente',
        'nombre' => $nombre_original,
        'url' => $ruta_publica,
        'id_recurso' => $id_recurso
    ]);

} else {
    // Si falla MySQL, eliminar el archivo para no dejar basura
    if (file_exists($ruta_fisica)) {
        unlink($ruta_fisica);
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al guardar en la base de datos: ' . $stmt->error
    ]);
}

$stmt->close();
$conexion->close();
?>