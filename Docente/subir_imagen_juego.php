<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

// Verificar que el usuario sea Docente
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar que se recibió un archivo
if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió ninguna imagen']);
    exit;
}

$archivo = $_FILES['imagen'];
$nombre_original = basename($archivo['name']);
$extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

// Tipos permitidos
$tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($extension, $tipos_permitidos, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de archivo no permitido. Usa JPG, PNG, GIF o WEBP.']);
    exit;
}

// Limitar tamaño a 5 MB
if ($archivo['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'La imagen excede el tamaño máximo (5MB)']);
    exit;
}

// =============================================
// CARPETA DE DESTINO
// =============================================

$carpeta_destino = __DIR__ . '/../uploads/juegos/';

// Crear carpeta si no existe
if (!is_dir($carpeta_destino)) {
    if (!mkdir($carpeta_destino, 0777, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo crear la carpeta de imágenes']);
        exit;
    }
}

// Verificar permisos
if (!is_writable($carpeta_destino)) {
    chmod($carpeta_destino, 0777);
    if (!is_writable($carpeta_destino)) {
        http_response_code(500);
        echo json_encode(['error' => 'No se tienen permisos de escritura en la carpeta']);
        exit;
    }
}

// =============================================
// GENERAR NOMBRE ÚNICO
// =============================================

$id_juego = isset($_POST['id_juego']) ? intval($_POST['id_juego']) : 0;
$id_docente = $_SESSION['usuario']['id_usuario'];
$timestamp = time();

// Nombre del archivo: juego_id_docente_timestamp.extension
$nombre_archivo = 'juego_' . $id_juego . '_' . $id_docente . '_' . $timestamp . '.' . $extension;

// Ruta física completa
$ruta_fisica = $carpeta_destino . $nombre_archivo;

// Ruta pública para la base de datos (SIN barra inicial)
$ruta_publica = 'uploads/juegos/' . $nombre_archivo;

// =============================================
// MOVER ARCHIVO
// =============================================

if (!move_uploaded_file($archivo['tmp_name'], $ruta_fisica)) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al mover la imagen al servidor']);
    exit;
}

// Verificar que el archivo existe
if (!file_exists($ruta_fisica)) {
    http_response_code(500);
    echo json_encode(['error' => 'La imagen no se guardó correctamente']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Imagen subida correctamente',
    'ruta' => $ruta_publica,
    'nombre' => $nombre_archivo,
    'tamano' => $archivo['size']
]);

exit;
?>