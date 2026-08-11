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
    echo json_encode(['success' => false, 'error' => 'No se recibió ningún archivo']);
    exit;
}

$archivo = $_FILES['archivo'];
$nombre_original = basename($archivo['name']);
$extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

// =====================================================
// VALIDAR TIPO DE ARCHIVO
// =====================================================
$tipos_permitidos = [
    'pdf', 'mp4', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'png'
];

if (!in_array($extension, $tipos_permitidos, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']);
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
$tipo_recurso = $_POST['tipo_curso'] ?? 'Documento';
$titulo = trim($_POST['titulo'] ?? $nombre_original);
$descripcion = trim($_POST['descripcion'] ?? '');
$compartido_tipo = $_POST['compartido_tipo'] ?? 'Curso';

$tipos_recurso_permitidos = ['Video', 'PDF', 'Documento'];
if (!in_array($tipo_recurso, $tipos_recurso_permitidos, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tipo de recurso no válido']);
    exit;
}

// =====================================================
// CREAR CARPETA SI NO EXISTE
// =====================================================
$carpeta_fisica = 'C:/AulamosCom/aulamos-api/uploads/recursos/';

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
        'error' => 'Error al mover el archivo'
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
        estado
    )
    VALUES (?, ?, ?, ?, ?, ?, 'Activo')
");

$stmt->bind_param(
    "ssssis",
    $titulo,
    $descripcion,
    $tipo_recurso,
    $ruta_publica,
    $id_docente,
    $compartido_tipo
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