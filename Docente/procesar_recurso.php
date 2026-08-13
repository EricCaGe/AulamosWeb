<?php
session_start();

// =====================================================
// VERIFICAR SESIÓN DOCENTE
// =====================================================
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Docente') {
    header('Location: ../InicioSesion/login.php');
    exit;
}

require_once '../Conexion/conexion.php';

$id_docente = $_SESSION['usuario']['id_usuario'];

// =====================================================
// OBTENER DATOS DEL FORMULARIO
// =====================================================
$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$id_curso = intval($_POST['id_curso'] ?? 0);
$tipo_recurso = $_POST['tipo_curso'] ?? 'Documento';
$estado = $_POST['estado'] ?? 'Activo';
$compartido_tipo = $_POST['compartido_tipo'] ?? 'Curso';

// =====================================================
// VALIDACIONES
// =====================================================
$errores = [];

if (empty($titulo)) {
    $errores[] = "El título es obligatorio.";
}
if ($id_curso <= 0) {
    $errores[] = "Debes seleccionar un curso.";
}
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    $errores[] = "Debes seleccionar un archivo válido.";
}

if (!empty($errores)) {
    $_SESSION['mensaje'] = implode(" ", $errores);
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: crear_recurso.php');
    exit;
}

// =====================================================
// VERIFICAR QUE EL CURSO PERTENEZCA AL DOCENTE
// =====================================================
$stmt = $conexion->prepare("
    SELECT id_curso, id_materia 
    FROM cursos 
    WHERE id_curso = ? AND id_docente = ? AND estado = 'Activo'
");
$stmt->bind_param("ii", $id_curso, $id_docente);
$stmt->execute();
$curso = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$curso) {
    $_SESSION['mensaje'] = "El curso seleccionado no existe o no te pertenece.";
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: crear_recurso.php');
    exit;
}

// =====================================================
// PROCESAR ARCHIVO
// =====================================================
$archivo = $_FILES['archivo'];
$nombre_original = basename($archivo['name']);
$extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

// Tipos permitidos
$tipos_permitidos = ['pdf', 'mp4', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'png', 'jpeg', 'gif', 'webp'];

if (!in_array($extension, $tipos_permitidos, true)) {
    $_SESSION['mensaje'] = "Tipo de archivo no permitido.";
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: crear_recurso.php');
    exit;
}

// Limitar tamaño a 50 MB
if ($archivo['size'] > 50 * 1024 * 1024) {
    $_SESSION['mensaje'] = "El archivo excede el tamaño máximo (50MB).";
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: crear_recurso.php');
    exit;
}

// Crear carpeta si no existe
$carpeta_fisica = __DIR__ . '/../uploads/recursos/';
if (!is_dir($carpeta_fisica)) {
    mkdir($carpeta_fisica, 0777, true);
}

// Guardar archivo
$nombre_archivo = uniqid() . '.' . $extension;
$ruta_fisica = $carpeta_fisica . $nombre_archivo;
$ruta_publica = '/uploads/recursos/' . $nombre_archivo;

if (!move_uploaded_file($archivo['tmp_name'], $ruta_fisica)) {
    $_SESSION['mensaje'] = "Error al guardar el archivo.";
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: crear_recurso.php');
    exit;
}

// =====================================================
// GUARDAR EN BASE DE DATOS
// =====================================================
$sql = "
    INSERT INTO recursos_educativos (
        titulo,
        descripcion,
        tipo,
        url_recurso,
        id_docente,
        id_materia,
        id_curso,
        compartido_tipo,
        estado,
        fecha_publicacion
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "ssssiiiss",
    $titulo,
    $descripcion,
    $tipo_recurso,
    $ruta_publica,
    $id_docente,
    $curso['id_materia'],
    $id_curso,
    $compartido_tipo,
    $estado
);

if ($stmt->execute()) {
    $_SESSION['mensaje'] = "✅ Recurso publicado correctamente.";
    $_SESSION['tipo_mensaje'] = 'success';
} else {
    // Eliminar archivo si falla la BD
    if (file_exists($ruta_fisica)) {
        unlink($ruta_fisica);
    }
    $_SESSION['mensaje'] = "Error al guardar en la base de datos: " . $stmt->error;
    $_SESSION['tipo_mensaje'] = 'error';
}

$stmt->close();
$conexion->close();

header('Location: mis_recursos.php');
exit;
?>